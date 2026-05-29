<?php
session_start();
include('database/connection.php');

$response = [
    'items' => [],
    'subtotal' => 0,
    'item_count' => 0
];

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $cart_ids = array_map('intval', $_SESSION['cart']);
    $id_string = implode(',', $cart_ids);

    // વેબસાઇટની મુખ્ય લિંક મેળવવા માટે
    $select_site = "SELECT * from credentials";
    $search_site = mysqli_query($conn, $select_site);
    $pwebsite = ($fetch_site = mysqli_fetch_array($search_site)) ? $fetch_site['site'] : '';

    $sql = "SELECT id, name, total, image FROM products WHERE id IN ($id_string)";
    $result = mysqli_query($conn, $sql);

    $subtotal = 0;
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $row['image_url'] = $pwebsite . 'assets/uploads/' . $row['image'];
            $response['items'][] = $row;
            $subtotal += (float)$row['total'];
        }
    }
    $response['subtotal'] = number_format($subtotal, 2);
    $response['item_count'] = count($cart_ids);
}

// JSON ફોર્મેટમાં પ્રતિસાદ મોકલો
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>