<?php
$pageTitle = 'Lupa Password - D\'Florist';
include 'includes/header.php';

// Load PHPMailer
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}
require_once 'includes/email.php';

if (isCustomerLoggedIn()) {
    redirect(SITE_URL);
}

$error = '';
$success = '';
$step = $_GET['step'] ?? 'email';
$email = $_SESSION['reset_email'] ?? '';
$otpExpires = $_SESSION['otp_expires'] ?? 0;

// Check for OTP success/error messages from session - only show in verify step
if ($step === 'verify') {
    if (isset($_SESSION['otp_success'])) {
        $success = $_SESSION['otp_success'];
    }
    if (isset($_SESSION['otp_error'])) {
        $error = $_SESSION['otp_error'];
    }
}

// Step 1: Send OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    // Clear old messages first
    unset($_SESSION['otp_success']);
    unset($_SESSION['otp_error']);
    
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Email harus diisi';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'customer'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate OTP
            $otp = sprintf("%06d", mt_rand(1, 999999));
            
            // Delete old OTP
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND user_type = 'customer'");
            $stmt->execute([$email]);
            
            // Insert new OTP with 10 minutes expiry using DATE_ADD
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, user_type, expires_at, created_at) VALUES (?, ?, 'customer', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW())");
            $stmt->execute([$email, $otp]);
            
            $_SESSION['reset_email'] = $email;
            $_SESSION['otp_expires'] = time() + 600; // 10 minutes from now
            
            // Send email
            if (sendOTPEmail($email, $otp, $user['name'])) {
                $_SESSION['otp_success'] = 'Kode OTP telah dikirim ke email Anda';
            } else {
                $_SESSION['otp_error'] = 'Gagal mengirim email. Silakan kirim ulang kode OTP.';
            }
            
            // Redirect to verify step to refresh the page
            header('Location: forgot_password.php?step=verify');
            exit;
        } else {
            $error = 'Email tidak terdaftar';
        }
    }
}

// Step 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    // Clear previous messages
    unset($_SESSION['otp_success']);
    unset($_SESSION['otp_error']);
    
    $otp = $_POST['otp'];
    $email = $_SESSION['reset_email'];
    
    $stmt = $pdo->prepare("
        SELECT * FROM password_resets 
        WHERE email = ? AND token = ? AND user_type = 'customer' AND expires_at > NOW()
    ");
    $stmt->execute([$email, $otp]);
    $reset = $stmt->fetch();
    
    if ($reset) {
        $_SESSION['otp_verified'] = true;
        header('Location: forgot_password.php?step=reset');
        exit;
    } else {
        $error = 'Kode OTP salah atau sudah kadaluarsa';
        $step = 'verify';
    }
}

// Step 3: Reset Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified']) {
        header('Location: forgot_password.php');
        exit;
    }
    
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Semua field harus diisi';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Password tidak cocok';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([hashPassword($newPassword), $email]);
        
        // Delete OTP
        $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ? AND user_type = 'customer'");
        $stmt->execute([$email]);
        
        // Clear session
        unset($_SESSION['reset_email']);
        unset($_SESSION['otp_verified']);
        unset($_SESSION['otp_expires']);
        
        header('Location: forgot_password.php?step=success');
        exit;
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-body p-5">
                    <?php if ($step === 'email'): ?>
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x mb-3" style="color: #FF69B4;"></i>
                        <h4>Lupa Password</h4>
                        <p class="text-muted mb-0" style="font-size: 13px;">Masukkan email yang terdaftar</p>
                    </div>
                    <?php else: ?>
                    <div class="text-center mb-4">
                        <i class="fas fa-shield-alt fa-3x mb-3" style="color: #FF69B4;"></i>
                        <h4>Lupa Password</h4>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($step === 'email'): ?>
                    <!-- Step 1: Enter Email -->
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required autofocus>
                        </div>
                        <button type="submit" name="send_otp" class="btn btn-primary w-100">
                            <i class="fas fa-paper-plane"></i> Kirim Kode OTP
                        </button>
                    </form>
                    
                    <?php elseif ($step === 'verify'): ?>
                    <!-- Step 2: Verify OTP -->
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Masukkan Kode OTP</label>
                            <input type="text" name="otp" class="form-control text-center" 
                                   maxlength="6" pattern="[0-9]{6}" required autofocus
                                   style="font-size: 20px; letter-spacing: 8px;"
                                   placeholder="000000">
                        </div>
                        
                        <button type="submit" name="verify_otp" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-check"></i> Verifikasi OTP
                        </button>
                    </form>
                    
                    <form method="POST">
                        <button type="submit" name="send_otp" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-redo"></i> Kirim Ulang OTP
                        </button>
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
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
                    <!-- Step 3: Reset Password -->
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" 
                                   required minlength="6" autofocus>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="reset_password" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Reset Password
                        </button>
                    </form>
                    
                    <?php elseif ($step === 'success'): ?>
                    <!-- Step 4: Success -->
                    <div class="text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h5 class="mb-3">Password Berhasil Diubah!</h5>
                        <p class="mb-4">Silakan login dengan password baru</p>
                        <a href="login.php" class="btn btn-primary w-100">
                            <i class="fas fa-sign-in-alt"></i> Login Sekarang
                        </a>
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

<?php include 'includes/footer.php'; ?>
