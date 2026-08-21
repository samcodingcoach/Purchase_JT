<?php
$baseUploadDir = __DIR__ . '/../images/uploads/barang/';
if (!is_dir($baseUploadDir)) {
    mkdir($baseUploadDir, 0777, true);
}
echo "Upload dir exists: " . (is_dir($baseUploadDir) ? 'YES' : 'NO') . PHP_EOL;

$sampleTarget = $baseUploadDir . 'test_verify.txt';
file_put_contents($sampleTarget, "Test content");
echo "File created successfully in uploads/barang: " . (file_exists($sampleTarget) ? 'YES' : 'NO') . PHP_EOL;

@unlink($sampleTarget);
