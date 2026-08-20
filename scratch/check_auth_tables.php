<?php
require_once __DIR__ . '/../config/koneksi.php';

echo "=== TABLE USERS ===" . PHP_EOL;
$uCols = $conn->query("SHOW COLUMNS FROM users");
if ($uCols) {
    while($c = $uCols->fetch_assoc()) {
        echo "- {$c['Field']} ({$c['Type']})" . PHP_EOL;
    }
} else {
    echo "users table error: " . $conn->error . PHP_EOL;
}

echo PHP_EOL . "=== TABLE KARYAWAN ===" . PHP_EOL;
$kCols = $conn->query("SHOW COLUMNS FROM karyawan");
if ($kCols) {
    while($c = $kCols->fetch_assoc()) {
        echo "- {$c['Field']} ({$c['Type']})" . PHP_EOL;
    }
} else {
    echo "karyawan table error: " . $conn->error . PHP_EOL;
}

echo PHP_EOL . "=== SAMPLE USERS ===" . PHP_EOL;
$uRes = $conn->query("SELECT * FROM users");
while($r = $uRes->fetch_assoc()) {
    echo json_encode($r) . PHP_EOL;
}

echo PHP_EOL . "=== SAMPLE KARYAWAN ===" . PHP_EOL;
$kRes = $conn->query("SELECT id_karyawan, kode_karyawan, nama_lengkap, email, no_hp, username, password, pin, id_jabatan, aktif FROM karyawan");
while($r = $kRes->fetch_assoc()) {
    echo json_encode($r) . PHP_EOL;
}
