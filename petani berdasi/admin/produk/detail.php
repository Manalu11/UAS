<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil ID produk dari URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil data produk dari database
$product = querySingle("SELECT p.*, u.username 
                       FROM products p 
                       JOIN users u ON p.user_id = u.id 
                       WHERE p.id = ?", [$product_id], 'i');
if (!$product) {
    header("Location: index.php");
    exit();
}

// Format tanggal
$created_at = date('d F Y H:i', strtotime($product['created_at']));
$updated_at = $product['updated_at'] ? date('d F Y H:i', strtotime($product['updated_at'])) : '-';
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Produk - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>

<body>
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../../includes/navbar_admin.php'; ?>

        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Detail Produk</h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <?php if ($product['image_url']): ?>
                            <img src="../../<?= htmlspecialchars($product['image_url']) ?>"
                                class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <h3><?= htmlspecialchars($product['name']) ?></h3>
                            <p class="text-muted">Ditambahkan pada: <?= $created_at ?></p>
                            <p class="text-muted">Terakhir diperbarui: <?= $updated_at ?></p>

                            <div class="mb-3">
                                <h5>Deskripsi Produk</h5>
                                <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h5>Harga</h5>
                                        <p>Rp <?= number_format($product['price'], 0, ',', '.') ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h5>Stok Tersedia</h5>
                                        <p><?= htmlspecialchars($product['stock']) ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h5>Penjual</h5>
                                        <p><?= htmlspecialchars($product['username']) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <h5>Status</h5>
                                        <span class="badge bg-<?= $product['is_active'] ? 'success' : 'danger' ?>">
                                            <?= $product['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-warning me-2">
                                    <i class="fas fa-edit"></i> Edit Produk
                                </a>
                                <a href="hapus.php?id=<?= $product['id'] ?>" class="btn btn-danger"
                                    onclick="return confirm('Apakah Anda yakin ingin menghapus produk ini?')">
                                    <i class="fas fa-trash"></i> Hapus Produk
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>