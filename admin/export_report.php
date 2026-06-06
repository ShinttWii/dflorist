<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) exit('Unauthorized');

$format     = $_GET['format']     ?? 'excel';
$reportType = $_GET['type']       ?? 'daily';
$startDate  = $_GET['start_date'] ?? date('Y-m-01');
$endDate    = $_GET['end_date']   ?? date('Y-m-d');
$productId  = $_GET['product_id'] ?? '';

// ── QUERY (sama persis dengan reports.php) ────────────────────────────────────
if ($reportType === 'daily') {
    $stmt = $pdo->prepare("SELECT DATE(created_at) AS date, COUNT(*) AS total_orders, SUM(CASE WHEN order_status='selesai' THEN 1 ELSE 0 END) AS selesai, SUM(CASE WHEN order_status='dibatalkan' THEN 1 ELSE 0 END) AS dibatalkan, SUM(CASE WHEN order_status NOT IN ('dibatalkan') THEN subtotal ELSE 0 END) AS pendapatan FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date DESC");
    $stmt->execute([$startDate, $endDate]);
    $reportData = $stmt->fetchAll();
    $reportTitle = 'Laporan Penjualan Harian';
} elseif ($reportType === 'monthly') {
    $stmt = $pdo->prepare("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, COUNT(*) AS total_orders, SUM(CASE WHEN order_status='selesai' THEN 1 ELSE 0 END) AS selesai, SUM(CASE WHEN order_status='dibatalkan' THEN 1 ELSE 0 END) AS dibatalkan, SUM(CASE WHEN order_status NOT IN ('dibatalkan') THEN subtotal ELSE 0 END) AS pendapatan FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY month DESC");
    $stmt->execute([$startDate, $endDate]);
    $reportData = $stmt->fetchAll();
    $reportTitle = 'Laporan Penjualan Bulanan';
} else {
    $sql = "SELECT p.name AS product_name, c.name AS category_name, SUM(oi.quantity) AS total_qty, COUNT(DISTINCT oi.order_id) AS total_transaksi, SUM(oi.subtotal) AS pendapatan FROM order_items oi JOIN products p ON oi.product_id=p.id JOIN categories c ON p.category_id=c.id JOIN orders o ON oi.order_id=o.id WHERE o.order_status NOT IN ('dibatalkan') AND DATE(o.created_at) BETWEEN ? AND ?";
    $params = [$startDate, $endDate];
    if ($productId) { $sql .= " AND p.id=?"; $params[] = $productId; }
    $sql .= " GROUP BY p.id ORDER BY pendapatan DESC";
    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $reportData = $stmt->fetchAll();
    $reportTitle = 'Laporan Penjualan Per Produk';
}

$stmt = $pdo->prepare("SELECT COUNT(*) AS total_orders, SUM(CASE WHEN order_status='selesai' THEN 1 ELSE 0 END) AS selesai, SUM(CASE WHEN order_status='dibatalkan' THEN 1 ELSE 0 END) AS dibatalkan, SUM(CASE WHEN order_status NOT IN ('dibatalkan') THEN subtotal ELSE 0 END) AS pendapatan FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
$stmt->execute([$startDate, $endDate]);
$summary = $stmt->fetch();

$filename = 'laporan_penjualan_' . date('Ymd_His');
$periode  = date('d M Y', strtotime($startDate)) . ' - ' . date('d M Y', strtotime($endDate));

function rupiah($n) { return 'Rp ' . number_format($n ?? 0, 0, ',', '.'); }

// ── EXCEL (CSV format — kompatibel semua versi Excel) ─────────────────────────
if ($format === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment;filename="' . $filename . '.csv"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    // BOM untuk Excel agar baca UTF-8 dengan benar
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');

    // Header info
    fputcsv($out, ["D'Florist - " . $reportTitle], ';');
    fputcsv($out, ['Periode: ' . $periode], ';');
    fputcsv($out, ['Dicetak: ' . date('d M Y H:i')], ';');
    fputcsv($out, [], ';');

    // Ringkasan
    fputcsv($out, ['RINGKASAN'], ';');
    fputcsv($out, ['Total Pesanan', $summary['total_orders']], ';');
    fputcsv($out, ['Pesanan Selesai', $summary['selesai']], ';');
    fputcsv($out, ['Pesanan Dibatalkan', $summary['dibatalkan']], ';');
    fputcsv($out, ['Total Pendapatan', rupiah($summary['pendapatan'])], ';');
    fputcsv($out, [], ';');

    // Header tabel
    if ($reportType === 'daily') {
        fputcsv($out, ['Tanggal', 'Total Pesanan', 'Selesai', 'Dibatalkan', 'Pendapatan'], ';');
    } elseif ($reportType === 'monthly') {
        fputcsv($out, ['Bulan', 'Total Pesanan', 'Selesai', 'Dibatalkan', 'Pendapatan'], ';');
    } else {
        fputcsv($out, ['Produk', 'Kategori', 'Qty Terjual', 'Transaksi', 'Pendapatan'], ';');
    }

    // Data
    foreach ($reportData as $row) {
        if ($reportType === 'daily') {
            fputcsv($out, [date('d M Y', strtotime($row['date'])), $row['total_orders'], $row['selesai'], $row['dibatalkan'], rupiah($row['pendapatan'])], ';');
        } elseif ($reportType === 'monthly') {
            fputcsv($out, [date('F Y', strtotime($row['month'].'-01')), $row['total_orders'], $row['selesai'], $row['dibatalkan'], rupiah($row['pendapatan'])], ';');
        } else {
            fputcsv($out, [$row['product_name'], $row['category_name'], $row['total_qty'].' pcs', $row['total_transaksi'], rupiah($row['pendapatan'])], ';');
        }
    }

    fclose($out);

// ── PDF (print via browser) ───────────────────────────────────────────────────
} elseif ($format === 'pdf') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>' . $reportTitle . '</title>
    <style>
        body{font-family:Arial,sans-serif;font-size:11pt;margin:20mm}
        h1{color:#FF69B4;margin:0 0 4px} h2{margin:0 0 4px;font-size:13pt}
        p{margin:2px 0;font-size:10pt}
        table{border-collapse:collapse;width:100%;margin-top:12px}
        th,td{border:1px solid #999;padding:6px 10px;font-size:10pt}
        th{background:#FFD6E8;font-weight:bold}
        .r{text-align:right} .c{text-align:center}
        .sum{width:50%;margin-bottom:20px}
        .sum th{background:#f5f5f5;text-align:left}
        .footer{margin-top:30px;border-top:1px solid #ddd;padding-top:10px;text-align:center;font-size:9pt;color:#999}
        .no-print{text-align:center;margin-bottom:20px}
        @media print{.no-print{display:none}}
    </style></head><body>';

    echo '<div class="no-print">
        <button onclick="window.print()" style="padding:8px 20px;background:#FF69B4;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13pt">
            Cetak / Simpan PDF
        </button>
    </div>';

    echo '<h1>D\'Florist</h1>
    <h2>' . $reportTitle . '</h2>
    <p>Periode: ' . $periode . '</p>
    <p>Dicetak: ' . date('d M Y H:i') . '</p>
    <hr style="border-color:#FFD6E8;margin:12px 0">';

    echo '<table class="sum">
        <tr><th>Total Pesanan</th><td>' . $summary['total_orders'] . '</td></tr>
        <tr><th>Pesanan Selesai</th><td>' . $summary['selesai'] . '</td></tr>
        <tr><th>Pesanan Dibatalkan</th><td>' . $summary['dibatalkan'] . '</td></tr>
        <tr><th>Total Pendapatan</th><td><strong>' . rupiah($summary['pendapatan']) . '</strong></td></tr>
    </table>';

    echo '<table><thead><tr>';
    if ($reportType === 'daily') {
        echo '<th>Tanggal</th><th class="c">Total</th><th class="c">Selesai</th><th class="c">Dibatalkan</th><th class="r">Pendapatan</th>';
    } elseif ($reportType === 'monthly') {
        echo '<th>Bulan</th><th class="c">Total</th><th class="c">Selesai</th><th class="c">Dibatalkan</th><th class="r">Pendapatan</th>';
    } else {
        echo '<th>Produk</th><th>Kategori</th><th class="c">Qty</th><th class="c">Transaksi</th><th class="r">Pendapatan</th>';
    }
    echo '</tr></thead><tbody>';

    if (empty($reportData)) {
        echo '<tr><td colspan="5" class="c">Tidak ada data</td></tr>';
    } else {
        foreach ($reportData as $row) {
            echo '<tr>';
            if ($reportType === 'daily') {
                echo '<td>' . date('d M Y', strtotime($row['date'])) . '</td>';
                echo '<td class="c">' . $row['total_orders'] . '</td>';
                echo '<td class="c">' . $row['selesai'] . '</td>';
                echo '<td class="c">' . $row['dibatalkan'] . '</td>';
                echo '<td class="r">' . rupiah($row['pendapatan']) . '</td>';
            } elseif ($reportType === 'monthly') {
                echo '<td>' . date('F Y', strtotime($row['month'] . '-01')) . '</td>';
                echo '<td class="c">' . $row['total_orders'] . '</td>';
                echo '<td class="c">' . $row['selesai'] . '</td>';
                echo '<td class="c">' . $row['dibatalkan'] . '</td>';
                echo '<td class="r">' . rupiah($row['pendapatan']) . '</td>';
            } else {
                echo '<td>' . htmlspecialchars($row['product_name']) . '</td>';
                echo '<td>' . $row['category_name'] . '</td>';
                echo '<td class="c">' . $row['total_qty'] . ' pcs</td>';
                echo '<td class="c">' . $row['total_transaksi'] . '</td>';
                echo '<td class="r">' . rupiah($row['pendapatan']) . '</td>';
            }
            echo '</tr>';
        }
    }
    echo '</tbody></table>';
    echo '<div class="footer">© ' . date('Y') . ' D\'Florist — Laporan digenerate otomatis</div>';
    echo '</body></html>';
}
?>
