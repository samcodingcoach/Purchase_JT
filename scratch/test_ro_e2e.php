<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/koneksi.php';

// Login simulation
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'MEKANIK';
$_SESSION['id_karyawan'] = 2;

echo "=== 1. VERIFYING RO AUTO NUMBER GENERATION ===" . PHP_EOL;
$year = (int)date('Y');
$stmtSeq = $conn->prepare("SELECT nomor FROM request_order WHERE nomor LIKE ? ORDER BY id_request DESC LIMIT 50");
$searchPattern = "RO-{$year}-%";
$stmtSeq->bind_param("s", $searchPattern);
$stmtSeq->execute();
$resSeq = $stmtSeq->get_result();

$maxSequence = 0;
while ($row = $resSeq->fetch_assoc()) {
    $numStr = $row['nomor'] ?? '';
    if (preg_match('/^RO-\d{4}-(\d+)$/i', $numStr, $matches)) {
        $seq = (int)$matches[1];
        if ($seq > $maxSequence) $maxSequence = $seq;
    }
}
$stmtSeq->close();
$expectedNext = "RO-{$year}-" . str_pad($maxSequence + 1, 4, '0', STR_PAD_LEFT);
echo "Expected Next RO Number: {$expectedNext}" . PHP_EOL;

echo PHP_EOL . "=== 2. VERIFYING RO CREATION TRANSACTION (DRAFT) ===" . PHP_EOL;
$conn->begin_transaction();
try {
    $stmtHeader = $conn->prepare("INSERT INTO request_order (nomor, tanggal_ro, id_karyawan, id_site, status, prioritas, keterangan) VALUES (?, NOW(), 2, 1, 'DRAFT', 'URGENT', 'Uji Coba RO Mesin Induk')");
    $stmtHeader->bind_param("s", $expectedNext);
    $stmtHeader->execute();
    $newRoId = $conn->insert_id;
    $stmtHeader->close();

    $stmtDetail = $conn->prepare("INSERT INTO request_order_detail (id_request, id_barang, kode_barang, nama_barang, qty, satuan, harga, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $item1 = [1, 'BRG0001', 'Plat Baja Marine AH36', 3, 'PCS', 1500000, 4500000];
    $stmtDetail->bind_param("iissdsdd", $newRoId, $item1[0], $item1[1], $item1[2], $item1[3], $item1[4], $item1[5], $item1[6]);
    $stmtDetail->execute();

    $item2 = [null, '', 'Oli Mesin Meditran S40', 10, 'PCS', 45000, 450000];
    $stmtDetail->bind_param("iissdsdd", $newRoId, $item2[0], $item2[1], $item2[2], $item2[3], $item2[4], $item2[5], $item2[6]);
    $stmtDetail->execute();
    $stmtDetail->close();

    $conn->commit();
    echo "✅ RO created with ID: {$newRoId} and 2 items." . PHP_EOL;

    // Verify select
    $chkRo = $conn->query("SELECT ro.*, k.nama_lengkap as pemohon, s.nama_site FROM request_order ro JOIN karyawan k ON ro.id_karyawan = k.id_karyawan JOIN site s ON ro.id_site = s.id_site WHERE ro.id_request = {$newRoId}")->fetch_assoc();
    echo "Header Check: {$chkRo['nomor']} | Status: {$chkRo['status']} | Prioritas: {$chkRo['prioritas']} | Pemohon: {$chkRo['pemohon']} | Site: {$chkRo['nama_site']}" . PHP_EOL;

    $chkItems = $conn->query("SELECT * FROM request_order_detail WHERE id_request = {$newRoId}");
    while($it = $chkItems->fetch_assoc()) {
        echo "- Item: {$it['nama_barang']} ({$it['qty']} {$it['satuan']}) @ Rp " . number_format($it['harga'], 0, ',', '.') . " = Rp " . number_format($it['subtotal'], 0, ',', '.') . PHP_EOL;
    }

    // Clean up test data
    $conn->query("DELETE FROM request_order_detail WHERE id_request = {$newRoId}");
    $conn->query("DELETE FROM request_order WHERE id_request = {$newRoId}");
    echo "✅ Test data cleaned up successfully." . PHP_EOL;
} catch (Exception $e) {
    $conn->rollback();
    echo "❌ Error: " . $e->getMessage() . PHP_EOL;
}
