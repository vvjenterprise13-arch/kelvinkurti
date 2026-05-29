<?php
// PHP Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('database/connection.php');

// POST મેથડથી ડેટા આવે છે કે નહીં તે તપાસો
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['final_amount']) || empty($_POST['final_amount'])) {
    die("Invalid access. Please proceed from the checkout page.");
}

// --- PayU માટે જરૂરી વિગતો મેળવો ---
$final_payable_amount = number_format($_POST['final_amount'], 2, '.', '');
$customer_name        = htmlspecialchars($_POST['customer_name'] ?? 'Guest User');
$customer_email       = htmlspecialchars($_POST['customer_email'] ?? 'guest@example.com');
$customer_contact     = htmlspecialchars($_POST['customer_contact'] ?? '9999999999');
$product_info         = 'Payment for Online Order';

// ટ્રાન્ઝેક્શન ID મેનેજ કરો (PayU તેને 'txnid' કહે છે)
$txnid = 'TXN_' . time() . rand(100, 999);
$_SESSION['txnid_for_verification'] = $txnid;

// --- ડેટાબેઝમાંથી PayU કી અને અન્ય વિગતો મેળવો ---
$merchant_key = '';
$merchant_salt = '';
$runsite_url = ''; // 'site' ને બદલે 'runsite' માટે વેરીએબલ
$payu_mode = 'live'; // ડિફોલ્ટ મોડ

// ======================== અહીં મુખ્ય ફેરફાર છે ========================
// 1. ડેટાબેઝ ક્વેરીમાં 'site' ને બદલે 'runsite' મેળવો
$creds_result = mysqli_query($conn, "SELECT runsite, payu_key, payu_salt, payu_mode FROM credentials LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $merchant_key = $fetch_creds['payu_key'] ?? '';
    $merchant_salt = $fetch_creds['payu_salt'] ?? '';
    $payu_mode = $fetch_creds['payu_mode'] ?? 'live';
    // 2. ડેટાબેઝમાંથી 'runsite' ની કિંમત મેળવો
    $runsite_url = $fetch_creds['runsite'] ?? '';
}
// ====================================================================

// ખાતરી કરો કે જરૂરી બધી વિગતો મળી છે
if (empty($merchant_key) || empty($merchant_salt) || empty($runsite_url)) {
    die("Payment gateway is not configured properly. Please contact support.");
}

// PayU ના પર્યાવરણ (Environment) મુજબ URL સેટ કરો
$PAYU_BASE_URL = (strtolower($payu_mode) === 'live') ? 'https://secure.payu.in/_payment' : 'https://test.payu.in/_payment';

// ======================== અહીં મુખ્ય ફેરફાર છે ========================
// 3. ડેટાબેઝમાંથી મળેલા 'runsite' URL નો ઉપયોગ કરો
$dynamic_runsite_url = rtrim($runsite_url, '/'); // URL ના અંતમાંથી વધારાનો '/' હટાવો
$success_url = $dynamic_runsite_url . '/verify-payment.php'; // સફળતા માટેનો URL
$failure_url = $dynamic_runsite_url . '/payment-failed.php'; // નિષ્ફળતા માટેનો URL
// ====================================================================

// --- PayU માટે Hash બનાવવાની પ્રક્રિયા ---
// આ ક્રમ ખૂબ જ મહત્વપૂર્ણ છે. તેમાં ફેરફાર કરશો નહીં.
$hash_string = $merchant_key . '|' . $txnid . '|' . $final_payable_amount . '|' . $product_info . '|' . $customer_name . '|' . $customer_email . '|||||||||||' . $merchant_salt;
$hash = strtolower(hash('sha512', $hash_string));
?>
<!DOCTYPE html>
<html lang="gu-IN">
<head>
    <title>ચુકવણી માટે રીડાયરેક્ટ કરી રહ્યાં છીએ...</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f1f2f4; margin: 0; }
        .container { text-align: center; padding: 30px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .loader { border: 8px solid #e3e3e3; border-radius: 50%; border-top: 8px solid #fb641b; width: 60px; height: 60px; animation: spin 1.5s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        h2 { color: #333; }
        p { color: #666; }
    </style>
</head>
<body onload="document.getElementById('payuForm').submit();">

    <div class="container">
        <div class="loader"></div>
        <h2>Please wait...</h2>
<p>You are being redirected to our secure payment page.</p>
<p>Do not close or refresh this page.</p>

    </div>

    <form action="<?php echo $PAYU_BASE_URL; ?>" method="post" id="payuForm" style="display: none;">
        <input type="hidden" name="key" value="<?php echo htmlspecialchars($merchant_key); ?>" />
        <input type="hidden" name="txnid" value="<?php echo htmlspecialchars($txnid); ?>" />
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($final_payable_amount); ?>" />
        <input type="hidden" name="productinfo" value="<?php echo htmlspecialchars($product_info); ?>" />
        <input type="hidden" name="firstname" value="<?php echo htmlspecialchars($customer_name); ?>" />
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($customer_email); ?>" />
        <input type="hidden" name="phone" value="<?php echo htmlspecialchars($customer_contact); ?>" />
        <input type="hidden" name="surl" value="<?php echo htmlspecialchars($success_url); ?>" />
        <input type="hidden" name="furl" value="<?php echo htmlspecialchars($failure_url); ?>" />
        <input type="hidden" name="service_provider" value="payu_paisa" />
        <input type="hidden" name="hash" value="<?php echo htmlspecialchars($hash); ?>" />
    </form>

</body>
</html>