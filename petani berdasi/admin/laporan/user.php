<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil parameter filter
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'revenue';
$role = isset($_GET['role']) ? $_GET['role'] : 'all';

// Query untuk laporan user
$where = "";
$params = [];
$types = "";

if ($role !== 'all') {
    $where = "WHERE u.role = ?";
    $params = [$role];
    $types = "s";
}

$order_by = "";
switch ($sort) {
    case 'revenue':
        $order_by = "total_revenue DESC";
        break;
    case 'transactions':
        $order_by = "transaction_count DESC";
        break;
    case 'products':
        $order_by = "product_count DESC";
        break;
    case 'registered':
        $order_by = "u.created_at DESC";
        break;
    default:
        $order_by = "total_revenue DESC";
}

$users = query("SELECT u.id, u.username, u.email, u.role, u.created_at,
               COUNT(DISTINCT t.id) as transaction_count,
               SUM(t.amount) as total_revenue,
               COUNT(DISTINCT p.id) as product_count
               FROM users u
               LEFT JOIN transactions t ON u.id = t.user_id AND t.status = 'completed'
               LEFT JOIN products p ON u.id = p.user_id
               $where
               GROUP BY u.id
               ORDER BY $order_by", $params, $types)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan User - Petani Berdasi</title>
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
                <h2>Laporan User</h2>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak Laporan
                </button>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="sort" class="form-label">Urutkan Berdasarkan</label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="revenue" <?= $sort == 'revenue' ? 'selected' : '' ?>>Total Pembelian
                                    </option>
                                    <option value="transactions" <?= $sort == 'transactions' ? 'selected' : '' ?>>Jumlah
                                        Transaksi</option>
                                    <option value="products" <?= $sort == 'products' ? 'selected' : '' ?>>Jumlah Produk
                                    </option>
                                    <option value="registered" <?= $sort == 'registered' ? 'selected' : '' ?>>Tanggal
                                        Daftar</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="all" <?= $role == 'all' ? 'selected' : '' ?>>Semua Role</option>
                                    <option value="user" <?= $role == 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="seller" <?= $role == 'seller' ? 'selected' : '' ?>>Seller</option>
                                    <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end mb-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Transaksi</th>
                                    <th>Total Pembelian</th>
                                    <th>Produk</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($user['username']) ?><br>
                                        <small><?= htmlspecialchars($user['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $user['role'] == 'admin' ? 'danger' : 
                                            ($user['role'] == 'seller' ? 'success' : 'primary')
                                        ?>">
                                            <?= ucfirst($user['role']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                    <td><?= $user['transaction_count'] ?></td>
                                    <td>Rp <?= number_format($user['total_revenue'] ?? 0, 0, ',', '.') ?></td>
                                    <td><?= $user['product_count'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>