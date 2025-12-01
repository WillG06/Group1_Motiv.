<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: loginPage.php");
    exit();
}

if (isset($_GET['car_id'])) {
    $car_id = intval($_GET['car_id']);
    $customer_id = $_SESSION['user']['id'];
    
    require_once 'db.php';
    
    $stmt = $conn->prepare("DELETE FROM favorites WHERE customer_id = ? AND car_id = ?");
    $stmt->bind_param("ii", $customer_id, $car_id);
    
    if ($stmt->execute()) {
        header("Location: customer-dashboard.php");
    } else {
        header("Location: customer-dashboard.php?error=remove_favorite");
    }
    $stmt->close();
    exit();
} else {
    header("Location: customer-dashboard.php");
    exit();
}
?>