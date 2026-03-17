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

// POST request handling
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
} elseif ($language == 'es') {
    $themeText = 'Tema';
    $lightText = 'Claro';
    $darkText = 'Oscuro';
    $fontSizeText = 'Tamaño de fuente';
    $resetText = 'Reiniciar';
    $languageText = 'Idioma';
    $home = 'Inicio';
    $about = 'Sobre Nosotros';
    $cars = 'Autos';
    $contact = 'Contacto';
    $dashboard = 'Panel';
    $login = 'Iniciar sesión';
    $logout = 'Cerrar sesión';
    $footerTagline = 'Su socio de confianza para servicios de alquiler de autos en Birmingham y más allá.';
    $quickLinks = 'Enlaces rápidos';
    $contactUs = 'Contáctenos';
    $rightsReserved = 'Todos los derechos reservados.';
} elseif ($language == 'fr') {
    $themeText = 'Thème';
    $lightText = 'Clair';
    $darkText = 'Sombre';
    $fontSizeText = 'Taille de police';
    $resetText = 'Réinitialiser';
    $languageText = 'Langue';
    $home = 'Accueil';
    $about = 'À propos';
    $cars = 'Voitures';
    $contact = 'Contact';
    $dashboard = 'Tableau de bord';
    $login = 'Connexion';
    $logout = 'Déconnexion';
    $footerTagline = 'Votre partenaire de confiance pour les services de location de voitures à Birmingham et au-delà.';
    $quickLinks = 'Liens rapides';
    $contactUs = 'Contactez-nous';
    $rightsReserved = 'Tous droits réservés.';
} elseif ($language == 'de') {
    $themeText = 'Design';
    $lightText = 'Hell';
    $darkText = 'Dunkel';
    $fontSizeText = 'Schriftgröße';
    $resetText = 'Zurücksetzen';
    $languageText = 'Sprache';
    $home = 'Startseite';
    $about = 'Über uns';
    $cars = 'Autos';
    $contact = 'Kontakt';
    $dashboard = 'Dashboard';
    $login = 'Anmelden';
    $logout = 'Abmelden';
    $footerTagline = 'Ihr vertrauenswürdiger Partner für Autovermietungen in Birmingham und darüber hinaus.';
    $quickLinks = 'Schnelllinks';
    $contactUs = 'Kontaktieren Sie uns';
    $rightsReserved = 'Alle Rechte vorbehalten.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Motiv Car Hire</title>
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

        /* Header Styles (from about.php) */
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

        /* Login Page Specific Styles */
        .login-section {
            background: linear-gradient(135deg, var(--bg-grad-a) 0%, var(--bg-grad-b) 100%);
            min-height: calc(100vh - 80px - 300px);
            padding: 60px 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .login-section::before,
        .login-section::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .login-section::before {
            width: 520px; height: 520px;
            background: rgba(255,255,255,0.035);
            top: -160px; left: -140px;
        }

        .login-section::after {
            width: 360px; height: 360px;
            background: rgba(255,255,255,0.03);
            bottom: -100px; right: -80px;
        }

        .login-box {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 900px;
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.20);
            display: flex;
            overflow: hidden;
        }

        .login-left {
            width: 300px;
            background: var(--bg-secondary);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            border-right: 1px solid var(--divider);
        }

        .login-left img {
            max-width: 100%;
            width: 200px;
            margin-bottom: 20px;
        }

        .login-left p {
            text-align: center;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .login-left p strong {
            display: block;
            font-size: 16px;
            color: var(--vivid-indigo);
            margin-bottom: 5px;
        }

        .login-right {
            flex: 1;
            padding: 50px 50px 40px;
            background: var(--card-bg);
        }

        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--vivid-indigo);
            margin-bottom: 19px; 
        }

        .login-fields,
        .register-fields {
            display: flex;
            flex-direction: column;
            gap: 18px; 
        }

        .register-fields {
            display: none;
        }

        .register-mode .login-fields {
            display: none;
        }

        .register-mode .register-fields {
            display: flex;
        }

        .input-wrap {
            position: relative;
            width: 100%;
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

        .input-wrap.has-eye .input100 {
            padding-right: 45px;
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

        .alert-validate .input100 {
            border-color: #e05252;
            animation: shake 0.3s;
        }

        .alert-validate::before {
            content: attr(data-validate);
            position: absolute;
            bottom: -32px;
            left: 0;
            width: 100%;
            background: #e05252;
            color: #fff;
            font-size: 11px;
            text-align: center;
            padding: 5px 10px;
            border-radius: 4px;
            z-index: 100;
        }

        .alert-validate::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border: 5px solid transparent;
            border-bottom-color: #e05252;
            z-index: 101;
        }

        .alert-mismatch::before {
            content: "Passwords do not match";
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Fixed button container with proper positioning for animations */
        .login-btn-container {
            width: 100%;
            height: 50px;
            border-radius: 25px;
            background: var(--btn-bg);
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            position: relative;
            margin: 35px auto 0; /* Increased top margin for more space */
            box-shadow: 0 6px 20px var(--btn-shadow);
            transition: all 0.3s;
            overflow: hidden; /* Changed from 'visible' to 'hidden' to contain animations */
        }

        .login-btn-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px var(--btn-shadow);
        }

        .btn-text {
            font-weight: 700;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-size: 14px;
            position: relative;
            z-index: 2;
            transition: opacity 0.3s;
        }

        /* Animation elements container */
        .animation-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            pointer-events: none;
            z-index: 3;
        }

        .fingerprint {
            position: absolute;
            opacity: 0;
            stroke: #777;
            transition: opacity 1ms;
            width: 40px;
            height: 40px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .fingerprint-active {
            stroke: #fff;
        }

        .fingerprint-out {
            opacity: 0;
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

        .ok, .cross {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            stroke: #fff;
            transition: opacity 300ms;
            width: 40px;
            height: 40px;
            pointer-events: none;
            display: none;
            z-index: 4;
        }

        /* Active state animations */
        .active.login-btn-container {
            animation: Container 3.5s forwards; /* Slightly faster animation */
        }

        .active .btn-text {
            opacity: 0;
            animation: Text 3.5s forwards;
        }

        .active .fingerprint {
            opacity: 1;
            transition: opacity 300ms 150ms;
        }

        .active .fingerprint-base .odd {
            stroke-dasharray: 50px 50px;
            transition: stroke-dasharray 600ms 80ms;
        }

        .active .fingerprint-base .even {
            stroke-dashoffset: 0px;
            transition: stroke-dashoffset 600ms;
        }

        .active .fingerprint-active .odd {
            stroke-dasharray: 50px 50px;
            transition: stroke-dasharray 1300ms 900ms;
        }

        .active .fingerprint-active .even {
            stroke-dashoffset: 0px;
            transition: stroke-dashoffset 1300ms 700ms;
        }

        .active .fingerprint-out {
            opacity: 0;
            transition: opacity 300ms 2400ms;
        }

        .active .ok {
            display: block;
            opacity: 1;
            animation: Ok 3.5s forwards;
        }

        .active .cross {
            display: block;
            animation: Cross 3.5s forwards;
        }

        @keyframes Container {
            0% { width: 100%; border-radius: 25px; }
            10% { width: 50px; border-radius: 50%; }
            70% { transform: scale(1); }
            74% { transform: scale(1.15); }
            76% { transform: scale(1); }
            90% { width: 50px; border-radius: 50%; }
            100% { width: 100%; border-radius: 25px; }
        }

        @keyframes Text {
            0% { opacity: 1; transform: scale(1); }
            10% { opacity: 0; transform: scale(0.5); }
            90% { opacity: 0; transform: scale(0.5); }
            100% { opacity: 1; transform: scale(1); }
        }

        @keyframes Ok {
            0% { opacity: 0; }
            70% { opacity: 0; transform: translate(-50%, -50%) scale(0); }
            75% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
            77% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            88% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            92% { opacity: 0; transform: translate(-50%, -50%) scale(0.5); }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(0); }
        }

        @keyframes Cross {
            0% { opacity: 0; }
            70% { opacity: 0; transform: translate(-50%, -50%) scale(0); }
            75% { opacity: 1; transform: translate(-50%, -50%) scale(1.1); }
            77% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            88% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            92% { opacity: 0; transform: translate(-50%, -50%) scale(0.5); }
            100% { opacity: 0; transform: translate(-50%, -50%) scale(0); }
        }

        .form-divider {
            width: 100%;
            height: 1px;
            background: var(--divider);
            margin: 25px 0 15px; /* Increased top margin */
        }

        .form-footer-links {
            margin-top: 15px;
            display: flex;
            flex-direction: column;
            gap: 12px; /* Slightly increased gap */
        }

        .form-footer-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            flex-wrap: wrap;
        }

        .txt1 {
            color: var(--text-secondary);
        }

        .txt2 {
            color: var(--link-color);
            font-weight: 600;
            text-decoration: none;
        }

        .txt2:hover {
            text-decoration: underline;
        }

        .admin-toggle {
            display: block;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            color: var(--cobalt-blue) !important;
            cursor: pointer;
            text-decoration: none;
        }

        .admin-toggle:hover {
            text-decoration: underline;
        }

        [data-theme="dark"] .admin-toggle {
            color: #7799ee !important;
        }

        /* Footer Styles (from about.php) */
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
            .login-box {
                flex-direction: column;
            }
            
            .login-left {
                width: 100%;
                border-right: none;
                border-bottom: 1px solid var(--divider);
                flex-direction: row;
                padding: 20px;
                gap: 15px;
            }
            
            .login-left img {
                width: 80px;
                margin-bottom: 0;
            }
            
            .login-left p {
                text-align: left;
            }
            
            .login-right {
                padding: 30px;
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
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 25px;
            }
            
            .login-title {
                font-size: 24px;
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
                    <li><a href="loginPage.php" style="background-color: rgba(255,255,255,0.25);"><?php echo $login; ?></a></li>
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
                                <?php if ($language == 'es'): ?>
                                    <i class="fas fa-check active-indicator"></i>
                                <?php endif; ?>
                            </a>
                            <a href="#" class="language-option" data-lang="fr">
                                <i class="fas fa-language"></i> Français
                                <?php if ($language == 'fr'): ?>
                                    <i class="fas fa-check active-indicator"></i>
                                <?php endif; ?>
                            </a>
                            <a href="#" class="language-option" data-lang="de">
                                <i class="fas fa-language"></i> Deutsch
                                <?php if ($language == 'de'): ?>
                                    <i class="fas fa-check active-indicator"></i>
                                <?php endif; ?>
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

<section class="login-section">
    <div class="login-box">
        <div class="login-left">
            <img src="img-01.png" alt="Login">
            <p>
                <strong>Welcome back</strong>
                Sign in to manage your bookings
            </p>
        </div>

        <div class="login-right">
            <form class="login100-form" id="authForm">
                <span class="login-title" id="formTitle" style="margin-bottom: 29px; display: block;">Customer Login</span>

                <!-- Login Fields -->
                <div class="login-fields">
                    <div class="input-wrap validate-input" data-validate="Valid email is required">
                        <input class="input100" type="text" name="email" id="loginEmail" placeholder="Email address">
                        <span class="symbol-input100"><i class="fa fa-envelope"></i></span>
                    </div>

                    <div class="input-wrap validate-input has-eye" data-validate="Password is required">
                        <input class="input100" type="password" name="password" id="loginPassword" placeholder="Password">
                        <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        <button type="button" class="eye-toggle" data-target="loginPassword">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Register Fields -->
                <div class="register-fields">
                    <div class="input-wrap validate-input" data-validate="Full name is required">
                        <input class="input100" type="text" id="regFullname" name="fullname" placeholder="Full Name">
                        <span class="symbol-input100"><i class="fa fa-user"></i></span>
                    </div>

                    <div class="input-wrap validate-input" data-validate="Valid email is required">
                        <input class="input100" type="text" id="regEmail" name="reg_email" placeholder="Email address">
                        <span class="symbol-input100"><i class="fa fa-envelope"></i></span>
                    </div>

                    <div class="input-wrap validate-input has-eye" data-validate="Password is required">
                        <input class="input100" type="password" id="regPassword" placeholder="Password">
                        <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        <button type="button" class="eye-toggle" data-target="regPassword">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>

                    <div class="input-wrap validate-input has-eye" data-validate="Confirm password">
                        <input class="input100" type="password" id="confirmPassword" placeholder="Confirm Password">
                        <span class="symbol-input100"><i class="fa fa-lock"></i></span>
                        <button type="button" class="eye-toggle" data-target="confirmPassword">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" name="action" id="formAction" value="login">
                <input type="hidden" name="loginType" id="loginType" value="customer">

                <div class="login-btn-container" id="submitBtn">
                    <span class="btn-text" id="btnText">LOGIN</span>
                    
                    <!-- Animation container to properly position all animated elements -->
                    <div class="animation-container">
                        <svg class="fingerprint fingerprint-base" width="40" height="40" viewBox="0 0 100 100">
                            <g class="fingerprint-out" fill="none" stroke-width="2" stroke-linecap="round">
                                <path class="odd" d="m 25.117139,57.142857 c 0,0 -1.968558,-7.660465 -0.643619,-13.149003 1.324939,-5.488538 4.659682,-8.994751 4.659682,-8.994751"/>
                                <path class="odd" d="m 31.925369,31.477584 c 0,0 2.153609,-2.934998 9.074971,-5.105078 6.921362,-2.17008 11.799844,-0.618718 11.799844,-0.618718"/>
                                <path class="odd" d="m 57.131213,26.814448 c 0,0 5.127709,1.731228 9.899495,7.513009 4.771786,5.781781 4.772971,12.109204 4.772971,12.109204"/>
                                <path class="odd" d="m 72.334009,50.76769 0.09597,2.298098 -0.09597,2.386485"/>
                                <path class="even" d="m 27.849282,62.75 c 0,0 1.286086,-1.279223 1.25,-4.25 -0.03609,-2.970777 -1.606117,-7.675266 -0.625,-12.75 0.981117,-5.074734 4.5,-9.5 4.5,-9.5"/>
                                <path class="even" d="m 36.224282,33.625 c 0,0 8.821171,-7.174484 19.3125,-2.8125 10.491329,4.361984 11.870558,14.952665 11.870558,14.952665"/>
                                <path class="even" d="m 68.349282,49.75 c 0,0 0.500124,3.82939 0.5625,5.8125 0.06238,1.98311 -0.1875,5.9375 -0.1875,5.9375"/>
                                <path class="odd" d="m 31.099282,65.625 c 0,0 1.764703,-4.224042 2,-7.375 0.235297,-3.150958 -1.943873,-9.276886 0.426777,-15.441942 2.370649,-6.165056 8.073223,-7.933058 8.073223,-7.933058"/>
                                <path class="odd" d="m 45.849282,33.625 c 0,0 12.805566,-1.968622 17,9.9375 4.194434,11.906122 1.125,24.0625 1.125,24.0625"/>
                                <path class="even" d="m 59.099282,70.25 c 0,0 0.870577,-2.956221 1.1875,-4.5625 0.316923,-1.606279 0.5625,-5.0625 0.5625,-5.0625"/>
                                <path class="even" d="m 60.901059,56.286612 c 0,0 0.903689,-9.415996 -3.801777,-14.849112 -3.03125,-3.5 -7.329245,-4.723939 -11.867187,-3.8125 -5.523438,1.109375 -7.570313,5.75 -7.570313,5.75"/>
                                <path class="even" d="m 34.072577,68.846248 c 0,0 2.274231,-4.165782 2.839205,-9.033748 0.443558,-3.821814 -0.49394,-5.649939 -0.714206,-8.05386 -0.220265,-2.403922 0.21421,-4.63364 0.21421,-4.63364"/>
                                <path class="odd" d="m 37.774165,70.831845 c 0,0 2.692139,-6.147592 3.223034,-11.251208 0.530895,-5.103616 -2.18372,-7.95562 -0.153491,-13.647655 2.030229,-5.692035 8.108442,-4.538898 8.108442,-4.538898"/>
                                <path class="odd" d="m 54.391174,71.715729 c 0,0 2.359472,-5.427681 2.519068,-16.175068 0.159595,-10.747388 -4.375223,-12.993087 -4.375223,-12.993087"/>
                                <path class="even" d="m 49.474282,73.625 c 0,0 3.730297,-8.451831 3.577665,-16.493718 -0.152632,-8.041887 -0.364805,-11.869326 -4.765165,-11.756282 -4.400364,0.113044 -3.875,4.875 -3.875,4.875"/>
                                <path class="even" d="m 41.132922,72.334447 c 0,0 2.49775,-5.267079 3.181981,-8.883029 0.68423,-3.61595 0.353553,-9.413359 0.353553,-9.413359"/>
                                <path class="odd" d="m 45.161782,73.75 c 0,0 1.534894,-3.679847 2.40625,-6.53125 0.871356,-2.851403 1.28125,-7.15625 1.28125,-7.15625"/>
                                <path class="odd" d="m 48.801947,56.125 c 0,0 0.234502,-1.809418 0.109835,-3.375 -0.124667,-1.565582 -0.5625,-3.1875 -0.5625,-3.1875"/>
                            </g>
                        </svg>

                        <svg class="fingerprint fingerprint-active" width="40" height="40" viewBox="0 0 100 100">
                            <g class="fingerprint-out" fill="none" stroke-width="2" stroke-linecap="round">
                                <path class="odd" d="m 25.117139,57.142857 c 0,0 -1.968558,-7.660465 -0.643619,-13.149003 1.324939,-5.488538 4.659682,-8.994751 4.659682,-8.994751"/>
                                <path class="odd" d="m 31.925369,31.477584 c 0,0 2.153609,-2.934998 9.074971,-5.105078 6.921362,-2.17008 11.799844,-0.618718 11.799844,-0.618718"/>
                                <path class="odd" d="m 57.131213,26.814448 c 0,0 5.127709,1.731228 9.899495,7.513009 4.771786,5.781781 4.772971,12.109204 4.772971,12.109204"/>
                                <path class="odd" d="m 72.334009,50.76769 0.09597,2.298098 -0.09597,2.386485"/>
                                <path class="even" d="m 27.849282,62.75 c 0,0 1.286086,-1.279223 1.25,-4.25 -0.03609,-2.970777 -1.606117,-7.675266 -0.625,-12.75 0.981117,-5.074734 4.5,-9.5 4.5,-9.5"/>
                                <path class="even" d="m 36.224282,33.625 c 0,0 8.821171,-7.174484 19.3125,-2.8125 10.491329,4.361984 11.870558,14.952665 11.870558,14.952665"/>
                                <path class="even" d="m 68.349282,49.75 c 0,0 0.500124,3.82939 0.5625,5.8125 0.06238,1.98311 -0.1875,5.9375 -0.1875,5.9375"/>
                                <path class="odd" d="m 31.099282,65.625 c 0,0 1.764703,-4.224042 2,-7.375 0.235297,-3.150958 -1.943873,-9.276886 0.426777,-15.441942 2.370649,-6.165056 8.073223,-7.933058 8.073223,-7.933058"/>
                                <path class="odd" d="m 45.849282,33.625 c 0,0 12.805566,-1.968622 17,9.9375 4.194434,11.906122 1.125,24.0625 1.125,24.0625"/>
                                <path class="even" d="m 59.099282,70.25 c 0,0 0.870577,-2.956221 1.1875,-4.5625 0.316923,-1.606279 0.5625,-5.0625 0.5625,-5.0625"/>
                                <path class="even" d="m 60.901059,56.286612 c 0,0 0.903689,-9.415996 -3.801777,-14.849112 -3.03125,-3.5 -7.329245,-4.723939 -11.867187,-3.8125 -5.523438,1.109375 -7.570313,5.75 -7.570313,5.75"/>
                                <path class="even" d="m 34.072577,68.846248 c 0,0 2.274231,-4.165782 2.839205,-9.033748 0.443558,-3.821814 -0.49394,-5.649939 -0.714206,-8.05386 -0.220265,-2.403922 0.21421,-4.63364 0.21421,-4.63364"/>
                                <path class="odd" d="m 37.774165,70.831845 c 0,0 2.692139,-6.147592 3.223034,-11.251208 0.530895,-5.103616 -2.18372,-7.95562 -0.153491,-13.647655 2.030229,-5.692035 8.108442,-4.538898 8.108442,-4.538898"/>
                                <path class="odd" d="m 54.391174,71.715729 c 0,0 2.359472,-5.427681 2.519068,-16.175068 0.159595,-10.747388 -4.375223,-12.993087 -4.375223,-12.993087"/>
                                <path class="even" d="m 49.474282,73.625 c 0,0 3.730297,-8.451831 3.577665,-16.493718 -0.152632,-8.041887 -0.364805,-11.869326 -4.765165,-11.756282 -4.400364,0.113044 -3.875,4.875 -3.875,4.875"/>
                                <path class="even" d="m 41.132922,72.334447 c 0,0 2.49775,-5.267079 3.181981,-8.883029 0.68423,-3.61595 0.353553,-9.413359 0.353553,-9.413359"/>
                                <path class="odd" d="m 45.161782,73.75 c 0,0 1.534894,-3.679847 2.40625,-6.53125 0.871356,-2.851403 1.28125,-7.15625 1.28125,-7.15625"/>
                                <path class="odd" d="m 48.801947,56.125 c 0,0 0.234502,-1.809418 0.109835,-3.375 -0.124667,-1.565582 -0.5625,-3.1875 -0.5625,-3.1875"/>
                            </g>
                        </svg>

                        <svg class="ok" width="40" height="40" viewBox="0 0 100 100">
                            <path d="M34.912 50.75l10.89 10.125L67 36.75" fill="none" stroke="#fff" stroke-width="6"/>
                        </svg>
                        <svg class="cross" width="40" height="40" viewBox="0 0 100 100">
                            <path d="M30 30 L70 70 M70 30 L30 70" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

                <div class="form-divider"></div>

                <div class="form-footer-links">
                    <div class="form-footer-row">
                        <span class="txt1">Forgot</span>
                        <a class="txt2" href="forgotPassword.php">Username / Password?</a>
                    </div>

                    <div class="form-footer-row">
                        <a class="txt2" href="#" id="toggleForm">
                            Create your Account <i class="fa fa-long-arrow-right"></i>
                        </a>
                    </div>

                    <div class="form-footer-row">
                        <a href="#" class="admin-toggle" id="adminToggle">Login as Admin</a>
                    </div>
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

    // Form toggle between login and register
    document.addEventListener('DOMContentLoaded', function() {
        const toggleLink = document.getElementById('toggleForm');
        const form = document.querySelector('.login100-form');
        const formTitle = document.getElementById('formTitle');
        const btnText = document.getElementById('btnText');
        const formAction = document.getElementById('formAction');
        const loginType = document.getElementById('loginType');
        const adminToggle = document.getElementById('adminToggle');

        if (toggleLink) {
            toggleLink.addEventListener('click', function(e) {
                e.preventDefault();
                form.classList.toggle('register-mode');

                if (form.classList.contains('register-mode')) {
                    formTitle.textContent = 'Create Account';
                    btnText.textContent = 'REGISTER';
                    toggleLink.innerHTML = 'Already have an account? <i class="fa fa-long-arrow-left"></i>';
                    formAction.value = 'register';
                    adminToggle.style.display = 'none';
                } else {
                    formTitle.textContent = 'Customer Login';
                    btnText.textContent = 'LOGIN';
                    toggleLink.innerHTML = 'Create your Account <i class="fa fa-long-arrow-right"></i>';
                    formAction.value = 'login';
                    adminToggle.style.display = 'block';
                }
            });
        }

        // Admin toggle
        if (adminToggle) {
            adminToggle.addEventListener('click', function(e) {
                e.preventDefault();
                if (!form.classList.contains('register-mode')) {
                    if (loginType.value === 'customer') {
                        loginType.value = 'admin';
                        adminToggle.textContent = 'Login as Customer';
                        formTitle.textContent = 'Admin Login';
                    } else {
                        loginType.value = 'customer';
                        adminToggle.textContent = 'Login as Admin';
                        formTitle.textContent = 'Customer Login';
                    }
                }
            });
        }

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

        // Password match indicator
        const regPass = document.getElementById('regPassword');
        const confirmPass = document.getElementById('confirmPassword');
        
        if (regPass && confirmPass) {
            function checkMatch() {
                const wrap = confirmPass.closest('.input-wrap');
                if (confirmPass.value.length > 0) {
                    if (regPass.value === confirmPass.value) {
                        wrap.classList.remove('alert-mismatch');
                        confirmPass.style.borderColor = '#28a745';
                    } else {
                        confirmPass.style.borderColor = '#e05252';
                        wrap.classList.add('alert-mismatch');
                    }
                } else {
                    confirmPass.style.borderColor = '';
                    wrap.classList.remove('alert-mismatch');
                }
            }
            confirmPass.addEventListener('input', checkMatch);
            regPass.addEventListener('input', checkMatch);
        }
    });

    // Validation functions
    function validate(input) {
        if (!$(input).is(':visible')) return true;

        if ($(input).attr('type') === 'email' ||
            $(input).attr('name') === 'email' ||
            $(input).attr('name') === 'reg_email') {
            const emailRegex = /^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{1,5}|[0-9]{1,3})(\]?)$/;
            if (!$(input).val().trim().match(emailRegex)) return false;
        } else {
            if ($(input).val().trim() === '') return false;
        }
        return true;
    }

    function showValidate(input) {
        $(input).parent().addClass('alert-validate');
    }

    function hideValidate(input) {
        $(input).parent().removeClass('alert-validate');
    }

    function validateAllInputs() {
        const form = document.querySelector('.login100-form');
        const isRegisterMode = form.classList.contains('register-mode');
        let check = true;

        if (isRegisterMode) {
            const nameInput = $('#regFullname');
            const emailInput = $('#regEmail');
            const passInput = $('#regPassword');
            const confirmInput = $('#confirmPassword');

            if (!validate(nameInput[0])) { showValidate(nameInput[0]); check = false; }
            if (!validate(emailInput[0])) { showValidate(emailInput[0]); check = false; }
            if (!validate(passInput[0])) { showValidate(passInput[0]); check = false; }
            if (!validate(confirmInput[0])) { showValidate(confirmInput[0]); check = false; }

            if (passInput.val().trim() !== '' && confirmInput.val().trim() !== '' &&
                passInput.val().trim() !== confirmInput.val().trim()) {
                showValidate(confirmInput[0]);
                check = false;
            }
        } else {
            const emailInput = $('#loginEmail');
            const passInput = $('#loginPassword');
            if (!validate(emailInput[0])) { showValidate(emailInput[0]); check = false; }
            if (!validate(passInput[0])) { showValidate(passInput[0]); check = false; }
        }
        return check;
    }

    // Clear validation on input
    document.querySelectorAll('.input100').forEach(function(inp) {
        inp.addEventListener('input', function() {
            this.closest('.input-wrap').classList.remove('alert-validate');
            this.closest('.input-wrap').classList.remove('alert-mismatch');
            this.style.borderColor = '';
        });
    });

    // Submit button with fixed animation positioning
    $(document).ready(function() {
        const container = document.querySelector('.login-btn-container');
        if (!container) return;

        const ok = container.querySelector('.ok');
        const cross = container.querySelector('.cross');
        let pendingRedirect = null;
        let pendingAlert = null;

        if (ok) { ok.style.display = 'none'; ok.style.opacity = '0'; }
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

        container.addEventListener('animationend', function() {
            container.classList.remove('active');
            if (ok) { ok.style.display = 'none'; ok.style.opacity = '0'; }
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

        container.addEventListener('click', async function(e) {
            e.preventDefault();

            const form = document.querySelector('.login100-form');
            const isRegisterMode = form.classList.contains('register-mode');
            const isValid = validateAllInputs();

            if (!isValid) { showResult(false); return; }

            const formData = new FormData();

            if (isRegisterMode) {
                const fullname = $('#regFullname').val().trim();
                const email = $('#regEmail').val().trim();
                const password = $('#regPassword').val().trim();
                const confirmPassword = $('#confirmPassword').val().trim();

                if (password !== confirmPassword) {
                    showResult(false);
                    pendingAlert = 'Passwords do not match';
                    return;
                }

                formData.append('action', 'register');
                formData.append('fullname', fullname);
                formData.append('email', email);
                formData.append('password', password);
                formData.append('confirm_password', confirmPassword);

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
                const response = await fetch('loginPage.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });

                const result = await response.json();

                if (result.success) {
                    showResult(true);
                    pendingRedirect = result.redirect;
                } else {
                    showResult(false);
                    pendingAlert = result.message;
                }
            } catch (err) {
                console.error('Error:', err);
                showResult(false);
                pendingAlert = 'An error occurred. Please try again.';
            }
        });
    });

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
