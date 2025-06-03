<?php
$servername = "localhost"; 
$username = "root"; 
$password = ""; 
$dbname = "portofolio_db"; 

// Membuat koneksi
$conn = new mysqli($servername, $username, $password);

// Cek koneksi
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}

// Membuat database jika belum ada
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql_create_db) === TRUE) {
  // echo "Database $dbname berhasil dibuat atau sudah ada.<br>";
} else {
  // Hentikan eksekusi jika ada error saat membuat database
  die("Error creating database: " . $conn->error . "<br>");
}

// Memilih database
$conn->select_db($dbname);

// Set karakter set ke utf8mb4 untuk mendukung berbagai karakter
if (!$conn->set_charset("utf8mb4")) {
    //printf("Error loading character set utf8mb4: %s\n", $conn->error);
}
?>