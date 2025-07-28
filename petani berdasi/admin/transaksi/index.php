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
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Query untuk transaksi
$where = [];
$params = [];
$types = '';

if (!empty($status)) {
    $where[] = "t.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($search)) {
    $where[] = "(t.invoice_number LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    $types .= 'sss';
}

if (!empty($start_date) && !empty($end_date)) {
    $where[] = "t.created_at BETWEEN ? AND ?";
    $params = array_merge($params, [$start_date, $end_date]);
    $types .= 'ss';
}

$where_clause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Hitung total transaksi
$totalQuery = "SELECT COUNT(*) as count FROM transactions t JOIN users u ON t.user_id = u.id $where_clause";
$totalResult = querySingle($totalQuery, $params, $types);
$totalTransactions = $totalResult['count'];
$totalPages = ceil($totalTransactions / $perPage);

// Ambil data transaksi
$transactions = query("SELECT t.id, t.invoice_number, t.amount, t.status, t.created_at, 
                      u.username, u.email 
                      FROM transactions t
                      JOIN users u ON t.user_id = u.id
                      $where_clause
                      ORDER BY t.created_at DESC
                      LIMIT ? OFFSET ?", 
                      array_merge($params, [$perPage, $offset]), 
                      $types . 'ii')->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Transaksi - Petani Berdasi</title>
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
                <h2>Manajemen Transaksi</h2>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="search" class="form-label">Cari</label>
                                <input type="text" class="form-control" id="search" name="search"
                                    value="<?= htmlspecialchars($search) ?>" placeholder="No. Invoice/Nama/Email">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Semua</option>
                                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending
                                    </option>
                                    <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid</option>
                                    <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed
                                    </option>
                                    <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="start_date" class="form-label">Tanggal Mulai</label>
                                <input type="date" class="form-control" id="start_date" name="start_date"
                                    value="<?= htmlspecialchars($start_date) ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="end_date" class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control" id="end_date" name="end_date"
                                    value="<?= htmlspecialchars($end_date) ?>">
                            </div>
                            <div class="col-md-1 d-flex align-items-end mb-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                        </div>
                    </form>
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
                                    <th>Aksi</th>
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
                                    <td>
                                        <a href="detail.php?id=<?= $transaction['id'] ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?= $page - 1 ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">
                                    &laquo; Sebelumnya
                                </a>
                            </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link"
                                    href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link"
                                    href="?page=<?= $page + 1 ?>&status=<?= urlencode($status) ?>&search=<?= urlencode($search) ?>&start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>">
                                    Selanjutnya &raquo;
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>