<?php
/**
 * API Purchase Order: Generate Next Number Endpoint - PT Jaya Teknis
 * Format: 
 * - Regular: PO-YYMM-0001 (Reset setiap bulan)
 * - Draft: DRF-PO-YYMM-01 (Reset setiap bulan)
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

// Cek apakah mode draft
$isDraft = isset($_GET['draft']) && ($_GET['draft'] == '1' || strtolower($_GET['draft']) === 'true' || strtolower($_GET['type'] ?? '') === 'draft');

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

if ($isDraft) {
    $prefix = "DRF-PO-{$yymm}-";
    $searchPattern = "DRF-PO-{$yymm}-%";
    $regex = '/^DRF-PO-\d{4}-(\d+)$/i';
    $padLen = 2; // DRF-PO-2608-01
} else {
    $prefix = "PO-{$yymm}-";
    $searchPattern = "PO-{$yymm}-%";
    $regex = '/^PO-\d{4}-(\d+)$/i';
    $padLen = 4; // PO-2608-0001
}

// Cari nomor urut terbesar di bulan & tahun tersebut
$stmt = $conn->prepare("SELECT nomor_po FROM purchase_order WHERE nomor_po LIKE ? ORDER BY id_po DESC LIMIT 100");
$stmt->bind_param("s", $searchPattern);
$stmt->execute();
$res = $stmt->get_result();

$maxSequence = 0;
while ($row = $res->fetch_assoc()) {
    $numStr = $row['nomor_po'] ?? '';
    if (preg_match($regex, $numStr, $matches)) {
        $seq = (int)$matches[1];
        if ($seq > $maxSequence) {
            $maxSequence = $seq;
        }
    }
}
$stmt->close();

$nextSequence = $maxSequence + 1;
$nextNumber = $prefix . str_pad($nextSequence, $padLen, '0', STR_PAD_LEFT);

jsonResponse(true, 'Nomor PO berikutnya berhasil di-generate.', [
    'is_draft' => $isDraft,
    'yymm' => $yymm,
    'year' => (int)$yearFull,
    'month' => (int)$month,
    'next_sequence' => $nextSequence,
    'nomor_po' => $nextNumber
], 200);
