<?php
require_once __DIR__ . '/../config/koneksi.php';

$res = $conn->query("SELECT id_level, id_jabatan, nama_menu, link, icon, parent_id, urutan, aktif FROM menu_level WHERE link LIKE '%request%' OR nama_menu LIKE '%Request%'");
if ($res) {
    while($r = $res->fetch_assoc()) {
        echo "ID: {$r['id_level']} | Jabatan: {$r['id_jabatan']} | Menu: {$r['nama_menu']} | Link: {$r['link']} | Aktif: {$r['aktif']}" . PHP_EOL;
    }
}
