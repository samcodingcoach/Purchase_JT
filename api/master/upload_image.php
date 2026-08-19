<?php
/**
 * API Upload Image - PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'Tidak ada berkas gambar yang diunggah atau terjadi kesalahan saat upload.', null, 400);
}

$file = $_FILES['image'];
$type = trim($_POST['type'] ?? 'barang'); // 'barang' | 'profile'

// Direktori target
$baseUploadDir = __DIR__ . '/../../images/uploads/' . ($type === 'profile' ? 'company/' : 'barang/');
if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0777, true);
}

// Validasi ukuran (maks 10MB)
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonResponse(false, 'Ukuran gambar maksimal adalah 10MB.', null, 422);
}

// Validasi Ekstensi & MIME
$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedMimes)) {
    jsonResponse(false, 'Format berkas tidak valid. Harap upload gambar berekstensi JPG, PNG, atau WEBP.', null, 422);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = ($type === 'profile' ? 'company_' : 'brg_') . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
$targetPath = $baseUploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativeUrl = 'images/uploads/' . ($type === 'profile' ? 'company/' : 'barang/') . $filename;
    jsonResponse(true, 'Gambar berhasil diunggah.', [
        'filename' => $filename,
        'url' => $relativeUrl
    ], 200);
} else {
    jsonResponse(false, 'Gagal memindahkan berkas yang diunggah.', null, 500);
}
