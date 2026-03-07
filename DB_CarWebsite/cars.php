<?php
session_start();
require_once 'db.php';

$is_logged_in = isset($_SESSION['user']) && $_SESSION['user']['role'] === 'customer';
$customer_id = $is_logged_in ? $_SESSION['user']['id'] : null;

$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : 'light';
$fontSize = isset($_COOKIE['fontSize']) ? $_COOKIE['fontSize'] : '100';
$language = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en';

// Language variables for cars page
$themeText = 'Theme';
$lightText = 'Light';
$darkText = 'Dark';
$fontSizeText = 'Font Size';
$resetText = 'Reset';
$languageText = 'Language';

// Cars page content translations
if ($language == 'en') {
    $homeText = 'Home';
    $aboutText = 'About';
    $carsText = 'Cars';
    $contactText = 'Contact';
    $loginText = 'Login';
    $dashboardText = 'Dashboard';
    $logoutText = 'Logout';
    $ourFleetText = 'Our Car Fleet';
    $searchPlaceholder = 'Search for cars...';
    $filteredResultsText = 'Filtered Results';
    $locationText = 'Location';
    $datesText = 'Dates';
    $clearSearchText = 'Clear Search Filters';
    $carTypeText = 'Car Type';
    $locationFilterText = 'Location';
    $priceRangeText = 'Price Range';
    $minText = 'Min';
    $maxText = 'Max';
    $availabilityText = 'Availability';
    $availableOnlyText = 'Available Only';
    $applyFiltersText = 'Apply Filters';
    $resetFiltersText = 'Reset Filters';
    $noCarsMessage1 = 'No cars available in';
    $noCarsMessage2 = 'No vehicles available';
    $noCarsSuggestion1 = 'Please try a different location or';
    $noCarsSuggestion2 = 'search again';
    $noCarsSuggestion3 = 'Please check back later or contact us for availability.';
    $searchDifferentText = 'Search Different Location';
    $dayText = '/day';
    $viewDetailsText = 'View Details';
    $bookNowText = 'Book Now';
    $unavailableText = 'Unavailable';
    $carDetailsText = 'Car Details';
    $addToBasketText = 'Add to Basket';
    $closeText = 'Close';
    $pleaseLoginText = 'Please login to save favorites';
    $loginPromptText = 'Login';
    $registerPromptText = 'Register';
    $footerTagline = 'Your trusted partner for car rental services in Birmingham and beyond.';
    $quickLinks = 'Quick Links';
    $home = 'Home';
    $about = 'About';
    $ourFleet = 'Our Fleet';
    $contact = 'Contact';
    $contactUs = 'Contact Us';
    $rightsReserved = 'All rights reserved.';
} elseif ($language == 'es') {
    $themeText = 'Tema';
    $lightText = 'Claro';
    $darkText = 'Oscuro';
    $fontSizeText = 'Tamaño de fuente';
    $resetText = 'Reiniciar';
    $languageText = 'Idioma';
    $homeText = 'Inicio';
    $aboutText = 'Acerca de';
    $carsText = 'Autos';
    $contactText = 'Contacto';
    $loginText = 'Iniciar sesión';
    $dashboardText = 'Panel';
    $logoutText = 'Cerrar sesión';
    $ourFleetText = 'Nuestra Flota de Autos';
    $searchPlaceholder = 'Buscar autos...';
    $filteredResultsText = 'Resultados Filtrados';
    $locationText = 'Ubicación';
    $datesText = 'Fechas';
    $clearSearchText = 'Limpiar Filtros';
    $carTypeText = 'Tipo de Auto';
    $locationFilterText = 'Ubicación';
    $priceRangeText = 'Rango de Precio';
    $minText = 'Mín';
    $maxText = 'Máx';
    $availabilityText = 'Disponibilidad';
    $availableOnlyText = 'Solo Disponibles';
    $applyFiltersText = 'Aplicar Filtros';
    $resetFiltersText = 'Reiniciar Filtros';
    $noCarsMessage1 = 'No hay autos disponibles en';
    $noCarsMessage2 = 'No hay vehículos disponibles';
    $noCarsSuggestion1 = 'Por favor pruebe otra ubicación o';
    $noCarsSuggestion2 = 'busque de nuevo';
    $noCarsSuggestion3 = 'Por favor verifique más tarde o contáctenos para disponibilidad.';
    $searchDifferentText = 'Buscar Otra Ubicación';
    $dayText = '/día';
    $viewDetailsText = 'Ver Detalles';
    $bookNowText = 'Reservar';
    $unavailableText = 'No Disponible';
    $carDetailsText = 'Detalles del Auto';
    $addToBasketText = 'Añadir al Carrito';
    $closeText = 'Cerrar';
    $pleaseLoginText = 'Por favor inicie sesión para guardar favoritos';
    $loginPromptText = 'Iniciar sesión';
    $registerPromptText = 'Registrarse';
    $footerTagline = 'Su socio de confianza para servicios de alquiler de autos en Birmingham y más allá.';
    $quickLinks = 'Enlaces rápidos';
    $home = 'Inicio';
    $about = 'Sobre Nosotros';
    $ourFleet = 'Nuestra Flota';
    $contact = 'Contacto';
    $contactUs = 'Contáctenos';
    $rightsReserved = 'Todos los derechos reservados.';
} elseif ($language == 'fr') {
    $themeText = 'Thème';
    $lightText = 'Clair';
    $darkText = 'Sombre';
    $fontSizeText = 'Taille de police';
    $resetText = 'Réinitialiser';
    $languageText = 'Langue';
    $homeText = 'Accueil';
    $aboutText = 'À propos';
    $carsText = 'Voitures';
    $contactText = 'Contact';
    $loginText = 'Connexion';
    $dashboardText = 'Tableau de bord';
    $logoutText = 'Déconnexion';
    $ourFleetText = 'Notre Flotte de Voitures';
    $searchPlaceholder = 'Rechercher des voitures...';
    $filteredResultsText = 'Résultats Filtrés';
    $locationText = 'Emplacement';
    $datesText = 'Dates';
    $clearSearchText = 'Effacer les Filtres';
    $carTypeText = 'Type de Voiture';
    $locationFilterText = 'Emplacement';
    $priceRangeText = 'Fourchette de Prix';
    $minText = 'Min';
    $maxText = 'Max';
    $availabilityText = 'Disponibilité';
    $availableOnlyText = 'Disponibles Seulement';
    $applyFiltersText = 'Appliquer les Filtres';
    $resetFiltersText = 'Réinitialiser les Filtres';
    $noCarsMessage1 = 'Aucune voiture disponible à';
    $noCarsMessage2 = 'Aucun véhicule disponible';
    $noCarsSuggestion1 = 'Veuillez essayer un autre emplacement ou';
    $noCarsSuggestion2 = 'rechercher à nouveau';
    $noCarsSuggestion3 = 'Veuillez vérifier plus tard ou nous contacter pour la disponibilité.';
    $searchDifferentText = 'Rechercher un Autre Emplacement';
    $dayText = '/jour';
    $viewDetailsText = 'Voir Détails';
    $bookNowText = 'Réserver';
    $unavailableText = 'Indisponible';
    $carDetailsText = 'Détails de la Voiture';
    $addToBasketText = 'Ajouter au Panier';
    $closeText = 'Fermer';
    $pleaseLoginText = 'Veuillez vous connecter pour sauvegarder les favoris';
    $loginPromptText = 'Connexion';
    $registerPromptText = 'S\'inscrire';
    $footerTagline = 'Votre partenaire de confiance pour les services de location de voitures à Birmingham et au-delà.';
    $quickLinks = 'Liens rapides';
    $home = 'Accueil';
    $about = 'À propos';
    $ourFleet = 'Notre flotte';
    $contact = 'Contact';
    $contactUs = 'Contactez-nous';
    $rightsReserved = 'Tous droits réservés.';
} elseif ($language == 'de') {
    $themeText = 'Design';
    $lightText = 'Hell';
    $darkText = 'Dunkel';
    $fontSizeText = 'Schriftgröße';
    $resetText = 'Zurücksetzen';
    $languageText = 'Sprache';
    $homeText = 'Startseite';
    $aboutText = 'Über uns';
    $carsText = 'Autos';
    $contactText = 'Kontakt';
    $loginText = 'Anmelden';
    $dashboardText = 'Dashboard';
    $logoutText = 'Abmelden';
    $ourFleetText = 'Unsere Fahrzeugflotte';
    $searchPlaceholder = 'Autos suchen...';
    $filteredResultsText = 'Gefilterte Ergebnisse';
    $locationText = 'Standort';
    $datesText = 'Daten';
    $clearSearchText = 'Filter löschen';
    $carTypeText = 'Fahrzeugtyp';
    $locationFilterText = 'Standort';
    $priceRangeText = 'Preisspanne';
    $minText = 'Min';
    $maxText = 'Max';
    $availabilityText = 'Verfügbarkeit';
    $availableOnlyText = 'Nur verfügbare';
    $applyFiltersText = 'Filter anwenden';
    $resetFiltersText = 'Filter zurücksetzen';
    $noCarsMessage1 = 'Keine Autos verfügbar in';
    $noCarsMessage2 = 'Keine Fahrzeuge verfügbar';
    $noCarsSuggestion1 = 'Bitte versuchen Sie einen anderen Standort oder';
    $noCarsSuggestion2 = 'erneut suchen';
    $noCarsSuggestion3 = 'Bitte überprüfen Sie später oder kontaktieren Sie uns für Verfügbarkeit.';
    $searchDifferentText = 'Anderen Standort suchen';
    $dayText = '/Tag';
    $viewDetailsText = 'Details anzeigen';
    $bookNowText = 'Jetzt buchen';
    $unavailableText = 'Nicht verfügbar';
    $carDetailsText = 'Fahrzeugdetails';
    $addToBasketText = 'In den Warenkorb';
    $closeText = 'Schließen';
    $pleaseLoginText = 'Bitte anmelden, um Favoriten zu speichern';
    $loginPromptText = 'Anmelden';
    $registerPromptText = 'Registrieren';
    $footerTagline = 'Ihr vertrauenswürdiger Partner für Autovermietungen in Birmingham und darüber hinaus.';
    $quickLinks = 'Schnelllinks';
    $home = 'Startseite';
    $about = 'Über uns';
    $ourFleet = 'Unsere Flotte';
    $contact = 'Kontakt';
    $contactUs = 'Kontaktieren Sie uns';
    $rightsReserved = 'Alle Rechte vorbehalten.';
}

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
                $stmt = $conn->prepare("DELETE FROM favorites WHERE customer_id = ? AND car_id = ?");
                $stmt->bind_param("ii", $customer_id, $car_id);
                $result = $stmt->execute();
                $stmt->close();
                
                echo json_encode(['success' => true, 'is_favorite' => false, 'message' => 'Removed from favorites']);
            } else {
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

// Build city data array for JavaScript
$cityData = [];
$cityQuery = $conn->query("SELECT city_id, city_name FROM cities");
while ($city = $cityQuery->fetch_assoc()) {
    $cityData[$city['city_id']] = $city['city_name'];
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

        if ($carStatus['status_id'] != 1) {
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
        $rentalDays = max(1, $rentalDays);
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
            --coral-red: #FF0000;
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

        [data-theme="dark"] h1,
        [data-theme="dark"] h2,
        [data-theme="dark"] h3,
        [data-theme="dark"] h4,
        [data-theme="dark"] h5,
        [data-theme="dark"] h6,
        [data-theme="dark"] .car-listings-title,
        [data-theme="dark"] .filter-section h3,
        [data-theme="dark"] .car-name {
            color: #ffffff !important;
        }

        [data-theme="dark"] p,
        [data-theme="dark"] .car-specs,
        [data-theme="dark"] .car-description,
        [data-theme="dark"] .filter-option label,
        [data-theme="dark"] .footer-column p,
        [data-theme="dark"] .footer-column ul li {
            color: #cccccc;
        }

        [data-theme="dark"] .car-card,
        [data-theme="dark"] .filters-sidebar,
        [data-theme="dark"] .no-cars-message,
        [data-theme="dark"] .search-info {
            background-color: #2d2d2d;
            border-color: #404040;
        }

        [data-theme="dark"] .car-listings-container {
            background-color: #1a1a1a;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: <?php echo $fontSize; ?>%;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        header, footer, .car-card, .filters-sidebar {
            background-color: var(--card-bg);
            color: var(--text-primary);
        }

        .car-listings-container {
            padding: 40px 0;
            background-color: var(--bg-secondary);
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
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px var(--shadow-color);
            height: fit-content;
            border: 1px solid var(--border-color);
        }
        
        .filter-section {
            margin-bottom: 25px;
        }
        
        .filter-section h3 {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid var(--border-color);
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
            color: var(--text-secondary);
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
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background-color: var(--card-bg);
            color: var(--text-primary);
        }
        
        .cars-grid {
            flex: 1;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .car-card {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px var(--shadow-color);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            border: 1px solid var(--border-color);
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px var(--shadow-color);
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
            color: var(--coral-red) !important;
        }
        
        .favorite-btn:hover i {
            color: var(--coral-red);
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
            color: var(--text-primary);
        }
        
        .car-price {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--vivid-indigo);
        }
        
        .car-price span {
            font-size: 0.9rem;
            font-weight: 400;
            color: var(--text-secondary);
        }
        
        .car-specs {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 15px;
            color: var(--text-secondary);
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
            color: var(--cobalt-blue);
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
            color: var(--text-secondary);
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
    padding: 60px 40px;
    background: var(--card-bg);
    border-radius: 12px;
    box-shadow: 0 3px 10px var(--shadow-color);
    border: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
}

.no-cars-message i {
    font-size: 64px;
    color: var(--vivid-indigo);
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-cars-message h3 {
    color: var(--vivid-indigo);
    margin-bottom: 15px;
    font-size: 1.8rem;
}

.no-cars-message p {
    margin-bottom: 20px;
    color: var(--text-secondary);
    font-size: 1.1rem;
    max-width: 500px;
    line-height: 1.6;
}

[data-theme="dark"] .no-cars-message {
    background: var(--card-bg);
    border-color: var(--border-color);
}

[data-theme="dark"] .no-cars-message h3 {
    color: #ffffff;
}

[data-theme="dark"] .no-cars-message p {
    color: #cccccc;
}

[data-theme="dark"] .no-cars-message i {
    color: #ffffff;
    opacity: 0.7;
}

        [data-theme="dark"] .no-cars-message {
            background: var(--card-bg);
            border-color: var(--border-color);
        }

        [data-theme="dark"] .no-cars-message h3 {
            color: #ffffff;
        }

        [data-theme="dark"] .no-cars-message p {
            color: #cccccc;
        }

        [data-theme="dark"] .no-cars-message i {
            color: #ffffff;
            opacity: 0.7;
        }
        
        .search-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--cobalt-blue);
        }
        
        [data-theme="dark"] .search-info {
            background: #2d2d2d;
            color: #ffffff;
        }
        
        .search-info h3 {
            margin: 0 0 10px 0;
            color: var(--vivid-indigo);
        }
        
        .search-info p {
            margin: 5px 0;
            color: var(--text-secondary);
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
            background: var(--card-bg);
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            border: 1px solid var(--border-color);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
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
            color: var(--text-secondary);
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
            color: var(--text-secondary);
        }
        
        .car-detail-spec i {
            color: var(--cobalt-blue);
            width: 20px;
            text-align: center;
        }
        
        .car-detail-description {
            margin-bottom: 25px;
            line-height: 1.6;
            color: var(--text-secondary);
        }
        
        .car-detail-price {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--vivid-indigo);
            margin-bottom: 20px;
        }
        
        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }
        
        .btn-secondary {
            padding: 10px 20px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--text-primary);
        }
        
        .btn-secondary:hover {
            background: var(--border-color);
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
            background: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 5px 15px var(--shadow-color);
            padding: 15px;
            width: 200px;
            z-index: 10;
            display: none;
            border: 1px solid var(--border-color);
        }
        
        .login-prompt p {
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: var(--text-primary);
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
            border: 1px solid var(--border-color);
            border-radius: 6px;
            width: 250px;
            font-size: 1rem;
            background-color: var(--card-bg);
            color: var(--text-primary);
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
        
        nav ul li.dropdown {
            position: relative;
        }

        nav ul li.dropdown .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 120px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1001;
            border-radius: 5px;
            overflow: hidden;
            top: 100%;
            left: 0;
        }

        nav ul li.dropdown:hover .dropdown-content {
            display: block;
        }

        nav ul li.dropdown .dropdown-content a {
            color: #333;
            padding: 10px 14px;
            display: block;
            transition: background-color 0.3s;
            border-bottom: 1px solid #f1f1f1;
            font-size: 0.9rem;
        }

        nav ul li.dropdown .dropdown-content a:hover {
            background-color: #f8f9fa;
            color: var(--vivid-indigo);
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
                    <a href="landing.php" class="dropbtn"><?php echo $homeText; ?> <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="landing.php"><?php echo $homeText; ?></a>
                        <a href="about.php"><?php echo $aboutText; ?></a>
                    </div>
                </li>
                <li><a href="cars.php" class="active"><?php echo $carsText; ?></a></li>
                <li><a href="contact.php"><?php echo $contactText; ?></a></li>

                <?php if (!isset($_SESSION['user'])): ?>
                    
                    <li><a href="loginPage.php"><?php echo $loginText; ?></a></li>
                <?php else: ?>
                    <li><a href="customer-dashboard.php"><?php echo $dashboardText; ?></a></li>
                    <li>
                        <a href="logout.php" style="color: #ff7f50;">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $logoutText; ?>
                        </a>
                    </li>
                <?php endif; ?>

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
                                <button class="font-btn" id="font-reset"><?php echo $resetText; ?></button>
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
            <h1 class="car-listings-title"><?php echo $ourFleetText; ?></h1>
            <div class="search-box">
                <input type="text" placeholder="<?php echo $searchPlaceholder; ?>" id="searchInput">
            </div>
        </div>
        
        <?php if (!empty($selectedCityId)): ?>
            <div class="search-info">
                <h3><i class="fas fa-filter"></i> <?php echo $filteredResultsText; ?></h3>
                <p><strong><?php echo $locationText; ?>:</strong> <?php echo htmlspecialchars($selectedCityName); ?></p>
                <p><strong><?php echo $datesText; ?>:</strong> <?php echo htmlspecialchars($searchCriteria['pickup_date'] . ' to ' . $searchCriteria['dropoff_date']); ?></p>
                <button id="clear-search" class="btn-secondary" style="margin-top: 10px; padding: 8px 15px; font-size: 0.9rem;">
                    <i class="fas fa-times"></i> <?php echo $clearSearchText; ?>
                </button>
            </div>
        <?php endif; ?>
        
        <div class="car-listings-content">
            <div class="filters-sidebar">
                <div class="filter-section">
                    <h3><?php echo $carTypeText; ?></h3>
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
                    <h3><?php echo $locationFilterText; ?></h3>
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
                    <h3><?php echo $priceRangeText; ?></h3>
                    <div class="price-range">
                        <div class="price-inputs">
                            <input type="number" id="min-price" placeholder="<?php echo $minText; ?>" min="0">
                            <input type="number" id="max-price" placeholder="<?php echo $maxText; ?>" min="0">
                        </div>
                    </div>
                </div>
                
                <div class="filter-section">
                    <h3><?php echo $availabilityText; ?></h3>
                    <div class="filter-options">
                        <div class="filter-option">
                            <input type="checkbox" id="filter-available" data-filter="availability" value="available">
                            <label for="filter-available"><?php echo $availableOnlyText; ?></label>
                        </div>
                    </div>
                </div>
                
                <button id="apply-filters" class="book-btn"><?php echo $applyFiltersText; ?></button>
                <button id="reset-filters" class="btn-secondary" style="margin-top: 10px; width: 100%;"><?php echo $resetFiltersText; ?></button>
            </div>
            
            <div class="cars-grid" id="carsGrid">
                <?php if (empty($cars)): ?>
                    <div class="no-cars-message">
                        <i class="fas fa-car"></i>
                        <h3><?php echo $noCarsMessage2; ?></h3>
                        <p><?php echo $noCarsSuggestion3; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($cars as $car): ?>
                        <div class="car-card" 
                             data-id="<?php echo $car['car_id']; ?>" 
                             data-status="<?php echo $car['status_id']; ?>"
                             data-city-id="<?php echo $car['city_id']; ?>"
                             data-price="<?php echo $car['price_per_day']; ?>"
                             data-type="<?php echo strtolower($car['type_name']); ?>">
                            <div class="car-image">
                                <?php if ($car['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($car['image_url']); ?>" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                <?php else: ?>
                                    <img src="car-default.jpg" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                <?php endif; ?>
                                
                                <?php if ($car['status_id'] == 2): ?>
                                    <div class="booked-badge"><?php echo $unavailableText; ?></div>
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
                                    <div class="car-price">£<?php echo number_format($car['price_per_day'], 2); ?><span><?php echo $dayText; ?></span></div>
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
                                    <button class="view-details-btn" data-id="<?php echo $car['car_id']; ?>"><?php echo $viewDetailsText; ?></button>
                                    <?php if ($car['status_id'] == 1): ?>
                                        <button class="book-now-btn" data-id="<?php echo $car['car_id']; ?>" data-name="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>"><?php echo $bookNowText; ?></button>
                                    <?php else: ?>
                                        <button class="book-now-btn" disabled style="background: #ccc; cursor: not-allowed;"><?php echo $unavailableText; ?></button>
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
                    <div id="modalBookedBadge" class="booked-badge" style="display: none;"><?php echo $unavailableText; ?></div>
                </div>
                <div class="car-detail-info">
                    <h3><?php echo $carDetailsText; ?></h3>
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
                    <div class="car-detail-price" id="modalCarPrice">£0<?php echo $dayText; ?></div>
                    <button class="btn-primary" id="modalAddToBasket"><?php echo $addToBasketText; ?></button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-secondary close-modal"><?php echo $closeText; ?></button>
        </div>
    </div>
</div>

<div class="login-prompt" id="loginPrompt">
    <p><?php echo $pleaseLoginText; ?></p>
    <div class="login-prompt-buttons">
        <button class="login-btn" onclick="window.location.href='loginPage.php'"><?php echo $loginPromptText; ?></button>
        <button class="register-btn" onclick="window.location.href='register.php'"><?php echo $registerPromptText; ?></button>
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
                    <h3><?php echo $quickLinks; ?></h3>
                    <ul>
                        <li><a href="landing.php"><?php echo $home; ?></a></li>
                        <li><a href="about.php"><?php echo $about; ?></a></li>
                        <li><a href="cars.php"></a></li>
                        <li><a href="contact.php"><?php echo $contact; ?></a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3><?php echo $contactUs; ?></h3>
                    <ul>
                        <li>New Street Station, Birmingham</li>
                        <li>0712345678</li>
                        <li>info@motivcarrental.com</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Motiv Car Rental. <?php echo $rightsReserved; ?></p>
            </div>
        </div>
    </footer>

<script>
    // Pass PHP variables to JavaScript
    const cityData = <?php echo json_encode($cityData); ?>;
    let currentFontSize = <?php echo $fontSize; ?>;
    let currentTheme = '<?php echo $darkMode; ?>';
    let currentLanguage = '<?php echo $language; ?>';
    const currentUser = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
    
    // Translations for JavaScript
    const translations = {
        noCarsMessage2: '<?php echo $noCarsMessage2; ?>',
        noCarsSuggestion3: '<?php echo $noCarsSuggestion3; ?>'
    };

    const carsGrid = document.getElementById('carsGrid');
    const searchInput = document.getElementById('searchInput');
    const applyFiltersBtn = document.getElementById('apply-filters');
    const resetFiltersBtn = document.getElementById('reset-filters');
    const carDetailModal = document.getElementById('carDetailModal');
    const loginPrompt = document.getElementById('loginPrompt');

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

    function checkForVisibleCars() {
    const carCards = document.querySelectorAll('.car-card');
    let visibleCount = 0;
    
    carCards.forEach(card => {
        if (card.style.display !== 'none') {
            visibleCount++;
        }
    });
    
    let noCarsMessage = document.querySelector('.no-cars-message');
    
    // If there are no visible cars and we don't have a no-cars message
    if (visibleCount === 0 && !noCarsMessage) {
        // Create and show the no vehicles message without the button
        noCarsMessage = document.createElement('div');
        noCarsMessage.className = 'no-cars-message';
        noCarsMessage.innerHTML = `
            <i class="fas fa-car"></i>
            <h3>${translations.noCarsMessage2}</h3>
            <p>${translations.noCarsSuggestion3}</p>
        `;
        document.getElementById('carsGrid').appendChild(noCarsMessage);
    }
    // If there are visible cars and we have a no-cars message, remove it
    else if (visibleCount > 0 && noCarsMessage) {
        noCarsMessage.remove();
    }
}

    function applyFilters() {
        const selectedTypes = getSelectedValues('type');
        const selectedCities = getSelectedValues('city');
        const minPrice = parseInt(document.getElementById('min-price').value) || 0;
        const maxPrice = parseInt(document.getElementById('max-price').value) || Infinity;
        const availableOnly = document.getElementById('filter-available').checked;
        
        const carCards = document.querySelectorAll('.car-card');
        
        carCards.forEach(card => {
            const carType = card.dataset.type;
            const carCityId = card.dataset.cityId;
            const carPrice = parseFloat(card.dataset.price);
            const carStatus = card.getAttribute('data-status');
            
            const typeMatch = selectedTypes.length === 0 || selectedTypes.includes(carType);
            const cityMatch = selectedCities.length === 0 || selectedCities.includes(carCityId);
            const priceMatch = carPrice >= minPrice && carPrice <= maxPrice;
            const availabilityMatch = !availableOnly || carStatus === '1';
            
            if (typeMatch && cityMatch && priceMatch && availabilityMatch) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        checkForVisibleCars();
    }

    function handleSearch() {
        const searchTerm = searchInput.value.toLowerCase();
        const carCards = document.querySelectorAll('.car-card');
        
        carCards.forEach(card => {
            // Only apply search filter if card isn't already hidden by other filters
            if (card.style.display !== 'none') {
                const carName = card.querySelector('.car-name').textContent.toLowerCase();
                const carDescription = card.querySelector('.car-description').textContent.toLowerCase();
                
                if (!carName.includes(searchTerm) && !carDescription.includes(searchTerm)) {
                    card.style.display = 'none';
                }
            }
        });
        
        checkForVisibleCars();
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
        
        checkForVisibleCars();
    }

    function getSelectedValues(filterType) {
        const checkboxes = document.querySelectorAll(`input[data-filter="${filterType}"]:checked`);
        return Array.from(checkboxes).map(checkbox => checkbox.value);
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
            
            const bookedBadge = document.getElementById('modalBookedBadge');
            if (isAvailable) {
                bookedBadge.style.display = 'none';
                document.getElementById('modalAddToBasket').disabled = false;
                document.getElementById('modalAddToBasket').textContent = '<?php echo $addToBasketText; ?>';
            } else {
                bookedBadge.style.display = 'block';
                document.getElementById('modalAddToBasket').disabled = true;
                document.getElementById('modalAddToBasket').textContent = '<?php echo $unavailableText; ?>';
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
    });

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


