<?php
require_once __DIR__ . '/../config/koneksi.php';
$res = $conn->query("DESCRIBE jabatan");
if ($res) {
    while($r = $res->fetch_assoc()) {
        echo $r['Field'] . ' - ' . $r['Type'] . PHP_EOL;
    }
} else {
    echo "Table jabatan not found: " . $conn->error . PHP_EOL;
}
