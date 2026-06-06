<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$success = '';
$error = '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['order_status'];
    
    try {
        $pdo->beginTransaction();
        
        // Get order details
        $stmt = $pdo->prepare("SELECT payment_method FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch();
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        
        // If COD and status changed to 'selesai', mark as paid
        if ($order && $order['payment_method'] === 'cod' && $newStatus === 'selesai') {
            $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid' WHERE id = ?");
            $stmt->execute([$orderId]);
        }
        
        $pdo->commit();
        $success = 'Status pesanan berhasil diupdate';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Gagal mengupdate status: ' . $e->getMessage();
    }
}

// Filter
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';

$sql = "
    SELECT o.*, u.name as customer_name, u.email, u.phone
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.order_status != 'dibatalkan'
";
$params = [];

if ($statusFilter) {
    $sql .= " AND o.order_status = ?";
    $params[] = $statusFilter;
}

if ($searchQuery) {
    $sql .= " AND (o.order_number LIKE ? OR u.name LIKE ?)";
    $params[] = "%$searchQuery%";
    $params[] = "%$searchQuery%";
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan - Admin D'florist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manajemen Pesanan</h1>
                    <?php
                    $pendingCancel = $pdo->query("SELECT COUNT(*) FROM cancellation_requests WHERE status = 'pending'")->fetchColumn();
                    ?>
                    <a href="cancellation_requests.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-ban me-1"></i> Permintaan Pembatalan
                        <?php if ($pendingCancel > 0): ?>
                        <span class="badge bg-warning text-dark ms-1"><?php echo $pendingCancel; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
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
                
                <!-- Filter & Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Cari nomor pesanan atau nama customer..."
                                       value="<?php echo htmlspecialchars($searchQuery); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value="">Semua Status</option>
                                    <option value="menunggu_pembayaran" <?php echo $statusFilter === 'menunggu_pembayaran' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                                    <option value="dibayar" <?php echo $statusFilter === 'dibayar' ? 'selected' : ''; ?>>Dibayar</option>
                                    <option value="diproses" <?php echo $statusFilter === 'diproses' ? 'selected' : ''; ?>>Diproses</option>
                                    <option value="dikirim" <?php echo $statusFilter === 'dikirim' ? 'selected' : ''; ?>>Dikirim</option>
                                    <option value="selesai" <?php echo $statusFilter === 'selesai' ? 'selected' : ''; ?>>Selesai</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-search"></i> Cari
                                </button>
                            </div>
                            <div class="col-md-3">
                                <a href="orders.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-redo"></i> Reset
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="50">No</th>
                                        <th>No. Pesanan</th>
                                        <th>Customer</th>
                                        <th>Total</th>
                                        <th>Pembayaran</th>
                                        <th>Status</th>
                                        <th>Tanggal</th>
                                        <th width="120">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">Tidak ada pesanan</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php 
                                        $no = 1; // Counter untuk nomor urut
                                        foreach ($orders as $order): 
                                        ?>
                                        <tr>
                                            <td class="text-center fw-bold"><?php echo $no++; ?></td>
                                            <td>
                                                <strong><?php echo $order['order_number']; ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                                                <small class="text-muted"><?php echo $order['email']; ?></small>
                                            </td>
                                            <td><?php echo formatRupiah($order['total']); ?></td>
                                            <td>
                                                <?php
                                                $pm = $order['payment_method'];
                                                if ($pm === 'cod') {
                                                    echo '<span class="badge bg-secondary">COD</span>';
                                                } else {
                                                    echo '<span class="badge" style="background:#FF69B4;">Bayar Online</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                                    <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning" onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['order_status']; ?>')">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Status Pesanan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="orderId">
                        <div class="mb-3">
                            <label class="form-label">Status Pesanan</label>
                            <select name="order_status" id="orderStatus" class="form-select" required>
                                <option value="menunggu_pembayaran">Menunggu Pembayaran</option>
                                <option value="dibayar">Dibayar</option>
                                <option value="diproses">Diproses</option>
                                <option value="dikirim">Dikirim</option>
                                <option value="selesai">Selesai</option>
                                <option value="dibatalkan">Dibatalkan</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function updateStatus(orderId, currentStatus) {
        document.getElementById('orderId').value = orderId;
        document.getElementById('orderStatus').value = currentStatus;
        const modal = new bootstrap.Modal(document.getElementById('statusModal'));
        modal.show();
    }
    
    function viewOrder(orderId) {
        window.location.href = 'order_detail.php?id=' + orderId;
    }
    </script>
</body>
</html>
