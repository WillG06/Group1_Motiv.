<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: loginPage.php');
    exit;
}

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
    
    // Get image path before deleting
    $getImageQuery = $conn->prepare("SELECT image_url FROM car_images WHERE image_id = ? AND car_id = ?");
    $getImageQuery->bind_param("ii", $imageId, $carId);
    $getImageQuery->execute();
    $imageResult = $getImageQuery->get_result();
    
    if ($imageResult->num_rows > 0) {
        $imageData = $imageResult->fetch_assoc();
        $imagePath = $imageData['image_url'];
        
        // Delete from database
        $deleteQuery = $conn->prepare("DELETE FROM car_images WHERE image_id = ? AND car_id = ?");
        $deleteQuery->bind_param("ii", $imageId, $carId);
        
        if ($deleteQuery->execute()) {
            // Delete physical file
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

// Handle adding new images
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_images'])) {
    $uploadedImages = [];
    $uploadErrors = [];
    
    // Get current image count
    $currentImages = getCarImages($conn, $carId);
    $currentCount = count($currentImages);
    $maxFiles = 5;
    
    if (isset($_FILES['car_images']) && !empty($_FILES['car_images']['name'][0])) {
        $uploadDir = 'uploads/cars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        $files = $_FILES['car_images'];
        
        // Count how many files were actually selected
        $fileCount = 0;
        foreach ($files['name'] as $name) {
            if (!empty($name)) $fileCount++;
        }
        
        if ($currentCount + $fileCount > $maxFiles) {
            $errorMessage = "You can have a maximum of $maxFiles images per car. You currently have $currentCount images and are trying to add $fileCount more.";
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
                    $uploadErrors[] = "File $originalName: Only JPG, JPEG, PNG, GIF, WebP & AVIF files are allowed.";
                    continue;
                }
                
                // Validate image
                $check = getimagesize($tmpName);
                if ($check === false) {
                    $uploadErrors[] = "File $originalName is not a valid image.";
                    continue;
                }
                
                // Generate unique filename
                $model = preg_replace('/[^a-zA-Z0-9]/', '_', $car['model'] ?? 'car');
                $fileName = uniqid() . '_' . $model . '_' . ($i+1) . '.' . $fileExtension;
                $targetPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $uploadedImages[] = $targetPath;
                } else {
                    $uploadErrors[] = "Sorry, there was an error uploading $originalName.";
                }
            }
        }
    }
    
    if (!empty($uploadErrors)) {
        $errorMessage = implode(" ", $uploadErrors);
    } elseif (!empty($uploadedImages)) {
        // Get the highest display order
        $maxOrderQuery = $conn->prepare("SELECT COALESCE(MAX(display_order), 0) as max_order FROM car_images WHERE car_id = ?");
        $maxOrderQuery->bind_param("i", $carId);
        $maxOrderQuery->execute();
        $maxOrderResult = $maxOrderQuery->get_result();
        $maxOrderData = $maxOrderResult->fetch_assoc();
        $nextOrder = $maxOrderData['max_order'] + 1;
        $maxOrderQuery->close();
        
        // Insert new images
        $imageInsertQuery = $conn->prepare("INSERT INTO car_images (car_id, image_url, display_order) VALUES (?, ?, ?)");
        $displayOrder = $nextOrder;
        $allSuccess = true;
        
        foreach ($uploadedImages as $imagePath) {
            $imageInsertQuery->bind_param("isi", $carId, $imagePath, $displayOrder);
            if (!$imageInsertQuery->execute()) {
                $errorMessage = "Error adding images: " . $conn->error;
                $allSuccess = false;
                break;
            }
            $displayOrder++;
        }
        $imageInsertQuery->close();
        
        if ($allSuccess && empty($errorMessage)) {
            $successMessage = count($uploadedImages) . " image(s) added successfully!";
            // Refresh car images
            $carImages = getCarImages($conn, $carId);
        }
    }
}

// Get car details
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

// Handle car details update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_car_details'])) {
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
            $successMessage = "Car details updated successfully!";
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
        } else {
            $errorMessage = "Error updating car: " . $conn->error;
        }
        $updateQuery->close();
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
        .edit-car-container {
            padding: 40px 0;
            background-color: #f5f5f5;
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
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
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
            border-bottom: 2px solid #eee;
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
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            background: #f9f9f9;
            transition: transform 0.2s;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
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
            background: #f0f8ff;
        }
        
        .image-info {
            padding: 8px;
            text-align: center;
            font-size: 12px;
            color: #666;
            background: white;
        }
        
        .add-images-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px dashed #ddd;
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
            border: 1px solid #ddd;
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
            background: #f8f9fa;
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
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--cobalt-blue);
            outline: none;
        }
        
        .btn-primary, .btn-secondary, .btn-danger {
            padding: 10px 20px;
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
        
        .current-images-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .image-count {
            font-size: 14px;
            color: #666;
        }
        
        .drag-instruction {
            font-size: 12px;
            color: #999;
            text-align: center;
            margin-top: 10px;
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
                <li><a href="landing.php">Home</a></li>
                <li><a href="admin-dashboard.php">Admin Dashboard</a></li>
                <li>
                    <a href="logout.php" style="color: #ff4444;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
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
            
            <!-- Car Details Form (FIRST) -->
            <h2 class="section-title">
                <i class="fas fa-car"></i> Car Details
            </h2>
            
            <form method="POST">
                <input type="hidden" name="update_car_details" value="1">
                
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
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                    <a href="admin-dashboard.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Update Car Details</button>
                </div>
            </form>
            
            <!-- Images Management Section (SECOND) -->
            <h2 class="section-title">
                <i class="fas fa-images"></i> Car Images
                <span class="image-count">(<?php echo count($carImages); ?>/5 images)</span>
            </h2>
            
            <?php if (!empty($carImages)): ?>
                <div class="reorder-section">
                    <form method="POST" id="reorderForm">
                        <input type="hidden" name="reorder_images" value="1">
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
                            <button type="submit" class="btn-primary btn-sm" id="saveOrderBtn">Save Image Order</button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 8px; margin-bottom: 20px;">
                    <i class="fas fa-camera" style="font-size: 48px; color: #ccc;"></i>
                    <p style="margin-top: 10px; color: #666;">No images uploaded yet. Add up to 5 images below.</p>
                </div>
            <?php endif; ?>
            
            <!-- Add New Images Section -->
            <?php if (count($carImages) < 5): ?>
                <div class="add-images-section">
                    <h3><i class="fas fa-plus-circle"></i> Add More Images (Max 5 total)</h3>
                    <form method="POST" enctype="multipart/form-data" id="addImagesForm">
                        <input type="hidden" name="add_images" value="1">
                        <div class="form-group">
                            <label for="car_images">Select Images (JPG, PNG, GIF, WebP, AVIF)</label>
                            <input type="file" id="car_images" name="car_images[]" accept="image/*" multiple>
                            <small>You can select up to <?php echo 5 - count($carImages); ?> more images. Recommended: 800x600px</small>
                            <div id="image-preview-container" class="image-preview-container"></div>
                        </div>
                        <button type="submit" class="btn-primary">Upload Images</button>
                    </form>
                </div>
            <?php else: ?>
                <div class="message info">
                    <i class="fas fa-info-circle"></i> Maximum 5 images reached. Delete some images to add more.
                </div>
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
                // Reorder the DOM elements
                const parent = this.parentNode;
                const allItems = [...parent.children];
                const dragIndex = allItems.indexOf(dragSrcElement);
                const dropIndex = allItems.indexOf(this);
                
                if (dragIndex < dropIndex) {
                    this.parentNode.insertBefore(dragSrcElement, this.nextSibling);
                } else {
                    this.parentNode.insertBefore(dragSrcElement, this);
                }
                
                // Update order numbers
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
</script>
</body>
</html>
<?php
$conn->close();
?>
