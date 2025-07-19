<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Ambil statistik untuk ditampilkan
$queryTotalProduk = mysqli_query($conn, "SELECT COUNT(*) as total FROM posts");
$totalProduk = mysqli_fetch_assoc($queryTotalProduk)['total'] ?? 0;

$queryTotalUser = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
$totalUser = mysqli_fetch_assoc($queryTotalUser)['total'] ?? 0;

$queryTotalKategori = mysqli_query($conn, "SELECT COUNT(DISTINCT kategori) as total FROM posts WHERE kategori IS NOT NULL");
$totalKategori = mysqli_fetch_assoc($queryTotalKategori)['total'] ?? 0;

$queryTotalTransaksi = mysqli_query($conn, "SELECT SUM(terjual) as total FROM posts");
$totalTransaksi = mysqli_fetch_assoc($queryTotalTransaksi)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Kami - Petani Berdasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
        --primary: #2e7d32;
        --secondary: #1b5e20;
        --light-green: #e8f5e9;
        --dark-gray: #333;
        --white: #ffffff;
        --light-gray: #f5f5f5;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    body {
        background-color: var(--light-green);
        line-height: 1.6;
    }

    /* Header Styles */
    .header {
        background: linear-gradient(to right, var(--secondary), var(--primary));
        padding: 15px 0;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 5px rgba(0, 0, 0, 0.1);
    }

    .header-container {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        padding: 0 15px;
    }

    .logo {
        display: flex;
        align-items: center;
        color: white;
        text-decoration: none;
        margin-right: 30px;
    }

    .logo img {
        height: 40px;
        margin-right: 10px;
    }

    .logo-text {
        font-size: 1.5rem;
        font-weight: 500;
    }

    .nav-menu {
        display: flex;
        list-style: none;
        margin-left: auto;
    }

    .nav-menu li {
        margin-left: 30px;
    }

    .nav-menu a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        padding: 8px 15px;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .nav-menu a:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .nav-menu a.active {
        background-color: rgba(255, 255, 255, 0.3);
    }

    /* Hero Section */
    .hero-section {
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        padding: 80px 0;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="rgba(255,255,255,0.1)"><path d="M0,20 Q250,80 500,20 T1000,20 L1000,100 L0,100 Z"/></svg>') repeat-x;
        background-size: 1000px 100px;
        background-position: bottom;
    }

    .hero-content {
        max-width: 800px;
        margin: 0 auto;
        padding: 0 20px;
        position: relative;
        z-index: 1;
    }

    .hero-title {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 20px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .hero-subtitle {
        font-size: 1.3rem;
        margin-bottom: 30px;
        opacity: 0.9;
    }

    .hero-description {
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
        opacity: 0.8;
    }

    /* Main Content */
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* Stats Section */
    .stats-section {
        background: var(--white);
        padding: 60px 0;
        margin-top: -30px;
        border-radius: 20px 20px 0 0;
        box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.1);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 30px;
        margin-top: 40px;
    }

    .stat-item {
        text-align: center;
        padding: 30px 20px;
        background: var(--light-gray);
        border-radius: 15px;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
        font-size: 3rem;
        color: var(--primary);
        margin-bottom: 15px;
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }

    .stat-label {
        font-size: 1.1rem;
        color: var(--dark-gray);
        font-weight: 500;
    }

    /* About Section */
    .about-section {
        padding: 80px 0;
    }

    .section-title {
        text-align: center;
        font-size: 2.5rem;
        color: var(--primary);
        margin-bottom: 50px;
        position: relative;
    }

    .section-title::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: var(--primary);
        border-radius: 2px;
    }

    .about-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        align-items: center;
        margin-bottom: 80px;
    }

    .about-text {
        font-size: 1.1rem;
        color: var(--dark-gray);
        line-height: 1.8;
    }

    .about-text p {
        margin-bottom: 20px;
    }

    .about-image {
        text-align: center;
    }

    .about-image img {
        width: 100%;
        max-width: 500px;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    /* Features Section */
    .features-section {
        background: var(--white);
        padding: 80px 0;
        border-radius: 20px;
        margin-bottom: 40px;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 40px;
        margin-top: 50px;
    }

    .feature-item {
        text-align: center;
        padding: 40px 20px;
        background: var(--light-green);
        border-radius: 15px;
        transition: transform 0.3s;
    }

    .feature-item:hover {
        transform: translateY(-10px);
    }

    .feature-icon {
        font-size: 3.5rem;
        color: var(--primary);
        margin-bottom: 20px;
    }

    .feature-title {
        font-size: 1.4rem;
        color: var(--primary);
        margin-bottom: 15px;
        font-weight: 600;
    }

    .feature-desc {
        color: var(--dark-gray);
        font-size: 1rem;
        line-height: 1.6;
    }

    /* Mission Vision Section */
    .mission-vision {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-top: 60px;
    }

    .mission-item,
    .vision-item {
        background: var(--white);
        padding: 40px;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
    }

    .mission-item h3,
    .vision-item h3 {
        color: var(--primary);
        font-size: 1.5rem;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
    }

    .mission-item i,
    .vision-item i {
        font-size: 2rem;
        margin-right: 15px;
    }

    /* Team Section */
    .team-section {
        background: var(--white);
        padding: 80px 0;
        border-radius: 20px;
        margin-top: 40px;
    }

    .team-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 30px;
        margin-top: 50px;
    }

    .team-member {
        text-align: center;
        padding: 30px;
        background: var(--light-gray);
        border-radius: 15px;
        transition: transform 0.3s;
    }

    .team-member:hover {
        transform: translateY(-5px);
    }

    .team-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        margin: 0 auto 20px;
        background: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3rem;
        color: white;
    }

    .team-name {
        font-size: 1.3rem;
        color: var(--primary);
        margin-bottom: 10px;
        font-weight: 600;
    }

    .team-role {
        color: var(--dark-gray);
        font-size: 1rem;
        margin-bottom: 15px;
    }

    .team-desc {
        color: var(--dark-gray);
        font-size: 0.9rem;
        line-height: 1.6;
    }

    /* Footer */
    .footer {
        background-color: var(--primary);
        color: white;
        padding: 40px 0;
        text-align: center;
        margin-top: 60px;
    }

    /* Responsive */
    @media (max-width: 992px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .features-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .about-content {
            grid-template-columns: 1fr;
            gap: 40px;
        }

        .mission-vision {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .hero-title {
            font-size: 2.2rem;
        }

        .hero-subtitle {
            font-size: 1.1rem;
        }

        .stats-grid {
            grid-template-columns: 1fr;
        }

        .features-grid {
            grid-template-columns: 1fr;
        }

        .team-grid {
            grid-template-columns: 1fr;
        }

        .nav-menu {
            display: none;
        }
    }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-container">
            <a href="beranda.php" class="logo">
                <img src="logo.jpg" alt="Petani Berdasi">
                <span class="logo-text">Petani Berdasi</span>
            </a>
            <ul class="nav-menu">
                <li><a href="beranda.php">Beranda</a></li>
                <li><a href="tentang.php" class="active">Tentang Kami</a></li>
                <li><a href="kontak.php">Kontak</a></li>
                <li><a href="produk.php">Jual Produk</a></li>
                <li><a href="profil.php">Profil</a></li>
            </ul>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Tentang Petani Berdasi</h1>
            <p class="hero-subtitle">Menjembatani Petani dengan Konsumen</p>
            <p class="hero-description">
                Platform digital yang menghubungkan petani lokal dengan konsumen,
                membantu meningkatkan kesejahteraan petani dan menyediakan produk segar berkualitas.
            </p>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-number"><?= number_format($totalProduk) ?></div>
                    <div class="stat-label">Total Produk</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?= number_format($totalUser) ?></div>
                    <div class="stat-label">Petani Terdaftar</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div class="stat-number"><?= number_format($totalKategori) ?></div>
                    <div class="stat-label">Kategori Produk</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div class="stat-number"><?= number_format($totalTransaksi) ?></div>
                    <div class="stat-label">Transaksi Berhasil</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section">
        <div class="container">
            <h2 class="section-title">Tentang Kami</h2>
            <div class="about-content">
                <div class="about-text">
                    <p>
                        <strong>Petani Berdasi</strong> adalah platform digital inovatif yang diciptakan untuk
                        menjembatani
                        kesenjangan antara petani lokal dan konsumen. Kami memahami bahwa petani Indonesia menghadapi
                        berbagai tantangan dalam memasarkan hasil panen mereka.
                    </p>
                    <p>
                        Dengan teknologi yang mudah digunakan, kami membantu petani untuk memasarkan produk mereka
                        secara langsung kepada konsumen, menghilangkan perantara yang seringkali merugikan petani.
                        Hal ini tidak hanya meningkatkan pendapatan petani, tetapi juga memberikan harga yang lebih
                        terjangkau bagi konsumen.
                    </p>
                    <p>
                        Kami berkomitmen untuk mendukung pertanian berkelanjutan dan memberikan akses yang mudah
                        bagi masyarakat untuk mendapatkan produk segar, berkualitas, dan langsung dari sumbernya.
                    </p>
                </div>
                <div class="about-image">
                    <img src="https://asset.kompas.com/crops/tAd--MTfNIt0VFZHScim_Um2g0o=/0x0:1000x667/1200x800/data/photo/2020/01/29/5e30e9bc69af5.jpg"
                        alt="Petani Indonesia">
                </div>
            </div>

            <!-- Mission Vision -->
            <div class="mission-vision">
                <div class="mission-item">
                    <h3>
                        <i class="fas fa-bullseye"></i>
                        Misi Kami
                    </h3>
                    <p>
                        Memberdayakan petani Indonesia melalui platform digital yang memudahkan akses pasar,
                        meningkatkan pendapatan, dan menjamin kualitas produk pertanian untuk kesejahteraan bersama.
                    </p>
                </div>
                <div class="vision-item">
                    <h3>
                        <i class="fas fa-eye"></i>
                        Visi Kami
                    </h3>
                    <p>
                        Menjadi platform terdepan dalam menghubungkan petani dengan konsumen di Indonesia,
                        menciptakan ekosistem pertanian yang berkelanjutan dan menguntungkan semua pihak.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <h2 class="section-title">Keunggulan Kami</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Mudah Digunakan</h3>
                    <p class="feature-desc">
                        Platform yang user-friendly dan dapat diakses melalui berbagai perangkat,
                        memudahkan petani untuk mengelola dan memasarkan produk mereka.
                    </p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3 class="feature-title">Aman & Terpercaya</h3>
                    <p class="feature-desc">
                        Sistem keamanan berlapis dan verifikasi produk yang ketat untuk menjamin
                        kualitas dan keamanan transaksi bagi semua pengguna.
                    </p>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Meningkatkan Pendapatan</h3>
                    <p class="feature-desc">
                        Membantu petani mendapatkan harga yang lebih baik dengan menghilangkan
                        perantara dan memberikan akses langsung ke pasar.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <h2 class="section-title">Tim Kami</h2>
            <div class="team-grid">
                <div class="team-member">
                    <div class="team-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="team-name">Cahaya</h3>
                    <p class="team-role">Founder & CEO</p>
                    <p class="team-desc">
                        Berlatar belakang pertanian dengan pengalaman 15 tahun di bidang agribisnis
                        dan teknologi informasi.
                    </p>
                </div>
                <div class="team-member">
                    <div class="team-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="team-name">Siti vidi</h3>
                    <p class="team-role">Head of Operations</p>
                    <p class="team-desc">
                        Ahli dalam manajemen rantai pasok dan pemberdayaan komunitas petani
                        di seluruh Indonesia.
                    </p>
                </div>
                <div class="team-member">
                    <div class="team-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="team-name">Nurhidayah</h3>
                    <p class="team-role">Technology Lead</p>
                    <p class="team-desc">
                        Pengembang platform dengan keahlian dalam teknologi web dan mobile
                        untuk solusi pertanian digital.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> Petani Berdasi. Semua hak dilindungi undang-undang.</p>
            <p>Bersama membangun pertanian Indonesia yang lebih baik</p>
        </div>
    </footer>
</body>

</html>