<?php
/**
 * API Auth: Login Endpoint Berfokus Karyawan & Super Admin - PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak diizinkan. Gunakan POST.', null, 405);
}

// Ambil input JSON atau POST form-data
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$identity = trim($input['username'] ?? $input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($identity) || empty($password)) {
    jsonResponse(false, 'Email/Kode Karyawan/Username dan Password wajib diisi.', null, 422);
}

$userFound = null;
$userSource = null;

// =============================================================
// 1. PRIORITAS UTAMA ADMIN: Cek Akun Administrator di Tabel USERS
// =============================================================
$stmt = $conn->prepare("SELECT id_users, nama_users, email, password, aktif 
                        FROM users 
                        WHERE (email = ? OR nama_users = ?) AND (nama_users = 'admin' OR id_users = 1) 
                        LIMIT 1");
$stmt->bind_param("ss", $identity, $identity);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows > 0) {
    $userRow = $res->fetch_assoc();
    if ($userRow['aktif'] == 1 || $userRow['aktif'] === null) {
        $userFound = $userRow;
        $userSource = 'users';
    }
}
$stmt->close();

// =============================================================
// 2. PRIORITAS UTAMA KARYAWAN: Cek di Tabel KARYAWAN
// =============================================================
if (!$userFound) {
    $emailPrefix = $identity . '@%';
    $stmt2 = $conn->prepare("SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.id_jabatan, k.id_divisi, k.id_site,
                                    k.email, k.no_handphone, k.password, k.aktif, k.login_web, k.status_karyawan,
                                    j.nama_jabatan, j.level as level_jabatan,
                                    d.nama_divisi,
                                    s.nama_site
                             FROM karyawan k 
                             LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                             LEFT JOIN divisi d ON k.id_divisi = d.id_divisi 
                             LEFT JOIN site s ON k.id_site = s.id_site
                             WHERE (k.email = ? OR k.email LIKE ? OR k.kode_karyawan = ? OR k.no_handphone = ? OR k.nama_karyawan = ?) LIMIT 1");
    $stmt2->bind_param("sssss", $identity, $emailPrefix, $identity, $identity, $identity);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    
    if ($res2 && $res2->num_rows > 0) {
        $karyawanRow = $res2->fetch_assoc();
        
        // Validasi Status Keaktifan Karyawan
        if ((int)$karyawanRow['aktif'] !== 1) {
            jsonResponse(false, 'Akun karyawan Anda berstatus Non-Aktif. Hubungi administrator.', null, 403);
        }

        // Validasi Izin Akses Login Web
        if (isset($karyawanRow['login_web']) && (int)$karyawanRow['login_web'] !== 1) {
            jsonResponse(false, 'Akun karyawan Anda tidak memiliki hak akses untuk login ke aplikasi web.', null, 403);
        }

        $userFound = $karyawanRow;
        $userSource = 'karyawan';
    }
    $stmt2->close();
}

// Jika akun tidak ditemukan di kedua tabel
if (!$userFound) {
    jsonResponse(false, 'Akun dengan email / kode karyawan tersebut tidak ditemukan.', null, 401);
}

// Verifikasi password (password_verify atau fallback plaintext upgrade)
$storedPassword = $userFound['password'];
$isPasswordValid = false;

if (password_verify($password, $storedPassword)) {
    $isPasswordValid = true;
} elseif ($password === $storedPassword) {
    // Fallback jika password di database existing masih plain text, lalu upgrade hash
    $isPasswordValid = true;
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    if ($userSource === 'users') {
        $up = $conn->prepare("UPDATE users SET password = ? WHERE id_users = ?");
        $up->bind_param("si", $newHash, $userFound['id_users']);
        $up->execute();
        $up->close();
    } else {
        $up = $conn->prepare("UPDATE karyawan SET password = ? WHERE id_karyawan = ?");
        $up->bind_param("si", $newHash, $userFound['id_karyawan']);
        $up->execute();
        $up->close();
    }
}

if (!$isPasswordValid) {
    jsonResponse(false, 'Password yang Anda masukkan salah.', null, 401);
}

// Tentukan Role & Payload Data Sesi
$role = ROLE_MEKANIK;
$namaUser = '';
$userId = 0;
$idKaryawan = null;
$kodeKaryawan = '';
$idJabatan = null;
$namaJabatan = '';
$levelJabatan = null;
$idDivisi = null;
$namaDivisi = '';
$idSite = null;
$namaSite = '';
$emailUser = $userFound['email'] ?? '';

if ($userSource === 'users') {
    $userId = (int)$userFound['id_users'];
    $namaUser = $userFound['nama_users'];
    $role = ROLE_ADMIN;
    $idJabatan = 1; // Default Super Admin
} else {
    $userId = (int)$userFound['id_karyawan'];
    $idKaryawan = (int)$userFound['id_karyawan'];
    $kodeKaryawan = $userFound['kode_karyawan'] ?? '';
    $namaUser = $userFound['nama_karyawan'];
    $idJabatan = !empty($userFound['id_jabatan']) ? (int)$userFound['id_jabatan'] : null;
    $namaJabatan = $userFound['nama_jabatan'] ?? '';
    $levelJabatan = isset($userFound['level_jabatan']) ? (int)$userFound['level_jabatan'] : null;
    $idDivisi = !empty($userFound['id_divisi']) ? (int)$userFound['id_divisi'] : null;
    $namaDivisi = $userFound['nama_divisi'] ?? '';
    $idSite = !empty($userFound['id_site']) ? (int)$userFound['id_site'] : null;
    $namaSite = $userFound['nama_site'] ?? '';

    // Deteksi Role berdasarkan Divisi / Jabatan
    $divisiLower = strtolower($namaDivisi);
    $jabatanLower = strtolower($namaJabatan);
    $emailLower = strtolower($emailUser);

    if (strpos($divisiLower, 'admin') !== false || strpos($divisiLower, 'it') !== false || strpos($jabatanLower, 'admin') !== false) {
        $role = ROLE_ADMIN;
    } elseif (strpos($divisiLower, 'logistik') !== false || strpos($jabatanLower, 'logistik') !== false || strpos($emailLower, 'logistik') !== false) {
        $role = ROLE_LOGISTIK;
    } elseif (strpos($divisiLower, 'purchasing') !== false || strpos($divisiLower, 'pengadaan') !== false || strpos($jabatanLower, 'purchasing') !== false) {
        $role = ROLE_PURCHASING;
    } elseif ($levelJabatan === 1 || strpos($divisiLower, 'manajemen') !== false || strpos($jabatanLower, 'manager') !== false || strpos($jabatanLower, 'direktur') !== false || strpos($jabatanLower, 'kepala') !== false) {
        $role = ROLE_MANAGER;
    } else {
        $role = ROLE_MEKANIK;
    }
}

// Generate API Token
$apiToken = bin2hex(random_bytes(32));

// Regenerate Session untuk keamanan
session_regenerate_id(true);

// Set data ke Session
$_SESSION['user_id'] = $userId;
$_SESSION['id_karyawan'] = $idKaryawan;
$_SESSION['kode_karyawan'] = $kodeKaryawan;
$_SESSION['username'] = $kodeKaryawan ?: $namaUser;
$_SESSION['nama'] = $namaUser;
$_SESSION['email'] = $emailUser;
$_SESSION['role'] = $role;
$_SESSION['id_jabatan'] = $idJabatan;
$_SESSION['nama_jabatan'] = $namaJabatan;
$_SESSION['level_jabatan'] = $levelJabatan;
$_SESSION['id_divisi'] = $idDivisi;
$_SESSION['nama_divisi'] = $namaDivisi;
$_SESSION['id_site'] = $idSite;
$_SESSION['nama_site'] = $namaSite;
$_SESSION['api_token'] = $apiToken;

$responseData = [
    'token' => $apiToken,
    'user' => [
        'id' => $userId,
        'id_karyawan' => $idKaryawan,
        'kode_karyawan' => $kodeKaryawan,
        'nama' => $namaUser,
        'email' => $emailUser,
        'role' => $role,
        'id_jabatan' => $idJabatan,
        'nama_jabatan' => $namaJabatan,
        'level_jabatan' => $levelJabatan,
        'id_divisi' => $idDivisi,
        'nama_divisi' => $namaDivisi,
        'id_site' => $idSite,
        'nama_site' => $namaSite
    ],
    'redirect_url' => BASE_URL . '/admin/dashboard.php'
];

jsonResponse(true, 'Login berhasil. Selamat datang, ' . $namaUser, $responseData, 200);
