<?php
header('Content-Type: application/json');
require 'koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);
$productId = $data['product_id'];
$userId = $_SESSION['user_id'] ?? 0; // Sesuaikan dengan sistem login Anda

// Cek apakah user sudah like
$check = mysqli_query($conn, "SELECT * FROM likes WHERE product_id = $productId AND user_id = $userId");

if (mysqli_num_rows($check) > 0) {
    // Unlike
    mysqli_query($conn, "DELETE FROM likes WHERE product_id = $productId AND user_id = $userId");
    mysqli_query($conn, "UPDATE produk SET jumlah_like = jumlah_like - 1 WHERE id = $productId");
    echo json_encode(['success' => true, 'action' => 'unliked']);
} else {
    // Like
    mysqli_query($conn, "INSERT INTO likes (product_id, user_id) VALUES ($productId, $userId)");
    mysqli_query($conn, "UPDATE produk SET jumlah_like = jumlah_like + 1 WHERE id = $productId");
    echo json_encode(['success' => true, 'action' => 'liked']);
}
?>