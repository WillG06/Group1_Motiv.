<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <title>FAQ's</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --dark-magenta: #1800AD;
            --vivid-indigo: #8C0050;
            --vivid-indigo-light: #72114a;
            --cobalt-blue: #004AAD;
            --coral-red: #FF7F50;
            --coral-red-light: #FF8C00;
            --vivid-red: #FF0000;
            --light-gray: #f8f8f8;
            --white: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .chat-toggle {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--vivid-indigo), var(--vivid-indigo-light));
            border-radius: 50%;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(123, 20, 80, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .chat-toggle:hover {
            transform: scale(1.1);
        }

        .chat-toggle img {
            width: 35px;
            height: 35px;
            object-fit: contain;
        }

        .chat-toggle svg {
            width: 30px;
            height: 30px;
            fill: white;
        }

        .chat-container {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 380px;
            height: 600px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.3);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 999;
        }

        .chat-container.active {
            display: flex;
        }

        .chat-header {
            background: linear-gradient(135deg, var(--vivid-indigo), var(--vivid-indigo-light));
            color: white;
            padding: 20px;
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
            width: 46px;
            height: 46px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--vivid-indigo);
            font-size: 18px;
            overflow: hidden;
        }

        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 4px;
        }

        .chat-title h3 {
            font-size: 18px;
            margin-bottom: 2px;
        }

        .chat-status {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 400;
            color: #50ff84;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
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
            padding: 20px;
            background: #f5f5f5;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
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

        .message.bot .message-avatar {
            background: linear-gradient(135deg, var(--vivid-indigo), var(--vivid-indigo-light));
            color: white;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            line-height: 1.5;
            font-size: 14px;

        }

        .message-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            flex-shrink: 0;
            overflow: hidden;
            align-self: flex-start;
            margin-top: -4px;
        }

        .message-avatar img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: white;
            padding: 4px;
        }

        .message.user .message-avatar {
            background: var(--coral-red-light);
            color: white;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
            opacity: 0.7;
        }


        .message.bot .message-content {
            background: white;
            color: #333;
            border-bottom-left-radius: 4px;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, var(--coral-red), var(--coral-red-light));
            color: white;
            border-top-right-radius: 4px;
        }

        .quick-replies {
            padding: 10px 20px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            background: #f3f3f3;
            border-top: 1px solid #eee;
        }

        .quick-reply-btn {
            padding: 8px 16px;
            background: white;
            border: 2px solid var(--vivid-indigo);
            color: var(--vivid-indigo);
            border-radius: 20px;
            cursor: pointer;
            font-size: 13px;
            transition: all 0.3s ease;
        }

        .quick-reply-btn:hover {
            background: var(--vivid-indigo);
            color: white;
        }

        .chat-input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
        }

        .input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #ddd;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s ease;
        }

        .chat-input:focus {
            border-color: var(--vivid-indigo);
        }

        .send-btn {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--vivid-indigo), var(--vivid-indigo-light));
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;
        }

        .send-btn:hover {
            transform: scale(1.1);
        }

        .send-btn svg {
            width: 20px;
            height: 20px;
            fill: white;
        }

        .typing-indicator {
            display: none;
            padding: 12px 16px;
            background: white;
            border-radius: 18px;
            border-bottom-left-radius: 4px;
            max-width: 70px;
        }

        .typing-indicator.active {
            display: block;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dots span {
            width: 8px;
            height: 8px;
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

            0%,
            60%,
            100% {
                transform: translateY(0);
                opacity: 0.7;
            }

            30% {
                transform: translateY(-10px);
                opacity: 1;
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

<body>
    <?php
    // PHP function to get bot response
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

        // Extended response logic
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

    // Handle AJAX request if this is an API endpoint
    if (isset($_POST['message']) && !empty($_POST['message'])) {
        $userMessage = $_POST['message'];
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

        function toggleChat() {
            chatContainer.classList.toggle('active');
            if (chatContainer.classList.contains('active')) {
                chatInput.focus();
            }
        }

        chatToggle.addEventListener('click', toggleChat);
        closeChat.addEventListener('click', toggleChat);

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
                body: 'message=' + encodeURIComponent(message)
            })
            .then(response => response.json())
            .then(data => {
                removeTypingIndicator();
                addMessage(data.response);
            })
            .catch(error => {
                console.error('Error:', error);
                removeTypingIndicator();
                addMessage('Sorry, I encountered an error. Please try again.');
            });
        }

        sendBtn.addEventListener('click', sendMessage);

        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        quickReplies.forEach(btn => {
            btn.addEventListener('click', () => {
                const reply = btn.getAttribute('data-reply');
                chatInput.value = reply;
                sendMessage();
            });
        });
    </script>
</body>

</html>
