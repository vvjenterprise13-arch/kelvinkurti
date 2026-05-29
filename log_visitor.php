<?php
// આ ફાઈલ AJAX દ્વારા કોલ થશે, તેથી તે પેજ લોડને બ્લોક કરશે નહીં.
include('database/connection.php');

// isMobileDevice ફંક્શનની વ્યાખ્યા અહીં પણ જરૂરી છે.
function isMobileDevice() {
    if (!isset($_SERVER["HTTP_USER_AGENT"])) return false;
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

// જો ડેટાબેઝ કનેક્શન સફળ થાય તો જ આગળ વધો
if ($conn) {
    // 1. વિઝિટરનું IP એડ્રેસ મેળવો
    $visitor_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';

    // 2. ફક્ત જો IP એડ્રેસ ઉપલબ્ધ હોય તો જ લોગ કરો.
    if ($visitor_ip !== 'UNKNOWN') {
        
        // 3. Geo-IP API થી લોકેશનની વિગતો મેળવો
        $api_url = "http://ip-api.com/json/{$visitor_ip}?fields=status,country,city,regionName,isp";
        $geo_data_json = @file_get_contents($api_url);
        $geo_data = json_decode($geo_data_json, true);

        // 4. API માંથી મળેલ ડેટાને વેરીએબલમાં સોંપો
        if ($geo_data && $geo_data['status'] === 'success') {
            $isp        = $geo_data['isp'] ?? 'N/A';
            $country    = $geo_data['country'] ?? 'N/A';
            $city       = $geo_data['city'] ?? 'N/A';
            $regionname = $geo_data['regionName'] ?? 'N/A';
        } else {
            $isp = $country = $city = $regionname = 'N/A';
        }

        // 5. અન્ય વિગતો મેળવો
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        $device = isMobileDevice() ? 'Mobile' : 'Desktop';
        $redirected = 0; // રીડાયરેક્શન નથી, તેથી હંમેશા 0
        $created_at = date('Y-m-d H:i:s');

        // 6. ડેટાબેઝમાં ડેટા દાખલ કરવા માટે SQL સ્ટેટમેન્ટ તૈયાર કરો
        $sql = "INSERT INTO visitors_log (ip, isp, country, city, regionname, device, user_agent, redirected, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sssssssis", 
                $visitor_ip, $isp, $country, $city, $regionname, 
                $device, $user_agent, $redirected, $created_at
            );
            $stmt->execute();
            $stmt->close();
            
            // સફળતાનો પ્રતિસાદ
            http_response_code(200);
            echo "Visitor logged.";
        } else {
            // ભૂલનો પ્રતિસાદ
            http_response_code(500);
            echo "Failed to prepare statement.";
        }
    } else {
        // જો કોઈ કારણોસર IP ન મળે તો
        http_response_code(400);
        echo "IP address is unknown.";
    }

    $conn->close();

} else {
    http_response_code(503);
    echo "Database connection failed.";
}
?>