<?php
header('Content-Type: application/json');
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$method = $_GET['method'] ?? '';
$distance = floatval($_GET['distance'] ?? 0);

// Special action: return cart weight in kg and grams
if ($method === 'get_weight') {
    $weightKg = getCartTotalWeight($pdo);
    echo json_encode([
        'success' => true,
        'weight' => $weightKg,
        'weight_gram' => (int)ceil($weightKg * 1000)
    ]);
    exit;
}

if (!$method) {
    echo json_encode(['success' => false, 'message' => 'Method required']);
    exit;
}

// Get total weight from cart
$totalWeight = getCartTotalWeight($pdo);

// Calculate shipping cost
$shippingCost = calculateShippingCost($pdo, $method, $distance, $totalWeight);

// Get breakdown for display
$breakdown = [];
if ($method === 'ekspedisi') {
    $costPerKg = getSetting($pdo, 'ekspedisi_cost_per_kg') ?: 10000;
    
    $weightRounded = ceil($totalWeight);
    $baseCost = $weightRounded * $costPerKg;
    
    $breakdown['weight'] = $totalWeight;
    $breakdown['weight_rounded'] = $weightRounded;
    $breakdown['base_cost'] = $baseCost;
    $breakdown['distance'] = $distance;
    
    // Determine tier
    $extraCost = 0;
    $tier = '';
    if ($distance > 600) {
        $extraCost = 15000;
        $tier = '>600 km';
    } elseif ($distance > 400) {
        $extraCost = 10000;
        $tier = '401-600 km';
    } elseif ($distance > 200) {
        $extraCost = 5000;
        $tier = '201-400 km';
    } else {
        $tier = '0-200 km';
    }
    
    if ($extraCost > 0) {
        $breakdown['tier'] = $tier;
        $breakdown['extra_cost'] = $extraCost;
    }
}

echo json_encode([
    'success' => true,
    'shipping_cost' => $shippingCost,
    'breakdown' => $breakdown
]);
