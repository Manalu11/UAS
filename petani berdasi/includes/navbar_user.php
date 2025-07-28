<?php
// navbar_user.php - User navigation bar
?>
<?php

?>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="produk/detail.php">
            <img src="../assets/images/logo.jpg" alt="Logo" height="40">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="beranda.php">Beranda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/produk">Produk</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../tentang.php">Tentang Kami</a>
                </li>
            </ul>

            <div class="d-flex align-items-center">
                <a href="produk/transaksi/keranjang.php" class="btn btn-link position-relative me-3">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <span id="cartCount"
                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= getCartCount($conn, $_SESSION['user_id']) ?>
                    </span>
                    <?php endif; ?>
                </a>

                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                        <img src="<?= $_SESSION['foto_profil'] ?? 'assets/images/profiles/default.jpg' ?>"
                            class="rounded-circle me-2" width="30" height="30">
                        <?= $_SESSION['username'] ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i> Profil
                            </a></li>
                        <li>
                            <a href="/produk/">Produk Saya</a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li><img src="<?= $_SESSION['foto_profil'] ?? 'assets/images/profiles/default.jpg' ?>"
                            class="rounded-circle">
                        <li><a class="dropdown-item" href="auth/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a></li>
                    </ul>
                </div>
                <?php else: ?>
                <a href="auth/login.php" class="btn btn-outline-success me-2">Login</a>
                <a href="auth/register.php" class="btn btn-success">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>