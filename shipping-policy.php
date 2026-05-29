<?php 
$page_title = "Shipping Policy";
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
            <p>At <strong><?php echo htmlspecialchars($settings['brand_name'] ?? 'our company'); ?></strong>, we are committed to delivering your favorite styles and outfits to your doorstep as quickly and efficiently as possible. This policy outlines everything you need to know about our shipping process.</p>

            <h3>1. Shipping Costs</h3>
            <p><strong>Free Shipping on All Orders:</strong> We are excited to offer <strong>FREE standard shipping on all orders</strong> across India. Shop your heart out without worrying about any extra shipping charges.</p>

            <h3>2. Order Processing Time</h3>
            <ul>
                <li>All orders are processed and dispatched from our warehouse within <strong>24 to 48 hours</strong> of being placed (excluding Sundays and public holidays).</li>
                <li>You will receive a confirmation email with tracking details as soon as your order is on its way.</li>
            </ul>

            <h3>3. Estimated Delivery Times</h3>
            <p>We partner with leading courier services to ensure your order reaches you safely and on time. The estimated delivery time depends on your location:</p>
            <ul>
                <li><strong>Metro Cities:</strong> For major metropolitan cities like Mumbai, Delhi, Bengaluru, Chennai, and Kolkata, you can expect your order to be delivered within <strong>2 to 4 business days</strong>.</li>
                <li><strong>Other Cities (Tier II & Tier III):</strong> For other locations across India, delivery typically takes between <strong>4 to 6 business days</strong>.</li>
                <li><strong>Remote Locations:</strong> For very remote areas, delivery may take up to <strong>7-9 business days</strong>.</li>
            </ul>
            <p class="text-muted"><small>Please note that these are estimated times. Actual delivery can be affected by weather conditions, logistical challenges, or other unforeseen circumstances.</small></p>

            <h3>4. Packaging</h3>
            <p>We take great care in packaging your products. All items are packed securely in discreet, tamper-proof packaging to ensure they reach you in perfect, wearable condition.</p>

            <h3>5. Order Tracking</h3>
            <p>Once your order is dispatched, you will receive an email and/or SMS with your tracking number and a link to the courier's website. You can use this to track your package's journey to your doorstep.</p>

            <h3>6. Shipping Restrictions</h3>
            <p>Currently, we only ship within India. We do not offer international shipping at this time.</p>

            <h3>7. Undelivered Packages</h3>
            <p>If our courier partner is unable to deliver the package after multiple attempts, it will be returned to us. If you would like us to re-dispatch the package, an additional shipping fee may be applicable.</p>

            <h3>8. Contact Us</h3>
            <p>If you have any questions or concerns about your order's shipment, please feel free to contact our customer support team:</p>
            <ul>
                <li><strong>Email:</strong> <?php echo htmlspecialchars($settings['contact_email'] ?? 'N/A'); ?></li>
                <li><strong>Phone:</strong> <?php echo htmlspecialchars($settings['contact_phone'] ?? 'N/A'); ?></li>
            </ul>
        </div>
    </div>
</main>

<?php include('footer.php'); ?>