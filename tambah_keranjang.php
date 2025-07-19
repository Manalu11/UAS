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

$checkProduct = mysqli_query($conn, "SELECT id, judul, harga, user_id FROM posts WHERE id = $product_id");
if (!$checkProduct || mysqli_num_rows($checkProduct) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan']);
    exit();
}

$product = mysqli_fetch_assoc($checkProduct);

if ($product['user_id'] == $user_id) {
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak dapat menambahkan produk sendiri ke keranjang']);
    exit();
}

$createTableQuery = "CREATE TABLE IF NOT EXISTS keranjang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES posts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
)";

if (!mysqli_query($conn, $createTableQuery)) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat tabel keranjang']);
    exit();
}

$checkCart = mysqli_query($conn, "SELECT id, quantity FROM keranjang WHERE user_id = $user_id AND product_id = $product_id");

if (mysqli_num_rows($checkCart) > 0) {
    $cartItem = mysqli_fetch_assoc($checkCart);
    $newQuantity = $cartItem['quantity'] + 1;
    
    $updateQuery = "UPDATE keranjang SET quantity = $newQuantity WHERE id = " . $cartItem['id'];
    if (mysqli_query($conn, $updateQuery)) {
        $countQuery = mysqli_query($conn, "SELECT SUM(quantity) as total FROM keranjang WHERE user_id = $user_id");
        $countResult = mysqli_fetch_assoc($countQuery);
        $cartCount = $countResult['total'] ?? 0;
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Produk berhasil ditambahkan ke keranjang',
            'cartCount' => (int)$cartCount,
            'action' => 'updated'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui keranjang']);
    }
} else {
    $insertQuery = "INSERT INTO keranjang (user_id, product_id, quantity) VALUES ($user_id, $product_id, 1)";
    if (mysqli_query($conn, $insertQuery)) {
        $countQuery = mysqli_query($conn, "SELECT SUM(quantity) as total FROM keranjang WHERE user_id = $user_id");
        $countResult = mysqli_fetch_assoc($countQuery);
        $cartCount = $countResult['total'] ?? 0;
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Produk berhasil ditambahkan ke keranjang',
            'cartCount' => (int)$cartCount,
            'action' => 'added'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan produk ke keranjang']);
    }
}

mysqli_close($conn);
?>