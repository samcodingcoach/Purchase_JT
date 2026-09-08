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

if (!function_exists('getLogistikApproverEmails')) {
    /**
     * Mengambil daftar email dan nama tim Logistik aktif untuk approval RO tahap 1
     * 
     * @param mysqli $conn
     * @return array Array of ['email' => string, 'nama' => string, 'role' => string]
     */
    function getLogistikApproverEmails($conn) {
        $recipients = [];
        $addedEmails = [];

        // 1. Ambil Karyawan aktif di Divisi Logistik / Jabatan Logistik
        $sql = "SELECT k.id_karyawan, k.nama_karyawan, k.email, j.nama_jabatan, d.nama_divisi, k.id_divisi, k.id_jabatan
                FROM karyawan k
                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                WHERE k.aktif = 1 AND k.email IS NOT NULL AND k.email != ''";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $email = strtolower(trim($row['email']));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || in_array($email, $addedEmails)) {
                    continue;
                }

                $idDiv = (int)($row['id_divisi'] ?? 0);
                $idJab = (int)($row['id_jabatan'] ?? 0);
                $divLower = strtolower($row['nama_divisi'] ?? '');
                $jabLower = strtolower($row['nama_jabatan'] ?? '');

                // Hanya Logistik
                $isLogistik = (
                    $idDiv === 2 ||
                    $idJab === 2 ||
                    strpos($divLower, 'logistik') !== false ||
                    strpos($jabLower, 'logistik') !== false
                );

                if ($isLogistik) {
                    $recipients[] = [
                        'email' => $row['email'],
                        'nama'  => $row['nama_karyawan'],
                        'role'  => $row['nama_jabatan'] ?: 'Logistik & Gudang'
                    ];
                    $addedEmails[] = $email;
                }
            }
        }

        // 2. Ambil User admin dengan email valid jika ada
        try {
            $resU = $conn->query("SELECT id_users, nama_users, email FROM users WHERE aktif = 1 AND email IS NOT NULL AND email != ''");
            if ($resU) {
                while ($u = $resU->fetch_assoc()) {
                    $email = strtolower(trim($u['email']));
                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $addedEmails)) {
                        // User admin default
                    }
                }
            }
        } catch (\Throwable $e) {}

        return $recipients;
    }
}

if (!function_exists('getPurchasingEmails')) {
    /**
     * Mengambil daftar email dan nama tim Purchasing aktif untuk pemrosesan RO ke PO
     * 
     * @param mysqli $conn
     * @return array Array of ['email' => string, 'nama' => string, 'role' => string]
     */
    function getPurchasingEmails($conn) {
        $recipients = [];
        $addedEmails = [];

        // 1. Ambil Karyawan aktif di Divisi Purchasing / Jabatan Purchasing
        $sql = "SELECT k.id_karyawan, k.nama_karyawan, k.email, j.nama_jabatan, d.nama_divisi, k.id_divisi, k.id_jabatan
                FROM karyawan k
                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                WHERE k.aktif = 1 AND k.email IS NOT NULL AND k.email != ''";
        $res = $conn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $email = strtolower(trim($row['email']));
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || in_array($email, $addedEmails)) {
                    continue;
                }

                $idDiv = (int)($row['id_divisi'] ?? 0);
                $idJab = (int)($row['id_jabatan'] ?? 0);
                $divLower = strtolower($row['nama_divisi'] ?? '');
                $jabLower = strtolower($row['nama_jabatan'] ?? '');

                // Hanya Purchasing
                $isPurchasing = (
                    $idDiv === 3 ||
                    $idJab === 5 ||
                    strpos($divLower, 'purchasing') !== false ||
                    strpos($jabLower, 'purchasing') !== false ||
                    strpos($divLower, 'pengadaan') !== false
                );

                if ($isPurchasing) {
                    $recipients[] = [
                        'email' => $row['email'],
                        'nama'  => $row['nama_karyawan'],
                        'role'  => $row['nama_jabatan'] ?: 'Purchasing & Pengadaan'
                    ];
                    $addedEmails[] = $email;
                }
            }
        }

        return $recipients;
    }
}

if (!function_exists('getRoApproverEmails')) {
    /**
     * Alias backward-compatible: saat RO baru dibuat, hanya dikirimkan ke Logistik
     * 
     * @param mysqli $conn
     * @return array
     */
    function getRoApproverEmails($conn) {
        return getLogistikApproverEmails($conn);
    }
}

if (!function_exists('renderNewRoEmailTemplate')) {
    /**
     * Template Email HTML Elegan untuk Pemberitahuan Request Order Baru (Butuh Persetujuan)
     */
    function renderNewRoEmailTemplate($roData, $items, $pemohonData) {
        $nomorRo = htmlspecialchars($roData['nomor'] ?? '-');
        $tglRo = !empty($roData['tanggal_ro']) ? date('d/m/Y H:i', strtotime($roData['tanggal_ro'])) : date('d/m/Y H:i');
        $prioritas = strtoupper($roData['prioritas'] ?? 'NORMAL');
        $namaSite = htmlspecialchars($roData['nama_site'] ?? 'Semua Lokasi / Site Utama');
        $keterangan = !empty($roData['keterangan']) ? htmlspecialchars($roData['keterangan']) : '-';
        $namaPemohon = htmlspecialchars($pemohonData['nama_karyawan'] ?? 'Karyawan');
        $jabatanPemohon = htmlspecialchars($pemohonData['nama_jabatan'] ?? ($pemohonData['nama_divisi'] ?? 'Operasional'));
        $loginUrl = defined('BASE_URL') ? BASE_URL . '/admin/pages/request_order/index.php' : 'http://localhost/JT_Purchase/admin/pages/request_order/index.php';

        $badgeColor = ($prioritas === 'URGENT') ? '#dc2626' : '#2563eb';
        $badgeBg = ($prioritas === 'URGENT') ? '#fee2e2' : '#dbeafe';

        $itemsHtml = '';
        $no = 1;
        foreach ($items as $it) {
            $namaBarang = htmlspecialchars($it['nama_barang'] ?? '-');
            $qty = isset($it['qty']) ? (float)$it['qty'] : 0;
            $satuan = htmlspecialchars($it['satuan'] ?? 'PCS');
            $harga = isset($it['harga']) && (float)$it['harga'] > 0 ? 'Rp ' . number_format((float)$it['harga'], 0, ',', '.') : '-';

            $itemsHtml .= '
            <tr style="border-bottom: 1px solid #f1f5f9;">
                <td style="padding: 10px 12px; font-size: 13px; color: #64748b; text-align: center;">' . $no++ . '</td>
                <td style="padding: 10px 12px; font-size: 13px; color: #1e293b; font-weight: 500;">' . $namaBarang . '</td>
                <td style="padding: 10px 12px; font-size: 13px; color: #1e293b; text-align: center; font-weight: 600;">' . $qty . ' ' . $satuan . '</td>
                <td style="padding: 10px 12px; font-size: 13px; color: #64748b; text-align: right;">' . $harga . '</td>
            </tr>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Request Order Baru - Butuh Persetujuan</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 30px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 620px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 28px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">PT JAYA TEKNIS</h1>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.85;">Notifikasi Sistem Pengadaan &amp; Logistik</p>
                                </td>
                            </tr>

                            <!-- Status Badge Banner -->
                            <tr>
                                <td style="background-color: #eff6ff; padding: 14px 24px; border-bottom: 1px solid #dbeafe;">
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                        <tr>
                                            <td style="font-size: 13px; font-weight: 700; color: #1e40af;">
                                                📋 REQUEST ORDER BARU MASUK
                                            </td>
                                            <td style="text-align: right;">
                                                <span style="background-color: ' . $badgeBg . '; color: ' . $badgeColor . '; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 0.5px;">
                                                    ' . $prioritas . '
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 28px 24px; color: #1e293b;">
                                    <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                        Yth. <strong>Bapak/Ibu Approver &amp; Tim Pengadaan</strong>,
                                    </p>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        Terdapat pengajuan <strong>Request Order (RO)</strong> baru yang telah dikirimkan dan membutuhkan peninjauan / persetujuan Anda:
                                    </p>

                                    <!-- Summary Card -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px; font-size: 13px;">
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; width: 35%; border-bottom: 1px solid #e2e8f0;">Nomor RO</td>
                                            <td style="padding: 10px 14px; font-weight: 700; color: #0f2744; font-family: monospace; font-size: 14px; border-bottom: 1px solid #e2e8f0;">' . $nomorRo . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Tanggal Pengajuan</td>
                                            <td style="padding: 10px 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;">' . $tglRo . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Pemohon</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">' . $namaPemohon . ' <span style="font-weight: normal; color: #64748b;">(' . $jabatanPemohon . ')</span></td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Lokasi / Site</td>
                                            <td style="padding: 10px 14px; color: #1e293b; border-bottom: 1px solid #e2e8f0;">' . $namaSite . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b;">Catatan / Keterangan</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-style: italic;">' . $keterangan . '</td>
                                        </tr>
                                    </table>

                                    <!-- Tabel Item Material -->
                                    <div style="margin-bottom: 24px;">
                                        <div style="font-size: 13px; font-weight: 700; color: #0f2744; margin-bottom: 8px;">RINCIAN MATERIAL / BARANG:</div>
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                                            <thead>
                                                <tr style="background-color: #f1f5f9; text-align: left;">
                                                    <th style="padding: 8px 12px; font-size: 12px; color: #475569; width: 40px; text-align: center;">No</th>
                                                    <th style="padding: 8px 12px; font-size: 12px; color: #475569;">Nama Material / Barang</th>
                                                    <th style="padding: 8px 12px; font-size: 12px; color: #475569; text-align: center;">Qty</th>
                                                    <th style="padding: 8px 12px; font-size: 12px; color: #475569; text-align: right;">Est. Harga</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ' . $itemsHtml . '
                                            </tbody>
                                        </table>
                                    </div>

                                    <!-- Action Button CTA -->
                                    <div style="text-align: center; margin: 30px 0 10px 0;">
                                        <a href="' . $loginUrl . '" target="_blank" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 700; display: inline-block; box-shadow: 0 4px 10px rgba(2,132,199,0.3);">
                                            🔍 Buka &amp; Tinjau Request Order
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' PT Jaya Teknis. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pemberitahuan otomatis dari Sistem Pengadaan &amp; Pembelian PT Jaya Teknis.
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

if (!function_exists('renderRoStatusEmailTemplate')) {
    /**
     * Template Email HTML Elegan untuk Pemberitahuan Status Persetujuan RO kepada Pemohon
     */
    function renderRoStatusEmailTemplate($roData, $status, $approverName, $keterangan = '', $extraData = []) {
        $nomorRo = htmlspecialchars($roData['nomor'] ?? '-');
        $namaPemohon = htmlspecialchars($roData['nama_pemohon'] ?? 'Karyawan');
        $statusUpper = strtoupper(trim($status));
        $isApproved = (strpos($statusUpper, 'DISETUJUI') !== false && strpos($statusUpper, 'TIDAK') === false);
        $loginUrl = defined('BASE_URL') ? BASE_URL . '/admin/pages/request_order/index.php' : 'http://localhost/JT_Purchase/admin/pages/request_order/index.php';

        // Tentukan Banner, Judul, dan Pesan berdasarkan status spesifik
        if ($statusUpper === 'DISETUJUI PURCHASING') {
            $bannerBg = '#ecfdf5';
            $bannerBorder = '#a7f3d0';
            $bannerTextColor = '#065f46';
            $statusTitle = '🎉 REQUEST ORDER TELAH DITERBITKAN MENJADI PO';
            $statusText = 'DISETUJUI OLEH PURCHASING (PO RESMI TERBIT)';
            $roleLabel = 'Purchasing / Pengadaan';
            $introMsg = 'Kabar baik! Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah disetujui oleh tim Purchasing dan <strong>Purchase Order (PO)</strong> resmi telah diterbitkan.';
        } elseif ($statusUpper === 'TIDAK DISETUJUI PURCHASING') {
            $bannerBg = '#fef2f2';
            $bannerBorder = '#fecaca';
            $bannerTextColor = '#991b1b';
            $statusTitle = '❌ REQUEST ORDER TIDAK DISETUJUI PURCHASING';
            $statusText = 'TIDAK DISETUJUI / DITOLAK PURCHASING';
            $roleLabel = 'Purchasing / Pengadaan';
            $introMsg = 'Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah ditinjau oleh tim Purchasing dan dinyatakan <strong>tidak disetujui / ditolak</strong>.';
        } elseif ($statusUpper === 'DISETUJUI LOGISTIK') {
            $bannerBg = '#dcfce7';
            $bannerBorder = '#bbf7d0';
            $bannerTextColor = '#166534';
            $statusTitle = '✅ REQUEST ORDER TELAH DISETUJUI LOGISTIK';
            $statusText = 'DISETUJUI OLEH LOGISTIK (SIAP PROSES PO)';
            $roleLabel = 'Logistik & Gudang';
            $introMsg = 'Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah ditinjau dan <strong>disetujui oleh Logistik</strong>. Dokumen ini diteruskan ke tim Purchasing untuk penerbitan Purchase Order.';
        } elseif ($statusUpper === 'TIDAK DISETUJUI LOGISTIK') {
            $bannerBg = '#fee2e2';
            $bannerBorder = '#fecaca';
            $bannerTextColor = '#991b1b';
            $statusTitle = '❌ REQUEST ORDER TIDAK DISETUJUI LOGISTIK';
            $statusText = 'TIDAK DISETUJUI / DITOLAK LOGISTIK';
            $roleLabel = 'Logistik & Gudang';
            $introMsg = 'Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah ditinjau oleh tim Logistik dan dinyatakan <strong>tidak disetujui / ditolak</strong>.';
        } else {
            $bannerBg = $isApproved ? '#dcfce7' : '#fee2e2';
            $bannerBorder = $isApproved ? '#bbf7d0' : '#fecaca';
            $bannerTextColor = $isApproved ? '#166534' : '#991b1b';
            $statusTitle = $isApproved ? '✅ REQUEST ORDER TELAH DISETUJUI' : '❌ REQUEST ORDER TIDAK DISETUJUI';
            $statusText = htmlspecialchars($statusUpper);
            $roleLabel = 'Pihak Berwenang';
            $introMsg = 'Pengajuan <strong>Request Order (RO)</strong> Anda dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah ditinjau dengan status terbaru: <strong>' . htmlspecialchars($statusUpper) . '</strong>.';
        }

        // Info Tambahan PO & Vendor jika ada
        $nomorPo = htmlspecialchars($extraData['nomor_po'] ?? ($roData['nomor_po'] ?? ''));
        $namaVendor = htmlspecialchars($extraData['nama_vendor'] ?? ($roData['nama_vendor'] ?? ''));
        $poRowHtml = '';
        if (!empty($nomorPo)) {
            $poRowHtml .= '
            <tr>
                <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Nomor PO Terbit</td>
                <td style="padding: 10px 14px; font-weight: 700; color: #1d4ed8; font-family: monospace; font-size: 14px; border-bottom: 1px solid #e2e8f0;">' . $nomorPo . '</td>
            </tr>';
        }
        if (!empty($namaVendor)) {
            $poRowHtml .= '
            <tr>
                <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Vendor Rekanan</td>
                <td style="padding: 10px 14px; font-weight: 600; color: #1e293b; border-bottom: 1px solid #e2e8f0;">' . $namaVendor . '</td>
            </tr>';
        }

        // Row Catatan/Alasan jika ada
        $catatanRowHtml = '';
        if (!empty($keterangan)) {
            $catatanRowHtml = '
            <tr>
                <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Catatan / Alasan</td>
                <td style="padding: 10px 14px; color: #334155; font-style: italic; border-bottom: 1px solid #e2e8f0;">' . nl2br(htmlspecialchars($keterangan)) . '</td>
            </tr>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Pembaruan Status Request Order - ' . $nomorRo . '</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 30px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 28px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">PT JAYA TEKNIS</h1>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.85;">Pembaruan Status Request Order</p>
                                </td>
                            </tr>

                            <!-- Status Badge Banner -->
                            <tr>
                                <td style="background-color: ' . $bannerBg . '; padding: 14px 24px; border-bottom: 1px solid ' . $bannerBorder . ';">
                                    <span style="font-size: 13px; font-weight: 700; color: ' . $bannerTextColor . ';">
                                        ' . $statusTitle . '
                                    </span>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 28px 24px; color: #1e293b;">
                                    <p style="margin: 0 0 16px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                        Halo <strong>' . $namaPemohon . '</strong>,
                                    </p>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        ' . $introMsg . '
                                    </p>

                                    <!-- Summary Card -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px; font-size: 13px;">
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; width: 35%; border-bottom: 1px solid #e2e8f0;">Nomor RO</td>
                                            <td style="padding: 10px 14px; font-weight: 700; color: #0f2744; font-family: monospace; font-size: 14px; border-bottom: 1px solid #e2e8f0;">' . $nomorRo . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Status Terbaru</td>
                                            <td style="padding: 10px 14px; font-weight: 700; color: ' . $bannerTextColor . '; border-bottom: 1px solid #e2e8f0;">' . $statusText . '</td>
                                        </tr>
                                        ' . $poRowHtml . '
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Ditinjau Oleh</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($approverName) . ' <span style="font-weight: normal; color: #64748b;">(' . $roleLabel . ')</span></td>
                                        </tr>
                                        ' . $catatanRowHtml . '
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b;">Waktu Pembaruan</td>
                                            <td style="padding: 10px 14px; color: #1e293b;">' . date('d/m/Y H:i') . '</td>
                                        </tr>
                                    </table>

                                    <!-- Action Button CTA -->
                                    <div style="text-align: center; margin: 25px 0 10px 0;">
                                        <a href="' . $loginUrl . '" target="_blank" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-size: 14px; font-weight: 700; display: inline-block; box-shadow: 0 4px 10px rgba(15,39,68,0.25);">
                                            📋 Lihat Rincian Request Order
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' PT Jaya Teknis. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pemberitahuan otomatis dari Sistem Pengadaan &amp; Pembelian PT Jaya Teknis.
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

if (!function_exists('sendRoApprovalNotification')) {
    /**
     * Mengirim notifikasi email Request Order baru kepada seluruh Approver aktif
     * 
     * @param mysqli $conn
     * @param int $idRequest
     * @return array [success => bool, sent_count => int, message => string]
     */
    function sendRoApprovalNotification($conn, $idRequest) {
        $idRequest = (int)$idRequest;
        if ($idRequest <= 0) {
            return ['success' => false, 'sent_count' => 0, 'message' => 'ID Request Order tidak valid.'];
        }

        // 1. Ambil data Header RO & Pemohon
        $sqlRo = "SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.prioritas, ro.keterangan, ro.status,
                         s.nama_site, 
                         k.nama_karyawan, k.email as email_pemohon, j.nama_jabatan, d.nama_divisi
                  FROM request_order ro
                  LEFT JOIN site s ON ro.id_site = s.id_site
                  LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
                  LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                  LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                  WHERE ro.id_request = ? LIMIT 1";
        $stmt = $conn->prepare($sqlRo);
        $stmt->bind_param("i", $idRequest);
        $stmt->execute();
        $resRo = $stmt->get_result();

        if (!$resRo || $resRo->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'sent_count' => 0, 'message' => 'Data Request Order tidak ditemukan.'];
        }
        $roData = $resRo->fetch_assoc();
        $stmt->close();

        // 2. Ambil Rincian Item Barang
        $sqlItems = "SELECT id_request_detail, nama_barang, qty, satuan, harga, subtotal 
                     FROM request_order_detail 
                     WHERE id_request = ? ORDER BY id_request_detail ASC";
        $stmtIt = $conn->prepare($sqlItems);
        $stmtIt->bind_param("i", $idRequest);
        $stmtIt->execute();
        $resIt = $stmtIt->get_result();

        $items = [];
        while ($it = $resIt->fetch_assoc()) {
            $items[] = $it;
        }
        $stmtIt->close();

        // 3. Render HTML Email
        $pemohonData = [
            'nama_karyawan' => $roData['nama_karyawan'],
            'nama_jabatan'  => $roData['nama_jabatan'],
            'nama_divisi'   => $roData['nama_divisi'],
            'email'         => $roData['email_pemohon']
        ];
        $htmlBody = renderNewRoEmailTemplate($roData, $items, $pemohonData);
        $prioritasTag = ($roData['prioritas'] === 'URGENT') ? '[URGENT] ' : '';
        $subject = $prioritasTag . "Permintaan Persetujuan Request Order: " . $roData['nomor'] . " - " . ($roData['nama_karyawan'] ?? 'Pemohon');

        // 4. Ambil Daftar Approver
        $approvers = getRoApproverEmails($conn);
        if (empty($approvers)) {
            return ['success' => false, 'sent_count' => 0, 'message' => 'Tidak ada email Approver aktif yang terdaftar di sistem.'];
        }

        $sentCount = 0;
        $errors = [];
        foreach ($approvers as $appr) {
            $resSend = sendSmtpEmail($conn, $appr['email'], $appr['nama'], $subject, $htmlBody);
            if (!empty($resSend['success'])) {
                $sentCount++;
            } else {
                $errors[] = $appr['email'] . ': ' . ($resSend['message'] ?? 'Gagal');
            }
        }

        return [
            'success' => ($sentCount > 0),
            'sent_count' => $sentCount,
            'message' => "Notifikasi RO berhasil dikirim ke {$sentCount} Approver." . (!empty($errors) ? ' Errors: ' . implode('; ', $errors) : '')
        ];
    }
}

if (!function_exists('renderRoApprovedForPurchasingTemplate')) {
    /**
     * Template Email HTML untuk Tim Purchasing saat RO Disetujui Logistik (Siap Buat PO)
     */
    function renderRoApprovedForPurchasingTemplate($roData, $items, $approverName, $keterangan = '') {
        $nomorRo = htmlspecialchars($roData['nomor'] ?? '-');
        $namaPemohon = htmlspecialchars($roData['nama_pemohon'] ?? 'Karyawan');
        $divisiPemohon = htmlspecialchars($roData['nama_divisi'] ?? 'Operasional');
        $prioritas = htmlspecialchars($roData['prioritas'] ?? 'NORMAL');
        $namaSite = htmlspecialchars($roData['nama_site'] ?? 'Pusat');
        $idRequest = (int)($roData['id_request'] ?? 0);
        $prosesPoUrl = defined('BASE_URL') 
            ? BASE_URL . '/admin/pages/request_order/proses_po.php?id=' . $idRequest 
            : 'http://localhost/JT_Purchase/admin/pages/request_order/proses_po.php?id=' . $idRequest;

        $badgePrioritas = ($prioritas === 'URGENT')
            ? '<span style="background-color: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">URGENT</span>'
            : '<span style="background-color: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;">NORMAL</span>';

        // Render tabel items
        $itemsHtml = '';
        $no = 1;
        foreach ($items as $item) {
            $namaBarang = htmlspecialchars($item['nama_barang'] ?? '-');
            $qty = (float)($item['qty'] ?? 0);
            $satuan = htmlspecialchars($item['satuan'] ?? 'pcs');
            
            $bgRow = ($no % 2 === 0) ? '#f8fafc' : '#ffffff';
            $itemsHtml .= '
            <tr style="background-color: ' . $bgRow . ';">
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: center; color: #64748b; font-size: 12px;">' . $no++ . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 13px; font-weight: 600; color: #1e293b;">' . $namaBarang . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-size: 13px; font-weight: 700; color: #0f2744; font-family: monospace;">' . number_format($qty, 0, ',', '.') . '</td>
                <td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">' . $satuan . '</td>
            </tr>';
        }

        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>RO Siap Diproses Menjadi PO - ' . $nomorRo . '</title>
        </head>
        <body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="table-layout: fixed;">
                <tr>
                    <td align="center" style="padding: 30px 10px;">
                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">
                            <!-- Header Brand -->
                            <tr>
                                <td align="center" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%); padding: 28px 20px; color: #ffffff;">
                                    <h1 style="margin: 0; font-size: 20px; font-weight: 700; letter-spacing: 0.5px;">PT JAYA TEKNIS</h1>
                                    <p style="margin: 4px 0 0 0; font-size: 12px; opacity: 0.85;">Pemberitahuan Purchasing &amp; Pengadaan</p>
                                </td>
                            </tr>

                            <!-- Status Badge Banner -->
                            <tr>
                                <td style="background-color: #dcfce7; padding: 14px 24px; border-bottom: 1px solid #bbf7d0;">
                                    <span style="font-size: 13px; font-weight: 700; color: #166534;">
                                        ⚡ RO TELAH DISETUJUI LOGISTIK &bull; SIAP DIPROSES MENJADI PO
                                    </span>
                                </td>
                            </tr>

                            <!-- Body Content -->
                            <tr>
                                <td style="padding: 28px 24px; color: #1e293b;">
                                    <p style="margin: 0 0 14px 0; font-size: 14px; line-height: 1.6; color: #334155;">
                                        Halo <strong>Tim Purchasing</strong>,
                                    </p>
                                    <p style="margin: 0 0 20px 0; font-size: 14px; line-height: 1.6; color: #475569;">
                                        Request Order dengan nomor <strong style="font-family: monospace;">' . $nomorRo . '</strong> telah <strong>disetujui oleh Logistik</strong> dan kini siap untuk diproses ke tahap pembuatan <strong>Purchase Order (PO)</strong>.
                                    </p>

                                    <!-- Summary Card -->
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 24px; font-size: 13px;">
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; width: 35%; border-bottom: 1px solid #e2e8f0;">Nomor RO</td>
                                            <td style="padding: 10px 14px; font-weight: 700; color: #0f2744; font-family: monospace; font-size: 14px; border-bottom: 1px solid #e2e8f0;">' . $nomorRo . '</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Pemohon / Divisi</td>
                                            <td style="padding: 10px 14px; color: #1e293b; font-weight: 600; border-bottom: 1px solid #e2e8f0;">' . $namaPemohon . ' (' . $divisiPemohon . ')</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Disetujui Oleh</td>
                                            <td style="padding: 10px 14px; color: #166534; font-weight: 700; border-bottom: 1px solid #e2e8f0;">' . htmlspecialchars($approverName) . ' (Logistik)</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Prioritas / Site</td>
                                            <td style="padding: 10px 14px; border-bottom: 1px solid #e2e8f0;">' . $badgePrioritas . ' &bull; ' . $namaSite . '</td>
                                        </tr>
                                        ' . (!empty($keterangan) ? '
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b; border-bottom: 1px solid #e2e8f0;">Catatan Logistik</td>
                                            <td style="padding: 10px 14px; color: #334155; font-style: italic; border-bottom: 1px solid #e2e8f0;">' . nl2br(htmlspecialchars($keterangan)) . '</td>
                                        </tr>' : '') . '
                                        <tr>
                                            <td style="padding: 10px 14px; color: #64748b;">Waktu Persetujuan</td>
                                            <td style="padding: 10px 14px; color: #1e293b;">' . date('d/m/Y H:i') . '</td>
                                        </tr>
                                    </table>

                                    <!-- Table Item Header -->
                                    <div style="font-size: 13px; font-weight: 700; color: #0f2744; margin-bottom: 8px;">
                                        📦 Rincian Barang yang Diajukan:
                                    </div>
                                    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin-bottom: 25px;">
                                        <thead>
                                            <tr style="background-color: #f1f5f9;">
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; width: 40px; text-align: center;">No</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: left;">Nama Barang</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: right; width: 60px;">Qty</th>
                                                <th style="padding: 10px 12px; border-bottom: 2px solid #cbd5e1; font-size: 11px; text-transform: uppercase; color: #475569; text-align: left; width: 60px;">Satuan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ' . $itemsHtml . '
                                        </tbody>
                                    </table>

                                    <!-- Action Button CTA -->
                                    <div style="text-align: center; margin: 25px 0 10px 0;">
                                        <a href="' . $prosesPoUrl . '" target="_blank" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; padding: 13px 32px; border-radius: 8px; font-size: 14px; font-weight: 700; display: inline-block; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);">
                                            ⚡ Proses Menjadi Purchase Order (PO)
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f8fafc; padding: 18px 24px; text-align: center; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8;">
                                    &copy; ' . date('Y') . ' PT Jaya Teknis. Seluruh hak cipta dilindungi undang-undang.<br>
                                    Pemberitahuan otomatis dari Sistem Pengadaan &amp; Pembelian PT Jaya Teknis.
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

if (!function_exists('sendRoReadyForPurchasingNotification')) {
    /**
     * Mengirim notifikasi email kepada Tim Purchasing saat RO telah disetujui Logistik
     * 
     * @param mysqli $conn
     * @param int $idRequest
     * @param string $approverName
     * @param string $keterangan
     * @return array [success => bool, sent_count => int, message => string]
     */
    function sendRoReadyForPurchasingNotification($conn, $idRequest, $approverName, $keterangan = '') {
        $idRequest = (int)$idRequest;
        if ($idRequest <= 0) {
            return ['success' => false, 'sent_count' => 0, 'message' => 'ID Request Order tidak valid.'];
        }

        // 1. Ambil Data Header RO & Pemohon
        $sqlRo = "SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.prioritas, ro.keterangan, ro.status,
                         s.nama_site, 
                         k.nama_karyawan as nama_pemohon, k.email as email_pemohon, j.nama_jabatan, d.nama_divisi
                  FROM request_order ro
                  LEFT JOIN site s ON ro.id_site = s.id_site
                  LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
                  LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                  LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                  WHERE ro.id_request = ? LIMIT 1";
        $stmt = $conn->prepare($sqlRo);
        $stmt->bind_param("i", $idRequest);
        $stmt->execute();
        $resRo = $stmt->get_result();

        if (!$resRo || $resRo->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'sent_count' => 0, 'message' => 'Data Request Order tidak ditemukan.'];
        }
        $roData = $resRo->fetch_assoc();
        $stmt->close();

        // 2. Ambil Rincian Item Barang
        $sqlItems = "SELECT id_request_detail, nama_barang, qty, satuan, harga, subtotal 
                     FROM request_order_detail 
                     WHERE id_request = ? ORDER BY id_request_detail ASC";
        $stmtIt = $conn->prepare($sqlItems);
        $stmtIt->bind_param("i", $idRequest);
        $stmtIt->execute();
        $resIt = $stmtIt->get_result();

        $items = [];
        while ($it = $resIt->fetch_assoc()) {
            $items[] = $it;
        }
        $stmtIt->close();

        // 3. Render HTML Body
        $htmlBody = renderRoApprovedForPurchasingTemplate($roData, $items, $approverName, $keterangan);
        $subject = "[SIAP PROSES PO] Request Order Disetujui Logistik: " . $roData['nomor'] . " - " . ($roData['nama_pemohon'] ?? 'Pemohon');

        // 4. Ambil Daftar Email Purchasing
        $purchasingList = getPurchasingEmails($conn);
        if (empty($purchasingList)) {
            return ['success' => false, 'sent_count' => 0, 'message' => 'Tidak ada email tim Purchasing aktif yang terdaftar.'];
        }

        $sentCount = 0;
        $errors = [];
        foreach ($purchasingList as $purch) {
            $resSend = sendSmtpEmail($conn, $purch['email'], $purch['nama'], $subject, $htmlBody);
            if (!empty($resSend['success'])) {
                $sentCount++;
            } else {
                $errors[] = $purch['email'] . ': ' . ($resSend['message'] ?? 'Gagal');
            }
        }

        return [
            'success' => ($sentCount > 0),
            'sent_count' => $sentCount,
            'message' => "Notifikasi siap PO berhasil dikirim ke {$sentCount} tim Purchasing." . (!empty($errors) ? ' Errors: ' . implode('; ', $errors) : '')
        ];
    }
}

if (!function_exists('sendRoStatusNotification')) {
    /**
     * Mengirim notifikasi status persetujuan RO kepada Karyawan Pemohon dan Tim Purchasing (jika relevan)
     * 
     * @param mysqli $conn
     * @param int $idRequest
     * @param string $status
     * @param string $approverName
     * @param string $keterangan
     * @param array $extraData
     * @return array [success => bool, message => string]
     */
    function sendRoStatusNotification($conn, $idRequest, $status, $approverName, $keterangan = '', $extraData = []) {
        $idRequest = (int)$idRequest;
        if ($idRequest <= 0) {
            return ['success' => false, 'message' => 'ID Request Order tidak valid.'];
        }

        // 1. Ambil Data RO & Pemohon beserta relasi PO & Vendor jika ada
        $sqlRo = "SELECT ro.id_request, ro.nomor, ro.status, ro.id_po, ro.id_vendor,
                         k.nama_karyawan as nama_pemohon, k.email as email_pemohon,
                         po.nomor_po, v.nama_perusahaan as nama_vendor
                  FROM request_order ro
                  LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
                  LEFT JOIN purchase_order po ON ro.id_po = po.id_po
                  LEFT JOIN vendor v ON (ro.id_vendor = v.id_vendor OR po.id_vendor = v.id_vendor)
                  WHERE ro.id_request = ? LIMIT 1";
        $stmt = $conn->prepare($sqlRo);
        $stmt->bind_param("i", $idRequest);
        $stmt->execute();
        $resRo = $stmt->get_result();

        if (!$resRo || $resRo->num_rows === 0) {
            $stmt->close();
            return ['success' => false, 'message' => 'Data Request Order tidak ditemukan.'];
        }
        $roData = $resRo->fetch_assoc();
        $stmt->close();

        // Gabungkan extraData jika ada nomor_po atau vendor yang baru saja dibuat
        if (!empty($extraData) && is_array($extraData)) {
            foreach ($extraData as $k => $v) {
                if (!empty($v)) {
                    $roData[$k] = $v;
                }
            }
        }

        $toEmail = trim($roData['email_pemohon'] ?? '');
        $toName = trim($roData['nama_pemohon'] ?? 'Karyawan');

        $statusUpper = strtoupper(trim($status));
        $emailSentPemohon = false;

        if (!empty($toEmail) && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $htmlBody = renderRoStatusEmailTemplate($roData, $statusUpper, $approverName, $keterangan, $extraData);

            if ($statusUpper === 'DISETUJUI PURCHASING') {
                $poInfo = !empty($roData['nomor_po']) ? " (PO: {$roData['nomor_po']})" : "";
                $subject = "[PO TERBIT] Request Order Telah Disetujui Purchasing: {$roData['nomor']}{$poInfo}";
            } elseif ($statusUpper === 'TIDAK DISETUJUI PURCHASING') {
                $subject = "[DITOLAK PURCHASING] Request Order Tidak Disetujui: {$roData['nomor']}";
            } elseif ($statusUpper === 'DISETUJUI LOGISTIK') {
                $subject = "[DISETUJUI LOGISTIK] Request Order Disetujui: {$roData['nomor']}";
            } elseif ($statusUpper === 'TIDAK DISETUJUI LOGISTIK') {
                $subject = "[DITOLAK LOGISTIK] Request Order Tidak Disetujui: {$roData['nomor']}";
            } else {
                $statusTag = (strpos($statusUpper, 'DISETUJUI') !== false && strpos($statusUpper, 'TIDAK') === false) ? '[DISETUJUI]' : '[DITOLAK]';
                $subject = "{$statusTag} Pembaruan Status Request Order: {$roData['nomor']}";
            }

            $resSend = sendSmtpEmail($conn, $toEmail, $toName, $subject, $htmlBody);
            $emailSentPemohon = !empty($resSend['success']);
        }

        // 2. Jika status adalah DISETUJUI LOGISTIK, kirim email notifikasi ke Tim Purchasing untuk proses PO
        $isLogistikApproved = ($statusUpper === 'DISETUJUI LOGISTIK');
        if ($isLogistikApproved && function_exists('sendRoReadyForPurchasingNotification')) {
            sendRoReadyForPurchasingNotification($conn, $idRequest, $approverName, $keterangan);
        }

        return [
            'success' => $emailSentPemohon || $isLogistikApproved,
            'message' => 'Notifikasi status RO berhasil diproses.'
        ];
    }
}
