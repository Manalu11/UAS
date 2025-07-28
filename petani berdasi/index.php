<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selamat Datang - Petani Berdasi</title>
    <link rel="stylesheet" href="assets/css/welcome.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="welcome-container">
        <div class="logo">
            <img src="../assets/images/logo.jpg" alt="Petani Berdasi">
        </div>

        <h1>Selamat Datang di Petani Berdasi</h1>
        <p>Platform jual beli produk pertanian terbaik untuk petani modern</p>

        <div class="action-buttons">
            <a href="auth/login.php" class="btn btn-login">
                <i class="fas fa-sign-in-alt"></i> Masuk
            </a>
            <a href="auth/register.php" class="btn btn-register">
                <i class="fas fa-user-plus"></i> Daftar Akun
            </a>
        </div>

        <div class="guest-option">
            <p>Atau lanjut sebagai tamu:</p>
            <a href="beranda.php" class="btn btn-guest">
                <i class="fas fa-store"></i> Lihat Produk
            </a>
        </div>
    </div>
</body>

</html>