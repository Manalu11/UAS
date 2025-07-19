<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil daftar pesanan
$query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Untuk setiap pesanan, ambil detailnya
foreach ($orders as &$order) {
    $order_id = $order['id'];
    $detailQuery = "SELECT od.*, p.judul, p.gambar_path 
                   FROM order_details od 
                   JOIN posts p ON od.product_id = p.id 
                   WHERE od.order_id = $order_id";
    $detailResult = mysqli_query($conn, $detailQuery);
    $order['items'] = mysqli_fetch_all($detailResult, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pesanan Saya - Petani Berdasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
    .order-list {
        margin-top: 20px;
    }

    .order-card {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .order-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .order-id {
        font-weight: bold;
    }

    .order-status {
        padding: 5px 10px;
        border-radius: 3px;
        color: white;
    }

    .status-pending {
        background-color: #FFC107;
    }

    .status-processing {
        background-color: #2196F3;
    }

    .status-shipped {
        background-color: #673AB7;
    }

    .status-completed {
        background-color: #4CAF50;
    }

    .status-cancelled {
        background-color: #f44336;
    }

    .order-total {
        text-align: right;
        font-weight: bold;
        margin-top: 10px;
    }

    .order-items {
        margin-top: 15px;
    }

    .order-item {
        display: flex;
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f5f5f5;
    }

    .item-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        margin-right: 15px;
    }

    .item-details {
        flex-grow: 1;
    }

    .item-title {
        font-weight: bold;
    }

    .item-price {
        color: #888;
    }

    .item-quantity {
        color: #888;
    }

    .empty-orders {
        text-align: center;
        padding: 50px;
        color: #888;
    }
    </style>
</head>

<body>
    <!-- Header sama seperti beranda.php -->

    <div class="container">
        <h2>Pesanan Saya</h2>

        <?php if (!empty($orders)): ?>
        <div class="order-list">
            <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="order-header">
                    <span class="order-id">Order #<?= $order['id'] ?></span>
                    <span class="order-date"><?= date('d M Y H:i', strtotime($order['created_at'])) ?></span>
                    <span class="order-status status-<?= $order['status'] ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>

                <div class="order-items">
                    <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <img src="/<?= htmlspecialchars($item['gambar_path']) ?>"
                            alt="<?= htmlspecialchars($item['judul']) ?>" class="item-image">
                        <div class="item-details">
                            <div class="item-title"><?= htmlspecialchars($item['judul']) ?></div>
                            <div class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                            <div class="item-quantity">Jumlah: <?= $item['quantity'] ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="order-total">
                    Total: Rp <?= number_format($order['total'], 0, ',', '.') ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-orders">
            <i class="fas fa-box-open fa-3x"></i>
            <h3>Belum ada pesanan</h3>
            <p>Silakan belanja terlebih dahulu</p>
            <a href="beranda.php" class="btn">Kembali ke Beranda</a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Footer sama seperti beranda.php -->
</body>

</html>