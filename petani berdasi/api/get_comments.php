<?php
header('Content-Type: application/json');
include 'koneksi.php';

$response = ['success' => false, 'comments' => []];

if (isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);

    $stmt = $conn->prepare("SELECT c.comment, c.created_at, u.username, u.foto_profil 
                            FROM comments c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.product_id = ? 
                            ORDER BY c.created_at DESC");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $response['comments'][] = [
            'comment' => htmlspecialchars($row['comment']),
            'username' => htmlspecialchars($row['username']),
            'foto_profil' => $row['foto_profil'] ?: 'default.jpg',
            'time_ago' => timeAgo($row['created_at']) // Fungsi ini harus dibuat
        ];
    }

    $response['success'] = true;
}

echo json_encode($response);

// Fungsi timeAgo untuk menampilkan "x menit lalu"
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return $diff . ' detik lalu';
    if ($diff < 3600) return floor($diff / 60) . ' menit lalu';
    if ($diff < 86400) return floor($diff / 3600) . ' jam lalu';
    return date('d M Y H:i', $time);
}