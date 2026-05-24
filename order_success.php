<?php
$pageTitle = 'Pesanan Berhasil - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php');
}

$orderNumber = $_GET['order'] ?? '';

if (!$orderNumber) {
    redirect('orders.php');
}

$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$orderNumber, $_SESSION['customer_id']]);
$order = $stmt->fetch();

if (!$order) {
    redirect('orders.php');
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card text-center">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x text-success"></i>
                    </div>
                    
                    <h2 class="fw-bold mb-3">Pesanan Berhasil Dibuat!</h2>
                    <p class="text-muted mb-4">Terima kasih telah berbelanja di D'florist</p>
                    
                    <div class="bg-light p-4 rounded mb-4">
                        <h5 class="fw-bold mb-3">Detail Pesanan</h5>
                        <div class="row text-start">
                            <div class="col-6">
                                <p class="mb-2"><strong>Nomor Pesanan:</strong></p>
                                <p class="mb-2"><strong>Total Pembayaran:</strong></p>
                                <p class="mb-2"><strong>Metode Pembayaran:</strong></p>
                                <p class="mb-2"><strong>Status:</strong></p>
                            </div>
                            <div class="col-6">
                                <p class="mb-2"><?php echo $order['order_number']; ?></p>
                                <p class="mb-2"><?php echo formatRupiah($order['total']); ?></p>
                                <p class="mb-2"><?php echo strtoupper($order['payment_method']); ?></p>
                                <p class="mb-2">
                                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($order['payment_method'] !== 'cod'): ?>
                    <div class="alert alert-warning mb-4">
                        <i class="fas fa-info-circle"></i> 
                        Silakan lakukan pembayaran untuk melanjutkan pesanan Anda
                    </div>
                    <a href="payment.php?order=<?php echo $order['order_number']; ?>" class="btn btn-primary btn-lg mb-2">
                        Bayar Sekarang
                    </a>
                    <?php else: ?>
                    <div class="alert alert-success mb-4">
                        <i class="fas fa-check-circle"></i> 
                        Pesanan Anda akan diproses. Pembayaran dilakukan saat pengiriman/pengambilan.
                    </div>
                    <?php endif; ?>
                    
                    <a href="orders.php" class="btn btn-outline-primary btn-lg">
                        Lihat Pesanan Saya
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
