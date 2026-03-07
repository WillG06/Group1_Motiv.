<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'customer') {
    header("Location: loginPage.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user']['id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $city_id = $_POST['city_id'];
    
    $stmt = $conn->prepare("UPDATE customers SET first_name = ?, last_name = ?, email = ?, phone = ?, city_id = ? WHERE customer_id = ?");
    $stmt->bind_param("ssssii", $first_name, $last_name, $email, $phone, $city_id, $customer_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Profile updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . $conn->error;
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: customer-dashboard.php#profile");
    exit();
}
?>
