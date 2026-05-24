<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get all customers - simplified query
$search = $_GET['search'] ?? '';

if ($search) {
    $searchParam = "%$search%";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'customer' AND (name LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY created_at DESC");
    $stmt->execute([$searchParam, $searchParam, $searchParam]);
} else {
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC");
}
$customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pelanggan - Admin D'florist</title>
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
                    <h1 class="h2">Manajemen Pelanggan</h1>
                </div>
                
                <!-- Search -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form method="GET" class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Cari nama, email, atau nomor HP..." value="<?php echo htmlspecialchars($search); ?>" style="border-radius: 10px 0 0 10px;">
                            <button type="submit" class="btn btn-primary" style="border-radius: 0 10px 10px 0;">
                                <i class="fas fa-search"></i> Cari
                            </button>
                            <?php if ($search): ?>
                            <a href="customers.php" class="btn btn-secondary ms-2" style="border-radius: 10px;">Reset</a>
                            <?php endif; ?>
                        </form>
                    </div>
                    <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
                        <span class="badge" style="background-color: #FF69B4; color: white; font-size: 1rem; padding: 0.7rem 1.5rem; border-radius: 20px;">
                            Total Pelanggan: <?php echo count($customers); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Customers Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Daftar Pelanggan</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 60px;">No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>No. HP</th>
                                        <th>Terdaftar</th>
                                        <th style="width: 120px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">Tidak ada data pelanggan</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php $no = 1; foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($customer['name']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($customer['created_at'])); ?></td>
                                            <td>
                                                <a href="customer_detail.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> Detail
                                                </a>
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
