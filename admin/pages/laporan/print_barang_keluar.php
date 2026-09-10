<?php
/**
 * Cetak Laporan Rekapitulasi Barang Keluar (Mutasi Site)
 * Path: admin/pages/laporan/print_barang_keluar.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER, ROLE_PURCHASING]);

$search = trim($_GET['search'] ?? $_GET['q'] ?? '');
$idSiteAsal = isset($_GET['id_site']) && is_numeric($_GET['id_site']) ? (int)$_GET['id_site'] : 0;
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$where = ["mo.status NOT IN ('BATAL', 'DRAFT')"];
$params = [];
$types = "";

$namaFilterSite = 'Semua Site Asal (Pengirim)';
if ($idSiteAsal > 0) {
    $where[] = "mo.id_site_asal = ?";
    $params[] = $idSiteAsal;
    $types .= "i";

    $sQ = $conn->query("SELECT nama_site FROM site WHERE id_site = $idSiteAsal");
    if ($sRow = $sQ->fetch_assoc()) {
        $namaFilterSite = $sRow['nama_site'];
    }
}

if (!empty($startDate)) {
    $where[] = "DATE(mo.tanggal_mutasi) >= ?";
    $params[] = $startDate;
    $types .= "s";
}

if (!empty($endDate)) {
    $where[] = "DATE(mo.tanggal_mutasi) <= ?";
    $params[] = $endDate;
    $types .= "s";
}

if (!empty($search)) {
    $where[] = "(
        b.kode_barang LIKE ? OR 
        b.nama_barang LIKE ? OR 
        b.serial_number LIKE ? OR
        mo.kode_mutasi LIKE ? OR 
        mo.nomor_surat_mutasi LIKE ? OR 
        sa.nama_site LIKE ? OR 
        st.nama_site LIKE ? OR 
        kr.nama_karyawan LIKE ?
    )";
    $wildcard = "%$search%";
    for ($i = 0; $i < 8; $i++) {
        $params[] = $wildcard;
        $types .= "s";
    }
}

$whereSql = implode(" AND ", $where);

$sql = "
    SELECT 
        modt.id_mutasi_detail,
        modt.id_mutasi,
        modt.id_barang,
        modt.qty,
        b.kode_barang,
        b.nama_barang,
        b.satuan,
        b.serial_number,
        b.deskripsi,
        mo.kode_mutasi,
        mo.nomor_surat_mutasi,
        mo.tanggal_mutasi,
        mo.status AS status_mutasi,
        sa.nama_site AS nama_site_asal,
        st.nama_site AS nama_site_tujuan,
        kr.nama_karyawan AS nama_pemohon
    FROM mutasi_order_detail modt
    INNER JOIN mutasi_order mo ON modt.id_mutasi = mo.id_mutasi
    INNER JOIN barang b ON modt.id_barang = b.id_barang
    LEFT JOIN site sa ON mo.id_site_asal = sa.id_site
    LEFT JOIN site st ON mo.id_site_tujuan = st.id_site
    LEFT JOIN karyawan kr ON mo.id_karyawan_request = kr.id_karyawan
    WHERE $whereSql
    ORDER BY mo.tanggal_mutasi DESC, modt.id_mutasi_detail DESC
";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$items = [];
$totalQty = 0;
while ($row = $res->fetch_assoc()) {
    $totalQty += (float)$row['qty'];
    $items[] = $row;
}
$stmt->close();

$comp = getCompanyProfile();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Barang Keluar - <?= htmlspecialchars($comp['nama'] ?: 'PT JAYA TEKNIK') ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles/print_document.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; font-size: 10pt; }
        }
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
        }
        .sheet {
            background: #fff;
            max-width: 950px;
            margin: 20px auto;
            padding: 30px 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header-title {
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #212529;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 9pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #333;
            padding: 5px 7px;
        }
        .data-table th {
            background-color: #f0f0f0 !important;
            font-weight: bold;
            text-align: center;
        }
        .sign-table {
            width: 100%;
            margin-top: 35px;
            border-collapse: collapse;
            text-align: center;
            font-size: 9.5pt;
        }
        .sign-table td {
            width: 50%;
            padding: 5px;
            vertical-align: top;
        }
        .sign-space {
            height: 65px;
        }
    </style>
</head>
<body>

<div class="no-print text-center py-3">
    <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm fw-semibold">
        <i class="bi bi-printer-fill"></i> Cetak Laporan
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary px-3 ms-2">
        Tutup
    </button>
</div>

<div class="sheet">
    <!-- KOP PERUSAHAAN -->
    <div class="row align-items-center mb-3">
        <div class="col-8">
            <h4 class="fw-bold mb-0 text-uppercase"><?= htmlspecialchars($comp['nama'] ?: 'PT JAYA TEKNIK') ?></h4>
            <div class="small text-muted"><?= htmlspecialchars($comp['alamat'] ?: 'Jl. Pelabuhan & Workshop Galangan Kapal') ?></div>
            <div class="small text-muted">Telp: <?= htmlspecialchars($comp['telepon'] ?: '-') ?> | Email: <?= htmlspecialchars($comp['email'] ?: '-') ?></div>
        </div>
        <div class="col-4 text-end">
            <div class="small text-muted">Tanggal Cetak:</div>
            <div class="fw-bold text-dark font-monospace"><?= date('d-m-Y H:i') ?> WITA</div>
        </div>
    </div>

    <!-- JUDUL LAPORAN -->
    <div class="text-center header-title">
        LAPORAN REKAPITULASI BARANG KELUAR (MUTASI SITE)
    </div>

    <!-- FILTER INFO -->
    <div class="row mb-2" style="font-size: 9.5pt;">
        <div class="col-6">
            <div><strong>Lokasi Site Asal:</strong> <?= htmlspecialchars($namaFilterSite) ?></div>
        </div>
        <div class="col-6 text-end">
            <div><strong>Periode Transaksi:</strong> <?= $startDate ? date('d/m/Y', strtotime($startDate)) : 'Awal' ?> s/d <?= $endDate ? date('d/m/Y', strtotime($endDate)) : 'Sekarang' ?></div>
        </div>
    </div>

    <!-- TABEL DATA -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 75px;">Tanggal</th>
                <th style="width: 100px;">Kode Mutasi</th>
                <th style="width: 100px;">Kode Barang</th>
                <th>Nama Barang / Material</th>
                <th style="width: 140px;">Rute Pengiriman</th>
                <th style="width: 80px;">Qty Keluar</th>
                <th style="width: 50px;">Satuan</th>
                <th style="width: 100px;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td class="text-center"><?= $it['tanggal_mutasi'] ? date('d/m/Y', strtotime($it['tanggal_mutasi'])) : '-' ?></td>
                        <td class="font-monospace fw-bold"><?= htmlspecialchars($it['kode_mutasi']) ?></td>
                        <td class="font-monospace"><?= htmlspecialchars($it['kode_barang']) ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($it['nama_barang']) ?></div>
                            <?php if (!empty($it['serial_number'])): ?>
                                <div class="text-muted small">SN: <?= htmlspecialchars($it['serial_number']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($it['nama_site_asal']) ?> &rarr; <?= htmlspecialchars($it['nama_site_tujuan']) ?>
                        </td>
                        <td class="text-end font-monospace fw-bold"><?= number_format($it['qty'], 0, ',', '.') ?></td>
                        <td class="text-center"><?= htmlspecialchars($it['satuan'] ?: 'PCS') ?></td>
                        <td class="text-center small"><?= htmlspecialchars($it['status_mutasi']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" class="text-center py-3 text-muted">Tidak ada data barang keluar yang sesuai periode/filter.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #fafafa;">
                <td colspan="6" class="text-end">TOTAL KUANTITAS KELUAR:</td>
                <td class="text-end font-monospace"><?= number_format($totalQty, 0, ',', '.') ?></td>
                <td colspan="2" class="text-center">ITEM KELUAR</td>
            </tr>
        </tfoot>
    </table>

    <!-- TANDA TANGAN -->
    <table class="sign-table">
        <tr>
            <td>
                <div>Dibuat Oleh,</div>
                <div class="small text-muted">Bagian Logistik &amp; Gudang</div>
                <div class="sign-space"></div>
                <div class="fw-bold text-decoration-underline"><?= htmlspecialchars($user['nama'] ?: 'Petugas Logistik') ?></div>
                <div class="small text-muted">Staff Logistik</div>
            </td>
            <td>
                <div>Mengetahui,</div>
                <div class="small text-muted">Manager Operasional / Direktur</div>
                <div class="sign-space"></div>
                <div class="fw-bold text-decoration-underline">......................................................</div>
                <div class="small text-muted">Manager</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
