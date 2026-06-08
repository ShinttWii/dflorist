<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$totalOrders    = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue   = $pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orders WHERE order_status NOT IN ('dibatalkan')")->fetchColumn();
$pendingOrders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'menunggu_pembayaran'")->fetchColumn();
$prosesOrders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('dibayar','diproses','dikirim')")->fetchColumn();
$selesaiOrders  = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'selesai'")->fetchColumn();
$batalOrders    = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'dibatalkan'")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$totalProducts  = $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
$revenueMonth   = $pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orders WHERE order_status NOT IN ('dibatalkan') AND MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetchColumn();
$pendingCancel  = $pdo->query("SELECT COUNT(*) FROM cancellation_requests WHERE status='pending'")->fetchColumn();

// Filter periode untuk top produk
$topPeriod  = $_GET['top_period'] ?? 'month';
$topStart   = $_GET['top_start']  ?? date('Y-m-01');
$topEnd     = $_GET['top_end']    ?? date('Y-m-d');

if ($topPeriod === 'month') {
    $topStart = date('Y-m-01');
    $topEnd   = date('Y-m-d');
} elseif ($topPeriod === 'year') {
    $topStart = date('Y-01-01');
    $topEnd   = date('Y-m-d');
} elseif ($topPeriod === 'all') {
    $topStart = '2025-01-01';
    $topEnd   = date('Y-m-d');
}
// kalau 'custom', pakai topStart & topEnd dari GET

$stmtTop = $pdo->prepare("SELECT p.name, SUM(oi.quantity) AS qty FROM order_items oi JOIN products p ON oi.product_id=p.id JOIN orders o ON oi.order_id=o.id WHERE o.order_status NOT IN ('dibatalkan') AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY p.id ORDER BY qty DESC LIMIT 5");
$stmtTop->execute([$topStart, $topEnd]);
$topProducts    = $stmtTop->fetchAll();

$recentOrders   = $pdo->query("SELECT o.*, u.name AS customer_name FROM orders o JOIN users u ON o.user_id=u.id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin D'florist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid">
<div class="row">
<?php include 'includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Dashboard</h1>
        <small class="text-muted"><?php echo date('d F Y'); ?></small>
    </div>

    <!-- Row 1: Stat Cards -->
    <div class="row g-2 g-md-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="background:#e8f4fd;width:38px;height:38px;min-width:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-shopping-bag" style="color:#007bff;font-size:15px;"></i>
                        </div>
                        <div style="min-width:0;">
                            <div class="fw-bold lh-1" style="font-size:1.3rem;"><?php echo $totalOrders; ?></div>
                            <div class="text-muted" style="font-size:11px;white-space:nowrap;">Total Pesanan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="background:#fce8f3;width:38px;height:38px;min-width:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-wallet" style="color:#FF69B4;font-size:15px;"></i>
                        </div>
                        <div style="min-width:0;">
                            <div class="fw-bold lh-1" style="font-size:12px;color:#FF69B4;"><?php echo formatRupiah($revenueMonth); ?></div>
                            <div class="text-muted" style="font-size:11px;">Bln Ini</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="background:#e8f8f0;width:38px;height:38px;min-width:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-coins" style="color:#28a745;font-size:15px;"></i>
                        </div>
                        <div style="min-width:0;">
                            <div class="fw-bold lh-1" style="font-size:12px;color:#28a745;"><?php echo formatRupiah($totalRevenue); ?></div>
                            <div class="text-muted" style="font-size:11px;">Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-2 p-md-3">
                    <div class="d-flex align-items-center gap-2">
                        <div style="background:#e8f7fd;width:38px;height:38px;min-width:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-users" style="color:#17a2b8;font-size:15px;"></i>
                        </div>
                        <div style="min-width:0;">
                            <div class="fw-bold lh-1" style="font-size:1.3rem;"><?php echo $totalCustomers; ?></div>
                            <div class="text-muted" style="font-size:11px;white-space:nowrap;">Pelanggan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Status Pesanan -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <a href="orders.php?status=menunggu_pembayaran" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm text-center py-3">
                <div class="fw-bold fs-3 text-warning"><?php echo $pendingOrders; ?></div>
                <div class="small text-muted">Menunggu Bayar</div>
            </div></a>
        </div>
        <div class="col-6 col-md-3">
            <a href="orders.php?status=diproses" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm text-center py-3">
                <div class="fw-bold fs-3 text-primary"><?php echo $prosesOrders; ?></div>
                <div class="small text-muted">Diproses / Dikirim</div>
            </div></a>
        </div>
        <div class="col-6 col-md-3">
            <a href="orders.php?status=selesai" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm text-center py-3">
                <div class="fw-bold fs-3 text-success"><?php echo $selesaiOrders; ?></div>
                <div class="small text-muted">Selesai</div>
            </div></a>
        </div>
        <div class="col-6 col-md-3">
            <a href="cancellation_requests.php" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm text-center py-3">
                <div class="fw-bold fs-3 text-danger">
                    <?php echo $batalOrders; ?>
                    <?php if ($pendingCancel > 0): ?>
                    <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem"><?php echo $pendingCancel; ?></span>
                    <?php endif; ?>
                </div>
                <div class="small text-muted">Dibatalkan</div>
            </div></a>
        </div>
    </div>

    <!-- Row 3: Tabel + Top Produk -->
    <div class="row g-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom">
                    <h6 class="mb-0 fw-bold">Pesanan Terbaru</h6>
                    <a href="orders.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                </div>
                <!-- Desktop: tabel -->
                <div class="d-none d-md-block">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">No. Pesanan</th>
                                <th>Pelanggan</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr onclick="location.href='order_detail.php?id=<?php echo $order['id']; ?>'" style="cursor:pointer;">
                                <td class="ps-3 small fw-semibold" style="color:#FF69B4;"><?php echo $order['order_number']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td class="text-end small"><?php echo formatRupiah($order['total']); ?></td>
                                <td><span class="status-badge status-<?php echo $order['order_status']; ?>" style="font-size:.7rem;">
                                    <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                </span></td>
                                <td class="small text-muted"><?php echo date('d M', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                </div>
                <!-- Mobile: card list -->
                <div class="d-md-none">
                <?php foreach ($recentOrders as $order): ?>
                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                <div style="padding:12px 16px;border-bottom:1px solid #f0f0f0;">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div style="font-size:12px;font-weight:700;color:#FF69B4;"><?php echo $order['order_number']; ?></div>
                            <div style="font-size:13px;color:#333;"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                            <div style="font-size:11px;color:#999;"><?php echo date('d M Y', strtotime($order['created_at'])); ?></div>
                        </div>
                        <div class="text-end">
                            <div style="font-size:13px;font-weight:700;color:#333;"><?php echo formatRupiah($order['total']); ?></div>
                            <span class="status-badge status-<?php echo $order['order_status']; ?>" style="font-size:.65rem;">
                                <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                </a>
                <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-trophy text-warning me-1"></i> Top Produk Terlaris</h6>
                    <form method="GET" class="mt-2 d-flex gap-1 flex-wrap">
                        <select name="top_period" class="form-select form-select-sm" style="min-width:130px;" onchange="this.form.submit()">
                            <option value="month" <?php echo $topPeriod==='month'?'selected':''; ?>>Bulan Ini</option>
                            <option value="year"  <?php echo $topPeriod==='year' ?'selected':''; ?>>Tahun Ini</option>
                            <option value="all"   <?php echo $topPeriod==='all'  ?'selected':''; ?>>Semua Waktu</option>
                            <option value="custom"<?php echo $topPeriod==='custom'?'selected':''; ?>>Custom</option>
                        </select>
                        <?php if ($topPeriod === 'custom'): ?>
                        <input type="date" name="top_start" class="form-control form-control-sm" style="width:130px;" value="<?php echo $topStart; ?>">
                        <input type="date" name="top_end"   class="form-control form-control-sm" style="width:130px;" value="<?php echo $topEnd; ?>">
                        <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topProducts as $i => $p): ?>
                        <li class="list-group-item px-3 py-2 d-flex align-items-center gap-2">
                            <span class="badge rounded-pill" style="background:#FF69B4;min-width:22px;"><?php echo $i+1; ?></span>
                            <div>
                                <div class="small fw-semibold"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="text-muted" style="font-size:11px;"><?php echo $p['qty']; ?> terjual</div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                        <?php if (empty($topProducts)): ?>
                        <li class="list-group-item text-muted text-center small py-4">Belum ada data</li>
                        <?php endif; ?>
                    </ul>
                    <div class="px-3 py-2 border-top">
                        <small class="text-muted">
                            Periode: <?php echo date('d M Y', strtotime($topStart)); ?> &ndash; <?php echo date('d M Y', strtotime($topEnd)); ?>
                            &nbsp;&middot;&nbsp;
                            <a href="reports.php?type=product&start_date=<?php echo $topStart; ?>&end_date=<?php echo $topEnd; ?>" class="text-decoration-none" style="color:#FF69B4;">Lihat Laporan</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>