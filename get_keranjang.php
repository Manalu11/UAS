<?php
session_start();
include 'koneksi.php';
header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['username'])) {
    echo json_encode(array(
        'status' => 'error', 
        'message' => 'Silakan login terlebih dahulu',
        'items' => array(),
        'cartCount' => 0
    ));
    exit();
}

$user_id = $_SESSION['user_id'];

// Query untuk mengambil data keranjang
$query = "SELECT k.id as cart_id, k.quantity, 
                 p.id, p.judul, p.harga, p.gambar_path, 
                 u.nomor_wa, u.username as seller_name
          FROM keranjang k
          JOIN posts p ON k.product_id = p.id
          JOIN users u ON p.user_id = u.id
          WHERE k.user_id = $user_id
          ORDER BY k.created_at DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Gagal mengambil data keranjang',
        'items' => array(),
        'cartCount' => 0
    ));
    exit();
}

$items = array();
$totalQuantity = 0;

while ($row = mysqli_fetch_assoc($result)) {
    // Format nomor WA (hilangkan karakter non-digit dan pastikan diawali 62)
    $nomor_wa = preg_replace('/\D/', '', $row['nomor_wa']);
    if (substr($nomor_wa, 0, 1) === '0') {
        $nomor_wa = '62' . substr($nomor_wa, 1);
    } elseif (substr($nomor_wa, 0, 2) !== '62') {
        $nomor_wa = '62' . $nomor_wa;
    }

    $items[] = array(
        'id' => $row['id'],
        'cart_id' => $row['cart_id'],
        'judul' => $row['judul'],
        'harga' => (int)$row['harga'],
        'gambar' => $row['gambar_path'],
        'quantity' => (int)$row['quantity'],
        'nomor_wa' => $nomor_wa,
        'seller_name' => $row['seller_name']
    );
    $totalQuantity += (int)$row['quantity'];
}

// Jika keranjang kosong
if (empty($items)) {
    echo json_encode(array(
        'status' => 'empty',
        'message' => 'Keranjang belanja kosong',
        'items' => array(),
        'cartCount' => 0
    ));
    exit();
}

// Jika berhasil
echo json_encode(array(
    'status' => 'success',
    'items' => $items,
    'cartCount' => $totalQuantity,
    'totalProducts' => count($items)
));

mysqli_close($conn);
?>