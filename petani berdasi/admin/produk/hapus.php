<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil ID produk dari URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) {
    header("Location: index.php");
    exit();
}

// Cek apakah produk ada
$product = querySingle("SELECT id, image_url FROM products WHERE id = ?", [$product_id], 'i');
if (!$product) {
    header("Location: index.php");
    exit();
}

// Hapus produk (gunakan transaksi untuk integritas data)
$conn->begin_transaction();

try {
    // Hapus likes terkait
    $conn->query("DELETE FROM likes WHERE product_id = $product_id");
    
    // Hapus komentar terkait
    $conn->query("DELETE FROM comments WHERE product_id = $product_id");
    
    // Hapus dari keranjang
    $conn->query("DELETE FROM cart WHERE product_id = $product_id");
    
    // Hapus produk
    $conn->query("DELETE FROM products WHERE id = $product_id");
    
    // Hapus gambar jika bukan default
    if ($product['image_url'] && strpos($product['image_url'], 'default.jpg') === false) {
        $image_path = '../../' . $product['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $conn->commit();
    $_SESSION['success_message'] = 'Produk berhasil dihapus';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Gagal menghapus produk: ' . $e->getMessage();
}

header("Location: index.php");
exit();
?>