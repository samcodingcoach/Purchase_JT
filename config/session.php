<?php
/**
 * Session Handler & Auth Utilities - PT Jaya Teknis
 */

require_once __DIR__ . '/config.php';

// Konfigurasi session yang aman
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    
    session_start();
}

/**
 * Cek apakah session aktif dan terautentikasi
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Mendapatkan data user yang sedang login
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'nama' => $_SESSION['nama'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? '',
        'id_karyawan' => $_SESSION['id_karyawan'] ?? null,
        'kode_karyawan' => $_SESSION['kode_karyawan'] ?? '',
        'id_jabatan' => $_SESSION['id_jabatan'] ?? null,
        'nama_jabatan' => $_SESSION['nama_jabatan'] ?? '',
        'level_jabatan' => $_SESSION['level_jabatan'] ?? null,
        'id_divisi' => $_SESSION['id_divisi'] ?? null,
        'nama_divisi' => $_SESSION['nama_divisi'] ?? '',
        'id_site' => $_SESSION['id_site'] ?? null,
        'nama_site' => $_SESSION['nama_site'] ?? '',
        'api_token' => $_SESSION['api_token'] ?? ''
    ];
}

/**
 * Guard untuk halaman Admin UI (Redirect ke login jika belum login)
 */
function requireAuth(array $allowedRoles = []): array {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
    
    $user = getCurrentUser();
    
    if (!empty($allowedRoles) && !in_array($user['role'], $allowedRoles)) {
        http_response_code(403);
        echo "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                <h2>403 - Akses Ditolak</h2>
                <p>Role Anda (<strong>" . htmlspecialchars($user['role']) . "</strong>) tidak memiliki izin untuk mengakses halaman ini.</p>
                <a href='" . BASE_URL . "/admin/dashboard.php' style='color:#0d6efd;'>Kembali ke Dashboard</a>
              </div>";
        exit;
    }
    
    return $user;
}
