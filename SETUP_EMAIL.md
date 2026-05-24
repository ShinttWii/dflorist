# Setup Email untuk OTP D'florist

## Pilihan 1: Menggunakan Gmail SMTP (Recommended untuk Testing)

### Langkah 1: Install PHPMailer
Jalankan command ini di terminal/cmd di folder project:
```bash
composer require phpmailer/phpmailer
```

Jika belum punya Composer, download di: https://getcomposer.org/download/

### Langkah 2: Setup Gmail App Password

1. Login ke Gmail kamu (dfloristreal@gmail.com)
2. Buka https://myaccount.google.com/security
3. Aktifkan "2-Step Verification" jika belum
4. Setelah aktif, cari "App passwords" atau "Sandi aplikasi"
5. Pilih "Mail" dan "Other (Custom name)"
6. Ketik "D'florist Website"
7. Klik "Generate"
8. Copy password 16 digit yang muncul (contoh: abcd efgh ijkl mnop)
9. Simpan password ini, akan digunakan di .env

### Langkah 3: Update file .env
Buka file `.env` (buat baru jika belum ada) dan tambahkan:

```
# Email Configuration
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=dfloristreal@gmail.com
SMTP_PASSWORD=abcd efgh ijkl mnop
SMTP_FROM_EMAIL=dfloristreal@gmail.com
SMTP_FROM_NAME=D'florist
```

Ganti `SMTP_PASSWORD` dengan App Password yang kamu generate tadi.

### Langkah 4: Test Email
Setelah setup, test dengan mengakses:
```
http://localhost/c-commerce_dflorist/test_email.php
```

---

## Pilihan 2: Menggunakan Mailtrap (Untuk Testing Tanpa Kirim Email Real)

### Setup Mailtrap:
1. Daftar gratis di https://mailtrap.io
2. Buat inbox baru
3. Copy credentials SMTP
4. Update .env:

```
SMTP_HOST=sandbox.smtp.mailtrap.io
SMTP_PORT=2525
SMTP_USERNAME=your_mailtrap_username
SMTP_PASSWORD=your_mailtrap_password
SMTP_FROM_EMAIL=noreply@dflorist.com
SMTP_FROM_NAME=D'florist
```

---

## Pilihan 3: Menggunakan Server SMTP Hosting (Untuk Production)

Jika sudah punya hosting dengan email (contoh: noreply@dflorist.com):

```
SMTP_HOST=mail.yourdomain.com
SMTP_PORT=587
SMTP_USERNAME=noreply@dflorist.com
SMTP_PASSWORD=your_email_password
SMTP_FROM_EMAIL=noreply@dflorist.com
SMTP_FROM_NAME=D'florist
```

---

## Troubleshooting

### Error: "Could not authenticate"
- Pastikan App Password Gmail benar (bukan password Gmail biasa)
- Pastikan 2-Step Verification sudah aktif di Gmail

### Error: "Connection timeout"
- Cek koneksi internet
- Coba ganti port 587 ke 465 dan ubah encryption ke 'ssl'

### Email masuk ke Spam
- Gunakan domain email yang sama dengan website
- Setup SPF dan DKIM records di DNS hosting

### Email tidak terkirim sama sekali
- Cek error log di test_email.php
- Pastikan PHP extension `openssl` aktif
- Cek firewall tidak block port 587/465

---

## Keamanan

⚠️ **PENTING:**
- Jangan commit file `.env` ke Git
- Tambahkan `.env` ke `.gitignore`
- Gunakan App Password, bukan password Gmail asli
- Untuk production, gunakan email hosting profesional

---

## Setelah Setup Berhasil

Fitur yang akan berfungsi:
1. ✅ Verifikasi email saat registrasi
2. ✅ Forgot password / reset password
3. ✅ Notifikasi order ke customer
4. ✅ Notifikasi order ke admin

---

## Butuh Bantuan?

Jika masih error, screenshot error message dan kirim ke developer.
