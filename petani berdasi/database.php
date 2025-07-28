<?php
class Database {
    private $host;
    private $username;
    private $password;
    private $database;
    private $conn;

    public function __construct() {
        $this->host = 'localhost'; // Ganti dengan host database Anda
        $this->username = 'dmvgxdem_cahaya'; // Ganti dengan username database
        $this->password = 'marcahayamanalu'; // Ganti dengan password database
        $this->database = 'dmvgxdem_petaniberdasi'; // Ganti dengan nama database
        
        $this->connect();
    }

    private function connect() {
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
            
            if ($this->conn->connect_error) {
                throw new Exception("Koneksi database gagal: " . $this->conn->connect_error);
            }
            
            // Set charset ke utf8
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            error_log($e->getMessage());
            die("Terjadi kesalahan pada sistem. Silakan coba lagi nanti.");
        }
    }

    public function getConnection() {
        // Periksa koneksi sebelum mengembalikan
        if (!$this->conn || $this->conn->ping() === false) {
            $this->connect();
        }
        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    // Method untuk prepared statement
    public function preparedQuery($sql, $params = [], $types = "") {
        $stmt = $this->getConnection()->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Error preparing statement: " . $this->conn->error);
        }
        
        if (!empty($params)) {
            if (empty($types)) {
                $types = str_repeat("s", count($params)); // Default semua string
            }
            $stmt->bind_param($types, ...$params);
        }
        
        return $stmt;
    }
}

// Buat instance database
$database = new Database();
$conn = $database->getConnection();

// Fungsi untuk memudahkan query
function query($sql, $params = [], $types = "") {
    global $database;
    
    try {
        $stmt = $database->preparedQuery($sql, $params, $types);
        $stmt->execute();
        return $stmt->get_result();
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Fungsi untuk mendapatkan single row
function querySingle($sql, $params = [], $types = "") {
    $result = query($sql, $params, $types);
    return $result ? $result->fetch_assoc() : false;
}

// Fungsi untuk eksekusi query (INSERT, UPDATE, DELETE)
function executeQuery($sql, $params = [], $types = "") {
    global $database;
    
    try {
        $stmt = $database->preparedQuery($sql, $params, $types);
        $stmt->execute();
        return $stmt->affected_rows;
    } catch (Exception $e) {
        error_log($e->getMessage());
        return false;
    }
}

// Register shutdown function untuk menutup koneksi
register_shutdown_function(function() use ($database) {
    $database->closeConnection();
});