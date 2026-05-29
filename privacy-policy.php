<?php 
$page_title = "Privacy Policy";
include('header.php'); 
?>

<main class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>
        </ol>
    </nav>
    
    <div class="policy-container">
        <div class="policy-header">
            <h2><?php echo $page_title; ?></h2>
        </div>
        <div class="policy-body">
            <p><strong>Last Updated:</strong> June 13, 2025</p>
            <p>
                <strong><?php echo htmlspecialchars($settings['brand_name'] ?? 'Our Brand'); ?></strong> ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website.
            </p>

            <h3 class="mt-4">Information We Collect</h3>
            <p>We may collect personal information in a variety of ways, including:</p>
            <ul>
                <li><strong>Personal Data:</strong> When you register, place an order, or subscribe to our newsletter, you may provide us with personally identifiable information, such as your name, shipping address, email address, and phone number.</li>
                <li><strong>Payment Data:</strong> We collect data necessary to process your payment if you make a purchase, such as your payment instrument number (like a credit card number). All payment data is stored by our secure payment processor.</li>
                <li><strong>Usage Data:</strong> We may automatically collect information about how you access and use the website, such as your IP address, browser type, and pages you've viewed.</li>
            </ul>

            <h3 class="mt-4">How We Use Your Information</h3>
            <p>We use the information we collect for various purposes, including to:</p>
            <ul>
                <li>Process and manage your orders, payments, and returns.</li>
                <li>Create and manage your account with us.</li>
                <li>Communicate with you about your order, new products, special offers, and promotions.</li>
                <li>Personalize and improve your shopping experience.</li>
                <li>Monitor and analyze usage and trends to improve our website's functionality.</li>
            </ul>

            <h3 class="mt-4">Sharing Your Information</h3>
            <p>We do not sell your personal information. We may share your information with trusted third-party service providers who perform services for us, such as:</p>
            <ul>
                <li>Payment processing</li>
                <li>Order fulfillment and shipping</li>
                <li>Email delivery services</li>
            </ul>

            <h3 class="mt-4">Security of Your Information</h3>
            <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that no security measures are perfect or impenetrable.</p>
            
            <h3 class="mt-4">Your Choices</h3>
            <p>You have the right to review or update your personal information through your account settings. You can also opt-out of receiving promotional emails from us at any time by following the unsubscribe link in the email.</p>

            <h3 class="mt-4">Contact Us</h3>
            <p>If you have any questions or comments about this Privacy Policy, please contact us:</p>
            <p>
                <strong>Email:</strong> <?php echo htmlspecialchars($settings['contact_email'] ?? 'N/A'); ?><br>
                <strong>Address:</strong> <?php echo htmlspecialchars($settings['full_address'] ?? 'N/A'); ?>
            </p>
        </div>
    </div>
</main>

<?php include('footer.php'); ?>