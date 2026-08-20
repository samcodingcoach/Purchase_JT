<?php
require_once __DIR__ . '/../config/koneksi.php';

$kRes = $conn->query("SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.email, k.no_handphone, k.password, k.login_web, k.aktif, j.nama_jabatan, j.level, d.nama_divisi 
                      FROM karyawan k 
                      LEFT JOIN jabatan j ON k.id_jabatan = j.id_jabatan 
                      LEFT JOIN divisi d ON k.id_divisi = d.id_divisi");
echo "=== LIST KARYAWAN ===" . PHP_EOL;
while($r = $kRes->fetch_assoc()) {
    echo "ID: {$r['id_karyawan']} | {$r['kode_karyawan']} | {$r['nama_karyawan']} | Email: {$r['email']} | Pass: " . substr($r['password'] ?? '', 0, 10) . "... | LoginWeb: {$r['login_web']} | Jabatan: {$r['nama_jabatan']} (Lvl: {$r['level']}) | Divisi: {$r['nama_divisi']}" . PHP_EOL;
}
