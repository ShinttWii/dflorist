<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$orderId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email, u.phone,
           a.label as address_label, a.recipient_name, a.recipient_phone, 
           a.address, a.notes as address_notes, a.latitude, a.longitude
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN addresses a ON o.address_id = a.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php');
}

// Get order items
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Check cancellation request status
$stmt = $pdo->prepare("SELECT * FROM cancellation_requests WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$orderId]);
$cancellationRequest = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Admin D'florist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detail Pesanan</h1>
                    <div class="d-flex gap-2">
                        <button onclick="printLabel()" class="btn btn-outline-primary btn-sm no-print">
                            <i class="fas fa-print me-1"></i> Cetak Label
                        </button>
                        <a href="<?php echo $order['order_status'] === 'dibatalkan' ? 'cancellation_requests.php?filter=cancelled' : 'orders.php'; ?>" class="btn btn-outline-secondary btn-sm no-print">
                            <i class="fas fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-8">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h4 class="fw-bold mb-1">Detail Pesanan</h4>
                                        <p class="text-muted mb-0">No. Pesanan: <?php echo $order['order_number']; ?></p>
                                    </div>
                                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                    </span>
                                </div>
                                
                                <hr>
                                
                                <h6 class="fw-bold mb-3">Produk yang Dipesan</h6>
                                <?php foreach ($items as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                    <div>
                                        <p class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></p>
                                        <small class="text-muted"><?php echo $item['quantity']; ?> x <?php echo formatRupiah($item['price']); ?></small>
                                    </div>
                                    <strong><?php echo formatRupiah($item['subtotal']); ?></strong>
                                </div>
                                <?php endforeach; ?>
                                
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Subtotal:</span>
                                        <span><?php echo formatRupiah($order['subtotal']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Ongkir:</span>
                                        <span><?php echo formatRupiah($order['shipping_cost']); ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong>Total:</strong>
                                        <strong class="text-primary"><?php echo formatRupiah($order['total']); ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Informasi Pengiriman</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Metode:</strong></p>
                                        <p><?php echo ucwords(str_replace('_', ' ', $order['delivery_method'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Tanggal:</strong></p>
                                        <p><?php echo date('d M Y', strtotime($order['delivery_date'])); ?></p>
                                    </div>
                                    <?php if ($order['delivery_time']): ?>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Waktu:</strong></p>
                                        <p><?php echo $order['delivery_time']; ?></p>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($order['distance']): ?>
                                    <div class="col-md-6">
                                        <p class="mb-1"><strong>Jarak:</strong></p>
                                        <p><?php echo $order['distance']; ?> km</p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <hr>
                                
                                <h6 class="fw-bold mb-2">Alamat Pengiriman</h6>
                                <p class="mb-1"><strong><?php echo htmlspecialchars($order['recipient_name']); ?></strong></p>
                                <p class="mb-1"><?php echo htmlspecialchars($order['recipient_phone']); ?></p>
                                <p class="mb-1"><?php echo htmlspecialchars($order['address']); ?></p>
                                <?php if ($order['address_notes']): ?>
                                <p class="text-muted small mb-0">Catatan: <?php echo htmlspecialchars($order['address_notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if ($order['notes']): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-2">Catatan Pesanan</h6>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Informasi Customer</h6>
                                <p class="mb-1"><strong>Nama:</strong></p>
                                <p class="mb-2"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                                
                                <p class="mb-1"><strong>Email:</strong></p>
                                <p class="mb-2"><?php echo htmlspecialchars($order['email']); ?></p>
                                
                                <p class="mb-1"><strong>Telepon:</strong></p>
                                <p class="mb-0"><?php echo htmlspecialchars($order['phone']); ?></p>
                            </div>
                        </div>
                        
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Informasi Pembayaran</h6>
                                <p class="mb-1"><strong>Metode:</strong></p>
                                <p class="mb-2"><?php echo formatPaymentMethod($order['payment_method']); ?></p>
                                
                                <p class="mb-1"><strong>Status:</strong></p>
                                <p>
                                    <?php 
                                    $isPaid = false;
                                    if ($order['payment_method'] === 'cod') {
                                        $isPaid = ($order['order_status'] === 'selesai');
                                    } else {
                                        $isPaid = ($order['payment_status'] === 'paid');
                                    }
                                    ?>
                                    <span class="badge <?php echo $isPaid ? 'bg-success' : 'bg-warning'; ?>">
                                        <?php echo $isPaid ? 'LUNAS' : 'BELUM LUNAS'; ?>
                                    </span>
                                </p>
                                
                                <?php if ($order['order_status'] === 'dibatalkan'): ?>
                                    <div class="alert alert-danger mb-0 mt-2">
                                        <i class="fas fa-times-circle"></i> Pesanan Dibatalkan
                                    </div>
                                <?php elseif ($cancellationRequest && $cancellationRequest['status'] === 'pending'): ?>
                                    <div class="alert alert-warning mb-0 mt-2">
                                        <i class="fas fa-clock"></i> Menunggu Persetujuan Pembatalan
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <h6 class="fw-bold mb-3">Timeline Pesanan</h6>
                                <div class="timeline">
                                    <?php
                                    // Determine which statuses to show based on current status
                                    $currentStatus = $order['order_status'];
                                    $paymentMethod = $order['payment_method'];
                                    $showStatuses = [];
                                    
                                    if ($currentStatus === 'dibatalkan') {
                                        // Show only created and cancelled
                                        $showStatuses = [
                                            ['label' => 'Pesanan Dibuat', 'date' => $order['created_at'], 'icon' => 'check-circle', 'color' => 'success'],
                                            ['label' => 'Pesanan Dibatalkan', 'date' => $order['updated_at'], 'icon' => 'times-circle', 'color' => 'danger']
                                        ];
                                    } else {
                                        // Always show created
                                        $showStatuses[] = ['label' => 'Pesanan Dibuat', 'date' => $order['created_at'], 'icon' => 'check-circle', 'color' => 'success'];
                                        
                                        // For non-COD, show payment status
                                        if ($paymentMethod !== 'cod') {
                                            if ($currentStatus === 'menunggu_pembayaran') {
                                                $showStatuses[] = ['label' => 'Menunggu Pembayaran', 'date' => null, 'icon' => 'clock', 'color' => 'warning'];
                                            } elseif (in_array($currentStatus, ['dibayar', 'diproses', 'dikirim', 'selesai'])) {
                                                $showStatuses[] = ['label' => 'Pembayaran Diterima', 'date' => $order['updated_at'], 'icon' => 'check-circle', 'color' => 'success'];
                                            }
                                        }
                                        
                                        // Show processing status
                                        if ($currentStatus === 'diproses') {
                                            $showStatuses[] = ['label' => 'Pesanan Diproses', 'date' => null, 'icon' => 'spinner', 'color' => 'primary'];
                                        } elseif (in_array($currentStatus, ['dikirim', 'selesai'])) {
                                            $showStatuses[] = ['label' => 'Pesanan Diproses', 'date' => null, 'icon' => 'check-circle', 'color' => 'success'];
                                        }
                                        
                                        // Show shipping status
                                        if ($currentStatus === 'dikirim') {
                                            $showStatuses[] = ['label' => 'Pesanan Dikirim', 'date' => null, 'icon' => 'truck', 'color' => 'info'];
                                        } elseif ($currentStatus === 'selesai') {
                                            $showStatuses[] = ['label' => 'Pesanan Dikirim', 'date' => null, 'icon' => 'check-circle', 'color' => 'success'];
                                        }
                                        
                                        // Show completed status
                                        if ($currentStatus === 'selesai') {
                                            $showStatuses[] = ['label' => 'Pesanan Selesai', 'date' => $order['updated_at'], 'icon' => 'check-circle', 'color' => 'success'];
                                        }
                                    }
                                    
                                    // Render timeline
                                    foreach ($showStatuses as $status):
                                    ?>
                                    <div class="timeline-item">
                                        <i class="fas fa-<?php echo $status['icon']; ?> text-<?php echo $status['color']; ?>"></i>
                                        <div>
                                            <strong><?php echo $status['label']; ?></strong>
                                            <?php if ($status['date']): ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('d M Y H:i', strtotime($status['date'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 20px;
        display: flex;
        gap: 15px;
    }

    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 8px;
        top: 25px;
        bottom: -5px;
        width: 2px;
        background: #ddd;
    }

    .timeline-item i {
        font-size: 1.2rem;
        flex-shrink: 0;
    }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Label Cetak (hidden, muncul saat print) -->
<div id="printLabel" style="display:none;">
    <style>
    @media print {
        body > *:not(#printLabel) { display: none !important; }
        #printLabel { display: block !important; }
        @page { margin: 10mm; }
    }
    #printLabel {
        font-family: Arial, sans-serif;
        max-width: 400px;
        border: 2px solid #333;
        border-radius: 8px;
        padding: 16px;
    }
    #printLabel .lbl-header {
        text-align: center;
        border-bottom: 2px dashed #333;
        padding-bottom: 10px;
        margin-bottom: 12px;
    }
    #printLabel .lbl-title { font-size: 18px; font-weight: bold; }
    #printLabel .lbl-section { margin-bottom: 10px; }
    #printLabel .lbl-label { font-size: 11px; color: #666; text-transform: uppercase; }
    #printLabel .lbl-value { font-size: 14px; font-weight: bold; }
    #printLabel .lbl-address { font-size: 13px; }
    #printLabel .lbl-divider { border-top: 1px dashed #999; margin: 10px 0; }
    #printLabel .lbl-products { font-size: 12px; }
    </style>

    <div class="lbl-header">
        <div class="lbl-title">D'florist</div>
        <div style="font-size:12px;">Label Pengiriman</div>
    </div>

    <div class="lbl-section">
        <div class="lbl-label">Kepada</div>
        <div class="lbl-value"><?php echo htmlspecialchars($order['recipient_name']); ?></div>
        <div class="lbl-address"><?php echo htmlspecialchars($order['recipient_phone']); ?></div>
        <div class="lbl-address"><?php echo htmlspecialchars($order['address']); ?></div>
        <?php if ($order['address_notes']): ?>
        <div class="lbl-address" style="color:#666;">Catatan: <?php echo htmlspecialchars($order['address_notes']); ?></div>
        <?php endif; ?>
    </div>

    <div class="lbl-divider"></div>

    <div class="lbl-section">
        <div class="lbl-label">Dari</div>
        <div class="lbl-value">D'florist</div>
    </div>

    <div class="lbl-divider"></div>

    <div style="display:flex;justify-content:space-between;font-size:12px;">
        <div>
            <div class="lbl-label">Metode Pengiriman</div>
            <div><?php echo ucwords(str_replace('_', ' ', $order['delivery_method'])); ?></div>
        </div>
        <div>
            <div class="lbl-label">Tanggal Kirim</div>
            <div><?php echo $order['delivery_date'] ? date('d M Y', strtotime($order['delivery_date'])) : '-'; ?></div>
        </div>
    </div>
</div>

<script>
function printLabel() {
    document.getElementById('printLabel').style.display = 'block';
    window.print();
    document.getElementById('printLabel').style.display = 'none';
}
</script>
</body>
</html>
