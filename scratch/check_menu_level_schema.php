<?php
require_once __DIR__ . '/../config/koneksi.php';

// Check if menu_level exists
$chk = $conn->query("SHOW TABLES LIKE 'menu_level'");
if ($chk && $chk->num_rows > 0) {
    echo "Table menu_level exists. Checking columns:" . PHP_EOL;
    $cols = $conn->query("SHOW COLUMNS FROM menu_level");
    while($c = $cols->fetch_assoc()) {
        echo "- {$c['Field']} ({$c['Type']})" . PHP_EOL;
    }
} else {
    echo "Table menu_level does not exist yet." . PHP_EOL;
}
