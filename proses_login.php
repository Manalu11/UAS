<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) === 1) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role']; // Pastikan kolom 'role' ada di tabel users
            
            header("Location: beranda.php");
            exit();
        } else {
            header("Location: login.php?error=Password salah");
            exit();
        }
    } else {
        header("Location: login.php?error=Username tidak ditemukan");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>