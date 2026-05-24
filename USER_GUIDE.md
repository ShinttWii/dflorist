# Panduan Lengkap Penggunaan Sistem D'florist

## 📋 Daftar Isi
1. [Instalasi Sistem](#instalasi-sistem)
2. [Setup Awal](#setup-awal)
3. [Panduan Admin](#panduan-admin)
4. [Panduan Customer](#panduan-customer)
5. [Troubleshooting](#troubleshooting)

---

## 1. INSTALASI SISTEM

### Langkah 1: Persiapan Environment

**Requirements:**
- PHP 8.0 atau lebih tinggi
- MySQL 5.7+ atau MariaDB 10.3+
- Apache/Nginx Web Server
- phpMyAdmin (opsional, untuk kemudahan)

**Cek PHP Version:**
```bash
php -v
```

### Langkah 2: Setup Database

**A. Buat Database:**
```sql
-- Via MySQL Command Line
mysql -u root -p
CREATE DATABASE dflorist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

**B. Import Database Schema:**
```bash
# Via Command Line
mysql -u root -p dflorist < database.sql

# Atau via phpMyAdmin:
# 1. Buka phpMyAdmin
# 2. Pilih database 'dflorist'
# 3. Klik tab 'Import'
# 4. Pilih file 'database.sql'
# 5. Klik 'Go'
```

### Langkah 3: Konfigurasi Aplikasi

**Edit file `config/database.php`:**
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');              // Ganti dengan user MySQL Anda
define('DB_PASS', '');                  // Ganti dengan password MySQL Anda
define('DB_NAME', 'dflorist');

define('SITE_URL', 'http://localhost/dflorist');  // Sesuaikan dengan URL Anda
```

### Langkah 4: Setup Folder Upload

**Windows (Command Prompt):**
```cmd
cd C:\xampp\htdocs\dflorist
mkdir assets\images\products
```

**Linux/Mac:**
```bash
cd /var/www/html/dflorist
mkdir -p assets/images/products
chmod 755 assets/images/products
```

### Langkah 5: Test Akses

Buka browser dan akses:
- **Frontend**: http://localhost/dflorist
- **Admin**: http://localhost/dflorist/admin

---

## 2. SETUP AWAL

### Langkah 1: Login Admin Pertama Kali

1. Buka: http://localhost/dflorist/admin
2. Login dengan kredensial default:
   - **Email**: admin@dflorist.com
   - **Password**: admin123

⚠️ **PENTING**: Segera ubah password default!

### Langkah 2: Ubah Password Admin

1. Klik nama admin di navbar (kanan atas)
2. Pilih "Profile" atau langsung edit di database
3. Atau gunakan fitur "Lupa Password" dengan OTP

### Langkah 3: Konfigurasi Pengaturan Toko

1. Login ke admin panel
2. Klik menu **"Pengaturan"**
3. Isi data toko:

**Lokasi Toko:**
- Latitude: -6.200000 (contoh Jakarta)
- Longitude: 106.816666
- Cara dapat koordinat: Buka Google Maps → Klik kanan lokasi toko → Pilih koordinat

**Pengaturan Pengiriman:**
- Radius Maksimal Kurir: 10 km (default)
- Minimal Pre-Order: 2 hari (default)
- Kuota Per Tanggal: 5 pesanan (default)

4. Klik **"Simpan Pengaturan"**

### Langkah 4: Tambah Produk

1. Klik menu **"Produk"**
2. Klik tombol **"Tambah Produk"**
3. Isi form:
   - Nama Produk: "Buket Mawar Merah"
   - Deskripsi: "Buket mawar merah segar..."
   - Kategori: Small/Medium/Big
   - Harga Normal: 150000
   - Harga Promo: 120000 (opsional)
   - Stok: 10
   - Centang "Produk Promo" jika ada promo
   - Upload gambar produk
4. Klik **"Simpan"**

**Tips**: Tambahkan minimal 5-10 produk untuk testing lengkap

---

## 3. PANDUAN ADMIN

### A. Dashboard

**Akses**: Admin Panel → Dashboard

**Fitur:**
- Lihat statistik real-time:
  - Total pesanan
  - Total pendapatan
  - Pesanan pending
  - Total pelanggan
- Lihat 10 pesanan terbaru
- Quick access ke semua menu

### B. Manajemen Produk

**Tambah Produk:**
1. Produk → Tambah Produk
2. Isi semua field
3. Upload gambar (JPG/PNG, max 2MB)
4. Simpan

**Edit Produk:**
1. Klik tombol Edit (icon pensil)
2. Ubah data yang diperlukan
3. Upload gambar baru (opsional)
4. Simpan

**Hapus Produk:**
1. Klik tombol Hapus (icon trash)
2. Konfirmasi penghapusan

**Toggle Status:**
1. Klik tombol status (Aktif/Nonaktif)
2. Status akan berubah otomatis

### C. Manajemen Pesanan

**Lihat Semua Pesanan:**
1. Klik menu **"Pesanan"**
2. Lihat daftar pesanan dengan info:
   - Nomor pesanan
   - Customer
   - Total
   - Status pembayaran
   - Status pesanan
   - Tanggal

**Filter Pesanan:**
1. Gunakan dropdown "Status"
2. Atau gunakan search box untuk cari nomor/nama

**Update Status Pesanan:**
1. Klik tombol Edit (icon pensil)
2. Pilih status baru:
   - Menunggu Pembayaran → Dibayar
   - Dibayar → Diproses
   - Diproses → Dikirim
   - Dikirim → Selesai
   - Atau → Dibatalkan
3. Klik "Update Status"

**Lihat Detail Pesanan:**
1. Klik tombol "Detail" (icon mata)
2. Lihat informasi lengkap:
   - Produk yang dipesan
   - Alamat pengiriman
   - Metode pengiriman
   - Informasi pembayaran

### D. Chat Customer Service

**Akses Chat:**
1. Klik menu **"Chat CS"**
2. Lihat daftar percakapan di sebelah kiri
3. Badge merah menunjukkan pesan belum dibaca

**Balas Chat Customer:**
1. Klik percakapan dari daftar
2. Lihat info customer di header (nama, email, phone)
3. Baca pesan customer
4. Ketik balasan di textarea bawah
5. Tekan Enter atau klik "Kirim"

**Tutup Percakapan:**
1. Klik tombol "Tutup Chat" di header
2. Konfirmasi penutupan
3. Percakapan akan dipindah ke status "closed"

**Tips:**
- Balas pesan customer dengan cepat
- Gunakan bahasa yang sopan dan ramah
- Berikan informasi yang jelas

### E. Laporan Penjualan

**Laporan Harian:**
1. Klik menu **"Laporan"**
2. Pilih "Jenis Laporan": Harian
3. Pilih tanggal mulai dan akhir
4. Klik "Tampilkan Laporan"
5. Lihat data per hari

**Laporan Bulanan:**
1. Pilih "Jenis Laporan": Bulanan
2. Pilih periode
3. Lihat data per bulan

**Laporan Per Produk:**
1. Pilih "Jenis Laporan": Per Produk
2. Pilih produk spesifik (opsional)
3. Lihat produk terlaris

**Export & Print:**
- Klik "Export ke CSV" untuk download data
- Klik "Cetak Laporan" untuk print

### F. Pengaturan Sistem

**Update Pengaturan:**
1. Klik menu **"Pengaturan"**
2. Ubah nilai yang diperlukan:
   - Koordinat toko
   - Radius kurir
   - Minimal pre-order
   - Kuota per tanggal
3. Klik "Simpan Pengaturan"

**Lihat Slot Waktu:**
- Scroll ke bawah untuk lihat slot waktu pengiriman
- Untuk ubah slot, edit file `checkout.php`

---

## 4. PANDUAN CUSTOMER

### A. Registrasi & Login

**Registrasi Akun Baru:**
1. Buka: http://localhost/dflorist
2. Klik icon User → "Daftar"
3. Isi form registrasi:
   - Nama Lengkap
   - Email
   - Nomor HP
   - Password (min 6 karakter)
   - Konfirmasi Password
4. Klik "Daftar"
5. Login dengan email dan password

**Login:**
1. Klik icon User → "Login"
2. Masukkan email dan password
3. Klik "Login"

**Lupa Password:**
1. Klik "Lupa Password?"
2. Masukkan email
3. Klik "Kirim Kode OTP"
4. Cek email untuk kode OTP (Demo: ditampilkan di halaman)
5. Masukkan kode OTP 6 digit
6. Klik "Verifikasi OTP"
7. Masukkan password baru
8. Klik "Reset Password"

### B. Browsing Produk

**Lihat Semua Produk:**
1. Klik menu "Produk" di navbar
2. Lihat daftar produk dengan gambar dan harga

**Filter Produk:**
- Klik kategori: Small, Medium, Big
- Pilih sorting: Termurah/Termahal

**Lihat Produk Promo:**
1. Klik "Lihat Semua Promo" di beranda
2. Atau filter produk dengan badge "PROMO"

**Detail Produk:**
1. Klik "Lihat Detail" pada produk
2. Lihat informasi lengkap:
   - Gambar produk
   - Deskripsi
   - Harga
   - Rating & ulasan
3. Pilih jumlah
4. Klik "Tambah ke Keranjang"

### C. Keranjang Belanja

**Lihat Keranjang:**
1. Klik icon Keranjang di navbar
2. Badge menunjukkan jumlah item

**Update Quantity:**
1. Ubah angka di kolom quantity
2. Otomatis update total

**Hapus Item:**
1. Klik tombol Trash (icon tempat sampah)
2. Item akan dihapus

**Lanjut Checkout:**
1. Klik "Lanjut ke Checkout"
2. Login jika belum login

### D. Manajemen Alamat

**Tambah Alamat Pertama:**
1. Login → Klik icon User → "Alamat Saya"
2. Klik "Tambah Alamat Baru"
3. Isi form:
   - Label: Rumah/Kantor/Lainnya
   - Nama Penerima
   - Nomor HP Penerima
   - Alamat Lengkap
   - Catatan (opsional)
4. **PENTING**: Klik/drag marker di peta untuk set lokasi
5. Centang "Jadikan alamat utama"
6. Klik "Simpan"

**Tambah Alamat Tambahan:**
1. Ulangi langkah di atas
2. Bisa punya banyak alamat

**Edit Alamat:**
1. Klik menu dropdown (3 titik)
2. Pilih "Edit"
3. Ubah data
4. Simpan

**Set Alamat Utama:**
1. Klik menu dropdown
2. Pilih "Jadikan Utama"

**Hapus Alamat:**
1. Klik menu dropdown
2. Pilih "Hapus"
3. Konfirmasi

### E. Checkout & Pemesanan

**Proses Checkout:**

**Step 1: Pilih Alamat**
1. Pilih salah satu alamat yang sudah disimpan
2. Atau tambah alamat baru

**Step 2: Metode Pengiriman**
- Sistem otomatis hitung jarak
- Pilih metode yang tersedia:
  - **Kurir Toko** (jika ≤10km): Rp 15.000
  - **Ekspedisi** (jika >10km): Rp 25.000
  - **Pick Up** (selalu tersedia): Gratis

**Step 3: Jadwal Pengiriman**
1. Pilih tanggal (minimal H+2)
   - Contoh: Pesan tgl 10 → Kirim minimal tgl 12
2. Pilih waktu (jika Kurir Toko atau Pick Up):
   - Kurir Toko: 09.00-12.00, 13.00-16.00, 17.00-20.00
   - Pick Up: 10.00-12.00, 13.00-15.00, 16.00-18.00

⚠️ **Perhatikan kuota**: Jika tanggal abu-abu = kuota penuh

**Step 4: Metode Pembayaran**
Pilih salah satu:
- DANA
- Bank Jago
- SeaBank
- COD (hanya untuk Kurir Toko & Pick Up)

**Step 5: Catatan (Opsional)**
- Tambahkan catatan khusus untuk pesanan

**Step 6: Review & Konfirmasi**
1. Cek ringkasan pesanan
2. Pastikan semua data benar
3. Klik "Buat Pesanan"

### F. Pembayaran

**Jika Pilih Transfer (DANA/Bank):**
1. Setelah checkout, akan redirect ke halaman pembayaran
2. Lihat detail transfer:
   - Nomor rekening/DANA
   - Atas nama
   - Jumlah yang harus dibayar
3. Lakukan transfer
4. Klik "Saya Sudah Transfer"
5. Tunggu konfirmasi admin

**Jika Pilih COD:**
- Status otomatis "Dibayar"
- Bayar saat pengiriman/pengambilan

**Lihat E-Receipt:**
1. Setelah pembayaran dikonfirmasi
2. Klik "Lihat E-Receipt"
3. Bisa di-print untuk bukti

### G. Tracking Pesanan

**Lihat Pesanan Saya:**
1. Klik menu "Pesanan Saya"
2. Lihat semua pesanan

**Filter Status:**
- Klik tab status untuk filter:
  - Menunggu Pembayaran
  - Dibayar
  - Diproses
  - Dikirim
  - Selesai

**Lihat Detail:**
1. Klik "Lihat Detail"
2. Lihat timeline pesanan
3. Lihat info lengkap

**Status Pesanan:**
- 🟡 **Menunggu Pembayaran**: Segera bayar
- 🔵 **Dibayar**: Pesanan akan diproses
- 🟢 **Diproses**: Sedang disiapkan
- 🚚 **Dikirim**: Dalam pengiriman
- ✅ **Selesai**: Pesanan selesai

### H. Review & Rating

**Beri Ulasan:**
1. Tunggu status pesanan "Selesai"
2. Klik "Beri Ulasan" di halaman pesanan
3. Pilih produk yang ingin diulas
4. Beri rating (1-5 bintang)
5. Tulis komentar
6. Klik "Kirim Ulasan"

**Lihat Ulasan:**
- Klik menu "Ulasan" di navbar
- Lihat semua ulasan pelanggan

### I. Chat Customer Service

**Mulai Chat:**
1. Login terlebih dahulu
2. Klik "Chat CS" di navbar
3. Sistem otomatis buat percakapan baru
4. Lihat pesan sambutan dari sistem

**Kirim Pesan:**
1. Ketik pertanyaan di textarea
2. Tekan Enter atau klik tombol kirim
3. Tunggu balasan dari admin

**Tips Bertanya:**
- Tanyakan tentang produk
- Tanyakan status pesanan
- Tanyakan cara pemesanan
- Tanyakan metode pembayaran

### J. Profil & Pengaturan

**Update Profil:**
1. Klik icon User → "Profil Saya"
2. Ubah nama atau nomor HP
3. Klik "Simpan Perubahan"

**Ubah Password:**
1. Di halaman profil
2. Masukkan password lama
3. Masukkan password baru
4. Konfirmasi password baru
5. Klik "Simpan Perubahan"

---

## 5. TROUBLESHOOTING

### Masalah Umum & Solusi

**1. Tidak bisa login**
- Cek email dan password
- Gunakan fitur "Lupa Password"
- Pastikan sudah registrasi

**2. Gambar produk tidak muncul**
- Cek folder `assets/images/products` ada
- Cek permission folder (755)
- Cek path di `config/database.php`

**3. Google Maps tidak muncul**
- Tambahkan API Key di `addresses.php`
- Aktifkan billing di Google Cloud Console
- Cek console browser untuk error

**4. Email OTP tidak terkirim**
- Untuk demo, OTP ditampilkan di halaman
- Untuk production, setup PHPMailer
- Cek konfigurasi SMTP

**5. Checkout error "Kuota penuh"**
- Pilih tanggal lain
- Atau pilih metode pengiriman lain
- Admin bisa tambah kuota di pengaturan

**6. Metode pengiriman tidak muncul**
- Pastikan sudah pilih alamat
- Pastikan alamat punya koordinat GPS
- Cek radius kurir di pengaturan admin

**7. Chat tidak real-time**
- Sistem menggunakan polling (3 detik)
- Refresh halaman jika perlu
- Cek console browser untuk error

**8. Database error**
- Cek koneksi database di `config/database.php`
- Pastikan database sudah di-import
- Cek user MySQL punya akses

**9. Permission denied saat upload**
- Windows: Tidak perlu chmod
- Linux/Mac: `chmod 755 assets/images/products`

**10. Halaman blank/error 500**
- Cek `error_log` di folder root
- Aktifkan `display_errors` di php.ini
- Cek syntax error di file PHP

### Tips Penggunaan

**Untuk Admin:**
- Login setiap hari untuk cek pesanan baru
- Balas chat customer dengan cepat
- Update status pesanan secara berkala
- Cek laporan penjualan mingguan
- Backup database secara rutin

**Untuk Customer:**
- Simpan beberapa alamat untuk kemudahan
- Pesan minimal H+2 untuk ketersediaan
- Cek kuota sebelum pilih tanggal
- Simpan e-receipt sebagai bukti
- Beri ulasan setelah pesanan selesai

### Kontak Support

Jika masih ada masalah:
- Email: support@dflorist.com
- Phone: +62 812-3456-7890
- Chat: Gunakan fitur Chat CS di website

---

## 📚 Dokumentasi Tambahan

- **README.md** - Overview sistem
- **INSTALLATION.md** - Panduan instalasi detail
- **FEATURES.md** - Daftar lengkap fitur
- **REFERENCE.md** - Referensi Alfagift
- **COMPARISON.md** - Perbandingan dengan Alfagift
- **CHAT_FEATURE.md** - Dokumentasi fitur chat
- **CHANGELOG.md** - Riwayat perubahan

---

**Selamat menggunakan D'florist! 🌸**
