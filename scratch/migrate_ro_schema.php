<?php
require_once __DIR__ . '/../config/koneksi.php';

echo "=== MIGRATING DATABASE SCHEMA FOR FASE 3 ===" . PHP_EOL;

// 1. Add id_barang to request_order_detail if not exists
$chkCol1 = $conn->query("SHOW COLUMNS FROM request_order_detail LIKE 'id_barang'");
if ($chkCol1 && $chkCol1->num_rows === 0) {
    $sql1 = "ALTER TABLE request_order_detail ADD COLUMN id_barang INT(11) NULL AFTER id_request";
    if ($conn->query($sql1)) {
        echo "✅ Added column 'id_barang' to 'request_order_detail'." . PHP_EOL;
    } else {
        echo "❌ Error adding 'id_barang': " . $conn->error . PHP_EOL;
    }
} else {
    echo "ℹ️ Column 'id_barang' already exists in 'request_order_detail'." . PHP_EOL;
}

// 2. Add prioritas to request_order if not exists
$chkCol2 = $conn->query("SHOW COLUMNS FROM request_order LIKE 'prioritas'");
if ($chkCol2 && $chkCol2->num_rows === 0) {
    $sql2 = "ALTER TABLE request_order ADD COLUMN prioritas ENUM('NORMAL', 'URGENT') NOT NULL DEFAULT 'NORMAL' AFTER status";
    if ($conn->query($sql2)) {
        echo "✅ Added column 'prioritas' to 'request_order'." . PHP_EOL;
    } else {
        echo "❌ Error adding 'prioritas': " . $conn->error . PHP_EOL;
    }
} else {
    echo "ℹ️ Column 'prioritas' already exists in 'request_order'." . PHP_EOL;
}

// 3. Ensure primary key and auto_increment on request_order if needed
$chkPk = $conn->query("SHOW KEYS FROM request_order WHERE Key_name = 'PRIMARY'");
if ($chkPk && $chkPk->num_rows === 0) {
    $conn->query("ALTER TABLE request_order ADD PRIMARY KEY (id_request)");
    $conn->query("ALTER TABLE request_order MODIFY id_request INT(11) NOT NULL AUTO_INCREMENT");
    echo "✅ Set PRIMARY KEY and AUTO_INCREMENT on 'request_order'." . PHP_EOL;
}

$chkPk2 = $conn->query("SHOW KEYS FROM request_order_detail WHERE Key_name = 'PRIMARY'");
if ($chkPk2 && $chkPk2->num_rows === 0) {
    $conn->query("ALTER TABLE request_order_detail ADD PRIMARY KEY (id_request_detail)");
    $conn->query("ALTER TABLE request_order_detail MODIFY id_request_detail INT(11) NOT NULL AUTO_INCREMENT");
    echo "✅ Set PRIMARY KEY and AUTO_INCREMENT on 'request_order_detail'." . PHP_EOL;
}

echo "=== MIGRATION COMPLETE ===" . PHP_EOL;
