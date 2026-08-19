<?php
/**
 * Database Seeder Utility - PT Jaya Teknis
 * Mengisi data awal akun (Admin, Mekanik, Logistik, Purchasing, Manager), divisi, site, vendor & barang sample.
 * Aman dijalankan berulang kali (tidak membuat duplikasi data).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/koneksi.php';

header('Content-Type: text/html; charset=utf-8');

$logs = [];

function logMsg($msg, $type = 'info') {
    global $logs;
    $logs[] = ['msg' => $msg, 'type' => $type];
}

try {
    // 1. Seed Divisi
    $divisiList = [
        ['DIV01', 'Manajemen', 1],
        ['DIV02', 'Logistik & Gudang', 2],
        ['DIV03', 'Purchasing & Pengadaan', 2],
        ['DIV04', 'Bengkel Las & Bubut (Mekanik)', 3],
        ['DIV05', 'IT & Administrasi', 2]
    ];

    foreach ($divisiList as $div) {
        $check = $conn->prepare("SELECT id_divisi FROM divisi WHERE kode_divisi = ? OR nama_divisi = ?");
        $check->bind_param("ss", $div[0], $div[1]);
        $check->execute();
        $res = $check->get_result();
        if ($res->num_rows == 0) {
            $ins = $conn->prepare("INSERT INTO divisi (kode_divisi, nama_divisi, level) VALUES (?, ?, ?)");
            $ins->bind_param("ssi", $div[0], $div[1], $div[2]);
            $ins->execute();
            $ins->close();
            logMsg("Divisi ditambahkan: {$div[1]}", 'success');
        }
        $check->close();
    }

    // Ambil ID Divisi
    $divMap = [];
    $resDiv = $conn->query("SELECT id_divisi, nama_divisi FROM divisi");
    while ($r = $resDiv->fetch_assoc()) {
        $divMap[$r['nama_divisi']] = $r['id_divisi'];
    }

    // 2. Seed Karyawan & User Akun
    $usersData = [
        [
            'kode' => 'KRY001',
            'nama' => 'Bambang Admin',
            'divisi' => 'IT & Administrasi',
            'email' => 'admin@jayateknis.com',
            'hp' => '081234567890',
            'username' => 'admin',
            'password' => 'admin123',
            'role' => 'ADMIN'
        ],
        [
            'kode' => 'KRY002',
            'nama' => 'Budi Mekanik',
            'divisi' => 'Bengkel Las & Bubut (Mekanik)',
            'email' => 'mekanik@jayateknis.com',
            'hp' => '081234567891',
            'username' => 'mekanik',
            'password' => 'mekanik123',
            'role' => 'MEKANIK'
        ],
        [
            'kode' => 'KRY003',
            'nama' => 'Agus Logistik',
            'divisi' => 'Logistik & Gudang',
            'email' => 'logistik@jayateknis.com',
            'hp' => '081234567892',
            'username' => 'logistik',
            'password' => 'logistik123',
            'role' => 'LOGISTIK'
        ],
        [
            'kode' => 'KRY004',
            'nama' => 'Siti Purchasing',
            'divisi' => 'Purchasing & Pengadaan',
            'email' => 'purchasing@jayateknis.com',
            'hp' => '081234567893',
            'username' => 'purchasing',
            'password' => 'purchasing123',
            'role' => 'PURCHASING'
        ],
        [
            'kode' => 'KRY005',
            'nama' => 'Hendra Manager',
            'divisi' => 'Manajemen',
            'email' => 'manager@jayateknis.com',
            'hp' => '081234567894',
            'username' => 'manager',
            'password' => 'manager123',
            'role' => 'MANAGER'
        ]
    ];

    $karyawanMap = [];
    foreach ($usersData as $u) {
        $passHash = password_hash($u['password'], PASSWORD_DEFAULT);
        $idDiv = $divMap[$u['divisi']] ?? 1;

        // Cek / Insert Karyawan
        $checkKry = $conn->prepare("SELECT id_karyawan FROM karyawan WHERE email = ? OR nama_karyawan = ?");
        $checkKry->bind_param("ss", $u['email'], $u['nama']);
        $checkKry->execute();
        $resKry = $checkKry->get_result();
        
        $idKaryawan = 0;
        if ($resKry->num_rows == 0) {
            $insKry = $conn->prepare("INSERT INTO karyawan (kode_karyawan, nama_karyawan, id_divisi, tanggal_bergabung, aktif, email, no_handphone, password, status_karyawan, login_web) 
                                      VALUES (?, ?, ?, NOW(), 1, ?, ?, ?, 3, 1)");
            $insKry->bind_param("ssisss", $u['kode'], $u['nama'], $idDiv, $u['email'], $u['hp'], $passHash);
            $insKry->execute();
            $idKaryawan = $conn->insert_id;
            $insKry->close();
            logMsg("Karyawan dibuat: {$u['nama']} ({$u['role']})", 'success');
        } else {
            $rowK = $resKry->fetch_assoc();
            $idKaryawan = $rowK['id_karyawan'];
            // Update password hash jika perlu
            $upK = $conn->prepare("UPDATE karyawan SET password = ?, aktif = 1, login_web = 1 WHERE id_karyawan = ?");
            $upK->bind_param("si", $passHash, $idKaryawan);
            $upK->execute();
            $upK->close();
        }
        $checkKry->close();
        $karyawanMap[$u['role']] = $idKaryawan;

        // Cek / Insert Users
        $checkUser = $conn->prepare("SELECT id_users FROM users WHERE email = ? OR nama_users = ?");
        $checkUser->bind_param("ss", $u['email'], $u['username']);
        $checkUser->execute();
        $resU = $checkUser->get_result();
        if ($resU->num_rows == 0) {
            $insU = $conn->prepare("INSERT INTO users (nama_users, email, password, aktif) VALUES (?, ?, ?, 1)");
            $insU->bind_param("sss", $u['username'], $u['email'], $passHash);
            $insU->execute();
            $insU->close();
            logMsg("User akun login dibuat: username '{$u['username']}' / pass '{$u['password']}'", 'success');
        } else {
            $rowU = $resU->fetch_assoc();
            $idU = $rowU['id_users'];
            $upU = $conn->prepare("UPDATE users SET password = ?, aktif = 1 WHERE id_users = ?");
            $upU->bind_param("si", $passHash, $idU);
            $upU->execute();
            $upU->close();
        }
        $checkUser->close();
    }

    // 3. Seed Site / Lokasi Workshop Galangan
    $siteList = [
        ['SIT01', 'Galangan Utama Dok 1', 'Bengkel', 'Jl. Pelabuhan Maritim No. 12, Surabaya', '031-889901'],
        ['SIT02', 'Workshop Bubut & Las Fabrikasi', 'Bengkel', 'Kawasan Industri Gresik Blok B-4', '031-889902'],
        ['SIT03', 'Gudang Logistik & Suku Cadang', 'Logistik', 'Jl. Dermaga Barat No. 8, Surabaya', '031-889903'],
        ['SIT04', 'Kantor Pusat & Operasional', 'Office', 'Jl. Perak Timur No. 100, Surabaya', '031-889900']
    ];

    foreach ($siteList as $s) {
        $checkS = $conn->prepare("SELECT id_site FROM site WHERE kode_site = ? OR nama_site = ?");
        $checkS->bind_param("ss", $s[0], $s[1]);
        $checkS->execute();
        if ($checkS->get_result()->num_rows == 0) {
            $insS = $conn->prepare("INSERT INTO site (kode_site, nama_site, jenis_site, alamat, no_hp) VALUES (?, ?, ?, ?, ?)");
            $insS->bind_param("sssss", $s[0], $s[1], $s[2], $s[3], $s[4]);
            $insS->execute();
            $insS->close();
            logMsg("Site ditambahkan: {$s[1]}", 'success');
        }
        $checkS->close();
    }

    // 4. Seed Vendor / Supplier Rekanan
    $vendorList = [
        ['VND001', 'PT Baja Maritim Nusantara', 'Kawasan Industri Rungkut Surabaya', '031-778811', 'Surabaya', 'Bpk. Gunawan', 'supplier plat baja & profil kapal', 30],
        ['VND002', 'CV Sumber Teknik Las & Gas', 'Jl. Kalianak Barat No. 45, Surabaya', '031-778822', 'Surabaya', 'Ibu Ratna', 'distributor kawat las & perlengkapan welding', 14],
        ['VND003', 'PT Indo Bearing & Seal Sejahtera', 'Jl. Dupak Rukun No. 88, Surabaya', '031-778833', 'Surabaya', 'Bpk. Tony', 'bearing propeller & mechanical seal', 30],
        ['VND004', 'PT Samudera Marine Supply', 'Jl. Tanjung Perak Barat No. 20, Surabaya', '031-778844', 'Surabaya', 'Bpk. Eko', 'sparepart mesin diesel kapal & valve', 45]
    ];

    $vendorMap = [];
    foreach ($vendorList as $v) {
        $checkV = $conn->prepare("SELECT id_vendor FROM vendor WHERE kode_vendor = ? OR nama_perusahaan = ?");
        $checkV->bind_param("ss", $v[0], $v[1]);
        $checkV->execute();
        $resV = $checkV->get_result();
        if ($resV->num_rows == 0) {
            $insV = $conn->prepare("INSERT INTO vendor (kode_vendor, nama_perusahaan, alamat, no_telepon, kota, person, jenis_vendor, aktif, term_of_payment) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
            $insV->bind_param("sssssssi", $v[0], $v[1], $v[2], $v[3], $v[4], $v[5], $v[6], $v[7]);
            $insV->execute();
            $vendorMap[$v[0]] = $conn->insert_id;
            $insV->close();
            logMsg("Vendor ditambahkan: {$v[1]}", 'success');
        } else {
            $rowV = $resV->fetch_assoc();
            $vendorMap[$v[0]] = $rowV['id_vendor'];
        }
        $checkV->close();
    }

    // 5. Seed Barang / Spareparts Kapal
    $defaultKaryawanId = $karyawanMap['ADMIN'] ?? 1;
    $barangList = [
        ['BRG001', 'Kawat Las LB-52 3.2mm Kobe Steel', 'PCS', 'VND002', 'Kawat las elektroda untuk lambung kapal & konstruksi berat'],
        ['BRG002', 'Plat Baja Marine Grade AH36 12mm x 5ft x 20ft', 'UNIT', 'VND001', 'Plat baja standar lambung kapal bersertifikat BKI/LR'],
        ['BRG003', 'Pipa Seamless Carbon Steel Sch 80 4 Inch', 'PCS', 'VND001', 'Pipa jalur bahan bakar & pendingin kapal'],
        ['BRG004', 'Mata Bubut Sandvik Coromant CNMG 120408', 'PCS', 'VND002', 'Insert bubut bubut as propeller dan shaft'],
        ['BRG005', 'Bearing Spherical Roller SKF 22220 EK', 'PCS', 'VND003', 'Bearing poros baling-baling kapal'],
        ['BRG006', 'Bronze Marine Globe Valve 3 Inch Flange PN16', 'PCS', 'VND004', 'Valve laut tahan korosi air asin'],
        ['BRG007', 'Mata Bor HSS Morse Taper 25mm Nachi', 'PCS', 'VND002', 'Mata bor mesin radial drill bengkel bubut'],
        ['BRG008', 'Cat Antifouling Jotun SeaForce 90 20L', 'UNIT', 'VND004', 'Cat bawah lambung kapal pencegah teritip']
    ];

    foreach ($barangList as $b) {
        $checkB = $conn->prepare("SELECT id_barang FROM barang WHERE kode_barang = ? OR nama_barang = ?");
        $checkB->bind_param("ss", $b[0], $b[1]);
        $checkB->execute();
        if ($checkB->get_result()->num_rows == 0) {
            $vendorId = $vendorMap[$b[3]] ?? null;
            $insB = $conn->prepare("INSERT INTO barang (kode_barang, id_merk, id_kategori, default_id_vendor, nama_barang, jenis, satuan, asset, deskripsi, id_karyawan) 
                                   VALUES (?, 1, 1, ?, ?, 1, ?, 0, ?, ?)");
            $insB->bind_param("sisssi", $b[0], $vendorId, $b[1], $b[2], $b[4], $defaultKaryawanId);
            $insB->execute();
            $insB->close();
            logMsg("Barang ditambahkan: {$b[1]}", 'success');
        }
        $checkB->close();
    }

} catch (Exception $e) {
    logMsg("Error seeder: " . $e->getMessage(), 'danger');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder - PT Jaya Teknis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-primary text-white py-3">
                    <h4 class="mb-0 fs-5"><i class="bi bi-database-check me-2"></i>Hasil Setup Data Awal (Database Seeder)</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Inisialisasi data akun, master site, vendor, dan barang selesai dijalankan.</p>
                    
                    <div class="list-group mb-4" style="max-height: 280px; overflow-y: auto;">
                        <?php if (empty($logs)): ?>
                            <div class="list-group-item text-muted">Data sudah ada atau tidak ada perubahan baru.</div>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <div class="list-group-item list-group-item-<?= $l['type'] === 'success' ? 'success' : ($l['type'] === 'danger' ? 'danger' : 'light') ?> py-2 small">
                                    <i class="bi <?= $l['type'] === 'success' ? 'bi-check-circle-fill text-success' : ($l['type'] === 'danger' ? 'bi-x-circle-fill text-danger' : 'bi-info-circle text-primary') ?> me-1"></i>
                                    <?= htmlspecialchars($l['msg']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <h5 class="fs-6 fw-bold mb-3">Daftar Kredensial Login Siap Pakai:</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle text-center small">
                            <thead class="table-light">
                                <tr>
                                    <th>Role</th>
                                    <th>Nama</th>
                                    <th>Username / Email</th>
                                    <th>Password</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-danger">ADMIN</span></td>
                                    <td class="text-start">Bambang Admin</td>
                                    <td><code>admin</code> atau <code>admin@jayateknis.com</code></td>
                                    <td><code>admin123</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-primary">MEKANIK</span></td>
                                    <td class="text-start">Budi Mekanik</td>
                                    <td><code>mekanik</code> atau <code>mekanik@jayateknis.com</code></td>
                                    <td><code>mekanik123</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info text-dark">LOGISTIK</span></td>
                                    <td class="text-start">Agus Logistik</td>
                                    <td><code>logistik</code> atau <code>logistik@jayateknis.com</code></td>
                                    <td><code>logistik123</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success">PURCHASING</span></td>
                                    <td class="text-start">Siti Purchasing</td>
                                    <td><code>purchasing</code> atau <code>purchasing@jayateknis.com</code></td>
                                    <td><code>purchasing123</code></td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-dark">MANAGER</span></td>
                                    <td class="text-start">Hendra Manager</td>
                                    <td><code>manager</code> atau <code>manager@jayateknis.com</code></td>
                                    <td><code>manager123</code></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 text-end">
                        <a href="<?= BASE_URL ?>/admin/login.php" class="btn btn-primary px-4 fw-semibold">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Buka Halaman Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
