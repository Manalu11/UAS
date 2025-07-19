<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan']);
    exit();
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$user_id = $_SESSION['user_id'];

if ($product_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Product ID tidak valid']);
    exit();
}

$checkQuery = "SELECT id FROM keranjang WHERE product_id = $product_id AND user_id = $user_id";
$checkResult = mysqli_query($conn, $checkQuery);

if (!$checkResult || mysqli_num_rows($checkResult) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Item keranjang tidak ditemukan atau bukan milik Anda']);
    exit();
}

$deleteQuery = "DELETE FROM keranjang WHERE product_id = $product_id AND user_id = $user_id";
if (mysqli_query($conn, $deleteQuery)) {
    $countQuery = mysqli_query($conn, "SELECT SUM(quantity) as total FROM keranjang WHERE user_id = $user_id");
    $countResult = mysqli_fetch_assoc($countQuery);
    $cartCount = $countResult['total'] ?? 0;
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Item berhasil dihapus dari keranjang',
        'cartCount' => (int)$cartCount
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus item dari keranjang']);
}

mysqli_close($conn);
?>