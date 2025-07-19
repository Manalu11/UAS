<?php
session_start();
include 'koneksi.php';

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Anda harus login terlebih dahulu']);
    exit();
}

// Validasi CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'Token CSRF tidak valid']);
    exit();
}

// Validasi input
if (!isset($_POST['post_id']) || !isset($_POST['komentar'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

$post_id = (int)$_POST['post_id'];
$komentar = trim($_POST['komentar']);
$user_id = $_SESSION['user_id'];

// Validasi parent_id jika ini adalah reply
$parent_id = 0; // Default untuk komentar utama
if (isset($_POST['parent_id']) && !empty($_POST['parent_id'])) {
    $parent_id = (int)$_POST['parent_id'];
    
    // Validasi bahwa parent comment exists dan tidak lebih dari 1 level
    $checkParent = mysqli_query($conn, "SELECT id, parent_id FROM comments WHERE id = $parent_id");
    if (!$checkParent || mysqli_num_rows($checkParent) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Komentar parent tidak ditemukan']);
        exit();
    }
    
    $parentData = mysqli_fetch_assoc($checkParent);
    // Jika parent sudah merupakan reply, maka gunakan parent_id dari parent tersebut
    // Ini untuk mencegah nested reply lebih dari 1 level
    if ($parentData['parent_id'] != 0) {
        $parent_id = $parentData['parent_id'];
    }
}

// Validasi panjang komentar
if (empty($komentar)) {
    echo json_encode(['status' => 'error', 'message' => 'Komentar tidak boleh kosong']);
    exit();
}

if (strlen($komentar) > 500) {
    echo json_encode(['status' => 'error', 'message' => 'Komentar terlalu panjang (maksimal 500 karakter)']);
    exit();
}

// Validasi bahwa post exists
$checkPost = mysqli_query($conn, "SELECT id FROM posts WHERE id = $post_id");
if (!$checkPost || mysqli_num_rows($checkPost) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Post tidak ditemukan']);
    exit();
}

// Escape string untuk mencegah SQL injection
$komentar = mysqli_real_escape_string($conn, $komentar);

// Query untuk insert komentar
$query = "INSERT INTO comments (post_id, user_id, parent_id, content, created_at) 
          VALUES ($post_id, $user_id, $parent_id, '$komentar', NOW())";

if (mysqli_query($conn, $query)) {
    $comment_id = mysqli_insert_id($conn);
    
    // Ambil data komentar yang baru saja diinsert untuk response
    $getComment = mysqli_query($conn, "
        SELECT c.*, u.username, u.foto_profil 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.id = $comment_id
    ");
    
    if ($getComment && mysqli_num_rows($getComment) > 0) {
        $newComment = mysqli_fetch_assoc($getComment);
        
        // Hitung total komentar untuk post ini
        $countQuery = mysqli_query($conn, "SELECT COUNT(*) as total FROM comments WHERE post_id = $post_id");
        $countResult = mysqli_fetch_assoc($countQuery);
        $totalComments = $countResult['total'];
        
        echo json_encode([
            'status' => 'success',
            'message' => $parent_id > 0 ? 'Balasan berhasil ditambahkan' : 'Komentar berhasil ditambahkan',
            'comment' => $newComment,
            'total_comments' => $totalComments
        ]);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'Komentar berhasil ditambahkan']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan komentar: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>