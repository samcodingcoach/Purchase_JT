<?php
/**
 * API Auth / Profile: Edit Profil Karyawan / User yang Sedang Login
 * Path: api/auth/profile.php
 * Endpoint: GET & POST / PUT
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/koneksi.php';

// Wajib Login
$currentUser = requireAuth();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $rawInput = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($rawInput['_method'])) {
        $method = strtoupper($rawInput['_method']);
    }
}

// =========================================================================
// 1. GET: Ambil Data Profil Karyawan / User yang Sedang Login
// =========================================================================
if ($method === 'GET') {
    $idKaryawan = $currentUser['id_karyawan'] ?? null;
    $idUsers = $currentUser['id_users'] ?? ($currentUser['id'] ?? null);

    $statusKaryawanMap = [
        0 => 'Magang (Internship)',
        1 => 'PKWT (Perjanjian Kerja Waktu Tertentu)',
        2 => 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)',
        3 => 'Pekerja paruh waktu (Part-time)',
        4 => 'Harian Lepas (Casual Workers)',
        5 => 'Freelance / Pekerja Lepas',
        6 => 'Outsourcing / Alih Daya',
        7 => 'Volunteer / Sukarelawan'
    ];

    if (!empty($idKaryawan)) {
        // Ambil data lengkap dari tabel karyawan
        $stmt = $conn->prepare("SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.email, k.no_handphone,
                                       k.tempat_lahir, k.tanggal_lahir, k.jenis_kelamin, k.tanggal_bergabung,
                                       k.id_jabatan, j.nama_jabatan, j.level as level_jabatan,
                                       k.id_divisi, d.nama_divisi,
                                       k.id_site, s.nama_site,
                                       k.status_karyawan, k.aktif
                                FROM karyawan k
                                LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan
                                LEFT JOIN divisi d ON k.id_divisi = d.id_divisi
                                LEFT JOIN site s ON k.id_site = s.id_site
                                WHERE k.id_karyawan = ? LIMIT 1");
        $stmt->bind_param("i", $idKaryawan);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            $row['source'] = 'karyawan';
            $row['role'] = $currentUser['role'];
            $stId = isset($row['status_karyawan']) && $row['status_karyawan'] !== null ? (int)$row['status_karyawan'] : 2;
            $row['status_karyawan_id'] = $stId;
            $row['status_karyawan_label'] = $statusKaryawanMap[$stId] ?? 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)';
            $stmt->close();
            jsonResponse(true, 'Profil karyawan berhasil dimuat.', $row);
        }
        $stmt->close();
    }

    // Fallback akun Super Admin di tabel users
    if (!empty($idUsers)) {
        $stmt = $conn->prepare("SELECT id_users, nama_users as nama_karyawan, email, 'ADMIN' as kode_karyawan,
                                       'Administrator' as nama_jabatan, 'IT & Manajemen' as nama_divisi,
                                       'Head Office' as nama_site, '' as no_handphone, 'Tetap' as status_karyawan,
                                       '' as tempat_lahir, NULL as tanggal_lahir, 1 as jenis_kelamin, NOW() as tanggal_bergabung
                                FROM users WHERE id_users = ? LIMIT 1");
        $stmt->bind_param("i", $idUsers);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $row = $res->fetch_assoc()) {
            $row['source'] = 'users';
            $row['role'] = ROLE_ADMIN;
            $row['status_karyawan_id'] = 2;
            $row['status_karyawan_label'] = 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)';
            $stmt->close();
            jsonResponse(true, 'Profil admin berhasil dimuat.', $row);
        }
        $stmt->close();
    }

    jsonResponse(false, 'Data profil pengguna tidak ditemukan.', null, 404);
}

// =========================================================================
// 2. POST / PUT: Update Data Profil & Password Pengguna yang Sedang Login
// =========================================================================
if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    $nama = trim($input['nama_karyawan'] ?? $input['nama'] ?? '');
    $email = trim($input['email'] ?? '');
    $noHp = trim($input['no_handphone'] ?? $input['telepon'] ?? '');
    $tempatLahir = trim($input['tempat_lahir'] ?? '');
    $tanggalLahir = !empty($input['tanggal_lahir']) ? trim($input['tanggal_lahir']) : null;
    $jenisKelamin = isset($input['jenis_kelamin']) && $input['jenis_kelamin'] !== '' ? (int)$input['jenis_kelamin'] : null;

    $passwordLama = trim($input['password_lama'] ?? '');
    $passwordBaru = trim($input['password_baru'] ?? '');
    $konfirmasiPassword = trim($input['konfirmasi_password'] ?? '');

    if (empty($nama)) {
        jsonResponse(false, 'Nama lengkap tidak boleh kosong.', null, 422);
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(false, 'Alamat email tidak valid.', null, 422);
    }

    $idKaryawan = $currentUser['id_karyawan'] ?? null;
    $idUsers = $currentUser['id_users'] ?? ($currentUser['id'] ?? null);

    // -------------------------------------------------------------
    // A. UPDATE UNTUK AKUN KARYAWAN
    // -------------------------------------------------------------
    if (!empty($idKaryawan)) {
        // Cek duplikasi email ke karyawan lain
        $stmtChk = $conn->prepare("SELECT id_karyawan FROM karyawan WHERE email = ? AND id_karyawan != ? LIMIT 1");
        $stmtChk->bind_param("si", $email, $idKaryawan);
        $stmtChk->execute();
        if ($stmtChk->get_result()->num_rows > 0) {
            $stmtChk->close();
            jsonResponse(false, 'Email tersebut sudah digunakan oleh akun karyawan lain.', null, 422);
        }
        $stmtChk->close();

        // Jika ingin ganti password
        if (!empty($passwordBaru)) {
            if (strlen($passwordBaru) < 5) {
                jsonResponse(false, 'Password baru minimal harus 5 karakter.', null, 422);
            }
            if ($passwordBaru !== $konfirmasiPassword) {
                jsonResponse(false, 'Konfirmasi password baru tidak cocok.', null, 422);
            }

            // Validasi password lama jika diisi
            if (!empty($passwordLama)) {
                $stmtPass = $conn->prepare("SELECT password FROM karyawan WHERE id_karyawan = ? LIMIT 1");
                $stmtPass->bind_param("i", $idKaryawan);
                $stmtPass->execute();
                $dbPass = $stmtPass->get_result()->fetch_assoc()['password'] ?? '';
                $stmtPass->close();

                if (!password_verify($passwordLama, $dbPass) && $passwordLama !== $dbPass) {
                    jsonResponse(false, 'Password saat ini yang Anda masukkan salah.', null, 422);
                }
            }

            $newHash = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $stmtUp = $conn->prepare("UPDATE karyawan SET nama_karyawan = ?, email = ?, no_handphone = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ?, password = ? WHERE id_karyawan = ?");
            $stmtUp->bind_param("sssssisi", $nama, $email, $noHp, $tempatLahir, $tanggalLahir, $jenisKelamin, $newHash, $idKaryawan);
        } else {
            // Update data profil tanpa ganti password
            $stmtUp = $conn->prepare("UPDATE karyawan SET nama_karyawan = ?, email = ?, no_handphone = ?, tempat_lahir = ?, tanggal_lahir = ?, jenis_kelamin = ? WHERE id_karyawan = ?");
            $stmtUp->bind_param("sssssii", $nama, $email, $noHp, $tempatLahir, $tanggalLahir, $jenisKelamin, $idKaryawan);
        }

        if ($stmtUp->execute()) {
            $stmtUp->close();
            // Update session
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['no_handphone'] = $noHp;
            $_SESSION['nama'] = $nama;

            jsonResponse(true, 'Profil akun Anda berhasil diperbarui.', [
                'nama_karyawan' => $nama,
                'email' => $email,
                'no_handphone' => $noHp
            ]);
        } else {
            $err = $stmtUp->error;
            $stmtUp->close();
            jsonResponse(false, 'Gagal memperbarui profil: ' . $err, null, 500);
        }
    }

    // -------------------------------------------------------------
    // B. UPDATE UNTUK AKUN USERS (ADMIN)
    // -------------------------------------------------------------
    if (!empty($idUsers)) {
        if (!empty($passwordBaru)) {
            if ($passwordBaru !== $konfirmasiPassword) {
                jsonResponse(false, 'Konfirmasi password baru tidak cocok.', null, 422);
            }
            $newHash = password_hash($passwordBaru, PASSWORD_DEFAULT);
            $stmtUp = $conn->prepare("UPDATE users SET nama_users = ?, email = ?, password = ? WHERE id_users = ?");
            $stmtUp->bind_param("sssi", $nama, $email, $newHash, $idUsers);
        } else {
            $stmtUp = $conn->prepare("UPDATE users SET nama_users = ?, email = ? WHERE id_users = ?");
            $stmtUp->bind_param("ssi", $nama, $email, $idUsers);
        }

        if ($stmtUp->execute()) {
            $stmtUp->close();
            $_SESSION['user']['nama'] = $nama;
            $_SESSION['user']['email'] = $email;
            $_SESSION['nama'] = $nama;

            jsonResponse(true, 'Profil admin berhasil diperbarui.', [
                'nama_karyawan' => $nama,
                'email' => $email
            ]);
        } else {
            $err = $stmtUp->error;
            $stmtUp->close();
            jsonResponse(false, 'Gagal memperbarui profil admin: ' . $err, null, 500);
        }
    }

    jsonResponse(false, 'Pengguna tidak valid.', null, 400);
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
