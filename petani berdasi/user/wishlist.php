<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Ambil produk dalam wishlist user
$wishlist_items = query("SELECT w.id as wishlist_id, p.id, p.nama, p.harga, p.gambar, p.stok 
                        FROM wishlist w
                        JOIN products p ON w.product_id = p.id
                        WHERE w.user_id = ?
                        ORDER BY w.created_at DESC", [$user_id], 'i')->fetch_all(MYSQLI_ASSOC);

// Handle penghapusan item dari wishlist
if (isset($_GET['remove'])) {
    $wishlist_id = (int)$_GET['remove'];
    
    // Validasi kepemilikan wishlist item
    $wishlist = querySingle("SELECT id FROM wishlist WHERE id = ? AND user_id = ?", [$wishlist_id, $user_id], 'ii');
    
    if ($wishlist) {
        $conn->query("DELETE FROM wishlist WHERE id = $wishlist_id");
        $_SESSION['success_message'] = 'Item berhasil dihapus dari wishlist';
        header("Location: wishlist.php");
        exit();
    } else {
        $errors[] = 'Item tidak ditemukan atau tidak memiliki akses';
    }
}

// Ambil pesan sukses dari session jika ada
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php include '../includes/navbar_user.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($_SESSION['foto_profil']) ?>" class="rounded-circle mb-3"
                            width="150" height="150">
                        <h4><?= htmlspecialchars($_SESSION['username']) ?></h4>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Menu Akun</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="profil.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profil Saya
                        </a>
                        <a href="edit_profil.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-edit me-2"></i> Edit Profil
                        </a>
                        <a href="alamat.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-map-marker-alt me-2"></i> Alamat Saya
                        </a>
                        <a href="transaksi.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-shopping-cart me-2"></i> Transaksi Saya
                        </a>
                        <a href="wishlist.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-heart me-2"></i> Wishlist
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Wishlist Saya</h2>
                    <a href="<?= base_url('produk/') ?>" class="btn btn-outline-primary">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </a>
                    <i class="fas fa-plus"></i> Tambah Produk
                    </a>
                </div>

                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?= $success_message ?>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <?php if (count($wishlist_items) > 0): ?>
                <div class="row">
                    <?php foreach ($wishlist_items as $item): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="<?= htmlspecialchars($item['gambar']) ?>"
                                        class="img-fluid rounded-start h-100" style="object-fit: cover;"
                                        alt="<?= htmlspecialchars($item['nama']) ?>">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($item['nama']) ?></h5>
                                        <p class="card-text text-success">
                                            Rp <?= number_format($item['harga'], 0, ',', '.') ?>
                                        </p>
                                        <p class="card-text">
                                            <small class="text-<?= $item['stok'] > 0 ? 'success' : 'danger' ?>">
                                                <?= $item['stok'] > 0 ? 'Stok tersedia' : 'Stok habis' ?>
                                            </small>
                                        </p>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <a href="../produk/detail.php?id=<?= $item['id'] ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-shopping-cart"></i> Beli
                                        </a>
                                        <a href="wishlist.php?remove=<?= $item['wishlist_id'] ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Yakin ingin menghapus dari wishlist?')">
                                            <i class="fas fa-trash"></i> Hapus
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                        <h5>Wishlist Anda kosong</h5>
                        <p>Tambahkan produk favorit Anda ke wishlist untuk menyimpannya di sini</p>
                        <a href="../produk/" class="btn btn-primary">
                            <i class="fas fa-store"></i> Jelajahi Produk
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/user-actions.js"></script>
</body>

</html>