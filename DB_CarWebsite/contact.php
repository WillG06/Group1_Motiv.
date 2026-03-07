<?php
session_start();
require_once 'db.php';

$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] : 'light';
$fontSize = isset($_COOKIE['fontSize']) ? $_COOKIE['fontSize'] : '100';
$language = isset($_COOKIE['language']) ? $_COOKIE['language'] : 'en';

$form_message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $form_message = 'Please fill in all required fields.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        if ($conn) {
            $stmt = $conn->prepare("
                INSERT INTO contact_inquiries 
                (name, email, phone, subject, message, status, created_at) 
                VALUES (?, ?, ?, ?, ?, 'new', NOW())
            ");
            $stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);
            
            if ($stmt->execute()) {
                $form_message = 'Thank you for your message! We will get back to you within 24 hours.';
                $message_type = 'success';
                
                $_POST = array();
            } else {
                $form_message = 'Sorry, there was an error sending your message. Please try again.';
                $message_type = 'error';
            }
            $stmt->close();
        } else {
            $form_message = 'Thank you for your message! We will get back to you within 24 hours.';
            $message_type = 'success';
        }
    }
}

$basketCount = 0;
if (isset($_SESSION['user'])) {
    $userId = $_SESSION['user']['customer_id'] ?? $_SESSION['user']['id'] ?? null;
    $userRole = $_SESSION['user']['role'] ?? 'customer';
    
    if ($userRole === 'customer' && $userId) {
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

// Language variables
$themeText = 'Theme';
$lightText = 'Light';
$darkText = 'Dark';
$fontSizeText = 'Font Size';
$resetText = 'Reset';
$languageText = 'Language';

// Contact page translations
if ($language == 'en') {
    $contactTitle = 'Get In Touch';
    $contactSubtitle = 'Have questions about our car rental services? We\'re here to help. Reach out to our friendly team.';
    $contactInfo = 'Contact Information';
    $ourLocation = 'Our Location';
    $phoneNumber = 'Phone Number';
    $emailAddress = 'Email Address';
    $businessHours = 'Business Hours';
    $mondayFriday = 'Monday - Friday';
    $saturday = 'Saturday';
    $sunday = 'Sunday';
    $emergencySupport = 'Emergency Support';
    $available24_7 = '24/7 Available';
    $sendMessage = 'Send Us a Message';
    $fullName = 'Full Name *';
    $emailAddressLabel = 'Email Address *';
    $phoneNumberLabel = 'Phone Number';
    $subjectLabel = 'Subject *';
    $selectSubject = 'Select a subject';
    $generalInquiry = 'General Inquiry';
    $bookingAssistance = 'Booking Assistance';
    $technicalSupport = 'Technical Support';
    $complaint = 'Complaint';
    $feedback = 'Feedback';
    $other = 'Other';
    $yourMessage = 'Your Message *';
    $sendMessageBtn = 'Send Message';
    $faqTitle = 'Frequently Asked Questions';
    $faq1Q = 'What documents do I need to rent a car?';
    $faq1A = 'You\'ll need a valid driver\'s license, a credit card in your name, and proof of identity (passport or ID card). For international renters, an International Driving Permit may be required.';
    $faq2Q = 'What is your cancellation policy?';
    $faq2A = 'You can cancel your booking free of charge up to 24 hours before your scheduled pickup time. Cancellations made within 24 hours may incur a fee.';
    $faq3Q = 'Do you offer one-way rentals?';
    $faq3A = 'Yes, we offer one-way rentals between most of our locations. Additional fees may apply depending on the drop-off location.';
    $faq4Q = 'What happens if I return the car late?';
    $faq4A = 'We provide a 59-minute grace period. Returns after this period will incur additional daily charges. Please contact us if you anticipate being late.';
    $faq5Q = 'Do you offer additional insurance?';
    $faq5A = 'Yes, we offer various insurance options including Collision Damage Waiver, Theft Protection, and Personal Accident Insurance for added peace of mind.';
    $faq6Q = 'Can I modify my booking after it\'s confirmed?';
    $faq6A = 'Yes, you can modify your booking online or by contacting our customer service team. Changes are subject to vehicle availability and rate differences.';
    $findUs = 'Find Us';
    $footerTagline = 'Your trusted partner for car rental services in Birmingham and beyond.';
    $quickLinks = 'Quick Links';
    $home = 'Home';
    $about = 'About';
    $cars = 'Cars';
    $contact = 'Contact';
    $offers = 'Offers';
    $contactUs = 'Contact Us';
    $rightsReserved = 'All rights reserved.';
    $dashboard = 'Dashboard';
    $login = 'Login';
    $logout = 'Logout';
} elseif ($language == 'es') {
    $themeText = 'Tema';
    $lightText = 'Claro';
    $darkText = 'Oscuro';
    $fontSizeText = 'Tamaño de fuente';
    $resetText = 'Reiniciar';
    $languageText = 'Idioma';
    $contactTitle = 'Ponte en Contacto';
    $contactSubtitle = '¿Tienes preguntas sobre nuestros servicios de alquiler de autos? Estamos aquí para ayudar. Comunícate con nuestro amable equipo.';
    $contactInfo = 'Información de Contacto';
    $ourLocation = 'Nuestra Ubicación';
    $phoneNumber = 'Número de Teléfono';
    $emailAddress = 'Correo Electrónico';
    $businessHours = 'Horario Comercial';
    $mondayFriday = 'Lunes - Viernes';
    $saturday = 'Sábado';
    $sunday = 'Domingo';
    $emergencySupport = 'Soporte de Emergencia';
    $available24_7 = 'Disponible 24/7';
    $sendMessage = 'Envíanos un Mensaje';
    $fullName = 'Nombre Completo *';
    $emailAddressLabel = 'Correo Electrónico *';
    $phoneNumberLabel = 'Número de Teléfono';
    $subjectLabel = 'Asunto *';
    $selectSubject = 'Selecciona un asunto';
    $generalInquiry = 'Consulta General';
    $bookingAssistance = 'Asistencia con Reservas';
    $technicalSupport = 'Soporte Técnico';
    $complaint = 'Queja';
    $feedback = 'Comentarios';
    $other = 'Otro';
    $yourMessage = 'Tu Mensaje *';
    $sendMessageBtn = 'Enviar Mensaje';
    $faqTitle = 'Preguntas Frecuentes';
    $faq1Q = '¿Qué documentos necesito para alquilar un auto?';
    $faq1A = 'Necesitarás una licencia de conducir válida, una tarjeta de crédito a tu nombre y una identificación (pasaporte o tarjeta de identificación). Para conductores internacionales, puede requerirse un Permiso Internacional de Conducir.';
    $faq2Q = '¿Cuál es su política de cancelación?';
    $faq2A = 'Puedes cancelar tu reserva sin cargo hasta 24 horas antes de la hora de recogida programada. Las cancelaciones realizadas dentro de las 24 horas pueden incurrir en una tarifa.';
    $faq3Q = '¿Ofrecen alquileres de ida?';
    $faq3A = 'Sí, ofrecemos alquileres de ida entre la mayoría de nuestras ubicaciones. Pueden aplicarse tarifas adicionales según el lugar de devolución.';
    $faq4Q = '¿Qué pasa si devuelvo el auto tarde?';
    $faq4A = 'Ofrecemos un período de gracia de 59 minutos. Las devoluciones después de este período incurrirán en cargos diarios adicionales. Por favor, contáctanos si anticipas que llegarás tarde.';
    $faq5Q = '¿Ofrecen seguro adicional?';
    $faq5A = 'Sí, ofrecemos varias opciones de seguro que incluyen Exención de Daños por Colisión, Protección contra Robo y Seguro de Accidentes Personales para mayor tranquilidad.';
    $faq6Q = '¿Puedo modificar mi reserva después de confirmada?';
    $faq6A = 'Sí, puedes modificar tu reserva en línea o contactando a nuestro equipo de servicio al cliente. Los cambios están sujetos a disponibilidad del vehículo y diferencias de tarifas.';
    $findUs = 'Encuéntranos';
    $footerTagline = 'Su socio de confianza para servicios de alquiler de autos en Birmingham y más allá.';
    $quickLinks = 'Enlaces rápidos';
    $home = 'Inicio';
    $about = 'Acerca de';
    $cars = 'Autos';
    $contact = 'Contacto';
    $offers = 'Ofertas';
    $contactUs = 'Contáctenos';
    $rightsReserved = 'Todos los derechos reservados.';
    $dashboard = 'Panel';
    $login = 'Iniciar sesión';
    $logout = 'Cerrar sesión';
} elseif ($language == 'fr') {
    $themeText = 'Thème';
    $lightText = 'Clair';
    $darkText = 'Sombre';
    $fontSizeText = 'Taille de police';
    $resetText = 'Réinitialiser';
    $languageText = 'Langue';
    $contactTitle = 'Entrez en Contact';
    $contactSubtitle = 'Des questions sur nos services de location de voitures ? Nous sommes là pour vous aider. Contactez notre équipe amicale.';
    $contactInfo = 'Informations de Contact';
    $ourLocation = 'Notre Emplacement';
    $phoneNumber = 'Numéro de Téléphone';
    $emailAddress = 'Adresse E-mail';
    $businessHours = 'Heures d\'Ouverture';
    $mondayFriday = 'Lundi - Vendredi';
    $saturday = 'Samedi';
    $sunday = 'Dimanche';
    $emergencySupport = 'Support d\'Urgence';
    $available24_7 = 'Disponible 24/7';
    $sendMessage = 'Envoyez-nous un Message';
    $fullName = 'Nom Complet *';
    $emailAddressLabel = 'Adresse E-mail *';
    $phoneNumberLabel = 'Numéro de Téléphone';
    $subjectLabel = 'Sujet *';
    $selectSubject = 'Sélectionnez un sujet';
    $generalInquiry = 'Demande Générale';
    $bookingAssistance = 'Aide à la Réservation';
    $technicalSupport = 'Support Technique';
    $complaint = 'Réclamation';
    $feedback = 'Commentaires';
    $other = 'Autre';
    $yourMessage = 'Votre Message *';
    $sendMessageBtn = 'Envoyer le Message';
    $faqTitle = 'Questions Fréquemment Posées';
    $faq1Q = 'Quels documents ai-je besoin pour louer une voiture ?';
    $faq1A = 'Vous aurez besoin d\'un permis de conduire valide, d\'une carte de crédit à votre nom et d\'une pièce d\'identité (passeport ou carte d\'identité). Pour les conducteurs internationaux, un permis de conduire international peut être requis.';
    $faq2Q = 'Quelle est votre politique d\'annulation ?';
    $faq2A = 'Vous pouvez annuler votre réservation gratuitement jusqu\'à 24 heures avant votre heure de prise en charge prévue. Les annulations effectuées dans les 24 heures peuvent entraîner des frais.';
    $faq3Q = 'Proposez-vous des locations aller simple ?';
    $faq3A = 'Oui, nous proposons des locations aller simple entre la plupart de nos emplacements. Des frais supplémentaires peuvent s\'appliquer selon le lieu de restitution.';
    $faq4Q = 'Que se passe-t-il si je rends la voiture en retard ?';
    $faq4A = 'Nous accordons un délai de grâce de 59 minutes. Les retours après cette période entraîneront des frais quotidiens supplémentaires. Veuillez nous contacter si vous prévoyez d\'être en retard.';
    $faq5Q = 'Proposez-vous une assurance supplémentaire ?';
    $faq5A = 'Oui, nous proposons diverses options d\'assurance, y compris l\'Exonération en Cas de Dommages, la Protection Contre le Vol et l\'Assurance Accident Personnel pour une tranquillité d\'esprit supplémentaire.';
    $faq6Q = 'Puis-je modifier ma réservation après confirmation ?';
    $faq6A = 'Oui, vous pouvez modifier votre réservation en ligne ou en contactant notre équipe de service client. Les modifications sont sujettes à la disponibilité du véhicule et aux différences de tarifs.';
    $findUs = 'Trouvez-nous';
    $footerTagline = 'Votre partenaire de confiance pour les services de location de voitures à Birmingham et au-delà.';
    $quickLinks = 'Liens rapides';
    $home = 'Accueil';
    $about = 'À propos';
    $cars = 'Voitures';
    $contact = 'Contact';
    $offers = 'Offres';
    $contactUs = 'Contactez-nous';
    $rightsReserved = 'Tous droits réservés.';
    $dashboard = 'Tableau de bord';
    $login = 'Connexion';
    $logout = 'Déconnexion';
} elseif ($language == 'de') {
    $themeText = 'Design';
    $lightText = 'Hell';
    $darkText = 'Dunkel';
    $fontSizeText = 'Schriftgröße';
    $resetText = 'Zurücksetzen';
    $languageText = 'Sprache';
    $contactTitle = 'Kontaktieren Sie Uns';
    $contactSubtitle = 'Haben Sie Fragen zu unseren Autovermietungsdiensten? Wir sind hier, um zu helfen. Wenden Sie sich an unser freundliches Team.';
    $contactInfo = 'Kontaktinformationen';
    $ourLocation = 'Unser Standort';
    $phoneNumber = 'Telefonnummer';
    $emailAddress = 'E-Mail-Adresse';
    $businessHours = 'Geschäftszeiten';
    $mondayFriday = 'Montag - Freitag';
    $saturday = 'Samstag';
    $sunday = 'Sonntag';
    $emergencySupport = 'Notfall-Support';
    $available24_7 = '24/7 Verfügbar';
    $sendMessage = 'Senden Sie Uns Eine Nachricht';
    $fullName = 'Vollständiger Name *';
    $emailAddressLabel = 'E-Mail-Adresse *';
    $phoneNumberLabel = 'Telefonnummer';
    $subjectLabel = 'Betreff *';
    $selectSubject = 'Wählen Sie einen Betreff';
    $generalInquiry = 'Allgemeine Anfrage';
    $bookingAssistance = 'Buchungshilfe';
    $technicalSupport = 'Technischer Support';
    $complaint = 'Beschwerde';
    $feedback = 'Feedback';
    $other = 'Sonstiges';
    $yourMessage = 'Ihre Nachricht *';
    $sendMessageBtn = 'Nachricht Senden';
    $faqTitle = 'Häufig Gestellte Fragen';
    $faq1Q = 'Welche Dokumente benötige ich, um ein Auto zu mieten?';
    $faq1A = 'Sie benötigen einen gültigen Führerschein, eine Kreditkarte auf Ihren Namen und einen Identitätsnachweis (Reisepass oder Personalausweis). Für internationale Mieter kann ein internationaler Führerschein erforderlich sein.';
    $faq2Q = 'Wie ist Ihre Stornierungsrichtlinie?';
    $faq2A = 'Sie können Ihre Buchung bis zu 24 Stunden vor Ihrer geplanten Abholzeit kostenlos stornieren. Bei Stornierungen innerhalb von 24 Stunden können Gebühren anfallen.';
    $faq3Q = 'Bieten Sie Einwegmieten an?';
    $faq3A = 'Ja, wir bieten Einwegmieten zwischen den meisten unserer Standorte an. Je nach Rückgabeort können zusätzliche Gebühren anfallen.';
    $faq4Q = 'Was passiert, wenn ich das Auto zu spät zurückgebe?';
    $faq4A = 'Wir gewähren eine Gnadenfrist von 59 Minuten. Rückgaben nach dieser Frist führen zu zusätzlichen Tagesgebühren. Bitte kontaktieren Sie uns, wenn Sie eine Verspätung erwarten.';
    $faq5Q = 'Bieten Sie zusätzliche Versicherungen an?';
    $faq5A = 'Ja, wir bieten verschiedene Versicherungsoptionen an, darunter Haftungsbefreiung bei Kollision, Diebstahlschutz und Unfallversicherung für zusätzliche Sicherheit.';
    $faq6Q = 'Kann ich meine Buchung nach der Bestätigung ändern?';
    $faq6A = 'Ja, Sie können Ihre Buchung online oder durch Kontaktaufnahme mit unserem Kundenservice-Team ändern. Änderungen unterliegen der Fahrzeugverfügbarkeit und Tarifunterschieden.';
    $findUs = 'Finden Sie Uns';
    $footerTagline = 'Ihr vertrauenswürdiger Partner für Autovermietungen in Birmingham und darüber hinaus.';
    $quickLinks = 'Schnelllinks';
    $home = 'Startseite';
    $about = 'Über uns';
    $cars = 'Autos';
    $contact = 'Kontakt';
    $offers = 'Angebote';
    $contactUs = 'Kontaktieren Sie uns';
    $rightsReserved = 'Alle Rechte vorbehalten.';
    $dashboard = 'Dashboard';
    $login = 'Anmelden';
    $logout = 'Abmelden';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f5f5f5;
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
        [data-theme="dark"] .contact-title,
        [data-theme="dark"] .faq-title,
        [data-theme="dark"] .map-title,
        [data-theme="dark"] .contact-info h2,
        [data-theme="dark"] .contact-form-container h2,
        [data-theme="dark"] .contact-text h4,
        [data-theme="dark"] .business-hours h3,
        [data-theme="dark"] .form-group label,
        [data-theme="dark"] .day,
        [data-theme="dark"] .faq-item h3,
        [data-theme="dark"] .footer-column h3 {
            color: #ffffff !important;
        }

        [data-theme="dark"] .contact-subtitle,
        [data-theme="dark"] .contact-text p,
        [data-theme="dark"] .time,
        [data-theme="dark"] .faq-item p,
        [data-theme="dark"] .footer-column p,
        [data-theme="dark"] .footer-column ul li {
            color: #cccccc;
        }

        [data-theme="dark"] .contact-info,
        [data-theme="dark"] .contact-form-container,
        [data-theme="dark"] .faq-item {
            background-color: #2d2d2d;
            border-color: #404040;
        }

        [data-theme="dark"] .contact-container {
            background-color: #1a1a1a;
        }

        [data-theme="dark"] .form-group input,
        [data-theme="dark"] .form-group textarea,
        [data-theme="dark"] .form-group select {
            background-color: #404040;
            border-color: #505050;
            color: #ffffff;
        }

        [data-theme="dark"] .form-group input:focus,
        [data-theme="dark"] .form-group textarea:focus,
        [data-theme="dark"] .form-group select:focus {
            border-color: var(--cobalt-blue);
        }

        [data-theme="dark"] .hours-list li {
            border-bottom-color: #404040;
        }

        body {
            background-color: var(--bg-primary);
            color: var(--text-primary);
            font-size: <?php echo $fontSize; ?>%;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        header, footer {
            background-color: var(--card-bg);
            color: var(--text-primary);
        }

        .contact-container {
            padding: 60px 0;
            background-color: var(--bg-secondary);
            min-height: calc(100vh - 80px);
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .contact-title {
            color: var(--vivid-indigo);
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .contact-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .contact-info {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 5px 15px var(--shadow-color);
            height: fit-content;
        }
        
        .contact-info h2 {
            color: var(--vivid-indigo);
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        .contact-details {
            margin-bottom: 30px;
        }
        
        .contact-detail {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .contact-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(to right, var(--cobalt-blue), var(--vivid-indigo));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .contact-icon i {
            color: white;
            font-size: 1.2rem;
        }
        
        .contact-text h4 {
            color: var(--vivid-indigo);
            margin-bottom: 5px;
            font-size: 1.1rem;
        }
        
        .contact-text p {
            color: var(--text-secondary);
            line-height: 1.5;
        }
        
        .business-hours {
            margin-top: 30px;
        }
        
        .business-hours h3 {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
            font-size: 1.3rem;
        }
        
        .hours-list {
            list-style: none;
        }
        
        .hours-list li {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .hours-list li:last-child {
            border-bottom: none;
        }
        
        .day {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .time {
            color: var(--text-secondary);
        }
        
        .contact-form-container {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 5px 15px var(--shadow-color);
        }
        
        .contact-form-container h2 {
            color: var(--vivid-indigo);
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--vivid-indigo);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--card-bg);
            color: var(--text-primary);
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--cobalt-blue);
            outline: none;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .submit-btn {
            background: linear-gradient(to right, var(--cobalt-blue), var(--vivid-indigo));
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0, 74, 173, 0.4);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 74, 173, 0.5);
        }
        
        .submit-btn:disabled {
            background: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .form-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            <?php if (!empty($form_message)): ?>
            display: block;
            <?php else: ?>
            display: none;
            <?php endif; ?>
        }
        
        .form-message.success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }
        
        .form-message.error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }
        
        .faq-section {
            margin-top: 80px;
            padding: 0 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .faq-title {
            text-align: center;
            color: var(--vivid-indigo);
            margin-bottom: 40px;
            font-size: 2.2rem;
        }
        
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .faq-item {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 3px 10px var(--shadow-color);
        }
        
        .faq-item h3 {
            color: var(--vivid-indigo);
            margin-bottom: 15px;
            font-size: 1.2rem;
        }
        
        .faq-item p {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        .map-section {
            margin-top: 20px;
            margin-bottom: 60px;
            padding: 0 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .map-title {
            text-align: center;
            color: var(--vivid-indigo);
            margin-bottom: 40px;
            font-size: 2.2rem;
        }
        
        .map-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px var(--shadow-color);
            height: 400px;
            background: #e9e9e9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 1.1rem;
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

        footer {
            background-color: var(--footer-bg);
            color: var(--footer-text);
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
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .contact-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .contact-info, .contact-form-container {
                padding: 30px;
            }
        }
        
        @media (max-width: 768px) {
            .contact-container {
                padding: 40px 0;
            }
            
            .contact-title {
                font-size: 2rem;
            }
            
            .contact-info, .contact-form-container {
                padding: 25px;
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-detail {
                flex-direction: column;
                text-align: center;
                gap: 10px;
            }
            
            .contact-icon {
                align-self: center;
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
                            <a href="about.php"><?php echo $about; ?></a>
                        </div>
                    </li>
                    <li><a href="cars.php"><?php echo $cars; ?></a></li>
                    <li><a href="contact.php" class="active"><?php echo $contact; ?></a></li>

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

    <section class="contact-container">
        <div class="contact-header">
            <h1 class="contact-title"><?php echo $contactTitle; ?></h1>
            <p class="contact-subtitle"><?php echo $contactSubtitle; ?></p>
        </div>
        
        <div class="contact-content">
            <div class="contact-info">
                <h2><?php echo $contactInfo; ?></h2>
                
                <div class="contact-details">
                    <div class="contact-detail">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4><?php echo $ourLocation; ?></h4>
                            <p>New Street Station, Birmingham B2 4QA, United Kingdom</p>
                        </div>
                    </div>
                    
                    <div class="contact-detail">
                        <div class="contact-icon">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="contact-text">
                            <h4><?php echo $phoneNumber; ?></h4>
                            <p>+44 (0) 7123 456 789</p>
                        </div>
                    </div>
                    
                    <div class="contact-detail">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-text">
                            <h4><?php echo $emailAddress; ?></h4>
                            <p>info@motivcarrental.com</p>
                        </div>
                    </div>
                </div>
                
                <div class="business-hours">
                    <h3><?php echo $businessHours; ?></h3>
                    <ul class="hours-list">
                        <li>
                            <span class="day"><?php echo $mondayFriday; ?></span>
                            <span class="time">8:00 AM - 8:00 PM</span>
                        </li>
                        <li>
                            <span class="day"><?php echo $saturday; ?></span>
                            <span class="time">9:00 AM - 6:00 PM</span>
                        </li>
                        <li>
                            <span class="day"><?php echo $sunday; ?></span>
                            <span class="time">10:00 AM - 4:00 PM</span>
                        </li>
                        <li>
                            <span class="day"><?php echo $emergencySupport; ?></span>
                            <span class="time"><?php echo $available24_7; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="contact-form-container">
                <h2><?php echo $sendMessage; ?></h2>
                
                <?php if (!empty($form_message)): ?>
                <div id="formMessage" class="form-message <?php echo $message_type; ?>">
                    <?php echo htmlspecialchars($form_message); ?>
                </div>
                <?php else: ?>
                <div id="formMessage" class="form-message" style="display: none;"></div>
                <?php endif; ?>
                
                <form id="contactForm" method="POST" action="">
                    <div class="form-group">
                        <label for="name"><?php echo $fullName; ?></label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email"><?php echo $emailAddressLabel; ?></label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone"><?php echo $phoneNumberLabel; ?></label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="subject"><?php echo $subjectLabel; ?></label>
                        <select id="subject" name="subject" required>
                            <option value=""><?php echo $selectSubject; ?></option>
                            <option value="general" <?php echo ($_POST['subject'] ?? '') === 'general' ? 'selected' : ''; ?>><?php echo $generalInquiry; ?></option>
                            <option value="booking" <?php echo ($_POST['subject'] ?? '') === 'booking' ? 'selected' : ''; ?>><?php echo $bookingAssistance; ?></option>
                            <option value="support" <?php echo ($_POST['subject'] ?? '') === 'support' ? 'selected' : ''; ?>><?php echo $technicalSupport; ?></option>
                            <option value="complaint" <?php echo ($_POST['subject'] ?? '') === 'complaint' ? 'selected' : ''; ?>><?php echo $complaint; ?></option>
                            <option value="feedback" <?php echo ($_POST['subject'] ?? '') === 'feedback' ? 'selected' : ''; ?>><?php echo $feedback; ?></option>
                            <option value="other" <?php echo ($_POST['subject'] ?? '') === 'other' ? 'selected' : ''; ?>><?php echo $other; ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message"><?php echo $yourMessage; ?></label>
                        <textarea id="message" name="message" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn"><?php echo $sendMessageBtn; ?></button>
                </form>
            </div>
        </div>
        
        <div class="faq-section">
            <h2 class="faq-title"><?php echo $faqTitle; ?></h2>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <h3><?php echo $faq1Q; ?></h3>
                    <p><?php echo $faq1A; ?></p>
                </div>
                
                <div class="faq-item">
                    <h3><?php echo $faq2Q; ?></h3>
                    <p><?php echo $faq2A; ?></p>
                </div>
                
                <div class="faq-item">
                    <h3><?php echo $faq3Q; ?></h3>
                    <p><?php echo $faq3A; ?></p>
                </div>
                
                <div class="faq-item">
                    <h3><?php echo $faq4Q; ?></h3>
                    <p><?php echo $faq4A; ?></p>
                </div>
                
                <div class="faq-item">
                    <h3><?php echo $faq5Q; ?></h3>
                    <p><?php echo $faq5A; ?></p>
                </div>
                
                <div class="faq-item">
                    <h3><?php echo $faq6Q; ?></h3>
                    <p><?php echo $faq6A; ?></p>
                </div>
            </div>
        </div>
        
        <div class="map-section">
            <h2 class="map-title"><?php echo $findUs; ?></h2>
            <div class="map-container">
                <iframe 
                    src="https://www.openstreetmap.org/export/embed.html?bbox=-1.9084%2C52.4747%2C-1.8912%2C52.4807&amp;layer=mapnik&amp;marker=52.4777%2C-1.8998" 
                    width="100%" 
                    height="100%" 
                    frameborder="0" 
                    scrolling="no">
                </iframe>
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

            const contactForm = document.getElementById('contactForm');
            const formMessage = document.getElementById('formMessage');
            const submitBtn = document.getElementById('submitBtn');
            
            <?php if (empty($form_message)): ?>
            contactForm.addEventListener('submit', function(e) {
                // Client-side validation (additional to server-side)
                const name = document.getElementById('name').value;
                const email = document.getElementById('email').value;
                const subject = document.getElementById('subject').value;
                const message = document.getElementById('message').value;
                
                if (!name || !email || !subject || !message) {
                    e.preventDefault();
                    showMessage('Please fill in all required fields.', 'error');
                    return;
                }
                
                if (!isValidEmail(email)) {
                    e.preventDefault();
                    showMessage('Please enter a valid email address.', 'error');
                    return;
                }
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.textContent = '<?php echo $sendMessageBtn; ?>...';
            });
            <?php endif; ?>
            
            function showMessage(text, type) {
                formMessage.textContent = text;
                formMessage.className = 'form-message ' + type;
                formMessage.style.display = 'block';
                
                formMessage.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                
                setTimeout(() => {
                    formMessage.style.display = 'none';
                }, 5000);
            }
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    $conn->close();
}
?>
