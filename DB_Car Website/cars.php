<?php
session_start();
require_once 'db.php';

$is_logged_in = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer';
$customer_id = $is_logged_in ? $_SESSION['user']['id'] : null;

function isCarInFavorites($conn, $customer_id, $car_id) {
    if (!$customer_id) return false;
    
    $stmt = $conn->prepare("SELECT * FROM favorites WHERE customer_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $customer_id, $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $is_favorite = $result->num_rows > 0;
    $stmt->close();
    
    return $is_favorite;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'toggle_favorite') {
        header('Content-Type: application/json');
        
        if (!$is_logged_in) {
            echo json_encode(['success' => false, 'message' => 'Please login to save favorites']);
            exit;
        }
        
        $car_id = intval($_POST['car_id']);
        
        try {
            $is_favorite = isCarInFavorites($conn, $customer_id, $car_id);
            
            if ($is_favorite) {
                // Remove from favorites
                $stmt = $conn->prepare("DELETE FROM favorites WHERE customer_id = ? AND car_id = ?");
                $stmt->bind_param("ii", $customer_id, $car_id);
                $result = $stmt->execute();
                $stmt->close();
                
                echo json_encode(['success' => true, 'is_favorite' => false, 'message' => 'Removed from favorites']);
            } else {
                // Add to favorites
                $stmt = $conn->prepare("INSERT INTO favorites (customer_id, car_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $customer_id, $car_id);
                $result = $stmt->execute();
                $stmt->close();
                
                echo json_encode(['success' => true, 'is_favorite' => true, 'message' => 'Added to favorites']);
            }
        } catch (Exception $e) {
            error_log("Favorite error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error saving favorite']);
        }
        exit;
    }
}

$searchCriteria = [
    'pickup_location' => $_SESSION['search_criteria']['pickup_location'] ?? '',
    'pickup_date' => $_SESSION['search_criteria']['pickup_date'] ?? date('Y-m-d'),
    'pickup_time' => $_SESSION['search_criteria']['pickup_time'] ?? '10:00',
    'dropoff_date' => $_SESSION['search_criteria']['dropoff_date'] ?? date('Y-m-d', strtotime('+3 days')),
    'dropoff_time' => $_SESSION['search_criteria']['dropoff_time'] ?? '10:00'
];

$selectedCityId = $searchCriteria['pickup_location'] ?? '';

$selectedCityName = '';
if (!empty($selectedCityId)) {
    $cityNameQuery = $conn->prepare("SELECT city_name FROM cities WHERE city_id = ?");
    $cityNameQuery->bind_param("i", $selectedCityId);
    $cityNameQuery->execute();
    $cityResult = $cityNameQuery->get_result();
    $cityData = $cityResult->fetch_assoc();
    $selectedCityName = $cityData['city_name'] ?? '';
    $cityNameQuery->close();
}

$carsQueryString = "
    SELECT c.car_id, c.model, c.year, c.price_per_day, c.deposit_required, 
           c.description, c.image_url,
           mk.make_name, ct.type_name, cs.status_name, ci.city_name,
           ci.city_id, cs.status_id
    FROM cars c
    JOIN makes mk ON c.make_id = mk.make_id
    JOIN car_types ct ON c.type_id = ct.type_id
    JOIN car_status cs ON c.status_id = cs.status_id
    JOIN cities ci ON c.city_id = ci.city_id
    WHERE 1=1
";

if (!empty($selectedCityId)) {
    $carsQueryString .= " AND c.city_id = ?";
}

$carsQueryString .= " ORDER BY cs.status_id ASC, c.created_at DESC";

if (!empty($selectedCityId)) {
    $carsQuery = $conn->prepare($carsQueryString);
    $carsQuery->bind_param("i", $selectedCityId);
    $carsQuery->execute();
    $carsResult = $carsQuery->get_result();
} else {
    $carsQuery = $conn->query($carsQueryString);
    $carsResult = $carsQuery;
}

$cars = [];
while ($car = $carsResult->fetch_assoc()) {
    $cars[] = $car;
}

if (!empty($selectedCityId) && isset($carsQuery)) {
    $carsQuery->close();
}

$carTypes = [];
$typesQuery = $conn->query("SELECT DISTINCT type_name FROM car_types ORDER BY type_name");
while ($type = $typesQuery->fetch_assoc()) {
    $carTypes[] = $type['type_name'];
}

$cities = [];
$cityQuery = $conn->query("SELECT city_id, city_name FROM cities ORDER BY city_name");
while ($city = $cityQuery->fetch_assoc()) {
    $cities[] = $city;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_basket'])) {
    if (!isset($_SESSION['user'])) {
        $_SESSION['error_message'] = 'Please login to add cars to your basket';
        header('Location: loginPage.php');
        exit;
    }
    
    $carId = intval($_POST['car_id']);
    $userId = $_SESSION['user']['id'];
    
    try {
        $carAvailableQuery = $conn->prepare("SELECT status_id FROM cars WHERE car_id = ?");
        $carAvailableQuery->bind_param("i", $carId);
        $carAvailableQuery->execute();
        $availableResult = $carAvailableQuery->get_result();

        if ($availableResult->num_rows === 0) {
            $_SESSION['error_message'] = 'Car not found.';
            header('Location: cars.php');
            exit;
        }

        $carStatus = $availableResult->fetch_assoc();
        $carAvailableQuery->close();

        if ($carStatus['status_id'] != 1) { // 1 = available
            $_SESSION['error_message'] = 'This car is no longer available for booking.';
            header('Location: cars.php');
            exit;
        }
        
        $basketQuery = $conn->prepare("SELECT basket_id FROM baskets WHERE customer_id = ? AND status = 'active'");
        $basketQuery->bind_param("i", $userId);
        $basketQuery->execute();
        $basketResult = $basketQuery->get_result();
        
        if ($basketResult->num_rows === 0) {
            $createBasketQuery = $conn->prepare("INSERT INTO baskets (customer_id, status) VALUES (?, 'active')");
            $createBasketQuery->bind_param("i", $userId);
            $createBasketQuery->execute();
            $basketId = $createBasketQuery->insert_id;
            $createBasketQuery->close();
        } else {
            $basketData = $basketResult->fetch_assoc();
            $basketId = $basketData['basket_id'];
        }
        $basketQuery->close();
        
        $checkItemQuery = $conn->prepare("SELECT item_id FROM basket_items WHERE basket_id = ? AND car_id = ?");
        $checkItemQuery->bind_param("ii", $basketId, $carId);
        $checkItemQuery->execute();
        $itemResult = $checkItemQuery->get_result();
        
        if ($itemResult->num_rows > 0) {
            $_SESSION['info_message'] = 'This car is already in your basket';
            header('Location: cars.php');
            exit;
        }
        $checkItemQuery->close();
        
        $startDate = $searchCriteria['pickup_date'];
        $endDate = $searchCriteria['dropoff_date'];
        
        if (empty($startDate) || !strtotime($startDate)) {
            $startDate = date('Y-m-d');
        }
        if (empty($endDate) || !strtotime($endDate)) {
            $endDate = date('Y-m-d', strtotime('+3 days'));
        }
        
        if (strtotime($endDate) <= strtotime($startDate)) {
            $endDate = date('Y-m-d', strtotime($startDate . ' +3 days'));
        }
        
        $carDetailsQuery = $conn->prepare("
            SELECT price_per_day, deposit_required 
            FROM cars 
            WHERE car_id = ?
        ");
        $carDetailsQuery->bind_param("i", $carId);
        $carDetailsQuery->execute();
        $carDetailsResult = $carDetailsQuery->get_result();
        
        if ($carDetailsResult->num_rows === 0) {
            throw new Exception('Car not found');
        }
        
        $carData = $carDetailsResult->fetch_assoc();
        $carDetailsQuery->close();
        
        $pricePerDay = floatval($carData['price_per_day']);
        $depositAmount = floatval($carData['deposit_required'] ?? 0);
        
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $rentalDays = $endDateTime->diff($startDateTime)->days;
        $rentalDays = max(1, $rentalDays); // Ensure at least 1 day
        $estimatedTotal = $pricePerDay * $rentalDays;
        
        $insertItemQuery = $conn->prepare("
            INSERT INTO basket_items (
                basket_id, 
                car_id, 
                start_date, 
                end_date, 
                deposit_amount, 
                estimated_total
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $insertItemQuery->bind_param(
            "iissdd", 
            $basketId, 
            $carId, 
            $startDate, 
            $endDate, 
            $depositAmount, 
            $estimatedTotal
        );
        
        if ($insertItemQuery->execute()) {
            $_SESSION['success_message'] = 'Car added to basket successfully!';
        } else {
            throw new Exception('Failed to add car to basket: ' . $insertItemQuery->error);
        }
        
        $insertItemQuery->close();
        
    } catch (Exception $e) {
        error_log("Basket error: " . $e->getMessage());
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }
    
    header('Location: cars.php');
    exit;
}

$basketCount = 0;
if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer') {
    $userId = $_SESSION['user']['id'];
    $basketCountQuery = $conn->prepare("
        SELECT COUNT(bi.item_id) as item_count 
        FROM baskets b 
        LEFT JOIN basket_items bi ON b.basket_id = bi.basket_id 
        WHERE b.customer_id = ? AND b.status = 'active'
    ");
    $basketCountQuery->bind_param("i", $userId);
    $basketCountQuery->execute();
    $basketResult = $basketCountQuery->get_result();
    
    if ($basketResult->num_rows > 0) {
        $basketData = $basketResult->fetch_assoc();
        $basketCount = $basketData['item_count'];
    }
    $basketCountQuery->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Listings - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .car-listings-container {
            padding: 40px 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 80px);
        }
        
        .car-listings-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .car-listings-title {
            color: var(--vivid-indigo);
            font-size: 2.2rem;
        }
        
        .car-listings-content {
            display: flex;
            gap: 30px;
        }
        
        .filters-sidebar {
            flex: 0 0 280px;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }
        
        .filter-section {
            margin-bottom: 25px;
        }
        
        .filter-section h3 {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        
        .filter-options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .filter-option {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .filter-option label {
            cursor: pointer;
        }
        
        .price-range {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .price-inputs {
            display: flex;
            gap: 10px;
        }
        
        .price-inputs input {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        
        .cars-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .car-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        
        .car-image {
            height: 200px;
            width: 100%;
            overflow: hidden;
            position: relative;
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
        
        .booked-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--coral-red);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }
        
        .car-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            gap: 10px;
        }
        
        .favorite-btn, .basket-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .favorite-btn:hover, .basket-btn:hover {
            background: white;
            transform: scale(1.1);
        }
        
        .favorite-btn i, .basket-btn i {
            font-size: 18px;
            color: #666;
            transition: color 0.3s;
        }
        
        .favorite-btn.active i {
            color: var(--vivid-red) !important;
        }
        
        .favorite-btn:hover i {
            color: var(--vivid-red);
        }
        
        .basket-btn.active i {
            color: var(--cobalt-blue);
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
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
        }
        
        .car-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--vivid-indigo);
        }
        
        .car-price span {
            font-size: 0.9rem;
            font-weight: 400;
            color: #666;
        }
        
        .car-specs {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .car-spec {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .car-spec i {
            width: 16px;
            text-align: center;
        }
        
        .status-available {
            color: #2ecc71;
            font-weight: 600;
        }
        
        .status-occupied {
            color: #e74c3c;
            font-weight: 600;
        }
        
        .car-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
            font-size: 0.95rem;
        }
        
        .car-cta {
            display: flex;
            gap: 10px;
        }
        
        .view-details-btn, .book-now-btn {
            flex: 1;
            padding: 10px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .view-details-btn {
            background: transparent;
            border: 1px solid var(--cobalt-blue);
            color: var(--cobalt-blue);
        }
        
        .view-details-btn:hover {
            background: rgba(0, 74, 173, 0.05);
        }
        
        .book-now-btn {
            background: var(--cobalt-blue);
            color: white;
        }
        
        .book-now-btn:hover {
            background: var(--dark-magenta);
        }
        
        .book-now-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
        }
        
        .book-now-btn:disabled:hover {
            background: #cccccc;
        }
        
        .no-cars-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .search-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--cobalt-blue);
        }
        
        .search-info h3 {
            margin: 0 0 10px 0;
            color: var(--vivid-indigo);
        }
        
        .search-info p {
            margin: 5px 0;
            color: #555;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-title {
            font-size: 1.5rem;
            color: var(--vivid-indigo);
            margin: 0;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .car-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .car-detail-image {
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .car-detail-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .car-detail-info h3 {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
        }
        
        .car-detail-specs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .car-detail-spec {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .car-detail-spec i {
            color: var(--cobalt-blue);
            width: 20px;
            text-align: center;
        }
        
        .car-detail-description {
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .car-detail-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--vivid-indigo);
            margin-bottom: 20px;
        }
        
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        
        .btn-secondary {
            padding: 10px 20px;
            background: #f0f0f0;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background: #e0e0e0;
        }
        
        .btn-primary {
            padding: 10px 20px;
            background: var(--cobalt-blue);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background: var(--dark-magenta);
        }
        
        .btn-primary:disabled {
            background: #cccccc;
            cursor: not-allowed;
        }
        
        .login-prompt {
            position: absolute;
            top: 50px;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
            padding: 15px;
            width: 200px;
            z-index: 10;
            display: none;
        }
        
        .login-prompt p {
            margin-bottom: 10px;
            font-size: 0.9rem;
        }
        
        .login-prompt-buttons {
            display: flex;
            gap: 10px;
        }
        
        .login-prompt-buttons button {
            flex: 1;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
        }
        
        .login-btn {
            background: var(--cobalt-blue);
            color: white;
            border: none;
        }
        
        .register-btn {
            background: transparent;
            border: 1px solid var(--cobalt-blue);
            color: var(--cobalt-blue);
        }
        
        .temp-message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 1000;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .temp-message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .temp-message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
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
        
        .search-box input {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 250px;
            font-size: 1rem;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--cobalt-blue);
        }
        
        .book-btn {
            background: var(--cobalt-blue);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
        }
        
        .book-btn:hover {
            background: var(--dark-magenta);
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .message.info {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
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
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .car-listings-content {
                flex-direction: column;
            }
            
            .filters-sidebar {
                width: 100%;
            }
            
            .car-detail-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .car-listings-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .cars-grid {
                grid-template-columns: 1fr;
            }
            
            .car-cta {
                flex-direction: column;
            }
            
            .search-box input {
                width: 100%;
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
                    <li><a href="#" class="active">Cars</a></li>
                    <li><a href="contact.php">Contact</a></li>

                    <?php if (!$is_logged_in): ?>
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

    <section class="car-listings-container">
        <div class="container">
            <!-- Display messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="message success">
                    <?php echo htmlspecialchars($_SESSION['success_message']); ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="message error">
                    <?php echo htmlspecialchars($_SESSION['error_message']); ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['info_message'])): ?>
                <div class="message info">
                    <?php echo htmlspecialchars($_SESSION['info_message']); ?>
                </div>
                <?php unset($_SESSION['info_message']); ?>
            <?php endif; ?>

            <div class="car-listings-header">
                <h1 class="car-listings-title">Our Car Fleet</h1>
                <div class="search-box">
                    <input type="text" placeholder="Search for cars..." id="searchInput">
                </div>
            </div>
            
            <?php if (!empty($selectedCityId)): ?>
                <div class="search-info">
                    <h3><i class="fas fa-filter"></i> Filtered Results</h3>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($selectedCityName); ?></p>
                    <p><strong>Dates:</strong> <?php echo htmlspecialchars($searchCriteria['pickup_date'] . ' to ' . $searchCriteria['dropoff_date']); ?></p>
                    <button id="clear-search" class="btn-secondary" style="margin-top: 10px; padding: 8px 15px; font-size: 0.9rem;">
                        <i class="fas fa-times"></i> Clear Search Filters
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="car-listings-content">
                <div class="filters-sidebar">
                    <div class="filter-section">
                        <h3>Car Type</h3>
                        <div class="filter-options">
                            <?php foreach ($carTypes as $type): ?>
                                <div class="filter-option">
                                    <input type="checkbox" id="type-<?php echo strtolower($type); ?>" data-filter="type" value="<?php echo strtolower($type); ?>">
                                    <label for="type-<?php echo strtolower($type); ?>"><?php echo htmlspecialchars($type); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h3>Location</h3>
                        <div class="filter-options">
                            <?php foreach ($cities as $city): ?>
                                <div class="filter-option">
                                    <input type="checkbox" id="city-<?php echo $city['city_id']; ?>" data-filter="city" value="<?php echo $city['city_id']; ?>">
                                    <label for="city-<?php echo $city['city_id']; ?>"><?php echo htmlspecialchars($city['city_name']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h3>Price Range</h3>
                        <div class="price-range">
                            <div class="price-inputs">
                                <input type="number" id="min-price" placeholder="Min" min="0">
                                <input type="number" id="max-price" placeholder="Max" min="0">
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-section">
                        <h3>Availability</h3>
                        <div class="filter-options">
                            <div class="filter-option">
                                <input type="checkbox" id="filter-available" data-filter="availability" value="available">
                                <label for="filter-available">Available Only</label>
                            </div>
                        </div>
                    </div>
                    
                    <button id="apply-filters" class="book-btn">Apply Filters</button>
                    <button id="reset-filters" class="btn-secondary" style="margin-top: 10px; width: 100%;">Reset Filters</button>
                </div>
                
                <div class="cars-grid" id="carsGrid">
                    <?php if (empty($cars)): ?>
                        <div class="no-cars-message">
                            <h3>
                                <?php if (!empty($selectedCityId)): ?>
                                    No cars available in <?php echo htmlspecialchars($selectedCityName); ?>
                                <?php else: ?>
                                    No cars available at the moment
                                <?php endif; ?>
                            </h3>
                            <p>
                                <?php if (!empty($selectedCityId)): ?>
                                    Please try a different location or <a href="landing.php">search again</a>.
                                <?php else: ?>
                                    Please check back later or contact us for availability.
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($selectedCityId)): ?>
                                <a href="landing.php" class="book-btn" style="display: inline-block; margin-top: 15px; text-decoration: none;">
                                    <i class="fas fa-search"></i> Search Different Location
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cars as $car): ?>
                            <div class="car-card" data-id="<?php echo $car['car_id']; ?>" data-status="<?php echo $car['status_id']; ?>">
                                <div class="car-image">
                                    <?php if ($car['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($car['image_url']); ?>" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                    <?php else: ?>
                                        <img src="car-default.jpg" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                    <?php endif; ?>
                                    
                                    <?php if ($car['status_id'] == 2): ?>
                                        <div class="booked-badge">Booked</div>
                                    <?php endif; ?>
                                    
                                    <div class="car-actions">
                                        <button class="favorite-btn <?php echo $is_logged_in && isCarInFavorites($conn, $customer_id, $car['car_id']) ? 'active' : ''; ?>" 
                                                data-id="<?php echo $car['car_id']; ?>">
                                            <i class="fas fa-heart"></i>
                                        </button>
                                        <?php if ($car['status_id'] == 1): ?>
                                            <button class="basket-btn" data-id="<?php echo $car['car_id']; ?>" data-name="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                                <i class="fas fa-shopping-basket"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model'] . ' (' . $car['year'] . ')'); ?></h3>
                                        <div class="car-price">£<?php echo number_format($car['price_per_day'], 2); ?><span>/day</span></div>
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
                                        <div class="car-spec">
                                            <i class="fas fa-info-circle"></i>
                                            <span class="status-<?php echo strtolower($car['status_name']); ?>"><?php echo $car['status_name']; ?></span>
                                        </div>
                                    </div>
                                    <p class="car-description"><?php echo htmlspecialchars($car['description'] ?? 'No description available.'); ?></p>
                                    <div class="car-cta">
                                        <button class="view-details-btn" data-id="<?php echo $car['car_id']; ?>">View Details</button>
                                        <?php if ($car['status_id'] == 1): ?>
                                            <button class="book-now-btn" data-id="<?php echo $car['car_id']; ?>" data-name="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">Book Now</button>
                                        <?php else: ?>
                                            <button class="book-now-btn" disabled style="background: #ccc; cursor: not-allowed;">Unavailable</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="modal" id="carDetailModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalCarName">Car Name</h2>
                <button class="close-modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modalCarId" value="">
                <div class="car-detail-grid">
                    <div class="car-detail-image">
                        <img id="modalCarImage" src="" alt="Car Image">
                        <div id="modalBookedBadge" class="booked-badge" style="display: none;">Booked</div>
                    </div>
                    <div class="car-detail-info">
                        <h3>Car Details</h3>
                        <div class="car-detail-specs">
                            <div class="car-detail-spec">
                                <i class="fas fa-car"></i>
                                <span id="modalCarType">Type</span>
                            </div>
                            <div class="car-detail-spec">
                                <i class="fas fa-map-marker-alt"></i>
                                <span id="modalCarLocation">Location</span>
                            </div>
                            <div class="car-detail-spec">
                                <i class="fas fa-calendar"></i>
                                <span id="modalCarYear">Year</span>
                            </div>
                            <div class="car-detail-spec">
                                <i class="fas fa-info-circle"></i>
                                <span id="modalCarStatus">Status</span>
                            </div>
                        </div>
                        <div class="car-detail-description" id="modalCarDescription">
                            Car description goes here...
                        </div>
                        <div class="car-detail-price" id="modalCarPrice">£0/day</div>
                        <button class="btn-primary" id="modalAddToBasket">Add to Basket</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary close-modal">Close</button>
            </div>
        </div>
    </div>

    <div class="login-prompt" id="loginPrompt">
        <p>Please login or register to save favorites</p>
        <div class="login-prompt-buttons">
            <button class="login-btn" onclick="window.location.href='loginPage.php'">Login</button>
            <button class="register-btn" onclick="window.location.href='register.php'">Register</button>
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
                        <li><a href="#">Our Fleet</a></li>
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
        const currentUser = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        let filteredCars = [...document.querySelectorAll('.car-card')];

        const carsGrid = document.getElementById('carsGrid');
        const searchInput = document.getElementById('searchInput');
        const applyFiltersBtn = document.getElementById('apply-filters');
        const resetFiltersBtn = document.getElementById('reset-filters');
        const carDetailModal = document.getElementById('carDetailModal');
        const loginPrompt = document.getElementById('loginPrompt');

        document.addEventListener('DOMContentLoaded', function() {
            applyFiltersBtn.addEventListener('click', applyFilters);
            resetFiltersBtn.addEventListener('click', resetFilters);
            searchInput.addEventListener('input', handleSearch);
            
            document.querySelectorAll('.close-modal').forEach(button => {
                button.addEventListener('click', () => {
                    carDetailModal.style.display = 'none';
                });
            });
            
            window.addEventListener('click', (e) => {
                if (e.target === carDetailModal) {
                    carDetailModal.style.display = 'none';
                }
            });
            
            document.querySelectorAll('.view-details-btn').forEach(button => {
                button.addEventListener('click', showCarDetails);
            });
            
            document.querySelectorAll('.favorite-btn').forEach(button => {
                button.addEventListener('click', handleFavoriteClick);
            });
            
            document.querySelectorAll('.basket-btn, .book-now-btn').forEach(button => {
                button.addEventListener('click', handleBasketClick);
            });
            
            document.getElementById('modalAddToBasket').addEventListener('click', addToBasketFromModal);
            
            document.getElementById('clear-search')?.addEventListener('click', function() {
                fetch('clear_search.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error clearing search:', error);
                        window.location.reload();
                    });
            });
            
            const searchCriteria = <?php echo json_encode($searchCriteria); ?>;
            if (searchCriteria.pickup_date && searchCriteria.dropoff_date && searchCriteria.pickup_location) {
                searchInput.placeholder = "Search within filtered results...";
            }
        });

        async function handleFavoriteClick(event) {
            event.stopPropagation();
            
            const button = event.currentTarget;
            const carId = button.getAttribute('data-id');
            
            if (!currentUser) {
                showLoginPrompt(event);
                return;
            }
            
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            try {
                const response = await fetch('cars.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=toggle_favorite&car_id=${carId}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    
                    if (result.is_favorite) {
                        button.classList.add('active');
                    } else {
                        button.classList.remove('active');
                    }
                    
                    showTemporaryMessage(result.message, 'success');
                } else {
                    showTemporaryMessage(result.message, 'error');
                }
            } catch (error) {
                console.error('Error toggling favorite:', error);
                showTemporaryMessage('Network error. Please try again.', 'error');
            } finally {
                // Restore button state
                button.innerHTML = '<i class="fas fa-heart"></i>';
                button.disabled = false;
            }
        }

        function handleBasketClick(event) {
            event.stopPropagation();
            
            const button = event.currentTarget;
            const carId = button.getAttribute('data-id');
            const carName = button.getAttribute('data-name');
            
            if (!currentUser) {
                showTemporaryMessage('Please login to add cars to basket', 'error');
                setTimeout(() => {
                    window.location.href = 'loginPage.php';
                }, 1500);
                return;
            }
            
            addToBasket(carId, carName, button);
        }


        async function addToBasket(carId, carName, button = null) {
            if (!currentUser) {
                showTemporaryMessage('Please login to add cars to basket', 'error');
                setTimeout(() => {
                    window.location.href = 'loginPage.php';
                }, 1500);
                return;
            }
            

            const carCard = document.querySelector(`.car-card[data-id="${carId}"]`);
            if (carCard && carCard.getAttribute('data-status') !== '1') {
                showTemporaryMessage('This car is no longer available for booking', 'error');
                return;
            }
            

            if (button) {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;
            }
            
            try {
                const formData = new FormData();
                formData.append('add_to_basket', '1');
                formData.append('car_id', carId);
                
                const response = await fetch('cars.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                
                const result = await response.text();
                

                window.location.reload();
                
            } catch (error) {
                console.error('Error adding to basket:', error);
                showTemporaryMessage('Error adding car to basket', 'error');
                
                if (button) {
                    button.innerHTML = button.classList.contains('basket-btn') ? '<i class="fas fa-shopping-basket"></i>' : 'Book Now';
                    button.disabled = false;
                }
            }
        }

        function showTemporaryMessage(message, type) {
            const existingMsg = document.querySelector('.temp-message');
            if (existingMsg) existingMsg.remove();
            
            const messageEl = document.createElement('div');
            messageEl.className = `temp-message ${type}`;
            messageEl.textContent = message;
            
            document.body.appendChild(messageEl);
            
            setTimeout(() => {
                messageEl.remove();
            }, 3000);
        }

        function applyFilters() {
            const selectedTypes = getSelectedValues('type');
            const selectedCities = getSelectedValues('city');
            const minPrice = parseInt(document.getElementById('min-price').value) || 0;
            const maxPrice = parseInt(document.getElementById('max-price').value) || Infinity;
            const availableOnly = document.getElementById('filter-available').checked;
            
            const carCards = document.querySelectorAll('.car-card');
            
            carCards.forEach(card => {
                const carType = card.querySelector('.car-spec:nth-child(1) span').textContent.toLowerCase();
                const carLocation = card.querySelector('.car-spec:nth-child(2) span').textContent;
                const carPrice = parseFloat(card.querySelector('.car-price').textContent.replace('£', '').replace('/day', ''));
                const carStatus = card.getAttribute('data-status');
                
                const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(carType);
                const cityMatch = selectedCities.length === 0 || selectedCities.some(cityId => {
                    return carLocation.toLowerCase().includes(getCityName(cityId).toLowerCase());
                });
                const priceMatch = carPrice >= minPrice && carPrice <= maxPrice;
                const availabilityMatch = !availableOnly || carStatus === '1';
                
                if (typeMatch && cityMatch && priceMatch && availabilityMatch) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function resetFilters() {
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            document.getElementById('min-price').value = '';
            document.getElementById('max-price').value = '';
            searchInput.value = '';
            
            document.querySelectorAll('.car-card').forEach(card => {
                card.style.display = 'block';
            });
        }

        function handleSearch() {
            const searchTerm = searchInput.value.toLowerCase();
            const carCards = document.querySelectorAll('.car-card');
            
            carCards.forEach(card => {
                const carName = card.querySelector('.car-name').textContent.toLowerCase();
                const carDescription = card.querySelector('.car-description').textContent.toLowerCase();
                
                if (carName.includes(searchTerm) || carDescription.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        function getSelectedValues(filterType) {
            const checkboxes = document.querySelectorAll(`input[data-filter="${filterType}"]:checked`);
            return Array.from(checkboxes).map(checkbox => checkbox.value);
        }

        function getCityName(cityId) {
            const cityMap = {
                '1': 'Birmingham',
                '2': 'London',
                '3': 'Manchester',
                '4': 'Liverpool',
                '5': 'Sheffield'
            };
            return cityMap[cityId] || '';
        }

        function showCarDetails(event) {
            event.stopPropagation();
            const carId = event.currentTarget.getAttribute('data-id');
            const carCard = document.querySelector(`.car-card[data-id="${carId}"]`);
            
            if (carCard) {
                const carName = carCard.querySelector('.car-name').textContent;
                const carImage = carCard.querySelector('.car-image img').src;
                const carType = carCard.querySelector('.car-spec:nth-child(1) span').textContent;
                const carLocation = carCard.querySelector('.car-spec:nth-child(2) span').textContent;
                const carStatus = carCard.querySelector('.car-spec:nth-child(3) span').textContent;
                const carYear = carName.match(/\((\d{4})\)/)?.[1] || '';
                const carDescription = carCard.querySelector('.car-description').textContent;
                const carPrice = carCard.querySelector('.car-price').textContent;
                const isAvailable = carCard.getAttribute('data-status') === '1';
                
                document.getElementById('modalCarName').textContent = carName;
                document.getElementById('modalCarImage').src = carImage;
                document.getElementById('modalCarType').textContent = carType;
                document.getElementById('modalCarLocation').textContent = carLocation;
                document.getElementById('modalCarYear').textContent = carYear;
                document.getElementById('modalCarStatus').textContent = carStatus;
                document.getElementById('modalCarDescription').textContent = carDescription;
                document.getElementById('modalCarPrice').textContent = carPrice;
                document.getElementById('modalCarId').value = carId;
                
                // Show/hide booked badge
                const bookedBadge = document.getElementById('modalBookedBadge');
                if (isAvailable) {
                    bookedBadge.style.display = 'none';
                    document.getElementById('modalAddToBasket').disabled = false;
                    document.getElementById('modalAddToBasket').textContent = 'Add to Basket';
                } else {
                    bookedBadge.style.display = 'block';
                    document.getElementById('modalAddToBasket').disabled = true;
                    document.getElementById('modalAddToBasket').textContent = 'Unavailable';
                }
                
                carDetailModal.style.display = 'flex';
            }
        }

        function addToBasketFromModal() {
            const carId = document.getElementById('modalCarId').value;
            const carName = document.getElementById('modalCarName').textContent;
            
            if (carId && carName) {
                addToBasket(carId, carName);
                carDetailModal.style.display = 'none';
            }
        }

        function showLoginPrompt(event) {
            const button = event.currentTarget;
            const rect = button.getBoundingClientRect();
            
            loginPrompt.style.top = `${rect.bottom + window.scrollY}px`;
            loginPrompt.style.right = `${window.innerWidth - rect.right}px`;
            loginPrompt.style.display = 'block';
            
            setTimeout(() => {
                loginPrompt.style.display = 'none';
            }, 3000);
        }

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.favorite-btn') && !e.target.closest('.login-prompt')) {
                loginPrompt.style.display = 'none';
            }
        });
    </script>
</body>
</html>
<?php
$conn->close();
?>