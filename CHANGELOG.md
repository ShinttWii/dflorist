# Changelog - D'florist E-Commerce

## Sistem OTP untuk Lupa Password

### Fitur OTP Email
Sistem OTP (One-Time Password) digunakan untuk proses reset password, baik untuk customer maupun admin.

#### Cara Kerja:
1. **Step 1 - Input Email**: User memasukkan email yang terdaftar
2. **Step 2 - Verifikasi OTP**: Sistem mengirim kode OTP 6 digit ke email
3. **Step 3 - Reset Password**: Setelah OTP valid, user bisa set password baru
4. **Step 4 - Success**: Password berhasil diubah, redirect ke login

#### Spesifikasi OTP:
- Format: 6 digit angka (000000 - 999999)
- Masa berlaku: 10 menit
- Auto-delete setelah digunakan atau expired
- Fitur resend OTP jika belum diterima
- Terpisah untuk customer dan admin (user_type)

#### File Terkait:
- `forgot_password.php` - Lupa password customer
- `admin/forgot_password.php` - Lupa password admin
- Tabel `password_resets` di database

#### Database Schema:
```sql
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,  -- Berisi OTP 6 digit
    user_type ENUM('customer', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL
);
```

#### Keamanan:
- OTP hanya berlaku 10 menit
- OTP dihapus setelah berhasil digunakan
- Validasi di backend untuk mencegah brute force
- Session-based verification untuk multi-step process

---

## Fitur Lengkap yang Sudah Diimplementasikan

### ✅ Payment Gateway
- Simulasi pembayaran untuk DANA, Bank Jago, SeaBank
- COD (Cash on Delivery) untuk Kurir Toko & Pick Up
- Konfirmasi pembayaran otomatis
- E-Receipt setelah pembayaran berhasil
- Ready untuk integrasi Midtrans/Xendit

### ✅ Laporan Penjualan
- **Laporan Harian**: Total pesanan dan pendapatan per hari
- **Laporan Bulanan**: Trend penjualan per bulan
- **Laporan Per Produk**: Best seller dan total pendapatan per produk
- Filter berdasarkan periode (tanggal mulai - akhir)
- Summary statistik lengkap
- Export ke CSV
- Print-friendly format

### ✅ Email OTP untuk Lupa Password
- OTP 6 digit dengan masa berlaku 10 menit
- 3 step verification process
- Resend OTP functionality
- Auto-delete setelah digunakan
- Terpisah untuk customer dan admin

---

## File Structure

### Customer Files:
```
├── index.php                  # Beranda
├── products.php               # Daftar produk
├── product_detail.php         # Detail produk
├── cart.php                   # Keranjang
├── checkout.php               # Checkout
├── payment.php                # Pembayaran
├── receipt.php                # E-Receipt
├── orders.php                 # Riwayat pesanan
├── order_detail.php           # Detail pesanan
├── order_success.php          # Konfirmasi pesanan
├── reviews.php                # Semua ulasan
├── review.php                 # Beri ulasan
├── login.php                  # Login
├── register.php               # Registrasi
├── logout.php                 # Logout
├── profile.php                # Profil
├── addresses.php              # Manajemen alamat
└── forgot_password.php        # Lupa password dengan OTP
```

### Admin Files:
```
admin/
├── dashboard.php              # Dashboard
├── products.php               # Manajemen produk
├── orders.php                 # Manajemen pesanan
├── reports.php                # Laporan penjualan
├── settings.php               # Pengaturan sistem
├── login.php                  # Login admin
├── logout.php                 # Logout admin
└── forgot_password.php        # Lupa password admin dengan OTP
```

### API Files:
```
api/
└── process_payment.php        # API proses pembayaran
```

---

## Cara Menggunakan Sistem OTP

### Untuk Development (Demo Mode):
Saat ini sistem menampilkan OTP di halaman untuk kemudahan testing:
```
Kode OTP telah dikirim ke email Anda. (Demo: 123456)
```

### Untuk Production:
1. Install PHPMailer:
   ```bash
   composer require phpmailer/phpmailer
   ```

2. Konfigurasi SMTP di `includes/functions.php`:
   ```php
   function sendEmail($to, $subject, $message) {
       $mail = new PHPMailer(true);
       $mail->isSMTP();
       $mail->Host = 'smtp.gmail.com';
       $mail->SMTPAuth = true;
       $mail->Username = 'your-email@gmail.com';
       $mail->Password = 'your-app-password';
       $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
       $mail->Port = 587;
       
       $mail->setFrom('noreply@dflorist.com', 'D\'Florist');
       $mail->addAddress($to);
       $mail->Subject = $subject;
       $mail->Body = $message;
       
       return $mail->send();
   }
   ```

3. Update file `forgot_password.php` dan `admin/forgot_password.php`:
   - Uncomment baris `sendEmail()`
   - Hapus atau comment baris yang menampilkan OTP di halaman

---

## Testing Checklist

### OTP System:
- [ ] Request OTP dengan email valid
- [ ] Request OTP dengan email tidak terdaftar
- [ ] Verifikasi OTP yang benar
- [ ] Verifikasi OTP yang salah
- [ ] Verifikasi OTP yang sudah expired (>10 menit)
- [ ] Resend OTP
- [ ] Reset password setelah OTP valid
- [ ] Coba akses step 3 tanpa verifikasi OTP

### Payment Gateway:
- [ ] Pembayaran dengan DANA
- [ ] Pembayaran dengan Bank Jago
- [ ] Pembayaran dengan SeaBank
- [ ] Pembayaran dengan COD
- [ ] COD tidak tersedia untuk Ekspedisi
- [ ] View E-Receipt setelah bayar

### Laporan Penjualan:
- [ ] Laporan Harian dengan filter tanggal
- [ ] Laporan Bulanan dengan filter periode
- [ ] Laporan Per Produk
- [ ] Filter per produk spesifik
- [ ] Export ke CSV
- [ ] Print laporan

---

## Version History

### v1.0.0 (Current)
- ✅ Sistem e-commerce lengkap
- ✅ Pre-order dengan H+2
- ✅ 3 metode pengiriman
- ✅ Payment gateway simulation
- ✅ OTP untuk lupa password
- ✅ Laporan penjualan lengkap
- ✅ E-Receipt
- ✅ Review & Rating
- ✅ Multi-address dengan Google Maps
- ✅ Admin panel lengkap

### Future Enhancements (Roadmap):
- [ ] Real payment gateway integration (Midtrans/Xendit)
- [ ] Email notification automation
- [ ] SMS OTP sebagai alternatif
- [ ] Push notification untuk mobile
- [ ] Loyalty program & points
- [ ] Voucher & discount system
- [ ] Live chat support
- [ ] Advanced analytics dashboard
- [ ] Mobile app (React Native/Flutter)
- [ ] API untuk third-party integration

---

## Support & Contact

Untuk pertanyaan atau bantuan:
- Email: support@dflorist.com
- Phone: +62 812-3456-7890
- Documentation: README.md, INSTALLATION.md, FEATURES.md, REFERENCE.md

## Referensi
Sistem ini mengadopsi best practices dari **Alfagift** (alfagift.id) dengan penyesuaian untuk kebutuhan toko bunga. Lihat REFERENCE.md untuk detail lengkap.
