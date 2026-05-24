<?php
// Reset Admin Password - Pasti Berhasil!
require_once 'config/database.php';

$email = 'dewishinta0128@gmail.com';
$newPassword = 'admin123';

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Reset Admin Password - D'florist</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: #C5E3F6; padding: 40px 0; font-family: Arial, sans-serif; }
        .card { background: #FFD6E8; border: none; border-radius: 15px; max-width: 700px; margin: 0 auto; }
        .success { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin: 15px 0; border: 2px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin: 15px 0; border: 2px solid #f5c6cb; }
        .info { background: #e7f3ff; color: #004085; padding: 15px; border-radius: 8px; margin: 15px 0; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
<div class='container'>
    <div class='card'>
        <div class='card-body p-5'>
            <h2 class='text-center mb-4'>🔐 Reset Admin Password</h2>";

try {
    // Generate password hash BARU
    $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    
    echo "<div class='info'>";
    echo "<h4>📝 Password Baru</h4>";
    echo "<p><strong>Password:</strong> <code>$newPassword</code></p>";
    echo "<p><strong>Hash:</strong></p>";
    echo "<div class='code'>" . $passwordHash . "</div>";
    echo "</div>";
    
    // Test hash langsung
    $testVerify = password_verify($newPassword, $passwordHash);
    echo "<div class='info'>";
    echo "<h4>🧪 Test Hash</h4>";
    echo "<p>Verifikasi password dengan hash baru: " . ($testVerify ? '✅ BERHASIL' : '❌ GAGAL') . "</p>";
    echo "</div>";
    
    // Cek tabel mana yang digunakan
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
    $hasRole = $stmt->fetch();
    
    $updated = false;
    
    if ($hasRole) {
        // Update di tabel users
        echo "<div class='info'>";
        echo "<h4>🔄 Update Password di Tabel Users</h4>";
        
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'admin'");
        $result = $stmt->execute([$passwordHash, $email]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo "<p>✅ Password berhasil diupdate di tabel users!</p>";
            $updated = true;
        } else {
            echo "<p>⚠️ Tidak ada baris yang diupdate di tabel users</p>";
            
            // Coba insert jika belum ada
            echo "<p>→ Mencoba insert admin baru...</p>";
            $stmt = $pdo->prepare("INSERT INTO users (name, email, phone, password, role, is_verified, created_at) 
                                   VALUES (?, ?, ?, ?, 'admin', 1, NOW())
                                   ON DUPLICATE KEY UPDATE password = ?");
            $result = $stmt->execute(['Administrator', $email, '081234567890', $passwordHash, $passwordHash]);
            
            if ($result) {
                echo "<p>✅ Admin berhasil dibuat/diupdate!</p>";
                $updated = true;
            }
        }
        echo "</div>";
    }
    
    // Update di tabel admins juga
    try {
        echo "<div class='info'>";
        echo "<h4>🔄 Update Password di Tabel Admins</h4>";
        
        $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE email = ?");
        $result = $stmt->execute([$passwordHash, $email]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo "<p>✅ Password berhasil diupdate di tabel admins!</p>";
            $updated = true;
        } else {
            echo "<p>⚠️ Tidak ada baris yang diupdate di tabel admins</p>";
            
            // Coba insert
            echo "<p>→ Mencoba insert admin baru...</p>";
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, created_at) 
                                   VALUES (?, ?, ?, NOW())
                                   ON DUPLICATE KEY UPDATE password = ?");
            $result = $stmt->execute(['Administrator', $email, $passwordHash, $passwordHash]);
            
            if ($result) {
                echo "<p>✅ Admin berhasil dibuat/diupdate!</p>";
                $updated = true;
            }
        }
        echo "</div>";
    } catch (PDOException $e) {
        echo "<p>ℹ️ Tabel admins tidak ada atau error: " . $e->getMessage() . "</p>";
    }
    
    // Verifikasi hasil
    echo "<div class='info'>";
    echo "<h4>✅ Verifikasi Hasil</h4>";
    
    // Cek di users
    if ($hasRole) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p><strong>Tabel Users:</strong></p>";
            echo "<p>✅ Admin ditemukan</p>";
            echo "<p>Email: " . $user['email'] . "</p>";
            
            $verify = password_verify($newPassword, $user['password']);
            echo "<p>Password verify: " . ($verify ? '✅ COCOK' : '❌ TIDAK COCOK') . "</p>";
        }
    }
    
    // Cek di admins
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p><strong>Tabel Admins:</strong></p>";
            echo "<p>✅ Admin ditemukan</p>";
            echo "<p>Email: " . $admin['email'] . "</p>";
            
            $verify = password_verify($newPassword, $admin['password']);
            echo "<p>Password verify: " . ($verify ? '✅ COCOK' : '❌ TIDAK COCOK') . "</p>";
        }
    } catch (PDOException $e) {
        // Ignore
    }
    
    echo "</div>";
    
    if ($updated) {
        echo "<div class='success'>";
        echo "<h3>🎉 BERHASIL!</h3>";
        echo "<p><strong>Password admin berhasil direset!</strong></p>";
        echo "<hr>";
        echo "<p><strong>Email:</strong> dewishinta0128@gmail.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<hr>";
        echo "<p><a href='admin/login.php' class='btn btn-success btn-lg'>→ Login Sekarang</a></p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<p><strong>⚠️ PENTING:</strong> Setelah berhasil login, hapus file ini (reset_admin_password.php) untuk keamanan!</p>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Gagal Update</h3>";
        echo "<p>Tidak ada data yang berhasil diupdate. Silakan cek database manual.</p>";
        echo "</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Database Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "      </div>
    </div>
</div>
</body>
</html>";
?>
