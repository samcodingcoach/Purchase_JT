<?php
/**
 * API Purchase Order: CRUD / List Endpoint - PT Jaya Teknis
 * Path: api/purchase_order/index.php
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
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : null;

    // 1. Single PO Detail
    if ($id) {
        $stmt = $conn->prepare("SELECT p.*, v.nama_vendor, v.telepon as telepon_vendor, v.email as email_vendor,
                                       s.nama_site, s.alamat as alamat_site,
                                       k.nama_karyawan as nama_pembuat,
                                       ka.nama_karyawan as nama_approver,
                                       ro.id_request, ro.nomor as nomor_ro
                                FROM purchase_order p
                                LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
                                LEFT JOIN site s ON p.id_site = s.id_site
                                LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan
                                LEFT JOIN karyawan ka ON p.id_karyawan_approved = ka.id_karyawan
                                LEFT JOIN request_order ro ON ro.id_po = p.id_po
                                WHERE p.id_po = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $po = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$po) {
            jsonResponse(false, 'Purchase Order tidak ditemukan.', null, 404);
        }

        // Ambil detail item
        $stmtItems = $conn->prepare("SELECT pd.*, b.kode_barang, b.nama_barang, b.satuan, b.id_kategori, b.id_merk 
                                     FROM purchase_order_detail pd
                                     LEFT JOIN barang b ON pd.id_barang = b.id_barang
                                     WHERE pd.id_po = ?");
        $stmtItems->bind_param("i", $id);
        $stmtItems->execute();
        $itemsRes = $stmtItems->get_result();
        
        $items = [];
        $grandTotal = 0;
        while ($item = $itemsRes->fetch_assoc()) {
            $grandTotal += (float)$item['subtotal'];
            $items[] = $item;
        }
        $stmtItems->close();

        $po['items'] = $items;
        $po['total_item'] = count($items);
        $po['grand_total'] = $grandTotal;

        jsonResponse(true, 'Detail Purchase Order berhasil diambil.', $po);
    }

    // 2. List PO
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $siteId = isset($_GET['site_id']) && is_numeric($_GET['site_id']) ? (int)$_GET['site_id'] : null;
    $vendorId = isset($_GET['vendor_id']) && is_numeric($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
    $status = trim($_GET['status'] ?? '');
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');

    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (p.nomor_po LIKE ? OR v.nama_vendor LIKE ? OR k.nama_karyawan LIKE ?)";
        $wildcard = "%" . $search . "%";
        $params[] = $wildcard;
        $params[] = $wildcard;
        $params[] = $wildcard;
        $types .= "sss";
    }

    if ($siteId) {
        $whereSql .= " AND p.id_site = ?";
        $params[] = $siteId;
        $types .= "i";
    }

    if ($vendorId) {
        $whereSql .= " AND p.id_vendor = ?";
        $params[] = $vendorId;
        $types .= "i";
    }

    if (!empty($status)) {
        $whereSql .= " AND p.status = ?";
        $params[] = $status;
        $types .= "s";
    }

    if (!empty($startDate) && !empty($endDate)) {
        $whereSql .= " AND DATE(p.tanggal_po) BETWEEN ? AND ?";
        $params[] = $startDate;
        $params[] = $endDate;
        $types .= "ss";
    }

    // Count Total
    $countSql = "SELECT COUNT(*) as total FROM purchase_order p LEFT JOIN vendor v ON p.id_vendor = v.id_vendor LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    // Fetch Data
    $sql = "SELECT p.*, v.nama_vendor, s.nama_site, k.nama_karyawan as nama_pembuat,
                   ro.nomor as nomor_ro,
                   (SELECT COUNT(*) FROM purchase_order_detail pd WHERE pd.id_po = p.id_po) as total_item,
                   (SELECT COALESCE(SUM(pd.subtotal), 0) FROM purchase_order_detail pd WHERE pd.id_po = p.id_po) as total_nilai
            FROM purchase_order p
            LEFT JOIN vendor v ON p.id_vendor = v.id_vendor
            LEFT JOIN site s ON p.id_site = s.id_site
            LEFT JOIN karyawan k ON p.id_karyawan = k.id_karyawan
            LEFT JOIN request_order ro ON ro.id_po = p.id_po
            $whereSql
            ORDER BY p.tanggal_po DESC, p.id_po DESC
            LIMIT ? OFFSET ?";

    $paramsWithLimit = $params;
    $typesWithLimit = $types . "ii";
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $row['total_nilai_formatted'] = 'Rp ' . number_format((float)$row['total_nilai'], 0, ',', '.');
        $items[] = $row;
    }
    $stmt->close();

    jsonResponse(true, 'Daftar Purchase Order berhasil dimuat.', [
        'items' => $items,
        'pagination' => [
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);
}
