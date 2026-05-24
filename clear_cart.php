<?php
session_start();

// Clear cart
$_SESSION['cart'] = [];

echo "<!DOCTYPE html>
<html lang='id'>
<head>
    <meta charset='UTF-8'>
    <title>Clear Cart - D'florist</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: #C5E3F6; padding: 40px 0; }
        .card { background: #FFD6E8; border: none; border-radius: 15px; max-width: 500px; margin: 0 auto; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='card'>
            <div class='card-body text-center p-5'>
                <i class='fas fa-check-circle text-success' style='font-size: 4rem;'></i>
                <h3 class='mt-3'>Keranjang Berhasil Dikosongkan!</h3>
                <p class='text-muted'>Silakan tambahkan produk lagi ke keranjang.</p>
                <a href='products.php' class='btn btn-primary mt-3'>Belanja Sekarang</a>
            </div>
        </div>
    </div>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
</body>
</html>";
?>
