<?php
/**
 * Cetak Surat Jalan / Surat Mutasi Barang Antar-Site
 * Path: admin/pages/mutasi_barang/print_surat.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MANAGER, ROLE_PURCHASING]);

$idMutasi = isset($_GET['id_mutasi']) && is_numeric($_GET['id_mutasi']) ? (int)$_GET['id_mutasi'] : 0;
if ($idMutasi <= 0) {
    die("ID Mutasi tidak valid.");
}

// 1. Ambil Data Header Mutasi
$sql = "
    SELECT 
        mo.*,
        sa.nama_site AS nama_site_asal,
        sa.kode_site AS kode_site_asal,
        sa.jenis_site AS jenis_site_asal,
        st.nama_site AS nama_site_tujuan,
        st.kode_site AS kode_site_tujuan,
        st.jenis_site AS jenis_site_tujuan,
        kp.nama_karyawan AS nama_pembuat,
        kr.nama_karyawan AS nama_pemohon,
        jr.nama_jabatan AS jabatan_pemohon,
        ka.nama_karyawan AS nama_penyetuju,
        ja.nama_jabatan AS jabatan_penyetuju
    FROM mutasi_order mo
    LEFT JOIN site sa ON mo.id_site_asal = sa.id_site
    LEFT JOIN site st ON mo.id_site_tujuan = st.id_site
    LEFT JOIN karyawan kp ON mo.id_karyawan = kp.id_karyawan
    LEFT JOIN karyawan kr ON mo.id_karyawan_request = kr.id_karyawan
    LEFT JOIN jabatan jr ON kr.id_jabatan = jr.id_jabatan
    LEFT JOIN karyawan ka ON mo.id_karyawan_approved = ka.id_karyawan
    LEFT JOIN jabatan ja ON ka.id_jabatan = ja.id_jabatan
    WHERE mo.id_mutasi = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $idMutasi);
$stmt->execute();
$header = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$header) {
    die("Data mutasi tidak ditemukan.");
}

// 2. Ambil Data Items
$sqlItems = "
    SELECT 
        modt.*,
        b.kode_barang,
        b.nama_barang,
        b.satuan,
        b.serial_number,
        b.deskripsi
    FROM mutasi_order_detail modt
    INNER JOIN barang b ON modt.id_barang = b.id_barang
    WHERE modt.id_mutasi = ?
    ORDER BY modt.id_mutasi_detail ASC
";
$stmtIt = $conn->prepare($sqlItems);
$stmtIt->bind_param('i', $idMutasi);
$stmtIt->execute();
$resItems = $stmtIt->get_result();
$items = [];
$totalQty = 0;
while ($it = $resItems->fetch_assoc()) {
    $totalQty += (float)$it['qty'];
    $items[] = $it;
}
$stmtIt->close();

// 3. Profil Perusahaan
$comp = getCompanyProfile();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Mutasi Barang - <?= htmlspecialchars($header['kode_mutasi']) ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/admin/styles/print_document.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; font-size: 11pt; }
        }
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
        }
        .sheet {
            background: #fff;
            max-width: 850px;
            margin: 20px auto;
            padding: 30px 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header-title {
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #212529;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .meta-table td {
            padding: 3px 6px;
            font-size: 10pt;
            vertical-align: top;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 10pt;
        }
        .data-table th, .data-table td {
            border: 1px solid #333;
            padding: 6px 8px;
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
            font-size: 10pt;
        }
        .sign-table td {
            width: 25%;
            padding: 5px;
            vertical-align: top;
        }
        .sign-space {
            height: 70px;
        }
    </style>
</head>
<body>

<div class="no-print text-center py-3">
    <button onclick="window.print()" class="btn btn-primary px-4 shadow-sm fw-semibold">
        <i class="bi bi-printer-fill"></i> Cetak Surat Jalan / Mutasi
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
            <div class="font-monospace fw-bold fs-6 text-primary"><?= htmlspecialchars($header['kode_mutasi']) ?></div>
            <?php if (!empty($header['nomor_surat_mutasi'])): ?>
                <div class="small text-muted font-monospace">No. Surat: <?= htmlspecialchars($header['nomor_surat_mutasi']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- JUDUL SURAT -->
    <div class="text-center header-title">
        SURAT JALAN &amp; MUTASI BARANG ANTAR-SITE
    </div>

    <!-- META DOKUMEN -->
    <div class="row mb-3">
        <div class="col-6">
            <table class="meta-table w-100">
                <tr>
                    <td style="width: 130px;" class="fw-semibold">Tanggal Mutasi</td>
                    <td style="width: 10px;">:</td>
                    <td><?= $header['tanggal_mutasi'] ? date('d-m-Y H:i', strtotime($header['tanggal_mutasi'])) : '-' ?> WITA</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Site Asal (Pengirim)</td>
                    <td>:</td>
                    <td><strong><?= htmlspecialchars($header['nama_site_asal']) ?></strong> (<?= htmlspecialchars($header['jenis_site_asal'] ?: 'Site') ?>)</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Site Tujuan (Penerima)</td>
                    <td>:</td>
                    <td><strong><?= htmlspecialchars($header['nama_site_tujuan']) ?></strong> (<?= htmlspecialchars($header['jenis_site_tujuan'] ?: 'Site') ?>)</td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <table class="meta-table w-100">
                <tr>
                    <td style="width: 140px;" class="fw-semibold">Pemohon Transfer</td>
                    <td style="width: 10px;">:</td>
                    <td><?= htmlspecialchars($header['nama_pemohon']) ?> (<?= htmlspecialchars($header['jabatan_pemohon'] ?: 'Staff') ?>)</td>
                </tr>
                <tr>
                    <td class="fw-semibold">Status Dokumen</td>
                    <td>:</td>
                    <td><span class="badge bg-secondary font-monospace"><?= htmlspecialchars($header['status']) ?></span></td>
                </tr>
                <tr>
                    <td class="fw-semibold">Petugas Logistik</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($header['nama_pembuat'] ?: '-') ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL RINCIAN BARANG -->
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 120px;">Kode Barang</th>
                <th>Nama Barang / Material</th>
                <th style="width: 120px;">Serial Number</th>
                <th style="width: 90px;">Jumlah</th>
                <th style="width: 70px;">Satuan</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td class="font-monospace fw-semibold"><?= htmlspecialchars($it['kode_barang']) ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($it['nama_barang']) ?></div>
                            <?php if (!empty($it['deskripsi'])): ?>
                                <div class="text-muted small" style="font-size: 8.5pt;"><?= htmlspecialchars($it['deskripsi']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="font-monospace small"><?= htmlspecialchars($it['serial_number'] ?: '-') ?></td>
                        <td class="text-end font-monospace fw-bold"><?= number_format($it['qty'], 0, ',', '.') ?></td>
                        <td class="text-center"><?= htmlspecialchars($it['satuan'] ?: 'PCS') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center py-3 text-muted">Tidak ada data rincian barang.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #fafafa;">
                <td colspan="4" class="text-end">TOTAL BARANG DIMUTASI:</td>
                <td class="text-end font-monospace"><?= number_format($totalQty, 0, ',', '.') ?></td>
                <td class="text-center">ITEM</td>
            </tr>
        </tfoot>
    </table>

    <!-- KETERANGAN / CATATAN -->
    <?php if (!empty($header['keterangan'])): ?>
        <div class="mt-3 p-2 border bg-light rounded" style="font-size: 9.5pt;">
            <strong>Catatan / Alasan Mutasi:</strong> <?= htmlspecialchars($header['keterangan']) ?>
        </div>
    <?php endif; ?>

    <!-- TANDA TANGAN 4 KOLOM -->
    <table class="sign-table">
        <tr>
            <td>
                <div>Yang Meminta,</div>
                <div class="small text-muted">(Pemohon)</div>
                <div class="sign-space"></div>
                <div class="fw-bold text-decoration-underline"><?= htmlspecialchars($header['nama_pemohon']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($header['jabatan_pemohon'] ?: 'Staff') ?></div>
            </td>
            <td>
                <div>Disetujui Oleh,</div>
                <div class="small text-muted">(Level 1 - Manager/Admin)</div>
                <div class="sign-space"></div>
                <div class="fw-bold text-decoration-underline"><?= htmlspecialchars($header['nama_penyetuju'] ?: '...........................') ?></div>
                <div class="small text-muted"><?= htmlspecialchars($header['jabatan_penyetuju'] ?: 'Manager') ?></div>
            </td>
            <td>
                <div>Pengirim,</div>
                <div class="small text-muted">(Logistik Site Asal)</div>
                <div class="sign-space"></div>
                <div class="fw-bold text-decoration-underline"><?= htmlspecialchars($header['nama_pembuat'] ?: '...........................') ?></div>
                <div class="small text-muted"><?= htmlspecialchars($header['nama_site_asal']) ?></div>
            </td>
            <td>
                <div>Penerima,</div>
                <div class="small text-muted">(Logistik Site Tujuan)</div>
                <div class="sign-space"></div>
                <div class="fw-bold text-decoration-underline">...........................</div>
                <div class="small text-muted"><?= htmlspecialchars($header['nama_site_tujuan']) ?></div>
            </td>
        </tr>
    </table>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    // Otomatis muncul dialog print jika diinginkan
    // window.print();
});
</script>

</body>
</html>
