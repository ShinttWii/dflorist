<?php
$pageTitle = 'Detail Pesanan - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php');
}

$orderId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email, u.phone,
           a.label as address_label, a.recipient_name, a.recipient_phone, 
           a.address, a.notes as address_notes, a.latitude, a.longitude
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN addresses a ON o.address_id = a.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $_SESSION['customer_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php');
}

// Ambil outlet aktif untuk pickup
$activeOutlet = $pdo->query("SELECT * FROM outlets WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetch();

// Get order items
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Check cancellation request status
$stmt = $pdo->prepare("SELECT * FROM cancellation_requests WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$orderId]);
$cancellationRequest = $stmt->fetch();
?>

<div class="container my-5">
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
                        <?php if ($order['delivery_method'] === 'pick_up' && $activeOutlet): ?>
                        <div class="col-12">
                            <p class="mb-1"><strong>Lokasi Pickup:</strong></p>
                            <p class="mb-0"><?php echo htmlspecialchars($activeOutlet['name']); ?></p>
                            <p class="text-muted small mb-0"><?php echo htmlspecialchars($activeOutlet['address']); ?></p>
                            <?php if ($activeOutlet['phone']): ?>
                            <p class="text-muted small mb-0"><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($activeOutlet['phone']); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
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
                    <h6 class="fw-bold mb-3">Informasi Pembayaran</h6>
                    <p class="mb-1"><strong>Metode:</strong></p>
                    <p class="mb-2"><?php echo formatPaymentMethod($order['payment_method']); ?></p>
                    
                    <p class="mb-1"><strong>Status:</strong></p>
                    <p>
                        <?php 
                        // COD: Lunas hanya jika pesanan selesai
                        // Non-COD: Lunas jika payment_status = paid
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
                    
                    <?php if ($isPaid): ?>
                    <a href="receipt.php?order=<?php echo $order['order_number']; ?>" class="btn btn-success w-100 mb-2" target="_blank">
                        <i class="fas fa-receipt"></i> Lihat E-Receipt
                    </a>
                    <?php elseif ($order['payment_method'] !== 'cod' && $order['payment_status'] !== 'paid'): ?>
                    <a href="payment.php?order=<?php echo $order['order_number']; ?>" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-credit-card"></i> Bayar Sekarang
                    </a>
                    <?php endif; ?>
                    
                    <?php 
                    // Show cancellation button or status
                    if ($order['order_status'] === 'dibatalkan'): ?>
                        <div class="alert alert-danger mb-0 mt-2">
                            <i class="fas fa-times-circle"></i> Pesanan Dibatalkan
                        </div>
                    <?php elseif ($cancellationRequest && $cancellationRequest['status'] === 'pending'): ?>
                        <div class="alert alert-warning mb-0 mt-2">
                            <i class="fas fa-clock"></i> Menunggu Persetujuan Pembatalan
                        </div>
                    <?php elseif ($cancellationRequest && $cancellationRequest['status'] === 'rejected'): ?>
                        <div class="alert alert-info mb-0 mt-2">
                            <i class="fas fa-info-circle"></i> Pembatalan Ditolak
                            <?php if ($cancellationRequest['rejection_reason']): ?>
                            <br><small><?php echo htmlspecialchars($cancellationRequest['rejection_reason']); ?></small>
                            <?php endif; ?>
                        </div>
                    <?php elseif (
                        $order['order_status'] === 'menunggu_pembayaran' || 
                        ($order['payment_method'] === 'cod' && $order['order_status'] === 'diproses')
                    ): ?>
                        <button type="button" class="btn btn-danger w-100 mt-2" onclick="cancelOrder(<?php echo $orderId; ?>)">
                            <i class="fas fa-times"></i> Batalkan Pesanan
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Timeline Pesanan</h6>
                    <div class="timeline">
                        <?php
                        // Define status order and labels
                        $statusFlow = [
                            'menunggu_pembayaran' => 'Menunggu Pembayaran',
                            'dibayar' => 'Pembayaran Diterima',
                            'diproses' => 'Pesanan Diproses',
                            'dikirim' => 'Pesanan Dikirim',
                            'selesai' => 'Pesanan Selesai',
                            'dibatalkan' => 'Pesanan Dibatalkan'
                        ];
                        
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
    
    <div class="text-center mt-4">
        <a href="orders.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
        </a>
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

<script>
function cancelOrder(orderId) {
    if (!confirm('Apakah Anda yakin ingin membatalkan pesanan ini?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('order_id', orderId);
    
    fetch('api/cancel_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan. Silakan coba lagi.');
        console.error('Error:', error);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
