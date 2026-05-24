<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$success = '';
$error = '';

// Get filter
$filter = $_GET['filter'] ?? 'pending_approval';

// Build query based on filter
if ($filter === 'pending_approval') {
    // Show only COD orders with pending cancellation requests
    $sql = "
        SELECT cr.id as request_id, cr.request_date, cr.status as request_status,
               o.id, o.order_number, o.total, o.order_status, o.payment_method, o.created_at, o.payment_status,
               u.name as customer_name, u.email, u.phone
        FROM cancellation_requests cr
        JOIN orders o ON cr.order_id = o.id
        JOIN users u ON o.user_id = u.id
        WHERE cr.status = 'pending'
        ORDER BY cr.created_at DESC
    ";
} else {
    // Show all cancelled orders
    $sql = "
        SELECT o.id, o.order_number, o.total, o.order_status, o.payment_method, o.created_at, o.updated_at, o.payment_status,
               u.name as customer_name, u.email, u.phone,
               cr.id as request_id, cr.request_date, cr.status as request_status
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN cancellation_requests cr ON o.id = cr.order_id
        WHERE o.order_status = 'dibatalkan'
        ORDER BY o.updated_at DESC
    ";
}

$stmt = $pdo->query($sql);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Dibatalkan - Admin D'florist</title>
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
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom"><h1 class="h2 mb-0">Pesanan Dibatalkan</h1><a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left"></i> Kembali</a></div>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Filter Tabs -->
                <ul class="nav nav-tabs mb-4">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter === 'pending_approval' ? 'active' : ''; ?>" 
                           href="cancellation_requests.php?filter=pending_approval">
                            Menunggu Persetujuan
                            <?php
                            $stmt = $pdo->query("SELECT COUNT(*) FROM cancellation_requests WHERE status = 'pending'");
                            $pendingCount = $stmt->fetchColumn();
                            if ($pendingCount > 0):
                            ?>
                            <span class="badge bg-warning text-dark"><?php echo $pendingCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $filter === 'cancelled' ? 'active' : ''; ?>" 
                           href="cancellation_requests.php?filter=cancelled">
                            Sudah Dibatalkan
                        </a>
                    </li>
                </ul>
                
                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p><?php echo $filter === 'pending_approval' ? 'Tidak ada permintaan pembatalan' : 'Tidak ada pesanan yang dibatalkan'; ?></p>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">No</th>
                                        <th>No. Pesanan</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Pembayaran</th>
                                        <?php if ($filter === 'pending_approval'): ?>
                                        <th>Tanggal Dipesan</th>
                                        <th width="200">Aksi</th>
                                        <?php else: ?>
                                        <th>Tanggal Dipesan</th>
                                        <th>Tanggal Dibatalkan</th>
                                        <th width="120">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    foreach ($orders as $order): 
                                    ?>
                                    <tr>
                                        <td class="text-center fw-bold"><?php echo $no++; ?></td>
                                        <td>
                                            <strong><?php echo $order['order_number']; ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($order['customer_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo $order['email']; ?></small>
                                        </td>
                                        <td><?php echo formatRupiah($order['total']); ?></td>
                                        <td>
                                            <?php
                                            // Determine payment status display
                                            $isPaid = false;
                                            if ($order['payment_method'] === 'cod') {
                                                $isPaid = ($order['order_status'] === 'selesai');
                                            } else {
                                                $isPaid = ($order['payment_status'] === 'paid');
                                            }
                                            ?>
                                            <span class="badge <?php echo $isPaid ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo strtoupper($order['payment_method']); ?>
                                            </span>
                                            <br>
                                            <small class="text-muted"><?php echo $isPaid ? 'LUNAS' : 'BELUM LUNAS'; ?></small>
                                        </td>
                                        
                                        <?php if ($filter === 'pending_approval'): ?>
                                        <td><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="approveCancellation(<?php echo $order['request_id']; ?>)">
                                                <i class="fas fa-check"></i> Setujui
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectCancellation(<?php echo $order['request_id']; ?>)">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </td>
                                        <?php else: ?>
                                        <td><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo date('d M Y H:i', strtotime($order['updated_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                <i class="fas fa-eye"></i> Detail
                                            </button>
                                        </td>
                                        <?php endif; ?>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function approveCancellation(requestId) {
        if (!confirm('Apakah Anda yakin ingin menyetujui pembatalan pesanan ini? Stok produk akan dikembalikan.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('action', 'approve');
        
        fetch('../api/admin_handle_cancellation.php', {
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
    
    function rejectCancellation(requestId) {
        if (!confirm('Apakah Anda yakin ingin menolak pembatalan pesanan ini? Pesanan akan kembali ke daftar pesanan biasa.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('request_id', requestId);
        formData.append('action', 'reject');
        
        fetch('../api/admin_handle_cancellation.php', {
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
    
    function viewOrder(orderId) {
        window.location.href = 'order_detail.php?id=' + orderId;
    }
    </script>
</body>
</html>



