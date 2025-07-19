<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_id = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

if ($cart_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID keranjang tidak valid']);
    exit();
}

if ($quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Jumlah harus lebih dari 0']);
    exit();
}

try {
    // Cek apakah item keranjang milik user
    $queryCheck = mysqli_query($conn, "SELECT id FROM keranjang WHERE id = $cart_id AND user_id = $user_id");
    
    if (!$queryCheck || mysqli_num_rows($queryCheck) == 0) {
        echo json_encode(['success' => false, 'message' => 'Item keranjang tidak ditemukan']);
        exit();
    }
    
    // Update quantity
    $queryUpdate = mysqli_query($conn, "UPDATE keranjang SET quantity = $quantity, updated_at = NOW() WHERE id = $cart_id AND user_id = $user_id");
    
    if (!$queryUpdate) {
        echo json_encode(['success' => false, 'message' => 'Gagal memperbarui keranjang']);
        exit();
    }
    
    // Hitung total item di keranjang setelah diupdate
    $queryCount = mysqli_query($conn, "SELECT SUM(quantity) as total FROM keranjang WHERE user_id = $user_id");
    $cartCount = mysqli_fetch_assoc($queryCount)['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'message' => 'Keranjang berhasil diperbarui',
        'cartCount' => $cartCount
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan sistem']);
}
?>