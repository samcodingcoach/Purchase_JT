<?php
/**
 * REST API: Manajemen Pembayaran Faktur PO (Payment Purchase)
 * Endpoint: /api/pembayaran_po/index.php
 * Path: api/pembayaran_po/index.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../middleware/auth.php';

$user = apiAuth([ROLE_FINANCE, ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

function sendJson($success, $message, $data = null, $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function generateKodePembayaran($conn) {
    $prefix = "PAY-" . date('ym') . "-";
    $query = "SELECT kode_pembayaran FROM payment_purchase_detail 
              WHERE kode_pembayaran LIKE '{$prefix}%' 
              ORDER BY kode_pembayaran DESC LIMIT 1";
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $lastSeq = (int)substr($row['kode_pembayaran'], -4);
        $nextSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $nextSeq = "0001";
    }
    return $prefix . $nextSeq;
}

// -------------------------------------------------------------
// 1. GET: Daftar Riwayat Pembayaran & Ringkasan Metrik Finansial
// -------------------------------------------------------------
if ($method === 'GET') {
    $idDetail = isset($_GET['id_detail']) ? intval($_GET['id_detail']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
    $idFaktur = isset($_GET['id_faktur']) ? intval($_GET['id_faktur']) : 0;

    // Single Detail View
    if ($idDetail > 0) {
        $sqlSingle = "SELECT ppd.*,
                             pp.id_faktur, pp.status_pembayaran, pp.jenis_pembayaran,
                             fp.nomor_faktur, fp.nomor_faktur_vendor, fp.tanggal_faktur_vendor,
                             fp.tanggal_jatuh_tempo, fp.total_tagihan, fp.terbayar AS total_terbayar_faktur,
                             fp.sisa_tagihan AS sisa_tagihan_faktur, fp.nama_bank AS bank_vendor,
                             fp.nomor_rekening AS norek_vendor, fp.atas_nama_rekening AS an_vendor,
                             po.nomor_po,
                             v.id_vendor, v.nama_perusahaan AS nama_vendor,
                             s.nama_site,
                             k.nama_karyawan AS nama_pembuat,
                             jk.nama_jabatan AS jabatan_pembuat,
                             dk.nama_divisi AS divisi_pembuat,
                             ka.nama_karyawan AS nama_approver,
                             ja.nama_jabatan AS jabatan_approver,
                             da.nama_divisi AS divisi_approver
                      FROM payment_purchase_detail ppd
                      JOIN payment_purchase pp ON ppd.id_pembayaran = pp.id_pembayaran
                      JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
                      JOIN purchase_order po ON fp.id_po = po.id_po
                      JOIN vendor v ON fp.id_vendor = v.id_vendor
                      JOIN site s ON fp.id_site = s.id_site
                      LEFT JOIN karyawan k ON ppd.id_karyawan = k.id_karyawan
                      LEFT JOIN jabatan jk ON k.id_jabatan = jk.id_jabatan
                      LEFT JOIN divisi dk ON k.id_divisi = dk.id_divisi
                      LEFT JOIN karyawan ka ON ppd.id_karyawan_approved = ka.id_karyawan
                      LEFT JOIN jabatan ja ON ka.id_jabatan = ja.id_jabatan
                      LEFT JOIN divisi da ON ka.id_divisi = da.id_divisi
                      WHERE ppd.id_pembayaran_detail = ? LIMIT 1";
        $stmtS = $conn->prepare($sqlSingle);
        $stmtS->bind_param("i", $idDetail);
        $stmtS->execute();
        $detail = $stmtS->get_result()->fetch_assoc();
        $stmtS->close();

        if (!$detail) {
            sendJson(false, 'Data pembayaran tidak ditemukan.', null, 404);
        }

        sendJson(true, 'Detail pembayaran berhasil dimuat.', $detail);
    }

    // List View with Filters & Pagination
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, min(100, intval($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;

    $search = trim($_GET['q'] ?? '');
    $jenis = isset($_GET['jenis']) && $_GET['jenis'] !== '' ? intval($_GET['jenis']) : null;
    $startDate = trim($_GET['start_date'] ?? '');
    $endDate = trim($_GET['end_date'] ?? '');

    $where = " WHERE 1=1 ";
    $params = [];
    $types = "";

    if ($idFaktur > 0) {
        $where .= " AND pp.id_faktur = ? ";
        $params[] = $idFaktur;
        $types .= "i";
    }

    if ($jenis !== null) {
        $where .= " AND pp.jenis_pembayaran = ? ";
        $params[] = $jenis;
        $types .= "i";
    }

    if (!empty($startDate)) {
        $where .= " AND DATE(ppd.tanggal_bayar) >= ? ";
        $params[] = $startDate;
        $types .= "s";
    }

    if (!empty($endDate)) {
        $where .= " AND DATE(ppd.tanggal_bayar) <= ? ";
        $params[] = $endDate;
        $types .= "s";
    }

    if ($search !== '') {
        $where .= " AND (ppd.kode_pembayaran LIKE ? OR fp.nomor_faktur LIKE ? OR fp.nomor_faktur_vendor LIKE ? OR v.nama_perusahaan LIKE ? OR ppd.bank_pengirim LIKE ? OR ppd.no_ref LIKE ?) ";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like, $like, $like]);
        $types .= "ssssss";
    }

    // Count Total Rows
    $sqlCount = "SELECT COUNT(*) as total 
                 FROM payment_purchase_detail ppd
                 JOIN payment_purchase pp ON ppd.id_pembayaran = pp.id_pembayaran
                 JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
                 JOIN vendor v ON fp.id_vendor = v.id_vendor
                 $where";
    $stmtC = $conn->prepare($sqlCount);
    if (!empty($params)) {
        $stmtC->bind_param($types, ...$params);
    }
    $stmtC->execute();
    $totalRows = (int)$stmtC->get_result()->fetch_assoc()['total'];
    $stmtC->close();

    // Data List
    $sqlList = "SELECT ppd.*,
                       pp.id_faktur, pp.status_pembayaran, pp.jenis_pembayaran,
                       fp.nomor_faktur, fp.nomor_faktur_vendor, fp.total_tagihan,
                       fp.tanggal_faktur_vendor, fp.tanggal_jatuh_tempo,
                       fp.status AS status_faktur,
                       v.nama_perusahaan AS nama_vendor,
                       po.nomor_po,
                       s.nama_site,
                       k.nama_karyawan AS nama_pembuat,
                       ka.nama_karyawan AS nama_approver
                FROM payment_purchase_detail ppd
                JOIN payment_purchase pp ON ppd.id_pembayaran = pp.id_pembayaran
                JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
                JOIN purchase_order po ON fp.id_po = po.id_po
                JOIN vendor v ON fp.id_vendor = v.id_vendor
                JOIN site s ON fp.id_site = s.id_site
                LEFT JOIN karyawan k ON ppd.id_karyawan = k.id_karyawan
                LEFT JOIN karyawan ka ON ppd.id_karyawan_approved = ka.id_karyawan
                $where
                ORDER BY ppd.id_pembayaran_detail DESC
                LIMIT ? OFFSET ?";

    $paramsList = array_merge($params, [$limit, $offset]);
    $typesList = $types . "ii";

    $stmtL = $conn->prepare($sqlList);
    $stmtL->bind_param($typesList, ...$paramsList);
    $stmtL->execute();
    $rows = $stmtL->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmtL->close();

    // Ringkasan Metrik Finansial
    $sqlMetrics = "SELECT 
                    COALESCE(SUM(ppd.nominal_pengiriman), 0) AS total_kas_keluar,
                    COALESCE(SUM(ppd.biaya_admin), 0) AS total_biaya_admin,
                    COUNT(DISTINCT ppd.id_pembayaran_detail) AS total_transaksi,
                    (SELECT COUNT(*) FROM faktur_po WHERE status = 'LUNAS') AS total_faktur_lunas,
                    (SELECT COUNT(*) FROM faktur_po WHERE status = 'SEBAGIAN DIBAYAR') AS total_faktur_kredit,
                    (SELECT COALESCE(SUM(sisa_tagihan), 0) FROM faktur_po WHERE status NOT IN ('LUNAS', 'BATAL')) AS total_sisa_hutang
                   FROM payment_purchase_detail ppd";
    $resM = $conn->query($sqlMetrics);
    $metrics = $resM ? $resM->fetch_assoc() : [];

    sendJson(true, 'Daftar pembayaran berhasil dimuat.', [
        'rows' => $rows,
        'pagination' => [
            'total' => $totalRows,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($totalRows / $limit)
        ],
        'metrics' => $metrics
    ]);
}

// Role Check: Purchasing hanya boleh melihat riwayat (GET), tidak boleh mencatat/mengubah pembayaran
if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
    if (!in_array($user['role'], [ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER])) {
        sendJson(false, 'Akses ditolak. Hanya Finance, Admin, atau Manager yang berhak mencatat dan memproses transaksi pembayaran kas.', null, 403);
    }
}

// -------------------------------------------------------------
// 2. POST: Catat Transaksi Pembayaran Baru (1x Lunas / Kredit)
// -------------------------------------------------------------
if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    if (!is_array($input)) {
        $input = $_POST;
    }

    $idFaktur = intval($input['id_faktur'] ?? 0);
    $jenisPembayaran = isset($input['jenis_pembayaran']) ? intval($input['jenis_pembayaran']) : 1; // 1=1x bayar (lunas), 0=kredit (sebagian)
    $tanggalBayar = trim($input['tanggal_bayar'] ?? date('Y-m-d H:i:s'));
    if (strlen($tanggalBayar) === 10) {
        $tanggalBayar .= ' ' . date('H:i:s');
    }

    $idKaryawanApproved = !empty($input['id_karyawan_approved']) ? intval($input['id_karyawan_approved']) : null;
    $bankPengirim = trim($input['bank_pengirim'] ?? '');
    $norekPengirim = trim($input['norek_pengirim'] ?? '');
    $anPengirim = trim($input['an_pengirim'] ?? '');
    $nominalPengiriman = floatval($input['nominal_pengiriman'] ?? 0);
    $biayaAdmin = floatval($input['biaya_admin'] ?? 0);
    $noRef = trim($input['no_ref'] ?? '');
    $keterangan = trim($input['keterangan'] ?? '');

    // Input Rekening Tujuan Vendor
    $bankTujuan = trim($input['bank_tujuan'] ?? '');
    $norekTujuan = trim($input['norek_tujuan'] ?? '');
    $anPengiriman = trim($input['an_pengiriman'] ?? '');

    // Validasi Dasar
    if ($idFaktur <= 0) {
        sendJson(false, 'Dokumen Faktur PO wajib dipilih.', null, 422);
    }
    if ($nominalPengiriman <= 0) {
        sendJson(false, 'Nominal pembayaran harus lebih besar dari 0.', null, 422);
    }
    if (empty($bankPengirim)) {
        sendJson(false, 'Nama Bank Pengirim wajib dipilih atau diisi.', null, 422);
    }
    if (empty($idKaryawanApproved)) {
        sendJson(false, 'Pejabat/Finance yang menyetujui transfer secara lisan wajib dipilih.', null, 422);
    }

    // Ambil Data Faktur Existing & Master Vendor
    $stmtF = $conn->prepare("SELECT fp.id_faktur, fp.nomor_faktur, fp.total_tagihan, fp.terbayar, fp.sisa_tagihan, 
                                    fp.tanggal_faktur_vendor, fp.tanggal_jatuh_tempo, fp.term_of_payment, fp.status,
                                    fp.nama_bank, fp.nomor_rekening, fp.atas_nama_rekening,
                                    v.nama_bank AS bank_vendor_master, v.nomor_rekening AS norek_vendor_master,
                                    v.nama_perusahaan AS nama_vendor
                             FROM faktur_po fp
                             JOIN vendor v ON fp.id_vendor = v.id_vendor
                             WHERE fp.id_faktur = ? FOR UPDATE");
    $conn->begin_transaction();

    $stmtF->bind_param("i", $idFaktur);
    $stmtF->execute();
    $faktur = $stmtF->get_result()->fetch_assoc();
    $stmtF->close();

    if (!$faktur) {
        $conn->rollback();
        sendJson(false, 'Dokumen Faktur PO tidak ditemukan.', null, 404);
    }

    if (in_array($faktur['status'], ['LUNAS', 'BATAL', 'DRAFT'])) {
        $conn->rollback();
        sendJson(false, "Faktur berstatus '{$faktur['status']}' tidak dapat dilakukan pembayaran.", null, 400);
    }

    $sisaTagihanExisting = floatval($faktur['sisa_tagihan']);
    if ($nominalPengiriman > $sisaTagihanExisting) {
        $conn->rollback();
        sendJson(false, "Nominal pembayaran (Rp " . number_format($nominalPengiriman, 0, ',', '.') . ") melebihi sisa tagihan faktur (Rp " . number_format($sisaTagihanExisting, 0, ',', '.') . ").", null, 422);
    }

    // Default Fallback Rekening Tujuan jika kosong
    if (empty($bankTujuan)) {
        $bankTujuan = !empty($faktur['nama_bank']) ? $faktur['nama_bank'] : ($faktur['bank_vendor_master'] ?? '');
    }
    if (empty($norekTujuan)) {
        $norekTujuan = !empty($faktur['nomor_rekening']) ? $faktur['nomor_rekening'] : ($faktur['norek_vendor_master'] ?? '');
    }
    if (empty($anPengiriman)) {
        $anPengiriman = !empty($faktur['atas_nama_rekening']) ? $faktur['atas_nama_rekening'] : ($faktur['nama_vendor'] ?? '');
    }

    // ATURAN BISNIS: Jika KREDIT / SEBAGIAN, tanggal bayar tidak boleh melebihi tanggal jatuh tempo TOP Faktur
    $tglBayarOnly = date('Y-m-d', strtotime($tanggalBayar));
    $tglJatuhTempo = $faktur['tanggal_jatuh_tempo'];

    if ($jenisPembayaran === 0 && !empty($tglJatuhTempo) && $tglJatuhTempo !== '0000-00-00') {
        if (strtotime($tglBayarOnly) > strtotime($tglJatuhTempo)) {
            $conn->rollback();
            sendJson(false, "Pembayaran kredit / sebagian tidak diizinkan melebihi Tanggal Jatuh Tempo TOP Faktur (" . date('d/m/Y', strtotime($tglJatuhTempo)) . ").", null, 422);
        }
    }

    // Handle Upload File Bukti Bayar jika Base64
    $fileBuktiBayar = null;
    $uploadDir = __DIR__ . '/../../uploads/pembayaran/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($input['file_bukti_bayar_base64'])) {
        $ext = preg_match('/^data:application\/pdf/', $input['file_bukti_bayar_base64']) ? 'pdf' : 'jpg';
        $dataImg = preg_replace('/^data:[^;]+;base64,/', '', $input['file_bukti_bayar_base64']);
        $filename = 'pay_proof_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, base64_decode($dataImg))) {
            $fileBuktiBayar = $filename;
        }
    }

    try {
        // 1. Cek atau Buat Record Master di payment_purchase
        $stmtC = $conn->prepare("SELECT id_pembayaran FROM payment_purchase WHERE id_faktur = ? LIMIT 1");
        $stmtC->bind_param("i", $idFaktur);
        $stmtC->execute();
        $existingMaster = $stmtC->get_result()->fetch_assoc();
        $stmtC->close();

        $sisaPiutangBaru = max(0, $sisaTagihanExisting - $nominalPengiriman);
        $statusPembayaranBaru = ($sisaPiutangBaru <= 0) ? 1 : 0; // 1=lunas, 0=belum

        if ($existingMaster) {
            $idPembayaran = (int)$existingMaster['id_pembayaran'];
            $stmtUpM = $conn->prepare("UPDATE payment_purchase SET status_pembayaran = ?, jenis_pembayaran = ? WHERE id_pembayaran = ?");
            $stmtUpM->bind_param("iii", $statusPembayaranBaru, $jenisPembayaran, $idPembayaran);
            $stmtUpM->execute();
            $stmtUpM->close();
        } else {
            $stmtInsM = $conn->prepare("INSERT INTO payment_purchase (id_faktur, status_pembayaran, jenis_pembayaran) VALUES (?, ?, ?)");
            $stmtInsM->bind_param("iii", $idFaktur, $statusPembayaranBaru, $jenisPembayaran);
            $stmtInsM->execute();
            $idPembayaran = $conn->insert_id;
            $stmtInsM->close();
        }

        // 2. Buat Record Detail Transaksi di payment_purchase_detail
        $kodePembayaran = generateKodePembayaran($conn);
        $idKaryawanInput = $user['id_karyawan'] ?? ($user['id'] ?? 1);

        $sqlD = "INSERT INTO payment_purchase_detail (
                    id_pembayaran, kode_pembayaran, tanggal_bayar, id_karyawan, id_karyawan_approved,
                    bank_pengirim, norek_pengirim, an_pengirim, nominal_pengiriman, biaya_admin,
                    no_ref, file_bukti_bayar, sisa_piutang, keterangan,
                    bank_tujuan, norek_tujuan, an_pengiriman
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmtD = $conn->prepare($sqlD);
        $stmtD->bind_param(
            "issiisssddssdssss",
            $idPembayaran, $kodePembayaran, $tanggalBayar, $idKaryawanInput, $idKaryawanApproved,
            $bankPengirim, $norekPengirim, $anPengirim, $nominalPengiriman, $biayaAdmin,
            $noRef, $fileBuktiBayar, $sisaPiutangBaru, $keterangan,
            $bankTujuan, $norekTujuan, $anPengiriman
        );
        $stmtD->execute();
        $idDetailBaru = $conn->insert_id;
        $stmtD->close();

        // 3. Update Status & Finansial di faktur_po
        $terbayarBaru = floatval($faktur['terbayar']) + $nominalPengiriman;
        $statusFakturBaru = ($sisaPiutangBaru <= 0) ? 'LUNAS' : 'SEBAGIAN DIBAYAR';

        $stmtUpF = $conn->prepare("UPDATE faktur_po SET terbayar = ?, sisa_tagihan = ?, status = ?, updated_at = NOW() WHERE id_faktur = ?");
        $stmtUpF->bind_param("ddsi", $terbayarBaru, $sisaPiutangBaru, $statusFakturBaru, $idFaktur);
        $stmtUpF->execute();
        $stmtUpF->close();

        $conn->commit();

        sendJson(true, "Pembayaran {$kodePembayaran} sebesar Rp " . number_format($nominalPengiriman, 0, ',', '.') . " berhasil dicatat.", [
            'id_pembayaran_detail' => $idDetailBaru,
            'id_pembayaran' => $idPembayaran,
            'kode_pembayaran' => $kodePembayaran,
            'nominal_pengiriman' => $nominalPengiriman,
            'sisa_piutang' => $sisaPiutangBaru,
            'status_faktur' => $statusFakturBaru
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        sendJson(false, 'Gagal menyimpan transaksi pembayaran: ' . $e->getMessage(), null, 500);
    }
}

// -------------------------------------------------------------
// 3. PUT: Perbarui Data Transaksi Pembayaran
// -------------------------------------------------------------
if ($method === 'PUT') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    $idDetail = intval($input['id_pembayaran_detail'] ?? 0);
    if ($idDetail <= 0) {
        sendJson(false, 'ID Pembayaran tidak valid.', null, 422);
    }

    $stmtOld = $conn->prepare("SELECT ppd.*, pp.id_faktur, fp.total_tagihan, fp.terbayar, fp.sisa_tagihan 
                               FROM payment_purchase_detail ppd
                               JOIN payment_purchase pp ON ppd.id_pembayaran = pp.id_pembayaran
                               JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
                               WHERE ppd.id_pembayaran_detail = ? LIMIT 1");
    $stmtOld->bind_param("i", $idDetail);
    $stmtOld->execute();
    $old = $stmtOld->get_result()->fetch_assoc();
    $stmtOld->close();

    if (!$old) {
        sendJson(false, 'Data transaksi pembayaran tidak ditemukan.', null, 404);
    }

    $idKaryawanApproved = !empty($input['id_karyawan_approved']) ? intval($input['id_karyawan_approved']) : $old['id_karyawan_approved'];
    $bankPengirim = trim($input['bank_pengirim'] ?? $old['bank_pengirim']);
    $norekPengirim = trim($input['norek_pengirim'] ?? $old['norek_pengirim']);
    $anPengirim = trim($input['an_pengirim'] ?? $old['an_pengirim']);
    $biayaAdmin = isset($input['biaya_admin']) ? floatval($input['biaya_admin']) : floatval($old['biaya_admin']);
    $noRef = trim($input['no_ref'] ?? $old['no_ref']);
    $keterangan = trim($input['keterangan'] ?? $old['keterangan']);

    // Rekening Tujuan Vendor
    $bankTujuan = isset($input['bank_tujuan']) ? trim($input['bank_tujuan']) : ($old['bank_tujuan'] ?? '');
    $norekTujuan = isset($input['norek_tujuan']) ? trim($input['norek_tujuan']) : ($old['norek_tujuan'] ?? '');
    $anPengiriman = isset($input['an_pengiriman']) ? trim($input['an_pengiriman']) : ($old['an_pengiriman'] ?? '');

    // Handle Upload File Bukti Bayar baru jika Base64
    $fileBuktiBayar = $old['file_bukti_bayar'];
    $uploadDir = __DIR__ . '/../../uploads/pembayaran/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (!empty($input['file_bukti_bayar_base64'])) {
        $ext = preg_match('/^data:application\/pdf/', $input['file_bukti_bayar_base64']) ? 'pdf' : 'jpg';
        $dataImg = preg_replace('/^data:[^;]+;base64,/', '', $input['file_bukti_bayar_base64']);
        $filename = 'pay_proof_' . time() . '_' . rand(100, 999) . '.' . $ext;
        if (file_put_contents($uploadDir . $filename, base64_decode($dataImg))) {
            $fileBuktiBayar = $filename;
        }
    }

    $stmtUp = $conn->prepare("UPDATE payment_purchase_detail SET 
                                id_karyawan_approved = ?,
                                bank_pengirim = ?,
                                norek_pengirim = ?,
                                an_pengirim = ?,
                                biaya_admin = ?,
                                no_ref = ?,
                                file_bukti_bayar = ?,
                                keterangan = ?,
                                bank_tujuan = ?,
                                norek_tujuan = ?,
                                an_pengiriman = ?,
                                updated_at = NOW()
                              WHERE id_pembayaran_detail = ?");
    $stmtUp->bind_param("isssdssssssi", $idKaryawanApproved, $bankPengirim, $norekPengirim, $anPengirim, $biayaAdmin, $noRef, $fileBuktiBayar, $keterangan, $bankTujuan, $norekTujuan, $anPengiriman, $idDetail);
    
    if ($stmtUp->execute()) {
        $stmtUp->close();
        sendJson(true, "Data pembayaran {$old['kode_pembayaran']} berhasil diperbarui.");
    } else {
        $err = $stmtUp->error;
        $stmtUp->close();
        sendJson(false, "Gagal memperbarui data pembayaran: {$err}", null, 500);
    }
}
