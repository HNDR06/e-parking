<?php
// Konfigurasi koneksi database
$host = "localhost";      // Biasanya localhost
$user = "root";           // Username default XAMPP
$pass = "";               // Password default kosong
$db   = "e_parking_db";      // Nama database yang sudah kamu buat

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>