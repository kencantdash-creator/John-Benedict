<?php

$host = "sql208.infinityfree.com";
$username = "if0_43036165";
$password = "Ruyujikcomsee";
$database = "if0_43036165_barangay";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

?>