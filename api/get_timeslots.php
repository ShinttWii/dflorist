<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';

try {
    $stmt = $pdo->query("SELECT * FROM delivery_timeslots WHERE is_active = 1 ORDER BY sort_order ASC");
    $timeslots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'timeslots' => $timeslots
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal mengambil data slot waktu'
    ]);
}
