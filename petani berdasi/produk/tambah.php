<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? '';
    $description = $_POST['description'] ?? '';
    $kategori = $_POST['kategori'] ?? '';
    $stock = $_POST['stock'] ?? 0;
    $weight = $_POST['weight'] ?? null;
    $condition = $_POST['`condition`'] ?? 'new';
    $image_url = '';

    // Handle file upload
    if (!empty($_FILES['image']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $error = 'Hanya file JPG, PNG, atau GIF yang diizinkan';
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $error = 'Ukuran file maksimal 2MB';
        } else {
            $uploadDir = '../uploads/products/';
            $fileName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $_FILES['image']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $image_url = $fileName;
            } else {
                $error = 'Gagal mengupload gambar';
            }
        }
    } else {
        $error = 'Gambar produk wajib diupload';
    }

    if (empty($error) && (empty($name) || empty($price) || empty($description) || empty($kategori))) {
        $error = 'Semua field wajib diisi!';
    }

    if (empty($error)) {
        $query = "INSERT INTO products (user_id, name, slug, description, price, kategori, stock, weight,kualitas, image_url) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        // Generate slug
        $slug = generateSlug($name);
        
        $stmt->bind_param("isssssiiss", 
            $_SESSION['user_id'], 
            $name, 
            $slug,
            $description, 
            $price, 
            $kategori,
            $stock,
            $weight,
            $condition,
            $image_url
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Produk berhasil ditambahkan!';
            header("Location: index.php");
            exit();
        } else {
            $error = 'Gagal menambahkan produk: ' . $conn->error;
            // Hapus file yang sudah diupload jika query gagal
            if (!empty($image_url)) {
                @unlink($uploadDir . $image_url);
            }
        }
    }
}

// Fungsi generate slug
function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    
    if (empty($text)) {
        return 'n-a';
    }
    
    return $text;
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    .product-image-preview {
        max-width: 200px;
        max-height: 200px;
        display: none;
        margin-top: 10px;
    }
    </style>
</head>

<body>
    <?php include '../includes/navbar_user.php'; ?>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Tambah Produk Baru</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" id="productForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Nama Produk <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Harga (Rp) <span
                                            class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price"
                                        value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kategori" class="form-label">Kategori <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="kategori" name="kategori" required>
                                        <option value="">Pilih Kategori</option>
                                        <option value="Sembako"
                                            <?= isset($_POST['kategori']) && $_POST['kategori'] == 'Sembako' ? 'selected' : '' ?>>
                                            Sembako</option>
                                        <option value="Sayuran"
                                            <?= isset($_POST['kategori']) && $_POST['kategori'] == 'Sayuran' ? 'selected' : '' ?>>
                                            Sayuran</option>
                                        <option value="Buah"
                                            <?= isset($_POST['kategori']) && $_POST['kategori'] == 'Buah' ? 'selected' : '' ?>>
                                            Buah</option>
                                        <option value="Protein"
                                            <?= isset($_POST['kategori']) && $_POST['kategori'] == 'Protein' ? 'selected' : '' ?>>
                                            Protein</option>
                                        <option value="Herbal"
                                            <?= isset($_POST['kategori']) && $_POST['kategori'] == 'Herbal' ? 'selected' : '' ?>>
                                            Herbal</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi Produk <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="4"
                                    required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="stock" class="form-label">Stok</label>
                                    <input type="number" class="form-control" id="stock" name="stock"
                                        value="<?= htmlspecialchars($_POST['stock'] ?? 0) ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="weight" class="form-label">Berat (gram)</label>
                                    <input type="number" class="form-control" id="weight" name="weight"
                                        value="<?= htmlspecialchars($_POST['weight'] ?? '') ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="`condition`" class="form-label">Kondisi</label>
                                    <select class="form-select" id="`condition`" name="`condition`">
                                        <option value="new"
                                            <?= isset($_POST['`condition`']) && $_POST['`condition`'] == 'new' ? 'selected' : '' ?>>
                                            Baru</option>
                                        <option value="used"
                                            <?= isset($_POST['`condition`']) && $_POST['`condition`'] == 'used' ? 'selected' : '' ?>>
                                            Bekas</option>
                                        <option value="refurbished"
                                            <?= isset($_POST['`condition`']) && $_POST['`condition`'] == 'refurbished' ? 'selected' : '' ?>>
                                            Rekondisi</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Gambar Produk <span
                                        class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*"
                                    required>
                                <small class="text-muted">Format: JPG, PNG, GIF (Maksimal 2MB)</small>
                                <img id="imagePreview" src="#" alt="Preview Gambar"
                                    class="product-image-preview img-thumbnail">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Simpan Produk
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Kembali
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/product.js"></script>

    <script>
    // Preview image before upload
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>

</html>