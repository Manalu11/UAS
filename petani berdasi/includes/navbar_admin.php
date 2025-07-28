<?php

// navbar_admin.php - Admin top navigation bar
?>
<?php
?>

<nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
    <div class="container-fluid">
        <button class="btn btn-link me-3" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>

        <div class="navbar-nav ms-auto">
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="navbarDropdown" role="button"
                    data-bs-toggle="dropdown">
                    <img src="<?= $_SESSION['foto_profil'] ?? '../../assets/images/profiles/default.jpg' ?>"
                        class="rounded-circle me-2" width="30" height="30">
                    <?= $_SESSION['username'] ?? 'Admin' ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../profil.php">
                            <i class="fas fa-user me-2"></i> Profil
                        </a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<script>
// Toggle sidebar on mobile
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.querySelector('.main-content').classList.toggle('sidebar-collapsed');
});
</script>