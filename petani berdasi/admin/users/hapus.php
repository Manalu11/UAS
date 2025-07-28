<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil ID user dari URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    header("Location: index.php");
    exit();
}

// Cek apakah user ada
$user = querySingle("SELECT id FROM users WHERE id = ?", [$user_id], 'i');
if (!$user) {
    header("Location: index.php");
    exit();
}

// Hapus user (gunakan transaksi untuk integritas data)
$conn->begin_transaction();

try {
    // Hapus produk yang dimiliki user
    $conn->query("DELETE FROM products WHERE user_id = $user_id");
    
    // Hapus komentar user
    $conn->query("DELETE FROM comments WHERE user_id = $user_id");
    
    // Hapus likes user
    $conn->query("DELETE FROM likes WHERE user_id = $user_id");
    
    // Hapus dari keranjang
    $conn->query("DELETE FROM cart WHERE user_id = $user_id");
    
    // Hapus transaksi user
    $conn->query("DELETE FROM transactions WHERE user_id = $user_id");
    
    // Hapus user
    $conn->query("DELETE FROM users WHERE id = $user_id");
    
    $conn->commit();
    $_SESSION['success_message'] = 'User berhasil dihapus';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Gagal menghapus user: ' . $e->getMessage();
}

header("Location: index.php");
exit();
?>