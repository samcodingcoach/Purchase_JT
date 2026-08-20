<?php
require_once __DIR__ . '/../config/koneksi.php';
$conn->query("UPDATE site SET penyimpanan_stok = 1 WHERE jenis_site IN ('Bengkel', 'Logistik', 'Pusat')");
$conn->query("UPDATE site SET penyimpanan_stok = 0 WHERE jenis_site = 'Office'");

$res = $conn->query("SELECT id_site, kode_site, nama_site, jenis_site, penyimpanan_stok FROM site");
while($r = $res->fetch_assoc()) {
    echo "ID: {$r['id_site']} | {$r['kode_site']} | {$r['nama_site']} | {$r['jenis_site']} | Penyimpanan: {$r['penyimpanan_stok']}" . PHP_EOL;
}
