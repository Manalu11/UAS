<?php
session_start();
include 'koneksi.php';
header('Content-Type: application/json');

if (!isset($_GET['post_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Post ID tidak valid']);
    exit();
}

$post_id = (int)$_GET['post_id'];

$query = "SELECT c.*, u.username, u.foto_profil 
          FROM comments c
          JOIN users u ON c.user_id = u.id
          WHERE c.post_id = $post_id
          ORDER BY c.created_at ASC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal mengambil komentar']);
    exit();
}

$comments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $comments[] = $row;
}

echo json_encode([
    'status' => 'success',
    'comments' => $comments
]);

mysqli_close($conn);
?>