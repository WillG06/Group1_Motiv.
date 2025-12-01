<?php
$host = 'localhost';
$username = 'root';
$password = 'AstonUni786!';
$database = 'car_rental_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>