<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/koneksi.php';

// Simulate logged in session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'ADMIN';

// Create a small test image
$testImgPath = __DIR__ . '/test_sample.jpg';
$im = imagecreatetruecolor(100, 100);
$bg = imagecolorallocate($im, 30, 82, 136);
imagefilledrectangle($im, 0, 0, 99, 99, $bg);
imagejpeg($im, $testImgPath);
imagedestroy($im);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['type'] = 'barang';
$_FILES['image'] = [
    'name' => 'test_sample.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => $testImgPath,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($testImgPath)
];

// Note: move_uploaded_file only works on HTTP POST uploads, but we can test the target folder and script syntax
$baseUploadDir = __DIR__ . '/../images/uploads/barang/';
if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0777, true);
}
echo "Upload dir exists: " . (is_dir($baseUploadDir) ? 'YES' : 'NO') . PHP_EOL;

$sampleTarget = $baseUploadDir . 'test_verify.jpg';
copy($testImgPath, $sampleTarget);
echo "File created successfully in uploads/barang: " . (file_exists($sampleTarget) ? 'YES' : 'NO') . PHP_EOL;

@unlink($testImgPath);
@unlink($sampleTarget);
