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

// Get current settings
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
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

                    <!-- RajaOngkir -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title mb-4">Integrasi RajaOngkir</h5>
                                <form method="POST">
                                    <!-- hidden fields -->
                                    <input type="hidden" name="store_latitude" value="<?php echo $settings['store_latitude'] ?? 0; ?>">
                                    <input type="hidden" name="store_longitude" value="<?php echo $settings['store_longitude'] ?? 0; ?>">
                                    <input type="hidden" name="max_delivery_radius" value="<?php echo $settings['max_delivery_radius'] ?? 10; ?>">
                                    <input type="hidden" name="min_preorder_days" value="<?php echo $settings['min_preorder_days'] ?? 1; ?>">
                                    <input type="hidden" name="max_quota_per_date" value="<?php echo $settings['max_quota_per_date'] ?? 10; ?>">
                                    <input type="hidden" name="kurir_toko_cost" value="<?php echo $settings['kurir_toko_cost'] ?? 10000; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">API Key RajaOngkir</label>
                                        <input type="text" name="rajaongkir_api_key" class="form-control" 
                                               value="<?php echo htmlspecialchars($settings['rajaongkir_api_key'] ?? ''); ?>"
                                               placeholder="Masukkan API key">
                                        <small class="text-muted">Daftar di <a href="https://rajaongkir.com" target="_blank">rajaongkir.com</a></small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kota Asal Ekspedisi</label>
                                        <input type="text" id="originCitySearch" class="form-control mb-1"
                                               placeholder="Cari kota asal..."
                                               value="<?php echo htmlspecialchars($settings['origin_city_name'] ?? ''); ?>"
                                               autocomplete="off">
                                        <div id="originCitySuggestions" class="list-group" style="position:absolute;z-index:999;width:100%;max-height:200px;overflow-y:auto;display:none;"></div>
                                        <input type="hidden" name="origin_city_id" id="originCityId" value="<?php echo htmlspecialchars($settings['origin_city_id'] ?? ''); ?>">
                                        <input type="hidden" name="origin_city_name" id="originCityName" value="<?php echo htmlspecialchars($settings['origin_city_name'] ?? ''); ?>">
                                        <small class="text-muted">Kota asal untuk kalkulasi ongkir ekspedisi</small>
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
            </main>
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
    const originInput = document.getElementById('originCitySearch');
    const originSuggestions = document.getElementById('originCitySuggestions');
    const originCityId = document.getElementById('originCityId');
    const originCityName = document.getElementById('originCityName');
    let originTimer;

    if (originInput) {
        originInput.addEventListener('input', function() {
            clearTimeout(originTimer);
            const q = this.value.trim();
            if (q.length < 2) { originSuggestions.style.display = 'none'; return; }
            originTimer = setTimeout(() => {
                fetch('<?php echo SITE_URL; ?>/api/rajaongkir.php?action=search_city&q=' + encodeURIComponent(q))
                    .then(r => r.json())
                    .then(data => {
                        if (!data.success || !data.data.length) { originSuggestions.style.display = 'none'; return; }
                        originSuggestions.innerHTML = data.data.map(c =>
                            `<a href="#" class="list-group-item list-group-item-action py-1 px-2 small" 
                                data-id="${c.city_id}" data-name="${c.city_name}">${c.city_name}</a>`
                        ).join('');
                        originSuggestions.style.display = 'block';
                        originSuggestions.querySelectorAll('a').forEach(a => {
                            a.addEventListener('click', function(e) {
                                e.preventDefault();
                                originInput.value = this.dataset.name;
                                originCityId.value = this.dataset.id;
                                originCityName.value = this.dataset.name;
                                originSuggestions.style.display = 'none';
                            });
                        });
                    });
            }, 400);
        });
        document.addEventListener('click', e => {
            if (!originInput.contains(e.target)) originSuggestions.style.display = 'none';
        });
    }
    </script>
</body>
</html>