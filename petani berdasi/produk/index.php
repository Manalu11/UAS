<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Get all products for the current user with additional info
$query = "SELECT p.id, p.name, p.description, p.price, p.image_url, p.created_at, 
          (SELECT COUNT(*) FROM comments WHERE product_id = p.id AND status = 'approved') as comment_count,
          (SELECT COALESCE(AVG(rating), 0) FROM comments WHERE product_id = p.id AND status = 'approved') as average_rating
          FROM products p 
          WHERE p.user_id = ? 
          AND p.deleted_at IS NULL
          GROUP BY p.id
          ORDER BY p.created_at DESC";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Saya - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
    .rating-stars {
        color: #ffc107;
        font-size: 1.2rem;
    }

    .rating-count {
        font-size: 0.9rem;
        color: #6c757d;
    }

    .product-image {
        height: 200px;
        object-fit: cover;
    }
    </style>
</head>

<body>
    <?php include '../includes/navbar_user.php'; ?>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Produk Saya</h2>
            <a href="tambah.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Tambah Produk
            </a>
        </div>

        <div class="row">
            <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="alert alert-info">Anda belum memiliki produk. <a href="tambah.php">Tambahkan produk pertama
                        Anda!</a></div>
            </div>
            <?php else: ?>
            <?php foreach ($products as $product): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="../uploads/products/<?= htmlspecialchars($product['image_url']) ?>"
                        class="card-img-top product-image" alt="<?= htmlspecialchars($product['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="card-text text-success">Rp <?= number_format($product['price'], 0, ',', '.') ?></p>

                        <!-- Rating Section -->
                        <div class="d-flex align-items-center mb-2">
                            <div class="rating-stars me-2">
                                <?php
                                $avgRating = $product['average_rating'] ?? 0;
                                $fullStars = floor($avgRating);
                                $hasHalfStar = ($avgRating - $fullStars) >= 0.5;
                                $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                                
                                for ($i = 0; $i < $fullStars; $i++) {
                                    echo '<i class="fas fa-star"></i>';
                                }
                                if ($hasHalfStar) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                }
                                for ($i = 0; $i < $emptyStars; $i++) {
                                    echo '<i class="far fa-star"></i>';
                                }
                                ?>
                            </div>
                            <span class="rating-count">
                                <?= number_format($avgRating, 1) ?> (<?= $product['comment_count'] ?> ulasan)
                            </span>
                        </div>

                        <p class="card-text text-muted">
                            <?= nl2br(htmlspecialchars(substr($product['description'], 0, 100))) . (strlen($product['description']) > 100 ? '...' : '') ?>
                        </p>
                    </div>
                    <div class="card-footer bg-white">
                        <a href="detail.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i> Detail
                        </a>
                        <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $product['id'] ?>">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus produk ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <a href="#" class="btn btn-danger" id="confirmDelete">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        $('.delete-btn').click(function() {
            var productId = $(this).data('id');
            $('#confirmDelete').attr('href', 'hapus.php?id=' + productId);
            $('#deleteModal').modal('show');
        });
    });
    </script>
</body>

</html>