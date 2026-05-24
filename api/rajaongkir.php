<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Load API key
$apiKey = getenv('RAJAONGKIR_API_KEY') ?: getSetting($pdo, 'rajaongkir_api_key');
if (!$apiKey || $apiKey === 'your_rajaongkir_api_key_here') {
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, 'RAJAONGKIR_API_KEY=') === 0) {
                $apiKey = trim(substr($line, strlen('RAJAONGKIR_API_KEY=')));
                break;
            }
        }
    }
}

$baseUrl = 'https://rajaongkir.komerce.id/api/v1';

if (!$apiKey || $apiKey === 'your_rajaongkir_api_key_here') {
    echo json_encode(['success' => false, 'message' => 'RajaOngkir API key belum dikonfigurasi']);
    exit;
}

function komercRequest($baseUrl, $apiKey, $endpoint, $method = 'GET', $data = []) {
    $ch = curl_init();
    $url = $baseUrl . $endpoint;
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['key: ' . $apiKey]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $response = curl_exec($ch);
    curl_close($ch);
    if (!$response) return null;
    return json_decode($response, true);
}

// Action: search destination (kota/kecamatan)
if ($action === 'search_city') {
    $query = trim($_GET['q'] ?? '');
    if (!$query) {
        echo json_encode(['success' => false, 'message' => 'Query diperlukan']);
        exit;
    }
    $result = komercRequest($baseUrl, $apiKey, '/destination/domestic-destination?search=' . urlencode($query));
    if ($result && isset($result['data'])) {
        $mapped = array_map(function($item) {
            return [
                'city_id'   => $item['id'],
                'city_name' => $item['label'],
                'type'      => '',
                'province'  => $item['province_name'] ?? '',
            ];
        }, array_slice($result['data'], 0, 15));
        echo json_encode(['success' => true, 'data' => $mapped]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kota tidak ditemukan']);
    }
    exit;
}

// Action: calculate shipping cost
if ($action === 'cost') {
    $originId   = $_POST['origin'] ?? '';
    $destId     = $_POST['destination'] ?? '';
    $weight     = intval($_POST['weight'] ?? 1000);
    $courier    = strtolower($_POST['courier'] ?? 'jne');

    if (!$originId || !$destId) {
        echo json_encode(['success' => false, 'message' => 'Origin dan destination diperlukan']);
        exit;
    }

    $result = komercRequest($baseUrl, $apiKey, '/calculate/domestic-cost', 'POST', [
        'origin'      => $originId,
        'destination' => $destId,
        'weight'      => $weight,
        'courier'     => $courier,
    ]);

    if ($result && isset($result['data'])) {
        $services = [];
        foreach ($result['data'] as $svc) {
            $services[] = [
                'courier'     => strtoupper($svc['code'] ?? $courier),
                'service'     => $svc['service'] ?? '',
                'description' => $svc['description'] ?? '',
                'cost'        => $svc['cost'] ?? 0,
                'etd'         => $svc['etd'] ?? '-',
            ];
        }
        echo json_encode(['success' => true, 'services' => $services]);
    } else {
        $msg = $result['meta']['message'] ?? 'Gagal menghitung ongkir';
        echo json_encode(['success' => false, 'message' => $msg]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action tidak valid']);
