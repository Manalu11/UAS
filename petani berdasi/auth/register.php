<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header("Location: ../beranda.php");
    exit();
}

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$input_values = [
    'username' => '',
    'email' => '',
    'nomor_wa' => '',
    'alamat' => ''
];

// Proses form pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Token keamanan tidak valid';
    } else {
        // Ambil dan sanitasi input
        $input_values['username'] = sanitize_input($_POST['username'] ?? '');
        $input_values['email'] = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $input_values['nomor_wa'] = sanitize_input($_POST['nomor_wa'] ?? '');
        $input_values['alamat'] = sanitize_input($_POST['alamat'] ?? '');

        // Handle file upload
        $foto_profil = 'default_profile.jpg';
        if (isset($_FILES['foto_profil']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['foto_profil'];
            
            // Validasi file
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $errors[] = 'Format file tidak didukung. Gunakan JPG, PNG, atau JPEG.';
            } elseif ($file['size'] > $max_size) {
                $errors[] = 'Ukuran file terlalu besar. Maksimal 2MB.';
            } else {
                // Generate unique filename
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $foto_profil = uniqid('profile_', true) . '.' . $ext;
                $upload_path = '../uploads/profiles/' . $foto_profil;
                
                if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $errors[] = 'Gagal mengupload foto profil.';
                    $foto_profil = 'default_profile.jpg';
                }
            }
        }

        // Validasi input
        if (empty($input_values['username'])) {
            $errors[] = 'Username harus diisi';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $input_values['username'])) {
            $errors[] = 'Username hanya boleh mengandung huruf, angka, dan underscore';
        } elseif (strlen($input_values['username']) < 4 || strlen($input_values['username']) > 20) {
            $errors[] = 'Username harus 4-20 karakter';
        }

        if (empty($input_values['email'])) {
            $errors[] = 'Email harus diisi';
        } elseif (!filter_var($input_values['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }

        if (empty($password)) {
            $errors[] = 'Password harus diisi';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password minimal 8 karakter';
        } elseif ($password !== $confirm_password) {
            $errors[] = 'Konfirmasi password tidak sama';
        }

        // Cek username/email sudah terdaftar
        if (empty($errors)) {
            $query = "SELECT id FROM users WHERE username = ? OR email = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $input_values['username'], $input_values['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $errors[] = 'Username atau email sudah terdaftar';
            }
        }

        // Jika validasi sukses, buat akun baru
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $current_time = date('Y-m-d H:i:s');

            $query = "INSERT INTO users (username, email, password, foto_profil, nomor_wa, alamat, created_at, updated_at) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssss", 
                $input_values['username'],
                $input_values['email'],
                $hashed_password,
                $foto_profil,
                $input_values['nomor_wa'],
                $input_values['alamat'],
                $current_time,
                $current_time
            );
            
            if ($stmt->execute()) {
                // Set session
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $input_values['username'];
                $_SESSION['foto_profil'] = $foto_profil;
                
                // Regenerasi session ID untuk keamanan
                session_regenerate_id(true);
                
                // Redirect ke halaman beranda
                header("Location: ../beranda.php");
                exit();
            } else {
                $errors[] = 'Gagal membuat akun. Silakan coba lagi.';
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
    <title>Daftar Akun - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>

<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="../assets/images/logo.png" alt="Logo" class="auth-logo">
                <h2>Buat Akun Baru</h2>
                <p>Isi data diri Anda untuk mendaftar</p>
            </div>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" class="auth-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" class="form-control" id="username" name="username"
                            value="<?= htmlspecialchars($input_values['username']) ?>" required>
                    </div>
                    <div class="form-text">4-20 karakter (huruf, angka, underscore)</div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?= htmlspecialchars($input_values['email']) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <button class="btn btn-outline-secondary toggle-password" type="button">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="form-text">Minimal 8 karakter</div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                            required>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="foto_profil" class="form-label">Foto Profil (Opsional)</label>
                    <input type="file" class="form-control" id="foto_profil" name="foto_profil" accept="image/*">
                    <div class="form-text">Maksimal 2MB (format: JPG, PNG, JPEG)</div>
                </div>

                <div class="mb-3">
                    <label for="nomor_wa" class="form-label">Nomor WhatsApp</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="nomor_wa" name="nomor_wa"
                            value="<?= htmlspecialchars($input_values['nomor_wa']) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="alamat" class="form-label">Alamat</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <textarea class="form-control" id="alamat"
                            name="alamat"><?= htmlspecialchars($input_values['alamat']) ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="fas fa-user-plus me-2"></i> Daftar Sekarang
                </button>

                <div class="auth-links text-center">
                    <span>Sudah punya akun? <a href="login.php">Masuk disini</a></span>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/register.js"></script>
</body>

</html>