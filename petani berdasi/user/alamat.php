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

// Ambil alamat user
$addresses = query("SELECT * FROM addresses WHERE user_id = ?", [$user_id], 'i')->fetch_all(MYSQLI_ASSOC);

// Handle penghapusan alamat
if (isset($_GET['delete'])) {
    $address_id = (int)$_GET['delete'];
    
    // Validasi kepemilikan alamat
    $address = querySingle("SELECT id FROM addresses WHERE id = ? AND user_id = ?", [$address_id, $user_id], 'ii');
    
    if ($address) {
        $conn->query("DELETE FROM addresses WHERE id = $address_id");
        $_SESSION['success_message'] = 'Alamat berhasil dihapus';
        header("Location: alamat.php");
        exit();
    } else {
        $errors[] = 'Alamat tidak ditemukan atau tidak memiliki akses';
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
    <title>Alamat Saya - Petani Berdasi</title>
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
                        <a href="alamat.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-map-marker-alt me-2"></i> Alamat Saya
                        </a>
                        <a href="transaksi.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-shopping-cart me-2"></i> Transaksi Saya
                        </a>
                        <a href="wishlist.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-heart me-2"></i> Wishlist
                        </a>
                    </div>
                </div>
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

            <?php if (count($addresses) > 0): ?>
            <div class="row">
                <?php foreach ($addresses as $address): ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($address['label']) ?></h5>
                            <p class="card-text">
                                <?= htmlspecialchars($address['address']) ?><br>
                                <?= htmlspecialchars($address['city']) ?>,
                                <?= htmlspecialchars($address['postal_code']) ?><br>
                                <?= htmlspecialchars($address['province']) ?>
                            </p>
                            <p class="card-text">
                                <strong>Penerima:</strong> <?= htmlspecialchars($address['recipient_name']) ?><br>
                                <strong>Telepon:</strong> <?= htmlspecialchars($address['recipient_phone']) ?>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="edit_alamat.php?id=<?= $address['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="alamat.php?delete=<?= $address['id'] ?>" class="btn btn-sm btn-outline-danger"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus alamat ini?')">
                                <i class="fas fa-trash"></i> Hapus
                            </a>
                            <?php if ($address['is_primary']): ?>
                            <span class="badge bg-success float-end">Utama</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="card">
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