<?php
require_once __DIR__ . '/../config/koneksi.php';
$res = $conn->query('DESCRIBE barang');
while($r = $res->fetch_assoc()) {
    echo $r['Field'] . ' - ' . $r['Type'] . PHP_EOL;
}
