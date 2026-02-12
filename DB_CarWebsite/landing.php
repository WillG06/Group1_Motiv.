<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>

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

$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : 'light';
$fontSize = isset($_COOKIE['fontSize']) ? $_COOKIE['fontSize'] : '100';
$language = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en';
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
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --text-primary: #333333;
            --text-secondary: #666666;
            --card-bg: #ffffff;
            --border-color: #e0e0e0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --vivid-indigo: #4b2e9b;
            --cobalt-blue: #0047ab;
            --coral-red: #ff7f50;
            --dark-magenta: #8b008b;
            --footer-bg: #2c3e50;
            --footer-text: #ecf0f1;
        }

        [data-theme="dark"] {
            --bg-primary: #1a1a1a;
            --bg-secondary: #2d2d2d;
            --text-primary: #ffffff;
            --text-secondary: #cccccc;
            --card-bg: #333333;
            --border-color: #404040;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --footer-bg: #000000;
            --footer-text: #ffffff;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: <?php echo $fontSize; ?>%;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        header, .booking-form, .service-card, .city-card, .feature-card {
            background-color: var(--card-bg);
            color: var(--text-primary);
        }

        .footer-content {
            background-color: var(--footer-bg);
            color: var(--footer-text);
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
            color: var(--text-primary);
        }

        .language-selector:hover > a {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .language-settings-dropdown {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            min-width: 240px;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
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
            padding: 15px 18px;
            border-bottom: 1px solid #e0e0e0;
        }

        [data-theme="dark"] .settings-section {
            border-color: #404040;
        }

        .settings-section:last-child {
            border-bottom: none;
        }

        .settings-section h4 {
            margin: 0 0 12px 0;
            color: #333;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }

        [data-theme="dark"] .settings-section h4 {
            color: #fff;
        }

        .theme-option, .language-option {
            display: flex;
            align-items: center;
            padding: 10px 14px;
            color: #333;
            text-decoration: none;
            transition: background-color 0.2s;
            border-radius: 6px;
            margin-bottom: 4px;
            font-size: 15px;
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
            width: 20px;
            margin-right: 12px;
            color: #4b2e9b;
            font-size: 16px;
        }

        [data-theme="dark"] .theme-option i, 
        [data-theme="dark"] .language-option i {
            color: #9b7bff;
        }

        .font-controls {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .font-btn {
            background: #4b2e9b;
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .font-btn:hover {
            background: #8b008b;
        }

        .font-size-display {
            font-size: 15px;
            color: #333;
            min-width: 60px;
            text-align: center;
            font-weight: 600;
        }

        [data-theme="dark"] .font-size-display {
            color: #fff;
        }

        .active-indicator {
            margin-left: auto;
            color: #4b2e9b;
        }

        [data-theme="dark"] .active-indicator {
            color: #9b7bff;
        }

        .best-selling-section {
            padding: 15px 0;
            background-color: var(--bg-secondary);
        }

        .best-selling-section .section-title {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
            font-size: 2.2rem;
        }

        .section-subtitle {
            text-align: center;
            margin-bottom: 40px;
            color: var(--text-secondary);
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
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px var(--shadow-color);
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
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .service-description {
            margin-bottom: 10px;
            color: var(--text-secondary);
            line-height: 1.5;
            flex-grow: 0;
        }

        .service-details {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
            padding: 10px 0;
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
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
            color: var(--text-secondary);
            margin-bottom: 3px;
        }

        .detail-value {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--vivid-indigo);
        }

        .testimonial {
            background: var(--bg-secondary);
            padding: 12px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .testimonial p {
            font-style: italic;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .testimonial-author {
            font-size: 0.85rem;
            color: var(--text-secondary);
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
            box-shadow: 0 2px 10px var(--shadow-color);
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
<body data-theme="<?php echo $darkMode; ?>">
  
<header>
    <div class="container header-content">
        <div class="logo">
            <img src="logo2.png" alt="Logo">
        </div>

        <nav>
            <ul>
                <li class="dropdown">
                    <a href="landing.php" class="dropbtn"><?php 
                        $homeText = 'Home';
                        $aboutText = 'About';
                        if ($language == 'es') { $homeText = 'Inicio'; $aboutText = 'Acerca de'; }
                        elseif ($language == 'fr') { $homeText = 'Accueil'; $aboutText = 'À propos'; }
                        elseif ($language == 'de') { $homeText = 'Startseite'; $aboutText = 'Über uns'; }
                        echo $homeText; ?> <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="landing.php"><?php echo $homeText; ?></a>
                        <a href="about.php"><?php echo $aboutText; ?></a>
                    </div>
                </li>
                <li><a href="cars.php" class="active"><?php 
                    $carsText = 'Cars';
                    if ($language == 'es') { $carsText = 'Autos'; }
                    elseif ($language == 'fr') { $carsText = 'Voitures'; }
                    elseif ($language == 'de') { $carsText = 'Autos'; }
                    echo $carsText;
                ?></a></li>
                <li><a href="contact.php"><?php 
                    $contactText = 'Contact';
                    if ($language == 'es') { $contactText = 'Contacto'; }
                    elseif ($language == 'fr') { $contactText = 'Contact'; }
                    elseif ($language == 'de') { $contactText = 'Kontakt'; }
                    echo $contactText;
                ?></a></li>

                <?php if (!isset($_SESSION['user'])): ?>
                    <li><a href="loginPage.php"><?php 
                        $loginText = 'Login';
                        if ($language == 'es') { $loginText = 'Iniciar sesión'; }
                        elseif ($language == 'fr') { $loginText = 'Connexion'; }
                        elseif ($language == 'de') { $loginText = 'Anmelden'; }
                        echo $loginText;
                    ?></a></li>
                <?php else: ?>
                    <li><a href="customer-dashboard.php"><?php 
                        $dashboardText = 'Dashboard';
                        if ($language == 'es') { $dashboardText = 'Panel'; }
                        elseif ($language == 'fr') { $dashboardText = 'Tableau de bord'; }
                        elseif ($language == 'de') { $dashboardText = 'Dashboard'; }
                        echo $dashboardText;
                    ?></a></li>
                    <li>
                        <a href="logout.php" style="color: #ff7f50;">
                            <i class="fas fa-sign-out-alt"></i> <?php 
                                $logoutText = 'Logout';
                                if ($language == 'es') { $logoutText = 'Cerrar sesión'; }
                                elseif ($language == 'fr') { $logoutText = 'Déconnexion'; }
                                elseif ($language == 'de') { $logoutText = 'Abmelden'; }
                                echo $logoutText;
                            ?>
                        </a>
                    </li>
                <?php endif; ?>

                <li class="language-selector">
                    <a href="#"><i class="fas fa-globe"></i></a>
                    <div class="language-settings-dropdown">
                        <div class="settings-section">
                            <h4><?php 
                                $themeText = 'Theme';
                                if ($language == 'es') { $themeText = 'Tema'; }
                                elseif ($language == 'fr') { $themeText = 'Thème'; }
                                elseif ($language == 'de') { $themeText = 'Design'; }
                                echo $themeText;
                            ?></h4>
                            <a href="#" class="theme-option" data-theme="light">
                                <i class="fas fa-sun"></i> <?php 
                                    $lightText = 'Light';
                                    if ($language == 'es') { $lightText = 'Claro'; }
                                    elseif ($language == 'fr') { $lightText = 'Clair'; }
                                    elseif ($language == 'de') { $lightText = 'Hell'; }
                                    echo $lightText;
                                ?>
                                <?php if ($darkMode == 'light'): ?>
                                    <i class="fas fa-check active-indicator"></i>
                                <?php endif; ?>
                            </a>
                            <a href="#" class="theme-option" data-theme="dark">
                                <i class="fas fa-moon"></i> <?php 
                                    $darkText = 'Dark';
                                    if ($language == 'es') { $darkText = 'Oscuro'; }
                                    elseif ($language == 'fr') { $darkText = 'Sombre'; }
                                    elseif ($language == 'de') { $darkText = 'Dunkel'; }
                                    echo $darkText;
                                ?>
                                <?php if ($darkMode == 'dark'): ?>
                                    <i class="fas fa-check active-indicator"></i>
                                <?php endif; ?>
                            </a>
                        </div>

                        <div class="settings-section">
                            <h4><?php 
                                $fontSizeText = 'Font Size';
                                if ($language == 'es') { $fontSizeText = 'Tamaño de fuente'; }
                                elseif ($language == 'fr') { $fontSizeText = 'Taille de police'; }
                                elseif ($language == 'de') { $fontSizeText = 'Schriftgröße'; }
                                echo $fontSizeText;
                            ?></h4>
                            <div class="font-controls">
                                <button class="font-btn" id="font-decrease">A-</button>
                                <span class="font-size-display" id="font-size-display"><?php echo $fontSize; ?>%</span>
                                <button class="font-btn" id="font-increase">A+</button>
                                <button class="font-btn" id="font-reset"><?php 
                                    $resetText = 'Reset';
                                    if ($language == 'es') { $resetText = 'Reiniciar'; }
                                    elseif ($language == 'fr') { $resetText = 'Réinitialiser'; }
                                    elseif ($language == 'de') { $resetText = 'Zurücksetzen'; }
                                    echo $resetText;
                                ?></button>
                            </div>
                        </div>

                        <div class="settings-section">
                            <h4><?php 
                                $languageText = 'Language';
                                if ($language == 'es') { $languageText = 'Idioma'; }
                                elseif ($language == 'fr') { $languageText = 'Langue'; }
                                elseif ($language == 'de') { $languageText = 'Sprache'; }
                                echo $languageText;
                            ?></h4>
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
                <h1><?php 
                    $heroTitle = 'Motiv, Car Rental';
                    if ($language == 'es') { $heroTitle = 'Motiv, Alquiler de Autos'; }
                    elseif ($language == 'fr') { $heroTitle = 'Motiv, Location de Voitures'; }
                    elseif ($language == 'de') { $heroTitle = 'Motiv, Autovermietung'; }
                    echo $heroTitle;
                ?></h1>
                <p><?php 
                    $heroText = 'At Motiv, we make car hire enjoyable! With flexible pick-up options, a variety of quality vehicles, and smooth booking, every journey feels effortless.';
                    if ($language == 'es') { $heroText = '¡En Motiv, hacemos que el alquiler de autos sea agradable! Con opciones flexibles de recogida, una variedad de vehículos de calidad y reservas sin problemas, cada viaje se siente sin esfuerzo.'; }
                    elseif ($language == 'fr') { $heroText = 'Chez Motiv, nous rendons la location de voitures agréable ! Avec des options de prise en charge flexibles, une variété de véhicules de qualité et une réservation fluide, chaque voyage semble sans effort.'; }
                    elseif ($language == 'de') { $heroText = 'Bei Motiv machen wir die Autovermietung angenehm! Mit flexiblen Abholmöglichkeiten, einer Vielzahl von Qualitätsfahrzeugen und reibungsloser Buchung fühlt sich jede Reise mühelos an.'; }
                    echo $heroText;
                ?></p>
            </div>
            <div class="booking-form">
                <h2><?php 
                    $reserveText = 'Reserve a Vehicle';
                    if ($language == 'es') { $reserveText = 'Reservar un Vehículo'; }
                    elseif ($language == 'fr') { $reserveText = 'Réserver un Véhicule'; }
                    elseif ($language == 'de') { $reserveText = 'Fahrzeug reservieren'; }
                    echo $reserveText;
                ?></h2>
                <form id="bookingForm" method="POST">
                    <input type="hidden" name="search_cars" value="1">
                    
                    <div class="form-group">
                        <label for="pickup-location"><?php 
                            $pickupLocationText = 'Pick-up Location';
                            if ($language == 'es') { $pickupLocationText = 'Lugar de recogida'; }
                            elseif ($language == 'fr') { $pickupLocationText = 'Lieu de prise en charge'; }
                            elseif ($language == 'de') { $pickupLocationText = 'Abholort'; }
                            echo $pickupLocationText;
                        ?></label>
                        <select id="pickup-location" name="pickup_location" required>
                            <option value=""><?php 
                                $selectLocationText = 'Select a location';
                                if ($language == 'es') { $selectLocationText = 'Selecciona un lugar'; }
                                elseif ($language == 'fr') { $selectLocationText = 'Sélectionnez un lieu'; }
                                elseif ($language == 'de') { $selectLocationText = 'Wählen Sie einen Ort'; }
                                echo $selectLocationText;
                            ?></option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['city_id']; ?>">
                                    <?php echo htmlspecialchars($city['city_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php 
                            $pickupDateTimeText = 'Pick-up Date & Time';
                            if ($language == 'es') { $pickupDateTimeText = 'Fecha y hora de recogida'; }
                            elseif ($language == 'fr') { $pickupDateTimeText = 'Date et heure de prise en charge'; }
                            elseif ($language == 'de') { $pickupDateTimeText = 'Abholdatum und -zeit'; }
                            echo $pickupDateTimeText;
                        ?></label>
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
                        <label><?php 
                            $dropoffDateTimeText = 'Drop-off Date & Time';
                            if ($language == 'es') { $dropoffDateTimeText = 'Fecha y hora de devolución'; }
                            elseif ($language == 'fr') { $dropoffDateTimeText = 'Date et heure de restitution'; }
                            elseif ($language == 'de') { $dropoffDateTimeText = 'Rückgabedatum und -zeit'; }
                            echo $dropoffDateTimeText;
                        ?></label>
                        <div class="date-time-group">
                            <div>
                                <input type="date" id="dropoff-date" name="dropoff_date" required>
                            </div>
                            <div>
                                <input type="time" id="dropoff-time" name="dropoff_time" value="12:00" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="book-btn"><?php 
                        $showCarsText = 'Show Available Cars';
                        if ($language == 'es') { $showCarsText = 'Mostrar Autos Disponibles'; }
                        elseif ($language == 'fr') { $showCarsText = 'Afficher les Voitures Disponibles'; }
                        elseif ($language == 'de') { $showCarsText = 'Verfügbare Autos anzeigen'; }
                        echo $showCarsText;
                    ?></button>
                </form>
            </div>
        </div>
    </section>

<section class="features">
    <div class="container">
        <h2 class="section-title"><?php 
            $whyChooseText = 'Why Choose Motiv?';
            if ($language == 'es') { $whyChooseText = '¿Por qué elegir Motiv?'; }
            elseif ($language == 'fr') { $whyChooseText = 'Pourquoi choisir Motiv?'; }
            elseif ($language == 'de') { $whyChooseText = 'Warum Motiv wählen?'; }
            echo $whyChooseText;
        ?></h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <img src="cars1.png" alt="Vehicle Selection">
                </div>
                <h3><?php 
                    $vehicleSelectionText = 'Wide Vehicle Selection';
                    if ($language == 'es') { $vehicleSelectionText = 'Amplia selección de vehículos'; }
                    elseif ($language == 'fr') { $vehicleSelectionText = 'Large sélection de véhicules'; }
                    elseif ($language == 'de') { $vehicleSelectionText = 'Große Fahrzeugauswahl'; }
                    echo $vehicleSelectionText;
                ?></h3>
                <p><?php 
                    $vehicleDescText = 'Choose from economy cars, premium sedans, SUVs, and electric vehicles to suit your needs.';
                    if ($language == 'es') { $vehicleDescText = 'Elija entre autos económicos, sedanes premium, SUV y vehículos eléctricos que se adapten a sus necesidades.'; }
                    elseif ($language == 'fr') { $vehicleDescText = 'Choisissez parmi les voitures économiques, les berlines premium, les SUV et les véhicules électriques adaptés à vos besoins.'; }
                    elseif ($language == 'de') { $vehicleDescText = 'Wählen Sie aus sparsamen Autos, Premium-Limousinen, SUVs und Elektrofahrzeugen, die Ihren Bedürfnissen entsprechen.'; }
                    echo $vehicleDescText;
                ?></p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="location1.png" alt="Convenient Locations">
                </div>
                <h3><?php 
                    $locationsText = 'Convenient Locations';
                    if ($language == 'es') { $locationsText = 'Ubicaciones convenientes'; }
                    elseif ($language == 'fr') { $locationsText = 'Emplacements pratiques'; }
                    elseif ($language == 'de') { $locationsText = 'Praktische Standorte'; }
                    echo $locationsText;
                ?></h3>
                <p><?php 
                    $locationsDescText = 'Multiple pickup and drop-off locations across Birmingham for your convenience.';
                    if ($language == 'es') { $locationsDescText = 'Múltiples ubicaciones de recogida y devolución en Birmingham para su conveniencia.'; }
                    elseif ($language == 'fr') { $locationsDescText = 'Plusieurs lieux de prise en charge et de restitution à Birmingham pour votre commodité.'; }
                    elseif ($language == 'de') { $locationsDescText = 'Mehrere Abhol- und Rückgabeorte in Birmingham für Ihre Bequemlichkeit.'; }
                    echo $locationsDescText;
                ?></p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="money1.png" alt="Best Price Guarantee">
                </div>
                <h3><?php 
                    $priceGuaranteeText = 'Best Price Guarantee';
                    if ($language == 'es') { $priceGuaranteeText = 'Mejor precio garantizado'; }
                    elseif ($language == 'fr') { $priceGuaranteeText = 'Meilleur prix garanti'; }
                    elseif ($language == 'de') { $priceGuaranteeText = 'Bester Preis garantiert'; }
                    echo $priceGuaranteeText;
                ?></h3>
                <p><?php 
                    $priceDescText = 'We offer competitive rates with no hidden fees and a best price guarantee.';
                    if ($language == 'es') { $priceDescText = 'Ofrecemos tarifas competitivas sin cargos ocultos y una garantía de mejor precio.'; }
                    elseif ($language == 'fr') { $priceDescText = 'Nous offrons des tarifs compétitifs sans frais cachés et une garantie du meilleur prix.'; }
                    elseif ($language == 'de') { $priceDescText = 'Wir bieten wettbewerbsfähige Preise ohne versteckte Gebühren und eine Bestpreisgarantie.'; }
                    echo $priceDescText;
                ?></p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <img src="cust1.png" alt="Customer Support">
                </div>
                <h3><?php 
                    $supportText = '24/7 Support';
                    if ($language == 'es') { $supportText = 'Soporte 24/7'; }
                    elseif ($language == 'fr') { $supportText = 'Assistance 24/7'; }
                    elseif ($language == 'de') { $supportText = '24/7 Support'; }
                    echo $supportText;
                ?></h3>
                <p><?php 
                    $supportDescText = 'Our customer service team is available around the clock to assist you.';
                    if ($language == 'es') { $supportDescText = 'Nuestro equipo de servicio al cliente está disponible las 24 horas para ayudarlo.'; }
                    elseif ($language == 'fr') { $supportDescText = 'Notre équipe de service client est disponible 24h/24 pour vous aider.'; }
                    elseif ($language == 'de') { $supportDescText = 'Unser Kundenservice-Team steht Ihnen rund um die Uhr zur Verfügung.'; }
                    echo $supportDescText;
                ?></p>
            </div>
        </div>
    </div>
</section>

<section class="cities-section">
    <h2 class="section-title"><?php 
        $topCitiesText = 'Top Cities for Car Hire';
        if ($language == 'es') { $topCitiesText = 'Principales ciudades para alquiler de autos'; }
        elseif ($language == 'fr') { $topCitiesText = 'Meilleures villes pour la location de voitures'; }
        elseif ($language == 'de') { $topCitiesText = 'Top-Städte für die Autovermietung'; }
        echo $topCitiesText;
    ?></h2>

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
            <h2 class="section-title"><?php 
                $bestSellingText = 'Best Selling Services';
                if ($language == 'es') { $bestSellingText = 'Servicios más vendidos'; }
                elseif ($language == 'fr') { $bestSellingText = 'Services les plus vendus'; }
                elseif ($language == 'de') { $bestSellingText = 'Bestseller-Dienstleistungen'; }
                echo $bestSellingText;
            ?></h2>
            <a href="cars.php" class="view-all-btn"><?php 
                $viewAllText = 'View All Listings';
                if ($language == 'es') { $viewAllText = 'Ver todos los listados'; }
                elseif ($language == 'fr') { $viewAllText = 'Voir toutes les annonces'; }
                elseif ($language == 'de') { $viewAllText = 'Alle Angebote anzeigen'; }
                echo $viewAllText;
            ?></a>
        </div>
        <p class="section-subtitle"><?php 
            $popularText = 'Our most popular rental options with top customer ratings';
            if ($language == 'es') { $popularText = 'Nuestras opciones de alquiler más populares con las mejores calificaciones de los clientes'; }
            elseif ($language == 'fr') { $popularText = 'Nos options de location les plus populaires avec les meilleures évaluations des clients'; }
            elseif ($language == 'de') { $popularText = 'Unsere beliebtesten Mietoptionen mit den besten Kundenbewertungen'; }
            echo $popularText;
        ?></p>
        
        <div class="services-scroll-container">
            <div class="services-scroll">
                <div class="service-card">
                    <div class="service-image">
                        <img src="car_pics/car1.png" alt="Premium SUV">
                        <div class="service-badge"><?php 
                            $popularBadgeText = 'Most Popular';
                            if ($language == 'es') { $popularBadgeText = 'Más popular'; }
                            elseif ($language == 'fr') { $popularBadgeText = 'Le plus populaire'; }
                            elseif ($language == 'de') { $popularBadgeText = 'Am beliebtesten'; }
                            echo $popularBadgeText;
                        ?></div>
                    </div>
                    <div class="service-content">
                        <h3><?php 
                            $suvText = 'Premium SUV';
                            if ($language == 'es') { $suvText = 'SUV Premium'; }
                            elseif ($language == 'fr') { $suvText = 'SUV Premium'; }
                            elseif ($language == 'de') { $suvText = 'Premium SUV'; }
                            echo $suvText;
                        ?></h3>
                        <div class="service-rating">
                            <span class="stars">★★★★★</span>
                            <span class="rating-text">5/5 (128 <?php 
                                $reviewsText = 'reviews';
                                if ($language == 'es') { $reviewsText = 'reseñas'; }
                                elseif ($language == 'fr') { $reviewsText = 'avis'; }
                                elseif ($language == 'de') { $reviewsText = 'Bewertungen'; }
                                echo $reviewsText;
                            ?>)</span>
                        </div>
                        <p class="service-description"><?php 
                            $suvDescText = 'Spacious and comfortable SUVs perfect for family trips or group travel.';
                            if ($language == 'es') { $suvDescText = 'SUVs espaciosos y cómodos perfectos para viajes familiares o en grupo.'; }
                            elseif ($language == 'fr') { $suvDescText = 'SUV spacieux et confortables parfaits pour les voyages en famille ou en groupe.'; }
                            elseif ($language == 'de') { $suvDescText = 'Geräumige und komfortable SUVs, perfekt für Familienausflüge oder Gruppenreisen.'; }
                            echo $suvDescText;
                        ?></p>
                        <div class="service-details">
                            <div class="detail-item">
                                <span class="detail-label"><?php 
                                    $seatsText = 'Seats:';
                                    if ($language == 'es') { $seatsText = 'Asientos:'; }
                                    elseif ($language == 'fr') { $seatsText = 'Sièges:'; }
                                    elseif ($language == 'de') { $seatsText = 'Sitze:'; }
                                    echo $seatsText;
                                ?></span>
                                <span class="detail-value">5-7</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php 
                                    $luggageText = 'Luggage:';
                                    if ($language == 'es') { $luggageText = 'Equipaje:'; }
                                    elseif ($language == 'fr') { $luggageText = 'Bagages:'; }
                                    elseif ($language == 'de') { $luggageText = 'Gepäck:'; }
                                    echo $luggageText;
                                ?></span>
                                <span class="detail-value">4-6 <?php 
                                    $bagsText = 'bags';
                                    if ($language == 'es') { $bagsText = 'maletas'; }
                                    elseif ($language == 'fr') { $bagsText = 'sacs'; }
                                    elseif ($language == 'de') { $bagsText = 'Taschen'; }
                                    echo $bagsText;
                                ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php 
                                    $fuelTypeText = 'Fuel Type:';
                                    if ($language == 'es') { $fuelTypeText = 'Tipo de combustible:'; }
                                    elseif ($language == 'fr') { $fuelTypeText = 'Type de carburant:'; }
                                    elseif ($language == 'de') { $fuelTypeText = 'Kraftstofftyp:'; }
                                    echo $fuelTypeText;
                                ?></span>
                                <span class="detail-value">Petrol/Diesel</span>
                            </div>
                        </div>
                        <div class="testimonial">
                            <p>"<?php 
                                $suvTestimonialText = 'The SUV was perfect for our family vacation. Plenty of space and very comfortable!';
                                if ($language == 'es') { $suvTestimonialText = 'El SUV fue perfecto para nuestras vacaciones familiares. ¡Mucho espacio y muy cómodo!'; }
                                elseif ($language == 'fr') { $suvTestimonialText = 'Le SUV était parfait pour nos vacances en famille. Beaucoup d\'espace et très confortable !'; }
                                elseif ($language == 'de') { $suvTestimonialText = 'Der SUV war perfekt für unseren Familienurlaub. Viel Platz und sehr komfortabel!'; }
                                echo $suvTestimonialText;
                            ?>"</p>
                            <div class="testimonial-author">- Zahra A.</div>
                        </div>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-image">
                        <img src="car_pics/car2.png" alt="Economy Car">
                        <div class="service-badge"><?php 
                            $bestValueText = 'Best Value';
                            if ($language == 'es') { $bestValueText = 'Mejor valor'; }
                            elseif ($language == 'fr') { $bestValueText = 'Meilleur rapport qualité-prix'; }
                            elseif ($language == 'de') { $bestValueText = 'Bestes Preis-Leistungs-Verhältnis'; }
                            echo $bestValueText;
                        ?></div>
                    </div>
                    <div class="service-content">
                        <h3><?php 
                            $economyText = 'Economy Car';
                            if ($language == 'es') { $economyText = 'Auto Económico'; }
                            elseif ($language == 'fr') { $economyText = 'Voiture Économique'; }
                            elseif ($language == 'de') { $economyText = 'Sparauto'; }
                            echo $economyText;
                        ?></h3>
                        <div class="service-rating">
                            <span class="stars">★★★★☆</span>
                            <span class="rating-text">4.7/5 (95 <?php echo $reviewsText; ?>)</span>
                        </div>
                        <p class="service-description"><?php 
                            $economyDescText = 'Fuel-efficient and affordable cars ideal for city driving and short trips.';
                            if ($language == 'es') { $economyDescText = 'Autos eficientes en combustible y asequibles ideales para conducir en la ciudad y viajes cortos.'; }
                            elseif ($language == 'fr') { $economyDescText = 'Voitures économes en carburant et abordables idéales pour la conduite en ville et les courts trajets.'; }
                            elseif ($language == 'de') { $economyDescText = 'Kraftstoffeffiziente und erschwingliche Autos, ideal für Stadtfahrten und kurze Reisen.'; }
                            echo $economyDescText;
                        ?></p>
                        <div class="service-details">
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $seatsText; ?></span>
                                <span class="detail-value">4-5</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $luggageText; ?></span>
                                <span class="detail-value">2-3 <?php echo $bagsText; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $fuelTypeText; ?></span>
                                <span class="detail-value">Petrol</span>
                            </div>
                        </div>
                        <div class="testimonial">
                            <p>"<?php 
                                $economyTestimonialText = 'Great value for money! The car was clean, efficient, and perfect for getting around the city.';
                                if ($language == 'es') { $economyTestimonialText = '¡Excelente relación calidad-precio! El auto estaba limpio, eficiente y perfecto para moverse por la ciudad.'; }
                                elseif ($language == 'fr') { $economyTestimonialText = 'Excellent rapport qualité-prix ! La voiture était propre, efficace et parfaite pour se déplacer en ville.'; }
                                elseif ($language == 'de') { $economyTestimonialText = 'Großartiges Preis-Leistungs-Verhältnis! Das Auto war sauber, effizient und perfekt, um in der Stadt herumzukommen.'; }
                                echo $economyTestimonialText;
                            ?>"</p>
                            <div class="testimonial-author">- Olivia E.S.</div>
                        </div>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-image">
                        <img src="car_pics/car3.jpg" alt="Luxury Sedan">
                    </div>
                    <div class="service-content">
                        <h3><?php 
                            $luxuryText = 'Luxury Sedan';
                            if ($language == 'es') { $luxuryText = 'Sedán de Lujo'; }
                            elseif ($language == 'fr') { $luxuryText = 'Berline de Luxe'; }
                            elseif ($language == 'de') { $luxuryText = 'Luxuslimousine'; }
                            echo $luxuryText;
                        ?></h3>
                        <div class="service-rating">
                            <span class="stars">★★★★☆</span>
                            <span class="rating-text">4.8/5 (67 <?php echo $reviewsText; ?>)</span>
                        </div>
                        <p class="service-description"><?php 
                            $luxuryDescText = 'Premium vehicles for business trips or special occasions with comfort.';
                            if ($language == 'es') { $luxuryDescText = 'Vehículos premium para viajes de negocios u ocasiones especiales con comodidad.'; }
                            elseif ($language == 'fr') { $luxuryDescText = 'Véhicules premium pour les voyages d\'affaires ou les occasions spéciales avec confort.'; }
                            elseif ($language == 'de') { $luxuryDescText = 'Premium-Fahrzeuge für Geschäftsreisen oder besondere Anlässe mit Komfort.'; }
                            echo $luxuryDescText;
                        ?></p>
                        <div class="service-details">
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $seatsText; ?></span>
                                <span class="detail-value">4-5</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $luggageText; ?></span>
                                <span class="detail-value">3-4 <?php echo $bagsText; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $fuelTypeText; ?></span>
                                <span class="detail-value">Petrol/Hybrid</span>
                            </div>
                        </div>
                        <div class="testimonial">
                            <p>"<?php 
                                $luxuryTestimonialText = 'The luxury sedan made our anniversary trip extra special. Smooth ride and excellent service!';
                                if ($language == 'es') { $luxuryTestimonialText = 'El sedán de lujo hizo que nuestro viaje de aniversario fuera aún más especial. ¡Viaje suave y excelente servicio!'; }
                                elseif ($language == 'fr') { $luxuryTestimonialText = 'La berline de luxe a rendu notre voyage d\'anniversaire encore plus spécial. Conduite agréable et excellent service !'; }
                                elseif ($language == 'de') { $luxuryTestimonialText = 'Die Luxuslimousine machte unsere Jubiläumsreise noch besonderer. Ruhige Fahrt und ausgezeichneter Service!'; }
                                echo $luxuryTestimonialText;
                            ?>"</p>
                            <div class="testimonial-author">- Will</div>
                        </div>
                    </div>
                </div>
                
                <div class="service-card">
                    <div class="service-image">
                        <img src="car_pics/car4.png" alt="Electric">
                        <div class="service-badge"><?php 
                            $ecoFriendlyText = 'Eco-Friendly';
                            if ($language == 'es') { $ecoFriendlyText = 'Ecológico'; }
                            elseif ($language == 'fr') { $ecoFriendlyText = 'Écologique'; }
                            elseif ($language == 'de') { $ecoFriendlyText = 'Umweltfreundlich'; }
                            echo $ecoFriendlyText;
                        ?></div>
                    </div>
                    <div class="service-content">
                        <h3><?php 
                            $electricText = 'Electric Vehicle';
                            if ($language == 'es') { $electricText = 'Vehículo Eléctrico'; }
                            elseif ($language == 'fr') { $electricText = 'Véhicule Électrique'; }
                            elseif ($language == 'de') { $electricText = 'Elektrofahrzeug'; }
                            echo $electricText;
                        ?></h3>
                        <div class="service-rating">
                            <span class="stars">★★★★☆</span>
                            <span class="rating-text">4.6/5 (52 <?php echo $reviewsText; ?>)</span>
                        </div>
                        <p class="service-description"><?php 
                            $electricDescText = 'Environmentally friendly electric cars with modern features and operations.';
                            if ($language == 'es') { $electricDescText = 'Autos eléctricos ecológicos con características y operaciones modernas.'; }
                            elseif ($language == 'fr') { $electricDescText = 'Voitures électriques respectueuses de l\'environnement avec des fonctionnalités et des opérations modernes.'; }
                            elseif ($language == 'de') { $electricDescText = 'Umweltfreundliche Elektroautos mit modernen Funktionen und Betrieb.'; }
                            echo $electricDescText;
                        ?></p>
                        <div class="service-details">
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $seatsText; ?></span>
                                <span class="detail-value">4-5</span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $luggageText; ?></span>
                                <span class="detail-value">2-3 <?php echo $bagsText; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label"><?php echo $fuelTypeText; ?></span>
                                <span class="detail-value">Electric</span>
                            </div>
                        </div>
                        <div class="testimonial">
                            <p>"<?php 
                                $electricTestimonialText = 'My first EV experience was fantastic! The car was quiet, smooth, and charging was convenient.';
                                if ($language == 'es') { $electricTestimonialText = '¡Mi primera experiencia con un vehículo eléctrico fue fantástica! El auto era silencioso, suave y la carga era conveniente.'; }
                                elseif ($language == 'fr') { $electricTestimonialText = 'Ma première expérience avec un véhicule électrique a été fantastique ! La voiture était silencieuse, confortable et la recharge était pratique.'; }
                                elseif ($language == 'de') { $electricTestimonialText = 'Meine erste Erfahrung mit einem Elektrofahrzeug war fantastisch! Das Auto war leise, ruhig und das Aufladen war bequem.'; }
                                echo $electricTestimonialText;
                            ?>"</p>
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
                <p><?php 
                    $footerTaglineText = 'Your trusted partner for car rental services in Birmingham and beyond.';
                    if ($language == 'es') { $footerTaglineText = 'Su socio de confianza para servicios de alquiler de autos en Birmingham y más allá.'; }
                    elseif ($language == 'fr') { $footerTaglineText = 'Votre partenaire de confiance pour les services de location de voitures à Birmingham et au-delà.'; }
                    elseif ($language == 'de') { $footerTaglineText = 'Ihr vertrauenswürdiger Partner für Autovermietungen in Birmingham und darüber hinaus.'; }
                    echo $footerTaglineText;
                ?></p>
            </div>
            <div class="footer-column">
                <h3><?php 
                    $quickLinksText = 'Quick Links';
                    if ($language == 'es') { $quickLinksText = 'Enlaces rápidos'; }
                    elseif ($language == 'fr') { $quickLinksText = 'Liens rapides'; }
                    elseif ($language == 'de') { $quickLinksText = 'Schnelllinks'; }
                    echo $quickLinksText;
                ?></h3>
                <ul>
                    <li><a href="landing.php"><?php echo $homeText; ?></a></li>
                    <li><a href="cars.php"><?php 
                        $ourFleetText = 'Our Fleet';
                        if ($language == 'es') { $ourFleetText = 'Nuestra flota'; }
                        elseif ($language == 'fr') { $ourFleetText = 'Notre flotte'; }
                        elseif ($language == 'de') { $ourFleetText = 'Unsere Flotte'; }
                        echo $ourFleetText;
                    ?></a></li>
                    <li><a href="contact.php"><?php 
                        $locationsText2 = 'Locations';
                        if ($language == 'es') { $locationsText2 = 'Ubicaciones'; }
                        elseif ($language == 'fr') { $locationsText2 = 'Emplacements'; }
                        elseif ($language == 'de') { $locationsText2 = 'Standorte'; }
                        echo $locationsText2;
                    ?></a></li>
                    <li><a href="#"><?php 
                        $offersText = 'Offers';
                        if ($language == 'es') { $offersText = 'Ofertas'; }
                        elseif ($language == 'fr') { $offersText = 'Offres'; }
                        elseif ($language == 'de') { $offersText = 'Angebote'; }
                        echo $offersText;
                    ?></a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3><?php 
                    $contactUsText = 'Contact Us';
                    if ($language == 'es') { $contactUsText = 'Contáctenos'; }
                    elseif ($language == 'fr') { $contactUsText = 'Contactez-nous'; }
                    elseif ($language == 'de') { $contactUsText = 'Kontaktieren Sie uns'; }
                    echo $contactUsText;
                ?></h3>
                <ul>
                    <li>New Street Station, Birmingham</li>
                    <li>0712345678</li>
                    <li>info@motivcarrental.com</li>
                </ul>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Motiv Car Rental. <?php 
                $rightsReservedText = 'All rights reserved.';
                if ($language == 'es') { $rightsReservedText = 'Todos los derechos reservados.'; }
                elseif ($language == 'fr') { $rightsReservedText = 'Tous droits réservés.'; }
                elseif ($language == 'de') { $rightsReservedText = 'Alle Rechte vorbehalten.'; }
                echo $rightsReservedText;
            ?></p>
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

        const today = new Date().toISOString().split('T')[0];
        const pickupDate = document.getElementById('pickup-date');
        const dropoffDate = document.getElementById('dropoff-date');
        
        if (pickupDate) {
            pickupDate.setAttribute('min', today);
        }
        if (dropoffDate) {
            dropoffDate.setAttribute('min', today);
        }
        
        if (pickupDate) {
            pickupDate.addEventListener('change', function() {
                if (dropoffDate) {
                    dropoffDate.setAttribute('min', this.value);
                }
            });
        }
        
        const bookingForm = document.getElementById('bookingForm');
        if (bookingForm) {
            bookingForm.addEventListener('submit', function(e) {
                const pickupLocation = document.getElementById('pickup-location');
                const pickupDate = document.getElementById('pickup-date');
                const pickupTime = document.getElementById('pickup-time');
                const dropoffDate = document.getElementById('dropoff-date');
                const dropoffTime = document.getElementById('dropoff-time');
                
                if (!pickupLocation.value || !pickupDate.value || !pickupTime.value || !dropoffDate.value || !dropoffTime.value) {
                    e.preventDefault();
                    alert('Please fill in all fields');
                    return;
                }
                
                const pickupDateTime = new Date(pickupDate.value + ' ' + pickupTime.value);
                const dropoffDateTime = new Date(dropoffDate.value + ' ' + dropoffTime.value);
                
                if (dropoffDateTime <= pickupDateTime) {
                    e.preventDefault();
                    alert('Drop-off date and time must be after pick-up date and time');
                    return;
                }
            });
        }

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
