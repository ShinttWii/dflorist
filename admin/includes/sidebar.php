<nav class="col-md-3 col-lg-2 d-md-block sidebar" id="adminSidebar">
    <div class="pt-3">
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

<!-- Overlay untuk mobile -->
<div id="sidebarOverlay" onclick="closeSidebar()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>

<style>
/* Desktop */
.sidebar {
    position: fixed;
    top: 56px;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 0;
    padding-top: 0.5rem;
    background-color: white;
    box-shadow: 2px 0 10px rgba(0,0,0,.05);
    overflow-y: auto;
    width: 200px;
}

/* Mobile: sidebar hidden by default */
@media (max-width: 767px) {
    .sidebar {
        left: -220px;
        transition: left 0.3s ease;
        top: 56px;
        width: 220px;
    }
    .sidebar.open {
        left: 0;
    }
    /* Main content full width on mobile */
    main.col-md-9, main.col-lg-10 {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        flex: 0 0 100% !important;
        padding: 0 12px !important;
    }
}

.sidebar .nav-link {
    font-weight: 500;
    color: #2C3E50;
    padding: 0.85rem 1.5rem;
    transition: all 0.3s;
    border-left: 3px solid transparent;
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
}
.sidebar .nav-link:hover {
    background-color: rgba(255,214,232,0.3);
    color: #FF69B4;
    border-left-color: #FFB3D9;
}
.sidebar .nav-link.active {
    background-color: rgba(255,214,232,0.5);
    color: #FF69B4;
    border-left-color: #FF69B4;
    font-weight: 600;
}
.sidebar .nav-link i {
    margin-right: 10px;
    width: 18px;
    text-align: center;
}
</style>

<script>
function openSidebar() {
    document.getElementById('adminSidebar').classList.add('open');
    document.getElementById('sidebarOverlay').style.display = 'block';
}
function closeSidebar() {
    document.getElementById('adminSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').style.display = 'none';
}
</script>
