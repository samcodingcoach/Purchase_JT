<?php
require_once __DIR__ . '/../config/koneksi.php';
$res = $conn->query("SHOW COLUMNS FROM menu_level");
while($r = $res->fetch_assoc()) {
    echo "- {$r['Field']}" . PHP_EOL;
}
$res2 = $conn->query("SELECT * FROM menu_level LIMIT 5");
while($r = $res2->fetch_assoc()) {
    print_r($r);
}
