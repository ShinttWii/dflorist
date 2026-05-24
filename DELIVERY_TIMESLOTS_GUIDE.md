# Panduan Manajemen Slot Waktu Pengiriman

## Setup Database

Jalankan SQL berikut untuk membuat tabel dan data default:

```sql
-- Jalankan file: create_delivery_timeslots_table.sql
```

Atau jalankan query ini di phpMyAdmin:

```sql
CREATE TABLE IF NOT EXISTS `delivery_timeslots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `time_slot` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `delivery_timeslots` (`time_slot`, `is_active`, `sort_order`) VALUES
('09.00 - 12.00 WIB', 1, 1),
('12.00 - 15.00 WIB', 1, 2),
('15.00 - 18.00 WIB', 1, 3);
```

## Cara Menggunakan

### 1. Akses Halaman Pengaturan
- Login sebagai admin
- Buka menu **Pengaturan** di sidebar
- Scroll ke bagian **Slot Waktu Pengiriman**

### 2. Menambah Slot Waktu Baru
- Klik tombol **Tambah Slot**
- Masukkan slot waktu dengan format: `HH.MM - HH.MM WIB`
- Contoh: `18.00 - 21.00 WIB`
- Klik **Simpan**

### 3. Menonaktifkan Slot Waktu
- Klik tombol toggle (icon toggle) pada slot yang ingin dinonaktifkan
- Slot akan tetap ada di database tapi tidak ditampilkan ke customer
- Klik lagi untuk mengaktifkan kembali

### 4. Menghapus Slot Waktu
- Klik tombol hapus (icon trash) pada slot yang ingin dihapus
- Konfirmasi penghapusan
- Slot akan dihapus permanen dari database

## Format Slot Waktu

Format yang direkomendasikan:
- `09.00 - 12.00 WIB`
- `12.00 - 15.00 WIB`
- `15.00 - 18.00 WIB`
- `18.00 - 21.00 WIB`

**Catatan:**
- Gunakan format 24 jam
- Tambahkan "WIB" di akhir
- Gunakan tanda hubung (-) untuk memisahkan waktu mulai dan selesai

## Integrasi dengan Checkout

Slot waktu yang aktif akan otomatis muncul di halaman checkout untuk:
- **Kurir Toko**: Customer memilih slot waktu pengiriman
- **Pick Up**: Customer memilih slot waktu pengambilan

Slot waktu **TIDAK** ditampilkan untuk metode **Ekspedisi** karena waktu pengiriman ditentukan oleh pihak ekspedisi.

## Troubleshooting

### Slot waktu tidak muncul di checkout
1. Pastikan tabel `delivery_timeslots` sudah dibuat
2. Pastikan ada slot waktu yang aktif (`is_active = 1`)
3. Cek console browser untuk error JavaScript
4. Pastikan file `api/get_timeslots.php` dapat diakses

### Error saat menambah/hapus slot
1. Cek koneksi database
2. Pastikan user database punya permission INSERT/DELETE
3. Cek error log PHP

## File yang Terlibat

- `create_delivery_timeslots_table.sql` - SQL untuk membuat tabel
- `admin/settings.php` - Halaman manajemen slot waktu
- `api/get_timeslots.php` - API endpoint untuk mengambil slot waktu
- `checkout.php` - Halaman checkout yang menampilkan slot waktu
