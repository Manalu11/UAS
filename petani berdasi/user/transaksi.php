<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Debug mode - uncomment untuk debugging
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Handle AJAX requests FIRST before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clean any output buffer
    if (ob_get_level()) {
        ob_clean();
    }
    
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update_quantity':
                    $cartId = intval($_POST['cart_id']);
                    $action = sanitize_input($_POST['quantity_action']);
                    
                    // Get current quantity and verify ownership
                    $query = "SELECT quantity FROM cart WHERE id = ? AND user_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ii", $cartId, $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $currentQty = $result->fetch_assoc()['quantity'];
                        
                        if ($action === 'increase') {
                            $newQty = $currentQty + 1;
                        } else if ($action === 'decrease') {
                            $newQty = max(1, $currentQty - 1);
                        } else {
                            throw new Exception('Invalid action');
                        }
                        
                        // Update quantity
                        $updateQuery = "UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?";
                        $updateStmt = $conn->prepare($updateQuery);
                        $updateStmt->bind_param("iii", $newQty, $cartId, $_SESSION['user_id']);
                        
                        if ($updateStmt->execute()) {
                            // Get updated item details for subtotal calculation
                            $detailQuery = "SELECT c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ?";
                            $detailStmt = $conn->prepare($detailQuery);
                            $detailStmt->bind_param("i", $cartId);
                            $detailStmt->execute();
                            $itemDetail = $detailStmt->get_result()->fetch_assoc();
                            
                            echo json_encode([
                                'success' => true, 
                                'new_quantity' => $newQty,
                                'price' => $itemDetail['price'],
                                'subtotal' => $itemDetail['price'] * $newQty
                            ]);
                        } else {
                            throw new Exception('Gagal mengupdate jumlah');
                        }
                    } else {
                        throw new Exception('Item tidak ditemukan');
                    }
                    break;
                    
                case 'remove_item':
                    $cartId = intval($_POST['cart_id']);
                    
                    $query = "DELETE FROM cart WHERE id = ? AND user_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("ii", $cartId, $_SESSION['user_id']);
                    
                    if ($stmt->execute() && $stmt->affected_rows > 0) {
                        echo json_encode(['success' => true]);
                    } else {
                        throw new Exception('Gagal menghapus item atau item tidak ditemukan');
                    }
                    break;
                    
                default:
                    throw new Exception('Action tidak valid');
            }
        } else {
            throw new Exception('Action tidak ditemukan');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
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

include '../includes/header.php';
?>

<div class="container mt-5">
    <h2 class="mb-4">Keranjang Belanja</h2>

    <?php if (empty($cartItems)): ?>
    <div class="alert alert-info">Keranjang belanja Anda kosong.</div>
    <a href="../user/beranda.php" class="btn btn-primary">Lanjut Belanja</a>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table" id="cart-table">
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
                $subtotal = $item['price'] * $item['quantity'];
                ?>
                <tr data-cart-id="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                    <td>
                        <div class="d-flex align-items-center">
                            <img src="<?= $imagePath ?>" width="60" height="60" class="me-3"
                                alt="<?= htmlspecialchars($item['name']) ?>"
                                onerror="this.src='../assets/images/no-image.png'">
                            <div><?= htmlspecialchars($item['name']) ?></div>
                        </div>
                    </td>
                    <td class="item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
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
                    <td class="item-subtotal">Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
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
                    <td colspan="2"><strong id="total-amount">Rp <?= number_format($total, 0, ',', '.') ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex justify-content-between mt-4">
        <a href="../beranda.php" class="btn btn-outline-primary">Lanjut Belanja</a>
        <a href="checkout.php" class="btn btn-primary">Proses Checkout</a>
    </div>
    <?php endif; ?>
</div>

<!-- Loading overlay -->
<div id="loading-overlay"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white;">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Pastikan jQuery sudah loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery belum dimuat!');
}

$(document).ready(function() {
    console.log('Document ready - Cart functions initialized');

    // Update quantity dengan event delegation untuk element dinamis
    $(document).on('click', '.update-quantity', function(e) {
        e.preventDefault();
        console.log('Update quantity clicked');

        const cartId = $(this).data('id');
        const action = $(this).data('action');
        const row = $(this).closest('tr');
        const quantityInput = row.find('.quantity-input');
        const currentQty = parseInt(quantityInput.val());

        console.log('Cart ID:', cartId, 'Action:', action, 'Current Qty:', currentQty);

        // Prevent decrease below 1
        if (action === 'decrease' && currentQty <= 1) {
            console.log('Cannot decrease below 1');
            return;
        }

        // Disable buttons during request
        $(this).prop('disabled', true);
        showLoading();

        $.ajax({
            url: window.location.href, // Use same page
            type: 'POST',
            data: {
                action: 'update_quantity',
                cart_id: cartId,
                quantity_action: action
            },
            dataType: 'json',
            timeout: 10000, // 10 second timeout
            success: function(response) {
                console.log('AJAX Response:', response);
                hideLoading();
                $('.update-quantity').prop('disabled', false);

                if (response.success) {
                    // Update quantity display
                    quantityInput.val(response.new_quantity);

                    // Update subtotal using response data
                    const newSubtotal = response.subtotal;
                    row.find('.item-subtotal').text('Rp ' + formatNumber(newSubtotal));

                    // Update total
                    updateTotal();

                    console.log('Quantity updated successfully');
                } else {
                    alert(response.message || 'Terjadi kesalahan saat mengupdate quantity');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response Text:', xhr.responseText);
                hideLoading();
                $('.update-quantity').prop('disabled', false);
                alert('Terjadi kesalahan jaringan: ' + error);
            }
        });
    });

    // Remove item dengan event delegation
    $(document).on('click', '.remove-item', function(e) {
        e.preventDefault();
        console.log('Remove item clicked');

        if (!confirm('Yakin ingin menghapus item ini dari keranjang?')) {
            return;
        }

        const cartId = $(this).data('id');
        const row = $(this).closest('tr');

        console.log('Removing cart ID:', cartId);

        showLoading();

        $.ajax({
            url: window.location.href,
            type: 'POST',
            data: {
                action: 'remove_item',
                cart_id: cartId
            },
            dataType: 'json',
            timeout: 10000,
            success: function(response) {
                console.log('Remove Response:', response);
                hideLoading();

                if (response.success) {
                    row.fadeOut(300, function() {
                        $(this).remove();
                        updateTotal();

                        // Check if cart is empty
                        if ($('#cart-table tbody tr:visible').length === 0) {
                            console.log('Cart is empty, reloading...');
                            setTimeout(function() {
                                location.reload();
                            }, 500);
                        }
                    });
                    console.log('Item removed successfully');
                } else {
                    alert(response.message || 'Terjadi kesalahan saat menghapus item');
                }
            },
            error: function(xhr, status, error) {
                console.error('Remove AJAX Error:', status, error);
                console.error('Response Text:', xhr.responseText);
                hideLoading();
                alert('Terjadi kesalahan jaringan: ' + error);
            }
        });
    });

    // Update total calculation
    function updateTotal() {
        let total = 0;
        $('#cart-table tbody tr:visible').each(function() {
            const price = parseInt($(this).data('price'));
            const quantity = parseInt($(this).find('.quantity-input').val());
            if (!isNaN(price) && !isNaN(quantity)) {
                total += price * quantity;
            }
        });
        $('#total-amount').text('Rp ' + formatNumber(total));
        console.log('Total updated:', total);
    }

    // Format number with thousand separator
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function showLoading() {
        $('#loading-overlay').show();
    }

    function hideLoading() {
        $('#loading-overlay').hide();
    }

    // Test AJAX functionality on page load
    console.log('Testing AJAX...');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Current URL:', window.location.href);
});
</script>

<?php include '../includes/footer.php'; ?>