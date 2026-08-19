<?php
/**
 * API Auth: Login Endpoint - PT Jaya Teknis
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
    jsonResponse(false, 'Email/Username dan Password wajib diisi.', null, 422);
}

// 1. Cek pada tabel users
$userFound = null;
$userSource = null;

$stmt = $conn->prepare("SELECT id_users, nama_users, email, password, aktif FROM users WHERE email = ? OR nama_users = ? LIMIT 1");
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

// 2. Jika tidak ditemukan di tabel users, coba cek di tabel karyawan
if (!$userFound) {
    $stmt2 = $conn->prepare("SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.id_divisi, k.email, k.password, k.aktif, d.nama_divisi 
                             FROM karyawan k 
                             LEFT JOIN divisi d ON k.id_divisi = d.id_divisi 
                             WHERE k.email = ? OR k.nama_karyawan = ? OR k.kode_karyawan = ? LIMIT 1");
    $stmt2->bind_param("sss", $identity, $identity, $identity);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($res2 && $res2->num_rows > 0) {
        $karyawanRow = $res2->fetch_assoc();
        if ($karyawanRow['aktif'] == 1 || $karyawanRow['aktif'] === null) {
            $userFound = $karyawanRow;
            $userSource = 'karyawan';
        }
    }
    $stmt2->close();
}

// Jika akun tidak ditemukan
if (!$userFound) {
    jsonResponse(false, 'Akun dengan email/username tersebut tidak ditemukan.', null, 401);
}

// Verifikasi password (password_verify atau fallback plain match untuk database existing demo)
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

// Tentukan Role Berdasarkan User / Divisi / Email
$role = ROLE_MEKANIK; // Default
$namaUser = '';
$userId = 0;
$idKaryawan = null;
$idDivisi = null;
$namaDivisi = '';
$emailUser = $userFound['email'] ?? '';

if ($userSource === 'users') {
    $userId = (int)$userFound['id_users'];
    $namaUser = $userFound['nama_users'];
    
    // Hubungkan dengan karyawan jika ada untuk mendapatkan divisi & id_karyawan
    $stmtK = $conn->prepare("SELECT k.id_karyawan, k.id_divisi, d.nama_divisi 
                             FROM karyawan k 
                             LEFT JOIN divisi d ON k.id_divisi = d.id_divisi 
                             WHERE k.email = ? OR k.nama_karyawan = ? LIMIT 1");
    $stmtK->bind_param("ss", $emailUser, $namaUser);
    $stmtK->execute();
    $resK = $stmtK->get_result();
    if ($resK && $rowK = $resK->fetch_assoc()) {
        $idKaryawan = (int)$rowK['id_karyawan'];
        $idDivisi = (int)$rowK['id_divisi'];
        $namaDivisi = $rowK['nama_divisi'] ?? '';
    }
    $stmtK->close();

    $emailLower = strtolower($emailUser);
    $nameLower = strtolower($namaUser);
    $divisiLower = strtolower($namaDivisi);
    
    if (strpos($emailLower, 'admin') !== false || strpos($nameLower, 'admin') !== false || strpos($divisiLower, 'admin') !== false) {
        $role = ROLE_ADMIN;
    } elseif (strpos($emailLower, 'logistik') !== false || strpos($nameLower, 'logistik') !== false || strpos($divisiLower, 'logistik') !== false) {
        $role = ROLE_LOGISTIK;
    } elseif (strpos($emailLower, 'purchasing') !== false || strpos($nameLower, 'purchasing') !== false || strpos($divisiLower, 'purchasing') !== false) {
        $role = ROLE_PURCHASING;
    } elseif (strpos($emailLower, 'manager') !== false || strpos($nameLower, 'manager') !== false || strpos($divisiLower, 'manajemen') !== false) {
        $role = ROLE_MANAGER;
    } elseif (strpos($emailLower, 'mekanik') !== false || strpos($nameLower, 'mekanik') !== false || strpos($divisiLower, 'mekanik') !== false || strpos($divisiLower, 'bengkel') !== false) {
        $role = ROLE_MEKANIK;
    } else {
        $role = ROLE_MEKANIK; // Default role
    }
} else {
    $userId = (int)$userFound['id_karyawan'];
    $idKaryawan = (int)$userFound['id_karyawan'];
    $namaUser = $userFound['nama_karyawan'];
    $idDivisi = (int)$userFound['id_divisi'];
    $namaDivisi = $userFound['nama_divisi'] ?? '';
    
    $divisiLower = strtolower($namaDivisi);
    $emailLower = strtolower($emailUser);
    $nameLower = strtolower($namaUser);
    
    if (strpos($divisiLower, 'admin') !== false || strpos($emailLower, 'admin') !== false) {
        $role = ROLE_ADMIN;
    } elseif (strpos($divisiLower, 'logistik') !== false || strpos($emailLower, 'logistik') !== false || strpos($nameLower, 'logistik') !== false) {
        $role = ROLE_LOGISTIK;
    } elseif (strpos($divisiLower, 'purchasing') !== false || strpos($emailLower, 'purchasing') !== false || strpos($nameLower, 'purchasing') !== false) {
        $role = ROLE_PURCHASING;
    } elseif (strpos($divisiLower, 'manager') !== false || strpos($emailLower, 'manager') !== false || strpos($nameLower, 'manager') !== false) {
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
$_SESSION['username'] = $namaUser;
$_SESSION['nama'] = $namaUser;
$_SESSION['email'] = $emailUser;
$_SESSION['role'] = $role;
$_SESSION['id_karyawan'] = $idKaryawan;
$_SESSION['id_divisi'] = $idDivisi;
$_SESSION['nama_divisi'] = $namaDivisi;
$_SESSION['api_token'] = $apiToken;

$responseData = [
    'token' => $apiToken,
    'user' => [
        'id' => $userId,
        'nama' => $namaUser,
        'email' => $emailUser,
        'role' => $role,
        'id_karyawan' => $idKaryawan,
        'nama_divisi' => $namaDivisi
    ],
    'redirect_url' => BASE_URL . '/admin/dashboard.php'
];

jsonResponse(true, 'Login berhasil. Selamat datang, ' . $namaUser, $responseData, 200);
