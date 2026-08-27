<?php
/**
 * RESTful API Modul Retur Purchase Order (Retur PO)
 * PT Jaya Teknis
 * Khusus role Logistik, Administrator, dan Manager
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth([ROLE_LOGISTIK, ROLE_ADMIN, ROLE_MANAGER]);
$method = $_SERVER['REQUEST_METHOD'];

// Helper function JSON Response
function sendJson($success, $message, $data = null, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// -------------------------------------------------------------
// 1. GET: Ambil Data List atau Single Detail
// -------------------------------------------------------------
if ($method === 'GET') {
    $idRetur = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['id_po_retur']) ? intval($_GET['id_po_retur']) : 0);

    try {
        if ($idRetur > 0) {
            // DETAIL RETUR TUNGGAL
            $sqlHeader = "SELECT r.*,
                                 po.nomor_po, po.tanggal_po,
                                 rcv.nomor_rcv, rcv.nomor_sj, rcv.tanggal_diterima,
                                 v.nama_perusahaan AS nama_vendor, v.telepon AS telepon_vendor, v.email AS email_vendor,
                                 s.nama_site,
                                 k_buat.nama_karyawan AS nama_pembuat,
                                 k_app.nama_karyawan AS nama_penyetuju
                          FROM retur_po r
                          LEFT JOIN purchase_order po ON r.id_po = po.id_po
                          LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                          LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                          LEFT JOIN site s ON r.id_site = s.id_site
                          LEFT JOIN karyawan k_buat ON r.id_karyawan = k_buat.id_karyawan
                          LEFT JOIN karyawan k_app ON r.id_karyawan_approved = k_app.id_karyawan
                          WHERE r.id_po_retur = ?";

            $stmtH = $conn->prepare($sqlHeader);
            $stmtH->bind_param("i", $idRetur);
            $stmtH->execute();
            $header = $stmtH->get_result()->fetch_assoc();
            $stmtH->close();

            if (!$header) {
                sendJson(false, 'Data Retur PO tidak ditemukan.', null, 404);
            }

            // AMBIL ITEMS DETAIL
            $sqlItems = "SELECT d.*, b.kode_barang, b.nama_barang, b.satuan AS master_satuan
                         FROM retur_po_detail d
                         JOIN barang b ON d.id_barang = b.id_barang
                         WHERE d.id_po_retur = ?
                         ORDER BY d.id_po_retur_detail ASC";

            $stmtI = $conn->prepare($sqlItems);
            $stmtI->bind_param("i", $idRetur);
            $stmtI->execute();
            $resItems = $stmtI->get_result();

            $items = [];
            while ($row = $resItems->fetch_assoc()) {
                $row['foto_url'] = !empty($row['foto_bukti']) ? BASE_URL . '/uploads/retur/' . $row['foto_bukti'] : null;
                $items[] = $row;
            }
            $stmtI->close();

            $header['items'] = $items;
            sendJson(true, 'Detail Retur PO berhasil dimuat.', $header);

        } else {
            // LIST RETUR PO (FILTER & PAGINATION)
            $page = max(1, isset($_GET['page']) ? intval($_GET['page']) : 1);
            $limit = max(1, min(100, isset($_GET['limit']) ? intval($_GET['limit']) : 20));
            $offset = ($page - 1) * $limit;

            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $siteId = isset($_GET['site_id']) ? intval($_GET['site_id']) : 0;
            $status = isset($_GET['status']) ? trim($_GET['status']) : '';
            $kompensasi = isset($_GET['kompensasi']) && $_GET['kompensasi'] !== '' ? intval($_GET['kompensasi']) : '';
            $startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
            $endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

            $where = "WHERE 1=1";
            $params = [];
            $types = "";

            if (!empty($q)) {
                $where .= " AND (r.nomor_po_retur LIKE ? OR po.nomor_po LIKE ? OR rcv.nomor_rcv LIKE ? OR v.nama_perusahaan LIKE ? OR r.nomor_sj_retur LIKE ?)";
                $search = "%{$q}%";
                $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search; $params[] = $search;
                $types .= "sssss";
            }

            if ($siteId > 0) {
                $where .= " AND r.id_site = ?";
                $params[] = $siteId;
                $types .= "i";
            }

            if (!empty($status)) {
                $where .= " AND r.status = ?";
                $params[] = $status;
                $types .= "s";
            }

            if ($kompensasi !== '') {
                $where .= " AND r.kompensasi = ?";
                $params[] = $kompensasi;
                $types .= "i";
            }

            if (!empty($startDate)) {
                $where .= " AND DATE(r.tanggal_po_retur) >= ?";
                $params[] = $startDate;
                $types .= "s";
            }

            if (!empty($endDate)) {
                $where .= " AND DATE(r.tanggal_po_retur) <= ?";
                $params[] = $endDate;
                $types .= "s";
            }

            // Hitung Total Data
            $sqlCount = "SELECT COUNT(*) AS total
                         FROM retur_po r
                         LEFT JOIN purchase_order po ON r.id_po = po.id_po
                         LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                         LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                         {$where}";

            $stmtCount = $conn->prepare($sqlCount);
            if (!empty($params)) {
                $stmtCount->bind_param($types, ...$params);
            }
            $stmtCount->execute();
            $totalRows = $stmtCount->get_result()->fetch_assoc()['total'];
            $stmtCount->close();

            // Hitung Metrik Ringkasan
            $sqlMetrics = "SELECT 
                            COUNT(*) AS total_retur,
                            COALESCE(SUM(CASE WHEN r.status = 'DRAFT' THEN 1 ELSE 0 END), 0) AS total_draft,
                            COALESCE(SUM(CASE WHEN r.status IN ('MENUNGGU KONFIRMASI VENDOR','DISETUJUI VENDOR','DIKIRIM KE VENDOR') THEN 1 ELSE 0 END), 0) AS total_proses,
                            COALESCE(SUM(CASE WHEN r.status = 'DITERIMA' THEN 1 ELSE 0 END), 0) AS total_selesai,
                            COALESCE(SUM(r.total), 0) AS total_nilai_retur
                           FROM retur_po r
                           LEFT JOIN purchase_order po ON r.id_po = po.id_po
                           LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                           LEFT JOIN vendor v ON r.id_vendor = v.id_vendor";
            $resMetrics = $conn->query($sqlMetrics)->fetch_assoc();

            // Ambil List Data
            $sqlList = "SELECT r.id_po_retur, r.nomor_po_retur, r.tanggal_po_retur, r.kompensasi, r.status,
                               r.total, r.nominal_pajak, r.rate_pajak, r.nomor_sj_retur, r.pic_vendor,
                               po.id_po, po.nomor_po,
                               rcv.id_rcv, rcv.nomor_rcv, rcv.nomor_sj,
                               v.id_vendor, v.nama_perusahaan AS nama_vendor,
                               s.id_site, s.nama_site,
                               k.nama_karyawan AS nama_pembuat,
                               COUNT(d.id_po_retur_detail) AS total_items,
                               SUM(d.qty_retur) AS total_qty_retur
                        FROM retur_po r
                        LEFT JOIN purchase_order po ON r.id_po = po.id_po
                        LEFT JOIN receiving_order rcv ON r.id_rcv = rcv.id_rcv
                        LEFT JOIN vendor v ON r.id_vendor = v.id_vendor
                        LEFT JOIN site s ON r.id_site = s.id_site
                        LEFT JOIN karyawan k ON r.id_karyawan = k.id_karyawan
                        LEFT JOIN retur_po_detail d ON r.id_po_retur = d.id_po_retur
                        {$where}
                        GROUP BY r.id_po_retur
                        ORDER BY r.id_po_retur DESC
                        LIMIT ? OFFSET ?";

            $paramsList = $params;
            $paramsList[] = $limit;
            $paramsList[] = $offset;
            $typesList = $types . "ii";

            $stmtList = $conn->prepare($sqlList);
            $stmtList->bind_param($typesList, ...$paramsList);
            $stmtList->execute();
            $resList = $stmtList->get_result();

            $items = [];
            while ($row = $resList->fetch_assoc()) {
                $items[] = $row;
            }
            $stmtList->close();

            sendJson(true, 'Data Retur PO berhasil diambil.', [
                'metrics' => $resMetrics,
                'items' => $items,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total_items' => (int)$totalRows,
                    'total_pages' => ceil($totalRows / $limit)
                ]
            ]);
        }

    } catch (Exception $e) {
        sendJson(false, 'Gagal mengambil data: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 2. POST: Tambah Dokumen Retur PO Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

    // Header Inputs
    $idPo = isset($input['id_po']) ? intval($input['id_po']) : 0;
    $idRcv = isset($input['id_rcv']) ? intval($input['id_rcv']) : 0;
    $idVendor = isset($input['id_vendor']) ? intval($input['id_vendor']) : 0;
    $idSite = isset($input['id_site']) ? intval($input['id_site']) : 0;
    $picVendor = trim($input['pic_vendor'] ?? '');
    $kompensasi = isset($input['kompensasi']) ? intval($input['kompensasi']) : 1; // 1: Tukar Unit, 0: Potong Tagihan
    $ratePajak = isset($input['rate_pajak']) ? intval($input['rate_pajak']) : 0;
    $nomorSjRetur = trim($input['nomor_sj_retur'] ?? '');
    $nomorNotaReturPajak = trim($input['nomor_nota_retur_pajak'] ?? '');
    $keterangan = trim($input['keterangan'] ?? '');
    $status = in_array($input['status'] ?? '', ['DRAFT', 'MENUNGGU KONFIRMASI VENDOR']) ? $input['status'] : 'MENUNGGU KONFIRMASI VENDOR';
    $tanggalRetur = !empty($input['tanggal_po_retur']) ? trim($input['tanggal_po_retur']) : date('Y-m-d H:i:s');
    if (strlen($tanggalRetur) === 10) { $tanggalRetur .= ' ' . date('H:i:s'); }

    $idKaryawan = !empty($currentUser['id_karyawan']) ? $currentUser['id_karyawan'] : 1;
    $items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

    // Validasi
    if ($idPo <= 0 || $idRcv <= 0 || $idVendor <= 0) {
        sendJson(false, 'Dokumen PO, Penerimaan (RCV), dan Vendor wajib dipilih.', null, 422);
    }
    if (empty($items)) {
        sendJson(false, 'Harap sertakan minimal 1 baris item barang yang diretur.', null, 422);
    }

    $conn->begin_transaction();
    try {
        // 1. Generate Nomor Retur Unik: RET-YYMM-XXXX
        $time = strtotime($tanggalRetur);
        $yymm = date('ym', $time);
        $prefix = "RET-{$yymm}-";

        $stmtSeq = $conn->prepare("SELECT nomor_po_retur FROM retur_po WHERE nomor_po_retur LIKE ? ORDER BY id_po_retur DESC LIMIT 100");
        $searchPattern = $prefix . "%";
        $stmtSeq->bind_param("s", $searchPattern);
        $stmtSeq->execute();
        $resSeq = $stmtSeq->get_result();

        $maxSequence = 0;
        while ($row = $resSeq->fetch_assoc()) {
            $numStr = $row['nomor_po_retur'] ?? '';
            if (preg_match('/^RET-\d{4}-(\d+)$/i', $numStr, $matches)) {
                $seq = (int)$matches[1];
                if ($seq > $maxSequence) { $maxSequence = $seq; }
            }
        }
        $stmtSeq->close();

        $nextSequence = $maxSequence + 1;
        $nomorRetur = $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        // 2. Hitung Total & Validasi Items
        $totalSubtotal = 0;
        $cleanItems = [];

        foreach ($items as $idx => $it) {
            $idBarang = intval($it['id_barang'] ?? 0);
            $qtyRetur = floatval($it['qty_retur'] ?? 0);
            $satuan = !empty($it['satuan']) ? trim($it['satuan']) : 'PCS';
            $hargaSatuan = floatval($it['harga_satuan'] ?? 0);
            $alasanRetur = in_array($it['alasan_retur'] ?? '', ['RUSAK_FISIK', 'CACAT_PRODUKSI', 'SALAH_SPESIFIKASI', 'KURANG_PENGIRIMAN', 'KADALUARSA_EXP']) ? $it['alasan_retur'] : 'RUSAK_FISIK';
            $ketKerusakan = trim($it['keterangan_kerusakan'] ?? '');
            $fotoBukti = trim($it['foto_bukti'] ?? '');

            if ($idBarang <= 0 || $qtyRetur <= 0) {
                continue;
            }

            // Handle Base64 Photo Upload if present
            if (!empty($it['foto_base64'])) {
                $imgData = $it['foto_base64'];
                if (preg_match('/^data:image\/(\w+);base64,/', $imgData, $type)) {
                    $imgData = substr($imgData, strpos($imgData, ',') + 1);
                    $type = strtolower($type[1]);
                    if (in_array($type, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $imgDecoded = base64_decode($imgData);
                        if ($imgDecoded !== false) {
                            $fotoFileName = 'retur_' . $yymm . '_' . uniqid() . '.' . $type;
                            $targetPath = __DIR__ . '/../../uploads/retur/' . $fotoFileName;
                            file_put_contents($targetPath, $imgDecoded);
                            $fotoBukti = $fotoFileName;
                        }
                    }
                }
            }

            $subtotal = $qtyRetur * $hargaSatuan;
            $totalSubtotal += $subtotal;

            $cleanItems[] = [
                'id_barang' => $idBarang,
                'qty_retur' => $qtyRetur,
                'satuan' => $satuan,
                'harga_satuan' => $hargaSatuan,
                'subtotal' => $subtotal,
                'alasan_retur' => $alasanRetur,
                'keterangan_kerusakan' => $ketKerusakan,
                'foto_bukti' => $fotoBukti
            ];
        }

        if (empty($cleanItems)) {
            throw new Exception('Barang yang diretur tidak valid atau kuantitasnya 0.');
        }

        // 3. Hitung Nominal Pajak
        $nominalPajak = $totalSubtotal * ($ratePajak / 100);

        // 4. Insert Header `retur_po`
        $sqlInsH = "INSERT INTO retur_po (
                        nomor_po_retur, id_karyawan, tanggal_po_retur, kompensasi,
                        id_vendor, pic_vendor, id_site, id_po, id_rcv,
                        total, status, nominal_pajak, rate_pajak,
                        nomor_sj_retur, nomor_nota_retur_pajak, keterangan
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtInsH = $conn->prepare($sqlInsH);
        $stmtInsH->bind_param(
            "sisissiiidsdisss",
            $nomorRetur, $idKaryawan, $tanggalRetur, $kompensasi,
            $idVendor, $picVendor, $idSite, $idPo, $idRcv,
            $totalSubtotal, $status, $nominalPajak, $ratePajak,
            $nomorSjRetur, $nomorNotaReturPajak, $keterangan
        );
        $stmtInsH->execute();
        $idPoRetur = $conn->insert_id;
        $stmtInsH->close();

        // 5. Insert Detail `retur_po_detail`
        $sqlInsD = "INSERT INTO retur_po_detail (
                        id_po_retur, id_barang, qty_retur, satuan,
                        harga_satuan, subtotal, alasan_retur,
                        keterangan_kerusakan, foto_bukti, qty_diganti
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";

        $stmtInsD = $conn->prepare($sqlInsD);
        foreach ($cleanItems as $ci) {
            $stmtInsD->bind_param(
                "iidssdsss",
                $idPoRetur, $ci['id_barang'], $ci['qty_retur'], $ci['satuan'],
                $ci['harga_satuan'], $ci['subtotal'], $ci['alasan_retur'],
                $ci['keterangan_kerusakan'], $ci['foto_bukti']
            );
            $stmtInsD->execute();
        }
        $stmtInsD->close();

        $conn->commit();
        sendJson(true, "Dokumen Retur PO {$nomorRetur} berhasil diterbitkan.", [
            'id_po_retur' => $idPoRetur,
            'nomor_po_retur' => $nomorRetur
        ], 201);

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal membuat dokumen Retur: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 3. PUT: Update Status Persetujuan & Unit Pengganti
// -------------------------------------------------------------
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $idRetur = isset($input['id_po_retur']) ? intval($input['id_po_retur']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

    if ($idRetur <= 0) {
        sendJson(false, 'ID Retur PO tidak valid.', null, 422);
    }

    $conn->begin_transaction();
    try {
        // Ambil data lama
        $stmtChk = $conn->prepare("SELECT * FROM retur_po WHERE id_po_retur = ?");
        $stmtChk->bind_param("i", $idRetur);
        $stmtChk->execute();
        $retur = $stmtChk->get_result()->fetch_assoc();
        $stmtChk->close();

        if (!$retur) {
            sendJson(false, 'Dokumen Retur tidak ditemukan.', null, 404);
        }

        $newStatus = trim($input['status'] ?? $retur['status']);
        $validStatuses = ['DRAFT', 'MENUNGGU KONFIRMASI VENDOR', 'DISETUJUI VENDOR', 'TIDAK DISETUJUI VENDOR', 'DIKIRIM KE VENDOR', 'DITERIMA'];
        if (!in_array($newStatus, $validStatuses)) {
            sendJson(false, 'Status tidak valid.', null, 422);
        }

        $picVendor = isset($input['pic_vendor']) ? trim($input['pic_vendor']) : $retur['pic_vendor'];
        $nomorSjRetur = isset($input['nomor_sj_retur']) ? trim($input['nomor_sj_retur']) : $retur['nomor_sj_retur'];
        $nomorNotaReturPajak = isset($input['nomor_nota_retur_pajak']) ? trim($input['nomor_nota_retur_pajak']) : $retur['nomor_nota_retur_pajak'];
        $keterangan = isset($input['keterangan']) ? trim($input['keterangan']) : $retur['keterangan'];

        $idKaryawanApproved = $retur['id_karyawan_approved'];
        if (in_array($newStatus, ['DISETUJUI VENDOR', 'DITERIMA']) && empty($idKaryawanApproved)) {
            $idKaryawanApproved = $currentUser['id_karyawan'] ?? 1;
        }

        // Update Header
        $sqlUpdH = "UPDATE retur_po SET 
                        status = ?, 
                        pic_vendor = ?, 
                        nomor_sj_retur = ?, 
                        nomor_nota_retur_pajak = ?, 
                        keterangan = ?, 
                        id_karyawan_approved = ?
                    WHERE id_po_retur = ?";

        $stmtUpdH = $conn->prepare($sqlUpdH);
        $stmtUpdH->bind_param("sssssii", $newStatus, $picVendor, $nomorSjRetur, $nomorNotaReturPajak, $keterangan, $idKaryawanApproved, $idRetur);
        $stmtUpdH->execute();
        $stmtUpdH->close();

        // Update Item Replacement / Qty Diganti jika ada
        if (isset($input['items']) && is_array($input['items'])) {
            $stmtUpdItem = $conn->prepare("UPDATE retur_po_detail SET qty_diganti = ? WHERE id_po_retur_detail = ? AND id_po_retur = ?");
            foreach ($input['items'] as $it) {
                $idDet = intval($it['id_po_retur_detail'] ?? 0);
                $qtyGanti = floatval($it['qty_diganti'] ?? 0);
                if ($idDet > 0) {
                    $stmtUpdItem->bind_param("dii", $qtyGanti, $idDet, $idRetur);
                    $stmtUpdItem->execute();
                }
            }
            $stmtUpdItem->close();
        }

        $conn->commit();
        sendJson(true, "Status Dokumen Retur {$retur['nomor_po_retur']} berhasil diperbarui menjadi {$newStatus}.", [
            'id_po_retur' => $idRetur,
            'status' => $newStatus
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal memperbarui status Retur: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 4. DELETE: Hapus Draft Dokumen Retur
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idRetur = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['id_po_retur']) ? intval($_GET['id_po_retur']) : 0);

    if ($idRetur <= 0) {
        sendJson(false, 'ID Retur tidak valid.', null, 422);
    }

    try {
        $stmtChk = $conn->prepare("SELECT status, nomor_po_retur FROM retur_po WHERE id_po_retur = ?");
        $stmtChk->bind_param("i", $idRetur);
        $stmtChk->execute();
        $retur = $stmtChk->get_result()->fetch_assoc();
        $stmtChk->close();

        if (!$retur) {
            sendJson(false, 'Dokumen Retur tidak ditemukan.', null, 404);
        }

        if ($retur['status'] !== 'DRAFT') {
            sendJson(false, "Dokumen berstatus '{$retur['status']}' tidak dapat dihapus.", null, 403);
        }

        $conn->begin_transaction();

        // Hapus detail
        $stmtDelD = $conn->prepare("DELETE FROM retur_po_detail WHERE id_po_retur = ?");
        $stmtDelD->bind_param("i", $idRetur);
        $stmtDelD->execute();
        $stmtDelD->close();

        // Hapus header
        $stmtDelH = $conn->prepare("DELETE FROM retur_po WHERE id_po_retur = ?");
        $stmtDelH->bind_param("i", $idRetur);
        $stmtDelH->execute();
        $stmtDelH->close();

        $conn->commit();
        sendJson(true, "Draft Retur PO {$retur['nomor_po_retur']} berhasil dihapus.");

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal menghapus Retur: ' . $e->getMessage(), null, 500);
    }
}
