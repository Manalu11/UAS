<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$product_id = $_GET['id'];

// Get product details with user info and ratings
// Di file detail.php, ganti query pertama dengan ini:
$query = "SELECT 
            p.*, 
            u.username, 
            u.foto_profil,
            COUNT(c.id) as comment_count,
            AVG(c.rating) as average_rating
          FROM products p 
          JOIN users u ON p.user_id = u.id 
          LEFT JOIN comments c ON p.id = c.product_id AND c.status = 'approved'
          WHERE p.id = ? AND p.deleted_at IS NULL
          GROUP BY p.id"; // Group by primary key

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: index.php");
    exit();
}

// Check if current user is the owner
$is_owner = ($product['user_id'] == $_SESSION['user_id']);

// Get approved comments
$query = "SELECT c.*, u.username, u.foto_profil 
          FROM comments c
          JOIN users u ON c.user_id = u.id
          WHERE c.product_id = ? AND c.status = 'approved'
          ORDER BY c.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
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

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <img src="<?= htmlspecialchars($product['foto_profil']) ?>" class="rounded-circle me-2"
                            width="40" height="40" alt="Profil">
                        <div>
                            <h6 class="mb-0"><?= htmlspecialchars($product['username']) ?></h6>
                            <small class="text-muted"><?= formatDate($product['created_at']) ?></small>
                        </div>
                        <?php if ($is_owner): ?>
                        <div class="ms-auto">
                            <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $product['id'] ?>">
                                <i class="fas fa-trash"></i> Hapus
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>

                    <img src="../uploads/products/<?= htmlspecialchars($product['image_url']) ?>" class="card-img-top"
                        alt="<?= htmlspecialchars($product['name']) ?>">

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="card-title mb-0"><?= htmlspecialchars($product['name']) ?></h3>
                            <h3 class="text-success mb-0">Rp <?= number_format($product['price'], 0, ',', '.') ?></h3>
                        </div>

                        <div class="rating mb-3">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i
                                class="fas fa-star <?= $i <= round($product['average_rating'] ?? 0) ? 'text-warning' : 'text-secondary' ?>"></i>
                            <?php endfor; ?>
                            <small>(<?= $product['comment_count'] ?> ulasan)</small>
                        </div>

                        <div class="card-text mb-4">
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </div>

                        <div class="product-meta mb-4">
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Kategori:</strong> <?= htmlspecialchars($product['kategori']) ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Stok:</strong> <?= htmlspecialchars($product['stock']) ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Berat:</strong> <?= htmlspecialchars($product['weight']) ?> gram</p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Kondisi:</strong>
                                        <?= $product['kondisi'] == 'new' ? 'Baru' : 
                                          ($product['kondisi'] == 'used' ? 'Bekas' : 'Rekondisi') ?>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Terjual:</strong> <?= htmlspecialchars($product['terjual']) ?></p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Dilihat:</strong> <?= htmlspecialchars($product['views']) ?></p>
                                </div>
                            </div>
                        </div>

                        <?php if (!$is_owner): ?>
                        <div class="d-grid mb-4">
                            <button class="btn btn-success btn-lg">
                                <i class="fas fa-shopping-cart"></i> Beli Sekarang
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- Komentar Section -->
                        <div class="mt-5">
                            <h5>Ulasan Produk</h5>

                            <?php if (empty($comments)): ?>
                            <p class="text-muted">Belum ada ulasan untuk produk ini</p>
                            <?php else: ?>
                            <div class="list-group mb-4">
                                <?php foreach ($comments as $comment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="<?= htmlspecialchars($comment['foto_profil']) ?>"
                                            class="rounded-circle me-2" width="30" height="30" alt="Profil">
                                        <strong><?= htmlspecialchars($comment['username']) ?></strong>
                                        <div class="ms-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i
                                                class="fas fa-star <?= $i <= ($comment['rating'] ?? 0) ? 'text-warning' : 'text-secondary' ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <small class="text-muted ms-2"><?= formatDate($comment['created_at']) ?></small>
                                    </div>
                                    <p class="mb-0"><?= htmlspecialchars($comment['comment']) ?></p>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!$is_owner): ?>
                            <div class="card">
                                <div class="card-body">
                                    <h6>Tambah Ulasan</h6>
                                    <form action="../actions/add_comment.php" method="POST">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <div class="mb-3">
                                            <label class="form-label">Rating</label>
                                            <div class="rating-input">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="far fa-star rating-star" data-rating="<?= $i ?>"></i>
                                                <?php endfor; ?>
                                                <input type="hidden" name="rating" id="ratingValue" value="0">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <textarea class="form-control" name="comment" rows="3"
                                                placeholder="Tulis ulasan Anda..." required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Kirim Ulasan</button>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Kembali ke Daftar Produk
                    </a>
                </div>
            </div>
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
        // Delete button
        $('.delete-btn').click(function() {
            var productId = $(this).data('id');
            $('#confirmDelete').attr('href', 'hapus.php?id=' + productId);
            $('#deleteModal').modal('show');
        });

        // Rating stars
        $('.rating-star').hover(function() {
            const rating = $(this).data('rating');
            $('.rating-star').each(function() {
                if ($(this).data('rating') <= rating) {
                    $(this).removeClass('far').addClass('fas text-warning');
                } else {
                    $(this).removeClass('fas text-warning').addClass('far');
                }
            });
        });

        $('.rating-star').click(function() {
            const rating = $(this).data('rating');
            $('#ratingValue').val(rating);
        });
    });
    </script>
</body>

</html>