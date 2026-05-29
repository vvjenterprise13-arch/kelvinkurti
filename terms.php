<?php 
$page_title = "Terms of Use";
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
            <p><strong>Last Updated:</strong> Feb 21, 2025</p>
            <p>Welcome to <strong><?php echo htmlspecialchars($settings['brand_name'] ?? 'Our Brand'); ?></strong>. Please read these Terms of Use ("Terms", "Terms of Use") carefully before using our website (the "Service") operated by us.</p>
            
            <h3 class="mt-4">1. Agreement to Terms</h3>
            <p>By accessing or using our Service, you agree to be bound by these Terms. If you disagree with any part of the terms, then you may not access the Service. Your access to and use of the Service is conditioned on your acceptance of and compliance with these Terms.</p>

            <h3 class="mt-4">2. User Accounts</h3>
            <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password that you use to access the Service and for any activities or actions under your password.</p>

            <h3 class="mt-4">3. Products, Pricing, and Availability</h3>
            <p>We make every effort to display as accurately as possible the colors and images of our products. We cannot guarantee that your computer monitor's display of any color will be accurate.</p>
            <p>All descriptions of products or product pricing are subject to change at any time without notice, at our sole discretion. We reserve the right to discontinue any product at any time. We do not warrant that the quality of any products purchased by you will meet your expectations.</p>

            <h3 class="mt-4">4. Orders and Billing Information</h3>
            <p>We reserve the right to refuse or cancel any order you place with us. We may, in our sole discretion, limit or cancel quantities purchased per person or per order. In the event that we make a change to or cancel an order, we may attempt to notify you by contacting the e-mail and/or billing address/phone number provided at the time the order was made.</p>
            <p>You agree to provide current, complete, and accurate purchase and account information for all purchases made at our store.</p>
            
            <h3 class="mt-4">5. Intellectual Property</h3>
            <p>The Service and its original content, features, and functionality are and will remain the exclusive property of <strong><?php echo htmlspecialchars($settings['brand_name'] ?? 'Our Brand'); ?></strong> and its licensors. Our trademarks and designs may not be used in connection with any product or service without our prior written consent.</p>

            <h3 class="mt-4">6. Limitation of Liability</h3>
            <p>In no case shall <strong><?php echo htmlspecialchars($settings['brand_name'] ?? 'Our Brand'); ?></strong>, our directors, employees, or affiliates be liable for any injury, loss, claim, or any direct or indirect damages of any kind, arising from your use of the service or any products procured using the service.</p>

            <h3 class="mt-4">7. Governing Law</h3>
            <p>These Terms shall be governed and construed in accordance with the laws of India, without regard to its conflict of law provisions.</p>
            
            <h3 class="mt-4">8. Changes to Terms</h3>
            <p>We reserve the right, at our sole discretion, to modify or replace these Terms at any time. We will notify you of any changes by posting the new Terms of Use on this page. By continuing to access or use our Service after those revisions become effective, you agree to be bound by the revised terms.</p>

            <h3 class="mt-4">9. Contact Us</h3>
            <p>If you have any questions about these Terms, please contact us at:</p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($settings['contact_email'] ?? 'N/A'); ?></p>
        </div>
    </div>
</main>

<?php include('footer.php'); ?>