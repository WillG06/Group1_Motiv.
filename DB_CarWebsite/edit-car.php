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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_car'])) {
    $makeId = $_POST['make_id'];
    $model = trim($_POST['model']);
    $year = $_POST['year'];
    $typeId = $_POST['type_id'];
    $pricePerDay = $_POST['price_per_day'];
    $depositRequired = $_POST['deposit_required'] ?? 0;
    $description = trim($_POST['description']);
    $statusId = $_POST['status_id'];
    $cityId = $_POST['city_id'];
    
    $imageUrl = $car['image_url']; // Keep existing image by default
    
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === 0) {
        $uploadDir = 'uploads/cars/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = strtolower(pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $model) . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            $check = getimagesize($_FILES['car_image']['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($_FILES['car_image']['tmp_name'], $targetPath)) {
                    if ($car['image_url'] && file_exists($car['image_url'])) {
                        unlink($car['image_url']);
                    }
                    $imageUrl = $targetPath;
                } else {
                    $errorMessage = "Sorry, there was an error uploading your file.";
                }
            } else {
                $errorMessage = "File is not an image.";
            }
        } else {
            $errorMessage = "Only JPG, JPEG, PNG & GIF files are allowed.";
        }
    }
    
    if (empty($model) || empty($year) || empty($pricePerDay)) {
        $errorMessage = "Please fill in all required fields.";
    } else {
        $updateQuery = $conn->prepare("
            UPDATE cars SET 
                make_id = ?, model = ?, year = ?, type_id = ?, price_per_day = ?, 
                deposit_required = ?, description = ?, status_id = ?, city_id = ?, image_url = ?
            WHERE car_id = ?
        ");
        $updateQuery->bind_param("isiiidsiisi", 
            $makeId, $model, $year, $typeId, $pricePerDay, 
            $depositRequired, $description, $statusId, $cityId, $imageUrl, $carId
        );
        
        if ($updateQuery->execute()) {
            $successMessage = "Car updated successfully!";
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
    <title>Edit Car - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
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
            max-width: 800px;
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
        
        .car-image-preview {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .car-image-preview img {
            max-width: 400px;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .no-image {
            width: 400px;
            height: 300px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 1.1rem;
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
        <p>Update car details and information</p>
    </div>
</section>

<section class="edit-car-container">
    <div class="container">
        <a href="admin-dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="edit-car-content">
            <?php if (isset($successMessage)): ?>
                <div style="background: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($successMessage); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($errorMessage)): ?>
                <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
            
            <div class="car-image-preview">
                <?php if ($car['image_url']): ?>
                    <img src="<?php echo htmlspecialchars($car['image_url']); ?>" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                <?php else: ?>
                    <div class="no-image">No Image Available</div>
                <?php endif; ?>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_car" value="1">
                
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
                    <label for="car_image">Update Car Image</label>
                    <input type="file" id="car_image" name="car_image" accept="image/*">
                    <small>Leave empty to keep current image. Recommended: 800x600px, JPG, PNG, GIF or WebP format</small>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4" placeholder="Enter car description..."><?php echo htmlspecialchars($car['description']); ?></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 30px;">
                    <a href="admin-dashboard.php" class="btn-secondary">Cancel</a>
                    <button type="submit" class="btn-primary">Update Car</button>
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

</body>
</html>
<?php
$conn->close();
?>