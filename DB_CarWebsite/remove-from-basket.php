<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: loginPage.php");
    exit();
}

if (isset($_GET['item_id'])) {
    $item_id = intval($_GET['item_id']);
    
    require_once 'db.php';
    
    $stmt = $conn->prepare("DELETE FROM basket_items WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    
    if ($stmt->execute()) {
        header("Location: customer-dashboard.php");
    } else {
        header("Location: customer-dashboard.php?error=remove_basket");
    }
    $stmt->close();
    exit();
} else {
    header("Location: customer-dashboard.php");
    exit();
}
?>