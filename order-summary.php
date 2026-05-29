<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
ob_start();

include('database/connection.php');

function isMobileDevice() {
    if (!isset($_SERVER["HTTP_USER_AGENT"])) return false;
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

function render_compact_product_carousel($title, $subtitle, $products_result, $pwebsite, $is_mobile_flag) {
    if ($products_result && $products_result->num_rows > 0) {
?>
        <section class="card-section">
            <div class="compact-section-header">
                <h5 class="fw-bold"><?php echo htmlspecialchars($title); ?></h5>
                <p class="text-muted small mb-3"><?php echo htmlspecialchars($subtitle); ?></p>
            </div>
            <div class="compact-carousel-container">
                <div class="compact-carousel">
                    <?php while ($item = $products_result->fetch_assoc()): 
                        $display_total = (float)$item['total'];
                        $display_mrp = (float)$item['price'];
                    ?>
                        <div class="compact-product-card">
                            <a href="singlepageview?pid=<?php echo $item['id']; ?>" class="product-link">
                                <div class="compact-image-wrapper"><img src="<?php echo $pwebsite . '/assets/uploads/' . htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="product-image"></div>
                                <div class="compact-info-wrapper">
                                    <p class="product-name"><?php echo htmlspecialchars($item['name']); ?></p>
                                    <div class="price-line">
                                        <span class="fw-bold">₹<?php echo number_format($display_total); ?></span>
                                        <del class="ms-2 text-muted small">₹<?php echo number_format($display_mrp); ?></del>
                                    </div>
                                </div>
                            </a>
                            <a href="add_to_cart?pid=<?php echo $item['id']; ?>" class="compact-add-to-cart-btn">Add to Cart</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>
<?php
    }
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
}
if (!isset($_SESSION['address'])) {
    header('Location: address.php');
    exit();
}

$pwebsite = '';
$form_action_url = '';
$is_payment_configured = false;

$creds_result = mysqli_query($conn, "SELECT site, pgsite, gateway FROM credentials WHERE id = 1 LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $pwebsite = isset($fetch_creds['site']) ? rtrim($fetch_creds['site'], '/') : '';
    $active_gateway = $fetch_creds['gateway'] ?? '';
    $base_pg_url = isset($fetch_creds['pgsite']) ? rtrim($fetch_creds['pgsite'], '/') : '';

    if (!empty($active_gateway) && !empty($base_pg_url)) {
        $safe_gateway_name = preg_replace("/[^a-zA-Z0-9]+/", "", $active_gateway);
        $form_action_url = $base_pg_url . '/' . $safe_gateway_name . '.php';
        $is_payment_configured = true;
    }
}

$address = $_SESSION['address'];
$cart_items = $_SESSION['cart'];
$product_ids = array_keys($cart_items);
$id_string = implode(',', array_map('intval', $product_ids));

$products_from_db = [];
if (!empty($id_string)) {
    $sql = "SELECT id, name, total, price, discount, image, category FROM products WHERE id IN ($id_string)";
    $result = mysqli_query($conn, $sql);
    
    $is_mobile = isMobileDevice();

    while ($item = mysqli_fetch_assoc($result)) {
        $products_from_db[$item['id']] = $item;
    }
}

$active_offer = 'b2g1';
$total_quantity = array_sum(array_column($cart_items, 'qty'));

$total_mrp = 0;
$final_total = 0;
$free_items_count = 0;
$total_selling_price_before_offer = 0;
$expanded_cart_for_offer = [];

foreach ($cart_items as $pid => $item_data) {
    if (isset($products_from_db[$pid])) {
        $quantity = $item_data['qty'];
        for ($i = 0; $i < $quantity; $i++) {
            $expanded_cart_for_offer[] = $products_from_db[$pid];
        }
    }
}

if ($active_offer == 'b2g1') {
    $free_items_count = floor($total_quantity / 3);
}

if ($free_items_count > 0) {
    usort($expanded_cart_for_offer, function($a, $b) {
        return (float)$a['total'] <=> (float)$b['total'];
    });
    for ($i = 0; $i < $free_items_count; $i++) {
        if(isset($expanded_cart_for_offer[$i])) {
            $expanded_cart_for_offer[$i]['is_free'] = true;
        }
    }
}

$processed_cart = [];
foreach ($expanded_cart_for_offer as $item) {
    $pid = $item['id'];
    if (!isset($processed_cart[$pid])) {
        $processed_cart[$pid] = $item;
        $processed_cart[$pid]['quantity'] = 0;
        $processed_cart[$pid]['free_quantity'] = 0;
    }
    $processed_cart[$pid]['quantity']++;
    if (isset($item['is_free']) && $item['is_free']) {
        $processed_cart[$pid]['free_quantity']++;
    }
}

foreach ($processed_cart as $pid => $product) {
    $payable_quantity = $product['quantity'] - $product['free_quantity'];
    $total_mrp += (float)$product['price'] * $product['quantity'];
    $total_selling_price_before_offer += (float)$product['total'] * $product['quantity'];
    $final_total += (float)$product['total'] * $payable_quantity;
}

$total_item_discount = $total_mrp - $total_selling_price_before_offer;
$offer_discount = $total_selling_price_before_offer - $final_total;
$coupon_discount = 20;
$protect_fee = 20;
$final_amount = $final_total - $coupon_discount + $protect_fee;
$total_savings = $total_item_discount + $offer_discount + $coupon_discount;

$_SESSION['final_amount'] = $final_amount;

$recommended_products_result = null;
if (!empty($id_string)) {
    $rec_query = "SELECT id, name, total, price, image FROM products WHERE id NOT IN ($id_string) ORDER BY RAND() LIMIT 8";
    $recommended_products_result = mysqli_query($conn, $rec_query);
}
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
      <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-07LLVZLYY1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-07LLVZLYY1');
</script>
    <title>Order Summary</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
   <style>
    body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; font-size: 13px; }
    .main-content { background-color: #fff; }
    .card-section { border-bottom: 8px solid #f1f2f4; padding: 12px 16px; background: #fff; }
    .page-header { background-color: #d81b60; padding: 12px 16px; display: flex; align-items: center; gap: 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
    .header-title { font-size: 17px; color: #fff; font-weight: 500; margin: 0; }
    .progress-stepper { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; }
    .step { display: flex; flex-direction: column; align-items: center; position: relative; flex-grow: 1; text-align: center; }
    .step-circle { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; margin-bottom: 5px; border: 1.5px solid #dbdbdb; color: #878787; background-color: #fff; }
    .step-label { font-size: 11px; color: #878787; }
    .step.active .step-circle { color: #2874f0; border-color: #2874f0; }
    .step.active .step-label { color: #212121; font-weight: 500; }
    .step.completed .step-circle { background-color: #2874f0; color: #fff; border-color: #2874f0; font-size: 16px; line-height: 1; }
    .step.completed .step-label { color: #212121; }
    .step:not(:last-child)::after { content: ''; position: absolute; top: 11px; left: 50%; width: 100%; height: 1.5px; background-color: #dbdbdb; z-index: -1; transform: translateX(12px); }
    .address-block .change-btn { border: 1px solid #2874f0; color: #2874f0; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 500; text-decoration: none; }
    .address-type-tag { background-color: #f0f2f5; color: #565656; font-size: 10px; padding: 2px 6px; border-radius: 2px; font-weight: 500; margin-left: 8px; }
    .product-card { display: flex; gap: 16px; }
    .product-name { font-size: 13px; font-weight: 500; line-height: 1.4; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; min-height: 36px; }
    .price-details-card { border: 1px solid #e0e0e0; border-radius: 8px; }
    .price-details-row { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 13px; }
    .total-amount-row { font-weight: bold; border-top: 1px dashed #e0e0e0; padding-top: 16px; margin-top: 8px; }
    .savings-banner { background-color: #e4f8e8; color: #388e3c; font-weight: 500; padding: 12px; border-radius: 8px; }
    .page-footer { background: #fff !important; border-top: 1px solid #e0e0e0 !important; position: fixed; bottom: 0; left: 0; right: 0; padding: 10px 16px; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); z-index: 101; }
    .footer-price { font-size: 17px; font-weight: bold; }
    .continue-btn { background-color: #d81b60; color: #fff; border: none; padding: 12px; font-size: 15px; font-weight: 500; border-radius: 4px; }
    .continue-btn:disabled { background-color: #e0e0e0; color: #878787; cursor: not-allowed; }
    .actions-container { display: flex; align-items: center; gap: 16px; }
    .remove-link { font-weight: 500; text-transform: uppercase; color: #dc3545; text-decoration: none; font-size: 12px; }
    .address-block p { word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
    .compact-carousel-container { position: relative; }
    .compact-carousel { display: flex; gap: 12px; overflow-x: auto; padding: 10px 0; scrollbar-width: none; scroll-behavior: smooth; }
    .compact-carousel::-webkit-scrollbar { display: none; }
    .compact-product-card { flex: 0 0 140px; border: 1px solid #e0e0e0; border-radius: 8px; display: flex; flex-direction: column; background-color: #fff; }
    .compact-product-card .product-link { text-decoration: none; color: #212121; flex-grow: 1; display: flex; flex-direction: column; }
    .compact-image-wrapper { height: 120px; padding: 10px; display: flex; align-items: center; justify-content: center; }
    .compact-image-wrapper img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .compact-info-wrapper { padding: 0 10px 10px 10px; }
    .compact-info-wrapper .product-name { font-size: 12px; font-weight: 500; line-height: 1.4; height: 34px; overflow: hidden; }
    .compact-info-wrapper .price-line { font-size: 13px; }
    .compact-add-to-cart-btn { display: block; text-align: center; padding: 6px; margin: 0 10px 10px 10px; background-color: #f0f5ff; color: #2874f0; border: 1px solid #d9e7ff; border-radius: 4px; font-weight: 500; font-size: 13px; text-decoration: none; }
</style>
</head>
<body style="padding-bottom: 90px;">

    <header class="page-header">
       <a href="cart.php" class="text-dark">
    <i class="bi bi-arrow-left fs-4" style="color: white;"></i>
</a>

        <h4 class="header-title">Order Summary</h4>
    </header>

    <main class="main-content">
        <div class="progress-stepper">
            <div class="step completed"><div class="step-circle">✓</div><div class="step-label">Address</div></div>
            <div class="step active"><div class="step-circle">2</div><div class="step-label">Order Summary</div></div>
            <div class="step"><div class="step-circle">3</div><div class="step-label">Payment</div></div>
        </div>
        <div class="card-section address-block">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 fw-bold">Deliver to:</h6><a href="address.php" class="change-btn">Change</a>
            </div>
            <p class="mb-1 fw-bold"><?php echo htmlspecialchars($address['name'] ?? ''); ?> <span class="address-type-tag"><?php echo strtoupper(htmlspecialchars($address['address_type'] ?? '')); ?></span></p>
            <p class="text-muted small mb-1"><?php echo htmlspecialchars($address['flat'] ?? '') . ', ' . htmlspecialchars($address['area'] ?? '') . ', ' . htmlspecialchars($address['city'] ?? ''); ?></p>
            <p class="text-muted small mb-0"><?php echo htmlspecialchars($address['number'] ?? ''); ?></p>
        </div>
        <div class="card-section">
            <?php foreach ($processed_cart as $pid => $product): ?>
            <div class="product-card mb-4">
                <img src="<?php echo htmlspecialchars($pwebsite); ?>/assets/uploads/<?php echo htmlspecialchars($product['image']); ?>" style="width: 100px; height: 100px; object-fit: contain;" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="product-info flex-grow-1">
                    <p class="product-name mb-2"><?php echo htmlspecialchars($product['name']); ?></p>
                    <div class="price-line mb-2">
                        <span class="fw-bold fs-6">₹<?php echo number_format((float)$product['total']); ?></span>
                        <del class="text-muted small ms-2">₹<?php echo number_format((float)$product['price']); ?></del>
                        <span class="text-success fw-bold small ms-2"><?php echo $product['discount']; ?>% off</span>
                    </div>
                    <div class="actions-container">
                        <select class="form-select form-select-sm w-auto" onchange="location = 'update_cart_quantity.php?pid=<?php echo $pid; ?>&qty=' + this.value + '&redirect_page=summary.php';">
                            <?php for($i=0; $i<=10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php if($product['quantity'] == $i) echo 'selected'; ?>>Qty: <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                        <a href="update_cart_quantity.php?pid=<?php echo $pid; ?>&qty=0&redirect_page=summary.php" class="remove-link">Remove</a>
                    </div>
                    <?php if($product['free_quantity'] > 0): ?>
                        <div class="mt-2 small text-success fw-bold" style="font-size: 10px; background-color: #eaf5ec; padding: 8px 12px; border-radius: 5px; border-left: 5px solid #198754;">
                            <i class="bi bi-tag-fill"></i> Buy 2 Get 1 Free Applied (<?php echo $product['free_quantity']; ?> FREE)
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php
            if (isset($recommended_products_result)) {
                render_compact_product_carousel('You Might Also Like', 'Recommended for You', $recommended_products_result, $pwebsite, $is_mobile);
            }
        ?>
        
        <div class="card-section">
            <div class="price-details-card p-3">
                <h6 class="fw-bold mb-3">Price Details</h6>
                <div class="price-details-row"><span>Price (<?php echo $total_quantity; ?> items)</span><span>₹<?php echo number_format($total_mrp); ?></span></div>
                <div class="price-details-row"><span>Discount</span><span class="text-success">- ₹<?php echo number_format($total_item_discount + $offer_discount); ?></span></div>
                <div class="price-details-row"><span>Coupons for you</span><span class="text-success">- ₹<?php echo number_format($coupon_discount); ?></span></div>
                <div class="price-details-row"><span>Secure Packaging Fee</span><span>₹<?php echo number_format($protect_fee); ?></span></div>
                <div class="price-details-row total-amount-row"><span>Total Amount</span><span>₹<?php echo number_format($final_amount); ?></span></div>
                <div class="savings-banner mt-2"><i class="bi bi-tag-fill"></i> You will save ₹<?php echo number_format($total_savings); ?> on this order!</div>
            </div>
        </div>
    </main>

    <footer class="page-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <del class="text-muted small d-block">₹<?php echo number_format($total_mrp); ?></del>
                <span class="footer-price">₹<?php echo number_format($final_amount); ?></span>
                
            </div>
            
            <form action="<?php echo htmlspecialchars($form_action_url); ?>" method="POST" style="width: 50%; margin: 0;">
                <input type="hidden" name="final_amount" value="<?php echo htmlspecialchars($final_amount); ?>">
                <input type="hidden" name="mobile_number" value=" <?php echo htmlspecialchars($address['number'] ?? ''); ?>">
                
                <?php if ($is_payment_configured): ?>
                    <button type="submit" class="continue-btn w-100">Continue</button>
                <?php else: ?>
                    <button type="button" class="continue-btn w-100" disabled>Payment Not Available</button>
                <?php endif; ?>
            </form>
        </div>
    </footer>

</body>
</html>