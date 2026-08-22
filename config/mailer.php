<?php
/**
 * Core SMTP Mailer Service - PT Jaya Teknis
 * Path: config/mailer.php
 * Standalone Engine: PHPMailer 7.1.1 (Native / No Composer Required)
 */

require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('sendSmtpEmail')) {
    /**
     * Kirim Email via PHPMailer 7.1.1 menggunakan Server SMTP aktif dari tabel database smtp_server
     * 
     * @param mysqli $conn
     * @param string $toEmail
     * @param string $toName
     * @param string $subject
     * @param string $htmlBody
     * @return array [success => bool, message => string]
     */
    function sendSmtpEmail($conn, $toEmail, $toName, $subject, $htmlBody) {
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Alamat email penerima tidak valid.'];
        }

        // 1. Ambil Server SMTP Aktif yang memiliki sisa kuota
        $stmt = $conn->prepare("SELECT id_stmp, nama_provider, stmp_server, port, user_login, password, limit_harian, sisa_harian 
                                FROM smtp_server 
                                WHERE aktif = 1 AND (sisa_harian > 0 OR sisa_harian IS NULL) 
                                ORDER BY id_stmp ASC LIMIT 1");
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$res || $res->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Tidak ada Server SMTP aktif yang memiliki sisa kuota harian. Hubungi administrator.'];
        }

        $smtp = $res->fetch_assoc();
        $stmt->close();

        $idSmtp = (int)$smtp['id_stmp'];
        $host = trim($smtp['stmp_server']);
        $port = (int)$smtp['port'];
        $user = trim($smtp['user_login']);
        $pass = $smtp['password'];

        // Tentukan From Email & From Name resmi
        $fromEmail = 'info@jayateknis.com';
        $fromName = 'PT Jaya Teknis System';

        // Jika Brevo atau relay provider yang memerlukan verified sender
        if (strpos($host, 'brevo.com') !== false || strpos($user, '@smtp-brevo.com') !== false) {
            $fromEmail = 'shem1990@gmail.com';
        } elseif (filter_var($user, FILTER_VALIDATE_EMAIL)) {
            $fromEmail = $user;
        }

        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->SMTPDebug   = SMTP::DEBUG_OFF;
            $mail->isSMTP();
            $mail->Host        = $host;
            $mail->SMTPAuth    = true;
            $mail->Username    = $user;
            $mail->Password    = $pass;

            if ($port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port        = $port;
            $mail->CharSet     = 'UTF-8';
            $mail->Timeout     = 15;

            // Recipients
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($toEmail, $toName);
            $mail->addReplyTo($fromEmail, $fromName);

            // Content
            $mail->isHTML(true);
            $mail->Subject     = $subject;
            $mail->Body        = $htmlBody;
            $mail->AltBody     = strip_tags($htmlBody);

            $mail->send();

            // Potong sisa kuota harian SMTP
            $conn->query("UPDATE smtp_server SET sisa_harian = GREATEST(0, sisa_harian - 1) WHERE id_stmp = $idSmtp");

            return ['success' => true, 'message' => "Email berhasil dikirim ke {$toEmail}"];
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Gagal mengirim email via PHPMailer: {$mail->ErrorInfo}"];
        }
    }
}

if (!function_exists('renderOtpEmailTemplate')) {
    /**
     * Template Email HTML Elegan untuk Kode OTP
     */
    function renderOtpEmailTemplate($nama, $otpCode, $expiresMinutes = 60) {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Kode Verifikasi Ganti Password</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 40px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 560px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 32px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;">PT JAYA TEKNIS</h1>
                                    <p style="margin: 6px 0 0 0; font-size: 13px; opacity: 0.85;">Web-Based Purchasing &amp; Logistics System</p>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 35px 30px; color: #1e293b;">
                                    <h2 style="margin: 0 0 12px 0; font-size: 18px; color: #0f2744; font-weight: 600;">Halo, ' . htmlspecialchars($nama) . '!</h2>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        Kami menerima permintaan perubahan kata sandi untuk akun Anda pada sistem <strong>PT Jaya Teknis</strong>. Gunakan kode verifikasi (OTP) berikut untuk menyelesaikan proses:
                                    </p>

                                    <!-- OTP Code Box -->
                                    <div style="background-color: #f8fafc; border: 2px dashed #0284c7; border-radius: 10px; padding: 22px 15px; text-align: center; margin: 25px 0;">
                                        <span style="font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 8px;">KODE VERIFIKASI OTP (6 DIGIT)</span>
                                        <span style="font-family: \'Courier New\', Courier, monospace; font-size: 36px; font-weight: 800; color: #0284c7; letter-spacing: 8px; display: inline-block;">' . htmlspecialchars($otpCode) . '</span>
                                    </div>

                                    <!-- Expiration & Security Info -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #eff6ff; border-radius: 8px; padding: 12px 16px; margin-bottom: 25px;">
                                        <tr>
                                            <td style="font-size: 13px; color: #1e40af; line-height: 1.5;">
                                                ⏱️ Kode OTP ini berlaku selama <strong>' . $expiresMinutes . ' menit (1 Jam)</strong>. Jangan berikan kode ini kepada siapapun termasuk pihak teknisi/IT.
                                            </td>
                                        </tr>
                                    </table>

                                    <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #94a3b8;">
                                        Jika Anda tidak merasa melakukan permintaan ini, segera laporkan ke bagian IT &amp; Administrator untuk mengamankan akun Anda.
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 20px 30px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' PT Jaya Teknis. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pesan ini dikirim otomatis oleh sistem keamanan. Mohon tidak membalas email ini.
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        ';
    }
}
