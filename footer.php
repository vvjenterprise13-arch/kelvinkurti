<?php
$pwebsite = '';
$settings = [];

if (isset($conn)) {
    // Fetch website base URL
    $creds_result = mysqli_query($conn, "SELECT site FROM credentials LIMIT 1");
    if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
        $pwebsite = isset($fetch_creds['site']) ? rtrim($fetch_creds['site'], '/') : '';
    }

    // Fetch all settings
    $settings_result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
    if ($settings_result) {
        while ($row = mysqli_fetch_assoc($settings_result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}
?>
<footer class="bg-dark text-white mt-5">
    <div class="container py-4">
        <div class="row">
            <div class="col-md-4 mb-3 mb-md-0">
              <img src="<?php echo $pwebsite; ?>/assets/banner/kurtilogo.png" 
                   alt="<?php echo htmlspecialchars($settings['brand_name'] ?? 'Brand Logo'); ?>" 
                   class="footer-brand-logo" 
                   style="width:120px; height:auto; filter: brightness(0) invert(1);">

                <p class="text-white-50 mt-2">Your ultimate destination for trendy and traditional women's fashion.</p>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="about-us" class="text-white-50 text-decoration-none">About Us</a></li>
                    <li><a href="privacy-policy" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                    <li><a href="terms" class="text-white-50 text-decoration-none">Terms of Use</a></li>
                    <li><a href="shipping-policy" class="text-white-50 text-decoration-none">Shipping Policy</a></li>
                    <li><a href="return-policy" class="text-white-50 text-decoration-none">Return Policy</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Contact Information</h5>
                <p class="text-white-50 mb-1"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($settings['full_address'] ?? 'N/A'); ?></p>
                <p class="text-white-50 mb-1"><i class="bi bi-envelope-fill"></i> <?php echo htmlspecialchars($settings['contact_email'] ?? 'N/A'); ?></p>
                <p class="text-white-50 mb-1"><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($settings['contact_phone'] ?? 'N/A'); ?></p>
            </div>
        </div>
        <div class="text-center text-white-50 pt-3 mt-3 border-top border-secondary">
            &copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($settings['brand_name'] ?? 'Company'); ?>. All Rights Reserved.
        </div>
    </div>
</footer>
</body>
</html>