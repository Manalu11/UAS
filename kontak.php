<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $messageContent = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Improved WhatsApp message format
    $whatsappMessage = "Halo Admin Petani Berdasi,%0A%0ASaya *$name* ingin berkonsultasi tentang produk/layanan Anda.%0A%0ARincian Kontak:%0A📧 Email: $email%0A%0A📝 Pesan Saya:%0A$messageContent%0A%0ATerima kasih atas perhatiannya.%0ASaya menunggu kabar dari Anda.";
    
    // WhatsApp number
    $whatsappNumber = "6281265698443";
    
    // Create WhatsApp link
    $whatsappLink = "https://wa.me/$whatsappNumber?text=$whatsappMessage";
    
    // Redirect to WhatsApp
    header("Location: $whatsappLink");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontak - Petani Berdasi</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
    :root {
        --primary: #2e7d32;
        --secondary: #1b5e20;
        --light-green: #e8f5e9;
        --dark-gray: #333;
        --white: #ffffff;
        --danger: #dc3545;
        --success: #28a745;
        --warning: #ffc107;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    }

    body {
        background-color: var(--light-green);
        min-height: 100vh;
    }

    /* Alert Messages */
    .alert {
        padding: 12px 20px;
        margin-bottom: 20px;
        border-radius: 4px;
        display: none;
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1000;
        max-width: 400px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .alert.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-close {
        float: right;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        margin-left: 10px;
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

    .search-bar {
        flex-grow: 1;
        display: flex;
        margin-right: 20px;
    }

    .search-bar input {
        width: 100%;
        padding: 10px 15px;
        border: none;
        border-radius: 2px 0 0 2px;
        font-size: 0.9rem;
    }

    .search-bar button {
        background-color: var(--primary);
        color: white;
        border: none;
        padding: 0 15px;
        border-radius: 0 2px 2px 0;
        cursor: pointer;
    }

    .user-actions {
        display: flex;
        align-items: center;
    }

    .user-actions a {
        color: white;
        text-decoration: none;
        margin-left: 20px;
        font-size: 0.9rem;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    .user-actions i {
        font-size: 1.3rem;
        margin-bottom: 3px;
    }

    /* Navbar Styles */
    .navbar {
        background-color: var(--primary);
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
    }

    .nav-links li {
        margin-right: 20px;
    }

    .nav-links a {
        color: white;
        text-decoration: none;
        font-weight: 500;
        padding: 5px 10px;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .nav-links a:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .nav-links a.active {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .nav-links a.post-btn {
        background-color: #ffc107;
        color: var(--dark-gray);
    }

    .nav-links a.post-btn:hover {
        background-color: #ffca28;
    }

    /* Main Content */
    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 0 15px;
    }

    .page-title {
        text-align: center;
        margin-bottom: 30px;
        color: var(--primary);
    }

    .page-title h1 {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .page-title p {
        font-size: 1.1rem;
        color: var(--dark-gray);
    }

    .contact-section {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-bottom: 40px;
    }

    .contact-info {
        background-color: var(--white);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .contact-info h2 {
        color: var(--primary);
        margin-bottom: 20px;
        font-size: 1.5rem;
    }

    .contact-item {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        border-radius: 8px;
        background-color: var(--light-green);
        transition: transform 0.2s;
    }

    .contact-item:hover {
        transform: translateY(-2px);
    }

    .contact-icon {
        width: 50px;
        height: 50px;
        background-color: var(--primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .contact-icon i {
        color: white;
        font-size: 1.2rem;
    }

    .contact-details h3 {
        color: var(--primary);
        margin-bottom: 5px;
    }

    .contact-details p {
        color: var(--dark-gray);
        margin: 0;
    }

    .contact-details a {
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
    }

    .contact-details a:hover {
        text-decoration: underline;
    }

    .contact-form {
        background-color: var(--white);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .contact-form h2 {
        color: var(--primary);
        margin-bottom: 20px;
        font-size: 1.5rem;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        color: var(--dark-gray);
        font-weight: 500;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: var(--primary);
    }

    .form-group textarea {
        resize: vertical;
        min-height: 120px;
    }

    .btn-submit {
        background-color: var(--primary);
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 5px;
        font-size: 1rem;
        cursor: pointer;
        transition: background-color 0.3s;
        width: 100%;
    }

    .btn-submit:hover {
        background-color: var(--secondary);
    }

    /* Social Media Section */
    .social-section {
        background-color: var(--white);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        text-align: center;
    }

    .social-section h2 {
        color: var(--primary);
        margin-bottom: 20px;
        font-size: 1.5rem;
    }

    .social-links {
        display: flex;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
    }

    .social-link {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        border-radius: 25px;
        text-decoration: none;
        color: white;
        font-weight: 500;
        transition: transform 0.2s;
    }

    .social-link:hover {
        transform: translateY(-2px);
    }

    .social-link i {
        margin-right: 8px;
        font-size: 1.2rem;
    }

    .social-link.whatsapp {
        background-color: #25D366;
    }

    .social-link.facebook {
        background-color: #1877F2;
    }

    .social-link.instagram {
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
    }

    .social-link.youtube {
        background-color: #FF0000;
    }

    .social-link.twitter {
        background-color: #1DA1F2;
    }

    .social-link.telegram {
        background-color: #0088cc;
    }

    /* Map Section */
    .map-section {
        background-color: var(--white);
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-top: 30px;
    }

    .map-section h2 {
        color: var(--primary);
        margin-bottom: 20px;
        font-size: 1.5rem;
        text-align: center;
    }

    .map-container {
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .map-container iframe {
        width: 100%;
        height: 400px;
        border: none;
    }

    /* Footer */
    .footer {
        background-color: var(--primary);
        color: white;
        padding: 30px 0;
        margin-top: 40px;
    }

    .footer-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 15px;
        text-align: center;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .contact-section {
            grid-template-columns: 1fr;
        }

        .header-container {
            flex-wrap: wrap;
        }

        .search-bar {
            order: 3;
            margin-top: 10px;
            width: 100%;
        }

        .nav-links {
            flex-wrap: wrap;
        }

        .nav-links li {
            margin-bottom: 10px;
        }

        .social-links {
            flex-direction: column;
            align-items: center;
        }

        .social-link {
            width: 200px;
            justify-content: center;
        }

        .page-title h1 {
            font-size: 2rem;
        }
    }
    </style>
    </style>
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
                <input type="text" placeholder="Cari produk..." id="searchInput">
                <button onclick="searchProducts()"><i class="fas fa-search"></i></button>
            </div>

            <div class="user-actions">
                <a href="profil.php">
                    <i class="fas fa-user"></i>
                    <span>Profil</span>
                </a>
                <a href="beranda.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Keranjang</span>
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="beranda.php">Beranda</a></li>
                <li><a href="tentang.php">Tentang Kami</a></li>
                <li><a href="kontak.php" class="active">Kontak</a></li>
                <li><a href="produk.php" class="post-btn">Jual Produk</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="page-title">
            <h1>Hubungi Kami</h1>
            <p>Kami siap membantu Anda dalam mengembangkan bisnis pertanian</p>
        </div>

        <div class="contact-section">
            <div class="contact-info">
                <h2>Informasi Kontak</h2>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fab fa-whatsapp"></i>
                    </div>
                    <div class="contact-details">
                        <h3>WhatsApp</h3>
                        <p><a href="https://wa.me/6281265698443" target="_blank">+62812-6569-8443</a></p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Email</h3>
                        <p><a href="mailto:marcahayamanalu417@gmail.com">marcahayamanalu417@gmail.com</a></p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Telepon</h3>
                        <p><a href="tel:+6281265698443">+62 812-6569-8443</a></p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Alamat</h3>
                        <p>Desa Lobusunut, kecamatan parmonangan</p>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="contact-details">
                        <h3>Jam Operasional</h3>
                        <p>Senin - Jumat: 08:00 - 17:00<br>Sabtu: 08:00 - 14:00</p>
                    </div>
                </div>
            </div>

            <div class="contact-form">
                <h2>Kirim Pesan via WhatsApp</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Nama Lengkap</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="message">Pesan</label>
                        <textarea id="message" name="message" required
                            placeholder="Tulis pesan Anda di sini..."></textarea>
                    </div>

                    <button type="submit" class="btn-submit">
                        <i class="fab fa-whatsapp"></i> Kirim via WhatsApp
                    </button>
                </form>
            </div>
        </div>

        <div class="social-section">
            <h2>Ikuti Kami di Media Sosial</h2>
            <div class="social-links">
                <a href="https://wa.me/6281265698443" target="_blank" class="social-link whatsapp">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="https://www.facebook.com/profile.php?id=100077889016031" target="_blank"
                    class="social-link facebook">
                    <i class="fab fa-facebook-f"></i> Facebook
                </a>
                <a href="https://www.instagram.com/cay.manalu/profilecard/?igsh=bnQxaGVvdm9xeGJh" target="_blank"
                    class="social-link instagram">
                    <i class="fab fa-instagram"></i> Instagram
                </a>
                <a href="https://youtube.com/@de-lirik8814?si=D4Wg68omkEyHRKzS" target="_blank"
                    class="social-link youtube">
                    <i class="fab fa-youtube"></i> YouTube
                </a>
            </div>
        </div>

        <div class="map-section">
            <h2>Lokasi Kami</h2>
            <div class="map-container">
                <iframe
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d127264.93350528324!2d117.33558470393582!3d0.13717207448483005!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x320df46b2ad49d6f%3A0x1f35b09c37bac56f!2sBontang%2C%20Kalimantan%20Timur!5e0!3m2!1sen!2sid!4v1720089600000!5m2!1sen!2sid"
                    allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="footer-container">
            <p>&copy; <?= date('Y') ?> Petani Berdasi. All rights reserved.</p>
        </div>
    </footer>

    <script>
    // Tampilkan pesan alert jika ada
    <?php if (!empty($message)): ?>
    document.getElementById('alertMessage').style.display = 'block';
    setTimeout(() => {
        document.getElementById('alertMessage').style.display = 'none';
    }, 5000);
    <?php endif; ?>

    function closeAlert() {
        document.getElementById('alertMessage').style.display = 'none';
    }

    function searchProducts() {
        const query = document.getElementById('searchInput').value.trim();
        if (query) {
            window.location.href = 'beranda.php?search=' + encodeURIComponent(query);
        }
    }

    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchProducts();
        }
    });
    </script>
</body>

</html>