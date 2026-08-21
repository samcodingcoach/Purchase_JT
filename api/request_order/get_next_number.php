<?php
/**
 * API Request Order: Generate Next Number Endpoint - PT Jaya Teknis
 * Format: RO-YYMM-0000 (Reset setiap bulan)
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

$currentUser = apiAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan GET.', null, 405);
}

// Ambil parameter tanggal / tahun & bulan (default sekarang)
$dateParam = trim($_GET['date'] ?? '');
if (!empty($dateParam) && strtotime($dateParam)) {
    $time = strtotime($dateParam);
    $yymm = date('ym', $time);
    $yearFull = date('Y', $time);
    $month = date('m', $time);
} else {
    $yymm = date('ym');
    $yearFull = date('Y');
    $month = date('m');
}

$prefix = "RO-{$yymm}-";

// Cari nomor urut terbesar di bulan & tahun tersebut
$stmt = $conn->prepare("SELECT nomor FROM request_order WHERE nomor LIKE ? ORDER BY id_request DESC LIMIT 100");
$searchPattern = $prefix . "%";
$stmt->bind_param("s", $searchPattern);
$stmt->execute();
$res = $stmt->get_result();

$maxSequence = 0;
while ($row = $res->fetch_assoc()) {
    $numStr = $row['nomor'] ?? '';
    // Cocokkan pola RO-YYMM-XXXX
    if (preg_match('/^RO-\d{4}-(\d+)$/i', $numStr, $matches)) {
        $seq = (int)$matches[1];
        if ($seq > $maxSequence) {
            $maxSequence = $seq;
        }
    }
}
$stmt->close();

$nextSequence = $maxSequence + 1;
$nextNumber = $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

jsonResponse(true, 'Nomor RO berikutnya berhasil di-generate.', [
    'yymm' => $yymm,
    'year' => (int)$yearFull,
    'month' => (int)$month,
    'next_sequence' => $nextSequence,
    'nomor_ro' => $nextNumber
], 200);
