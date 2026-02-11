<?php
session_start();
require_once 'db.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <style>
        .about-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                        url('bg1.jpg') center/cover no-repeat;
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .about-hero h1 {
            font-size: 3rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .about-hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.9;
        }
        
        .story-section {
            padding: 60px 0;
            background-color: white;
        }
        
        .story-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        
        .story-text h2 {
            color: var(--vivid-indigo);
            font-size: 2.2rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 12px;
        }
        
        .story-text h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 70px;
            height: 3px;
            background: var(--coral-red);
        }
        
        .story-text p {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 20px;
            color: #555;
        }
        
        .story-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
            text-align: center;
        }
        
        .stat-item {
            padding: 15px;
            background: var(--light-gray);
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-3px);
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--vivid-indigo);
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 600;
        }
        
        .story-image img {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }
        
        .values-section {
            padding: 40px 0 60px;
            background-color: white;
        }
        
        .section-title {
            text-align: center;
            color: var(--vivid-indigo);
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .section-subtitle {
            text-align: center;
            color: #666;
            font-size: 1rem;
            margin-bottom: 40px;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .value-card {
            background: white;
            padding: 30px 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #f0f0f0;
        }
        
        .value-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
        }
        
        .value-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--vivid-indigo), var(--cobalt-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .value-icon i {
            font-size: 1.8rem;
            color: white;
        }
        
        .value-card h3 {
            color: var(--vivid-indigo);
            margin-bottom: 12px;
            font-size: 1.3rem;
        }
        
        .value-card p {
            color: #666;
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .team-section {
            padding: 60px 0;
            background-color: var(--light-gray);
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .team-member {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-8px);
        }
        
        .member-image {
            height: 200px;
            overflow: hidden;
        }
        
        .member-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .team-member:hover .member-image img {
            transform: scale(1.1);
        }
        
        .member-info {
            padding: 20px;
            text-align: center;
        }
        
        .member-info h4 {
            color: var(--vivid-indigo);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .member-info .position {
            color: var(--coral-red);
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            font-size: 0.9rem;
        }
        
        .member-info p {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .slider-section {
            padding: 60px 0;
            background-color: white;
        }
        
        .slider-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .swiper {
            width: 100%;
            height: 450px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .swiper-slide {
            position: relative;
            overflow: hidden;
        }
        
        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .swiper-slide:hover img {
            transform: scale(1.05);
        }
        
        .slide-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 25px;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .swiper-slide:hover .slide-content {
            transform: translateY(0);
        }
        
        .slide-content h3 {
            font-size: 1.6rem;
            margin-bottom: 8px;
            color: white;
        }
        
        .slide-content p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        @media (max-width: 992px) {
            .story-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .story-image {
                order: -1;
            }
            
            .about-hero h1 {
                font-size: 2.5rem;
            }
            
            .swiper {
                height: 350px;
            }
            
            .story-image img {
                height: 300px;
            }
        }
        
        @media (max-width: 768px) {
            .about-hero {
                padding: 60px 0;
            }
            
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .about-hero p {
                font-size: 1rem;
                padding: 0 15px;
            }
            
            .story-stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            
            .swiper {
                height: 280px;
            }
            
            .slide-content {
                padding: 15px;
            }
            
            .slide-content h3 {
                font-size: 1.2rem;
            }
            
            .story-text h2 {
                font-size: 1.8rem;
            }
            
            .story-image img {
                height: 250px;
            }
        }
        
        @media (max-width: 576px) {
            .story-stats {
                grid-template-columns: 1fr;
                max-width: 300px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .swiper {
                height: 220px;
            }
            
            .value-card {
                padding: 25px 20px;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
                max-width: 300px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .values-grid {
                grid-template-columns: 1fr;
                max-width: 300px;
                margin-left: auto;
                margin-right: auto;
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
                        <a href="about.php" class="active">About</a>
                    </div>
                </li>
                <li><a href="cars.php">Cars</a></li>
                <li><a href="contact.php">Contact</a></li>

                <?php if (!isset($_SESSION['user'])): ?>
                    
                    <li><a href="loginPage.php">Login</a></li>
                <?php else: ?>
                    <li><a href="customer-dashboard.php">Dashboard</a></li>
                    <li>
                        <a href="logout.php">
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

<section class="about-hero">
    <div class="container">
        <h1>About Motiv Car Hire</h1>
        <p>Your trusted partner for premium car rental services in Birmingham and across the UK</p>
    </div>
</section>

<section class="story-section">
    <div class="container">
        <div class="story-grid">
            <div class="story-text">
                <h2>Our Story</h2>
                <p>Founded in Birmingham, Motiv Car Hire began as a small business in Birmingham with a vision to make car rental accessible and enjoyable for everyone. We've grown into one of the UK's trusted car rental companies.</p>
                
                <p>Our commitment to customer satisfaction, transparent pricing, and maintaining a modern, reliable fleet has earned us the trust of thousands nationwide. We believe the right vehicle makes every journey memorable.</p>
                
                <div class="story-stats">
                    <div class="stat-item">
                        <span class="stat-number">5,000+</span>
                        <span class="stat-label">Happy Customers</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">200+</span>
                        <span class="stat-label">Vehicles</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">5+</span>
                        <span class="stat-label">UK Cities</span>
                    </div>
                </div>
            </div>
            
            <div class="story-image">
                <img src="car_pics/car1.png" alt="Premium Fleet">
            </div>
        </div>
    </div>
</section>

<section class="values-section">
    <div class="container">
        <h2 class="section-title">Our Values</h2>
        <p class="section-subtitle">The principles that guide everything we do</p>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3>Trust & Reliability</h3>
                <p>We build lasting relationships based on trust, delivering reliable services and maintaining transparent communication.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3>Excellence</h3>
                <p>We strive for excellence in every aspect, from vehicle maintenance to customer support and booking experience.</p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Customer First</h3>
                <p>Our customers are at the heart of everything we do. We listen, adapt, and go the extra mile for complete satisfaction.</p>
            </div>
        </div>
    </div>
</section>

<section class="team-section">
    <div class="container">
        <h2 class="section-title">Meet Our Leadership Team</h2>
        <p class="section-subtitle">The passionate individuals behind Motiv Car Hire</p>
        
        <div class="team-grid">
            <div class="team-member">
                <div class="member-info">
                    <h4>Zahra Ali Jaffer</h4>
                    <span class="position">Chief Operating Officer</span>
                    <p>Leads company-wide operations with focus on efficiency, service quality, and team performance.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Olivia Evans-Simms</h4>
                    <span class="position">Chief Fleet Officer</span>
                    <p>Oversees fleet strategy, ensuring vehicles are well-maintained, safe, and aligned with business needs.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>William Giles</h4>
                    <span class="position">Regional Director</span>
                    <p>Manages regional performance and supports branches in delivering consistent customer satisfaction.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Aaron Boadu</h4>
                    <span class="position">Business Manager</span>
                    <p>Drives business operations, ensuring smooth workflows and optimized commercial processes.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Aisha Bashir</h4>
                    <span class="position">Digital Marketing Manager</span>
                    <p>Develops and executes brand-focused digital strategies to grow visibility and customer engagement.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Ibrahim Al-Mohannadi</h4>
                    <span class="position">Accounts Manager</span>
                    <p>Ensures financial accuracy and oversees client billing, revenue tracking, and account coordination.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Yunus Masood</h4>
                    <span class="position">Rental Operations Supervisor</span>
                    <p>Coordinates daily rental operations, ensuring customers receive fast and seamless service.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Omar Abou Jalil</h4>
                    <span class="position">Corporate Manager</span>
                    <p>Leads corporate client relations, supporting strategic partnerships and tailored mobility solutions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="slider-section">
    <div class="slider-container">
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <img src="car_pics/car1.png" alt="Premium Fleet">
                    <div class="slide-content">
                        <h3>Premium Vehicle Fleet</h3>
                        <p>Discover our wide range of quality vehicles from economy to luxury</p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <img src="car_pics/car2.png" alt="Customer Service">
                    <div class="slide-content">
                        <h3>Excellent Customer Service</h3>
                        <p>Our dedicated team is available 24/7 to assist you</p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <img src="car_pics/car3.jpg" alt="Multiple Locations">
                    <div class="slide-content">
                        <h3>Convenient Locations</h3>
                        <p>Pick-up and drop-off points across major cities</p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <img src="car_pics/car4.png" alt="Eco Friendly">
                    <div class="slide-content">
                        <h3>Eco-Friendly Options</h3>
                        <p>Choose from our range of electric and hybrid vehicles</p>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
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
                    <li><a href="about.php">About Us</a></li>
                    <li><a href="cars.php">Our Fleet</a></li>
                    <li><a href="contact.php">Contact</a></li>
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

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    const swiper = new Swiper('.swiper', {
        direction: 'horizontal',
        loop: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: false,
        },
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        effect: 'fade',
        fadeEffect: {
            crossFade: true
        },
        speed: 1000,
    });
    
    const swiperContainer = document.querySelector('.swiper');
    swiperContainer.addEventListener('mouseenter', function() {
        swiper.autoplay.stop();
    });
    
    swiperContainer.addEventListener('mouseleave', function() {
        swiper.autoplay.start();
    });
    
    document.addEventListener('DOMContentLoaded', function() {
        const currentYear = new Date().getFullYear();
        const yearElements = document.querySelectorAll('.copyright p');
        yearElements.forEach(el => {
            el.innerHTML = el.innerHTML.replace('2025', currentYear);
        });
    });
</script>

</body>
</html>
<?php
$conn->close();
?>