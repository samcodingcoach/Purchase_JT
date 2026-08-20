<?php
require_once __DIR__ . '/../config/koneksi.php';

// Initialize default 0 stock for existing barang in storage sites
$barangRes = $conn->query("SELECT id_barang, nama_barang FROM barang");
$siteRes = $conn->query("SELECT id_site, nama_site FROM site WHERE penyimpanan_stok = 1");

$sites = [];
while($s = $siteRes->fetch_assoc()) {
    $sites[] = $s;
}

$inserted = 0;
while($b = $barangRes->fetch_assoc()) {
    $bId = (int)$b['id_barang'];
    foreach ($sites as $site) {
        $sId = (int)$site['id_site'];
        $chk = $conn->query("SELECT id_stok FROM barang_stok WHERE id_barang = $bId AND id_site = $sId");
        if ($chk && $chk->num_rows === 0) {
            $conn->query("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES ($bId, $sId, 0)");
            $inserted++;
        }
    }
}

echo "Berhasil sinkronisasi inisialisasi $inserted baris stok bernilai 0 di tabel barang_stok." . PHP_EOL;

$res = $conn->query("SELECT bs.*, b.nama_barang, s.nama_site FROM barang_stok bs JOIN barang b ON bs.id_barang = b.id_barang JOIN site s ON bs.id_site = s.id_site");
while($r = $res->fetch_assoc()) {
    echo "ID Stok: {$r['id_stok']} | {$r['nama_barang']} (ID: {$r['id_barang']}) | Site: {$r['nama_site']} (ID: {$r['id_site']}) | Stok: {$r['stok']}" . PHP_EOL;
}
