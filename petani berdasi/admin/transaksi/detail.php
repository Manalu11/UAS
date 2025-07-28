<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil ID transaksi dari URL
$transaction_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($transaction_id <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil data transaksi
$transaction = querySingle("SELECT t.*, u.username, u.email, u.phone, 
                          a.address, a.city, a.postal_code, a.province
                          FROM transactions t
                          JOIN users u ON t.user_id = u.id
                          LEFT JOIN addresses a ON t.address_id = a.id
                          WHERE t.id = ?", [$transaction_id], 'i');

if (!$transaction) {
    header("Location: index.php");
    exit();
}

// Ambil item transaksi
$items = query("SELECT ti.*, p.name, p.image_url 
               FROM transaction_items ti
               JOIN products p ON ti.product_id = p.id
               WHERE ti.transaction_id = ?", [$transaction_id], 'i')->fetch_all(MYSQLI_ASSOC);

// Update status transaksi jika ada request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $new_status = sanitize_input($_POST['status']);
    
    // Validasi status
    $allowed_statuses = ['pending', 'paid', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $query = "UPDATE transactions SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $new_status, $transaction_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Status transaksi berhasil diperbarui';
            header("Location: detail.php?id=$transaction_id");
            exit();
        } else {
            $_SESSION['error_message'] = 'Gagal memperbarui status transaksi';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi - Petani Berdasi</title>
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
                <h2>Detail Transaksi #<?= htmlspecialchars($transaction['invoice_number']) ?></h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
            </div>

            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error_message'] ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Item Transaksi</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th>Harga</th>
                                            <th>Qty</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?= htmlspecialchars($item['image_url']) ?>"
                                                        class="img-thumbnail me-3" width="60" height="60">
                                                    <div>
                                                        <h6 class="mb-0"><?= htmlspecialchars($item['name']) ?></h6>
                                                        <small>ID: <?= $item['product_id'] ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                                            <td><?= $item['quantity'] ?></td>
                                            <td>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total</th>
                                            <th>Rp <?= number_format($transaction['amount'], 0, ',', '.') ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>Informasi Transaksi</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="pending"
                                            <?= $transaction['status'] == 'pending' ? 'selected' : '' ?>>Pending
                                        </option>
                                        <option value="paid" <?= $transaction['status'] == 'paid' ? 'selected' : '' ?>>
                                            Paid</option>
                                        <option value="completed"
                                            <?= $transaction['status'] == 'completed' ? 'selected' : '' ?>>Completed
                                        </option>
                                        <option value="cancelled"
                                            <?= $transaction['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled
                                        </option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Tanggal</label>
                                    <p><?= date('d M Y H:i', strtotime($transaction['created_at'])) ?></p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Metode Pembayaran</label>
                                    <p><?= htmlspecialchars($transaction['payment_method'] ?? 'N/A') ?></p>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-save"></i> Update Status
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Informasi Pelanggan</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Nama</label>
                                <p><?= htmlspecialchars($transaction['username']) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <p><?= htmlspecialchars($transaction['email']) ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telepon</label>
                                <p><?= htmlspecialchars($transaction['phone'] ?? 'N/A') ?></p>
                            </div>
                            <?php if (!empty($transaction['address'])): ?>
                            <div class="mb-3">
                                <label class="form-label">Alamat Pengiriman</label>
                                <p>
                                    <?= htmlspecialchars($transaction['address']) ?><br>
                                    <?= htmlspecialchars($transaction['city']) ?>,
                                    <?= htmlspecialchars($transaction['postal_code']) ?><br>
                                    <?= htmlspecialchars($transaction['province']) ?>
                                </p>
                            </div>
                            <?php endif; ?>
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