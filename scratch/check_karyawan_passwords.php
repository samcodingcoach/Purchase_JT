<?php
require_once __DIR__ . '/../config/koneksi.php';

$res = $conn->query("SELECT id_karyawan, kode_karyawan, nama_karyawan, password FROM karyawan");
while($r = $res->fetch_assoc()) {
    echo "ID: {$r['id_karyawan']} | {$r['kode_karyawan']} | {$r['nama_karyawan']} | Pass: {$r['password']}" . PHP_EOL;
}
