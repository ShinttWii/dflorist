# D'florist - Sistem E-Commerce Toko Bunga

Sistem e-commerce berbasis web untuk toko bunga dengan konsep pre-order dan pengiriman terjadwal.

## Referensi Sistem
Sistem ini mengadopsi best practices dari **Alfagift** (alfagift.id) - platform e-commerce retail terkemuka di Indonesia, dengan penyesuaian untuk kebutuhan toko bunga dan sistem pre-order.

## Teknologi
- PHP 8
- MySQL
- Bootstrap 5
- JavaScript

## Fitur Utama

### Customer (Frontend)
✅ Registrasi dan login dengan hash password (bcrypt)
✅ Verifikasi email dengan OTP (6 digit, berlaku 10 menit)
✅ Lupa password dengan reset token via email
✅ Browsing produk dengan filter kategori dan harga
✅ Sistem keranjang belanja dengan update quantity
✅ Multi-address management dengan Google Maps integration
✅ Pre-order dengan minimal H+2 dari tanggal pemesanan
✅ 3 metode pengiriman dengan logika jarak otomatis:
   - Kurir Toko (≤10km dengan slot waktu)
   - Ekspedisi (>10km)
   - Pick Up (selalu tersedia dengan slot waktu)
✅ Sistem kuota pengiriman (max 5 per tanggal per metode)
✅ Multiple payment methods:
   - DANA (e-wallet)
   - Bank Jago (transfer bank)
   - SeaBank (transfer bank)
   - COD (hanya untuk Kurir Toko & Pick Up)
✅ Payment gateway simulation dengan konfirmasi pembayaran
✅ E-Receipt yang dapat dicetak untuk pesanan yang sudah dibayar
✅ Tracking status pesanan dengan filter
✅ Sistem review dan rating produk (1-5 bintang)
✅ Halaman produk promo
✅ Profil management dengan update password

### Admin (Backend)
✅ Admin dashboard dengan statistik real-time
✅ Manajemen produk (CRUD) dengan upload gambar
✅ Manajemen pesanan dengan update status
✅ Filter dan search pesanan
✅ Laporan Penjualan Lengkap:
   - Laporan Harian (per tanggal)
   - Laporan Bulanan (per bulan)
   - Laporan Per Produk (best seller)
   - Export ke CSV
   - Print laporan
✅ Pengaturan Sistem:
   - Koordinat lokasi toko
   - Radius maksimal kurir toko
   - Minimal hari pre-order
   - Kuota maksimal per tanggal
✅ View alamat customer dengan Google Maps

## Instalasi

1. Import database:
```bash
mysql -u root -p < database.sql
```

2. Konfigurasi database di `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'dflorist');
```

3. Buat folder untuk upload gambar:
```bash
mkdir -p assets/images/products
chmod 777 assets/images/products
```

4. Akses aplikasi:
- Frontend: http://localhost/dflorist
- Admin: http://localhost/dflorist/admin

## Login Default

### Admin
- Email: admin@dflorist.com
- Password: admin123

## Struktur Folder
```
dflorist/
├── admin/              # Panel admin
├── assets/             # CSS, JS, images
├── config/             # Konfigurasi database
├── includes/           # Header, footer, functions
├── index.php           # Halaman utama
├── products.php        # Daftar produk
├── cart.php            # Keranjang
├── checkout.php        # Checkout
├── orders.php          # Riwayat pesanan
└── database.sql        # Database schema
```

## Catatan Penting

1. **Google Maps API**: Tambahkan API key Anda di file:
   - `addresses.php` (line ~200)
   - `checkout.php` (jika ada maps)
   - Ganti `YOUR_GOOGLE_MAPS_API_KEY` dengan API key Anda

2. **Email Configuration**: 
   - Untuk OTP dan reset password, konfigurasi SMTP di `includes/functions.php`
   - Install PHPMailer: `composer require phpmailer/phpmailer`
   - Update fungsi `sendEmail()` dan `sendOTPEmail()`

3. **Payment Gateway**: 
   - Saat ini menggunakan simulasi pembayaran
   - Untuk production, integrasikan dengan:
     - Midtrans (https://midtrans.com)
     - Xendit (https://xendit.co)
     - Atau payment gateway lainnya

4. **PHP Extensions Required**:
   - PDO
   - PDO_MySQL
   - GD (untuk image processing)
   - mbstring
   - OpenSSL (untuk email)

5. **File Permissions**:
   ```bash
   chmod 755 assets/images/products
   chmod 644 config/database.php
   ```

6. **Security untuk Production**:
   - Ubah password admin default
   - Enable HTTPS di `.htaccess`
   - Set `display_errors = Off` di php.ini
   - Gunakan environment variables untuk credentials
   - Enable CSRF protection

## Warna Tema
- Pink Pastel: #FFD6E8
- Blue Pastel: #C5E3F6
- Pink Dark: #FF9EC7
- Blue Dark: #7BBCE8

## 📚 Dokumentasi Lengkap

Sistem ini dilengkapi dengan dokumentasi komprehensif:

### 🚀 Untuk Pemula:
- **[QUICK_START.md](QUICK_START.md)** - Setup sistem dalam 10 menit
- **[USER_GUIDE.md](USER_GUIDE.md)** - Panduan lengkap step-by-step
- **[VIDEO_TUTORIAL_SCRIPT.md](VIDEO_TUTORIAL_SCRIPT.md)** - Script video tutorial

### 📋 Dokumentasi Teknis:
- **[INSTALLATION.md](INSTALLATION.md)** - Panduan instalasi detail
- **[FEATURES.md](FEATURES.md)** - Daftar lengkap semua fitur
- **[CHAT_FEATURE.md](CHAT_FEATURE.md)** - Dokumentasi live chat

### 🎯 Referensi:
- **[REFERENCE.md](REFERENCE.md)** - Referensi Alfagift & best practices
- **[COMPARISON.md](COMPARISON.md)** - Perbandingan dengan Alfagift
- **[CHANGELOG.md](CHANGELOG.md)** - Riwayat perubahan

### 📖 Index:
- **[DOCUMENTATION_INDEX.md](DOCUMENTATION_INDEX.md)** - Indeks semua dokumentasi

**Total: 12 dokumen lengkap (~150 halaman)**

## Lisensi
Proprietary - D'florist © 2024
