<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isAdminLoggedIn()) {
    redirect(ADMIN_URL . '/login.php');
}

$success = '';
$error = '';

// Handle Time Slot Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'add_timeslot') {
        $timeSlot = sanitize($_POST['time_slot']);
        try {
            $stmt = $pdo->prepare("INSERT INTO delivery_timeslots (time_slot, is_active, sort_order) VALUES (?, 1, (SELECT IFNULL(MAX(sort_order), 0) + 1 FROM delivery_timeslots AS dt))");
            $stmt->execute([$timeSlot]);
            $success = 'Slot waktu berhasil ditambahkan';
        } catch (Exception $e) {
            $error = 'Gagal menambahkan slot waktu: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_timeslot') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM delivery_timeslots WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Slot waktu berhasil dihapus';
        } catch (Exception $e) {
            $error = 'Gagal menghapus slot waktu: ' . $e->getMessage();
        }
    } elseif ($action === 'toggle_timeslot') {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("UPDATE delivery_timeslots SET is_active = NOT is_active WHERE id = ?");
            $stmt->execute([$id]);
            $success = 'Status slot waktu berhasil diubah';
        } catch (Exception $e) {
            $error = 'Gagal mengubah status: ' . $e->getMessage();
        }
    }
}

// Update settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $storeLat = $_POST['store_latitude'];
    $storeLng = $_POST['store_longitude'];
    $maxRadius = $_POST['max_delivery_radius'];
    $minPreorderDays = $_POST['min_preorder_days'];
    $maxQuota = $_POST['max_quota_per_date'];
    
    // Shipping costs
    $kurirTokoCost = $_POST['kurir_toko_cost'];
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        
        $stmt->execute(['store_latitude', $storeLat, $storeLat]);
        $stmt->execute(['store_longitude', $storeLng, $storeLng]);
        $stmt->execute(['max_delivery_radius', $maxRadius, $maxRadius]);
        $stmt->execute(['min_preorder_days', $minPreorderDays, $minPreorderDays]);
        $stmt->execute(['max_quota_per_date', $maxQuota, $maxQuota]);
        $stmt->execute(['kurir_toko_cost', $kurirTokoCost, $kurirTokoCost]);
        
        // RajaOngkir API key
        if (!empty($_POST['rajaongkir_api_key'])) {
            $rajaongkirKey = sanitize($_POST['rajaongkir_api_key']);
            $stmt->execute(['rajaongkir_api_key', $rajaongkirKey, $rajaongkirKey]);
        }

        // Midtrans keys
        if (!empty($_POST['midtrans_server_key'])) {
            $v = trim($_POST['midtrans_server_key']);
            $stmt->execute(['midtrans_server_key', $v, $v]);
        }
        if (!empty($_POST['midtrans_client_key'])) {
            $v = trim($_POST['midtrans_client_key']);
            $stmt->execute(['midtrans_client_key', $v, $v]);
        }
        $midtransProd = isset($_POST['midtrans_is_production']) ? 'true' : 'false';
        $stmt->execute(['midtrans_is_production', $midtransProd, $midtransProd]);
        
        // Origin city untuk ekspedisi
        if (!empty($_POST['origin_city_id'])) {
            $stmt->execute(['origin_city_id', sanitize($_POST['origin_city_id']), sanitize($_POST['origin_city_id'])]);
            $stmt->execute(['origin_city_name', sanitize($_POST['origin_city_name']), sanitize($_POST['origin_city_name'])]);
        }
        
        $pdo->commit();
        $success = 'Pengaturan berhasil disimpan';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Gagal menyimpan pengaturan: ' . $e->getMessage();
    }
}

$bannerDir = __DIR__ . '/../assets/images/banners/';
if (!file_exists($bannerDir)) mkdir($bannerDir, 0755, true);

// Upload hero background image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_hero') {
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','avif'])) {
            $dest = __DIR__ . '/../assets/images/hero-bg.' . $ext;
            // Hapus hero lama
            foreach (['jpg','jpeg','png','webp','avif'] as $e) {
                $old = __DIR__ . '/../assets/images/hero-bg.' . $e;
                if (file_exists($old)) unlink($old);
            }
            move_uploaded_file($_FILES['hero_image']['tmp_name'], $dest);
            $pdo->prepare("INSERT INTO settings (setting_key,setting_value) VALUES ('hero_image',?) ON DUPLICATE KEY UPDATE setting_value=?")
                ->execute(['hero-bg.'.$ext, 'hero-bg.'.$ext]);
            $success = 'Foto latar beranda berhasil diupload';
        } else { $error = 'Format tidak didukung. Gunakan JPG/PNG/WebP'; }
    } else { $error = 'Pilih file gambar'; }
}

// Edit banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_banner') {
    $bid = intval($_POST['banner_id']);
    $link = trim($_POST['banner_link'] ?? '') ?: '#';
    try {
        if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                // Hapus gambar lama
                $old = $pdo->prepare("SELECT image FROM banners WHERE id=?");
                $old->execute([$bid]);
                $oldImg = $old->fetchColumn();
                if ($oldImg && file_exists($bannerDir . $oldImg)) unlink($bannerDir . $oldImg);
                $filename = 'banner_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['banner_image']['tmp_name'], $bannerDir . $filename);
                $pdo->prepare("UPDATE banners SET image=?, link=? WHERE id=?")->execute([$filename, $link, $bid]);
            }
        } else {
            $pdo->prepare("UPDATE banners SET link=? WHERE id=?")->execute([$link, $bid]);
        }
        $success = 'Banner berhasil diupdate';
    } catch (Exception $e) {
        $error = 'Gagal update: ' . $e->getMessage();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_banner') {
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['banner_image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            try {
                $filename = 'banner_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['banner_image']['tmp_name'], $bannerDir . $filename);
                $maxOrder = $pdo->query("SELECT COALESCE(MAX(sort_order),0)+1 FROM banners")->fetchColumn();
                $pdo->prepare("INSERT INTO banners (title, link, image, sort_order) VALUES (?,?,?,?)")
                    ->execute([trim($_POST['banner_title'] ?? ''), trim($_POST['banner_link'] ?? '') ?: '#', $filename, $maxOrder]);
                $success = 'Banner berhasil ditambahkan';
            } catch (Exception $e) {
                $error = 'Gagal menyimpan banner. Pastikan tabel banners sudah dibuat. Error: ' . $e->getMessage();
            }
        } else { $error = 'Format tidak didukung. Gunakan JPG/PNG/WebP'; }
    } else { $error = 'Gambar wajib diupload'; }
}

// Hapus banner slide
if (isset($_GET['delete_banner'])) {
    $row = $pdo->prepare("SELECT image FROM banners WHERE id=?");
    $row->execute([intval($_GET['delete_banner'])]);
    $img = $row->fetchColumn();
    if ($img && file_exists($bannerDir . $img)) unlink($bannerDir . $img);
    $pdo->prepare("DELETE FROM banners WHERE id=?")->execute([intval($_GET['delete_banner'])]);
    header('Location: settings.php?tab=banner&success=1'); exit;
}

// Toggle aktif banner
if (isset($_GET['toggle_banner'])) {
    $pdo->prepare("UPDATE banners SET is_active = NOT is_active WHERE id=?")->execute([intval($_GET['toggle_banner'])]);
    header('Location: settings.php?tab=banner'); exit;
}

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get banners
$banners = [];
try { $banners = $pdo->query("SELECT * FROM banners ORDER BY sort_order ASC")->fetchAll(); } catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan - Admin D'florist</title>
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
                    <h1 class="h2">Pengaturan Sistem</h1>
                    <a href="outlets.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-store me-1"></i> Kelola Outlet
                    </a>
                </div>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <div class="row g-4">
                    <!-- Pengaturan Pengiriman -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Pengaturan Pengiriman</h5>
                                <form method="POST">
                                    <div class="mb-3">
                                        <label class="form-label">Radius Maksimal Kurir Toko (km)</label>
                                        <input type="number" name="max_delivery_radius" class="form-control" 
                                               value="<?php echo $settings['max_delivery_radius'] ?? 10; ?>" 
                                               min="1" max="50" required>
                                        <small class="text-muted">Jarak maksimal untuk kurir toko</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Minimal Pre-Order (Hari)</label>
                                        <input type="number" name="min_preorder_days" class="form-control" 
                                               value="<?php echo $settings['min_preorder_days'] ?? 1; ?>" 
                                               min="1" max="7" required>
                                        <small class="text-muted">Minimal hari sebelum pengiriman (H+)</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kuota Maksimal Per Tanggal</label>
                                        <input type="number" name="max_quota_per_date" class="form-control" 
                                               value="<?php echo $settings['max_quota_per_date'] ?? 10; ?>" 
                                               min="1" max="100" required>
                                        <small class="text-muted">Maksimal pesanan per tanggal per metode</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Biaya Kurir Toko (Flat)</label>
                                        <input type="number" name="kurir_toko_cost" class="form-control" 
                                               value="<?php echo $settings['kurir_toko_cost'] ?? 10000; ?>" 
                                               min="0" step="1000" required>
                                    </div>
                                    <!-- hidden fields agar tidak error saat save -->
                                    <input type="hidden" name="store_latitude" value="<?php echo $settings['store_latitude'] ?? 0; ?>">
                                    <input type="hidden" name="store_longitude" value="<?php echo $settings['store_longitude'] ?? 0; ?>">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save me-1"></i> Simpan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Integrasi API -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Integrasi API</h5>
                                <form method="POST">
                                    <input type="hidden" name="store_latitude" value="<?php echo $settings['store_latitude'] ?? 0; ?>">
                                    <input type="hidden" name="store_longitude" value="<?php echo $settings['store_longitude'] ?? 0; ?>">
                                    <input type="hidden" name="max_delivery_radius" value="<?php echo $settings['max_delivery_radius'] ?? 10; ?>">
                                    <input type="hidden" name="min_preorder_days" value="<?php echo $settings['min_preorder_days'] ?? 1; ?>">
                                    <input type="hidden" name="max_quota_per_date" value="<?php echo $settings['max_quota_per_date'] ?? 10; ?>">
                                    <input type="hidden" name="kurir_toko_cost" value="<?php echo $settings['kurir_toko_cost'] ?? 10000; ?>">

                                    <p class="fw-semibold text-muted small mb-2">RajaOngkir</p>
                                    <div class="mb-3">
                                        <label class="form-label">API Key RajaOngkir</label>
                                        <input type="text" name="rajaongkir_api_key" class="form-control"
                                               value="<?php echo htmlspecialchars($settings['rajaongkir_api_key'] ?? ''); ?>"
                                               placeholder="Masukkan API key RajaOngkir">
                                        <small class="text-muted">Daftar di <a href="https://rajaongkir.com" target="_blank">rajaongkir.com</a>. Kota asal diambil otomatis dari outlet aktif.</small>
                                    </div>

                                    <hr class="my-3">
                                    <p class="fw-semibold text-muted small mb-2">Midtrans Payment Gateway</p>
                                    <div class="mb-3">
                                        <label class="form-label">Server Key</label>
                                        <input type="text" name="midtrans_server_key" class="form-control"
                                               value="<?php echo htmlspecialchars($settings['midtrans_server_key'] ?? ($_ENV['MIDTRANS_SERVER_KEY'] ?? '')); ?>"
                                               placeholder="Mid-server-xxxx">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Client Key</label>
                                        <input type="text" name="midtrans_client_key" class="form-control"
                                               value="<?php echo htmlspecialchars($settings['midtrans_client_key'] ?? ($_ENV['MIDTRANS_CLIENT_KEY'] ?? '')); ?>"
                                               placeholder="Mid-client-xxxx">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="midtrans_is_production" id="midtransProd"
                                                   <?php echo (($settings['midtrans_is_production'] ?? 'false') === 'true') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="midtransProd">
                                                Mode Production
                                                <small class="text-muted d-block">Matikan untuk mode Sandbox (testing)</small>
                                            </label>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary btn-sm">
                                        <i class="fas fa-save me-1"></i> Simpan
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Slot Waktu -->
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Slot Waktu Pengiriman</h5>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTimeslotModal">
                                        <i class="fas fa-plus"></i> Tambah Slot
                                    </button>
                                </div>
                                <?php
                                $stmt = $pdo->query("SELECT * FROM delivery_timeslots ORDER BY sort_order ASC");
                                $timeslots = $stmt->fetchAll();
                                ?>
                                <?php if (empty($timeslots)): ?>
                                <div class="alert alert-info mb-0">Belum ada slot waktu.</div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Slot Waktu</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($timeslots as $slot): ?>
                                            <tr>
                                                <td><?php echo $no++; ?></td>
                                                <td><?php echo htmlspecialchars($slot['time_slot']); ?></td>
                                                <td>
                                                    <?php if ($slot['is_active']): ?>
                                                    <span class="badge bg-success">Aktif</span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">Nonaktif</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <form method="POST" class="d-inline">
                                                        <input type="hidden" name="action" value="toggle_timeslot">
                                                        <input type="hidden" name="id" value="<?php echo $slot['id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning" title="Toggle">
                                                            <i class="fas fa-toggle-on"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus slot ini?')">
                                                        <input type="hidden" name="action" value="delete_timeslot">
                                                        <input type="hidden" name="id" value="<?php echo $slot['id']; ?>">
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
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hero Background -->
                <?php $heroImage = getSetting($pdo, 'hero_image') ?: ''; ?>
                <div class="card border-0 shadow-sm mt-4 mb-2">
                    <div class="card-header bg-white fw-bold">
                        <i class="fas fa-image me-2" style="color:#FF69B4;"></i> Foto Latar Beranda
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                            <input type="hidden" name="action" value="upload_hero">
                            <div class="col-md-6">
                                <label class="form-label">Upload Foto Bunga / Latar</label>
                                <input type="file" name="hero_image" class="form-control" accept="image/*,.avif" required>
                                <small class="text-muted">JPG/PNG/WebP/AVIF. Rekomendasi: 1400×500px landscape.</small>
                            </div>
                            <div class="col-md-4">
                                <?php if ($heroImage): ?>
                                <img src="<?php echo SITE_URL; ?>/assets/images/<?php echo htmlspecialchars($heroImage); ?>"
                                     style="max-height:70px;border-radius:8px;max-width:100%;" alt="Hero saat ini">
                                <div class="small text-muted mt-1">Foto saat ini</div>
                                <?php else: ?>
                                <div class="text-muted small">Belum ada foto latar</div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Upload</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Banner Beranda -->
                <div class="card border-0 shadow-sm mt-4 mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold"><i class="fas fa-images me-2" style="color:#FF69B4;"></i> Banner Beranda</span>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBannerModal">
                            <i class="fas fa-plus me-1"></i> Tambah Banner
                        </button>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($banners)): ?>
                        <p class="text-muted text-center py-4">Belum ada banner. Klik Tambah Banner untuk mulai.</p>
                        <?php else: ?>
                        <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Gambar</th>
                                    <th>Link</th>
                                    <th>Status</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($banners as $b): ?>
                            <tr>
                                <td class="ps-3">
                                    <img src="<?php echo SITE_URL; ?>/assets/images/banners/<?php echo htmlspecialchars($b['image']); ?>"
                                         style="width:120px;height:55px;object-fit:cover;border-radius:6px;" alt="">
                                </td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($b['link'] ?: '-'); ?></small></td>
                                <td>
                                    <a href="?toggle_banner=<?php echo $b['id']; ?>" class="text-decoration-none">
                                        <span class="badge <?php echo $b['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $b['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-warning me-1"
                                        data-bs-toggle="modal" data-bs-target="#editBanner<?php echo $b['id']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="?delete_banner=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Hapus banner ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Modal Edit Banner -->
    <?php foreach ($banners as $b): ?>
    <div class="modal fade" id="editBanner<?php echo $b['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="action" value="edit_banner">
                <input type="hidden" name="banner_id" value="<?php echo $b['id']; ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3 text-center">
                        <img src="<?php echo SITE_URL; ?>/assets/images/banners/<?php echo htmlspecialchars($b['image']); ?>"
                             style="max-height:100px;border-radius:8px;max-width:100%;" alt="">
                        <div class="small text-muted mt-1">Gambar saat ini</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ganti Gambar (kosongkan jika tidak ingin mengganti)</label>
                        <input type="file" name="banner_image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link</label>
                        <input type="text" name="banner_link" class="form-control"
                               value="<?php echo htmlspecialchars($b['link'] !== '#' ? $b['link'] : ''); ?>"
                               placeholder="Contoh: products.php">
                        <small class="text-muted">Kosongkan jika tidak perlu link</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Modal Tambah Banner -->
    <div class="modal fade" id="addBannerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="action" value="add_banner">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Banner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Gambar Banner <span class="text-danger">*</span></label>
                        <input type="file" name="banner_image" class="form-control" accept="image/*" required>
                        <small class="text-muted">Rekomendasi: 1200×400px. JPG/PNG/WebP.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link (opsional)</label>
                        <input type="text" name="banner_link" class="form-control" placeholder="Contoh: products.php">
                        <small class="text-muted">Biarkan kosong jika tidak perlu link</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Upload Banner</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Tambah Slot -->
    <div class="modal fade" id="addTimeslotModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content" style="background-color:#FFD6E8;">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Slot Waktu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_timeslot">
                    <div class="modal-body">
                        <label class="form-label">Slot Waktu</label>
                        <input type="text" name="time_slot" class="form-control" 
                               placeholder="Contoh: 09.00 - 12.00 WIB" required>
                        <small class="text-muted">Format: HH.MM - HH.MM WIB</small>
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
    </script>
</body>
</html>