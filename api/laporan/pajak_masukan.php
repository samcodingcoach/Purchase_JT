<?php
/**
 * REST API: Laporan Pajak Masukan (PPN & PPnBM Faktur Pembelian)
 * Endpoint: /api/laporan/pajak_masukan.php
 * Path: api/laporan/pajak_masukan.php
 * Khusus Role: ADMIN, FINANCE, MANAGER
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

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendJson(false, 'Metode HTTP tidak diizinkan. Gunakan GET.', null, 405);
}

try {
    // Parameter Filter
    $tahun = isset($_GET['tahun']) && is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
    $bulan = isset($_GET['bulan']) && is_numeric($_GET['bulan']) ? (int)$_GET['bulan'] : 0; // 0 = Semua Bulan
    $idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
    $search = trim($_GET['q'] ?? ($_GET['search'] ?? ''));

    // Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 15)));
    $isAll = isset($_GET['all']) && (int)$_GET['all'] === 1;

    // Filter Dasar: Faktur tidak batal dan nomor_faktur_pajak tidak kosong/null
    $where = " WHERE f.status <> 'BATAL' 
                 AND f.nomor_faktur_pajak IS NOT NULL 
                 AND TRIM(f.nomor_faktur_pajak) <> '' ";
    $params = [];
    $types = "";

    // Filter Berdasarkan Tahun & Bulan Tanggal Faktur Pajak (Fallback: Tanggal Terima Faktur jika tanggal_faktur_pajak belum diset)
    // Utamakan f.tanggal_faktur_pajak
    if ($tahun > 0) {
        $where .= " AND YEAR(COALESCE(f.tanggal_faktur_pajak, f.tanggal_faktur_vendor)) = ? ";
        $params[] = $tahun;
        $types .= "i";
    }

    if ($bulan >= 1 && $bulan <= 12) {
        $where .= " AND MONTH(COALESCE(f.tanggal_faktur_pajak, f.tanggal_faktur_vendor)) = ? ";
        $params[] = $bulan;
        $types .= "i";
    }

    if ($idVendor > 0) {
        $where .= " AND f.id_vendor = ? ";
        $params[] = $idVendor;
        $types .= "i";
    }

    if (!empty($search)) {
        $where .= " AND (
            f.nomor_faktur_pajak LIKE ? OR 
            f.nomor_faktur_vendor LIKE ? OR 
            f.nomor_faktur LIKE ? OR 
            po.nomor_po LIKE ? OR 
            v.nama_perusahaan LIKE ?
        ) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like, $like]);
        $types .= "sssss";
    }

    // 1. QUERY GRAND TOTAL SUMMARY
    $sqlGrandTotal = "SELECT 
                        COUNT(f.id_faktur) AS total_rows,
                        COALESCE(SUM(f.dpp), 0) AS grand_total_dpp,
                        COALESCE(SUM(f.nominal_pajak), 0) AS grand_total_ppn,
                        COALESCE(SUM(f.nominal_ppnbm), 0) AS grand_total_ppnbm,
                        COALESCE(SUM(f.total_tagihan), 0) AS grand_total_tagihan
                      FROM faktur_po f
                      INNER JOIN vendor v ON v.id_vendor = f.id_vendor
                      INNER JOIN purchase_order po ON po.id_po = f.id_po
                      {$where}";

    $stmtTotal = $conn->prepare($sqlGrandTotal);
    if (!empty($params)) {
        $stmtTotal->bind_param($types, ...$params);
    }
    $stmtTotal->execute();
    $resTotal = $stmtTotal->get_result()->fetch_assoc();
    $stmtTotal->close();

    $totalRows = (int)($resTotal['total_rows'] ?? 0);
    $grandTotals = [
        'total_faktur' => $totalRows,
        'grand_total_dpp' => (float)($resTotal['grand_total_dpp'] ?? 0),
        'grand_total_ppn' => (float)($resTotal['grand_total_ppn'] ?? 0),
        'grand_total_ppnbm' => (float)($resTotal['grand_total_ppnbm'] ?? 0),
        'grand_total_tagihan' => (float)($resTotal['grand_total_tagihan'] ?? 0),
    ];

    // 2. QUERY DAFTAR FAKTUR DENGAN PAGINATION
    $sqlList = "SELECT 
                    f.id_faktur,
                    COALESCE(f.tanggal_faktur_pajak, f.tanggal_faktur_vendor) AS tanggal_faktur_pajak,
                    f.tanggal_faktur_pajak AS tgl_pajak_asli,
                    f.nomor_faktur_pajak,
                    f.nomor_faktur_vendor,
                    f.nomor_faktur,
                    f.status,
                    f.term_of_payment,
                    f.tanggal_jatuh_tempo,
                    f.file_faktur_pajak,
                    f.file_faktur_vendor,

                    po.nomor_po,
                    v.id_vendor,
                    v.nama_perusahaan AS vendor,

                    COALESCE(f.subtotal_diterima, 0) AS subtotal_diterima,
                    COALESCE(f.nilai_retur, 0) AS nilai_retur,
                    COALESCE(f.diskon, 0) AS diskon,
                    COALESCE(f.dpp, 0) AS dpp,

                    COALESCE(f.rate_pajak, 0) AS rate_ppn,
                    COALESCE(f.nominal_pajak, 0) AS ppn_masukan,

                    COALESCE(f.rate_ppnbm, 0) AS rate_ppnbm,
                    COALESCE(f.nominal_ppnbm, 0) AS ppnbm,

                    COALESCE(f.biaya_lain, 0) AS biaya_lain,
                    COALESCE(f.total_tagihan, 0) AS total_tagihan
                FROM faktur_po f
                INNER JOIN vendor v ON v.id_vendor = f.id_vendor
                INNER JOIN purchase_order po ON po.id_po = f.id_po
                {$where}
                ORDER BY COALESCE(f.tanggal_faktur_pajak, f.tanggal_faktur_vendor) DESC, f.id_faktur DESC";

    if (!$isAll) {
        $offset = ($page - 1) * $limit;
        $sqlList .= " LIMIT ?, ? ";
        $params[] = $offset;
        $params[] = $limit;
        $types .= "ii";
    }

    $stmtList = $conn->prepare($sqlList);
    if (!empty($params)) {
        $stmtList->bind_param($types, ...$params);
    }
    $stmtList->execute();
    $resList = $stmtList->get_result();

    $rows = [];
    while ($r = $resList->fetch_assoc()) {
        $rows[] = [
            'id_faktur' => (int)$r['id_faktur'],
            'tanggal_faktur_pajak' => $r['tanggal_faktur_pajak'],
            'nomor_faktur_pajak' => $r['nomor_faktur_pajak'],
            'nomor_faktur_vendor' => $r['nomor_faktur_vendor'],
            'nomor_faktur' => $r['nomor_faktur'],
            'nomor_po' => $r['nomor_po'],
            'id_vendor' => (int)$r['id_vendor'],
            'vendor' => $r['vendor'],
            'npwp_vendor' => $r['npwp_vendor'] ?? '-',
            'status' => $r['status'],
            'dpp' => (float)$r['dpp'],
            'rate_ppn' => (float)$r['rate_ppn'],
            'ppn_masukan' => (float)$r['ppn_masukan'],
            'rate_ppnbm' => (float)$r['rate_ppnbm'],
            'ppnbm' => (float)$r['ppnbm'],
            'total_tagihan' => (float)$r['total_tagihan'],
            'file_faktur_pajak' => $r['file_faktur_pajak'],
            'file_faktur_vendor' => $r['file_faktur_vendor']
        ];
    }
    $stmtList->close();

    // Ambil daftar vendor untuk filter dropdown
    $vendors = [];
    $resV = $conn->query("SELECT DISTINCT v.id_vendor, v.nama_perusahaan 
                          FROM faktur_po f 
                          INNER JOIN vendor v ON v.id_vendor = f.id_vendor 
                          WHERE f.status <> 'BATAL' 
                          ORDER BY v.nama_perusahaan ASC");
    if ($resV) {
        while ($v = $resV->fetch_assoc()) {
            $vendors[] = [
                'id_vendor' => (int)$v['id_vendor'],
                'nama_perusahaan' => $v['nama_perusahaan']
            ];
        }
    }

    // Ambil daftar tahun yang tersedia di faktur_po
    $years = [];
    $resY = $conn->query("SELECT DISTINCT YEAR(COALESCE(tanggal_faktur_pajak, tanggal_faktur_vendor)) as thn FROM faktur_po WHERE status <> 'BATAL' ORDER BY thn DESC");
    if ($resY) {
        while ($y = $resY->fetch_assoc()) {
            if (!empty($y['thn'])) {
                $years[] = (int)$y['thn'];
            }
        }
    }
    if (!in_array((int)date('Y'), $years)) {
        array_unshift($years, (int)date('Y'));
    }

    sendJson(true, 'Data Laporan Pajak Masukan berhasil dimuat.', [
        'rows' => $rows,
        'grand_totals' => $grandTotals,
        'filter_options' => [
            'vendors' => $vendors,
            'years' => $years
        ],
        'pagination' => [
            'total' => $totalRows,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $limit > 0 ? (int)ceil($totalRows / $limit) : 1
        ]
    ]);

} catch (Exception $e) {
    sendJson(false, 'Terjadi kesalahan sistem: ' . $e->getMessage(), null, 500);
}
