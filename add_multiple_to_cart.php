<?php
session_start();

if (isset($_GET['pids']) && is_string($_GET['pids'])) {
    $product_ids = explode(',', $_GET['pids']);
    
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    foreach ($product_ids as $pid) {
        $product_id = (int)$pid;
        if ($product_id > 0) {
            if (!isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = ['qty' => 1];
            }
        }
    }
    
    http_response_code(200);
    echo count($_SESSION['cart']);
    exit;
}

http_response_code(400);
echo "Invalid product IDs provided.";
exit;
?>