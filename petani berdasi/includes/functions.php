<?php
// Fungsi untuk mencegah XSS
function sanitize_input($data) {
return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fungsi untuk memformat tanggal
function formatDate($dateString) {
$date = new DateTime($dateString);
$now = new DateTime();
$diff = $now->diff($date);

if ($diff->y > 0) {
return $diff->y . ' tahun lalu';
} elseif ($diff->m > 0) {
return $diff->m . ' bulan lalu';
} elseif ($diff->d > 0) {
return $diff->d . ' hari lalu';
} elseif ($diff->h > 0) {
return $diff->h . ' jam lalu';
} elseif ($diff->i > 0) {
return $diff->i . ' menit lalu';
} else {
return 'Baru saja';
}
}

// Fungsi untuk validasi CSRF token
function validateCSRFToken($token) {
if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
die('Invalid CSRF token');
}
}

// Fungsi untuk memeriksa kepemilikan produk
function isProductOwner($conn, $productId, $userId) {
$stmt = $conn->prepare("SELECT user_id FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
return false;
}

$product = $result->fetch_assoc();
return $product['user_id'] == $userId;
}

// Fungsi untuk mendapatkan komentar produk
function getProductComments($conn, $productId) {
$query = "SELECT c.*, u.username, u.foto_profil
FROM comments c
JOIN users u ON c.user_id = u.id
WHERE c.product_id = ?
ORDER BY c.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $productId);
$stmt->execute();
return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fungsi untuk menambahkan komentar
function addComment($conn, $productId, $userId, $comment) {
$query = "INSERT INTO comments (product_id, user_id, content) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iis", $productId, $userId, $comment);
return $stmt->execute();
}

// Fungsi untuk menangani like/unlike produk
function toggleProductLike($conn, $productId, $userId, $action) {
if ($action === 'like') {
$query = "INSERT INTO likes (product_id, user_id) VALUES (?, ?)";
} else {
$query = "DELETE FROM likes WHERE product_id = ? AND user_id = ?";
}

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $productId, $userId);
return $stmt->execute();
}

// Fungsi untuk menambahkan produk ke keranjang
function addToCart($conn, $productId, $userId) {
// Cek apakah produk sudah ada di keranjang
$checkQuery = "SELECT id FROM cart WHERE product_id = ? AND user_id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("ii", $productId, $userId);
$checkStmt->execute();

if ($checkStmt->get_result()->num_rows > 0) {
return ['success' => false, 'message' => 'Produk sudah ada di keranjang'];
}

// Tambahkan ke keranjang
$insertQuery = "INSERT INTO cart (product_id, user_id, quantity) VALUES (?, ?, 1)";
$insertStmt = $conn->prepare($insertQuery);
$insertStmt->bind_param("ii", $productId, $userId);

if ($insertStmt->execute()) {
return ['success' => true];
} else {
return ['success' => false, 'message' => 'Gagal menambahkan ke keranjang'];
}
}

// Fungsi untuk menghapus produk
function deleteProduct($conn, $productId, $userId) {
// Verifikasi kepemilikan produk
if (!isProductOwner($conn, $productId, $userId)) {
return ['success' => false, 'message' => 'Anda tidak memiliki izin untuk menghapus produk ini'];
}

// Mulai transaksi
$conn->begin_transaction();

try {
// Hapus likes terkait
$conn->query("DELETE FROM likes WHERE product_id = $productId");

// Hapus komentar terkait
$conn->query("DELETE FROM comments WHERE product_id = $productId");

// Hapus dari keranjang
$conn->query("DELETE FROM cart WHERE product_id = $productId");

// Hapus produk
$conn->query("DELETE FROM products WHERE id = $productId");

$conn->commit();
return ['success' => true];
} catch (Exception $e) {
$conn->rollback();
return ['success' => false, 'message' => 'Gagal menghapus produk: ' . $e->getMessage()];
}
}

// Fungsi untuk mendapatkan jumlah item di keranjang
function getCartCount($conn, $userId) {
$query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
return $result->fetch_assoc()['count'];
}
function isLoggedIn() {
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
return isset($_SESSION['user_id']);
}
function is_admin() {
return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_admin() {
if (!is_admin()) {
header("Location: ../dashboard.php");
exit();
}
}
function log_action($action, $user_id = null, $details = '') {
global $conn;
$ip = $_SERVER['REMOTE_ADDR'];
$conn->query("INSERT INTO activity_logs (user_id, action, details, ip_address)
VALUES ($user_id, '$action', '$details', '$ip')");
}// Di functions.php
function base_url($path = '') {
return BASE_URL . '/' . ltrim($path, '/');
}