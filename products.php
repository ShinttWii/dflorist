<?php
$pageTitle = 'Produk - D\'Florist';
include 'includes/header.php';

// Filter
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? '';
$promo = $_GET['promo'] ?? '';

// Query builder
$sql = "SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.is_active = 1";
$params = [];

if ($category) {
    $sql .= " AND c.name = ?";
    $params[] = $category;
}

if ($promo) {
    $sql .= " AND p.is_promo = 1";
}



// Perbaikan Bagian Sorting
if ($sort === 'price_asc') {
    $sql .= " ORDER BY (CASE WHEN p.is_promo = 1 AND p.promo_price > 0 THEN p.promo_price ELSE p.price END) ASC";
} elseif ($sort === 'price_desc') {
    $sql .= " ORDER BY (CASE WHEN p.is_promo = 1 AND p.promo_price > 0 THEN p.promo_price ELSE p.price END) DESC";
} else {
    // Default urutan terbaru
    $sql .= " ORDER BY p.created_at DESC";
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get categories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>

<div class="container my-5">
    <h2 class="fw-bold mb-4">Semua Produk</h2>
    
    <!-- Filter -->
    <div class="row mb-4">
        <div class="col-md-7">
            <div class="btn-group flex-wrap" role="group">
                <a href="products.php" class="btn <?php echo !$category ? 'btn-primary' : 'btn-outline-primary'; ?>">Semua</a>
                <?php foreach ($categories as $cat): ?>
                <a href="products.php?category=<?php echo $cat['name']; ?>" 
                   class="btn <?php echo $category === $cat['name'] ? 'btn-primary' : 'btn-outline-primary'; ?>">
                    <?php echo $cat['name']; ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col-md-3 mt-2 mt-md-0">
            <div class="input-group">
                <span class="input-group-text"><i class="fas fa-search"></i></span>
                <input type="text" id="productSearch" class="form-control" placeholder="Cari nama produk...">
            </div>
        </div>
        <div class="col-md-2 mt-2 mt-md-0">
    <select class="form-select" id="sortSelect">
        <option value="">Urutkan</option>
        <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Termurah</option>
        <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Termahal</option>
    </select>
</div>

<script>
document.getElementById('sortSelect').addEventListener('change', function() {
    const url = new URL(window.location.href);
    const val = this.value;

    if (val) {
        url.searchParams.set('sort', val);
    } else {
        url.searchParams.delete('sort');
    }
    
    // Pastikan halaman kembali ke 1 jika ada pagination, 
    // tapi untuk kasus Anda ini akan menjaga category tetap ada di URL
    window.location.href = url.toString();
});
</script>
    </div>
    
    <!-- Products Grid -->
    <div class="row g-4">
        <?php if (empty($products)): ?>
        <div class="col-12 text-center">
            <p class="text-muted">Tidak ada produk ditemukan</p>
        </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
            <div class="col-md-3 product-item" data-name="<?php echo strtolower(htmlspecialchars($product['name'])); ?>">
                <div class="card product-card position-relative">
                    <?php if ($product['is_promo']): ?>
                    <span class="badge-promo">PROMO</span>
                    <?php endif; ?>
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
                                <?php if ($product['is_promo']): ?>
                                    <div class="text-decoration-line-through text-muted" style="font-size: 0.75rem;"><?php echo formatRupiah($product['price']); ?></div>
                                    <div class="text-danger fw-bold" style="font-size: 0.9rem;"><?php echo formatRupiah($product['promo_price']); ?></div>
                                <?php else: ?>
                                    <div class="fw-bold" style="font-size: 0.9rem;"><?php echo formatRupiah($product['price']); ?></div>
                                <?php endif; ?>
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
                                onclick="addToCartAjax(<?php echo $product['id']; ?>, document.getElementById('qty-card-<?php echo $product['id']; ?>').value, this)">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="mb-3">
                            <?php if ($product['is_promo']): ?>
                                <span class="text-decoration-line-through text-muted"><?php echo formatRupiah($product['price']); ?></span>
                                <span class="text-danger fw-bold ms-2"><?php echo formatRupiah($product['promo_price']); ?></span>
                            <?php else: ?>
                                <span class="fw-bold"><?php echo formatRupiah($product['price']); ?></span>
                            <?php endif; ?>
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

<script>
document.getElementById('productSearch').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('.product-item').forEach(item => {
        item.style.display = (!q || item.dataset.name.includes(q)) ? '' : 'none';
    });
});
</script>


<?php include 'includes/footer.php'; ?>
