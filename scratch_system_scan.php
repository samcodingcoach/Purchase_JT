<?php
require_once 'config/koneksi.php';

echo "=== SCAN INTEGRITAS DATABASE & SYSTEM ===\n";

// 1. Cek tabel-tabel utama
$tables = [
    'users', 'karyawan', 'jabatan', 'divisi', 'site', 'vendor',
    'barang', 'barang_kategori', 'barang_stok',
    'request_order', 'request_order_detail',
    'purchase_order', 'purchase_order_detail',
    'receiving', 'receiving_detail',
    'faktur_po', 'faktur_po_detail',
    'pembayaran_po', 'pembayaran_po_detail',
    'retur_po', 'retur_po_detail',
    'mutasi_order', 'mutasi_order_detail',
    'menu_level', 'activity_log', 'profile'
];

foreach ($tables as $t) {
    $res = $conn->query("SHOW TABLES LIKE '$t'");
    if ($res && $res->num_rows > 0) {
        $cnt = $conn->query("SELECT COUNT(*) as c FROM $t")->fetch_assoc()['c'];
        echo "[OK] Table `$t` exists (Rows: $cnt)\n";
    } else {
        echo "[MISSING] Table `$t` does NOT exist!\n";
    }
}

echo "\n=== SCAN INTEGRITAS MENU_LEVEL PER JABATAN ===\n";
$jabs = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan");
while ($j = $jabs->fetch_assoc()) {
    $jId = $j['id_jabatan'];
    $cntM = $conn->query("SELECT COUNT(*) as c FROM menu_level WHERE id_jabatan = $jId")->fetch_assoc()['c'];
    echo "- Jabatan [{$j['nama_jabatan']}] (ID $jId): $cntM menu terdaftar\n";
}

echo "\n=== SCAN LINK MENU YANG TIDAK DITEMUKAN PADA FILESYSTEM ===\n";
$menus = $conn->query("SELECT id_levelmenu, id_jabatan, nama_menu, link FROM menu_level WHERE link IS NOT NULL AND link != '#' AND link != ''");
$broken = 0;
while ($m = $menus->fetch_assoc()) {
    $link = ltrim($m['link'], '/');
    if (strpos($link, '?') !== false) {
        $link = substr($link, 0, strpos($link, '?'));
    }
    if (!file_exists($link)) {
        echo "[BROKEN LINK] Menu ID {$m['id_levelmenu']} (Jabatan {$m['id_jabatan']}) '{$m['nama_menu']}' -> Link '$link' TIDAK DITEMUKAN di folder!\n";
        $broken++;
    }
}
if ($broken === 0) {
    echo "[OK] Semua link di menu_level valid dan filenya ada di server.\n";
}

echo "\n=== SCAN STOK NEGATIF DI BARANG_STOK ===\n";
$neg = $conn->query("SELECT * FROM barang_stok WHERE stok < 0");
if ($neg && $neg->num_rows > 0) {
    while ($r = $neg->fetch_assoc()) {
        echo "[WARNING] Stok negatif ditemukan: Barang ID {$r['id_barang']} di Site ID {$r['id_site']} (Stok: {$r['stok']})\n";
    }
} else {
    echo "[OK] Tidak ada stok negatif di barang_stok.\n";
}
