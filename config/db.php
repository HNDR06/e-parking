<?php
// Konfigurasi koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "e_parking_db";

// CRITICAL: Set timezone PHP ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// CRITICAL: Set timezone MySQL ke WIB (UTC+7)
$conn->query("SET time_zone = '+07:00'");

// Set charset UTF-8 untuk handle karakter Indonesia
$conn->set_charset("utf8mb4");

// Optional: Set SQL mode untuk keamanan
// $conn->query("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'");
?>