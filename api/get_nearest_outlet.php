<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$lat = $_GET['lat'] ?? null;
$lng = $_GET['lng'] ?? null;

if ($lat === null || $lng === null || ($lat == 0 && $lng == 0)) {
    // Koordinat tidak valid — kembalikan outlet yang punya city_id sebagai fallback
    $stmt = $pdo->query("SELECT * FROM outlets WHERE is_active = 1 AND city_id IS NOT NULL AND city_id != 0 LIMIT 1");
    $outlet = $stmt->fetch();
    if (!$outlet) {
        $stmt = $pdo->query("SELECT * FROM outlets WHERE is_active = 1 LIMIT 1");
        $outlet = $stmt->fetch();
    }
    if ($outlet) {
        $cityId = (!empty($outlet['city_id']) && $outlet['city_id'] != 0) ? $outlet['city_id'] : null;
        echo json_encode([
            'success' => true,
            'outlet' => [
                'id'        => $outlet['id'],
                'name'      => $outlet['name'],
                'address'   => $outlet['address'],
                'phone'     => $outlet['phone'],
                'distance'  => 0,
                'latitude'  => $outlet['latitude'],
                'longitude' => $outlet['longitude'],
                'city_id'   => $cityId,
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tidak ada outlet aktif']);
    }
    exit;
}

$nearestOutlet = findNearestOutlet($pdo, $lat, $lng);

if ($nearestOutlet) {
    // Kalau outlet terdekat tidak punya city_id, cari outlet lain yang punya city_id
    $cityId = (!empty($nearestOutlet['city_id']) && $nearestOutlet['city_id'] != 0) ? $nearestOutlet['city_id'] : null;
    if (!$cityId) {
        $stmt = $pdo->query("SELECT * FROM outlets WHERE is_active = 1 AND city_id IS NOT NULL AND city_id != 0 LIMIT 1");
        $outletWithCity = $stmt->fetch();
        if ($outletWithCity) {
            $cityId = $outletWithCity['city_id'];
        }
    }
    echo json_encode([
        'success' => true,
        'outlet' => [
            'id'        => $nearestOutlet['id'],
            'name'      => $nearestOutlet['name'],
            'address'   => $nearestOutlet['address'],
            'phone'     => $nearestOutlet['phone'],
            'distance'  => $nearestOutlet['distance'],
            'latitude'  => $nearestOutlet['latitude'],
            'longitude' => $nearestOutlet['longitude'],
            'city_id'   => $cityId,
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Tidak ada outlet aktif']);
}
