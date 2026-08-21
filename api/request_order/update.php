<?php
/**
 * API Modul Request Order (RO) - Update / Edit
 * Endpoint: POST / PUT /api/request_order/update.php
 * Path: api/request_order/update.php
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../config/koneksi.php';
require_once __DIR__ . '/../../config/session.php';

// Verifikasi Sesi & Hak Akses
$currentUser = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(false, 'Metode HTTP tidak didukung. Gunakan POST atau PUT.', null, 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$idRequest = isset($input['id_request']) ? (int)$input['id_request'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if ($idRequest <= 0) {
    jsonResponse(false, 'ID Request Order tidak valid.', null, 400);
}

// 1. Ambil data RO eksisting & validasi status
$stmtCheck = $conn->prepare("SELECT id_request, nomor, id_karyawan, status, tanggal_ro FROM request_order WHERE id_request = ? LIMIT 1");
$stmtCheck->bind_param("i", $idRequest);
$stmtCheck->execute();
$resCheck = $stmtCheck->get_result();

if (!$resCheck || $resCheck->num_rows === 0) {
    jsonResponse(false, 'Data Request Order tidak ditemukan.', null, 404);
}
$existingRo = $resCheck->fetch_assoc();
$stmtCheck->close();

// Validasi hanya status DRAFT atau TERKIRIM yang boleh diedit
if (!in_array($existingRo['status'], ['DRAFT', 'TERKIRIM'])) {
    jsonResponse(false, "Request Order dengan status '{$existingRo['status']}' sudah tidak dapat diubah.", null, 422);
}

// Validasi Hak Akses Edit:
// - Status DRAFT: Hanya boleh diedit oleh pembuatnya sendiri (atau Super Admin).
// - Status TERKIRIM: Boleh diedit oleh pembuatnya sendiri ATAU tim Logistik, Purchasing, Manager, dan Admin.
$isOwner = ((int)$existingRo['id_karyawan'] === (int)($currentUser['id_karyawan'] ?? 0));
$isLogistikManagement = in_array($currentUser['role'], [ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER, ROLE_ADMIN]);

if ($existingRo['status'] === 'DRAFT') {
    if (!$isOwner && $currentUser['role'] !== ROLE_ADMIN) {
        jsonResponse(false, 'Dokumen berstatus DRAFT hanya dapat diedit oleh pembuatnya sendiri.', null, 403);
    }
} elseif ($existingRo['status'] === 'TERKIRIM') {
    if (!$isOwner && !$isLogistikManagement) {
        jsonResponse(false, 'Anda tidak memiliki hak akses untuk mengedit Request Order ini.', null, 403);
    }
}

// 2. Ekstraksi Input
$idSite = !empty($input['id_site']) ? (int)$input['id_site'] : 0;
$idVendor = !empty($input['id_vendor']) ? (int)$input['id_vendor'] : null;
$idKaryawan = !empty($input['id_karyawan']) ? (int)$input['id_karyawan'] : $existingRo['id_karyawan'];
$prioritas = in_array(strtoupper($input['prioritas'] ?? ''), ['NORMAL', 'URGENT']) ? strtoupper($input['prioritas']) : 'NORMAL';
$status = in_array(strtoupper($input['status'] ?? ''), ['DRAFT', 'TERKIRIM']) ? strtoupper($input['status']) : $existingRo['status'];
$keterangan = trim($input['keterangan'] ?? '');
$items = isset($input['items']) && is_array($input['items']) ? $input['items'] : [];

if ($idSite <= 0) {
    jsonResponse(false, 'Site penempatan / lokasi tujuan pengadaan wajib dipilih.', null, 422);
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

// 3. Mulai Database Transaction
$conn->begin_transaction();

try {
    $tanggalStatus = ($status === 'TERKIRIM') ? date('Y-m-d H:i:s') : null;

    // Update Header Request Order
    $stmtHeader = $conn->prepare("UPDATE request_order 
                                  SET id_karyawan = ?, id_site = ?, status = ?, prioritas = ?, id_vendor = ?, tanggal_status = ?, keterangan = ? 
                                  WHERE id_request = ?");
    $stmtHeader->bind_param("iississi", $idKaryawan, $idSite, $status, $prioritas, $idVendor, $tanggalStatus, $keterangan, $idRequest);
    
    if (!$stmtHeader->execute()) {
        throw new Exception("Gagal memperbarui header Request Order: " . $stmtHeader->error);
    }
    $stmtHeader->close();

    // Hapus detail lama untuk diperbarui secara utuh
    $stmtDel = $conn->prepare("DELETE FROM request_order_detail WHERE id_request = ?");
    $stmtDel->bind_param("i", $idRequest);
    $stmtDel->execute();
    $stmtDel->close();

    // Simpan Detail Baru
    $stmtDetail = $conn->prepare("INSERT INTO request_order_detail (id_request, id_barang, kode_barang, nama_barang, qty, satuan, harga, subtotal) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($cleanItems as $cItem) {
        $idBarang = $cItem['id_barang'];
        $kodeBarang = $cItem['kode_barang'];
        $namaBarang = $cItem['nama_barang'];
        $satuan = $cItem['satuan'];

        // HANYA jika status = 'TERKIRIM' dan barang belum ada di master barang ($idBarang kosong)
        if ($status === 'TERKIRIM' && empty($idBarang)) {
            $chkExisting = $conn->prepare("SELECT id_barang, kode_barang FROM barang WHERE LOWER(TRIM(nama_barang)) = LOWER(TRIM(?)) LIMIT 1");
            $chkExisting->bind_param("s", $namaBarang);
            $chkExisting->execute();
            $resExist = $chkExisting->get_result();

            if ($resExist && $rowExist = $resExist->fetch_assoc()) {
                $idBarang = (int)$rowExist['id_barang'];
                $kodeBarang = $rowExist['kode_barang'];
            } else {
                if (empty($kodeBarang)) {
                    $resCount = $conn->query("SELECT MAX(id_barang) as max_id FROM barang");
                    $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
                    $kodeBarang = 'BRG' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
                }

                $idMerkDefault = 1;
                $idKategoriDefault = 1;
                $jenisDefault = 1;
                $assetDefault = 0;
                $aktifDefault = 1;
                $serialDefault = '';
                $deskripsiDefault = 'Ditambahkan otomatis dari Request Order ' . $existingRo['nomor'];

                $stmtNewBrg = $conn->prepare("INSERT INTO barang (kode_barang, id_merk, id_kategori, default_id_vendor, nama_barang, jenis, satuan, asset, serial_number, deskripsi, id_karyawan, aktif) 
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtNewBrg->bind_param("siiisisissii", $kodeBarang, $idMerkDefault, $idKategoriDefault, $idVendor, $namaBarang, $jenisDefault, $satuan, $assetDefault, $serialDefault, $deskripsiDefault, $idKaryawan, $aktifDefault);
                
                if (!$stmtNewBrg->execute()) {
                    throw new Exception("Gagal mendaftarkan material baru '{$namaBarang}' ke master barang: " . $stmtNewBrg->error);
                }
                $idBarang = $conn->insert_id;
                $stmtNewBrg->close();

                // Inisialisasi stok = 0 untuk seluruh site penyimpanan aktif
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

    $conn->commit();

    $actionMsg = ($status === 'TERKIRIM') 
        ? "Request Order {$existingRo['nomor']} berhasil diperbarui dan dikirimkan ke Logistik." 
        : "Perubahan Draft Request Order {$existingRo['nomor']} berhasil disimpan.";

    jsonResponse(true, $actionMsg, [
        'id_request' => $idRequest,
        'nomor' => $existingRo['nomor'],
        'status' => $status
    ]);

} catch (Exception $e) {
    $conn->rollback();
    jsonResponse(false, 'Gagal memperbarui Request Order: ' . $e->getMessage(), null, 500);
}
