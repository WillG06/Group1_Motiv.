<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: loginPage.php');
    exit;
}

$inquiryId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($inquiryId === 0) {
    header('Location: admin-dashboard.php');
    exit;
}

$inquiryQuery = $conn->prepare("
    SELECT inquiry_id, name, email, phone, subject, message, created_at, status
    FROM contact_inquiries
    WHERE inquiry_id = ?
");
$inquiryQuery->bind_param("i", $inquiryId);
$inquiryQuery->execute();
$inquiryResult = $inquiryQuery->get_result();
$inquiry = $inquiryResult->fetch_assoc();
$inquiryQuery->close();

if (!$inquiry) {
    header('Location: admin-dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = $_POST['new_status'];
    
    $updateQuery = $conn->prepare("UPDATE contact_inquiries SET status = ? WHERE inquiry_id = ?");
    $updateQuery->bind_param("si", $newStatus, $inquiryId);
    
    if ($updateQuery->execute()) {
        $successMessage = "Inquiry status updated successfully!";
        $inquiry['status'] = $newStatus;
    } else {
        $errorMessage = "Error updating inquiry status: " . $conn->error;
    }
    $updateQuery->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiry Details - Motiv Car Hire</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .inquiry-details-container {
            padding: 40px 0;
            background-color: #f5f5f5;
            min-height: 100vh;
        }
        
        .inquiry-details-header {
            background: linear-gradient(to right, var(--dark-magenta), var(--vivid-indigo));
            color: white;
            padding: 40px 0;
            margin-bottom: 40px;
        }
        
        .inquiry-details-content {
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
        
        .inquiry-info {
            background: #f8f8f8;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--vivid-indigo);
            min-width: 120px;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        .message-content {
            background: white;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid var(--cobalt-blue);
            margin-top: 10px;
            line-height: 1.6;
        }
        
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-new {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .status-replied {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: flex-end;
        }
        
        .btn-primary, .btn-secondary, .btn-success {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary {
            background: var(--cobalt-blue);
            color: white;
        }
        
        .btn-secondary {
            background: transparent;
            border: 1px solid var(--cobalt-blue);
            color: var(--cobalt-blue);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
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

<section class="inquiry-details-header">
    <div class="container">
        <h1>Inquiry Details</h1>
        <p>View and manage customer inquiry</p>
    </div>
</section>

<section class="inquiry-details-container">
    <div class="container">
        <a href="admin-dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="inquiry-details-content">
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
            
            <div class="inquiry-info">
                <div class="info-row">
                    <div class="info-label">Inquiry ID:</div>
                    <div class="info-value">#<?php echo $inquiry['inquiry_id']; ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Customer Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($inquiry['name']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Email:</div>
                    <div class="info-value">
                        <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>">
                            <?php echo htmlspecialchars($inquiry['email']); ?>
                        </a>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo htmlspecialchars($inquiry['phone'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Subject:</div>
                    <div class="info-value"><?php echo htmlspecialchars($inquiry['subject']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Date Received:</div>
                    <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($inquiry['created_at'])); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Status:</div>
                    <div class="info-value">
                        <span class="status-badge status-<?php echo strtolower($inquiry['status']); ?>">
                            <?php echo ucfirst($inquiry['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Message:</div>
                    <div class="info-value">
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>?subject=Re: <?php echo urlencode($inquiry['subject']); ?>" class="btn-primary">
                    <i class="fas fa-reply"></i> Reply via Email
                </a>
                
                <?php if ($inquiry['status'] === 'new'): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="update_status" value="1">
                        <input type="hidden" name="new_status" value="replied">
                        <button type="submit" class="btn-success">
                            <i class="fas fa-check"></i> Mark as Replied
                        </button>
                    </form>
                <?php endif; ?>
                
                <a href="admin-dashboard.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Inquiries
                </a>
            </div>
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