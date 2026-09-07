<?php
/**
 * API Master: Profile Perusahaan - PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }
}

// GET: Ambil data profil perusahaan
if ($method === 'GET') {
    $profile = getCompanyProfile($conn);
    jsonResponse(true, 'Profil perusahaan berhasil diambil.', ['profile' => $profile], 200);
}

// Hanya Role ADMIN yang dapat mengupdate profil perusahaan
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengubah profil perusahaan.', null, 403);
}

// POST / PUT: Update Profil Perusahaan & Upload Logo
if ($method === 'POST' || $method === 'PUT') {
    $action = $_GET['action'] ?? ($_POST['action'] ?? '');

    // 1. ACTION: HAPUS LOGO PERUSAHAAN
    if ($action === 'delete_logo') {
        $check = $conn->query("SELECT id_perusahaan, picture FROM profile LIMIT 1");
        if ($check && $row = $check->fetch_assoc()) {
            $oldPic = $row['picture'];
            if (!empty($oldPic)) {
                $oldPath = __DIR__ . '/../../' . $oldPic;
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $conn->query("UPDATE profile SET picture = '' WHERE id_perusahaan = " . (int)$row['id_perusahaan']);
        }
        jsonResponse(true, 'Logo perusahaan berhasil dihapus.', [
            'profile' => getCompanyProfile($conn)
        ], 200);
    }

    // 2. ACTION: UPLOAD LOGO PERUSAHAAN
    if ($action === 'upload_logo' || isset($_FILES['logo']) || isset($_FILES['picture'])) {
        $file = $_FILES['logo'] ?? $_FILES['picture'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(false, 'Berkas logo tidak valid atau gagal diunggah.', null, 400);
        }

        // Validasi Ekstensi Berkas (.PNG Saja)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'png') {
            jsonResponse(false, 'Format berkas tidak diizinkan. Logo harus berupa berkas dengan format .PNG.', null, 422);
        }

        // Validasi Ukuran Berkas (Maks 500 KB = 512,000 bytes)
        $maxSizeBytes = 500 * 1024;
        if ($file['size'] > $maxSizeBytes) {
            jsonResponse(false, 'Ukuran berkas melebihi batas maksimal 500 KB (Ukuran: ' . round($file['size'] / 1024, 1) . ' KB).', null, 422);
        }

        // Validasi MIME Type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mimeType !== 'image/png') {
            jsonResponse(false, 'Tipe MIME berkas tidak valid. Harap unggah berkas gambar PNG asli.', null, 422);
        }

        // Validasi Dimensi & Rasio Gambar (Harus 1:1 / Persegi)
        $imgInfo = @getimagesize($file['tmp_name']);
        if (!$imgInfo) {
            jsonResponse(false, 'Berkas gambar tidak dapat dibaca atau rusak.', null, 422);
        }

        $width = $imgInfo[0];
        $height = $imgInfo[1];

        // Toleransi rasio 1:1 (rasio lebar dan tinggi harus sama persis atau mendekati)
        $ratio = $width / max(1, $height);
        if ($ratio < 0.95 || $ratio > 1.05) {
            jsonResponse(false, "Dimensi logo harus memiliki rasio 1:1 (persegi). Dimensi saat ini: {$width}x{$height} px.", null, 422);
        }

        // Direktori Upload
        $uploadDir = __DIR__ . '/../../images/uploads/company/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filename = 'company_logo_' . time() . '_' . bin2hex(random_bytes(3)) . '.png';
        $targetPath = $uploadDir . $filename;
        $relativeDbPath = 'images/uploads/company/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Hapus logo lama jika ada
            $check = $conn->query("SELECT id_perusahaan, picture FROM profile LIMIT 1");
            if ($check && $row = $check->fetch_assoc()) {
                $oldPic = $row['picture'];
                if (!empty($oldPic) && $oldPic !== $relativeDbPath) {
                    $oldPath = __DIR__ . '/../../' . $oldPic;
                    if (file_exists($oldPath)) {
                        @unlink($oldPath);
                    }
                }
                $conn->query("UPDATE profile SET picture = '" . $conn->real_escape_string($relativeDbPath) . "' WHERE id_perusahaan = " . (int)$row['id_perusahaan']);
            }

            jsonResponse(true, 'Logo perusahaan berhasil diunggah dan disimpan.', [
                'picture' => $relativeDbPath,
                'profile' => getCompanyProfile($conn)
            ], 200);
        } else {
            jsonResponse(false, 'Gagal menyimpan berkas logo ke server.', null, 500);
        }
    }

    // 3. UPDATE DATA IDENTITAS / TEKS PROFIL
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $nama = trim($input['nama'] ?? '');
    $telepon1 = trim($input['telepon1'] ?? '');
    $whatsapp = trim($input['whatsapp'] ?? '');
    $email = trim($input['email'] ?? '');
    $alamat = trim($input['alamat'] ?? '');
    $alamatGps = trim($input['alamat_gps'] ?? '');
    $kota = trim($input['kota'] ?? '');
    $provinsi = trim($input['provinsi'] ?? '');
    $npwp = trim($input['npwp'] ?? '');
    $klu = trim($input['KLU'] ?? '');
    $nitku = trim($input['NITKU'] ?? '');
    $timezone = trim($input['timezone'] ?? 'Asia/Makassar');
    if (empty($timezone) || !in_array($timezone, DateTimeZone::listIdentifiers())) {
        $timezone = 'Asia/Makassar';
    }
    $pajak12 = isset($input['pajak12']) ? (int)$input['pajak12'] : 1;
    $picture = trim($input['picture'] ?? '');

    if (empty($nama)) {
        jsonResponse(false, 'Nama perusahaan tidak boleh kosong.', null, 422);
    }

    // Cek apakah data profil sudah ada
    $check = $conn->query("SELECT id_perusahaan, picture FROM profile LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $idPerusahaan = $row['id_perusahaan'];
        if (empty($picture)) {
            $picture = $row['picture'] ?? '';
        }
        
        $stmt = $conn->prepare("UPDATE profile SET nama = ?, telepon1 = ?, whatsapp = ?, email = ?, alamat = ?, alamat_gps = ?, 
                                kota = ?, provinsi = ?, npwp = ?, KLU = ?, NITKU = ?, timezone = ?, pajak12 = ?, picture = ? 
                                WHERE id_perusahaan = ?");
        $stmt->bind_param("ssssssssssssisi", $nama, $telepon1, $whatsapp, $email, $alamat, $alamatGps, $kota, $provinsi, $npwp, $klu, $nitku, $timezone, $pajak12, $picture, $idPerusahaan);
    } else {
        $stmt = $conn->prepare("INSERT INTO profile (nama, telepon1, whatsapp, email, alamat, alamat_gps, kota, provinsi, npwp, KLU, NITKU, timezone, pajak12, picture) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssssis", $nama, $telepon1, $whatsapp, $email, $alamat, $alamatGps, $kota, $provinsi, $npwp, $klu, $nitku, $timezone, $pajak12, $picture);
    }

    if ($stmt->execute()) {
        $stmt->close();
        if (function_exists('applyAppTimezone')) {
            applyAppTimezone($conn);
        }
        jsonResponse(true, 'Profil perusahaan berhasil diperbarui.', [
            'profile' => getCompanyProfile($conn)
        ], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui profil perusahaan.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
