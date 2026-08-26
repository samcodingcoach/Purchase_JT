<?php
/**
 * API Receiving: Generate Nomor RCV Otomatis
 * Path: api/receiving/get_next_number.php
 * Format: RCV-YYMM-XXXX
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

apiAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER]);

$tanggal = isset($_GET['tanggal']) && !empty($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');
$yearMonth = date('ym', strtotime($tanggal));
$prefix = "RCV-{$yearMonth}-";

$stmt = $conn->prepare("SELECT nomor_rcv FROM receiving_order WHERE nomor_rcv LIKE CONCAT(?, '%') ORDER BY id_rcv DESC LIMIT 1");
$stmt->bind_param("s", $prefix);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nextSeq = 1;
if ($row && !empty($row['nomor_rcv'])) {
    $lastSeqStr = substr($row['nomor_rcv'], strlen($prefix));
    $nextSeq = (int)$lastSeqStr + 1;
}

$nextNumber = $prefix . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

jsonResponse(true, 'Nomor Receiving berhasil digenerate.', [
    'nomor_rcv' => $nextNumber,
    'prefix' => $prefix,
    'sequence' => $nextSeq
]);
