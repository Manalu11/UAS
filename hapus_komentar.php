<?php
session_start();
include 'koneksi.php';
header('Content-Type: application/json');

// Tambahkan pengecekan CSRF token
if (!isset($_POST['csrf_token'])) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid']);
    exit();
}

if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login']);
    exit();
}

if (!isset($_POST['comment_id'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'ID komentar tidak valid']);
    exit();
}

$comment_id = intval($_POST['comment_id']);
$user_id = $_SESSION['user_id'];

// Gunakan prepared statement untuk mencegah SQL injection
$check_query = "SELECT user_id FROM comments WHERE id = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "i", $comment_id);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($check_result) > 0) {
    $comment_data = mysqli_fetch_assoc($check_result);
    
    if ($comment_data['user_id'] == $user_id || $_SESSION['role'] == 'admin') {
        $delete_query = "DELETE FROM comments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($stmt, "i", $comment_id);
        
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode(['status' => 'success']);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode([
                'status' => 'error', 
                'message' => 'Gagal menghapus komentar: ' . mysqli_error($conn)
            ]);
        }
    } else {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki izin']);
    }
} else {
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'Komentar tidak ditemukan']);
}

mysqli_close($conn);
?>