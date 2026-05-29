<?php 
$page_title = "About Us";
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
            <div class="row align-items-center">
                
                <div class="col-md-12">
                    <h3>Our Story: Weaving Style for the Modern Woman</h3>
                    <p class="lead">
                        Our journey began with a simple idea: to create a destination where every woman can find her perfect outfit, no matter the occasion. We saw a world full of amazing women juggling different roles, each with a unique style. Yet, finding a single place that celebrated this diversity—offering everything from elegant Kurtis and timeless Sarees to chic Western Wear and playful Dresses—was a challenge.
                    </p>
                    <p>
                        This is where <strong><?php echo htmlspecialchars($settings['brand_name'] ?? 'Our Brand'); ?></strong> comes in. Born from a passion for fashion and a desire to empower women through their wardrobe, we set out to build a collection that is as versatile and vibrant as you are.
                    </p>
                    
                    <h4 class="mt-4">Our Philosophy: Style, Quality, and You</h4>
                    <p>
                        Our collection is a carefully curated blend of tradition and trend. We believe that every woman deserves to feel beautiful and confident. That's why we are committed to:
                    </p>
                    <ul>
                        <li><strong>Versatile Collections:</strong> Whether you're looking for the timeless elegance of a Saree, the effortless grace of a Kurti, the modern flair of our Western Wear, or the perfect Dress for a special day, we've got you covered.</li>
                        <li><strong>Quality Craftsmanship:</strong> We pay close attention to every detail, from selecting high-quality fabrics to ensuring a perfect fit, so you can wear our pieces with pride and comfort.</li>
                        <li><strong>Accessible Fashion:</strong> We strive to bring you the latest styles and timeless classics at prices that make fashion accessible to everyone.</li>
                    </ul>
                    
                    <h4 class="mt-4">More Than Just Clothing</h4>
                    <p>
                        At <strong><?php echo htmlspecialchars($settings['brand_name'] ?? 'Our Brand'); ?></strong>, we believe fashion is a form of self-expression. It’s about more than just clothes; it’s about the story you tell and the confidence you feel when you step out into the world. We are honoured to be a part of your life's moments, big and small.
                    </p>
                    <p>
                        Thank you for letting us be a part of your style journey. We can't wait to see how you make our pieces your own.
                    </p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include('footer.php'); ?>