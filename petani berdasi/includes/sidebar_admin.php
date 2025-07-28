<?php
// sidebar_admin.php - Admin sidebar navigation
?>
<div class="sidebar bg-dark text-white" style="width: 250px; height: 100vh; position: fixed; left: 0; top: 0;">
    <div class="sidebar-header p-3 d-flex align-items-center">
        <img src="../assets/images/logo.jpg" alt="Logo" width="40" height="40" class="me-2">
        <h5 class="mb-0">Petani Berdasi</h5>
    </div>
    <ul class="nav flex-column p-3">
        <li class="nav-item">
            <a class="nav-link text-white" href="dashboard.php">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="../admin/produk/index.php">
                <i class="fas fa-box me-2"></i> Produk
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="../admin/users/index.php">
                <i class="fas fa-users me-2"></i> Pengguna
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link text-white" href="../admin/transaksi/detail.php">
                <i class="fas fa-exchange-alt me-2"></i> Transaksi
            </a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link text-white" href="../auth/logout.php">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>