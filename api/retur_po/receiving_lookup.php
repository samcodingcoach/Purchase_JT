<?php
/**
 * API Lookup Dokumen Penerimaan Barang (Receiving Order) untuk Retur PO
 * Khusus untuk Logistik, Admin, dan Manager
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

$user = apiAuth([ROLE_LOGISTIK, ROLE_ADMIN, ROLE_MANAGER]);

$idRcv = isset($_GET['id_rcv']) ? intval($_GET['id_rcv']) : 0;
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

try {
    if ($idRcv > 0) {
        // Ambil rincian spesifik satu dokumen receiving
        $sqlRcv = "SELECT r.*, 
                          po.nomor_po, po.id_vendor, po.id_site, po.pajak AS rate_pajak,
                          v.nama_perusahaan AS nama_vendor, v.kontak_person AS pic_vendor, v.telepon AS telepon_vendor,
                          s.nama_site
                   FROM receiving_order r
                   JOIN purchase_order po ON r.id_po = po.id_po
                   JOIN vendor v ON po.id_vendor = v.id_vendor
                   JOIN site s ON po.id_site = s.id_site
                   WHERE r.id_rcv = ?";
        
        $stmt = $conn->prepare($sqlRcv);
        $stmt->bind_param("i", $idRcv);
        $stmt->execute();
        $rcvHeader = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$rcvHeader) {
            echo json_encode([
                'success' => false,
                'message' => 'Dokumen Penerimaan Barang (RCV) tidak ditemukan.'
            ]);
            exit;
        }

        // Ambil item receiving detail dan JOIN ke barang & purchase_order_detail untuk harga
        $sqlItems = "SELECT rod.id_rcv_detail, rod.id_rcv, rod.id_barang, rod.qty AS qty_rcv, rod.status_qc, rod.keterangan AS ket_rcv,
                            b.kode_barang, b.nama_barang, b.satuan AS master_satuan,
                            COALESCE(pod.harga, 0) AS harga_satuan,
                            COALESCE(pod.diskon, 0) AS diskon_item,
                            pod.subtotal AS subtotal_po
                     FROM receiving_order_detail rod
                     JOIN barang b ON rod.id_barang = b.id_barang
                     LEFT JOIN purchase_order_detail pod ON (pod.id_po = ? AND pod.id_barang = rod.id_barang)
                     WHERE rod.id_rcv = ?
                     ORDER BY rod.status_qc ASC, rod.id_rcv_detail ASC";

        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bind_param("ii", $rcvHeader['id_po'], $idRcv);
        $stmtItems->execute();
        $resItems = $stmtItems->get_result();

        $items = [];
        $damagedItems = [];

        while ($item = $resItems->fetch_assoc()) {
            $item['is_damaged'] = ($item['status_qc'] == 0);
            $item['suggested_reason'] = $item['is_damaged'] ? 'RUSAK_FISIK' : '';
            $items[] = $item;
            if ($item['is_damaged']) {
                $damagedItems[] = $item;
            }
        }
        $stmtItems->close();

        echo json_encode([
            'success' => true,
            'data' => [
                'header' => $rcvHeader,
                'items' => $items,
                'damaged_items' => $damagedItems
            ]
        ]);
        exit;

    } else {
        // Ambil daftar dokumen receiving untuk dropdown / modal selector
        $where = "WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($q)) {
            $where .= " AND (r.nomor_rcv LIKE ? OR po.nomor_po LIKE ? OR v.nama_perusahaan LIKE ? OR r.nomor_sj LIKE ?)";
            $search = "%{$q}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "ssss";
        }

        $sql = "SELECT r.id_rcv, r.nomor_rcv, r.tanggal_rcv, r.tanggal_diterima, r.nomor_sj,
                       po.id_po, po.nomor_po,
                       v.id_vendor, v.nama_perusahaan AS nama_vendor,
                       s.id_site, s.nama_site,
                       COUNT(rod.id_rcv_detail) AS total_items,
                       SUM(CASE WHEN rod.status_qc = 0 THEN 1 ELSE 0 END) AS damaged_count
                FROM receiving_order r
                JOIN purchase_order po ON r.id_po = po.id_po
                JOIN vendor v ON po.id_vendor = v.id_vendor
                JOIN site s ON po.id_site = s.id_site
                LEFT JOIN receiving_order_detail rod ON r.id_rcv = rod.id_rcv
                {$where}
                GROUP BY r.id_rcv
                ORDER BY r.id_rcv DESC
                LIMIT 50";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $list = [];
        while ($row = $result->fetch_assoc()) {
            $list[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'data' => $list
        ]);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
    ]);
}
