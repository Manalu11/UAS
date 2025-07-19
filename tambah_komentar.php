<?php
session_start();
include 'koneksi.php';
header('Content-Type: application/json');

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'CSRF token tidak valid']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login']);
    exit();
}

if (!isset($_POST['post_id']) || !isset($_POST['content'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

$post_id = intval($_POST['post_id']);
$user_id = intval($_SESSION['user_id']);
$parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
$content = mysqli_real_escape_string($conn, $_POST['content']);

$query = "INSERT INTO comments (post_id, user_id, parent_id, isi, created_at) 
          VALUES (?, ?, ?, ?, NOW())";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "iiis", $post_id, $user_id, $parent_id, $content);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        'status' => 'success',
        'comment_id' => mysqli_insert_id($conn)
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Gagal menambahkan komentar: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>