<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil ID kategori dari URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($category_id <= 0) {
    header("Location: index.php");
    exit();
}

// Ambil data kategori dari database
$category = querySingle("SELECT id, name, description, is_active FROM categories WHERE id = ?", [$category_id], 'i');
if (!$category) {
    header("Location: index.php");
    exit();
}

$errors = [];
$name = $category['name'];
$description = $category['description'];
$is_active = $category['is_active'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validasi
    if (empty($name)) {
        $errors['name'] = 'Nama kategori harus diisi';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Nama kategori minimal 3 karakter';
    } elseif ($name !== $category['name']) {
        if (querySingle("SELECT id FROM categories WHERE name = ? AND id != ?", [$name, $category_id], 'si')) {
            $errors['name'] = 'Nama kategori sudah digunakan';
        }
    }

    if (empty($description)) {
        $errors['description'] = 'Deskripsi kategori harus diisi';
    }

    // Jika tidak ada error, update database
    if (empty($errors)) {
        $query = "UPDATE categories SET name = ?, description = ?, is_active = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $name, $description, $is_active, $category_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Kategori berhasil diperbarui';
            header("Location: index.php");
            exit();
        } else {
            $errors['database'] = 'Gagal memperbarui kategori: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori - Petani Berdasi</title>
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
                <h2>Edit Kategori</h2>
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
                            <label for="name" class="form-label">Nama Kategori</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?= htmlspecialchars($name) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?= 
                                htmlspecialchars($description) ?></textarea>
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
</body>

</html>