<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = $_POST['id'] ?? 0;
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $price = $_POST['price'];
        $categoryId = $_POST['category_id'];
        $isPromo = isset($_POST['is_promo']) ? 1 : 0;
        $promoPrice = $_POST['promo_price'] ?? null;
        $stock = $_POST['stock'];
        $weight = $_POST['weight'] ?? 0.5; // Default 0.5 kg
        
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $newFilename = uniqid() . '.' . $ext;
                
                // Pastikan folder upload ada
                $uploadDir = __DIR__ . '/../assets/images/products/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $uploadPath = $uploadDir . $newFilename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $imagePath = $newFilename;
                } else {
                    $error = 'Gagal upload gambar';
                }
            } else {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau GIF';
            }
        }
        
        if ($action === 'add') {
            // Cek duplikat nama (hanya kalau tidak ada error sebelumnya)
            if (!$error) {
                $checkStmt = $pdo->prepare("SELECT id FROM products WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))");
                $checkStmt->execute([$name]);
                if ($checkStmt->fetch()) {
                    $error = 'Produk dengan nama "' . htmlspecialchars($name) . '" sudah ada. Gunakan nama yang berbeda.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (name, description, price, category_id, image, is_promo, promo_price, stock, weight)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $price, $categoryId, $imagePath, $isPromo, $promoPrice, $stock, $weight]);
                    $success = 'Produk berhasil ditambahkan';
                }
            }
        } else {
            if ($imagePath) {
                $stmt = $pdo->prepare("
                    UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image = ?, is_promo = ?, promo_price = ?, stock = ?, weight = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $price, $categoryId, $imagePath, $isPromo, $promoPrice, $stock, $weight, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, is_promo = ?, promo_price = ?, stock = ?, weight = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $price, $categoryId, $isPromo, $promoPrice, $stock, $weight, $id]);
            }
            $success = 'Produk berhasil diupdate';
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Produk berhasil dihapus';
    } elseif ($action === 'toggle_status') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Status produk berhasil diubah';
    }
}

// Get products
$stmt = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC
");
$products = $stmt->fetchAll();

// Get categories
$categories = $pdo->query("SELECT * FROM categories")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Admin D'florist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Manajemen Produk</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#productModal" onclick="resetForm()">
                        <i class="fas fa-plus"></i> Tambah Produk
                    </button>
                </div>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th width="50">No</th>
                                <th width="80">Gambar</th>
                                <th>Nama</th>
                                <th>Kategori</th>
                                <th>Harga</th>
                                <th>Promo</th>
                                <th width="80">Stok</th>
                                <th width="80">Berat</th>
                                <th width="100">Status</th>
                                <th width="150">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1; // Counter untuk nomor urut
                            foreach ($products as $product): 
                            ?>
                            <tr>
                                <td class="text-center fw-bold"><?php echo $no++; ?></td>
                                <td>
                                    <img src="<?php echo $product['image'] ? UPLOAD_URL . $product['image'] : 'https://via.placeholder.com/50'; ?>" 
                                         width="50" height="50" style="object-fit: cover; border-radius: 5px;">
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo $product['category_name']; ?></td>
                                <td><?php echo formatRupiah($product['price']); ?></td>
                                <td>
                                    <?php if ($product['is_promo']): ?>
                                        <span class="badge bg-danger"><?php echo formatRupiah($product['promo_price']); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo $product['stock']; ?></td>
                                <td class="text-center"><?php echo $product['weight'] ?? 0.5; ?> kg</td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $product['is_active'] ? 'btn-success' : 'btn-secondary'; ?>">
                                            <?php echo $product['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick='editProduct(<?php echo json_encode($product); ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus produk ini?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" id="productForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Tambah Produk</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="productId">
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <input type="text" name="name" id="name" class="form-control" required autocomplete="off">
                            <div id="nameCheckMsg" style="display:none; margin-top:6px; padding:8px 12px; border-radius:8px; font-size:13px;"></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" id="description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="category_id" id="category_id" class="form-select" required>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stok</label>
                                <input type="number" name="stock" id="stock" class="form-control" value="0" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Berat (kg)</label>
                                <input type="number" name="weight" id="weight" class="form-control" value="0.5" min="0.1" step="0.1" required>
                                <small class="text-muted">Untuk perhitungan ongkir ekspedisi</small>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga Normal</label>
                                <input type="number" name="price" id="price" class="form-control" min="0" step="1000" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Harga Promo</label>
                                <input type="number" name="promo_price" id="promo_price" class="form-control" min="0" step="1000">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_promo" id="is_promo">
                                <label class="form-check-label" for="is_promo">
                                    Produk Promo
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Gambar Produk</label>
                            <input type="file" name="image" id="image" class="form-control" accept="image/*">
                            <small class="text-muted">Format: JPG, PNG, GIF. Max 2MB</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function resetForm() {
        document.getElementById('productForm').reset();
        document.getElementById('formAction').value = 'add';
        document.getElementById('modalTitle').textContent = 'Tambah Produk';
    }
    
    function editProduct(product) {
        document.getElementById('formAction').value = 'edit';
        document.getElementById('productId').value = product.id;
        document.getElementById('name').value = product.name;
        document.getElementById('description').value = product.description;
        document.getElementById('price').value = product.price;
        document.getElementById('category_id').value = product.category_id;
        document.getElementById('promo_price').value = product.promo_price || '';
        document.getElementById('stock').value = product.stock;
        document.getElementById('weight').value = product.weight || 0.5;
        document.getElementById('is_promo').checked = product.is_promo == 1;
        document.getElementById('modalTitle').textContent = 'Edit Produk';
        hideNameMsg();
        const modal = new bootstrap.Modal(document.getElementById('productModal'));
        modal.show();
    }

    // Cek duplikat nama real-time
    let nameTimer;
    let nameIsValid = true;

    document.getElementById('name').addEventListener('input', function() {
        clearTimeout(nameTimer);
        const name = this.value.trim();
        const action = document.getElementById('formAction').value;
        const excludeId = action === 'edit' ? document.getElementById('productId').value : 0;

        if (name.length < 2) { hideNameMsg(); return; }

        nameTimer = setTimeout(() => {
            fetch('check_product_name.php?name=' + encodeURIComponent(name) + '&exclude_id=' + excludeId)
                .then(r => r.json())
                .then(data => {
                    if (data.exists) {
                        showNameMsg('Produk dengan nama ini sudah ada: "' + data.existing_name + '"', 'error');
                        nameIsValid = false;
                    } else {
                        hideNameMsg();
                        nameIsValid = true;
                    }
                });
        }, 400);
    });

    document.getElementById('productForm').addEventListener('submit', function(e) {
        if (!nameIsValid) {
            e.preventDefault();
            showNameMsg('Nama produk sudah ada. Gunakan nama yang berbeda.', 'error');
            document.getElementById('name').focus();
        }
    });

    function showNameMsg(msg, type) {
        const el = document.getElementById('nameCheckMsg');
        el.textContent = msg;
        el.style.display = 'block';
        el.style.background = type === 'error' ? '#fdecea' : '#e8f8f0';
        el.style.color = type === 'error' ? '#c62828' : '#2e7d32';
        el.style.border = '1px solid ' + (type === 'error' ? '#f5c6cb' : '#c3e6cb');
    }

    function hideNameMsg() {
        document.getElementById('nameCheckMsg').style.display = 'none';
        nameIsValid = true;
    }
    </script>
</body>
</html>
