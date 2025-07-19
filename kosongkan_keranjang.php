<?php
session_start();

// Pastikan hanya menerima request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['status' => 'error', 'message' => 'Method not allowed']));
}

// Set header JSON
header('Content-Type: application/json');

// Validasi CSRF token
if (!isset($_POST['csrf_token'])) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Token CSRF tidak ditemukan']));
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid']));
}

// Kosongkan keranjang
$_SESSION['keranjang'] = [];

// Beri respons sukses
echo json_encode([
    'status' => 'success',
    'message' => 'Keranjang berhasil dikosongkan'
]);