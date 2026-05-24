# Panduan Instalasi D'florist

## Persyaratan Sistem

- PHP 8.0 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi / MariaDB 10.3+
- Apache/Nginx Web Server
- Composer (opsional, untuk PHPMailer)

## Langkah Instalasi

### 1. Persiapan Database

```bash
# Login ke MySQL
mysql -u root -p

# Buat database
CREATE DATABASE dflorist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Import schema
mysql -u root -p dflorist < database.sql

# Atau via phpMyAdmin:
# - Buat database 'dflorist'
# - Import file database.sql
```

### 2. Konfigurasi Aplikasi

Edit file `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Sesuaikan dengan user MySQL Anda
define('DB_PASS', '');              // Sesuaikan dengan password MySQL Anda
define('DB_NAME', 'dflorist');

define('SITE_URL', 'http://localhost/dflorist');  // Sesuaikan dengan URL Anda
```

### 3. Buat Folder Upload

```bash
# Linux/Mac
mkdir -p assets/images/products
chmod 755 assets/images/products

# Windows (via Command Prompt)
mkdir assets\images\products
```

### 4. Konfigurasi Google Maps API

1. Dapatkan API Key dari [Google Cloud Console](https://console.cloud.google.com/)
2. Enable APIs:
   - Maps JavaScript API
   - Geocoding API
3. Edit file `addresses.php` (line ~200):
   ```javascript
   <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
   ```

### 5. Konfigurasi Email (Opsional)

Install PHPMailer via Composer:

```bash
composer require phpmailer/phpmailer
```

Edit `includes/functions.php`, update fungsi `sendEmail()`:

```php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('noreply@dflorist.com', 'D\'Florist');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}
```

### 6. Akses Aplikasi

- **Frontend**: http://localhost/dflorist
- **Admin Panel**: http://localhost/dflorist/admin

### 7. Login Default

**Admin:**
- Email: admin@dflorist.com
- Password: admin123

**PENTING**: Segera ubah password admin setelah login pertama!

## Troubleshooting

### Error: "Access denied for user"
- Periksa username dan password MySQL di `config/database.php`
- Pastikan user memiliki akses ke database

### Error: "Call to undefined function imagecreatefromjpeg()"
- Install PHP GD extension:
  ```bash
  # Ubuntu/Debian
  sudo apt-get install php-gd
  
  # CentOS/RHEL
  sudo yum install php-gd
  ```

### Upload gambar gagal
- Periksa permission folder `assets/images/products`
- Pastikan `upload_max_filesize` di php.ini cukup besar (min 10MB)

### Google Maps tidak muncul
- Periksa API Key sudah benar
- Pastikan billing sudah diaktifkan di Google Cloud Console
- Periksa console browser untuk error

### Email tidak terkirim
- Pastikan PHPMailer sudah terinstall
- Periksa konfigurasi SMTP
- Untuk Gmail, gunakan App Password, bukan password biasa
- Periksa error log di `error_log` atau console

## Konfigurasi Production

### 1. Security

Edit `.htaccess`, uncomment baris HTTPS redirect:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

### 2. PHP Configuration

Edit `php.ini`:
```ini
display_errors = Off
log_errors = On
error_log = /path/to/error.log
upload_max_filesize = 10M
post_max_size = 10M
```

### 3. Database Backup

Setup cron job untuk backup otomatis:
```bash
# Backup setiap hari jam 2 pagi
0 2 * * * mysqldump -u root -p'password' dflorist > /backup/dflorist_$(date +\%Y\%m\%d).sql
```

### 4. File Permissions

```bash
# Set permission yang aman
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 600 config/database.php
```

## Update Aplikasi

1. Backup database dan files
2. Download versi terbaru
3. Replace files (kecuali config dan uploads)
4. Run migration SQL jika ada
5. Clear cache browser

## Support

Untuk bantuan lebih lanjut, hubungi:
- Email: support@dflorist.com
- Phone: +62 812-3456-7890

## Referensi
Sistem ini mengadopsi best practices dari **Alfagift** (alfagift.id). Lihat file REFERENCE.md untuk detail lengkap tentang fitur yang diadopsi dan perbedaannya.
