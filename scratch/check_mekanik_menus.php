<?php
require_once __DIR__ . '/../config/koneksi.php';
$res = $conn->query("SELECT * FROM menu_level WHERE id_jabatan = 2");
while($r = $res->fetch_assoc()) {
    echo "ID: {$r['id_levelmenu']} | Menu: {$r['nama_menu']} | Link: {$r['link']} | Akses: {$r['akses']} | Terlihat: {$r['terlihat']}" . PHP_EOL;
}
