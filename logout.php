<?php
session_start();
require_once 'config/database.php';

// Update last activity to 6 minutes ago on logout (so status becomes offline but still recent)
if (isset($_SESSION['customer_id'])) {
    $stmt = $pdo->prepare("UPDATE users SET updated_at = DATE_SUB(NOW(), INTERVAL 6 MINUTE) WHERE id = ?");
    $stmt->execute([$_SESSION['customer_id']]);
    
    // Only unset customer session variables, not destroy entire session
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_email']);
    unset($_SESSION['cart']);
}

header('Location: index.php');
exit;
