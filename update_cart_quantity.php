<?php
// update_cart_quantity.php

session_start();

// ઇનપુટને સુરક્ષિત રીતે મેળવો
$product_id = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 0;

// 'redirect_page' પરથી નક્કી કરો કે ક્યાં પાછા જવું છે.
// જો તે URL માં આપવામાં ન આવ્યું હોય, તો ડિફોલ્ટ તરીકે cart.php પર જાઓ.
$redirect_location = isset($_GET['redirect_page']) ? $_GET['redirect_page'] : 'cart.php';


// ખાતરી કરો કે કાર્ટ સેશન અસ્તિત્વમાં છે અને તે એક એરે છે
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {

    // ખાતરી કરો કે પ્રોડક્ટ ID કાર્ટમાં અસ્તિત્વમાં છે
    if (isset($_SESSION['cart'][$product_id])) {
        
        // જો જથ્થો 0 કે તેથી ઓછો હોય, તો આઇટમને કાર્ટમાંથી દૂર કરો
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } 
        // અન્યથા, જથ્થો અપડેટ કરો (સાચી સંરચના સાથે)
        else {
            $_SESSION['cart'][$product_id] = ['qty' => $quantity];
        }
    }
}

// નક્કી કરેલા પેજ પર પાછા મોકલો
header('Location: ' . $redirect_location);
exit();
?>