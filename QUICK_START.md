# 🚀 Quick Start Guide - D'florist

Panduan cepat untuk mulai menggunakan sistem dalam 10 menit!

## ⚡ Setup Cepat (5 Menit)

### 1. Import Database
```bash
mysql -u root -p
CREATE DATABASE dflorist;
exit;
mysql -u root -p dflorist < database.sql
```

### 2. Edit Konfigurasi
File: `config/database.php`
```php
define('DB_USER', 'root');        // User MySQL Anda
define('DB_PASS', '');            // Password MySQL Anda
define('SITE_URL', 'http://localhost/dflorist');
```

### 3. Buat Folder Upload
```bash
mkdir assets/images/products
```

### 4. Akses Sistem
- Frontend: http://localhost/dflorist
- Admin: http://localhost/dflorist/admin

---

## 👨‍💼 Admin Quick Start (5 Menit)

### Login Admin
```
URL: http://localhost/dflorist/admin
Email: admin@dflorist.com
Password: admin123
```

### Setup Awal (Wajib!)

**1. Ubah Password Admin**
- Gunakan fitur "Lupa Password" dengan OTP

**2. Konfigurasi Toko**
- Menu: Pengaturan
- Set koordinat toko (Google Maps)
- Set radius kurir: 10 km
- Set minimal pre-order: 2 hari
- Set kuota: 5 per hari

**3. Tambah Produk (Minimal 3)**
- Menu: Produk → Tambah Produk
- Isi: Nama, Deskripsi, Kategori, Harga, Stok
- Upload gambar
- Simpan

**4. Test Pesanan**
- Buat akun customer
- Pesan produk
- Cek di menu Pesanan admin

---

## 🛍️ Customer Quick Start (5 Menit)

### 1. Registrasi
```
URL: http://localhost/dflorist
Klik: Icon User → Daftar
Isi: Nama, Email, HP, Password
```

### 2. Tambah Alamat
```
Menu: Icon User → Alamat Saya
Klik: Tambah Alamat Baru
PENTING: Klik marker di peta untuk set lokasi GPS!
```

### 3. Belanja
```
1. Browse produk
2. Klik "Tambah ke Keranjang"
3. Lihat keranjang
4. Klik "Lanjut ke Checkout"
```

### 4. Checkout
```
1. Pilih alamat
2. Pilih metode pengiriman (otomatis muncul berdasarkan jarak)
3. Pilih tanggal (minimal H+2)
4. Pilih waktu (jika perlu)
5. Pilih pembayaran
6. Buat Pesanan
```

### 5. Bayar
```
- Transfer sesuai instruksi
- Klik "Saya Sudah Transfer"
- Atau pilih COD (bayar saat terima)
```

### 6. Track Pesanan
```
Menu: Pesanan Saya
Lihat status: Menunggu Pembayaran → Dibayar → Diproses → Dikirim → Selesai
```

### 7. Review
```
Setelah status "Selesai":
- Klik "Beri Ulasan"
- Rating 1-5 bintang
- Tulis komentar
- Kirim
```

---

## 💬 Chat Customer Service

### Customer:
```
1. Login
2. Klik "Chat CS" di navbar
3. Ketik pertanyaan
4. Tekan Enter
5. Tunggu balasan admin
```

### Admin:
```
1. Login admin
2. Menu: Chat CS
3. Klik percakapan (badge merah = unread)
4. Baca pesan customer
5. Ketik balasan
6. Tekan Enter
```

---

## 📊 Laporan Penjualan

### Cara Cepat:
```
1. Login admin
2. Menu: Laporan
3. Pilih jenis: Harian/Bulanan/Per Produk
4. Pilih periode
5. Klik "Tampilkan Laporan"
6. Export CSV atau Print
```

---

## 🔧 Troubleshooting Cepat

### Gambar tidak muncul?
```bash
# Cek folder ada
ls assets/images/products

# Buat jika belum ada
mkdir assets/images/products
```

### Google Maps tidak muncul?
```
1. Buka addresses.php
2. Cari: YOUR_GOOGLE_MAPS_API_KEY
3. Ganti dengan API key Anda
4. Get API key: https://console.cloud.google.com/
```

### Database error?
```
1. Cek config/database.php
2. Pastikan user/password benar
3. Pastikan database sudah di-import
```

### Checkout error "Kuota penuh"?
```
1. Pilih tanggal lain
2. Atau admin tambah kuota di Pengaturan
```

---

## 📱 Testing Checklist

### Admin:
- [ ] Login admin
- [ ] Ubah password
- [ ] Set pengaturan toko
- [ ] Tambah 3 produk
- [ ] Cek dashboard
- [ ] Test chat CS
- [ ] Lihat laporan

### Customer:
- [ ] Registrasi akun
- [ ] Tambah alamat (dengan GPS!)
- [ ] Browse produk
- [ ] Tambah ke keranjang
- [ ] Checkout
- [ ] Pilih metode pengiriman
- [ ] Bayar
- [ ] Track pesanan
- [ ] Chat CS
- [ ] Beri review

---

## 🎯 Next Steps

Setelah quick start, pelajari lebih lanjut:

1. **USER_GUIDE.md** - Panduan lengkap semua fitur
2. **FEATURES.md** - Daftar lengkap fitur
3. **INSTALLATION.md** - Setup production
4. **CHAT_FEATURE.md** - Detail fitur chat

---

## 💡 Tips

**Admin:**
- Cek pesanan baru setiap hari
- Balas chat customer cepat
- Update status pesanan berkala
- Backup database mingguan

**Customer:**
- Simpan beberapa alamat
- Pesan H+2 untuk ketersediaan
- Cek kuota sebelum pilih tanggal
- Simpan e-receipt

---

## 📞 Butuh Bantuan?

- **Email**: support@dflorist.com
- **Phone**: +62 812-3456-7890
- **Chat**: Gunakan fitur Chat CS
- **Docs**: Lihat USER_GUIDE.md

---

**Happy Selling! 🌸**

Sistem siap digunakan dalam 10 menit!
