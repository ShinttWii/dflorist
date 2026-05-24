<nav class="col-md-3 col-lg-2 d-md-block sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>" href="products.php">
                    <i class="fas fa-box"></i> Produk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart"></i> Pesanan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' || basename($_SERVER['PHP_SELF']) == 'customer_detail.php' ? 'active' : ''; ?>" href="customers.php">
                    <i class="fas fa-users"></i> Pelanggan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'chats.php' ? 'active' : ''; ?>" href="chats.php">
                    <i class="fas fa-comments"></i> Chat CS
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i> Laporan
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="fas fa-cog"></i> Pengaturan
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 56px;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 0;
    padding-top: 1rem;
    background-color: white;
    box-shadow: 2px 0 10px rgba(0, 0, 0, .05);
}

.sidebar .nav-link {
    font-weight: 500;
    color: #2C3E50;
    padding: 1rem 1.5rem;
    transition: all 0.3s;
    border-left: 3px solid transparent;
    font-family: 'Poppins', sans-serif;
}

.sidebar .nav-link:hover {
    background-color: rgba(255, 214, 232, 0.3);
    color: #FF69B4;
    border-left-color: #FFB3D9;
}

.sidebar .nav-link.active {
    background-color: rgba(255, 214, 232, 0.5);
    color: #FF69B4;
    border-left-color: #FF69B4;
    font-weight: 600;
}

.sidebar .nav-link i {
    margin-right: 12px;
    width: 20px;
    text-align: center;
}
</style>
