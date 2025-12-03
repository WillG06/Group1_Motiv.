<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: loginPage.php');
    exit;
}

$user = $_SESSION['user'];
$adminId = $user['id'];

$successMessage = '';
$errorMessage = '';

// ADD CAR
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
    
    $imageUrl = '';
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
        $insertQuery = $conn->prepare("
            INSERT INTO cars (agent_id, make_id, model, year, type_id, price_per_day, 
                             deposit_required, description, status_id, city_id, image_url)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insertQuery->bind_param("iisiiidsiis", 
            $adminId, $makeId, $model, $year, $typeId, $pricePerDay, 
            $depositRequired, $description, $statusId, $cityId, $imageUrl
        );
        
        if ($insertQuery->execute()) {
            $successMessage = "Car added successfully! It will now appear in the listings.";
        } else {
            $errorMessage = "Error adding car: " . $conn->error;
        }
        $insertQuery->close();
    }
}

// UPDATE CAR STATUS (form-based, not AJAX)
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

// CANCEL BOOKING
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
    }
    $carQuery->close();
}

// ADD CITY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_city'])) {
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

// UPDATE INQUIRY STATUS
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

// STATS
$stats = [];

$revenueQuery = $conn->query("
    SELECT COALESCE(SUM(total_cost), 0) as total_revenue 
    FROM bookings 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$stats['revenue'] = $revenueQuery ? $revenueQuery->fetch_assoc()['total_revenue'] : 0;

$customersQuery = $conn->query("SELECT COUNT(*) as total_customers FROM customers");
$stats['customers'] = $customersQuery ? $customersQuery->fetch_assoc()['total_customers'] : 0;

$bookingsQuery = $conn->query("
    SELECT COUNT(*) as monthly_bookings 
    FROM bookings 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$stats['bookings'] = $bookingsQuery ? $bookingsQuery->fetch_assoc()['monthly_bookings'] : 0;

$inquiriesQuery = $conn->query("
    SELECT COUNT(*) as new_inquiries 
    FROM contact_inquiries 
    WHERE status = 'new' 
    AND DATE(created_at) >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
");
$stats['inquiries'] = $inquiriesQuery ? $inquiriesQuery->fetch_assoc()['new_inquiries'] : 0;

$carsQuery = $conn->query("SELECT COUNT(*) as total_cars FROM cars");
$stats['cars'] = $carsQuery ? $carsQuery->fetch_assoc()['total_cars'] : 0;

// BOOKING STATUSES
$bookingStatuses = [];
$statusQuery = $conn->query("SELECT booking_status_id, status_name FROM booking_status ORDER BY booking_status_id");
if ($statusQuery) {
    while ($status = $statusQuery->fetch_assoc()) {
        $bookingStatuses[] = $status;
    }
}

// RECENT BOOKINGS
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

// CUSTOMERS
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

// CARS
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
        $carsData[] = $car;
    }
}

// INQUIRIES
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

// CITIES SUMMARY
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

// REPORTS
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

// RECENT ACTIVITY
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

// MAKES
$makes = [];
$makesQuery = $conn->query("SELECT make_id, make_name FROM makes ORDER BY make_name");
if ($makesQuery) {
    while ($make = $makesQuery->fetch_assoc()) {
        $makes[] = $make;
    }
}

// CAR TYPES
$carTypes = [];
$typesQuery = $conn->query("SELECT type_id, type_name FROM car_types ORDER BY type_name");
if ($typesQuery) {
    while ($type = $typesQuery->fetch_assoc()) {
        $carTypes[] = $type;
    }
}

// CAR STATUSES
$carStatuses = [];
$statusQuery = $conn->query("SELECT status_id, status_name FROM car_status ORDER BY status_name");
if ($statusQuery) {
    while ($status = $statusQuery->fetch_assoc()) {
        $carStatuses[] = $status;
    }
}

// CITIES (for add car form)
$cities = [];
$citiesQuery = $conn->query("SELECT city_id, city_name FROM cities ORDER BY city_name");
if ($citiesQuery) {
    while ($city = $citiesQuery->fetch_assoc()) {
        $cities[] = $city;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Motiv — Admin Dashboard</title>

  <!-- Font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Your admin styles -->
  <link rel="stylesheet" href="adminstyles.css" />
</head>
<body>
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">
      <img src="motivlogo.jpg" alt="Motiv" class="brand__logo">
    </div>

    <nav class="side-nav" aria-label="Admin Navigation">
      <button class="side-link active" data-route="dashboard">Dashboard</button>
      <button class="side-link" data-route="inquiries">Inquiries</button>
    </nav>

    <div class="side-bottom">
      <button id="btn-logout" class="btn btn--ghost" onclick="window.location.href='logout.php'">Logout</button>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">
    <header class="main__header">
      <h1>Admin</h1>
      <div class="header-right">
        <button id="admin-theme-toggle" class="icon-btn" aria-label="Toggle dark mode">🌙</button>
        <div class="today-chip" id="today-chip">Today</div>
      </div>
    </header>


    <!-- Flash messages -->
    <?php if (!empty($successMessage)): ?>
      <div class="alert alert--success">
        <?php echo htmlspecialchars($successMessage); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
      <div class="alert alert--error">
        <?php echo htmlspecialchars($errorMessage); ?>
      </div>
    <?php endif; ?>

    <!-- DASHBOARD VIEW -->
    <section class="grid" data-view="dashboard">
      <!-- Income -->
      <article class="card">
        <header class="card__header">
          <h3>Income</h3>
          <select class="range" id="income-range" aria-label="Income range">
            <option value="month" selected>This Month</option>
          </select>
        </header>
        <div class="metric">
          <div class="metric__amount" id="income-amount">
            £ <?php echo number_format($stats['revenue'] ?? 0, 2); ?>
          </div>
          <div class="metric__delta" id="income-delta">This month</div>
        </div>
        <canvas id="income-chart" height="140" aria-label="Income chart"></canvas>
      </article>

      <!-- Car Availability (wide) -->
      <article class="card card--wide">
        <header class="card__header">
          <h3>Car Availability</h3>
          <div class="card__actions">
            <input id="car-search" class="input input--sm" placeholder="Search car/city…">
            <select id="availability-filter" class="input input--sm">
              <option value="all" selected>All</option>
              <option value="available">Available</option>
              <option value="booked">Booked</option>
              <option value="service">In Service</option>
            </select>
            <button class="btn btn--sm" type="button" onclick="showAddCarModal()">Add Car</button>
            <button class="btn btn--sm" type="button" onclick="showAddCityModal()">Add City</button>
          </div>
        </header>

        <div class="table-wrap">
          <table class="table" id="availability-table">
            <thead>
              <tr>
                <th>Car</th>
                <th>Class</th>
                <th>City</th>
                <th>Status</th>
                <th>Rate (£/day)</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($carsData)): ?>
                <?php foreach ($carsData as $car): ?>
                  <tr data-status="<?php echo strtolower($car['status_name']); ?>">
                    <td><?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model'] . ' (' . $car['year'] . ')'); ?></td>
                    <td><?php echo htmlspecialchars($car['type_name']); ?></td>
                    <td><?php echo htmlspecialchars($car['city_name']); ?></td>
                    <td><?php echo htmlspecialchars($car['status_name']); ?></td>
                    <td>£<?php echo number_format($car['price_per_day'], 2); ?></td>
                    <td>
                      <form method="POST" style="display:inline;">
                        <input type="hidden" name="update_car_status" value="1">
                        <input type="hidden" name="car_id" value="<?php echo $car['car_id']; ?>">
                        <?php if ($car['status_id'] == 2): ?>
                          <input type="hidden" name="new_status" value="1">
                          <button type="submit" class="btn btn--sm">Mark Available</button>
                        <?php else: ?>
                          <input type="hidden" name="new_status" value="2">
                          <button type="submit" class="btn btn--sm">Mark Occupied</button>
                        <?php endif; ?>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="6" style="text-align:center;">No cars found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </article>

      <!-- Expenses -->
      <article class="card">
        <header class="card__header">
          <h3>Expenses</h3>
          <select class="range" id="expense-range" aria-label="Expense range">
            <option value="month" selected>This Month</option>
          </select>
        </header>
        <div class="metric">
          <div class="metric__amount" id="expense-amount">£ 0.00</div>
          <div class="metric__delta" id="expense-delta">No data yet</div>
        </div>
        <canvas id="expense-chart" height="140" aria-label="Expenses chart"></canvas>
      </article>

      <!-- Hire vs Cancel -->
      <article class="card">
        <header class="card__header">
          <h3>Hire vs Cancel</h3>
          <select class="range" id="hire-range" aria-label="Hire range">
            <option value="month" selected>This Month</option>
          </select>
        </header>
        <div class="metric metric--row">
          <div>
            <div class="tiny-label">Hires</div>
            <div class="big-number" id="hires-count">
              <?php echo $stats['bookings'] ?? 0; ?>
            </div>
          </div>
          <div>
            <div class="tiny-label">Cancels</div>
            <div class="big-number" id="cancels-count">0</div>
          </div>
        </div>
        <canvas id="hire-chart" height="140" aria-label="Hire vs cancel chart"></canvas>
      </article>

      <!-- Notes -->
      <article class="card">
        <header class="card__header">
          <h3>Notes</h3>
        </header>
        <textarea id="admin-notes" class="textarea" placeholder="Quick notes…"></textarea>
      </article>
    </section>

    <!-- INQUIRIES VIEW -->
    <section class="grid" data-view="inquiries" style="display:none;">
      <article class="card card--wide">
        <header class="card__header">
          <h3>Customer Inquiries</h3>
        </header>

        <div class="table-wrap">
          <table class="table">
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
            <tbody>
              <?php if (!empty($inquiriesData)): ?>
                <?php foreach ($inquiriesData as $inquiry): ?>
                  <tr>
                    <td>#<?php echo $inquiry['inquiry_id']; ?></td>
                    <td><?php echo htmlspecialchars($inquiry['name']); ?></td>
                    <td><?php echo htmlspecialchars($inquiry['email']); ?></td>
                    <td><?php echo htmlspecialchars($inquiry['phone'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($inquiry['subject']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($inquiry['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($inquiry['status'])); ?></td>
                    <td>
                      <button type="button"
                              class="btn btn--sm"
                              onclick="replyToInquiry('<?php echo htmlspecialchars($inquiry['email']); ?>', '<?php echo htmlspecialchars($inquiry['subject']); ?>')">
                        Reply
                      </button>
                      <?php if ($inquiry['status'] === 'new'): ?>
                        <form method="POST" style="display:inline;">
                          <input type="hidden" name="update_inquiry_status" value="1">
                          <input type="hidden" name="inquiry_id" value="<?php echo $inquiry['inquiry_id']; ?>">
                          <input type="hidden" name="new_status" value="replied">
                          <button type="submit" class="btn btn--sm">Mark Replied</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="8" style="text-align:center;">No inquiries found</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </article>
    </section>
  </main>

  <!-- ADD CAR MODAL -->
  <div class="modal" id="addCarModal" style="display:none;">
    <div class="modal__content">
      <header class="modal__header">
        <h3>Add New Car</h3>
        <button class="modal__close" type="button" onclick="closeAddCarModal()">×</button>
      </header>

      <form method="POST" enctype="multipart/form-data">
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

        <div class="form-group">
          <label for="car_image">Car Image</label>
          <input type="file" id="car_image" name="car_image" accept="image/*">
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea id="description" name="description" rows="3" placeholder="Enter car description..."></textarea>
        </div>

        <footer class="modal__footer">
          <button type="button" class="btn btn--ghost" onclick="closeAddCarModal()">Cancel</button>
          <button type="submit" class="btn">Add Car</button>
        </footer>
      </form>
    </div>
  </div>

  <!-- ADD CITY MODAL -->
  <div class="modal" id="addCityModal" style="display:none;">
    <div class="modal__content">
      <header class="modal__header">
        <h3>Add New City</h3>
        <button class="modal__close" type="button" onclick="closeAddCityModal()">×</button>
      </header>

      <form method="POST">
        <input type="hidden" name="add_city" value="1">

        <div class="form-group">
          <label for="city_name">City Name *</label>
          <input type="text" id="city_name" name="city_name" required>
        </div>

        <div class="form-group">
          <label for="region">Region *</label>
          <input type="text" id="region" name="region" required>
        </div>

        <footer class="modal__footer">
          <button type="button" class="btn btn--ghost" onclick="closeAddCityModal()">Cancel</button>
          <button type="submit" class="btn">Add City</button>
        </footer>
      </form>
    </div>
  </div>

  <!-- Your JS -->
  <script src="adminapp.js" defer></script>
  <script>
    // Sidebar route switching
    document.querySelectorAll('.side-link').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.side-link').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const route = btn.dataset.route;
        document.querySelectorAll('section.grid').forEach(sec => {
          sec.style.display = (sec.dataset.view === route) ? 'grid' : 'none';
        });
      });
    });

    // Simple filter for car availability (front-end only)
    const searchInput = document.getElementById('car-search');
    const filterSelect = document.getElementById('availability-filter');
    const carRows = document.querySelectorAll('#availability-table tbody tr');

    function filterCars() {
      const term = (searchInput.value || '').toLowerCase();
      const statusFilter = filterSelect.value;

      carRows.forEach(row => {
        const text = row.innerText.toLowerCase();
        const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
        const matchesTerm = text.includes(term);
        const matchesStatus = statusFilter === 'all' || rowStatus.includes(statusFilter);
        row.style.display = (matchesTerm && matchesStatus) ? '' : 'none';
      });
    }

    if (searchInput && filterSelect) {
      searchInput.addEventListener('input', filterCars);
      filterSelect.addEventListener('change', filterCars);
    }

    // Modals
    function showAddCarModal() {
      document.getElementById('addCarModal').style.display = 'flex';
    }
    function closeAddCarModal() {
      document.getElementById('addCarModal').style.display = 'none';
    }
    function showAddCityModal() {
      document.getElementById('addCityModal').style.display = 'flex';
    }
    function closeAddCityModal() {
      document.getElementById('addCityModal').style.display = 'none';
    }

    document.addEventListener('click', function(e) {
      document.querySelectorAll('.modal').forEach(modal => {
        if (e.target === modal) {
          modal.style.display = 'none';
        }
      });
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
          modal.style.display = 'none';
        });
      }
    });

    // Mailto helpers
    function replyToInquiry(email, subject) {
      const url = `mailto:${email}?subject=${encodeURIComponent('Re: ' + subject)}`;
      window.location.href = url;
    }
  </script>
</body>
</html>
<?php
$conn->close();
?>


