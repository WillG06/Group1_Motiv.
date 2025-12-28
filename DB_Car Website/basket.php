<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: loginPage.php');
    exit;
}

$currentStep = isset($_GET['step']) ? $_GET['step'] : 1;
// Handle string 'success' step
if ($currentStep === 'success') {
    $currentStep = 'success';
} else {
    $currentStep = intval($currentStep);
}

$user = $_SESSION['user'];
$userId = $user['id'];
$userRole = $user['role'];

$basketItems = [];
$basketTotal = 0;
$basketCount = 0;
$selectedExtras = [];

$extras = [

    ['extra_id' => 1, 'name' => 'Personal Accident Insurance', 'price' => 12.50, 'description' => 'Covers medical costs for you and your passengers', 'category' => 'Protection Products', 'unit' => 'per day'],
    ['extra_id' => 2, 'name' => 'Theft Protection', 'price' => 10.00, 'description' => 'Reduces your liability if the vehicle is stolen', 'category' => 'Protection Products', 'unit' => 'per day'],
    
    ['extra_id' => 3, 'name' => 'Additional Driver', 'price' => 10.00, 'description' => 'Add an extra authorised driver to your rental', 'category' => 'Additional Services', 'unit' => 'per day'],
    ['extra_id' => 4, 'name' => 'Young Driver Fee', 'price' => 15.00, 'description' => 'Required surcharge for drivers aged 21-24', 'category' => 'Additional Services', 'unit' => 'per day'],

    ['extra_id' => 5, 'name' => 'Child Seat', 'price' => 7.50, 'description' => 'Suitable for children 9-18kg (Age 9 months to 4 years)', 'category' => 'Equipment & Services', 'unit' => 'per day'],
    ['extra_id' => 6, 'name' => 'Booster Seat', 'price' => 7.50, 'description' => 'Suitable for children 15-36kg (Age 4 to 11 years)', 'category' => 'Equipment & Services', 'unit' => 'per day'],

    ['extra_id' => 7, 'name' => 'GPS Navigation', 'price' => 8.00, 'description' => 'Satellite navigation system', 'category' => 'Equipment & Services', 'unit' => 'per day'],
    ['extra_id' => 8, 'name' => 'Pre-paid Fuel', 'price' => 60.00, 'description' => 'Purchase a full tank and return empty', 'category' => 'Equipment & Services', 'unit' => 'one-time'],

    ['extra_id' => 9, 'name' => 'One-Way Rental Fee', 'price' => 45.00, 'description' => 'Drop off at a different location', 'category' => 'Equipment & Services', 'unit' => 'one-time'],
    ['extra_id' => 10, 'name' => 'Out-of-Hours Service', 'price' => 25.00, 'description' => 'Collection or return outside standard opening hours', 'category' => 'Equipment & Services', 'unit' => 'one-time'],

    ['extra_id' => 11, 'name' => 'Winter Tyres', 'price' => 12.00, 'description' => 'Winter tyres for snowy conditions', 'category' => 'Equipment & Services', 'unit' => 'per day'],
    ['extra_id' => 12, 'name' => 'Roadside Assistance', 'price' => 7.00, 'description' => '24/7 roadside assistance', 'category' => 'Equipment & Services', 'unit' => 'per day'],
];

$extrasByCategory = [];
foreach ($extras as $extra) {
    $category = $extra['category'];
    if (!isset($extrasByCategory[$category])) {
        $extrasByCategory[$category] = [];
    }
    $extrasByCategory[$category][] = $extra;
}

if (isset($_SESSION['error'])) {
    $errorMessage = $_SESSION['error'];
    unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
    $successMessage = $_SESSION['success'];
    unset($_SESSION['success']);
}

if (isset($_SESSION['selected_extras'])) {
    $selectedExtras = $_SESSION['selected_extras'];
}

if (isset($_POST['add_to_basket']) && isset($_POST['car_id'])) {
    $carId = $_POST['car_id'];
    $startDate = $_POST['start_date'] ?? date('Y-m-d');
    $endDate = $_POST['end_date'] ?? date('Y-m-d', strtotime('+1 day'));
    
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $rentalDays = $start->diff($end)->days;
    $rentalDays = $rentalDays > 0 ? $rentalDays : 1;
    
    $carQuery = $conn->prepare("SELECT price_per_day, status_id FROM cars WHERE car_id = ?");
    $carQuery->bind_param("i", $carId);
    $carQuery->execute();
    $carResult = $carQuery->get_result();
    
    if ($carResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Car not found!']);
        exit;
    }
    
    $carData = $carResult->fetch_assoc();
    $pricePerDay = $carData['price_per_day'];
    
    if ($carData['status_id'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Car is not available for rental!']);
        exit;
    }
    
    $estimatedTotal = $pricePerDay * $rentalDays;
    $depositAmount = $estimatedTotal * 0.2; // 20% deposit
    
    $basketQuery = $conn->prepare("SELECT basket_id FROM baskets WHERE customer_id = ? AND status = 'active'");
    $basketQuery->bind_param("i", $userId);
    $basketQuery->execute();
    $basketResult = $basketQuery->get_result();
    
    if ($basketResult->num_rows > 0) {
        $basketData = $basketResult->fetch_assoc();
        $basketId = $basketData['basket_id'];
    } else {
        $createBasketQuery = $conn->prepare("INSERT INTO baskets (customer_id, status) VALUES (?, 'active')");
        $createBasketQuery->bind_param("i", $userId);
        $createBasketQuery->execute();
        $basketId = $createBasketQuery->insert_id;
        $createBasketQuery->close();
    }
    $basketQuery->close();
    
    $checkItemQuery = $conn->prepare("SELECT item_id FROM basket_items WHERE basket_id = ? AND car_id = ?");
    $checkItemQuery->bind_param("ii", $basketId, $carId);
    $checkItemQuery->execute();
    $checkItemResult = $checkItemQuery->get_result();
    
    if ($checkItemResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Car is already in your basket!']);
        exit;
    }
    $checkItemQuery->close();
    
    $addItemQuery = $conn->prepare("
        INSERT INTO basket_items (basket_id, car_id, start_date, end_date, rental_days, deposit_amount, estimated_total) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $addItemQuery->bind_param("iissidd", $basketId, $carId, $startDate, $endDate, $rentalDays, $depositAmount, $estimatedTotal);
    
    if ($addItemQuery->execute()) {
        echo json_encode(['success' => true, 'message' => 'Car added to basket!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add car to basket!']);
    }
    
    $addItemQuery->close();
    exit;
}

if ($userRole === 'customer') {
    
    $basketStatus = ($currentStep == 'success') ? 'completed' : 'active';
    
    $basketQuery = $conn->prepare("
        SELECT b.basket_id, bi.item_id, bi.car_id, bi.start_date, bi.end_date, 
               bi.deposit_amount, bi.estimated_total, bi.rental_days,
               c.car_id, c.model, c.year, c.price_per_day, c.image_url,
               mk.make_name, ct.type_name, cs.status_name,
               ci.city_name
        FROM baskets b 
        LEFT JOIN basket_items bi ON b.basket_id = bi.basket_id 
        LEFT JOIN cars c ON bi.car_id = c.car_id
        LEFT JOIN makes mk ON c.make_id = mk.make_id
        LEFT JOIN car_types ct ON c.type_id = ct.type_id
        LEFT JOIN car_status cs ON c.status_id = cs.status_id
        LEFT JOIN cities ci ON c.city_id = ci.city_id
        WHERE b.customer_id = ? AND b.status = ?
        ORDER BY b.basket_id DESC
        LIMIT 1
    ");
    $basketQuery->bind_param("is", $userId, $basketStatus);
    $basketQuery->execute();
    $basketResult = $basketQuery->get_result();
    
    while ($item = $basketResult->fetch_assoc()) {
        if ($item['car_id']) {
            $basketItems[] = $item;
            $basketTotal += $item['estimated_total'];
            $basketCount++;
        }
    }
    $basketQuery->close();
    
    if (empty($basketItems) && $currentStep == 'success' && isset($_SESSION['booking_confirmation'])) {

        $basketItems = [];
    }
}


$cities = [];
$cityQuery = $conn->query("SELECT city_id, city_name FROM cities ORDER BY city_name");
if ($cityQuery->num_rows > 0) {
    while ($city = $cityQuery->fetch_assoc()) {
        $cities[] = $city;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_item'])) {
        $itemId = $_POST['item_id'];
        
        
        $deleteQuery = $conn->prepare("DELETE FROM basket_items WHERE item_id = ?");
        $deleteQuery->bind_param("i", $itemId);
        $deleteQuery->execute();
        $deleteQuery->close();
        
        $_SESSION['success'] = 'Item removed from basket';
        header('Location: basket.php');
        exit;
        
    } elseif (isset($_POST['update_rental_dates'])) {
        $itemId = $_POST['item_id'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        
       
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $rentalDays = $start->diff($end)->days;
        $rentalDays = $rentalDays > 0 ? $rentalDays : 1;
        
        
        $carQuery = $conn->prepare("
            SELECT c.price_per_day 
            FROM basket_items bi 
            JOIN cars c ON bi.car_id = c.car_id 
            WHERE bi.item_id = ?
        ");
        $carQuery->bind_param("i", $itemId);
        $carQuery->execute();
        $carResult = $carQuery->get_result();
        $carData = $carResult->fetch_assoc();
        $pricePerDay = $carData['price_per_day'];
        
        $estimatedTotal = $pricePerDay * $rentalDays;
        $depositAmount = $estimatedTotal * 0.2;
        
        
        $updateQuery = $conn->prepare("
            UPDATE basket_items 
            SET start_date = ?, end_date = ?, rental_days = ?, estimated_total = ?, deposit_amount = ?
            WHERE item_id = ?
        ");
        $updateQuery->bind_param("ssiddi", $startDate, $endDate, $rentalDays, $estimatedTotal, $depositAmount, $itemId);
        $updateQuery->execute();
        $updateQuery->close();
        
        $_SESSION['success'] = 'Rental dates updated';
        header('Location: basket.php');
        exit;
        
    } elseif (isset($_POST['save_rental_details'])) {
        
        $pickupLocation = $_POST['pickup_location'];
        $dropoffLocation = $_POST['dropoff_location'];
        $pickupDate = $_POST['pickup_date'];
        $pickupTime = $_POST['pickup_time'];
        $dropoffDate = $_POST['dropoff_date'];
        $dropoffTime = $_POST['dropoff_time'];
        
        $_SESSION['rental_details'] = [
            'pickup_location' => $pickupLocation,
            'dropoff_location' => $dropoffLocation,
            'pickup_date' => $pickupDate,
            'pickup_time' => $pickupTime,
            'dropoff_date' => $dropoffDate,
            'dropoff_time' => $dropoffTime
        ];
        
        header('Location: basket.php?step=3');
        exit;
        
    } elseif (isset($_POST['save_extras'])) {

        $selectedExtras = isset($_POST['extras']) ? $_POST['extras'] : [];
        $_SESSION['selected_extras'] = $selectedExtras;
        
        header('Location: basket.php?step=4');
        exit;
        
    } elseif (isset($_POST['save_confirmation'])) {

        $specialRequests = $_POST['special_requests'] ?? '';
        $_SESSION['special_requests'] = $specialRequests;
        
        header('Location: basket.php?step=5');
        exit;
        
    } elseif (isset($_POST['process_payment'])) {
        $paymentMethod = $_POST['payment_method'];
        $cardNumber = $_POST['card_number'] ?? '';
        $expiryDate = $_POST['expiry_date'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        $cardholderName = $_POST['cardholder_name'] ?? '';
        $specialRequests = $_SESSION['special_requests'] ?? '';
        

        $basketQuery = $conn->prepare("
            SELECT b.basket_id, bi.item_id, bi.car_id, bi.start_date, bi.end_date, 
                   bi.estimated_total, bi.deposit_amount, bi.rental_days
            FROM baskets b 
            JOIN basket_items bi ON b.basket_id = bi.basket_id 
            WHERE b.customer_id = ? AND b.status = 'active'
        ");
        $basketQuery->bind_param("i", $userId);
        $basketQuery->execute();
        $basketResult = $basketQuery->get_result();
        $basketItemsForBooking = [];
        
        while ($item = $basketResult->fetch_assoc()) {
            $basketItemsForBooking[] = $item;
        }
        $basketQuery->close();
        
        if (empty($basketItemsForBooking)) {
            $_SESSION['error'] = 'Your basket is empty or has already been processed!';
            header('Location: basket.php?step=4');
            exit;
        }
        
        $conn->begin_transaction();
        
        try {
            
            $statusQuery = $conn->query("SELECT booking_status_id FROM booking_status WHERE status_name = 'Confirmed' LIMIT 1");
            if ($statusQuery->num_rows > 0) {
                $statusData = $statusQuery->fetch_assoc();
                $bookingStatusId = $statusData['booking_status_id'];
            } else {
                $conn->query("INSERT INTO booking_status (status_name) VALUES ('Confirmed')");
                $bookingStatusId = $conn->insert_id;
            }
            
         
            $paymentMethodId = 1; 
            $paymentMethodsQuery = $conn->query("SELECT method_id FROM payment_methods LIMIT 1");
            if ($paymentMethodsQuery->num_rows > 0) {
                $paymentMethodData = $paymentMethodsQuery->fetch_assoc();
                $paymentMethodId = $paymentMethodData['method_id'];
            } else {
                
                $conn->query("INSERT INTO payment_methods (method_name) VALUES ('Credit Card')");
                $paymentMethodId = $conn->insert_id;
            }
            
            $paymentStatusId = 1; // Default to completed status
            $paymentStatusQuery = $conn->query("SELECT payment_status_id FROM payment_status WHERE status_name = 'Completed' LIMIT 1");
            if ($paymentStatusQuery->num_rows > 0) {
                $paymentStatusData = $paymentStatusQuery->fetch_assoc();
                $paymentStatusId = $paymentStatusData['payment_status_id'];
            } else {
                // Create completed status if not exists
                $conn->query("INSERT INTO payment_status (status_name) VALUES ('Completed')");
                $paymentStatusId = $conn->insert_id;
            }
            
            // Process each basket item as separate booking
            $bookingIds = [];
            $totalAmount = 0;

            foreach ($basketItemsForBooking as $item) {
                // Calculate total price per extra item based on rental period
                $extrasTotal = 0;
                if (!empty($selectedExtras)) {
                    foreach ($selectedExtras as $extraId) {
                        foreach ($extras as $extra) {
                            if ($extra['extra_id'] == $extraId) {
                                // Apply per-item rental days for accurate pricing
                                if ($extra['unit'] === 'per day') {
                                    $extrasTotal += ($extra['price'] * $item['rental_days']);
                                } else {
                                    $extrasTotal += $extra['price'];
                                }
                                break;
                            }
                        }
                    }
                }
        
                $itemTotal = $item['estimated_total'] + $extrasTotal;
                
                $bookingQuery = $conn->prepare("
                    INSERT INTO bookings (customer_id, car_id, start_date, end_date, total_cost, deposit_paid, booking_status_id, special_requests) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $bookingQuery->bind_param("iissddss", 
                    $userId, 
                    $item['car_id'], 
                    $item['start_date'], 
                    $item['end_date'], 
                    $itemTotal,
                    $item['deposit_amount'], 
                    $bookingStatusId,
                    $specialRequests
                );
                
                if ($bookingQuery->execute()) {
                    $bookingId = $bookingQuery->insert_id;
                    $bookingIds[] = $bookingId;
                    $totalAmount += $itemTotal;
                    
                    if (!empty($selectedExtras)) {
                        $selectedExtrasNames = [];
                        foreach ($selectedExtras as $extraId) {
                            foreach ($extras as $extra) {
                                if ($extra['extra_id'] == $extraId) {
                                    $selectedExtrasNames[] = $extra['name'] . ' (£' . $extra['price'] . ')';
                                    break;
                                }
                            }
                        }
                        $extrasText = "Extras: " . implode(', ', $selectedExtrasNames);
                        
                        $updateBookingQuery = $conn->prepare("
                            UPDATE bookings SET special_requests = CONCAT(COALESCE(special_requests, ''), ' | ', ?) WHERE booking_id = ?
                        ");
                        $updateBookingQuery->bind_param("si", $extrasText, $bookingId);
                        $updateBookingQuery->execute();
                        $updateBookingQuery->close();
                    }
                    
                    $paymentQuery = $conn->prepare("
                        INSERT INTO payments (booking_id, payment_method_id, payment_status_id, amount, payment_date) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $paymentQuery->bind_param("iiid", $bookingId, $paymentMethodId, $paymentStatusId, $itemTotal);
                    
                    if (!$paymentQuery->execute()) {
                        throw new Exception("Payment creation failed: " . $conn->error);
                    }
                    $paymentQuery->close();
                    
                    $updateCarQuery = $conn->prepare("UPDATE cars SET status_id = 2 WHERE car_id = ?");
                    $updateCarQuery->bind_param("i", $item['car_id']);
                    if (!$updateCarQuery->execute()) {
                        throw new Exception("Car status update failed: " . $conn->error);
                    }
                    $updateCarQuery->close();
                    
                    $bookingQuery->close();
                } else {
                    throw new Exception("Booking creation failed: " . $conn->error);
                }
            }
            
            $updateBasketQuery = $conn->prepare("
                UPDATE baskets SET status = 'completed' WHERE customer_id = ? AND status = 'active'
            ");
            $updateBasketQuery->bind_param("i", $userId);
            if (!$updateBasketQuery->execute()) {
                throw new Exception("Basket update failed: " . $conn->error);
            }
            $updateBasketQuery->close();
            
            $conn->commit();
            
            $_SESSION['booking_confirmation'] = [
                'booking_ids' => $bookingIds,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'extras_total' => $extrasTotal,
                'pickup_location' => $_SESSION['rental_details']['pickup_location'] ?? '',
                'pickup_date' => $_SESSION['rental_details']['pickup_date'] ?? '',
                'pickup_time' => $_SESSION['rental_details']['pickup_time'] ?? ''
            ];
            
            unset($_SESSION['selected_extras']);
            unset($_SESSION['rental_details']);
            unset($_SESSION['special_requests']);
            
            header('Location: basket.php?step=success');
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $_SESSION['error'] = 'Payment processing failed: ' . $e->getMessage();
            header('Location: basket.php?step=5');
            exit;
        }
    }
}

$basketTotal = 0;
foreach ($basketItems as $item) {
    $basketTotal += $item['estimated_total'];
}


$extrasTotal = 0;
if (!empty($selectedExtras) && !empty($basketItems)) {
    // Use the amount rental days from the first basket item
    $rentalDays = $basketItems[0]['rental_days'];
    
    foreach ($selectedExtras as $extraId) {
        foreach ($extras as $extra) {
            if ($extra['extra_id'] == $extraId) {
                // Multiply extras by the amount of days for items prices per day, flat rate used for items used once
                if ($extra['unit'] === 'per day') {
                    $extrasTotal += ($extra['price'] * $rentalDays);
                } else {
                    $extrasTotal += $extra['price'];
                }
                break;
            }
        }
    }
}

$grandTotal = $basketTotal + $extrasTotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Basket - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Extras */
        .extras-grid {
            display: flex;
            flex-direction: column;
            gap: 35px;
        }

        .extra-category {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 30px;
        }

        .category-title {
            font-size: 22px;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--vivid-indigo);
            color: var(--vivid-indigo);
            font-weight: 700;
        }
        
        .extra-option {
            background-color: white;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }

        .extra-option:last-child {
            margin-bottom: 0;
        }

        .extra-option:hover {
            border-color: var(--cobalt-blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .extra-option.selected {
            border-color: var(--vivid-indigo);
            background: rgba(140, 0, 80, 0.05);
            box-shadow: 0 4px 12px rgba(140, 0, 80, 0.15);
        }
        
        .extra-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
            gap: 20px;
        }
        
        .extra-name {
            font-weight: 700;
            color: #333;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .extra-price {
            text-align: right;
            flex-shrink: 0;
        }

        .price-amount {
            font-size: 24px;
            font-weight: bold;
            color: var(--vivid-indigo);
            line-height: 1;
        }

        .price-unit {
            font-size: 12px;
            color: #666;
            margin-top: 4px;
        }
        
        .extra-description {
            color: #666;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .extra-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .learn-more {
            color: var(--vivid-indigo);
            text-decoration: underline;
            font-size: 14px;
            cursor: pointer;
            transition: color 0.2s;
        }

        .learn-more:hover {
            color: var(--dark-magenta);
        }

        /* Progress Bar colour change */
        .step::after {
            content: '';
            position: absolute;
            top: 20px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #ddd; 
            z-index: 1;
            transition: background-color 0.3s ease;
        }

        .step:last-child::after {
            display: none;
        }

        .step.completed::after {
            background: var(--vivid-indigo);
        }
        .step.active::after {
            background: linear-gradient(to right, var(--vivid-indigo), #ddd);
            
        }

        .step {
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .step:hover {
            opacity: 0.8;
        }

        .basket-container {
            padding: 40px 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 80px);
        }
        
        .basket-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .basket-title {
            color: var(--vivid-indigo);
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .basket-subtitle {
            color: #666;
            font-size: 1.1rem;
        }
        
        .checkout-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 40px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            position: relative;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ddd;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-bottom: 10px;
            z-index: 2;
            transition: all 0.3s;
        }
        
        .step.active .step-number {
            background: var(--cobalt-blue);
            color: white;
        }
        
        .step.completed .step-number {
            background: var(--vivid-indigo);
            color: white;
        }
        
        .step-label {
            font-size: 0.9rem;
            color: #666;
            text-align: center;
            font-weight: 500;
        }
        
        .step.active .step-label {
            color: var(--vivid-indigo);
            font-weight: 600;
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .basket-items, .step-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
        }
        
        .section-title {
            color: var(--vivid-indigo);
            font-size: 1.5rem;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .basket-item {
            display: grid;
            grid-template-columns: 100px 1fr auto;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .basket-item:last-child {
            border-bottom: none;
        }
        
        .item-image {
            width: 100px;
            height: 70px;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details h3 {
            color: #333;
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .item-specs {
            display: flex;
            gap: 15px;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .item-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--vivid-indigo);
        }
        
        .item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 10px;
        }
        
        .remove-btn, .update-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .remove-btn {
            color: #dc3545;
        }
        
        .remove-btn:hover {
            background-color: #ffe6e6;
        }
        
        .update-btn {
            color: var(--cobalt-blue);
        }
        
        .update-btn:hover {
            background-color: #e6f0ff;
        }
        
        .rental-dates-form {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
        }
        
        .rental-dates-form.show {
            display: block;
        }
        
        .date-inputs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .basket-summary {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .summary-row.total {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--vivid-indigo);
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .checkout-btn {
            background: linear-gradient(to right, var(--cobalt-blue), var(--vivid-indigo));
            color: white;
            border: none;
            padding: 15px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0, 74, 173, 0.4);
            margin-top: 20px;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 74, 173, 0.5);
            color: white;
        }
        
        .checkout-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .empty-basket {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-basket i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
            display: block;
        }
        
        .empty-basket h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .step-content {
            display: none;
            margin-bottom: 30px;
        }
        
        .step-content.active {
            display: block;
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
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--cobalt-blue);
            outline: none;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .confirmation-details {
            background: #f8f8f8;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .detail-group {
            margin-bottom: 15px;
        }
        
        .detail-group:last-child {
            margin-bottom: 0;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--vivid-indigo);
            margin-bottom: 5px;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .payment-method {
            border: 2px solid #f0f0f0;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: var(--cobalt-blue);
        }
        
        .payment-method.selected {
            border-color: var(--vivid-indigo);
            background: rgba(140, 0, 80, 0.05);
        }
        
        .payment-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--vivid-indigo);
        }
        
        .payment-name {
            font-weight: 600;
            color: #333;
        }
        
        .step-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .btn-back {
            background: transparent;
            border: 1px solid var(--cobalt-blue);
            color: var(--cobalt-blue);
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-back:hover {
            background: rgba(0, 74, 173, 0.05);
        }
        
        .btn-next {
            background: var(--cobalt-blue);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-next:hover {
            background: var(--dark-magenta);
        }
        
        .success-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .success-icon {
            font-size: 4rem;
            color: #2ecc71;
            margin-bottom: 20px;
        }
        
        .success-title {
            color: var(--vivid-indigo);
            font-size: 2rem;
            margin-bottom: 15px;
        }
        
        .success-text {
            color: #666;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .basket-indicator {
            position: relative;
        }
        
        .basket-indicator a {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .basket-indicator a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: scale(1.1);
        }
        
        .basket-indicator a.animate {
            animation: basketPulse 0.6s ease;
        }
        
        @keyframes basketPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1.1); }
        }
        
        .basket-count {
            background: var(--cobalt-blue);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            margin-left: 5px;
            animation: countBounce 0.5s ease;
        }
        
        @keyframes countBounce {
            0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
            40% {transform: translateY(-5px);}
            60% {transform: translateY(-3px);}
        }

        .toast {
            position: fixed;
            top: 100px;
            right: 20px;
            background: var(--vivid-indigo);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: 300px;
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        .toast.success {
            background: #2ecc71;
        }
        
        .toast.error {
            background: #e74c3c;
        }

        @media (max-width: 992px) {
            .checkout-content {
                grid-template-columns: 1fr;
            }
            
            .basket-summary {
                position: static;
            }
        }
        
        @media (max-width: 768px) {
            .basket-title {
                font-size: 2rem;
            }
            
            .checkout-steps {
                flex-wrap: wrap;
                gap: 15px;
            }
            
            .step {
                flex: 0 0 calc(33.333% - 10px);
            }
            
            .step::after {
                display: none;
            }
            
            .basket-item {
                grid-template-columns: 80px 1fr;
            }
            
            .item-actions {
                grid-column: 1 / -1;
                flex-direction: row;
                justify-content: space-between;
                margin-top: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .date-inputs {
                grid-template-columns: 1fr;
            }
            
            .extras-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .basket-items,
            .basket-summary,
            .step-content {
                padding: 20px;
            }
            
            .step-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .btn-back,
            .btn-next {
                width: 100%;
                text-align: center;
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
                    
                    <li>
                        <?php if (isset($_SESSION['user'])): ?>
                            <a href="<?php echo $_SESSION['user']['role'] === 'admin' ? 'admin-dashboard.php' : 'customer-dashboard.php'; ?>">
                                Dashboard
                            </a>
                        <?php else: ?>
                            <a href="loginPage.php">Login</a>
                        <?php endif; ?>
                    </li>
                    <li><a href="#">🌐︎</a></li>
                    <li class="basket-indicator">
                        <a href="basket.php" id="basketLink">
                            <i class="fas fa-shopping-basket"></i>
                            <?php if ($basketCount > 0): ?>
                                <span class="basket-count"><?php echo $basketCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user'])): ?>
                    <li>
                        <a href="logout.php" style="color: #ff4444;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="toast" id="toast"></div>

    <section class="basket-container">
        <div class="basket-header">
            <h1 class="basket-title">Your Basket</h1>
            <p class="basket-subtitle">Review your selection and complete your booking</p>
        </div>


        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>


        <div class="checkout-steps">
            <div class="step <?php echo $currentStep == 1 ? 'active' : ($currentStep > 1 ? 'completed' : ''); ?>" data-step="1">
                <div class="step-number">1</div>
                <div class="step-label">Basket</div>
            </div>
            <div class="step <?php echo $currentStep == 2 ? 'active' : ($currentStep > 2 ? 'completed' : ''); ?>" data-step="2">
                <div class="step-number">2</div>
                <div class="step-label">Rental Details</div>
            </div>
            <div class="step <?php echo $currentStep == 3 ? 'active' : ($currentStep > 3 ? 'completed' : ''); ?>" data-step="3">
                <div class="step-number">3</div>
                <div class="step-label">Extras</div>
            </div>
            <div class="step <?php echo $currentStep == 4 ? 'active' : ($currentStep > 4 ? 'completed' : ''); ?>" data-step="4">
                <div class="step-number">4</div>
                <div class="step-label">Confirmation</div>
            </div>
            <div class="step <?php echo $currentStep == 5 ? 'active' : ($currentStep > 5 || $currentStep == 'success' ? 'completed' : ''); ?>" data-step="5">
                <div class="step-number">5</div>
                <div class="step-label">Payment</div>
            </div>
        </div>

        <div class="checkout-content">

            <div class="step-content <?php echo $currentStep == 1 ? 'active' : ''; ?>" id="step1">
                <h2 class="section-title">Your Selected Cars</h2>
                <div id="basketItems">
                    <?php if (empty($basketItems)): ?>
                        <div class="empty-basket">
                            <i class="fas fa-shopping-basket"></i>
                            <h3>Your basket is empty</h3>
                            <p>Add some cars to your basket to get started!</p>
                            <a href="cars.php" class="checkout-btn" style="display: inline-block; width: auto; padding: 12px 30px; margin-top: 20px;">Browse Cars</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($basketItems as $item): ?>
                            <div class="basket-item" data-id="<?php echo $item['item_id']; ?>">
                                <div class="item-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model']); ?>">
                                </div>
                                <div class="item-details">
                                    <h3><?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model'] . ' (' . $item['year'] . ')'); ?></h3>
                                    <div class="item-specs">
                                        <span><?php echo htmlspecialchars($item['type_name']); ?></span>
                                        <span><?php echo htmlspecialchars($item['city_name']); ?></span>
                                    </div>
                                    <div class="item-price">£<?php echo number_format($item['price_per_day'], 2); ?>/day</div>
                                    <div class="item-specs">
                                        <span>Rental Days: <?php echo $item['rental_days']; ?></span>
                                        <span>Total: £<?php echo number_format($item['estimated_total'], 2); ?></span>
                                    </div>
                                    

                                    <div class="rental-dates-form" id="datesForm-<?php echo $item['item_id']; ?>">
                                        <form method="POST" class="date-inputs">
                                            <input type="hidden" name="update_rental_dates" value="1">
                                            <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                            <div>
                                                <label>Start Date:</label>
                                                <input type="date" name="start_date" value="<?php echo $item['start_date']; ?>" required>
                                            </div>
                                            <div>
                                                <label>End Date:</label>
                                                <input type="date" name="end_date" value="<?php echo $item['end_date']; ?>" required>
                                            </div>
                                            <button type="submit" class="update-btn">
                                                <i class="fas fa-save"></i> Update Dates
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="item-actions">
                                    <button type="button" class="update-btn" onclick="toggleDatesForm(<?php echo $item['item_id']; ?>)">
                                        <i class="fas fa-calendar-alt"></i> Update Dates
                                    </button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="remove_item" value="1">
                                        <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                                        <button type="submit" class="remove-btn">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="step-content <?php echo $currentStep == 2 ? 'active' : ''; ?>" id="step2">
                <h2 class="section-title">Rental Details</h2>
                <form method="POST">
                    <input type="hidden" name="save_rental_details" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pickupLocation">Pick-up Location *</label>
                            <select id="pickupLocation" name="pickup_location" required>
                                <option value="">Select location</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo $city['city_id']; ?>" <?php echo isset($_SESSION['rental_details']['pickup_location']) && $_SESSION['rental_details']['pickup_location'] == $city['city_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($city['city_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="dropoffLocation">Drop-off Location *</label>
                            <select id="dropoffLocation" name="dropoff_location" required>
                                <option value="">Select location</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?php echo $city['city_id']; ?>" <?php echo isset($_SESSION['rental_details']['dropoff_location']) && $_SESSION['rental_details']['dropoff_location'] == $city['city_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($city['city_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="pickupDate">Pick-up Date *</label>
                            <input type="date" id="pickupDate" name="pickup_date" value="<?php echo isset($_SESSION['rental_details']['pickup_date']) ? $_SESSION['rental_details']['pickup_date'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="pickupTime">Pick-up Time *</label>
                            <input type="time" id="pickupTime" name="pickup_time" value="<?php echo isset($_SESSION['rental_details']['pickup_time']) ? $_SESSION['rental_details']['pickup_time'] : '10:00'; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="dropoffDate">Drop-off Date *</label>
                            <input type="date" id="dropoffDate" name="dropoff_date" value="<?php echo isset($_SESSION['rental_details']['dropoff_date']) ? $_SESSION['rental_details']['dropoff_date'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="dropoffTime">Drop-off Time *</label>
                            <input type="time" id="dropoffTime" name="dropoff_time" value="<?php echo isset($_SESSION['rental_details']['dropoff_time']) ? $_SESSION['rental_details']['dropoff_time'] : '10:00'; ?>" required>
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <a href="basket.php?step=1" class="btn-back">Back to Basket</a>
                        <button type="submit" class="btn-next">Continue to Extras</button>
                    </div>
                </form>
            </div>

            <div class="step-content <?php echo $currentStep == 3 ? 'active' : ''; ?>" id="step3">
                <h2 class="section-title">Additional Services & Extras</h2>
                <p style="margin-bottom: 30px; color: #666;">Enhance your rental experience with these optional extras</p>
                
                <form method="POST" id="extrasForm">
                    <input type="hidden" name="save_extras" value="1">
                    
                    <div class="extras-grid" id="extrasGrid">
                        <?php foreach ($extrasByCategory as $category => $categoryExtras): ?>
                            <div class="extra-category">
                                <div class="category-title"><?php echo htmlspecialchars($category); ?></div>
                                
                                <?php foreach ($categoryExtras as $extra): ?>
                                    <div class="extra-option <?php echo in_array($extra['extra_id'], $selectedExtras) ? 'selected' : ''; ?>" 
                                         data-id="<?php echo $extra['extra_id']; ?>"
                                         data-price="<?php echo $extra['price']; ?>"
                                         data-unit="<?php echo $extra['unit']; ?>">
                                        <div class="extra-header">
                                            <div style="flex: 1;">
                                                <div class="extra-name"><?php echo htmlspecialchars($extra['name']); ?></div>
                                                <div class="extra-description"><?php echo htmlspecialchars($extra['description']); ?></div>
                                            </div>
                                            <div class="extra-price">
                                                <div class="price-amount">£<?php echo number_format($extra['price'], 2); ?></div>
                                                <div class="price-unit"><?php echo htmlspecialchars($extra['unit']); ?></div>
                                            </div>
                                        </div>
                                        <input type="checkbox" name="extras[]" value="<?php echo $extra['extra_id']; ?>" 
                                               <?php echo in_array($extra['extra_id'], $selectedExtras) ? 'checked' : ''; ?> 
                                               style="display: none;">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="step-actions">
                        <a href="basket.php?step=2" class="btn-back">Back to Rental Details</a>
                        <button type="submit" class="btn-next">Continue to Confirmation</button>
                    </div>
                </form>
            </div>
            <div class="step-content <?php echo $currentStep == 4 ? 'active' : ''; ?>" id="step4">
                <h2 class="section-title">Booking Confirmation</h2>
                <p style="margin-bottom: 25px; color: #666;">Please review your booking details before proceeding to payment</p>
                
                <div class="confirmation-details" style="margin-bottom: 30px;">
                    <h3 style="color: var(--vivid-indigo); margin-bottom: 20px;">Booking Summary</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <div class="detail-group">
                                <div class="detail-label">Pick-up Location</div>
                                <div>
                                    <?php 
                                    $pickupCity = 'Not specified';
                                    if (isset($_SESSION['rental_details']['pickup_location'])) {
                                        foreach ($cities as $city) {
                                            if ($city['city_id'] == $_SESSION['rental_details']['pickup_location']) {
                                                $pickupCity = $city['city_name'];
                                                break;
                                            }
                                        }
                                    }
                                    echo htmlspecialchars($pickupCity);
                                    ?>
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Pick-up Date & Time</div>
                                <div>
                                    <?php 
                                    $pickupDateTime = 'Not specified';
                                    if (isset($_SESSION['rental_details']['pickup_date'])) {
                                        $pickupDateTime = htmlspecialchars($_SESSION['rental_details']['pickup_date']);
                                        if (isset($_SESSION['rental_details']['pickup_time'])) {
                                            $pickupDateTime .= ' ' . htmlspecialchars($_SESSION['rental_details']['pickup_time']);
                                        }
                                    }
                                    echo $pickupDateTime;
                                    ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="detail-group">
                                <div class="detail-label">Drop-off Location</div>
                                <div>
                                    <?php 
                                    $dropoffCity = 'Not specified';
                                    if (isset($_SESSION['rental_details']['dropoff_location'])) {
                                        foreach ($cities as $city) {
                                            if ($city['city_id'] == $_SESSION['rental_details']['dropoff_location']) {
                                                $dropoffCity = $city['city_name'];
                                                break;
                                            }
                                        }
                                    }
                                    echo htmlspecialchars($dropoffCity);
                                    ?>
                                </div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Drop-off Date & Time</div>
                                <div>
                                    <?php 
                                    $dropoffDateTime = 'Not specified';
                                    if (isset($_SESSION['rental_details']['dropoff_date'])) {
                                        $dropoffDateTime = htmlspecialchars($_SESSION['rental_details']['dropoff_date']);
                                        if (isset($_SESSION['rental_details']['dropoff_time'])) {
                                            $dropoffDateTime .= ' ' . htmlspecialchars($_SESSION['rental_details']['dropoff_time']);
                                        }
                                    }
                                    echo $dropoffDateTime;
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-group">
                        <div class="detail-label">Selected Cars & Pricing</div>
                        <div style="margin-top: 10px;">
                            <?php foreach ($basketItems as $item): ?>
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                                    <span><?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model'] . ' (' . $item['year'] . ')'); ?></span>
                                    <span style="font-weight: 600;">£<?php echo number_format($item['estimated_total'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if ($extrasTotal > 0): ?>
                                <div style="display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee;">
                                    <span>Additional Extras</span>
                                    <span style="font-weight: 600;">£<?php echo number_format($extrasTotal, 2); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div style="display: flex; justify-content: space-between; padding: 12px 0; font-weight: 700; font-size: 1.1rem; color: var(--vivid-indigo);">
                                <span>Total Amount</span>
                                <span>£<?php echo number_format($grandTotal, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!empty($selectedExtras)): ?>
                        <div class="detail-group">
                            <div class="detail-label">Selected Extras</div>
                            <div>
                                <?php 
                                $extraNames = [];
                                foreach ($selectedExtras as $extraId) {
                                    foreach ($extras as $extra) {
                                        if ($extra['extra_id'] == $extraId) {
                                            $extraNames[] = $extra['name'] . ' (£' . number_format($extra['price'], 2) . ')';
                                            break;
                                        }
                                    }
                                }
                                echo implode(', ', $extraNames);
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <form method="POST" id="confirmationForm">
                    <input type="hidden" name="save_confirmation" value="1">
                    
                    <div class="form-group">
                        <label for="specialRequests">Special Requests (Optional)</label>
                        <textarea id="specialRequests" name="special_requests" placeholder="Any special requirements or requests..." rows="4"><?php echo isset($_SESSION['special_requests']) ? htmlspecialchars($_SESSION['special_requests']) : ''; ?></textarea>
                    </div>
                    
                    <div class="step-actions">
                        <a href="basket.php?step=3" class="btn-back">Back to Extras</a>
                        <button type="submit" class="btn-next">Proceed to Payment</button>
                    </div>
                </form>
            </div>

            <div class="step-content <?php echo $currentStep == 5 ? 'active' : ''; ?>" id="step5">
                <h2 class="section-title">Payment Details</h2>
                
                <form method="POST" id="paymentForm">
                    <input type="hidden" name="process_payment" value="1">
                    
                    <div class="payment-methods">
                        <div class="payment-method" data-method="card">
                            <div class="payment-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="payment-name">Credit/Debit Card</div>
                        </div>
                    </div>
                    
                    <input type="hidden" name="payment_method" id="paymentMethod" required>
                    
                    <div id="cardPaymentForm" style="display: none; margin-top: 25px;">
                        <div class="form-group">
                            <label for="cardNumber">Card Number *</label>
                            <input type="text" id="cardNumber" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiryDate">Expiry Date *</label>
                                <input type="text" id="expiryDate" name="expiry_date" placeholder="MM/YY" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV *</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="3">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="cardholderName">Cardholder Name *</label>
                            <input type="text" id="cardholderName" name="cardholder_name" placeholder="John Smith">
                        </div>
                    </div>
                    
                    <div class="step-actions">
                        <a href="basket.php?step=4" class="btn-back">Back to Confirmation</a>
                        <button type="submit" class="btn-next">Complete Booking</button>
                    </div>
                </form>
            </div>

            <div class="step-content <?php echo $currentStep == 'success' ? 'active' : ''; ?>" id="successStep">
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="success-title">Booking Confirmed!</h2>
                    <p class="success-text">Thank you for your booking. Your reservation has been confirmed and a confirmation email has been sent to you.</p>
                    <?php if (isset($_SESSION['booking_confirmation'])): ?>
                        <div class="confirmation-details">
                            <div class="detail-group">
                                <div class="detail-label">Booking Reference</div>
                                <div>#<?php echo implode(', ', $_SESSION['booking_confirmation']['booking_ids']); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Total Amount</div>
                                <div>£<?php echo number_format($_SESSION['booking_confirmation']['total_amount'], 2); ?></div>
                            </div>
                            <div class="detail-group">
                                <div class="detail-label">Pick-up</div>
                                <div>
                                    <?php 
                                    $pickupCity = 'Not specified';
                                    if (isset($_SESSION['booking_confirmation']['pickup_location'])) {
                                        foreach ($cities as $city) {
                                            if ($city['city_id'] == $_SESSION['booking_confirmation']['pickup_location']) {
                                                $pickupCity = $city['city_name'];
                                                break;
                                            }
                                        }
                                    }
                                    echo htmlspecialchars($pickupCity) . ' - ' . 
                                         htmlspecialchars($_SESSION['booking_confirmation']['pickup_date'] ?? '') . ' ' . 
                                         htmlspecialchars($_SESSION['booking_confirmation']['pickup_time'] ?? '');
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="step-actions" style="justify-content: center;">
                        <a href="customer-dashboard.php" class="btn-next">View My Bookings</a>
                        <a href="cars.php" class="btn-back">Book Another Car</a>
                    </div>
                </div>
            </div>

            <div class="basket-summary">
                <h2 class="section-title">
                    <?php echo $currentStep == 'success' ? 'Booking Complete' : 'Booking Summary'; ?>
                </h2>
                <div id="summaryContent">
                    <?php if ($currentStep == 'success' && isset($_SESSION['booking_confirmation'])): ?>
                        
                        <div class="summary-row">
                            <span>Status</span>
                            <span style="color: #2ecc71; font-weight: 600;">Confirmed</span>
                        </div>
                        <div class="summary-row">
                            <span>Booking Reference</span>
                            <span>#<?php echo implode(', ', $_SESSION['booking_confirmation']['booking_ids']); ?></span>
                        </div>
                        <div class="summary-row total">
                            <span>Total Paid</span>
                            <span>£<?php echo number_format($_SESSION['booking_confirmation']['total_amount'], 2); ?></span>
                        </div>
                    <?php elseif (!empty($basketItems)): ?>
                        
                        <?php foreach ($basketItems as $item): ?>
                            <div class="summary-row">
                                <span><?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model']); ?></span>
                                <span>£<?php echo number_format($item['estimated_total'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($extrasTotal > 0): ?>
                            <div class="summary-row">
                                <span>Extras</span>
                                <span>£<?php echo number_format($extrasTotal, 2); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>£<?php echo number_format($grandTotal, 2); ?></span>
                        </div>
                    <?php else: ?>
                        <p>Your basket is empty</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($currentStep == 'success'): ?>
                  
                    <a href="customer-dashboard.php" class="checkout-btn">View My Bookings</a>
                    <a href="cars.php" class="checkout-btn" style="margin-top: 10px; background: var(--vivid-indigo);">Book Another Car</a>
                <?php elseif (!empty($basketItems)): ?>
                
                    <?php if ($currentStep == 1): ?>
                        <a href="basket.php?step=2" class="checkout-btn">Continue to Rental Details</a>
                    <?php else: ?>
                        <a href="basket.php?step=1" class="checkout-btn">Back to Basket</a>
                    <?php endif; ?>
                <?php else: ?>
                    <button class="checkout-btn" disabled>Continue to Rental Details</button>
                <?php endif; ?>
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
            const currentStep = <?php echo is_numeric($currentStep) ? $currentStep : 0; ?>;
            
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                const stepNumber = index + 1;
                
                if (stepNumber <= currentStep) {
                    step.style.cursor = 'pointer';
                    
                    step.addEventListener('click', function() {
                        window.location.href = 'basket.php?step=' + stepNumber;
                    });
                    
                    step.addEventListener('mouseenter', function() {
                        this.style.opacity = '0.7';
                    });
                    
                    step.addEventListener('mouseleave', function() {
                        this.style.opacity = '1';
                    });
                } else {

                    step.style.cursor = 'not-allowed';
                    step.style.opacity = '0.6';
                }
            });
            
            const today = new Date().toISOString().split('T')[0];
            const pickupDate = document.getElementById('pickupDate');
            const dropoffDate = document.getElementById('dropoffDate');
            
            if (pickupDate) {
                pickupDate.min = today;
                pickupDate.addEventListener('change', function() {
                    if (dropoffDate) {
                        dropoffDate.min = this.value;
                    }
                });
            }
            
            if (dropoffDate) {
                dropoffDate.min = today;
            }
            
            const paymentMethods = document.querySelectorAll('.payment-method');
            const paymentMethodInput = document.getElementById('paymentMethod');
            const cardPaymentForm = document.getElementById('cardPaymentForm');
            
            paymentMethods.forEach(method => {
                method.addEventListener('click', function() {
                    paymentMethods.forEach(m => m.classList.remove('selected'));
                    this.classList.add('selected');
                    const selectedMethod = this.getAttribute('data-method');
                    paymentMethodInput.value = selectedMethod;
                    
                    if (cardPaymentForm) {
                        cardPaymentForm.style.display = selectedMethod === 'card' ? 'block' : 'none';
                    }
                });
            });
            
            const extrasGrid = document.getElementById('extrasGrid');
            if (extrasGrid) {
                extrasGrid.addEventListener('click', function(e) {
                    const extraOption = e.target.closest('.extra-option');
                    if (extraOption) {
                        const checkbox = extraOption.querySelector('input[type="checkbox"]');
                        
                        if (extraOption.classList.contains('selected')) {
                            extraOption.classList.remove('selected');
                            checkbox.checked = false;
                        } else {
                            extraOption.classList.add('selected');
                            checkbox.checked = true;
                        }
                    }
                });
            }
            
            const cardNumberInput = document.getElementById('cardNumber');
            if (cardNumberInput) {
                cardNumberInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    let matches = value.match(/\d{4,16}/g);
                    let match = matches && matches[0] || '';
                    let parts = [];
                    
                    for (let i = 0, len = match.length; i < len; i += 4) {
                        parts.push(match.substring(i, i + 4));
                    }
                    
                    if (parts.length) {
                        e.target.value = parts.join(' ');
                    } else {
                        e.target.value = value;
                    }
                });
            }
            
            const expiryDateInput = document.getElementById('expiryDate');
            if (expiryDateInput) {
                expiryDateInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length >= 2) {
                        e.target.value = value.substring(0, 2) + '/' + value.substring(2, 4);
                    }
                });
            }
            
            const paymentForm = document.getElementById('paymentForm');
            if (paymentForm) {
                paymentForm.addEventListener('submit', function(e) {
                    const paymentMethod = paymentMethodInput.value;
                    
                    if (!paymentMethod) {
                        e.preventDefault();
                        alert('Please select a payment method');
                        return;
                    }
                    
                    if (paymentMethod === 'card') {
                        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
                        const expiryDate = document.getElementById('expiryDate').value;
                        const cvv = document.getElementById('cvv').value;
                        const cardholderName = document.getElementById('cardholderName').value;
                        
                        if (!cardNumber || !expiryDate || !cvv || !cardholderName) {
                            e.preventDefault();
                            alert('Please fill in all card details');
                            return;
                        }
                        
                        if (cardNumber.length !== 16) {
                            e.preventDefault();
                            alert('Please enter a valid 16-digit card number');
                            return;
                        }
                        
                        if (!/^\d{2}\/\d{2}$/.test(expiryDate)) {
                            e.preventDefault();
                            alert('Please enter expiry date in MM/YY format');
                            return;
                        }
                        
                        if (cvv.length !== 3) {
                            e.preventDefault();
                            alert('Please enter a valid 3-digit CVV');
                            return;
                        }
                    }
                });
            }

            const basketLink = document.getElementById('basketLink');
            if (basketLink) {
                basketLink.addEventListener('click', function(e) {
                    this.classList.add('animate');
                    setTimeout(() => {
                        this.classList.remove('animate');
                    }, 600);
                });
            }

            function showToast(message, type = 'success') {
                const toast = document.getElementById('toast');
                toast.textContent = message;
                toast.className = `toast ${type} show`;
                
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 3000);
            }

            window.addToBasket = function(carId, carName) {
                fetch('basket.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `add_to_basket=1&car_id=${carId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(`${carName} added to basket!`, 'success');
                        
                        if (basketLink) {
                            basketLink.classList.add('animate');
                            setTimeout(() => {
                                basketLink.classList.remove('animate');
                            }, 600);
                        }
                        
                        let basketCount = basketLink.querySelector('.basket-count');
                        if (basketCount) {
                            let count = parseInt(basketCount.textContent) + 1;
                            basketCount.textContent = count;
                        } else {
                            basketCount = document.createElement('span');
                            basketCount.className = 'basket-count';
                            basketCount.textContent = '1';
                            basketLink.appendChild(basketCount);
                        }
                    } else {
                        showToast(data.message || 'Failed to add car to basket', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error adding car to basket', 'error');
                });
            };
        });

        function toggleDatesForm(itemId) {
            const form = document.getElementById('datesForm-' + itemId);
            form.classList.toggle('show');
        }
    </script>
</body>
</html>
<?php

$conn->close();


?>

