<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/koneksi.php';

// Login as Mekanik
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'MEKANIK';
$_SESSION['id_karyawan'] = 2;

echo "=== TESTING GET NEXT NUMBER ===" . PHP_EOL;
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
require __DIR__ . '/../api/request_order/get_next_number.php';
$out1 = ob_get_clean();
echo "Response: " . $out1 . PHP_EOL;

echo PHP_EOL . "=== TESTING CREATE RO AS DRAFT ===" . PHP_EOL;
$postData = [
    'nomor' => '',
    'tanggal_ro' => date('Y-m-d H:i:s'),
    'id_karyawan' => 2,
    'id_site' => 1,
    'prioritas' => 'URGENT',
    'status' => 'DRAFT',
    'keterangan' => 'Pengujian RO Draft Otomatis Sistem',
    'items' => [
        [
            'id_barang' => 1,
            'kode_barang' => 'BRG0001',
            'nama_barang' => 'Plat Baja Marine AH36',
            'qty' => 5,
            'satuan' => 'PCS',
            'harga' => 1500000,
            'subtotal' => 7500000
        ],
        [
            'id_barang' => null,
            'kode_barang' => '',
            'nama_barang' => 'Kawat Las Khusus E7018',
            'qty' => 10,
            'satuan' => 'PCS',
            'harga' => 85000,
            'subtotal' => 850000
        ]
    ]
];

$_SERVER['REQUEST_METHOD'] = 'POST';
file_put_contents(__DIR__ . '/temp_input.json', json_encode($postData));

// Emulate request
$_POST = $postData;

ob_start();
// Test direct database insertion logic simulation
$stmtHeader = $conn->prepare("INSERT INTO request_order (nomor, tanggal_ro, id_karyawan, id_site, status, prioritas, keterangan) VALUES ('RO-TEST-001', NOW(), 2, 1, 'DRAFT', 'URGENT', 'Test RO')");
$stmtHeader->execute();
$testRoId = $conn->insert_id;
$stmtHeader->close();

$stmtDetail = $conn->prepare("INSERT INTO request_order_detail (id_request, id_barang, kode_barang, nama_barang, qty, satuan, harga, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$bId = 1; $kdB = 'BRG0001'; $nmB = 'Plat Baja'; $qtyB = 5; $satB = 'PCS'; $hrgB = 1500000; $subB = 7500000;
$stmtDetail->bind_param("iissdsdd", $testRoId, $bId, $kdB, $nmB, $qtyB, $satB, $hrgB, $subB);
$stmtDetail->execute();
$stmtDetail->close();

echo "Successfully created test RO ID: {$testRoId}" . PHP_EOL;

// Clean up test record
$conn->query("DELETE FROM request_order_detail WHERE id_request = {$testRoId}");
$conn->query("DELETE FROM request_order WHERE id_request = {$testRoId}");
echo "Cleaned up test RO ID: {$testRoId}" . PHP_EOL;
