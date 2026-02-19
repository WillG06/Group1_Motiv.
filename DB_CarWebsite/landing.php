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

// Language variables
$themeText = 'Theme';
$lightText = 'Light';
$darkText = 'Dark';
$fontSizeText = 'Font Size';
$resetText = 'Reset';
$languageText = 'Language';
$heroTitle = 'Motiv, Car Rental';
$heroText = 'At Motiv, we make car hire enjoyable! With flexible pick-up options, a variety of quality vehicles, and smooth booking, every journey feels effortless.';
$reserveText = 'Reserve a Vehicle';
$pickupLocationText = 'Pick-up Location';
$selectLocationText = 'Select a location';
$pickupDateTimeText = 'Pick-up Date & Time';
$dropoffDateTimeText = 'Drop-off Date & Time';
$showCarsText = 'Show Available Cars';
$whyChooseText = 'Why Choose Motiv?';
$vehicleSelectionText = 'Wide Vehicle Selection';
$vehicleDescText = 'Choose from economy cars, premium sedans, SUVs, and electric vehicles to suit your needs.';
$locationsText = 'Convenient Locations';
$locationsDescText = 'Multiple pickup and drop-off locations across Birmingham for your convenience.';
$priceGuaranteeText = 'Best Price Guarantee';
$priceDescText = 'We offer competitive rates with no hidden fees and a best price guarantee.';
$supportText = '24/7 Support';
$supportDescText = 'Our customer service team is available around the clock to assist you.';
$topCitiesText = 'Top Cities for Car Hire';
$bestSellingText = 'Best Selling Services';
$viewAllText = 'View All Listings';
$popularText = 'Our most popular rental options with top customer ratings';
$popularBadgeText = 'Most Popular';
$bestValueText = 'Best Value';
$ecoFriendlyText = 'Eco-Friendly';
$suvText = 'Premium SUV';
$economyText = 'Economy Car';
$luxuryText = 'Luxury Sedan';
$electricText = 'Electric Vehicle';
$suvDescText = 'Spacious and comfortable SUVs perfect for family trips or group travel.';
$economyDescText = 'Fuel-efficient and affordable cars ideal for city driving and short trips.';
$luxuryDescText = 'Premium vehicles for business trips or special occasions with comfort.';
$electricDescText = 'Environmentally friendly electric cars with modern features and operations.';
$reviewsText = 'reviews';
$seatsText = 'Seats:';
$luggageText = 'Luggage:';
$bagsText = 'bags';
$fuelTypeText = 'Fuel Type:';
$footerTaglineText = 'Your trusted partner for car rental services in Birmingham and beyond.';
$quickLinksText = 'Quick Links';
$ourFleetText = 'Our Fleet';
$locationsText2 = 'Locations';
$offersText = 'Offers';
$contactUsText = 'Contact Us';
$rightsReservedText = 'All rights reserved.';

// Testimonial texts
$suvTestimonialText = 'The SUV was perfect for our family vacation. Plenty of space and very comfortable!';
$economyTestimonialText = 'Great value for money! The car was clean, efficient, and perfect for getting around the city.';
$luxuryTestimonialText = 'The luxury sedan made our anniversary trip extra special. Smooth ride and excellent service!';
$electricTestimonialText = 'My first EV experience was fantastic! The car was quiet, smooth, and charging was convenient.';

// Spanish translations
if ($language == 'es') {
    $themeText = 'Tema';
    $lightText = 'Claro';
    $darkText = 'Oscuro';
    $fontSizeText = 'Tamaño de fuente';
    $resetText = 'Reiniciar';
    $languageText = 'Idioma';
    $heroTitle = 'Motiv, Alquiler de Autos';
    $heroText = '¡En Motiv, hacemos que el alquiler de autos sea agradable! Con opciones flexibles de recogida, una variedad de vehículos de calidad y reservas sin problemas, cada viaje se siente sin esfuerzo.';
    $reserveText = 'Reservar un Vehículo';
    $pickupLocationText = 'Lugar de recogida';
    $selectLocationText = 'Selecciona un lugar';
    $pickupDateTimeText = 'Fecha y hora de recogida';
    $dropoffDateTimeText = 'Fecha y hora de devolución';
    $showCarsText = 'Mostrar Autos Disponibles';
    $whyChooseText = '¿Por qué elegir Motiv?';
    $vehicleSelectionText = 'Amplia selección de vehículos';
    $vehicleDescText = 'Elija entre autos económicos, sedanes premium, SUV y vehículos eléctricos que se adapten a sus necesidades.';
    $locationsText = 'Ubicaciones convenientes';
    $locationsDescText = 'Múltiples ubicaciones de recogida y devolución en Birmingham para su conveniencia.';
    $priceGuaranteeText = 'Mejor precio garantizado';
    $priceDescText = 'Ofrecemos tarifas competitivas sin cargos ocultos y una garantía de mejor precio.';
    $supportText = 'Soporte 24/7';
    $supportDescText = 'Nuestro equipo de servicio al cliente está disponible las 24 horas para ayudarlo.';
    $topCitiesText = 'Principales ciudades para alquiler de autos';
    $bestSellingText = 'Servicios más vendidos';
    $viewAllText = 'Ver todos los listados';
    $popularText = 'Nuestras opciones de alquiler más populares con las mejores calificaciones de los clientes';
    $popularBadgeText = 'Más popular';
    $bestValueText = 'Mejor valor';
    $ecoFriendlyText = 'Ecológico';
    $suvText = 'SUV Premium';
    $economyText = 'Auto Económico';
    $luxuryText = 'Sedán de Lujo';
    $electricText = 'Vehículo Eléctrico';
    $suvDescText = 'SUVs espaciosos y cómodos perfectos para viajes familiares o en grupo.';
    $economyDescText = 'Autos eficientes en combustible y asequibles ideales para conducir en la ciudad y viajes cortos.';
    $luxuryDescText = 'Vehículos premium para viajes de negocios u ocasiones especiales con comodidad.';
    $electricDescText = 'Autos eléctricos ecológicos con características y operaciones modernas.';
    $reviewsText = 'reseñas';
    $seatsText = 'Asientos:';
    $luggageText = 'Equipaje:';
    $bagsText = 'maletas';
    $fuelTypeText = 'Tipo de combustible:';
    $footerTaglineText = 'Su socio de confianza para servicios de alquiler de autos en Birmingham y más allá.';
    $quickLinksText = 'Enlaces rápidos';
    $ourFleetText = 'Nuestra flota';
    $locationsText2 = 'Ubicaciones';
    $offersText = 'Ofertas';
    $contactUsText = 'Contáctenos';
    $rightsReservedText = 'Todos los derechos reservados.';
    $suvTestimonialText = 'El SUV fue perfecto para nuestras vacaciones familiares. ¡Mucho espacio y muy cómodo!';
    $economyTestimonialText = '¡Excelente relación calidad-precio! El auto estaba limpio, eficiente y perfecto para moverse por la ciudad.';
    $luxuryTestimonialText = 'El sedán de lujo hizo que nuestro viaje de aniversario fuera aún más especial. ¡Viaje suave y excelente servicio!';
    $electricTestimonialText = '¡Mi primera experiencia con un vehículo eléctrico fue fantástica! El auto era silencioso, suave y la carga era conveniente.';
} 
// French translations
elseif ($language == 'fr') {
    $themeText = 'Thème';
    $lightText = 'Clair';
    $darkText = 'Sombre';
    $fontSizeText = 'Taille de police';
    $resetText = 'Réinitialiser';
    $languageText = 'Langue';
    $heroTitle = 'Motiv, Location de Voitures';
    $heroText = 'Chez Motiv, nous rendons la location de voitures agréable ! Avec des options de prise en charge flexibles, une variété de véhicules de qualité et une réservation fluide, chaque voyage semble sans effort.';
    $reserveText = 'Réserver un Véhicule';
    $pickupLocationText = 'Lieu de prise en charge';
    $selectLocationText = 'Sélectionnez un lieu';
    $pickupDateTimeText = 'Date et heure de prise en charge';
    $dropoffDateTimeText = 'Date et heure de restitution';
    $showCarsText = 'Afficher les Voitures Disponibles';
    $whyChooseText = 'Pourquoi choisir Motiv?';
    $vehicleSelectionText = 'Large sélection de véhicules';
    $vehicleDescText = 'Choisissez parmi les voitures économiques, les berlines premium, les SUV et les véhicules électriques adaptés à vos besoins.';
    $locationsText = 'Emplacements pratiques';
    $locationsDescText = 'Plusieurs lieux de prise en charge et de restitution à Birmingham pour votre commodité.';
    $priceGuaranteeText = 'Meilleur prix garanti';
    $priceDescText = 'Nous offrons des tarifs compétitifs sans frais cachés et une garantie du meilleur prix.';
    $supportText = 'Assistance 24/7';
    $supportDescText = 'Notre équipe de service client est disponible 24h/24 pour vous aider.';
    $topCitiesText = 'Meilleures villes pour la location de voitures';
    $bestSellingText = 'Services les plus vendus';
    $viewAllText = 'Voir toutes les annonces';
    $popularText = 'Nos options de location les plus populaires avec les meilleures évaluations des clients';
    $popularBadgeText = 'Le plus populaire';
    $bestValueText = 'Meilleur rapport qualité-prix';
    $ecoFriendlyText = 'Écologique';
    $suvText = 'SUV Premium';
    $economyText = 'Voiture Économique';
    $luxuryText = 'Berline de Luxe';
    $electricText = 'Véhicule Électrique';
    $suvDescText = 'SUV spacieux et confortables parfaits pour les voyages en famille ou en groupe.';
    $economyDescText = 'Voitures économes en carburant et abordables idéales pour la conduite en ville et les courts trajets.';
    $luxuryDescText = 'Véhicules premium pour les voyages d\'affaires ou les occasions spéciales avec confort.';
    $electricDescText = 'Voitures électriques respectueuses de l\'environnement avec des fonctionnalités et des opérations modernes.';
    $reviewsText = 'avis';
    $seatsText = 'Sièges:';
    $luggageText = 'Bagages:';
    $bagsText = 'sacs';
    $fuelTypeText = 'Type de carburant:';
    $footerTaglineText = 'Votre partenaire de confiance pour les services de location de voitures à Birmingham et au-delà.';
    $quickLinksText = 'Liens rapides';
    $ourFleetText = 'Notre flotte';
    $locationsText2 = 'Emplacements';
    $offersText = 'Offres';
    $contactUsText = 'Contactez-nous';
    $rightsReservedText = 'Tous droits réservés.';
    $suvTestimonialText = 'Le SUV était parfait pour nos vacances en famille. Beaucoup d\'espace et très confortable !';
    $economyTestimonialText = 'Excellent rapport qualité-prix ! La voiture était propre, efficace et parfaite pour se déplacer en ville.';
    $luxuryTestimonialText = 'La berline de luxe a rendu notre voyage d\'anniversaire encore plus spécial. Conduite agréable et excellent service !';
    $electricTestimonialText = 'Ma première expérience avec un véhicule électrique a été fantastique ! La voiture était silencieuse, confortable et la recharge était pratique.';
} 
// German translations
elseif ($language == 'de') {
    $themeText = 'Design';
    $lightText = 'Hell';
    $darkText = 'Dunkel';
    $fontSizeText = 'Schriftgröße';
    $resetText = 'Zurücksetzen';
    $languageText = 'Sprache';
    $heroTitle = 'Motiv, Autovermietung';
    $heroText = 'Bei Motiv machen wir die Autovermietung angenehm! Mit flexiblen Abholmöglichkeiten, einer Vielzahl von Qualitätsfahrzeugen und reibungsloser Buchung fühlt sich jede Reise mühelos an.';
    $reserveText = 'Fahrzeug reservieren';
    $pickupLocationText = 'Abholort';
    $selectLocationText = 'Wählen Sie einen Ort';
    $pickupDateTimeText = 'Abholdatum und -zeit';
    $dropoffDateTimeText = 'Rückgabedatum und -zeit';
    $showCarsText = 'Verfügbare Autos anzeigen';
    $whyChooseText = 'Warum Motiv wählen?';
    $vehicleSelectionText = 'Große Fahrzeugauswahl';
    $vehicleDescText = 'Wählen Sie aus sparsamen Autos, Premium-Limousinen, SUVs und Elektrofahrzeugen, die Ihren Bedürfnissen entsprechen.';
    $locationsText = 'Praktische Standorte';
    $locationsDescText = 'Mehrere Abhol- und Rückgabeorte in Birmingham für Ihre Bequemlichkeit.';
    $priceGuaranteeText = 'Bester Preis garantiert';
    $priceDescText = 'Wir bieten wettbewerbsfähige Preise ohne versteckte Gebühren und eine Bestpreisgarantie.';
    $supportText = '24/7 Support';
    $supportDescText = 'Unser Kundenservice-Team steht Ihnen rund um die Uhr zur Verfügung.';
    $topCitiesText = 'Top-Städte für die Autovermietung';
    $bestSellingText = 'Bestseller-Dienstleistungen';
    $viewAllText = 'Alle Angebote anzeigen';
    $popularText = 'Unsere beliebtesten Mietoptionen mit den besten Kundenbewertungen';
    $popularBadgeText = 'Am beliebtesten';
    $bestValueText = 'Bestes Preis-Leistungs-Verhältnis';
    $ecoFriendlyText = 'Umweltfreundlich';
    $suvText = 'Premium SUV';
    $economyText = 'Sparauto';
    $luxuryText = 'Luxuslimousine';
    $electricText = 'Elektrofahrzeug';
    $suvDescText = 'Geräumige und komfortable SUVs, perfekt für Familienausflüge oder Gruppenreisen.';
    $economyDescText = 'Kraftstoffeffiziente und erschwingliche Autos, ideal für Stadtfahrten und kurze Reisen.';
    $luxuryDescText = 'Premium-Fahrzeuge für Geschäftsreisen oder besondere Anlässe mit Komfort.';
    $electricDescText = 'Umweltfreundliche Elektroautos mit modernen Funktionen und Betrieb.';
    $reviewsText = 'Bewertungen';
    $seatsText = 'Sitze:';
    $luggageText = 'Gepäck:';
    $bagsText = 'Taschen';
    $fuelTypeText = 'Kraftstofftyp:';
    $footerTaglineText = 'Ihr vertrauenswürdiger Partner für Autovermietungen in Birmingham und darüber hinaus.';
    $quickLinksText = 'Schnelllinks';
    $ourFleetText = 'Unsere Flotte';
    $locationsText2 = 'Standorte';
    $offersText = 'Angebote';
    $contactUsText = 'Kontaktieren Sie uns';
    $rightsReservedText = 'Alle Rechte vorbehalten.';
    $suvTestimonialText = 'Der SUV war perfekt für unseren Familienurlaub. Viel Platz und sehr komfortabel!';
    $economyTestimonialText = 'Großartiges Preis-Leistungs-Verhältnis! Das Auto war sauber, effizient und perfekt, um in der Stadt herumzukommen.';
    $luxuryTestimonialText = 'Die Luxuslimousine machte unsere Jubiläumsreise noch besonderer. Ruhige Fahrt und ausgezeichneter Service!';
    $electricTestimonialText = 'Meine erste Erfahrung mit einem Elektrofahrzeug war fantastisch! Das Auto war leise, ruhig und das Aufladen war bequem.';
}

// Include chatbot response function
function getBotResponse($message) {
    $msg = strtolower($message);
    
    // Define responses array
    $responses = [
        // Vehicles available
        'view available cars' => 'We have a wide selection of vehicles including Economy Cars, Premium SUVs, Luxury Sedans, and Electric Vehicles. Visit our <a href="cars.php">Cars page</a> to see all available listings!',
        'suv' => 'Our Premium SUVs are perfect for families or groups — seating 5-7 with space for 4-6 bags! Petrol or Diesel. Rated highly by many customers.',
        'luxury' => 'Our Luxury Sedans are ideal for business trips or special occasions — seating 4-5 with Petrol/Hybrid engines. Rated 4.8/5 by our buyers. Starting prices available on our <a href="cars.php">Cars page</a>.',
        'economy' => 'Our Economy Cars are fuel-efficient and budget-friendly — seating 4-5 with 2-3 bags of luggage. Rated 4.7/5 by our buyers. Great for city driving and short trips!',
        'electric' => 'We offer Electric Vehicles with modern features and zero emissions — seating 4-5, 2-3 bags. Rated 4.6/5 by our buyers. Eco-friendly and smooth to drive!',
        'sedan' => 'Our Luxury Sedans seat 4-5 passengers and are perfect for business or special occasions. Check our <a href="cars.php">Cars page</a> for current availability and pricing.',
        'van' => 'Please contact our team for van availability at <strong>info@motivcarrental.com</strong> or call <strong>0712345678</strong>.',

        // our pricing
        'rental rates' => 'Our rates vary by vehicle — visit our <a href="cars.php">Cars page</a> to see current pricing for all categories. We offer a <strong>Best Price Guarantee</strong> with no hidden fees!',
        'price' => 'We offer a Best Price Guarantee with no hidden fees! Check our <a href="cars.php">Cars page</a> for exact pricing on each vehicle.',
        'how much' => 'Pricing depends on the vehicle and rental period. Visit our <a href="cars.php">Cars page</a> for up-to-date rates, or contact us at <strong>info@motivcarrental.com</strong>.',

        // location
        'located' => 'Motiv serves multiple cities including <strong>Birmingham, London, Liverpool, Manchester, and Sheffield</strong>. Our main office is at <strong>New Street Station, Birmingham</strong>.',
        'location' => 'Motiv serves multiple cities including <strong>Birmingham, London, Liverpool, Manchester, and Sheffield</strong>. Our main office is at <strong>New Street Station, Birmingham</strong>.',
        'locations' => 'We have pick-up and drop-off locations across Birmingham and other major UK cities. Use the booking form on our <a href="landing.php">homepage</a> to select your preferred location.',
        'birmingham' => 'Birmingham is our home city! Our main location is at <strong>New Street Station, Birmingham</strong>. We offer multiple pick-up and drop-off points across the city.',
        'london' => 'We serve London! Use the booking form on our <a href="landing.php">homepage</a> to search for available vehicles in London.',
        'liverpool' => 'We serve Liverpool! Use the booking form on our <a href="landing.php">homepage</a> to search for available vehicles in Liverpool.',
        'manchester' => 'We serve Manchester! Use the booking form on our <a href="landing.php">homepage</a> to search for available vehicles in Manchester.',
        'sheffield' => 'We serve Sheffield! Use the booking form on our <a href="landing.php">homepage</a> to search for available vehicles in Sheffield.',

        // support 
        'contact support' => 'You can reach us at <strong>info@motivcarrental.com</strong> or call <strong>0712345678</strong>. Our customer service team is available <strong>24/7</strong>!',
        'contact' => 'Get in touch via our <a href="contact.php">Contact page</a>, email us at <strong>info@motivcarrental.com</strong>, or call <strong>07123456789</strong>.',
        'phone' => 'You can call us on <strong>0712345678</strong> — our team is available 24/7!',
        'email' => 'Send us an email at <strong>info@motivcarrental.com</strong> and we\'ll get back to you promptly!',
        'hours' => 'Our store is open <strong>Monday to Saturday, 9:00 AM - 5:00 PM</strong>, and all vehicles are available for collection during these hours. If you have any questions outside of opening hours, you can still reach us at <strong>info@motivcarrental.com</strong> or <strong>07123456789</strong>.',
        'support' => 'We offer <strong>24/7 customer support</strong>! Contact us at <strong>info@motivcarrental.com</strong> or <strong>07123456789</strong>.',

        // book
        'book' => 'Booking is easy! Use the reservation form on our <a href="landing.php">homepage</a> — just select your pick-up location, dates, and times to see available cars.',
        'reserve' => 'To reserve a vehicle, use our booking form on the <a href="landing.php">homepage</a>. Choose your location, pick-up date/time, and drop-off date/time.',
        'how to book' => 'Simply go to our <a href="landing.php">homepage</a>, fill in your pick-up location, dates, and times, then click "Show Available Cars" to browse and book!',

        // Insurance
        'insurance' => 'We offer insurance coverage to give you peace of mind on the road! Our options include:<br><br>• <strong>Collision Damage Waiver (CDW)</strong> — reduces your liability if the vehicle is damaged<br>• <strong>Theft Protection</strong> — covers you in the event of vehicle theft<br>• <strong>Personal Accident Insurance</strong> — covers medical expenses for you and your passengers<br>• <strong>Third Party Liability</strong> — included as standard with all rentals<br><br>For full details or to add coverage to your booking, contact us at <strong>info@motivcarrental.com</strong> or call <strong>0712345678</strong>.',

        // about
        'about' => 'Motiv is a Birmingham-based car rental company offering flexible pick-up options, a variety of quality vehicles, and smooth booking. We\'re your trusted partner for car hire across the UK!',
        'who are you' => 'I\'m the Motiv virtual assistant! Motiv is a car rental company based in Birmingham offering Premium SUVs, Economy Cars, Luxury Sedans, Electric Vehicles, and more. How can I help?',

        // hello stuff 
        'hello' => 'Hello! Welcome to Motiv Car Rental 🚗 How can I help you today?',
        'hi' => 'Hi there! Welcome to Motiv Car Rental 🚗 How can I help you today?',
        'hey' => 'Hey! Welcome to Motiv Car Rental 🚗 What can I do for you?',
        'thank' => 'You\'re welcome! Is there anything else I can help you with today? 😊',
        'thanks' => 'You\'re welcome! Is there anything else I can help you with today? 😊',
        'bye' => 'Thank you for chatting with Motiv! Have a great day and safe travels! 🚗',
        'goodbye' => 'Thank you for chatting with Motiv! Have a great day and safe travels! 🚗',

        'default' => 'Thanks for your question! For detailed information, please visit our <a href="contact.php">Contact page</a>, email us at <strong>info@motivcarrental.com</strong>, or call <strong>0712345678</strong>. Is there anything else I can help with?'
    ];

    // Check for exact matches in responses array
    foreach ($responses as $key => $response) {
        if (strpos($msg, $key) !== false) {
            return $response;
        }
    }

    // Greetings
    if (preg_match('/(hello|hi|hey|good morning|good afternoon)/', $msg)) {
        return 'Hello! Welcome to Motiv Car Rental 🚗 How can I help you today?';
    }
    // Booking
    if (preg_match('/(book|reserve|rent|hire)/', $msg)) {
        return $responses['book'];
    }
    // Pricing
    if (preg_match('/(price|cost|cheap|expensive|how much)/', $msg)) {
        return $responses['rental rates'];
    }
    // Vehicle types
    if (preg_match('/(4x4|off road|family car)/', $msg)) {
        return $responses['suv'];
    }
    if (preg_match('/(executive|premium|high end)/', $msg)) {
        return $responses['luxury'];
    }
    if (preg_match('/(small|compact|budget)/', $msg)) {
        return $responses['economy'];
    }
    if (preg_match('/(people carrier|moving|cargo)/', $msg)) {
        return $responses['van'];
    }
    // Electric
    if (preg_match('/(electric|ev|eco|green)/', $msg)) {
        return $responses['electric'];
    }
    // Cities
    if (strpos($msg, 'birmingham') !== false) { return $responses['birmingham']; }
    if (strpos($msg, 'london') !== false) { return $responses['london']; }
    if (strpos($msg, 'liverpool') !== false) { return $responses['liverpool']; }
    if (strpos($msg, 'manchester') !== false) { return $responses['manchester']; }
    if (strpos($msg, 'sheffield') !== false) { return $responses['sheffield']; }
    // Locations
    if (preg_match('/(where|location|located|branch|pick up|pickup)/', $msg)) {
        return $responses['locations'];
    }
    // Hours
    if (preg_match('/(open|hours|opening|when|collect|collection)/', $msg)) {
        return $responses['hours'];
    }
    // Insurance
    if (preg_match('/(insurance|insured|cover|coverage|damage|accident)/', $msg)) {
        return $responses['insurance'];
    }
    // Contact
    if (preg_match('/(contact|support|help|speak|call|email)/', $msg)) {
        return $responses['contact support'];
    }
    // Phone
    if (preg_match('/(phone|number|ring)/', $msg)) {
        return $responses['phone'];
    }
    // Age/License
    if (preg_match('/(age|old|young|license|licence)/', $msg)) {
        return 'For age requirements and licence queries, please contact us at <strong>info@motivcarrental.com</strong> or call <strong>0712345678</strong> and our team will be happy to help!';
    }
    // Payment
    if (preg_match('/(payment|pay|credit card|debit card|cash)/', $msg)) {
        return 'We accept all major credit and debit cards. For specific payment queries, please contact us at <strong>info@motivcarrental.com</strong> or call <strong>0712345678</strong>.';
    }
    // Fuel
    if (preg_match('/(fuel|petrol|diesel|gas)/', $msg)) {
        return 'Our vehicles are available in Petrol, Diesel, Petrol/Hybrid, and fully Electric. The fuel type is listed for each car on our <a href="cars.php">Cars page</a>.';
    }
    // Mileage
    if (preg_match('/(mileage|miles|distance|km)/', $msg)) {
        return 'For mileage terms and limits, please contact us at <strong>info@motivcarrental.com</strong> or call <strong>0712345678</strong> and our team will be happy to help!';
    }
    // Thank you
    if (preg_match('/(thanks|thank you|cheers)/', $msg)) {
        return $responses['thank'];
    }
    // Goodbye
    if (preg_match('/(bye|goodbye|see you|cya)/', $msg)) {
        return $responses['bye'];
    }

    return $responses['default'];
}

// Handle AJAX request for chatbot
if (isset($_POST['chat_message']) && !empty($_POST['chat_message'])) {
    $userMessage = $_POST['chat_message'];
    $botResponse = getBotResponse($userMessage);
    
    // Return JSON response for AJAX calls
    header('Content-Type: application/json');
    echo json_encode([
        'response' => $botResponse,
        'time' => date('H:i')
    ]);
    exit;
}
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
            --footer-text: #ffffff;
        }

        /* Dark theme heading styles */
        [data-theme="dark"] h1,
        [data-theme="dark"] h2,
        [data-theme="dark"] h3,
        [data-theme="dark"] h4,
        [data-theme="dark"] h5,
        [data-theme="dark"] h6,
        [data-theme="dark"] .section-title,
        [data-theme="dark"] .best-selling-section .section-title,
        [data-theme="dark"] .cities-section .section-title,
        [data-theme="dark"] .features .section-title,
        [data-theme="dark"] .feature-card h3,
        [data-theme="dark"] .service-content h3,
        [data-theme="dark"] .footer-column h3,
        [data-theme="dark"] .hero-text h1,
        [data-theme="dark"] .booking-form h2,
        [data-theme="dark"] .form-group label,
        [data-theme="dark"] .detail-value,
        [data-theme="dark"] .city-name,
        [data-theme="dark"] .testimonial-author {
            color: #ffffff !important;
        }

        [data-theme="dark"] .service-description,
        [data-theme="dark"] .rating-text,
        [data-theme="dark"] .testimonial p,
        [data-theme="dark"] .detail-label,
        [data-theme="dark"] .section-subtitle,
        [data-theme="dark"] .hero-text p {
            color: #cccccc;
        }

        [data-theme="dark"] .service-card,
        [data-theme="dark"] .feature-card,
        [data-theme="dark"] .city-card,
        [data-theme="dark"] .booking-form {
            background-color: #2d2d2d;
            color: #ffffff;
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

        /* Features Section Styles */
        .features {
            padding: 25px 0;
            background-color: #f5f5f5;
        }

        [data-theme="dark"] .features {
            background-color: #2d2d2d;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .feature-card {
            background: #fafafa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
        }

        [data-theme="dark"] .feature-card {
            background: #3d3d3d;
        }

        /* Cities Section Styles */
        .cities-section {
            background-color: #f5f5f5;
            padding: 70px 0;
            text-align: center;
            margin-top: -50px;
            margin-bottom: 10px;
        }

        [data-theme="dark"] .cities-section {
            background-color: #2d2d2d;
        }

        .city-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            width: 90%;
            max-width: 1100px;
            margin: 0 auto;
        }

        .city-card {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #fafafa;
        }

        [data-theme="dark"] .city-card {
            background: #3d3d3d;
        }

        .city-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .city-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            opacity: 0.8;
            transition: opacity 0.3s ease;
        }

        .city-card:hover img {
            opacity: 1;
        }

        .city-name {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 8px 0;
            font-size: 1.1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Chatbot Styles */
        .chat-toggle {
            position: fixed;
            bottom: 25px;
            right: 25px;
            width: 60px;
            height: 60px;
            background: var(--vivid-indigo);
            border-radius: 50%;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(140, 0, 80, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease, background-color 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .chat-toggle:hover {
            transform: scale(1.1);
            background: var(--dark-magenta);
        }

        .chat-toggle svg {
            width: 30px;
            height: 30px;
            fill: white;
        }

        .chat-container {
            position: fixed;
            bottom: 95px;
            right: 25px;
            width: 320px;
            height: 480px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 999;
        }

        [data-theme="dark"] .chat-container {
            background: #333333;
            color: white;
        }

        .chat-container.active {
            display: flex;
        }

        .chat-header {
            background: var(--vivid-indigo);
            color: white;
            padding: 15px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .chat-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .chat-avatar {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--vivid-indigo);
            font-size: 16px;
            overflow: hidden;
        }

        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 5px;
        }

        .chat-title h3 {
            font-size: 16px;
            margin-bottom: 2px;
            color: white;
        }

        .chat-status {
            font-size: 12px;
            opacity: 0.9;
            font-weight: 400;
            color: #50ff84;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 22px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
            background: #f5f5f5;
        }

        [data-theme="dark"] .chat-messages {
            background: #2d2d2d;
        }

        .message {
            margin-bottom: 12px;
            display: flex;
            gap: 8px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.bot {
            flex-direction: row;
        }

        .message.user {
            flex-direction: row-reverse;
        }

        .message-content {
            max-width: 80%;
            padding: 10px 14px;
            border-radius: 18px;
            line-height: 1.4;
            font-size: 13px;
        }

        .message-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: bold;
            flex-shrink: 0;
            overflow: hidden;
            align-self: flex-start;
        }

        .message-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: white;
            padding: 4px;
        }

        .message.user .message-avatar {
            background: var(--coral-red);
            color: white;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message-time {
            font-size: 10px;
            color: #999;
            margin-top: 3px;
            opacity: 0.7;
        }

        .message.bot .message-content {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
        }

        .message.user .message-content {
            background: var(--coral-red);
            color: white;
            border-top-right-radius: 4px;
        }

        .quick-replies {
            padding: 10px;
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            background: #f3f3f3;
            border-top: 1px solid #eee;
        }

        [data-theme="dark"] .quick-replies {
            background: #404040;
            border-color: #505050;
        }

        .quick-reply-btn {
            padding: 6px 12px;
            background: white;
            border: 1px solid var(--vivid-indigo);
            color: var(--vivid-indigo);
            border-radius: 20px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.3s ease;
        }

        .quick-reply-btn:hover {
            background: var(--vivid-indigo);
            color: white;
        }

        .chat-input-area {
            padding: 12px;
            background: white;
            border-top: 1px solid #eee;
        }

        [data-theme="dark"] .chat-input-area {
            background: #333;
            border-color: #404040;
        }

        .input-wrapper {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 10px 14px;
            border: 1px solid #ddd;
            border-radius: 25px;
            font-size: 13px;
            outline: none;
            transition: border-color 0.3s ease;
            background: white;
        }

        [data-theme="dark"] .chat-input {
            background: #404040;
            border-color: #505050;
            color: white;
        }

        .chat-input:focus {
            border-color: var(--vivid-indigo);
        }

        .send-btn {
            width: 40px;
            height: 40px;
            background: var(--vivid-indigo);
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease, background-color 0.3s ease;
        }

        .send-btn:hover {
            transform: scale(1.1);
            background: var(--dark-magenta);
        }

        .send-btn svg {
            width: 18px;
            height: 18px;
            fill: white;
        }

        .typing-indicator {
            display: none;
            padding: 10px 14px;
            background: white;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            max-width: 60px;
        }

        .typing-indicator.active {
            display: block;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            width: 6px;
            height: 6px;
            background: var(--vivid-indigo);
            border-radius: 50%;
            animation: typing 1.4s infinite;
        }

        .typing-dots span:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dots span:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typing {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.7;
            }
            30% {
                transform: translateY(-6px);
                opacity: 1;
            }
        }

        /* Best Selling Section Styles */
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
            
            .chat-container {
                width: 280px;
                height: 420px;
                bottom: 80px;
                right: 15px;
            }
            
            .chat-toggle {
                bottom: 20px;
                right: 20px;
                width: 55px;
                height: 55px;
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

        @media (max-width: 480px) {
            .chat-container {
                width: 100%;
                height: 100%;
                bottom: 0;
                right: 0;
                border-radius: 0;
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
                    <a href="landing.php" class="dropbtn">Home <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="landing.php">Home</a>
                        <a href="about.php">About</a>
                    </div>
                </li>
                <li><a href="cars.php">Cars</a></li>
                <li><a href="contact.php">Contact</a></li>

                <?php if (!isset($_SESSION['user'])): ?>
                    <li><a href="loginPage.php">Login</a></li>
                <?php else: ?>
                    <li><a href="customer-dashboard.php">Dashboard</a></li>
                    <li>
                        <a href="logout.php" style="color: #ff7f50;">
                            <i class="fas fa-sign-out-alt"></i> Logout
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

    <section class="hero">
        <div class="container hero-content">
            <div class="hero-text">
                <h1><?php echo $heroTitle; ?></h1>
                <p><?php echo $heroText; ?></p>
            </div>
            <div class="booking-form">
                <h2><?php echo $reserveText; ?></h2>
                <form id="bookingForm" method="POST">
                    <input type="hidden" name="search_cars" value="1">
                    
                    <div class="form-group">
                        <label for="pickup-location"><?php echo $pickupLocationText; ?></label>
                        <select id="pickup-location" name="pickup_location" required>
                            <option value=""><?php echo $selectLocationText; ?></option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo $city['city_id']; ?>">
                                    <?php echo htmlspecialchars($city['city_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><?php echo $pickupDateTimeText; ?></label>
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
                        <label><?php echo $dropoffDateTimeText; ?></label>
                        <div class="date-time-group">
                            <div>
                                <input type="date" id="dropoff-date" name="dropoff_date" required>
                            </div>
                            <div>
                                <input type="time" id="dropoff-time" name="dropoff_time" value="12:00" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="book-btn"><?php echo $showCarsText; ?></button>
                </form>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="container">
            <h2 class="section-title"><?php echo $whyChooseText; ?></h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="cars1.png" alt="Vehicle Selection">
                    </div>
                    <h3><?php echo $vehicleSelectionText; ?></h3>
                    <p><?php echo $vehicleDescText; ?></p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="location1.png" alt="Convenient Locations">
                    </div>
                    <h3><?php echo $locationsText; ?></h3>
                    <p><?php echo $locationsDescText; ?></p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="money1.png" alt="Best Price Guarantee">
                    </div>
                    <h3><?php echo $priceGuaranteeText; ?></h3>
                    <p><?php echo $priceDescText; ?></p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <img src="cust1.png" alt="Customer Support">
                    </div>
                    <h3><?php echo $supportText; ?></h3>
                    <p><?php echo $supportDescText; ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="cities-section">
        <h2 class="section-title"><?php echo $topCitiesText; ?></h2>

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
                <h2 class="section-title"><?php echo $bestSellingText; ?></h2>
                <a href="cars.php" class="view-all-btn"><?php echo $viewAllText; ?></a>
            </div>
            <p class="section-subtitle"><?php echo $popularText; ?></p>
            
            <div class="services-scroll-container">
                <div class="services-scroll">
                    <div class="service-card">
                        <div class="service-image">
                            <img src="car_pics/car1.png" alt="Premium SUV">
                            <div class="service-badge"><?php echo $popularBadgeText; ?></div>
                        </div>
                        <div class="service-content">
                            <h3><?php echo $suvText; ?></h3>
                            <div class="service-rating">
                                <span class="stars">★★★★★</span>
                                <span class="rating-text">5/5 (128 <?php echo $reviewsText; ?>)</span>
                            </div>
                            <p class="service-description"><?php echo $suvDescText; ?></p>
                            <div class="service-details">
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $seatsText; ?></span>
                                    <span class="detail-value">5-7</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $luggageText; ?></span>
                                    <span class="detail-value">4-6 <?php echo $bagsText; ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label"><?php echo $fuelTypeText; ?></span>
                                    <span class="detail-value">Petrol/Diesel</span>
                                </div>
                            </div>
                            <div class="testimonial">
                                <p>"<?php echo $suvTestimonialText; ?>"</p>
                                <div class="testimonial-author">- Zahra A.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-image">
                            <img src="car_pics/car2.png" alt="Economy Car">
                            <div class="service-badge"><?php echo $bestValueText; ?></div>
                        </div>
                        <div class="service-content">
                            <h3><?php echo $economyText; ?></h3>
                            <div class="service-rating">
                                <span class="stars">★★★★☆</span>
                                <span class="rating-text">4.7/5 (95 <?php echo $reviewsText; ?>)</span>
                            </div>
                            <p class="service-description"><?php echo $economyDescText; ?></p>
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
                                <p>"<?php echo $economyTestimonialText; ?>"</p>
                                <div class="testimonial-author">- Olivia E.S.</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-image">
                            <img src="car_pics/car3.jpg" alt="Luxury Sedan">
                        </div>
                        <div class="service-content">
                            <h3><?php echo $luxuryText; ?></h3>
                            <div class="service-rating">
                                <span class="stars">★★★★☆</span>
                                <span class="rating-text">4.8/5 (67 <?php echo $reviewsText; ?>)</span>
                            </div>
                            <p class="service-description"><?php echo $luxuryDescText; ?></p>
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
                                <p>"<?php echo $luxuryTestimonialText; ?>"</p>
                                <div class="testimonial-author">- Will</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-image">
                            <img src="car_pics/car4.png" alt="Electric">
                            <div class="service-badge"><?php echo $ecoFriendlyText; ?></div>
                        </div>
                        <div class="service-content">
                            <h3><?php echo $electricText; ?></h3>
                            <div class="service-rating">
                                <span class="stars">★★★★☆</span>
                                <span class="rating-text">4.6/5 (52 <?php echo $reviewsText; ?>)</span>
                            </div>
                            <p class="service-description"><?php echo $electricDescText; ?></p>
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
                                <p>"<?php echo $electricTestimonialText; ?>"</p>
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
                    <p><?php echo $footerTaglineText; ?></p>
                </div>
                <div class="footer-column">
                    <h3><?php echo $quickLinksText; ?></h3>
                    <ul>
                        <li><a href="landing.php">Home</a></li>
                        <li><a href="cars.php"><?php echo $ourFleetText; ?></a></li>
                        <li><a href="contact.php"><?php echo $locationsText2; ?></a></li>
                        <li><a href="#"><?php echo $offersText; ?></a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3><?php echo $contactUsText; ?></h3>
                    <ul>
                        <li>New Street Station, Birmingham</li>
                        <li>0712345678</li>
                        <li>info@motivcarrental.com</li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2025 Motiv Car Rental. <?php echo $rightsReservedText; ?></p>
            </div>
        </div>
    </footer>

<!-- Chatbot HTML -->
<button class="chat-toggle" id="chatToggle">
    <svg viewBox="0 0 24 24">
        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z" />
    </svg>
</button>

<div class="chat-container" id="chatContainer">
    <div class="chat-header">
        <div class="chat-header-info">
            <div class="chat-avatar">
                <img src="logo.png" alt="Logo">
            </div>
            <div class="chat-title">
                <h3>Motiv Assistant</h3>
                <div class="chat-status">Online</div>
            </div>
        </div>
        <button class="close-btn" id="closeChat">&times;</button>
    </div>

    <div class="chat-messages" id="chatMessages">
        <div class="message bot">
            <div class="message-avatar">
                <img src="logo.png" alt="Logo">
            </div>
            <div class="message-content">
                Welcome to Motiv! How can I help you today? 🚗🚗🚗
                <div class="message-time"><?php echo date('H:i'); ?></div>
            </div>
        </div>
    </div>

    <div class="quick-replies" id="quickReplies">
        <button class="quick-reply-btn" data-reply="View available cars">View Cars</button>
        <button class="quick-reply-btn" data-reply="What are your rental rates?">Rental Rates</button>
        <button class="quick-reply-btn" data-reply="Where are you located?">Locations</button>
        <button class="quick-reply-btn" data-reply="Contact support">Contact Us</button>
        <button class="quick-reply-btn" data-reply="Hours">Our Hours</button>
        <button class="quick-reply-btn" data-reply="Insurance">Insurance</button>
    </div>

    <div class="chat-input-area">
        <div class="input-wrapper">
            <input type="text" class="chat-input" id="chatInput" placeholder="Type your message...">
            <button class="send-btn" id="sendBtn">
                <svg viewBox="0 0 24 24">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z" />
                </svg>
            </button>
        </div>
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

        // Chatbot functionality
        const chatToggle = document.getElementById('chatToggle');
        const chatContainer = document.getElementById('chatContainer');
        const closeChat = document.getElementById('closeChat');
        const chatMessages = document.getElementById('chatMessages');
        const chatInput = document.getElementById('chatInput');
        const sendBtn = document.getElementById('sendBtn');
        const quickReplies = document.querySelectorAll('.quick-reply-btn');

        function getCurrentTime() {
            const now = new Date();
            return now.getHours().toString().padStart(2, '0') + ':' + 
                   now.getMinutes().toString().padStart(2, '0');
        }

        function openChat() {
            chatContainer.classList.add('active');
            chatInput.focus();
        }

        function closeChatFunc() {
            chatContainer.classList.remove('active');
        }

        if (chatToggle) chatToggle.addEventListener('click', openChat);
        if (closeChat) closeChat.addEventListener('click', closeChatFunc);

        function addMessage(text, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'bot'}`;

            const avatar = document.createElement('div');
            avatar.className = 'message-avatar';

            if (!isUser) {
                const img = document.createElement('img');
                img.src = 'logo.png';
                img.alt = 'Logo';
                avatar.appendChild(img);
            } else {
                avatar.innerHTML = '<i class="fas fa-user"></i>';
            }

            const content = document.createElement('div');
            content.className = 'message-content';
            content.innerHTML = `${text}<div class="message-time">${getCurrentTime()}</div>`;

            messageDiv.appendChild(avatar);
            messageDiv.appendChild(content);
            chatMessages.appendChild(messageDiv);

            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showTypingIndicator() {
            const indicator = document.createElement('div');
            indicator.className = 'message bot';
            indicator.id = 'typingIndicator';

            const avatar = document.createElement('div');
            avatar.className = 'message-avatar';
            const img = document.createElement('img');
            img.src = 'logo.png';
            img.alt = 'Logo';
            avatar.appendChild(img);

            const typing = document.createElement('div');
            typing.className = 'typing-indicator active';
            typing.innerHTML = '<div class="typing-dots"><span></span><span></span><span></span></div>';

            indicator.appendChild(avatar);
            indicator.appendChild(typing);
            chatMessages.appendChild(indicator);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function removeTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            if (indicator) {
                indicator.remove();
            }
        }

        function sendMessage() {
            const message = chatInput.value.trim();

            if (message === '') return;

            addMessage(message, true);
            chatInput.value = '';

            showTypingIndicator();

            // Use fetch API to get response from PHP
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'chat_message=' + encodeURIComponent(message)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                removeTypingIndicator();
                if (data && data.response) {
                    addMessage(data.response);
                } else {
                    addMessage('Sorry, I received an invalid response. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                removeTypingIndicator();
                // Fallback response
                addMessage('Thanks for your question! For detailed information, please visit our <a href="contact.php">Contact page</a>, email us at <strong>info@motivcarrental.com</strong>, or call <strong>0712345678</strong>.');
            });
        }

        if (sendBtn) sendBtn.addEventListener('click', sendMessage);

        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
        }

        quickReplies.forEach(btn => {
            btn.addEventListener('click', () => {
                const reply = btn.getAttribute('data-reply');
                chatInput.value = reply;
                sendMessage();
            });
        });
    });
</script>
</body>
</html>
<?php
$conn->close();
?>
