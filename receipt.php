<?php
$pageTitle = 'E-Receipt - D\'Florist';
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php');
}

$orderNumber = $_GET['order'] ?? '';

if (!$orderNumber) {
    redirect('orders.php');
}

$stmt = $pdo->prepare("
    SELECT o.*, u.name as customer_name, u.email, u.phone,
           a.address, a.recipient_name, a.recipient_phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN addresses a ON o.address_id = a.id
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$orderNumber, $_SESSION['customer_id']]);
$order = $stmt->fetch();

// Check if order is completed (for both COD and non-COD)
$isPaid = false;
if ($order['payment_method'] === 'cod') {
    $isPaid = ($order['order_status'] === 'selesai');
} else {
    $isPaid = ($order['payment_status'] === 'paid');
}

if (!$order || !$isPaid) {
    redirect('orders.php');
}

// Get order items
$stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Receipt - <?php echo $orderNumber; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none; }
        }
        body {
            background-color: #C5E3F6 !important;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #FFD6E8;
            padding: 30px;
            border: 2px solid #FF69B4;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .table {
            background-color: white;
        }
        .table-light {
            background-color: #f8f9fa !important;
        }
        .table-primary {
            background-color: #FFD6E8 !important;
        }
        .text-primary {
            color: #FF69B4 !important;
        }
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .btn-primary {
            background-color: #FF69B4;
            border-color: #FF69B4;
        }
        .btn-primary:hover {
            background-color: #FF1493;
            border-color: #FF1493;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="receipt-container">
            <!-- Header -->
            <div class="text-center mb-4">
                <h2 class="fw-bold text-primary"><i class="fas fa-flower"></i> D'florist</h2>
                <p class="text-muted mb-0">Toko Bunga Online</p>
                <p class="text-muted">Email: info@dflorist.com | Phone: +62 812-3456-7890</p>
            </div>
            
            <hr>
            
            <!-- Receipt Info -->
            <div class="row mb-4">
                <div class="col-6">
                    <h5 class="fw-bold">E-RECEIPT</h5>
                    <p class="mb-1"><strong>No. Pesanan:</strong> <?php echo $order['order_number']; ?></p>
                    <p class="mb-1"><strong>Tanggal:</strong> <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></p>
                    <p class="mb-1"><strong>Status:</strong> 
                        <span class="badge bg-success">LUNAS</span>
                    </p>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-1"><strong>Pelanggan:</strong></p>
                    <p class="mb-1"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p class="mb-1"><?php echo htmlspecialchars($order['email']); ?></p>
                    <p class="mb-1"><?php echo htmlspecialchars($order['phone']); ?></p>
                </div>
            </div>
            
            <!-- Order Items -->
            <table class="table table-bordered">
                <thead class="table-light">
                    <tr>
                        <th>Produk</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end"><?php echo formatRupiah($item['price']); ?></td>
                        <td class="text-end"><?php echo formatRupiah($item['subtotal']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                        <td class="text-end"><?php echo formatRupiah($order['subtotal']); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Ongkir:</strong></td>
                        <td class="text-end"><?php echo formatRupiah($order['shipping_cost']); ?></td>
                    </tr>
                    <tr class="table-primary">
                        <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                        <td class="text-end"><strong><?php echo formatRupiah($order['total']); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <!-- Delivery Info -->
            <div class="row mb-4">
                <div class="col-6">
                    <h6 class="fw-bold">Informasi Pengiriman</h6>
                    <p class="mb-1"><strong>Metode:</strong> <?php echo ucwords(str_replace('_', ' ', $order['delivery_method'])); ?></p>
                    <p class="mb-1"><strong>Tanggal:</strong> <?php echo date('d M Y', strtotime($order['delivery_date'])); ?></p>
                    <?php if ($order['delivery_time']): ?>
                    <p class="mb-1"><strong>Waktu:</strong> <?php echo $order['delivery_time']; ?></p>
                    <?php endif; ?>
                </div>
                <div class="col-6">
                    <h6 class="fw-bold">Alamat Pengiriman</h6>
                    <p class="mb-1"><strong><?php echo htmlspecialchars($order['recipient_name']); ?></strong></p>
                    <p class="mb-1"><?php echo htmlspecialchars($order['recipient_phone']); ?></p>
                    <p class="mb-1"><?php echo htmlspecialchars($order['address']); ?></p>
                </div>
            </div>
            
            <!-- Payment Info -->
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> 
                <strong>Pembayaran Berhasil</strong><br>
                Metode: <?php echo formatPaymentMethod($order['payment_method']); ?><br>
                Tanggal: <?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?>
            </div>
            
            <!-- Footer -->
            <div class="text-center mt-4">
                <p class="text-muted small mb-2">Terima kasih telah berbelanja di D'florist</p>
                <p class="text-muted small">Dokumen ini adalah bukti pembayaran yang sah</p>
            </div>
            
            <!-- Action Buttons -->
            <div class="text-center mt-4 no-print">
                <button onclick="window.print()" class="btn btn-primary me-2">
                    <i class="fas fa-print"></i> Cetak Receipt
                </button>
                <a href="orders.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
                </a>
            </div>
        </div>
    </div>
</body>
</html>
