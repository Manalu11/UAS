<?php
require_once 'database.php';

class Auth {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Registrasi user baru
     * @param string $username
     * @param string $email
     * @param string $password
     * @return bool|int Returns user ID on success, false on failure
     */
    public function register($username, $email, $password) {
        // Validasi input
        if (empty($username) || empty($email) || empty($password)) {
            return false;
        }

        // Cek apakah email sudah terdaftar
        if ($this->getUserByEmail($email)) {
            return false;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user baru ke database
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        $result = executeQuery($sql, [$username, $email, $hashedPassword], 'sss');

        if ($result) {
            return $this->db->getConnection()->insert_id;
        }

        return false;
    }

    /**
     * Login user
     * @param string $email
     * @param string $password
     * @return array|bool Returns user data on success, false on failure
     */
    public function login($email, $password) {
        // Validasi input
        if (empty($email) || empty($password)) {
            return false;
        }

        // Ambil user dari database
        $user = $this->getUserByEmail($email);

        if (!$user) {
            return false;
        }

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Hapus password dari array sebelum return
            unset($user['password']);
            return $user;
        }

        return false;
    }

    /**
     * Dapatkan user berdasarkan email
     * @param string $email
     * @return array|bool Returns user data on success, false on failure
     */
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        return querySingle($sql, [$email], 's');
    }

    /**
     * Dapatkan user berdasarkan ID
     * @param int $id
     * @return array|bool Returns user data on success, false on failure
     */
    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $user = querySingle($sql, [$id], 'i');
        
        if ($user) {
            unset($user['password']);
        }
        
        return $user;
    }

    /**
     * Update password user
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword($userId, $newPassword) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        return executeQuery($sql, [$hashedPassword, $userId], 'si') !== false;
    }

    /**
     * Verifikasi apakah user sudah login
     * @return bool
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Redirect jika user belum login
     * @param string $redirectTo
     */
    public static function requireLogin($redirectTo = 'login.php') {
        if (!self::isLoggedIn()) {
            header("Location: $redirectTo");
            exit();
        }
    }

    /**
     * Redirect jika user sudah login
     * @param string $redirectTo
     */
    public static function requireGuest($redirectTo = 'index.php') {
        if (self::isLoggedIn()) {
            header("Location: $redirectTo");
            exit();
        }
    }
}

// Buat instance Auth
$auth = new Auth();