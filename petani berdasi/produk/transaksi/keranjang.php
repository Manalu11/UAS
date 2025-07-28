<?php
session_start();
require_once('../../config/database.php');
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get cart items
$query = "SELECT c.*, p.name, p.price, p.image_url 
          FROM cart c 
          JOIN products p ON c.product_id = p.id 
          WHERE c.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$cartItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

include '../../includes/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Keranjang Belanja</h2>

    <?php if (empty($cartItems)): ?>
    <div class="alert alert-info">Keranjang belanja Anda kosong.</div>
    <a href="../produk/index.php" class="btn btn-primary">Lanjut Belanja</a>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Subtotal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                <?php 
                // Pastikan path gambar benar
                $imagePath = '../' . ltrim(htmlspecialchars($item['image_url']), '/');
                ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="<?= file_exists($imagePath) ? $imagePath : 'assets/images/logo.jpg' ?>" width="60"
                                height="60" class="me-3" alt="<?= htmlspecialchars($item['name']) ?>">
                            <div><?= htmlspecialchars($item['name']) ?></div>
                        </div>
                    </td>
                    <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td>
                        <div class="input-group" style="width: 120px;">
                            <button class="btn btn-outline-secondary update-quantity" data-id="<?= $item['id'] ?>"
                                data-action="decrease">-</button>
                            <input type="text" class="form-control text-center quantity-input"
                                value="<?= $item['quantity'] ?>" readonly>
                            <button class="btn btn-outline-secondary update-quantity" data-id="<?= $item['id'] ?>"
                                data-action="increase">+</button>
                        </div>
                    </td>
                    <td>Rp <?= number_format($item['price'] * $item['quantity'], 0, ',', '.') ?></td>
                    <td>
                        <button class="btn btn-danger btn-sm remove-item" data-id="<?= $item['id'] ?>">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td colspan="2"><strong>Rp <?= number_format($total, 0, ',', '.') ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="beranda.php" class="btn btn-outline-primary">Lanjut Belanja</a>
        <a href="../produk/transaksi/checkout" class="btn btn-primary">Proses Checkout</a>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Update quantity
    $('.update-quantity').click(function() {
        const cartId = $(this).data('id');
        const action = $(this).data('action');

        $.post('../produk/edit.php', {
            id: cartId,
            action: action
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.message);
            }
        }, 'json');
    });

    // Remove item
    $('.remove-item').click(function() {
        if (confirm('Apakah Anda yakin ingin menghapus item ini?')) {
            const cartId = $(this).data('id');

            $.post('produk/hapus.php', {
                id: cartId
            }, function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            }, 'json');
        }
    });
});
</script>