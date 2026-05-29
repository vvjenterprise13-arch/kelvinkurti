<?php
session_start();

if (isset($_GET['pid'])) {
    $product_id_to_remove = (int)$_GET['pid'];

    // જો પ્રોડક્ટ કાર્ટમાં હોય તો તેને દૂર કરો
    if (isset($_SESSION['cart'][$product_id_to_remove])) {
        unset($_SESSION['cart'][$product_id_to_remove]);
    }
}

// વપરાશકર્તાને cart.php પેજ પર પાછા મોકલો
header('Location: cart.php');
exit();