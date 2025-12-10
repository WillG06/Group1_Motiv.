<?php
session_start();

if (isset($_SESSION['search_criteria'])) {
    unset($_SESSION['search_criteria']);
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);
?>