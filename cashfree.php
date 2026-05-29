<?php
// great/cashfree.php (with keys and mode from the database)

// To display errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Include the database connection file
include('database/connection.php');

// Ensure the connection was successful
if (!isset($conn) || !$conn) {
    die("Database connection failed. Please check the 'database/connection.php' file.");
}

// =================== Get Credentials from Database ===================
$app_id = '';
$secret_key = '';
$cashfree_mode = 'sandbox'; // Default to sandbox
$runsite_url = '';

$query = "SELECT cashfree_app_id, cashfree_secret_key, cashfree_mode, runsite FROM credentials LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && $credentials = mysqli_fetch_assoc($result)) {
    $app_id         = $credentials['cashfree_app_id'];
    $secret_key     = $credentials['cashfree_secret_key'];
    $cashfree_mode  = strtolower(trim($credentials['cashfree_mode']));
    $runsite_url    = rtrim($credentials['runsite'], '/');
}

// Ensure that the keys and URL were found
if (empty($app_id) || empty($secret_key) || empty($runsite_url)) {
    die("<h1>Error:</h1> <p>Configuration details (App ID, Secret Key, or Site URL) not found in the database.</p>");
}

// Set the API URL based on the mode
$CASHFREE_API_URL = ($cashfree_mode === 'prod') 
    ? 'https://api.cashfree.com/pg/orders' 
    : 'https://sandbox.cashfree.com/pg/orders';

// Check if data has been received via the POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['final_amount'])) {
    die("Invalid access or amount missing.");
}

// =========================================================================
// * RANDOM CUSTOMER DETAILS GENERATED HERE *
// =========================================================================
$first_names = [
    "Aarav","Vihaan","Aditya","Sai","Arjun","Reyansh","Aryan","Anika","Saanvi","Diya","Pari","Myra","Aadhya","Ishita","Rohan","Priya",
    "Krishna","Lakshmi","Manish","Sonal","Harsh","Kavya","Tanvi","Amit","Sneha","Rajesh","Pooja","Rahul","Bhavna","Chetan","Nikita","Ritika",
    "Gaurav","Komal","Jatin","Meera","Ramesh","Nandini","Pranav","Swati","Deepak","Simran","Anjali","Vikas","Reema","Chirag","Neha","Kiran",
    "Suresh","Geeta","Mohit","Payal","Sanjay","Alka","Vivek","Shweta","Anand","Divya","Ashok","Ruchi","Naveen","Shikha","Dinesh","Monika",
    "Hemant","Seema","Varun","Kajal","Yash","Pinky","Mukul","Anusha","Karan","Asmita","Akash","Smita","Abhay","Karishma","Siddharth","Juhi",
    "Anil","Usha","Balaji","Radha","Siddhi","Rajiv","Shivani","Raj","Trisha","Suraj","Muskan","Akhil","Avni","Pradeep","Roshni","Devansh","Pallavi",
    "Om","Jaya","Mohan","Nisha","Chandra","Bhavya","Vishal","Ira","Sagar","Avantika","Dhruv","Tanya","Kabir","Aarohi","Shyam","Amrita","Jay","Lavanya",
    "Tejas","Supriya","Harsha","Anaya","Nitin","Yamini","Vijay","Mahima","Anup","Aaradhya","Parth","Khushi","Satyam","Isha","Shubham","Ria",
    "Raghav","Mitali","Naman","Tara","Tarun","Megha","Ravi","Srishti","Ajay","Poonam","Siddhant","Anushka","Arnav","Vaishnavi","Kunal","Aanya",
    "Hemangi","Chaitanya","Sanjana","Radhika","Madhav","Harini","Madhuri","Vidya","Omkar","Shanaya","Abhishek","Gitanjali","Puneet","Sakshi",
    "Rajendra","Bharti","Milind","Neelam","Dev","Anvi","Tushar","Manisha","Sharad","Kashish","Rupesh","Shruti","Arvind","Tanisha","Himanshu","Niharika",
    "Darshan","Shreya","Ganesh","Aarushi","Mahesh","Suvarna","Alok","Pallavi","Sudhir","Mridula","Prakash","Vaidehi","Saurabh","Anamika","Santosh","Kirti",
    "Narendra","Hemlata","Vipin","Sanya","Shiv","Charu","Lokesh","Prerna","Roshan","Chhavi","Anmol","Srishti","Akhilesh","Tanu","Rajat","Mona",
    "Ayaan","Prisha","Adnan","Ayushi","Sameer","Anusha","Imran","Sana","Farhan","Shazia","Aftab","Zoya","Salman","Heena","Aslam","Rukhsar","Faizan","Shabnam",
    "Arif","Nazma","Sohail","Parveen","Yusuf","Razia","Naseer","Tabassum","Irfan","Nilofer","Khalid","Mehnaz","Shahid","Afreen","Javed","Shagufta",
    "Wasim","Najma","Rizwan","Shamina","Tariq","Kausar","Sabir","Ruksana","Anwar","Nusrat","Amjad","Shireen","Azhar","Sultana","Saeed","Rubina",
    "Rameez","Aaliya","Iqbal","Hina","Nawaz","Saira","Shakir","Arifa","Arsalan","Asiya","Majid","Shamim","Sami","Rizwana","Naeem","Nasreen",
    "Owais","Lubna","Hassan","Shamaila","Habib","Aamna","Taha","Farida","Zubair","Nagma","Rehan","Noor","Adil","Shama","Sarfaraz","Tahira",
    // … (extend till 500+ names, both Hindu/Muslim/Christian/Sikh mix, modern + traditional)
];

$last_names = [
    "Sharma","Verma","Gupta","Singh","Kumar","Patel","Shah","Reddy","Yadav","Jain","Mehta","Chopra",
    "Iyer","Nair","Menon","Pillai","Warrier","Panicker","Kurup","Chettiar","Mudaliar","Naidu","Rao","Shetty",
    "Gowda","Acharya","Kulkarni","Deshmukh","Patil","Chavan","Sawant","Gaikwad","Bhosale","Jadhav","Shinde","More",
    "Salunkhe","Deshpande","Joshi","Apte","Phadke","Gokhale","Vaidya","Sane","Kelkar","Damle","Inamdar","Bendre",
    "Banerjee","Chatterjee","Mukherjee","Bhattacharya","Ganguly","Sengupta","Datta","Dey","Bose","Roy","Saha","Pal",
    "Choudhury","Ghosh","Das","Lahiri","Bagchi","Kar","Sen","Halder","Mitra","Chakraborty","Barua","Sarma","Kalita",
    "Gogoi","Phukan","Saikia","Bhuyan","Hazarika","Borah","Deka","Medhi","Neog","Dasgupta","Chakravarty","Tripathi",
    "Tiwari","Mishra","Shukla","Pandey","Dubey","Chaturvedi","Pathak","Trivedi","Upadhyay","Dwivedi","Jha","Rastogi",
    "Saxena","Agarwal","Bansal","Khandelwal","Mittal","Goenka","Kejriwal","Lohia","Poddar","Tulsiyan","Saraf","Seth",
    "Kapoor","Malhotra","Khanna","Bhatia","Anand","Mehrotra","Sodhi","Ahluwalia","Chopra","Kohli","Oberoi","Nanda",
    "Gill","Bedi","Puri","Sethi","Grover","Tandon","Wadhwa","Suri","Walia","Arora","Mann","Sandhu","Dhillon","Sidhu",
    "Brar","Randhawa","Bajwa","Grewal","Chahal","Pannu","Monga","Sekhon","Bhullar","Deol","Toor","Virk","Cheema",
    "Hans","Riar","Bains","Pannu","Dhaliwal","Bath","Bal","Kang","Sekhon","Gill","Sidana","Bains","Lamba",
    "Thomas","Mathew","Varghese","Kurian","Paul","Joseph","Abraham","George","John","Antony","Cherian","Philip",
    "Sebastian","Mathews","Manoj","Sunny","Alex","Raju","Simon","Francis","Benny","Michael","Peter","Daniel","David",
    // … (extend till 500+ last names, covering Hindu, Muslim, Christian, Sikh, Jain surnames pan India)
];
$email_domains = ["@gmail.com", "@outlook.com", "@yahoo.com", "@hotmail.com"];

// Generate random details
$random_name = $first_names[array_rand($first_names)] . ' ' . $last_names[array_rand($last_names)];
$random_domain = $email_domains[array_rand($email_domains)];
$random_email = strtolower(str_replace(' ', '.', $random_name)) . rand(10, 999) . $random_domain;

// =================================================================
// * THIS IS THE CORRECTED LINE OF CODE *
$random_mobile = rand(6, 9) . rand(100000000, 999999999);
// =================================================================

// Get the details for the payment
$final_payable_amount = number_format((float)$_POST['final_amount'], 2, '.', '');
$customer_name        = $random_name;
$customer_email       = $random_email;
$customer_contact     = $random_mobile; // Using the corrected 10-digit number
$product_info         = 'Payment for your Order';

// Create a unique order ID
$order_id = 'ORD_' . time() . rand(100, 999);
$_SESSION['order_id_for_verification'] = $order_id;

// The URL to return to after payment
$return_url = $runsite_url . '/verify-cashfree-payment.php';

// Prepare data for the Cashfree API
$order_data = [
    'order_id'          => $order_id,
    'order_amount'      => $final_payable_amount,
    'order_currency'    => 'INR',
    'order_note'        => $product_info,
    'customer_details'  => [
        'customer_id'   => 'CUST_' . time() . rand(100, 999),
        'customer_name' => $customer_name,
        'customer_email'=> $customer_email,
        'customer_phone'=> $customer_contact,
    ],
    'order_meta' => [
        'return_url' => $return_url . '?order_id={order_id}'
    ]
];

// Headers for the API request
$headers = [
    'Content-Type: application/json',
    'x-client-id: ' . $app_id,
    'x-client-secret: ' . $secret_key,
    'x-api-version: 2022-09-01'
];

// Make the API call using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $CASHFREE_API_URL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($order_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$payment_session_id = null;
if ($http_status == 200 || $http_status == 201) {
    $response_data = json_decode($response, true);
    if (isset($response_data['payment_session_id'])) {
        $payment_session_id = $response_data['payment_session_id'];
    }
}

// If the payment session ID is not created, show an error
if (is_null($payment_session_id)) {
    echo "<h2>Failed to create payment session</h2>";
    echo "<p>HTTP Status: {$http_status}</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    die();
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Payment...</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f1f2f4; margin: 0; }
        .container { text-align: center; padding: 30px; background-color: #fff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .loader { border: 8px solid #e3e3e3; border-radius: 50%; border-top: 8px solid #4f46e5; width: 60px; height: 60px; animation: spin 1.5s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <div class="loader"></div>
        <h2>Please wait...</h2>
        <p>You are being redirected to our secure payment page.</p>
    </div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const cashfree_js_mode = "<?php echo ($cashfree_mode === 'prod' ? 'production' : 'sandbox'); ?>";
        
        const cashfree = new Cashfree({
            mode: cashfree_js_mode
        });

        const paymentSessionId = "<?php echo $payment_session_id; ?>";
        const checkoutOptions = {
            paymentSessionId: paymentSessionId,
            redirectTarget: "_self"
        }
        cashfree.checkout(checkoutOptions);
    });
</script>
</body>
</html>