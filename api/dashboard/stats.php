<?php
/**
 * RESTful API Dashboard Statistics - PT Jaya Teknis
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
    jsonResponse(false, 'Metode HTTP tidak diizinkan. Gunakan GET.', null, 405);
}

// -------------------------------------------------------------
// 1. STATISTIK MASTER DATA & INVENTARIS (Khusus Admin / Manajemen)
// -------------------------------------------------------------
$karyawanAktif = 0;
$barangAktif = 0;
$barangNonAktif = 0;
$totalStok = 0;
$vendorAktif = 0;
$siteAktif = 0;

$resKaryawan = $conn->query("SELECT COUNT(*) as c FROM karyawan WHERE aktif = 1");
if ($resKaryawan) $karyawanAktif = (int)$resKaryawan->fetch_assoc()['c'];

$resBarangAktif = $conn->query("SELECT COUNT(*) as c FROM barang WHERE aktif = 1");
if ($resBarangAktif) $barangAktif = (int)$resBarangAktif->fetch_assoc()['c'];

$resBarangNonAktif = $conn->query("SELECT COUNT(*) as c FROM barang WHERE aktif = 0");
if ($resBarangNonAktif) $barangNonAktif = (int)$resBarangNonAktif->fetch_assoc()['c'];

$resTotalStok = $conn->query("SELECT COALESCE(SUM(stok), 0) as s FROM barang_stok");
if ($resTotalStok) $totalStok = (int)$resTotalStok->fetch_assoc()['s'];

$resVendorAktif = $conn->query("SELECT COUNT(*) as c FROM vendor WHERE aktif = 1");
if ($resVendorAktif) $vendorAktif = (int)$resVendorAktif->fetch_assoc()['c'];

$resSiteAktif = $conn->query("SELECT COUNT(*) as c FROM site");
if ($resSiteAktif) $siteAktif = (int)$resSiteAktif->fetch_assoc()['c'];

// -------------------------------------------------------------
// 2. STATISTIK REQUEST ORDER (OPERASIONAL)
// -------------------------------------------------------------
$roDraft = 0;
$roSubmitted = 0;
$roApproved = 0;
$roRejected = 0;
$roProcessing = 0;

// Periksa apakah tabel request_order sudah dibuat
$chkRo = $conn->query("SHOW TABLES LIKE 'request_order'");
if ($chkRo && $chkRo->num_rows > 0) {
    $whereUser = "";
    if ($currentUser['role'] === ROLE_MEKANIK && !empty($currentUser['id_karyawan'])) {
        $idK = (int)$currentUser['id_karyawan'];
        $whereUser = " WHERE id_karyawan = $idK";
    }

    $resDraft = $conn->query("SELECT COUNT(*) as c FROM request_order " . ($whereUser ? "$whereUser AND status = 'DRAFT'" : "WHERE status = 'DRAFT'"));
    if ($resDraft) $roDraft = (int)$resDraft->fetch_assoc()['c'];

    $resSub = $conn->query("SELECT COUNT(*) as c FROM request_order " . ($whereUser ? "$whereUser AND status = 'TERKIRIM'" : "WHERE status = 'TERKIRIM'"));
    if ($resSub) $roSubmitted = (int)$resSub->fetch_assoc()['c'];

    $resApp = $conn->query("SELECT COUNT(*) as c FROM request_order " . ($whereUser ? "$whereUser AND status = 'DISETUJUI'" : "WHERE status = 'DISETUJUI'"));
    if ($resApp) $roApproved = (int)$resApp->fetch_assoc()['c'];

    $resRej = $conn->query("SELECT COUNT(*) as c FROM request_order " . ($whereUser ? "$whereUser AND status = 'TIDAK DISETUJUI'" : "WHERE status = 'TIDAK DISETUJUI'"));
    if ($resRej) $roRejected = (int)$resRej->fetch_assoc()['c'];
}

$data = [
    'master_admin' => [
        'karyawan_aktif' => $karyawanAktif,
        'barang_aktif' => $barangAktif,
        'barang_nonaktif' => $barangNonAktif,
        'total_stok' => $totalStok,
        'vendor_aktif' => $vendorAktif,
        'site_aktif' => $siteAktif
    ],
    'request_order' => [
        'draft' => $roDraft,
        'submitted' => $roSubmitted,
        'processing' => $roProcessing,
        'approved' => $roApproved,
        'rejected' => $roRejected
    ]
];

jsonResponse(true, 'Data statistik dashboard berhasil dimuat.', $data, 200);
