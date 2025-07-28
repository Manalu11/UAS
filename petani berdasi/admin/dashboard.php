<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 1 day
        'cookie_secure' => false, // Set to true if using HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../auth/login.php?error=not_logged_in");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../beranda.php?error=not_admin");
    exit();
}

// Get statistics data
$stats = [
    'total_users' => querySingle("SELECT COUNT(*) as count FROM users")['count'],
    'total_products' => querySingle("SELECT COUNT(*) as count FROM products")['count'],
    'total_transactions' => querySingle("SELECT COUNT(*) as count FROM transactions")['count'],
    'recent_users' => query("SELECT username, email, foto_profil, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC),
    'recent_products' => query("SELECT p.id, p.name, p.price, u.username, p.created_at 
                              FROM products p 
                              JOIN users u ON p.user_id = u.id 
                              ORDER BY p.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC)
];

// Calculate current month revenue
$currentMonthRevenue = querySingle("SELECT SUM(amount) as total FROM transactions 
                                  WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                                  AND YEAR(created_at) = YEAR(CURRENT_DATE())")['total'] ?? 0;

// Prepare chart data
$revenueData = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $revenue = querySingle("SELECT SUM(amount) as total FROM transactions 
                          WHERE DATE_FORMAT(created_at, '%Y-%m') = ?", [$month], 's')['total'] ?? 0;
    $revenueData[] = [
        'month' => date('M Y', strtotime($month . '-01')),
        'revenue' => $revenue
    ];
}

$chartData = [
    'labels' => array_column($revenueData, 'month'),
    'data' => array_column($revenueData, 'revenue')
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
    .card-stat {
        transition: transform 0.3s;
    }

    .card-stat:hover {
        transform: translateY(-5px);
    }

    .btn-detail {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    #recent-products-table th {
        white-space: nowrap;
    }
    </style>
</head>

<body>
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../includes/navbar_admin.php'; ?>

        <div class="container-fluid py-4">
            <h2 class="mb-4">Dashboard Admin</h2>

            <!-- Main Stats -->
            <div class="row mb-4" id="stats-container">
                <div class="col-md-3 mb-3">
                    <div class="card card-stat bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Pengguna</h5>
                                    <h2 class="card-text mb-0"><?= $stats['total_users'] ?></h2>
                                </div>
                                <i class="fas fa-users fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stat bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Produk</h5>
                                    <h2 class="card-text mb-0"><?= $stats['total_products'] ?></h2>
                                </div>
                                <i class="fas fa-boxes fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stat bg-warning text-dark h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Total Transaksi</h5>
                                    <h2 class="card-text mb-0"><?= $stats['total_transactions'] ?></h2>
                                </div>
                                <i class="fas fa-receipt fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="card card-stat bg-info text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title">Pendapatan Bulan Ini</h5>
                                    <h2 class="card-text mb-0">Rp
                                        <?= number_format($currentMonthRevenue, 0, ',', '.') ?></h2>
                                </div>
                                <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Pendapatan 6 Bulan Terakhir</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Pengguna Terbaru</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($stats['recent_users'] as $user): ?>
                                <div class="list-group-item d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($user['foto_profil'] ?? '../assets/images/default-profile.png') ?>"
                                        class="rounded-circle me-3" width="40" height="40" alt="Profil">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($user['username']) ?></h6>
                                        <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Produk Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="recent-products-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Produk</th>
                                            <th>Harga</th>
                                            <th>Penjual</th>
                                            <th>Tanggal</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['recent_products'] as $product): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td>Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                                            <td><?= htmlspecialchars($product['username']) ?></td>
                                            <td><?= date('d M Y', strtotime($product['created_at'])) ?></td>
                                            <td>
                                                <a href="produk/detail.php?id=<?= $product['id'] ?>"
                                                    class="btn btn-sm btn-primary btn-detail">
                                                    <i class="fas fa-eye me-1"></i> Detail
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

    <script>
    window.dashboardData = {
        chartData: {
            labels: <?= json_encode($chartData['labels']) ?>,
            data: <?= json_encode($chartData['data']) ?>
        }
    };
    </script>

    <script src="../assets/js/admin.js"></script>
</body>

</html>
</body>

</html>