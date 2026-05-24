# Troubleshooting Guide - D'florist

## Masalah yang Sudah Diperbaiki

### 1. Komentar Panjang Muncul di Browser ✅
**Masalah:** Komentar DocBlock muncul sebagai teks di bagian atas halaman.

**Penyebab:** Komentar di `includes/functions.php` berada di luar tag `<?php`

**Solusi:** Sudah diperbaiki - komentar sekarang berada di dalam tag PHP.

### 2. Warna Tidak Sesuai (Pink & Blue Pastel) ✅
**Masalah:** Warna masih default Bootstrap, bukan pink & blue pastel.

**Penyebab:** CSS variables tidak diterapkan dengan benar.

**Solusi:** Sudah diperbaiki dengan:
- Pink Pastel: `#FFD6E8`
- Blue Pastel: `#C5E3F6`
- Pink Dark: `#FFB3D9`
- Blue Dark: `#A8D8F0`
- Gradient: `linear-gradient(135deg, #FFD6E8 0%, #C5E3F6 100%)`

### 3. "Tidak Ada Produk Ditemukan" ✅
**Masalah:** Database kosong atau belum terisi data.

**Solusi:** Import file SQL berikut secara berurutan:

## Langkah-Langkah Perbaikan

### Step 1: Import Database
```sql
1. Buka phpMyAdmin
2. Buat database baru: dflorist
3. Import file: database.sql
4. Import file: sample_data.sql (untuk data contoh)
```

### Step 2: Konfigurasi Database
Edit file `config/database.php` sesuai dengan setting MySQL Anda:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Isi password MySQL Anda
define('DB_NAME', 'dflorist');
```

### Step 3: Test Koneksi
Buka browser dan akses:
```
http://localhost/dflorist/test_db.php
```

Anda akan melihat:
- Status koneksi database
- Jumlah data di setiap tabel
- Daftar kategori
- Daftar produk

### Step 4: Clear Browser Cache
Tekan `Ctrl + Shift + R` (Windows) atau `Cmd + Shift + R` (Mac) untuk hard refresh.

### Step 5: Verifikasi Tampilan
1. Buka `http://localhost/dflorist/`
2. Pastikan:
   - ✅ Tidak ada komentar panjang di atas
   - ✅ Navbar berwarna pink & blue pastel
   - ✅ Tombol berwarna pink & blue gradient
   - ✅ Produk muncul di halaman

## Masalah Umum Lainnya

### Database Connection Error
**Error:** "Koneksi database gagal"

**Solusi:**
1. Pastikan MySQL/XAMPP sudah running
2. Cek username dan password di `config/database.php`
3. Pastikan database `dflorist` sudah dibuat
4. Cek apakah port MySQL default (3306) atau custom

### Gambar Produk Tidak Muncul
**Masalah:** Placeholder image muncul terus.

**Solusi:**
1. Buat folder: `assets/images/products/`
2. Set permission folder ke 755 (Linux/Mac)
3. Upload gambar melalui admin panel
4. Format gambar: JPG, PNG (max 2MB)

### CSS Tidak Berubah
**Masalah:** Setelah edit CSS, tampilan tidak berubah.

**Solusi:**
1. Hard refresh: `Ctrl + Shift + R`
2. Clear browser cache
3. Cek apakah file `assets/css/style.css` sudah tersimpan
4. Cek console browser (F12) untuk error

### Admin Tidak Bisa Login
**Masalah:** Login admin gagal terus.

**Solusi:**
1. Pastikan sudah import `sample_data.sql`
2. Default admin:
   - Email: `admin@dflorist.com`
   - Password: `admin123`
3. Jika lupa password, gunakan fitur "Lupa Password"

### Halaman Blank/Error 500
**Masalah:** Halaman tidak muncul sama sekali.

**Solusi:**
1. Aktifkan error reporting di `config/database.php`:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
2. Cek error log di XAMPP/Apache
3. Pastikan PHP version 8.0 atau lebih tinggi
4. Cek file permission (755 untuk folder, 644 untuk file)

## Cek Versi PHP
Buat file `info.php`:
```php
<?php phpinfo(); ?>
```

Akses: `http://localhost/dflorist/info.php`

Pastikan:
- PHP Version: 8.0 atau lebih tinggi
- PDO: Enabled
- PDO MySQL: Enabled

## Struktur Folder yang Benar
```
dflorist/
├── admin/
├── api/
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── js/
│   │   └── main.js
│   └── images/
│       └── products/ (buat folder ini!)
├── config/
│   └── database.php
├── includes/
│   ├── functions.php
│   ├── header.php
│   └── footer.php
├── index.php
└── ... (file lainnya)
```

## Kontak Support
Jika masih ada masalah:
1. Cek file `test_db.php` untuk status database
2. Cek browser console (F12) untuk JavaScript error
3. Cek Apache error log untuk PHP error

## Checklist Setelah Perbaikan
- [ ] Komentar panjang sudah hilang
- [ ] Warna pink & blue pastel sudah muncul
- [ ] Produk sudah tampil di halaman
- [ ] Navbar gradient pink-blue
- [ ] Tombol gradient pink-blue
- [ ] Hero section gradient pink-blue
- [ ] Card hover effect bekerja
- [ ] Database terkoneksi
- [ ] Admin bisa login
- [ ] Customer bisa registrasi

Semua sudah diperbaiki! 🎉
