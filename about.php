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

$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : 'light';
$fontSize = isset($_COOKIE['fontSize']) ? $_COOKIE['fontSize'] : '100';
$language = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en';

// Language variables for about page
$themeText = 'Theme';
$lightText = 'Light';
$darkText = 'Dark';
$fontSizeText = 'Font Size';
$resetText = 'Reset';
$languageText = 'Language';

// About page content translations
if ($language == 'en') {
    $aboutHeroTitle = 'About Motiv Car Hire';
    $aboutHeroText = 'Your trusted partner for premium car rental services in Birmingham and across the UK';
    $ourStory = 'Our Story';
    $storyDesc1 = 'Founded in Birmingham, Motiv Car Hire began as a small business in Birmingham with a vision to make car rental accessible and enjoyable for everyone. We\'ve grown into one of the UK\'s trusted car rental companies.';
    $storyDesc2 = 'Our commitment to customer satisfaction, transparent pricing, and maintaining a modern, reliable fleet has earned us the trust of thousands nationwide. We believe the right vehicle makes every journey memorable.';
    $happyCustomers = 'Happy Customers';
    $vehicles = 'Vehicles';
    $ukCities = 'UK Cities';
    $ourValues = 'Our Values';
    $valuesSubtitle = 'The principles that guide everything we do';
    $trustTitle = 'Trust & Reliability';
    $trustDesc = 'We build lasting relationships based on trust, delivering reliable services and maintaining transparent communication.';
    $excellenceTitle = 'Excellence';
    $excellenceDesc = 'We strive for excellence in every aspect, from vehicle maintenance to customer support and booking experience.';
    $customerFirstTitle = 'Customer First';
    $customerFirstDesc = 'Our customers are at the heart of everything we do. We listen, adapt, and go the extra mile for complete satisfaction.';
    $teamTitle = 'Meet Our Leadership Team';
    $teamSubtitle = 'The passionate individuals behind Motiv Car Hire';
    $fleetTitle = 'Premium Vehicle Fleet';
    $fleetDesc = 'Discover our wide range of quality vehicles from economy to luxury';
    $serviceTitle = 'Excellent Customer Service';
    $serviceDesc = 'Our dedicated team is available 24/7 to assist you';
    $locationsTitle = 'Convenient Locations';
    $locationsDesc = 'Pick-up and drop-off points across major cities';
    $ecoTitle = 'Eco-Friendly Options';
    $ecoDesc = 'Choose from our range of electric and hybrid vehicles';
    $footerTagline = 'Your trusted partner for car rental services in Birmingham and beyond.';
    $quickLinks = 'Quick Links';
    $home = 'Home';
    $about = 'About Us';
    $ourFleet = 'Our Fleet';
    $contact = 'Contact';
    $contactUs = 'Contact Us';
    $rightsReserved = 'All rights reserved.';
    $dashboard = 'Dashboard';
    $login = 'Login';
    $logout = 'Logout';
    $cars = 'Cars';
} elseif ($language == 'es') {
    $themeText = 'Tema';
    $lightText = 'Claro';
    $darkText = 'Oscuro';
    $fontSizeText = 'Tamaño de fuente';
    $resetText = 'Reiniciar';
    $languageText = 'Idioma';
    $aboutHeroTitle = 'Acerca de Motiv Car Hire';
    $aboutHeroText = 'Su socio de confianza para servicios de alquiler de autos premium en Birmingham y en todo el Reino Unido';
    $ourStory = 'Nuestra Historia';
    $storyDesc1 = 'Fundada en Birmingham, Motiv Car Hire comenzó como un pequeño negocio en Birmingham con la visión de hacer que el alquiler de autos sea accesible y agradable para todos. Nos hemos convertido en una de las empresas de alquiler de autos de confianza del Reino Unido.';
    $storyDesc2 = 'Nuestro compromiso con la satisfacción del cliente, precios transparentes y mantener una flota moderna y confiable nos ha ganado la confianza de miles de personas en todo el país. Creemos que el vehículo adecuado hace que cada viaje sea memorable.';
    $happyCustomers = 'Clientes Felices';
    $vehicles = 'Vehículos';
    $ukCities = 'Ciudades del Reino Unido';
    $ourValues = 'Nuestros Valores';
    $valuesSubtitle = 'Los principios que guían todo lo que hacemos';
    $trustTitle = 'Confianza y Confiabilidad';
    $trustDesc = 'Construimos relaciones duraderas basadas en la confianza, brindando servicios confiables y manteniendo una comunicación transparente.';
    $excellenceTitle = 'Excelencia';
    $excellenceDesc = 'Nos esforzamos por la excelencia en cada aspecto, desde el mantenimiento del vehículo hasta el soporte al cliente y la experiencia de reserva.';
    $customerFirstTitle = 'Cliente Primero';
    $customerFirstDesc = 'Nuestros clientes están en el corazón de todo lo que hacemos. Escuchamos, nos adaptamos y hacemos todo lo posible para lograr una satisfacción completa.';
    $teamTitle = 'Conozca a Nuestro Equipo Directivo';
    $teamSubtitle = 'Las personas apasionadas detrás de Motiv Car Hire';
    $fleetTitle = 'Flota de Vehículos Premium';
    $fleetDesc = 'Descubra nuestra amplia gama de vehículos de calidad, desde económicos hasta de lujo';
    $serviceTitle = 'Excelente Servicio al Cliente';
    $serviceDesc = 'Nuestro equipo dedicado está disponible 24/7 para ayudarlo';
    $locationsTitle = 'Ubicaciones Convenientes';
    $locationsDesc = 'Puntos de recogida y devolución en las principales ciudades';
    $ecoTitle = 'Opciones Ecológicas';
    $ecoDesc = 'Elija entre nuestra gama de vehículos eléctricos e híbridos';
    $footerTagline = 'Su socio de confianza para servicios de alquiler de autos en Birmingham y más allá.';
    $quickLinks = 'Enlaces rápidos';
    $home = 'Inicio';
    $about = 'Sobre Nosotros';
    $ourFleet = 'Nuestra Flota';
    $contact = 'Contacto';
    $contactUs = 'Contáctenos';
    $rightsReserved = 'Todos los derechos reservados.';
    $dashboard = 'Panel';
    $login = 'Iniciar sesión';
    $logout = 'Cerrar sesión';
    $cars = 'Autos';
} elseif ($language == 'fr') {
    $themeText = 'Thème';
    $lightText = 'Clair';
    $darkText = 'Sombre';
    $fontSizeText = 'Taille de police';
    $resetText = 'Réinitialiser';
    $languageText = 'Langue';
    $aboutHeroTitle = 'À propos de Motiv Car Hire';
    $aboutHeroText = 'Votre partenaire de confiance pour les services de location de voitures premium à Birmingham et dans tout le Royaume-Uni';
    $ourStory = 'Notre Histoire';
    $storyDesc1 = 'Fondée à Birmingham, Motiv Car Hire a commencé comme une petite entreprise à Birmingham avec la vision de rendre la location de voitures accessible et agréable pour tous. Nous sommes devenus l\'une des sociétés de location de voitures de confiance au Royaume-Uni.';
    $storyDesc2 = 'Notre engagement envers la satisfaction client, des prix transparents et le maintien d\'une flotte moderne et fiable nous a valu la confiance de milliers de personnes à travers le pays. Nous croyons que le bon véhicule rend chaque voyage mémorable.';
    $happyCustomers = 'Clients Satisfaits';
    $vehicles = 'Véhicules';
    $ukCities = 'Villes du Royaume-Uni';
    $ourValues = 'Nos Valeurs';
    $valuesSubtitle = 'Les principes qui guident tout ce que nous faisons';
    $trustTitle = 'Confiance et Fiabilité';
    $trustDesc = 'Nous construisons des relations durables basées sur la confiance, en fournissant des services fiables et en maintenant une communication transparente.';
    $excellenceTitle = 'Excellence';
    $excellenceDesc = 'Nous recherchons l\'excellence dans tous les aspects, de l\'entretien des véhicules au support client et à l\'expérience de réservation.';
    $customerFirstTitle = 'Client d\'abord';
    $customerFirstDesc = 'Nos clients sont au cœur de tout ce que nous faisons. Nous écoutons, nous adaptons et allons au-delà pour une satisfaction complète.';
    $teamTitle = 'Rencontrez Notre Équipe de Direction';
    $teamSubtitle = 'Les personnes passionnées derrière Motiv Car Hire';
    $fleetTitle = 'Flotte de Véhicules Premium';
    $fleetDesc = 'Découvrez notre large gamme de véhicules de qualité, de l\'économique au luxe';
    $serviceTitle = 'Excellent Service Client';
    $serviceDesc = 'Notre équipe dédiée est disponible 24h/24 et 7j/7 pour vous aider';
    $locationsTitle = 'Emplacements Pratiques';
    $locationsDesc = 'Points de prise en charge et de restitution dans les grandes villes';
    $ecoTitle = 'Options Écologiques';
    $ecoDesc = 'Choisissez parmi notre gamme de véhicules électriques et hybrides';
    $footerTagline = 'Votre partenaire de confiance pour les services de location de voitures à Birmingham et au-delà.';
    $quickLinks = 'Liens rapides';
    $home = 'Accueil';
    $about = 'À propos';
    $ourFleet = 'Notre flotte';
    $contact = 'Contact';
    $contactUs = 'Contactez-nous';
    $rightsReserved = 'Tous droits réservés.';
    $dashboard = 'Tableau de bord';
    $login = 'Connexion';
    $logout = 'Déconnexion';
    $cars = 'Voitures';
} elseif ($language == 'de') {
    $themeText = 'Design';
    $lightText = 'Hell';
    $darkText = 'Dunkel';
    $fontSizeText = 'Schriftgröße';
    $resetText = 'Zurücksetzen';
    $languageText = 'Sprache';
    $aboutHeroTitle = 'Über Motiv Car Hire';
    $aboutHeroText = 'Ihr vertrauenswürdiger Partner für Premium-Autovermietungen in Birmingham und im gesamten Vereinigten Königreich';
    $ourStory = 'Unsere Geschichte';
    $storyDesc1 = 'Motiv Car Hire wurde in Birmingham als kleines Unternehmen mit der Vision gegründet, Autovermietung für alle zugänglich und angenehm zu machen. Wir sind zu einem der vertrauenswürdigsten Autovermietungen Großbritanniens geworden.';
    $storyDesc2 = 'Unser Engagement für Kundenzufriedenheit, transparente Preise und eine moderne, zuverlässige Flotte hat uns das Vertrauen von Tausenden im ganzen Land eingebracht. Wir glauben, dass das richtige Fahrzeug jede Reise unvergesslich macht.';
    $happyCustomers = 'Zufriedene Kunden';
    $vehicles = 'Fahrzeuge';
    $ukCities = 'Städte in Großbritannien';
    $ourValues = 'Unsere Werte';
    $valuesSubtitle = 'Die Prinzipien, die alles leiten, was wir tun';
    $trustTitle = 'Vertrauen und Zuverlässigkeit';
    $trustDesc = 'Wir bauen dauerhafte Beziehungen auf, die auf Vertrauen basieren, bieten zuverlässige Dienstleistungen und pflegen eine transparente Kommunikation.';
    $excellenceTitle = 'Exzellenz';
    $excellenceDesc = 'Wir streben nach Exzellenz in jedem Aspekt, von der Fahrzeugwartung bis zum Kundensupport und Buchungserlebnis.';
    $customerFirstTitle = 'Kunde zuerst';
    $customerFirstDesc = 'Unsere Kunden stehen im Mittelpunkt alles, was wir tun. Wir hören zu, passen uns an und gehen die Extrameile für vollständige Zufriedenheit.';
    $teamTitle = 'Lernen Sie Unser Führungsteam Kennen';
    $teamSubtitle = 'Die leidenschaftlichen Menschen hinter Motiv Car Hire';
    $fleetTitle = 'Premium-Fahrzeugflotte';
    $fleetDesc = 'Entdecken Sie unser breites Angebot an Qualitätsfahrzeugen von Economy bis Luxus';
    $serviceTitle = 'Hervorragender Kundenservice';
    $serviceDesc = 'Unser engagiertes Team steht Ihnen 24/7 zur Verfügung';
    $locationsTitle = 'Praktische Standorte';
    $locationsDesc = 'Abhol- und Rückgabeorte in Großstädten';
    $ecoTitle = 'Umweltfreundliche Optionen';
    $ecoDesc = 'Wählen Sie aus unserem Angebot an Elektro- und Hybridfahrzeugen';
    $footerTagline = 'Ihr vertrauenswürdiger Partner für Autovermietungen in Birmingham und darüber hinaus.';
    $quickLinks = 'Schnelllinks';
    $home = 'Startseite';
    $about = 'Über uns';
    $ourFleet = 'Unsere Flotte';
    $contact = 'Kontakt';
    $contactUs = 'Kontaktieren Sie uns';
    $rightsReserved = 'Alle Rechte vorbehalten.';
    $dashboard = 'Dashboard';
    $login = 'Anmelden';
    $logout = 'Abmelden';
    $cars = 'Autos';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
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

        [data-theme="dark"] h1,
        [data-theme="dark"] h2,
        [data-theme="dark"] h3,
        [data-theme="dark"] h4,
        [data-theme="dark"] h5,
        [data-theme="dark"] h6,
        [data-theme="dark"] .section-title,
        [data-theme="dark"] .stat-number,
        [data-theme="dark"] .stat-label,
        [data-theme="dark"] .value-card h3,
        [data-theme="dark"] .member-info h4,
        [data-theme="dark"] .slide-content h3,
        [data-theme="dark"] .footer-column h3 {
            color: #ffffff !important;
        }

        [data-theme="dark"] p,
        [data-theme="dark"] .value-card p,
        [data-theme="dark"] .member-info p,
        [data-theme="dark"] .member-info .position,
        [data-theme="dark"] .slide-content p,
        [data-theme="dark"] .footer-column p,
        [data-theme="dark"] .footer-column ul li {
            color: #cccccc;
        }

        [data-theme="dark"] .value-card,
        [data-theme="dark"] .team-member,
        [data-theme="dark"] .story-image img {
            background-color: #2d2d2d;
            border-color: #404040;
        }

        [data-theme="dark"] .values-section,
        [data-theme="dark"] .team-section {
            background-color: #1a1a1a;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: <?php echo $fontSize; ?>%;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        header, footer, .value-card, .team-member {
            background-color: var(--card-bg);
            color: var(--text-primary);
        }

        .about-hero {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)),
                        url('bg1.jpg') center/cover no-repeat;
            color: white;
            padding: 80px 0;
            text-align: center;
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

        [data-theme="dark"] nav ul li.dropdown .dropdown-content {
            background-color: #333333;
            border-color: #404040;
        }

        [data-theme="dark"] nav ul li.dropdown .dropdown-content a {
            color: #fff;
            background-color: #333333;
            border-bottom-color: #404040;
        }

        [data-theme="dark"] nav ul li.dropdown .dropdown-content a:hover {
            background-color: #404040;
            color: var(--coral-red);
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

        .about-hero h1 {
            font-size: 3rem;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            color: white;
        }
        
        .about-hero p {
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.9;
            color: white;
        }
        
        .story-section {
            padding: 60px 0;
            background-color: var(--bg-primary);
        }
        
        .story-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        
        .story-text h2 {
            color: var(--vivid-indigo);
            font-size: 2.2rem;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 12px;
        }
        
        .story-text h2::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 70px;
            height: 3px;
            background: var(--coral-red);
        }
        
        .story-text p {
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 20px;
            color: var(--text-secondary);
        }
        
        .story-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 30px;
            text-align: center;
        }
        
        .stat-item {
            padding: 15px;
            background: var(--bg-secondary);
            border-radius: 8px;
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-3px);
            background: var(--card-bg);
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--vivid-indigo);
            display: block;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        .story-image img {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: 0 5px 20px var(--shadow-color);
        }
        
        .values-section {
            padding: 40px 0 60px;
            background-color: var(--bg-secondary);
        }
        
        .section-title {
            text-align: center;
            color: var(--vivid-indigo);
            font-size: 2.2rem;
            margin-bottom: 10px;
        }
        
        .section-subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 1rem;
            margin-bottom: 40px;
        }
        
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .value-card {
            background: var(--card-bg);
            padding: 30px 25px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .value-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px var(--shadow-color);
        }
        
        .value-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--vivid-indigo), var(--cobalt-blue));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        
        .value-icon i {
            font-size: 1.8rem;
            color: white;
        }
        
        .value-card h3 {
            color: var(--vivid-indigo);
            margin-bottom: 12px;
            font-size: 1.3rem;
        }
        
        .value-card p {
            color: var(--text-secondary);
            line-height: 1.6;
            font-size: 0.95rem;
        }
        
        .team-section {
            padding: 60px 0;
            background-color: var(--bg-primary);
        }
        
        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 40px;
        }
        
        .team-member {
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease;
        }
        
        .team-member:hover {
            transform: translateY(-8px);
        }
        
        .member-image {
            height: 200px;
            overflow: hidden;
        }
        
        .member-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .team-member:hover .member-image img {
            transform: scale(1.1);
        }
        
        .member-info {
            padding: 20px;
            text-align: center;
        }
        
        .member-info h4 {
            color: var(--vivid-indigo);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .member-info .position {
            color: var(--coral-red);
            font-weight: 600;
            margin-bottom: 10px;
            display: block;
            font-size: 0.9rem;
        }
        
        .member-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .slider-section {
            padding: 60px 0;
            background-color: var(--bg-secondary);
        }
        
        .slider-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .swiper {
            width: 100%;
            height: 450px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 25px var(--shadow-color);
        }
        
        .swiper-slide {
            position: relative;
            overflow: hidden;
        }
        
        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .swiper-slide:hover img {
            transform: scale(1.05);
        }
        
        .slide-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 25px;
            transform: translateY(0);
            transition: transform 0.3s ease;
        }
        
        .swiper-slide:hover .slide-content {
            transform: translateY(0);
        }
        
        .slide-content h3 {
            font-size: 1.6rem;
            margin-bottom: 8px;
            color: white;
        }
        
        .slide-content p {
            font-size: 0.95rem;
            opacity: 0.9;
            margin-bottom: 0;
            color: white;
        }

        /* Footer Styles */
        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 0 20px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }

        .footer-column h3 {
            color: white;
            margin-bottom: 20px;
            font-size: 1.3rem;
            position: relative;
            padding-bottom: 10px;
        }

        .footer-column h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 2px;
            background: var(--coral-red);
        }

        .footer-column p {
            color: var(--footer-text);
            line-height: 1.6;
            opacity: 0.9;
        }

        .footer-column ul {
            list-style: none;
            padding: 0;
        }

        .footer-column ul li {
            margin-bottom: 12px;
        }

        .footer-column ul li a {
            color: var(--footer-text);
            text-decoration: none;
            transition: color 0.3s ease, padding-left 0.3s ease;
            display: inline-block;
        }

        .footer-column ul li a:hover {
            color: var(--coral-red);
            padding-left: 5px;
        }

        .footer-column ul li i {
            margin-right: 10px;
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
            opacity: 0.8;
        }
        
        @media (max-width: 992px) {
            .story-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .story-image {
                order: -1;
            }
            
            .about-hero h1 {
                font-size: 2.5rem;
            }
            
            .swiper {
                height: 350px;
            }
            
            .story-image img {
                height: 300px;
            }
            
            .footer-content {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .about-hero {
                padding: 60px 0;
            }
            
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .about-hero p {
                font-size: 1rem;
                padding: 0 15px;
            }
            
            .story-stats {
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }
            
            .swiper {
                height: 280px;
            }
            
            .slide-content {
                padding: 15px;
            }
            
            .slide-content h3 {
                font-size: 1.2rem;
            }
            
            .story-text h2 {
                font-size: 1.8rem;
            }
            
            .story-image img {
                height: 250px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .footer-column h3::after {
                left: 50%;
                transform: translateX(-50%);
            }
        }
        
        @media (max-width: 576px) {
            .story-stats {
                grid-template-columns: 1fr;
                max-width: 300px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .swiper {
                height: 220px;
            }
            
            .value-card {
                padding: 25px 20px;
            }
            
            .team-grid {
                grid-template-columns: 1fr;
                max-width: 300px;
                margin-left: auto;
                margin-right: auto;
            }
            
            .values-grid {
                grid-template-columns: 1fr;
                max-width: 300px;
                margin-left: auto;
                margin-right: auto;
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
                    <a href="landing.php" class="dropbtn"><?php echo $home; ?> <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="landing.php"><?php echo $home; ?></a>
                        <a href="about.php" class="active"><?php echo $about; ?></a>
                    </div>
                </li>
                <li><a href="cars.php"><?php echo $cars; ?></a></li>
                <li><a href="contact.php"><?php echo $contact; ?></a></li>

                <?php if (!isset($_SESSION['user'])): ?>
                    
                    <li><a href="loginPage.php"><?php echo $login; ?></a></li>
                <?php else: ?>
                    <li><a href="customer-dashboard.php"><?php echo $dashboard; ?></a></li>
                    <li>
                        <a href="logout.php" style="color: #ff7f50;">
                            <i class="fas fa-sign-out-alt"></i> <?php echo $logout; ?>
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

<section class="about-hero">
    <div class="container">
        <h1><?php echo $aboutHeroTitle; ?></h1>
        <p><?php echo $aboutHeroText; ?></p>
    </div>
</section>

<section class="story-section">
    <div class="container">
        <div class="story-grid">
            <div class="story-text">
                <h2><?php echo $ourStory; ?></h2>
                <p><?php echo $storyDesc1; ?></p>
                
                <p><?php echo $storyDesc2; ?></p>
                
                <div class="story-stats">
                    <div class="stat-item">
                        <span class="stat-number">5,000+</span>
                        <span class="stat-label"><?php echo $happyCustomers; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">200+</span>
                        <span class="stat-label"><?php echo $vehicles; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">5+</span>
                        <span class="stat-label"><?php echo $ukCities; ?></span>
                    </div>
                </div>
            </div>
            
            <div class="story-image">
                <img src="car_pics/car1.png" alt="Premium Fleet">
            </div>
        </div>
    </div>
</section>

<section class="values-section">
    <div class="container">
        <h2 class="section-title"><?php echo $ourValues; ?></h2>
        <p class="section-subtitle"><?php echo $valuesSubtitle; ?></p>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3><?php echo $trustTitle; ?></h3>
                <p><?php echo $trustDesc; ?></p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3><?php echo $excellenceTitle; ?></h3>
                <p><?php echo $excellenceDesc; ?></p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3><?php echo $customerFirstTitle; ?></h3>
                <p><?php echo $customerFirstDesc; ?></p>
            </div>
        </div>
    </div>
</section>

<section class="team-section">
    <div class="container">
        <h2 class="section-title"><?php echo $teamTitle; ?></h2>
        <p class="section-subtitle"><?php echo $teamSubtitle; ?></p>
        
        <div class="team-grid">
            <div class="team-member">
                <div class="member-info">
                    <h4>Zahra Ali Jaffer</h4>
                    <span class="position">Chief Operating Officer</span>
                    <p>Leads company-wide operations with focus on efficiency, service quality, and team performance.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Olivia Evans-Simms</h4>
                    <span class="position">Chief Fleet Officer</span>
                    <p>Oversees fleet strategy, ensuring vehicles are well-maintained, safe, and aligned with business needs.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>William Giles</h4>
                    <span class="position">Regional Director</span>
                    <p>Manages regional performance and supports branches in delivering consistent customer satisfaction.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Aaron Boadu</h4>
                    <span class="position">Business Manager</span>
                    <p>Drives business operations, ensuring smooth workflows and optimized commercial processes.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Aisha Bashir</h4>
                    <span class="position">Digital Marketing Manager</span>
                    <p>Develops and executes brand-focused digital strategies to grow visibility and customer engagement.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Ibrahim Al-Mohannadi</h4>
                    <span class="position">Accounts Manager</span>
                    <p>Ensures financial accuracy and oversees client billing, revenue tracking, and account coordination.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Yunus Masood</h4>
                    <span class="position">Rental Operations Supervisor</span>
                    <p>Coordinates daily rental operations, ensuring customers receive fast and seamless service.</p>
                </div>
            </div>

            <div class="team-member">
                <div class="member-info">
                    <h4>Omar Abou Jalil</h4>
                    <span class="position">Corporate Manager</span>
                    <p>Leads corporate client relations, supporting strategic partnerships and tailored mobility solutions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="slider-section">
    <div class="slider-container">
        <div class="swiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <img src="car_pics/car1.png" alt="Premium Fleet">
                    <div class="slide-content">
                        <h3><?php echo $fleetTitle; ?></h3>
                        <p><?php echo $fleetDesc; ?></p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <img src="car_pics/car2.png" alt="Customer Service">
                    <div class="slide-content">
                        <h3><?php echo $serviceTitle; ?></h3>
                        <p><?php echo $serviceDesc; ?></p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <img src="car_pics/car3.jpg" alt="Multiple Locations">
                    <div class="slide-content">
                        <h3><?php echo $locationsTitle; ?></h3>
                        <p><?php echo $locationsDesc; ?></p>
                    </div>
                </div>
                <div class="swiper-slide">
                    <img src="car_pics/car4.png" alt="Eco Friendly">
                    <div class="slide-content">
                        <h3><?php echo $ecoTitle; ?></h3>
                        <p><?php echo $ecoDesc; ?></p>
                    </div>
                </div>
            </div>
            <div class="swiper-pagination"></div>
            <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div>
        </div>
    </div>
</section>

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <h3>Motiv, Car Rental</h3>
                <p><?php echo $footerTagline; ?></p>
            </div>
            <div class="footer-column">
                <h3><?php echo $quickLinks; ?></h3>
                <ul>
                    <li><a href="landing.php"><?php echo $home; ?></a></li>
                    <li><a href="about.php"><?php echo $about; ?></a></li>
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

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
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

        const swiper = new Swiper('.swiper', {
            direction: 'horizontal',
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            speed: 1000,
        });
        
        const swiperContainer = document.querySelector('.swiper');
        swiperContainer.addEventListener('mouseenter', function() {
            swiper.autoplay.stop();
        });
        
        swiperContainer.addEventListener('mouseleave', function() {
            swiper.autoplay.start();
        });
        
    });
</script>

</body>
</html>
<?php
$conn->close();
?>
