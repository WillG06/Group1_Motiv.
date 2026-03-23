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

// Language variables
$themeText = 'Theme';
$lightText = 'Light';
$darkText = 'Dark';
$fontSizeText = 'Font Size';
$resetText = 'Reset';
$languageText = 'Language';

$carId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($carId === 0) {
    header('Location: admin-dashboard.php');
    exit;
}

// Function to get all images for a car
function getCarImages($conn, $carId) {
    $images = [];
    $stmt = $conn->prepare("SELECT image_id, image_url, display_order FROM car_images WHERE car_id = ? ORDER BY display_order");
    $stmt->bind_param("i", $carId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
    $stmt->close();
    return $images;
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imageId = intval($_POST['image_id']);
    
    $getImageQuery = $conn->prepare("SELECT image_url FROM car_images WHERE image_id = ? AND car_id = ?");
    $getImageQuery->bind_param("ii", $imageId, $carId);
    $getImageQuery->execute();
    $imageResult = $getImageQuery->get_result();
    
    if ($imageResult->num_rows > 0) {
        $imageData = $imageResult->fetch_assoc();
        $imagePath = $imageData['image_url'];
        
        $deleteQuery = $conn->prepare("DELETE FROM car_images WHERE image_id = ? AND car_id = ?");
        $deleteQuery->bind_param("ii", $imageId, $carId);
        
        if ($deleteQuery->execute()) {
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
            $successMessage = "Image deleted successfully!";
        } else {
            $errorMessage = "Error deleting image: " . $conn->error;
        }
        $deleteQuery->close();
    }
    $getImageQuery->close();
}

// Handle image reordering
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reorder_images'])) {
    $imageOrders = $_POST['image_order'];
    
    foreach ($imageOrders as $imageId => $order) {
        $updateOrderQuery = $conn->prepare("UPDATE car_images SET display_order = ? WHERE image_id = ? AND car_id = ?");
        $updateOrderQuery->bind_param("iii", $order, $imageId, $carId);
        $updateOrderQuery->execute();
        $updateOrderQuery->close();
    }
    $successMessage = "Image order updated successfully!";
}

// Get car details first
$carQuery = $conn->prepare("
    SELECT c.*, mk.make_name, ct.type_name, cs.status_name, ci.city_name
    FROM cars c
    JOIN makes mk ON c.make_id = mk.make_id
    JOIN car_types ct ON c.type_id = ct.type_id
    JOIN car_status cs ON c.status_id = cs.status_id
    JOIN cities ci ON c.city_id = ci.city_id
    WHERE c.car_id = ?
");
$carQuery->bind_param("i", $carId);
$carQuery->execute();
$carResult = $carQuery->get_result();
$car = $carResult->fetch_assoc();
$carQuery->close();

if (!$car) {
    header('Location: admin-dashboard.php');
    exit;
}

// Get existing images for this car
$carImages = getCarImages($conn, $carId);

// Handle complete update (both car details and images)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_complete'])) {
    $makeId = $_POST['make_id'];
    $model = trim($_POST['model']);
    $year = $_POST['year'];
    $typeId = $_POST['type_id'];
    $pricePerDay = $_POST['price_per_day'];
    $depositRequired = $_POST['deposit_required'] ?? 0;
    $description = trim($_POST['description']);
    $statusId = $_POST['status_id'];
    $cityId = $_POST['city_id'];
    $seats = !empty($_POST['seats']) ? $_POST['seats'] : null;
    $doors = !empty($_POST['doors']) ? $_POST['doors'] : null;
    
    // Update car details first
    if (empty($model) || empty($year) || empty($pricePerDay)) {
        $errorMessage = "Please fill in all required fields.";
    } else {
        $updateQuery = $conn->prepare("
            UPDATE cars SET 
                make_id = ?, model = ?, year = ?, type_id = ?, price_per_day = ?, 
                deposit_required = ?, description = ?, status_id = ?, city_id = ?,
                seats = ?, doors = ?
            WHERE car_id = ?
        ");
        $updateQuery->bind_param("isiiidsiiiii", 
            $makeId, $model, $year, $typeId, $pricePerDay, 
            $depositRequired, $description, $statusId, $cityId,
            $seats, $doors, $carId
        );
        
        if ($updateQuery->execute()) {
            $carDetailsUpdated = true;
        } else {
            $errorMessage = "Error updating car details: " . $conn->error;
        }
        $updateQuery->close();
    }
    
    // Handle image uploads if any
    $uploadedImages = [];
    $uploadErrors = [];
    
    if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
        $uploadDir = 'uploads/cars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $maxFiles = 5;
        $files = $_FILES['car_images'];
        
        $currentCount = count($carImages);
        $fileCount = 0;
        foreach ($files['name'] as $name) {
            if (!empty($name)) $fileCount++;
        }
        
        if ($currentCount + $fileCount > $maxFiles) {
            $uploadErrors[] = "Maximum $maxFiles images allowed. You currently have $currentCount images.";
        } else {
            for ($i = 0; $i < count($files['name']); $i++) {
                if (empty($files['name'][$i])) continue;
                
                $fileError = $files['error'][$i];
                $tmpName = $files['tmp_name'][$i];
                $originalName = $files['name'][$i];
                
                if ($fileError !== UPLOAD_ERR_OK) {
                    $uploadErrors[] = "Error uploading file: $originalName";
                    continue;
                }
                
                $fileExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                if (!in_array($fileExtension, $allowedExtensions)) {
                    $uploadErrors[] = "File $originalName: Invalid format.";
                    continue;
                }
                
                $check = getimagesize($tmpName);
                if ($check === false) {
                    $uploadErrors[] = "File $originalName is not a valid image.";
                    continue;
                }
                
                $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $model) . '_' . ($i+1) . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedImages[] = $targetPath;
                } else {
                    $uploadErrors[] = "Error uploading $originalName.";
                }
            }
        }
        
        // Insert new images if any
        if (!empty($uploadedImages)) {
            $maxOrderQuery = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) as max_order FROM car_images WHERE car_id = ?");
            $maxOrderQuery->bind_param("i", $carId);
            $maxOrderQuery->execute();
            $maxOrderResult = $maxOrderQuery->get_result();
            $maxOrderData = $maxOrderResult->fetch_assoc();
            $nextOrder = $maxOrderData['max_order'] + 1;
            $maxOrderQuery->close();
            
            $imageInsertQuery = $conn->prepare("INSERT INTO car_images (car_id, image_url, display_order) VALUES (?, ?, ?)");
            $displayOrder = $nextOrder;
            
            foreach ($uploadedImages as $imagePath) {
                $imageInsertQuery->bind_param("isi", $carId, $imagePath, $displayOrder);
                if (!$imageInsertQuery->execute()) {
                    $uploadErrors[] = "Error inserting image into database";
                    break;
                }
                $displayOrder++;
            }
            $imageInsertQuery->close();
        }
    }
    
    // Set final message
    if (empty($errorMessage)) {
        if (!empty($uploadErrors)) {
            $errorMessage = implode(" ", $uploadErrors);
        } elseif (isset($carDetailsUpdated)) {
            $successMessage = "Car details updated successfully!";
            if (!empty($uploadedImages)) {
                $successMessage .= " " . count($uploadedImages) . " image(s) added.";
            }
            // Refresh car images
            $carImages = getCarImages($conn, $carId);
            // Refresh car data
            $carQuery = $conn->prepare("
                SELECT c.*, mk.make_name, ct.type_name, cs.status_name, ci.city_name
                FROM cars c
                JOIN makes mk ON c.make_id = mk.make_id
                JOIN car_types ct ON c.type_id = ct.type_id
                JOIN car_status cs ON c.status_id = cs.status_id
                JOIN cities ci ON c.city_id = ci.city_id
                WHERE c.car_id = ?
            ");
            $carQuery->bind_param("i", $carId);
            $carQuery->execute();
            $carResult = $carQuery->get_result();
            $car = $carResult->fetch_assoc();
            $carQuery->close();
        }
    }
}

// Get all needed data for dropdowns
$makes = [];
$makesQuery = $conn->query("SELECT make_id, make_name FROM makes ORDER BY make_name");
while ($make = $makesQuery->fetch_assoc()) {
    $makes[] = $make;
}

$carTypes = [];
$typesQuery = $conn->query("SELECT type_id, type_name FROM car_types ORDER BY type_name");
while ($type = $typesQuery->fetch_assoc()) {
    $carTypes[] = $type;
}

$carStatuses = [];
$statusQuery = $conn->query("SELECT status_id, status_name FROM car_status ORDER BY status_name");
while ($status = $statusQuery->fetch_assoc()) {
    $carStatuses[] = $status;
}

$cities = [];
$citiesQuery = $conn->query("SELECT city_id, city_name FROM cities ORDER BY city_name");
while ($city = $citiesQuery->fetch_assoc()) {
    $cities[] = $city;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Car - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #333333;
            --text-secondary: #666666;
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --footer-bg: #8C0050;
            --footer-text: #ecf0f1;
            --vivid-indigo: #8C0050;
            --dark-magenta: #1800AD;
            --cobalt-blue: #004AAD;
            --coral-red: #FF7F50;
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
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: <?php echo $fontSize; ?>%;
            transition: background-color 0.3s ease, color 0.3s ease;
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
            cursor: pointer;
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

        .edit-car-container {
            padding: 40px 0;
            background-color: var(--bg-secondary);
            min-height: 100vh;
        }
        
        .edit-car-header {
            background: linear-gradient(to right, var(--dark-magenta), var(--vivid-indigo));
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .edit-car-content {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 3px 10px var(--shadow-color);
            border: 1px solid var(--border-color);
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--cobalt-blue);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            color: var(--dark-magenta);
        }
        
        .section-title {
            color: var(--vivid-indigo);
            font-size: 1.3rem;
            margin: 25px 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .section-title:first-of-type {
            margin-top: 0;
        }
        
        /* Image Gallery Styles */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .image-card {
            position: relative;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            background: var(--bg-secondary);
            transition: transform 0.2s;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .image-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }
        
        .image-order {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(0,0,0,0.7);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        
        .image-actions {
            position: absolute;
            top: 5px;
            right: 5px;
            display: flex;
            gap: 5px;
        }
        
        .delete-image-btn {
            background: rgba(220, 53, 69, 0.9);
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        
        .delete-image-btn:hover {
            background: #c82333;
            transform: scale(1.05);
        }
        
        .drag-handle {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background: rgba(0,0,0,0.6);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: move;
            font-size: 16px;
        }
        
        .image-card.dragging {
            opacity: 0.5;
        }
        
        .image-card.drag-over {
            border: 2px dashed var(--cobalt-blue);
            background: rgba(0, 74, 173, 0.1);
        }
        
        .image-info {
            padding: 8px;
            text-align: center;
            font-size: 12px;
            color: var(--text-secondary);
            background: var(--card-bg);
        }
        
        .add-images-section {
            margin: 20px 0 30px;
            padding: 20px;
            background: var(--bg-secondary);
            border-radius: 8px;
            border: 1px dashed var(--border-color);
        }
        
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
            border: 1px solid var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-preview .remove-preview {
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
        
        .reorder-section {
            background: var(--bg-secondary);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
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
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
            background-color: var(--card-bg);
            color: var(--text-primary);
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--cobalt-blue);
            outline: none;
        }
        
        .btn-primary, .btn-secondary, .btn-danger {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--cobalt-blue);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--dark-magenta);
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
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
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .image-count {
            font-size: 14px;
            color: var(--text-secondary);
            margin-left: 10px;
        }
        
        .drag-instruction {
            font-size: 12px;
            color: var(--text-secondary);
            text-align: center;
            margin-top: 10px;
        }
        
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            color: white;
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .footer-column p {
            color: var(--footer-text);
            line-height: 1.6;
        }
        
        .footer-column ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-column ul li {
            margin-bottom: 10px;
        }
        
        .footer-column ul li a {
            color: var(--footer-text);
            text-decoration: none;
            transition: color 0.3s ease;
        }
        
        .footer-column ul li a:hover {
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
        }
        
        @media (max-width: 768px) {
            .edit-car-content {
                padding: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .image-gallery {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }
            
            .form-actions {
                flex-direction: column-reverse;
            }
            
            .form-actions button,
            .form-actions a {
                width: 100%;
                text-align: center;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
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
                <li><a href="landing.php">Home</a></li>
                <li><a href="admin-dashboard.php">Admin Dashboard</a></li>
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
                                <button class="font-btn" id="font-reset" aria-label="<?php echo $resetText; ?>"><i class="fas fa-redo"></i></button>
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

<section class="edit-car-header">
    <div class="container">
        <h1>Edit Car</h1>
        <p>Update car details, images, and information</p>
    </div>
</section>

<section class="edit-car-container">
    <div class="container">
        <a href="admin-dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="edit-car-content">
            <?php if (isset($successMessage)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <!-- Combined Form - Updates Both Car Details and Images -->
            <form method="POST" enctype="multipart/form-data" id="editCarForm">
                <input type="hidden" name="update_complete" value="1">
                
                <!-- Car Details Section -->
                <h2 class="section-title">
                    <i class="fas fa-car"></i> Car Details
                </h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="make_id">Make *</label>
                        <select id="make_id" name="make_id" required>
                            <option value="">Select Make</option>
                            <?php foreach ($makes as $make): ?>
                                <option value="<?php echo $make['make_id']; ?>" <?php echo $car['make_id'] == $make['make_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($make['make_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="model">Model *</label>
                        <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($car['model']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="year">Year *</label>
                        <input type="number" id="year" name="year" min="2000" max="2030" value="<?php echo htmlspecialchars($car['year']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="type_id">Type *</label>
                        <select id="type_id" name="type_id" required>
                            <option value="">Select Type</option>
                            <?php foreach ($carTypes as $type): ?>
                                <option value="<?php echo $type['type_id']; ?>" <?php echo $car['type_id'] == $type['type_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="price_per_day">Price Per Day (£) *</label>
                        <input type="number" id="price_per_day" name="price_per_day" step="0.01" min="0" value="<?php echo htmlspecialchars($car['price_per_day']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="deposit_required">Deposit Required (£)</label>
                        <input type="number" id="deposit_required" name="deposit_required" step="0.01" min="0" value="<?php echo htmlspecialchars($car['deposit_required']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="seats">Seats</label>
                        <input type="number" id="seats" name="seats" min="2" max="9" value="<?php echo htmlspecialchars($car['seats']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="doors">Doors</label>
                        <input type="number" id="doors" name="doors" min="2" max="5" value="<?php echo htmlspecialchars($car['doors']); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="status_id">Status *</label>
                        <select id="status_id" name="status_id" required>
                            <option value="">Select Status</option>
                            <?php foreach ($carStatuses as $status): ?>
                                <option value="<?php echo $status['status_id']; ?>" <?php echo $car['status_id'] == $status['status_id'] ? 'selected' : ''; ?>>
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
                                <option value="<?php echo $city['city_id']; ?>" <?php echo $car['city_id'] == $city['city_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city['city_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="5" placeholder="Enter car description..."><?php echo htmlspecialchars($car['description']); ?></textarea>
                </div>
                
                <!-- Images Management Section -->
                <h2 class="section-title">
                    <i class="fas fa-images"></i> Car Images
                    <span class="image-count">(<?php echo count($carImages); ?>/5 images)</span>
                </h2>
                
                <?php if (!empty($carImages)): ?>
                    <div class="reorder-section">
                        <div class="image-gallery" id="imageGallery">
                            <?php foreach ($carImages as $image): ?>
                                <div class="image-card" data-id="<?php echo $image['image_id']; ?>" data-order="<?php echo $image['display_order']; ?>">
                                    <img src="<?php echo htmlspecialchars($image['image_url']); ?>" alt="Car Image">
                                    <div class="image-order"><?php echo $image['display_order']; ?></div>
                                    <div class="image-actions">
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this image?');">
                                            <input type="hidden" name="delete_image" value="1">
                                            <input type="hidden" name="image_id" value="<?php echo $image['image_id']; ?>">
                                            <button type="submit" class="delete-image-btn" title="Delete Image">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                    <div class="drag-handle" title="Drag to reorder">
                                        <i class="fas fa-grip-vertical"></i>
                                    </div>
                                    <div class="image-info">
                                        <input type="hidden" name="image_order[<?php echo $image['image_id']; ?>]" value="<?php echo $image['display_order']; ?>" class="order-input">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="drag-instruction">
                            <i class="fas fa-arrows-alt"></i> Drag and drop images to reorder them
                        </div>
                        <div style="margin-top: 15px; text-align: right;">
                            <button type="submit" name="reorder_images" value="1" class="btn-primary btn-sm" id="saveOrderBtn">Save Image Order</button>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: var(--bg-secondary); border-radius: 8px; margin-bottom: 20px;">
                        <i class="fas fa-camera" style="font-size: 48px; color: #ccc;"></i>
                        <p style="margin-top: 10px; color: var(--text-secondary);">No images uploaded yet. Add up to 5 images below.</p>
                    </div>
                <?php endif; ?>
                
                <!-- Add New Images Section -->
                <?php if (count($carImages) < 5): ?>
                    <div class="add-images-section">
                        <h3><i class="fas fa-plus-circle"></i> Add More Images (Max 5 total)</h3>
                        <div class="form-group">
                            <label for="car_images">Select Images (JPG, PNG, GIF, WebP, AVIF)</label>
                            <input type="file" id="car_images" name="car_images[]" accept="image/*" multiple>
                            <small>You can select up to <?php echo 5 - count($carImages); ?> more images. Recommended: 800x600px</small>
                            <div id="image-preview-container" class="image-preview-container"></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="message info">
                        <i class="fas fa-info-circle"></i> Maximum 5 images reached. Delete some images to add more.
                    </div>
                <?php endif; ?>
                
                <!-- Single Submit Button at the End -->
                <div class="form-actions">
                    <a href="admin-dashboard.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Save All Changes
                    </button>
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

    // Image preview for multiple files
    const imageInput = document.getElementById('car_images');
    const previewContainer = document.getElementById('image-preview-container');
    
    if (imageInput) {
        imageInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            const files = Array.from(e.target.files);
            const maxNewImages = <?php echo 5 - count($carImages); ?>;
            
            if (files.length > maxNewImages) {
                alert(`You can only add up to ${maxNewImages} more images. You currently have ${<?php echo count($carImages); ?>} images.`);
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
                        removeBtn.className = 'remove-preview';
                        removeBtn.innerHTML = '×';
                        removeBtn.onclick = function() {
                            const dt = new DataTransfer();
                            const currentFiles = Array.from(imageInput.files);
                            const filteredFiles = currentFiles.filter((f, i) => i !== index);
                            filteredFiles.forEach(f => dt.items.add(f));
                            imageInput.files = dt.files;
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
    
    // Drag and drop reordering
    const gallery = document.getElementById('imageGallery');
    if (gallery) {
        let dragSrcElement = null;
        
        function handleDragStart(e) {
            dragSrcElement = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.innerHTML);
            this.classList.add('dragging');
        }
        
        function handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            e.dataTransfer.dropEffect = 'move';
            return false;
        }
        
        function handleDragEnter(e) {
            this.classList.add('drag-over');
        }
        
        function handleDragLeave(e) {
            this.classList.remove('drag-over');
        }
        
        function handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation();
            }
            
            if (dragSrcElement !== this) {
                const parent = this.parentNode;
                const allItems = [...parent.children];
                const dragIndex = allItems.indexOf(dragSrcElement);
                const dropIndex = allItems.indexOf(this);
                
                if (dragIndex < dropIndex) {
                    this.parentNode.insertBefore(dragSrcElement, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(dragSrcElement, this);
                }
                
                updateOrderNumbers();
            }
            
            this.classList.remove('drag-over');
            return false;
        }
        
        function handleDragEnd(e) {
            this.classList.remove('dragging');
            const items = document.querySelectorAll('.image-card');
            items.forEach(item => {
                item.classList.remove('drag-over');
            });
        }
        
        function updateOrderNumbers() {
            const items = document.querySelectorAll('.image-card');
            items.forEach((item, index) => {
                const orderNum = index + 1;
                const orderDiv = item.querySelector('.image-order');
                const orderInput = item.querySelector('.order-input');
                if (orderDiv) orderDiv.textContent = orderNum;
                if (orderInput) orderInput.value = orderNum;
            });
        }
        
        const items = document.querySelectorAll('.image-card');
        items.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragover', handleDragOver);
            item.addEventListener('dragenter', handleDragEnter);
            item.addEventListener('dragleave', handleDragLeave);
            item.addEventListener('drop', handleDrop);
            item.addEventListener('dragend', handleDragEnd);
            item.setAttribute('draggable', 'true');
        });
    }
    
    // Save order button confirmation
    const saveOrderBtn = document.getElementById('saveOrderBtn');
    if (saveOrderBtn) {
        saveOrderBtn.addEventListener('click', function(e) {
            if (!confirm('Save the new image order?')) {
                e.preventDefault();
            }
        });
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
    });
</script>
</body>
</html>
<?php
$conn->close();
?>
