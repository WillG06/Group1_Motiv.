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

$basketCount = 0;
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['id'];
    $userRole = $_SESSION['user']['role'];
    
    if ($userRole === 'customer') {
        $basketQuery = $conn->prepare("
            SELECT COUNT(bi.item_id) as item_count 
            FROM baskets b 
            LEFT JOIN basket_items bi ON b.basket_id = bi.basket_id 
            WHERE b.customer_id = ? AND b.status = 'active'
        ");
        $basketQuery->bind_param("i", $userId);
        $basketQuery->execute();
        $basketResult = $basketQuery->get_result();
        
        if ($basketResult->num_rows > 0) {
            $basketData = $basketResult->fetch_assoc();
            $basketCount = $basketData['item_count'];
        }
        $basketQuery->close();
    }
}

// Handle password reset
$step = 1; // 1 = email entry, 2 = password reset
$email = '';
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_email'])) {
        // Step 1: Check if email exists
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email)) {
            $message = 'Please enter your email address';
            $messageType = 'error';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address';
            $messageType = 'error';
        } else {
            // Check if email exists in customers table
            $stmt = $conn->prepare("SELECT customer_id, first_name FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                $step = 2; // Move to password reset step
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_user_id'] = $user['customer_id'];
            } else {
                $message = 'No account found with that email address';
                $messageType = 'error';
            }
            $stmt->close();
        }
    } elseif (isset($_POST['reset_password'])) {
        // Step 2: Reset the password
        $newPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $email = $_SESSION['reset_email'] ?? '';
        $userId = $_SESSION['reset_user_id'] ?? '';
        
        if (empty($newPassword) || empty($confirmPassword)) {
            $message = 'Please fill in all fields';
            $messageType = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match';
            $messageType = 'error';
        } elseif (strlen($newPassword) < 5) {
            $message = 'Password must be at least 5 characters long';
            $messageType = 'error';
        } else {
            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update the password in customers table
            $updateStmt = $conn->prepare("UPDATE customers SET password = ? WHERE customer_id = ? AND email = ?");
            $updateStmt->bind_param("sis", $hashedPassword, $userId, $email);
            
            if ($updateStmt->execute()) {
                $message = 'Password has been successfully reset! You can now login with your new password.';
                $messageType = 'success';
                
                // Clear session data
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_user_id']);
                $step = 1; // Return to step 1
            } else {
                $message = 'An error occurred. Please try again.';
                $messageType = 'error';
            }
            $updateStmt->close();
        }
    }
}

$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : 'light';
$fontSize = isset($_COOKIE['fontSize']) ? $_COOKIE['fontSize'] : '100';
$language = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en';

// Language variables
if ($language == 'en') {
    $themeText = 'Theme';
    $lightText = 'Light';
    $darkText = 'Dark';
    $fontSizeText = 'Font Size';
    $resetText = 'Reset';
    $languageText = 'Language';
    $home = 'Home';
    $about = 'About';
    $cars = 'Cars';
    $contact = 'Contact';
    $dashboard = 'Dashboard';
    $login = 'Login';
    $logout = 'Logout';
    $footerTagline = 'Your trusted partner for car rental services in Birmingham and beyond.';
    $quickLinks = 'Quick Links';
    $contactUs = 'Contact Us';
    $rightsReserved = 'All rights reserved.';
    
    // Page specific translations
    $pageTitle = 'Forgot Password';
    $resetSubtitle = 'Reset your password';
    $emailInstruction = 'Enter your email address to reset your password.';
    $emailLabel = 'Email Address';
    $checkEmailButton = 'Check Email';
    $newPasswordLabel = 'New Password';
    $confirmPasswordLabel = 'Confirm New Password';
    $resetButton = 'Reset Password';
    $backToLogin = 'Back to Login';
    $customerReset = 'Customer Password Reset';
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --vivid-indigo: #8C0050;
            --dark-magenta: #1800AD;
            --cobalt-blue: #004AAD;
            --coral-red: #FF7F50;
            --bg-grad-a: rgba(140, 0, 80, 0.82);
            --bg-grad-b: rgba(65, 88, 208, 0.90);
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #333333;
            --text-secondary: #666666;
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --footer-bg: #8C0050;
            --footer-text: #ecf0f1;
            --input-bg: #f1f1f5;
            --input-text: #333344;
            --input-border: transparent;
            --input-icon: #999aaa;
            --btn-bg: #d96817;
            --btn-shadow: rgba(217,104,23,0.35);
            --divider: #e8e8f0;
            --link-color: #8C0050;
            --success-color: #28a745;
            --error-color: #e05252;
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --card-bg: #333333;
            --border-color: #404040;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --footer-bg: #222222;
            --footer-text: #ffffff;
            --input-bg: #28283c;
            --input-text: #d8d8ee;
            --input-icon: #8888aa;
            --divider: #2e2e44;
            --link-color: #cc88bb;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: <?php echo $fontSize; ?>%;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        header {
            background: linear-gradient(to right, var(--vivid-indigo), var(--dark-magenta));
            color: white;
            height: 80px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .logo img {
            height: 115px;
            width: auto;
            margin-top: 3px;
            margin-left: -40px;
        }

        nav ul {
            display: flex;
            gap: 25px;
            list-style: none;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 4px;
            transition: 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        nav ul li a:hover {
            background-color: rgba(255, 255, 255, 0.25);
        }

        nav ul li.dropdown {
            position: relative;
        }

        nav ul li.dropdown .dropdown-content {
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
        }

        nav ul li.dropdown:hover .dropdown-content {
            display: block;
        }

        nav ul li.dropdown .dropdown-content a {
            color: #333;
            padding: 10px 14px;
            display: block;
            transition: background-color 0.3s;
            border-bottom: 1px solid #f1f1f1;
            font-size: 0.9rem;
        }

        nav ul li.dropdown .dropdown-content a:hover {
            background-color: #f8f9fa;
            color: var(--vivid-indigo);
        }

        [data-theme="dark"] nav ul li.dropdown .dropdown-content {
            background-color: #333333;
            border-color: #404040;
        }

        [data-theme="dark"] nav ul li.dropdown .dropdown-content a {
            color: #fff;
            background-color: #333333;
            border-bottom-color: #404040;
        }

        [data-theme="dark"] nav ul li.dropdown .dropdown-content a:hover {
            background-color: #404040;
            color: var(--coral-red);
        }

        .basket-indicator {
            position: relative;
            display: inline-block;
        }

        .basket-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--coral-red);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .language-selector {
            position: relative;
            display: flex;
            align-items: center;
        }

        .language-selector > a {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 8px;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-size: 18px;
            line-height: 0;
            color: white;
        }

        .language-selector:hover > a {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .language-settings-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            min-width: 200px;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            z-index: 1000;
        }

        [data-theme="dark"] .language-settings-dropdown {
            background-color: #333333;
            border-color: #404040;
            color: white;
        }

        .language-selector:hover .language-settings-dropdown {
            display: block;
        }

        .settings-section {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        [data-theme="dark"] .settings-section {
            border-color: #404040;
        }

        .settings-section:last-child {
            border-bottom: none;
        }

        .settings-section h4 {
            margin: 0 0 8px 0;
            color: #333;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        [data-theme="dark"] .settings-section h4 {
            color: #fff;
        }

        .theme-option, .language-option {
            display: flex;
            align-items: center;
            padding: 8px 12px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s;
            border-radius: 4px;
            margin-bottom: 2px;
            font-size: 14px;
        }

        [data-theme="dark"] .theme-option, 
        [data-theme="dark"] .language-option {
            color: #fff;
        }

        .theme-option:hover, .language-option:hover {
            background-color: #f1f1f1;
        }

        [data-theme="dark"] .theme-option:hover, 
        [data-theme="dark"] .language-option:hover {
            background-color: #404040;
        }

        .theme-option i, .language-option i {
            width: 18px;
            margin-right: 10px;
            color: var(--vivid-indigo);
            font-size: 14px;
        }

        .font-controls {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .font-btn {
            background: var(--vivid-indigo);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .font-btn:hover {
            background: var(--dark-magenta);
        }

        .font-size-display {
            font-size: 14px;
            color: #333;
            min-width: 50px;
            text-align: center;
            font-weight: 600;
        }

        [data-theme="dark"] .font-size-display {
            color: #fff;
        }

        .active-indicator {
            margin-left: auto;
            color: var(--vivid-indigo);
            font-size: 12px;
        }

        /* Forgot Password Page Specific Styles */
        .forgot-section {
            background: linear-gradient(135deg, var(--bg-grad-a) 0%, var(--bg-grad-b) 100%);
            min-height: calc(100vh - 80px - 300px);
            padding: 60px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .forgot-section::before,
        .forgot-section::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .forgot-section::before {
            width: 520px;
            height: 520px;
            background: rgba(255,255,255,0.035);
            top: -160px;
            left: -140px;
        }

        .forgot-section::after {
            width: 360px;
            height: 360px;
            background: rgba(255,255,255,0.03);
            bottom: -100px;
            right: -80px;
        }

        .forgot-box {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 500px;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.20);
            overflow: hidden;
        }

        .forgot-header {
            background: linear-gradient(to right, var(--vivid-indigo), var(--dark-magenta));
            color: white;
            padding: 30px 40px;
            text-align: center;
        }

        .forgot-header h1 {
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .forgot-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .forgot-body {
            padding: 40px;
            background: var(--card-bg);
        }

        .instruction-text {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 25px;
            text-align: center;
            line-height: 1.6;
        }

        .input-wrap {
            position: relative;
            width: 100%;
            margin-bottom: 25px;
        }

        .input100 {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: var(--input-text);
            width: 100%;
            height: 50px;
            background: var(--input-bg);
            border: 1.5px solid var(--input-border);
            border-radius: 8px;
            padding: 0 16px 0 45px;
            transition: all 0.22s;
        }

        .input100:focus {
            border-color: #d96817;
            box-shadow: 0 0 0 3px rgba(217,104,23,0.18);
            background: var(--card-bg);
            outline: none;
        }

        .input-wrap.has-eye .input100 {
            padding-right: 45px;
        }

        .symbol-input100 {
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--input-icon);
            font-size: 14px;
            pointer-events: none;
        }

        .eye-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: var(--input-icon);
            background: none;
            border: none;
            display: flex;
            align-items: center;
        }

        .eye-toggle:hover {
            color: #d96817;
        }

        .btn-container {
            width: 100%;
            margin-top: 15px;
        }

        .forgot-btn {
            width: 100%;
            height: 50px;
            border-radius: 25px;
            background: var(--btn-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            position: relative;
            box-shadow: 0 6px 20px var(--btn-shadow);
            transition: all 0.3s;
            border: none;
            color: white;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 14px;
        }

        .forgot-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px var(--btn-shadow);
        }

        .message-container {
            margin-bottom: 20px;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 14px;
            text-align: center;
        }

        .message-success {
            background: rgba(40, 167, 69, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .message-error {
            background: rgba(224, 82, 82, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .back-to-login {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid var(--divider);
        }

        .back-to-login a {
            color: var(--link-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        .back-to-login a:hover {
            color: var(--vivid-indigo);
            text-decoration: underline;
        }

        .back-to-login a i {
            font-size: 12px;
        }

        .reset-note {
            margin-top: 15px;
            padding: 10px;
            background: rgba(140, 0, 80, 0.05);
            border-radius: 8px;
            font-size: 12px;
            color: var(--text-secondary);
            text-align: center;
            border: 1px dashed var(--vivid-indigo);
        }

        .reset-note strong {
            color: var(--vivid-indigo);
        }

        /* Footer Styles */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-column h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.3rem;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: var(--coral-red);
        }

        .footer-column p {
            color: var(--footer-text);
            line-height: 1.6;
            opacity: 0.9;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
        }

        .footer-column ul li {
            margin-bottom: 12px;
        }

        .footer-column ul li a {
            color: var(--footer-text);
            text-decoration: none;
            transition: color 0.3s ease, padding-left 0.3s ease;
            display: inline-block;
        }

        .footer-column ul li a:hover {
            color: var(--coral-red);
            padding-left: 5px;
        }

        .footer-column ul li i {
            margin-right: 10px;
            color: var(--coral-red);
        }

        .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .copyright p {
            color: var(--footer-text);
            font-size: 0.9rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .forgot-header {
                padding: 25px 20px;
            }
            
            .forgot-header h1 {
                font-size: 24px;
            }
            
            .forgot-body {
                padding: 30px 20px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .footer-column h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
        }

        @media (max-width: 480px) {
            .forgot-box {
                max-width: 100%;
            }
        }
    </style>
</head>
<body data-theme="<?php echo $darkMode; ?>">

<header>
    <div class="container header-content">
        <div class="logo">
            <img src="logo2.png" alt="Logo">
        </div>

        <nav>
            <ul>
                <li class="dropdown">
                    <a href="landing.php" class="dropbtn"><?php echo $home; ?> <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="landing.php"><?php echo $home; ?></a>
                        <a href="about.php"><?php echo $about; ?></a>
                    </div>
                </li>
                <li><a href="cars.php"><?php echo $cars; ?></a></li>
                <li><a href="contact.php"><?php echo $contact; ?></a></li>

                <?php if (!isset($_SESSION['user'])): ?>
                    <li><a href="loginPage.php"><?php echo $login; ?></a></li>
                <?php else: ?>
                    <li><a href="customer-dashboard.php"><?php echo $dashboard; ?></a></li>
                    <li>
                        <a href="logout.php" style="color: #ff7f50;">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $logout; ?>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="language-selector">
                    <a href="#"><i class="fa-solid fa-circle-info"></i></a>
                    <div class="language-settings-dropdown">
                        <div class="settings-section">
                            <h4><?php echo $themeText; ?></h4>
                            <a href="#" class="theme-option" data-theme="light">
                                <i class="fas fa-sun"></i> <?php echo $lightText; ?>
                                <?php if ($darkMode == 'light'): ?>
                                    <i class="fas fa-check active-indicator"></i>
                                <?php endif; ?>
                            </a>
                            <a href="#" class="theme-option" data-theme="dark">
                                <i class="fas fa-moon"></i> <?php echo $darkText; ?>
                                <?php if ($darkMode == 'dark'): ?>
                                    <i class="fas fa-check active-indicator"></i>
                                <?php endif; ?>
                            </a>
                        </div>

                        <div class="settings-section">
                            <h4><?php echo $fontSizeText; ?></h4>
                            <div class="font-controls">
                                <button class="font-btn" id="font-decrease">A-</button>
                                <span class="font-size-display" id="font-size-display"><?php echo $fontSize; ?>%</span>
                                <button class="font-btn" id="font-increase">A+</button>
                                <button class="font-btn" id="font-reset"><?php echo $resetText; ?></button>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h4><?php echo $languageText; ?></h4>
                            <a href="#" class="language-option" data-lang="en">
                                <i class="fas fa-language"></i> English
                                <?php if ($language == 'en'): ?>
                                    <i class="fas fa-check active-indicator"></i>
                                <?php endif; ?>
                            </a>
                            <a href="#" class="language-option" data-lang="es">
                                <i class="fas fa-language"></i> Español
                            </a>
                            <a href="#" class="language-option" data-lang="fr">
                                <i class="fas fa-language"></i> Français
                            </a>
                            <a href="#" class="language-option" data-lang="de">
                                <i class="fas fa-language"></i> Deutsch
                            </a>
                        </div>
                    </div>
                </li>

                <li class="basket-indicator">
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

<section class="forgot-section">
    <div class="forgot-box">
        <div class="forgot-header">
            <h1><?php echo $pageTitle; ?></h1>
            <p><?php echo $resetSubtitle; ?></p>
        </div>

        <div class="forgot-body">
            <?php if (!empty($message)): ?>
                <div class="message-container message-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- Step 1: Email Entry -->
                <div class="instruction-text">
                    <?php echo $emailInstruction; ?>
                </div>

                <form method="POST" action="forgotPassword.php" id="emailForm">
                    <div class="input-wrap">
                        <input class="input100" type="email" name="email" id="email" placeholder="<?php echo $emailLabel; ?>" value="<?php echo htmlspecialchars($email); ?>" required>
                        <span class="symbol-input100"><i class="fa fa-envelope"></i></span>
                    </div>

                    <div class="btn-container">
                        <button type="submit" name="check_email" class="forgot-btn">
                            <?php echo $checkEmailButton; ?>
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <!-- Step 2: Password Reset -->
                <div class="instruction-text">
                    <strong>Email verified:</strong> <?php echo htmlspecialchars($_SESSION['reset_email']); ?>
                </div>

                <form method="POST" action="forgotPassword.php" id="resetForm">
                    <div class="input-wrap has-eye">
                        <input class="input100" type="password" name="password" id="password" placeholder="<?php echo $newPasswordLabel; ?>" required minlength="5">
                        <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        <button type="button" class="eye-toggle" data-target="password">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>

                    <div class="input-wrap has-eye">
                        <input class="input100" type="password" name="confirm_password" id="confirmPassword" placeholder="<?php echo $confirmPasswordLabel; ?>" required minlength="5">
                        <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        <button type="button" class="eye-toggle" data-target="confirmPassword">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>

                    <div class="btn-container">
                        <button type="submit" name="reset_password" class="forgot-btn">
                            <?php echo $resetButton; ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>

            <div class="back-to-login">
                <a href="loginPage.php">
                    <i class="fas fa-arrow-left"></i> <?php echo $backToLogin; ?>
                </a>
            </div>

            <div class="reset-note">
                <strong>Note:</strong> This is for customer accounts only.
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <h3>Motiv, Car Rental</h3>
                <p><?php echo $footerTagline; ?></p>
            </div>
            <div class="footer-column">
                <h3><?php echo $quickLinks; ?></h3>
                <ul>
                    <li><a href="landing.php"><?php echo $home; ?></a></li>
                    <li><a href="about.php"><?php echo $about; ?></a></li>
                    <li><a href="cars.php"><?php echo $cars; ?></a></li>
                    <li><a href="contact.php"><?php echo $contact; ?></a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3><?php echo $contactUs; ?></h3>
                <ul>
                    <li><i class="fas fa-map-marker-alt"></i> New Street Station, Birmingham</li>
                    <li><i class="fas fa-phone"></i> 0712345678</li>
                    <li><i class="fas fa-envelope"></i> info@motivcarrental.com</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Motiv Car Rental. <?php echo $rightsReserved; ?></p>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
(function($) {
    "use strict";

    // Eye toggle for password fields
    document.querySelectorAll('.eye-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    });

    // Password match validation for reset form
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirmPassword');
    
    if (password && confirmPassword) {
        function validateMatch() {
            if (confirmPassword.value.length > 0) {
                if (password.value !== confirmPassword.value) {
                    confirmPassword.style.borderColor = 'var(--error-color)';
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.style.borderColor = 'var(--success-color)';
                    confirmPassword.setCustomValidity('');
                }
            } else {
                confirmPassword.style.borderColor = '';
                confirmPassword.setCustomValidity('');
            }
        }
        
        password.addEventListener('input', validateMatch);
        confirmPassword.addEventListener('input', validateMatch);
        
        // Form validation before submit
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    }

    // Theme, font size, language settings
    let currentFontSize = <?php echo $fontSize; ?>;
    let currentTheme = '<?php echo $darkMode; ?>';
    let currentLanguage = '<?php echo $language; ?>';

    function updateFontSizeDisplay() {
        const display = document.getElementById('font-size-display');
        if (display) {
            display.textContent = currentFontSize + '%';
        }
        document.documentElement.style.fontSize = currentFontSize + '%';
        document.cookie = "fontSize=" + currentFontSize + "; path=/; max-age=" + (60 * 60 * 24 * 365);
    }

    function setTheme(theme) {
        currentTheme = theme;
        document.body.setAttribute('data-theme', theme);
        document.cookie = "darkMode=" + theme + "; path=/; max-age=" + (60 * 60 * 24 * 365);
        location.reload();
    }

    function setLanguage(lang) {
        currentLanguage = lang;
        document.cookie = "language=" + lang + "; path=/; max-age=" + (60 * 60 * 24 * 365);
        location.reload();
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateFontSizeDisplay();

        const themeOptions = document.querySelectorAll('.theme-option');
        themeOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                setTheme(this.getAttribute('data-theme'));
            });
        });

        const decreaseBtn = document.getElementById('font-decrease');
        const increaseBtn = document.getElementById('font-increase');
        const resetBtn = document.getElementById('font-reset');

        if (decreaseBtn) {
            decreaseBtn.addEventListener('click', function() {
                if (currentFontSize > 70) {
                    currentFontSize -= 10;
                    updateFontSizeDisplay();
                }
            });
        }

        if (increaseBtn) {
            increaseBtn.addEventListener('click', function() {
                if (currentFontSize < 150) {
                    currentFontSize += 10;
                    updateFontSizeDisplay();
                }
            });
        }

        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                currentFontSize = 100;
                updateFontSizeDisplay();
            });
        }

        const languageOptions = document.querySelectorAll('.language-option');
        languageOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                setLanguage(this.getAttribute('data-lang'));
            });
        });
    });

})(jQuery);
</script>
</body>
</html>
<?php
$conn->close();
?>
