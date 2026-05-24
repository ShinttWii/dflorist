    <footer>
        <div class="container">
            <div class="row g-4">
                <!-- Brand -->
                <div class="col-md-4 mb-2">
                    <h5 class="fw-bold mb-2">D'florist</h5>
                    <p class="mb-3" style="font-size:0.9rem; line-height:1.7;">
                        Kami adalah spesialis buket bunga artificial yang mengutamakan detail dan kualitas. Setiap produk kami dirangkai secara custom agar sesuai dengan keinginan Anda, memberikan keindahan yang abadi tanpa perlu khawatir akan layu.
                    </p>
                    <p style="font-size:0.85rem; color:#888;">
                        <i class="fas fa-clock me-1"></i> Senin – Sabtu, 08.00 – 20.00 WIB
                    </p>
                </div>

                <!-- Navigasi -->
                <div class="col-md-2 mb-2">
                    <h6 class="fw-bold mb-3">Navigasi</h6>
                    <ul class="list-unstyled" style="font-size:0.9rem;">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/index.php" class="footer-link">Beranda</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/products.php" class="footer-link">Produk</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/promo.php" class="footer-link">Promo</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/reviews.php" class="footer-link">Ulasan</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/orders.php" class="footer-link">Pesanan Saya</a></li>
                    </ul>
                </div>

                <!-- Layanan -->
                <div class="col-md-2 mb-2">
                    <h6 class="fw-bold mb-3">Layanan</h6>
                    <ul class="list-unstyled" style="font-size:0.9rem;">
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/checkout.php" class="footer-link">Pre-Order</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/chat.php" class="footer-link">Chat CS</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/profile.php" class="footer-link">Akun Saya</a></li>
                        <li class="mb-2"><a href="<?php echo SITE_URL; ?>/addresses.php" class="footer-link">Alamat</a></li>
                    </ul>
                </div>

                <!-- Kontak & Sosmed -->
                <div class="col-md-4 mb-2">
                    <h6 class="fw-bold mb-3">Hubungi Kami</h6>
                    <ul class="list-unstyled" style="font-size:0.9rem;">
                        <li class="mb-2">
                            <a href="https://wa.me/6285863437122" target="_blank" class="footer-link d-flex align-items-center gap-2">
                                <i class="fab fa-whatsapp" style="color:#25D366; font-size:1.1rem;"></i>
                                0858-6343-7122
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="https://instagram.com/d.flo.rist" target="_blank" class="footer-link d-flex align-items-center gap-2">
                                <i class="fab fa-instagram" style="color:#E1306C; font-size:1.1rem;"></i>
                                @d.flo.rist
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="mailto:dfloristreal@gmail.com" class="footer-link d-flex align-items-center gap-2">
                                <i class="fas fa-envelope" style="color:#EA4335; font-size:1.1rem;"></i>
                                dfloristreal@gmail.com
                            </a>
                        </li>
                    </ul>
                    <div class="d-flex gap-3 mt-3">
                        <a href="https://wa.me/6285863437122" target="_blank" class="footer-social-btn" title="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="https://instagram.com/d.flo.rist" target="_blank" class="footer-social-btn" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="mailto:dfloristreal@gmail.com" class="footer-social-btn" title="Email">
                            <i class="fas fa-envelope"></i>
                        </a>
                    </div>
                </div>
            </div>

            <hr style="border-color: rgba(0,0,0,0.1); margin-top:1.5rem;">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center" style="font-size:0.82rem; color:#888;">
                <span>&copy; <?php echo date('Y'); ?> D'florist. All rights reserved.</span>
            </div>
        </div>
    </footer>

    <style>
    .footer-link { color: inherit; text-decoration: none; transition: color 0.2s; }
    .footer-link:hover { color: #FF69B4; }
    .footer-social-btn {
        width: 36px; height: 36px; border-radius: 50%;
        background: rgba(255,255,255,0.1);
        display: flex; align-items: center; justify-content: center;
        color: inherit; text-decoration: none; font-size: 1rem;
        transition: background 0.2s, color 0.2s;
    }
    .footer-social-btn:hover { background: #FF69B4; color: #fff; }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>const SITE_URL = <?php echo json_encode(SITE_URL); ?>;</script>
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
</body>
</html>

<?php
if (ob_get_level()) {
    ob_end_flush(); // Flush output buffer
}
?>
