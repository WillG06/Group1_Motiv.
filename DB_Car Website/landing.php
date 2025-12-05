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

$cities = [];
$cityQuery = $conn->query("SELECT city_id, city_name FROM cities ORDER BY city_name");
if ($cityQuery->num_rows > 0) {
    while ($city = $cityQuery->fetch_assoc()) {
        $cities[] = $city;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_cars'])) {
    $pickupLocation = $_POST['pickup_location'] ?? '';
    $pickupDate = $_POST['pickup_date'] ?? '';
    $pickupTime = $_POST['pickup_time'] ?? '';
    $dropoffDate = $_POST['dropoff_date'] ?? '';
    $dropoffTime = $_POST['dropoff_time'] ?? '';
    
    $_SESSION['search_criteria'] = [
        'pickup_location' => $pickupLocation,
        'pickup_date' => $pickupDate,
        'pickup_time' => $pickupTime,
        'dropoff_date' => $dropoffDate,
        'dropoff_time' => $dropoffTime
    ];
    
    header('Location: cars.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Motiv Car Hire - Birmingham</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

.best-selling-section {
    padding: 15px 0;
    background-color: var(--white);
}

.best-selling-section .section-title {
    color: var(--vivid-indigo);
    margin-bottom: 15px;
    font-size: 2.2rem;
}

.section-subtitle {
    text-align: center;
    margin-bottom: 40px;
    color: #666;
    font-size: 1.1rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.services-scroll-container {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    overflow: hidden;
}

.services-scroll {
    display: flex;
    gap: 25px;
    overflow-x: auto;
    scroll-behavior: smooth;
    padding: 10px 0 30px;
    scrollbar-width: none; 
    -ms-overflow-style: none; 
}

.services-scroll::-webkit-scrollbar {
    display: none; 
}

.service-card {
    flex: 0 0 320px;
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    display: flex;
    flex-direction: column;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.service-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.service-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.service-card:hover .service-image img {
    transform: scale(1.05);
}

.service-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: var(--coral-red);
    color: white;
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.service-content {
    padding: 20px;
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.service-content h3 {
    color: var(--vivid-indigo);
    margin-bottom: 10px;
    font-size: 1.3rem;
}

.service-rating {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.stars {
    color: #FFC107;
    font-size: 1.1rem;
    margin-right: 8px;
}

.rating-text {
    color: #666;
    font-size: 0.9rem;
}

.service-description {
    margin-bottom: 10px;
    color: #555;
    line-height: 1.5;
    flex-grow: 0;
}

.service-details {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 15px;
    padding: 10px 0;
    border-top: 1px solid #eee;
    border-bottom: 1px solid #eee;
    min-height: 70px;
    align-items: center;
    flex-grow: 0;
}

.detail-item {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 80px;
}

.detail-label {
    font-size: 0.8rem;
    color: #888;
    margin-bottom: 3px;
}

.detail-value {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--vivid-indigo);
}

.testimonial {
    background: #f9f9f9;
    padding: 12px;
    border-radius: 8px;
    margin-top: 10px;
}

.testimonial p {
    font-style: italic;
    color: #555;
    margin-bottom: 8px;
    font-size: 0.9rem;
    line-height: 1.4;
}

.testimonial-author {
    font-size: 0.85rem;
    color: #777;
    text-align: right;
}

.scroll-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: var(--vivid-indigo);
    color: white;
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    font-size: 1.2rem;
    cursor: pointer;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    transition: background 0.3s, transform 0.2s;
}

.scroll-btn:hover {
    background: var(--dark-magenta);
    transform: translateY(-50%) scale(1.1);
}

.scroll-left {
    left: 10px;
}

.scroll-right {
    right: 10px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 20px;
}

.view-all-btn {
    background: linear-gradient(to right, var(--cobalt-blue), var(--vivid-indigo));
    color: white;
    text-decoration: none;
    padding: 12px 25px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
    box-shadow: 0 4px 8px rgba(0, 71, 171, 0.4);
    white-space: nowrap;
}

.view-all-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 71, 171, 0.5);
    color: white;
}

@media (max-width: 768px) {
    .section-header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .section-header .section-title {
        margin-bottom: 0;
    }
    
    .view-all-btn {
        margin-top: 10px;
    }
}

@media (max-width: 768px) {
    .service-card {
        flex: 0 0 280px;
    }
    
    .scroll-btn {
        display: none; 
    }
    
    .services-scroll {
        padding-bottom: 20px;
    }
}

@media (max-width: 992px) {
    .hero-content {
        flex-direction: column;
        text-align: center;
    }
    
    .hero-text {
        margin-bottom: 40px;
    }
    
    .booking-form {
        width: 100%;
        max-width: 500px;
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
                    <li><a href="cars.php" class="active">Cars</a></li>
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



    <section class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <h1>Motiv, Car Rental</h1>
                <p>At Motiv, we make car hire enjoyable! With flexible pick-up options, a variety of quality vehicles, and smooth booking, every journey feels effortless.</p>
            </div>
            <div class="booking-form">
                <h2>Reserve a Vehicle</h2>
                <form id="bookingForm" method="POST">
                    <input type="hidden" name="search_cars" value="1">
                    
                    <div class="form-group">
                        <label for="pickup-location">Pick-up Location</label>
                        <select id="pickup-location" name="pickup_location" required>
                            <option value="">Select a location</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['city_id']; ?>">
                                    <?php echo htmlspecialchars($city['city_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Pick-up Date & Time</label>
                        <div class="date-time-group">
                            <div>
                                <input type="date" id="pickup-date" name="pickup_date" required>
                            </div>
                            <div>
                                <input type="time" id="pickup-time" name="pickup_time" value="12:00" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Drop-off Date & Time</label>
                        <div class="date-time-group">
                            <div>
                                <input type="date" id="dropoff-date" name="dropoff_date" required>
                            </div>
                            <div>
                                <input type="time" id="dropoff-time" name="dropoff_time" value="12:00" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="book-btn">Show Available Cars</button>
                </form>
            </div>
        </div>
    </section>

<section class="features">
    <div class="container">
        <h2 class="section-title">Why Choose Motiv?</h2>
        <div class="features-grid">
            
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="cars1.png" alt="Vehicle Selection">
                </div>
                <h3>Wide Vehicle Selection</h3>
                <p>Choose from economy cars, premium sedans, SUVs, and electric vehicles to suit your needs.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="location1.png" alt="Convenient Locations">
                </div>
                <h3>Convenient Locations</h3>
                <p>Multiple pickup and drop-off locations across Birmingham for your convenience.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="money1.png" alt="Best Price Guarantee">
                </div>
                <h3>Best Price Guarantee</h3>
                <p>We offer competitive rates with no hidden fees and a best price guarantee.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="cust1.png" alt="Customer Support">
                </div>
                <h3>24/7 Support</h3>
                <p>Our customer service team is available around the clock to assist you.</p>
            </div>
        </div>
    </div>
</section>

<section class="cities-section">
    <h2 class="section-title">Top Cities for Car Hire</h2>

    <div class="city-grid">
        <?php
        $cities = [
            ['name' => 'Birmingham', 'image' => 'city1.jpg'],
            ['name' => 'London', 'image' => 'city2.png'],
            ['name' => 'Liverpool', 'image' => 'city3.jpeg'],
            ['name' => 'Manchester', 'image' => 'city4.jpg'],
            ['name' => 'Sheffield', 'image' => 'city5.jpg']
        ];
        
        foreach ($cities as $city) {
            $imagePath = $city['image'];
            
            if (!file_exists($imagePath)) {
                $imagePath = 'city_default.jpg';
            }
            
            echo '
            <div class="city-card">
                <img src="' . $imagePath . '" alt="' . $city['name'] . '">
                <div class="city-name">' . $city['name'] . '</div>
            </div>';
        }
        ?>
    </div>
</section>


<section class="best-selling-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Best Selling Services</h2>
            <a href="cars.php" class="view-all-btn">View All Listings</a>
        </div>
        <p class="section-subtitle">Our most popular rental options with top customer ratings</p>
        
        <div class="services-scroll-container">
            <div class="services-scroll">

                <div class="service-card">
                    <div class="service-image">
                        <img src="car_pics/car1.png" alt="Premium SUV">
                        <div class="service-badge">Most Popular</div>
                    </div>
                    <div class="service-content">
                        <h3>Premium SUV</h3>
                        <div class="service-rating">
                            <span class="stars">★★★★★</span>
                            <span class="rating-text">5/5 (128 reviews)</span>
                        </div>
                        <p class="service-description">Spacious and comfortable SUVs perfect for family trips or group travel.</p>
                        <div class="service-details">
                            <div class="detail-item">
                                <span class="detail-label">Seats:</span>
                                <span class="detail-value">5-7</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Luggage:</span>
                                <span class="detail-value">4-6 bags</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fuel Type:</span>
                                <span class="detail-value">Petrol/Diesel</span>
                            </div>
                        </div>
                        <div class="testimonial">
                            <p>"The SUV was perfect for our family vacation. Plenty of space and very comfortable!"</p>
                            <div class="testimonial-author">- Zahra A.</div>
                        </div>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-image">
                        <img src="car_pics/car2.png" alt="Economy Car">
                        <div class="service-badge">Best Value</div>
                    </div>
                    <div class="service-content">
                        <h3>Economy Car</h3>
                        <div class="service-rating">
                            <span class="stars">★★★★☆</span>
                            <span class="rating-text">4.7/5 (95 reviews)</span>
                        </div>
                        <p class="service-description">Fuel-efficient and affordable cars ideal for city driving and short trips.</p>
                        <div class="service-details">
                            <div class="detail-item">
                                <span class="detail-label">Seats:</span>
                                <span class="detail-value">4-5</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Luggage:</span>
                                <span class="detail-value">2-3 bags</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fuel Type:</span>
                                <span class="detail-value">Petrol</span>
                            </div>
                        </div>
                        <div class="testimonial">
                            <p>"Great value for money! The car was clean, efficient, and perfect for getting around the city."</p>
                            <div class="testimonial-author">- Olivia E.S.</div>
                        </div>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-image">
                        <img src="car_pics/car3.jpg" alt="Luxury Sedan">
                    </div>
                    <div class="service-content">
                        <h3>Luxury Sedan</h3>
                        <div class="service-rating">
                            <span class="stars">★★★★☆</span>
                            <span class="rating-text">4.8/5 (67 reviews)</span>
                        </div>
                        <p class="service-description">Premium vehicles for business trips or special occasions with comfort.</p>
                        <div class="service-details">
                            <div class="detail-item">
                                <span class="detail-label">Seats:</span>
                                <span class="detail-value">4-5</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Luggage:</span>
                                <span class="detail-value">3-4 bags</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fuel Type:</span>
                                <span class="detail-value">Petrol/Hybrid</span>
                            </div>
                        </div>
                        <div class="testimonial">
                            <p>"The luxury sedan made our anniversary trip extra special. Smooth ride and excellent service!"</p>
                            <div class="testimonial-author">- Will</div>
                        </div>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-image">
                        <img src="car_pics/car4.png" alt="Electric">
                        <div class="service-badge">Eco-Friendly</div>
                    </div>
                    <div class="service-content">
                        <h3>Electric Vehicle</h3>
                        <div class="service-rating">
                            <span class="stars">★★★★☆</span>
                            <span class="rating-text">4.6/5 (52 reviews)</span>
                        </div>
                        <p class="service-description">Environmentally friendly electric cars with modern features and operations.</p>
                        <div class="service-details">
                            <div class="detail-item">
                                <span class="detail-label">Seats:</span>
                                <span class="detail-value">4-5</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Luggage:</span>
                                <span class="detail-value">2-3 bags</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Fuel Type:</span>
                                <span class="detail-value">Petrol</span>
                            </div>
                        </div>
                        <div class="testimonial">
                            <p>"My first EV experience was fantastic! The car was quiet, smooth, and charging was convenient."</p>
                            <div class="testimonial-author">- Aaron</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <button class="scroll-btn scroll-left" aria-label="Scroll left">&#8249;</button>
            <button class="scroll-btn scroll-right" aria-label="Scroll right">&#8250;</button>
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

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('pickup-date').setAttribute('min', today);
        document.getElementById('dropoff-date').setAttribute('min', today);
        document.getElementById('pickup-date').addEventListener('change', function() {
            document.getElementById('dropoff-date').setAttribute('min', this.value);
        });
        
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const pickupLocation = document.getElementById('pickup-location').value;
            const pickupDate = document.getElementById('pickup-date').value;
            const pickupTime = document.getElementById('pickup-time').value;
            const dropoffDate = document.getElementById('dropoff-date').value;
            const dropoffTime = document.getElementById('dropoff-time').value;
            
            if (!pickupLocation || !pickupDate || !pickupTime || !dropoffDate || !dropoffTime) {
                e.preventDefault();
                alert('Please fill in all fields');
                return;
            }
            
            const pickupDateTime = new Date(pickupDate + ' ' + pickupTime);
            const dropoffDateTime = new Date(dropoffDate + ' ' + dropoffTime);
            
            if (dropoffDateTime <= pickupDateTime) {
                e.preventDefault();
                alert('Drop-off date and time must be after pick-up date and time');
                return;
            }
        });
        

        document.addEventListener('DOMContentLoaded', function() {
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
        });
document.addEventListener('DOMContentLoaded', function() {
    const servicesScroll = document.querySelector('.services-scroll');
    const scrollLeftBtn = document.querySelector('.scroll-left');
    const scrollRightBtn = document.querySelector('.scroll-right');
    
    if (servicesScroll && scrollLeftBtn && scrollRightBtn) {
        const scrollAmount = 350;
        
        scrollRightBtn.addEventListener('click', function() {
            servicesScroll.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });
        
        scrollLeftBtn.addEventListener('click', function() {
            servicesScroll.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });
        
        function updateScrollButtons() {
            const maxScrollLeft = servicesScroll.scrollWidth - servicesScroll.clientWidth;
            
            if (servicesScroll.scrollLeft <= 10) {
                scrollLeftBtn.style.opacity = '0.5';
                scrollLeftBtn.style.cursor = 'default';
            } else {
                scrollLeftBtn.style.opacity = '1';
                scrollLeftBtn.style.cursor = 'pointer';
            }
            
            if (servicesScroll.scrollLeft >= maxScrollLeft - 10) {
                scrollRightBtn.style.opacity = '0.5';
                scrollRightBtn.style.cursor = 'default';
            } else {
                scrollRightBtn.style.opacity = '1';
                scrollRightBtn.style.cursor = 'pointer';
            }
        }
        
        servicesScroll.addEventListener('scroll', updateScrollButtons);
        updateScrollButtons(); 
    }
});
    </script>
</body>
</html>
<?php

$conn->close();
?>

