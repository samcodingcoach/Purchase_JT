<?php
require_once __DIR__ . '/../config/koneksi.php';

$hashAdmin = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("UPDATE karyawan SET password = '$hashAdmin'");
$conn->query("UPDATE users SET password = '$hashAdmin'");

echo "Updated all passwords to 'admin123'." . PHP_EOL;
