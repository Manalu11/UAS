<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = $_GET['id'];

// Check if product belongs to current user before deleting
$query = "DELETE FROM products WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $product_id, $_SESSION['user_id']);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Produk berhasil dihapus!";
} else {
    $_SESSION['error_message'] = "Gagal menghapus produk: " . $conn->error;
}

header("Location: index.php");
exit();
?>