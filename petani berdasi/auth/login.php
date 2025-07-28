<?php
// Mulai session
session_start();

// Hubungkan ke database dan fungsi
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect jika user sudah login
// Ganti pengecekan awal menjadi:
if (isset($_SESSION['user_id'])) {
    // Pastikan role juga terdefinisi
    if (isset($_SESSION['role'])) {
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../admin/dashboard.php");
        } else {
            header("Location: ../beranda.php");
        }
        exit();
    }
}

// Generate CSRF token untuk keamanan form
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = ''; // Variabel untuk menyimpan pesan error
$username = ''; // Variabel untuk menyimpan input username

// Proses form login jika metode POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Token CSRF tidak valid!';
    } else {
        $username = sanitize_input($_POST['username']); // Bersihkan input username
        $password = $_POST['password']; // Ambil password

        // Validasi input kosong
        if (empty($username) || empty($password)) {
            $error = 'Username dan password harus diisi!';
        } else {
            // Query ke database untuk cek user
            $query = "SELECT id, username, password, foto_profil, role FROM users WHERE username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            // Jika user ditemukan
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verifikasi password
                if (password_verify($password, $user['password'])) {
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['foto_profil'] = $user['foto_profil'];
                    $_SESSION['role'] = $user['role']; // Simpan role (admin/user)
                    
                    // Regenerate session ID untuk keamanan
                    session_regenerate_id(true);
                    
                    // Redirect berdasarkan role
                    if ($user['role'] === 'admin') {
                        header("Location: ../admin/dashboard.php");
                    } else {
                        $redirect = $_GET['redirect'] ?? '../beranda.php';
                        header("Location: $redirect");
                    }
                    exit();
                } else {
                    $error = 'Password salah!';
                }
            } else {
                $error = 'Username tidak ditemukan!';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Petani Berdasi</title>
    <!-- Load CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="../assets/images/logo.png" alt="Logo Petani Berdasi" class="auth-logo">
                <h2>Masuk ke Akun Anda</h2>
                <p>Silakan masuk untuk mengakses semua fitur</p>
            </div>

            <!-- Tampilkan error jika ada -->
            <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form method="POST" class="auth-form">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <!-- Input Username -->
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                            value="<?= htmlspecialchars($username) ?>" required>
                    </div>
                </div>

                <!-- Input Password -->
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Checkbox "Ingat Saya" -->
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>

                <!-- Tombol Login -->
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-sign-in-alt me-2"></i> Masuk
                </button>

                <!-- Link Lupa Password & Daftar -->
                <div class="auth-links">
                    <a href="forgot_password.php">Lupa password?</a>
                    <span class="mx-2">•</span>
                    <a href="register.php">Belum punya akun? Daftar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Load JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script> <!-- File JavaScript terpisah -->
</body>

</html>