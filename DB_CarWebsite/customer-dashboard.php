<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: loginPage.php");
    exit(); 
}

require_once 'db.php';

// Get user preferences from cookies
$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : 'light';
$fontSize = isset($_COOKIE['fontSize']) ? $_COOKIE['fontSize'] : '100';
$language = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en';

// Language variables for dashboard
if ($language == 'en') {
    $themeText = 'Theme';
    $lightText = 'Light';
    $darkText = 'Dark';
    $fontSizeText = 'Font Size';
    $resetText = 'Reset';
    $languageText = 'Language';
    $welcomeBack = 'Welcome back';
    $memberId = 'Member ID';
    $overview = 'Overview';
    $myFavorites = 'My Favorites';
    $myBasket = 'My Basket';
    $rentalHistory = 'Rental History';
    $dashboardSubtitle = 'Manage your rentals, favorites, and account settings';
    $profileSettings = 'Profile Settings'; 
    $favoriteCars = 'Favorite Cars';
    $itemsInBasket = 'Items in Basket';
    $totalRentals = 'Total Rentals';
    $loyaltyPoints = 'Loyalty Points';
    $recentFavorites = 'Recent Favorites';
    $viewAll = 'View All';
    $upcomingRentals = 'Upcoming Rentals';
    $noFavorites = 'No favorites yet';
    $startBrowsing = 'Start browsing our car collection and add your favorites!';
    $browseCars = 'Browse Cars';
    $noUpcoming = 'No upcoming rentals';
    $remove = 'Remove';
    $checkout = 'Checkout';
    $details = 'Details';
    $bookNow = 'Book Now';
    $modifyBooking = 'Modify Booking';
    $viewDetails = 'View Details';
    $basketSummary = 'Basket Summary';
    $total = 'Total';
    $proceedToCheckout = 'Proceed to Checkout';
    $emptyBasket = 'Your basket is empty';
    $addCarsToBasket = 'Add some cars to your basket to get started!';
    $noRentalHistory = 'No rental history';
    $bookFirstCar = 'Book Your First Car';
    $profileInfo = 'Profile Information';
    $editProfile = 'Edit Profile';
    $fullName = 'Full Name';
    $emailAddress = 'Email Address';
    $phoneNumber = 'Phone Number';
    $memberSince = 'Member Since';
    $city = 'City';
    $accountSettings = 'Account Settings';
    $changePassword = 'Change Password';
    $notificationPrefs = 'Notification Preferences';
    $deleteAccount = 'Delete Account';
    $notProvided = 'Not provided';
    $logout = 'Logout';
    $home = 'Home';
    $cars = 'Cars';
    $contact = 'Contact';
    $dashboard = 'Dashboard';
    $quickLinks = 'Quick Links';
    $contactUs = 'Contact Us';
    $rightsReserved = 'All rights reserved.';
    // NEW: Loyalty points translations
    $redeemPoints = 'Redeem Points';
    $availableRewards = 'Available Rewards';
    $pointsNeeded = 'Points Needed';
    $redeem = 'Redeem';
    $congrats = 'Congratulations!';
    $voucherCode = 'Your voucher code is';
    $copyCode = 'Copy Code';
    $codeCopied = 'Code copied to clipboard!';
    $insufficientPoints = 'Insufficient points';
    $voucherDescription = '£10 off your next rental';
    $voucherDescription2 = '£25 off your next rental'; 
    $voucherDescription3 = 'Free upgrade to premium car';
    $redeemSuccess = 'Voucher redeemed successfully!';
    $pointsRemaining = 'Points remaining';
} elseif ($language == 'es') {
    $themeText = 'Tema';
    $lightText = 'Claro';
    $darkText = 'Oscuro';
    $fontSizeText = 'Tamaño de fuente';
    $resetText = 'Reiniciar';
    $languageText = 'Idioma';
    $welcomeBack = 'Bienvenido de nuevo';
    $memberId = 'ID de miembro';
    $overview = 'Resumen';
    $myFavorites = 'Mis favoritos';
    $myBasket = 'Mi cesta';
    $rentalHistory = 'Historial de alquileres';
    $profileSettings = 'Configuración de perfil';
    $dashboardSubtitle = 'Gestiona tus alquileres, favoritos y configuración de cuenta'; 
    $favoriteCars = 'Autos favoritos';
    $itemsInBasket = 'Artículos en cesta';
    $totalRentals = 'Total alquileres';
    $loyaltyPoints = 'Puntos de fidelidad';
    $recentFavorites = 'Favoritos recientes';
    $viewAll = 'Ver todo';
    $upcomingRentals = 'Próximos alquileres';
    $noFavorites = 'Aún no hay favoritos';
    $startBrowsing = '¡Empiece a explorar nuestra colección de autos y agregue sus favoritos!';
    $browseCars = 'Buscar autos';
    $noUpcoming = 'No hay próximos alquileres';
    $remove = 'Eliminar';
    $checkout = 'Pagar';
    $details = 'Detalles';
    $bookNow = 'Reservar ahora';
    $modifyBooking = 'Modificar reserva';
    $viewDetails = 'Ver detalles';
    $basketSummary = 'Resumen de cesta';
    $total = 'Total';
    $proceedToCheckout = 'Proceder al pago';
    $emptyBasket = 'Tu cesta está vacía';
    $addCarsToBasket = '¡Agrega algunos autos a tu cesta para comenzar!';
    $noRentalHistory = 'No hay historial de alquileres';
    $bookFirstCar = 'Reserva tu primer auto';
    $profileInfo = 'Información de perfil';
    $editProfile = 'Editar perfil';
    $fullName = 'Nombre completo';
    $emailAddress = 'Correo electrónico';
    $phoneNumber = 'Teléfono';
    $memberSince = 'Miembro desde';
    $city = 'Ciudad';
    $accountSettings = 'Configuración de cuenta';
    $changePassword = 'Cambiar contraseña';
    $notificationPrefs = 'Preferencias de notificación';
    $deleteAccount = 'Eliminar cuenta';
    $notProvided = 'No proporcionado';
    $logout = 'Cerrar sesión';
    $home = 'Inicio';
    $cars = 'Autos';
    $contact = 'Contacto';
    $dashboard = 'Panel';
    $quickLinks = 'Enlaces rápidos';
    $contactUs = 'Contáctenos';
    $rightsReserved = 'Todos los derechos reservados.';
    $redeemPoints = 'Canjear puntos';
    $availableRewards = 'Recompensas disponibles';
    $pointsNeeded = 'Puntos necesarios';
    $redeem = 'Canjear';
    $congrats = '¡Felicidades!';
    $voucherCode = 'Tu código de vale es';
    $copyCode = 'Copiar código';
    $codeCopied = '¡Código copiado al portapapeles!';
    $insufficientPoints = 'Puntos insuficientes';
    $voucherDescription = '£10 de descuento en tu próximo alquiler';
    $voucherDescription2 = '£25 de descuento en tu próximo alquiler';
    $voucherDescription3 = 'Actualización gratuita a auto premium';
    $redeemSuccess = '¡Vale canjeado exitosamente!';
    $pointsRemaining = 'Puntos restantes';
} elseif ($language == 'fr') {
    $themeText = 'Thème';
    $lightText = 'Clair';
    $darkText = 'Sombre';
    $fontSizeText = 'Taille de police';
    $resetText = 'Réinitialiser';
    $languageText = 'Langue';
    $welcomeBack = 'Bon retour';
    $memberId = 'Identifiant membre';
    $overview = 'Aperçu';
    $myFavorites = 'Mes favoris';
    $myBasket = 'Mon panier';
    $rentalHistory = 'Historique des locations';
    $profileSettings = 'Paramètres du profil';
    $dashboardSubtitle = 'Gérez vos locations, favoris et paramètres du compte';
    $favoriteCars = 'Voitures favorites';
    $itemsInBasket = 'Articles dans le panier';
    $totalRentals = 'Total locations';
    $loyaltyPoints = 'Points de fidélité';
    $recentFavorites = 'Favoris récents';
    $viewAll = 'Voir tout';
    $upcomingRentals = 'Locations à venir';
    $noFavorites = 'Pas encore de favoris';
    $startBrowsing = 'Commencez à parcourir notre collection de voitures et ajoutez vos favoris !';
    $browseCars = 'Parcourir les voitures';
    $noUpcoming = 'Aucune location à venir';
    $remove = 'Supprimer';
    $checkout = 'Paiement';
    $details = 'Détails';
    $bookNow = 'Réserver maintenant';
    $modifyBooking = 'Modifier la réservation';
    $viewDetails = 'Voir les détails';
    $basketSummary = 'Récapitulatif du panier';
    $total = 'Total';
    $proceedToCheckout = 'Procéder au paiement';
    $emptyBasket = 'Votre panier est vide';
    $addCarsToBasket = 'Ajoutez des voitures à votre panier pour commencer !';
    $noRentalHistory = 'Aucun historique de location';
    $bookFirstCar = 'Réservez votre première voiture';
    $profileInfo = 'Informations du profil';
    $editProfile = 'Modifier le profil';
    $fullName = 'Nom complet';
    $emailAddress = 'Adresse e-mail';
    $phoneNumber = 'Téléphone';
    $memberSince = 'Membre depuis';
    $city = 'Ville';
    $accountSettings = 'Paramètres du compte';
    $changePassword = 'Changer le mot de passe';
    $notificationPrefs = 'Préférences de notification';
    $deleteAccount = 'Supprimer le compte';
    $notProvided = 'Non fourni';
    $logout = 'Déconnexion';
    $home = 'Accueil';
    $cars = 'Voitures';
    $contact = 'Contact';
    $dashboard = 'Tableau de bord';
    $quickLinks = 'Liens rapides';
    $contactUs = 'Contactez-nous';
    $rightsReserved = 'Tous droits réservés.';
    $redeemPoints = 'Échanger des points';
    $availableRewards = 'Récompenses disponibles';
    $pointsNeeded = 'Points nécessaires';
    $redeem = 'Échanger';
    $congrats = 'Félicitations !';
    $voucherCode = 'Votre code promo est';
    $copyCode = 'Copier le code';
    $codeCopied = 'Code copié dans le presse-papiers !';
    $insufficientPoints = 'Points insuffisants';
    $voucherDescription = '£10 de réduction sur votre prochaine location';
    $voucherDescription2 = '£25 de réduction sur votre prochaine location';
    $voucherDescription3 = 'Surclassement gratuit en voiture premium';
    $redeemSuccess = 'Bon échangé avec succès !';
    $pointsRemaining = 'Points restants';
} elseif ($language == 'de') {
    $themeText = 'Design';
    $lightText = 'Hell';
    $darkText = 'Dunkel';
    $fontSizeText = 'Schriftgröße';
    $resetText = 'Zurücksetzen';
    $languageText = 'Sprache';
    $welcomeBack = 'Willkommen zurück';
    $memberId = 'Mitglieds-ID';
    $overview = 'Übersicht';
    $myFavorites = 'Meine Favoriten';
    $myBasket = 'Mein Warenkorb';
    $rentalHistory = 'Mietverlauf';
    $profileSettings = 'Profileinstellungen';
    $dashboardSubtitle = 'Verwalten Sie Ihre Mieten, Favoriten und Kontoeinstellungen';
    $favoriteCars = 'Lieblingsautos';
    $itemsInBasket = 'Artikel im Warenkorb';
    $totalRentals = 'Gesamtmieten';
    $loyaltyPoints = 'Treuepunkte';
    $recentFavorites = 'Letzte Favoriten';
    $viewAll = 'Alle ansehen';
    $upcomingRentals = 'Kommende Mieten';
    $noFavorites = 'Noch keine Favoriten';
    $startBrowsing = 'Durchstöbern Sie unsere Fahrzeugsammlung und fügen Sie Ihre Favoriten hinzu!';
    $browseCars = 'Autos durchsuchen';
    $noUpcoming = 'Keine kommenden Mieten';
    $remove = 'Entfernen';
    $checkout = 'Zur Kasse';
    $details = 'Details';
    $bookNow = 'Jetzt buchen';
    $modifyBooking = 'Buchung ändern';
    $viewDetails = 'Details ansehen';
    $basketSummary = 'Warenkorb Zusammenfassung';
    $total = 'Gesamt';
    $proceedToCheckout = 'Zur Kasse gehen';
    $emptyBasket = 'Ihr Warenkorb ist leer';
    $addCarsToBasket = 'Fügen Sie Autos zu Ihrem Warenkorb hinzu, um zu beginnen!';
    $noRentalHistory = 'Kein Mietverlauf';
    $bookFirstCar = 'Buchen Sie Ihr erstes Auto';
    $profileInfo = 'Profilinformationen';
    $editProfile = 'Profil bearbeiten';
    $fullName = 'Vollständiger Name';
    $emailAddress = 'E-Mail-Adresse';
    $phoneNumber = 'Telefon';
    $memberSince = 'Mitglied seit';
    $city = 'Stadt';
    $accountSettings = 'Kontoeinstellungen';
    $changePassword = 'Passwort ändern';
    $notificationPrefs = 'Benachrichtigungseinstellungen';
    $deleteAccount = 'Konto löschen';
    $notProvided = 'Nicht angegeben';
    $logout = 'Abmelden';
    $home = 'Startseite';
    $cars = 'Autos';
    $contact = 'Kontakt';
    $dashboard = 'Dashboard';
    $quickLinks = 'Schnelllinks';
    $contactUs = 'Kontaktieren Sie uns';
    $rightsReserved = 'Alle Rechte vorbehalten.';
    $redeemPoints = 'Punkte einlösen';
    $availableRewards = 'Verfügbare Belohnungen';
    $pointsNeeded = 'Benötigte Punkte';
    $redeem = 'Einlösen';
    $congrats = 'Glückwunsch!';
    $voucherCode = 'Ihr Gutscheincode ist';
    $copyCode = 'Code kopieren';
    $codeCopied = 'Code in Zwischenablage kopiert!';
    $insufficientPoints = 'Nicht genügend Punkte';
    $voucherDescription = '£10 Rabatt auf Ihre nächste Miete';
    $voucherDescription2 = '£25 Rabatt auf Ihre nächste Miete';
    $voucherDescription3 = 'Kostenlose Upgrade auf Premium-Auto';
    $redeemSuccess = 'Gutschein erfolgreich eingelöst!';
    $pointsRemaining = 'Verbleibende Punkte';
}

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

// NEW: Check for existing redeemed vouchers in session
$redeemed_vouchers = $_SESSION['redeemed_vouchers'] ?? [];

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

// Get cities for dropdown in edit profile
$cities_query = "SELECT city_id, city_name FROM cities ORDER BY city_name";
$cities_result = $conn->query($cities_query);
$cities = [];
while ($city_row = $cities_result->fetch_assoc()) {
    $cities[] = $city_row;
}

// Display success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// NEW: Handle voucher redemption via POST
$voucher_message = '';
$voucher_code = '';
$redeemed_points = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['redeem_voucher'])) {
    $points_required = (int)$_POST['points_required'];
    $voucher_value = $_POST['voucher_value'];
    
    if ($loyalty_points >= $points_required) {
        // Generate a random voucher code
        $voucher_code = strtoupper(substr(md5(uniqid()), 0, 8));
        
        // Store in session to prevent duplicate redemptions (simple approach)
        $redemption_id = 'voucher_' . $points_required;
        if (!in_array($redemption_id, $redeemed_vouchers)) {
            $redeemed_vouchers[] = $redemption_id;
            $_SESSION['redeemed_vouchers'] = $redeemed_vouchers;
            
            // In a real app, you would store this in a database
            $voucher_message = $redeemSuccess;
            $redeemed_points = $points_required;
        } else {
            $error_message = 'You have already redeemed this voucher!';
        }
    } else {
        $error_message = $insufficientPoints;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - Motiv Car Hire</title>
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
            --success: #28a745;
            --warning: #ffc107;
            --info: #17a2b8;
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
        [data-theme="dark"] .section-title,
        [data-theme="dark"] .stat-number,
        [data-theme="dark"] .stat-label,
        [data-theme="dark"] .info-label {
            color: #ffffff !important;
        }

        [data-theme="dark"] p,
        [data-theme="dark"] .car-specs,
        [data-theme="dark"] .car-name,
        [data-theme="dark"] .info-value,
        [data-theme="dark"] .footer-column p,
        [data-theme="dark"] .footer-column ul li {
            color: #cccccc;
        }

        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .car-card,
        [data-theme="dark"] .dashboard-section,
        [data-theme="dark"] .dashboard-nav,
        [data-theme="dark"] .reward-card {
            background-color: #2d2d2d;
            border-color: #404040;
        }

        [data-theme="dark"] .dashboard-container {
            background-color: #1a1a1a;
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
        
        .basket-indicator {
            position: relative;
        }
        
        .basket-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff7f50;
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
        
        .dashboard-container {
            padding: 40px 0;
            background-color: #f5f5f5;
            min-height: calc(100vh - 80px);
        }
        
        .dashboard-header {
            background: linear-gradient(to right, #5c2aa5, #a0206a);
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .welcome-section {
            text-align: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .welcome-title {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .welcome-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .member-id {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-weight: 600;
        }
        
        .dashboard-nav {
            background: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .nav-tabs {
            display: flex;
            list-style: none;
            overflow-x: auto;
        }
        
        .nav-tab {
            padding: 20px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
            font-weight: 600;
            color: #666;
        }
        
        .nav-tab.active {
            color: #5c2aa5;
            border-bottom-color: #5c2aa5;
        }
        
        .nav-tab:hover {
            color: #5c2aa5;
            background: rgba(140, 0, 80, 0.05);
        }
        
        .tab-content {
            display: none;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .tab-content.active {
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
            color: #5c2aa5;
            font-size: 1.5rem;
            margin: 0;
        }
        
        .view-all {
            color: #004aad;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .view-all:hover {
            text-decoration: underline;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(to right, #004aad, #5c2aa5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }
        
        .stat-icon i {
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #5c2aa5;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .cars-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        
        .car-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .car-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }
        
        .car-image {
            height: 160px;
            width: 100%;
            overflow: hidden;
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
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }
        
        .car-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: #5c2aa5;
        }
        
        .car-specs {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .car-spec {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .car-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-secondary, .btn-primary, .btn-success, .btn-warning {
            flex: 1;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-align: center;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid #004aad;
            color: #004aad;
        }
        
        .btn-secondary:hover {
            background: rgba(0, 74, 173, 0.05);
        }
        
        .btn-primary {
            background: #004aad;
            color: white;
        }
        
        .btn-primary:hover {
            background: #a0206a;
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
            color: #333;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-danger:hover {
            background: #c82333;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 15px;
            display: block;
        }
        
        .rental-history {
            width: 100%;
            border-collapse: collapse;
        }
        
        .rental-history th,
        .rental-history td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .rental-history th {
            background: #f8f8f8;
            color: #5c2aa5;
            font-weight: 600;
        }
        
        .rental-history tr:hover {
            background: #f8f8f8;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-upcoming {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .status-completed {
            background: #f5f5f5;
            color: #666;
        }
        
        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }
        
        .profile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .info-group {
            margin-bottom: 20px;
        }
        
        .info-label {
            font-weight: 600;
            color: #5c2aa5;
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .info-value {
            color: #333;
            font-size: 1.1rem;
        }
        
        .edit-profile-btn {
            background: #004aad;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .edit-profile-btn:hover {
            background: #a0206a;
        }
        
        .basket-summary {
            background: #f8f8f8;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .basket-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .basket-item:last-child {
            border-bottom: none;
        }
        
        .basket-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            margin-top: 15px;
            border-top: 2px solid #ddd;
            font-weight: 700;
            font-size: 1.2rem;
            color: #5c2aa5;
        }

        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            margin-top: 40px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
            padding: 40px 0 20px;
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
            padding: 20px 0;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .copyright p {
            color: var(--footer-text);
            font-size: 0.9rem;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
        }

        .modal-content {
            background-color: var(--bg-primary);
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
        }

        .close-modal:hover {
            color: var(--text-primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: var(--text-primary);
            font-weight: 600;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--vivid-indigo);
        }

        .checkbox-group {
            margin-bottom: 15px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-weight: normal;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .profile-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        [data-theme="dark"] .success-message {
            background-color: #155724;
            color: #d4edda;
            border-color: #28a745;
        }

        [data-theme="dark"] .error-message {
            background-color: #721c24;
            color: #f8d7da;
            border-color: #dc3545;
        }

        [data-theme="dark"] .modal-content {
            background-color: #2d2d2d;
            border: 1px solid #404040;
        }

        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group select {
            background-color: #333;
            border-color: #404040;
            color: #fff;
        }

        /* NEW: Rewards/Loyalty Points Styles */
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .reward-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            border: 2px solid transparent;
        }

        .reward-card:hover {
            transform: translateY(-5px);
            border-color: var(--vivid-indigo);
        }

        .reward-card.redeemed {
            opacity: 0.7;
            filter: grayscale(0.5);
            border-color: var(--success);
        }

        .reward-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(to right, #004aad, #5c2aa5);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
        }

        .reward-icon i {
            color: white;
            font-size: 1.8rem;
        }

        .reward-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: #5c2aa5;
            margin-bottom: 10px;
        }

        .reward-description {
            color: #666;
            margin-bottom: 15px;
            font-size: 0.95rem;
        }

        .reward-points {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ff7f50;
            margin-bottom: 15px;
        }

        .reward-points i {
            margin-right: 5px;
        }

        .reward-status {
            margin-top: 15px;
            padding: 8px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .reward-status.redeemed {
            background-color: #d4edda;
            color: #155724;
        }

        .voucher-display {
            background: linear-gradient(to right, #004aad, #5c2aa5);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            text-align: center;
        }

        .voucher-code {
            font-size: 1.8rem;
            font-weight: 700;
            letter-spacing: 3px;
            margin: 10px 0;
            font-family: monospace;
        }

        .copy-btn {
            background: white;
            color: #5c2aa5;
            border: none;
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: #f0f0f0;
            transform: scale(1.05);
        }

        .points-badge {
            display: inline-block;
            background: #ff7f50;
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .current-points {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 20px;
        }

        .current-points span {
            font-size: 1.8rem;
            font-weight: 700;
            color: #5c2aa5;
        }
        
        @media (max-width: 992px) {
            .profile-info {
                grid-template-columns: 1fr;
            }
            
            .cars-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 2rem;
            }
            
            .nav-tabs {
                flex-wrap: wrap;
            }
            
            .nav-tab {
                padding: 15px 20px;
            }
            
            .dashboard-section {
                padding: 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .rental-history {
                display: block;
                overflow-x: auto;
            }

            .modal-content {
                margin: 10% auto;
                padding: 20px;
                width: 95%;
            }

            .rewards-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .cars-grid {
                grid-template-columns: 1fr;
            }
            
            .car-actions {
                flex-direction: column;
            }

            .profile-actions {
                flex-direction: column;
            }

            .profile-actions button {
                width: 100%;
                margin: 5px 0 !important;
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
                    <li><a href="landing.php"><?php echo $home; ?></a></li>
                    <li><a href="cars.php"><?php echo $cars; ?></a></li>
                    <li><a href="contact.php"><?php echo $contact; ?></a></li>
                    <li><a href="customer-dashboard.php" class="active"><?php echo $dashboard; ?></a></li>
                    <li>
                        <a href="logout.php" style="color: #ff4444;">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $logout; ?>
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
                            <span class="basket-count"><?php echo $basket_count; ?></span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="dashboard-header">
        <div class="welcome-section">
            <h1 class="welcome-title"><?php echo $welcomeBack; ?>, <?php echo htmlspecialchars($customer['first_name']); ?>!</h1>
            <p class="welcome-subtitle"><?php echo $dashboardSubtitle; ?></p>
            <div class="member-id"><?php echo $memberId; ?>: <?php echo $customer['customer_id']; ?></div>
        </div>
    </section>

    <nav class="dashboard-nav">
        <div class="nav-container">
            <ul class="nav-tabs">
                <li class="nav-tab active" data-tab="overview"><?php echo $overview; ?></li>
                <li class="nav-tab" data-tab="favorites"><?php echo $myFavorites; ?></li>
                <li class="nav-tab" data-tab="basket"><?php echo $myBasket; ?></li>
                <li class="nav-tab" data-tab="rentals"><?php echo $rentalHistory; ?></li>
                <li class="nav-tab" data-tab="profile"><?php echo $profileSettings; ?></li>
                <!-- NEW: Rewards tab -->
                <li class="nav-tab" data-tab="rewards"><?php echo $redeemPoints; ?></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <?php if ($success_message): ?>
            <div class="message success-message" style="max-width: 1200px; margin: 0 auto 20px; padding: 0 20px;">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error-message" style="max-width: 1200px; margin: 0 auto 20px; padding: 0 20px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- NEW: Voucher success display -->
        <?php if ($voucher_message && $voucher_code): ?>
            <div class="message success-message" style="max-width: 1200px; margin: 0 auto 20px; padding: 0 20px;">
                <strong><?php echo $congrats; ?></strong><br>
                <?php echo $voucher_message; ?><br>
                <div style="background: white; color: #155724; padding: 10px; border-radius: 6px; margin-top: 10px; font-family: monospace; font-size: 1.2rem;">
                    <?php echo $voucherCode; ?>: <strong><?php echo $voucher_code; ?></strong>
                </div>
                <button class="copy-btn" onclick="copyVoucherCode('<?php echo $voucher_code; ?>')" style="margin-top: 10px;"><?php echo $copyCode; ?></button>
            </div>
        <?php endif; ?>

        <div class="tab-content active" id="overview">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-number"><?php echo $favorites_count; ?></div>
                    <div class="stat-label"><?php echo $favoriteCars; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-basket"></i>
                    </div>
                    <div class="stat-number"><?php echo $basket_count; ?></div>
                    <div class="stat-label"><?php echo $itemsInBasket; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-car"></i>
                    </div>
                    <div class="stat-number"><?php echo $rentals_count; ?></div>
                    <div class="stat-label"><?php echo $totalRentals; ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number"><?php echo $loyalty_points; ?></div>
                    <div class="stat-label"><?php echo $loyaltyPoints; ?></div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $recentFavorites; ?></h2>
                    <a href="#favorites" class="view-all" onclick="switchTab('favorites')"><?php echo $viewAll; ?></a>
                </div>
                <div class="cars-grid" id="recentFavorites">
                    <?php if (count($recent_favorites) > 0): ?>
                        <?php foreach($recent_favorites as $car): ?>
                            <div class="car-card">
                                <div class="car-image">
                                    <img src="<?php echo htmlspecialchars($car['image_url'] ?? 'car-default.jpg'); ?>" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                </div>
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?></h3>
                                        <div class="car-price">£<?php echo $car['price_per_day']; ?><span>/day</span></div>
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
                                    </div>
                                    <!-- REMOVED: View Details and Book Now buttons -->
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-heart"></i>
                            <h3><?php echo $noFavorites; ?></h3>
                            <p><?php echo $startBrowsing; ?></p>
                            <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;"><?php echo $browseCars; ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $upcomingRentals; ?></h2>
                    <a href="#rentals" class="view-all" onclick="switchTab('rentals')"><?php echo $viewAll; ?></a>
                </div>
                <div id="upcomingRentals">
                    <?php if (count($upcoming_rentals) > 0): ?>
                        <?php foreach($upcoming_rentals as $rental): ?>
                            <div class="car-card" style="margin-bottom: 15px;">
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($rental['make_name'] . ' ' . $rental['model']); ?></h3>
                                        <div class="car-price">£<?php echo $rental['total_cost']; ?></div>
                                    </div>
                                    <div class="car-specs">
                                        <div class="car-spec">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M j, Y', strtotime($rental['start_date'])); ?></span>
                                        </div>
                                        <div class="car-spec">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($rental['city_name']); ?></span>
                                        </div>
                                    </div>
                                    <!-- REMOVED: View Details and Modify Booking buttons -->
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 20px;">
                            <i class="fas fa-calendar"></i>
                            <p><?php echo $noUpcoming; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="favorites">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $myFavorites; ?></h2>
                </div>
                <div class="cars-grid" id="favoritesGrid">
                    <?php if (count($all_favorites) > 0): ?>
                        <?php foreach($all_favorites as $car): ?>
                            <div class="car-card">
                                <div class="car-image">
                                    <img src="<?php echo htmlspecialchars($car['image_url'] ?? 'car-default.jpg'); ?>" alt="<?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?>">
                                </div>
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($car['make_name'] . ' ' . $car['model']); ?></h3>
                                        <div class="car-price">£<?php echo $car['price_per_day']; ?><span>/day</span></div>
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
                                    </div>
                                    <div class="car-actions">
                                        <!-- REMOVED: View Details and Book Now buttons, keeping only Remove button -->
                                        <button class="btn-secondary remove-favorite" data-id="<?php echo $car['car_id']; ?>"><?php echo $remove; ?></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state" id="emptyFavorites">
                            <i class="fas fa-heart"></i>
                            <h3><?php echo $noFavorites; ?></h3>
                            <p><?php echo $startBrowsing; ?></p>
                            <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;"><?php echo $browseCars; ?></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tab-content" id="basket">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $myBasket; ?></h2>
                    <?php if ($basket_count > 0): ?>
                        <a href="basket.php" class="view-all"><?php echo $checkout; ?></a>
                    <?php endif; ?>
                </div>
                
                <?php if ($basket_count > 0): ?>
                    <div class="cars-grid" id="basketGrid">
                        <?php foreach($basket_items as $item): ?>
                            <div class="car-card">
                                <div class="car-image">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'car-default.jpg'); ?>" alt="<?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model']); ?>">
                                </div>
                                <div class="car-details">
                                    <div class="car-title">
                                        <h3 class="car-name"><?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model']); ?></h3>
                                        <div class="car-price">£<?php echo $item['price_per_day']; ?><span>/day</span></div>
                                    </div>
                                    <div class="car-specs">
                                        <div class="car-spec">
                                            <i class="fas fa-calendar"></i>
                                            <span><?php echo date('M j, Y', strtotime($item['start_date'])); ?> - <?php echo date('M j, Y', strtotime($item['end_date'])); ?></span>
                                        </div>
                                        <div class="car-spec">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($item['city_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="car-actions">
                                        <button class="btn-secondary remove-from-basket" data-id="<?php echo $item['item_id']; ?>"><?php echo $remove; ?></button>
                                        <button class="btn-primary" onclick="window.location.href='basket.php'"><?php echo $checkout; ?></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="basket-summary">
                        <h3><?php echo $basketSummary; ?></h3>
                        <div id="basketItemsList">
                            <?php foreach($basket_items as $item): ?>
                                <div class="basket-item">
                                    <div><?php echo htmlspecialchars($item['make_name'] . ' ' . $item['model']); ?> (<?php echo $item['rental_days']; ?> days)</div>
                                    <div>£<?php echo $item['estimated_total']; ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="basket-total">
                            <span><?php echo $total; ?>:</span>
                            <span>£<?php echo number_format($basket_total, 2); ?></span>
                        </div>
                        <a href="basket.php" class="btn-primary" style="display: block; text-align: center; margin-top: 15px;"><?php echo $proceedToCheckout; ?></a>
                    </div>
                <?php else: ?>
                    <div class="empty-state" id="emptyBasket">
                        <i class="fas fa-shopping-basket"></i>
                        <h3><?php echo $emptyBasket; ?></h3>
                        <p><?php echo $addCarsToBasket; ?></p>
                        <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;"><?php echo $browseCars; ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="rentals">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $rentalHistory; ?></h2>
                </div>
                <?php if (count($rentals) > 0): ?>
                    <div class="table-responsive">
                        <table class="rental-history">
                            <thead>
                                <tr>
                                    <th><?php echo $cars; ?></th>
                                    <th><?php echo $rentalHistory; ?></th>
                                    <th><?php echo $city; ?></th>
                                    <th><?php echo $total; ?></th>
                                    <th><?php echo $overview; ?></th>
                                </tr>
                            </thead>
                            <tbody id="rentalsTableBody">
                                <?php foreach($rentals as $rental): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($rental['make_name'] . ' ' . $rental['model']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($rental['start_date'])); ?> - <?php echo date('M j, Y', strtotime($rental['end_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($rental['city_name']); ?></td>
                                        <td>£<?php echo $rental['total_cost']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($rental['status_name']); ?>">
                                                <?php echo htmlspecialchars($rental['status_name']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state" id="emptyRentals">
                        <i class="fas fa-history"></i>
                        <h3><?php echo $noRentalHistory; ?></h3>
                        <p><?php echo $bookFirstCar; ?></p>
                        <a href="cars.php" class="btn-primary" style="display: inline-block; margin-top: 15px;"><?php echo $browseCars; ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-content" id="profile">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $profileInfo; ?></h2>
                    <button class="edit-profile-btn" id="editProfileBtn"><?php echo $editProfile; ?></button>
                </div>
                <div class="profile-info">
                    <div>
                        <div class="info-group">
                            <div class="info-label"><?php echo $fullName; ?></div>
                            <div class="info-value" id="profileName"><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><?php echo $emailAddress; ?></div>
                            <div class="info-value" id="profileEmail"><?php echo htmlspecialchars($customer['email']); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><?php echo $phoneNumber; ?></div>
                            <div class="info-value" id="profilePhone"><?php echo htmlspecialchars($customer['phone'] ?? $notProvided); ?></div>
                        </div>
                    </div>
                    <div>
                        <div class="info-group">
                            <div class="info-label"><?php echo $memberSince; ?></div>
                            <div class="info-value" id="profileMemberSince"><?php echo date('F j, Y', strtotime($customer['created_at'])); ?></div>
                        </div>
                        <div class="info-group">
                            <div class="info-label"><?php echo $city; ?></div>
                            <div class="info-value" id="profileCity"><?php echo htmlspecialchars($customer['city_name'] ?? $notProvided); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $accountSettings; ?></h2>
                </div>
                <div class="profile-actions">
                    <button class="btn-secondary" id="changePasswordBtn" style="margin-right: 10px;"><?php echo $changePassword; ?></button>
                    <button class="btn-secondary" style="margin-right: 10px;"><?php echo $notificationPrefs; ?></button>
                    <button class="btn-secondary" id="deleteAccountBtn"><?php echo $deleteAccount; ?></button>
                </div>
            </div>
        </div>

        <!-- NEW: Rewards Tab Content -->
        <div class="tab-content" id="rewards">
            <div class="dashboard-section">
                <div class="section-header">
                    <h2 class="section-title"><?php echo $redeemPoints; ?></h2>
                </div>
                
                <div class="current-points">
                    <?php echo $loyaltyPoints; ?>: <span><?php echo $loyalty_points; ?></span>
                </div>

                <div class="rewards-grid">
                    <!-- Voucher 1: £10 off -->
                    <div class="reward-card <?php echo (in_array('voucher_50', $redeemed_vouchers)) ? 'redeemed' : ''; ?>">
                        <div class="reward-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h3 class="reward-title">£10 Voucher</h3>
                        <p class="reward-description"><?php echo $voucherDescription; ?></p>
                        <div class="reward-points">
                            <i class="fas fa-star"></i> 50 <?php echo $loyaltyPoints; ?>
                        </div>
                        
                        <?php if (in_array('voucher_50', $redeemed_vouchers)): ?>
                            <div class="reward-status redeemed">
                                <i class="fas fa-check-circle"></i> Already Redeemed
                            </div>
                        <?php elseif ($loyalty_points >= 50): ?>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="points_required" value="50">
                                <input type="hidden" name="voucher_value" value="10">
                                <button type="submit" name="redeem_voucher" class="btn-success" style="width: 100%;">
                                    <i class="fas fa-gift"></i> <?php echo $redeem; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="reward-status" style="color: #dc3545;">
                                <i class="fas fa-times-circle"></i> <?php echo $insufficientPoints; ?>
                            </div>
                            <div class="points-badge" style="margin-top: 10px;">
                                Need: <?php echo (50 - $loyalty_points); ?> more points
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Voucher 2: £25 off -->
                    <div class="reward-card <?php echo (in_array('voucher_100', $redeemed_vouchers)) ? 'redeemed' : ''; ?>">
                        <div class="reward-icon">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <h3 class="reward-title">£25 Voucher</h3>
                        <p class="reward-description"><?php echo $voucherDescription2; ?></p>
                        <div class="reward-points">
                            <i class="fas fa-star"></i> 100 <?php echo $loyaltyPoints; ?>
                        </div>
                        
                        <?php if (in_array('voucher_100', $redeemed_vouchers)): ?>
                            <div class="reward-status redeemed">
                                <i class="fas fa-check-circle"></i> Already Redeemed
                            </div>
                        <?php elseif ($loyalty_points >= 100): ?>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="points_required" value="100">
                                <input type="hidden" name="voucher_value" value="25">
                                <button type="submit" name="redeem_voucher" class="btn-success" style="width: 100%;">
                                    <i class="fas fa-gift"></i> <?php echo $redeem; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="reward-status" style="color: #dc3545;">
                                <i class="fas fa-times-circle"></i> <?php echo $insufficientPoints; ?>
                            </div>
                            <div class="points-badge" style="margin-top: 10px;">
                                Need: <?php echo (100 - $loyalty_points); ?> more points
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Voucher 3: Free Upgrade -->
                    <div class="reward-card <?php echo (in_array('voucher_150', $redeemed_vouchers)) ? 'redeemed' : ''; ?>">
                        <div class="reward-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h3 class="reward-title">Premium Upgrade</h3>
                        <p class="reward-description"><?php echo $voucherDescription3; ?></p>
                        <div class="reward-points">
                            <i class="fas fa-star"></i> 150 <?php echo $loyaltyPoints; ?>
                        </div>
                        
                        <?php if (in_array('voucher_150', $redeemed_vouchers)): ?>
                            <div class="reward-status redeemed">
                                <i class="fas fa-check-circle"></i> Already Redeemed
                            </div>
                        <?php elseif ($loyalty_points >= 150): ?>
                            <form method="POST" style="margin-top: 15px;">
                                <input type="hidden" name="points_required" value="150">
                                <input type="hidden" name="voucher_value" value="upgrade">
                                <button type="submit" name="redeem_voucher" class="btn-success" style="width: 100%;">
                                    <i class="fas fa-gift"></i> <?php echo $redeem; ?>
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="reward-status" style="color: #dc3545;">
                                <i class="fas fa-times-circle"></i> <?php echo $insufficientPoints; ?>
                            </div>
                            <div class="points-badge" style="margin-top: 10px;">
                                Need: <?php echo (150 - $loyalty_points); ?> more points
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($redeemed_points > 0): ?>
                    <div class="current-points" style="margin-top: 20px; color: var(--success);">
                        <i class="fas fa-check-circle"></i> <?php echo $pointsRemaining; ?>: <?php echo ($loyalty_points - $redeemed_points); ?>
                    </div>
                <?php endif; ?>
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
                    <h3><?php echo $quickLinks; ?></h3>
                    <ul>
                        <li><a href="landing.php"><?php echo $home; ?></a></li>
                        <li><a href="cars.php"><?php echo $cars; ?></a></li>
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

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><?php echo $editProfile; ?></h2>
            <form action="update-profile.php" method="POST">
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email"><?php echo $emailAddress; ?>:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone"><?php echo $phoneNumber; ?>:</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="city"><?php echo $city; ?>:</label>
                    <select id="city" name="city_id">
                        <option value=""><?php echo $notProvided; ?></option>
                        <?php foreach ($cities as $city_option): ?>
                            <option value="<?php echo $city_option['city_id']; ?>" <?php echo ($customer['city_id'] == $city_option['city_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($city_option['city_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-primary">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2><?php echo $changePassword; ?></h2>
            <form action="change-password.php" method="POST">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn-primary">Update Password</button>
            </form>
        </div>
    </div>

    <!-- Delete Account Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Confirm Delete</h2>
            <p style="margin: 20px 0; color: var(--text-primary);">Are you sure you want to delete your account? This action cannot be undone.</p>
            <form action="delete-account.php" method="POST">
                <div class="form-group">
                    <label for="confirm_delete">Type "DELETE" to confirm:</label>
                    <input type="text" id="confirm_delete" name="confirm_delete" placeholder="DELETE" required>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-secondary cancel-delete" style="flex: 1;">Cancel</button>
                    <button type="submit" class="btn-primary" style="flex: 1; background-color: #dc3545;">Delete Account</button>
                </div>
            </form>
        </div>
    </div>

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

        function switchTab(tabName) {
            // Update tab classes
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelector(`.nav-tab[data-tab="${tabName}"]`).classList.add('active');
            
            // Update content visibility
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
        }

        // NEW: Copy voucher code function
        function copyVoucherCode(code) {
            navigator.clipboard.writeText(code).then(function() {
                alert('<?php echo $codeCopied; ?>');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateFontSizeDisplay();
            
            // Theme options
            const themeOptions = document.querySelectorAll('.theme-option');
            themeOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    const theme = this.getAttribute('data-theme');
                    setTheme(theme);
                });
            });

            // Font size controls
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

            // Language options
            const languageOptions = document.querySelectorAll('.language-option');
            languageOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    const lang = this.getAttribute('data-lang');
                    setLanguage(lang);
                });
            });
            
            // Tab switching
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    switchTab(this.getAttribute('data-tab'));
                });
            });
            
            // Modal functionality
            const editProfileModal = document.getElementById('editProfileModal');
            const changePasswordModal = document.getElementById('changePasswordModal');
            const deleteConfirmModal = document.getElementById('deleteConfirmModal');

            // Open modals
            document.getElementById('editProfileBtn').addEventListener('click', function() {
                editProfileModal.style.display = 'block';
            });

            document.getElementById('changePasswordBtn').addEventListener('click', function() {
                changePasswordModal.style.display = 'block';
            });

            document.getElementById('deleteAccountBtn').addEventListener('click', function() {
                deleteConfirmModal.style.display = 'block';
            });

            // Close modals with X buttons
            document.querySelectorAll('.close-modal').forEach(closeBtn => {
                closeBtn.addEventListener('click', function() {
                    this.closest('.modal').style.display = 'none';
                });
            });

            // Cancel delete button
            document.querySelector('.cancel-delete')?.addEventListener('click', function() {
                deleteConfirmModal.style.display = 'none';
                document.getElementById('confirm_delete').value = '';
            });

            // Close modals when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target.classList.contains('modal')) {
                    event.target.style.display = 'none';
                }
            });

            // Password confirmation validation
            document.querySelector('#changePasswordModal form')?.addEventListener('submit', function(e) {
                const newPass = document.getElementById('new_password').value;
                const confirmPass = document.getElementById('confirm_password').value;
                
                if (newPass !== confirmPass) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });

            // Delete account confirmation validation
            document.querySelector('#deleteConfirmModal form')?.addEventListener('submit', function(e) {
                const confirmInput = document.getElementById('confirm_delete').value;
                if (confirmInput !== 'DELETE') {
                    e.preventDefault();
                    alert('Please type DELETE to confirm account deletion');
                }
            });
            
            // Add car card event listeners
            addCarCardEventListeners();
        });

        function addCarCardEventListeners() {
            // View details buttons
            document.querySelectorAll('.view-details').forEach(button => {
                button.addEventListener('click', function() {
                    const carId = this.getAttribute('data-id');
                    window.location.href = 'car-details.php?id=' + carId;
                });
            });
            
            // Book now buttons
            document.querySelectorAll('.book-now').forEach(button => {
                button.addEventListener('click', function() {
                    const carId = this.getAttribute('data-id');
                    window.location.href = 'booking.php?car_id=' + carId;
                });
            });
            
            // Remove from basket buttons
            document.querySelectorAll('.remove-from-basket').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.getAttribute('data-id');
                    if (confirm('Are you sure you want to remove this item from your basket?')) {
                        window.location.href = 'remove-from-basket.php?item_id=' + itemId;
                    }
                });
            });
            
            // Remove favorite buttons
            document.querySelectorAll('.remove-favorite').forEach(button => {
                button.addEventListener('click', function() {
                    const carId = this.getAttribute('data-id');
                    if (confirm('Are you sure you want to remove this car from your favorites?')) {
                        window.location.href = 'remove-favorite.php?car_id=' + carId;
                    }
                });
            });
        }

        // Auto-hide messages after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.message').forEach(msg => {
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
<?php
$conn->close();
?>
