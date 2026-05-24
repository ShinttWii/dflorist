<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/dashboard.php');
}

$error = '';
$debug = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        try {
            // Debug: Cek tabel users
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                $debug[] = "User ditemukan di tabel users";
                $debug[] = "Email: " . $user['email'];
                $debug[] = "Punya kolom role: " . (isset($user['role']) ? 'Ya (' . $user['role'] . ')' : 'Tidak');
                
                // Cek password
                $passwordMatch = password_verify($password, $user['password']);
                $debug[] = "Password match: " . ($passwordMatch ? 'Ya' : 'Tidak');
                
                // Jika ada kolom role dan role = admin
                if (isset($user['role']) && $user['role'] === 'admin') {
                    if ($passwordMatch) {
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_name'] = $user['name'];
                        $_SESSION['admin_email'] = $user['email'];
                        header('Location: ' . ADMIN_URL . '/dashboard.php');
                        exit;
                    } else {
                        $error = 'Password salah';
                    }
                } else {
                    $debug[] = "User bukan admin atau tidak punya role";
                }
            } else {
                $debug[] = "User tidak ditemukan di tabel users";
            }
            
            // Jika belum berhasil, coba tabel admins
            if (!isset($_SESSION['admin_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
                $stmt->execute([$email]);
                $admin = $stmt->fetch();
                
                if ($admin) {
                    $debug[] = "Admin ditemukan di tabel admins";
                    
                    $passwordMatch = password_verify($password, $admin['password']);
                    $debug[] = "Password match: " . ($passwordMatch ? 'Ya' : 'Tidak');
                    
                    if ($passwordMatch) {
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_name'] = $admin['name'];
                        $_SESSION['admin_email'] = $admin['email'];
                        header('Location: ' . ADMIN_URL . '/dashboard.php');
                        exit;
                    } else {
                        $error = 'Password salah';
                    }
                } else {
                    $debug[] = "Admin tidak ditemukan di tabel admins";
                }
            }
            
            if (!isset($_SESSION['admin_id'])) {
                $error = 'Email atau password salah';
            }
            
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
            $debug[] = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - D'florist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <div class="logo-circle mx-auto mb-3" style="width: 60px; height: 60px; font-size: 2rem;">
                                <i class="fas fa-flower"></i>
                            </div>
                            <h2 class="fw-bold" style="color: #FF69B4; font-family: 'Quicksand', sans-serif;">D'florist</h2>
                            <h5 style="color: #5A6C7D;">Admin Panel</h5>
                        </div>
                        
                        <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <strong>Error:</strong> <?php echo $error; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($debug) && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                        <div class="alert alert-info">
                            <strong>Debug Info:</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($debug as $msg): ?>
                                <li><?php echo $msg; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" placeholder="dewishinta0128@gmail.com" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password</label>
                                <input type="password" name="password" class="form-control" placeholder="admin123" required>
                            </div>
                            
                            <div class="mb-3 text-end">
                                <a href="forgot_password.php">Lupa Password?</a>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <a href="<?php echo SITE_URL; ?>">
                                <i class="fas fa-arrow-left"></i> Kembali ke Website
                            </a>
                        </div>
                    
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
