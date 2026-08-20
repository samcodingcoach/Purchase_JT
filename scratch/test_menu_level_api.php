<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/koneksi.php';

// Simulate logged in session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'ADMIN';
$_SESSION['id_jabatan'] = 1;

// Capture output of GET request to api/master/menu_level.php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id_jabatan'] = 1;

ob_start();
include __DIR__ . '/../api/master/menu_level.php';
$out = ob_get_clean();

echo "Response from api/master/menu_level.php:" . PHP_EOL;
echo $out . PHP_EOL;
