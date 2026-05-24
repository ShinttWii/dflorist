// D'Florist Main JavaScript
// Inspired by Alfagift's smooth UX and interaction patterns

// Initialize Google Maps
let map;
let marker;
let selectedLocation = null;

function initMap(lat = -6.200000, lng = 106.816666) {
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: lat, lng: lng },
        zoom: 13
    });

    marker = new google.maps.Marker({
        position: { lat: lat, lng: lng },
        map: map,
        draggable: true
    });

    google.maps.event.addListener(marker, 'dragend', function() {
        const position = marker.getPosition();
        selectedLocation = {
            lat: position.lat(),
            lng: position.lng()
        };
        document.getElementById('latitude').value = position.lat();
        document.getElementById('longitude').value = position.lng();
    });

    map.addListener('click', function(e) {
        marker.setPosition(e.latLng);
        selectedLocation = {
            lat: e.latLng.lat(),
            lng: e.latLng.lng()
        };
        document.getElementById('latitude').value = e.latLng.lat();
        document.getElementById('longitude').value = e.latLng.lng();
    });
}

// Add to cart
function addToCart(productId, productName, price) {
    const quantity = document.getElementById('quantity_' + productId)?.value || 1;
    
    fetch(SITE_URL + '/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'add',
            product_id: productId,
            product_name: productName,
            price: price,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produk berhasil ditambahkan ke keranjang!');
            location.reload();
        } else {
            alert('Gagal menambahkan produk: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan');
    });
}

// Update cart quantity
function updateCartQuantity(productId, quantity) {
    fetch(SITE_URL + '/api/cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update',
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// Remove from cart
function removeFromCart(productId) {
    if (confirm('Hapus produk dari keranjang?')) {
        fetch(SITE_URL + '/api/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}

// Check delivery quota
function checkQuota(date, method) {
    fetch(SITE_URL + '/api/check_quota.php?date=' + date + '&method=' + method)
        .then(response => response.json())
        .then(data => {
            if (!data.available) {
                alert('Kuota pengiriman untuk tanggal ini sudah penuh');
                return false;
            }
            return true;
        });
}

// Format currency
function formatRupiah(amount) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(amount);
}

// Show rating stars
function showRating(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="fas fa-star rating-stars"></i>';
        } else {
            stars += '<i class="far fa-star rating-stars"></i>';
        }
    }
    return stars;
}

// Confirm delete
function confirmDelete(message) {
    return confirm(message || 'Apakah Anda yakin ingin menghapus?');
}

// Image preview
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(previewId).src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Date picker min date (H+2)
document.addEventListener('DOMContentLoaded', function() {
    const dateInputs = document.querySelectorAll('input[type="date"].delivery-date');
    dateInputs.forEach(input => {
        const today = new Date();
        today.setDate(today.getDate() + 2);
        const minDate = today.toISOString().split('T')[0];
        input.setAttribute('min', minDate);
    });
});

// Add to cart via AJAX (tanpa redirect)
function addToCartAjax(productId, quantity, btnEl) {
    const fd = new FormData();
    fd.append('product_id', productId);
    fd.append('quantity', quantity || 1);

    if (btnEl) {
        btnEl.disabled = true;
        btnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    }

    fetch(SITE_URL + '/api/add_to_cart.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            if (data.success) {
                // Update cart badge
                const badge = document.querySelector('.cart-badge');
                if (badge) {
                    badge.textContent = data.cart_count;
                    badge.style.display = '';
                }

                // Animasi tombol
                if (btnEl) {
                    btnEl.innerHTML = '<i class="fas fa-check"></i>';
                    btnEl.style.color = '#28a745';
                    setTimeout(() => {
                        btnEl.innerHTML = '<i class="fas fa-cart-plus"></i>';
                        btnEl.style.color = '';
                        btnEl.disabled = false;
                    }, 1200);
                }

                // Toast notifikasi
                showCartToast(data.message || 'Ditambahkan ke keranjang');
            } else {
                if (btnEl) {
                    btnEl.innerHTML = '<i class="fas fa-cart-plus"></i>';
                    btnEl.disabled = false;
                }
                alert(data.message || 'Gagal menambahkan produk');
            }
        })
        .catch(() => {
            if (btnEl) {
                btnEl.innerHTML = '<i class="fas fa-cart-plus"></i>';
                btnEl.disabled = false;
            }
        });
}

function showCartToast(msg) {
    let toast = document.getElementById('cartToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'cartToast';
        toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#333;color:#fff;padding:10px 20px;border-radius:20px;font-size:0.9rem;z-index:9999;opacity:0;transition:opacity 0.3s;pointer-events:none;';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.style.opacity = '1';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => { toast.style.opacity = '0'; }, 2000);
}
