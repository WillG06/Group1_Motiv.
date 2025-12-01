<?php
session_start();
require_once 'db.php';

$basketCount = 0;
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer') {
    $userId = $_SESSION['user']['id'];
    
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
        $adminId = $stmt->insert_id;
        $stmt->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginType = $_POST['loginType'] ?? '';
    $email = $_POST['email'] ?? '';
    $memberId = $_POST['memberId'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $response = ['success' => false, 'message' => ''];
    
    try {
        if ($loginType === 'customer') {
            // Customer login
            $stmt = $conn->prepare("SELECT customer_id, first_name, last_name, email, password, phone, city_id FROM customers WHERE email = ?");
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
                        'user' => $_SESSION['user']
                    ];
                } else {
                    $response['message'] = 'Invalid password';
                }
            } else {
                $response['message'] = 'Customer not found';
            }
            $stmt->close();
            
        } elseif ($loginType === 'admin') {
            
            $stmt = $conn->prepare("SELECT agent_id, first_name, last_name, email, password, phone, city_id FROM agents WHERE agent_id = ?");
            $stmt->bind_param("s", $memberId);
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
                        'user' => $_SESSION['user']
                    ];
                } else {
                    $response['message'] = 'Invalid password';
                }
            } else {
                $response['message'] = 'Admin not found';
            }
            $stmt->close();
        } else {
            $response['message'] = 'Invalid login type';
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
        if ($_SESSION['user']['role'] === 'customer') {
            header('Location: customer-dashboard.php');
        } else {
            header('Location: admin-dashboard.php');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Login - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css" />
<style>
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
}

.language-selector:hover > a {
    background-color: rgba(255, 255, 255, 0.1);
}

.language-dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 100%;
    min-width: 160px;
    background-color: white;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
    z-index: 1000;
}

.language-selector:hover .language-dropdown {
    display: block;
}

.language-dropdown a {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    color: #333;
    text-decoration: none;
    transition: background-color 0.3s;
    font-size: 15px !important;
}

.language-dropdown a:hover {
    background-color: #f1f1f1;
}

.language-selector a {
    font-size: 18px;     
    line-height: 0;       
}

.basket-count:empty {
    display: none;
}

.login-container {
    padding: 60px 0;
    background: #f5f5f5;
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: start;
    justify-content: center;
}

.login-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    align-items: start;
}

.login-info {
    padding: 20px;
}

.login-title {
    color: var(--vivid-indigo);
    font-size: 2.5rem;
    margin-bottom: 20px;
    line-height: 1.2;
}

.login-subtitle {
    color: #666;
    font-size: 1.1rem;
    margin-bottom: 30px;
    line-height: 1.6;
}

.login-form-container {
    background: #fff;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    max-width: 500px;
    width: 100%;
}

.login-type-toggle {
    display: flex;
    background: #f0f0f0;
    border-radius: 8px;
    padding: 5px;
    margin-bottom: 25px;
}

.toggle-option {
    flex: 1;
    padding: 12px;
    text-align: center;
    cursor: pointer;
    border-radius: 6px;
    font-weight: 600;
    transition: all 0.3s;
}

.toggle-option.active {
    background: #fff;
    color: var(--vivid-indigo);
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--vivid-indigo);
}

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s;
}

.form-group input:focus {
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

.remember-forgot {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
}

.remember-me input {
    width: 16px;
    height: 16px;
}

.remember-me label {
    font-weight: normal;
    color: #666;
    font-size: 0.9rem;
    margin: 0;
}

.forgot-password {
    color: var(--cobalt-blue);
    text-decoration: none;
    font-size: 0.9rem;
}

.submit-btn {
    background: linear-gradient(90deg, var(--cobalt-blue), var(--vivid-indigo));
    color: #fff;
    border: none;
    padding: 14px 30px;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.2s, box-shadow 0.2s;
    width: 100%;
    box-shadow: 0 4px 8px rgba(0,74,173,0.4);
    margin-bottom: 20px;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,74,173,0.5);
}

.submit-btn:disabled {
    background: #cccccc;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

.register-link {
    text-align: center;
    color: #666;
}

.register-link a {
    color: var(--cobalt-blue);
    text-decoration: none;
    font-weight: 600;
}

.form-message {
    display: none;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
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

.admin-info {
    background: #e3f2fd;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
    border-left: 4px solid var(--cobalt-blue);
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

.demo-credentials {
    background: #f8f8f8;
    border-radius: 8px;
    padding: 20px;
    margin-top: 25px;
    border: 1px dashed #ddd;
}

.demo-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--vivid-indigo);
    margin-bottom: 10px;
}

.demo-account {
    margin-bottom: 15px;
}

.demo-account h4 {
    font-size: 0.85rem;
    color: #333;
    margin: 0 0 5px 0;
}

.demo-account p {
    font-size: 0.8rem;
    color: #666;
    margin: 0;
    line-height: 1.4;
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

.basket-count:empty {
    display: none;
}

@media (max-width: 992px) {
    .login-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    .login-info {
        text-align: center;
        padding: 0 20px;
    }
    .login-form-container {
        margin: 0 auto;
    }
}

@media (max-width: 768px) {
    .login-container {
        padding: 40px 0;
    }
    .login-title {
        font-size: 2rem;
    }
    .login-form-container {
        padding: 30px 25px;
    }
    .remember-forgot {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
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
                <li><a href="landing.php">Home</a></li>
                <li><a href="cars.php">Cars</a></li>
                <li><a href="contact.php">Contact</a></li>

                <?php if (!isset($_SESSION['user'])): ?>
                    <li><a href="register.php">Register</a></li>
                    <li><a href="loginPage.php" class="active">Login</a></li>
                <?php else: ?>
                    <li>
                        <a href="<?php echo $_SESSION['user']['role'] === 'admin' ? 'admin-dashboard.php' : 'customer-dashboard.php'; ?>">
                            Dashboard
                        </a>
                    </li>
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
                        <?php if ($basketCount > 0): ?>
                            <span class="basket-count"><?php echo $basketCount; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>

    <section class="login-container">
        <div class="login-content">
            <div class="login-info">
                <h1 class="login-title">Welcome Back to Motiv Car Hire</h1>
                <p class="login-subtitle">Sign in to your account to access your rentals, manage bookings, and enjoy member-exclusive benefits.</p>
                
                <ul class="features-list" style="list-style:none;margin-top:30px">
                    <li style="display:flex;align-items:center;gap:15px;margin-bottom:20px;color:#555">
                        <i class="fas fa-history" style="color:var(--cobalt-blue);font-size:1.2rem;width:24px;text-align:center"></i>
                        <span>Access your complete rental history</span>
                    </li>
                    <li style="display:flex;align-items:center;gap:15px;margin-bottom:20px;color:#555">
                        <i class="fas fa-heart" style="color:var(--cobalt-blue);font-size:1.2rem;width:24px;text-align:center"></i>
                        <span>Manage your favorite vehicles</span>
                    </li>
                    <li style="display:flex;align-items:center;gap:15px;margin-bottom:20px;color:#555">
                        <i class="fas fa-bolt" style="color:var(--cobalt-blue);font-size:1.2rem;width:24px;text-align:center"></i>
                        <span>Faster booking with saved preferences</span>
                    </li>
                    <li style="display:flex;align-items:center;gap:15px;margin-bottom:20px;color:#555">
                        <i class="fas fa-percentage" style="color:var(--cobalt-blue);font-size:1.2rem;width:24px;text-align:center"></i>
                        <span>Exclusive member discounts and offers</span>
                    </li>
                </ul>
            </div>

            <div class="login-form-container">
                <div class="form-header" style="text-align:center;margin-bottom:30px">
                    <h2 style="color:var(--vivid-indigo);font-size:1.8rem;margin-bottom:10px">Sign In to Your Account</h2>
                    <p style="color:#666;font-size:1rem">Enter your credentials to continue</p>
                </div>

                <div class="login-type-toggle">
                    <div class="toggle-option active" data-type="customer">Customer Login</div>
                    <div class="toggle-option" data-type="admin">Admin Login</div>
                </div>

                <div id="formMessage" class="form-message">
                    <?php if (isset($response) && !$response['success'] && !empty($response['message'])): ?>
                        <div class="form-message error"><?php echo htmlspecialchars($response['message']); ?></div>
                    <?php endif; ?>
                </div>

                <form id="loginForm" method="POST" novalidate>
                    <input type="hidden" name="loginType" id="loginType" value="customer">
                    
                    <div class="form-group" id="emailGroup">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group" id="memberIdGroup" style="display:none">
                        <label for="memberId">Member ID</label>
                        <input type="text" id="memberId" name="memberId" value="<?php echo htmlspecialchars($_POST['memberId'] ?? ''); ?>" placeholder="Enter your admin ID">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-password" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="remember-forgot">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                            <label for="remember">Remember me</label>
                        </div>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <div class="admin-info" id="adminInfo" style="display:none">
                        <h4>Admin Access</h4>
                        <p>Use your admin member ID and password to access the admin dashboard.</p>
                    </div>

                    <button type="submit" class="submit-btn" id="submitBtn">Sign In</button>

                    <div class="register-link">
                        Don't have an account? <a href="register.php">Create one here</a>
                    </div>
                </form>

                <div class="demo-credentials">
                    <div class="demo-title">Demo Credentials (for testing):</div>
                    
                    <div class="demo-account">
                        <h4>Customer Account:</h4>
                        <p>Email: customer@demo.com<br>Password: demo123</p>
                    </div>
                    
                    <div class="demo-account">
                        <h4>Admin Account:</h4>
                        <p>Member ID: 1 (check agents table for actual ID)<br>Password: admin123</p>
                    </div>
                </div>
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
        (function () {
            const loginForm = document.getElementById('loginForm');
            const msg = document.getElementById('formMessage');
            const toggleOptions = document.querySelectorAll('.toggle-option');
            const emailGroup = document.getElementById('emailGroup');
            const memberIdGroup = document.getElementById('memberIdGroup');
            const adminInfo = document.getElementById('adminInfo');
            const loginTypeInput = document.getElementById('loginType');
            const togglePassword = document.getElementById('togglePassword');
            const submitBtn = document.getElementById('submitBtn');

            toggleOptions.forEach(opt => {
                opt.addEventListener('click', () => {
                    toggleOptions.forEach(o => o.classList.remove('active'));
                    opt.classList.add('active');
                    const loginType = opt.getAttribute('data-type');
                    loginTypeInput.value = loginType;
                    
                    if (loginType === 'admin') {
                        emailGroup.style.display = 'none';
                        memberIdGroup.style.display = 'block';
                        adminInfo.style.display = 'block';
                        document.querySelector('.form-header h2').textContent = 'Admin Sign In';
                    } else {
                        emailGroup.style.display = 'block';
                        memberIdGroup.style.display = 'none';
                        adminInfo.style.display = 'none';
                        document.querySelector('.form-header h2').textContent = 'Sign In to Your Account';
                    }
                    hideMessage();
                });
            });

            togglePassword.addEventListener('click', () => {
                const pw = document.getElementById('password');
                const icon = togglePassword.querySelector('i');
                if (pw.type === 'password') {
                    pw.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    pw.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });

            function showMessage(text, type = 'error') {
                msg.innerHTML = '<div class="form-message ' + (type === 'success' ? 'success' : 'error') + '">' + text + '</div>';
                msg.style.display = 'block';
            }

            function hideMessage() {
                msg.style.display = 'none';
            }

            function validateEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }

            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                hideMessage();

                const loginType = loginTypeInput.value;
                const password = document.getElementById('password').value.trim();
                const formData = new FormData(loginForm);

                if (loginType === 'customer') {
                    const email = document.getElementById('email').value.trim();
                    if (!email || !password) {
                        showMessage('Please enter email and password.');
                        return;
                    }
                    if (!validateEmail(email)) {
                        showMessage('Please enter a valid email address.');
                        return;
                    }
                } else {
                    const memberId = document.getElementById('memberId').value.trim();
                    if (!memberId || !password) {
                        showMessage('Please enter member ID and password.');
                        return;
                    }
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Signing In...';

                try {
                    const response = await fetch('loginPage.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    const result = await response.json();

                    if (result.success) {
                        showMessage(result.message + ' — Redirecting...', 'success');
                        setTimeout(() => {
                            if (result.user.role === 'customer') {
                                window.location.href = 'customer-dashboard.php';
                            } else {
                                window.location.href = 'admin-dashboard.php';
                            }
                        }, 1000);
                    } else {
                        showMessage(result.message);
                    }
                } catch (error) {
                    console.error('Login error:', error);
                    
                    loginForm.removeEventListener('submit', arguments.callee);
                    loginForm.submit();
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Sign In';
                }
            });

            const languageLinks = document.querySelectorAll('.language-dropdown a');
            languageLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const selectedLang = this.getAttribute('data-lang');
                    localStorage.setItem('selectedLanguage', selectedLang);
                    alert(`Language changed to: ${this.textContent.trim()}`);
                });
            });

            const storedLang = localStorage.getItem('selectedLanguage');
            if (storedLang) {
                languageLinks.forEach(link => {
                    if (link.getAttribute('data-lang') === storedLang) {
                        link.style.fontWeight = 'bold';
                        link.style.backgroundColor = '#e9ecef';
                    }
                });
            }

            setTimeout(() => {
                hideMessage();
            }, 5000);

        })();
    </script>
</body>
</html>