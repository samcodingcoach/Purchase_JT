<?php
/**
 * API Request Order: Create RO Endpoint - PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan POST.', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// 1. Ekstraksi Data Header RO
$idKaryawan = !empty($input['id_karyawan']) ? (int)$input['id_karyawan'] : ($currentUser['id_karyawan'] ?? null);
if (!$idKaryawan && $currentUser['role'] === ROLE_ADMIN) {
    // Jika admin tidak memilih karyawan, cari karyawan default
    $resKry = $conn->query("SELECT id_karyawan FROM karyawan WHERE aktif = 1 LIMIT 1");
    if ($resKry && $rKry = $resKry->fetch_assoc()) {
        $idKaryawan = (int)$rKry['id_karyawan'];
    }
}

$idSite = !empty($input['id_site']) ? (int)$input['id_site'] : 0;
$idVendor = !empty($input['id_vendor']) ? (int)$input['id_vendor'] : null;
$tanggalRo = !empty($input['tanggal_ro']) ? trim($input['tanggal_ro']) : date('Y-m-d H:i:s');
if (strlen($tanggalRo) === 10) {
    // Format YYYY-MM-DD -> tambahkan jam, menit, detik saat ini
    $tanggalRo .= ' ' . date('H:i:s');
}
$nomorRo = trim($input['nomor'] ?? '');
$prioritas = in_array(strtoupper($input['prioritas'] ?? ''), ['NORMAL', 'URGENT']) ? strtoupper($input['prioritas']) : 'NORMAL';
$status = in_array(strtoupper($input['status'] ?? ''), ['DRAFT', 'TERKIRIM']) ? strtoupper($input['status']) : 'DRAFT';
$keterangan = trim($input['keterangan'] ?? '');
$items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

// 2. Validasi Input Header & Items
if ($idSite <= 0) {
    jsonResponse(false, 'Site penempatan / lokasi tujuan pengadaan wajib dipilih.', null, 422);
}

if (!$idKaryawan) {
    jsonResponse(false, 'Identitas karyawan peminta wajib ditentukan.', null, 422);
}

if (empty($items)) {
    jsonResponse(false, 'Harap tambahkan minimal 1 baris item material / barang dalam Request Order.', null, 422);
}

// Validasi baris item
$cleanItems = [];
foreach ($items as $idx => $item) {
    $namaBarang = trim($item['nama_barang'] ?? '');
    $qty = isset($item['qty']) && is_numeric($item['qty']) ? (float)$item['qty'] : 0;
    $satuan = in_array(strtoupper($item['satuan'] ?? ''), ['UNIT', 'PCS']) ? strtoupper($item['satuan']) : 'PCS';
    $harga = isset($item['harga']) && is_numeric($item['harga']) ? max(0, (float)$item['harga']) : 0;
    $idBarang = !empty($item['id_barang']) ? (int)$item['id_barang'] : null;
    $kodeBarang = trim($item['kode_barang'] ?? '');

    if (empty($namaBarang)) {
        jsonResponse(false, "Nama barang pada baris ke-" . ($idx + 1) . " tidak boleh kosong.", null, 422);
    }
    if ($qty <= 0) {
        jsonResponse(false, "Jumlah (Qty) untuk barang '{$namaBarang}' harus lebih besar dari 0.", null, 422);
    }

    $subtotal = $qty * $harga;

    $cleanItems[] = [
        'id_barang' => $idBarang,
        'kode_barang' => $kodeBarang,
        'nama_barang' => $namaBarang,
        'qty' => $qty,
        'satuan' => $satuan,
        'harga' => $harga,
        'subtotal' => $subtotal
    ];
}

// 3. Generate Nomor RO jika kosong (Format: RO-YYMM-0000)
if (empty($nomorRo)) {
    $time = strtotime($tanggalRo);
    $yymm = date('ym', $time);
    $prefix = "RO-{$yymm}-";

    $stmtSeq = $conn->prepare("SELECT nomor FROM request_order WHERE nomor LIKE ? ORDER BY id_request DESC LIMIT 100");
    $searchPattern = $prefix . "%";
    $stmtSeq->bind_param("s", $searchPattern);
    $stmtSeq->execute();
    $resSeq = $stmtSeq->get_result();

    $maxSequence = 0;
    while ($row = $resSeq->fetch_assoc()) {
        $numStr = $row['nomor'] ?? '';
        if (preg_match('/^RO-\d{4}-(\d+)$/i', $numStr, $matches)) {
            $seq = (int)$matches[1];
            if ($seq > $maxSequence) {
                $maxSequence = $seq;
            }
        }
    }
    $stmtSeq->close();

    $nextSequence = $maxSequence + 1;
    $nomorRo = $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
}

// 4. Mulai Database Transaction
$conn->begin_transaction();

try {
    $tanggalStatus = ($status === 'TERKIRIM') ? date('Y-m-d H:i:s') : null;

    $stmtHeader = $conn->prepare("INSERT INTO request_order (nomor, tanggal_ro, id_karyawan, id_site, status, prioritas, id_vendor, tanggal_status, keterangan) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtHeader->bind_param("ssiississ", $nomorRo, $tanggalRo, $idKaryawan, $idSite, $status, $prioritas, $idVendor, $tanggalStatus, $keterangan);
    
    if (!$stmtHeader->execute()) {
        throw new Exception("Gagal menyimpan header Request Order: " . $stmtHeader->error);
    }
    $idRequest = $conn->insert_id;
    $stmtHeader->close();

    // 5. Simpan Item Detail
    $stmtDetail = $conn->prepare("INSERT INTO request_order_detail (id_request, id_barang, kode_barang, nama_barang, qty, satuan, harga, subtotal) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($cleanItems as $cItem) {
        $idBarang = $cItem['id_barang'];
        $kodeBarang = $cItem['kode_barang'];
        $namaBarang = $cItem['nama_barang'];
        $satuan = $cItem['satuan'];

        // HANYA jika status = 'TERKIRIM' dan barang belum ada di master barang ($idBarang kosong)
        if ($status === 'TERKIRIM' && empty($idBarang)) {
            // 1. Cek apakah barang dengan nama persis sudah ada di tabel barang
            $chkExisting = $conn->prepare("SELECT id_barang, kode_barang FROM barang WHERE LOWER(TRIM(nama_barang)) = LOWER(TRIM(?)) LIMIT 1");
            $chkExisting->bind_param("s", $namaBarang);
            $chkExisting->execute();
            $resExist = $chkExisting->get_result();

            if ($resExist && $rowExist = $resExist->fetch_assoc()) {
                $idBarang = (int)$rowExist['id_barang'];
                $kodeBarang = $rowExist['kode_barang'];
            } else {
                // 2. Generate kode barang baru jika belum ada
                if (empty($kodeBarang)) {
                    $resCount = $conn->query("SELECT MAX(id_barang) as max_id FROM barang");
                    $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
                    $kodeBarang = 'BRG' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                }

                // 3. Insert ke tabel barang (default kategori 1 Umum, merk 1 Umum, jenis 1 Persediaan, aktif 1)
                $idMerkDefault = 1;
                $idKategoriDefault = 1;
                $jenisDefault = 1;
                $assetDefault = 0;
                $aktifDefault = 1;
                $serialDefault = '';
                $deskripsiDefault = 'Ditambahkan otomatis dari Request Order ' . $nomorRo;

                $stmtNewBrg = $conn->prepare("INSERT INTO barang (kode_barang, id_merk, id_kategori, default_id_vendor, nama_barang, jenis, satuan, asset, serial_number, deskripsi, id_karyawan, aktif) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtNewBrg->bind_param("siiisisissii", $kodeBarang, $idMerkDefault, $idKategoriDefault, $idVendor, $namaBarang, $jenisDefault, $satuan, $assetDefault, $serialDefault, $deskripsiDefault, $idKaryawan, $aktifDefault);
                
                if (!$stmtNewBrg->execute()) {
                    throw new Exception("Gagal mendaftarkan material baru '{$namaBarang}' ke master barang: " . $stmtNewBrg->error);
                }
                $idBarang = $conn->insert_id;
                $stmtNewBrg->close();

                // 4. Inisialisasi stok = 0 untuk seluruh site penyimpanan aktif
                $resSites = $conn->query("SELECT id_site FROM site WHERE penyimpanan_stok = 1");
                if ($resSites) {
                    while ($stRow = $resSites->fetch_assoc()) {
                        $stId = (int)$stRow['id_site'];
                        $insDef = $conn->prepare("INSERT INTO barang_stok (id_barang, id_site, stok) VALUES (?, ?, 0)");
                        $insDef->bind_param("ii", $idBarang, $stId);
                        $insDef->execute();
                        $insDef->close();
                    }
                }
            }
            $chkExisting->close();
        }

        $stmtDetail->bind_param("iissdsdd", 
            $idRequest, 
            $idBarang, 
            $kodeBarang, 
            $namaBarang, 
            $cItem['qty'], 
            $cItem['satuan'], 
            $cItem['harga'], 
            $cItem['subtotal']
        );
        if (!$stmtDetail->execute()) {
            throw new Exception("Gagal menyimpan detail item '{$namaBarang}': " . $stmtDetail->error);
        }
    }
    $stmtDetail->close();

    // Commit Transaction jika seluruh tahapan berhasil
    $conn->commit();

    $actionMsg = ($status === 'TERKIRIM') 
        ? "Request Order {$nomorRo} berhasil dibuat dan dikirimkan ke Logistik." 
        : "Request Order {$nomorRo} berhasil disimpan sebagai Draft.";

    jsonResponse(true, $actionMsg, [
        'id_request' => $idRequest,
        'nomor_ro' => $nomorRo,
        'status' => $status,
        'prioritas' => $prioritas,
        'total_items' => count($cleanItems)
    ], 201);

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, "Terjadi kesalahan saat memproses data: " . $e->getMessage(), null, 500);
}
