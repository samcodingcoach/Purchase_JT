<?php
require_once __DIR__ . '/../config/koneksi.php';

function checkTable($tableName, $conn) {
    echo "=== TABLE: $tableName ===" . PHP_EOL;
    $res = $conn->query("DESCRIBE `$tableName`");
    if ($res) {
        while($r = $res->fetch_assoc()) {
            echo "  " . $r['Field'] . " - " . $r['Type'] . " - Null:" . $r['Null'] . " - Key:" . $r['Key'] . " - Default:" . $r['Default'] . PHP_EOL;
        }
    } else {
        echo "  Table `$tableName` not found or error: " . $conn->error . PHP_EOL;
    }
}

checkTable('barang', $conn);
checkTable('barang_stok', $conn);
checkTable('barang_hargavendor', $conn);
