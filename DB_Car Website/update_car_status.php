<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['car_id']) && isset($_POST['new_status'])) {
    $carId = intval($_POST['car_id']);
    $newStatus = intval($_POST['new_status']);
    
    $updateQuery = $conn->prepare("UPDATE cars SET status_id = ? WHERE car_id = ?");
    $updateQuery->bind_param("ii", $newStatus, $carId);
    
    if ($updateQuery->execute()) {
        echo json_encode(['success' => true, 'message' => 'Car status updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating car status: ' . $conn->error]);
    }
    $updateQuery->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}

$conn->close();
?>