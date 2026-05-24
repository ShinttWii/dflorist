<?php
$pageTitle = 'Profil Saya - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['customer_id']]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($name) || empty($phone)) {
        $error = 'Nama dan nomor HP harus diisi';
    } else {
        // Update profile
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$name, $phone, $_SESSION['customer_id']]);
        $_SESSION['customer_name'] = $name;
        
        // Update password if provided
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                $error = 'Masukkan password lama';
            } elseif (!verifyPassword($currentPassword, $user['password'])) {
                $error = 'Password lama salah';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'Password baru tidak cocok';
            } elseif (strlen($newPassword) < 6) {
                $error = 'Password minimal 6 karakter';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([hashPassword($newPassword), $_SESSION['customer_id']]);
                $success = 'Profil dan password berhasil diupdate';
            }
        } else {
            $success = 'Profil berhasil diupdate';
        }
        
        // Refresh user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $user = $stmt->fetch();
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="fw-bold mb-4">Profil Saya</h2>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST">
                        <h5 class="fw-bold mb-3">Informasi Akun</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            <small class="text-muted">Email tidak dapat diubah</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor HP</label>
                            <input type="tel" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <hr class="my-4">
                        
                        <h5 class="fw-bold mb-3">Ubah Password (Opsional)</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Lama</label>
                            <input type="password" name="current_password" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" minlength="6">
                            <small class="text-muted">Minimal 6 karakter</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control">
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">Informasi Akun</h6>
                    <p class="text-muted mb-1">Terdaftar sejak: <?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
                    <p class="text-muted mb-0">Terakhir diupdate: <?php echo date('d M Y H:i', strtotime($user['updated_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
