<?php
session_start();
$is_logged_in = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer';

$servername = "localhost";
$username = "root";
$password = "AstonUni786!";
$dbname = "car_rental_db";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $pdo = null;
    error_log("Database connection failed: " . $e->getMessage());
}

$form_message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userType = $_POST['userType'] ?? 'customer';
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $dateOfBirth = $_POST['dateOfBirth'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $postcode = trim($_POST['postcode'] ?? '');
    $drivingLicense = trim($_POST['drivingLicense'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $adminCode = $_POST['adminCode'] ?? '';
    $terms = isset($_POST['terms']);
    $marketing = isset($_POST['marketing']);

    if (empty($firstName) || empty($lastName) || empty($email) || empty($phone) || 
        empty($dateOfBirth) || empty($password) || empty($confirmPassword)) {
        $form_message = 'Please fill in all required fields.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_message = 'Please enter a valid email address.';
        $message_type = 'error';
    } elseif (strlen($password) < 8) {
        $form_message = 'Password must be at least 8 characters long.';
        $message_type = 'error';
    } elseif (!preg_match('/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/', $password)) {
        $form_message = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
        $message_type = 'error';
    } elseif ($password !== $confirmPassword) {
        $form_message = 'Passwords do not match.';
        $message_type = 'error';
    } elseif (!$terms) {
        $form_message = 'You must accept the Terms of Service to continue.';
        $message_type = 'error';
    } elseif ($userType === 'admin' && $adminCode !== 'ADMIN2025') {
        $form_message = 'Invalid admin registration code.';
        $message_type = 'error';
    } else {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $form_message = 'This email address is already registered. Please use a different email or login.';
                    $message_type = 'error';
                } else {
                    $city_id = null;
                    if (!empty($city)) {
                        $stmt = $pdo->prepare("SELECT city_id FROM cities WHERE city_name = ?");
                        $stmt->execute([$city]);
                        $result = $stmt->fetch();
                        
                        if ($result) {
                            $city_id = $result['city_id'];
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO cities (city_name, region) VALUES (?, ?)");
                            $stmt->execute([$city, 'Unknown']);
                            $city_id = $pdo->lastInsertId();
                        }
                    }

                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $pdo->prepare("
                        INSERT INTO customers 
                        (first_name, last_name, email, password, phone, city_id, driving_license, address, postcode, date_of_birth, marketing_opt_in, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    
                    $stmt->execute([
                        $firstName,
                        $lastName,
                        $email,
                        $hashed_password,
                        $phone,
                        $city_id, // This can be NULL
                        $drivingLicense ?: null,
                        $address ?: null,
                        $postcode ?: null,
                        $dateOfBirth ?: null,
                        $marketing ? 1 : 0
                    ]);

                    $customer_id = $pdo->lastInsertId();

                    $_SESSION['customer_id'] = $customer_id;
                    $_SESSION['user'] = [
                        'id' => $customer_id,
                        'firstName' => $firstName,
                        'lastName' => $lastName,
                        'email' => $email,
                        'role' => 'customer'
                    ];

                    $form_message = 'Account created successfully! Redirecting to your dashboard...';
                    $message_type = 'success';

                    $_POST = array();

                    
                    header("Refresh: 2; URL=customer-dashboard.php");
                }
            } catch(PDOException $e) {
                $form_message = 'Database error: ' . $e->getMessage() . '. Please check your input.';
                $message_type = 'error';
                error_log("Registration PDO Error: " . $e->getMessage());
            }
        } else {
            $form_message = 'Database connection error. Please try again later.';
            $message_type = 'error';
        }
    }
}

$basket_count = 0;
if (isset($_SESSION['customer_id']) && $pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM basket_items bi 
            JOIN baskets b ON bi.basket_id = b.basket_id 
            WHERE b.customer_id = ? AND b.status = 'active'
        ");
        $stmt->execute([$_SESSION['customer_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $basket_count = $result['count'] ?? 0;
    } catch(PDOException $e) {
        
        error_log("Basket count error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .register-container {
            padding: 60px 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 80px);
            display: block;         
        }

        .register-form-container {
            margin-top: 20px;
        }

        .register-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            align-items: start;   
        }

        .register-info {
            padding: 20px;
            padding-top: 50px;
        }
        
        .register-title {
            color: var(--vivid-indigo);
            font-size: 2.5rem;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .register-subtitle {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .benefits-list {
            list-style: none;
            margin-top: 30px;
        }
        
        .benefits-list li {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            color: #555;
        }
        
        .benefits-list i {
            color: var(--cobalt-blue);
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }
        
        .register-form-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-title {
            color: var(--vivid-indigo);
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--vivid-indigo);
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            border-color: var(--cobalt-blue);
            outline: none;
        }
        
        .password-input {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
        }
        
        .terms-group {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .terms-group input {
            margin-top: 3px;
            width: 18px;
            height: 18px;
        }
        
        .terms-group label {
            font-weight: normal;
            color: #666;
            font-size: 0.9rem;
            line-height: 1.4;
        }
        
        .terms-group a {
            color: var(--cobalt-blue);
            text-decoration: none;
        }
        
        .terms-group a:hover {
            text-decoration: underline;
        }
        
        .submit-btn {
            background: linear-gradient(to right, var(--cobalt-blue), var(--vivid-indigo));
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0, 74, 173, 0.4);
            margin-bottom: 20px;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 74, 173, 0.5);
        }
        
        .submit-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .login-link {
            text-align: center;
            color: #666;
        }
        
        .login-link a {
            color: var(--cobalt-blue);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .form-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            <?php if (!empty($form_message)): ?>
            display: block;
            <?php else: ?>
            display: none;
            <?php endif; ?>
        }
        
        .form-message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .form-message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .password-strength {
            margin-top: 5px;
            height: 4px;
            border-radius: 2px;
            background: #f0f0f0;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .password-strength.weak .password-strength-bar {
            background-color: #ff4757;
            width: 33%;
        }
        
        .password-strength.medium .password-strength-bar {
            background-color: #ffa502;
            width: 66%;
        }
        
        .password-strength.strong .password-strength-bar {
            background-color: #2ed573;
            width: 100%;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
        
        .admin-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border-left: 4px solid var(--cobalt-blue);
            display: none;
        }
        
        .admin-info h4 {
            color: var(--cobalt-blue);
            margin: 0 0 8px 0;
            font-size: 0.9rem;
        }
        
        .admin-info p {
            margin: 0;
            font-size: 0.8rem;
            color: #666;
            line-height: 1.4;
        }

        /* Dropdown styles for header */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropbtn {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1000;
            border-radius: 4px;
            overflow: hidden;
        }

        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .dropdown-content a:hover {
            background-color: #f5f5f5;
            color: var(--cobalt-blue);
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        nav ul {
            display: flex;
            gap: 25px;
            list-style: none;
            align-items: center;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            margin: 0;
            position: relative;
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

        nav ul li a:hover,
        nav ul li a.active {
            background-color: rgba(255, 255, 255, 0.25);
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
        
        @media (max-width: 992px) {
            .register-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .register-info {
                text-align: center;
                padding: 0 20px;
            }
            
            .register-form-container {
                margin: 0 auto;
            }
            
            /* Responsive dropdown */
            .dropdown-content {
                position: static;
                box-shadow: none;
                background-color: transparent;
            }
            
            .dropdown-content a {
                color: white;
                padding: 10px 20px;
            }
            
            .dropdown:hover .dropdown-content {
                display: none;
            }
            
            .dropdown.active .dropdown-content {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .register-container {
                padding: 40px 0;
            }
            
            .register-title {
                font-size: 2rem;
            }
            
            .register-form-container {
                padding: 30px 25px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            nav ul {
                gap: 15px;
                flex-wrap: wrap;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .register-form-container {
                padding: 25px 20px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="container header-content">
            <div class="logo">
                <img src="logo2.png" alt="Logo">
            </div>

            <nav>
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

                    <?php if (!$is_logged_in): ?>
                        <li><a href="register.php" class="active">Register</a></li>
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

                    <li class="basket-indicator">
                        <a href="basket.php">
                            <i class="fas fa-shopping-basket"></i>
                            <?php if ($basket_count > 0): ?>
                                <span class="basket-count"><?php echo $basket_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="register-container">
        <div class="register-content">
            <div class="register-info">
                <h1 class="register-title">Join Motiv Car Hire Today</h1>
                <p class="register-subtitle">Create your account to enjoy faster bookings, save your favorite cars, manage your rentals, and access exclusive member deals.</p>
                
                <ul class="benefits-list">
                    <li>
                        <i class="fas fa-bolt"></i>
                        <span>Quick and easy booking process</span>
                    </li>
                    <li>
                        <i class="fas fa-heart"></i>
                        <span>Save your favorite vehicles</span>
                    </li>
                    <li>
                        <i class="fas fa-history"></i>
                        <span>Access your rental history</span>
                    </li>
                    <li>
                        <i class="fas fa-percentage"></i>
                        <span>Exclusive member discounts</span>
                    </li>
                    <li>
                        <i class="fas fa-shield-alt"></i>
                        <span>Enhanced security for your payments</span>
                    </li>
                </ul>
            </div>
            
            <div class="register-form-container">
                <div class="form-header">
                    <h2 class="form-title">Create Account</h2>
                    <p class="form-subtitle">Fill in your details to get started</p>
                </div>
                
                <?php if (!empty($form_message)): ?>
                <div id="formMessage" class="form-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($form_message); ?>
                </div>
                <?php else: ?>
                <div id="formMessage" class="form-message"></div>
                <?php endif; ?>
                
                <form id="registerForm" method="POST" action="">
                    <div class="form-group">
                        <label for="userType">Account Type</label>
                        <select id="userType" name="userType">
                            <option value="customer" <?php echo ($_POST['userType'] ?? 'customer') === 'customer' ? 'selected' : ''; ?>>Customer Account</option>
                            <option value="admin" <?php echo ($_POST['userType'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin Account</option>
                        </select>
                    </div>
                    
                    <div class="admin-info" id="adminInfo">
                        <h4>Admin Registration</h4>
                        <p>Admin accounts require a special registration code. Contact your system administrator to obtain this code.</p>
                    </div>
                    
                    <div class="form-group" id="adminCodeGroup" style="display: none;">
                        <label for="adminCode">Admin Registration Code *</label>
                        <input type="password" id="adminCode" name="adminCode" placeholder="Enter admin registration code" value="<?php echo htmlspecialchars($_POST['adminCode'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name *</label>
                            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="lastName">Last Name *</label>
                            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="dateOfBirth">Date of Birth *</label>
                        <input type="date" id="dateOfBirth" name="dateOfBirth" value="<?php echo htmlspecialchars($_POST['dateOfBirth'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <input type="text" id="address" name="address" placeholder="Street address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="postcode">Postcode</label>
                            <input type="text" id="postcode" name="postcode" value="<?php echo htmlspecialchars($_POST['postcode'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="drivingLicense">Driving License Number</label>
                        <input type="text" id="drivingLicense" name="drivingLicense" value="<?php echo htmlspecialchars($_POST['drivingLicense'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar"></div>
                        </div>
                        <div class="password-requirements">
                            Password must be at least 8 characters with uppercase, lowercase, and number
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password *</label>
                        <div class="password-input">
                            <input type="password" id="confirmPassword" name="confirmPassword" required>
                            <button type="button" class="toggle-password" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="terms-group">
                        <input type="checkbox" id="terms" name="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
                        <label for="terms">
                            I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a> *
                        </label>
                    </div>
                    
                    <div class="terms-group">
                        <input type="checkbox" id="marketing" name="marketing" <?php echo isset($_POST['marketing']) ? 'checked' : ''; ?>>
                        <label for="marketing">
                            I'd like to receive special offers and updates via email
                        </label>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">Create Account</button>
                    
                    <div class="login-link">
                        Already have an account? <a href="loginPage.php">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Motiv, Car Rental</h3>
                    <p>Your trusted partner for car rental services in Birmingham and beyond.</p>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="landing.php">Home</a></li>
                        <li><a href="cars.php">Our Fleet</a></li>
                        <li><a href="contact.php">Locations</a></li>
                        <li><a href="#">Offers</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li>New Street Station, Birmingham</li>
                        <li>0712345678</li>
                        <li>info@motivcarrental.com</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Motiv Car Rental. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.getElementById('registerForm');
            const formMessage = document.getElementById('formMessage');
            const submitBtn = document.getElementById('submitBtn');
            const togglePasswordBtn = document.getElementById('togglePassword');
            const toggleConfirmPasswordBtn = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const passwordStrength = document.getElementById('passwordStrength');
            const userTypeSelect = document.getElementById('userType');
            const adminInfo = document.getElementById('adminInfo');
            const adminCodeGroup = document.getElementById('adminCodeGroup');
            
            togglePasswordBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            toggleConfirmPasswordBtn.addEventListener('click', function() {
                const confirmPasswordInput = document.getElementById('confirmPassword');
                const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmPasswordInput.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            userTypeSelect.addEventListener('change', function() {
                if (this.value === 'admin') {
                    adminInfo.style.display = 'block';
                    adminCodeGroup.style.display = 'block';
                } else {
                    adminInfo.style.display = 'none';
                    adminCodeGroup.style.display = 'none';
                }
            });
            
            if (userTypeSelect.value === 'admin') {
                adminInfo.style.display = 'block';
                adminCodeGroup.style.display = 'block';
            }
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength += 1;
                
                if (/[a-z]/.test(password)) strength += 1;
                
                if (/[A-Z]/.test(password)) strength += 1;
                
                if (/[0-9]/.test(password)) strength += 1;
                
                if (/[^A-Za-z0-9]/.test(password)) strength += 1;
                
                passwordStrength.className = 'password-strength';
                if (password.length > 0) {
                    if (strength <= 2) {
                        passwordStrength.classList.add('weak');
                    } else if (strength <= 4) {
                        passwordStrength.classList.add('medium');
                    } else {
                        passwordStrength.classList.add('strong');
                    }
                }
            });
            
            registerForm.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirmPassword').value;
                const userType = document.getElementById('userType').value;
                const adminCode = document.getElementById('adminCode').value;
                const terms = document.getElementById('terms').checked;
                
                if (password.length < 8) {
                    e.preventDefault();
                    showMessage('Password must be at least 8 characters long.', 'error');
                    return;
                }
                
                if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])/.test(password)) {
                    e.preventDefault();
                    showMessage('Password must contain at least one uppercase letter, one lowercase letter, and one number.', 'error');
                    return;
                }
                
                if (password !== confirmPassword) {
                    e.preventDefault();
                    showMessage('Passwords do not match.', 'error');
                    return;
                }
                
                if (userType === 'admin' && !adminCode) {
                    e.preventDefault();
                    showMessage('Admin registration code is required.', 'error');
                    return;
                }
                
                if (!terms) {
                    e.preventDefault();
                    showMessage('You must accept the Terms of Service to continue.', 'error');
                    return;
                }
                
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating Account...';
            });
            
            function showMessage(text, type) {
                formMessage.textContent = text;
                formMessage.className = 'form-message ' + type;
                formMessage.style.display = 'block';
                
                formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
            
           
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(dropdown => {
                const dropbtn = dropdown.querySelector('.dropbtn');
                dropbtn.addEventListener('click', function(e) {
                    if (window.innerWidth <= 992) {
                        e.preventDefault();
                        dropdowns.forEach(other => {
                            if (other !== dropdown) {
                                other.classList.remove('active');
                            }
                        });
                        dropdown.classList.toggle('active');
                    }
                });
            });
            
            
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown') && window.innerWidth <= 992) {
                    dropdowns.forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                }
            });
        });
    </script>
</body>
</html>