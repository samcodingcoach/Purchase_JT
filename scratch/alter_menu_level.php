<?php
require_once __DIR__ . '/../config/koneksi.php';

// Check if urutan column already exists
$chkUrutan = $conn->query("SHOW COLUMNS FROM menu_level LIKE 'urutan'");
if ($chkUrutan && $chkUrutan->num_rows === 0) {
    $conn->query("ALTER TABLE menu_level ADD COLUMN urutan INT NULL DEFAULT 1 AFTER icon");
}

// Modify column widths
$conn->query("ALTER TABLE menu_level MODIFY COLUMN nama_menu VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT NULL");
$conn->query("ALTER TABLE menu_level MODIFY COLUMN icon VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT 'bi-circle'");

// Add indexes for high performance query in sidebar
$chkIdx = $conn->query("SHOW INDEX FROM menu_level WHERE Key_name = 'idx_jabatan_urutan'");
if ($chkIdx && $chkIdx->num_rows === 0) {
    $conn->query("ALTER TABLE menu_level ADD INDEX idx_jabatan_urutan (id_jabatan, urutan)");
}

echo "=== UPDATED COLUMNS IN menu_level ===" . PHP_EOL;
$cols = $conn->query("SHOW COLUMNS FROM menu_level");
while($c = $cols->fetch_assoc()) {
    echo "- {$c['Field']} ({$c['Type']}) Default: " . var_export($c['Default'], true) . PHP_EOL;
}
