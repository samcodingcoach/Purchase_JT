<?php
/**
 * API Modul Request Order (RO)
 * Endpoint:
 * - GET    /api/request_order/index.php            : Mengambil daftar RO (List, Search, Filter, Pagination, Summary Metrics)
 * - GET    /api/request_order/index.php?id={id}    : Mengambil detail lengkap 1 RO beserta rincian item
 * - DELETE /api/request_order/index.php?id={id}    : Menghapus RO (hanya jika status DRAFT)
 * - POST   /api/request_order/index.php?action=cancel : Membatalkan RO (status menjadi BATAL)
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../config/session.php';

// Verifikasi Sesi & Hak Akses
$currentUser = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------
// 1. GET: List RO atau Detail RO
// -------------------------------------------------------------
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['id_request']) ? (int)$_GET['id_request'] : null);

    // A. DETAIL RO TUNGGAL
    if ($id && $id > 0) {
        $stmt = $conn->prepare("SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.id_karyawan, ro.id_site, 
                                       ro.status, ro.prioritas, ro.id_vendor, ro.tanggal_status, ro.keterangan, ro.id_po,
                                       kry.nama_karyawan, kry.kode_karyawan, j.nama_jabatan, d.nama_divisi,
                                       s.nama_site, s.kode_site,
                                       v.nama_perusahaan AS nama_vendor, v.kode_vendor
                                FROM request_order ro
                                LEFT JOIN karyawan kry ON ro.id_karyawan = kry.id_karyawan
                                LEFT JOIN jabatan j ON kry.id_jabatan = j.id_jabatan
                                LEFT JOIN divisi d ON kry.id_divisi = d.id_divisi
                                LEFT JOIN site s ON ro.id_site = s.id_site
                                LEFT JOIN vendor v ON ro.id_vendor = v.id_vendor
                                WHERE ro.id_request = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if (!$res || $res->num_rows === 0) {
            jsonResponse(false, 'Data Request Order tidak ditemukan.', null, 404);
        }

        $header = $res->fetch_assoc();
        $stmt->close();

        // Ambil rincian material barang
        $stmtItems = $conn->prepare("SELECT rod.id_request_detail, rod.id_request, rod.id_barang, 
                                            rod.kode_barang, rod.nama_barang, rod.qty, rod.satuan, rod.harga, rod.subtotal,
                                            b.foto1, b.nama_barang AS master_nama_barang, m.nama_merk, k.nama_kategori
                                     FROM request_order_detail rod
                                     LEFT JOIN barang b ON rod.id_barang = b.id_barang
                                     LEFT JOIN merk_barang m ON b.id_merk = m.id_merk
                                     LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori
                                     WHERE rod.id_request = ?
                                     ORDER BY rod.id_request_detail ASC");
        $stmtItems->bind_param("i", $id);
        $stmtItems->execute();
        $resItems = $stmtItems->get_result();

        $items = [];
        $totalQty = 0;
        $grandTotal = 0;

        while ($item = $resItems->fetch_assoc()) {
            $qty = (float)$item['qty'];
            $harga = (float)$item['harga'];
            $subtotal = (float)$item['subtotal'];
            $totalQty += $qty;
            $grandTotal += $subtotal;

            $items[] = [
                'id_request_detail' => (int)$item['id_request_detail'],
                'id_barang' => $item['id_barang'] ? (int)$item['id_barang'] : null,
                'kode_barang' => $item['kode_barang'] ?? '',
                'nama_barang' => $item['nama_barang'],
                'foto1' => $item['foto1'] ?? null,
                'nama_merk' => $item['nama_merk'] ?? 'Umum',
                'nama_kategori' => $item['nama_kategori'] ?? 'Material',
                'qty' => $qty,
                'satuan' => $item['satuan'] ?? 'PCS',
                'harga' => $harga,
                'subtotal' => $subtotal
            ];
        }
        $stmtItems->close();

        $header['items'] = $items;
        $header['total_items'] = count($items);
        $header['total_qty'] = $totalQty;
        $header['grand_total'] = $grandTotal;

        jsonResponse(true, 'Detail Request Order berhasil dimuat.', $header);
    }

    // B. LIST REQUEST ORDER
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $status = trim($_GET['status'] ?? '');
    $siteId = isset($_GET['site_id']) && is_numeric($_GET['site_id']) ? (int)$_GET['site_id'] : null;
    $prioritas = trim($_GET['prioritas'] ?? '');
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 20;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    // Role filtering: Jika user adalah Mekanik biasa, hanya tampilkan RO yang dibuat oleh site miliknya atau dirinya (kecuali admin/manager/logistik)
    if ($currentUser['role'] === ROLE_MEKANIK && !empty($currentUser['id_karyawan'])) {
        // Mekanik melihat semua pengajuan di site-nya atau yang dibuat olehnya
        if (!empty($currentUser['id_site'])) {
            $whereSql .= " AND (ro.id_karyawan = ? OR ro.id_site = ?)";
            $params[] = (int)$currentUser['id_karyawan'];
            $params[] = (int)$currentUser['id_site'];
            $types .= "ii";
        } else {
            $whereSql .= " AND ro.id_karyawan = ?";
            $params[] = (int)$currentUser['id_karyawan'];
            $types .= "i";
        }
    }

    if (!empty($search)) {
        $searchWild = "%" . $search . "%";
        
        // Jika pencarian berupa angka (misal: "0001", "1", dsb), cocokkan 4 digit terakhir nomor RO (RO-YYMM-XXXX) & padding
        if (is_numeric($search)) {
            $padded = str_pad($search, 4, '0', STR_PAD_LEFT);
            $paddedWild = "%" . $padded;
            $whereSql .= " AND (ro.nomor LIKE ? OR ro.nomor LIKE ? OR RIGHT(ro.nomor, 4) = ? OR kry.nama_karyawan LIKE ? OR kry.kode_karyawan LIKE ? OR ro.keterangan LIKE ?)";
            $params[] = $searchWild;
            $params[] = $paddedWild;
            $params[] = $padded;
            $params[] = $searchWild;
            $params[] = $searchWild;
            $params[] = $searchWild;
            $types .= "ssssss";
        } else {
            $whereSql .= " AND (ro.nomor LIKE ? OR kry.nama_karyawan LIKE ? OR kry.kode_karyawan LIKE ? OR ro.keterangan LIKE ?)";
            for ($i = 0; $i < 4; $i++) {
                $params[] = $searchWild;
                $types .= "s";
            }
        }
    }

    if (!empty($status) && in_array(strtoupper($status), ['DRAFT', 'TERKIRIM', 'DISETUJUI', 'TIDAK DISETUJUI', 'BATAL'])) {
        $whereSql .= " AND ro.status = ?";
        $params[] = strtoupper($status);
        $types .= "s";
    }

    if ($siteId !== null && $siteId > 0) {
        $whereSql .= " AND ro.id_site = ?";
        $params[] = $siteId;
        $types .= "i";
    }

    if (!empty($prioritas) && in_array(strtoupper($prioritas), ['NORMAL', 'URGENT'])) {
        $whereSql .= " AND ro.prioritas = ?";
        $params[] = strtoupper($prioritas);
        $types .= "s";
    }

    if (!empty($startDate)) {
        $whereSql .= " AND DATE(ro.tanggal_ro) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $whereSql .= " AND DATE(ro.tanggal_ro) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }

    // 1. Hitung Total Records
    $countSql = "SELECT COUNT(*) as total FROM request_order ro 
                 LEFT JOIN karyawan kry ON ro.id_karyawan = kry.id_karyawan" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    // 2. Query Summary Metrics (Statistik Status)
    $metricsSql = "SELECT 
        COUNT(*) as total_ro,
        SUM(CASE WHEN status = 'DRAFT' THEN 1 ELSE 0 END) as total_draft,
        SUM(CASE WHEN status = 'TERKIRIM' THEN 1 ELSE 0 END) as total_terkirim,
        SUM(CASE WHEN status = 'DISETUJUI' THEN 1 ELSE 0 END) as total_disetujui,
        SUM(CASE WHEN status = 'TIDAK DISETUJUI' THEN 1 ELSE 0 END) as total_ditolak,
        SUM(CASE WHEN status = 'BATAL' THEN 1 ELSE 0 END) as total_batal,
        SUM(CASE WHEN prioritas = 'URGENT' AND status IN ('DRAFT', 'TERKIRIM') THEN 1 ELSE 0 END) as total_urgent
    FROM request_order ro";
    
    // Sesuaikan filter role untuk metrik jika mekanik
    if ($currentUser['role'] === ROLE_MEKANIK && !empty($currentUser['id_karyawan'])) {
        if (!empty($currentUser['id_site'])) {
            $metricsSql .= " WHERE (ro.id_karyawan = " . (int)$currentUser['id_karyawan'] . " OR ro.id_site = " . (int)$currentUser['id_site'] . ")";
        } else {
            $metricsSql .= " WHERE ro.id_karyawan = " . (int)$currentUser['id_karyawan'];
        }
    }
    
    $resMetrics = $conn->query($metricsSql);
    $metrics = $resMetrics ? $resMetrics->fetch_assoc() : [
        'total_ro' => 0, 'total_draft' => 0, 'total_terkirim' => 0, 
        'total_disetujui' => 0, 'total_ditolak' => 0, 'total_batal' => 0, 'total_urgent' => 0
    ];

    // 3. Query List Data RO dengan agregasi item
    $sql = "SELECT ro.id_request, ro.nomor, ro.tanggal_ro, ro.id_karyawan, ro.id_site, 
                   ro.status, ro.prioritas, ro.id_vendor, ro.tanggal_status, ro.keterangan, ro.id_po,
                   kry.nama_karyawan, kry.kode_karyawan, j.nama_jabatan, d.nama_divisi,
                   s.nama_site, s.kode_site,
                   v.nama_perusahaan AS nama_vendor,
                   COUNT(rod.id_request_detail) AS total_items,
                   COALESCE(SUM(rod.qty), 0) AS total_qty,
                   COALESCE(SUM(rod.subtotal), 0) AS grand_total
            FROM request_order ro
            LEFT JOIN karyawan kry ON ro.id_karyawan = kry.id_karyawan
            LEFT JOIN jabatan j ON kry.id_jabatan = j.id_jabatan
            LEFT JOIN divisi d ON kry.id_divisi = d.id_divisi
            LEFT JOIN site s ON ro.id_site = s.id_site
            LEFT JOIN vendor v ON ro.id_vendor = v.id_vendor
            LEFT JOIN request_order_detail rod ON ro.id_request = rod.id_request"
            . $whereSql . " 
            GROUP BY ro.id_request 
            ORDER BY ro.id_request DESC 
            LIMIT ? OFFSET ?";

    $paramsWithLimit = $params;
    $typesWithLimit = $types . "ii";
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt = $conn->prepare($sql);
    if (!empty($paramsWithLimit)) {
        $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    }
    $stmt->execute();
    $res = $stmt->get_result();

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $items[] = [
            'id_request' => (int)$row['id_request'],
            'nomor' => $row['nomor'],
            'tanggal_ro' => $row['tanggal_ro'],
            'status' => $row['status'],
            'prioritas' => $row['prioritas'] ?? 'NORMAL',
            'id_karyawan' => (int)$row['id_karyawan'],
            'nama_karyawan' => $row['nama_karyawan'] ?? 'Karyawan',
            'kode_karyawan' => $row['kode_karyawan'] ?? '',
            'nama_jabatan' => $row['nama_jabatan'] ?? '',
            'nama_divisi' => $row['nama_divisi'] ?? '',
            'id_site' => (int)$row['id_site'],
            'nama_site' => $row['nama_site'] ?? '-',
            'kode_site' => $row['kode_site'] ?? '',
            'nama_vendor' => $row['nama_vendor'] ?? null,
            'keterangan' => $row['keterangan'] ?? '',
            'total_items' => (int)$row['total_items'],
            'total_qty' => (float)$row['total_qty'],
            'grand_total' => (float)$row['grand_total'],
            'id_po' => $row['id_po'] ? (int)$row['id_po'] : null
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Daftar Request Order berhasil dimuat.', [
        'items' => $items,
        'metrics' => $metrics,
        'pagination' => [
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit
        ]
    ]);
}

// -------------------------------------------------------------
// 2. DELETE: Hapus Request Order (Hanya jika status DRAFT)
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        jsonResponse(false, 'ID Request Order tidak valid.', null, 400);
    }

    $chk = $conn->prepare("SELECT id_request, nomor, status FROM request_order WHERE id_request = ? LIMIT 1");
    $chk->bind_param("i", $id);
    $chk->execute();
    $resChk = $chk->get_result();

    if (!$resChk || $resChk->num_rows === 0) {
        jsonResponse(false, 'Data Request Order tidak ditemukan.', null, 404);
    }
    $roData = $resChk->fetch_assoc();
    $chk->close();

    if ($roData['status'] !== 'DRAFT') {
        jsonResponse(false, 'Hanya Request Order dengan status DRAFT yang dapat dihapus.', null, 422);
    }

    $conn->begin_transaction();
    try {
        $delDetail = $conn->prepare("DELETE FROM request_order_detail WHERE id_request = ?");
        $delDetail->bind_param("i", $id);
        $delDetail->execute();
        $delDetail->close();

        $delHeader = $conn->prepare("DELETE FROM request_order WHERE id_request = ?");
        $delHeader->bind_param("i", $id);
        $delHeader->execute();
        $delHeader->close();

        $conn->commit();
        jsonResponse(true, "Draft Request Order {$roData['nomor']} berhasil dihapus.");
    } catch (Exception $e) {
        $conn->rollback();
        jsonResponse(false, 'Gagal menghapus Draft RO: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 3. POST / PATCH: Pembatalan RO (Status BATAL)
// -------------------------------------------------------------
if ($method === 'POST') {
    $action = $_GET['action'] ?? '';
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $id = isset($input['id_request']) ? (int)$input['id_request'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

    if ($action === 'cancel' || ($input['status'] ?? '') === 'BATAL') {
        if ($id <= 0) {
            jsonResponse(false, 'ID Request Order tidak valid.', null, 400);
        }

        $stmt = $conn->prepare("SELECT id_request, nomor, status FROM request_order WHERE id_request = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $ro = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$ro) {
            jsonResponse(false, 'Data Request Order tidak ditemukan.', null, 404);
        }

        if (in_array($ro['status'], ['DISETUJUI', 'BATAL'])) {
            jsonResponse(false, "Request Order dengan status {$ro['status']} tidak dapat dibatalkan.", null, 422);
        }

        $now = date('Y-m-d H:i:s');
        $up = $conn->prepare("UPDATE request_order SET status = 'BATAL', tanggal_status = ? WHERE id_request = ?");
        $up->bind_param("si", $now, $id);
        
        if ($up->execute()) {
            $up->close();
            jsonResponse(true, "Request Order {$ro['nomor']} berhasil dibatalkan.");
        } else {
            $up->close();
            jsonResponse(false, 'Gagal membatalkan Request Order.', null, 500);
        }
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
