<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Get and validate input
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
$userId = $_SESSION['user_id'];

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

if (empty($comment)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Komentar tidak boleh kosong']);
    exit();
}

if (strlen($comment) > 500) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Komentar terlalu panjang (maksimal 500 karakter)']);
    exit();
}

// Check if product exists
$productCheck = $conn->prepare("SELECT id FROM products WHERE id = ?");
$productCheck->bind_param("i", $productId);
$productCheck->execute();
if ($productCheck->get_result()->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit();
}

try {
    // Sanitize comment
    $comment = sanitize_input($comment);
    
    // Add comment to database
    if (addComment($conn, $productId, $userId, $comment)) {
        // Get user info for response
        $userQuery = $conn->prepare("SELECT username, foto_profil FROM users WHERE id = ?");
        $userQuery->bind_param("i", $userId);
        $userQuery->execute();
        $user = $userQuery->get_result()->fetch_assoc();
        
        // Log the action
        log_action('add_comment', $userId, "Product ID: $productId");
        
        echo json_encode([
            'success' => true,
            'message' => 'Komentar berhasil ditambahkan',
            'comment' => [
                'username' => $user['username'],
                'foto_profil' => $user['foto_profil'],
                'content' => $comment,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        throw new Exception('Failed to add comment');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
?>