<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$step = $_GET['step'] ?? 'email';
$email = $_SESSION['admin_reset_email'] ?? '';

// Step 1: Send OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    try {
        $email = sanitize($_POST['email']);
        
        if (empty($email)) {
            $error = 'Email harus diisi';
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate OTP
                $otp = sprintf("%06d", mt_rand(1, 999999));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                
                // Delete old OTP
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND user_type = 'admin'");
                $stmt->execute([$email]);
                
                // Insert new OTP
                $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, user_type, expires_at) VALUES (?, ?, 'admin', ?)");
                $stmt->execute([$email, $otp, $expiresAt]);
                
                $_SESSION['admin_reset_email'] = $email;
                $success = 'Kode OTP: <strong style="font-size: 24px; color: #FF69B4;">' . $otp . '</strong><br><small>Simpan kode ini untuk verifikasi</small>';
                $step = 'verify';
            } else {
                $error = 'Email admin tidak terdaftar';
            }
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Step 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    try {
        $otp = $_POST['otp'];
        $email = $_SESSION['admin_reset_email'];
        
        $stmt = $pdo->prepare("
            SELECT * FROM password_resets 
            WHERE email = ? AND token = ? AND user_type = 'admin' AND expires_at > NOW()
        ");
        $stmt->execute([$email, $otp]);
        $reset = $stmt->fetch();
        
        if ($reset) {
            $_SESSION['admin_otp_verified'] = true;
            $step = 'reset';
        } else {
            $error = 'Kode OTP salah atau sudah kadaluarsa';
            $step = 'verify';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Step 3: Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    try {
        if (!isset($_SESSION['admin_otp_verified']) || !$_SESSION['admin_otp_verified']) {
            header('Location: forgot_password_simple.php');
            exit;
        }
        
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        $email = $_SESSION['admin_reset_email'];
        
        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Semua field harus diisi';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'Password tidak cocok';
        } elseif (strlen($newPassword) < 6) {
            $error = 'Password minimal 6 karakter';
        } else {
            // Update password
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'");
            $stmt->execute([hashPassword($newPassword), $email]);
            
            // Delete OTP
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND user_type = 'admin'");
            $stmt->execute([$email]);
            
            // Clear session
            unset($_SESSION['admin_reset_email']);
            unset($_SESSION['admin_otp_verified']);
            
            $success = 'Password berhasil diubah! Silakan login dengan password baru.';
            $step = 'success';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Admin D'florist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #C5E3F6; font-family: 'Poppins', sans-serif; }
        .card { background-color: #FFD6E8; border: none; border-radius: 15px; }
        .logo-circle { width: 60px; height: 60px; background: #FF69B4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 2rem; }
        .btn-primary { background-color: #FF69B4; border: none; }
        .btn-primary:hover { background-color: #FF1493; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="logo-circle mx-auto mb-3">
                                <i class="fas fa-flower"></i>
                            </div>
                            <h2 class="fw-bold" style="color: #FF69B4;">D'florist Admin</h2>
                            <h5 style="color: #5A6C7D;">Lupa Password</h5>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($step === 'email'): ?>
                        <p class="text-center mb-4">Masukkan email admin</p>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email Admin</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" name="send_otp" class="btn btn-primary w-100">
                                Kirim Kode OTP
                            </button>
                        </form>
                        
                        <?php elseif ($step === 'verify'): ?>
                        <p class="text-center mb-4">Masukkan kode OTP</p>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kode OTP</label>
                                <input type="text" name="otp" class="form-control text-center" 
                                       maxlength="6" pattern="[0-9]{6}" required
                                       style="font-size: 24px; letter-spacing: 10px;">
                            </div>
                            <button type="submit" name="verify_otp" class="btn btn-success w-100">
                                Verifikasi
                            </button>
                        </form>
                        
                        <?php elseif ($step === 'reset'): ?>
                        <p class="text-center mb-4">Buat password baru</p>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="new_password" class="form-control" required minlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Konfirmasi Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>
                            <button type="submit" name="reset_password" class="btn btn-primary w-100">
                                Reset Password
                            </button>
                        </form>
                        
                        <?php elseif ($step === 'success'): ?>
                        <div class="text-center">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h5 class="mb-3">Berhasil!</h5>
                            <a href="login.php" class="btn btn-primary w-100">Login Sekarang</a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($step !== 'success'): ?>
                        <div class="text-center mt-3">
                            <a href="login.php">Kembali ke Login</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
