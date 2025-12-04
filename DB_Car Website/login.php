<?php
session_start();
require_once 'config.php';  

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'customer') {
        header('Location: customer-dashboard.php');
    } else {
        header('Location: admin-dashboard.php');
    }
    exit;
}

$basketCount = 0;

// Initialize demo data
initializeDemoData();

function initializeDemoData() {
    $conn = getDBConnection();
    
    $customerPassword = password_hash('demo123', PASSWORD_DEFAULT);
    $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);

    $cityCheck = $conn->query("SELECT city_id FROM cities WHERE city_name = 'Birmingham'");
    if ($cityCheck->num_rows === 0) {
        $conn->query("INSERT INTO cities (city_name, region) VALUES ('Birmingham', 'West Midlands')");
        $cityId = $conn->insert_id;
    } else {
        $city = $cityCheck->fetch_assoc();
        $cityId = $city['city_id'];
    }

    $customerCheck = $conn->query("SELECT customer_id FROM customers WHERE email = 'customer@demo.com'");
    if ($customerCheck->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, email, password, phone, city_id) VALUES (?, ?, ?, ?, ?, ?)");
        $firstName = 'Demo';
        $lastName = 'Customer';
        $email = 'customer@demo.com';
        $phone = '0712345678';
        $stmt->bind_param("sssssi", $firstName, $lastName, $email, $customerPassword, $phone, $cityId);
        $stmt->execute();
        $stmt->close();
    }

    $adminCheck = $conn->query("SELECT agent_id FROM agents WHERE email = 'admin@motivcarrental.com'");
    if ($adminCheck->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO agents (first_name, last_name, email, password, phone, city_id, hire_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $firstName = 'Admin';
        $lastName = 'User';
        $email = 'admin@motivcarrental.com';
        $phone = '0712345679';
        $hireDate = date('Y-m-d');
        $stmt->bind_param("sssssis", $firstName, $lastName, $email, $adminPassword, $phone, $cityId, $hireDate);
        $stmt->execute();
        $stmt->close();
    }
    $conn->close(); 
}

// POST request for login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();

    $action = $_POST['action'] ?? '';
    $response = ['success' => false, 'message' => ''];
    
    try {
        if ($action === 'login') {
    $loginType = $_POST['loginType'] ?? 'customer';
    $password = $_POST['password'] ?? '';
    
    if ($loginType === 'customer') {
        $email = $_POST['email'] ?? '';
        
        if (empty($email) || empty($password)) {
            $response['message'] = 'Please fill in all fields';
        } else {
            $stmt = $conn->prepare("SELECT customer_id, first_name, last_name, email, password FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['customer_id'],
                        'firstName' => $user['first_name'],
                        'lastName' => $user['last_name'],
                        'email' => $user['email'],
                        'role' => 'customer'
                    ];
                    
                    $response = [
                        'success' => true,
                        'message' => 'Login successful',
                        'redirect' => 'customer-dashboard.php'
                    ];
                } else {
                    $response['message'] = 'Invalid email or password';
                }
            } else {
                $response['message'] = 'Customer not found';
            }
            $stmt->close();
        }
        
    } elseif ($loginType === 'admin') {
        $identifier = $_POST['email'] ?? ''; // now can be email and or member id 

        if (empty($identifier) || empty($password)) {
            $response['message'] = 'Please fill in all fields';
        } else {
            
            if (is_numeric($identifier)) {
                // Login with member ID
                $stmt = $conn->prepare("SELECT agent_id, first_name, last_name, email, password FROM agents WHERE agent_id = ?");
                $stmt->bind_param("i", $identifier);
            } else {
                // Login with email
                $stmt = $conn->prepare("SELECT agent_id, first_name, last_name, email, password FROM agents WHERE email = ?");
                $stmt->bind_param("s", $identifier);
            }
    
            $stmt->execute();
            $result = $stmt->get_result();
    
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user'] = [
                        'id' => $user['agent_id'],
                        'firstName' => $user['first_name'],
                        'lastName' => $user['last_name'],
                        'email' => $user['email'],
                        'role' => 'admin'
                    ];
                    
                    $response = [
                        'success' => true,
                        'message' => 'Admin login successful',
                        'redirect' => 'admin-dashboard.php'
                    ];
                } else {
                    $response['message'] = 'Invalid credentials';
                }
            } else {
                $response['message'] = 'Admin account not found';
            }
            $stmt->close();
        }
    }

            
        } elseif ($action === 'register') {
            $fullname = trim($_POST['fullname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $drivingLicence = trim($_POST['driving_licence'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $confirm_password = trim($_POST['confirm_password'] ?? '');

            
            if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
                $response['message'] = 'Please fill in all fields';
            } elseif ($password !== $confirm_password) {
                $response['message'] = 'Passwords do not match';
            } elseif (strlen($password) < 5) {
                $response['message'] = 'Password must be at least 5 characters';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Invalid email format';
            } else {
                $stmt = $conn->prepare("SELECT customer_id FROM customers WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $response['message'] = 'Email already registered';
                } else {
                    $stmt->close();
                    
                    $nameParts = explode(' ', $fullname, 2);
                    $firstName = $nameParts[0];
                    $lastName = $nameParts[1] ?? '';
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $cityCheck = $conn->query("SELECT city_id FROM cities WHERE city_name = 'Birmingham'");
                    if ($cityCheck->num_rows === 0) {
                        $conn->query("INSERT INTO cities (city_name, region) VALUES ('Birmingham', 'West Midlands')");
                        $cityId = $conn->insert_id;
                    } else {
                        $city = $cityCheck->fetch_assoc();
                        $cityId = $city['city_id'];
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO customers (first_name, last_name, email, password, city_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssssi", $firstName, $lastName, $email, $hashed_password, $cityId);
                    
                    if ($stmt->execute()) {
                        $customerId = $conn->insert_id;
                        
                        $_SESSION['user'] = [
                            'id' => $customerId,
                            'firstName' => $firstName,
                            'lastName' => $lastName,
                            'email' => $email,
                            'role' => 'customer'
                        ];
                        
                        $response = [
                            'success' => true,
                            'message' => 'Registration successful',
                            'redirect' => 'customer-dashboard.php'
                        ];
                    } else {
                        $response['message'] = 'Registration failed. Please try again.';
                    }
                }
                $stmt->close();
            }
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Server error: ' . $e->getMessage();
    }

    $conn->close();
    
    // AJAX requests
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // redirect when successful
    if ($response['success']) {
        header('Location: ' . $response['redirect']);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Login to Motiv.</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icons/favicon.ico" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="css/loginPage.css">
</head>

<body>


    <div class="LoginPageLimit">
        <div class="Background-colours">
            <div class="login-box">

                <div class="login100-pic js-tilt">
                    <img src="images/img-01.png" alt="IMG">
                </div>

                <form class="login100-form validate-form" id="authForm">
                    <span class="login-title">Member Login</span>

                    <!-- Login inputs -->
                    <div class="login-fields">
                        <div class="input-wrap validate-input" data-validate="Valid email is required: example@abc.xyz">
                            <input class="input100" type="text" name="email" id="loginEmail" placeholder="Email / Member ID">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100"><i class="fa fa-envelope" aria-hidden="true"></i></span>
                        </div>

                        <div class="input-wrap validate-input" data-validate="Password is required">
                            <input class="input100" type="password" name="password" id="loginPassword" placeholder="Password">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100"><i class="fa fa-lock" aria-hidden="true"></i></span>
                        </div>
                    </div>

                    <!-- Register inputs -->
                    <div class="register-fields">
                        <div class="input-wrap validate-input" data-validate="Full name is required">
                            <input class="input100" type="text" id="regFullname" name="fullname" placeholder="Full Name">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100"><i class="fa fa-user"></i></span>
                        </div>

                        <div class="input-wrap validate-input" data-validate="Valid email is required">
                            <input class="input100" type="text" id="regEmail" name="reg_email" placeholder="Email / Member ID">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100"><i class="fa fa-envelope"></i></span>
                        </div>

                        <div class="input-wrap validate-input" data-validate="Driving licence format: ABCDE123456AB12">
                            <input class="input100" type="text" id="regDriving" name="driving_licence" placeholder="Driving Licence Number">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100"><i class="fa fa-id-card"></i></span>
                        </div>


                        <div class="input-wrap validate-input" data-validate="Password is required">
                            <input class="input100" type="password" id="regPassword" placeholder="Password">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        </div>

                        <div class="input-wrap validate-input" data-validate="Confirm password">
                            <input class="input100" type="password" id="confirmPassword" placeholder="Confirm Password">
                            <span class="focus-input100"></span>
                            <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        </div>
                    </div>


                    <input type="hidden" name="action" id="formAction" value="login">
                    <input type="hidden" name="loginType" id="loginType" value="customer">

                    <div class="container" id="submitBtn">
                        <span class="text">LOGIN</span>
                        <svg class="fingerprint fingerprint-base" xmlns="http://www.w3.org/2000/svg" width="100"
                            height="100" viewBox="0 0 100 100">
                            <g class="fingerprint-out" fill="none" stroke-width="2" stroke-linecap="round">
                                <path class="odd"
                                    d="m 25.117139,57.142857 c 0,0 -1.968558,-7.660465 -0.643619,-13.149003 1.324939,-5.488538 4.659682,-8.994751 4.659682,-8.994751" />
                                <path class="odd"
                                    d="m 31.925369,31.477584 c 0,0 2.153609,-2.934998 9.074971,-5.105078 6.921362,-2.17008 11.799844,-0.618718 11.799844,-0.618718" />
                                <path class="odd"
                                    d="m 57.131213,26.814448 c 0,0 5.127709,1.731228 9.899495,7.513009 4.771786,5.781781 4.772971,12.109204 4.772971,12.109204" />
                                <path class="odd" d="m 72.334009,50.76769 0.09597,2.298098 -0.09597,2.386485" />
                                <path class="even"
                                    d="m 27.849282,62.75 c 0,0 1.286086,-1.279223 1.25,-4.25 -0.03609,-2.970777 -1.606117,-7.675266 -0.625,-12.75 0.981117,-5.074734 4.5,-9.5 4.5,-9.5" />
                                <path class="even"
                                    d="m 36.224282,33.625 c 0,0 8.821171,-7.174484 19.3125,-2.8125 10.491329,4.361984 11.870558,14.952665 11.870558,14.952665" />
                                <path class="even"
                                    d="m 68.349282,49.75 c 0,0 0.500124,3.82939 0.5625,5.8125 0.06238,1.98311 -0.1875,5.9375 -0.1875,5.9375" />
                                <path class="odd"
                                    d="m 31.099282,65.625 c 0,0 1.764703,-4.224042 2,-7.375 0.235297,-3.150958 -1.943873,-9.276886 0.426777,-15.441942 2.370649,-6.165056 8.073223,-7.933058 8.073223,-7.933058" />
                                <path class="odd"
                                    d="m 45.849282,33.625 c 0,0 12.805566,-1.968622 17,9.9375 4.194434,11.906122 1.125,24.0625 1.125,24.0625" />
                                <path class="even"
                                    d="m 59.099282,70.25 c 0,0 0.870577,-2.956221 1.1875,-4.5625 0.316923,-1.606279 0.5625,-5.0625 0.5625,-5.0625" />
                                <path class="even"
                                    d="m 60.901059,56.286612 c 0,0 0.903689,-9.415996 -3.801777,-14.849112 -3.03125,-3.5 -7.329245,-4.723939 -11.867187,-3.8125 -5.523438,1.109375 -7.570313,5.75 -7.570313,5.75" />
                                <path class="even"
                                    d="m 34.072577,68.846248 c 0,0 2.274231,-4.165782 2.839205,-9.033748 0.443558,-3.821814 -0.49394,-5.649939 -0.714206,-8.05386 -0.220265,-2.403922 0.21421,-4.63364 0.21421,-4.63364" />
                                <path class="odd"
                                    d="m 37.774165,70.831845 c 0,0 2.692139,-6.147592 3.223034,-11.251208 0.530895,-5.103616 -2.18372,-7.95562 -0.153491,-13.647655 2.030229,-5.692035 8.108442,-4.538898 8.108442,-4.538898" />
                                <path class="odd"
                                    d="m 54.391174,71.715729 c 0,0 2.359472,-5.427681 2.519068,-16.175068 0.159595,-10.747388 -4.375223,-12.993087 -4.375223,-12.993087" />
                                <path class="even"
                                    d="m 49.474282,73.625 c 0,0 3.730297,-8.451831 3.577665,-16.493718 -0.152632,-8.041887 -0.364805,-11.869326 -4.765165,-11.756282 -4.400364,0.113044 -3.875,4.875 -3.875,4.875" />
                                <path class="even"
                                    d="m 41.132922,72.334447 c 0,0 2.49775,-5.267079 3.181981,-8.883029 0.68423,-3.61595 0.353553,-9.413359 0.353553,-9.413359" />
                                <path class="odd"
                                    d="m 45.161782,73.75 c 0,0 1.534894,-3.679847 2.40625,-6.53125 0.871356,-2.851403 1.28125,-7.15625 1.28125,-7.15625" />
                                <path class="odd"
                                    d="m 48.801947,56.125 c 0,0 0.234502,-1.809418 0.109835,-3.375 -0.124667,-1.565582 -0.5625,-3.1875 -0.5625,-3.1875" />
                            </g>
                        </svg>
                        <svg class="fingerprint fingerprint-active" xmlns="http://www.w3.org/2000/svg" width="100"
                            height="100" viewBox="0 0 100 100">
                            <g class="fingerprint-out" fill="none" stroke-width="2" stroke-linecap="round">
                                <path class="odd"
                                    d="m 25.117139,57.142857 c 0,0 -1.968558,-7.660465 -0.643619,-13.149003 1.324939,-5.488538 4.659682,-8.994751 4.659682,-8.994751" />
                                <path class="odd"
                                    d="m 31.925369,31.477584 c 0,0 2.153609,-2.934998 9.074971,-5.105078 6.921362,-2.17008 11.799844,-0.618718 11.799844,-0.618718" />
                                <path class="odd"
                                    d="m 57.131213,26.814448 c 0,0 5.127709,1.731228 9.899495,7.513009 4.771786,5.781781 4.772971,12.109204 4.772971,12.109204" />
                                <path class="odd" d="m 72.334009,50.76769 0.09597,2.298098 -0.09597,2.386485" />
                                <path class="even"
                                    d="m 27.849282,62.75 c 0,0 1.286086,-1.279223 1.25,-4.25 -0.03609,-2.970777 -1.606117,-7.675266 -0.625,-12.75 0.981117,-5.074734 4.5,-9.5 4.5,-9.5" />
                                <path class="even"
                                    d="m 36.224282,33.625 c 0,0 8.821171,-7.174484 19.3125,-2.8125 10.491329,4.361984 11.870558,14.952665 11.870558,14.952665" />
                                <path class="even"
                                    d="m 68.349282,49.75 c 0,0 0.500124,3.82939 0.5625,5.8125 0.06238,1.98311 -0.1875,5.9375 -0.1875,5.9375" />
                                <path class="odd"
                                    d="m 31.099282,65.625 c 0,0 1.764703,-4.224042 2,-7.375 0.235297,-3.150958 -1.943873,-9.276886 0.426777,-15.441942 2.370649,-6.165056 8.073223,-7.933058 8.073223,-7.933058" />
                                <path class="odd"
                                    d="m 45.849282,33.625 c 0,0 12.805566,-1.968622 17,9.9375 4.194434,11.906122 1.125,24.0625 1.125,24.0625" />
                                <path class="even"
                                    d="m 59.099282,70.25 c 0,0 0.870577,-2.956221 1.1875,-4.5625 0.316923,-1.606279 0.5625,-5.0625 0.5625,-5.0625" />
                                <path class="even"
                                    d="m 60.901059,56.286612 c 0,0 0.903689,-9.415996 -3.801777,-14.849112 -3.03125,-3.5 -7.329245,-4.723939 -11.867187,-3.8125 -5.523438,1.109375 -7.570313,5.75 -7.570313,5.75" />
                                <path class="even"
                                    d="m 34.072577,68.846248 c 0,0 2.274231,-4.165782 2.839205,-9.033748 0.443558,-3.821814 -0.49394,-5.649939 -0.714206,-8.05386 -0.220265,-2.403922 0.21421,-4.63364 0.21421,-4.63364" />
                                <path class="odd"
                                    d="m 37.774165,70.831845 c 0,0 2.692139,-6.147592 3.223034,-11.251208 0.530895,-5.103616 -2.18372,-7.95562 -0.153491,-13.647655 2.030229,-5.692035 8.108442,-4.538898 8.108442,-4.538898" />
                                <path class="odd"
                                    d="m 54.391174,71.715729 c 0,0 2.359472,-5.427681 2.519068,-16.175068 0.159595,-10.747388 -4.375223,-12.993087 -4.375223,-12.993087" />
                                <path class="even"
                                    d="m 49.474282,73.625 c 0,0 3.730297,-8.451831 3.577665,-16.493718 -0.152632,-8.041887 -0.364805,-11.869326 -4.765165,-11.756282 -4.400364,0.113044 -3.875,4.875 -3.875,4.875" />
                                <path class="even"
                                    d="m 41.132922,72.334447 c 0,0 2.49775,-5.267079 3.181981,-8.883029 0.68423,-3.61595 0.353553,-9.413359 0.353553,-9.413359" />
                                <path class="odd"
                                    d="m 45.161782,73.75 c 0,0 1.534894,-3.679847 2.40625,-6.53125 0.871356,-2.851403 1.28125,-7.15625 1.28125,-7.15625" />
                                <path class="odd"
                                    d="m 48.801947,56.125 c 0,0 0.234502,-1.809418 0.109835,-3.375 -0.124667,-1.565582 -0.5625,-3.1875 -0.5625,-3.1875" />
                            </g>
                        </svg>

                        <svg class="ok" xmlns="http://www.w3.org/2000/svg" width="100" height="100"
                            viewBox="0 0 100 100">
                            <path d="M34.912 50.75l10.89 10.125L67 36.75" fill="none" stroke="#fff" stroke-width="6" />
                        </svg>

                        <svg class="cross" xmlns="http://www.w3.org/2000/svg" width="100" height="100"
                            viewBox="0 0 100 100">
                            <path d="M30 30 L70 70 M70 30 L30 70" fill="none" stroke="#fff" stroke-width="6"
                                stroke-linecap="round" />
                        </svg>
                    </div>

                    <div class="text-center p-t-12">
                        <span class="txt1">Forgot</span>
                        <a class="txt2" href="forgotPassword.php">Username / Password?</a>
                    </div>

                    <div class="text-center p-t-136">
                        <a class="txt2" href="#" id="toggleForm">
                            Create your Account
                            <i class="fa fa-long-arrow-right m-l-5" aria-hidden="true"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<script src="js/loginPage.js"></script>
    
</body>

</html>
