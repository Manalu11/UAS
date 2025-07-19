<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: login.php");
    exit();
}

$kategoriTersedia = [
    'Benih' => 'fas fa-seedling',
    'Alat Tani' => 'fas fa-tractor',
    'Pupuk' => 'fas fa-flask',
    'Pestisida' => 'fas fa-spray-can',
    'Pakaian' => 'fas fa-tshirt',
    'Edukasi' => 'fas fa-book',
    'Perlengkapan' => 'fas fa-tools',
    'Organik' => 'fas fa-leaf',
    'Gudang' => 'fas fa-warehouse',
    'hasil tani' => 'fas fa-carrot',
];

// Query produk
$queryProduk = mysqli_query($conn, "SELECT posts.*, users.username, users.foto_profil, users.nomor_wa
                                   FROM posts
                                   JOIN users ON posts.user_id = users.id
                                   ORDER BY posts.created_at DESC");
$produkList = mysqli_fetch_all($queryProduk, MYSQLI_ASSOC);

// Filter berdasarkan kategori jika ada
$kategoriAktif = isset($_GET['kategori']) ? $_GET['kategori'] : '';
if ($kategoriAktif && array_key_exists($kategoriAktif, $kategoriTersedia)) {
    $queryProduk = mysqli_query($conn, "SELECT posts.*, users.username, users.foto_profil, users.nomor_wa
                                      FROM posts
                                      JOIN users ON posts.user_id = users.id
                                      WHERE posts.kategori = '".mysqli_real_escape_string($conn, $kategoriAktif)."'
                                      ORDER BY posts.created_at DESC");
    $produkList = mysqli_fetch_all($queryProduk, MYSQLI_ASSOC);
}

// Ambil statistik kategori
$queryStats = mysqli_query($conn, "SELECT kategori, COUNT(*) as jumlah FROM posts WHERE kategori IS NOT NULL GROUP BY kategori");
$statsKategori = [];
while ($row = mysqli_fetch_assoc($queryStats)) {
    $statsKategori[$row['kategori']] = $row['jumlah'];
}

// Ambil komentar untuk semua produk sekaligus
$produkIds = array_column($produkList, 'id');
$commentsByProduct = [];

if (!empty($produkIds)) {
    $ids = implode(",", $produkIds);
    $queryKomentar = mysqli_query($conn, 
        "SELECT c.*, u.username, u.foto_profil 
         FROM comments c 
         JOIN users u ON c.user_id = u.id 
         WHERE c.post_id IN ($ids) 
         ORDER BY c.created_at ASC");
    
    while ($komentar = mysqli_fetch_assoc($queryKomentar)) {
        if (!isset($commentsByProduct[$komentar['post_id']])) {
            $commentsByProduct[$komentar['post_id']] = [];
        }
        $commentsByProduct[$komentar['post_id']][] = $komentar;
    }
}

// Pesan notifikasi
$message = '';
$messageType = '';
if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = $_GET['error'];
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beranda - Petani Berdasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>">
</head>

<body>
    <?php if (!empty($message)): ?>
    <div class="alert <?= $messageType ?>" id="alertMessage">
        <?= htmlspecialchars($message) ?>
        <span class="alert-close" onclick="closeAlert()">&times;</span>
    </div>
    <?php endif; ?>

    <header class="header">
        <div class="header-container">
            <a href="beranda.php" class="logo">
                <img src="logo.jpg" alt="Petani Berdasi">
                <span class="logo-text">Petani Berdasi</span>
            </a>
            <div class="search-bar">
                <input type="text" placeholder="Cari produk..." id="searchInput" onkeypress="handleSearchEnter(event)">
                <button onclick="searchProducts()"><i class="fas fa-search"></i></button>
            </div>
            <div class="user-actions">
                <a href="profil.php"><i class="fas fa-user"></i><span>Profil</span></a>
                <a href="#" id="cartButton">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Keranjang</span>
                    <span id="cartCount" style="display: none;">0</span>
                </a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </div>
        </div>
    </header>

    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="beranda.php" <?= !$kategoriAktif ? 'class="active"' : '' ?>>Beranda</a></li>
                <li>
                    <a href="#">Kategori <i class="fas fa-caret-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="beranda.php">Semua Kategori</a></li>
                        <?php foreach ($kategoriTersedia as $kategori => $icon): ?>
                        <li>
                            <a href="beranda.php?kategori=<?= urlencode($kategori) ?>"
                                data-count="<?= isset($statsKategori[$kategori]) ? $statsKategori[$kategori] : 0 ?>">
                                <?= htmlspecialchars($kategori) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <li><a href="tentang.php">Tentang Kami</a></li>
                <li><a href="kontak.php">Kontak</a></li>
                <li><a href="produk.php" class="post-btn">Jual Produk</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="categories">
            <?php foreach ($kategoriTersedia as $kategori => $icon): ?>
            <a href="beranda.php?kategori=<?= urlencode($kategori) ?>" class="category-item">
                <div class="category-icon"><i class="<?= $icon ?>"></i></div>
                <span class="category-name"><?= $kategori ?></span>
                <?php if (isset($statsKategori[$kategori]) && $statsKategori[$kategori] > 0): ?>
                <span class="category-count"><?= $statsKategori[$kategori] ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($kategoriAktif): ?>
        <div class="kategori-info">
            <i class="<?= $kategoriTersedia[$kategoriAktif] ?>"></i>
            <h2 class="kategori-title"><?= htmlspecialchars($kategoriAktif) ?></h2>
            <p class="kategori-desc">Menampilkan <?= count($produkList) ?> produk dalam kategori
                <?= htmlspecialchars($kategoriAktif) ?></p>
        </div>
        <?php else: ?>
        <h2 class="section-title">Produk Terbaru</h2>
        <?php endif; ?>

        <?php if (!empty($produkList)): ?>
        <div class="products-grid">
            <?php foreach ($produkList as $produk): ?>
            <div class="product-card">
                <?php if ($produk['user_id'] == $_SESSION['user_id']): ?>
                <span class="product-owner-badge">Produk Anda</span>
                <?php endif; ?>
                <img src="/<?= htmlspecialchars($produk['gambar_path']) ?>"
                    alt="<?= htmlspecialchars($produk['judul']) ?>" class="product-image">
                <div class="product-info">
                    <h3 class="product-name"><?= htmlspecialchars($produk['judul']) ?></h3>
                    <div class="product-price">Rp <?= number_format($produk['harga'], 0, ',', '.') ?></div>
                    <span class="product-category"><?= htmlspecialchars($produk['kategori']) ?></span>
                    <div class="product-sold">Terjual: <?= $produk['terjual'] ?? 0 ?></div>
                    <div class="product-location">
                        <?= isset($produk['lokasi']) ? htmlspecialchars($produk['lokasi']) : '' ?></div>

                    <div class="product-actions">
                        <button class="btn-keranjang" onclick="addToCart(<?= $produk['id'] ?>)">
                            <i class="fas fa-cart-plus"></i>
                        </button>
                        <button class="btn-hubungi"
                            onclick="checkoutProduct(<?= $produk['id'] ?>, '<?= htmlspecialchars($produk['judul']) ?>', <?= $produk['harga'] ?>, '<?= htmlspecialchars($produk['username']) ?>', '<?= htmlspecialchars($produk['nomor_wa']) ?>')">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                        <?php if ($produk['user_id'] == $_SESSION['user_id']): ?>
                        <button class="btn-hapus" onclick="confirmDelete(<?= $produk['id'] ?>)">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                        <button class="btn-komentar" onclick="toggleComments(<?= $produk['id'] ?>)">
                            <i class="far fa-comment"></i>
                            <span class="comment-count" id="comment-count-<?= $produk['id'] ?>">
                                <?= isset($commentsByProduct[$produk['id']]) ? count($commentsByProduct[$produk['id']]) : 0 ?>
                            </span>
                        </button>
                    </div>

                    <!-- Komentar Section -->
                    <div id="comments-<?= $produk['id'] ?>" class="comments-container" style="display:none;">
                        <div id="comments-list-<?= $produk['id'] ?>">
                            <!-- Tempat komentar akan dimuat via JavaScript -->
                            <div class="loading-comments">
                                <i class="fas fa-spinner fa-spin"></i> Memuat komentar...
                            </div>
                        </div>

                        <!-- Reply Form (hidden by default) -->
                        <div id="reply-form-container-<?= $produk['id'] ?>" class="reply-form-container"
                            style="display:none;">
                            <form class="reply-form" onsubmit="return addReply(event, <?= $produk['id'] ?>)">
                                <input type="hidden" name="parent_id" id="parent-id-<?= $produk['id'] ?>" value="">
                                <textarea name="reply_content" placeholder="Tulis balasan Anda..." required></textarea>
                                <div class="form-actions">
                                    <button type="submit" class="btn-submit-reply">
                                        <i class="fas fa-paper-plane"></i> Kirim Balasan
                                    </button>
                                    <button type="button" class="btn-cancel-reply"
                                        onclick="hideReplyForm(<?= $produk['id'] ?>)">
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Main Comment Form -->
                        <div class="comment-form-container">
                            <form class="form-komentar" onsubmit="return addComment(event, <?= $produk['id'] ?>)">
                                <textarea name="comment" placeholder="Tambahkan komentar..." required></textarea>
                                <div class="form-actions">
                                    <button type="submit" class="submit-comment-btn">
                                        <i class="fas fa-paper-plane"></i> Kirim
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-category">
            <i class="fas fa-box-open"></i>
            <h3>Tidak ada produk yang ditemukan</h3>
            <p>Silakan coba kategori lain atau tambahkan produk baru</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cart Modal -->
    <!-- Cart Modal -->
    <div id="cartModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Keranjang Belanja</h3>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <div id="cartItems"></div>
            <div class="cart-total">Total: Rp <span id="cartTotal">0</span></div>
            <div class="cart-actions">
                <button class="btn-clear" onclick="clearCart()">Kosongkan Keranjang</button>
                <button class="btn-checkout" onclick="checkout()">Checkout via WhatsApp</button>
            </div>
        </div>
    </div>
    <div class="cart-actions">
        <button class="btn-clear" onclick="clearCart()">Kosongkan Keranjang</button>
        <button class="btn-checkout" onclick="checkout()">Checkout via WhatsApp</button>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="confirmModal" class="confirm-modal">
        <div class="confirm-content">
            <h3>Konfirmasi Hapus</h3>
            <p>Apakah Anda yakin ingin menghapus produk ini?</p>
            <div class="confirm-buttons">
                <button class="btn-confirm btn-cancel" onclick="closeConfirmModal()">Batal</button>
                <button class="btn-confirm btn-delete" id="confirmDeleteBtn">Hapus</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <p>&copy; <?= date('Y') ?> Petani Berdasi. All rights reserved.</p>
        </div>
    </footer>

    <!-- JavaScript Files -->
    <script src="script.js"></script>
</body>

</html>