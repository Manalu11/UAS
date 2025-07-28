<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../users/transaksi.php");
    exit();
}

$orderId = $_GET['id'];

// Get order data
$orderQuery = "SELECT o.*, u.full_name, u.email, u.phone 
               FROM orders o 
               JOIN users u ON o.user_id = u.id 
               WHERE o.id = ? AND o.user_id = ?";
$orderStmt = $conn->prepare($orderQuery);
$orderStmt->bind_param("ii", $orderId, $_SESSION['user_id']);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: transaksi.php");
    exit();
}

// Get order items
$itemsQuery = "SELECT oi.*, p.name, p.image_url 
               FROM order_items oi 
               JOIN products p ON oi.product_id = p.id 
               WHERE oi.order_id = ?";
$itemsStmt = $conn->prepare($itemsQuery);
$itemsStmt->bind_param("i", $orderId);
$itemsStmt->execute();
$orderItems = $itemsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Invoice #<?= $order['id'] ?></h4>
                <span class="badge bg-light text-dark"><?= ucfirst($order['status']) ?></span>
            </div>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Informasi Pelanggan</h5>
                    <p>
                        <strong>Nama:</strong> <?= htmlspecialchars($order['full_name']) ?><br>
                        <strong>Email:</strong> <?= htmlspecialchars($order['email']) ?><br>
                        <strong>Telepon:</strong> <?= htmlspecialchars($order['phone']) ?>
                    </p>
                </div>

                <div class="col-md-6 text-end">
                    <h5>Informasi Pesanan</h5>
                    <p>
                        <strong>Tanggal:</strong> <?= formatDate($order['created_at']) ?><br>
                        <strong>Metode Pembayaran:</strong>
                        <?= ucfirst(str_replace('_', ' ', $order['payment_method'])) ?><br>
                        <strong>Alamat Pengiriman:</strong> <?= htmlspecialchars($order['shipping_address']) ?>
                    </p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= htmlspecialchars($item['image_url']) ?>" width="60" height="60"
                                        class="me-3" alt="<?= htmlspecialchars($item['name']) ?>">
                                    <div><?= htmlspecialchars($item['name']) ?></div>
                                </div>
                            </td>
                            <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>Rp <?= number_format($order['total_amount'], 0, ',', '.') ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-4">
                <h5>Status Pesanan</h5>
                <div class="progress">
                    <?php 
                    $steps = [
                        'pending' => ['Pending', 'active'],
                        'processing' => ['Diproses', $order['status'] === 'processing' || $order['status'] === 'shipped' || $order['status'] === 'completed' ? 'active' : ''],
                        'shipped' => ['Dikirim', $order['status'] === 'shipped' || $order['status'] === 'completed' ? 'active' : ''],
                        'completed' => ['Selesai', $order['status'] === 'completed' ? 'active' : '']
                    ];
                    
                    foreach ($steps as $key => $step) {
                        $class = $step[1] ? 'bg-success' : 'bg-secondary';
                        echo '<div class="progress-bar '.$class.'" style="width: 25%">'.$step[0].'</div>';
                    }
                    ?>
                </div>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="../user/transaksi.php" class="btn btn-outline-primary">Kembali ke Daftar Transaksi</a>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Cetak Invoice
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<style>
@media print {
    body * {
        visibility: hidden;
    }

    .card,
    .card * {
        visibility: visible;
    }

    .card {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        border: none;
    }

    .progress {
        display: none;
    }

    .btn {
        display: none;
    }
}
</style>