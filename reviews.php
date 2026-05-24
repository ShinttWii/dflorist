<?php
$pageTitle = 'Ulasan Pelanggan - D\'Florist';
include 'includes/header.php';

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review'])) {
    if (isCustomerLoggedIn()) {
        $reviewId = intval($_POST['review_id']);
        // Ambil review dulu untuk cek ownership dan hapus file
        $stmt = $pdo->prepare("SELECT * FROM reviews WHERE id = ? AND user_id = ?");
        $stmt->execute([$reviewId, $_SESSION['customer_id']]);
        $toDelete = $stmt->fetch();
        if ($toDelete) {
            // Hapus file media
            if (!empty($toDelete['media_files'])) {
                $files = json_decode($toDelete['media_files'], true);
                if ($files) {
                    foreach ($files as $f) {
                        $path = __DIR__ . '/assets/images/reviews/' . $f;
                        if (file_exists($path)) unlink($path);
                    }
                }
            }
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
            $stmt->execute([$reviewId, $_SESSION['customer_id']]);
        }
    }
    // Redirect back
    $back = $_POST['redirect'] ?? 'reviews.php';
    header('Location: ' . $back);
    exit;
}

$currentUserId = isCustomerLoggedIn() ? ($_SESSION['customer_id'] ?? 0) : 0;

$productId = intval($_GET['product'] ?? 0);
$productName = '';

if ($productId) {
    $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $p = $stmt->fetch();
    $productName = $p ? $p['name'] : '';
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as user_name, p.name as product_name, p.id as product_id
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        JOIN products p ON r.product_id = p.id
        WHERE r.product_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$productId]);
} else {
    $stmt = $pdo->query("
        SELECT r.*, u.name as user_name, p.name as product_name, p.id as product_id
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        JOIN products p ON r.product_id = p.id
        ORDER BY r.created_at DESC
    ");
}
$reviews = $stmt->fetchAll();

// Build JS data for all reviews
$reviewsJs = [];
foreach ($reviews as $r) {
    $media = [];
    if (!empty($r['media_files'])) {
        $decoded = json_decode($r['media_files'], true);
        if ($decoded) {
            foreach ($decoded as $f) {
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                $media[] = [
                    'url'  => SITE_URL . '/assets/images/reviews/' . $f,
                    'type' => in_array($ext, ['mp4','mov','avi','webm']) ? 'video' : 'image',
                ];
            }
        }
    }
    $reviewsJs[] = [
        'name'    => $r['user_name'],
        'product' => $r['product_name'],
        'rating'  => (int)$r['rating'],
        'comment' => $r['comment'],
        'date'    => date('d M Y', strtotime($r['created_at'])),
        'media'   => $media,
    ];
}
?>

<div class="container my-5">
    <div class="d-flex align-items-center mb-4 gap-3">
        <?php if ($productId): ?>
        <a href="product_detail.php?id=<?php echo $productId; ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        <?php endif; ?>
        <h2 class="fw-bold mb-0">
            <?php echo $productName ? 'Ulasan: ' . htmlspecialchars($productName) : 'Ulasan Pelanggan'; ?>
        </h2>
    </div>

    <?php if (empty($reviews)): ?>
    <div class="text-center py-5">
        <i class="fas fa-comments fa-5x text-muted mb-3"></i>
        <h4>Belum Ada Ulasan</h4>
        <p class="text-muted">Jadilah yang pertama memberikan ulasan</p>
    </div>
    <?php else: ?>
    <p class="text-muted mb-3"><small>Klik ulasan untuk melihat selengkapnya</small></p>
    <div class="row">
        <?php foreach ($reviews as $i => $review): ?>
        <div class="col-md-6 mb-3">
            <div class="review-card review-clickable" onclick="openReviewSlider(<?php echo $i; ?>)" style="cursor:pointer;">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                        <?php if (!$productId): ?>
                        <small class="text-muted"><?php echo htmlspecialchars($review['product_name']); ?></small>
                        <?php endif; ?>
                    </div>
                    <div class="rating-stars">
                        <?php for ($i2 = 1; $i2 <= 5; $i2++): ?>
                            <i class="fa<?php echo $i2 <= $review['rating'] ? 's' : 'r'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <p class="mb-2 review-comment-preview"><?php echo htmlspecialchars($review['comment']); ?></p>
                <?php if (!empty($review['media_files'])): ?>
                <?php $media = json_decode($review['media_files'], true); ?>
                <?php if ($media): ?>
                <div class="review-media mb-2">
                    <?php foreach (array_slice($media, 0, 3) as $file): ?>
                    <?php $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION)); ?>
                    <?php $url = SITE_URL . '/assets/images/reviews/' . $file; ?>
                    <?php if (in_array($ext, ['mp4','mov','avi','webm'])): ?>
                    <div class="review-thumb-wrap"><video src="<?php echo $url; ?>" class="review-media-item" muted></video><span class="play-icon"><i class="fas fa-play"></i></span></div>
                    <?php else: ?>
                    <img src="<?php echo $url; ?>" class="review-media-item" alt="Foto ulasan">
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (count($media) > 3): ?>
                    <div class="review-media-more">+<?php echo count($media) - 3; ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
                <small class="text-muted"><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($review['created_at'])); ?></small>
                <?php if ($currentUserId && $review['user_id'] == $currentUserId): ?>
                <form method="POST" class="d-inline mt-1" onsubmit="return confirm('Hapus ulasan ini?')">
                    <input type="hidden" name="delete_review" value="1">
                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                    <button type="submit" class="btn btn-link btn-sm text-danger p-0 mt-1" style="font-size:0.8rem;">
                        <i class="fas fa-trash-alt"></i> Hapus ulasan
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Fullscreen Slider -->
<div id="reviewSlider" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.95); z-index:9999; flex-direction:column;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px;">
        <span id="sliderCounter" style="color:#aaa; font-size:0.85rem;"></span>
        <button onclick="closeReviewSlider()" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    <div style="flex:1; display:flex; align-items:center; overflow:hidden; position:relative;">
        <!-- Prev -->
        <button id="sliderPrev" onclick="slideReview(-1)"
            style="position:absolute; left:8px; z-index:10; background:rgba(255,255,255,0.15); border:none; color:#fff; border-radius:50%; width:40px; height:40px; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-chevron-left"></i>
        </button>
        <!-- Slides -->
        <div id="sliderTrack" style="display:flex; width:100%; height:100%; transition:transform 0.3s ease; will-change:transform;"></div>
        <!-- Next -->
        <button id="sliderNext" onclick="slideReview(1)"
            style="position:absolute; right:8px; z-index:10; background:rgba(255,255,255,0.15); border:none; color:#fff; border-radius:50%; width:40px; height:40px; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
    <!-- Dots -->
    <div id="sliderDots" style="display:flex; justify-content:center; gap:6px; padding:12px;"></div>
</div>

<style>
.review-clickable:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); transform: translateY(-2px); transition: all 0.2s; }
.review-comment-preview { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.review-media { display: flex; flex-wrap: wrap; gap: 6px; }
.review-media-item { width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; }
.review-thumb-wrap { position: relative; width: 120px; height: 120px; }
.review-thumb-wrap video { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
.play-icon { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.2rem; background:rgba(0,0,0,0.35); border-radius:6px; }
.review-media-more { width:72px; height:72px; border-radius:6px; background:#eee; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#666; }

/* Slider slide */
.slider-slide {
    min-width: 100%;
    height: 100%;
    overflow-y: auto;
    padding: 16px 24px;
    box-sizing: border-box;
    color: #fff;
}
.slider-stars { color: #FFD700; font-size: 1.1rem; }
.slider-media-grid { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.slider-media-grid img, .slider-media-grid video {
    width: 160px; height: 160px; object-fit: cover; border-radius: 10px; cursor: pointer; border: 2px solid rgba(255,255,255,0.2);
    transition: transform 0.15s, border-color 0.15s;
}
.slider-media-grid img:hover, .slider-media-grid video:hover { border-color: #fff; transform: scale(1.04); }
.slider-vid-wrap {
    position: relative; width: 160px; height: 160px; border-radius: 10px; overflow: hidden;
    cursor: pointer; border: 2px solid rgba(255,255,255,0.2); flex-shrink: 0;
    transition: transform 0.15s, border-color 0.15s;
}
.slider-vid-wrap:hover { border-color: #fff; transform: scale(1.04); }
.slider-vid-wrap video { width: 100%; height: 100%; object-fit: cover; display: block; pointer-events: none; }
.slider-play-icon {
    position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    background: rgba(0,0,0,0.4); color: #fff; font-size: 1.8rem;
    pointer-events: none;
}
@media (max-width: 576px) {
    .slider-media-grid img, .slider-vid-wrap { width: calc(50vw - 28px); height: calc(50vw - 28px); }
}
.slider-dot { width: 8px; height: 8px; border-radius: 50%; background: rgba(255,255,255,0.3); cursor: pointer; transition: background 0.2s; }
.slider-dot.active { background: #fff; }
</style>

<script>
const reviewsData = <?php echo json_encode($reviewsJs, JSON_UNESCAPED_UNICODE); ?>;
let currentSlide = 0;
let touchStartX = 0;

function buildSlides() {
    const track = document.getElementById('sliderTrack');
    track.innerHTML = '';
    reviewsData.forEach((r, i) => {
        const div = document.createElement('div');
        div.className = 'slider-slide';

        let stars = '';
        for (let s = 1; s <= 5; s++) stars += `<i class="fa${s <= r.rating ? 's' : 'r'} fa-star"></i>`;

        let mediaHtml = '';
        if (r.media && r.media.length) {
            mediaHtml = '<div class="slider-media-grid">';
            r.media.forEach((m, mi) => {
                if (m.type === 'video') {
                    mediaHtml += `<div class="slider-vid-wrap" data-index="${mi}" onclick="openSliderFromEl(this)">` +
                        `<video src="${m.url}" muted preload="metadata"></video>` +
                        `<span class="slider-play-icon"><i class="fas fa-play"></i></span></div>`;
                } else {
                    mediaHtml += `<img src="${m.url}" data-index="${mi}" onclick="openSliderFromEl(this)" alt="Foto ulasan" style="cursor:pointer;">`;
                }
            });
            mediaHtml += '</div>';
            // Store media array on parent slide after render
        }

        div.innerHTML = `
            <div class="mb-1" style="font-size:0.8rem; color:#aaa;">${r.product}</div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong style="font-size:1.1rem;">${escHtml(r.name)}</strong>
                <span class="slider-stars">${stars}</span>
            </div>
            ${mediaHtml}
            <p style="color:#ddd; line-height:1.6; margin-top:12px;">${escHtml(r.comment)}</p>
            <div style="margin-top:8px; color:#888; font-size:0.8rem;"><i class="fas fa-calendar"></i> ${r.date}</div>
        `;
        // Simpan media array di slide element sebagai dataset
        if (r.media && r.media.length) {
            div._mediaData = r.media;
        }
        track.appendChild(div);
    });

    // Dots
    const dots = document.getElementById('sliderDots');
    dots.innerHTML = '';
    reviewsData.forEach((_, i) => {
        const d = document.createElement('div');
        d.className = 'slider-dot' + (i === currentSlide ? ' active' : '');
        d.onclick = () => goToSlide(i);
        dots.appendChild(d);
    });
}

function openReviewSlider(index) {
    currentSlide = index;
    buildSlides();
    updateSlider();
    const el = document.getElementById('reviewSlider');
    el.style.display = 'flex';
    document.body.style.overflow = 'hidden';

    // Touch swipe
    const track = document.getElementById('sliderTrack');
    track.addEventListener('touchstart', e => { touchStartX = e.touches[0].clientX; }, { passive: true });
    track.addEventListener('touchend', e => {
        const diff = touchStartX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) slideReview(diff > 0 ? 1 : -1);
    });
}

function closeReviewSlider() {
    document.getElementById('reviewSlider').style.display = 'none';
    document.body.style.overflow = '';
    // stop any playing video
    document.querySelectorAll('#sliderTrack video').forEach(v => v.pause());
}

function slideReview(dir) {
    const next = currentSlide + dir;
    if (next < 0 || next >= reviewsData.length) return;
    currentSlide = next;
    updateSlider();
}

function goToSlide(i) {
    currentSlide = i;
    updateSlider();
}

function updateSlider() {
    document.getElementById('sliderTrack').style.transform = `translateX(-${currentSlide * 100}%)`;
    document.getElementById('sliderCounter').textContent = `${currentSlide + 1} / ${reviewsData.length}`;
    document.getElementById('sliderPrev').style.opacity = currentSlide === 0 ? '0.3' : '1';
    document.getElementById('sliderNext').style.opacity = currentSlide === reviewsData.length - 1 ? '0.3' : '1';
    document.querySelectorAll('.slider-dot').forEach((d, i) => d.classList.toggle('active', i === currentSlide));
}

// Keyboard nav
document.addEventListener('keydown', e => {
    if (document.getElementById('reviewSlider').style.display === 'none') return;
    if (e.key === 'ArrowLeft') slideReview(-1);
    if (e.key === 'ArrowRight') slideReview(1);
    if (e.key === 'Escape') closeReviewSlider();
});

function openSliderFromEl(el) {
    const index = parseInt(el.dataset.index) || 0;
    // Cari slide parent yang punya _mediaData
    let parent = el;
    while (parent && !parent._mediaData) parent = parent.parentElement;
    if (parent && parent._mediaData) {
        openSliderMedia(parent._mediaData, index);
    }
}

function openSliderMediaEncoded(encoded, startIndex) {
    openSliderMedia(JSON.parse(decodeURIComponent(encoded)), startIndex);
}

// Lightbox for media inside slider — swipeable
let lbMedia = [];
let lbIndex = 0;
let lbTouchX = 0;

function openSliderMedia(mediaArr, startIndex) {
    lbMedia = mediaArr;
    lbIndex = startIndex;
    renderLightbox();
}

function renderLightbox() {
    let existing = document.getElementById('sliderLightbox');
    if (existing) existing.remove();

    const overlay = document.createElement('div');
    overlay.id = 'sliderLightbox';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.98);z-index:10000;display:flex;flex-direction:column;align-items:center;justify-content:center;';

    // Close
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = 'position:absolute;top:12px;right:16px;background:none;border:none;color:#fff;font-size:2.5rem;line-height:1;cursor:pointer;z-index:2;';
    closeBtn.onclick = () => overlay.remove();
    overlay.appendChild(closeBtn);

    // Counter
    const counter = document.createElement('div');
    counter.style.cssText = 'position:absolute;top:16px;left:16px;color:#aaa;font-size:0.85rem;';
    counter.textContent = lbMedia.length > 1 ? `${lbIndex + 1} / ${lbMedia.length}` : '';
    overlay.appendChild(counter);

    // Media
    const mediaWrap = document.createElement('div');
    mediaWrap.style.cssText = 'display:flex;align-items:center;justify-content:center;width:100%;height:100%;padding:50px 8px 40px;box-sizing:border-box;';
    const m = lbMedia[lbIndex];
    if (m.type === 'video') {
        const v = document.createElement('video');
        v.src = m.url;
        v.controls = true;
        v.autoplay = true;
        v.muted = true;
        v.playsinline = true;
        v.setAttribute('playsinline', '');
        v.style.cssText = 'max-width:100%;max-height:calc(100vh - 100px);width:auto;border-radius:8px;background:#000;';
        v.onclick = e => e.stopPropagation();
        v.addEventListener('click', e => e.stopPropagation());
        // Unmute setelah user interact
        v.addEventListener('play', () => { v.muted = false; });
        mediaWrap.appendChild(v);
    } else {
        const img = document.createElement('img');
        img.src = m.url; img.alt = 'Foto ulasan';
        img.style.cssText = 'max-width:100%;max-height:calc(100vh - 100px);width:auto;height:auto;object-fit:contain;border-radius:8px;display:block;';
        mediaWrap.appendChild(img);
    }
    overlay.appendChild(mediaWrap);

    // Prev / Next arrows
    if (lbMedia.length > 1) {
        const prev = document.createElement('button');
        prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prev.style.cssText = 'position:absolute;left:8px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:44px;height:44px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;';
        prev.onclick = () => { if (lbIndex > 0) { lbIndex--; renderLightbox(); } };
        prev.style.opacity = lbIndex === 0 ? '0.3' : '1';

        const next = document.createElement('button');
        next.innerHTML = '<i class="fas fa-chevron-right"></i>';
        next.style.cssText = 'position:absolute;right:8px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:44px;height:44px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;';
        next.onclick = () => { if (lbIndex < lbMedia.length - 1) { lbIndex++; renderLightbox(); } };
        next.style.opacity = lbIndex === lbMedia.length - 1 ? '0.3' : '1';

        overlay.appendChild(prev);
        overlay.appendChild(next);

        // Dots
        const dots = document.createElement('div');
        dots.style.cssText = 'position:absolute;bottom:16px;display:flex;gap:6px;';
        lbMedia.forEach((_, i) => {
            const d = document.createElement('div');
            d.style.cssText = `width:8px;height:8px;border-radius:50%;background:${i===lbIndex?'#fff':'rgba(255,255,255,0.3)'};cursor:pointer;`;
            d.onclick = () => { lbIndex = i; renderLightbox(); };
            dots.appendChild(d);
        });
        overlay.appendChild(dots);
    }

    // Touch swipe
    overlay.addEventListener('touchstart', e => { lbTouchX = e.touches[0].clientX; }, { passive: true });
    overlay.addEventListener('touchend', e => {
        const diff = lbTouchX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) {
            if (diff > 0 && lbIndex < lbMedia.length - 1) { lbIndex++; renderLightbox(); }
            if (diff < 0 && lbIndex > 0) { lbIndex--; renderLightbox(); }
        }
    });

    // Click backdrop to close — hanya overlay, bukan mediaWrap (supaya video controls tidak tertutup)
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });

    document.body.appendChild(overlay);
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include 'includes/footer.php'; ?>
