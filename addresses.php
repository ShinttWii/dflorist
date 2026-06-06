<?php
$pageTitle = 'Alamat Saya - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';
$editAddr = null;
$showForm = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $id           = $_POST['id'] ?? 0;
        $label        = sanitize($_POST['label']);
        $recipientName  = sanitize($_POST['recipient_name']);
        $recipientPhone = sanitize($_POST['recipient_phone']);
        $province     = sanitize($_POST['province_name'] ?? '');
        $cityId       = intval($_POST['city_id'] ?? 0);
        $cityName     = sanitize($_POST['city_name'] ?? '');
        $district     = sanitize($_POST['district'] ?? '');   // kecamatan
        $postalCode   = sanitize($_POST['postal_code'] ?? '');
        $streetDetail = sanitize($_POST['street_detail'] ?? '');
        $notes        = sanitize($_POST['notes'] ?? '');
        $isPrimary    = isset($_POST['is_primary']) ? 1 : 0;

        // Build full address string — hindari duplikasi
        $parts = array_filter([$streetDetail, $district, $cityName, $province, $postalCode]);
        $fullAddress = implode(', ', $parts);

        if (!$streetDetail) {
            $error = 'Alamat lengkap wajib diisi';
            $showForm = true;
        } else {
            // Auto-geocode via Nominatim (best-effort, fallback to 0,0)
            $lat = 0; $lng = 0;
            $geoQuery = urlencode($fullAddress . ', Indonesia');
            $geoUrl = "https://nominatim.openstreetmap.org/search?format=json&q={$geoQuery}&countrycodes=id&limit=1";
            $ctx = stream_context_create(['http' => ['timeout' => 5, 'header' => "User-Agent: DFlorist/1.0\r\n"]]);
            $geoResult = @file_get_contents($geoUrl, false, $ctx);
            if ($geoResult) {
                $geoData = json_decode($geoResult, true);
                if (!empty($geoData[0])) {
                    $lat = $geoData[0]['lat'];
                    $lng = $geoData[0]['lon'];
                }
            }

            if ($isPrimary) {
                $pdo->prepare("UPDATE addresses SET is_primary = 0 WHERE user_id = ?")->execute([$_SESSION['customer_id']]);
            }

            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO addresses (user_id, label, recipient_name, recipient_phone, address, notes, latitude, longitude, city_id, city_name, province_id, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['customer_id'], $label, $recipientName, $recipientPhone, $fullAddress, $notes, $lat, $lng, $cityId, $cityName, 0, $isPrimary]);
                header('Location: addresses.php?success=added');
                exit;
            } else {
                $stmt = $pdo->prepare("UPDATE addresses SET label=?, recipient_name=?, recipient_phone=?, address=?, notes=?, latitude=?, longitude=?, city_id=?, city_name=?, is_primary=? WHERE id=? AND user_id=?");
                $stmt->execute([$label, $recipientName, $recipientPhone, $fullAddress, $notes, $lat, $lng, $cityId, $cityName, $isPrimary, $id, $_SESSION['customer_id']]);
                $success = 'Alamat berhasil diupdate';
            }
            $showForm = false;
        }

    } elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        $pdo->prepare("UPDATE orders SET address_id = NULL WHERE address_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?")->execute([$id, $_SESSION['customer_id']]);
        $success = 'Alamat berhasil dihapus';

    } elseif ($action === 'set_primary') {
        $id = intval($_POST['id']);
        $pdo->prepare("UPDATE addresses SET is_primary = 0 WHERE user_id = ?")->execute([$_SESSION['customer_id']]);
        $pdo->prepare("UPDATE addresses SET is_primary = 1 WHERE id = ? AND user_id = ?")->execute([$id, $_SESSION['customer_id']]);
        $success = 'Alamat utama berhasil diubah';
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'add') {
    $showForm = true;
}
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $_SESSION['customer_id']]);
    $editAddr = $stmt->fetch();
    if ($editAddr) $showForm = true;
}

$stmt = $pdo->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_primary DESC, created_at DESC");
$stmt->execute([$_SESSION['customer_id']]);
$addresses = $stmt->fetchAll();
?>

<div class="container my-5">

<?php if ($showForm): ?>

    <!-- FORM -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="addresses.php" class="text-dark"><i class="fas fa-arrow-left fa-lg"></i></a>
        <h2 class="mb-0 fw-bold"><?= $editAddr ? 'Ubah Alamat' : 'Tambah Alamat Baru' ?></h2>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" id="addrForm">
        <input type="hidden" name="action" value="<?= $editAddr ? 'edit' : 'add' ?>">
        <input type="hidden" name="id"     value="<?= $editAddr['id'] ?? '' ?>">
        <input type="hidden" name="latitude"  id="latitude"  value="<?= $editAddr['latitude']  ?? 0 ?>">
        <input type="hidden" name="longitude" id="longitude" value="<?= $editAddr['longitude'] ?? 0 ?>">

        <!-- Penerima -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Info Penerima</h6>
                <div class="mb-3">
                    <label class="form-label">Nama Penerima <span class="text-danger">*</span></label>
                    <input type="text" name="recipient_name" class="form-control"
                           value="<?= htmlspecialchars($editAddr['recipient_name'] ?? '') ?>" required
                           placeholder="Nama lengkap penerima">
                </div>
                <div class="mb-0">
                    <label class="form-label">Nomor HP <span class="text-danger">*</span></label>
                    <input type="tel" name="recipient_phone" class="form-control"
                           value="<?= htmlspecialchars($editAddr['recipient_phone'] ?? '') ?>" required
                           placeholder="08xxxxxxxxxx">
                </div>
            </div>
        </div>

        <!-- Alamat -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body">
                <h6 class="fw-bold mb-3">Detail Alamat</h6>

                <!-- Paste & Parse -->
                <div class="mb-3 p-3 rounded-3" style="background:#f8f9fa;border:1.5px dashed #ccc;">
                    <label class="form-label fw-semibold small">Paste Alamat Lengkap (Opsional)</label>
                    <textarea id="pasteFull" class="form-control mb-2" rows="3"
                              placeholder="Contoh: Jl. Raya Jatiwaringin No.10, Kel. Jati Cempaka, Kec. Pondok Gede, Kota Bekasi, Jawa Barat 17411"></textarea>
                    <button type="button" class="btn btn-sm btn-primary w-100" onclick="parseAddress()">
                        <i class="fas fa-magic me-1"></i> Isi Otomatis dari Teks di Atas
                    </button>
                </div>

                <div class="mb-3">
                    <label class="form-label">Label Alamat <span class="text-danger">*</span></label>
                    <div class="d-flex gap-2 flex-wrap mb-2">
                        <?php foreach (['Rumah','Kantor','Kos','Lainnya'] as $lbl): ?>
                        <button type="button" class="btn btn-sm lbl-chip"
                                data-val="<?= $lbl ?>"
                                style="border-radius:20px;border:1.5px solid #ddd;font-size:12px;">
                            <?= $lbl ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" name="label" id="labelInput" class="form-control"
                           value="<?= htmlspecialchars($editAddr['label'] ?? '') ?>"
                           placeholder="Rumah / Kantor / dll" required>
                </div>

                <!-- Kota / Kabupaten search -->
                <div class="mb-3">
                    <label class="form-label">Kota / Kabupaten <span class="text-danger">*</span></label>
                    <input type="hidden" name="city_id"   id="city_id"   value="<?= $editAddr['city_id'] ?? '' ?>">
                    <input type="hidden" name="city_name" id="city_name" value="<?= htmlspecialchars($editAddr['city_name'] ?? '') ?>">
                    <input type="hidden" name="province_name" id="province_name" value="">
                    <div style="position:relative;">
                        <input type="text" id="citySearchInput" class="form-control"
                               placeholder="Ketik nama kota/kabupaten..."
                               autocomplete="off"
                               value="<?= htmlspecialchars($editAddr['city_name'] ?? '') ?>">
                        <div id="citySuggestions" class="list-group shadow"
                             style="display:none;position:absolute;top:100%;left:0;right:0;z-index:9999;max-height:220px;overflow-y:auto;"></div>
                    </div>
                    <?php if (!empty($editAddr['city_name'])): ?>
                    <small class="text-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($editAddr['city_name']) ?></small>
                    <?php endif; ?>
                </div>

                <!-- Kecamatan -->
                <div class="mb-3">
                    <label class="form-label">Kecamatan</label>
                    <input type="text" name="district" class="form-control"
                           value="<?= htmlspecialchars($editAddr['district'] ?? '') ?>"
                           placeholder="Nama kecamatan">
                </div>

                <!-- Kode Pos -->
                <div class="mb-3">
                    <label class="form-label">Kode Pos</label>
                    <input type="text" name="postal_code" class="form-control" maxlength="10"
                           value="<?= htmlspecialchars($editAddr['postal_code'] ?? '') ?>"
                           placeholder="Contoh: 16111">
                </div>

                <!-- Detail Jalan -->
                <div class="mb-3">
                    <label class="form-label">Alamat Lengkap (Nama Jalan, No. Rumah, RT/RW) <span class="text-danger">*</span></label>
                    <textarea name="street_detail" class="form-control" rows="3" required
                              placeholder="Contoh: Jl. Mawar No. 5, RT 02/RW 03"><?= htmlspecialchars($editAddr['street_detail'] ?? (isset($editAddr['address']) ? $editAddr['address'] : '')) ?></textarea>
                </div>

                <!-- Catatan -->
                <div class="mb-0">
                    <label class="form-label">Catatan untuk Kurir (Opsional)</label>
                    <input type="text" name="notes" class="form-control"
                           value="<?= htmlspecialchars($editAddr['notes'] ?? '') ?>"
                           placeholder="Contoh: Pagar biru, depan warung">
                </div>
            </div>
        </div>

        <!-- Peta Lokasi -->
        <div class="card border-0 shadow-sm rounded-3 mb-3">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-1">Titik Lokasi <span class="text-muted fw-normal" style="font-size:12px;">(untuk hitung jarak pengiriman)</span></h6>

                <!-- GPS Button — cara utama -->
                <button type="button" id="btnGPS" class="btn btn-success w-100 mb-2" onclick="useGPS()">
                    <i class="fas fa-crosshairs me-2"></i> Gunakan Lokasi GPS Saya
                </button>
                <div id="gpsStatus" class="small mb-2" style="display:none;"></div>

                <!-- Search box — backup -->
                <div style="position:relative;" class="mb-2">
                    <input type="text" id="mapSearch" class="form-control form-control-sm"
                           placeholder="Atau ketik nama jalan / tempat untuk cari di peta..."
                           autocomplete="off">
                    <div id="mapSuggestions" class="list-group shadow"
                         style="display:none;position:absolute;top:100%;left:0;right:0;z-index:9999;max-height:200px;overflow-y:auto;"></div>
                </div>

                <!-- Peta -->
                <div id="mapWarning" class="alert alert-warning py-2 mb-2" style="font-size:12px; display:none;">
                    <i class="fas fa-exclamation-triangle me-1"></i> <strong>Wajib:</strong> Klik peta atau gunakan GPS untuk menentukan lokasi. Tanpa koordinat, metode pengiriman kurir toko tidak akan tersedia.
                </div>
                <div id="mapSuccess" class="alert alert-success py-2 mb-2" style="font-size:12px; display:none;">
                    <i class="fas fa-check-circle me-1"></i> Lokasi berhasil ditentukan.
                </div>
                <div id="map" style="height:260px;border-radius:10px;"></div>
                <small class="text-muted d-block mt-1" style="font-size:11px;">
                    <i class="fas fa-info-circle"></i> Klik peta, drag pin, atau gunakan tombol GPS untuk menentukan lokasi
                </small>
            </div>
        </div>

        <!-- Pengaturan -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="is_primary" id="isPrimary" role="switch"
                           <?= ($editAddr['is_primary'] ?? 0) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="isPrimary">
                        <strong>Jadikan Alamat Utama</strong>
                        <div><small class="text-muted">Otomatis dipilih saat checkout</small></div>
                    </label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold" style="border-radius:10px;" onclick="return validateCoords();">
            Simpan Alamat
        </button>
    </form>

<?php else: ?>

    <!-- LIST -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h2 class="mb-0 fw-bold">Alamat Saya</h2>
        <a href="addresses.php?action=add" class="btn btn-primary btn-sm px-3" style="border-radius:20px;">
            <i class="fas fa-plus"></i> Tambah
        </a>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
    <div class="alert alert-success">Alamat berhasil ditambahkan</div>
    <?php endif; ?>

    <?php if (empty($addresses)): ?>
    <div class="text-center py-5">
        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3 d-block"></i>
        <p class="text-muted">Belum ada alamat tersimpan</p>
        <a href="addresses.php?action=add" class="btn btn-primary">Tambah Alamat Pertama</a>
    </div>
    <?php else: ?>
    <div class="d-flex flex-column gap-3">
        <?php foreach ($addresses as $addr): ?>
        <div class="card border-0 shadow-sm rounded-3" style="<?= $addr['is_primary'] ? 'border-left:4px solid #FF69B4!important;' : '' ?>">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div style="flex:1;">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge rounded-pill" style="background:#FFD6E8;color:#c2185b;font-size:11px;">
                                <?= htmlspecialchars($addr['label']) ?>
                            </span>
                            <?php if ($addr['is_primary']): ?>
                            <span class="badge rounded-pill" style="background:#FF69B4;color:#fff;font-size:11px;">Utama</span>
                            <?php endif; ?>
                        </div>
                        <p class="mb-0 fw-semibold"><?= htmlspecialchars($addr['recipient_name']) ?></p>
                        <p class="mb-1 text-muted small"><?= htmlspecialchars($addr['recipient_phone']) ?></p>
                        <p class="mb-0 small" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                            <?= htmlspecialchars($addr['address']) ?>
                        </p>
                        <?php if ($addr['notes']): ?>
                        <p class="mb-0 text-muted small"><i class="fas fa-sticky-note"></i> <?= htmlspecialchars($addr['notes']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="ms-2">
                        <button class="btn btn-light btn-sm rounded-circle addr-dropdown-btn" style="width:32px;height:32px;padding:0;">
                            <i class="fas fa-ellipsis-v" style="font-size:12px;"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm addr-dropdown-menu" style="font-size:13px;">
                            <li>
                                <a class="dropdown-item" href="addresses.php?action=edit&id=<?= $addr['id'] ?>">
                                    <i class="fas fa-edit text-primary me-2"></i> Ubah
                                </a>
                            </li>
                            <?php if (!$addr['is_primary']): ?>
                            <li>
                                <form method="POST">
                                    <input type="hidden" name="action" value="set_primary">
                                    <input type="hidden" name="id" value="<?= $addr['id'] ?>">
                                    <button type="submit" class="dropdown-item">
                                        <i class="fas fa-star text-warning me-2"></i> Jadikan Utama
                                    </button>
                                </form>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form method="POST" onsubmit="return confirm('Hapus alamat ini?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $addr['id'] ?>">
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-trash me-2"></i> Hapus
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

<?php endif; ?>
</div>

<?php if ($showForm): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const siteUrl = <?= json_encode(SITE_URL) ?>;
const editCityId   = <?= json_encode($editAddr['city_id']   ?? '') ?>;
const editCityName = <?= json_encode($editAddr['city_name'] ?? '') ?>;
const initLat = <?= floatval($editAddr['latitude']  ?? 0) ?>;
const initLng = <?= floatval($editAddr['longitude'] ?? 0) ?>;

// ── MAP ──────────────────────────────────────────────
let map, marker;
window.addEventListener('DOMContentLoaded', () => {
    const startLat = (initLat != 0) ? initLat : -6.2;
    const startLng = (initLng != 0) ? initLng : 106.816;
    map = L.map('map').setView([startLat, startLng], initLat != 0 ? 17 : 12);

    // Tampilkan status koordinat awal
    if (initLat != 0 && initLng != 0) {
        document.getElementById('mapSuccess').style.display = 'block';
    } else {
        document.getElementById('mapWarning').style.display = 'block';
        // Auto-geocode dari alamat yang sudah diisi
        const alamat = <?php echo json_encode(($editAddr['address'] ?? '') . ', Indonesia'); ?>;
        if (alamat.length > 5) {
            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(alamat) + '&countrycodes=id&limit=1', {
                headers: {'User-Agent': 'DFlorist/1.0'}
            }).then(r => r.json()).then(data => {
                if (data && data[0]) {
                    const lat = parseFloat(data[0].lat);
                    const lng = parseFloat(data[0].lon);
                    map.setView([lat, lng], 16);
                    marker.setLatLng([lat, lng]);
                    // Jangan panggil setCoords — hanya geser pin, biarkan user konfirmasi
                    document.getElementById('latitude').value  = lat;
                    document.getElementById('longitude').value = lng;
                }
            }).catch(() => {});
        }
    }
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 19
    }).addTo(map);
    marker = L.marker([startLat, startLng], { draggable: true }).addTo(map);
    if (initLat != 0) setGPSStatus('success', '<i class="fas fa-check-circle text-success"></i> Lokasi tersimpan');

    marker.on('dragend', () => { const p = marker.getLatLng(); setCoords(p.lat, p.lng); reverseGeocode(p.lat, p.lng); });
    map.on('click', e => { marker.setLatLng(e.latlng); setCoords(e.latlng.lat, e.latlng.lng); reverseGeocode(e.latlng.lat, e.latlng.lng); });

    // Search box
    document.getElementById('mapSearch').addEventListener('input', function() {
        clearTimeout(window._mapTimer);
        const q = this.value.trim();
        if (q.length < 3) { document.getElementById('mapSuggestions').style.display='none'; return; }
        window._mapTimer = setTimeout(() => {
            fetch('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q+' Indonesia') + '&countrycodes=id&limit=5')
                .then(r=>r.json()).then(data => {
                    if (!data.length) return;
                    const box = document.getElementById('mapSuggestions');
                    box.innerHTML = data.map((r,i) =>
                        `<button type="button" class="list-group-item list-group-item-action py-2 px-3" style="font-size:12px;text-align:left;" onclick="pickMapResult(${i})">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>${r.display_name}</button>`
                    ).join('');
                    box._r = data; box.style.display='block';
                }).catch(()=>{});
        }, 400);
    });
    document.addEventListener('click', e => {
        if (!e.target.closest('#mapSearch') && !e.target.closest('#mapSuggestions'))
            document.getElementById('mapSuggestions').style.display='none';
    });
});

function setCoords(lat, lng) {
    document.getElementById('latitude').value  = lat;
    document.getElementById('longitude').value = lng;
    document.getElementById('mapWarning').style.display = 'none';
    document.getElementById('mapSuccess').style.display = 'block';
}

function useGPS() {
    const btn = document.getElementById('btnGPS');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Mendeteksi...';
    if (!navigator.geolocation) {
        btn.disabled=false; btn.innerHTML='<i class="fas fa-crosshairs me-2"></i> Gunakan Lokasi GPS Saya';
        setGPSStatus('danger','GPS tidak didukung browser ini'); return;
    }
    navigator.geolocation.getCurrentPosition(pos => {
        const lat=pos.coords.latitude, lng=pos.coords.longitude;
        map.setView([lat,lng],18); marker.setLatLng([lat,lng]); setCoords(lat,lng);
        btn.disabled=false; btn.innerHTML='<i class="fas fa-check me-2"></i> GPS Terdeteksi';
        btn.className='btn btn-outline-success w-100 mb-2';
        setGPSStatus('success','<i class="fas fa-check-circle text-success"></i> Lokasi berhasil dideteksi. Drag pin untuk sesuaikan jika perlu.');
        reverseGeocode(lat,lng);
    }, err => {
        btn.disabled=false; btn.innerHTML='<i class="fas fa-crosshairs me-2"></i> Gunakan Lokasi GPS Saya';
        setGPSStatus('warning', err.code===1 ? 'Izin lokasi ditolak. Aktifkan izin lokasi di browser lalu coba lagi.' : 'GPS gagal. Cari manual di kolom pencarian.');
    }, {enableHighAccuracy:true, timeout:10000});
}

function setGPSStatus(type, msg) {
    const el = document.getElementById('gpsStatus');
    el.className = 'small mb-2';
    el.style.color = type==='success'?'#198754':type==='danger'?'#dc3545':'#856404';
    el.innerHTML = msg; el.style.display='block';
}

function pickMapResult(i) {
    const r = document.getElementById('mapSuggestions')._r[i];
    const lat=parseFloat(r.lat), lng=parseFloat(r.lon);
    map.setView([lat,lng],17); marker.setLatLng([lat,lng]); setCoords(lat,lng);
    document.getElementById('mapSuggestions').style.display='none';
    document.getElementById('mapSearch').value='';
    reverseGeocode(lat,lng);
}

function validateCoords() {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);
    if (!lat || !lng || (lat === 0 && lng === 0)) {
        document.getElementById('mapWarning').style.display = 'block';
        document.getElementById('map').scrollIntoView({behavior: 'smooth', block: 'center'});
        return false;
    }
    return true;
}

function reverseGeocode(lat, lng) {
    fetch('https://nominatim.openstreetmap.org/reverse?format=json&lat='+lat+'&lon='+lng+'&addressdetails=1')
        .then(r=>r.json()).then(r => {
            if (!r.address) return;
            const a=r.address;
            const road=a.road||a.pedestrian||a.footway||'';
            const village=a.village||a.suburb||a.neighbourhood||'';
            const postcode=a.postcode||'';
            const district=a.city_district||a.county||'';
            const streetEl=document.querySelector('textarea[name="street_detail"]');
            if (streetEl && !streetEl.value && road) streetEl.value=road+(village?', '+village:'');
            const pcEl=document.querySelector('input[name="postal_code"]');
            if (pcEl && !pcEl.value && postcode) pcEl.value=postcode;
            const distEl=document.querySelector('input[name="district"]');
            if (distEl && !distEl.value && district) distEl.value=district;
        }).catch(()=>{});
}

// ── PARSE ALAMAT OTOMATIS ────────────────────────────
function parseAddress() {
    const raw = document.getElementById('pasteFull').value.trim();
    if (!raw) return;
    const postalMatch = raw.match(/\b(\d{5})\b/);
    if (postalMatch) document.querySelector('input[name="postal_code"]').value = postalMatch[1];
    const streetMatch = raw.match(/^([^,]+(?:No\.?\s*\d+[^,]*)?)/i);
    if (streetMatch) document.querySelector('textarea[name="street_detail"]').value = streetMatch[1].trim();
    const kecMatch = raw.match(/Kec(?:amatan)?\.?\s+([^,]+)/i);
    if (kecMatch) document.querySelector('input[name="district"]').value = kecMatch[1].trim();
    const kotaMatch = raw.match(/(?:Kota|Kabupaten|Kab\.?)\s+([^,\d]+)/i);
    if (kotaMatch) {
        document.getElementById('citySearchInput').value = kotaMatch[0].trim();
        fetch(siteUrl+'/api/rajaongkir.php?action=search_city&q='+encodeURIComponent(kotaMatch[1].trim()))
            .then(r=>r.json()).then(data => {
                if (data.success && data.data.length) { const c=data.data[0]; pickCity(c.city_id, c.type+' '+c.city_name, c.province||''); }
            }).catch(()=>{});
    }
}
document.querySelectorAll('.lbl-chip').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('labelInput').value = this.dataset.val;
        document.querySelectorAll('.lbl-chip').forEach(b => {
            b.style.background='#fff'; b.style.color='#333'; b.style.borderColor='#ddd';
        });
        this.style.background='#FF69B4'; this.style.color='#fff'; this.style.borderColor='#FF69B4';
    });
    if (btn.dataset.val === document.getElementById('labelInput').value) {
        btn.style.background='#FF69B4'; btn.style.color='#fff'; btn.style.borderColor='#FF69B4';
    }
});

// ── KOTA SEARCH AUTOCOMPLETE ─────────────────────────
let cityTimer;
document.getElementById('citySearchInput').addEventListener('input', function() {
    clearTimeout(cityTimer);
    const q = this.value.trim();
    // Reset hidden values when user types
    document.getElementById('city_id').value   = '';
    document.getElementById('city_name').value = '';
    document.getElementById('province_name').value = '';
    if (q.length < 2) { hideCitySuggestions(); return; }
    cityTimer = setTimeout(() => fetchCitySuggestions(q), 350);
});

function fetchCitySuggestions(q) {
    fetch(siteUrl + '/api/rajaongkir.php?action=search_city&q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            const box = document.getElementById('citySuggestions');
            if (!data.success || !data.data.length) {
                box.innerHTML = '<div class="list-group-item text-muted small">Kota tidak ditemukan</div>';
                box.style.display = 'block'; return;
            }
            box.innerHTML = data.data.slice(0, 10).map((c, i) => {
                const name = c.type + ' ' + c.city_name;
                return `<button type="button" class="list-group-item list-group-item-action py-2 px-3"
                            style="font-size:13px;text-align:left;" onclick="pickCity(${c.city_id},'${name.replace(/'/g,"\\'")}','${(c.province||'').replace(/'/g,"\\'")}')">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            <strong>${name}</strong> <span class="text-muted">— ${c.province}</span>
                        </button>`;
            }).join('');
            box._data = data.data;
            box.style.display = 'block';
        }).catch(() => {});
}

function pickCity(cityId, cityName, province) {
    document.getElementById('city_id').value       = cityId;
    document.getElementById('city_name').value     = cityName;
    document.getElementById('province_name').value = province;
    document.getElementById('citySearchInput').value = cityName;
    hideCitySuggestions();
}

function hideCitySuggestions() {
    document.getElementById('citySuggestions').style.display = 'none';
}

document.addEventListener('click', e => {
    if (!e.target.closest('#citySearchInput') && !e.target.closest('#citySuggestions'))
        hideCitySuggestions();
});

// ── VALIDASI ─────────────────────────────────────────
document.getElementById('addrForm').addEventListener('submit', function(e) {
    // Kalau user ketik kota tapi tidak pilih dari dropdown, simpan teks apa adanya
    const cityTyped = document.getElementById('citySearchInput').value.trim();
    if (cityTyped && !document.getElementById('city_id').value) {
        document.getElementById('city_name').value = cityTyped;
        // city_id tetap kosong/0, tidak masalah — ekspedisi bisa diupdate nanti
    }
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
<script>
// Teleport dropdown menu ke body supaya tidak terpotong overflow card
document.querySelectorAll('.addr-dropdown-btn').forEach(btn => {
    const menu = btn.nextElementSibling;
    document.body.appendChild(menu); // pindah ke body

    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        // Tutup semua dropdown lain
        document.querySelectorAll('.addr-dropdown-menu.show').forEach(m => {
            if (m !== menu) m.classList.remove('show');
        });

        const rect = btn.getBoundingClientRect();
        menu.style.position = 'fixed';
        menu.style.top = (rect.bottom + 4) + 'px';
        menu.style.right = (window.innerWidth - rect.right) + 'px';
        menu.style.left = 'auto';
        menu.style.zIndex = '9999';
        menu.classList.toggle('show');
    });
});

document.addEventListener('click', () => {
    document.querySelectorAll('.addr-dropdown-menu').forEach(m => m.classList.remove('show'));
});
</script>
