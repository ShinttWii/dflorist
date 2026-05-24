<?php
// Konfigurasi Database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dflorist');

// Koneksi Database
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

// Konfigurasi Aplikasi - Auto detect base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Detect base directory
$scriptPath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$configPath = str_replace('\\', '/', dirname(__FILE__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');

// Calculate base URL
if (strpos($configPath, $docRoot) === 0) {
    $baseDir = substr(dirname($configPath), strlen($docRoot));
} else {
    $baseDir = dirname($scriptPath);
}

$baseDir = ($baseDir === '/' || $baseDir === '\\' || $baseDir === '.') ? '' : $baseDir;

define('SITE_URL', $protocol . '://' . $host . $baseDir);
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_PATH', __DIR__ . '/../assets/images/products/');
define('UPLOAD_URL', SITE_URL . '/assets/images/products/');

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
