<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Validasi CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cek autentikasi pengguna
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <?php include 'includes/navbar_user.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4>Tentang Petani Berdasi</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title">Visi Kami</h5>
                        <p class="card-text">Menjadi platform terdepan dalam menghubungkan petani langsung dengan
                            konsumen untuk menciptakan rantai pasok yang lebih efisien dan adil.</p>

                        <h5 class="card-title mt-4">Misi Kami</h5>
                        <ul>
                            <li>Memberdayakan petani lokal dengan memberikan akses pasar yang lebih luas</li>
                            <li>Menyediakan produk pertanian berkualitas tinggi langsung dari sumbernya</li>
                            <li>Menciptakan transparansi dalam rantai pasok produk pertanian</li>
                            <li>Mengurangi biaya perantara untuk memberikan harga terbaik bagi konsumen dan petani</li>
                        </ul>

                        <h5 class="card-title mt-4">Sejarah</h5>
                        <p class="card-text">Petani Berdasi didirikan pada tahun 2023 oleh sekelompok anak muda yang
                            peduli dengan nasib petani Indonesia. Kami melihat adanya kesenjangan antara harga yang
                            diterima petani dengan harga yang dibayar konsumen, sehingga kami menciptakan platform ini
                            untuk menjembatani kedua pihak.</p>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>