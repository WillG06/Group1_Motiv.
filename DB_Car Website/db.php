<?php
$host = 'localhost';   
$username = 'cs2team1';
$password = 'GIzgRTkFQWYg5bByiUxSMhhcJ';
$database = 'cs2team1_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
