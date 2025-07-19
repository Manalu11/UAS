<?php
$host = "localhost";
$user = "root"; // ganti sesuai konfigurasi kamu
$pass = "202312035";     // password MySQL kamu
$db   = "petani_berdasi_db"; // nama database kamu

$conn = mysqli_connect($host, $user, $pass, $db);

// Cek koneksi
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// HAPUS baris echo "koneksi berhasil"; karena mengganggu JSON response
// Jika ingin debug koneksi, bisa menggunakan error_log() atau tampilkan hanya saat development

// Optional: Set charset untuk menghindari masalah encoding
mysqli_set_charset($conn, "utf8");
?>