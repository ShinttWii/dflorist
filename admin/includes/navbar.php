<nav class="navbar navbar-expand-lg navbar-light sticky-top" style="background-color: white; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo ADMIN_URL; ?>/dashboard.php" style="display: flex; align-items: center; gap: 10px;">
            <div class="logo-wrapper">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="D'florist Logo" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                <div class="logo-circle" style="display: none;">
                    <i class="fas fa-flower"></i>
                </div>
            </div>
            <span class="brand-text" style="font-family: 'Quicksand', sans-serif; font-weight: 700; color: #FF69B4;">D'florist Admin</span>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Lihat Website
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" style="gap: 8px;">
                        <div style="width: 35px; height: 35px; background: linear-gradient(135deg, #FFD6E8 0%, #FFB3D9 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #2C3E50; font-weight: 600;">
                            <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
                        </div>
                        <span style="font-weight: 500;"><?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo ADMIN_URL; ?>/settings.php"><i class="fas fa-cog"></i> Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo ADMIN_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
