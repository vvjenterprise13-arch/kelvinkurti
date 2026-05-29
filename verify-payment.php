<?php
ob_start(); // Keep this here!
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('database/connection.php');
require('vendor/autoload.php');

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// !! VERY IMPORTANT !!
// આ એક સિક્રેટ કી છે. આ કી બંને ડોમેન પરની ફાઇલોમાં સમાન હોવી જોઈએ.
// આને બદલીને કંઈક લાંબુ અને રેન્ડમ રાખો.
define('CROSS_DOMAIN_SECRET_KEY', 'Your_Very_Long_And_Secret_Key_Goes_Here_12345');


if (empty($_POST['razorpay_payment_id']) || empty($_POST['razorpay_signature']) || empty($_POST['razorpay_order_id'])) {
    die('Invalid access. Required data is missing.');
}

// વેરીએબલને શરૂઆતમાં ખાલી સેટ કરો
$razorpay_key_id = '';
$razorpay_key_secret = '';
$runsite_url = ''; // 'runsite' URL માટે નવો વેરીએબલ

// ======================== નવો ફેરફાર અહીં છે ========================
// 1. ડેટાબેઝમાંથી 'runsite' પણ મેળવો
$creds_result = mysqli_query($conn, "SELECT razorpay_key_id, razorpay_key_secret, runsite FROM credentials LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $razorpay_key_id = $fetch_creds['razorpay_key_id'];
    $razorpay_key_secret = $fetch_creds['razorpay_key_secret'];
    $runsite_url = $fetch_creds['runsite']; // 'runsite' ની કિંમત મેળવો
}

// ખાતરી કરો કે જરૂરી બધી વિગતો મળી છે
if (empty($razorpay_key_id) || empty($razorpay_key_secret)) {
    die("Payment gateway keys are not configured.");
}
// 2. ખાતરી કરો કે 'runsite' URL સેટ છે
if (empty($runsite_url)) {
    die("Critical Error: 'runsite' URL is not configured in the database.");
}
// ====================================================================

$success = true;
$error = "Payment Failed";

try {
    $api = new Api($razorpay_key_id, $razorpay_key_secret);
    $attributes = [
        'razorpay_order_id'   => $_POST['razorpay_order_id'],
        'razorpay_payment_id' => $_POST['razorpay_payment_id'],
        'razorpay_signature'  => $_POST['razorpay_signature']
    ];
    $api->utility->verifyPaymentSignature($attributes);
} catch(SignatureVerificationError $e) {
    $success = false;
    $error = 'Razorpay Error: ' . $e->getMessage();
}

if ($success === true) {
    // PAYMENT IS SUCCESSFUL

    $payment_id = $_POST['razorpay_payment_id'];
    $order_id = $_POST['razorpay_order_id'];
    $status = 'success';
    
    $data_string_to_hash = $payment_id . $order_id . $status;
    $token = hash_hmac('sha256', $data_string_to_hash, CROSS_DOMAIN_SECRET_KEY);

    // ======================== નવો ફેરફાર અહીં છે ========================
    // 3. 'runsite' URL નો ઉપયોગ કરીને ડાયનેમિક રીડાયરેક્ટ URL બનાવો
    // rtrim ખાતરી કરે છે કે URL ના અંતમાં ડબલ સ્લેશ (//) ન આવે.
    $redirect_url = rtrim($runsite_url, '/') . "/thankyou.php?" . http_build_query([
        'pid' => $payment_id,
        'oid' => $order_id,
        'status' => $status,
        'token' => $token
    ]);
    // ====================================================================
    
    header('Location: ' . $redirect_url);
    exit();

} else {
    // Payment failed
    // ======================== નવો ફેરફાર અહીં છે ========================
    // 4. ભૂલ પેજ માટે પણ 'runsite' URL નો ઉપયોગ કરો
    $error_url = rtrim($runsite_url, '/') . "/payment-error.php";
    // ====================================================================
    
    header('Location: ' . $error_url);
    exit();
}

ob_end_flush();
?>