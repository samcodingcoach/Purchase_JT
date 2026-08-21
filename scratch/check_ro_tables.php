<?php
require_once __DIR__ . '/../config/koneksi.php';

echo "=== TABLES MATCHING 'request' or 'ro' or 'order' ===" . PHP_EOL;
$res = $conn->query("SHOW TABLES LIKE '%request%'");
while($r = $res->fetch_row()) {
    echo "- {$r[0]}" . PHP_EOL;
}
$res2 = $conn->query("SHOW TABLES LIKE '%ro%'");
while($r = $res2->fetch_row()) {
    echo "- {$r[0]}" . PHP_EOL;
}
$res3 = $conn->query("SHOW TABLES LIKE '%order%'");
while($r = $res3->fetch_row()) {
    echo "- {$r[0]}" . PHP_EOL;
}

$allTables = $conn->query("SHOW TABLES");
echo PHP_EOL . "=== ALL TABLES IN DATABASE ===" . PHP_EOL;
while($r = $allTables->fetch_row()) {
    echo "- {$r[0]}" . PHP_EOL;
}
