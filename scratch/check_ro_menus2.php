<?php
require_once __DIR__ . '/../config/koneksi.php';

$res = $conn->query("SELECT * FROM menu_level WHERE url LIKE '%request_order%' OR nama_menu LIKE '%Request%' OR nama_menu LIKE '%RO%'");
if ($res) {
    while($r = $res->fetch_assoc()) {
        print_r($r);
    }
}
