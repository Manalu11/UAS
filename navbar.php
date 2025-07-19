<?php
$queryKategori = mysqli_query($conn, "SELECT DISTINCT kategori FROM posts");
$kategoriList = [];
while ($row = mysqli_fetch_assoc($queryKategori)) {
    $kategoriList[] = $row['kategori'];
}

$kategoriNames = [
    'benih' => 'Benih',
    'alat_tani' => 'Alat Tani',
    'pupuk' => 'Pupuk',
    'pestisida' => 'Pestisida',
    'pakaian' => 'Pakaian',
    'edukasi' => 'Edukasi',
    'perlengkapan' => 'Perlengkapan',
    'organik' => 'Organik',
    'gudang' => 'Gudang',
    'lainnya' => 'Lainnya'
];
?>

<nav class="navbar">
    <div class="nav-container">
        <ul class="nav-links">
            <li><a href="beranda.php">Beranda</a></li>

            <?php foreach ($kategoriList as $kategori): ?>
            <?php if (isset($kategoriNames[$kategori])): ?>
            <li><a href="kategori.php?kategori=<?= $kategori ?>">
                    <?= $kategoriNames[$kategori] ?>
                </a></li>
            <?php endif; ?>
            <?php endforeach; ?>

            <li><a href="post_produk.php" class="post-btn">
                    <i class="fas fa-plus"></i> Posting Produk
                </a></li>
        </ul>
    </div>
</nav>

<style>
.navbar {
    background-color: #2e7d32;
    padding: 10px 0;
}

.nav-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 15px;
}

.nav-links {
    display: flex;
    list-style: none;
    gap: 10px;
    flex-wrap: wrap;
}

.nav-links a {
    color: white;
    text-decoration: none;
    padding: 8px 12px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.nav-links a:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.nav-links a.post-btn {
    background-color: #ffc107;
    color: #333;
    margin-left: auto;
}

@media (max-width: 768px) {
    .nav-links {
        flex-direction: column;
    }

    .nav-links a.post-btn {
        margin-left: 0;
        margin-top: 10px;
    }
}
</style>