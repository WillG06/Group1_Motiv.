<?php
session_start();
require_once 'db.php';

$form_message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $form_message = 'Please fill in all required fields.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {

        if ($conn) {
            $stmt = $conn->prepare("
                INSERT INTO contact_inquiries 
                (name, email, phone, subject, message, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'new', NOW())
            ");
            $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
            
            if ($stmt->execute()) {
                $form_message = 'Thank you for your message! We will get back to you within 24 hours.';
                $message_type = 'success';
                
                $_POST = array();
            } else {
                $form_message = 'Sorry, there was an error sending your message. Please try again.';
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $form_message = 'Thank you for your message! We will get back to you within 24 hours.';
            $message_type = 'success';
        }
    }
}

$basketCount = 0;
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['customer_id'] ?? $_SESSION['user']['id'] ?? null;
    $userRole = $_SESSION['user']['role'] ?? 'customer';
    
    if ($userRole === 'customer' && $userId) {
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contact-container {
            padding: 60px 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 80px);
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .contact-title {
            color: var(--vivid-indigo);
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .contact-subtitle {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .contact-info {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }
        
        .contact-info h2 {
            color: var(--vivid-indigo);
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        .contact-details {
            margin-bottom: 30px;
        }
        
        .contact-detail {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(to right, var(--cobalt-blue), var(--vivid-indigo));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .contact-icon i {
            color: white;
            font-size: 1.2rem;
        }
        
        .contact-text h4 {
            color: var(--vivid-indigo);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .contact-text p {
            color: #666;
            line-height: 1.5;
        }
        
        .business-hours {
            margin-top: 30px;
        }
        
        .business-hours h3 {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .hours-list {
            list-style: none;
        }
        
        .hours-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .hours-list li:last-child {
            border-bottom: none;
        }
        
        .day {
            color: #333;
            font-weight: 500;
        }
        
        .time {
            color: #666;
        }
        
        .contact-form-container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .contact-form-container h2 {
            color: var(--vivid-indigo);
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--vivid-indigo);
        }
        
        .form-group input,
        .form-group textarea,
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
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--cobalt-blue);
            outline: none;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
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
        
        .faq-section {
            margin-top: 80px;
            padding: 0 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .faq-title {
            text-align: center;
            color: var(--vivid-indigo);
            margin-bottom: 40px;
            font-size: 2.2rem;
        }
        
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .faq-item {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .faq-item h3 {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .faq-item p {
            color: #666;
            line-height: 1.6;
        }
        
        .map-section {
            margin-top: 80px;
            padding: 0 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .map-title {
            text-align: center;
            color: var(--vivid-indigo);
            margin-bottom: 40px;
            font-size: 2.2rem;
        }
        
        .map-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            height: 400px;
            background: #e9e9e9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 1.1rem;
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
        }

        .language-dropdown a {
            font-size: 15px !important;
            padding: 10px 14px !important;
        }

        .language-dropdown a:hover {
            background-color: #f1f1f1;
        }

        .language-selector i,
        .language-selector svg {
            display: block;
        }

        .language-selector a {
            font-size: 18px;     
            line-height: 0;       
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
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .contact-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .contact-info, .contact-form-container {
                padding: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .contact-container {
                padding: 40px 0;
            }
            
            .contact-title {
                font-size: 2rem;
            }
            
            .contact-info, .contact-form-container {
                padding: 25px;
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-detail {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .contact-icon {
                align-self: center;
            }
        }
    </style>
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
                    <li><a href="cars.php">Cars</a></li>
                    <li><a href="contact.php">Contact</a></li>

                    <?php if (!isset($_SESSION['user'])): ?>
                        <li><a href="register.php">Register</a></li>
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
                            <?php if ($basketCount > 0): ?>
                                <span class="basket-count"><?php echo $basketCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>



    <section class="contact-container">
        <div class="contact-header">
            <h1 class="contact-title">Get In Touch</h1>
            <p class="contact-subtitle">Have questions about our car rental services? We're here to help. Reach out to our friendly team.</p>
        </div>
        
        <div class="contact-content">
            <div class="contact-info">
                <h2>Contact Information</h2>
                
                <div class="contact-details">
                    <div class="contact-detail">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Our Location</h4>
                            <p>New Street Station, Birmingham B2 4QA, United Kingdom</p>
                        </div>
                    </div>
                    
                    <div class="contact-detail">
                        <div class="contact-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Phone Number</h4>
                            <p>+44 (0) 7123 456 789</p>
                        </div>
                    </div>
                    
                    <div class="contact-detail">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h4>Email Address</h4>
                            <p>info@motivcarrental.com</p>
                        </div>
                    </div>
                </div>
                
                <div class="business-hours">
                    <h3>Business Hours</h3>
                    <ul class="hours-list">
                        <li>
                            <span class="day">Monday - Friday</span>
                            <span class="time">8:00 AM - 8:00 PM</span>
                        </li>
                        <li>
                            <span class="day">Saturday</span>
                            <span class="time">9:00 AM - 6:00 PM</span>
                        </li>
                        <li>
                            <span class="day">Sunday</span>
                            <span class="time">10:00 AM - 4:00 PM</span>
                        </li>
                        <li>
                            <span class="day">Emergency Support</span>
                            <span class="time">24/7 Available</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="contact-form-container">
                <h2>Send Us a Message</h2>
                
                <?php if (!empty($form_message)): ?>
                <div id="formMessage" class="form-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($form_message); ?>
                </div>
                <?php else: ?>
                <div id="formMessage" class="form-message" style="display: none;"></div>
                <?php endif; ?>
                
                <form id="contactForm" method="POST" action="">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="general" <?php echo ($_POST['subject'] ?? '') === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="booking" <?php echo ($_POST['subject'] ?? '') === 'booking' ? 'selected' : ''; ?>>Booking Assistance</option>
                            <option value="support" <?php echo ($_POST['subject'] ?? '') === 'support' ? 'selected' : ''; ?>>Technical Support</option>
                            <option value="complaint" <?php echo ($_POST['subject'] ?? '') === 'complaint' ? 'selected' : ''; ?>>Complaint</option>
                            <option value="feedback" <?php echo ($_POST['subject'] ?? '') === 'feedback' ? 'selected' : ''; ?>>Feedback</option>
                            <option value="other" <?php echo ($_POST['subject'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message *</label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn">Send Message</button>
                </form>
            </div>
        </div>
        
        <div class="faq-section">
            <h2 class="faq-title">Frequently Asked Questions</h2>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <h3>What documents do I need to rent a car?</h3>
                    <p>You'll need a valid driver's license, a credit card in your name, and proof of identity (passport or ID card). For international renters, an International Driving Permit may be required.</p>
                </div>
                
                <div class="faq-item">
                    <h3>What is your cancellation policy?</h3>
                    <p>You can cancel your booking free of charge up to 24 hours before your scheduled pickup time. Cancellations made within 24 hours may incur a fee.</p>
                </div>
                
                <div class="faq-item">
                    <h3>Do you offer one-way rentals?</h3>
                    <p>Yes, we offer one-way rentals between most of our locations. Additional fees may apply depending on the drop-off location.</p>
                </div>
                
                <div class="faq-item">
                    <h3>What happens if I return the car late?</h3>
                    <p>We provide a 59-minute grace period. Returns after this period will incur additional daily charges. Please contact us if you anticipate being late.</p>
                </div>
                
                <div class="faq-item">
                    <h3>Do you offer additional insurance?</h3>
                    <p>Yes, we offer various insurance options including Collision Damage Waiver, Theft Protection, and Personal Accident Insurance for added peace of mind.</p>
                </div>
                
                <div class="faq-item">
                    <h3>Can I modify my booking after it's confirmed?</h3>
                    <p>Yes, you can modify your booking online or by contacting our customer service team. Changes are subject to vehicle availability and rate differences.</p>
                </div>
            </div>
        </div>
        
        <div class="map-section">
            <h2 class="map-title">Find Us</h2>
            <div class="map-container">

                <div style="text-align: center;">
                    <i class="fas fa-map" style="font-size: 3rem; margin-bottom: 15px; display: block; color: var(--vivid-indigo);"></i>
                    <p>Map</p>
                    <p>New Street Station, Birmingham</p>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>Motiv Car Rental</h3>
                    <p>Your trusted partner for car rental services in Birmingham and beyond.</p>
                </div>
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="landing.php">Home</a></li>
                        <li><a href="cars.php">Our Fleet</a></li>
                        <li><a href="contact.php">Contact</a></li>
                        <li><a href="#">Offers</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Contact Us</h3>
                    <ul>
                        <li>New Street Station, Birmingham</li>
                        <li>+44 (0) 7123 456 789</li>
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
            const contactForm = document.getElementById('contactForm');
            const formMessage = document.getElementById('formMessage');
            const submitBtn = document.getElementById('submitBtn');
            
            <?php if (empty($form_message)): ?>
            contactForm.addEventListener('submit', function(e) {
                // Client-side validation (additional to server-side)
                const name = document.getElementById('name').value;
                const email = document.getElementById('email').value;
                const subject = document.getElementById('subject').value;
                const message = document.getElementById('message').value;
                
                if (!name || !email || !subject || !message) {
                    e.preventDefault();
                    showMessage('Please fill in all required fields.', 'error');
                    return;
                }
                
                if (!isValidEmail(email)) {
                    e.preventDefault();
                    showMessage('Please enter a valid email address.', 'error');
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';
            });
            <?php endif; ?>
            
            function showMessage(text, type) {
                formMessage.textContent = text;
                formMessage.className = 'form-message ' + type;
                formMessage.style.display = 'block';
                
                formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                
                setTimeout(() => {
                    formMessage.style.display = 'none';
                }, 5000);
            }
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }

       
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
                // Highlight the current language in dropdown
                languageLinks.forEach(link => {
                    if (link.getAttribute('data-lang') === storedLang) {
                        link.style.fontWeight = 'bold';
                        link.style.backgroundColor = '#e9ecef';
                    }
                });
            }
        });
    </script>
</body>
</html>
<?php

if (isset($conn)) {
    $conn->close();
}

?>
