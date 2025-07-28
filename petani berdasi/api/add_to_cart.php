<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json'); // Tambahkan header JSON

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit;
}

// Validasi input
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak valid']);
    exit;
}

$productId = (int)$_POST['product_id'];
$userId = (int)$_SESSION['user_id'];

try {
    // Cek apakah produk ada di database
    $productCheck = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $productCheck->bind_param("i", $productId);
    $productCheck->execute();
    
    if (!$productCheck->get_result()->num_rows) {
        echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
        exit;
    }

    // Cek apakah produk sudah ada di keranjang user
    $checkQuery = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $checkQuery->bind_param("ii", $userId, $productId);
    $checkQuery->execute();
    $existing = $checkQuery->get_result()->fetch_assoc();

    if ($existing) {
        // Update quantity jika sudah ada
        $updateQuery = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?");
        $updateQuery->bind_param("i", $existing['id']);
        $updateQuery->execute();
    } else {
        // Tambah baru jika belum ada
        $insertQuery = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $insertQuery->bind_param("ii", $userId, $productId);
        $insertQuery->execute();
    }

    // Hitung total item di keranjang
    $countQuery = $conn->prepare("SELECT COUNT(*) as total_items, SUM(quantity) as total_quantity FROM cart WHERE user_id = ?");
    $countQuery->bind_param("i", $userId);
    $countQuery->execute();
    $result = $countQuery->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'cart_count' => $result['total_quantity'] ?? 0,
        'message' => 'Produk berhasil ditambahkan ke keranjang'
    ]);

} catch (Exception $e) {
    // Log error untuk debugging
    error_log("Error in add_to_cart.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}