<?php
$pageTitle = 'Beri Ulasan - D\'Florist';
include 'includes/header.php';

if (!isCustomerLoggedIn()) {
    redirect('login.php');
}

$orderId = $_GET['order'] ?? 0;

$stmt = $pdo->prepare("
    SELECT o.*, oi.product_id, oi.product_name, p.image
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.id = ? AND o.user_id = ? AND o.order_status = 'selesai'
");
$stmt->execute([$orderId, $_SESSION['customer_id']]);
$orderItems = $stmt->fetchAll();

if (empty($orderItems)) {
    redirect('orders.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cek apakah POST data hilang karena post_max_size terlampaui
    if (empty($_POST) && $_SERVER['CONTENT_LENGTH'] > 0) {
        $error = 'Ukuran total file terlalu besar. Maksimal 150MB total upload.';
    } else {
    $productId = $_POST['product_id'];
    $rating    = $_POST['rating'];
    $comment   = sanitize($_POST['comment']);

    if (empty($rating) || $rating < 1 || $rating > 5) {
        $error = 'Rating harus antara 1-5';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ? AND order_id = ?");
        $stmt->execute([$_SESSION['customer_id'], $productId, $orderId]);
        if ($stmt->fetch()) {
            $error = 'Anda sudah memberikan ulasan untuk produk ini';
        } else {
            // Handle file uploads
            $mediaFiles = [];
            $allowedImages = ['jpg', 'jpeg', 'png'];
            $allowedVideos = ['mp4', 'mov', 'avi', 'webm'];
            $allowedAll    = array_merge($allowedImages, $allowedVideos);
            $uploadDir     = __DIR__ . '/assets/images/reviews/';
            $maxSize       = 50 * 1024 * 1024; // 50MB

            if (!empty($_FILES['media']['name'][0])) {
                foreach ($_FILES['media']['tmp_name'] as $i => $tmpName) {
                    if (count($mediaFiles) >= 3) break;
                    if ($_FILES['media']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    if ($_FILES['media']['size'][$i] > $maxSize) {
                        $error = 'Ukuran file maksimal 50MB';
                        break;
                    }
                    $origName = $_FILES['media']['name'][$i];
                    $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowedAll)) {
                        $error = 'Format file tidak didukung. Gunakan JPG, PNG, MP4, MOV, AVI, atau WEBM.';
                        break;
                    }
                    $newName = uniqid('review_', true) . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                        $mediaFiles[] = $newName;
                    }
                }
            }
            // upload selesai

            if (!$error) {
                $mediaJson = !empty($mediaFiles) ? json_encode($mediaFiles) : null;
                $stmt = $pdo->prepare("
                    INSERT INTO reviews (user_id, product_id, order_id, rating, comment, media_files)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                if ($stmt->execute([$_SESSION['customer_id'], $productId, $orderId, $rating, $comment, $mediaJson])) {
                    $success = 'Terima kasih! Ulasan Anda berhasil disimpan.';
                } else {
                    $error = 'Gagal menyimpan ulasan';
                }
            }
        }
    }
} // end else post_max_size check
}

$reviewedProducts = [];
$stmt = $pdo->prepare("SELECT product_id FROM reviews WHERE user_id = ? AND order_id = ?");
$stmt->execute([$_SESSION['customer_id'], $orderId]);
while ($row = $stmt->fetch()) {
    $reviewedProducts[] = $row['product_id'];
}
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h2 class="fw-bold mb-4">Beri Ulasan Produk</h2>

            <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php foreach ($orderItems as $item): ?>
            <?php if (in_array($item['product_id'], $reviewedProducts)): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <img src="<?php echo $item['image'] ? UPLOAD_URL . $item['image'] : 'https://via.placeholder.com/100'; ?>"
                                 class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        </div>
                        <div class="col-md-10">
                            <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                            <span class="badge bg-success"><i class="fas fa-check"></i> Sudah Diulas</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card mb-3">
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">

                        <div class="row mb-3">
                            <div class="col-md-2">
                                <img src="<?php echo $item['image'] ? UPLOAD_URL . $item['image'] : 'https://via.placeholder.com/100'; ?>"
                                     class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            </div>
                            <div class="col-md-10">
                                <h5><?php echo htmlspecialchars($item['product_name']); ?></h5>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating-input">
                                <input type="radio" name="rating" value="5" id="star5_<?php echo $item['product_id']; ?>" required>
                                <label for="star5_<?php echo $item['product_id']; ?>"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="4" id="star4_<?php echo $item['product_id']; ?>">
                                <label for="star4_<?php echo $item['product_id']; ?>"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="3" id="star3_<?php echo $item['product_id']; ?>">
                                <label for="star3_<?php echo $item['product_id']; ?>"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="2" id="star2_<?php echo $item['product_id']; ?>">
                                <label for="star2_<?php echo $item['product_id']; ?>"><i class="fas fa-star"></i></label>
                                <input type="radio" name="rating" value="1" id="star1_<?php echo $item['product_id']; ?>">
                                <label for="star1_<?php echo $item['product_id']; ?>"><i class="fas fa-star"></i></label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ulasan Anda</label>
                            <textarea name="comment" class="form-control" rows="4"
                                      placeholder="Ceritakan pengalaman Anda dengan produk ini..." required></textarea>
                        </div>

                        <!-- Media Upload -->
                        <div class="mb-3">
                            <label class="form-label">Foto / Video <span class="text-muted">(opsional, maks. 3 file)</span></label>
                            <div id="fileSlots_<?php echo $item['product_id']; ?>">
                                <!-- slot input file akan di-generate JS -->
                            </div>
                            <div class="upload-area mt-2" id="uploadArea_<?php echo $item['product_id']; ?>"
                                 onclick="addFileSlot('<?php echo $item['product_id']; ?>')">
                                <i class="fas fa-camera fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0" id="uploadHint_<?php echo $item['product_id']; ?>">Klik untuk pilih foto atau video</p>
                                <small class="text-muted">JPG, PNG, MP4, MOV, AVI, WEBM &bull; Maks. 50MB per file</small>
                            </div>
                            <div id="preview_<?php echo $item['product_id']; ?>" class="media-preview mt-2"></div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Kirim Ulasan
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>

            <div class="text-center mt-4">
                <a href="orders.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
}
.rating-input input[type="radio"] { display: none; }
.rating-input label {
    cursor: pointer;
    font-size: 2rem;
    color: #ddd;
    transition: color 0.2s;
}
.rating-input label:hover,
.rating-input label:hover ~ label,
.rating-input input[type="radio"]:checked ~ label { color: #FFD700; }

.upload-area {
    border: 2px dashed #ccc;
    border-radius: 8px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: border-color 0.2s;
}
.upload-area:hover { border-color: #e91e8c; }

.media-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.media-preview .preview-item {
    position: relative;
    width: 90px;
    height: 90px;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #ddd;
}
.media-preview .preview-item img,
.media-preview .preview-item video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.media-preview .preview-item .remove-btn {
    position: absolute;
    top: 2px;
    right: 2px;
    background: rgba(0,0,0,0.6);
    color: #fff;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<script>
// Tiap file punya input tersendiri — tidak pakai DataTransfer
const slotCount = {};

function addFileSlot(pid) {
    if (!slotCount[pid]) slotCount[pid] = 0;
    if (slotCount[pid] >= 3) {
        alert('Maksimal 3 file sudah tercapai. Hapus salah satu dulu.');
        return;
    }
    const input = document.createElement('input');
    input.type = 'file';
    input.name = 'media[]';
    input.accept = '.jpg,.jpeg,.png,.mp4,.mov,.avi,.webm';
    input.style.display = 'none';
    input.dataset.pid = pid;
    input.dataset.slot = slotCount[pid];

    input.addEventListener('change', function() {
        if (!this.files[0]) return;
        const file = this.files[0];
        const slot = this.dataset.slot;
        slotCount[pid]++;
        renderSlotPreview(pid, slot, file, this);
        updateHint(pid);
    });

    document.getElementById('fileSlots_' + pid).appendChild(input);
    input.click();
}

function renderSlotPreview(pid, slot, file, input) {
    const preview = document.getElementById('preview_' + pid);
    const item = document.createElement('div');
    item.className = 'preview-item';
    item.dataset.slot = slot;

    if (file.type.startsWith('video/')) {
        const vid = document.createElement('video');
        vid.src = URL.createObjectURL(file);
        vid.controls = true;
        vid.style.cssText = 'width:100%;height:100%;object-fit:cover;';
        item.appendChild(vid);
    } else {
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        item.appendChild(img);
    }

    const btn = document.createElement('button');
    btn.className = 'remove-btn';
    btn.type = 'button';
    btn.innerHTML = '&times;';
    btn.onclick = function() {
        // Hapus input file dari DOM supaya tidak terkirim
        input.remove();
        item.remove();
        slotCount[pid] = Math.max(0, slotCount[pid] - 1);
        updateHint(pid);
    };
    item.appendChild(btn);
    preview.appendChild(item);
}

function updateHint(pid) {
    const count = slotCount[pid] || 0;
    const hint = document.getElementById('uploadHint_' + pid);
    if (!hint) return;
    const remaining = 3 - count;
    hint.textContent = count === 0
        ? 'Klik untuk pilih foto atau video'
        : (remaining > 0 ? 'Tambah lagi (sisa ' + remaining + ' slot)' : 'Maksimal 3 file tercapai');
}
</script>

<?php include 'includes/footer.php'; ?>
