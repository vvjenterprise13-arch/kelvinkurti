<?php
// સત્ર હંમેશા પેજની શરૂઆતમાં શરૂ કરો
session_start();

// =======================================================================
// NEW: Function to detect mobile devices
// =======================================================================
function isMobileDevice() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}
// =======================================================================

// સ્ટાર રેટિંગ જનરેટ કરવા માટેનું PHP ફંક્શન (index.php માંથી ઉમેરેલું)
function generate_star_rating($rating) {
    $stars_html = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    for ($i = 0; $i < $full_stars; $i++) { $stars_html .= '<i class="bi bi-star-fill"></i>'; }
    if ($half_star) { $stars_html .= '<i class="bi bi-star-half"></i>'; }
    for ($i = 0; $i < $empty_stars; $i++) { $stars_html .= '<i class="bi bi-star"></i>'; }
    return $stars_html;
}
?>
<!DOCTYPE html>
<html lang="gu-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Bootstrap CSS અને Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <?php
    // ડેટાબેઝ કનેક્શન
    include('database/connection.php');

    // URL માંથી કેટેગરીનું નામ મેળવો
    $category_name = isset($_GET['category']) ? $_GET['category'] : '';

    // જો કેટેગરીનું નામ ન મળે, તો ભૂલ બતાવો
    if (empty($category_name)) {
        echo "<title>ભૂલ</title></head><body><p class='text-center p-5'>કેટેગરી પસંદ કરવામાં આવી નથી.</p></body></html>";
        exit();
    }
    
    // SQL Injection થી બચવા માટે
    $safe_category_name = mysqli_real_escape_string($conn, $category_name);

    // વેબસાઇટની લિંક મેળવો
    $pwebsite = '';
    $select_creds = "SELECT site FROM credentials LIMIT 1";
    $creds_result = mysqli_query($conn, $select_creds);
    if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
        $pwebsite = isset($fetch_creds['site']) ? rtrim($fetch_creds['site'], '/') : '';
    }
    ?>
    <title><?php echo htmlspecialchars($category_name); ?> - પ્રોડક્ટ્સ | YourStore</title>
    
    <!-- નવી CSS સ્ટાઇલ (index.php જેવી જ) -->
    <style>
        body { background-color: #f1f2f4; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; }
        .page-header { background-color: #d81b60; color: #212121; padding: 12px 16px; display: flex; align-items: center; position: sticky; top: 0; z-index: 1000; box-shadow: 0 1px 2px 0 rgba(0,0,0,0.1); }
        .back-arrow { color: #fff; text-decoration: none; font-size: 24px; margin-right: 16px; }
        .header-title { font-size: 18px; color: #fff; font-weight: 500; margin: 0; }
        .products-section { background-color: #f1f2f4; padding-top: 6px; }
        .mainbody { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background-color: #e0e0e0; }
        .products { background: white; text-decoration: none; color: black; }
        .productcard { padding: 10px; display: flex; flex-direction: column; height: 100%; }
        .imagecontainer { text-align: center; }
        .productimage { width: 100%; height: 150px; object-fit: contain; }
        .product-info { padding-top: 10px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-name { font-size: 14px; color: #212121; line-height: 1.4; height: 40px; overflow: hidden; margin-bottom: 8px; }
        .price-line { display: flex; align-items: center; flex-wrap: wrap; margin-top: auto; }
        .selling-price { font-size: 16px; font-weight: 500; color: #212121; }
        .mrp { text-decoration: line-through; color: #878787; font-size: 12px; margin: 0 8px; }
        .discount { font-size: 13px; color: #388e3c; font-weight: 500; }
        .rating-line { display: flex; align-items: center; margin-top: 6px; }
        .rating-stars { font-size: 14px; color: #ffc107; }
        .quality-assured-line { display: flex; align-items: center; margin-top: 6px; }
        .quality-text { font-size: 12px; font-weight: 500; }
        .quality-icon { font-size: 16px; color: #2874f0; margin-right: 5px; }
    </style>
</head>
<body>

    <header class="page-header">
        <a href="index.php" class="back-arrow"><i class="bi bi-arrow-left"></i></a>
        <h1 class="header-title"><?php echo htmlspecialchars($category_name); ?></h1>
    </header>
    
    <main class="products-section">
        <div class="mainbody">
            <?php
            $select_products = "SELECT * FROM products WHERE category = '$safe_category_name'";
            $search_products = mysqli_query($conn, $select_products);

            if ($search_products && mysqli_num_rows($search_products) > 0) {
                // NEW: Check device type before the loop
                $is_mobile = isMobileDevice();

                while ($fetch_product = mysqli_fetch_array($search_products)) {
                    $star_rating = isset($fetch_product['star']) ? (float)$fetch_product['star'] : 4.5;
                    
                    // NEW: Price modification logic for Desktop users
                    $display_total = (float)$fetch_product['total'];
                    $display_mrp = (float)$fetch_product['price'];
                    
                    if (!$is_mobile) {
                        $display_total += 800;
                        $display_mrp += 800;
                    }
            ?>
                <a href="singlepageview.php?pid=<?php echo $fetch_product['id']; ?>" class="products">
                    <div class="productcard">
                        <div class="imagecontainer">
                            <img src="<?php echo $pwebsite; ?>/assets/uploads/<?php echo $fetch_product['image']; ?>" class="productimage" />
                        </div>
                        <div class="product-info">
                            <p class="product-name"><?php echo htmlspecialchars($fetch_product['name']); ?></p>
                            <div class="rating-line">
                                <div class="rating-stars"><?php echo generate_star_rating($star_rating); ?></div>
                            </div>
                            <div class="price-line">
                                <span class="selling-price">₹<?php echo number_format($display_total); ?></span>
                                <del class="mrp">₹<?php echo number_format($display_mrp); ?></del>
                                <span class="discount"><?php echo htmlspecialchars($fetch_product['discount']); ?>% off</span>
                            </div>
                            <div class="quality-assured-line">
                                <i class="bi bi-patch-check-fill quality-icon"></i>
                                <span class="quality-text">Premium Quality</span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php
                }
            } else {
               echo "<div class='w-100 p-5 bg-white text-center'><strong style='color:red;'>Product Out of Stock</strong></div>";
            }
            ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>