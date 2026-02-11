<?php
session_start();

// Database connection
$host = 'localhost';   
$username = 'cs2team1';
$password = 'GIzgRTkFQWYg5bByiUxSMhhcJ';
$database = 'cs2team1_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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
    global $conn;
    
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
}

// POST request for login or register
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
                $identifier = $_POST['email'] ?? '';

                if (empty($identifier) || empty($password)) {
                    $response['message'] = 'Please fill in all fields';
                } else {
                    if (is_numeric($identifier)) {
                        $stmt = $conn->prepare("SELECT agent_id, first_name, last_name, email, password FROM agents WHERE agent_id = ?");
                        $stmt->bind_param("i", $identifier);
                    } else {
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

    // AJAX request
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Login Page CSS Variables - To avoid conflicts with landing.css */
        :root {
            --login-dark-magenta: #1800AD;
            --login-vivid-indigo: #8C0050;
            --login-cobalt-blue: #004AAD;
            --login-coral-red: #FF7F50;
            --login-vivid-red: #FF0000;
            --login-light-gray: #f8f8f8;
            --login-white: #ffffff;
        }

        /* Login Page Header Styles */
        .login-header {
            background: linear-gradient(to right, var(--login-vivid-indigo), var(--login-dark-magenta));
            color: white;
            height: 80px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
            z-index: 1000;
        }

        .login-header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .login-logo img {
            height: 115px;
            width: auto;
            margin-top: 3px;
            margin-left: -40px;
        }

        .login-nav ul {
            display: flex;
            gap: 25px;
            list-style: none;
        }

        .login-nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 4px;
            transition: 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }

        .login-nav ul li.dropdown {
            position: relative;
            height: 100%;
            display: flex;
            align-items: center;
        }

        .login-nav ul li.dropdown .dropbtn {
            display: flex;
            align-items: center;
            gap: 5px;
            height: 100%;
            padding: 0 12px;
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: 0.3s ease;
            white-space: nowrap;
            position: relative;
            cursor: pointer;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-nav ul li.dropdown .dropbtn i {
            font-size: 12px;
            margin-left: 3px;
        }

        .login-nav ul li.dropdown .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 120px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1001;
            border-radius: 5px;
            overflow: hidden;
            top: 100%;
            left: 0;
            margin-top: 0;
        }

        .login-nav ul li.dropdown:hover .dropdown-content {
            display: block;
        }

        .login-nav ul li.dropdown:hover .dropbtn {
            background-color: rgba(255, 255, 255, 0.25);
        }

        .login-nav ul li.dropdown .dropdown-content a {
            color: #333;
            padding: 10px 14px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
            border-bottom: 1px solid #f1f1f1;
            font-weight: 600;
            font-size: 0.9rem;
            line-height: 1.2;
            height: auto;
            white-space: nowrap;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-nav ul li.dropdown .dropdown-content a:last-child {
            border-bottom: none;
        }

        .login-nav ul li.dropdown .dropdown-content a:hover {
            background-color: #f8f9fa;
            color: var(--login-vivid-indigo);
        }

        .login-basket-indicator .basket-count {
            background-color: var(--login-coral-red);
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-left: 5px;
            position: relative;
            top: -2px;
        }

        /* Original Login Page CSS (unchanged) */
        * {
          margin: 0px;
          padding: 0px;
          box-sizing: border-box;
        }

        body,
        html {
          height: 100%;
        }

        a {
          font-size: 14px;
          line-height: 1.7;
          color: #666666;
          margin: 0px;
          transition: all 0.4s;
          -webkit-transition: all 0.4s;
          -o-transition: all 0.4s;
          -moz-transition: all 0.4s;
        }

        a:focus {
          outline: none !important;
        }

        a:hover {
          text-decoration: none;
          color: #d96817;
        }

        p {
          font-size: 14px;
          line-height: 1.7;
          color: #666666;
          margin: 0px;
        }

        ul,
        li {
          margin: 0px;
          list-style-type: none;
        }

        /* inputs */
        input {
          outline: none;
          border: none;
        }

        textarea {
          outline: none;
          border: none;
        }

        textarea:focus,
        input:focus {
          border-color: transparent !important;
        }

        button {
          outline: none !important;
          border: none;
          background: transparent;
        }

        button:hover {
          cursor: pointer;
        }

        iframe {
          border: none !important;
        }

        /*TEXT*/
        .txt1 {
          font-size: 13px;
          line-height: 1.5;
          color: #999999;
        }

        .txt2 {
          font-size: 13px;
          line-height: 1.5;
          color: #666666;
        }

        .text-center {
          text-align: center;
        }

        .p-t-12 {
          padding-top: 10px;
        }

        .p-t-136 {
          padding-top: 100px;
        }

        /* LOGIN */
        .LoginPageLimit {
          width: 100%;
          margin: 0 auto;
        }

        .Background-colours {
          width: 100%;
          min-height: 100vh;
          display: -webkit-box;
          display: -webkit-flex;
          display: -moz-box;
          display: -ms-flexbox;
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          align-items: center;
          padding: 15px;
          background: linear-gradient(-135deg,
              rgba(140, 0, 80, 0.8),
              rgba(65, 88, 208, 0.9)
            );
        }

        .login-box {
          width: 960px;
          background: #fff;
          border-radius: 10px;
          overflow: hidden;
          align-content: center;
          display: -webkit-box;
          display: -webkit-flex;
          display: -moz-box;
          display: -ms-flexbox;
          display: flex;
          flex-wrap: wrap;
          justify-content: space-between;
          padding: 177px 130px 33px 95px;
        }

        .login100-pic {
          width: 316px;
        }

        .login100-pic img {
          max-width: 100%;
        }

        .timmy-format-div {
          display: flex;
          justify-content: center;
          align-items: center;
        }

        .login100-form .input-wrap {
          width: 100%;
          max-width: 290px;
          margin: 0 auto 12px auto;
        }

        .login-title {
          font-family: Poppins-Bold;
          font-size: 24px;
          color: #333333;
          line-height: 1.2;
          text-align: center;
          width: 100%;
          display: block;
          padding-bottom: 54px;
        }

        .input-wrap {
          position: relative;
          width: 100%;
          z-index: 1;
          margin-bottom: 10px;
        }

        .alert-validate.input-wrap {
          margin-bottom: 50px;
        }

        .input100 {
          font-size: 15px;
          line-height: 1.5;
          color: #666666;
          display: block;
          width: 100%;
          background: #e6e6e6;
          height: 50px;
          border-radius: 25px;
          padding: 0 30px 0 68px;
        }

        /*shadows and focus */
        .focus-input100 {
          display: block;
          position: absolute;
          border-radius: 25px;
          bottom: 0;
          left: 0;
          z-index: -1;
          width: 100%;
          height: 100%;
          box-shadow: 0px 0px 0px 0px;
          color: #d96817;
        }

        .input100:focus+.focus-input100 {
          -webkit-animation: anim-shadow 0.5s ease-in-out forwards;
          animation: anim-shadow 0.5s ease-in-out forwards;
        }

        @-webkit-keyframes anim-shadow {
          to {
            box-shadow: 0px 0px 70px 25px;
            opacity: 0;
          }
        }

        @keyframes anim-shadow {
          to {
            box-shadow: 0px 0px 70px 25px;
            opacity: 0;
          }
        }

        .symbol-input100 {
          font-size: 15px;
          display: -webkit-box;
          display: -webkit-flex;
          display: -moz-box;
          display: -ms-flexbox;
          display: flex;
          align-items: center;
          position: absolute;
          border-radius: 25px;
          bottom: 0;
          left: 0;
          width: 100%;
          height: 100%;
          padding-left: 35px;
          pointer-events: none;
          color: #666666;
          -webkit-transition: all 0.4s;
          -o-transition: all 0.4s;
          -moz-transition: all 0.4s;
          transition: all 0.4s;
        }

        .input100:focus+.focus-input100+.symbol-input100 {
          color: #d96817;
          padding-left: 28px;
        }

        /*media responsiveness defaults*/
        @media (max-width: 992px) {
          .login-box {
            padding: 177px 90px 33px 85px;
          }
          .login100-pic {
            width: 43%;
          }
          .login100-form {
            width: 50%;
          }
          .login100-form.register-mode .input-wrap {
            max-width: 100%;
          }
        }

        @media (max-width: 768px) {
          .login-box {
            padding: 100px 80px 33px 80px;
          }
          .login100-pic {
            display: none;
          }
          .login100-form {
            width: 100%;
          }
          .login100-form.register-mode .input-wrap {
            width: 100%;
            max-width: 100%;
          }
          .login100-form.register-mode .container {
            width: 100%;
          }
        }

        @media (max-width: 576px) {
          .login-box {
            padding: 100px 15px 33px 15px;
          }
          .login100-form {
            width: 100%;
          }
          .login100-form.register-mode .input100 {
            font-size: 14px;
          }
        }

        @media (min-width: 430px) {
          .login100-form .input-wrap {
            width: 100%;
            max-width: 290px;
          }
        }

        /*if form is valid*/
        .validate-input {
          position: relative;
        }

        .alert-validate .input100 {
          border: 2px solid #d96817;
          animation: shake 0.3s;
        }

        .alert-validate::before {
          content: attr(data-validate);
          position: absolute;
          width: 100%;
          box-sizing: border-box;
          background: #d96817;
          border-radius: 25px;
          padding: 8px 0;
          bottom: -45px;
          left: 0;
          pointer-events: none;
          font-family: Poppins-Medium, sans-serif;
          color: white;
          font-size: 12px;
          line-height: 1.4;
          text-align: center;
          visibility: visible;
          opacity: 1;
          z-index: 100;
          transition: all 0.3s ease;
          box-shadow: 0 4px 15px rgba(255, 82, 82, 0.3);
        }

        .alert-validate::after {
          content: "";
          position: absolute;
          width: 0;
          height: 0;
          border-left: 6px solid transparent;
          border-right: 6px solid transparent;
          border-bottom: 6px solid #d96817;
          bottom: -8px;
          left: 50%;
          transform: translateX(-50%);
          z-index: 101;
        }

        .alert-mismatch::before {
          content: "Passwords do not match";
          color: #fff;
        }

        .alert-validate.input-wrap {
          margin-bottom: 60px;
        }

        /*hides errors when not in error state*/
        .validate-input:not(.alert-validate)::before,
        .validate-input:not(.alert-validate)::after {
          display: none;
        }

        @keyframes shake {
          0%, 100% {
            transform: translateX(0);
          }
          25% {
            transform: translateX(-5px);
          }
          75% {
            transform: translateX(5px);
          }
        }

        @media (max-width: 992px) {
          .alert-validate::before {
            font-size: 11px;
            padding: 6px 15px;
            bottom: -40px;
          }
        }

        @media (max-width: 576px) {
          .alert-validate::before {
            font-size: 10px;
            padding: 5px 12px;
          }
        }

        /* REGISTER PAGE */
        .hidden {
          display: none;
        }

        .login100-form.register-mode .register-fields {
          display: block;
        }

        .register-fields {
          display: none;
        }

        .login100-form.register-mode .register-fields {
          display: block;
        }

        .login100-form.register-mode .text {
          content: "REGISTER";
        }

        .login100-form.register-mode .input-wrap {
          width: 100%;
          max-width: 290px;
        }

        .login100-form.register-mode .input100 {
          width: 100%;
        }

        .container {
          width: 100%;
          max-width: 290px;
          height: 50px;
          border-radius: 25px;
          background: #d96817;
          display: -webkit-box;
          display: -webkit-flex;
          display: -moz-box;
          display: -ms-flexbox;
          display: flex;
          justify-content: center;
          align-items: center;
          padding: 0 25px;
          cursor: pointer;
          position: relative;
          margin: 0 auto;
          -webkit-transition: all 0.4s;
          -o-transition: all 0.4s;
          -moz-transition: all 0.4s;
          transition: all 0.4s;
        }

        .text {
          text-align: center;
          font-family: Montserrat-Bold;
          font-size: 16px;
          line-height: 1.5;
          color: #fff;
          text-transform: uppercase;
        }

        .fingerprint {
          left: 1.5px;
          opacity: 0;
          position: absolute;
          stroke: #777;
          top: -24px;
          transition: opacity 1ms;
          width: 100%;
        }

        .fingerprint-active {
          stroke: #fff;
        }

        .fingerprint-out {
          opacity: 1;
        }

        .odd {
          stroke-dasharray: 0px 50px;
          stroke-dashoffset: 1px;
          transition: stroke-dasharray 1ms;
        }

        .even {
          stroke-dasharray: 50px 50px;
          stroke-dashoffset: -41px;
          transition: stroke-dashoffset 1ms;
        }

        .ok {
          opacity: 0;
          left: 1.5px;
          position: absolute;
          stroke: #fff;
          top: -24px;
          transition: opacity 300ms;
          width: 100%;
          pointer-events: none;
          display: none;
        }

        .cross {
          opacity: 0;
          position: absolute;
          stroke: #fff;
          top: -24px;
          transition: opacity 300ms;
          width: 80%;
          pointer-events: none;
          display: none;
        }

        .active.container {
          animation: 6s Container;
        }

        .active .text {
          opacity: 0;
          animation: 6s Text forwards;
        }

        .active .fingerprint {
          opacity: 1;
          transition: opacity 300ms 200ms;
        }

        .active .fingerprint-base .odd {
          stroke-dasharray: 50px 50px;
          transition: stroke-dasharray 800ms 100ms;
        }

        .active .fingerprint-base .even {
          stroke-dashoffset: 0px;
          transition: stroke-dashoffset 800ms;
        }

        .active .fingerprint-active .odd {
          stroke-dasharray: 50px 50px;
          transition: stroke-dasharray 2000ms 1500ms;
        }

        .active .fingerprint-active .even {
          stroke-dashoffset: 0px;
          transition: stroke-dashoffset 2000ms 1300ms;
        }

        .active .fingerprint-out {
          opacity: 0;
          transition: opacity 300ms 4100ms;
        }

        .active .ok {
          display: block;
          opacity: 100;
          animation: 6s Ok forwards;
        }

        .active .cross {
          display: block;
          animation: 6s Cross forwards;
        }

        @keyframes Container {
          0% { width: 200px; }
          6% { width: 80px; }
          71% { transform: scale(1); }
          75% { transform: scale(1.2); }
          77% { transform: scale(1); }
          94% { width: 80px; }
          100% { width: 200px; }
        }

        @keyframes Text {
          0% {
            opacity: 1;
            transform: scale(1);
          }
          6% {
            opacity: 0;
            transform: scale(0.5);
          }
          94% {
            opacity: 0;
            transform: scale(0.5);
          }
          100% {
            opacity: 1;
            transform: scale(1);
          }
        }

        @keyframes Ok {
          0% { opacity: 0; }
          70% { opacity: 0; transform: scale(0); }
          75% { opacity: 1; transform: scale(1.1); }
          77% { opacity: 1; transform: scale(1); }
          92% { opacity: 1; transform: scale(1); }
          96% { opacity: 0; transform: scale(0.5); }
          100% { opacity: 0; }
        }

        @keyframes Cross {
          0% { opacity: 0; }
          70% { opacity: 0; transform: scale(0); }
          75% { opacity: 1; transform: scale(1.1); }
          77% { opacity: 1; transform: scale(1); }
          92% { opacity: 1; transform: scale(1); }
          96% { opacity: 0; transform: scale(0.5); }
          100% { opacity: 0; }
        }

        .wrap-inputs {
          display: grid;
          gap: 12px;
          justify-content: center;
        }

        .wrap-inputs {
          grid-template-areas:
            "email"
            "password";
        }
        .login100-form.register-mode .wrap-inputs {
          grid-template-areas:
            "name"
            "email"
            "password"
            "confirm";
        }

        .input-email {
          grid-area: email;
        }

        .input-password {
          grid-area: password;
        }

        .input-name {
          grid-area: name;
        }

        .input-confirm {
          grid-area: confirm;
        }

        .login100-form.register-mode .input-wrap:not(.register-fields .input-wrap) {
          display: none;
        }

        .login100-form.register-mode .register-fields {
          display: block;
        }

        .login100-form .input-wrap {
          display: block;
        }

        .login100-form.register-mode .register-fields {
          display: flex;
          flex-direction: column;
          align-items: center;
        }

        .login100-form.register-mode {
          display: flex;
          flex-direction: column;
          align-items: center;
        }

        .login100-form.register-mode .input-wrap[data-validate*="Full name"] {
          order: 0;
        }

        .login100-form.register-mode .input-wrap[data-validate*="email"] {
          order: 1;
        }

        .login100-form.register-mode .input-wrap[data-validate*="Password is required"] {
          order: 2;
        }

        .login100-form.register-mode .input-wrap[data-validate*="Confirm password"] {
          order: 3;
        }

        .register-fields {
            display: none;
        }

        .register-mode .login-fields {
            display: none;
        }

        .register-mode .register-fields {
            display: block;
        }

        /* --- FIX: Show validation bubble for driving licence + all register fields --- */
        .register-fields .validate-input.alert-validate::before,
        .register-fields .validate-input.alert-validate::after {
            display: block;
        }

        .register-fields .validate-input.alert-validate .input100 {
            border: 2px solid #d96817;
            animation: shake 0.3s;
        }
    </style>
</head>

<body>
    <!-- Header from landing.php -->
    <header class="login-header">
        <div class="login-header-content">
            <div class="login-logo">
                <img src="logo2.png" alt="Logo">
            </div>

            <nav class="login-nav">
                <ul>
                    <li class="dropdown">
                        <a href="landing.php" class="dropbtn">Home <i class="fas fa-caret-down"></i></a>
                        <div class="dropdown-content">
                            <a href="landing.php">Home</a>
                            <a href="about.php">About</a>
                        </div>
                    </li>
                    <li><a href="cars.php">Cars</a></li>
                    <li><a href="contact.php">Contact</a></li>

                    <?php if (!isset($_SESSION['user'])): ?>
                        
                        <li><a href="loginPage.php">Login</a></li>
                    <?php else: ?>
                        <li><a href="customer-dashboard.php">Dashboard</a></li>
                        <li>
                            <a href="logout.php" style="color: #ff7f50;">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    <?php endif; ?>

                    <li><a href="#">🌐︎</a></li>

                    <li class="login-basket-indicator">
                        <a href="basket.php">
                            <i class="fas fa-shopping-basket"></i>
                            <?php if ($basketCount > 0): ?>
                                <span class="basket-count"><?php echo $basketCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <!-- Original Login Page Content -->
    <div class="LoginPageLimit">
        <div class="Background-colours">
            <div class="login-box">

                <div class="login100-pic js-tilt">
                    <img src="img-01.png" alt="IMG">
                </div>

                <form class="login100-form validate-form" id="authForm">
                    <span class="login-title">Customer Login</span>

                    <!-- Login inputs -->
                    <div class="login-fields">
                        <div class="input-wrap validate-input" data-validate="Valid email is required: example@abc.xyz">
                            <input class="input100" type="text" name="email" id="loginEmail" placeholder="Email">
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
                            <input class="input100" type="text" id="regEmail" name="reg_email" placeholder="Email">
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
    <script> 
/* Made by 240115551 // Will Giles -> working updated version as of 03/12/2025 */

(function ($) {
    "use strict";

    var input = $('.validate-input .input100');

    $('.validate-form').on('submit', function (e) {
        e.preventDefault();
        return false;
    });

    $('.validate-form .input100:visible').each(function () {
        $(this).focus(function () {
            hideValidate(this);
        });
    });

    function validate(input) {
        if (!$(input).is(':visible')) {
            return true;
        }

        if (
            $(input).attr('type') === 'email' ||
            $(input).attr('name') === 'email' ||
            $(input).attr('name') === 'reg_email'
        ) {
            const emailRegex = /^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{1,5}|[0-9]{1,3})(\]?)$/;

            if (!$(input).val().trim().match(emailRegex)) {
                return false;
            }

        } else if ($(input).attr('id') === 'regDriving') {

            const licenceRegex = /^[A-Z9]{5}\d{6}[A-Z]{2}\d{2}$/i;

            if (!$(input).val().trim().match(licenceRegex)) {
                return false;
            }

        } else {

            if ($(input).val().trim() === '') {
                return false;
            }
        }

        return true;
    }

    function showValidate(input) {
        var thisAlert = $(input).parent();
        $(thisAlert).addClass('alert-validate');
    }
    
    function hideValidate(input) {
        var thisAlert = $(input).parent();
        $(thisAlert).removeClass('alert-validate');
    }

    function validateAllInputs() {
        const form = document.querySelector(".login100-form");
        const isRegisterMode = form.classList.contains("register-mode");
        let check = true;

        if (isRegisterMode) {
            const nameInput = $('#regFullname');
            const emailInput = $('#regEmail');
            const drivingInput = $('#regDriving');          
            const passInput = $('#regPassword');
            const confirmInput = $('#confirmPassword');

            if (!validate(nameInput[0])) {
                showValidate(nameInput[0]);
                check = false;
            }

            if (!validate(emailInput[0])) {
                showValidate(emailInput[0]);
                check = false;
            }

            if (!validate(drivingInput[0])) {
                showValidate(drivingInput[0]);
                check = false;
            }

            if (!validate(passInput[0])) {
                showValidate(passInput[0]);
                check = false;
            }
            if (!validate(confirmInput[0])) {
                showValidate(confirmInput[0]);
                check = false;
            }

            if (passInput.val().trim() !== "" &&
                confirmInput.val().trim() !== "" &&
                passInput.val().trim() !== confirmInput.val().trim()) {
                showMismatch(confirmInput[0]);
                check = false;
            }

        } else {
            const emailInput = $('#loginEmail');
            const passInput = $('#loginPassword');

            if (!validate(emailInput[0])) {
                showValidate(emailInput[0]);
                check = false;
            }
            if (!validate(passInput[0])) {
                showValidate(passInput[0]);
                check = false;
            }
        }

        return check;
    }

    function showMismatch(input) {
        var thisAlert = $(input).parent();
        $(thisAlert).addClass('alert-mismatch');
    }

    const container = document.querySelector('.container');
    if (container) {
        container.addEventListener('animationend', () => {
            container.classList.remove('active');
        });
    }

    // Toggle between login and register
    document.addEventListener("DOMContentLoaded", function () {
        const toggleLink = document.getElementById("toggleForm");
        const form = document.querySelector(".login100-form");
        const title = form.querySelector(".login-title");
        const textButton = document.querySelector(".text");
        const formActionInput = document.getElementById("formAction");
        const loginTypeInput = document.getElementById("loginType");
        const loginFields = document.querySelector('.login-fields');
        const registerFields = document.querySelector('.register-fields');
        const emailInput = document.getElementById('loginEmail');

        if (toggleLink && form && title && textButton) {
            toggleLink.addEventListener("click", function (e) {
                e.preventDefault();

                form.classList.toggle("register-mode");

                if (form.classList.contains("register-mode")) {
                    // SHOW REGISTER FIELDS
                    if (loginFields) loginFields.style.display = 'none';
                    if (registerFields) registerFields.style.display = 'block';
                    
                    title.textContent = "Create Account";
                    textButton.textContent = "REGISTER";
                    toggleLink.innerHTML = 'Already have an account? <i class="fa fa-long-arrow-left m-l-5" aria-hidden="true"></i>';
                    if (formActionInput) formActionInput.value = "register";
                } else {
                    // SHOW LOGIN FIELDS
                    if (loginFields) loginFields.style.display = 'block';
                    if (registerFields) registerFields.style.display = 'none';
                    
                    // Reset to customer login when going back to login
                    if (loginTypeInput) loginTypeInput.value = 'customer';
                    title.textContent = "Member Login";
                    textButton.textContent = "LOGIN";
                    if (emailInput) emailInput.placeholder = 'Email';
                    toggleLink.innerHTML = 'Create your Account <i class="fa fa-long-arrow-right m-l-5" aria-hidden="true"></i>';
                    if (formActionInput) formActionInput.value = "login";
                }
            });
        }

        const adminToggle = document.createElement('a');
        adminToggle.href = '#';
        adminToggle.className = 'txt2 admin-toggle';
        adminToggle.style.display = 'block';
        adminToggle.style.marginTop = '10px';
        adminToggle.textContent = 'Login as Admin';
        
        const forgotSection = document.querySelector('.text-center.p-t-12');
        if (forgotSection) {
            forgotSection.appendChild(adminToggle);
        }

        adminToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Only allow toggle when NOT in register mode
            if (!form.classList.contains("register-mode")) {
                if (loginTypeInput && loginTypeInput.value === 'customer') {
                    loginTypeInput.value = 'admin';
                    this.textContent = 'Login as Customer';
                    if (emailInput) emailInput.placeholder = 'Email';
                    title.textContent = "Admin Login";
                } else if (loginTypeInput) {
                    loginTypeInput.value = 'customer';
                    this.textContent = 'Login as Admin';
                    if (emailInput) emailInput.placeholder = 'Email';
                    title.textContent = "Member Login";
                }
            }
        });
    });

    // AJAX authentication
    $(document).ready(function () {
        const container = document.querySelector('.container');
        if (!container) return;

        const ok = container.querySelector('.ok');
        const cross = container.querySelector('.cross');

        if (ok && cross) {
            ok.style.display = 'none';
            ok.style.opacity = '0';
            cross.style.display = 'none';
            cross.style.opacity = '0';
        }

        function showResult(isSuccess) {
            container.classList.add('active');

            if (isSuccess && ok) {
                ok.style.display = 'block';
                ok.style.opacity = '1';
            } else if (!isSuccess && cross) {
                cross.style.display = 'block';
                cross.style.opacity = '1';
            }
        }

        container.addEventListener('animationend', () => {
            container.classList.remove('active');

            if (ok && cross) {
                ok.style.display = 'none';
                ok.style.opacity = '0';
                cross.style.display = 'none';
                cross.style.opacity = '0';
            }
        });

        // Remove validation alerts on input
        document.querySelectorAll('.input100').forEach(input => {
            input.addEventListener('input', function() {
                this.closest('.input-wrap').classList.remove('alert-validate');
                this.closest('.input-wrap').classList.remove('alert-mismatch');
            });

            input.addEventListener('blur', function() {
                if ($(this).is(':visible')) {
                    validate(this);
                }
            });
        });

        // Handle form submission
        container.addEventListener('click', async (e) => {
            e.preventDefault();

            const form = document.querySelector(".login100-form");
            const isRegisterMode = form.classList.contains("register-mode");

            const isValid = validateAllInputs();

            if (!isValid) {
                showResult(false);
                console.log('Validation failed!');
                return;
            }

            // Prepare form data
            const formData = new FormData();
            
            if (isRegisterMode) {
                const fullname = $('#regFullname').val().trim();
                const email = $('#regEmail').val().trim();
                const password = $('#regPassword').val().trim();
                const confirmPassword = $('#confirmPassword').val().trim();

                // Check password match
                if (password !== confirmPassword) {
                    showResult(false);
                    setTimeout(() => {
                        alert('Passwords do not match');
                    }, 1000);
                    return;
                }

                formData.append('action', 'register');
                formData.append('fullname', fullname);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);
        
                const driving = $('#regDriving').val().trim();
                formData.append('driving_licence', driving);

                
            } else {
                const email = $('#loginEmail').val().trim();
                const password = $('#loginPassword').val().trim();
                const loginType = $('#loginType').val() || 'customer';

                formData.append('action', 'login');
                formData.append('email', email);
                formData.append('password', password);
                formData.append('loginType', loginType);
            }

            try {
                const response = await fetch('loginPage.php', {  // Changed from 'login.php' to 'loginPage.php'
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();
                console.log('Login response:', result);  // Debug logging

                if (result.success) {
                    showResult(true);
                    
                    // Redirect after successful login/registration
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 1500);  // Reduced from 6000ms to 1500ms
                } else {
                    showResult(false);
                    
                    // Show error message
                    setTimeout(() => {
                        alert(result.message);
                    }, 1000);
                }
            } catch (error) {
                console.error('Error:', error);
                showResult(false);
                
                setTimeout(() => {
                    alert('An error occurred. Please try again.');
                }, 1000);
            }
        });
    });

})(jQuery);
</script>
    
</body>

</html>