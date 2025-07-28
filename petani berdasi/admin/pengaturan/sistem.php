<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

$errors = [];
$success_message = '';
$settings = [];

// Ambil semua pengaturan dari database
$result = query("SELECT name, value FROM settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['name']] = $row['value'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update pengaturan umum
    $settings['site_name'] = sanitize_input($_POST['site_name']);
    $settings['site_description'] = sanitize_input($_POST['site_description']);
    $settings['site_email'] = sanitize_input($_POST['site_email']);
    $settings['site_phone'] = sanitize_input($_POST['site_phone']);
    $settings['site_address'] = sanitize_input($_POST['site_address']);
    $settings['maintenance_mode'] = isset($_POST['maintenance_mode']) ? 1 : 0;
    
    // Update logo jika diupload
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['site_logo']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['site_logo'] = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.';
        } else {
            $upload_dir = '../../assets/images/';
            $file_name = 'logo_' . uniqid() . '.' . pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $target_path)) {
                // Hapus logo lama jika bukan default
                if ($settings['site_logo'] && strpos($settings['site_logo'], 'default_logo') === false) {
                    $old_logo_path = '../../' . $settings['site_logo'];
                    if (file_exists($old_logo_path)) {
                        unlink($old_logo_path);
                    }
                }
                $settings['site_logo'] = 'assets/images/' . $file_name;
            } else {
                $errors['site_logo'] = 'Gagal mengunggah logo';
            }
        }
    }
    
    // Jika tidak ada error, update database
    if (empty($errors)) {
        $conn->begin_transaction();
        
        try {
            foreach ($settings as $name => $value) {
                $query = "INSERT INTO settings (name, value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE value = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("sss", $name, $value, $value);
                $stmt->execute();
            }
            
            $conn->commit();
            $success_message = 'Pengaturan berhasil diperbarui';
        } catch (Exception $e) {
            $conn->rollback();
            $errors['database'] = 'Gagal memperbarui pengaturan: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Sistem - Petani Berdasi</title>
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
                <h2>Pengaturan Sistem</h2>
            </div>

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

            <div class="card">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_name" class="form-label">Nama Situs</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name"
                                        value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="site_description" class="form-label">Deskripsi Situs</label>
                                    <textarea class="form-control" id="site_description" name="site_description"
                                        rows="3"
                                        required><?= htmlspecialchars($settings['site_description'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="site_email" class="form-label">Email Kontak</label>
                                    <input type="email" class="form-control" id="site_email" name="site_email"
                                        value="<?= htmlspecialchars($settings['site_email'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_phone" class="form-label">Telepon Kontak</label>
                                    <input type="text" class="form-control" id="site_phone" name="site_phone"
                                        value="<?= htmlspecialchars($settings['site_phone'] ?? '') ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="site_address" class="form-label">Alamat</label>
                                    <textarea class="form-control" id="site_address" name="site_address" rows="3"
                                        required><?= htmlspecialchars($settings['site_address'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="site_logo" class="form-label">Logo Situs</label>
                                    <input type="file" class="form-control" id="site_logo" name="site_logo"
                                        accept="image/*">
                                    <small class="text-muted">Biarkan kosong jika tidak ingin mengubah logo</small>
                                    <?php if (!empty($settings['site_logo'])): ?>
                                    <div class="mt-2">
                                        <img src="../../<?= htmlspecialchars($settings['site_logo']) ?>"
                                            class="img-thumbnail" width="150">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="maintenance_mode"
                                name="maintenance_mode" <?= ($settings['maintenance_mode'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="maintenance_mode">Mode Maintenance</label>
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
</body>

</html>