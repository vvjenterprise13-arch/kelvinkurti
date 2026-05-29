<?php
$page_title = "Contact Us";
include('header.php'); 
$message_sent = false;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // PHP email sending logic would go here.
    $message_sent = true;
}
?>
<main class="container my-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo $page_title; ?></li>
        </ol>
    </nav>
    
    <div class="policy-container">
        <div class="policy-header text-center">
            <h2>Get In Touch</h2>
        </div>
        <div class="policy-body">
            <?php if ($message_sent): ?>
                <div class="alert alert-success">Thank you! Your message has been sent.</div>
            <?php endif; ?>
            <div class="row">
                <div class="col-lg-7 mb-4 mb-lg-0">
                    <form action="contact-us.php" method="post">
                        <div class="mb-3"><label class="form-label">Full Name</label><input type="text" class="form-control" name="name" required></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" required></div>
                        <div class="mb-3"><label class="form-label">Message</label><textarea class="form-control" name="message" rows="5" required></textarea></div>
                        <button type="submit" class="btn btn-primary w-100">Send Message</button>
                    </form>
                </div>
                <div class="col-lg-5">
                    <h5>Contact Information</h5>
                    <hr>
                    <p><strong><i class="bi bi-geo-alt-fill"></i> Address:</strong><br> <?php echo htmlspecialchars($settings['full_address'] ?? 'N/A'); ?></p>
                    <p><strong><i class="bi bi-envelope-fill"></i> Email:</strong><br> <?php echo htmlspecialchars($settings['contact_email'] ?? 'N/A'); ?></p>
                    <p><strong><i class="bi bi-telephone-fill"></i> Phone:</strong><br> <?php echo htmlspecialchars($settings['contact_phone'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>
</main>
<?php include('footer.php'); ?>