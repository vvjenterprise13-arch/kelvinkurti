<?php
session_start();
include('database/connection.php');

define('CACHE_DIR', __DIR__ . '/cache/');
define('CACHE_EXPIRATION_SETTINGS', 3600);
define('CACHE_EXPIRATION_CATEGORIES', 3600);
define('CACHE_EXPIRATION_PRODUCTS', 900);

function get_cached_data($key, $expiration, $callback) {
    if (!is_dir(CACHE_DIR)) {
        if (!@mkdir(CACHE_DIR, 0755, true)) {
            return $callback();
        }
    }
    $cache_file = CACHE_DIR . sha1($key) . '.cache';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $expiration) {
        return unserialize(file_get_contents($cache_file));
    }
    $data = $callback();
    @file_put_contents($cache_file, serialize($data));
    return $data;
}

function generate_star_rating($rating) {
    $rating = (float)$rating; $stars_html = ''; $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5; $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
    for ($i = 0; $i < $full_stars; $i++) { $stars_html .= '<i class="bi bi-star-fill"></i>'; }
    if ($half_star) { $stars_html .= '<i class="bi bi-star-half"></i>'; }
    for ($i = 0; $i < $empty_stars; $i++) { $stars_html .= '<i class="bi bi-star"></i>'; }
    return $stars_html;
}

function isMobileDevice() {
    if (!isset($_SERVER["HTTP_USER_AGENT"])) return false;
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

if (!$conn) { die("Error: Unable to connect to the database."); }

$site_settings = get_cached_data('site_settings', CACHE_EXPIRATION_SETTINGS, function() use ($conn) {
    $settings = ['brandName' => 'YourStore', 'pwebsite' => ''];
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = 'brand_name'");
    $stmt->execute();
    if ($fetch = $stmt->get_result()->fetch_assoc()) { $settings['brandName'] = htmlspecialchars($fetch['setting_value']); }
    $stmt->close();
    $creds_result = mysqli_query($conn, "SELECT site FROM credentials LIMIT 1");
    if ($fetch_creds = mysqli_fetch_assoc($creds_result)) { $settings['pwebsite'] = rtrim($fetch_creds['site'], '/'); }
    return $settings;
});
$brandName = $site_settings['brandName'];
$pwebsite = $site_settings['pwebsite'];

$all_categories = get_cached_data('all_categories', CACHE_EXPIRATION_CATEGORIES, function() use ($conn) {
    $categories = [];
    $cat_result = mysqli_query($conn, "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    if ($cat_result) { while ($row = mysqli_fetch_assoc($cat_result)) { $categories[] = $row['category']; } }
    return $categories;
});

$is_mobile = isMobileDevice();
$products_cache_key = 'best_sellers_clothing_v3_' . ($is_mobile ? 'mobile' : 'desktop');

$best_sellers_html = get_cached_data($products_cache_key, CACHE_EXPIRATION_PRODUCTS, function() use ($conn, $is_mobile, $pwebsite) {
    $html = '';
    $select_products = "SELECT id, name, image, total, price, discount, star FROM products LIMIT 20";
    $search_products = mysqli_query($conn, $select_products);
    
    if ($search_products && mysqli_num_rows($search_products) > 0) {
        while ($fetch_product = mysqli_fetch_assoc($search_products)) {
            $star_rating = $fetch_product['star'] ?? 4.5;
            $selling_price = (float)$fetch_product['total'];
            $mrp = (float)$fetch_product['price'];

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
                $fetch_product['id'], $pwebsite, htmlspecialchars($fetch_product['image']),
                htmlspecialchars($fetch_product['name']), htmlspecialchars($fetch_product['name']),
                generate_star_rating($star_rating), number_format($selling_price),
                number_format($mrp), htmlspecialchars($fetch_product['discount'])
            );
        }
    } else {
        $html = "<p class='text-center w-100 p-4 bg-white'>No products found.</p>";
    }
    return $html;
});

$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
  <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-16800131424"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'AW-16800131424');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $brandName; ?> - Shop Western Wear Dress Plazza</title>
    <meta name="description" content="Discover the latest collection of women's clothing western wear at <?php echo $brandName; ?>. Shop now for trendy styles and great offers.">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #d81b60; 
            --secondary-color: #fce4ec;
            --light-gray: #f1f2f4;
            --text-color: #212121;
            --green-color: #388e3c;
            --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            --card-hover-shadow: 0 6px 16px rgba(0, 0, 0, 0.12);
        }
        body { background-color: #fff; font-family: 'Inter', sans-serif; }
        .main-container { max-width: 1300px; margin: 0 auto; }
        .page-header { background-color: var(--primary-color); padding: 8px 16px; position: sticky; top: 0; z-index: 1020; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header-icon { color: white; font-size: 24px; }
        .brand-logo { height: 60px; filter: brightness(0) invert(1); }
        .search-bar-wrapper { background-color: white; border-radius: 8px; padding: 4px; }
        .search-bar-wrapper input { border: none; box-shadow: none !important; }
        .offcanvas-header { background-color: var(--primary-color); color: white; }
        .section-title { font-size: 1rem; font-weight: 700; color: var(--text-color); }
        .section-subtitle { color: #6c757d; }
        .category-section { background-color: #fff; }
        .category-item { text-align: center; text-decoration: none; color: var(--text-color); flex: 0 0 80px; }
        .category-item img { width: 54px; height: 54px; border-radius: 50%; object-fit: cover; margin-bottom: 8px; transition: transform 0.2s, box-shadow 0.2s; box-shadow: var(--card-shadow); }
        .category-item:hover img { transform: scale(1.08); box-shadow: var(--card-hover-shadow); }
        .category-label { font-size: 13px; font-weight: 500; }
        .product-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1px; background-color: #eee; }
        @media (min-width: 768px) { .product-grid { grid-template-columns: repeat(4, 1fr); } }
        @media (min-width: 992px) { .product-grid { grid-template-columns: repeat(5, 1fr); } }
        .products { text-decoration: none; color: var(--text-color); }
        .product-card { background: white; display: flex; flex-direction: column; height: 100%; transition: transform 0.2s, box-shadow 0.2s; }
        .product-card:hover { transform: translateY(-5px); box-shadow: var(--card-hover-shadow); z-index: 10; }
        .image-container { height: 220px; display: flex; align-items: center; justify-content: center; padding: 5px; }
        .product-image { max-width: 100%; max-height: 100%; object-fit: contain; }
        .product-info { padding: 12px; flex-grow: 1; display: flex; flex-direction: column; }
        .product-name { font-size: 14px; line-height: 1.4; height: 40px; overflow: hidden; margin-bottom: 8px; }
        .rating-stars { color: var(--primary-color); font-size: 14px; }
        .price-line { display: flex; align-items: center; flex-wrap: wrap; margin-top: auto; }
        .selling-price { font-size: 16px; font-weight: 700; }
        .mrp { text-decoration: line-through; color: #878787; font-size: 12px; margin: 0 8px; }
        .discount { font-size: 13px; color: var(--green-color); font-weight: 500; }
        .deals-section { background: var(--secondary-color); border: 1px solid #e0e0e0; border-radius: 8px;  padding: 6px 12px; display: flex; justify-content: space-between; align-items: center; }
        .deals-title { font-size: 1.1rem; font-weight: 700; color: var(--primary-color); margin: 0 0 4px 0; }
        .deals-timer { font-size: 0.9rem; color: #6c757d; }
        .sale-button { color: white; text-decoration: none; font-weight: bold; font-size: 14px; padding: 10px 20px; border-radius: 20px; background: linear-gradient(90deg, #d81b60, #8e24aa, #d81b60); background-size: 200% auto; animation: gradient-flow 3s linear infinite; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); transition: transform 0.2s; }
        .sale-button:hover { transform: scale(1.05); color: white; }
        @keyframes gradient-flow { 0% { background-position: 0% 50%; } 100% { background-position: 200% 50%; } }
        .why-choose-us { background: linear-gradient(to right, #fce4ec, #ffffff); }
        .feature-icon { font-size: 40px; color: var(--primary-color); margin-bottom: 12px; }
        footer.bg-dark.text-white.mt-5 { margin-top: 0 !important; }
        .category-section .overflow-auto { scrollbar-width: none; -ms-overflow-style: none; }
        .category-section .overflow-auto::-webkit-scrollbar { display: none; }
        .my-container {
            --bs-gutter-x: 0 !important;
        }
    </style>
</head>
<body>

<div class="main-container ">
    <header class="page-header">
        <div class="container-fluid my-container d-flex justify-content-between align-items-center main-header-row">
            <button class="btn p-0 d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sideMenu">
                <i class="bi bi-list header-icon"></i>
            </button>
            <a class="navbar-brand" href="/">
                <img src="<?php echo $pwebsite; ?>/assets/banner/kurtilogo.png" alt="<?php echo $brandName; ?>" class="brand-logo">
            </a>
            <div class="d-none d-lg-block mx-auto" style="width: 500px;">
                 <div class="search-bar-wrapper">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search for Kurtis, Sarees, Dresses...">
                        <button class="btn btn-light" type="button"><i class="bi bi-search"></i></button>
                    </div>
                </div>
            </div>
            <a href="cart.php" class="text-white">
                <i class="bi bi-cart3 header-icon position-relative">
                    <?php if ($cart_count > 0) { echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">' . $cart_count . '</span>'; } ?>
                </i>
            </a>
        </div>
        <div class="d-lg-none mt-2 mobile-search-container">
            <div class="search-bar-wrapper">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search for products...">
                    <button class="btn btn-light" type="button"><i class="bi bi-search"></i></button>
                </div>
            </div>
        </div>
    </header>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="sideMenu">
        <div class="offcanvas-header"><h5 class="offcanvas-title">Menu</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button></div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link" href="/"><i class="bi bi-house-door-fill"></i> Home</a>
                <?php if (!empty($all_categories)): ?>
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="collapse" data-bs-target="#categoryCollapse"><i class="bi bi-grid-fill"></i> Shop by Category</a>
                    <div class="collapse" id="categoryCollapse">
                        <ul class="list-unstyled m-0 p-0">
                            <?php foreach ($all_categories as $category): ?>
                            <li><a class="dropdown-item ps-5" href="category_products?category=<?php echo urlencode($category); ?>"><?php echo htmlspecialchars(ucfirst($category)); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                <a class="nav-link" href="about-us"><i class="bi bi-info-circle-fill"></i> About Us</a>
                <a class="nav-link" href="contact-us"><i class="bi bi-telephone-fill"></i> Contact Us</a>
                <hr class="my-2">
                <a class="nav-link" href="shipping-policy"><i class="bi bi-truck"></i> Shipping Policy</a>
                <a class="nav-link" href="return-policy"><i class="bi bi-box-arrow-left"></i> Return Policy</a>
            </nav>
        </div>
    </div>

    <main>
        
        
        <section class="pb-4">
            <div class="container-fluid my-container">
                
                <div class="product-grid">
                    <?php echo $best_sellers_html; ?>
                </div>
                <div id="loader" class="text-center py-4" style="display: none;"><div class="spinner-border text-primary"></div></div>
            </div>
        </section>

        
    </main>
    
   <?php include('footer.php'); ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let page = 2;
    let isLoading = false;
    let allProductsLoaded = false;
    const loader = document.getElementById('loader');
    const mainBody = document.querySelector('.product-grid');
    const whyChooseUsSection = document.querySelector('.why-choose-us');

    // Initially hide the "Why Choose Us" section if it exists
    if (whyChooseUsSection) {
        whyChooseUsSection.style.display = 'none';
    }

    function loadMoreProducts() {
        if (isLoading || allProductsLoaded) return;
        isLoading = true;
        loader.style.display = 'block';
        fetch(`load_products.php?page=${page}`)
            .then(response => response.text())
            .then(html => {
                if (html.trim() !== '') {
                    mainBody.insertAdjacentHTML('beforeend', html);
                    page++;
                } else {
                    allProductsLoaded = true;
                    // Show the "Why Choose Us" section when all products are loaded
                    if (whyChooseUsSection) {
                        whyChooseUsSection.style.display = 'block';
                    }
                }
            })
            .catch(error => console.error('Error loading products:', error))
            .finally(() => {
                isLoading = false;
                loader.style.display = 'none';
            });
    }

    // Check if there are few enough products that no scroll is needed
    // If so, all products are already "loaded", so show the section.
    if (document.body.scrollHeight <= window.innerHeight) {
        if (whyChooseUsSection) {
            whyChooseUsSection.style.display = 'block';
        }
        allProductsLoaded = true;
    }

    window.addEventListener('scroll', () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
            loadMoreProducts();
        }
    });

    function startCountdown() {
        const countdownElement = document.getElementById('countdown-timer');
        if (!countdownElement) return;
        let timeInSeconds = 5 * 60;
        const timerInterval = setInterval(() => {
            const minutes = Math.floor(timeInSeconds / 60);
            let seconds = timeInSeconds % 60;
            let displayMinutes = minutes < 10 ? '0' + minutes : minutes;
            let displaySeconds = seconds < 10 ? '0' + seconds : seconds;
            countdownElement.textContent = `${displayMinutes}:${displaySeconds}`;
            if (timeInSeconds <= 0) {
                clearInterval(timerInterval);
                countdownElement.textContent = "Deal Ended!";
            } else {
                timeInSeconds--;
            }
        }, 1000);
    }
    startCountdown();
});
</script>
</body>
</html>