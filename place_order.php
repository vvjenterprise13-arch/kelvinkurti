<?php
// PHP Error Reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('database/connection.php');

// ખાતરી કરો કે જરૂરી સેશન વેરિયેબલ્સ હાજર છે
if (
    !isset($_SESSION['cart']) || empty($_SESSION['cart']) ||
    !isset($_SESSION['address']) || empty($_SESSION['address']) ||
    !isset($_SESSION['final_amount'])
) {
    // જો ડેટા ખૂટતો હોય તો યુઝરને પાછા મોકલો
    header('Location: cart.php');
    exit();
}

// સેશનમાંથી ડેટા મેળવો
$cart_items = $_SESSION['cart']; // આ એક એરે છે: [product_id => quantity]
$address = $_SESSION['address'];
$final_amount = $_SESSION['final_amount'];

// પ્રોડક્ટની કિંમત મેળવવા માટે ડેટાબેઝમાંથી ફરીથી ક્વેરી કરો
$product_ids = array_keys($cart_items);
$id_string = implode(',', array_map('intval', $product_ids));
$products_from_db = [];
if (!empty($id_string)) {
    $sql = "SELECT id, total FROM products WHERE id IN ($id_string)";
    $result = mysqli_query($conn, $sql);
    while ($item = mysqli_fetch_assoc($result)) {
        $products_from_db[$item['id']] = $item;
    }
}

// INSERT સ્ટેટમેન્ટ તૈયાર કરો (SQL ઇન્જેક્શનથી બચવા માટે)
// તમારા ટેબલના કોલમ સાથે મેળ ખાય છે તેની ખાતરી કરો.
$stmt = mysqli_prepare($conn, "INSERT INTO orders_checkout (quantity, price, product_id, mobile, name, flat, area, city, state, pin, pay_to) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if ($stmt === false) {
    die('MySQLi prepare error: ' . mysqli_error($conn));
}

// કાર્ટમાંની દરેક આઇટમ માટે લૂપ ચલાવો અને ડેટા દાખલ કરો
foreach ($cart_items as $product_id => $quantity) {
    if (isset($products_from_db[$product_id])) {
        // 'total' ને વેચાણ કિંમત તરીકે વાપરો
        $product_price = $products_from_db[$product_id]['total']; 

        // ખાતરી કરો કે state અને pin તમારા $_SESSION['address'] માં છે
        $state = isset($address['state']) ? $address['state'] : ''; // જો ન હોય તો ખાલી સ્ટ્રિંગ
        $pin = isset($address['pin']) ? $address['pin'] : '';       // જો ન હોય તો ખાલી સ્ટ્રિંગ

        // સ્ટેટમેન્ટમાં પેરામીટર્સને બાઇન્ડ કરો
        mysqli_stmt_bind_param(
            $stmt,
            'ididssssssd', // i=integer, d=double, s=string
            $quantity,
            $product_price,
            $product_id,
            $address['number'],  // mobile
            $address['name'],    // name
            $address['flat'],    // flat
            $address['area'],    // area
            $address['city'],    // city
            $state,              // state
            $pin,                // pin
            $final_amount        // pay_to
        );

        // સ્ટેટમેન્ટ એક્ઝિક્યુટ કરો
        if (!mysqli_stmt_execute($stmt)) {
            // જો કોઈ ભૂલ હોય તો તેને લોગ કરો
            error_log("ઓર્ડર આઇટમ દાખલ કરવામાં નિષ્ફળ. Product ID: $product_id. Error: " . mysqli_stmt_error($stmt));
            die('ઓર્ડર આપવામાં ભૂલ આવી. કૃપા કરીને ફરી પ્રયાસ કરો.');
        }
    }
}

// સ્ટેટમેન્ટ બંધ કરો
mysqli_stmt_close($stmt);

// (વૈકલ્પિક) ઓર્ડર પ્લેસ થયા પછી કાર્ટ ખાલી કરો.
// સફળ પેમેન્ટ પછી આ કરવું વધુ સારું છે.
// unset($_SESSION['cart']); 
// unset($_SESSION['address']);

// યુઝરને પેમેન્ટ પેજ પર રીડાયરેક્ટ કરો
header('Location: payment.php');
exit();
?>