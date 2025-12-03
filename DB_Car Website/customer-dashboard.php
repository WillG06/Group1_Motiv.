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
<html lang="en" data-theme="light">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Motiv — Customer Home</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- your main customer styles -->
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <!-- Background slider -->
  <div class="bg-slider" aria-hidden="true">
    <img class="bg-slide active" src="ModelSPlaid.png.webp" alt="">
    <img class="bg-slide" src="C63s.jpg" alt="">
    <img class="bg-slide" src="BMW.jpg" alt="">
    <div class="bg-dim"></div>
  </div>
  
  <!-- Top bar -->
  <header class="topbar">
    <a class="brand" href="landing.php">
      <img src="motivlogo.jpg" alt="Motiv logo" class="brand__logo" />
    </a>

    <nav class="topbar__center" aria-label="Primary">
      <!-- Favourites button (you can later connect this to $favorites_count if you want) -->
      <button class="icon-btn" aria-label="Saved (Favourites)" id="btn-favourites" title="Favourites">
        <span class="icon">♡</span>
      </button>

      <label class="language-label" for="lang-select">Language</label>
      <select id="lang-select" class="language-select" aria-label="Language">
        <option value="en" selected>English</option>
        <option value="ar">العربية (Arabic)</option>
        <option value="fr">Français</option>
        <option value="tr">Türkçe</option>
      </select>

      <a class="link" href="contact.php" id="nav-contact">Contact Us</a>

      <!-- Profile dropdown -->
      <details class="profile" id="profile-menu">
        <summary class="profile__summary">
          <span class="avatar" aria-hidden="true">👤</span>
          <span>
            <?php echo htmlspecialchars($customer['first_name'] ?? 'Profile'); ?>
          </span>
        </summary>
        <div class="profile__dropdown">
          <a href="customer-dashboard.php">Account</a>
          <a href="customer-dashboard.php">My Bookings</a>
          <a href="logout.php">Logout</a>
        </div>
      </details>
    </nav>
  </header>

  <main>
    <!-- Search card -->
    <section class="card card--search" aria-labelledby="search-title">
      <h2 class="card__title" id="search-title">Cars</h2>

      <form id="search-form" class="search-grid" autocomplete="off">
        <div class="field">
          <label for="location" class="label">Pickup &amp; Return</label>
          <div class="sub-label">Location</div>
          <input
            list="location-list"
            id="location"
            name="location"
            class="input"
            placeholder="Type a city, airport or branch"
            required
          />
          <datalist id="location-list">
            <option value="London"></option>
            <option value="Birmingham"></option>
            <option value="Manchester"></option>
            <option value="Leeds"></option>
            <option value="Liverpool"></option>
            <option value="Glasgow"></option>
            <option value="Edinburgh"></option>
            <option value="Bristol"></option>
            <option value="Newcastle"></option>
            <option value="Nottingham"></option>
          </datalist>
        </div>

        <div class="field">
          <label class="label" for="pickup-date">Pickup Date &amp; Time</label>
          <div class="field-row">
            <input type="date" id="pickup-date" class="input" required />
            <select id="pickup-time" class="input" required></select>
          </div>
        </div>

        <div class="field">
          <label class="label" for="dropoff-date">Return date &amp; time</label>
          <div class="field-row">
            <input type="date" id="dropoff-date" class="input" required />
            <select id="dropoff-time" class="input" required></select>
          </div>
        </div>

        <div class="actions">
          <button type="submit" class="btn btn--primary" id="btn-available">
            Available Cars
          </button>
        </div>
      </form>
      <p class="hint" id="duration-hint"></p>
    </section>

    <!-- Results / featured cars -->
    <section class="results" aria-live="polite" aria-busy="false">
      <div class="placeholder">
        <p>Search to see available cars, or browse featured picks below.</p>
      </div>
      <div id="featured" class="grid-cards"></div>
      <div id="results" class="grid-cards hidden"></div>
    </section>
  </main>

  <!-- Footer -->
  <footer class="site-footer">
    <div class="footer-search-box">
      <h3>Find Cars by City</h3>
      <p class="footer-sub">Quickly browse available cars in your preferred UK city.</p>

      <div class="footer-search-form">
        <select id="uk-city" class="footer-city-select" aria-label="Choose a city">
          <option value="" disabled selected>Select a city</option>
          <option>London</option>
          <option>Birmingham</option>
          <option>Manchester</option>
          <option>Leeds</option>
          <option>Liverpool</option>
          <option>Glasgow</option>
          <option>Edinburgh</option>
          <option>Bristol</option>
          <option>Newcastle</option>
          <option>Nottingham</option>
        </select>
        <button id="city-go" class="btn btn--primary">Search Cars</button>
      </div>
    </div>

    <div class="footer-credit">
      <p>&copy; 2025 Motiv Rentals. All rights reserved.</p>
    </div>
  </footer>

  <!-- Template for JS-rendered car cards -->
  <template id="car-card-tpl">
    <article class="car-card">
      <div class="car-card__media">
        <img loading="lazy" alt="" />
      </div>
      <div class="car-card__body">
        <h3 class="car-card__title"></h3>
        <p class="car-card__meta"></p>
        <div class="car-card__price"></div>
        <button class="btn btn--outline car-card__book">View &amp; Book</button>
      </div>
    </article>
  </template>

  <!-- Your customer JS app -->
  <script src="app.js" defer></script>
</body>
</html>

