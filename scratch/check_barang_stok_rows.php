<?php
require_once __DIR__ . '/../config/koneksi.php';

// Check current rows in barang_stok
$res = $conn->query("SELECT bs.*, b.nama_barang, s.nama_site FROM barang_stok bs JOIN barang b ON bs.id_barang = b.id_barang JOIN site s ON bs.id_site = s.id_site");
echo "=== BARANG_STOK RECORDS ===" . PHP_EOL;
$count = 0;
while($r = $res->fetch_assoc()) {
    $count++;
    echo "ID Stok: {$r['id_stok']} | Barang: {$r['nama_barang']} (ID: {$r['id_barang']}) | Site: {$r['nama_site']} (ID: {$r['id_site']}) | Stok: {$r['stok']}" . PHP_EOL;
}
if ($count === 0) {
    echo "No records yet in barang_stok." . PHP_EOL;
}
