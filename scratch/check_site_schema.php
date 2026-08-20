<?php
require_once __DIR__ . '/../config/koneksi.php';
$res = $conn->query("DESCRIBE site");
while($r = $res->fetch_assoc()) {
    echo $r['Field'] . ' - ' . $r['Type'] . ' - Null:' . $r['Null'] . ' - Default:' . $r['Default'] . PHP_EOL;
}
