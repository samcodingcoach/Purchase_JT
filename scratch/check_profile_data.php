<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../config/config.php';

$res = $conn->query("SELECT * FROM profile");
echo "=== ALL ROWS IN PROFILE TABLE ===" . PHP_EOL;
while($r = $res->fetch_assoc()) {
    print_r($r);
}

echo "=== getCompanyProfile() RESULT ===" . PHP_EOL;
print_r(getCompanyProfile($conn));
