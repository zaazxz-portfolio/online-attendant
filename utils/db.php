<?php
date_default_timezone_set('Asia/Jakarta');

// Dummy acc
$conn = new mysqli(
    "mysql", 
    "root", 
    "mudahaja123",
    "kknm_dummy"
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
