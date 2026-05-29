<?php
include('database/connection.php');

function isMobileDevice() {
    if (!isset($_SERVER["HTTP_USER_AGENT"])) return false;
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

function generate_star_rating($rating) {
    $rating = (float)$rating;
    $stars_html = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    for ($i = 0; $i < $full_stars; $i++) { $stars_html .= '<i class="bi bi-star-fill"></i>'; }
    if ($half_star) { $stars_html .= '<i class="bi bi-star-half"></i>'; }
    for ($i = 0; $i < $empty_stars; $i++) { $stars_html .= '<i class="bi bi-star"></i>'; }
    return $stars_html;
}

$pwebsite = '';
$creds_result = mysqli_query($conn, "SELECT site FROM credentials LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $pwebsite = rtrim($fetch_creds['site'], '/');
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$html = '';

$stmt = $conn->prepare("SELECT id, name, image, total, price, discount, star FROM products LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$search_products = $stmt->get_result();

if ($search_products && $search_products->num_rows > 0) {
    while ($fetch_product = $search_products->fetch_assoc()) {
        $star_rating = $fetch_product['star'] ?? 4.5;
        $display_total = $fetch_product['total'];
        $display_mrp = $fetch_product['price'];

        $html .= sprintf(
            '<a href="singlepageview?pid=%s" class="products">
                <div class="product-card">
                    <div class="image-container"><img src="%s/assets/uploads/%s" class="product-image" alt="%s" loading="lazy"/></div>
                    <div class="product-info">
                        <p class="product-name">%s</p>
                        <div class="rating-line"><div class="rating-stars">%s</div></div>
                        <div class="price-line">
                            <span class="selling-price">₹%s</span>
                            <del class="mrp">₹%s</del>
                            <span class="discount">%s%% off</span>
                        </div>
                    </div>
                </div>
            </a>',
            $fetch_product['id'],
            $pwebsite,
            htmlspecialchars($fetch_product['image']),
            htmlspecialchars($fetch_product['name']),
            htmlspecialchars($fetch_product['name']),
            generate_star_rating($star_rating),
            number_format($display_total),
            number_format($display_mrp),
            htmlspecialchars($fetch_product['discount'])
        );
    }
}

$stmt->close();
echo $html;
?>