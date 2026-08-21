<?php
require_once __DIR__ . '/../config/koneksi.php';

echo "=== CHECKING MENU_LEVEL FOR REQUEST_ORDER ===" . PHP_EOL;
$res = $conn->query("SELECT id_menu, id_jabatan, nama_menu, url, icon, parent_id, aktif FROM menu_level WHERE url LIKE '%request_order%' OR nama_menu LIKE '%Request%' OR nama_menu LIKE '%RO%'");
if ($res) {
    while($r = $res->fetch_assoc()) {
        echo "- ID: {$r['id_menu']} | Jabatan: {$r['id_jabatan']} | Menu: {$r['nama_menu']} | URL: {$r['url']} | Active: {$r['aktif']}" . PHP_EOL;
    }
}
