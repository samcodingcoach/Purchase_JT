<?php
require_once __DIR__ . '/../config/koneksi.php';

$res = $conn->query("SELECT j.id_jabatan, j.nama_jabatan, j.level, d.nama_divisi FROM jabatan j JOIN divisi d ON j.id_divisi = d.id_divisi ORDER BY j.id_jabatan");
echo "=== LIST JABATAN ===" . PHP_EOL;
while($r = $res->fetch_assoc()) {
    echo "ID: {$r['id_jabatan']} | {$r['nama_jabatan']} (Level: {$r['level']}) - Divisi: {$r['nama_divisi']}" . PHP_EOL;
}
