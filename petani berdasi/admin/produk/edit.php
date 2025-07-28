<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid product ID';
    header("Location: index.php");
    exit();
}

$product_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify product ownership
if (!isProductOwner($conn, $product_id, $user_id)) {
    $_SESSION['error'] = 'You are not authorized to edit this product';
    header("Location: index.php");
    exit();
}

// Get current product data
$product = querySingle(
    "SELECT * FROM products WHERE id = ? AND deleted_at IS NULL", 
    [$product_id], 
    'i'
);

if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header("Location: index.php");
    exit();
}

// Initialize form data
$form_data = [
    'name' => $product['name'],
    'description' => $product['description'],
    'price' => $product['price'],
    'kategori' => $product['kategori'],
    'stock' => $product['stock'],
    'weight' => $product['weight'],
    'kondisi' => $product['kondisi'], // Using 'kondisi' as per database
    'image_url' => $product['image_url']
];

// Generate CSRF token
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid CSRF token';
    } else {
        // Sanitize input
        $form_data = [
            'name' => trim($_POST['name'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'price' => (float)($_POST['price'] ?? 0),
            'kategori' => trim($_POST['kategori'] ?? ''),
            'stock' => (int)($_POST['stock'] ?? 0),
            'weight' => !empty($_POST['weight']) ? (int)$_POST['weight'] : null,
            'kondisi' => trim($_POST['kondisi'] ?? 'new'), // Using 'kondisi'
            'image_url' => $product['image_url']
        ];

        // Validate input
        $errors = [];

        if (empty($form_data['name'])) {
            $errors['name'] = 'Product name is required';
        }

        if (empty($form_data['description'])) {
            $errors['description'] = 'Description is required';
        }

        if ($form_data['price'] <= 0) {
            $errors['price'] = 'Price must be greater than 0';
        }

        if (empty($form_data['kategori'])) {
            $errors['kategori'] = 'Category is required';
        }

        // Handle file upload
        if (!empty($_FILES['image']['name'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $errors['image'] = 'Only JPG, PNG or GIF files are allowed';
            } elseif ($_FILES['image']['size'] > $max_size) {
                $errors['image'] = 'Maximum file size is 2MB';
            } else {
                $upload_dir = '../uploads/products/';
                $file_name = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $_FILES['image']['name']);
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                    // Delete old image if exists
                    if (!empty($product['image_url']) && file_exists($upload_dir . $product['image_url'])) {
                        @unlink($upload_dir . $product['image_url']);
                    }
                    $form_data['image_url'] = $file_name;
                } else {
                    $errors['image'] = 'Failed to upload image';
                }
            }
        }

        // Update database if no errors
        if (empty($errors)) {
            $query = "UPDATE products SET 
                      name = ?, 
                      description = ?, 
                      price = ?, 
                      kategori = ?, 
                      stock = ?, 
                      weight = ?, 
                      kondisi = ?,  // Using 'kondisi' in query
                      image_url = ?,
                      updated_at = NOW()
                      WHERE id = ?";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssdsisssi",
                $form_data['name'],
                $form_data['description'],
                $form_data['price'],
                $form_data['kategori'],
                $form_data['stock'],
                $form_data['weight'],
                $form_data['kondisi'],
                $form_data['image_url'],
                $product_id
            );
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Product updated successfully';
                header("Location: index.php");
                exit();
            } else {
                $errors[] = 'Failed to update product: ' . $conn->error;
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
    <title>Edit Product - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    .product-image-preview {
        max-width: 200px;
        max-height: 200px;
        margin-top: 10px;
        display: none;
    }

    .current-image {
        max-width: 200px;
        max-height: 200px;
        margin-bottom: 10px;
    }

    .error-message {
        color: #dc3545;
        font-size: 0.875em;
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
                        <h4>Edit Product</h4>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data" novalidate>
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                            <div class="mb-3">
                                <label for="name" class="form-label">Product Name <span
                                        class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name"
                                    name="name" value="<?= htmlspecialchars($form_data['name']) ?>" required>
                                <?php if (isset($errors['name'])): ?>
                                <div class="error-message"><?= $errors['name'] ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Price (Rp) <span
                                            class="text-danger">*</span></label>
                                    <input type="number"
                                        class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                                        id="price" name="price" value="<?= htmlspecialchars($form_data['price']) ?>"
                                        min="0" step="100" required>
                                    <?php if (isset($errors['price'])): ?>
                                    <div class="error-message"><?= $errors['price'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="kategori" class="form-label">Category <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select <?= isset($errors['kategori']) ? 'is-invalid' : '' ?>"
                                        id="kategori" name="kategori" required>
                                        <option value="">Select Category</option>
                                        <option value="Sembako"
                                            <?= $form_data['kategori'] === 'Sembako' ? 'selected' : '' ?>>Sembako
                                        </option>
                                        <option value="Sayuran"
                                            <?= $form_data['kategori'] === 'Sayuran' ? 'selected' : '' ?>>Sayuran
                                        </option>
                                        <option value="Buah" <?= $form_data['kategori'] === 'Buah' ? 'selected' : '' ?>>
                                            Fruit</option>
                                        <option value="Protein"
                                            <?= $form_data['kategori'] === 'Protein' ? 'selected' : '' ?>>Protein
                                        </option>
                                        <option value="Herbal"
                                            <?= $form_data['kategori'] === 'Herbal' ? 'selected' : '' ?>>Herbal</option>
                                    </select>
                                    <?php if (isset($errors['kategori'])): ?>
                                    <div class="error-message"><?= $errors['kategori'] ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span
                                        class="text-danger">*</span></label>
                                <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                    id="description" name="description" rows="4" required><?= 
                                    htmlspecialchars($form_data['description']) ?></textarea>
                                <?php if (isset($errors['description'])): ?>
                                <div class="error-message"><?= $errors['description'] ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="stock" name="stock"
                                        value="<?= htmlspecialchars($form_data['stock']) ?>" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="weight" class="form-label">Weight (grams)</label>
                                    <input type="number" class="form-control" id="weight" name="weight"
                                        value="<?= htmlspecialchars($form_data['weight']) ?>" min="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="kondisi" class="form-label">Condition</label>
                                    <select class="form-select" id="kondisi" name="kondisi">
                                        <option value="new" <?= $form_data['kondisi'] === 'new' ? 'selected' : '' ?>>New
                                        </option>
                                        <option value="used" <?= $form_data['kondisi'] === 'used' ? 'selected' : '' ?>>
                                            Used</option>
                                        <option value="refurbished"
                                            <?= $form_data['kondisi'] === 'refurbished' ? 'selected' : '' ?>>Refurbished
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Current Image</label>
                                <?php if (!empty($product['image_url'])): ?>
                                <img src="../uploads/products/<?= htmlspecialchars($product['image_url']) ?>"
                                    class="current-image img-thumbnail d-block">
                                <?php else: ?>
                                <p class="text-muted">No image available</p>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">New Image (leave blank to keep current)</label>
                                <input type="file"
                                    class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>" id="image"
                                    name="image" accept="image/*">
                                <small class="text-muted">Formats: JPG, PNG, GIF (Max 2MB)</small>
                                <?php if (isset($errors['image'])): ?>
                                <div class="error-message"><?= $errors['image'] ?></div>
                                <?php endif; ?>
                                <img id="imagePreview" src="#" alt="Image Preview"
                                    class="product-image-preview img-thumbnail">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Back
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Image preview functionality
    document.getElementById('image').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.getElementById('imagePreview');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(file);
        } else {
            preview.src = '#';
            preview.style.display = 'none';
        }
    });

    // Form validation
    document.querySelector('form').addEventListener('submit', function(e) {
        const requiredFields = document.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('is-invalid');
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Please fill all required fields!');
        }
    });
    </script>
</body>

</html>