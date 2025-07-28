<?php
session_start();

// Debug mode
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Validasi CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Cek autentikasi pengguna
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

// Debug: Periksa koneksi database
if (!isset($conn) || !$conn) {
    die("Error: Tidak dapat terhubung ke database. Periksa file config/database.php");
}

// Fungsi untuk mendapatkan semua produk
function getProducts($conn, $currentUserId) {
    try {
        $query = "SELECT p.*, u.username, u.foto_profil, 
                  COALESCE((SELECT COUNT(*) FROM likes WHERE product_id = p.id), 0) AS like_count,
                  COALESCE((SELECT COUNT(*) FROM likes WHERE product_id = p.id AND user_id = ?), 0) AS is_liked
                  FROM products p
                  JOIN users u ON p.user_id = u.id
                  ORDER BY p.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Error prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $currentUserId);
        if (!$stmt->execute()) {
            throw new Exception("Error execute statement: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        // Log error dan return empty array
        error_log("Error in getProducts: " . $e->getMessage());
        return [];
    }
}

try {
    $products = getProducts($conn, $_SESSION['user_id']);
} catch (Exception $e) {
    die("Error mengambil produk: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Petani Berdasi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .product-card {
        border: 1px solid #e0e0e0;
        border-radius: 10px;
        overflow: hidden;
        transition: box-shadow 0.3s ease;
    }

    .product-card:hover {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .product-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 15px;
    }

    .btn-like,
    .btn-comment {
        background: none;
        border: none;
        padding: 5px 10px;
    }

    .btn-like:hover,
    .btn-comment:hover {
        background-color: #f8f9fa;
        border-radius: 5px;
    }
    </style>
</head>

<body>
    <!-- Include Navbar -->
    <?php 
    if (file_exists('includes/navbar_user.php')) {
        include 'includes/navbar_user.php';
    } else {
        echo '<nav class="navbar navbar-expand-lg navbar-dark bg-success">
                <div class="container">
                    <a class="navbar-brand" href="#">Petani Berdasi</a>
                    <div class="navbar-nav ms-auto">
                        <a class="nav-link" href="auth/logout.php">Logout</a>
                    </div>
                </div>
              </nav>';
    }
    ?>

    <div class="container mt-4">
        <!-- Debug Info -->
        <?php if (isset($_GET['debug'])): ?>
        <div class="alert alert-info">
            <strong>Debug Info:</strong><br>
            User ID: <?= $_SESSION['user_id'] ?? 'Not set' ?><br>
            Jumlah produk: <?= count($products) ?><br>
            Database Connected: <?= $conn ? 'Yes' : 'No' ?>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="text-center mb-4">Selamat Datang di Petani Berdasi</h2>
                <p class="text-center text-muted">Temukan produk pertanian segar langsung dari petani</p>
            </div>
        </div>

        <!-- Products Section -->
        <?php if (empty($products)): ?>
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h4>Belum Ada Produk</h4>
                    <p>Belum ada produk yang tersedia saat ini.</p>
                    <a href="produk/tambah.php" class="btn btn-success">Tambah Produk Pertama</a>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
            <div class="card product-card">
                <!-- Card Header -->
                <div class="card-header bg-white d-flex align-items-center">
                    <?php
                        $fotoProfil = 'assets/images/default-profile.jpg';
                        if (!empty($product['foto_profil'])) {
                            $path = htmlspecialchars($product['foto_profil']);
                            if (file_exists($path)) {
                                $fotoProfil = $path;
                            }
                        }
                        ?>
                    <img src="<?= $fotoProfil ?>" class="rounded-circle me-2" width="40" height="40"
                        alt="Profil <?= htmlspecialchars($product['username']) ?>"
                        onerror="this.src='assets/images/default-profile.jpg'">
                    <div class="flex-grow-1">
                        <h6 class="mb-0"><?= htmlspecialchars($product['username']) ?></h6>
                        <small class="text-muted">
                            <?= isset($product['created_at']) ? date('d M Y', strtotime($product['created_at'])) : 'Tanggal tidak tersedia' ?>
                        </small>
                    </div>
                    <?php if ($product['user_id'] == $_SESSION['user_id']): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="produk/edit.php?id=<?= $product['id'] ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a></li>
                            <li><a class="dropdown-item text-danger delete-product" href="#"
                                    data-id="<?= $product['id'] ?>">
                                    <i class="fas fa-trash"></i> Hapus
                                </a></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Product Image -->
                <?php
                    $productImage = 'assets/images/default-product.jpg';
                    if (!empty($product['image_url'])) {
                        $imagePath = 'uploads/products/' . basename($product['image_url']);
                        if (file_exists($imagePath)) {
                            $productImage = $imagePath;
                        }
                    }
                    ?>
                <img src="<?= htmlspecialchars($productImage) ?>" class="card-img-top"
                    alt="<?= htmlspecialchars($product['name']) ?>" style="height: 250px; object-fit: cover;"
                    onerror="this.src='assets/images/default-product.jpg'">

                <!-- Card Body -->
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title mb-0"><?= htmlspecialchars($product['name']) ?></h5>
                        <h5 class="text-success mb-0">Rp <?= number_format($product['price'], 0, ',', '.') ?></h5>
                    </div>

                    <p class="card-text text-muted small mb-3">
                        <?= nl2br(htmlspecialchars(substr($product['description'], 0, 100))) ?>
                        <?= strlen($product['description']) > 100 ? '...' : '' ?>
                    </p>

                    <!-- Product Actions -->
                    <div class="product-actions">
                        <button class="btn btn-like <?= $product['is_liked'] ? 'text-danger' : 'text-secondary' ?>"
                            data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-heart"></i>
                            <span class="like-count"><?= $product['like_count'] ?></span>
                        </button>

                        <button class="btn btn-comment text-secondary" data-bs-toggle="collapse"
                            data-bs-target="#comments-<?= $product['id'] ?>">
                            <i class="fas fa-comment"></i> Komentar
                        </button>

                        <?php if ($product['user_id'] != $_SESSION['user_id']): ?>
                        <button class="btn btn-success btn-sm ms-auto" data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-cart-plus"></i> Keranjang
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Comments Section -->
                    <div class="collapse mt-3" id="comments-<?= $product['id'] ?>">
                        <hr>
                        <div class="comments-container" id="comments-container-<?= $product['id'] ?>">
                            <p class="text-muted small">Memuat komentar...</p>
                        </div>

                        <form class="comment-form mt-3" data-product-id="<?= $product['id'] ?>">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <div class="input-group input-group-sm">
                                <input type="text" name="comment" class="form-control" placeholder="Tulis komentar..."
                                    required>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Apakah Anda yakin ingin menghapus produk ini?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Like button functionality
        $('.btn-like').click(function() {
            const productId = $(this).data('product-id');
            const button = $(this);

            $.ajax({
                url: 'api/toggle_like.php',
                method: 'POST',
                data: {
                    product_id: productId,
                    csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                },
                success: function(response) {
                    if (response.success) {
                        button.toggleClass('text-danger text-secondary');
                        button.find('.like-count').text(response.like_count);
                    }
                },
                error: function() {
                    console.log('Error toggling like');
                }
            });
        });

        // Delete product
        let deleteProductId = null;

        $('.delete-product').click(function(e) {
            e.preventDefault();
            deleteProductId = $(this).data('id');
            $('#deleteModal').modal('show');
        });

        $('#confirmDelete').click(function() {
            if (deleteProductId) {
                window.location.href = 'produk/hapus.php?id=' + deleteProductId;
            }
        });

        // Load comments when expanded
        $('.btn-comment').click(function() {
            const productId = $(this).data('bs-target').replace('#comments-', '');
            const container = $('#comments-container-' + productId);

            if (container.find('p').text() === 'Memuat komentar...') {
                // Load comments via AJAX
                $.get('api/get_comments.php?product_id=' + productId, function(data) {
                    container.html(data);
                }).fail(function() {
                    container.html('<p class="text-muted small">Gagal memuat komentar</p>');
                });
            }
        });
    });
    </script>
</body>

</html>