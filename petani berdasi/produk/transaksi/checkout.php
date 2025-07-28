<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get user data
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $_SESSION['user_id']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

// Get cart items
$cartQuery = "SELECT c.*, p.name, p.price, p.image_url 
              FROM cart c 
              JOIN products p ON c.product_id = p.id 
              WHERE c.user_id = ?";
$cartStmt = $conn->prepare($cartQuery);
$cartStmt->bind_param("i", $_SESSION['user_id']);
$cartStmt->execute();
$cartItems = $cartStmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create order
        $orderQuery = "INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method) 
                       VALUES (?, ?, 'pending', ?, ?)";
        $orderStmt = $conn->prepare($orderQuery);
        $orderStmt->bind_param("idss", $_SESSION['user_id'], $total, $_POST['shipping_address'], $_POST['payment_method']);
        $orderStmt->execute();
        $orderId = $conn->insert_id;
        
        // Add order items
        foreach ($cartItems as $item) {
            $orderItemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                              VALUES (?, ?, ?, ?)";
            $orderItemStmt = $conn->prepare($orderItemQuery);
            $orderItemStmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
            $orderItemStmt->execute();
        }
        
        // Clear cart
        $clearCartQuery = "DELETE FROM cart WHERE user_id = ?";
        $clearCartStmt = $conn->prepare($clearCartQuery);
        $clearCartStmt->bind_param("i", $_SESSION['user_id']);
        $clearCartStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        header("Location: invoice.php?id=$orderId");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Checkout gagal: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Checkout</h2>

    <?php if (empty($cartItems)): ?>
    <div class="alert alert-info">Keranjang belanja Anda kosong.</div>
    <a href="../beranda.php" class="btn btn-primary">Lanjut Belanja</a>
    <?php else: ?>
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informasi Pengiriman</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="name"
                                value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email"
                                value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">No. Telepon</label>
                            <input type="tel" class="form-control" id="phone"
                                value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="shipping_address" class="form-label">Alamat Pengiriman</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"
                                required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="payment_method" class="form-label">Metode Pembayaran</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="bank_transfer">Transfer Bank</option>
                                <option value="credit_card">Kartu Kredit</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="cod">COD (Bayar di Tempat)</option>
                            </select>
                        </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Ringkasan Pesanan</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($cartItems as $item): ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <div>
                                <?= htmlspecialchars($item['name']) ?>
                                <small class="text-muted">x<?= $item['quantity'] ?></small>
                            </div>
                            <span>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></span>
                        </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between fw-bold">
                            <span>Total</span>
                            <span>Rp <?= number_format($total, 0, ',', '.') ?></span>
                        </li>
                    </ul>

                    <button type="submit" class="btn btn-primary w-100 mt-3">Buat Pesanan</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>