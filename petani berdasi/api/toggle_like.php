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
$userId = $_SESSION['user_id'];

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
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
    // Check if user already liked the product
    $isLiked = isProductLiked($conn, $productId, $userId);
    
    if ($isLiked) {
        // Unlike the product
        $action = 'unlike';
        $query = "DELETE FROM likes WHERE product_id = ? AND user_id = ?";
    } else {
        // Like the product
        $action = 'like';
        $query = "INSERT INTO likes (product_id, user_id, created_at) VALUES (?, ?, NOW())";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $productId, $userId);
    
    if ($stmt->execute()) {
        // Get updated like count
        $likeCount = getProductLikeCount($conn, $productId);
        
        // Log the action
        log_action($action . '_product', $userId, "Product ID: $productId");
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'like_count' => $likeCount,
            'is_liked' => !$isLiked
        ]);
    } else {
        throw new Exception('Database error');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
/**
 * Get the total number of likes for a product.
 *
 * @param mysqli $conn
 * @param int $productId
 * @return int
 */
function getProductLikeCount($conn, $productId) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM likes WHERE product_id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return (int)$count;
}

/**
 * Check if a user has liked a product.
 *
 * @param mysqli $conn
 * @param int $productId
 * @param int $userId
 * @return bool
 */
function isProductLiked($conn, $productId, $userId) {
    $stmt = $conn->prepare("SELECT 1 FROM likes WHERE product_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $productId, $userId);
    $stmt->execute();
    $stmt->store_result();
    $liked = $stmt->num_rows > 0;
    $stmt->close();
    return $liked;
}
?>