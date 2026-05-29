<?php
// હેડરમાં ડેટાબેઝ કનેક્શન અને સેટિંગ્સ લોડ થઈ જશે
// તેથી પેજનો ટાઇટલ અહીં સેટ કરવો
$page_title = "Policy"; // ડિફોલ્ટ ટાઇટલ
include('header.php');

// URL માંથી પેજ સ્લગ મેળવો
$page_slug = isset($_GET['page']) ? $_GET['page'] : '';

if (empty($page_slug)) {
    echo "<div class='container my-5'><div class='alert alert-danger'>Invalid page request.</div></div>";
} else {
    // SQL ઇન્જેક્શનથી બચવા માટે પ્રિપેર્ડ સ્ટેટમેન્ટનો ઉપયોગ કરો
    $stmt = $conn->prepare("SELECT title, content FROM policies WHERE page_slug = ?");
    $stmt->bind_param("s", $page_slug);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $policy = $result->fetch_assoc();
        $page_title = $policy['title']; // ડાયનેમિક ટાઇટલ અહીં અપડેટ કરો
        $page_content = $policy['content'];

        // પ્લેસહોલ્ડરને સેટિંગ્સમાંથી આવેલી કિંમતો સાથે બદલો
        foreach ($settings as $key => $value) {
            $page_content = str_replace('{{' . $key . '}}', htmlspecialchars($value), $page_content);
        }
        // ડાયનેમિક તારીખ માટે પ્લેસહોલ્ડર બદલો
        $page_content = str_replace('{current_date}', date("F j, Y"), $page_content);
        
        ?>
        <div class="container my-5">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($page_title); ?></li>
                </ol>
            </nav>
            
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h2><?php echo htmlspecialchars($page_title); ?></h2>
                </div>
                <div class="card-body" style="line-height: 1.7;">
                    <?php echo $page_content; ?>
                </div>
            </div>
        </div>
        <?php
    } else {
        // જો પેજ ન મળે તો 404 સંદેશ બતાવો
        $page_title = "Page Not Found";
        echo "<div class='container my-5'><div class='alert alert-warning'><h1>404 Not Found</h1><p>The page you requested could not be found.</p></div></div>";
    }
    $stmt->close();
}

// ફૂટર શામેલ કરો
include('footer.php');
?>
<script>
    // JS નો ઉપયોગ કરીને બ્રાઉઝરના ટેબમાં ટાઇટલ અપડેટ કરો
    document.title = "<?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars($settings['company_name'] ?? 'DryNuts'); ?>";
</script>