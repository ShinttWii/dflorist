<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Cek Login Admin
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Handle Add Outlet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_outlet'])) {
    $name = sanitize($_POST['name']);
    $address = sanitize($_POST['address']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $phone = sanitize($_POST['phone']);
    $cityId = intval($_POST['city_id'] ?? 0);
    $cityName = sanitize($_POST['city_name'] ?? '');
    
    try {
        $stmt = $pdo->prepare("INSERT INTO outlets (name, address, latitude, longitude, phone, city_id, city_name, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$name, $address, $latitude, $longitude, $phone, ($cityId > 0 ? $cityId : null), ($cityName ?: null)]);
        $success = 'Outlet baru berhasil ditambahkan dan otomatis aktif.';
    } catch (Exception $e) {
        $error = 'Gagal menambahkan outlet: ' . $e->getMessage();
    }
}

// Handle Edit Outlet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_outlet'])) {
    $id = $_POST['id'];
    $name = sanitize($_POST['name']);
    $address = sanitize($_POST['address']);
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $phone = sanitize($_POST['phone']);
    $cityId = intval($_POST['city_id'] ?? 0);
    $cityName = sanitize($_POST['city_name'] ?? '');
    
    try {
        $stmt = $pdo->prepare("UPDATE outlets SET name = ?, address = ?, latitude = ?, longitude = ?, phone = ?, city_id = ?, city_name = ? WHERE id = ?");
        $stmt->execute([$name, $address, $latitude, $longitude, $phone, ($cityId > 0 ? $cityId : null), ($cityName ?: null), $id]);
        $success = 'Data outlet berhasil diperbarui.';
    } catch (Exception $e) {
        $error = 'Gagal mengupdate outlet: ' . $e->getMessage();
    }
}

// Handle Toggle Active & Delete (Tetap sama seperti kode Anda)
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $pdo->prepare("UPDATE outlets SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    header("Location: outlets.php?success=Status diubah"); exit;
}
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $pdo->prepare("DELETE FROM outlets WHERE id = ?")->execute([$id]);
    header("Location: outlets.php?success=Outlet dihapus"); exit;
}

$outlets = $pdo->query("SELECT * FROM outlets ORDER BY is_active DESC, name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Outlet - Admin D'florist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css?v=<?php echo time(); ?>">
    <style>
        .city-results { position: absolute; z-index: 1050; width: 100%; max-height: 200px; overflow-y: auto; }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="container-fluid">
    <div class="row">
    <?php include 'includes/sidebar.php'; ?>
    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Manajemen Outlet</h4>
            <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addOutletModal">
                <i class="fas fa-plus me-1"></i> Tambah Outlet
            </button>
        </div>

        <?php if ($success || isset($_GET['success'])): ?>
            <div class="alert alert-success border-0 shadow-sm"><?php echo $success ?: htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger border-0 shadow-sm"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Nama Outlet</th>
                                <th>Alamat & Koordinat</th>
                                <th>Kota (RajaOngkir)</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($outlets as $row): ?>
                            <tr>
                                <td class="ps-4"><strong><?php echo htmlspecialchars($row['name']); ?></strong><br><small class="text-muted"><?php echo $row['phone']; ?></small></td>
                                <td>
                                    <div class="small text-truncate" style="max-width:250px;"><?php echo htmlspecialchars($row['address']); ?></div>
                                    <span class="badge bg-light text-dark fw-normal" style="font-size:.7rem;">Lat: <?php echo $row['latitude']; ?> | Lng: <?php echo $row['longitude']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($row['city_name'] ?: '-'); ?></td>
                                <td>
                                    <a href="?toggle=<?php echo $row['id']; ?>" class="text-decoration-none">
                                        <span class="badge <?php echo $row['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $row['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-warning me-1" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $row['id']; ?>"><i class="fas fa-edit"></i></button>
                                    <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus outlet ini?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>

                            <?php if (empty($outlets)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada outlet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
    </div>
    </div>

    <!-- Edit Modals (di luar tabel) -->
    <?php foreach ($outlets as $row): ?>
    <div class="modal fade" id="editModal<?php echo $row['id']; ?>" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Outlet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Nama Outlet</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($row['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($row['phone']); ?>">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Latitude</label>
                            <input type="number" name="latitude" class="form-control" step="any" value="<?php echo $row['latitude']; ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Longitude</label>
                            <input type="number" name="longitude" class="form-control" step="any" value="<?php echo $row['longitude']; ?>" required>
                        </div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label">Kota (RajaOngkir)</label>
                        <input type="hidden" name="city_id" id="city_id_<?php echo $row['id']; ?>" value="<?php echo $row['city_id']; ?>">
                        <input type="hidden" name="city_name" id="city_name_<?php echo $row['id']; ?>" value="<?php echo htmlspecialchars($row['city_name']); ?>">
                        <input type="text" class="form-control" onkeyup="searchCity(this.value, <?php echo $row['id']; ?>)" value="<?php echo htmlspecialchars($row['city_name']); ?>" placeholder="Ketik nama kota...">
                        <div id="results_<?php echo $row['id']; ?>" class="list-group city-results shadow-sm" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="address" class="form-control" rows="2" required><?php echo htmlspecialchars($row['address']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="edit_outlet" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="modal fade" id="addOutletModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Tambah Outlet Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Outlet</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: D'florist Cabang Sukabumi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Telepon</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label">Latitude</label>
                            <input type="number" name="latitude" class="form-control" step="any" placeholder="-6.9xxx" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Longitude</label>
                            <input type="number" name="longitude" class="form-control" step="any" placeholder="106.9xxx" required>
                        </div>
                    </div>
                    <div class="mb-3 position-relative">
                        <label class="form-label">Kota (RajaOngkir)</label>
                        <input type="hidden" name="city_id" id="city_id_0">
                        <input type="hidden" name="city_name" id="city_name_0">
                        <input type="text" class="form-control" id="citySearch_0" onkeyup="searchCity(this.value, 0)" placeholder="Ketik nama kota...">
                        <div id="results_0" class="list-group city-results shadow-sm" style="display:none;"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat Lengkap</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="add_outlet" class="btn btn-primary w-100">Simpan Outlet</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const siteUrl = "<?php echo SITE_URL; ?>";
    let timer;

    function searchCity(q, id) {
        clearTimeout(timer);
        const resultDiv = document.getElementById('results_' + id);
        if (q.length < 2) { resultDiv.style.display = 'none'; return; }
        timer = setTimeout(() => {
            fetch(siteUrl + '/api/rajaongkir.php?action=search_city&q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data.length > 0) {
                        let html = '';
                        res.data.forEach(city => {
                            const fullName = city.type + ' ' + city.city_name;
                            html += '<button type="button" class="list-group-item list-group-item-action py-2" onclick="selectCity(' + id + ', ' + city.city_id + ', \'' + fullName.replace(/'/g, "\\'") + '\')">'
                                  + '<small><strong>' + fullName + '</strong>, ' + city.province + '</small></button>';
                        });
                        resultDiv.innerHTML = html;
                        resultDiv.style.display = 'block';
                    } else {
                        resultDiv.style.display = 'none';
                    }
                });
        }, 400);
    }

    function selectCity(id, cityId, cityName) {
        document.getElementById('city_id_' + id).value = cityId;
        document.getElementById('city_name_' + id).value = cityName;
        const modal = id === 0 ? document.getElementById('addOutletModal') : document.getElementById('editModal' + id);
        const textInput = modal.querySelector('input[placeholder="Ketik nama kota..."]');
        if (textInput) textInput.value = cityName;
        document.getElementById('results_' + id).style.display = 'none';
    }

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.city-results') && !e.target.closest('input[placeholder="Ketik nama kota..."]')) {
            document.querySelectorAll('.city-results').forEach(el => el.style.display = 'none');
        }
    });
    </script>
</body>
</html>
