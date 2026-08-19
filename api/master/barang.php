<?php
/**
 * API Master: Barang CRUD Endpoint - PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

// Handle POST with _method override for PUT/DELETE
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }
}

// -------------------------------------------------------------
// GET: Mengambil Data Barang (List / Detail)
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $vendorId = isset($_GET['vendor_id']) && is_numeric($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : null;
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (b.nama_barang LIKE ? OR b.kode_barang LIKE ? OR b.deskripsi LIKE ? OR b.serial_number LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        for ($i = 0; $i < 4; $i++) {
            $params[] = $searchWildcard;
            $types .= "s";
        }
    }

    if ($vendorId !== null) {
        $whereSql .= " AND (b.default_id_vendor = ? OR b.default_id_vendor IS NULL)";
        $params[] = $vendorId;
        $types .= "i";
    }

    // 1. Total records
    $countSql = "SELECT COUNT(*) as total FROM barang b" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    // 2. Query Data
    $sql = "SELECT b.id_barang, b.kode_barang, b.nama_barang, b.id_merk, b.id_kategori, b.default_id_vendor,
                   b.jenis, b.satuan, b.asset, b.serial_number, b.foto1, b.foto2, b.deskripsi, b.created_at, b.id_karyawan, b.aktif,
                   v.nama_perusahaan AS nama_vendor, v.kode_vendor,
                   k.nama_kategori, m.nama_merk, kry.nama_karyawan AS pembuat_barang
            FROM barang b
            LEFT JOIN vendor v ON b.default_id_vendor = v.id_vendor
            LEFT JOIN kategori_barang k ON b.id_kategori = k.id_kategori
            LEFT JOIN merk_barang m ON b.id_merk = m.id_merk
            LEFT JOIN karyawan kry ON b.id_karyawan = kry.id_karyawan"
            . $whereSql . " ORDER BY b.id_barang DESC LIMIT ? OFFSET ?";

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
        $items[] = [
            'id_barang' => (int)$row['id_barang'],
            'kode_barang' => $row['kode_barang'] ?? '',
            'nama_barang' => $row['nama_barang'] ?? '',
            'id_merk' => (int)($row['id_merk'] ?? 1),
            'nama_merk' => $row['nama_merk'] ?? 'Umum',
            'id_kategori' => (int)($row['id_kategori'] ?? 1),
            'nama_kategori' => $row['nama_kategori'] ?? 'Umum',
            'default_id_vendor' => $row['default_id_vendor'] ? (int)$row['default_id_vendor'] : null,
            'nama_vendor' => $row['nama_vendor'] ?? '',
            'kode_vendor' => $row['kode_vendor'] ?? '',
            'jenis' => (int)($row['jenis'] ?? 1),
            'jenis_label' => (int)($row['jenis'] ?? 1) === 1 ? 'Persediaan' : 'Jasa',
            'satuan' => $row['satuan'] ?? 'PCS',
            'asset' => (int)($row['asset'] ?? 0),
            'asset_label' => (int)($row['asset'] ?? 0) === 1 ? 'Asset' : 'Bukan Asset',
            'serial_number' => $row['serial_number'] ?? '',
            'foto1' => $row['foto1'] ?? '',
            'foto2' => $row['foto2'] ?? '',
            'deskripsi' => $row['deskripsi'] ?? '',
            'created_at' => $row['created_at'] ? date('d-m-Y H:i', strtotime($row['created_at'])) : '-',
            'pembuat_barang' => $row['pembuat_barang'] ?? '-',
            'aktif' => isset($row['aktif']) ? (int)$row['aktif'] : 1,
            'aktif_label' => (isset($row['aktif']) && (int)$row['aktif'] === 0) ? 'Non-aktif' : 'Aktif'
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data master barang berhasil diambil.', [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'from' => $totalRecords > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $totalRecords)
        ]
    ], 200);
}

// -------------------------------------------------------------
// Hanya Role ADMIN yang diizinkan melakukan CREATE, UPDATE, DELETE
// -------------------------------------------------------------
if ($currentUser['role'] !== ROLE_ADMIN) {
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data master.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Barang Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $namaBarang = trim($input['nama_barang'] ?? '');
    $kodeBarang = trim($input['kode_barang'] ?? '');
    $satuan = in_array(strtoupper($input['satuan'] ?? ''), ['UNIT', 'PCS']) ? strtoupper($input['satuan']) : 'PCS';
    $jenis = isset($input['jenis']) ? (int)$input['jenis'] : 1;
    $asset = isset($input['asset']) ? (int)$input['asset'] : 0;
    $idVendor = !empty($input['default_id_vendor']) ? (int)$input['default_id_vendor'] : null;
    $idKategori = !empty($input['id_kategori']) ? (int)$input['id_kategori'] : 1;
    $idMerk = !empty($input['id_merk']) ? (int)$input['id_merk'] : 1;
    $serialNumber = trim($input['serial_number'] ?? '');
    $deskripsi = trim($input['deskripsi'] ?? '');
    $idKaryawan = $currentUser['id_karyawan'] ?? 1;
    $foto1 = trim($input['foto1'] ?? '');
    $foto2 = trim($input['foto2'] ?? '');
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaBarang)) {
        jsonResponse(false, 'Nama barang wajib diisi.', null, 422);
    }

    // Auto-generate kode barang jika kosong
    if (empty($kodeBarang)) {
        $resCount = $conn->query("SELECT MAX(id_barang) as max_id FROM barang");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeBarang = 'BRG' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
    }

    $stmt = $conn->prepare("INSERT INTO barang (kode_barang, id_merk, id_kategori, default_id_vendor, nama_barang, jenis, satuan, asset, serial_number, foto1, foto2, deskripsi, id_karyawan, aktif) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siiisisissssii", $kodeBarang, $idMerk, $idKategori, $idVendor, $namaBarang, $jenis, $satuan, $asset, $serialNumber, $foto1, $foto2, $deskripsi, $idKaryawan, $aktif);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        jsonResponse(true, 'Barang berhasil ditambahkan.', ['id_barang' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan barang.', null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Data Barang
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idBarang = isset($input['id_barang']) ? (int)$input['id_barang'] : 0;
    $namaBarang = trim($input['nama_barang'] ?? '');
    $kodeBarang = trim($input['kode_barang'] ?? '');
    $satuan = in_array(strtoupper($input['satuan'] ?? ''), ['UNIT', 'PCS']) ? strtoupper($input['satuan']) : 'PCS';
    $jenis = isset($input['jenis']) ? (int)$input['jenis'] : 1;
    $asset = isset($input['asset']) ? (int)$input['asset'] : 0;
    $idVendor = !empty($input['default_id_vendor']) ? (int)$input['default_id_vendor'] : null;
    $idKategori = !empty($input['id_kategori']) ? (int)$input['id_kategori'] : 1;
    $idMerk = !empty($input['id_merk']) ? (int)$input['id_merk'] : 1;
    $serialNumber = trim($input['serial_number'] ?? '');
    $deskripsi = trim($input['deskripsi'] ?? '');
    $foto1 = trim($input['foto1'] ?? '');
    $foto2 = trim($input['foto2'] ?? '');
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if ($idBarang <= 0 || empty($namaBarang)) {
        jsonResponse(false, 'ID barang dan nama barang wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE barang SET kode_barang = ?, id_merk = ?, id_kategori = ?, default_id_vendor = ?, 
                            nama_barang = ?, jenis = ?, satuan = ?, asset = ?, serial_number = ?, foto1 = ?, foto2 = ?, deskripsi = ?, aktif = ? 
                            WHERE id_barang = ?");
    $stmt->bind_param("siiisisissssii", $kodeBarang, $idMerk, $idKategori, $idVendor, $namaBarang, $jenis, $satuan, $asset, $serialNumber, $foto1, $foto2, $deskripsi, $aktif, $idBarang);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Data barang berhasil diperbarui.', ['id_barang' => $idBarang], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui data barang.', null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Data Barang
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idBarang = isset($input['id_barang']) ? (int)$input['id_barang'] : (int)($_GET['id'] ?? 0);

    if ($idBarang <= 0) {
        jsonResponse(false, 'ID barang tidak valid.', null, 422);
    }

    $stmt = $conn->prepare("DELETE FROM barang WHERE id_barang = ?");
    $stmt->bind_param("i", $idBarang);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Barang berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus barang. Data mungkin terkait dengan transaksi lain.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
