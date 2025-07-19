<?php
session_start();
require 'koneksi.php';

header('Content-Type: application/json');

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid']);
    exit;
}

// Ambil data keranjang dari database
$userId = $_SESSION['user_id'];
$queryCart = mysqli_query($conn, 
    "SELECT k.*, p.judul, p.harga, p.user_id as seller_id, u.nomor_wa, u.username as seller_name 
     FROM keranjang k
     JOIN posts p ON k.product_id = p.id
     JOIN users u ON p.user_id = u.id
     WHERE k.user_id = $userId");

if (mysqli_num_rows($queryCart) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Keranjang kosong']);
    exit;
}

// Kelompokkan item berdasarkan penjual
$ordersBySeller = [];
while ($item = mysqli_fetch_assoc($queryCart)) {
    $sellerId = $item['seller_id'];
    if (!isset($ordersBySeller[$sellerId])) {
        $ordersBySeller[$sellerId] = [
            'seller_name' => $item['seller_name'],
            'nomor_wa' => $item['nomor_wa'],
            'items' => []
        ];
    }
    $ordersBySeller[$sellerId]['items'][] = $item;
}

// Format pesan WhatsApp untuk setiap penjual
$waLinks = [];
$customMessage = isset($_POST['custom_message']) ? $_POST['custom_message'] : 'Saya ingin memesan produk berikut:';

foreach ($ordersBySeller as $sellerId => $sellerData) {
    if (empty($sellerData['nomor_wa'])) {
        continue; // Skip penjual tanpa nomor WhatsApp
    }

    $message = "Halo *" . $sellerData['seller_name'] . "*,\n";
    $message .= "$customMessage\n\n";
    
    $total = 0;
    foreach ($sellerData['items'] as $item) {
        $message .= "➤ *" . $item['judul'] . "*\n";
        $message .= "   Harga: Rp " . number_format($item['harga'], 0, ',', '.') . "\n";
        $message .= "   Jumlah: " . $item['quantity'] . "\n\n";
        $total += $item['harga'] * $item['quantity'];
    }
    
    $message .= "Total: *Rp " . number_format($total, 0, ',', '.') . "*\n\n";
    $message .= "Mohon konfirmasi ketersediaan stok dan total pembayaran. Terima kasih!";
    
    // Encode pesan untuk URL WhatsApp
    $encodedMessage = urlencode($message);
    $nomorWa = preg_replace('/[^0-9]/', '', $sellerData['nomor_wa']);
    
    // Format nomor WA (ubah 08 menjadi 628)
    if (substr($nomorWa, 0, 1) === '0') {
        $nomorWa = '62' . substr($nomorWa, 1);
    }
    
    $waLinks[] = [
        'seller_id' => $sellerId,
        'url' => "https://wa.me/$nomorWa?text=$encodedMessage"
    ];
}

// Kosongkan keranjang setelah checkout
mysqli_query($conn, "DELETE FROM keranjang WHERE user_id = $userId");

// Response untuk frontend
echo json_encode([
    'status' => 'success',
    'orders_count' => count($waLinks),
    'wa_links' => $waLinks,
    'warning' => count($ordersBySeller) > count($waLinks) ? 
        'Beberapa penjual tidak memiliki nomor WhatsApp' : null
]);
?>