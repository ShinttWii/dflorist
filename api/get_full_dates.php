<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$method = $_GET['method'] ?? 'kurir_toko';

// Ambil semua tanggal yang kuotanya penuh untuk metode ini
$stmt = $pdo->prepare("
    SELECT delivery_date 
    FROM delivery_quotas 
    WHERE delivery_method = ? 
      AND current_quota >= max_quota
      AND delivery_date >= CURDATE()
");
$stmt->execute([$method]);
$fullDates = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['full_dates' => $fullDates]);
