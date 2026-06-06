<?php
$pageTitle = 'Detail Produk - D\'Florist';
include 'includes/header.php';

$productId = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('products.php');
}

// Get reviews
$stmt = $pdo->prepare("
    SELECT r.*, u.name as user_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$productId]);
$reviews = $stmt->fetchAll();

// Calculate average rating
$avgRating = 0;
if (!empty($reviews)) {
    $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
}

$finalPrice = $product['is_promo'] ? $product['promo_price'] : $product['price'];
?>

<div class="container my-5 product-detail-container">
    <div class="row g-4">
        <div class="col-md-5">
            <div class="text-center">
                <img src="<?php echo $product['image'] ? UPLOAD_URL . $product['image'] : 'https://via.placeholder.com/400'; ?>" 
                     class="product-detail-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
        </div>
        <div class="col-md-7 product-detail-info">
            <div class="mb-2">
                <span class="badge bg-secondary"><?php echo $product['category_name']; ?></span>
                <?php if ($product['is_promo']): ?>
                <span class="badge bg-danger ms-1">PROMO</span>
                <?php endif; ?>
            </div>
            
            <h2 class="fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h2>
            
            <div class="mb-3">
                <span class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <?php if ($i <= round($avgRating)): ?>
                            <i class="fas fa-star"></i>
                        <?php else: ?>
                            <i class="far fa-star"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </span>
                <span class="text-muted ms-2">(<?php echo count($reviews); ?> ulasan)</span>
            </div>
            
            <div class="mb-4">
                <?php if ($product['is_promo']): ?>
                    <div class="price-original mb-1"><?php echo formatRupiah($product['price']); ?></div>
                    <div class="price"><?php echo formatRupiah($product['promo_price']); ?></div>
                <?php else: ?>
                    <div class="price"><?php echo formatRupiah($product['price']); ?></div>
                <?php endif; ?>
            </div>
            
            <p class="mb-4" style="font-size: 0.9rem; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            
            <form onsubmit="return false;">
                <input type="hidden" id="detail-product-id" value="<?php echo $product['id']; ?>">
                
                <div class="mb-3">
                    <span class="badge bg-info">Stok Tersedia: <?php echo $product['stock']; ?></span>
                </div>
                
                <div class="row g-2 align-items-end">
                    <div class="col-auto">
                        <label class="form-label fw-semibold">Jumlah</label>
                        <div class="input-group" style="width: 140px;">
                            <button type="button" class="btn btn-outline-secondary" onclick="decreaseQty()" id="btn-minus-detail">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" name="quantity" id="quantity" class="form-control text-center" value="1" min="1" max="<?php echo $product['stock']; ?>" readonly>
                            <button type="button" class="btn btn-outline-secondary" onclick="increaseQty(<?php echo $product['stock']; ?>)" id="btn-plus-detail">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-auto">
                        <?php if ($product['stock'] > 0): ?>
                        <button type="button" id="btn-add-to-cart" class="btn btn-primary"
                            onclick="addToCartDetail(this)">
                            <i class="fas fa-shopping-cart"></i> Tambah ke Keranjang
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-secondary" disabled>
                            <i class="fas fa-times"></i> Stok Habis
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
            
            <script>
            function increaseQty(maxStock) {
                var qty = document.getElementById('quantity');
                var btnPlus = document.getElementById('btn-plus-detail');
                var btnMinus = document.getElementById('btn-minus-detail');
                var currentVal = parseInt(qty.value);
                
                if (currentVal < maxStock) {
                    qty.value = currentVal + 1;
                    
                    // Update button states
                    if (qty.value >= maxStock) {
                        btnPlus.disabled = true;
                    }
                    if (qty.value > 1) {
                        btnMinus.disabled = false;
                    }
                }
            }
            
            function decreaseQty() {
                var qty = document.getElementById('quantity');
                var btnPlus = document.getElementById('btn-plus-detail');
                var btnMinus = document.getElementById('btn-minus-detail');
                var currentVal = parseInt(qty.value);
                
                if (currentVal > 1) {
                    qty.value = currentVal - 1;
                    
                    // Update button states
                    if (qty.value < <?php echo $product['stock']; ?>) {
                        btnPlus.disabled = false;
                    }
                    if (qty.value <= 1) {
                        btnMinus.disabled = true;
                    }
                }
            }

            function addToCartDetail(btnEl) {
                var productId = document.getElementById('detail-product-id').value;
                var quantity  = document.getElementById('quantity').value;

                btnEl.disabled = true;
                var originalHtml = btnEl.innerHTML;
                btnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

                var fd = new FormData();
                fd.append('product_id', productId);
                fd.append('quantity', quantity);

                fetch(SITE_URL + '/api/add_to_cart.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            // Update badge keranjang di navbar
                            var badge = document.querySelector('.cart-count');
                            if (badge) {
                                badge.textContent = data.cart_count;
                                badge.style.display = data.cart_count > 0 ? '' : 'none';
                            }
                            btnEl.innerHTML = '<i class="fas fa-check"></i> Ditambahkan!';
                            btnEl.classList.replace('btn-primary', 'btn-success');
                            setTimeout(function() {
                                btnEl.innerHTML = originalHtml;
                                btnEl.classList.replace('btn-success', 'btn-primary');
                                btnEl.disabled = false;
                            }, 1500);
                            if (typeof showCartToast === 'function') {
                                showCartToast(data.message || 'Ditambahkan ke keranjang');
                            }
                        } else {
                            alert(data.message || 'Gagal menambahkan produk');
                            btnEl.innerHTML = originalHtml;
                            btnEl.disabled = false;
                        }
                    })
                    .catch(function() {
                        alert('Terjadi kesalahan koneksi.');
                        btnEl.innerHTML = originalHtml;
                        btnEl.disabled = false;
                    });
            }
            </script>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Ulasan Pelanggan</h4>
                <?php if (count($reviews) > 1): ?>
                <a href="reviews.php?product=<?php echo $productId; ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-comments"></i> Lihat Semua Ulasan (<?php echo count($reviews); ?>)
                </a>
                <?php endif; ?>
            </div>
            <?php if (empty($reviews)): ?>
            <p class="text-muted">Belum ada ulasan untuk produk ini</p>
            <?php else: ?>
                <?php
                $firstReview = $reviews[0];
                // Build JS data for slider
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
                        'rating'  => (int)$r['rating'],
                        'comment' => $r['comment'],
                        'date'    => date('d M Y', strtotime($r['created_at'])),
                        'media'   => $media,
                    ];
                }
                ?>
                <p class="text-muted mb-3"><small>Klik ulasan untuk melihat selengkapnya</small></p>
                <!-- Tampilkan 1 ulasan terbaru -->
                <div class="col-md-6 mb-3 px-0">
                    <div class="review-card review-clickable" onclick="openReviewSlider(0)" style="cursor:pointer;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="mb-0"><?php echo htmlspecialchars($firstReview['user_name']); ?></h6>
                            <div class="rating-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa<?php echo $i <= $firstReview['rating'] ? 's' : 'r'; ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="mb-2 review-comment-preview"><?php echo htmlspecialchars($firstReview['comment']); ?></p>
                        <?php if (!empty($firstReview['media_files'])): ?>
                        <?php $media = json_decode($firstReview['media_files'], true); ?>
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
                        <small class="text-muted"><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($firstReview['created_at'])); ?></small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Fullscreen Slider (sama persis dengan reviews.php) -->
<div id="reviewSlider" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.95); z-index:9999; flex-direction:column;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 16px;">
        <span id="sliderCounter" style="color:#aaa; font-size:0.85rem;"></span>
        <button onclick="closeReviewSlider()" style="background:none; border:none; color:#fff; font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    <div style="flex:1; display:flex; align-items:center; overflow:hidden; position:relative;">
        <button id="sliderPrev" onclick="slideReview(-1)"
            style="position:absolute; left:8px; z-index:10; background:rgba(255,255,255,0.15); border:none; color:#fff; border-radius:50%; width:40px; height:40px; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-chevron-left"></i>
        </button>
        <div id="sliderTrack" style="display:flex; width:100%; height:100%; transition:transform 0.3s ease; will-change:transform;"></div>
        <button id="sliderNext" onclick="slideReview(1)"
            style="position:absolute; right:8px; z-index:10; background:rgba(255,255,255,0.15); border:none; color:#fff; border-radius:50%; width:40px; height:40px; font-size:1.2rem; cursor:pointer; display:flex; align-items:center; justify-content:center;">
            <i class="fas fa-chevron-right"></i>
        </button>
    </div>
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
.slider-slide { min-width:100%; height:100%; overflow-y:auto; padding:16px 24px; box-sizing:border-box; color:#fff; }
.slider-stars { color:#FFD700; font-size:1.1rem; }
.slider-media-grid { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; }
.slider-media-grid img { width:160px; height:160px; object-fit:cover; border-radius:10px; cursor:pointer; border:2px solid rgba(255,255,255,0.2); transition:transform 0.15s,border-color 0.15s; }
.slider-media-grid img:hover { border-color:#fff; transform:scale(1.04); }
.slider-vid-wrap { position:relative; width:160px; height:160px; border-radius:10px; overflow:hidden; cursor:pointer; border:2px solid rgba(255,255,255,0.2); flex-shrink:0; transition:transform 0.15s,border-color 0.15s; }
.slider-vid-wrap:hover { border-color:#fff; transform:scale(1.04); }
.slider-vid-wrap video { width:100%; height:100%; object-fit:cover; display:block; pointer-events:none; }
.slider-play-icon { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(0,0,0,0.4); color:#fff; font-size:1.8rem; pointer-events:none; }
.slider-dot { width:8px; height:8px; border-radius:50%; background:rgba(255,255,255,0.3); cursor:pointer; transition:background 0.2s; }
.slider-dot.active { background:#fff; }
@media (max-width:576px) { .slider-media-grid img,.slider-vid-wrap { width:calc(50vw - 28px); height:calc(50vw - 28px); } }
</style>

<script>
const reviewsData = <?php echo json_encode($reviewsJs ?? [], JSON_UNESCAPED_UNICODE); ?>;
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
                    mediaHtml += `<div class="slider-vid-wrap" data-index="${mi}" onclick="openSliderFromEl(this)"><video src="${m.url}" muted preload="metadata"></video><span class="slider-play-icon"><i class="fas fa-play"></i></span></div>`;
                } else {
                    mediaHtml += `<img src="${m.url}" data-index="${mi}" onclick="openSliderFromEl(this)" alt="Foto ulasan">`;
                }
            });
            mediaHtml += '</div>';
        }
        div.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong style="font-size:1.1rem;">${escHtml(r.name)}</strong>
                <span class="slider-stars">${stars}</span>
            </div>
            ${mediaHtml}
            <p style="color:#ddd; line-height:1.6; margin-top:12px;">${escHtml(r.comment)}</p>
            <div style="margin-top:8px; color:#888; font-size:0.8rem;"><i class="fas fa-calendar"></i> ${r.date}</div>
        `;
        if (r.media && r.media.length) div._mediaData = r.media;
        track.appendChild(div);
    });
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
    document.querySelectorAll('#sliderTrack video').forEach(v => v.pause());
}

function slideReview(dir) {
    const next = currentSlide + dir;
    if (next < 0 || next >= reviewsData.length) return;
    currentSlide = next;
    updateSlider();
}

function goToSlide(i) { currentSlide = i; updateSlider(); }

function updateSlider() {
    document.getElementById('sliderTrack').style.transform = `translateX(-${currentSlide * 100}%)`;
    document.getElementById('sliderCounter').textContent = `${currentSlide + 1} / ${reviewsData.length}`;
    document.getElementById('sliderPrev').style.opacity = currentSlide === 0 ? '0.3' : '1';
    document.getElementById('sliderNext').style.opacity = currentSlide === reviewsData.length - 1 ? '0.3' : '1';
    document.querySelectorAll('.slider-dot').forEach((d, i) => d.classList.toggle('active', i === currentSlide));
}

document.addEventListener('keydown', e => {
    if (document.getElementById('reviewSlider').style.display === 'none') return;
    if (e.key === 'ArrowLeft') slideReview(-1);
    if (e.key === 'ArrowRight') slideReview(1);
    if (e.key === 'Escape') closeReviewSlider();
});

function openSliderFromEl(el) {
    const index = parseInt(el.dataset.index) || 0;
    let parent = el;
    while (parent && !parent._mediaData) parent = parent.parentElement;
    if (parent && parent._mediaData) openSliderMedia(parent._mediaData, index);
}

let lbMedia = [], lbIndex = 0, lbTouchX = 0;
function openSliderMedia(mediaArr, startIndex) { lbMedia = mediaArr; lbIndex = startIndex; renderLightbox(); }

function renderLightbox() {
    let existing = document.getElementById('sliderLightbox');
    if (existing) existing.remove();
    const overlay = document.createElement('div');
    overlay.id = 'sliderLightbox';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.98);z-index:10000;display:flex;flex-direction:column;align-items:center;justify-content:center;';
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = 'position:absolute;top:12px;right:16px;background:none;border:none;color:#fff;font-size:2.5rem;line-height:1;cursor:pointer;z-index:2;';
    closeBtn.onclick = () => overlay.remove();
    overlay.appendChild(closeBtn);
    const counter = document.createElement('div');
    counter.style.cssText = 'position:absolute;top:16px;left:16px;color:#aaa;font-size:0.85rem;';
    counter.textContent = lbMedia.length > 1 ? `${lbIndex + 1} / ${lbMedia.length}` : '';
    overlay.appendChild(counter);
    const mediaWrap = document.createElement('div');
    mediaWrap.style.cssText = 'display:flex;align-items:center;justify-content:center;width:100%;height:100%;padding:50px 8px 40px;box-sizing:border-box;';
    const m = lbMedia[lbIndex];
    if (m.type === 'video') {
        const v = document.createElement('video');
        v.src = m.url; v.controls = true; v.autoplay = true; v.muted = true; v.playsinline = true;
        v.style.cssText = 'max-width:100%;max-height:calc(100vh - 100px);width:auto;border-radius:8px;background:#000;';
        v.onclick = e => e.stopPropagation();
        v.addEventListener('play', () => { v.muted = false; });
        mediaWrap.appendChild(v);
    } else {
        const img = document.createElement('img');
        img.src = m.url; img.alt = 'Foto ulasan';
        img.style.cssText = 'max-width:100%;max-height:calc(100vh - 100px);width:auto;height:auto;object-fit:contain;border-radius:8px;display:block;';
        mediaWrap.appendChild(img);
    }
    overlay.appendChild(mediaWrap);
    if (lbMedia.length > 1) {
        const prev = document.createElement('button');
        prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prev.style.cssText = 'position:absolute;left:8px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:44px;height:44px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;';
        prev.style.opacity = lbIndex === 0 ? '0.3' : '1';
        prev.onclick = () => { if (lbIndex > 0) { lbIndex--; renderLightbox(); } };
        const next = document.createElement('button');
        next.innerHTML = '<i class="fas fa-chevron-right"></i>';
        next.style.cssText = 'position:absolute;right:8px;top:50%;transform:translateY(-50%);background:rgba(255,255,255,0.15);border:none;color:#fff;border-radius:50%;width:44px;height:44px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;';
        next.style.opacity = lbIndex === lbMedia.length - 1 ? '0.3' : '1';
        next.onclick = () => { if (lbIndex < lbMedia.length - 1) { lbIndex++; renderLightbox(); } };
        overlay.appendChild(prev); overlay.appendChild(next);
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
    overlay.addEventListener('touchstart', e => { lbTouchX = e.touches[0].clientX; }, { passive: true });
    overlay.addEventListener('touchend', e => {
        const diff = lbTouchX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) {
            if (diff > 0 && lbIndex < lbMedia.length - 1) { lbIndex++; renderLightbox(); }
            if (diff < 0 && lbIndex > 0) { lbIndex--; renderLightbox(); }
        }
    });
    overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
    document.body.appendChild(overlay);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php include 'includes/footer.php'; ?>
