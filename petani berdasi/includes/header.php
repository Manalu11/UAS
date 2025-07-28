<?php
// header.php - Common header for all pages
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Petani Berdasi' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (isset($cssFile)): ?>
    <link rel="stylesheet" href="<?= $cssFile ?>">
    <?php endif; ?>
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
</head>

<body>
    <header class="bg-light shadow-sm">
        <div class="container">
            <nav class="navbar navbar-expand-lg navbar-light">
                <!-- Logo -->
                <a class="navbar-brand" href="/">
                    <img src="assets/images/logo.jpg" alt="Logo Petani Berdasi" width="120">
                </a>
                <!-- Menu Navigasi -->
                <div class="collapse navbar-collapse">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link" href="beranda.php">Beranda</a></li>
                        <li class="nav-item"><a class="nav-link" href="produk">Produk</a></li>
                        <li class="nav-item"><a class="nav-link" href="tentang.php">Tentang Kami</a></li>
                    </ul>
                </div>
            </nav>
        </div>
    </header>