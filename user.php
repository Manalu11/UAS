<?php
class User {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username_or_email, $password) {
        $stmt = $this->conn->prepare("SELECT id, username, email, nama_lengkap, password, foto_profil FROM users WHERE username = ? OR email = ? LIMIT 1");
        if (!$stmt) {
            die("Query error: " . $this->conn->error);
        }

        $stmt->bind_param("ss", $username_or_email, $username_or_email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    public function register($username, $email, $nama_lengkap, $password_plain, $foto_profil) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return false;
        }

        $hashedPassword = password_hash($password_plain, PASSWORD_BCRYPT);

        $stmt = $this->conn->prepare("INSERT INTO users (username, email, nama_lengkap, password, foto_profil) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $email, $nama_lengkap, $hashedPassword, $foto_profil);

        return $stmt->execute();
    }
}
?>