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
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Query untuk laporan penjualan
$where = "WHERE t.created_at BETWEEN ? AND ?";
$params = [$start_date, $end_date];
$types = "ss";

if (!empty($status)) {
    $where .= " AND t.status = ?";
    $params[] = $status;
    $types .= "s";
}

$transactions = query("SELECT t.id, t.invoice_number, t.amount, t.status, t.created_at, 
                      u.username, u.email 
                      FROM transactions t
                      JOIN users u ON t.user_id = u.id
                      $where
                      ORDER BY t.created_at DESC", $params, $types)->fetch_all(MYSQLI_ASSOC);

// Hitung total penjualan
$total_sales = querySingle("SELECT SUM(amount) as total FROM transactions t $where", $params, $types)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - Petani Berdasi</title>
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
                <h2>Laporan Penjualan</h2>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak Laporan
                </button>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                    value="<?= $start_date ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="end_date" class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                    value="<?= $end_date ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua Status</option>
                                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending
                                    </option>
                                    <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed
                                    </option>
                                    <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end mb-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Penjualan</h5>
                            <h2 class="card-text">Rp <?= number_format($total_sales, 0, ',', '.') ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Total Transaksi</h5>
                            <h2 class="card-text"><?= count($transactions) ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-info mb-3">
                        <div class="card-body">
                            <h5 class="card-title">Rata-rata Transaksi</h5>
                            <h2 class="card-text">Rp
                                <?= count($transactions) > 0 ? number_format($total_sales / count($transactions), 0, ',', '.') : 0 ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>No. Invoice</th>
                                    <th>Tanggal</th>
                                    <th>Pelanggan</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td><?= htmlspecialchars($transaction['invoice_number']) ?></td>
                                    <td><?= date('d M Y H:i', strtotime($transaction['created_at'])) ?></td>
                                    <td>
                                        <?= htmlspecialchars($transaction['username']) ?><br>
                                        <small><?= htmlspecialchars($transaction['email']) ?></small>
                                    </td>
                                    <td>Rp <?= number_format($transaction['amount'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $transaction['status'] == 'completed' ? 'success' : 
                                            ($transaction['status'] == 'paid' ? 'primary' : 
                                            ($transaction['status'] == 'pending' ? 'warning' : 'danger')) 
                                        ?>">
                                            <?= ucfirst($transaction['status']) ?>
                                        </span>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    // Anda bisa menambahkan grafik penjualan di sini jika diperlukan
    </script>
</body>

</html>