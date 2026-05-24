<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Load PHPMailer
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}
require_once '../includes/email.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$step = $_GET['step'] ?? 'email';
$email = $_SESSION['admin_reset_email'] ?? '';
$otpExpires = $_SESSION['admin_otp_expires'] ?? 0;

// Check for OTP success/error messages from session - only show in verify step
if ($step === 'verify') {
    if (isset($_SESSION['admin_otp_success'])) {
        $success = $_SESSION['admin_otp_success'];
    }
    if (isset($_SESSION['admin_otp_error'])) {
        $error = $_SESSION['admin_otp_error'];
    }
}

// Step 1: Send OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    try {
        // Clear old messages first
        unset($_SESSION['admin_otp_success']);
        unset($_SESSION['admin_otp_error']);
        
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
                
                // Delete old OTP
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND user_type = 'admin'");
                $stmt->execute([$email]);
                
                // Insert new OTP with 10 minutes expiry using DATE_ADD
                $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, user_type, expires_at, created_at) VALUES (?, ?, 'admin', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())");
                $stmt->execute([$email, $otp]);
                
                $_SESSION['admin_reset_email'] = $email;
                $_SESSION['admin_otp_expires'] = time() + 600; // 10 minutes from now
                
                // Send email
                if (sendOTPEmail($email, $otp, $user['name'])) {
                    $_SESSION['admin_otp_success'] = 'Kode OTP telah dikirim ke email Anda';
                } else {
                    $_SESSION['admin_otp_error'] = 'Gagal mengirim email. Silakan kirim ulang kode OTP.';
                }
                
                // Always redirect to verify step
                header('Location: forgot_password.php?step=verify');
                exit;
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
        // Clear previous messages
        unset($_SESSION['admin_otp_success']);
        unset($_SESSION['admin_otp_error']);
        
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
            header('Location: forgot_password.php?step=reset');
            exit;
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
            header('Location: forgot_password.php');
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
            
            header('Location: forgot_password.php?step=success');
            exit;
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
        body { background-color: #C5E3F6; font-family: 'Poppins', sans-serif; font-size: 14px; }
        .card { background-color: #FFD6E8; border: none; border-radius: 15px; }
        .logo-circle { background: #FF69B4; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; }
        .btn-primary { background-color: #FF69B4; border: none; font-size: 14px; }
        .btn-primary:hover { background-color: #FF1493; }
        .btn-success { font-size: 14px; }
        .btn-outline-secondary { font-size: 14px; }
        .form-label { font-size: 14px; }
        .form-control { font-size: 14px; }
        h2 { font-size: 1.5rem; }
        h5 { font-size: 1rem; }
        .alert { font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body p-5">
                        <?php if ($step === 'email'): ?>
                        <div class="text-center mb-4">
                            <div class="logo-circle mx-auto mb-3" style="width: 60px; height: 60px; font-size: 2rem;">
                                <i class="fas fa-flower"></i>
                            </div>
                            <h2 class="fw-bold" style="color: #FF69B4; font-family: 'Quicksand', sans-serif;">D'florist</h2>
                            <h5 style="color: #5A6C7D;">Lupa Password</h5>
                            <p class="text-muted mb-0" style="font-size: 13px;">Masukkan email yang terdaftar</p>
                        </div>
                        <?php else: ?>
                        <div class="text-center mb-4">
                            <div class="logo-circle mx-auto mb-3" style="width: 60px; height: 60px; font-size: 2rem;">
                                <i class="fas fa-flower"></i>
                            </div>
                            <h2 class="fw-bold" style="color: #FF69B4; font-family: 'Quicksand', sans-serif;">D'florist</h2>
                            <h5 style="color: #5A6C7D;">Lupa Password</h5>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($step === 'email'): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email Admin</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <button type="submit" name="send_otp" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane"></i> Kirim Kode OTP
                            </button>
                        </form>
                        
                        <?php elseif ($step === 'verify'): ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kode OTP</label>
                                <input type="text" name="otp" class="form-control text-center" 
                                       maxlength="6" pattern="[0-9]{6}" required
                                       style="font-size: 20px; letter-spacing: 8px;"
                                       placeholder="000000">
                            </div>
                            <button type="submit" name="verify_otp" class="btn btn-success w-100">
                                <i class="fas fa-check"></i> Verifikasi
                            </button>
                        </form>
                        
                        <form method="POST" class="mt-2">
                            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                            <button type="submit" name="send_otp" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo"></i> Kirim Ulang OTP
                            </button>
                        </form>
                        
                        <div class="alert alert-info mt-3 mb-0 text-center">
                            <small>
                                <i class="fas fa-clock"></i> 
                                Berlaku: <strong id="countdown">10:00</strong>
                            </small>
                        </div>
                        
                        <script>
                        // Countdown timer
                        <?php if ($otpExpires > 0): ?>
                        var expiryTime = <?php echo $otpExpires; ?>;
                        var countdownElement = document.getElementById('countdown');
                        
                        function updateCountdown() {
                            var now = Math.floor(Date.now() / 1000);
                            var remaining = expiryTime - now;
                            
                            if (remaining <= 0) {
                                countdownElement.textContent = '00:00';
                                countdownElement.style.color = 'red';
                                return;
                            }
                            
                            var minutes = Math.floor(remaining / 60);
                            var seconds = remaining % 60;
                            
                            countdownElement.textContent = 
                                String(minutes).padStart(2, '0') + ':' + 
                                String(seconds).padStart(2, '0');
                            
                            if (remaining <= 60) {
                                countdownElement.style.color = 'red';
                            }
                            
                            setTimeout(updateCountdown, 1000);
                        }
                        
                        updateCountdown();
                        <?php endif; ?>
                        </script>
                        
                        <?php elseif ($step === 'reset'): ?>
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
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="mb-3">Password Berhasil Diubah!</h5>
                            <p class="mb-4">Silakan login dengan password baru</p>
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
