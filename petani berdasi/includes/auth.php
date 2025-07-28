<?php
session_start();
require_once __DIR__ . '/../config/database.php';

/**
 * Mengecek apakah user sudah login
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Mengecek apakah user memiliki role admin
 */
function isAdmin() {
    // Pastikan user sudah login dan memiliki data role
    if (isLoggedIn() && isset($_SESSION['user_role'])) {
        return $_SESSION['user_role'] === 'admin';
    }
    return false;
}

/**
 * Melakukan proses login
 */
function login($username, $password) {
    global $conn;
    
    // Cari user di database
    $query = "SELECT id, username, password, role, is_active FROM users WHERE username = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password dan status aktif
        if (password_verify($password, $user['password']) && $user['is_active'] == 1) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            return true;
        }
    }
    
    return false;
}

/**
 * Melakukan proses logout
 */
function logout() {
    // Hapus semua data session
    $_SESSION = array();
    
    // Hapus cookie session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Hancurkan session
    session_destroy();
}

/**
 * Redirect ke halaman login jika belum login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

/**
 * Redirect ke halaman login jika bukan admin
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

/**
 * Mendapatkan data user yang sedang login
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    global $conn;
    $query = "SELECT id, username, email, foto_profil, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
?>