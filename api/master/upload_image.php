<?php
/**
 * API Upload Image - PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan POST.', null, 405);
}

// Deteksi berkas dari field 'image', 'foto', atau 'file'
$file = $_FILES['image'] ?? $_FILES['foto'] ?? $_FILES['file'] ?? null;

if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'Tidak ada berkas gambar yang diunggah atau terjadi kesalahan saat upload.', null, 400);
}

$type = trim($_POST['type'] ?? $_POST['subfolder'] ?? 'barang'); // 'barang' | 'profile' | 'company'

// Direktori target
$subDir = ($type === 'profile' || $type === 'company') ? 'company/' : 'barang/';
$baseUploadDir = __DIR__ . '/../../images/uploads/' . $subDir;

if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0777, true);
}

// Validasi ukuran (maks 10MB)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonResponse(false, 'Ukuran gambar maksimal adalah 10MB.', null, 422);
}

// Validasi Ekstensi & MIME
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes)) {
    jsonResponse(false, 'Format berkas tidak valid. Harap upload gambar berekstensi JPG, PNG, atau WEBP.', null, 422);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (empty($ext)) $ext = 'jpg';

$prefix = ($type === 'profile' || $type === 'company') ? 'company_' : 'brg_';
$filename = $prefix . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
$targetPath = $baseUploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativeUrl = 'images/uploads/' . $subDir . $filename;
    jsonResponse(true, 'Gambar berhasil diunggah.', [
        'filename' => $filename,
        'url' => $relativeUrl,
        'file_path' => $relativeUrl
    ], 200);
} else {
    jsonResponse(false, 'Gagal memindahkan berkas yang diunggah ke folder target.', null, 500);
}
