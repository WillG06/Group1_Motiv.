<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_all']) && $_POST['mark_all'] == '1') {

        $updateQuery = $conn->prepare("UPDATE contact_inquiries SET status = ? WHERE status = 'new'");
        $updateQuery->bind_param("s", $_POST['status']);
        
        if ($updateQuery->execute()) {
            echo json_encode(['success' => true, 'message' => 'All inquiries updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating inquiries']);
        }
        $updateQuery->close();
    } elseif (isset($_POST['inquiry_id'])) {

        $updateQuery = $conn->prepare("UPDATE contact_inquiries SET status = ? WHERE inquiry_id = ?");
        $updateQuery->bind_param("si", $_POST['status'], $_POST['inquiry_id']);
        
        if ($updateQuery->execute()) {
            echo json_encode(['success' => true, 'message' => 'Inquiry status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating inquiry']);
        }
        $updateQuery->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}

$conn->close();
?>