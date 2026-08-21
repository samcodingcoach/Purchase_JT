<?php
require_once __DIR__ . '/../config/koneksi.php';

echo "=== COLUMNS FROM request_order ===" . PHP_EOL;
$res = $conn->query("SHOW COLUMNS FROM request_order");
if ($res) {
    while($c = $res->fetch_assoc()) {
        echo "- {$c['Field']} ({$c['Type']}) Default: " . var_export($c['Default'], true) . PHP_EOL;
    }
} else {
    echo "Error: " . $conn->error . PHP_EOL;
}

echo PHP_EOL . "=== COLUMNS FROM request_order_detail ===" . PHP_EOL;
$res2 = $conn->query("SHOW COLUMNS FROM request_order_detail");
if ($res2) {
    while($c = $res2->fetch_assoc()) {
        echo "- {$c['Field']} ({$c['Type']}) Default: " . var_export($c['Default'], true) . PHP_EOL;
    }
} else {
    echo "Error: " . $conn->error . PHP_EOL;
}
