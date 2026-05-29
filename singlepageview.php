<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();

include('database/connection.php');

define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_EXPIRATION', 900);

function isMobileDevice() {
    if (!isset($_SERVER["HTTP_USER_AGENT"])) return false;
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

function getProductPageData(mysqli $conn, int $id): ?array {
    $data = [];

    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        return null;
    }
    $data['product'] = $result->fetch_assoc();
    $stmt->close();

    $settings_query = "
        (SELECT 'brand_name' as setting_key, setting_value FROM settings WHERE setting_key = 'brand_name' LIMIT 1)
        UNION
        (SELECT 'site_url' as setting_key, site as setting_value FROM credentials LIMIT 1)
    ";
    $settings_result = $conn->query($settings_query);
    while ($row = $settings_result->fetch_assoc()) {
        $data['settings'][$row['setting_key']] = $row['setting_value'];
    }

    $category = $data['product']['category'] ?? '';
    $stmt_rec = $conn->prepare("SELECT id, name, total, price, image FROM products WHERE category != ? AND id != ? ORDER BY id DESC LIMIT 30");
    $stmt_rec->bind_param("si", $category, $id);
    $stmt_rec->execute();
    $all_related = $stmt_rec->get_result()->fetch_all(MYSQLI_ASSOC);
    shuffle($all_related);
    $data['related_products'] = array_slice($all_related, 0, 10);
    $stmt_rec->close();
    
    $stmt_combo = $conn->prepare("SELECT id, name, total, image FROM products WHERE id != ? ORDER BY RAND() LIMIT 2");
    $stmt_combo->bind_param("i", $id);
    $stmt_combo->execute();
    $data['combo_products'] = $stmt_combo->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_combo->close();

    return $data;
}

$id = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
if ($id <= 0) {
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>The product you are looking for does not exist.</p>";
    exit;
}

$is_mobile = isMobileDevice();
$cache_key = 'product_page_clothing_v14_' . $id . '_' . ($is_mobile ? 'mobile' : 'desktop');
$cache_file = CACHE_DIR . sha1($cache_key) . '.cache';

if (file_exists($cache_file) && (time() - filemtime($cache_file)) < CACHE_EXPIRATION) {
    echo file_get_contents($cache_file);
    exit;
}

ob_start();

if (!$conn) {
    ob_end_clean();
    http_response_code(500);
    die("Database connection failed. Please try again later.");
}

$data = getProductPageData($conn, $id);
$conn->close();

if ($data === null) {
    ob_end_clean();
    http_response_code(404);
    echo "<h1>404 Not Found</h1><p>The product with ID " . htmlspecialchars($id) . " was not found.</p>";
    exit;
}

$item = $data['product'];
$pid = (int)$item['id'];

$productName = htmlspecialchars($item['name']);
$productDetails = strip_tags($item['description'], '<p><b><i><u><ul><ol><li><strong><em><br>');
$productPrice = (float)$item['total'];
$productDiscount = (int)$item['discount'];
$productMRP = (float)$item['price'];
$productImage = htmlspecialchars($item['image'] ?? 'default.jpg');
$brandName = htmlspecialchars($data['settings']['brand_name'] ?? 'YourStore');
$pwebsite = rtrim($data['settings']['site_url'] ?? '', '/');

$metaDescription = htmlspecialchars(mb_substr(strip_tags($item['description']), 0, 155, 'UTF-8')) . '...';
$canonical_url = $pwebsite . '/singlepageview?pid=' . $pid;
$stock_left = rand(2, 7);
$is_in_cart = isset($_SESSION['cart'][$pid]);
$available_sizes = !empty($item['size']) ? explode(',', trim($item['size'])) : [];

$rating = mt_rand(41, 50) / 10;
$total_ratings_count = rand(500, 5000);
$rating_percentages = [];
$remaining_percentage = 100;
$rating_percentages[5] = rand(50, 75);
$remaining_percentage -= $rating_percentages[5];
$rating_percentages[4] = rand(15, min(25, $remaining_percentage - 3));
$remaining_percentage -= $rating_percentages[4];
$rating_percentages[3] = rand(1, min(5, $remaining_percentage - 2));
$remaining_percentage -= $rating_percentages[3];
$rating_percentages[2] = rand(1, min(3, $remaining_percentage - 1));
$rating_percentages[1] = max(0, $remaining_percentage - $rating_percentages[2]);

$rating_counts = [];
foreach ($rating_percentages as $star => $percentage) {
    $rating_counts[$star] = floor(($total_ratings_count * $percentage) / 100);
}

$reviews = [];
$names = [
    "Priya Patel", "Sneha Joshi", "Anjali Desai", "Meena Gupta",
    "Ananya Das", "Kavita Choudhury", "Pooja Reddy", "Divya Iyer",
    "Sonia Mehta", "Tanvi Shah", "Neha Kulkarni", "Ritika Sharma",
    "Shalini Nair", "Komal Verma", "Harshita Goyal",
    "Aditi Chauhan", "Bhavna Soni", "Chhavi Agarwal", "Deepa Menon", "Esha Kapoor",
    "Farah Sheikh", "Garima Thakur", "Himani Saxena", "Ishita Bansal", "Juhi Arora",
    "Kiran Malhotra", "Lavanya Mishra", "Monika Jain", "Nisha Rawat", "Ojasvi Tripathi"
];

$cities = [
    "Mumbai", "Pune", "Bengaluru", "Delhi", "Ahmedabad", "Surat",
    "Jaipur", "Hyderabad", "Chennai", "Kolkata", "Nagpur", "Indore",
    "Lucknow", "Vadodara", "Rajkot",
    "Amritsar", "Bhopal", "Chandigarh", "Coimbatore", "Guwahati",
    "Patna", "Mysuru", "Thiruvananthapuram", "Udaipur", "Visakhapatnam",
    "Noida", "Gurgaon", "Jodhpur", "Aurangabad", "Varanasi"
];

$titles = [
    "Beautiful Design!", "Perfect Fit!", "Value for Money", "Highly Recommended",
    "Great Fabric Quality!", "Color is Vibrant", "Exactly as Shown", "Good Stitching",
    "Will Buy Again", "Elegant & Stylish", "Loved the Comfort", "Best Purchase Ever!",
    "Truly Amazing", "Classy Look", "Soft & Light Material",
    "Super Stylish!", "Very Comfortable", "Totally Worth It", "Excellent Finish", "Trendy & Chic",
    "Fantastic Product!", "Impressed with Quality", "Lovely Fabric", "Modern Look", "Perfect Everyday Wear",
    "Quality Exceeded Expectations", "Eye-Catching Design", "Best Value Buy", "Feels Premium", "Looks Gorgeous",

    // 🔥 Buy 2 Get 1 Related
    "Buy 2 Get 1 Free Deal!",
    "Triple Style, Double Price",
    "Best B2G1 Offer",
    "3 Kurtis at Price of 2",
    "Unbeatable B2G1 Discount"
];

$texts = [
    "The fabric quality is amazing, exactly as shown in the picture. Very happy with the purchase.",
    "Absolutely worth the price. The fit is perfect and the color is so vibrant.",
    "I'm very satisfied with this dress. It exceeded my expectations in terms of quality and style.",
    "Great design and good material. Received the delivery on time. Good experience overall.",
    "This is the second kurti I've bought from here. Consistently good quality and fit.",
    "The product was neatly packed. The material is very soft and comfortable to wear.",
    "Superb quality for the price. My sister loved it as a gift!",
    "The color is exactly as described, and the fit is perfect. Definitely buying another one in a different color.",
    "I was doubtful about buying clothes online, but the quality is top-notch. Highly trustworthy brand.",
    "Excellent service and great packaging. Loved the design and feel of the fabric.",
    "Really soft fabric and stylish pattern. Got many compliments when I wore it.",
    "Perfect summer wear – light, breathable, and elegant design.",
    "Fitting is so accurate, feels like it’s tailored just for me.",
    "Totally worth the money, looks even better than the photos online.",
    "Fast delivery and amazing quality. Definitely going to recommend to my friends.",
    "The kurti looks exactly like the pictures, no color difference at all.",
    "Fabric doesn’t shrink after wash, very reliable quality.",
    "This dress is both modern and traditional, love the balance!",
    "Got it as a gift and it was perfect in size and design.",
    "The stitching is neat and strong, feels very durable.",
    "Loved the unique prints, stands out in the crowd.",
    "Very comfortable for office wear, elegant and stylish.",
    "The material feels premium, definitely not cheap at all.",
    "I got many compliments when I wore this to a function.",
    "The packaging was neat and protective, no damage at all.",
    "Perfect for festive occasions, adds charm to the look.",
    "Wearing it feels light and airy, good for summer heat.",
    "Affordable and stylish, rare to find such quality online.",
    "Delivered before the expected date, great service.",
    "I’m impressed with the consistency of their fabric quality.",

    // 🔥 Buy 2 Get 1 Related
    "The Buy 2 Get 1 Free offer is just amazing! Got 3 kurtis for price of 2.",
    "Perfect deal for festive shopping – 3 dresses in budget of 2. Love it!",
    "B2G1 made it so affordable, plus the designs are super trendy.",
    "I was able to gift one kurti to my sister with this offer. Great value!",
    "Best shopping experience ever – B2G1 saved me money and gave more variety."
];


for ($i = 0; $i < 25; $i++) {
    $reviews[] = [ 'name' => $names[array_rand($names)], 'city' => $cities[array_rand($cities)], 'rating' => mt_rand(40, 50) / 10, 'title' => $titles[array_rand($titles)], 'text' => $texts[array_rand($texts)] ];
}
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $productName; ?> - <?php echo $brandName; ?></title>
    <meta name="description" content="<?php echo $metaDescription; ?>">
    <link rel="canonical" href="<?php echo $canonical_url; ?>" />
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root { --primary-color: #d81b60; --success-color: #198754; --danger-color: #dc3545; --light-gray: #f1f2f4; --text-color: #212121; }
        body { background-color: var(--light-gray); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; color: var(--text-color); }
        .main-container { max-width: 1248px; margin: 0 auto; }
        .page-header { background-color: var(--primary-color); padding: 8px 16px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 1020; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .header-icon { color: white !important; font-size: 1.5rem; }
        .brand-logo { height: 60px; width: auto; filter: brightness(0) invert(1); }
        .content-section { background-color: #fff; padding: 24px 16px; border-top: 8px solid var(--light-gray); }
        .section-title { font-size: 1rem; margin-bottom: 12px; color: #555; }
        .carousel-item img { width: 100%; height: auto; max-height: 400px; object-fit: contain; }
        .product-info-section { background-color: #fff; padding: 16px; }
        .product-title { font-size: 1.1rem; font-weight: 600; line-height: 1.3; }
        .rating-box { background-color: var(--success-color); color: white; padding: 2px 8px; font-size: 0.9rem; border-radius: 12px; display: inline-flex; align-items: center; gap: 4px; }
        .final-price { font-size: 1.8rem; font-weight: bold; }
        .mrp { text-decoration: line-through; color: #6c757d; }
        .discount { font-size: 1rem; color: var(--success-color); font-weight: bold; }
        .stock-alert { color: var(--danger-color); font-weight: 600; }
        .trust-badges-container { display: flex; justify-content: space-around; text-align: center; padding: 16px 0; }
        .trust-badge { display: flex; flex-direction: column; align-items: center; gap: 8px; font-size: 0.75rem; color: #555; }
        .trust-badge i { font-size: 1.8rem; color: var(--success-color); }
        .combo-deal-card { border: 2px dashed var(--primary-color); padding: 16px; border-radius: 8px; }
        .combo-items { display: flex; align-items: center; justify-content: center; gap: 10px; }
        .combo-item img { width: 80px; height: 80px; object-fit: contain; }
        .combo-plus { font-size: 2rem; font-weight: bold; color: #777; }
        .related-carousel { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 10px; scrollbar-width: none; }
        .related-carousel::-webkit-scrollbar { display: none; }
        .product-card { flex: 0 0 160px; border: 1px solid #e0e0e0; border-radius: 8px; background-color: #fff; }
        .product-card-link { text-decoration: none; color: inherit; }
        .product-card-img { height: 140px; padding: 10px; text-align: center; }
        .product-card-img img { max-width: 100%; max-height: 100%; object-fit: contain; }
        .product-card-body { padding: 8px 12px; }
        .product-card-title { font-size: 0.9rem; font-weight: 500; height: 40px; overflow: hidden; }
        .product-card-cart-btn { display: block; margin: 0 12px 12px; padding: 6px; font-size: 0.85rem; font-weight: 600; text-align: center; text-decoration: none; background-color: #fce4ec; color: var(--primary-color); border: 1px solid #f8bbd0; border-radius: 6px; }
        .footer-buttons { position: fixed; bottom: 0; left: 0; right: 0; max-width: 1248px; margin: 0 auto; z-index: 1000; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); display: flex; }
        .footer-buttons .btn { flex: 1; height: 50px; border: none; font-size: 1rem; font-weight: 600; border-radius: 0; }
        .btn-cart { background-color: #fff; color: var(--primary-color); border-top: 1px solid #ddd; }
        .btn-buy { background-color: var(--primary-color); color: #fff; }
        .size-options { display: flex; flex-wrap: wrap; gap: 8px; }
        .size-option-btn { height: 40px; width: 40px; border-radius: 50%; border: 1px solid #ccc; background-color: #fff; color: #333; cursor: pointer; font-weight: 500; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
        .size-option-btn:hover { border-color: #888; }
        .size-option-btn.active { background-color: var(--primary-color); color: white; border-color: var(--primary-color); font-weight: bold; }
        .ratings-section-header { display: flex; justify-content: space-between; align-items: center; }
        .overall-rating-value { font-size: 2.8rem; font-weight: 500; line-height: 1; }
        .overall-rating-value .bi-star-fill { font-size: 1.8rem; color: var(--success-color); vertical-align: text-bottom; margin-left: 4px; }
        .rating-bar-row { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .rating-bar-container { flex-grow: 1; height: 6px; background-color: #e0e0e0; border-radius: 3px; overflow: hidden; }
        .rating-bar { height: 100%; background-color: var(--success-color); border-radius: 3px; }
        .individual-review { padding-top: 1rem; border-top: 1px solid #f0f0f0;}
        .verified-buyer-icon { color: var(--success-color); }
        .btn-buy:active, .btn-buy:focus { background-color: var(--primary-color) !important; box-shadow: none !important; }
    </style>
</head>
<body>
<div class="main-container">

    <header class="page-header">
       <a href="#" onclick="history.back(); return false;" class="header-icon"><i class="bi bi-arrow-left"></i></a>
      <a class="navbar-brand" href="/"><img src="<?php echo $pwebsite; ?>/assets/banner/kurtilogo.png" alt="<?php echo $brandName; ?>" class="brand-logo"></a>
       <a href="cart.php" class="header-icon position-relative">
            <i class="bi bi-cart3"></i>
            <span id="cart-badge-container">
            <?php $cart_count = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? count($_SESSION['cart']) : 0; if ($cart_count > 0) { echo '<span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" style="font-size: 0.6em;">' . $cart_count . '</span>'; } ?>
            </span>
        </a>
    </header>

    <main>
        <section class="product-image-section bg-white">
            <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active"><img src="<?php echo $pwebsite . '/assets/uploads/' . $productImage; ?>" alt="<?php echo $productName; ?> - Image 1"></div>
                    <?php for ($i = 2; $i <= 10; $i++): $img_col = 'image' . $i; if (!empty($item[$img_col])): ?>
                        <div class="carousel-item"><img src="<?php echo $pwebsite . '/assets/uploads/' . htmlspecialchars($item[$img_col]); ?>" alt="<?php echo $productName; ?> - Image <?php echo $i; ?>"></div>
                    <?php endif; endfor; ?>
                </div>
            </div>
        </section>

        <section class="product-info-section">
            <h1 class="product-title"><?php echo $productName; ?></h1>
            <div class="d-flex align-items-center gap-3 mt-2">
                <span class="rating-box"><?php echo number_format($rating, 1); ?> <i class="bi bi-star-fill" style="font-size: 0.7em;"></i></span>
                <span class="text-muted small"><?php echo number_format($total_ratings_count); ?> Ratings</span>
            </div>
            
            <div class="price-container d-flex align-items-baseline gap-3 mt-2">
                <span id="finalPriceDisplay" class="final-price">₹<?php echo number_format($productPrice); ?></span>
                <del id="mrpDisplay" class="mrp">₹<?php echo number_format($productMRP); ?></del>
                <span class="discount"><?php echo $productDiscount; ?>% Off</span>
            </div>

            <?php if (!empty($available_sizes)): ?>
                <div class="mt-4 size-selector-container">
                    <h3 class="section-title fw-bold">Select Size:</h3>
                    <div class="size-options">
                        <?php foreach ($available_sizes as $size): $s = htmlspecialchars(trim($size)); ?>
                            <button class="size-option-btn" data-size="<?php echo $s; ?>"><?php echo $s; ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" id="selectedSize" name="selected_size" value="">
                </div>
            <?php endif; ?>

            <div class="mt-3">
                 <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#sizeChartModal">
                     <i class="bi bi-rulers"></i> Size Chart
                 </button>
            </div>

            <?php if ($stock_left <= 15): ?>
                <div class="stock-alert mt-4"><i class="bi bi-exclamation-triangle-fill"></i> Hurry! Only <?php echo $stock_left; ?> left in stock!</div>
            <?php endif; ?>
        </section>
        
        <section class="content-section py-2">
            <div class="trust-badges-container">
                <div class="trust-badge"><i class="bi bi-shield-check"></i><span>Secure<br>Payments</span></div>
                <div class="trust-badge"><i class="bi bi-truck"></i><span>Free & Fast<br>Delivery</span></div>
                <div class="trust-badge"><i class="bi bi-arrow-repeat"></i><span>Easy 7-Day<br>Returns</span></div>
                <div class="trust-badge"><i class="bi bi-patch-check"></i><span>100% Genuine<br>Products</span></div>
            </div>
        </section>

        <?php if (!empty($data['combo_products']) && count($data['combo_products']) == 2): ?>
        <section class="content-section">
            <h2 class="section-title fw-bold">Complete The Look</h2>
            <div class="combo-deal-card">
                <div class="combo-items">
                    <div class="combo-item"><img src="<?php echo $pwebsite . '/assets/uploads/' . $productImage; ?>" alt="<?php echo $productName; ?>"></div>
                    <div class="combo-plus">+</div>
                    <div class="combo-item"><img src="<?php echo $pwebsite . '/assets/uploads/' . htmlspecialchars($data['combo_products'][0]['image']); ?>" alt=""></div>
                    <div class="combo-plus">+</div>
                    <div class="combo-item"><img src="<?php echo $pwebsite . '/assets/uploads/' . htmlspecialchars($data['combo_products'][1]['image']); ?>" alt=""></div>
                </div>
               <div class="combo-price-section mt-3 text-center">
    <?php
        $combo_total = $productPrice + (float)$data['combo_products'][0]['total'] + (float)$data['combo_products'][1]['total'];
        $combo_discounted_total = $combo_total * 0.95;
    ?>
    <p class="mb-2">
        Total Price:
        <del>₹<?php echo number_format($combo_total); ?></del>
        <strong class="h5">₹<?php echo number_format($combo_discounted_total); ?></strong>
    </p>
    <button id="addComboToCartBtn" class="btn btn-success fw-bold"
        data-pids="<?php echo $pid . ',' . $data['combo_products'][0]['id'] . ',' . $data['combo_products'][1]['id']; ?>">
        Add Combo to Cart
    </button>
</div>
        </section>
        <?php endif; ?>

        <section class="content-section">
            <h2 class="section-title fw-bold">You Might Also Like</h2>
            <div class="related-carousel">
                <?php foreach ($data['related_products'] as $related_item):
                    $display_total = (float)$related_item['total'];
                    $display_mrp = (float)$related_item['price'];
                ?>
                    <div class="product-card">
                        <a href="singlepageview?pid=<?php echo $related_item['id']; ?>" class="product-card-link">
                            <div class="product-card-img"><img src="<?php echo $pwebsite . '/assets/uploads/' . htmlspecialchars($related_item['image']); ?>" alt="<?php echo htmlspecialchars($related_item['name']); ?>" loading="lazy"></div>
                            <div class="product-card-body">
                                <p class="product-card-title"><?php echo htmlspecialchars($related_item['name']); ?></p>
                                <div><span class="fw-bold">₹<?php echo number_format($display_total); ?></span> <del class="ms-2 text-muted small">₹<?php echo number_format($display_mrp); ?></del></div>
                            </div>
                        </a>
                        <a href="add_to_cart.php?pid=<?php echo $related_item['id']; ?>" class="product-card-cart-btn">Add to Cart</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="content-section">
            <h2 class="section-title fw-bold">Product Description & Care</h2>
            <div class="text-muted small lh-lg"><?php echo $productDetails; ?></div>
            <div class="mt-3">
                <h4 class="small fw-bold">Care Instructions:</h4>
                <p class="text-muted small">Machine wash cold with similar colors, gentle cycle. Do not bleach. Tumble dry low. Warm iron if needed.</p>
            </div>
        </section>

        <section class="content-section">
            <div class="ratings-section-header">
                <h2 class="section-title fw-bold mb-0">Ratings & Reviews</h2>
            </div>
            <div class="row align-items-center mt-3">
                <div class="col-lg-4 col-md-5 col-12 text-center text-md-start mb-4 mb-md-0">
                    <div class="overall-rating-value"><?php echo number_format($rating, 1); ?> <i class="bi bi-star-fill"></i></div>
                    <div class="text-muted"><?php echo number_format($total_ratings_count); ?> Ratings</div>
                </div>
                <div class="col-lg-8 col-md-7 col-12">
                    <div class="rating-distribution">
                        <?php krsort($rating_percentages); ?>
                        <?php foreach ($rating_percentages as $star => $percent): ?>
                        <div class="rating-bar-row">
                            <span class="small"><?php echo $star; ?>★</span>
                            <div class="rating-bar-container">
                                <div class="rating-bar" style="width: <?php echo $percent; ?>%;"></div>
                            </div>
                            <span class="small text-muted"><?php echo number_format($rating_counts[$star]); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div>
                <?php foreach (array_slice($reviews, 0, 15) as $review): ?>
                    <article class="individual-review mt-3">
                        <div class="d-flex align-items-center mb-2">
                            <span class="rating-box me-2"><?php echo number_format($review['rating'], 1); ?> <i class="bi bi-star-fill" style="font-size:0.7em;"></i></span>
                            <h3 class="mb-0 h6 fw-bold"><?php echo htmlspecialchars($review['title']); ?></h3>
                        </div>
                        <p class="small my-2"><?php echo htmlspecialchars($review['text']); ?></p>
                        <div class="small text-muted">
                            <span><?php echo htmlspecialchars($review['name']); ?></span>
                            <span class="mx-1">|</span>
                            <span><?php echo htmlspecialchars($review['city']); ?></span>
                            <span class="ms-2"><i class="bi bi-patch-check-fill verified-buyer-icon"></i> Verified Buyer</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

    </main>
</div>

<div style="height: 60px;"></div>

<div class="footer-buttons">
    <button id="addToCartBtn" class="btn btn-cart" <?php echo $is_in_cart ? 'style="display:none;"' : ''; ?>>Add To Cart</button>
    <a href="cart.php" id="goToCartBtn" class="btn btn-cart text-center pt-3" <?php echo !$is_in_cart ? 'style="display:none;"' : ''; ?>>Go To Cart</a>
    <button id="buyNowBtn" class="btn btn-buy">Buy Now</button>
</div>

<div class="modal fade" id="sizeChartModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Size Chart (For Kurtis/Dresses)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small">All measurements are in inches. Please allow a 0.5 inch variance.</p>
                <div class="table-responsive"><table class="table table-bordered text-center"><thead class="table-light"><tr><th>Size</th><th>Bust</th><th>Waist</th><th>Hip</th><th>Length</th></tr></thead><tbody><tr><td>S</td><td>34</td><td>28</td><td>38</td><td>42</td></tr><tr><td>M</td><td>36</td><td>30</td><td>40</td><td>42.5</td></tr><tr><td>L</td><td>38</td><td>32</td><td>42</td><td>43</td></tr><tr><td>XL</td><td>40</td><td>34</td><td>44</td><td>43.5</td></tr><tr><td>XXL</td><td>42</td><td>36</td><td>46</td><td>44</td></tr></tbody></table></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const productId = <?php echo $pid; ?>;
    const addToCartBtn = document.getElementById('addToCartBtn');
    const goToCartBtn = document.getElementById('goToCartBtn');
    const buyNowBtn = document.getElementById('buyNowBtn');
    const quantity = 1;

    const sizeOptionsContainer = document.querySelector('.size-options');
    const selectedSizeInput = document.getElementById('selectedSize');

    if (sizeOptionsContainer) {
        const sizeButtons = sizeOptionsContainer.querySelectorAll('.size-option-btn');
        sizeButtons.forEach(button => {
            button.addEventListener('click', function() {
                sizeButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                if (selectedSizeInput) {
                    selectedSizeInput.value = this.getAttribute('data-size');
                }
            });
        });
    }

    function showLoading(btn, text = '...') {
        if (!btn || btn.classList.contains('loading')) return;
        btn.classList.add('loading');
        btn.dataset.originalContent = btn.innerHTML;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span> ${text}`;
    }

    function hideLoading(btn) {
        if (!btn || !btn.classList.contains('loading')) return;
        let defaultText = btn.dataset.originalContent || 'Action';
        btn.innerHTML = defaultText;
        btn.classList.remove('loading');
    }

    function handleAddToCart() {
        showLoading(addToCartBtn, 'Adding...');
        let url = `add_to_cart.php?pid=${productId}&qty=${quantity}&ajax=1`;
        if (selectedSizeInput && selectedSizeInput.value) {
            url += `&size=${encodeURIComponent(selectedSizeInput.value)}`;
        }
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    addToCartBtn.style.display = 'none';
                    goToCartBtn.style.display = 'block';
                    const cartBadgeContainer = document.getElementById('cart-badge-container');
                    cartBadgeContainer.innerHTML = (data.cart_count > 0) ? `<span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" style="font-size: 0.6em;">${data.cart_count}</span>` : '';
                } else {
                    alert(data.message || 'Could not add to cart.');
                }
            })
            .catch(() => { alert('An error occurred. Please try again.'); })
            .finally(() => { hideLoading(addToCartBtn); });
    }

    function handleBuyNow() {
        let url = `add_to_cart.php?pid=${productId}&qty=${quantity}&buy_now=true`;
        if (selectedSizeInput && selectedSizeInput.value) {
            url += `&size=${encodeURIComponent(selectedSizeInput.value)}`;
        }
        window.location.href = url;
    }

    const addComboToCartBtn = document.getElementById('addComboToCartBtn');
    if (addComboToCartBtn) {
        addComboToCartBtn.addEventListener('click', function() {
            showLoading(this, 'Adding...');
            const allPids = this.getAttribute('data-pids');
            fetch(`add_multiple_to_cart.php?pids=${allPids}`)
                .then(res => res.text())
                .then(newCartCount => {
                    this.classList.remove('btn-success');
                    this.classList.add('btn-secondary');
                    this.innerHTML = '<i class="bi bi-check-circle-fill"></i> Added!';
                    this.disabled = true;
                    if(addToCartBtn) addToCartBtn.style.display = 'none';
                    if(goToCartBtn) goToCartBtn.style.display = 'block';
                    const cartBadgeContainer = document.getElementById('cart-badge-container');
                    cartBadgeContainer.innerHTML = (parseInt(newCartCount) > 0) ? `<span class="badge bg-danger rounded-pill position-absolute top-0 start-100 translate-middle" style="font-size: 0.6em;">${newCartCount}</span>` : '';
                })
                .catch(() => { alert('Could not add combo to cart.'); })
                .finally(() => { hideLoading(this); });
        });
    }

    if (addToCartBtn) { addToCartBtn.addEventListener('click', handleAddToCart); }
    if (buyNowBtn) { buyNowBtn.addEventListener('click', handleBuyNow); }

    (function autoScroll() {
        const el = document.querySelector('.related-carousel');
        if(!el) return;
        let id = null, speed = 0.5;
        const step = () => { el.scrollLeft += speed; if (el.scrollLeft >= el.scrollWidth / 2) el.scrollLeft = 0; id = requestAnimationFrame(step); };
        el.addEventListener('mouseenter', () => cancelAnimationFrame(id));
        el.addEventListener('mouseleave', () => requestAnimationFrame(step));
        requestAnimationFrame(step);
    })();
});

window.addEventListener('pageshow', function(event) {
    const addToCartBtn = document.getElementById('addToCartBtn');
    if (addToCartBtn && addToCartBtn.classList.contains('loading')) {
        let defaultText = addToCartBtn.dataset.originalContent || 'Add To Cart';
        addToCartBtn.innerHTML = defaultText;
        addToCartBtn.classList.remove('loading');
    }
});
</script>
</body>
</html>
<?php
$page_content = ob_get_clean();
if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}
file_put_contents($cache_file, $page_content);
echo $page_content;
?>