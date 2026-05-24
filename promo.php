<?php
$pageTitle = 'Produk Promo - D\'Florist';
include 'includes/header.php';

// Get all promo products
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.is_promo = 1 AND p.is_active = 1 
    ORDER BY p.created_at DESC
");
$promoProducts = $stmt->fetchAll();
?>

<div class="container my-5">
    <h2 class="fw-bold mb-4">Produk Promo</h2>
    
    <?php if (!empty($promoProducts)): ?>
    <div class="alert alert-info mb-4" style="border-left: 4px solid #FF69B4;">
        <i class="fas fa-info-circle"></i> 
        Dapatkan penawaran terbaik untuk produk pilihan kami. Promo terbatas!
    </div>
    <div class="input-group mb-4" style="max-width:320px;">
        <span class="input-group-text"><i class="fas fa-search"></i></span>
        <input type="text" id="promoSearch" class="form-control" placeholder="Cari nama produk promo...">
    </div>
    <?php endif; ?>
    
    <!-- Products Grid -->
    <div class="row g-4">
        <?php if (empty($promoProducts)): ?>
        <div class="col-12 text-center py-5">
            <i class="fas fa-tag fa-5x text-muted mb-3"></i>
            <h4>Belum Ada Produk Promo</h4>
            <p class="text-muted">Saat ini belum ada produk dengan penawaran khusus</p>
            <a href="products.php" class="btn btn-primary mt-3">Lihat Semua Produk</a>
        </div>
        <?php else: ?>
            <?php foreach ($promoProducts as $product): ?>
            <div class="col-md-3 product-item" data-name="<?php echo strtolower(htmlspecialchars($product['name'])); ?>">
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
                                <?php 
                                $discount = (($product['price'] - $product['promo_price']) / $product['price']) * 100;
                                ?>
                                <small class="badge bg-danger">Hemat <?php echo round($discount); ?>%</small>
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
                            <form method="POST" action="cart.php" class="d-inline">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantity" id="qty-submit-<?php echo $product['id']; ?>" value="1">
                                <button type="submit" class="btn btn-link p-0 cart-icon-btn" onclick="document.getElementById('qty-submit-<?php echo $product['id']; ?>').value = document.getElementById('qty-card-<?php echo $product['id']; ?>').value">
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
</div>

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

<?php include 'includes/footer.php'; ?>
<script>
const promoSearchEl = document.getElementById('promoSearch');
if (promoSearchEl) {
    promoSearchEl.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('.product-item').forEach(item => {
            item.style.display = (!q || item.dataset.name.includes(q)) ? '' : 'none';
        });
    });
}
</script>
