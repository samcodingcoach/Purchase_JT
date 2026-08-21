<?php
require_once __DIR__ . '/../config/koneksi.php';

// Check site columns
$siteCols = $conn->query("SHOW COLUMNS FROM site");
echo "=== SITE COLUMNS ===" . PHP_EOL;
while($c = $siteCols->fetch_assoc()) echo "- {$c['Field']}" . PHP_EOL;

// Check vendor columns
$venCols = $conn->query("SHOW COLUMNS FROM vendor");
echo PHP_EOL . "=== VENDOR COLUMNS ===" . PHP_EOL;
while($c = $venCols->fetch_assoc()) echo "- {$c['Field']}" . PHP_EOL;

// Counts
$karyawanAktif = $conn->query("SELECT COUNT(*) as c FROM karyawan WHERE aktif = 1")->fetch_assoc()['c'];
$barangAktif = $conn->query("SELECT COUNT(*) as c FROM barang WHERE aktif = 1")->fetch_assoc()['c'];
$barangNonAktif = $conn->query("SELECT COUNT(*) as c FROM barang WHERE aktif = 0")->fetch_assoc()['c'];
$totalStok = $conn->query("SELECT COALESCE(SUM(stok), 0) as s FROM barang_stok")->fetch_assoc()['s'];
$vendorAktif = $conn->query("SELECT COUNT(*) as c FROM vendor WHERE aktif = 1")->fetch_assoc()['c'];
$siteAktif = $conn->query("SELECT COUNT(*) as c FROM site")->fetch_assoc()['c'];

echo PHP_EOL . "=== COUNTS ===" . PHP_EOL;
echo "Karyawan Aktif: $karyawanAktif" . PHP_EOL;
echo "Barang Aktif: $barangAktif" . PHP_EOL;
echo "Barang Non-Aktif: $barangNonAktif" . PHP_EOL;
echo "Total Stok: $totalStok" . PHP_EOL;
echo "Vendor Aktif: $vendorAktif" . PHP_EOL;
echo "Site Aktif: $siteAktif" . PHP_EOL;
