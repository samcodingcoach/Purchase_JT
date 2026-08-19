<?php
/**
 * API Master: Karyawan CRUD Endpoint - PT Jaya Teknis
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../middleware/auth.php';

$currentUser = apiAuth();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    if (isset($input['_method'])) {
        $method = strtoupper($input['_method']);
    }
}

// -------------------------------------------------------------
// GET: Mengambil Data Karyawan (List / Detail)
// -------------------------------------------------------------
if ($method === 'GET') {
    $search = trim($_GET['q'] ?? $_GET['search'] ?? '');
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 100) : 10;
    $offset = ($page - 1) * $limit;

    $whereSql = " WHERE 1=1";
    $params = [];
    $types = "";

    if (!empty($search)) {
        $whereSql .= " AND (k.nama_karyawan LIKE ? OR k.kode_karyawan LIKE ? OR d.nama_divisi LIKE ? OR k.email LIKE ? OR k.no_handphone LIKE ?)";
        $searchWildcard = "%" . $search . "%";
        for ($i = 0; $i < 5; $i++) {
            $params[] = $searchWildcard;
            $types .= "s";
        }
    }

    $countSql = "SELECT COUNT(*) as total FROM karyawan k LEFT JOIN divisi d ON k.id_divisi = d.id_divisi" . $whereSql;
    $stmtCount = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmtCount->bind_param($types, ...$params);
    }
    $stmtCount->execute();
    $totalRecords = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
    $stmtCount->close();

    $totalPages = $totalRecords > 0 ? (int)ceil($totalRecords / $limit) : 1;

    $sql = "SELECT k.id_karyawan, k.kode_karyawan, k.nama_karyawan, k.id_divisi, k.tanggal_bergabung, 
                   k.aktif, k.email, k.no_handphone, k.status_karyawan, k.login_web, d.nama_divisi, d.kode_divisi
            FROM karyawan k
            LEFT JOIN divisi d ON k.id_divisi = d.id_divisi"
            . $whereSql . " ORDER BY k.id_karyawan DESC LIMIT ? OFFSET ?";

    $paramsWithLimit = $params;
    $typesWithLimit = $types . "ii";
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    $stmt->execute();
    $res = $stmt->get_result();

    $statusKaryawanMap = [
        1 => 'Magang (Internship)',
        2 => 'PKWT (Kontrak Waktu Tertentu)',
        3 => 'PKWTT (Karyawan Tetap)',
        4 => 'Pekerja Paruh Waktu (Part-time)',
        5 => 'Harian Lepas (Casual Workers)',
        6 => 'Freelance / Pekerja Lepas',
        7 => 'Outsourcing / Alih Daya',
        8 => 'Volunteer / Sukarelawan'
    ];

    $items = [];
    while ($row = $res->fetch_assoc()) {
        $statusId = (int)($row['status_karyawan'] ?? 3);
        $items[] = [
            'id_karyawan' => (int)$row['id_karyawan'],
            'kode_karyawan' => $row['kode_karyawan'] ?? '',
            'nama_karyawan' => $row['nama_karyawan'] ?? '',
            'id_divisi' => (int)$row['id_divisi'],
            'nama_divisi' => $row['nama_divisi'] ?? '-',
            'kode_divisi' => $row['kode_divisi'] ?? '-',
            'tanggal_bergabung' => $row['tanggal_bergabung'] ? date('d-m-Y H:i', strtotime($row['tanggal_bergabung'])) : '-',
            'email' => $row['email'] ?? '-',
            'no_handphone' => $row['no_handphone'] ?? '-',
            'status_karyawan_id' => $statusId,
            'status_karyawan_label' => $statusKaryawanMap[$statusId] ?? 'PKWTT (Tetap)',
            'login_web' => (int)($row['login_web'] ?? 1),
            'aktif' => (int)($row['aktif'] ?? 1)
        ];
    }
    $stmt->close();

    jsonResponse(true, 'Data karyawan berhasil diambil.', [
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
    jsonResponse(false, 'Forbidden. Hanya Role ADMIN yang dapat mengelola data karyawan.', null, 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

// -------------------------------------------------------------
// POST: Tambah Karyawan Baru
// -------------------------------------------------------------
if ($method === 'POST') {
    $namaKaryawan = trim($input['nama_karyawan'] ?? '');
    $kodeKaryawan = trim($input['kode_karyawan'] ?? '');
    $idDivisi = isset($input['id_divisi']) ? (int)$input['id_divisi'] : 1;
    $email = trim($input['email'] ?? '');
    $noHp = trim($input['no_handphone'] ?? '');
    $password = trim($input['password'] ?? '123456');
    $statusKaryawan = isset($input['status_karyawan']) ? (int)$input['status_karyawan'] : 3;
    $loginWeb = isset($input['login_web']) ? (int)$input['login_web'] : 1;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if (empty($namaKaryawan)) {
        jsonResponse(false, 'Nama karyawan wajib diisi.', null, 422);
    }

    if (empty($kodeKaryawan)) {
        $resCount = $conn->query("SELECT MAX(id_karyawan) as max_id FROM karyawan");
        $nextId = ((int)($resCount->fetch_assoc()['max_id'] ?? 0)) + 1;
        $kodeKaryawan = 'KRY' . str_pad($nextId, 3, '0', STR_PAD_LEFT);
    }

    $passHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO karyawan (kode_karyawan, nama_karyawan, id_divisi, tanggal_bergabung, aktif, email, no_handphone, password, status_karyawan, login_web) 
                            VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssiisssii", $kodeKaryawan, $namaKaryawan, $idDivisi, $aktif, $email, $noHp, $passHash, $statusKaryawan, $loginWeb);
    
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();

        // Buat user login jika email terisi dan login_web aktif
        if (!empty($email) && $loginWeb) {
            $stmtU = $conn->prepare("INSERT IGNORE INTO users (nama_users, email, password, aktif) VALUES (?, ?, ?, ?)");
            $stmtU->bind_param("sssi", $namaKaryawan, $email, $passHash, $aktif);
            $stmtU->execute();
            $stmtU->close();
        }

        jsonResponse(true, 'Karyawan berhasil ditambahkan.', ['id_karyawan' => $newId], 201);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menambahkan karyawan.', null, 500);
    }
}

// -------------------------------------------------------------
// PUT: Update Data Karyawan
// -------------------------------------------------------------
if ($method === 'PUT') {
    $idKaryawan = isset($input['id_karyawan']) ? (int)$input['id_karyawan'] : 0;
    $namaKaryawan = trim($input['nama_karyawan'] ?? '');
    $kodeKaryawan = trim($input['kode_karyawan'] ?? '');
    $idDivisi = isset($input['id_divisi']) ? (int)$input['id_divisi'] : 1;
    $email = trim($input['email'] ?? '');
    $noHp = trim($input['no_handphone'] ?? '');
    $statusKaryawan = isset($input['status_karyawan']) ? (int)$input['status_karyawan'] : 3;
    $loginWeb = isset($input['login_web']) ? (int)$input['login_web'] : 1;
    $aktif = isset($input['aktif']) ? (int)$input['aktif'] : 1;

    if ($idKaryawan <= 0 || empty($namaKaryawan)) {
        jsonResponse(false, 'ID karyawan dan nama karyawan wajib diisi.', null, 422);
    }

    $stmt = $conn->prepare("UPDATE karyawan SET kode_karyawan = ?, nama_karyawan = ?, id_divisi = ?, email = ?, 
                            no_handphone = ?, status_karyawan = ?, login_web = ?, aktif = ? 
                            WHERE id_karyawan = ?");
    $stmt->bind_param("ssisssiii", $kodeKaryawan, $namaKaryawan, $idDivisi, $email, $noHp, $statusKaryawan, $loginWeb, $aktif, $idKaryawan);
    
    if ($stmt->execute()) {
        $stmt->close();
        
        // Update users table jika ada password baru atau status aktif
        if (!empty($input['password'])) {
            $newPassHash = password_hash(trim($input['password']), PASSWORD_DEFAULT);
            $upU = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upU->bind_param("ss", $newPassHash, $email);
            $upU->execute();
            $upU->close();
        }

        jsonResponse(true, 'Data karyawan berhasil diperbarui.', ['id_karyawan' => $idKaryawan], 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal memperbarui data karyawan.', null, 500);
    }
}

// -------------------------------------------------------------
// DELETE: Hapus Data Karyawan
// -------------------------------------------------------------
if ($method === 'DELETE') {
    $idKaryawan = isset($input['id_karyawan']) ? (int)$input['id_karyawan'] : (int)($_GET['id'] ?? 0);

    if ($idKaryawan <= 0) {
        jsonResponse(false, 'ID karyawan tidak valid.', null, 422);
    }

    $stmt = $conn->prepare("DELETE FROM karyawan WHERE id_karyawan = ?");
    $stmt->bind_param("i", $idKaryawan);
    
    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(true, 'Karyawan berhasil dihapus.', null, 200);
    } else {
        $stmt->close();
        jsonResponse(false, 'Gagal menghapus karyawan. Data mungkin terkait dengan transaksi RO.', null, 500);
    }
}

jsonResponse(false, 'Metode HTTP tidak didukung.', null, 405);
