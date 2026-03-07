<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: loginPage.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    $customer_id = $_SESSION['user']['id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete related records (adjust based on your database schema)
        $conn->query("DELETE FROM favorites WHERE customer_id = $customer_id");
        $conn->query("DELETE FROM basket_items WHERE basket_id IN (SELECT basket_id FROM baskets WHERE customer_id = $customer_id)");
        $conn->query("DELETE FROM baskets WHERE customer_id = $customer_id");
        $conn->query("DELETE FROM bookings WHERE customer_id = $customer_id");
        
        // Delete customer
        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        
        $conn->commit();
        
        // Logout and redirect
        session_destroy();
        header("Location: landing.php?message=account_deleted");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting account: " . $e->getMessage();
        header("Location: customer-dashboard.php#profile");
        exit();
    }
}
?>
