<?php
// ભૂલો બતાવવા માટે (વિકાસ દરમિયાન ખૂબ જ ઉપયોગી)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// =======================================================================
//          ઇનપુટ વેલિડેશન
// =======================================================================
$product_id = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;
$quantity = isset($_GET['qty']) ? (int)$_GET['qty'] : 1;
$is_buy_now = (isset($_GET['buy_now']) && $_GET['buy_now'] === 'true');
$is_ajax_request = (isset($_GET['ajax']) && $_GET['ajax'] === '1');

// =======================================================================
//          કાર્ટ લોજીક
// =======================================================================

// જો buy_now=true હોય તો જૂનો કાર્ટ ખાલી કરો
if ($is_buy_now) {
    $_SESSION['cart'] = [];
}

// જો પ્રોડક્ટ ID અને જથ્થો માન્ય હોય તો જ કાર્ટમાં ઉમેરો
if ($product_id > 0 && $quantity > 0) {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // જો આઇટમ પહેલાથી જ કાર્ટમાં હોય, તો જથ્થો વધારો, નહીં તો નવી આઇટમ ઉમેરો
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['qty'] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = ['qty' => $quantity];
    }
}

// =======================================================================
//          આઉટપુટ/રીડાયરેક્શન લોજીક
// =======================================================================

// જો 'Buy Now' પર ક્લિક કર્યું હોય, તો હંમેશા address.php પર જાઓ
if ($is_buy_now) {
    header('Location: address.php');
    exit(); // સ્ક્રિપ્ટ અહીં સમાપ્ત કરવી ખૂબ જ મહત્વપૂર્ણ છે
}

// જો singlepageview માંથી 'Add To Cart' AJAX રિક્વેસ્ટ આવી હોય, તો JSON જવાબ આપો
if ($is_ajax_request) {
    header('Content-Type: application/json');
    $cart_count = (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) ? count($_SESSION['cart']) : 0;
    echo json_encode(['success' => true, 'cart_count' => $cart_count]);
    exit(); // સ્ક્રિપ્ટ અહીં સમાપ્ત કરો
}

// અન્ય કોઈ પણ 'Add to Cart' ક્લિક માટે (જે AJAX નથી), પાછલા પેજ પર રીડાયરેક્ટ કરો
// (ઉદાહરણ તરીકે, જો તમે ભલામણ કરેલ પ્રોડક્ટ્સમાંથી ઉમેરો)
$previous_page = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $previous_page);
exit();
?>