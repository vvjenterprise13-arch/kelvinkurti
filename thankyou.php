<?php
// PHP Error Reporting (can be removed in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('database/connection.php');

// જો ઓર્ડરની વિગતો સેશનમાં ન હોય, તો હોમ પેજ પર મોકલો
if (!isset($_SESSION['order_id']) || !isset($_SESSION['final_amount']) || !isset($_SESSION['address'])) {
    header('Location: index.php'); // હોમ પેજ પર રીડાયરેક્ટ કરો
    exit();
}

// સેશનમાંથી ઓર્ડરની વિગતો મેળવો
$order_id = htmlspecialchars($_SESSION['order_id'] ?? 'N/A');
$final_amount = htmlspecialchars($_SESSION['final_amount'] ?? '0.00');
$address = $_SESSION['address'];

// સેશનમાંથી સાચી 'key' ('number' અને 'flat') નો ઉપયોગ કરીને સરનામાની વિગતો મેળવો
$name = htmlspecialchars($address['name'] ?? '');
$mobile = htmlspecialchars($address['number'] ?? '');
$house = htmlspecialchars($address['flat'] ?? '');
$area = htmlspecialchars($address['area'] ?? '');
$city = htmlspecialchars($address['city'] ?? '');
$state = htmlspecialchars($address['state'] ?? '');
$pincode = htmlspecialchars($address['pincode'] ?? '');

// સંપૂર્ણ સરનામું બનાવો
$address_parts = array_filter([$house, $area, $city, $state]);
$full_address = implode(', ', $address_parts);
if ($pincode) {
    $full_address .= ' - ' . $pincode;
}

// *** META PIXEL ID અને GOOGLE ADS ID/LABELS મેળવવા માટેનો કોડ અહીં ઉમેર્યો છે ***
$pixel_ids = []; // Array to store all available Meta pixel IDs
$google_ads_conversion_tags = []; // Array to store Google Ads Conversion Tags (ID and Label pairs)
$google_ads_configs = []; // To store unique Google Ads IDs for config calls

// Build the SELECT query dynamically to include all relevant columns
$select_cols = [];
for ($i = 1; $i <= 5; $i++) {
    $select_cols[] = 'meta_pixel_id_' . $i;
}
for ($i = 1; $i <= 4; $i++) {
    $select_cols[] = 'google_ads_id_' . $i;
    $select_cols[] = 'google_ads_purchase_label_' . $i;
}
// Ensure we don't try to select duplicate column names if for some reason they exist in the loops (unlikely but safe)
$select_query_cols = implode(', ', array_unique($select_cols));

$creds_result = mysqli_query($conn, "SELECT " . $select_query_cols . " FROM credentials LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    // Populate Meta Pixel IDs
    for ($i = 1; $i <= 5; $i++) {
        if (isset($fetch_creds['meta_pixel_id_' . $i]) && !empty($fetch_creds['meta_pixel_id_' . $i])) {
            $pixel_ids[] = $fetch_creds['meta_pixel_id_' . $i];
        }
    }

    // Populate Google Ads IDs and Labels
    for ($i = 1; $i <= 4; $i++) {
        $google_ads_id_key = 'google_ads_id_' . $i;
        $google_ads_label_key = 'google_ads_purchase_label_' . $i;

        if (isset($fetch_creds[$google_ads_id_key]) && !empty($fetch_creds[$google_ads_id_key])) {
            $current_ads_id = $fetch_creds[$google_ads_id_key];
            // Add to unique Google Ads IDs for gtag config calls (using key for uniqueness)
            $google_ads_configs[$current_ads_id] = true; 
            
            // If a corresponding purchase label exists, add it as a conversion tag
            if (isset($fetch_creds[$google_ads_label_key]) && !empty($fetch_creds[$google_ads_label_key])) {
                $google_ads_conversion_tags[] = [
                    'id' => $current_ads_id,
                    'label' => $fetch_creds[$google_ads_label_key]
                ];
            }
        }
    }
}

// Ensure final_amount is numeric for pixel events
$final_amount_numeric = (float)$final_amount;

// બધી માહિતી બતાવ્યા પછી, સેશનમાંથી ઓર્ડર સંબંધિત વિગતો દૂર કરો
unset($_SESSION['cart']);
unset($_SESSION['address']);
unset($_SESSION['final_amount']);
unset($_SESSION['order_id']);

?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <title>Order Placed Successfully</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <?php
    // --- META PIXEL PURCHASE EVENT ---
    // જો પિક્સેલ આઈડી ઉપલબ્ધ હોય તો જ કોડ રેન્ડર કરો
    if (!empty($pixel_ids) && is_numeric($final_amount_numeric)) {
        // રકમ (amount) ને એકવાર સેનિટાઇઝ કરો
        $sanitized_final_amount = htmlspecialchars($final_amount_numeric, ENT_QUOTES, 'UTF-8');

        foreach ($pixel_ids as $pixel_id) {
            $sanitized_pixel_id = htmlspecialchars($pixel_id, ENT_QUOTES, 'UTF-8');
            
            echo "<!-- Meta Pixel Code -->\n";
            echo "<script>\n";
            echo "!function(f,b,e,v,n,t,s)\n";
            echo "{if(f.fbq)return;n=f.fbq=function(){n.callMethod?\n";
            echo "n.callMethod.apply(n,arguments):n.queue.push(arguments)};\n";
            echo "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\n";
            echo "n.queue=[];t=b.createElement(e);t.async=!0;\n";
            echo "t.src=v;s=b.getElementsByTagName(e)[0];\n";
            echo "s.parentNode.insertBefore(t,s)}(window, document,'script',\n";
            echo "'https://connect.facebook.net/en_US/fbevents.js');\n";
            
            // પિક્સેલ શરૂ કરો
            echo "fbq('init', '" . $sanitized_pixel_id . "');\n";
            
            // પેજ વ્યૂ ટ્રેક કરો
            echo "fbq('track', 'PageView');\n";
            
            // *** આ સૌથી મહત્વપૂર્ણ ભાગ છે: Purchase ઇવેન્ટ ટ્રેક કરો ***
            echo "fbq('track', 'Purchase', {value: " . $sanitized_final_amount . ", currency: 'INR'});\n";
            
            echo "</script>\n";
            
            // noscript ટેગ પણ Purchase ઇવેન્ટ સાથે અપડેટ કરો
            echo '<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id=' . $sanitized_pixel_id . '&ev=Purchase&cd[value]=' . $sanitized_final_amount . '&cd[currency]=INR&noscript=1" /></noscript>' . "\n";
            echo "<!-- End Meta Pixel Code -->\n\n";
        }
    }
    ?>
    <style>
        body {
            background-color: #f1f2f4;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }
        .thank-you-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 15px;
        }
        .thank-you-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .success-icon {
            font-size: 60px;
            color: #28a745;
        }
        .thank-you-card h2 {
            font-size: 24px;
            font-weight: 600;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .thank-you-card p {
            color: #6c757d;
            font-size: 14px;
        }
        .order-details {
            border-top: 1px solid #e9ecef;
            border-bottom: 1px solid #e9ecef;
            margin: 25px 0;
            padding: 20px 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            gap: 15px;
        }
        .detail-row-label {
            color: #6c757d;
            flex-shrink: 0;
        }
        .detail-row-value {
            font-weight: 500;
            color: #212529;
        }
        .address-details {
            font-size: 13px;
            line-height: 1.6;
            word-break: break-word;
        }
        .continue-shopping-btn {
            background-color: #fb641b;
            color: #fff;
            border: none;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 500;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        /* ADDED STYLE FOR THE NOTE */
        .payment-note {
            margin-top: 25px;
            font-size: 14px;
            color: #dc3545; /* Red color */
            text-align: left;
            line-height: 1.6;
        }
        .payment-note b {
            color: #212529; /* Black color for "Note:" */
        }
    </style>
    <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17492944901"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-17492944901');
</script>

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-YQZKNNT3TY"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-YQZKNNT3TY'); // Existing Google Analytics config

  <?php
  // Dynamic Google Ads Configuration Tags
  if (!empty($google_ads_configs)) {
      foreach (array_keys($google_ads_configs) as $ads_id_config) {
          $ads_id_config = htmlspecialchars($ads_id_config, ENT_QUOTES, 'UTF-8');
          // Add config for each unique Google Ads ID fetched from the database
          echo "  gtag('config', '" . $ads_id_config . "');\n";
      }
  }

  // Dynamic Google Ads Conversion Tracking Events
  if (!empty($google_ads_conversion_tags) && is_numeric($final_amount_numeric)) {
      foreach ($google_ads_conversion_tags as $tag) {
          $ads_id = htmlspecialchars($tag['id'], ENT_QUOTES, 'UTF-8');
          $ads_label = htmlspecialchars($tag['label'], ENT_QUOTES, 'UTF-8');
          echo "  gtag('event', 'purchase', {\n";
          echo "    'send_to': '" . $ads_id . "/" . $ads_label . "',\n";
          echo "    'value': " . $final_amount_numeric . ",\n"; // Use numeric value for 'value'
          echo "    'currency': 'INR',\n";
          echo "    'transaction_id': '" . $order_id . "'\n"; // Added transaction_id for deduplication
          echo "  });\n";
      }
  }
  ?>
</script>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17500622506"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-17500622506');
</script>
</head>
<body>

<div class="thank-you-container">
    <div class="thank-you-card">
        <i class="bi bi-patch-check-fill success-icon"></i>
        <h2>Thank You For Your Order!</h2>
        <p>Your order has been placed successfully. order will be confirmed after payment is complete</p>

        <div class="order-details">
            <div class="detail-row">
                <span class="detail-row-label">Order ID:</span>
                <span class="detail-row-value"><?php echo $order_id; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-row-label">Total Amount:</span>
                <span class="detail-row-value">₹<?php echo number_format((float)$final_amount, 2); ?></span>
            </div>
             <div class="detail-row">
                <span class="detail-row-label">Delivery To:</span>
                <div class="detail-row-value text-end address-details">
                    <b><?php echo $name; ?></b>
                    <?php if ($mobile): ?>
                        (<?php echo $mobile; ?>)
                    <?php endif; ?>
                    <br>
                    <?php echo $full_address; ?>
                </div>
            </div>
        </div>

        <!-- NOTE FROM THE IMAGE ADDED HERE -->
        <div class="payment-note">
            <b>Note:</b><br>
            If your payment is not successful by you, then your order will be cancelled automatically!<br>
            Please make sure not to close any UPI app until payment is done!
        </div>

        <a href="index.php" class="continue-shopping-btn">Continue Shopping</a>
    </div>
</div>

<!-- Event snippet for Purchase (1) conversion page -->
<script>
  gtag('event', 'conversion', {
      'send_to': 'AW-17500622506/6NocCN63m44bEKqd-ZhB',
      'value': '<?php echo number_format((float)$final_amount, 2); ?>',
      'currency': 'INR',
      'transaction_id': '<?php echo $order_id; ?>'
  });
</script>

<!-- Event snippet for pur (1) conversion page -->
<script>
  gtag('event', 'conversion', {
      'send_to': 'AW-17492944901/UfjNCJm-vI4bEIXQpJVB',
     'value': '<?php echo number_format((float)$final_amount, 2); ?>',
      'currency': 'INR',
      'transaction_id': '<?php echo $order_id; ?>'
  });
</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>