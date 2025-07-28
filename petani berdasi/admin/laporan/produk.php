<?php
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Validasi akses admin
if (!isAdmin()) {
    header("Location: ../../auth/login.php");
    exit();
}

// Ambil parameter filter
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'sold';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Query untuk laporan produk
$where = "";
$params = [];
$types = "";

if ($category > 0) {
    $where = "WHERE p.category_id = ?";
    $params = [$category];
    $types = "i";
}

$order_by = "";
switch ($sort) {
    case 'sold':
        $order_by = "total_sold DESC";
        break;
    case 'revenue':
        $order_by = "total_revenue DESC";
        break;
    case 'rating':
        $order_by = "average_rating DESC";
        break;
    default:
        $order_by = "total_sold DESC";
}

$products = query("SELECT p.id, p.name, p.price, 
                  COUNT(t.id) as total_sold,
                  SUM(ti.quantity * ti.price) as total_revenue,
                  AVG(r.rating) as average_rating,
                  c.name as category_name
                  FROM products p
                  LEFT JOIN transaction_items ti ON p.id = ti.product_id
                  LEFT JOIN transactions t ON ti.transaction_id = t.id AND t.status = 'completed'
                  LEFT JOIN reviews r ON p.id = r.product_id
                  LEFT JOIN categories c ON p.category_id = c.id
                  $where
                  GROUP BY p.id
                  ORDER BY $order_by", $params, $types)->fetch_all(MYSQLI_ASSOC);

// Ambil daftar kategori untuk dropdown
$categories = query("SELECT id, name FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Produk - Petani Berdasi</title>
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
                <h2>Laporan Produk</h2>
                <button class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print"></i> Cetak Laporan
                </button>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="sort" class="form-label">Urutkan Berdasarkan</label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="sold" <?= $sort == 'sold' ? 'selected' : '' ?>>Terlaris</option>
                                    <option value="revenue" <?= $sort == 'revenue' ? 'selected' : '' ?>>Pendapatan
                                        Tertinggi</option>
                                    <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>Rating Tertinggi
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="category" class="form-label">Kategori</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="0">Semua Kategori</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end mb-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Products Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Terjual</th>
                                    <th>Pendapatan</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                                    <td>Rp <?= number_format($product['price'], 0, ',', '.') ?></td>
                                    <td><?= $product['total_sold'] ?></td>
                                    <td>Rp <?= number_format($product['total_revenue'] ?? 0, 0, ',', '.') ?></td>
                                    <td>
                                        <?php if ($product['average_rating']): ?>
                                        <?= number_format($product['average_rating'], 1) ?>
                                        <small class="text-warning">
                                            <?= str_repeat('★', round($product['average_rating'])) ?>
                                            <?= str_repeat('☆', 5 - round($product['average_rating'])) ?>
                                        </small>
                                        <?php else: ?>
                                        Belum ada rating
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>