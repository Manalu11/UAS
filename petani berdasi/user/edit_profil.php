<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Pastikan user sudah login
if (!isLoggedIn()) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

// Ambil data user saat ini
$user = querySingle("SELECT * FROM users WHERE id = ?", [$user_id], 'i');

// Set nilai default
$username = $user['username'];
$email = $user['email'];
$full_name = $user['full_name'] ?? '';
$phone = $user['phone'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);
    $phone = sanitize_input($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $profile_pic = $_FILES['profile_pic'] ?? null;

    // Validasi
    if (empty($username)) {
        $errors['username'] = 'Username harus diisi';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username minimal 3 karakter';
    } elseif ($username !== $user['username']) {
        if (querySingle("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $user_id], 'si')) {
            $errors['username'] = 'Username sudah digunakan';
        }
    }

    if (empty($email)) {
        $errors['email'] = 'Email harus diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email tidak valid';
    } elseif ($email !== $user['email']) {
        if (querySingle("SELECT id FROM users WHERE email = ? AND id != ?", [$email, $user_id], 'si')) {
            $errors['email'] = 'Email sudah digunakan';
        }
    }

    // Jika ingin mengganti password
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (empty($current_password)) {
            $errors['current_password'] = 'Password saat ini harus diisi';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors['current_password'] = 'Password saat ini salah';
        }

        if (empty($new_password)) {
            $errors['new_password'] = 'Password baru harus diisi';
        } elseif (strlen($new_password) < 6) {
            $errors['new_password'] = 'Password minimal 6 karakter';
        }

        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Konfirmasi password tidak sama';
        }
    }

    // Handle upload gambar profil
    $profile_pic_path = $user['foto_profil'];
    if ($profile_pic && $profile_pic['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $profile_pic['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['profile_pic'] = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.';
        } else {
            $upload_dir = '../assets/images/profiles/';
            $file_name = 'profile_' . $user_id . '_' . time() . '.' . pathinfo($profile_pic['name'], PATHINFO_EXTENSION);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($profile_pic['tmp_name'], $target_path)) {
                // Hapus foto lama jika bukan default
                if ($user['foto_profil'] && strpos($user['foto_profil'], 'default.jpg') === false) {
                    $old_profile_path = '../' . $user['foto_profil'];
                    if (file_exists($old_profile_path)) {
                        unlink($old_profile_path);
                    }
                }
                $profile_pic_path = 'assets/images/profiles/' . $file_name;
            } else {
                $errors['profile_pic'] = 'Gagal mengunggah gambar profil';
            }
        }
    }

    // Jika tidak ada error, update database
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            // Update data dasar
            $query = "UPDATE users SET username = ?, email = ?, full_name = ?, phone = ?, foto_profil = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssi", $username, $email, $full_name, $phone, $profile_pic_path, $user_id);
            $stmt->execute();
            
            // Jika ada perubahan password
            if (!empty($new_password)) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("si", $hashed_password, $user_id);
                $stmt->execute();
            }
            
            $conn->commit();
            $success_message = 'Profil berhasil diperbarui';
            
            // Update session jika username berubah
            if ($username !== $user['username']) {
                $_SESSION['username'] = $username;
            }
            
            // Refresh data user
            $user = querySingle("SELECT * FROM users WHERE id = ?", [$user_id], 'i');
        } catch (Exception $e) {
            $conn->rollback();
            $errors['database'] = 'Gagal memperbarui profil: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <?php include '../includes/navbar_user.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?= htmlspecialchars($user['foto_profil']) ?>" class="rounded-circle mb-3" width="150"
                            height="150">
                        <h4><?= htmlspecialchars($user['username']) ?></h4>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Menu Akun</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="profil.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profil Saya
                        </a>
                        <a href="edit_profil.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-edit me-2"></i> Edit Profil
                        </a>
                        <a href="alamat.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-map-marker-alt me-2"></i> Alamat Saya
                        </a>
                        <a href="transaksi.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-shopping-cart me-2"></i> Transaksi Saya
                        </a>
                        <a href="wishlist.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-heart me-2"></i> Wishlist
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Edit Profil</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?= $success_message ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                    value="<?= htmlspecialchars($username) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= htmlspecialchars($email) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="full_name" class="form-label">Nama Lengkap</label>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                    value="<?= htmlspecialchars($full_name) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Nomor Telepon</label>
                                <input type="tel" class="form-control" id="phone" name="phone"
                                    value="<?= htmlspecialchars($phone) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="profile_pic" class="form-label">Foto Profil</label>
                                <input type="file" class="form-control" id="profile_pic" name="profile_pic"
                                    accept="image/*">
                                <small class="text-muted">Biarkan kosong jika tidak ingin mengubah foto</small>
                            </div>

                            <hr class="my-4">

                            <h6 class="mb-3">Ganti Password</h6>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Password Saat Ini</label>
                                <input type="password" class="form-control" id="current_password"
                                    name="current_password">
                                <small class="text-muted">Isi hanya jika ingin mengganti password</small>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/user-actions.js"></script>
</body>

</html>