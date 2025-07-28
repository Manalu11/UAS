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
$name = '';
$description = '';
$price = '';
$stock = '';
$is_active = 1;
$user_id = '';

// Ambil daftar user untuk dropdown
$users = query("SELECT id, username FROM users ORDER BY username")->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $description = sanitize_input($_POST['description']);
    $price = sanitize_input($_POST['price']);
    $stock = sanitize_input($_POST['stock']);
    $user_id = sanitize_input($_POST['user_id']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Validasi
    if (empty($name)) {
        $errors['name'] = 'Nama produk harus diisi';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Nama produk minimal 3 karakter';
    }

    if (empty($description)) {
        $errors['description'] = 'Deskripsi produk harus diisi';
    }

    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors['price'] = 'Harga harus berupa angka lebih dari 0';
    }

    if (empty($stock) || !is_numeric($stock) || $stock < 0) {
        $errors['stock'] = 'Stok harus berupa angka positif';
    }

    if (empty($user_id) || !is_numeric($user_id)) {
        $errors['user_id'] = 'Penjual harus dipilih';
    }

    // Handle file upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors['image'] = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.';
        } else {
            $upload_dir = '../../assets/images/products/';
            $file_name = uniqid() . '_' . basename($_FILES['image']['name']);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                $image_url = 'assets/images/products/' . $file_name;
            } else {
                $errors['image'] = 'Gagal mengunggah gambar';
            }
        }
    } else {
        $errors['image'] = 'Gambar produk harus diupload';
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $query = "INSERT INTO products (name, description, price, stock, image_url, user_id, is_active) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdisii", $name, $description, $price, $stock, $image_url, $user_id, $is_active);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Produk berhasil ditambahkan';
            header("Location: index.php");
            exit();
        } else {
            $errors['database'] = 'Gagal menambahkan produk: ' . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk - Petani Berdasi</title>
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
                <h2>Tambah Produk</h2>
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
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nama Produk</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="<?= htmlspecialchars($name) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required><?= 
                                htmlspecialchars($description) ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Harga (Rp)</label>
                                <input type="number" class="form-control" id="price" name="price"
                                    value="<?= htmlspecialchars($price) ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="stock" class="form-label">Stok</label>
                                <input type="number" class="form-control" id="stock" name="stock"
                                    value="<?= htmlspecialchars($stock) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="user_id" class="form-label">Penjual</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">Pilih Penjual</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>" <?= $user_id == $user['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['username']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Gambar Produk</label>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                            <small class="text-muted">Format: JPG, PNG, GIF (Maksimal 2MB)</small>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active"
                                <?= $is_active ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Aktif</label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
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