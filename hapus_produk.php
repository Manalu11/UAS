<?php
header('Content-Type: application/json');
session_start();
require 'koneksi.php';

$response = ['success' => false, 'message' => ''];

try {
    // Debug: Log semua input
    error_log('REQUEST DATA: ' . print_r($_REQUEST, true));
    error_log('SESSION DATA: ' . print_r($_SESSION, true));

    // Validasi session
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Sesi telah berakhir, silakan login kembali');
    }

    // Validasi parameter ID
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Parameter ID tidak valid');
    }

    $productId = (int)$_GET['id'];
    $userId = (int)$_SESSION['user_id'];
    $isAdmin = ($_SESSION['role'] ?? '') === 'admin';

    error_log("Debug: UserID=$userId, ProductID=$productId, IsAdmin=".($isAdmin?'Yes':'No'));

    // Mulai transaksi
    $conn->begin_transaction();

    // 1. Ambil data produk (dengan FOR UPDATE untuk konsistensi)
    $stmt = $conn->prepare("SELECT user_id, gambar_path FROM posts WHERE id = ? FOR UPDATE");
    if (!$stmt) {
        throw new Exception('Prepare statement gagal: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $productId);
    if (!$stmt->execute()) {
        throw new Exception('Eksekusi statement gagal: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Produk tidak ditemukan di database');
    }

    $product = $result->fetch_assoc();
    $stmt->close();

    // 2. Verifikasi kepemilikan
    if ($product['user_id'] !== $userId && !$isAdmin) {
        throw new Exception('Anda tidak memiliki izin untuk menghapus produk ini');
    }

    // 3. Hapus komentar terkait
    $deleteComments = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
    if (!$deleteComments) {
        throw new Exception('Prepare statement komentar gagal: ' . $conn->error);
    }
    
    $deleteComments->bind_param("i", $productId);
    if (!$deleteComments->execute()) {
        throw new Exception('Hapus komentar gagal: ' . $deleteComments->error);
    }
    $deleteComments->close();

    // 4. Hapus produk
    $deletePost = $conn->prepare("DELETE FROM posts WHERE id = ?");
    if (!$deletePost) {
        throw new Exception('Prepare statement post gagal: ' . $conn->error);
    }
    
    $deletePost->bind_param("i", $productId);
    if (!$deletePost->execute()) {
        throw new Exception('Hapus post gagal: ' . $deletePost->error);
    }
    
    $rowsAffected = $deletePost->affected_rows;
    $deletePost->close();

    if ($rowsAffected === 0) {
        throw new Exception('Tidak ada produk yang terhapus');
    }

    // 5. Hapus file gambar jika ada
    if (!empty($product['gambar_path'])) {
        $filePath = realpath($product['gambar_path']);
        if ($filePath && file_exists($filePath)) {
            if (!unlink($filePath)) {
                error_log("Peringatan: Gagal menghapus file gambar: $filePath");
            }
        }
    }

    // Commit transaksi jika semua berhasil
    $conn->commit();

    $response = [
        'success' => true,
        'message' => 'Produk dan komentar terkait berhasil dihapus',
        'deleted_id' => $productId
    ];

} catch (Exception $e) {
    // Rollback jika ada error
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) {
        $conn->rollback();
    }

    $response['message'] = $e->getMessage();
    error_log('ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());

} finally {
    // Tutup koneksi jika ada
    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_errno) {
        $conn->close();
    }

    echo json_encode($response);
    exit;
}