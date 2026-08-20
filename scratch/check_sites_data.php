<?php
require_once __DIR__ . '/../config/koneksi.php';
$res = $conn->query("SELECT id_site, kode_site, nama_site, jenis_site, penyimpanan_stok FROM site");
while($r = $res->fetch_assoc()) {
    echo "ID: {$r['id_site']} | Kode: {$r['kode_site']} | Nama: {$r['nama_site']} | Jenis: {$r['jenis_site']} | Penyimpanan Stok: {$r['penyimpanan_stok']}" . PHP_EOL;
}
