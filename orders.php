<?php
$pageTitle = 'Pesanan Saya - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php');
}

// Auto-cancel expired unpaid orders
$pdo->prepare("
    UPDATE orders 
    SET payment_status = 'failed', order_status = 'dibatalkan'
    WHERE payment_status = 'pending' 
      AND payment_method = 'midtrans'
      AND order_status = 'menunggu_pembayaran'
      AND created_at < DATE_SUB(NOW(), INTERVAL 25 MINUTE)
      AND user_id = ?
")->execute([$_SESSION['customer_id']]);

// Cek status ke Midtrans langsung untuk order yang masih pending
$pendingOrders = $pdo->prepare("
    SELECT order_number FROM orders 
    WHERE payment_status = 'pending' 
      AND payment_method = 'midtrans'
      AND user_id = ?
      AND created_at > DATE_SUB(NOW(), INTERVAL 25 MINUTE)
");
$pendingOrders->execute([$_SESSION['customer_id']]);
$pendingList = $pendingOrders->fetchAll();

if (!empty($pendingList)) {
    $serverKey    = $_ENV['MIDTRANS_SERVER_KEY'] ?? getenv('MIDTRANS_SERVER_KEY') ?? '';
    $isProduction = ($_ENV['MIDTRANS_IS_PRODUCTION'] ?? 'false') === 'true';
    $baseUrl      = $isProduction ? 'https://api.midtrans.com/v2/' : 'https://api.sandbox.midtrans.com/v2/';

    foreach ($pendingList as $pending) {
        $ch = curl_init($baseUrl . $pending['order_number'] . '/status');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($serverKey . ':')]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res  = json_decode(curl_exec($ch), true);
        curl_close($ch);

        if (!$res) continue;

        $txStatus  = $res['transaction_status'] ?? '';
        $fraud     = $res['fraud_status'] ?? '';
        $payType   = $res['payment_type'] ?? '';

        if ($txStatus === 'settlement' || ($txStatus === 'capture' && $fraud === 'accept')) {
            $pdo->prepare("UPDATE orders SET payment_status='paid', order_status='dibayar', payment_method=? WHERE order_number=?")
                ->execute([$payType, $pending['order_number']]);
        } elseif (in_array($txStatus, ['cancel', 'deny', 'expire'])) {
            $pdo->prepare("UPDATE orders SET payment_status='failed', order_status='dibatalkan' WHERE order_number=?")
                ->execute([$pending['order_number']]);
        }
    }
}

// Tampilkan notif kalau baru expired
$expiredNotif = isset($_GET['expired']) ? 'Pesanan dibatalkan karena waktu pembayaran habis.' : '';

$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT * FROM orders WHERE user_id = ?";
$params = [$_SESSION['customer_id']];

if ($statusFilter) {
    $sql .= " AND order_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<div class="container my-5">
    <h2 class="fw-bold mb-4">Pesanan Saya</h2>
    <?php if ($expiredNotif): ?>
    <div class="alert alert-warning"><i class="fas fa-clock me-2"></i><?php echo $expiredNotif; ?></div>
    <?php endif; ?>
    <!-- Status Filter + Search -->
    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
        <div class="btn-group flex-wrap" role="group">
            <a href="orders.php" class="btn btn-sm <?php echo !$statusFilter ? 'btn-primary' : 'btn-outline-primary'; ?>">Semua</a>
            <a href="orders.php?status=menunggu_pembayaran" class="btn btn-sm <?php echo $statusFilter === 'menunggu_pembayaran' ? 'btn-primary' : 'btn-outline-primary'; ?>">Menunggu Pembayaran</a>
            <a href="orders.php?status=dibayar" class="btn btn-sm <?php echo $statusFilter === 'dibayar' ? 'btn-primary' : 'btn-outline-primary'; ?>">Dibayar</a>
            <a href="orders.php?status=diproses" class="btn btn-sm <?php echo $statusFilter === 'diproses' ? 'btn-primary' : 'btn-outline-primary'; ?>">Diproses</a>
            <a href="orders.php?status=dikirim" class="btn btn-sm <?php echo $statusFilter === 'dikirim' ? 'btn-primary' : 'btn-outline-primary'; ?>">Dikirim</a>
            <a href="orders.php?status=selesai" class="btn btn-sm <?php echo $statusFilter === 'selesai' ? 'btn-primary' : 'btn-outline-primary'; ?>">Selesai</a>
            <a href="orders.php?status=dibatalkan" class="btn btn-sm <?php echo $statusFilter === 'dibatalkan' ? 'btn-primary' : 'btn-outline-primary'; ?>">Dibatalkan</a>
        </div>
        <div class="input-group" style="max-width:240px;">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" id="orderSearch" class="form-control form-control-sm" placeholder="Cari nama produk...">
        </div>
    </div>
    
    <?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <i class="fas fa-box-open fa-5x text-muted mb-3"></i>
        <h4>Belum Ada Pesanan</h4>
        <p class="text-muted">Anda belum memiliki pesanan</p>
        <a href="products.php" class="btn btn-primary">Belanja Sekarang</a>
    </div>
    <?php else: ?>
    
    <?php foreach ($orders as $order): ?>
    <?php
    // Check cancellation request status
    $stmt = $pdo->prepare("SELECT * FROM cancellation_requests WHERE order_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$order['id']]);
    $cancellationRequest = $stmt->fetch();
    ?>
    <div class="card mb-3 order-card" data-products="<?php
        $stmt2 = $pdo->prepare("SELECT product_name FROM order_items WHERE order_id = ?");
        $stmt2->execute([$order['id']]);
        $names = array_column($stmt2->fetchAll(), 'product_name');
        echo strtolower(htmlspecialchars(implode(' ', $names)));
    ?>">
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h5 class="mb-1"><?php echo $order['order_number']; ?></h5>
                            <small class="text-muted"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></small>
                            <?php if ($cancellationRequest && $cancellationRequest['status'] === 'pending'): ?>
                            <br><span class="badge bg-warning text-dark mt-1">
                                <i class="fas fa-clock"></i> Menunggu Persetujuan Pembatalan
                            </span>
                            <?php elseif ($cancellationRequest && $cancellationRequest['status'] === 'rejected'): ?>
                            <br><span class="badge bg-info mt-1">
                                <i class="fas fa-info-circle"></i> Pembatalan Ditolak
                            </span>
                            <?php endif; ?>
                        </div>
                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                        </span>
                    </div>
                    
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $stmt->execute([$order['id']]);
                    $items = $stmt->fetchAll();
                    ?>
                    
                    <div class="mb-2">
                        <?php foreach ($items as $item): ?>
                        <div class="text-muted small">
                            <?php echo htmlspecialchars($item['product_name']); ?> x<?php echo $item['quantity']; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mb-2">
                        <strong>Total: <?php echo formatRupiah($order['total']); ?></strong>
                    </div>
                    
                    <div class="text-muted small">
                        <i class="fas fa-truck"></i> <?php echo ucwords(str_replace('_', ' ', $order['delivery_method'])); ?>
                        | <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($order['delivery_date'])); ?>
                        <?php if ($order['delivery_time']): ?>
                        | <i class="fas fa-clock"></i> <?php echo $order['delivery_time']; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-4 text-end">
                    <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm mb-2">
                        Lihat Detail
                    </a>
                    
                    <?php 
                    // Determine if paid
                    $isPaid = false;
                    if ($order['payment_method'] === 'cod') {
                        $isPaid = ($order['order_status'] === 'selesai');
                    } else {
                        $isPaid = ($order['payment_status'] === 'paid');
                    }
                    ?>
                    
                    <?php if (!$isPaid && $order['payment_method'] !== 'cod' && $order['order_status'] === 'menunggu_pembayaran'): ?>
                    <a href="payment.php?order=<?php echo $order['order_number']; ?>" class="btn btn-primary btn-sm mb-2">
                        Bayar Sekarang
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($isPaid): ?>
                    <a href="receipt.php?order=<?php echo $order['order_number']; ?>" class="btn btn-success btn-sm mb-2" target="_blank">
                        Lihat E-Receipt
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($order['order_status'] === 'selesai'): ?>
                    <?php
                    // Check if already reviewed
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE order_id = ? AND user_id = ?");
                    $stmt->execute([$order['id'], $_SESSION['customer_id']]);
                    $hasReview = $stmt->fetchColumn() > 0;
                    ?>
                    <?php if (!$hasReview): ?>
                    <a href="review.php?order=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm mb-2">
                        Beri Ulasan
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script>
document.getElementById('orderSearch').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    const cards = document.querySelectorAll('.order-card');
    cards.forEach(card => {
        const products = card.dataset.products || '';
        card.style.display = (!q || products.includes(q)) ? '' : 'none';
    });
});
</script>
