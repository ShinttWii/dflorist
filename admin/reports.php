<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
if (!isAdminLoggedIn()) { redirect(ADMIN_URL . '/login.php'); }

$reportType = $_GET['type'] ?? 'daily';
$startDate  = $_GET['start_date'] ?? date('Y-m-01');
$endDate    = $_GET['end_date']   ?? date('Y-m-d');
$productId  = $_GET['product_id'] ?? '';

if ($reportType === 'daily') {
    $stmt = $pdo->prepare("SELECT DATE(created_at) AS date, COUNT(*) AS total_orders, SUM(CASE WHEN order_status='selesai' THEN 1 ELSE 0 END) AS selesai, SUM(CASE WHEN order_status='dibatalkan' THEN 1 ELSE 0 END) AS dibatalkan, SUM(CASE WHEN order_status NOT IN ('dibatalkan') THEN subtotal ELSE 0 END) AS pendapatan FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date DESC");
    $stmt->execute([$startDate, $endDate]); $reportData = $stmt->fetchAll();
} elseif ($reportType === 'monthly') {
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS total_orders, SUM(CASE WHEN order_status='selesai' THEN 1 ELSE 0 END) AS selesai, SUM(CASE WHEN order_status='dibatalkan' THEN 1 ELSE 0 END) AS dibatalkan, SUM(CASE WHEN order_status NOT IN ('dibatalkan') THEN subtotal ELSE 0 END) AS pendapatan FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month DESC");
    $stmt->execute([$startDate, $endDate]); $reportData = $stmt->fetchAll();
} elseif ($reportType === 'product') {
    $sql = "SELECT p.id, p.name AS product_name, c.name AS category_name, SUM(oi.quantity) AS total_qty, COUNT(DISTINCT oi.order_id) AS total_transaksi, SUM(oi.subtotal) AS pendapatan FROM order_items oi JOIN products p ON oi.product_id=p.id JOIN categories c ON p.category_id=c.id JOIN orders o ON oi.order_id=o.id WHERE o.order_status NOT IN ('dibatalkan') AND DATE(o.created_at) BETWEEN ? AND ?";
    $params = [$startDate, $endDate];
    if ($productId) { $sql .= " AND p.id=?"; $params[] = $productId; }
    $sql .= " GROUP BY p.id ORDER BY pendapatan DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params); $reportData = $stmt->fetchAll();
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_orders, SUM(CASE WHEN order_status='selesai' THEN 1 ELSE 0 END) AS selesai, SUM(CASE WHEN order_status='dibatalkan' THEN 1 ELSE 0 END) AS dibatalkan, SUM(CASE WHEN order_status NOT IN ('dibatalkan') THEN subtotal ELSE 0 END) AS pendapatan FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]); $summary = $stmt->fetch();

$stmtTop = $pdo->prepare("SELECT p.name, SUM(oi.quantity) AS qty, SUM(oi.subtotal) AS rev FROM order_items oi JOIN products p ON oi.product_id=p.id JOIN orders o ON oi.order_id=o.id WHERE o.order_status NOT IN ('dibatalkan') AND DATE(o.created_at) BETWEEN ? AND ? GROUP BY p.id ORDER BY qty DESC LIMIT 5");
$stmtTop->execute([$startDate, $endDate]); $topProducts = $stmtTop->fetchAll();

$products = $pdo->query("SELECT id, name FROM products ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Laporan Penjualan - Admin D'florist</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
<style>
@media print { .no-print{display:none!important} .sidebar{display:none!important} main{margin-left:0!important} }
.stat-card { border-radius:10px; border:none; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.stat-card .label { font-size:.75rem; color:#6c757d; margin-bottom:4px; }
.stat-card .value { font-size:1.25rem; font-weight:700; }
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container-fluid"><div class="row">
<?php include 'includes/sidebar.php'; ?>
<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom no-print">
    <h1 class="h2">Laporan Penjualan</h1>
    <button onclick="window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print me-1"></i> Cetak</button>
</div>

<div class="text-center mb-4 d-none d-print-block">
    <h4 class="fw-bold">D'florist — Laporan Penjualan</h4>
    <p class="mb-0">Periode: <?php echo date('d M Y', strtotime($startDate)); ?> s/d <?php echo date('d M Y', strtotime($endDate)); ?></p>
    <small class="text-muted">Dicetak: <?php echo date('d M Y H:i'); ?></small><hr>
</div>

<!-- Filter -->
<div class="card mb-4 no-print">
<div class="card-body py-3">
<form method="GET" class="row g-2 align-items-end">
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Jenis Laporan</label>
        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="daily"   <?php echo $reportType==='daily'  ?'selected':''; ?>>Harian</option>
            <option value="monthly" <?php echo $reportType==='monthly'?'selected':''; ?>>Bulanan</option>
            <option value="product" <?php echo $reportType==='product'?'selected':''; ?>>Per Produk</option>
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Tanggal Mulai</label>
        <input type="date" name="start_date" class="form-control form-control-sm" value="<?php echo $startDate; ?>">
    </div>
    <div class="col-md-2">
        <label class="form-label small fw-semibold">Tanggal Akhir</label>
        <input type="date" name="end_date" class="form-control form-control-sm" value="<?php echo $endDate; ?>">
    </div>
    <?php if ($reportType==='product'): ?>
    <div class="col-md-3">
        <label class="form-label small fw-semibold">Produk</label>
        <select name="product_id" class="form-select form-select-sm">
            <option value="">Semua Produk</option>
            <?php foreach ($products as $p): ?>
            <option value="<?php echo $p['id']; ?>" <?php echo $productId==$p['id']?'selected':''; ?>><?php echo htmlspecialchars($p['name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search me-1"></i> Tampilkan</button></div>
    <div class="col-auto"><a href="reports.php" class="btn btn-outline-secondary btn-sm">Reset</a></div>
</form>
</div></div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card card p-3">
            <div class="label">Total Pesanan</div>
            <div class="value"><?php echo $summary['total_orders']; ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card p-3" style="border-left:4px solid #28a745!important">
            <div class="label">Pesanan Selesai</div>
            <div class="value text-success"><?php echo $summary['selesai']; ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card p-3" style="border-left:4px solid #dc3545!important">
            <div class="label">Dibatalkan</div>
            <div class="value text-danger"><?php echo $summary['dibatalkan']; ?></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card card p-3" style="border-left:4px solid #fd7e14!important">
            <div class="label">Sedang Diproses</div>
            <div class="value text-warning"><?php echo $summary['total_orders'] - $summary['selesai'] - $summary['dibatalkan']; ?></div>
        </div>
    </div>
    <div class="col-12">
        <div class="stat-card card p-3" style="border-left:4px solid #FF69B4!important">
            <div class="label">Total Pendapatan <small class="text-muted">(tidak termasuk pesanan dibatalkan)</small></div>
            <div class="value" style="color:#FF69B4"><?php echo formatRupiah($summary['pendapatan']); ?></div>
        </div>
    </div>
</div>

<!-- Tabel + Top Produk -->
<div class="row g-3 mb-5">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Detail <?php echo $reportType==='daily'?'Harian':($reportType==='monthly'?'Bulanan':'Per Produk'); ?></h6>
                <div class="no-print d-flex gap-1">
                    <a href="export_report.php?format=excel&type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?><?php echo $productId?'&product_id='.$productId:''; ?>" class="btn btn-success btn-sm"><i class="fas fa-file-excel"></i> Excel</a>
                    <a href="export_report.php?format=pdf&type=<?php echo $reportType; ?>&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?><?php echo $productId?'&product_id='.$productId:''; ?>" class="btn btn-danger btn-sm"><i class="fas fa-file-pdf"></i> PDF</a>
                </div>
            </div>
            <div class="card-body p-0">
            <div class="table-responsive">
            <?php if ($reportType==='daily' || $reportType==='monthly'): ?>
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light"><tr>
                    <th><?php echo $reportType==='daily'?'Tanggal':'Bulan'; ?></th>
                    <th class="text-center">Total Pesanan</th>
                    <th class="text-center">Selesai</th>
                    <th class="text-center">Dibatalkan</th>
                    <th class="text-end">Pendapatan</th>
                </tr></thead>
                <tbody>
                <?php if (empty($reportData)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data untuk periode ini</td></tr>
                <?php else: foreach ($reportData as $row): ?>
                <tr>
                    <td><?php echo $reportType==='daily' ? date('d M Y', strtotime($row['date'])) : date('F Y', strtotime($row['month'].'-01')); ?></td>
                    <td class="text-center"><?php echo $row['total_orders']; ?></td>
                    <td class="text-center"><span class="badge bg-success"><?php echo $row['selesai']; ?></span></td>
                    <td class="text-center"><span class="badge bg-danger"><?php echo $row['dibatalkan']; ?></span></td>
                    <td class="text-end fw-semibold"><?php echo formatRupiah($row['pendapatan']); ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php else: ?>
            <table class="table table-hover table-sm mb-0">
                <thead class="table-light"><tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th class="text-center">Qty Terjual</th>
                    <th class="text-center">Transaksi</th>
                    <th class="text-end">Pendapatan</th>
                </tr></thead>
                <tbody>
                <?php if (empty($reportData)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada data untuk periode ini</td></tr>
                <?php else: foreach ($reportData as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo $row['category_name']; ?></span></td>
                    <td class="text-center"><?php echo $row['total_qty']; ?> pcs</td>
                    <td class="text-center"><?php echo $row['total_transaksi']; ?></td>
                    <td class="text-end fw-semibold"><?php echo formatRupiah($row['pendapatan']); ?></td>
                </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
            <?php endif; ?>
            </div></div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-trophy text-warning me-1"></i> Top 5 Produk Terlaris</h6></div>
            <div class="card-body p-0">
            <?php if (empty($topProducts)): ?>
            <p class="text-muted text-center py-4 small">Tidak ada data</p>
            <?php else: ?>
            <ul class="list-group list-group-flush">
            <?php foreach ($topProducts as $i => $tp): ?>
            <li class="list-group-item px-3 py-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge rounded-pill" style="background:#FF69B4;min-width:22px;"><?php echo $i+1; ?></span>
                    <div style="min-width:0;flex:1">
                        <div class="small fw-semibold text-truncate"><?php echo htmlspecialchars($tp['name']); ?></div>
                        <div class="text-muted" style="font-size:11px"><?php echo $tp['qty']; ?> terjual &middot; <?php echo formatRupiah($tp['rev']); ?></div>
                    </div>
                </div>
            </li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</main></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>

