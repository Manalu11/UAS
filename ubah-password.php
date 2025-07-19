<?php
session_start();
include 'koneksi.php';

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validasi
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = 'Semua field harus diisi';
        $messageType = 'error';
    } elseif ($new_password !== $confirm_password) {
        $message = 'Password baru tidak cocok';
        $messageType = 'error';
    } else {
        // Verifikasi password saat ini
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($current_password, $user['password'])) {
            // Update password baru
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $hashed_password, $userId);
            
            if ($updateStmt->execute()) {
                $message = 'Password berhasil diubah';
                $messageType = 'success';
            } else {
                $message = 'Gagal mengubah password: ' . $conn->error;
                $messageType = 'error';
            }
        } else {
            $message = 'Password saat ini salah';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ubah Password - Petani Berdasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    /* Gunakan style yang sama dengan profil.php */
    :root {
        --primary: #2e7d32;
        --secondary: #1b5e20;
        --light: #e8f5e9;
        --white: #ffffff;
        --dark: #333;
        --gray: #6c757d;
        --success: #28a745;
        --danger: #dc3545;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: var(--light);
        margin: 0;
        padding: 20px;
    }

    .container {
        max-width: 500px;
        margin: 20px auto;
        background: var(--white);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        color: var(--primary);
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: var(--dark);
    }

    input[type="password"] {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
        transition: border-color 0.3s;
    }

    input:focus {
        border-color: var(--primary);
        outline: none;
    }

    .btn {
        background-color: var(--primary);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 16px;
        transition: background-color 0.3s;
        display: block;
        width: 100%;
    }

    .btn:hover {
        background-color: var(--secondary);
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .back-link {
        display: inline-block;
        margin-top: 20px;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .back-link i {
        margin-right: 5px;
    }
    </style>
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-key"></i> Ubah Password</h2>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="ubah-password.php">
            <div class="form-group">
                <label for="current_password"><i class="fas fa-lock"></i> Password Saat Ini</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password"><i class="fas fa-key"></i> Password Baru</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-key"></i> Konfirmasi Password Baru</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </form>

        <a href="profil.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Profil
        </a>
    </div>
</body>

</html>