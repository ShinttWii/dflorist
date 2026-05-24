<?php
// Email Configuration and Functions
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Send email function
function sendEmail($to, $subject, $body, $isHTML = true) {
    // Check if PHPMailer is installed
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        error_log('PHPMailer not installed. Run: composer require phpmailer/phpmailer');
        return false;
    }
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $_ENV['SMTP_USERNAME'] ?? '';
        $mail->Password   = $_ENV['SMTP_PASSWORD'] ?? '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $_ENV['SMTP_PORT'] ?? 587;
        $mail->CharSet    = 'UTF-8';
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ];
        
        // Recipients
        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@dflorist.com', $_ENV['SMTP_FROM_NAME'] ?? "D'florist");
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML($isHTML);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        if ($isHTML) {
            $mail->AltBody = strip_tags($body);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Send OTP Email
function sendOTPEmail($email, $otp, $name = '') {
    $subject = "Kode Verifikasi D'florist";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FFD6E8; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: #FF69B4; margin: 0; font-size: 28px; }
            .content { background: #fff; padding: 30px; border: 2px solid #FFD6E8; border-radius: 0 0 10px 10px; }
            .otp-box { background: #FFD6E8; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0; }
            .otp-code { font-size: 32px; font-weight: bold; color: #FF69B4; letter-spacing: 5px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>D'florist</h1>
            </div>
            <div class='content'>
                <h2>Kode Verifikasi Email</h2>
                " . ($name ? "<p>Halo <strong>$name</strong>,</p>" : "<p>Halo,</p>") . "
                <p>Gunakan kode OTP berikut untuk verifikasi:</p>
                
                <div class='otp-box'>
                    <div class='otp-code'>$otp</div>
                </div>
                
                <p><strong>Kode ini berlaku selama 10 menit.</strong></p>
                <p>Jika Anda tidak merasa melakukan permintaan ini, abaikan email ini.</p>
                
                <div class='footer'>
                    <p>© " . date('Y') . " D'florist. All rights reserved.</p>
                    <p>Email ini dikirim otomatis, mohon tidak membalas.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

// Send Password Reset Email
function sendPasswordResetEmail($email, $resetToken, $name = '') {
    $resetLink = SITE_URL . "/reset_password.php?token=" . $resetToken;
    $subject = "Reset Password - D'florist";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FFD6E8; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: #FF69B4; margin: 0; font-size: 28px; }
            .content { background: #fff; padding: 30px; border: 2px solid #FFD6E8; border-radius: 0 0 10px 10px; }
            .button { display: inline-block; padding: 15px 30px; background: #FF69B4; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>D'florist</h1>
            </div>
            <div class='content'>
                <h2>Reset Password</h2>
                " . ($name ? "<p>Halo <strong>$name</strong>,</p>" : "<p>Halo,</p>") . "
                <p>Kami menerima permintaan untuk reset password akun Anda. Klik tombol di bawah untuk membuat password baru:</p>
                
                <div style='text-align: center;'>
                    <a href='$resetLink' class='button'>Reset Password</a>
                </div>
                
                <p>Atau copy link berikut ke browser:</p>
                <p style='word-break: break-all; color: #666;'>$resetLink</p>
                
                <p><strong>Link ini berlaku selama 1 jam.</strong></p>
                <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                
                <div class='footer'>
                    <p>© " . date('Y') . " D'florist. All rights reserved.</p>
                    <p>Email ini dikirim otomatis, mohon tidak membalas.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}

// Send Order Confirmation Email
function sendOrderConfirmationEmail($email, $orderNumber, $name, $total) {
    $subject = "Konfirmasi Pesanan #$orderNumber - D'florist";
    
    $body = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #FFD6E8; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .header h1 { color: #FF69B4; margin: 0; font-size: 28px; }
            .content { background: #fff; padding: 30px; border: 2px solid #FFD6E8; border-radius: 0 0 10px 10px; }
            .order-box { background: #FFD6E8; padding: 20px; border-radius: 10px; margin: 20px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>D'florist</h1>
            </div>
            <div class='content'>
                <h2>Terima Kasih atas Pesanan Anda!</h2>
                <p>Halo <strong>$name</strong>,</p>
                <p>Pesanan Anda telah kami terima dan sedang diproses.</p>
                
                <div class='order-box'>
                    <p><strong>Nomor Pesanan:</strong> $orderNumber</p>
                    <p><strong>Total Pembayaran:</strong> Rp " . number_format($total, 0, ',', '.') . "</p>
                </div>
                
                <p>Anda dapat melihat detail pesanan di halaman <a href='" . SITE_URL . "/orders.php'>Pesanan Saya</a>.</p>
                <p>Kami akan mengirimkan notifikasi jika ada update status pesanan.</p>
                
                <div class='footer'>
                    <p>© " . date('Y') . " D'florist. All rights reserved.</p>
                    <p>Butuh bantuan? Hubungi kami melalui Chat CS</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    return sendEmail($email, $subject, $body);
}
?>
