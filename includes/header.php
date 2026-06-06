<?php
ob_start(); // Start output buffering
require_once __DIR__ . '/functions.php';

// Update customer activity timestamp if logged in
if (isCustomerLoggedIn() && isset($_SESSION['customer_id'])) {
    try {
        $customerId = $_SESSION['customer_id'];
        $stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
        $stmt->execute([$customerId]);

        // Auto-cancel pesanan yang expired
        cancelExpiredOrders($pdo, $customerId);
        
        // Sinkronisasi data keranjang belanja (cart) dari database ke session
        loadCartFromDb($pdo, $customerId);
        
    } catch (Exception $e) {
        // Silent fail - don't break page if update fails
        error_log("Header Error: " . $e->getMessage());
    }
}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'D\'Florist - Toko Bunga Online'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <div class="logo-wrapper">
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="D'florist Logo" class="logo-img" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="logo-circle" style="display: none;">
                        <i class="fas fa-flower"></i>
                    </div>
                </div>
                <span class="brand-text">D'florist</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/products.php">Produk</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/reviews.php">Ulasan</a>
                    </li>
                    <?php if (isCustomerLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/orders.php">Pesanan Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/chat.php">
                            <i class="fas fa-comments"></i> Chat CS
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?php echo SITE_URL; ?>/cart.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="badge bg-danger position-absolute top-0 start-100 translate-middle cart-count" <?php if (getCartCount() == 0): ?>style="display:none"<?php endif; ?>>
                                <?php echo getCartCount(); ?>
                            </span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="accountDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isCustomerLoggedIn()): ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">Profil Saya</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/addresses.php">Alamat Saya</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">Logout</a></li>
                            <?php else: ?>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/login.php">Login</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/register.php">Daftar</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>