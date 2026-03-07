<?php
session_start();


$host = 'localhost';   
$username = 'cs2team1';
$password = 'GIzgRTkFQWYg5bByiUxSMhhcJ';
$database = 'cs2team1_db';


$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['role'] === 'customer') {
        header('Location: customer-dashboard.php');
    } else {
        header('Location: admin-dashboard.php');
    }
    exit;
}

$basketCount = 0;


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

    // AJAX request — UNCHANGED
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    if ($response['success']) {
        header('Location: ' . $response['redirect']);
        exit;
    }
}

$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login to Motiv.</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="images/icons/favicon.ico" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>

        /* ─── VARIABLES ──────────────────────────────────────────────── */
        :root {
            --vivid-indigo: #8C0050;
            --dark-magenta: #1800AD;
            --cobalt-blue:  #004AAD;
            --coral-red:    #FF7F50;

            --bg-grad-a: rgba(140, 0, 80, 0.82);
            --bg-grad-b: rgba(65, 88, 208, 0.90);

            --card-bg:      #ffffff;
            --card-shadow:  0 20px 60px rgba(0,0,0,0.20);

            --panel-bg:     rgba(140,0,80,0.04);

            --input-bg:     #f1f1f5;
            --input-text:   #333344;
            --input-border: transparent;
            --input-icon:   #999aaa;

            --label-color:  #8C0050;
            --title-color:  #8C0050;
            --body-color:   #555566;
            --link-color:   #8C0050;
            --divider:      #e8e8f0;

            --btn-bg:       #d96817;
            --btn-shadow:   rgba(217,104,23,0.35);
        }

        [data-theme="dark"] {
            --bg-grad-a: rgba(85, 0, 48, 0.94);
            --bg-grad-b: rgba(18, 20, 110, 0.96);

            --card-bg:      #18182a;
            --card-shadow:  0 20px 60px rgba(0,0,0,0.55);

            --panel-bg:     rgba(140,0,80,0.10);

            --input-bg:     #28283c;
            --input-text:   #d8d8ee;
            --input-icon:   #8888aa;

            --label-color:  #e080b8;
            --title-color:  #e080b8;
            --body-color:   #aaaacc;
            --link-color:   #cc88bb;
            --divider:      #2e2e44;
        }

        /* ─── RESET ──────────────────────────────────────────────────── */
        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; }

        body {
            font-family: 'Poppins', sans-serif;
            background: #12122a;
            transition: background .3s;
        }

        a { color: var(--link-color); text-decoration: none; transition: opacity .2s; }
        a:hover { opacity: .72; }
        ul, li { list-style: none; }
        button { background: transparent; border: none; outline: none !important; cursor: pointer; }
        input  { outline: none; border: none; }

        /* ─── HEADER ─────────────────────────────────────────────────── */
        .login-header {
            background: linear-gradient(to right, var(--vivid-indigo), var(--dark-magenta));
            height: 72px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 16px rgba(0,0,0,0.28);
            position: relative;
            z-index: 1000;
        }

        .login-header-content {
            padding: 0 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .login-logo img {
            height: 108px;
            width: auto;
            margin-top: 2px;
            margin-left: -34px;
        }

        .login-nav ul {
            display: flex;
            gap: 2px;
            align-items: center;
        }

        .login-nav ul li a {
            color: rgba(255,255,255,0.92);
            font-size: 14px;
            font-weight: 600;
            padding: 7px 13px;
            border-radius: 6px;
            transition: background .2s;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }

        .login-nav ul li a:hover { background: rgba(255,255,255,0.18); opacity: 1; color: #fff; }

        .login-nav ul li.dropdown { position: relative; display: flex; align-items: center; }

        .login-nav ul li.dropdown .dropbtn {
            color: rgba(255,255,255,0.92);
            font-size: 14px;
            font-weight: 600;
            padding: 7px 13px;
            border-radius: 6px;
            transition: background .2s;
            display: flex; align-items: center; gap: 5px;
            cursor: pointer;
        }

        .login-nav ul li.dropdown:hover .dropbtn { background: rgba(255,255,255,0.18); }

        .login-nav ul li.dropdown .dropdown-content {
            display: none;
            position: absolute;
            top: calc(100% + 6px); left: 0;
            background: #fff;
            min-width: 130px;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.14);
            overflow: hidden;
            z-index: 1001;
        }

        .login-nav ul li.dropdown:hover .dropdown-content { display: block; }

        .login-nav ul li.dropdown .dropdown-content a {
            color: #333;
            font-size: 13px;
            font-weight: 600;
            padding: 10px 16px;
            display: block;
            border-bottom: 1px solid #f0f0f0;
            transition: background .15s;
        }

        .login-nav ul li.dropdown .dropdown-content a:last-child { border-bottom: none; }
        .login-nav ul li.dropdown .dropdown-content a:hover { background: #f5f5f5; color: var(--vivid-indigo); opacity: 1; }

        .language-selector { position: relative; display: flex; align-items: center; }

        .language-selector > a {
            padding: 7px 10px; border-radius: 6px;
            color: rgba(255,255,255,0.92); font-size: 17px;
            display: flex; align-items: center;
            transition: background .2s;
        }

        .language-selector > a:hover { background: rgba(255,255,255,0.18); opacity: 1; }

        .language-settings-dropdown {
            display: none;
            position: absolute;
            right: 0; top: calc(100% + 6px);
            min-width: 210px;
            background: #fff;
            border: 1px solid #e8e8ee;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
            z-index: 1001;
        }

        [data-theme="dark"] .language-settings-dropdown { background: #242436; border-color: #3a3a52; }

        .language-selector.open .language-settings-dropdown { display: block; }

        .settings-section { padding: 12px 14px; border-bottom: 1px solid #eee; }
        [data-theme="dark"] .settings-section { border-color: #3a3a52; }
        .settings-section:last-child { border-bottom: none; }

        .settings-section h4 {
            font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 1px;
            color: #aaa; margin-bottom: 8px;
        }

        [data-theme="dark"] .settings-section h4 { color: #777; }

        .theme-option, .language-option {
            display: flex; align-items: center; gap: 10px;
            padding: 7px 10px; border-radius: 6px;
            font-size: 13px; color: #333;
            transition: background .15s; margin-bottom: 2px;
            text-decoration: none;
        }

        [data-theme="dark"] .theme-option,
        [data-theme="dark"] .language-option { color: #ddd; }

        .theme-option:hover, .language-option:hover { background: #f2f2f5; opacity: 1; }
        [data-theme="dark"] .theme-option:hover, [data-theme="dark"] .language-option:hover { background: #323248; }

        .theme-option i, .language-option i { color: var(--vivid-indigo); width: 16px; font-size: 13px; }
        .active-indicator { margin-left: auto; color: var(--vivid-indigo); font-size: 11px; }

        .font-controls { display: flex; align-items: center; gap: 6px; }

        .font-btn {
            background: var(--vivid-indigo); color: #fff;
            width: 30px; height: 30px; border-radius: 6px;
            font-size: 12px; font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            transition: background .2s;
        }

        .font-btn:hover { background: var(--dark-magenta); }

        .font-size-display {
            font-size: 12px; color: #333; font-weight: 600;
            min-width: 40px; text-align: center;
        }

        [data-theme="dark"] .font-size-display { color: #ddd; }

        .login-basket-indicator { position: relative; display: inline-flex; align-items: center; }

        .basket-count {
            background: var(--coral-red); color: #fff;
            border-radius: 50%; width: 16px; height: 16px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 9px; font-weight: 700;
            position: absolute; top: -7px; right: -7px;
        }

        .LoginPageLimit { width: 100%; }

        .Background-colours {
            width: 100%;
            min-height: calc(100vh - 72px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 20px;
            background: linear-gradient(135deg, var(--bg-grad-a) 0%, var(--bg-grad-b) 100%);
            position: relative;
            overflow: hidden;
        }

    
        .Background-colours::before,
        .Background-colours::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .Background-colours::before {
            width: 520px; height: 520px;
            background: rgba(255,255,255,0.035);
            top: -160px; left: -140px;
        }

        .Background-colours::after {
            width: 360px; height: 360px;
            background: rgba(255,255,255,0.03);
            bottom: -100px; right: -80px;
        }

        /* ─── LOGIN CARD ─────────────────────────────────────────────── */
        .login-box {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1020px;
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: stretch;
            overflow: visible; 
            transition: background .3s;
        }

  
        .login-box::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 24px;
            pointer-events: none;
        }

     
        .login100-pic {
            width: 400px;
            flex-shrink: 0;
            background: var(--panel-bg);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 44px;
            border-right: 1px solid var(--divider);
            gap: 28px;
            border-radius: 24px 0 0 24px;
        }

        .login100-pic img { max-width: 100%; width: 280px; }

        .pic-tagline {
            text-align: center;
            font-size: 14px;
            color: var(--body-color);
            line-height: 1.6;
        }

        .pic-tagline strong {
            display: block;
            font-size: 17px;
            font-weight: 700;
            color: var(--title-color);
            margin-bottom: 5px;
        }

        .login100-form {
            flex: 1;
            padding: 60px 60px 52px;
            display: flex;
            flex-direction: column;
            border-radius: 0 24px 24px 0;
            background: var(--card-bg);
        }

        /* ─── TITLE ──────────────────────────────────────────────────── */
        .login-title {
            font-size: 30px;
            font-weight: 700;
            color: var(--title-color);
            margin-bottom: 32px;
            letter-spacing: -0.3px;
        }

        /* ─── INPUTS ─────────────────────────────────────────────────── */

        .login-fields,
        .register-fields { display: flex; flex-direction: column; gap: 14px; }

        .register-fields { display: none; }
        .register-mode .login-fields   { display: none; }
        .register-mode .register-fields { display: flex; }

        .input-wrap {
            position: relative;
            width: 100%;
        }

        .alert-validate.input-wrap { margin-bottom: 46px; }

        .input100 {
            font-family: 'Poppins', sans-serif;
            font-size: 14.5px;
            color: var(--input-text);
            width: 100%;
            height: 54px;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 11px;
            padding: 0 16px 0 52px;
            transition: border-color .22s, box-shadow .22s, background .3s;
        }

        .input100:focus {
            border-color: #d96817;
            box-shadow: 0 0 0 3px rgba(217,104,23,0.18), 0 4px 16px rgba(217,104,23,0.13);
            background: var(--card-bg);
        }

        .input100::placeholder { color: var(--input-icon); font-size: 13.5px; }

        .symbol-input100 {
            position: absolute;
            left: 0; top: 0;
            height: 100%; width: 48px;
            display: flex; align-items: center; justify-content: center;
            color: var(--input-icon);
            font-size: 14px;
            pointer-events: none;
            transition: color .22s;
        }

        .input100:focus ~ .symbol-input100 { color: #d96817; }


        .focus-input100 { display: none; }


        .input-wrap.has-eye .input100 { padding-right: 48px; }

        .eye-toggle {
            position: absolute;
            right: 14px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--input-icon);
            font-size: 15px;
            transition: color .2s;
            background: none;
            border: none;
            padding: 0;
            outline: none !important;
            display: flex; align-items: center;
        }

        .eye-toggle:hover { color: #d96817; }

        .licence-hint {
            display: none;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 0;
            background: #1a1a2e;
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            padding: 9px 14px;
            border-radius: 8px;
            white-space: nowrap;
            z-index: 200;
            pointer-events: none;
            box-shadow: 0 4px 16px rgba(0,0,0,0.28);
            letter-spacing: 0.3px;
        }

        .licence-hint::after {
            content: '';
            position: absolute;
            top: 100%; left: 24px;
            border: 6px solid transparent;
            border-top-color: #1a1a2e;
        }

        /* format template — coloured segments */
        .licence-hint .h-alpha { color: #ff9966; font-weight: 700; }
        .licence-hint .h-num   { color: #66ccff; font-weight: 700; }
        .licence-hint .h-alpha2{ color: #aaffaa; font-weight: 700; }
        .licence-hint .h-num2  { color: #ffff66; font-weight: 700; }

        .input-wrap.driving-wrap:focus-within .licence-hint { display: block; }

        .validate-input { position: relative; }

        .alert-validate .input100 {
            border-color: #e05252;
            animation: shake .3s;
        }

        .alert-validate::before {
            content: attr(data-validate);
            position: absolute;
            bottom: -42px; left: 0;
            width: 100%;
            background: #e05252;
            color: #fff;
            font-size: 11px;
            font-weight: 500;
            text-align: center;
            padding: 7px 12px;
            border-radius: 8px;
            z-index: 100;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(224,82,82,0.28);
        }

        .alert-validate::after {
            content: '';
            position: absolute;
            bottom: -12px; left: 50%;
            transform: translateX(-50%);
            border: 6px solid transparent;
            border-bottom-color: #e05252;
            z-index: 101;
            pointer-events: none;
        }

        .alert-mismatch::before { content: "Passwords do not match"; }

        .validate-input:not(.alert-validate)::before,
        .validate-input:not(.alert-validate)::after { display: none; }

        .register-fields .validate-input.alert-validate::before,
        .register-fields .validate-input.alert-validate::after { display: block; }

        .register-fields .validate-input.alert-validate .input100 { border-color: #e05252; }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25%      { transform: translateX(-5px); }
            75%      { transform: translateX(5px); }
        }

        .container {
            width: 100%;
            max-width: 500px;       /* gives the keyframe pixel widths a reference */
            height: 56px;
            border-radius: 28px;
            background: var(--btn-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 0 25px;
            cursor: pointer;
            position: relative;
            margin: 24px auto 0;    /* centred */
            transition: box-shadow .3s, transform .3s;
            box-shadow: 0 6px 20px var(--btn-shadow);
            overflow: visible;
        }

        .container:hover { transform: translateY(-2px); box-shadow: 0 10px 28px var(--btn-shadow); }

        .text {
            font-family: 'Poppins', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .fingerprint {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            stroke: #777;
            transition: opacity 1ms;
            width: 52px;
            height: 52px;
            pointer-events: none;
        }
        .fingerprint-active { stroke: #fff; }
        .fingerprint-out { opacity: 0; }
        .odd  { stroke-dasharray:0px 50px;  stroke-dashoffset:1px;   transition:stroke-dasharray 1ms; }
        .even { stroke-dasharray:50px 50px; stroke-dashoffset:-41px; transition:stroke-dashoffset 1ms; }
        .ok   {
            opacity: 0;
            position: absolute;
            left: 50%; top: 50%;
            transform: translate(-50%, -50%);
            stroke: #fff;
            transition: opacity 300ms;
            width: 52px; height: 52px;
            pointer-events: none; display: none;
        }
        .cross {
            opacity: 0;
            position: absolute;
            left: 50%; top: 50%;
            transform: translate(-50%, -50%);
            stroke: #fff;
            transition: opacity 300ms;
            width: 44px; height: 44px;
            pointer-events: none; display: none;
        }

        .active.container { animation: 6s Container; }
        .active .text     { opacity:0; animation: 6s Text forwards; }
        .active .fingerprint                    { opacity:1; transition:opacity 300ms 200ms; }
        .active .fingerprint-base .odd          { stroke-dasharray:50px 50px; transition:stroke-dasharray 800ms 100ms; }
        .active .fingerprint-base .even         { stroke-dashoffset:0px;      transition:stroke-dashoffset 800ms; }
        .active .fingerprint-active .odd        { stroke-dasharray:50px 50px; transition:stroke-dasharray 2000ms 1500ms; }
        .active .fingerprint-active .even       { stroke-dashoffset:0px;      transition:stroke-dashoffset 2000ms 1300ms; }
        .active .fingerprint-out                { opacity:0; transition:opacity 300ms 4100ms; }
        .active .ok    { display:block; opacity:100; animation:6s Ok    forwards; }
        .active .cross { display:block;             animation:6s Cross  forwards; }

        @keyframes Container {
            0%   { width: 100%; border-radius: 28px; }
            6%   { width: 56px; border-radius: 50%; }
            71%  { transform: scale(1); }
            75%  { transform: scale(1.15); }
            77%  { transform: scale(1); }
            94%  { width: 56px; border-radius: 50%; }
            100% { width: 100%; border-radius: 28px; }
        }
        @keyframes Text {
            0%  { opacity:1; transform:scale(1);  }  6%  { opacity:0; transform:scale(.5); }
            94% { opacity:0; transform:scale(.5); } 100% { opacity:1; transform:scale(1);  }
        }
        @keyframes Ok {
            0%  { opacity:0; }
            70% { opacity:0; transform:scale(0);   } 75% { opacity:1; transform:scale(1.1); }
            77% { opacity:1; transform:scale(1);   } 92% { opacity:1; transform:scale(1);   }
            96% { opacity:0; transform:scale(.5);  } 100%{ opacity:0; }
        }
        @keyframes Cross {
            0%  { opacity:0; }
            70% { opacity:0; transform:scale(0);   } 75% { opacity:1; transform:scale(1.1); }
            77% { opacity:1; transform:scale(1);   } 92% { opacity:1; transform:scale(1);   }
            96% { opacity:0; transform:scale(.5);  } 100%{ opacity:0; }
        }

        .form-divider {
            width: 100%;
            height: 1px;
            background: var(--divider);
            margin: 22px 0 0;
        }

        .form-footer-links {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-footer-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .txt1 { color: var(--body-color); font-size: 13px; }
        .txt2 { color: var(--link-color); font-size: 13px; font-weight: 600; }

        .admin-toggle {
            display: block;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: var(--cobalt-blue) !important;
        }

        [data-theme="dark"] .admin-toggle { color: #7799ee !important; }


        .p-t-12  { }
        .p-t-136 { }
        .text-center { text-align: center; }
        .hidden { display: none !important; }

        .nav-hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background .2s;
            background: transparent;
            border: none;
        }

        .nav-hamburger:hover { background: rgba(255,255,255,0.18); }

        .nav-hamburger span {
            display: block;
            width: 22px;
            height: 2px;
            background: #fff;
            border-radius: 2px;
            transition: all .25s;
        }

        /* hamburger → X when open */
        .nav-hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .nav-hamburger.open span:nth-child(2) { opacity: 0; transform: scaleX(0); }
        .nav-hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        /* ─── RESPONSIVE ─────────────────────────────────────────────── */

        @media (max-width: 1024px) {
            .login-box { max-width: 840px; }
            .login100-pic { width: 320px; padding: 48px 32px; }
            .login100-pic img { width: 220px; }
            .login100-form { padding: 48px 44px 40px; }
        }


        @media (max-width: 860px) {
            .login-box { max-width: 500px; flex-direction: column; }
            .login100-pic {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--divider);
                flex-direction: row;
                padding: 24px 28px;
                gap: 18px;
                justify-content: flex-start;
                border-radius: 16px 16px 0 0;
            }
            .login100-pic img { width: 72px; flex-shrink: 0; }
            .pic-tagline { text-align: left; font-size: 13px; }
            .pic-tagline strong { font-size: 14px; }
            .login100-form { padding: 36px 36px 32px; border-radius: 0 0 16px 16px; }
            .login-title { font-size: 24px; margin-bottom: 24px; }
            .login-box { border-radius: 16px; }
        }

        /* Phone: ≤560px */
        @media (max-width: 560px) {
            .login-box { max-width: 100%; border-radius: 16px; }
            .login100-pic { display: none; }
            .login100-form { padding: 32px 24px 28px; border-radius: 16px; }
            .input100 { height: 50px; font-size: 14px; }
            .login-title { font-size: 22px; }
            .Background-colours { padding: 24px 16px; }
        }



        @media (max-width: 768px) {
            .nav-hamburger { display: flex; }

            .login-nav {
                position: fixed;
                top: 72px; right: 0;
                width: 260px;
                height: calc(100vh - 72px);
                background: #bdbdbd;                   /*Needs changing */
                transform: translateX(100%);
                transition: transform .28s ease;
                z-index: 999;
                overflow-y: auto;
                box-shadow: -4px 0 24px rgba(0,0,0,0.3);
            }

            .login-nav.nav-open { transform: translateX(0); }

            .login-nav ul {
                flex-direction: column;
                gap: 0;
                padding: 16px 0;
                align-items: stretch;
            }

            .login-nav ul li,
            .login-nav ul li.dropdown,
            .login-nav ul li.language-selector,
            .login-nav ul li.login-basket-indicator {
                display: flex;
                align-items: stretch;
                width: 100%;
                position: static;
            }

            .login-nav ul li a,
            .login-nav ul li.dropdown .dropbtn {
                width: 100%;
                padding: 13px 24px;
                border-radius: 0;
                font-size: 15px;
                border-bottom: 1px solid rgba(255,255,255,0.07);
                justify-content: flex-start;
            }

            .login-nav ul li a:hover,
            .login-nav ul li.dropdown:hover .dropbtn { background: rgba(255,255,255,0.12); }

            /* dropdown in mobile — always visible as indented items */
            .login-nav ul li.dropdown .dropdown-content {
                display: block;
                position: static;
                background: transparent;
                box-shadow: none;
                border-radius: 0;
                overflow: visible;
            }

            .login-nav ul li.dropdown .dropdown-content a {
                color: rgba(255,255,255,0.75);
                background: transparent;
                padding: 10px 24px 10px 40px;
                font-size: 13px;
                border-bottom: 1px solid rgba(255,255,255,0.05);
            }

            .login-nav ul li.dropdown .dropdown-content a:hover { background: rgba(255,255,255,0.10); color: #fff; }

            /* settings dropdown in mobile */
            .language-selector > a {
                width: 100%;
                padding: 13px 24px;
                border-radius: 0;
                font-size: 15px;
                border-bottom: 1px solid rgba(255,255,255,0.07);
                justify-content: flex-start;
                gap: 10px;
            }

            .language-selector > a::after { content: 'Settings'; font-size: 15px; font-weight: 600; }

            .language-settings-dropdown {
                display: block;
                position: static;
                background: transparent;
                border: none;
                border-radius: 0;
                box-shadow: none;
                min-width: unset;
            }

            .settings-section { border-color: rgba(255,255,255,0.07); padding: 8px 16px; }
            .settings-section h4 { color: rgba(255,255,255,0.4); }
            .theme-option, .language-option { color: rgba(255,255,255,0.82); padding: 8px 16px; }
            .theme-option i, .language-option i { color: rgba(255,255,255,0.6); }
            .theme-option:hover, .language-option:hover { background: rgba(255,255,255,0.10); }
            .active-indicator { color: #ffaa55; }
            .font-size-display { color: rgba(255,255,255,0.9); }

            /* basket in mobile nav */
            .login-basket-indicator { padding: 13px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); }
            .login-basket-indicator a { width: auto; padding: 0; border: none; }

   
            .nav-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.45);
                z-index: 998;
            }

            .nav-overlay.active { display: block; }
        }

        /* Very small phones */
        @media (max-width: 380px) {
            .login-nav { width: 230px; }
            .login-logo img { height: 88px; margin-left: -24px; }
        }

        
        @media (min-width: 769px) {
            [data-theme="dark"] .login-nav ul li.dropdown .dropdown-content { background: #242436; }
            [data-theme="dark"] .login-nav ul li.dropdown .dropdown-content a { color: #ddd; border-color: #33334a; }
            [data-theme="dark"] .login-nav ul li.dropdown .dropdown-content a:hover { background: #32324a; color: #e080b8; opacity: 1; }
        }
    </style>
</head>

<body data-theme="<?php echo $darkMode; ?>">


<header class="login-header">
    <div class="login-header-content">
        <div class="login-logo">
            <img src="logo2.png" alt="Logo">
        </div>

        <!-- Hamburger — visible on mobile only -->
        <button class="nav-hamburger" id="navHamburger" aria-label="Open menu">
            <span></span><span></span><span></span>
        </button>

        <nav class="login-nav" id="loginNav">
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
                        <a href="logout.php" style="color:#ff7f50;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                <?php endif; ?>

                <!-- settings dropdown — same structure as landing.php -->
                <li class="language-selector">
                    <a href="#"><i class="fa-solid fa-circle-info"></i></a>
                    <div class="language-settings-dropdown">
                        <div class="settings-section">
                            <h4>Theme</h4>
                            <a href="#" class="theme-option" data-theme="light">
                                <i class="fas fa-sun"></i> Light
                                <?php if ($darkMode == 'light'): ?><i class="fas fa-check active-indicator"></i><?php endif; ?>
                            </a>
                            <a href="#" class="theme-option" data-theme="dark">
                                <i class="fas fa-moon"></i> Dark
                                <?php if ($darkMode == 'dark'): ?><i class="fas fa-check active-indicator"></i><?php endif; ?>
                            </a>
                        </div>
                        <div class="settings-section">
                            <h4>Font Size</h4>
                            <div class="font-controls">
                                <button class="font-btn" id="font-decrease">A-</button>
                                <span class="font-size-display" id="font-size-display">100%</span>
                                <button class="font-btn" id="font-increase">A+</button>
                                <button class="font-btn" id="font-reset">Reset</button>
                            </div>
                        </div>
                        <div class="settings-section">
                            <h4>Language</h4>
                            <a href="#" class="language-option" data-lang="en"><i class="fas fa-language"></i> English <i class="fas fa-check active-indicator"></i></a>
                            <a href="#" class="language-option" data-lang="es"><i class="fas fa-language"></i> Español</a>
                            <a href="#" class="language-option" data-lang="fr"><i class="fas fa-language"></i> Français</a>
                            <a href="#" class="language-option" data-lang="de"><i class="fas fa-language"></i> Deutsch</a>
                        </div>
                    </div>
                </li>

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
<!-- Mobile nav overlay -->
<div class="nav-overlay" id="navOverlay"></div>

<div class="LoginPageLimit">
    <div class="Background-colours">
        <div class="login-box">

            <div class="login100-pic js-tilt">
                <img src="img-01.png" alt="IMG">
                <div class="pic-tagline">
                    <strong>Welcome back</strong>
                    Sign in to manage your bookings
                </div>
            </div>


            <form class="login100-form validate-form" id="authForm">

                <span class="login-title">Customer Login</span>


                <div class="login-fields">
                    <div class="input-wrap validate-input" data-validate="Valid email is required: example@abc.xyz">
                        <input class="input100" type="text" name="email" id="loginEmail" placeholder="Email address">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100"><i class="fa fa-envelope"></i></span>
                    </div>

                    <div class="input-wrap validate-input has-eye" data-validate="Password is required">
                        <input class="input100" type="password" name="password" id="loginPassword" placeholder="Password">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        <button type="button" class="eye-toggle" data-target="loginPassword" aria-label="Show/hide password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                </div>

      
                <div class="register-fields">
                    <div class="input-wrap validate-input" data-validate="Full name is required">
                        <input class="input100" type="text" id="regFullname" name="fullname" placeholder="Full Name">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100"><i class="fa fa-user"></i></span>
                    </div>

                    <div class="input-wrap validate-input" data-validate="Valid email is required">
                        <input class="input100" type="text" id="regEmail" name="reg_email" placeholder="Email address">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100"><i class="fa fa-envelope"></i></span>
                    </div>

                    <div class="input-wrap validate-input driving-wrap" data-validate="Driving licence format: ABCDE123456AB12">
                        <input class="input100" type="text" id="regDriving" name="driving_licence" placeholder="Driving Licence Number">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100"><i class="fa fa-id-card"></i></span>
                        <div class="licence-hint">
                            Format:&nbsp;
                            <span class="h-alpha">ABCDE</span><span class="h-num">123456</span><span class="h-alpha2">AB</span><span class="h-num2">12</span>
                            &nbsp;—&nbsp;
                            <span class="h-alpha">5 letters</span> · <span class="h-num">6 digits</span> · <span class="h-alpha2">2 letters</span> · <span class="h-num2">2 digits</span>
                        </div>
                    </div>

                    <div class="input-wrap validate-input has-eye" data-validate="Password is required">
                        <input class="input100" type="password" id="regPassword" placeholder="Password">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        <button type="button" class="eye-toggle" data-target="regPassword" aria-label="Show/hide password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>

                    <div class="input-wrap validate-input has-eye" data-validate="Confirm password">
                        <input class="input100" type="password" id="confirmPassword" placeholder="Confirm Password">
                        <span class="focus-input100"></span>
                        <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        <button type="button" class="eye-toggle" data-target="confirmPassword" aria-label="Show/hide password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="action"    id="formAction" value="login">
                <input type="hidden" name="loginType" id="loginType"  value="customer">

                <div class="container" id="submitBtn">
                    <span class="text">LOGIN</span>

                    <svg class="fingerprint fingerprint-base" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                        <g class="fingerprint-out" fill="none" stroke-width="2" stroke-linecap="round">
                            <path class="odd"  d="m 25.117139,57.142857 c 0,0 -1.968558,-7.660465 -0.643619,-13.149003 1.324939,-5.488538 4.659682,-8.994751 4.659682,-8.994751"/>
                            <path class="odd"  d="m 31.925369,31.477584 c 0,0 2.153609,-2.934998 9.074971,-5.105078 6.921362,-2.17008 11.799844,-0.618718 11.799844,-0.618718"/>
                            <path class="odd"  d="m 57.131213,26.814448 c 0,0 5.127709,1.731228 9.899495,7.513009 4.771786,5.781781 4.772971,12.109204 4.772971,12.109204"/>
                            <path class="odd"  d="m 72.334009,50.76769 0.09597,2.298098 -0.09597,2.386485"/>
                            <path class="even" d="m 27.849282,62.75 c 0,0 1.286086,-1.279223 1.25,-4.25 -0.03609,-2.970777 -1.606117,-7.675266 -0.625,-12.75 0.981117,-5.074734 4.5,-9.5 4.5,-9.5"/>
                            <path class="even" d="m 36.224282,33.625 c 0,0 8.821171,-7.174484 19.3125,-2.8125 10.491329,4.361984 11.870558,14.952665 11.870558,14.952665"/>
                            <path class="even" d="m 68.349282,49.75 c 0,0 0.500124,3.82939 0.5625,5.8125 0.06238,1.98311 -0.1875,5.9375 -0.1875,5.9375"/>
                            <path class="odd"  d="m 31.099282,65.625 c 0,0 1.764703,-4.224042 2,-7.375 0.235297,-3.150958 -1.943873,-9.276886 0.426777,-15.441942 2.370649,-6.165056 8.073223,-7.933058 8.073223,-7.933058"/>
                            <path class="odd"  d="m 45.849282,33.625 c 0,0 12.805566,-1.968622 17,9.9375 4.194434,11.906122 1.125,24.0625 1.125,24.0625"/>
                            <path class="even" d="m 59.099282,70.25 c 0,0 0.870577,-2.956221 1.1875,-4.5625 0.316923,-1.606279 0.5625,-5.0625 0.5625,-5.0625"/>
                            <path class="even" d="m 60.901059,56.286612 c 0,0 0.903689,-9.415996 -3.801777,-14.849112 -3.03125,-3.5 -7.329245,-4.723939 -11.867187,-3.8125 -5.523438,1.109375 -7.570313,5.75 -7.570313,5.75"/>
                            <path class="even" d="m 34.072577,68.846248 c 0,0 2.274231,-4.165782 2.839205,-9.033748 0.443558,-3.821814 -0.49394,-5.649939 -0.714206,-8.05386 -0.220265,-2.403922 0.21421,-4.63364 0.21421,-4.63364"/>
                            <path class="odd"  d="m 37.774165,70.831845 c 0,0 2.692139,-6.147592 3.223034,-11.251208 0.530895,-5.103616 -2.18372,-7.95562 -0.153491,-13.647655 2.030229,-5.692035 8.108442,-4.538898 8.108442,-4.538898"/>
                            <path class="odd"  d="m 54.391174,71.715729 c 0,0 2.359472,-5.427681 2.519068,-16.175068 0.159595,-10.747388 -4.375223,-12.993087 -4.375223,-12.993087"/>
                            <path class="even" d="m 49.474282,73.625 c 0,0 3.730297,-8.451831 3.577665,-16.493718 -0.152632,-8.041887 -0.364805,-11.869326 -4.765165,-11.756282 -4.400364,0.113044 -3.875,4.875 -3.875,4.875"/>
                            <path class="even" d="m 41.132922,72.334447 c 0,0 2.49775,-5.267079 3.181981,-8.883029 0.68423,-3.61595 0.353553,-9.413359 0.353553,-9.413359"/>
                            <path class="odd"  d="m 45.161782,73.75 c 0,0 1.534894,-3.679847 2.40625,-6.53125 0.871356,-2.851403 1.28125,-7.15625 1.28125,-7.15625"/>
                            <path class="odd"  d="m 48.801947,56.125 c 0,0 0.234502,-1.809418 0.109835,-3.375 -0.124667,-1.565582 -0.5625,-3.1875 -0.5625,-3.1875"/>
                        </g>
                    </svg>

                    <svg class="fingerprint fingerprint-active" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                        <g class="fingerprint-out" fill="none" stroke-width="2" stroke-linecap="round">
                            <path class="odd"  d="m 25.117139,57.142857 c 0,0 -1.968558,-7.660465 -0.643619,-13.149003 1.324939,-5.488538 4.659682,-8.994751 4.659682,-8.994751"/>
                            <path class="odd"  d="m 31.925369,31.477584 c 0,0 2.153609,-2.934998 9.074971,-5.105078 6.921362,-2.17008 11.799844,-0.618718 11.799844,-0.618718"/>
                            <path class="odd"  d="m 57.131213,26.814448 c 0,0 5.127709,1.731228 9.899495,7.513009 4.771786,5.781781 4.772971,12.109204 4.772971,12.109204"/>
                            <path class="odd"  d="m 72.334009,50.76769 0.09597,2.298098 -0.09597,2.386485"/>
                            <path class="even" d="m 27.849282,62.75 c 0,0 1.286086,-1.279223 1.25,-4.25 -0.03609,-2.970777 -1.606117,-7.675266 -0.625,-12.75 0.981117,-5.074734 4.5,-9.5 4.5,-9.5"/>
                            <path class="even" d="m 36.224282,33.625 c 0,0 8.821171,-7.174484 19.3125,-2.8125 10.491329,4.361984 11.870558,14.952665 11.870558,14.952665"/>
                            <path class="even" d="m 68.349282,49.75 c 0,0 0.500124,3.82939 0.5625,5.8125 0.06238,1.98311 -0.1875,5.9375 -0.1875,5.9375"/>
                            <path class="odd"  d="m 31.099282,65.625 c 0,0 1.764703,-4.224042 2,-7.375 0.235297,-3.150958 -1.943873,-9.276886 0.426777,-15.441942 2.370649,-6.165056 8.073223,-7.933058 8.073223,-7.933058"/>
                            <path class="odd"  d="m 45.849282,33.625 c 0,0 12.805566,-1.968622 17,9.9375 4.194434,11.906122 1.125,24.0625 1.125,24.0625"/>
                            <path class="even" d="m 59.099282,70.25 c 0,0 0.870577,-2.956221 1.1875,-4.5625 0.316923,-1.606279 0.5625,-5.0625 0.5625,-5.0625"/>
                            <path class="even" d="m 60.901059,56.286612 c 0,0 0.903689,-9.415996 -3.801777,-14.849112 -3.03125,-3.5 -7.329245,-4.723939 -11.867187,-3.8125 -5.523438,1.109375 -7.570313,5.75 -7.570313,5.75"/>
                            <path class="even" d="m 34.072577,68.846248 c 0,0 2.274231,-4.165782 2.839205,-9.033748 0.443558,-3.821814 -0.49394,-5.649939 -0.714206,-8.05386 -0.220265,-2.403922 0.21421,-4.63364 0.21421,-4.63364"/>
                            <path class="odd"  d="m 37.774165,70.831845 c 0,0 2.692139,-6.147592 3.223034,-11.251208 0.530895,-5.103616 -2.18372,-7.95562 -0.153491,-13.647655 2.030229,-5.692035 8.108442,-4.538898 8.108442,-4.538898"/>
                            <path class="odd"  d="m 54.391174,71.715729 c 0,0 2.359472,-5.427681 2.519068,-16.175068 0.159595,-10.747388 -4.375223,-12.993087 -4.375223,-12.993087"/>
                            <path class="even" d="m 49.474282,73.625 c 0,0 3.730297,-8.451831 3.577665,-16.493718 -0.152632,-8.041887 -0.364805,-11.869326 -4.765165,-11.756282 -4.400364,0.113044 -3.875,4.875 -3.875,4.875"/>
                            <path class="even" d="m 41.132922,72.334447 c 0,0 2.49775,-5.267079 3.181981,-8.883029 0.68423,-3.61595 0.353553,-9.413359 0.353553,-9.413359"/>
                            <path class="odd"  d="m 45.161782,73.75 c 0,0 1.534894,-3.679847 2.40625,-6.53125 0.871356,-2.851403 1.28125,-7.15625 1.28125,-7.15625"/>
                            <path class="odd"  d="m 48.801947,56.125 c 0,0 0.234502,-1.809418 0.109835,-3.375 -0.124667,-1.565582 -0.5625,-3.1875 -0.5625,-3.1875"/>
                        </g>
                    </svg>

                    <svg class="ok" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                        <path d="M34.912 50.75l10.89 10.125L67 36.75" fill="none" stroke="#fff" stroke-width="6"/>
                    </svg>
                    <svg class="cross" xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                        <path d="M30 30 L70 70 M70 30 L30 70" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/>
                    </svg>
                </div>

                <!-- Bottom links — p-t-12 / p-t-136 classes kept for JS compat -->
                <div class="form-divider"></div>

                <div class="form-footer-links">
                    <div class="form-footer-row text-center p-t-12">
                        <span class="txt1">Forgot</span>
                        <a class="txt2" href="forgotPassword.php">Username / Password?</a>
                    </div>

                    <div class="form-footer-row text-center p-t-136">
                        <a class="txt2" href="#" id="toggleForm">
                            Create your Account <i class="fa fa-long-arrow-right"></i>
                        </a>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

(function ($) {
    "use strict";


    $('.validate-form').on('submit', function (e) { e.preventDefault(); return false; });

    /* validate — UNCHANGED */
    function validate(input) {
        if (!$(input).is(':visible')) return true;

        if ($(input).attr('type') === 'email' ||
            $(input).attr('name') === 'email'  ||
            $(input).attr('name') === 'reg_email') {
            var emailRegex = /^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{1,5}|[0-9]{1,3})(\]?)$/;
            if (!$(input).val().trim().match(emailRegex)) return false;
        } else if ($(input).attr('id') === 'regDriving') {
            var licenceRegex = /^[A-Z9]{5}\d{6}[A-Z]{2}\d{2}$/i;
            if (!$(input).val().trim().match(licenceRegex)) return false;
        } else {
            if ($(input).val().trim() === '') return false;
        }
        return true;
    }

    function showValidate(input) { $(input).parent().addClass('alert-validate'); }
    function hideValidate(input) { $(input).parent().removeClass('alert-validate'); }
    function showMismatch(input) { $(input).parent().addClass('alert-mismatch'); }

    function validateAllInputs() {
        var form = document.querySelector('.login100-form');
        var isRegisterMode = form.classList.contains('register-mode');
        var check = true;

        if (isRegisterMode) {
            var nameInput    = $('#regFullname');
            var emailInput   = $('#regEmail');
            var drivingInput = $('#regDriving');
            var passInput    = $('#regPassword');
            var confirmInput = $('#confirmPassword');

            if (!validate(nameInput[0]))    { showValidate(nameInput[0]);    check = false; }
            if (!validate(emailInput[0]))   { showValidate(emailInput[0]);   check = false; }
            if (!validate(drivingInput[0])) { showValidate(drivingInput[0]); check = false; }
            if (!validate(passInput[0]))    { showValidate(passInput[0]);    check = false; }
            if (!validate(confirmInput[0])) { showValidate(confirmInput[0]); check = false; }

            if (passInput.val().trim() !== '' && confirmInput.val().trim() !== '' &&
                passInput.val().trim() !== confirmInput.val().trim()) {
                showMismatch(confirmInput[0]);
                check = false;
            }
        } else {
            var emailInput2 = $('#loginEmail');
            var passInput2  = $('#loginPassword');
            if (!validate(emailInput2[0])) { showValidate(emailInput2[0]); check = false; }
            if (!validate(passInput2[0]))  { showValidate(passInput2[0]);  check = false; }
        }
        return check;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var toggleLink      = document.getElementById('toggleForm');
        var form            = document.querySelector('.login100-form');
        var title           = form.querySelector('.login-title');
        var textButton      = document.querySelector('.text');
        var formActionInput = document.getElementById('formAction');
        var loginTypeInput  = document.getElementById('loginType');
        var loginFields     = document.querySelector('.login-fields');
        var registerFields  = document.querySelector('.register-fields');
        var emailInput      = document.getElementById('loginEmail');

        if (toggleLink && form && title && textButton) {
            toggleLink.addEventListener('click', function (e) {
                e.preventDefault();
                form.classList.toggle('register-mode');

                if (form.classList.contains('register-mode')) {
                    if (loginFields)    loginFields.style.display    = 'none';
                    if (registerFields) registerFields.style.display = 'flex';
                    title.textContent = 'Create Account';
                    textButton.textContent = 'REGISTER';
                    toggleLink.innerHTML = 'Already have an account? <i class="fa fa-long-arrow-left"></i>';
                    if (formActionInput) formActionInput.value = 'register';
                } else {
                    if (loginFields)    loginFields.style.display    = 'flex';
                    if (registerFields) registerFields.style.display = 'none';
                    if (loginTypeInput) loginTypeInput.value = 'customer';
                    title.textContent = 'Member Login';
                    textButton.textContent = 'LOGIN';
                    if (emailInput) emailInput.placeholder = 'Email address';
                    toggleLink.innerHTML = 'Create your Account <i class="fa fa-long-arrow-right"></i>';
                    if (formActionInput) formActionInput.value = 'login';
                }
            });
        }

        /* admin toggle */
        var adminToggle = document.createElement('a');
        adminToggle.href = '#';
        adminToggle.className = 'admin-toggle';
        adminToggle.textContent = 'Login as Admin';

        var forgotSection = document.querySelector('.text-center.p-t-12');
        if (forgotSection) forgotSection.appendChild(adminToggle);

        adminToggle.addEventListener('click', function (e) {
            e.preventDefault();
            if (!form.classList.contains('register-mode')) {
                if (loginTypeInput && loginTypeInput.value === 'customer') {
                    loginTypeInput.value = 'admin';
                    this.textContent = 'Login as Customer';
                    if (emailInput) emailInput.placeholder = 'Email address';
                    title.textContent = 'Admin Login';
                } else if (loginTypeInput) {
                    loginTypeInput.value = 'customer';
                    this.textContent = 'Login as Admin';
                    if (emailInput) emailInput.placeholder = 'Email address';
                    title.textContent = 'Member Login';
                }
            }
        });
    });

    /* AJAX auth + fingerprint */
    $(document).ready(function () {
        var container = document.querySelector('.container');
        if (!container) return;

        var ok    = container.querySelector('.ok');
        var cross = container.querySelector('.cross');
        var pendingRedirect = null;   
        var pendingAlert    = null;   

        if (ok)    { ok.style.display    = 'none'; ok.style.opacity    = '0'; }
        if (cross) { cross.style.display = 'none'; cross.style.opacity = '0'; }

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

        /* 6-second Container animation completes */
        container.addEventListener('animationend', function () {
            container.classList.remove('active');
            if (ok)    { ok.style.display    = 'none'; ok.style.opacity    = '0'; }
            if (cross) { cross.style.display = 'none'; cross.style.opacity = '0'; }

            if (pendingRedirect) {
                window.location.href = pendingRedirect;
                pendingRedirect = null;
            }
            if (pendingAlert) {
                alert(pendingAlert);
                pendingAlert = null;
            }
        });

        /* clear validation on type */
        document.querySelectorAll('.input100').forEach(function (inp) {
            inp.addEventListener('input', function () {
                this.closest('.input-wrap').classList.remove('alert-validate');
                this.closest('.input-wrap').classList.remove('alert-mismatch');
            });
        });

        /* submit handler */
        container.addEventListener('click', async function (e) {
            e.preventDefault();

            var form = document.querySelector('.login100-form');
            var isRegisterMode = form.classList.contains('register-mode');
            var isValid = validateAllInputs();

            if (!isValid) { showResult(false); return; }

            var formData = new FormData();

            if (isRegisterMode) {
                var fullname        = $('#regFullname').val().trim();
                var email           = $('#regEmail').val().trim();
                var password        = $('#regPassword').val().trim();
                var confirmPassword = $('#confirmPassword').val().trim();

                if (password !== confirmPassword) {
                    showResult(false);
                    pendingAlert = 'Passwords do not match';
                    return;
                }

                formData.append('action',           'register');
                formData.append('fullname',          fullname);
                formData.append('email',             email);
                formData.append('password',          password);
                formData.append('confirm_password',  confirmPassword);
                formData.append('driving_licence',   $('#regDriving').val().trim());

            } else {
                var email2     = $('#loginEmail').val().trim();
                var password2  = $('#loginPassword').val().trim();
                var loginType  = $('#loginType').val() || 'customer';

                formData.append('action',    'login');
                formData.append('email',     email2);
                formData.append('password',  password2);
                formData.append('loginType', loginType);
            }

            try {
                var response = await fetch('loginPage.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                var result = await response.json();

                if (result.success) {
                    showResult(true);
                    pendingRedirect = result.redirect;   /* redirect fires on animationend */
                } else {
                    showResult(false);
                    pendingAlert = result.message;       /* alert fires on animationend */
                }
            } catch (err) {
                console.error('Error:', err);
                showResult(false);
                pendingAlert = 'An error occurred. Please try again.';
            }
        });
    });

})(jQuery);

/* HAMBURGER NAV TOGGLE */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var hamburger = document.getElementById('navHamburger');
        var nav       = document.getElementById('loginNav');
        var overlay   = document.getElementById('navOverlay');

        function openNav() {
            nav.classList.add('nav-open');
            hamburger.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeNav() {
            nav.classList.remove('nav-open');
            hamburger.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        if (hamburger) hamburger.addEventListener('click', function () {
            nav.classList.contains('nav-open') ? closeNav() : openNav();
        });

        if (overlay) overlay.addEventListener('click', closeNav);

        // Close on resize to desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth > 768) closeNav();
        });
    });
})();

document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.eye-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = this.getAttribute('data-target');
            var input    = document.getElementById(targetId);
            var icon     = this.querySelector('i');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    /* live password-match indicator on confirm field */
    var regPass    = document.getElementById('regPassword');
    var confirmPass = document.getElementById('confirmPassword');
    if (regPass && confirmPass) {
        function checkMatch() {
            var wrap = confirmPass.closest('.input-wrap');
            if (confirmPass.value.length > 0) {
                if (regPass.value === confirmPass.value) {
                    wrap.classList.remove('alert-mismatch');
                    confirmPass.style.borderColor = '#28a745';
                } else {
                    confirmPass.style.borderColor = '#e05252';
                }
            } else {
                confirmPass.style.borderColor = '';
            }
        }
        confirmPass.addEventListener('input', checkMatch);
        regPass.addEventListener('input', checkMatch);
    }
});

/* ACCESSIBILITY DROPDOWN */

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var selector  = document.querySelector('.language-selector');
        var trigger   = selector ? selector.querySelector(':scope > a') : null;
        var dropdown  = selector ? selector.querySelector('.language-settings-dropdown') : null;
        var closeTimer;

        function openDropdown() {
            clearTimeout(closeTimer);
            if (selector) selector.classList.add('open');
        }

        function scheduleClose() {
            closeTimer = setTimeout(function () {
                if (selector) selector.classList.remove('open');
            }, 120); /* small grace period so cursor can reach the panel */
        }

        if (trigger)  { trigger.addEventListener('mouseenter',  openDropdown); trigger.addEventListener('mouseleave',  scheduleClose); }
        if (dropdown) { dropdown.addEventListener('mouseenter', openDropdown); dropdown.addEventListener('mouseleave', scheduleClose); }

        /* close on outside click */
        document.addEventListener('click', function (e) {
            if (selector && !selector.contains(e.target)) selector.classList.remove('open');
        });
    });
})();


(function () {
    var currentFontSize = parseInt((document.cookie.match(/fontSize=(\d+)/) || [0,'100'])[1]);

    function updateFontSize() {
        var d = document.getElementById('font-size-display');
        if (d) d.textContent = currentFontSize + '%';
        document.documentElement.style.fontSize = currentFontSize + '%';
        document.cookie = 'fontSize=' + currentFontSize + '; path=/; max-age=' + (60*60*24*365);
    }

    function setTheme(theme) {
        document.cookie = 'darkMode=' + theme + '; path=/; max-age=' + (60*60*24*365);
        location.reload();
    }

    function setLanguage(lang) {
        document.cookie = 'language=' + lang + '; path=/; max-age=' + (60*60*24*365);
        location.reload();
    }

    document.addEventListener('DOMContentLoaded', function () {
        updateFontSize();

        document.querySelectorAll('.theme-option').forEach(function (el) {
            el.addEventListener('click', function (e) { e.preventDefault(); setTheme(this.getAttribute('data-theme')); });
        });

        var dec = document.getElementById('font-decrease');
        var inc = document.getElementById('font-increase');
        var rst = document.getElementById('font-reset');

        if (dec) dec.addEventListener('click', function () { if (currentFontSize > 70)  { currentFontSize -= 10; updateFontSize(); } });
        if (inc) inc.addEventListener('click', function () { if (currentFontSize < 150) { currentFontSize += 10; updateFontSize(); } });
        if (rst) rst.addEventListener('click', function () { currentFontSize = 100; updateFontSize(); });

        document.querySelectorAll('.language-option').forEach(function (el) {
            el.addEventListener('click', function (e) { e.preventDefault(); setLanguage(this.getAttribute('data-lang')); });
        });
    });
})();
</script>

</body>
</html>