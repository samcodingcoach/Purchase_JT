<?php
require_once __DIR__ . '/../config/koneksi.php';

echo "=== JABATAN TABLE ===" . PHP_EOL;
$resJ = $conn->query("SELECT * FROM jabatan");
while($j = $resJ->fetch_assoc()) {
    print_r($j);
}

echo "=== KARYAWAN TABLE ===" . PHP_EOL;
$resK = $conn->query("SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.id_jabatan, j.nama_jabatan, k.id_divisi, d.nama_divisi FROM karyawan k LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan LEFT JOIN divisi d ON k.id_divisi = d.id_divisi");
while($k = $resK->fetch_assoc()) {
    print_r($k);
}
