<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil ID user dari URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil data user dari database
$user = querySingle("SELECT id, username, email, foto_profil, is_active FROM users WHERE id = ?", [$user_id], 'i');
if (!$user) {
    header("Location: index.php");
    exit();
}

$errors = [];
$username = $user['username'];
$email = $user['email'];
$is_active = $user['is_active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $change_password = isset($_POST['change_password']) ? true : false;
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

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

    if ($change_password) {
        if (empty($password)) {
            $errors['password'] = 'Password harus diisi';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'Password minimal 6 karakter';
        } elseif ($password !== $password_confirm) {
            $errors['password_confirm'] = 'Password tidak sama';
        }
    }

    // Jika tidak ada error, update database
    if (empty($errors)) {
        if ($change_password) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET username = ?, email = ?, password = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssii", $username, $email, $hashed_password, $is_active, $user_id);
        } else {
            $query = "UPDATE users SET username = ?, email = ?, is_active = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssii", $username, $email, $is_active, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'User berhasil diperbarui';
            header("Location: index.php");
            exit();
        } else {
            $errors['database'] = 'Gagal memperbarui user: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>

<body>
    <?php include '../../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../../includes/navbar_admin.php'; ?>

        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Edit User</h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
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

            <div class="card">
                <div class="card-body">
                    <form method="POST">
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

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="change_password" name="change_password">
                            <label class="form-check-label" for="change_password">Ganti Password</label>
                        </div>

                        <div id="password_fields" style="display: none;">
                            <div class="mb-3">
                                <label for="password" class="form-label">Password Baru</label>
                                <input type="password" class="form-control" id="password" name="password">
                            </div>

                            <div class="mb-3">
                                <label for="password_confirm" class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" class="form-control" id="password_confirm"
                                    name="password_confirm">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                <?= $is_active ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#change_password').change(function() {
            if ($(this).is(':checked')) {
                $('#password_fields').show();
                $('#password').prop('required', true);
                $('#password_confirm').prop('required', true);
            } else {
                $('#password_fields').hide();
                $('#password').prop('required', false);
                $('#password_confirm').prop('required', false);
            }
        });
    });
    </script>
</body>

</html>