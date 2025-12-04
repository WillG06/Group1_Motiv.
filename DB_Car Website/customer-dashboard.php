<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: loginPage.php");
    exit();
}

require_once 'db.php';

$customer_id = $_SESSION['user']['id'];

$stmt = $conn->prepare("
    SELECT c.*, ci.city_name 
    FROM customers c 
    LEFT JOIN cities ci ON c.city_id = ci.city_id 
    WHERE c.customer_id = ?
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Customer not found in database. ID: " . $customer_id);
}

$customer = $result->fetch_assoc();
$stmt->close();

$favorites_stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM favorites WHERE customer_id = ?
");
$favorites_stmt->bind_param("i", $customer_id);
$favorites_stmt->execute();
$favorites_result = $favorites_stmt->get_result();
$favorites_count = $favorites_result->fetch_assoc()['count'];
$favorites_stmt->close();

$basket_stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM basket_items bi 
    JOIN baskets b ON bi.basket_id = b.basket_id 
    WHERE b.customer_id = ? AND b.status = 'active'
");
$basket_stmt->bind_param("i", $customer_id);
$basket_stmt->execute();
$basket_result = $basket_stmt->get_result();
$basket_count = $basket_result->fetch_assoc()['count'];
$basket_stmt->close();

$rentals_stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM bookings WHERE customer_id = ?
");
$rentals_stmt->bind_param("i", $customer_id);
$rentals_stmt->execute();
$rentals_result = $rentals_stmt->get_result();
$rentals_count = $rentals_result->fetch_assoc()['count'];
$rentals_stmt->close();
$loyalty_points = $rentals_count * 10;


$recent_favorites_stmt = $conn->prepare("
    SELECT c.*, m.make_name, ct.type_name, cs.status_name, ci.city_name
    FROM favorites f
    JOIN cars c ON f.car_id = c.car_id
    JOIN makes m ON c.make_id = m.make_id
    JOIN car_types ct ON c.type_id = ct.type_id
    JOIN car_status cs ON c.status_id = cs.status_id
    JOIN cities ci ON c.city_id = ci.city_id
    WHERE f.customer_id = ?
    ORDER BY f.created_at DESC
    LIMIT 3
");
$recent_favorites_stmt->bind_param("i", $customer_id);
$recent_favorites_stmt->execute();
$recent_favorites_result = $recent_favorites_stmt->get_result();
$recent_favorites = $recent_favorites_result->fetch_all(MYSQLI_ASSOC);
$recent_favorites_stmt->close();

$all_favorites_stmt = $conn->prepare("
    SELECT c.*, m.make_name, ct.type_name, cs.status_name, ci.city_name
    FROM favorites f
    JOIN cars c ON f.car_id = c.car_id
    JOIN makes m ON c.make_id = m.make_id
    JOIN car_types ct ON c.type_id = ct.type_id
    JOIN car_status cs ON c.status_id = cs.status_id
    JOIN cities ci ON c.city_id = ci.city_id
    WHERE f.customer_id = ?
    ORDER BY f.created_at DESC
");
$all_favorites_stmt->bind_param("i", $customer_id);
$all_favorites_stmt->execute();
$all_favorites_result = $all_favorites_stmt->get_result();
$all_favorites = $all_favorites_result->fetch_all(MYSQLI_ASSOC);
$all_favorites_stmt->close();

$basket_items_stmt = $conn->prepare("
    SELECT bi.*, c.*, m.make_name, ct.type_name, ci.city_name
    FROM basket_items bi
    JOIN baskets b ON bi.basket_id = b.basket_id
    JOIN cars c ON bi.car_id = c.car_id
    JOIN makes m ON c.make_id = m.make_id
    JOIN car_types ct ON c.type_id = ct.type_id
    JOIN cities ci ON c.city_id = ci.city_id
    WHERE b.customer_id = ? AND b.status = 'active'
");
$basket_items_stmt->bind_param("i", $customer_id);
$basket_items_stmt->execute();
$basket_items_result = $basket_items_stmt->get_result();
$basket_items = $basket_items_result->fetch_all(MYSQLI_ASSOC);
$basket_items_stmt->close();


$basket_total = 0;
foreach ($basket_items as $item) {
    $basket_total += $item['estimated_total'] ?? 0;
}


$rentals_stmt = $conn->prepare("
    SELECT b.*, c.model, m.make_name, bs.status_name, ci.city_name
    FROM bookings b
    JOIN cars c ON b.car_id = c.car_id
    JOIN makes m ON c.make_id = m.make_id
    JOIN booking_status bs ON b.booking_status_id = bs.booking_status_id
    JOIN cities ci ON c.city_id = ci.city_id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
");
$rentals_stmt->bind_param("i", $customer_id);
$rentals_stmt->execute();
$rentals_result = $rentals_stmt->get_result();
$rentals = $rentals_result->fetch_all(MYSQLI_ASSOC);
$rentals_stmt->close();

$upcoming_rentals_stmt = $conn->prepare("
    SELECT b.*, c.model, m.make_name, bs.status_name, ci.city_name
    FROM bookings b
    JOIN cars c ON b.car_id = c.car_id
    JOIN makes m ON c.make_id = m.make_id
    JOIN booking_status bs ON b.booking_status_id = bs.booking_status_id
    JOIN cities ci ON c.city_id = ci.city_id
    WHERE b.customer_id = ? AND b.start_date >= CURDATE()
    ORDER BY b.start_date ASC
    LIMIT 2
");
$upcoming_rentals_stmt->bind_param("i", $customer_id);
$upcoming_rentals_stmt->execute();
$upcoming_rentals_result = $upcoming_rentals_stmt->get_result();
$upcoming_rentals = $upcoming_rentals_result->fetch_all(MYSQLI_ASSOC);
$upcoming_rentals_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
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

header {
    position: relative;
    z-index: 1000;
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
        }
        
        .basket-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff7f50;
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
        
        .dashboard-container {
            padding: 40px 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 80px);
        }
        
        .dashboard-header {
            background: linear-gradient(to right, #5c2aa5, #a0206a);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .welcome-section {
            text-align: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .welcome-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .member-id {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
        }
        
        .dashboard-nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-tabs {
            display: flex;
            list-style: none;
            overflow-x: auto;
        }
        
        .nav-tab {
            padding: 20px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
            font-weight: 600;
            color: #666;
        }
        
        .nav-tab.active {
            color: #5c2aa5;
            border-bottom-color: #5c2aa5;
        }
        
        .nav-tab:hover {
            color: #5c2aa5;
            background: rgba(140, 0, 80, 0.05);
        }
        
        .tab-content {
            display: none;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .dashboard-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .section-title {
            color: #5c2aa5;
            font-size: 1.5rem;
            margin: 0;
        }
        
        .view-all {
            color: #004aad;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(to right, #004aad, #5c2aa5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .stat-icon i {
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #5c2aa5;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .car-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        
        .car-image {
            height: 160px;
            width: 100%;
            overflow: hidden;
        }
        
        .car-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .car-card:hover .car-image img {
            transform: scale(1.05);
        }
        
        .car-details {
            padding: 20px;
        }
        
        .car-title {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        
        .car-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        .car-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #5c2aa5;
        }
        
        .car-specs {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .car-spec {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .car-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-secondary, .btn-primary {
            flex: 1;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-align: center;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid #004aad;
            color: #004aad;
        }
        
        .btn-secondary:hover {
            background: rgba(0, 74, 173, 0.05);
        }
        
        .btn-primary {
            background: #004aad;
            color: white;
        }
        
        .btn-primary:hover {
            background: #a0206a;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }
        
        .rental-history {
            width: 100%;
            border-collapse: collapse;
        }
        
        .rental-history th,
        .rental-history td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .rental-history th {
            background: #f8f8f8;
            color: #5c2aa5;
            font-weight: 600;
        }
        
        .rental-history tr:hover {
            background: #f8f8f8;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-upcoming {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-completed {
            background: #f5f5f5;
            color: #666;
        }
        
        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .info-group {
            margin-bottom: 20px;
        }
        
        .info-label {
            font-weight: 600;
            color: #5c2aa5;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #333;
            font-size: 1.1rem;
        }
        
        .edit-profile-btn {
            background: #004aad;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .edit-profile-btn:hover {
            background: #a0206a;
        }
        
        .basket-summary {
            background: #f8f8f8;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .basket-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .basket-item:last-child {
            border-bottom: none;
        }
        
        .basket-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #ddd;
            font-weight: 700;
            font-size: 1.2rem;
            color: #5c2aa5;
        }
        
        @media (max-width: 992px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .cars-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2rem;
            }
            
            .nav-tabs {
                flex-wrap: wrap;
            }
            
            .nav-tab {
                padding: 15px 20px;
            }
            
            .dashboard-section {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .rental-history {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .cars-grid {
                grid-template-columns: 1fr;
            }
            
            .car-actions {
                flex-direction: column;
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
                <li><a href="customer-dashboard.php" class="active">Dashboard</a></li>
                <li>
                    <a href="logout.php" style="color: #ff4444;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
                <li class="language-selector">
                    <a href="#">🌐︎</a>
                    <div class="language-dropdown">
                        <a href="#" data-lang="en">English</a>
                        <a href="#" data-lang="es">Español</a>
                        <a href="#" data-lang="fr">Français</a>
                        <a href="#" data-lang="de">Deutsch</a>
                        <a href="#" data-lang="it">Italiano</a>
                        <a href="#" data-lang="zh">中文</a>
                    </div>
                </li>
                <li class="basket-indicator">
                    <a href="basket.php">
                        <i class="fas fa-shopping-basket"></i>
                        <span class="basket-count"><?php echo $basket_count; ?></span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>

    <section class="dashboard-header">
        <div class="welcome-section">
            <h1 class="welcome-title">Welcome back, <?php echo htmlspecialchars($customer['first_name']); ?>!</h1>
            <p class="welcome-subtitle">Manage your rentals, favorites, and account settings</p>
            <div class="member-id">Member ID: <?php echo $customer['customer_id']; ?></div>
        </div>
    </section>

    <nav class="dashboard-nav">
        <div class="nav-container">
            <ul class="nav-tabs">
                <li class="nav-tab active" data-tab="overview">Overview</li>
                <li class="nav-tab" data-tab="favorites">My Favorites</li>
                <li class="nav-tab" data-tab="basket">My Basket</li>
                <li class="nav-tab" data-tab="rentals">Rental History</li>
                <li class="nav-tab" data-tab="profile">Profile Settings</li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">

        <div class="tab-content active" id="overview">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-number"><?php echo $favorites_count; ?></div>
                    <div class="stat-label">Favorite Cars</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div class="stat-number"><?php echo $basket_count; ?></div>
                    <div class="stat-label">Items in Basket</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-number"><?php echo $rentals_count; ?></div>
                    <div class="stat-label">Total Rentals</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo $loyalty_points; ?></div>
                    <div class="stat-label">Loyalty Points</div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Recent Favorites</h2>
                    <a href="#favorites" class="view-all" onclick="switchTab('favorites')">View All</a>
                </div>
                <div class="cars-grid" id="recentFavorites">
                    <?php if (count($recent_favorites) > 0): ?>
                        <?php foreach($recent_favorites as $car): ?>
                            <div class="car-card">
                                <div class="car-image">
                                    <img src="<?php echo htmlspecialchars($car['image_url'] ?? 'car-default.jpg'); ?>" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                </div>
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?></h3>
                                        <div class="car-price">£<?php echo $car['price_per_day']; ?><span>/day</span></div>
                                    </div>
                                    <div class="car-specs">
                                        <div class="car-spec">
                                            <i class="fas fa-car"></i>
                                            <span><?php echo htmlspecialchars($car['type_name']); ?></span>
                                        </div>
                                        <div class="car-spec">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($car['city_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="car-actions">
                                        <button class="btn-secondary view-details" data-id="<?php echo $car['car_id']; ?>">Details</button>
                                        <button class="btn-primary book-now" data-id="<?php echo $car['car_id']; ?>">Book Now</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-heart"></i>
                            <h3>No favorites yet</h3>
                            <p>Start browsing our car collection and add your favorites!</p>
                            <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">Browse Cars</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Upcoming Rentals</h2>
                    <a href="#rentals" class="view-all" onclick="switchTab('rentals')">View All</a>
                </div>
                <div id="upcomingRentals">
                    <?php if (count($upcoming_rentals) > 0): ?>
                        <?php foreach($upcoming_rentals as $rental): ?>
                            <div class="car-card" style="margin-bottom: 15px;">
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($rental['make_name'] . ' ' . $rental['model']); ?></h3>
                                        <div class="car-price">£<?php echo $rental['total_cost']; ?></div>
                                    </div>
                                    <div class="car-specs">
                                        <div class="car-spec">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M j, Y', strtotime($rental['start_date'])); ?></span>
                                        </div>
                                        <div class="car-spec">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($rental['city_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="car-actions">
                                        <button class="btn-secondary">View Details</button>
                                        <button class="btn-primary">Modify Booking</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 20px;">
                            <i class="fas fa-calendar"></i>
                            <p>No upcoming rentals</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="favorites">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">My Favorite Cars</h2>
                </div>
                <div class="cars-grid" id="favoritesGrid">
                    <?php if (count($all_favorites) > 0): ?>
                        <?php foreach($all_favorites as $car): ?>
                            <div class="car-card">
                                <div class="car-image">
                                    <img src="<?php echo htmlspecialchars($car['image_url'] ?? 'car-default.jpg'); ?>" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                </div>
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?></h3>
                                        <div class="car-price">£<?php echo $car['price_per_day']; ?><span>/day</span></div>
                                    </div>
                                    <div class="car-specs">
                                        <div class="car-spec">
                                            <i class="fas fa-car"></i>
                                            <span><?php echo htmlspecialchars($car['type_name']); ?></span>
                                        </div>
                                        <div class="car-spec">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($car['city_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="car-actions">
                                        <button class="btn-secondary view-details" data-id="<?php echo $car['car_id']; ?>">Details</button>
                                        <button class="btn-primary book-now" data-id="<?php echo $car['car_id']; ?>">Book Now</button>
                                        <button class="btn-secondary remove-favorite" data-id="<?php echo $car['car_id']; ?>">Remove</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" id="emptyFavorites">
                            <i class="fas fa-heart"></i>
                            <h3>No favorites yet</h3>
                            <p>Start browsing our car collection and add your favorites!</p>
                            <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">Browse Cars</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="basket">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">My Basket</h2>
                    <?php if ($basket_count > 0): ?>
                        <a href="basket.php" class="view-all">Go to Checkout</a>
                    <?php endif; ?>
                </div>
                
                <?php if ($basket_count > 0): ?>
                    <div class="cars-grid" id="basketGrid">
                        <?php foreach($basket_items as $item): ?>
                            <div class="car-card">
                                <div class="car-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'car-default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model']); ?>">
                                </div>
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model']); ?></h3>
                                        <div class="car-price">£<?php echo $item['price_per_day']; ?><span>/day</span></div>
                                    </div>
                                    <div class="car-specs">
                                        <div class="car-spec">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M j, Y', strtotime($item['start_date'])); ?> - <?php echo date('M j, Y', strtotime($item['end_date'])); ?></span>
                                        </div>
                                        <div class="car-spec">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($item['city_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="car-actions">
                                        <button class="btn-secondary remove-from-basket" data-id="<?php echo $item['item_id']; ?>">Remove</button>
                                        <button class="btn-primary" onclick="window.location.href='basket.php'">Checkout</button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="basket-summary">
                        <h3>Basket Summary</h3>
                        <div id="basketItemsList">
                            <?php foreach($basket_items as $item): ?>
                                <div class="basket-item">
                                    <div><?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model']); ?> (<?php echo $item['rental_days']; ?> days)</div>
                                    <div>£<?php echo $item['estimated_total']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="basket-total">
                            <span>Total:</span>
                            <span>£<?php echo number_format($basket_total, 2); ?></span>
                        </div>
                        <a href="basket.php" class="btn-primary" style="display: block; text-align: center; margin-top: 15px;">Proceed to Checkout</a>
                    </div>
                <?php else: ?>
                    <div class="empty-state" id="emptyBasket">
                        <i class="fas fa-shopping-basket"></i>
                        <h3>Your basket is empty</h3>
                        <p>Add some cars to your basket to get started!</p>
                        <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">Browse Cars</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="rentals">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Rental History</h2>
                </div>
                <?php if (count($rentals) > 0): ?>
                    <div class="table-responsive">
                        <table class="rental-history">
                            <thead>
                                <tr>
                                    <th>Car</th>
                                    <th>Rental Period</th>
                                    <th>Pickup Location</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="rentalsTableBody">
                                <?php foreach($rentals as $rental): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rental['make_name'] . ' ' . $rental['model']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($rental['start_date'])); ?> - <?php echo date('M j, Y', strtotime($rental['end_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($rental['city_name']); ?></td>
                                        <td>£<?php echo $rental['total_cost']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($rental['status_name']); ?>">
                                                <?php echo htmlspecialchars($rental['status_name']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" id="emptyRentals">
                        <i class="fas fa-history"></i>
                        <h3>No rental history</h3>
                        <p>You haven't made any rentals yet.</p>
                        <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">Book Your First Car</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="profile">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Profile Information</h2>
                    <button class="edit-profile-btn" id="editProfileBtn">Edit Profile</button>
                </div>
                <div class="profile-info">
                    <div>
                        <div class="info-group">
                            <div class="info-label">Full Name</div>
                            <div class="info-value" id="profileName"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Email Address</div>
                            <div class="info-value" id="profileEmail"><?php echo htmlspecialchars($customer['email']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value" id="profilePhone"><?php echo htmlspecialchars($customer['phone'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="info-group">
                            <div class="info-label">Member Since</div>
                            <div class="info-value" id="profileMemberSince"><?php echo date('F j, Y', strtotime($customer['created_at'])); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label">City</div>
                            <div class="info-value" id="profileCity"><?php echo htmlspecialchars($customer['city_name'] ?? 'Not provided'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title">Account Settings</h2>
                </div>
                <div class="profile-actions">
                    <button class="btn-secondary" style="margin-right: 10px;">Change Password</button>
                    <button class="btn-secondary" style="margin-right: 10px;">Notification Preferences</button>
                    <button class="btn-secondary" id="deleteAccountBtn">Delete Account</button>
                </div>
            </div>
        </div>
    </div>

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
            
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    switchTab(this.getAttribute('data-tab'));
                });
            });
            
           
            document.getElementById('editProfileBtn').addEventListener('click', function() {
                alert('Edit profile functionality would open a form here.');
            });
            
            
            document.getElementById('deleteAccountBtn').addEventListener('click', function() {
                if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
                    window.location.href = 'delete-account.php';
                }
            });
            
            
            addCarCardEventListeners();
        });

        function addCarCardEventListeners() {
           
            document.querySelectorAll('.view-details').forEach(button => {
                button.addEventListener('click', function() {
                    const carId = this.getAttribute('data-id');
                    window.location.href = 'car-details.php?id=' + carId;
                });
            });
            
            
            document.querySelectorAll('.book-now').forEach(button => {
                button.addEventListener('click', function() {
                    const carId = this.getAttribute('data-id');
                    window.location.href = 'booking.php?car_id=' + carId;
                });
            });
            
            
            document.querySelectorAll('.remove-from-basket').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    if (confirm('Are you sure you want to remove this item from your basket?')) {
                        window.location.href = 'remove-from-basket.php?item_id=' + itemId;
                    }
                });
            });
            
            
            document.querySelectorAll('.remove-favorite').forEach(button => {
                button.addEventListener('click', function() {
                    const carId = this.getAttribute('data-id');
                    if (confirm('Are you sure you want to remove this car from your favorites?')) {
                        window.location.href = 'remove-favorite.php?car_id=' + carId;
                    }
                });
            });
        }

        function switchTab(tabName) {
            
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.nav-tab[data-tab="${tabName}"]`).classList.add('active');
            
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
        }
    </script>
</body>
</html>
