<?php
$pageTitle = 'Beranda - D\'Florist';
include 'includes/header.php';

// Ambil produk promo (hanya 4 untuk beranda)
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_promo = 1 AND p.is_active = 1 
    LIMIT 4
");
$promoProducts = $stmt->fetchAll();

// Ambil ulasan terbaru
$stmt = $pdo->query("
    SELECT r.*, u.name as user_name, p.name as product_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN products p ON r.product_id = p.id 
    ORDER BY r.created_at DESC 
    LIMIT 3
");
$recentReviews = $stmt->fetchAll();
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Selamat Datang di D'florist</h1>
        <p class="lead mb-4">Abadikan Setiap Momen dengan Keindahan yang Takkan Layu</p>
        <a href="products.php" class="btn btn-primary btn-lg px-5">Lihat Produk</a>
    </div>
</section>

<!-- Produk Promo -->
<section class="container my-5">
    <h2 class="text-center fw-bold mb-4">Produk Promo</h2>
    <div class="row g-4">
        <?php if (empty($promoProducts)): ?>
        <div class="col-12 text-center">
            <p class="text-muted">Belum ada produk promo saat ini</p>
        </div>
        <?php else: ?>
            <?php foreach ($promoProducts as $product): ?>
            <div class="col-md-3">
                <div class="card product-card position-relative">
                    <span class="badge-promo">PROMO</span>
                    <a href="product_detail.php?id=<?php echo $product['id']; ?>">
                        <img src="<?php echo $product['image'] ? UPLOAD_URL . $product['image'] : 'https://via.placeholder.com/300x250'; ?>" 
                             class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <div class="card-body">
                        <span class="badge bg-secondary mb-2"><?php echo $product['category_name']; ?></span>
                        <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        </a>
                        <p class="text-muted small"><?php echo substr(htmlspecialchars($product['description']), 0, 80); ?>...</p>
                        
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fas fa-box"></i> Stok: <?php echo $product['stock']; ?>
                            </small>
                        </div>
                        
                        <?php if ($product['stock'] > 0): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <div class="text-decoration-line-through text-muted" style="font-size: 0.75rem;"><?php echo formatRupiah($product['price']); ?></div>
                                <div class="text-danger fw-bold" style="font-size: 0.9rem;"><?php echo formatRupiah($product['promo_price']); ?></div>
                            </div>
                            <div class="input-group input-group-sm" style="width: 90px;">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="decreaseQtyCard(<?php echo $product['id']; ?>)" style="padding: 0.2rem 0.4rem;">
                                    <i class="fas fa-minus" style="font-size: 0.7rem;"></i>
                                </button>
                                <input type="number" id="qty-card-<?php echo $product['id']; ?>" class="form-control text-center" value="1" min="1" max="<?php echo $product['stock']; ?>" readonly style="font-size: 0.75rem; padding: 0.2rem;">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="increaseQtyCard(<?php echo $product['id']; ?>, <?php echo $product['stock']; ?>)" id="btn-plus-card-<?php echo $product['id']; ?>" style="padding: 0.2rem 0.4rem;">
                                    <i class="fas fa-plus" style="font-size: 0.7rem;"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm flex-grow-1">Lihat Detail</a>
                            <button type="button" class="btn btn-link p-0 cart-icon-btn"
                                onclick="addToCartAjax(<?php echo $product['id']; ?>, document.getElementById('qty-card-<?php echo $product['id']; ?>') ? document.getElementById('qty-card-<?php echo $product['id']; ?>').value : 1, this)">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <span class="text-decoration-line-through text-muted"><?php echo formatRupiah($product['price']); ?></span>
                            <span class="text-danger fw-bold ms-2"><?php echo formatRupiah($product['promo_price']); ?></span>
                        </div>
                        <button class="btn btn-secondary btn-sm w-100" disabled>
                            <i class="fas fa-times"></i> Stok Habis
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="text-center mt-4">
        <a href="promo.php" class="btn btn-outline-primary">Lihat Semua Promo</a>
    </div>
</section>

<!-- Ulasan Pelanggan -->
<section class="container my-5">
    <h2 class="text-center fw-bold mb-4">Ulasan Pelanggan</h2>
    <div class="row">
        <?php if (empty($recentReviews)): ?>
        <div class="col-12 text-center">
            <p class="text-muted">Belum ada ulasan</p>
        </div>
        <?php else: ?>
            <?php foreach ($recentReviews as $review): ?>
            <div class="col-md-4">
                <div class="review-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($review['product_name']); ?></small>
                        </div>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= $review['rating']): ?>
                                    <i class="fas fa-star"></i>
                                <?php else: ?>
                                    <i class="far fa-star"></i>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                    <small class="text-muted"><?php echo date('d M Y', strtotime($review['created_at'])); ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="text-center mt-4">
        <a href="reviews.php" class="btn btn-outline-primary">Lihat Semua Ulasan</a>
    </div>
</section>

<!-- Keunggulan -->
<section class="container my-5">
    <div class="row text-center g-4">
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <i class="fas fa-calendar-check fa-3x text-primary mb-3"></i>
                <h5>Pesan Kapan Saja</h5>
                <p class="text-muted">Pre-order bunga sesuai momen spesialmu</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <i class="fas fa-truck fa-3x text-primary mb-3"></i>
                <h5>Pengiriman Tepat Waktu</h5>
                <p class="text-muted">Pilih tanggal & jam, kami antar tepat waktu</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-4 h-100">
                <i class="fas fa-star fa-3x text-primary mb-3"></i>
                <h5>Rangkaian Berkualitas</h5>
                <p class="text-muted">Bunga pilihan, dirangkai dengan penuh perhatian</p>
            </div>
        </div>
    </div>
</section>

<script>
function increaseQtyCard(productId, maxStock) {
    var qty = document.getElementById('qty-card-' + productId);
    var btnPlus = document.getElementById('btn-plus-card-' + productId);
    var currentVal = parseInt(qty.value);
    
    if (currentVal < maxStock) {
        qty.value = currentVal + 1;
        if (qty.value >= maxStock) {
            btnPlus.disabled = true;
        }
    }
}

function decreaseQtyCard(productId) {
    var qty = document.getElementById('qty-card-' + productId);
    var btnPlus = document.getElementById('btn-plus-card-' + productId);
    var currentVal = parseInt(qty.value);
    
    if (currentVal > 1) {
        qty.value = currentVal - 1;
        btnPlus.disabled = false;
    }
}
</script>

<!-- Toast dan fungsi addToCart sudah ada di main.js -->

<?php include 'includes/footer.php'; ?>
