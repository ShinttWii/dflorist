<?php
$pageTitle = 'Keranjang - D\'Florist';
include 'includes/header.php';

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $productId = $_POST['product_id'];
        
        // Get product info from database
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if ($product) {
            $price = $product['is_promo'] ? $product['promo_price'] : $product['price'];
            $quantity = $_POST['quantity'];
            $stock = $product['stock'];
            
            // Check current cart quantity
            $currentCartQty = isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId]['quantity'] : 0;
            $newTotalQty = $currentCartQty + $quantity;
            
            // Validate against stock - if exceeds, keep current quantity (don't add)
            if ($newTotalQty > $stock) {
                // Don't add, keep current quantity
                if (!isset($_SESSION['cart'][$productId])) {
                    // If not in cart yet, add with max available stock
                    $_SESSION['cart'][$productId] = [
                        'product_id' => $productId,
                        'product_name' => $product['name'],
                        'price' => $price,
                        'original_price' => $product['price'],
                        'is_promo' => $product['is_promo'],
                        'quantity' => min($quantity, $stock),
                        'image' => $product['image'],
                        'stock' => $stock,
                        'selected' => true
                    ];
                }
                // If already in cart and would exceed, don't change quantity
            } else {
                // Stock is sufficient, add normally
                if (isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId]['quantity'] = $newTotalQty;
                    $_SESSION['cart'][$productId]['stock'] = $stock;
                } else {
                    $_SESSION['cart'][$productId] = [
                        'product_id' => $productId,
                        'product_name' => $product['name'],
                        'price' => $price,
                        'original_price' => $product['price'],
                        'is_promo' => $product['is_promo'],
                        'quantity' => $quantity,
                        'image' => $product['image'],
                        'stock' => $stock,
                        'selected' => true
                    ];
                }
            }
        }
        
        redirect('cart.php');
    } elseif ($action === 'update') {
        $productId = $_POST['product_id'];
        $quantity = $_POST['quantity'];
        
        if ($quantity > 0) {
            // Get current stock from database
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Limit to available stock
                $finalQty = min($quantity, $product['stock']);
                $_SESSION['cart'][$productId]['quantity'] = $finalQty;
                $_SESSION['cart'][$productId]['stock'] = $product['stock'];
            }
        } else {
            unset($_SESSION['cart'][$productId]);
        }
        
        redirect('cart.php');
    } elseif ($action === 'remove') {
        $productId = $_POST['product_id'];
        unset($_SESSION['cart'][$productId]);
        redirect('cart.php');
    } elseif ($action === 'toggle_select') {
        $productId = $_POST['product_id'];
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId]['selected'] = !($_SESSION['cart'][$productId]['selected'] ?? true);
        }
        redirect('cart.php');
    } elseif ($action === 'select_all') {
        $selectAll = $_POST['select_all'] === '1';
        foreach ($_SESSION['cart'] as $id => $item) {
            $_SESSION['cart'][$id]['selected'] = $selectAll;
        }
        redirect('cart.php');
    }
}

$cartItems = $_SESSION['cart'] ?? [];

// Update stock information from database for all cart items
if (!empty($cartItems)) {
    $productIds = array_keys($cartItems);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id, stock FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $stockData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    foreach ($cartItems as $id => $item) {
        if (isset($stockData[$id])) {
            $_SESSION['cart'][$id]['stock'] = $stockData[$id];
            // Adjust quantity if it exceeds current stock
            if ($item['quantity'] > $stockData[$id]) {
                $_SESSION['cart'][$id]['quantity'] = $stockData[$id];
            }
        }
    }
    $cartItems = $_SESSION['cart'];
}

$total = 0;
$selectedTotal = 0;
$totalDiscount = 0;
$selectedDiscount = 0;
$allSelected = true;

foreach ($cartItems as $item) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
    
    // Calculate discount if promo
    if (isset($item['is_promo']) && $item['is_promo'] && isset($item['original_price'])) {
        $originalSubtotal = $item['original_price'] * $item['quantity'];
        $discount = $originalSubtotal - $subtotal;
        $totalDiscount += $discount;
        
        if ($item['selected'] ?? true) {
            $selectedDiscount += $discount;
        }
    }
    
    if ($item['selected'] ?? true) {
        $selectedTotal += $subtotal;
    } else {
        $allSelected = false;
    }
}

$originalTotal = $total + $totalDiscount;
$selectedOriginalTotal = $selectedTotal + $selectedDiscount;
?>

<div class="container my-5">
    <h2 class="fw-bold mb-4">Keranjang Belanja</h2>
    
    <?php if (empty($cartItems)): ?>
    <div class="text-center py-5">
        <i class="fas fa-shopping-cart fa-5x text-muted mb-3"></i>
        <h4>Keranjang Anda Kosong</h4>
        <p class="text-muted">Belum ada produk di keranjang</p>
        <a href="products.php" class="btn btn-primary">Belanja Sekarang</a>
    </div>
    <?php else: ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <!-- Select All -->
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <form method="POST" class="d-flex align-items-center">
                            <input type="hidden" name="action" value="select_all">
                            <input type="hidden" name="select_all" value="<?php echo $allSelected ? '0' : '1'; ?>">
                            <input type="checkbox" class="form-check-input me-2" <?php echo $allSelected ? 'checked' : ''; ?> onchange="this.form.submit()">
                            <label class="form-check-label fw-semibold">Pilih Semua</label>
                        </form>
                    </div>
                    
                    <?php foreach ($cartItems as $item): ?>
                    <?php
                    $subtotal = $item['price'] * $item['quantity'];
                    $isSelected = $item['selected'] ?? true;
                    $isPromo = isset($item['is_promo']) && $item['is_promo'] && isset($item['original_price']);
                    $originalSubtotal = $isPromo ? ($item['original_price'] * $item['quantity']) : 0;
                    $maxStock = isset($item['stock']) ? $item['stock'] : 10;
                    ?>
                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                        <!-- Checkbox -->
                        <div class="me-2">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_select">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="checkbox" class="form-check-input" <?php echo $isSelected ? 'checked' : ''; ?> onchange="this.form.submit()">
                            </form>
                        </div>
                        
                        <!-- Product Image -->
                        <div class="me-3">
                            <?php
                            $imagePath = isset($item['image']) && $item['image'] ? UPLOAD_URL . $item['image'] : 'https://via.placeholder.com/80';
                            ?>
                            <img src="<?php echo $imagePath; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                 style="width: 70px; height: 70px; object-fit: cover; border-radius: 10px;"
                                 onerror="this.src='https://via.placeholder.com/80'">
                        </div>
                        
                        <!-- Product Info -->
                        <div class="flex-grow-1 me-3">
                            <h6 class="mb-1" style="font-size: 0.95rem;"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                            <div>
                                <?php if ($isPromo): ?>
                                    <span class="text-decoration-line-through text-muted" style="font-size: 0.85rem;">
                                        <?php echo formatRupiah($item['original_price']); ?>
                                    </span>
                                    <span class="text-danger fw-bold ms-2" style="font-size: 0.9rem;">
                                        <?php echo formatRupiah($item['price']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="fw-bold" style="font-size: 0.9rem;">
                                        <?php echo formatRupiah($item['price']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted" style="font-size: 0.75rem;">Stok: <?php echo $maxStock; ?></small>
                        </div>
                        
                        <!-- Quantity Controls -->
                        <div class="me-3">
                            <div class="input-group input-group-sm" style="width: 110px;">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="updateQty(<?php echo $item['product_id']; ?>, -1, <?php echo $maxStock; ?>)" id="btn-minus-<?php echo $item['product_id']; ?>">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="text" class="form-control text-center" id="qty-<?php echo $item['product_id']; ?>" value="<?php echo $item['quantity']; ?>" readonly style="font-size: 0.9rem;">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="updateQty(<?php echo $item['product_id']; ?>, 1, <?php echo $maxStock; ?>)" id="btn-plus-<?php echo $item['product_id']; ?>" <?php echo ($item['quantity'] >= $maxStock) ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <form method="POST" id="form-<?php echo $item['product_id']; ?>" style="display: none;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="hidden" name="quantity" id="qty-input-<?php echo $item['product_id']; ?>">
                            </form>
                        </div>
                        
                        <!-- Remove Button -->
                        <div>
                            <form method="POST">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Ringkasan Belanja</h5>
                    
                    <?php if ($selectedDiscount > 0): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="font-size: 0.9rem;">Total Harga</span>
                        <span style="font-size: 0.9rem;"><?php echo formatRupiah($selectedOriginalTotal); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-danger" style="font-size: 0.9rem;">Total Diskon</span>
                        <span class="text-danger" style="font-size: 0.9rem;">-<?php echo formatRupiah($selectedDiscount); ?></span>
                    </div>
                    <?php else: ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span style="font-size: 0.9rem;">Total Harga</span>
                        <span style="font-size: 0.9rem;"><?php echo formatRupiah($selectedTotal); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between mb-2">
                        <span style="font-size: 0.9rem;">Item Dipilih</span>
                        <span style="font-size: 0.9rem;"><?php echo count(array_filter($cartItems, function($item) { return $item['selected'] ?? true; })); ?> item</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total Pembayaran</strong>
                        <strong class="text-primary" style="font-size: 1.1rem;"><?php echo formatRupiah($selectedTotal); ?></strong>
                    </div>
                    
                    <?php if ($selectedDiscount > 0): ?>
                    <div class="alert alert-success py-2 mb-3" style="font-size: 0.85rem;">
                        <i class="fas fa-tag"></i> Hemat <?php echo formatRupiah($selectedDiscount); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isCustomerLoggedIn()): ?>
                    <a href="checkout.php" class="btn btn-primary w-100">
                        Checkout (<?php echo count(array_filter($cartItems, function($item) { return $item['selected'] ?? true; })); ?>)
                    </a>
                    <?php else: ?>
                    <a href="login.php?redirect=checkout.php" class="btn btn-primary w-100">Login untuk Checkout</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function updateQty(productId, change, maxStock) {
        var qtyElement = document.getElementById('qty-' + productId);
        var btnPlus = document.getElementById('btn-plus-' + productId);
        var btnMinus = document.getElementById('btn-minus-' + productId);
        var currentQty = parseInt(qtyElement.value);
        var newQty = currentQty + change;
        
        if (newQty >= 1 && newQty <= maxStock) {
            qtyElement.value = newQty;
            document.getElementById('qty-input-' + productId).value = newQty;
            
            // Update button states
            if (newQty >= maxStock) {
                btnPlus.disabled = true;
            } else {
                btnPlus.disabled = false;
            }
            
            if (newQty <= 1) {
                btnMinus.disabled = true;
            } else {
                btnMinus.disabled = false;
            }
            
            document.getElementById('form-' + productId).submit();
        }
    }
    </script>
    
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
