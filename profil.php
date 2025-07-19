<?php
session_start();
include 'koneksi.php';

// Redirect jika belum login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Inisialisasi variabel
$message = '';
$messageType = '';
$userId = $_SESSION['user_id'];

// Ambil data user dari database
$user = []; 
try {
    $stmt = $conn->prepare("SELECT id, username, email, nama_lengkap, foto_profil, nomor_wa FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc() ?? [];
    $stmt->close();
    
    // Set nilai default jika kolom kosong
    $user['nama_lengkap'] = $user['nama_lengkap'] ?? '';
    $user['email'] = $user['email'] ?? '';
    $user['nomor_wa'] = $user['nomor_wa'] ?? '';
    $user['foto_profil'] = $user['foto_profil'] ?? '';
    
} catch (Exception $e) {
    $message = 'Error mengambil data: ' . $e->getMessage();
    $messageType = 'error';
}

// Proses form update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $nomor_wa = mysqli_real_escape_string($conn, $_POST['nomor_wa'] ?? '');

    // Validasi input
    if (empty($nama_lengkap)) {
        $message = 'Nama lengkap tidak boleh kosong';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Format email tidak valid';
        $messageType = 'error';
    } else {
        try {
            $updateStmt = $conn->prepare("UPDATE users SET nama_lengkap=?, email=?, nomor_wa=? WHERE id=?");
            $updateStmt->bind_param("sssi", $nama_lengkap, $email, $nomor_wa, $userId);
            
            if ($updateStmt->execute()) {
                $message = 'Profil berhasil diperbarui';
                $messageType = 'success';
                // Update data user lokal
                $user['nama_lengkap'] = $nama_lengkap;
                $user['email'] = $email;
                $user['nomor_wa'] = $nomor_wa;
            } else {
                $message = 'Gagal memperbarui profil: ' . $conn->error;
                $messageType = 'error';
            }
            $updateStmt->close();
        } catch (Exception $e) {
            $message = 'Database error: ' . $e->getMessage();
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
    <title>Profil - Petani Berdasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
        max-width: 800px;
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
        font-size: 28px;
    }

    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .profile-pic {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background-color: var(--light);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
        overflow: hidden;
    }

    .profile-pic img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-pic i {
        font-size: 50px;
        color: var(--primary);
    }

    .profile-info h3 {
        margin: 0;
        color: var(--dark);
        font-size: 22px;
    }

    .profile-info p {
        margin: 5px 0 0;
        color: var(--gray);
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

    input[type="text"],
    input[type="email"],
    input[type="tel"],
    textarea {
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
        display: inline-block;
        text-align: center;
    }

    .btn:hover {
        background-color: var(--secondary);
    }

    .btn-block {
        display: block;
        width: 100%;
    }

    .btn-gray {
        background-color: var(--gray);
    }

    .btn-gray:hover {
        background-color: #5a6268;
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

    .action-links {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
    }

    .back-link {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
    }

    .back-link:hover {
        text-decoration: underline;
    }

    .back-link i {
        margin-right: 5px;
    }

    .password-section {
        margin-bottom: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
    }

    .password-section h4 {
        margin-top: 0;
        color: var(--dark);
    }

    .fake-password {
        background-color: #e9ecef;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .container {
            padding: 20px;
            margin: 10px;
        }

        .profile-header {
            flex-direction: column;
            text-align: center;
        }

        .profile-pic {
            margin-right: 0;
            margin-bottom: 15px;
        }

        .action-links {
            flex-direction: column;
            gap: 10px;
        }

        .btn {
            width: 100%;
        }
    }
    </style>
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-user-circle"></i> Profil Pengguna</h2>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <div class="profile-header">
            <div class="profile-pic">
                <?php if (!empty($user['foto_profil']) && file_exists($user['foto_profil'])): ?>
                <img src="<?= htmlspecialchars($user['foto_profil']) ?>" alt="Foto Profil">
                <?php else: ?>
                <i class="fas fa-user"></i>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3><?= htmlspecialchars($user['nama_lengkap']) ?></h3>
                <p>@<?= htmlspecialchars($user['username'] ?? '') ?></p>
            </div>
        </div>

        <form method="POST" action="profil.php">
            <div class="form-group">
                <label for="nama_lengkap"><i class="fas fa-user"></i> Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap"
                    value="<?= htmlspecialchars($user['nama_lengkap']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="nomor_wa"><i class="fab fa-whatsapp"></i> Nomor WhatsApp</label>
                <input type="tel" id="nomor_wa" name="nomor_wa" value="<?= htmlspecialchars($user['nomor_wa']) ?>">
            </div>

            <div class="password-section">
                <h4>Password</h4>
                <div style="display: flex; align-items: center;">
                    <input type="password" value="••••••••" class="fake-password" style="flex-grow: 1;" readonly>
                    <button type="button" class="btn btn-gray" onclick="location.href='ubah-password.php'"
                        style="margin-left: 10px;">
                        <i class="fas fa-key"></i> Ganti Password
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-block">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </form>

        <div class="action-links">
            <a href="beranda.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</body>

</html>