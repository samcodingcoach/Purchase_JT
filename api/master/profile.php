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

// POST / PUT: Update Profil Perusahaan
if ($method === 'POST' || $method === 'PUT') {
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
    $pajak12 = isset($input['pajak12']) ? (int)$input['pajak12'] : 1;
    $picture = trim($input['picture'] ?? '');

    if (empty($nama)) {
        jsonResponse(false, 'Nama perusahaan tidak boleh kosong.', null, 422);
    }

    // Cek apakah data profil sudah ada
    $check = $conn->query("SELECT id_perusahaan FROM profile LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $idPerusahaan = $row['id_perusahaan'];
        
        $stmt = $conn->prepare("UPDATE profile SET nama = ?, telepon1 = ?, whatsapp = ?, email = ?, alamat = ?, alamat_gps = ?, 
                                kota = ?, provinsi = ?, npwp = ?, KLU = ?, NITKU = ?, pajak12 = ?, picture = ? 
                                WHERE id_perusahaan = ?");
        $stmt->bind_param("sssssssssssisi", $nama, $telepon1, $whatsapp, $email, $alamat, $alamatGps, $kota, $provinsi, $npwp, $klu, $nitku, $pajak12, $picture, $idPerusahaan);
    } else {
        $stmt = $conn->prepare("INSERT INTO profile (nama, telepon1, whatsapp, email, alamat, alamat_gps, kota, provinsi, npwp, KLU, NITKU, pajak12, picture) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssssssis", $nama, $telepon1, $whatsapp, $email, $alamat, $alamatGps, $kota, $provinsi, $npwp, $klu, $nitku, $pajak12, $picture);
    }

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Profil perusahaan berhasil diperbarui.', [
            'profile' => getCompanyProfile($conn)
        ], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui profil perusahaan.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
