# Daftar Fitur Lengkap D'florist E-Commerce

## 🛍️ CUSTOMER FEATURES

### 1. Autentikasi & Keamanan
- ✅ Registrasi dengan validasi email
- ✅ Login dengan session management
- ✅ Logout
- ✅ Lupa password dengan OTP via email (6 digit, berlaku 10 menit)
- ✅ Reset password dengan verifikasi OTP
- ✅ Password hashing dengan bcrypt
- ✅ Session timeout otomatis

### 2. Manajemen Profil
- ✅ Update informasi profil (nama, nomor HP)
- ✅ Ubah password dengan verifikasi password lama
- ✅ View informasi akun (tanggal registrasi, last update)

### 3. Manajemen Alamat
- ✅ Tambah alamat baru dengan Google Maps
- ✅ Edit alamat existing
- ✅ Hapus alamat
- ✅ Set alamat utama
- ✅ Multiple alamat per user
- ✅ Validasi koordinat GPS (wajib pilih di peta)
- ✅ Label alamat (Rumah, Kantor, dll)
- ✅ Nama & nomor HP penerima berbeda
- ✅ Catatan tambahan untuk alamat

### 4. Browsing & Shopping
- ✅ Halaman beranda dengan produk promo
- ✅ Daftar semua produk
- ✅ Detail produk dengan gambar
- ✅ Filter produk berdasarkan kategori (Small, Medium, Big)
- ✅ Sorting harga (Termurah ke Termahal, Termahal ke Termurah)
- ✅ Halaman khusus produk promo
- ✅ View ulasan produk dengan rating
- ✅ Hitung rata-rata rating produk

### 5. Keranjang Belanja
- ✅ Tambah produk ke keranjang
- ✅ Update quantity produk
- ✅ Hapus produk dari keranjang
- ✅ View total harga
- ✅ Counter badge jumlah item di navbar
- ✅ Session-based cart (tidak perlu login untuk browse)

### 6. Checkout & Pre-Order
- ✅ Pilih alamat pengiriman dari daftar alamat
- ✅ Perhitungan jarak otomatis dari toko ke alamat
- ✅ Metode pengiriman berdasarkan jarak:
  - Kurir Toko (jarak ≤ 10km)
  - Ekspedisi (jarak > 10km)
  - Pick Up (selalu tersedia)
- ✅ Pilih tanggal pengiriman (minimal H+2)
- ✅ Pilih slot waktu untuk Kurir Toko & Pick Up
- ✅ Sistem kuota (max 5 pesanan per tanggal per metode)
- ✅ Indikator kuota penuh
- ✅ Perhitungan ongkir otomatis
- ✅ Catatan pesanan (opsional)

### 7. Pembayaran
- ✅ Multiple payment methods:
  - DANA (e-wallet)
  - Bank Jago (transfer bank)
  - SeaBank (transfer bank)
  - COD (Cash on Delivery)
- ✅ COD hanya untuk Kurir Toko & Pick Up
- ✅ Payment gateway simulation
- ✅ Konfirmasi pembayaran
- ✅ Update status otomatis setelah bayar

### 8. Manajemen Pesanan
- ✅ Riwayat pesanan lengkap
- ✅ Filter pesanan berdasarkan status:
  - Menunggu Pembayaran
  - Dibayar
  - Diproses
  - Dikirim
  - Selesai
  - Dibatalkan
- ✅ Detail pesanan dengan timeline
- ✅ Track status pesanan
- ✅ View alamat pengiriman
- ✅ View informasi pembayaran

### 9. E-Receipt
- ✅ Generate e-receipt untuk pesanan lunas
- ✅ Print-friendly format
- ✅ Informasi lengkap (produk, alamat, pembayaran)
- ✅ Nomor pesanan unik
- ✅ Timestamp pembayaran

### 10. Review & Rating
- ✅ Beri rating 1-5 bintang
- ✅ Tulis komentar ulasan
- ✅ Hanya bisa review jika status "Selesai"
- ✅ Satu review per produk per pesanan
- ✅ View semua ulasan pelanggan
- ✅ View ulasan per produk

---

## 👨‍💼 ADMIN FEATURES

### 1. Dashboard
- ✅ Total pesanan
- ✅ Total pendapatan
- ✅ Pesanan pending
- ✅ Total pelanggan
- ✅ Grafik statistik
- ✅ Pesanan terbaru (10 terakhir)
- ✅ Quick access ke semua menu

### 2. Manajemen Produk
- ✅ Tambah produk baru
- ✅ Edit produk existing
- ✅ Hapus produk
- ✅ Upload gambar produk
- ✅ Set kategori (Small, Medium, Big)
- ✅ Set harga normal
- ✅ Set harga promo
- ✅ Toggle status promo
- ✅ Manajemen stok
- ✅ Toggle status aktif/nonaktif
- ✅ View semua produk dalam tabel

### 3. Manajemen Pesanan
- ✅ View semua pesanan
- ✅ Filter berdasarkan status
- ✅ Search berdasarkan nomor pesanan atau nama customer
- ✅ Update status pesanan:
  - Menunggu Pembayaran → Dibayar
  - Dibayar → Diproses
  - Diproses → Dikirim
  - Dikirim → Selesai
  - Atau → Dibatalkan
- ✅ View detail pesanan lengkap
- ✅ View alamat pengiriman customer
- ✅ View informasi pembayaran
- ✅ View produk yang dipesan

### 4. Laporan Penjualan
- ✅ Laporan Harian:
  - Total pesanan per hari
  - Total pendapatan per hari
  - Pendapatan yang sudah dibayar
- ✅ Laporan Bulanan:
  - Total pesanan per bulan
  - Total pendapatan per bulan
  - Trend penjualan
- ✅ Laporan Per Produk:
  - Produk terlaris
  - Total quantity terjual
  - Total pendapatan per produk
  - Filter per produk spesifik
- ✅ Filter berdasarkan periode (tanggal mulai - akhir)
- ✅ Summary statistik:
  - Total pesanan
  - Total pendapatan
  - Sudah dibayar
  - Belum dibayar
- ✅ Export ke CSV
- ✅ Print laporan (print-friendly)

### 5. Pengaturan Sistem
- ✅ Set koordinat lokasi toko (latitude/longitude)
- ✅ Set radius maksimal kurir toko (default 10km)
- ✅ Set minimal hari pre-order (default H+2)
- ✅ Set kuota maksimal per tanggal (default 5)
- ✅ View slot waktu pengiriman
- ✅ Quick stats di sidebar

### 6. Manajemen Customer
- ✅ View daftar customer
- ✅ View detail customer
- ✅ View alamat customer
- ✅ View riwayat pesanan customer

---

## 🔧 TECHNICAL FEATURES

### 1. Security
- ✅ Password hashing (bcrypt)
- ✅ SQL injection protection (prepared statements)
- ✅ XSS protection (sanitize input)
- ✅ Session management
- ✅ Role-based access control (customer/admin)
- ✅ HTTPS ready (.htaccess configured)
- ✅ Secure file upload validation

### 2. Database
- ✅ 14 tabel dengan relasi proper
- ✅ Foreign key constraints
- ✅ Indexes untuk performa
- ✅ Timestamps (created_at, updated_at)
- ✅ Soft delete ready
- ✅ Transaction support

### 3. Email System
- ✅ OTP verification (6 digit)
- ✅ Password reset token
- ✅ Order confirmation email (ready)
- ✅ Payment confirmation email (ready)
- ✅ PHPMailer integration ready

### 4. Payment Gateway
- ✅ Multiple payment methods
- ✅ Payment simulation
- ✅ Payment confirmation
- ✅ Midtrans integration ready
- ✅ Xendit integration ready

### 5. Google Maps Integration
- ✅ Interactive map untuk pilih lokasi
- ✅ Drag & drop marker
- ✅ Click to set location
- ✅ Geocoding support
- ✅ Distance calculation (Haversine formula)
- ✅ Radius validation

### 6. UI/UX
- ✅ Responsive design (mobile & desktop)
- ✅ Bootstrap 5
- ✅ Font Awesome icons
- ✅ Modern & clean design
- ✅ Pink & blue pastel theme
- ✅ Smooth animations
- ✅ Loading states
- ✅ Error handling
- ✅ Success notifications
- ✅ Print-friendly pages

### 7. Performance
- ✅ Optimized queries
- ✅ Image compression ready
- ✅ Browser caching (.htaccess)
- ✅ GZIP compression
- ✅ Lazy loading ready
- ✅ CDN for libraries

### 8. Code Quality
- ✅ PSR-12 coding standards ready
- ✅ Modular structure
- ✅ Reusable functions
- ✅ Separation of concerns
- ✅ DRY principle
- ✅ Comments & documentation
- ✅ Error logging ready

---

## 📊 BUSINESS LOGIC

### 1. Pre-Order System
- Minimal H+2 dari tanggal pemesanan
- Customer tidak bisa pilih tanggal sebelum H+2
- Validasi di frontend & backend

### 2. Delivery Logic
- Perhitungan jarak otomatis (Haversine formula)
- Kurir Toko: jarak ≤ 10km (configurable)
- Ekspedisi: jarak > 10km
- Pick Up: selalu tersedia
- Slot waktu berbeda per metode

### 3. Quota System
- Max 5 pesanan per tanggal per metode (configurable)
- Dihitung per transaksi, bukan per item
- Indikator visual jika kuota penuh
- Tanggal disabled jika kuota penuh

### 4. Payment Logic
- COD hanya untuk Kurir Toko & Pick Up
- COD tidak tersedia untuk Ekspedisi
- Auto update status jika COD
- Manual confirmation untuk transfer

### 5. Order Status Flow
```
Menunggu Pembayaran → Dibayar → Diproses → Dikirim → Selesai
                    ↓
                Dibatalkan
```

### 6. Review Logic
- Hanya bisa review jika status "Selesai"
- Satu review per produk per pesanan
- Rating 1-5 bintang (required)
- Komentar (required)

---

## 🎨 DESIGN SYSTEM

### Colors
- Pink Pastel: #FFD6E8
- Blue Pastel: #C5E3F6
- Pink Dark: #FF9EC7
- Blue Dark: #7BBCE8
- Text Dark: #2C3E50

### Typography
- Font: Segoe UI, Tahoma, Geneva, Verdana, sans-serif
- Headings: Bold
- Body: Regular

### Components
- Cards dengan shadow & hover effect
- Buttons dengan gradient & hover animation
- Badges untuk status
- Modals untuk forms
- Alerts untuk notifications
- Timeline untuk order tracking

---

## 📱 RESPONSIVE BREAKPOINTS

- Mobile: < 768px
- Tablet: 768px - 1024px
- Desktop: > 1024px

Semua halaman fully responsive dengan layout yang optimal untuk setiap device.
