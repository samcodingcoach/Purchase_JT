<?php
/**
 * Database Auto-Increment & Seeder Fix Utility - PT Jaya Teknis
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/koneksi.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Pastikan Auto Increment aktif di tabel-tabel utama jika belum
    $tables = ['karyawan' => 'id_karyawan', 'site' => 'id_site', 'barang' => 'id_barang', 'users' => 'id_users', 'vendor' => 'id_vendor'];
    foreach ($tables as $tbl => $pk) {
        $checkAI = $conn->query("SHOW COLUMNS FROM `{$tbl}` WHERE Field = '{$pk}'");
        if ($checkAI && $row = $checkAI->fetch_assoc()) {
            if (strpos($row['Extra'], 'auto_increment') === false) {
                // Set auto increment
                $conn->query("ALTER TABLE `{$tbl}` MODIFY `{$pk}` INT(11) NOT NULL AUTO_INCREMENT");
                echo "Enabled AUTO_INCREMENT on {$tbl}.{$pk}\n";
            }
        }
    }
    echo "Check completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
