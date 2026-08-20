<?php
require_once __DIR__ . '/../config/koneksi.php';

// Test login function simulation
function simulateLogin($identity, $pass) {
    global $conn;

    // 1. Check users for super admin
    $stmt = $conn->prepare("SELECT id_users, nama_users, email, password, aktif FROM users WHERE (email = ? OR nama_users = ?) AND (nama_users = 'admin' OR id_users = 1) LIMIT 1");
    $stmt->bind_param("ss", $identity, $identity);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows > 0) {
        $u = $res->fetch_assoc();
        if (password_verify($pass, $u['password']) || $pass === $u['password']) {
            return "SUCCESS: Logged in via USERS table as Super Admin (Name: {$u['nama_users']})";
        }
    }

    // 2. Check karyawan table
    $stmt2 = $conn->prepare("SELECT k.*, j.nama_jabatan, j.level as level_jabatan, d.nama_divisi, s.nama_site 
                             FROM karyawan k 
                             LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan 
                             LEFT JOIN divisi d ON k.id_divisi = d.id_divisi 
                             LEFT JOIN site s ON k.id_site = s.id_site 
                             WHERE (k.email = ? OR k.kode_karyawan = ? OR k.no_handphone = ?) LIMIT 1");
    $stmt2->bind_param("sss", $identity, $identity, $identity);
    $stmt2->execute();
    $res2 = $stmt2->get_result();

    if ($res2 && $res2->num_rows > 0) {
        $k = $res2->fetch_assoc();
        if ((int)$k['aktif'] !== 1) return "FAILED: Karyawan is Non-Aktif";
        if (isset($k['login_web']) && (int)$k['login_web'] !== 1) return "FAILED: Karyawan login_web = 0";
        if (password_verify($pass, $k['password']) || $pass === $k['password']) {
            return "SUCCESS: Logged in via KARYAWAN table (ID: {$k['id_karyawan']} | {$k['kode_karyawan']} | {$k['nama_karyawan']} | Jabatan: {$k['nama_jabatan']} Level {$k['level_jabatan']} | Divisi: {$k['nama_divisi']})";
        }
        return "FAILED: Password mismatch for karyawan";
    }

    return "FAILED: Account not found";
}

echo "1. Admin: " . simulateLogin('admin', 'admin123') . PHP_EOL;
echo "2. Mekanik (email): " . simulateLogin('mekanik@jayateknis.com', 'admin123') . PHP_EOL;
echo "3. Mekanik (kode): " . simulateLogin('KRY002', 'admin123') . PHP_EOL;
echo "4. Logistik: " . simulateLogin('logistik@jayateknis.com', 'admin123') . PHP_EOL;
echo "5. Purchasing: " . simulateLogin('purchasing@jayateknis.com', 'admin123') . PHP_EOL;
echo "6. Manager: " . simulateLogin('manager@jayateknis.com', 'admin123') . PHP_EOL;
