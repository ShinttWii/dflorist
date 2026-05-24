<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isCustomerLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$addressId = intval($_POST['address_id'] ?? 0);
if (!$addressId) {
    echo json_encode(['success' => false]);
    exit;
}

// Get address text
$stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
$stmt->execute([$addressId, $_SESSION['customer_id']]);
$addr = $stmt->fetch();

if (!$addr || ($addr['latitude'] != 0 && $addr['longitude'] != 0)) {
    // Already has coords or not found
    echo json_encode(['success' => true, 'lat' => $addr['latitude'] ?? 0, 'lng' => $addr['longitude'] ?? 0]);
    exit;
}

// Geocode via Nominatim
$query = urlencode($addr['address'] . ', Indonesia');
$url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}&countrycodes=id&limit=1";
$ctx = stream_context_create(['http' => ['timeout' => 5, 'header' => "User-Agent: DFlorist/1.0\r\n"]]);
$result = @file_get_contents($url, false, $ctx);

$lat = 0; $lng = 0;
if ($result) {
    $data = json_decode($result, true);
    if (!empty($data[0])) {
        $lat = $data[0]['lat'];
        $lng = $data[0]['lon'];
        // Save to DB
        $pdo->prepare("UPDATE addresses SET latitude=?, longitude=? WHERE id=?")->execute([$lat, $lng, $addressId]);
    }
}

echo json_encode(['success' => true, 'lat' => floatval($lat), 'lng' => floatval($lng)]);
