<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: loginPage.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user']['id'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New passwords do not match!";
        header("Location: customer-dashboard.php#profile");
        exit();
    }
    
    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE customers SET password = ? WHERE customer_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $customer_id);
        
        if ($update_stmt->execute()) {
            $_SESSION['success_message'] = "Password changed successfully!";
        } else {
            $_SESSION['error_message'] = "Error changing password!";
        }
        $update_stmt->close();
    } else {
        $_SESSION['error_message'] = "Current password is incorrect!";
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: customer-dashboard.php#profile");
    exit();
}
?>
