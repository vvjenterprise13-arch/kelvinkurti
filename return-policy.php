<?php 
$page_title = "Return & Exchange Policy";
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
            <p>At <strong><?php echo htmlspecialchars($settings['brand_name'] ?? 'our company'); ?></strong>, we strive to ensure you love every item you purchase. We understand that finding the perfect fit and style is important, and we've made our return and exchange process simple and customer-friendly.</p>

<h3>1. 7-Day Return & Exchange Window</h3>
<p>You can initiate a return or exchange request within <strong>7 days</strong> from the date of delivery. Any request initiated after this period will not be eligible.</p>

<h3>2. Conditions for a Valid Return/Exchange</h3>
<p>To ensure your request is processed smoothly, please make sure the following conditions are met:</p>
<ul>
    <li><strong>Eligible Reasons:</strong> Returns and exchanges are accepted for the following reasons:
        <ul>
            <li>You received a damaged or defective product.</li>
            <li>You received an incorrect item (wrong size, color, or style).</li>
            <li>The product does not fit you correctly (size issue).</li>
            <li>You are not satisfied with the product's quality or look.</li>
        </ul>
    </li>
    <li><strong>Item Condition:</strong> The product must be in its original, unworn, unwashed, and unused condition.</li>
    <li><strong>Original Tags & Packaging:</strong> All original tags, labels, and packaging must be intact and attached to the product.</li>
    <li><strong>Proof for Damaged/Incorrect Items:</strong> For damaged or incorrect items, providing a clear photo helps us resolve your issue faster.</li>
</ul>

<h3>3. Non-Returnable Items</h3>
<p>The following items are not eligible for return or exchange:</p>
<ul>
    <li>Items that have been washed, worn, altered, or have stains and odors.</li>
    <li>Items returned without their original tags.</li>
    <li>Items sold during a "Final Sale" or "Clearance" event.</li>
    <li>Return requests made after the 7-day window has expired.</li>
</ul>

<h3>4. How to Initiate a Return or Exchange</h3>
<p>To initiate a return or exchange, please follow these steps:</p>
<ol>
    <li><strong>Contact Us:</strong> Email our customer support team at <strong><?php echo htmlspecialchars($settings['contact_email'] ?? 'N/A'); ?></strong> or call us at <strong><?php echo htmlspecialchars($settings['contact_phone'] ?? 'N/A'); ?></strong> within 7 days of receiving your order.</li>
    <li><strong>Provide Details:</strong> Please include your Order ID and the reason for the return/exchange (e.g., size issue, damaged item, etc.). For exchanges, please mention the size you would like to receive.</li>
    <li><strong>Await Confirmation:</strong> Our team will review your request and, upon approval, will provide you with instructions for the return shipment.</li>
</ol>

<h3>5. Refund & Exchange Process</h3>
<p>Once we receive the returned product and it passes our quality check, we will process your request as follows:</p>
<ul>
    <li><strong>Refunds:</strong> If you are eligible for a refund, the amount will be credited back to your original method of payment within <strong>7-10 business days</strong>.</li>
    <li><strong>Exchanges:</strong> We offer size exchanges for the same product, subject to availability. If the desired size is not in stock, we can offer a refund or store credit. The exchange item will be dispatched once the original item is received and inspected.</li>
</ul>
<p>Your satisfaction is our priority. If you have any questions, please don't hesitate to reach out to our customer support team.</p>
        </div>
    </div>
</main>

<?php include('footer.php'); ?>