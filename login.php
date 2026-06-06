<?php
$pageTitle = 'Login - D\'Florist';
include 'includes/header.php';

if (isCustomerLoggedIn()) {
    redirect(SITE_URL);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && verifyPassword($password, $user['password'])) {
            $customerId = $user['id'];

            // ── Merge cart guest ke database sebelum set session ──
            // Ambil cart guest dari session (sebelum di-overwrite oleh loadCartFromDb)
            $guestCart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

            $_SESSION['customer_id']    = $customerId;
            $_SESSION['customer_name']  = $user['name'];
            $_SESSION['customer_email'] = $user['email'];

            if (!empty($guestCart)) {
                foreach ($guestCart as $productId => $item) {
                    $productId = (int)$productId;
                    $qty       = (int)$item['quantity'];

                    // Cek stok produk agar tidak melebihi
                    $stmtStock = $pdo->prepare("SELECT stock FROM products WHERE id = ? AND is_active = 1");
                    $stmtStock->execute([$productId]);
                    $stock = $stmtStock->fetchColumn();
                    if ($stock === false) continue; // produk tidak ada, skip

                    // Cek apakah sudah ada di cart DB
                    $stmtCheck = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
                    $stmtCheck->execute([$customerId, $productId]);
                    $existing = $stmtCheck->fetch();

                    if ($existing) {
                        // Gabungkan quantity, tapi tidak boleh melebihi stok
                        $newQty = min($existing['quantity'] + $qty, $stock);
                        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")
                            ->execute([$newQty, $existing['id']]);
                    } else {
                        $newQty = min($qty, $stock);
                        $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)")
                            ->execute([$customerId, $productId, $newQty]);
                    }
                }
            }

            // Sinkronisasi DB → session (termasuk item guest yang baru di-merge)
            loadCartFromDb($pdo, $customerId);

            // Update last activity timestamp
            $stmt = $pdo->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            $redirect = $_GET['redirect'] ?? 'index.php';
            redirect($redirect);
        } else {
            $error = 'Email atau password salah';
        }
    }
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card">
                <div class="card-body p-5">
                    <h3 class="text-center mb-4">Login</h3>
                    
                    <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?php echo $_POST['email'] ?? ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        
                        <div class="mb-3 text-end">
                            <a href="forgot_password.php" class="text-decoration-none">Lupa Password?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    
                    <div class="text-center mt-3">
                        <p>Belum punya akun? <a href="register.php">Daftar di sini</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
