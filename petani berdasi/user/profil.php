<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Ambil data user saat ini
$user = querySingle("SELECT * FROM users WHERE id = ?", [$user_id], 'i');

// Ambil transaksi terakhir
$transactions = query("SELECT t.id, t.invoice_number, t.amount, t.status, t.created_at 
                      FROM transactions t 
                      WHERE t.user_id = ?
                      ORDER BY t.created_at DESC 
                      LIMIT 5", [$user_id], 'i')->fetch_all(MYSQLI_ASSOC);

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
    <title>Profil Saya - Petani Berdasi</title>
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
                        <?php
// 1. Tentukan path default
$default_foto = 'assets/images/profiles/default.jpg'; // Pastikan file ini ada

// 2. Cek foto profil (dengan sanitasi)
$foto_profil = !empty($user['foto_profil']) && file_exists($user['foto_profil']) 
    ? htmlspecialchars($user['foto_profil']) 
    : $default_foto;
?>

                        <!-- 3. Tampilkan dengan alt text yang aman -->
                        <img src="<?= $foto_profil ?>" class="rounded-circle mb-3" width="150" height="150"
                            alt="Foto Profil <?= htmlspecialchars($user['username'] ?? 'User') ?>">

                        <h4><?= htmlspecialchars($user['username'] ?? 'User Tidak Dikenal') ?></h4>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Menu Akun</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="profil.php" class="list-group-item list-group-item-action active">
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
                        <a href="wishlist.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-heart me-2"></i> Wishlist
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Profil Saya</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?= $success_message ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <h6>Informasi Akun</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Nama Lengkap:</strong> <?= htmlspecialchars($user['full_name'] ?? '-') ?>
                                    </p>
                                    <p><strong>Telepon:</strong> <?= htmlspecialchars($user['phone'] ?? '-') ?></p>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6>Transaksi Terakhir</h6>
                                <a href="transaksi.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                            </div>

                            <?php if (count($transactions) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Invoice</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($transactions as $txn): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($txn['invoice_number']) ?></td>
                                            <td>Rp <?= number_format($txn['amount'], 0, ',', '.') ?></td>
                                            <td>
                                                <span class="badge <?= getStatusBadgeClass($txn['status']) ?>">
                                                    <?= htmlspecialchars($txn['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('d M Y', strtotime($txn['created_at'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">Belum ada transaksi.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/user-actions.js"></script>
</body>

</html>
<?php
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'completed':
        case 'selesai':
            return 'bg-success';
        case 'pending':
        case 'menunggu':
            return 'bg-warning';
        case 'failed':
        case 'gagal':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>