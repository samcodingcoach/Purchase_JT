<?php
require_once __DIR__ . '/../config/koneksi.php';

echo "=== DATA IN BARANG_STOK ===" . PHP_EOL;
$res = $conn->query("SELECT * FROM barang_stok LIMIT 10");
while($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "=== DATA IN BARANG_HARGAVENDOR ===" . PHP_EOL;
$res = $conn->query("SELECT * FROM barang_hargavendor LIMIT 10");
while($r = $res->fetch_assoc()) {
    print_r($r);
}
