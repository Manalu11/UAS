<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil ID kategori dari URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($category_id <= 0) {
    header("Location: index.php");
    exit();
}

// Cek apakah kategori ada
$category = querySingle("SELECT id FROM categories WHERE id = ?", [$category_id], 'i');
if (!$category) {
    header("Location: index.php");
    exit();
}

// Hapus kategori (gunakan transaksi untuk integritas data)
$conn->begin_transaction();

try {
    // Update semua produk yang menggunakan kategori ini ke kategori default (misalnya ID 1)
    $conn->query("UPDATE products SET category_id = 1 WHERE category_id = $category_id");
    
    // Hapus kategori
    $conn->query("DELETE FROM categories WHERE id = $category_id");
    
    $conn->commit();
    $_SESSION['success_message'] = 'Kategori berhasil dihapus';
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = 'Gagal menghapus kategori: ' . $e->getMessage();
}

header("Location: index.php");
exit();
?>