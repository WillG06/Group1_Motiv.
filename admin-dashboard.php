<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: loginPage.php');
    exit;
}

// Get user preferences from cookies
$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : 'light';
$fontSize = isset($_COOKIE['fontSize']) ? $_COOKIE['fontSize'] : '100';
$language = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en';

// Language variables for the settings dropdown
$themeText = 'Theme';
$lightText = 'Light';
$darkText = 'Dark';
$fontSizeText = 'Font Size';
$resetText = 'Reset';
$languageText = 'Language';

$user = $_SESSION['user'];
$adminId = $user['id'];

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_car'])) {
    $makeId = $_POST['make_id'];
    $model = trim($_POST['model']);
    $year = $_POST['year'];
    $typeId = $_POST['type_id'];
    $pricePerDay = $_POST['price_per_day'];
    $depositRequired = $_POST['deposit_required'] ?? 0;
    $description = trim($_POST['description']);
    $statusId = $_POST['status_id'];
    $cityId = $_POST['city_id'];
    
    // --- Multiple Image Upload Handling ---
    $uploadedImages = []; // To store paths of successfully uploaded images
    $uploadErrors = [];
    
    // Check if files were uploaded
    if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
        $uploadDir = 'uploads/cars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFiles = 5; // Maximum number of images
        $files = $_FILES['car_images'];
        
        // Count how many files were actually selected (non-empty names)
        $fileCount = 0;
        foreach ($files['name'] as $name) {
            if (!empty($name)) $fileCount++;
        }
        
        if ($fileCount > $maxFiles) {
            $errorMessage = "You can upload a maximum of 5 images.";
        } else {
            for ($i = 0; $i < count($files['name']); $i++) {
                if (empty($files['name'][$i])) continue;
                
                $fileError = $files['error'][$i];
                $tmpName = $files['tmp_name'][$i];
                $originalName = $files['name'][$i];
                
                if ($fileError !== UPLOAD_ERR_OK) {
                    $uploadErrors[] = "Error uploading file: $originalName. Error code: $fileError";
                    continue;
                }
                
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $uploadErrors[] = "File $originalName: Only JPG, JPEG, PNG, GIF & WebP files are allowed.";
                    continue;
                }
                
                // Validate image
                $check = getimagesize($tmpName);
                if ($check === false) {
                    $uploadErrors[] = "File $originalName is not a valid image.";
                    continue;
                }
                
                // Generate unique filename
                $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $model) . '_' . ($i+1) . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedImages[] = $targetPath;
                } else {
                    $uploadErrors[] = "Sorry, there was an error uploading $originalName.";
                }
            }
        }
    }
    
    if (empty($model) || empty($year) || empty($pricePerDay)) {
        $errorMessage = "Please fill in all required fields.";
    } elseif (!empty($uploadErrors)) {
        $errorMessage = implode(" ", $uploadErrors);
    } else {
        // First, insert the car without images to get the car_id
        $insertQuery = $conn->prepare("
            INSERT INTO cars (agent_id, make_id, model, year, type_id, price_per_day, 
                             deposit_required, description, status_id, city_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertQuery->bind_param("iisiiidsii", 
            $adminId, $makeId, $model, $year, $typeId, $pricePerDay, 
            $depositRequired, $description, $statusId, $cityId
        );
        
        if ($insertQuery->execute()) {
            $carId = $insertQuery->insert_id;
            $insertQuery->close();
            
            // Now insert the images into car_images table if table exists
            if (!empty($uploadedImages)) {
                // Check if car_images table exists
                $tableCheck = $conn->query("SHOW TABLES LIKE 'car_images'");
                if ($tableCheck->num_rows > 0) {
                    $imageInsertQuery = $conn->prepare("INSERT INTO car_images (car_id, image_url, display_order) VALUES (?, ?, ?)");
                    $displayOrder = 1;
                    foreach ($uploadedImages as $imagePath) {
                        $imageInsertQuery->bind_param("isi", $carId, $imagePath, $displayOrder);
                        if (!$imageInsertQuery->execute()) {
                            $errorMessage = "Error adding images: " . $conn->error;
                            break;
                        }
                        $displayOrder++;
                    }
                    $imageInsertQuery->close();
                } else {
                    // If table doesn't exist, store only first image in cars table (backward compatibility)
                    $updateCarImage = $conn->prepare("UPDATE cars SET image_url = ? WHERE car_id = ?");
                    $firstImage = $uploadedImages[0];
                    $updateCarImage->bind_param("si", $firstImage, $carId);
                    $updateCarImage->execute();
                    $updateCarImage->close();
                    $errorMessage = "Note: car_images table doesn't exist. Only one image was saved to the cars table.";
                }
            }
            
            // If no errors occurred during image insertion, set success message
            if (empty($errorMessage) || strpos($errorMessage, 'Note:') !== false) {
                $successMessage = "Car added successfully with " . count($uploadedImages) . " images!";
                if (strpos($errorMessage, 'Note:') !== false) {
                    $successMessage .= " " . $errorMessage;
                    $errorMessage = '';
                }
            }
        } else {
            $errorMessage = "Error adding car: " . $conn->error;
            $insertQuery->close();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_car_status'])) {
    $carId = $_POST['car_id'];
    $newStatus = $_POST['new_status'];
    
    $updateQuery = $conn->prepare("UPDATE cars SET status_id = ? WHERE car_id = ?");
    $updateQuery->bind_param("ii", $newStatus, $carId);
    
    if ($updateQuery->execute()) {
        $successMessage = "Car status updated successfully!";
    } else {
        $errorMessage = "Error updating car status: " . $conn->error;
    }
    $updateQuery->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $bookingId = $_POST['booking_id'];
    
    error_log("Cancellation attempt for booking ID: " . $bookingId);
    
    $carQuery = $conn->prepare("SELECT car_id FROM bookings WHERE booking_id = ?");
    $carQuery->bind_param("i", $bookingId);
    $carQuery->execute();
    $carResult = $carQuery->get_result();
    
    if ($carResult->num_rows > 0) {
        $carData = $carResult->fetch_assoc();
        $carId = $carData['car_id'];
        error_log("Found car ID: " . $carId);
        
        $statusQuery = $conn->prepare("SELECT booking_status_id, status_name FROM booking_status");
        $statusQuery->execute();
        $statusResult = $statusQuery->get_result();
        
        error_log("Available statuses in booking_status table:");
        $statuses = [];
        while ($status = $statusResult->fetch_assoc()) {
            $statuses[] = $status;
            error_log("ID: " . $status['booking_status_id'] . " - Name: '" . $status['status_name'] . "'");
        }
        
        $cancelledStatusId = null;
        $variations = ['cancelled', 'canceled', 'Cancelled', 'Canceled'];
        
        foreach ($variations as $variation) {
            $checkQuery = $conn->prepare("SELECT booking_status_id FROM booking_status WHERE status_name = ?");
            $checkQuery->bind_param("s", $variation);
            $checkQuery->execute();
            $checkResult = $checkQuery->get_result();
            
            if ($checkResult->num_rows > 0) {
                $cancelledStatusId = $checkResult->fetch_assoc()['booking_status_id'];
                error_log("Found cancelled status with ID: " . $cancelledStatusId . " for variation: '" . $variation . "'");
                break;
            }
            $checkQuery->close();
        }
        
        if ($cancelledStatusId) {
            $updateQuery = $conn->prepare("UPDATE bookings SET booking_status_id = ? WHERE booking_id = ?");
            $updateQuery->bind_param("ii", $cancelledStatusId, $bookingId);
            
            if ($updateQuery->execute()) {
                $carUpdateQuery = $conn->prepare("UPDATE cars SET status_id = 1 WHERE car_id = ?");
                $carUpdateQuery->bind_param("i", $carId);
                $carUpdateQuery->execute();
                $carUpdateQuery->close();
                
                $successMessage = "Booking cancelled successfully! The car is now available again.";
                error_log("Booking cancelled successfully");
            } else {
                $errorMessage = "Error cancelling booking: " . $conn->error;
                error_log("Error updating booking: " . $conn->error);
            }
            $updateQuery->close();
        } else {
            $errorMessage = "Cancelled status not found in booking_status table. Available statuses: " . implode(', ', array_column($statuses, 'status_name'));
            error_log("Cancelled status not found");
        }
        $statusQuery->close();
    } else {
        $errorMessage = "Booking not found.";
        error_log("Booking not found for ID: " . $bookingId);
    }
    $carQuery->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_city'])) {
        $cityName = trim($_POST['city_name']);
        $region = trim($_POST['region']);
        
        $insertQuery = $conn->prepare("INSERT INTO cities (city_name, region) VALUES (?, ?)");
        $insertQuery->bind_param("ss", $cityName, $region);
        
        if ($insertQuery->execute()) {
            $successMessage = "City added successfully!";
        } else {
            $errorMessage = "Error adding city: " . $conn->error;
        }
        $insertQuery->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inquiry_status'])) {
    $inquiryId = $_POST['inquiry_id'];
    $newStatus = $_POST['new_status'];
    
    $updateQuery = $conn->prepare("UPDATE contact_inquiries SET status = ? WHERE inquiry_id = ?");
    $updateQuery->bind_param("si", $newStatus, $inquiryId);
    
    if ($updateQuery->execute()) {
        $successMessage = "Inquiry status updated successfully!";
    } else {
        $errorMessage = "Error updating inquiry status: " . $conn->error;
    }
    $updateQuery->close();
}

$stats = [];

$revenueQuery = $conn->query("
    SELECT COALESCE(SUM(total_cost), 0) as total_revenue 
    FROM bookings 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
if ($revenueQuery) {
    $stats['revenue'] = $revenueQuery->fetch_assoc()['total_revenue'];
} else {
    $stats['revenue'] = 0;
}

$customersQuery = $conn->query("SELECT COUNT(*) as total_customers FROM customers");
if ($customersQuery) {
    $stats['customers'] = $customersQuery->fetch_assoc()['total_customers'];
} else {
    $stats['customers'] = 0;
}

$bookingsQuery = $conn->query("
    SELECT COUNT(*) as monthly_bookings 
    FROM bookings 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
if ($bookingsQuery) {
    $stats['bookings'] = $bookingsQuery->fetch_assoc()['monthly_bookings'];
} else {
    $stats['bookings'] = 0;
}

$inquiriesQuery = $conn->query("
    SELECT COUNT(*) as new_inquiries 
    FROM contact_inquiries 
    WHERE status = 'new' 
    AND DATE(created_at) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
");
if ($inquiriesQuery) {
    $stats['inquiries'] = $inquiriesQuery->fetch_assoc()['new_inquiries'];
} else {
    $stats['inquiries'] = 0;
}

$carsQuery = $conn->query("SELECT COUNT(*) as total_cars FROM cars");
if ($carsQuery) {
    $stats['cars'] = $carsQuery->fetch_assoc()['total_cars'];
} else {
    $stats['cars'] = 0;
}

$bookingStatuses = [];
$statusQuery = $conn->query("SELECT booking_status_id, status_name FROM booking_status ORDER BY booking_status_id");
if ($statusQuery) {
    while ($status = $statusQuery->fetch_assoc()) {
        $bookingStatuses[] = $status;
    }
}

$recentBookings = [];
$bookingsData = $conn->query("
    SELECT b.booking_id, b.start_date, b.end_date, b.total_cost, 
           bs.booking_status_id, bs.status_name,
           c.customer_id, c.first_name, c.last_name, c.email, c.phone,
           car.car_id, car.model, mk.make_name
    FROM bookings b
    JOIN customers c ON b.customer_id = c.customer_id
    JOIN cars car ON b.car_id = car.car_id
    JOIN makes mk ON car.make_id = mk.make_id
    JOIN booking_status bs ON b.booking_status_id = bs.booking_status_id
    ORDER BY b.created_at DESC
    LIMIT 10
");
if ($bookingsData) {
    while ($booking = $bookingsData->fetch_assoc()) {
        $recentBookings[] = $booking;
    }
}

$customersData = [];
$customersQuery = $conn->query("
    SELECT c.customer_id, c.first_name, c.last_name, c.email, c.phone, c.created_at,
           c.driving_license, c.address, c.postcode, c.date_of_birth,
           COUNT(b.booking_id) as total_rentals
    FROM customers c
    LEFT JOIN bookings b ON c.customer_id = b.customer_id
    GROUP BY c.customer_id
    ORDER BY c.created_at DESC
    LIMIT 10
");
if ($customersQuery) {
    while ($customer = $customersQuery->fetch_assoc()) {
        $customersData[] = $customer;
    }
}

// Check if car_images table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'car_images'");
$hasImageTable = ($tableExists && $tableExists->num_rows > 0);

$carsData = [];
$carsQuery = $conn->query("
    SELECT c.car_id, c.model, c.year, c.price_per_day, c.image_url, c.description,
           mk.make_name, ct.type_name, cs.status_name, cs.status_id,
           ci.city_name
    FROM cars c
    JOIN makes mk ON c.make_id = mk.make_id
    JOIN car_types ct ON c.type_id = ct.type_id
    JOIN car_status cs ON c.status_id = cs.status_id
    JOIN cities ci ON c.city_id = ci.city_id
    ORDER BY c.created_at DESC
    LIMIT 10
");
if ($carsQuery) {
    while ($car = $carsQuery->fetch_assoc()) {
        // Fetch images for this car if table exists
        $images = [];
        if ($hasImageTable) {
            $imageQuery = $conn->prepare("SELECT image_url FROM car_images WHERE car_id = ? ORDER BY display_order");
            $imageQuery->bind_param("i", $car['car_id']);
            $imageQuery->execute();
            $imageResult = $imageQuery->get_result();
            while ($img = $imageResult->fetch_assoc()) {
                $images[] = $img['image_url'];
            }
            $imageQuery->close();
        } elseif (!empty($car['image_url'])) {
            // Fallback to single image from cars table
            $images[] = $car['image_url'];
        }
        $car['images'] = $images;
        $carsData[] = $car;
    }
}

$inquiriesData = [];
$inquiriesQuery = $conn->query("
    SELECT inquiry_id, name, email, phone, subject, message, created_at, status
    FROM contact_inquiries
    ORDER BY created_at DESC
    LIMIT 10
");
if ($inquiriesQuery) {
    while ($inquiry = $inquiriesQuery->fetch_assoc()) {
        $inquiriesData[] = $inquiry;
    }
}

$citiesData = [];
$citiesQuery = $conn->query("
    SELECT ci.city_id, ci.city_name, ci.region,
           COUNT(DISTINCT c.car_id) as available_cars,
           COUNT(DISTINCT b.booking_id) as monthly_bookings,
           COALESCE(SUM(b.total_cost), 0) as monthly_revenue
    FROM cities ci
    LEFT JOIN cars c ON ci.city_id = c.city_id AND c.status_id = 1
    LEFT JOIN bookings b ON c.car_id = b.car_id
        AND MONTH(b.created_at) = MONTH(CURRENT_DATE())
        AND YEAR(b.created_at) = YEAR(CURRENT_DATE())
    GROUP BY ci.city_id
    ORDER BY monthly_revenue DESC
");
if ($citiesQuery) {
    while ($city = $citiesQuery->fetch_assoc()) {
        $citiesData[] = $city;
    }
}

$reportsData = [];
$monthlyRevenue = $conn->query("
    SELECT MONTH(created_at) as month, 
           YEAR(created_at) as year,
           SUM(total_cost) as revenue,
           COUNT(*) as bookings
    FROM bookings 
    WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY YEAR(created_at), MONTH(created_at)
    ORDER BY year, month
");
if ($monthlyRevenue) {
    while ($row = $monthlyRevenue->fetch_assoc()) {
        $reportsData['monthly_revenue'][] = $row;
    }
}

$topCars = $conn->query("
    SELECT c.car_id, mk.make_name, c.model, 
           COUNT(b.booking_id) as total_bookings,
           SUM(b.total_cost) as total_revenue
    FROM cars c
    JOIN makes mk ON c.make_id = mk.make_id
    LEFT JOIN bookings b ON c.car_id = b.car_id
    GROUP BY c.car_id
    ORDER BY total_revenue DESC
    LIMIT 5
");
if ($topCars) {
    while ($row = $topCars->fetch_assoc()) {
        $reportsData['top_cars'][] = $row;
    }
}

$recentActivity = [];
$activityQuery = $conn->query("
    (SELECT 'booking' as type, b.booking_id as id, CONCAT('New booking created for ', mk.make_name, ' ', c.model) as activity, b.created_at as timestamp
     FROM bookings b
     JOIN cars c ON b.car_id = c.car_id
     JOIN makes mk ON c.make_id = mk.make_id
     ORDER BY b.created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'customer' as type, customer_id as id, CONCAT('New customer registration: ', first_name, ' ', last_name) as activity, created_at as timestamp
     FROM customers
     ORDER BY created_at DESC LIMIT 2)
    UNION ALL
    (SELECT 'car' as type, c.car_id as id, CONCAT('New car added: ', mk.make_name, ' ', c.model) as activity, c.created_at as timestamp
     FROM cars c
     JOIN makes mk ON c.make_id = mk.make_id
     ORDER BY c.created_at DESC LIMIT 2)
    ORDER BY timestamp DESC
    LIMIT 6
");
if ($activityQuery) {
    while ($activity = $activityQuery->fetch_assoc()) {
        $recentActivity[] = $activity;
    }
}

$makes = [];
$makesQuery = $conn->query("SELECT make_id, make_name FROM makes ORDER BY make_name");
if ($makesQuery) {
    while ($make = $makesQuery->fetch_assoc()) {
        $makes[] = $make;
    }
}

$carTypes = [];
$typesQuery = $conn->query("SELECT type_id, type_name FROM car_types ORDER BY type_name");
if ($typesQuery) {
    while ($type = $typesQuery->fetch_assoc()) {
        $carTypes[] = $type;
    }
}

$carStatuses = [];
$statusQuery = $conn->query("SELECT status_id, status_name FROM car_status ORDER BY status_name");
if ($statusQuery) {
    while ($status = $statusQuery->fetch_assoc()) {
        $carStatuses[] = $status;
    }
}

$cities = [];
$citiesQuery = $conn->query("SELECT city_id, city_name FROM cities ORDER BY city_name");
if ($citiesQuery) {
    while ($city = $citiesQuery->fetch_assoc()) {
        $cities[] = $city;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* ... (keep all existing styles) ... */
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

        .language-selector:hover .language-settings-dropdown {
            display: block;
        }

        .settings-section {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
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
            cursor: pointer;
        }

        .theme-option:hover, .language-option:hover {
            background-color: #f1f1f1;
        }

        .theme-option i, .language-option i {
            width: 18px;
            margin-right: 10px;
            color: var(--vivid-indigo, #8C0050);
            font-size: 14px;
        }

        .font-controls {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .font-btn {
            background: var(--vivid-indigo, #8C0050);
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
            background: var(--dark-magenta, #1800AD);
        }

        .font-size-display {
            font-size: 14px;
            color: #333;
            min-width: 50px;
            text-align: center;
            font-weight: 600;
        }

        .active-indicator {
            margin-left: auto;
            color: var(--vivid-indigo, #8C0050);
            font-size: 12px;
        }
        
        .admin-container {
            padding: 40px 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 80px);
        }
        
        .admin-header {
            background: linear-gradient(to right, var(--dark-magenta), var(--vivid-indigo));
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .admin-welcome {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .welcome-text h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .welcome-text p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        
        .admin-id {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 20px;
            font-weight: 600;
        }
        
        .admin-nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-tabs {
            display: flex;
            list-style: none;
            overflow-x: auto;
        }
        
        .admin-tab {
            padding: 20px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
            font-weight: 600;
            color: #666;
        }
        
        .admin-tab.active {
            color: var(--vivid-indigo);
            border-bottom-color: var(--vivid-indigo);
        }
        
        .admin-tab:hover {
            color: var(--vivid-indigo);
            background: rgba(140, 0, 80, 0.05);
        }
        
        .admin-content {
            display: none;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .admin-content.active {
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
            color: var(--vivid-indigo);
            font-size: 1.5rem;
            margin: 0;
        }
        
        .section-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-primary, .btn-secondary, .btn-danger, .btn-success, .btn-warning {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: var(--cobalt-blue);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--dark-magenta);
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid var(--cobalt-blue);
            color: var(--cobalt-blue);
        }
        
        .btn-secondary:hover {
            background: rgba(0, 74, 173, 0.05);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            border-left: 4px solid var(--cobalt-blue);
        }
        
        .metric-card:hover {
            transform: translateY(-5px);
        }
        
        .metric-card.revenue {
            border-left-color: #2ecc71;
        }
        
        .metric-card.customers {
            border-left-color: #3498db;
        }
        
        .metric-card.bookings {
            border-left-color: #9b59b6;
        }
        
        .metric-card.cars {
            border-left-color: #e74c3c;
        }
        
        .metric-card.inquiries {
            border-left-color: #f39c12;
        }
        
        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .metric-card.revenue .metric-value {
            color: #2ecc71;
        }
        
        .metric-card.customers .metric-value {
            color: #3498db;
        }
        
        .metric-card.bookings .metric-value {
            color: #9b59b6;
        }
        
        .metric-card.cars .metric-value {
            color: #e74c3c;
        }
        
        .metric-card.inquiries .metric-value {
            color: #f39c12;
        }
        
        .metric-label {
            color: #666;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .metric-change {
            margin-top: 10px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .metric-change.positive {
            color: #2ecc71;
        }
        
        .metric-change.negative {
            color: #e74c3c;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .data-table th,
        .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f8f8f8;
            color: var(--vivid-indigo);
            font-weight: 600;
        }
        
        .data-table tr:hover {
            background: #f8f8f8;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .status-confirmed {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-completed {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }
        
        .status-new {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .status-replied {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-available {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-occupied {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
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
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--cobalt-blue);
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(to right, var(--cobalt-blue), var(--vivid-indigo));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .action-icon i {
            color: white;
            font-size: 1.5rem;
        }
        
        .action-title {
            font-weight: 600;
            color: var(--vivid-indigo);
            margin-bottom: 8px;
        }
        
        .action-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .activity-icon i {
            color: var(--vivid-indigo);
            font-size: 1rem;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            margin-bottom: 5px;
            color: #333;
        }
        
        .activity-time {
            color: #666;
            font-size: 0.8rem;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-title {
            color: var(--vivid-indigo);
            margin: 0;
            font-size: 1.5rem;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .report-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .report-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            border-left: 4px solid var(--cobalt-blue);
        }
        
        .report-card h4 {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .report-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .report-item:last-child {
            border-bottom: none;
        }
        
        .report-label {
            color: #666;
        }
        
        .report-value {
            font-weight: 600;
            color: #333;
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

        /* Image preview container styles */
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .image-preview {
            position: relative;
            width: 80px;
            height: 80px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview .remove-image {
            position: absolute;
            top: 2px;
            right: 2px;
            background: rgba(0,0,0,0.6);
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
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
            --vivid-indigo: #8C0050;
            --dark-magenta: #1800AD;
            --cobalt-blue: #004AAD;
            --coral-red: #FF7F50;
        }

        [data-theme="dark"] body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }

        [data-theme="dark"] .admin-container {
            background-color: var(--bg-secondary);
        }

        [data-theme="dark"] .dashboard-section,
        [data-theme="dark"] .metric-card,
        [data-theme="dark"] .action-card,
        [data-theme="dark"] .report-card,
        [data-theme="dark"] .modal-content,
        [data-theme="dark"] .admin-nav {
            background-color: var(--card-bg);
            color: var(--text-primary);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .admin-tab {
            color: var(--text-secondary);
        }

        [data-theme="dark"] .admin-tab.active {
            color: #ffffff;
            border-bottom-color: var(--coral-red);
        }

        [data-theme="dark"] .admin-tab:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        [data-theme="dark"] .data-table th {
            background-color: #404040;
            color: #ffffff;
        }

        [data-theme="dark"] .data-table td {
            border-bottom-color: #404040;
            color: #cccccc;
        }

        [data-theme="dark"] .data-table tr:hover {
            background-color: #404040;
        }

        [data-theme="dark"] .section-title,
        [data-theme="dark"] .metric-value,
        [data-theme="dark"] .metric-label,
        [data-theme="dark"] .action-title,
        [data-theme="dark"] .report-card h4,
        [data-theme="dark"] .report-value,
        [data-theme="dark"] .activity-text,
        [data-theme="dark"] .modal-title {
            color: #ffffff;
        }

        [data-theme="dark"] .report-label,
        [data-theme="dark"] .activity-time,
        [data-theme="dark"] .action-description {
            color: #cccccc;
        }

        [data-theme="dark"] .activity-icon {
            background-color: #404040;
        }

        [data-theme="dark"] .activity-icon i {
            color: var(--coral-red);
        }

        [data-theme="dark"] .modal-header {
            border-bottom-color: #404040;
        }

        [data-theme="dark"] .close-modal {
            color: #cccccc;
        }

        [data-theme="dark"] .close-modal:hover {
            color: #ffffff;
        }

        [data-theme="dark"] .form-group label {
            color: #ffffff;
        }

        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group select,
        [data-theme="dark"] .form-group textarea {
            background-color: #404040;
            border-color: #555555;
            color: #ffffff;
        }

        [data-theme="dark"] .form-group input:focus,
        [data-theme="dark"] .form-group select:focus,
        [data-theme="dark"] .form-group textarea:focus {
            border-color: var(--coral-red);
        }

        [data-theme="dark"] .form-group input::placeholder,
        [data-theme="dark"] .form-group textarea::placeholder {
            color: #999999;
        }

        [data-theme="dark"] .language-settings-dropdown {
            background-color: #333333;
            border-color: #404040;
        }

        [data-theme="dark"] .settings-section {
            border-color: #404040;
        }

        [data-theme="dark"] .settings-section h4 {
            color: #ffffff;
        }

        [data-theme="dark"] .theme-option,
        [data-theme="dark"] .language-option {
            color: #ffffff;
        }

        [data-theme="dark"] .theme-option:hover,
        [data-theme="dark"] .language-option:hover {
            background-color: #404040;
        }

        [data-theme="dark"] .font-size-display {
            color: #ffffff;
        }
        
        @media (max-width: 992px) {
            .admin-welcome {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .welcome-text h1 {
                font-size: 2rem;
            }
            
            .admin-tabs {
                flex-wrap: wrap;
            }
            
            .admin-tab {
                padding: 15px 20px;
            }
            
            .dashboard-section {
                padding: 20px;
            }
            
            .metrics-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .section-actions {
                width: 100%;
                justify-content: flex-start;
            }
        }
        
        @media (max-width: 480px) {
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-theme="<?php echo $darkMode; ?>">

<header>
    <div class="container header-content">
        <div class="logo">
            <img src="logo2.png" alt="Logo">
        </div>

        <nav>
            <ul>
                <li><a href="admin-dashboard.php" class="active">Admin Dashboard</a></li>
                <li>
                    <a href="logout.php" style="color: #ff4444;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
                <li class="language-selector">
                    <a href="#"><i class="fa-solid fa-circle-info" style="color: white;"></i></a>
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
                                <button class="font-btn" id="font-reset" aria-label="<?php echo $resetText; ?>"><i class="fas fa-redo"></i></button //improved reset button
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
            </ul>
        </nav>
    </div>
</header>

    <div class="modal" id="addCarModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New Car</h3>
                <button class="close-modal" onclick="closeAddCarModal()">&times;</button>
            </div>
            
            <?php if (!empty($successMessage)): ?>
                <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div style="background: #ffebee; color: #c62828; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" id="addCarForm">
                <input type="hidden" name="add_car" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="make_id">Make *</label>
                        <select id="make_id" name="make_id" required>
                            <option value="">Select Make</option>
                            <?php foreach ($makes as $make): ?>
                                <option value="<?php echo $make['make_id']; ?>">
                                    <?php echo htmlspecialchars($make['make_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="model">Model *</label>
                        <input type="text" id="model" name="model" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="year">Year *</label>
                        <input type="number" id="year" name="year" min="2000" max="2030" value="<?php echo date('Y'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="type_id">Type *</label>
                        <select id="type_id" name="type_id" required>
                            <option value="">Select Type</option>
                            <?php foreach ($carTypes as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>">
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price_per_day">Price Per Day (£) *</label>
                        <input type="number" id="price_per_day" name="price_per_day" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label for="deposit_required">Deposit Required (£)</label>
                        <input type="number" id="deposit_required" name="deposit_required" step="0.01" min="0">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status_id">Status *</label>
                        <select id="status_id" name="status_id" required>
                            <option value="">Select Status</option>
                            <?php foreach ($carStatuses as $status): ?>
                                <option value="<?php echo $status['status_id']; ?>">
                                    <?php echo htmlspecialchars($status['status_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city_id">City *</label>
                        <select id="city_id" name="city_id" required>
                            <option value="">Select City</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['city_id']; ?>">
                                    <?php echo htmlspecialchars($city['city_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <!-- Multiple Image Upload Section -->
                <div class="form-group">
                    <label for="car_images">Car Images (Up to 5 images)</label>
                    <input type="file" id="car_images" name="car_images[]" accept="image/*" multiple>
                    <small>Recommended: 800x600px, JPG, PNG, GIF or WebP format. You can select up to 5 images.</small>
                    <div id="image-preview-container" class="image-preview-container"></div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Enter car description..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeAddCarModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Add Car</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal" id="addCityModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Add New City</h3>
                <button class="close-modal" onclick="closeAddCityModal()">&times;</button>
            </div>
            
            <form method="POST" id="addCityForm">
                <input type="hidden" name="add_city" value="1">
                
                <div class="form-group">
                    <label for="city_name">City Name *</label>
                    <input type="text" id="city_name" name="city_name" required>
                </div>
                
                <div class="form-group">
                    <label for="region">Region *</label>
                    <input type="text" id="region" name="region" required>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <button type="button" onclick="closeAddCityModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">Add City</button>
                </div>
            </form>
        </div>
    </div>

    <section class="admin-header">
        <div class="admin-welcome">
            <div class="welcome-text">
                <h1>Admin Dashboard</h1>
                <p>Manage your car rental business efficiently</p>
            </div>
            <div class="admin-id">Admin ID: <?php echo htmlspecialchars($user['memberId'] ?? $user['id']); ?></div>
        </div>
    </section>

    <nav class="admin-nav">
        <div class="nav-container">
            <ul class="admin-tabs">
                <li class="admin-tab active" data-tab="overview">Overview</li>
                <li class="admin-tab" data-tab="bookings">Bookings</li>
                <li class="admin-tab" data-tab="customers">Customers</li>
                <li class="admin-tab" data-tab="cars">Car Fleet</li>
                <li class="admin-tab" data-tab="inquiries">Customer Inquiries</li>
                <li class="admin-tab" data-tab="cities">Cities</li>
                <li class="admin-tab" data-tab="reports">Reports</li>
            </ul>
        </div>
    </nav>

    <div class="admin-container">
        <div class="admin-content active" id="overview">
            <div class="metrics-grid">
                <div class="metric-card revenue">
                    <div class="metric-value">£<?php echo number_format($stats['revenue'], 2); ?></div>
                    <div class="metric-label">Total Revenue</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>This month</span>
                    </div>
                </div>
                
                <div class="metric-card customers">
                    <div class="metric-value"><?php echo $stats['customers']; ?></div>
                    <div class="metric-label">Total Customers</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>All time</span>
                    </div>
                </div>
                
                <div class="metric-card bookings">
                    <div class="metric-value"><?php echo $stats['bookings']; ?></div>
                    <div class="metric-label">Monthly Bookings</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>This month</span>
                    </div>
                </div>
                
                <div class="metric-card inquiries">
                    <div class="metric-value"><?php echo $stats['inquiries']; ?></div>
                    <div class="metric-label">New Inquiries</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>Last 30 days</span>
                    </div>
                </div>

                <div class="metric-card cars">
                    <div class="metric-value"><?php echo $stats['cars']; ?></div>
                    <div class="metric-label">Total Cars</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>In fleet</span>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Quick Actions</h3>
                </div>
                <div class="quick-actions">
                    <div class="action-card" onclick="showAddCarModal()">
                        <div class="action-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="action-title">Add New Car</div>
                        <div class="action-description">Add a new vehicle to the fleet</div>
                    </div>
                    
                    <div class="action-card" onclick="switchTab('bookings')">
                        <div class="action-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="action-title">View Bookings</div>
                        <div class="action-description">Manage current reservations</div>
                    </div>
                    
                    <div class="action-card" onclick="switchTab('inquiries')">
                        <div class="action-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="action-title">Customer Inquiries</div>
                        <div class="action-description">Respond to customer questions</div>
                    </div>
                    
                    <div class="action-card" onclick="switchTab('reports')">
                        <div class="action-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <div class="action-title">Generate Reports</div>
                        <div class="action-description">Create business reports</div>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Recent Activity</h3>
                    <a href="#" class="btn-secondary">View All</a>
                </div>
                <ul class="activity-list" id="recentActivity">
                    <?php if (!empty($recentActivity)): ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <li class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?php echo $activity['type'] === 'booking' ? 'car' : ($activity['type'] === 'customer' ? 'user' : 'car'); ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text"><?php echo htmlspecialchars($activity['activity']); ?></div>
                                    <div class="activity-time"><?php echo date('M j, Y g:i A', strtotime($activity['timestamp'])); ?></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="activity-item">
                            <div class="activity-content">
                                <div class="activity-text">No recent activity</div>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="admin-content" id="bookings">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Manage Bookings</h3>
                    <div class="section-actions">
                        <button class="btn-secondary">Export Data</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
                            <th>Car</th>
                            <th>Pickup Date</th>
                            <th>Return Date</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bookingsTable">
                        <?php if (!empty($recentBookings)): ?>
                            <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo $booking['booking_id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['make_name'] . ' ' . $booking['model']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['start_date'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($booking['end_date'])); ?></td>
                                    <td>£<?php echo number_format($booking['total_cost'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($booking['status_name']); ?>">
                                            <?php echo $booking['status_name']; ?>
                                        </span>
                                    </td>
                                    <td class="action-buttons">
                                        <?php if ($booking['booking_status_id'] != 4): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="cancel_booking" value="1">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                                <button type="submit" class="btn-danger" onclick="return confirm('Are you sure you want to cancel booking #<?php echo $booking['booking_id']; ?>?')">Cancel</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No bookings found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-content" id="customers">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Customer Management</h3>
                    <div class="section-actions">
                        <button class="btn-secondary">Export Data</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Join Date</th>
                            <th>Total Rentals</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customersTable">
                        <?php if (!empty($customersData)): ?>
                            <?php foreach ($customersData as $customer): ?>
                                <tr>
                                    <td>#<?php echo $customer['customer_id']; ?></td>
                                    <td><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                    <td><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                                    <td><?php echo $customer['total_rentals']; ?></td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                    <td class="action-buttons">
                                        <button class="btn-primary" onclick="contactCustomer('<?php echo htmlspecialchars($customer['email']); ?>', '<?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?>')">Contact</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No customers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-content" id="cars">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Car Fleet Management</h3>
                    <div class="section-actions">
                        <button class="btn-primary" onclick="showAddCarModal()">Add New Car</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Car ID</th>
                            <th>Images</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Daily Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="carsTable">
                        <?php if (!empty($carsData)): ?>
                            <?php foreach ($carsData as $car): ?>
                                <tr>
                                    <td>#<?php echo $car['car_id']; ?></td>
                                    <td>
                                        <div style="display: flex; gap: 5px;">
                                            <?php if (!empty($car['images'])): ?>
                                                <?php 
                                                $displayCount = 0;
                                                foreach ($car['images'] as $index => $img): 
                                                    if ($displayCount < 2 && !empty($img)):
                                                ?>
                                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="Car image" style="width: 40px; height: 30px; object-fit: cover; border-radius: 4px;">
                                                <?php 
                                                        $displayCount++;
                                                    endif;
                                                endforeach; 
                                                ?>
                                                <?php if (count($car['images']) > 2): ?>
                                                    <span style="font-size: 12px;">+<?php echo count($car['images']) - 2; ?></span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <div style="width: 40px; height: 30px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; color: #666;">No Img</div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model'] . ' (' . $car['year'] . ')'); ?></td>
                                    <td><?php echo htmlspecialchars($car['type_name']); ?></td>
                                    <td>£<?php echo number_format($car['price_per_day'], 2); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($car['status_name']); ?>"><?php echo $car['status_name']; ?></span></td>
                                    <td class="action-buttons">
                                        <button class="btn-secondary" onclick="editCar(<?php echo $car['car_id']; ?>)">Edit</button>
                                        <?php if ($car['status_id'] == 2): ?>
                                            <button class="btn-success" onclick="updateCarStatus(<?php echo $car['car_id']; ?>, 1)">Mark Available</button>
                                        <?php else: ?>
                                            <button class="btn-warning" onclick="updateCarStatus(<?php echo $car['car_id']; ?>, 2)">Mark Occupied</button>
                                        <?php endif; ?>
                                    </td>
                                 </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No cars found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-content" id="inquiries">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Customer Inquiries</h3>
                    <div class="section-actions">
                        <button class="btn-secondary">Export Data</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="inquiriesTable">
                        <?php if (!empty($inquiriesData)): ?>
                            <?php foreach ($inquiriesData as $inquiry): ?>
                                <tr>
                                    <td>#<?php echo $inquiry['inquiry_id']; ?></td>
                                    <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                                    <td><?php echo htmlspecialchars($inquiry['email']); ?></td>
                                    <td><?php echo htmlspecialchars($inquiry['phone'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($inquiry['subject']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($inquiry['created_at'])); ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($inquiry['status']); ?>"><?php echo ucfirst($inquiry['status']); ?></span></td>
                                    <td class="action-buttons">
                                        <button class="btn-secondary" onclick="viewInquiry(<?php echo $inquiry['inquiry_id']; ?>)">View Details</button>
                                        <button class="btn-primary" onclick="replyToInquiry('<?php echo htmlspecialchars($inquiry['email']); ?>', '<?php echo htmlspecialchars($inquiry['subject']); ?>')">Reply</button>
                                        <?php if ($inquiry['status'] === 'new'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="update_inquiry_status" value="1">
                                                <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['inquiry_id']; ?>">
                                                <input type="hidden" name="new_status" value="replied">
                                                <button type="submit" class="btn-success">Mark Replied</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No inquiries found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-content" id="cities">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Cities & Locations</h3>
                    <div class="section-actions">
                        <button class="btn-primary" onclick="showAddCityModal()">Add New City</button>
                    </div>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>City</th>
                            <th>Region</th>
                            <th>Available Cars</th>
                            <th>Bookings (Month)</th>
                            <th>Revenue (Month)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="citiesTable">
                        <?php if (!empty($citiesData)): ?>
                            <?php foreach ($citiesData as $city): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($city['city_name']); ?></td>
                                    <td><?php echo htmlspecialchars($city['region']); ?></td>
                                    <td><?php echo $city['available_cars']; ?></td>
                                    <td><?php echo $city['monthly_bookings']; ?></td>
                                    <td>£<?php echo number_format($city['monthly_revenue'], 2); ?></td>
                                    <td><span class="status-badge status-active">Active</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No cities found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="admin-content" id="reports">
            <div class="dashboard-section">
                <div class="section-header">
                    <h3 class="section-title">Business Reports Summary</h3>
                    <div class="section-actions">
                        <button class="btn-secondary" onclick="printReport()">Print Report</button>
                    </div>
                </div>
                
                <div class="report-summary">
                    <div class="report-card">
                        <h4>Financial Overview</h4>
                        <div class="report-item">
                            <span class="report-label">Monthly Revenue</span>
                            <span class="report-value">£<?php echo number_format($stats['revenue'], 2); ?></span>
                        </div>
                        <div class="report-item">
                            <span class="report-label">Monthly Bookings</span>
                            <span class="report-value"><?php echo $stats['bookings']; ?></span>
                        </div>
                        <div class="report-item">
                            <span class="report-label">Average Booking Value</span>
                            <span class="report-value">£<?php echo $stats['bookings'] > 0 ? number_format($stats['revenue'] / $stats['bookings'], 2) : '0.00'; ?></span>
                        </div>
                    </div>
                    
                    <div class="report-card">
                        <h4>Customer Insights</h4>
                        <div class="report-item">
                            <span class="report-label">Total Customers</span>
                            <span class="report-value"><?php echo $stats['customers']; ?></span>
                        </div>
                        <div class="report-item">
                            <span class="report-label">New Inquiries</span>
                            <span class="report-value"><?php echo $stats['inquiries']; ?></span>
                        </div>
                        <div class="report-item">
                            <span class="report-label">Active Cars</span>
                            <span class="report-value"><?php echo $stats['cars']; ?></span>
                        </div>
                    </div>
                    
                    <div class="report-card">
                        <h4>Top Performing Cars</h4>
                        <?php if (!empty($reportsData['top_cars'])): ?>
                            <?php foreach ($reportsData['top_cars'] as $car): ?>
                                <div class="report-item">
                                    <span class="report-label"><?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?></span>
                                    <span class="report-value">£<?php echo number_format($car['total_revenue'] ?? 0, 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="report-item">
                                <span class="report-label">No data available</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-section">
                    <h4>Recent Performance Trends</h4>
                    <div style="background: #f8f8f8; padding: 20px; border-radius: 8px;">
                        <p><strong>Last 6 Months Revenue:</strong></p>
                        <?php if (!empty($reportsData['monthly_revenue'])): ?>
                            <?php foreach ($reportsData['monthly_revenue'] as $month): ?>
                                <div class="report-item">
                                    <span class="report-label">
                                        <?php echo date('F Y', mktime(0, 0, 0, $month['month'], 1, $month['year'])); ?>
                                    </span>
                                    <span class="report-value">
                                        £<?php echo number_format($month['revenue'], 2); ?> (<?php echo $month['bookings']; ?> bookings)
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No revenue data available for the last 6 months.</p>
                        <?php endif; ?>
                    </div>
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
                    <li><a href="about.php">About</a></li>
                    <li><a href="cars.php">Cars</a></li>
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

<script>
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
                const theme = this.getAttribute('data-theme');
                setTheme(theme);
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
                const lang = this.getAttribute('data-lang');
                setLanguage(lang);
            });
        });

        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
        });

        function switchTab(tabName) {
            document.querySelectorAll('.admin-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.admin-tab[data-tab="${tabName}"]`).classList.add('active');
            
            document.querySelectorAll('.admin-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
        }

        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                switchTab(this.getAttribute('data-tab'));
            });
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.style.display = 'none';
                });
            }
        });

        // Image preview for multiple files
        const imageInput = document.getElementById('car_images');
        const previewContainer = document.getElementById('image-preview-container');
        
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                previewContainer.innerHTML = '';
                const files = Array.from(e.target.files);
                
                if (files.length > 5) {
                    alert('You can only upload up to 5 images.');
                    imageInput.value = '';
                    return;
                }
                
                files.forEach((file, index) => {
                    if (file && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            const previewDiv = document.createElement('div');
                            previewDiv.className = 'image-preview';
                            previewDiv.setAttribute('data-index', index);
                            
                            const img = document.createElement('img');
                            img.src = ev.target.result;
                            
                            const removeBtn = document.createElement('button');
                            removeBtn.className = 'remove-image';
                            removeBtn.innerHTML = '×';
                            removeBtn.onclick = function() {
                                // Remove this preview and remove the file from the input
                                const dt = new DataTransfer();
                                const currentFiles = Array.from(imageInput.files);
                                const filteredFiles = currentFiles.filter((f, i) => i !== index);
                                filteredFiles.forEach(f => dt.items.add(f));
                                imageInput.files = dt.files;
                                // Re-trigger change event to refresh previews
                                const event = new Event('change', { bubbles: true });
                                imageInput.dispatchEvent(event);
                            };
                            
                            previewDiv.appendChild(img);
                            previewDiv.appendChild(removeBtn);
                            previewContainer.appendChild(previewDiv);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });
        }
    });

    function showAddCarModal() {
        document.getElementById('addCarModal').style.display = 'flex';
    }
    
    function closeAddCarModal() {
        document.getElementById('addCarModal').style.display = 'none';
        // Clear previews
        const previewContainer = document.getElementById('image-preview-container');
        if (previewContainer) previewContainer.innerHTML = '';
        const imageInput = document.getElementById('car_images');
        if (imageInput) imageInput.value = '';
    }
    
    function showAddCityModal() {
        document.getElementById('addCityModal').style.display = 'flex';
    }
    
    function closeAddCityModal() {
        document.getElementById('addCityModal').style.display = 'none';
    }

    function switchTab(tabName) {
        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelector(`.admin-tab[data-tab="${tabName}"]`).classList.add('active');
        
        document.querySelectorAll('.admin-content').forEach(content => {
            content.classList.remove('active');
        });
        document.getElementById(tabName).classList.add('active');
    }

    function contactCustomer(email, name) {
        const subject = 'Message from Motiv Car Hire';
        const body = `Dear ${name},\n\n`;
        const emailUrl = `mailto:${email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
        window.location.href = emailUrl;
    }

    function editCar(carId) {
        window.location.href = `edit-car.php?id=${carId}`;
    }
    
    function updateCarStatus(carId, newStatus) {
        const statusText = newStatus == 1 ? 'available' : 'occupied';
        if (confirm(`Are you sure you want to mark this car as ${statusText}?`)) {
            fetch('update_car_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `car_id=${carId}&new_status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showTemporaryMessage(data.message, 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showTemporaryMessage(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showTemporaryMessage('Network error. Please try again.', 'error');
            });
        }
    }

    function viewInquiry(inquiryId) {
        window.location.href = `inquiry-details.php?id=${inquiryId}`;
    }

    function replyToInquiry(email, subject) {
        const emailUrl = `mailto:${email}?subject=Re: ${encodeURIComponent(subject)}&body=Dear Customer,%0D%0A%0D%0AThank you for your inquiry. We appreciate your interest in Motiv Car Hire.%0D%0A%0D%0A`;
        window.location.href = emailUrl;
    }

    function printReport() {
        window.print();
    }

    function showTemporaryMessage(message, type) {
        const existingMsg = document.querySelector('.temp-message');
        if (existingMsg) existingMsg.remove();
        
        const messageEl = document.createElement('div');
        messageEl.className = `temp-message ${type}`;
        messageEl.textContent = message;
        messageEl.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            z-index: 1000;
            max-width: 300px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            background: ${type === 'success' ? '#e8f5e9' : '#ffebee'};
            color: ${type === 'success' ? '#2e7d32' : '#c62828'};
            border: 1px solid ${type === 'success' ? '#a5d6a7' : '#ef9a9a'};
        `;
        
        document.body.appendChild(messageEl);
        
        setTimeout(() => {
            messageEl.remove();
        }, 3000);
    }
</script>
</body>
</html>
<?php
$conn->close();
?>
