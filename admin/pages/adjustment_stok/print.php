<?php
/**
 * Cetak Berita Acara Penyesuaian Stok (Stock Adjustment)
 * Path: admin/pages/adjustment_stok/print.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MEKANIK, ROLE_MANAGER]);

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('ID Adjustment tidak valid.');
}

// Query Header
$stmt = $conn->prepare("
    SELECT 
        a.id_adjustment,
        a.nomor_adjustment,
        a.tanggal_adjustment,
        a.jenis_adjustment,
        a.alasan,
        a.keterangan,
        a.status,
        a.tanggal_approved,
        s.nama_site,
        s.kode_site AS inisial_site,
        k_created.nama_lengkap AS pembuat_nama,
        k_created.username AS pembuat_user,
        j_created.nama_jabatan AS pembuat_jabatan,
        k_app.nama_lengkap AS approver_nama,
        k_app.username AS approver_user,
        j_app.nama_jabatan AS approver_jabatan
    FROM adjustment_stok a
    LEFT JOIN site s ON a.id_site = s.id_site
    LEFT JOIN karyawan k_created ON a.id_karyawan = k_created.id_karyawan
    LEFT JOIN jabatan j_created ON k_created.id_jabatan = j_created.id_jabatan
    LEFT JOIN karyawan k_app ON a.id_karyawan_approved = k_app.id_karyawan
    LEFT JOIN jabatan j_app ON k_app.id_jabatan = j_app.id_jabatan
    WHERE a.id_adjustment = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$header = $res->fetch_assoc();
$stmt->close();

if (!$header) {
    die('Data Adjustment tidak ditemukan.');
}

// Query Detail
$stmtDtl = $conn->prepare("
    SELECT 
        d.id_adjustment_detail,
        d.id_barang,
        d.qty_sistem,
        d.qty_fisik,
        d.qty_adjustment,
        d.qty_akhir,
        d.keterangan,
        d.harga_satuan,
        d.subtotal_adjustment,
        b.kode_barang,
        b.nama_barang,
        b.satuan,
        kb.nama_kategori,
        mb.nama_merk
    FROM adjustment_stok_detail d
    JOIN barang b ON d.id_barang = b.id_barang
    LEFT JOIN kategori_barang kb ON b.id_kategori = kb.id_kategori
    LEFT JOIN merk_barang mb ON b.id_merk = mb.id_merk
    WHERE d.id_adjustment = ?
    ORDER BY d.id_adjustment_detail ASC
");
$stmtDtl->bind_param("i", $id);
$stmtDtl->execute();
$resDtl = $stmtDtl->get_result();
$items = [];
$totalNilaiSelisih = 0;
while ($r = $resDtl->fetch_assoc()) {
    $items[] = $r;
    $totalNilaiSelisih += (float)$r['subtotal_adjustment'];
}
$stmtDtl->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Berita Acara Stock Adjustment - <?= htmlspecialchars($header['nomor_adjustment']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #111;
            margin: 20px;
            line-height: 1.4;
        }
        .header-box {
            text-align: center;
            border-bottom: 2px solid #222;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        .header-box h2 {
            margin: 0 0 4px 0;
            font-size: 16px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .header-box p {
            margin: 0;
            font-size: 11px;
            color: #555;
        }
        .info-table {
            width: 100%;
            margin-bottom: 15px;
            font-size: 12px;
        }
        .info-table td {
            padding: 3px 6px;
            vertical-align: top;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .table-data th, .table-data td {
            border: 1px solid #444;
            padding: 5px 6px;
        }
        .table-data th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .font-monospace { font-family: 'Courier New', monospace; }
        .fw-bold { font-weight: bold; }
        
        .signature-table {
            width: 100%;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
        }
        .sign-space {
            height: 65px;
        }

        .status-stamp {
            display: inline-block;
            padding: 3px 8px;
            font-weight: bold;
            border-radius: 3px;
            border: 1px solid;
            font-size: 11px;
        }
        .status-APPROVED { border-color: #198754; color: #198754; }
        .status-PENDING { border-color: #ffc107; color: #b58105; }
        .status-DRAFT { border-color: #6c757d; color: #6c757d; }
        .status-REJECTED { border-color: #dc3545; color: #dc3545; }
        .status-BATAL { border-color: #dc3545; color: #dc3545; }

        @media print {
            .no-print { display: none !important; }
            body { margin: 10mm 15mm; }
        }
    </style>
</head>
<body>

    <!-- Tombol Cetak -->
    <div class="no-print" style="margin-bottom: 20px; text-align: right;">
        <button onclick="window.print()" style="padding: 6px 16px; background-color: #0d6efd; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: bold;">
            Cetak Dokumen
        </button>
        <button onclick="window.close()" style="padding: 6px 14px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; margin-left: 5px;">
            Tutup
        </button>
    </div>

    <!-- Header Perusahaan -->
    <div class="header-box">
        <h2>BERITA ACARA PENYESUAIAN STOK (STOCK ADJUSTMENT)</h2>
        <p>PT JAYA TEKNIK UTAMA - SISTEM MANAJEMEN LOGISTIK &amp; PERSEDIAAN</p>
    </div>

    <!-- Metadata Informasi Dokumen -->
    <table class="info-table">
        <tr>
            <td style="width: 18%;" class="fw-bold">No. Transaksi</td>
            <td style="width: 2%;">:</td>
            <td style="width: 35%;" class="fw-bold font-monospace"><?= htmlspecialchars($header['nomor_adjustment']) ?></td>
            <td style="width: 18%;" class="fw-bold">Lokasi Site / Gudang</td>
            <td style="width: 2%;">:</td>
            <td style="width: 25%;"><?= htmlspecialchars($header['nama_site']) ?> (<?= htmlspecialchars($header['inisial_site'] ?: '-') ?>)</td>
        </tr>
        <tr>
            <td class="fw-bold">Tanggal Penyesuaian</td>
            <td>:</td>
            <td><?= date('d/m/Y H:i', strtotime($header['tanggal_adjustment'])) ?></td>
            <td class="fw-bold">Jenis Penyesuaian</td>
            <td>:</td>
            <td class="fw-bold"><?= htmlspecialchars($header['jenis_adjustment']) ?></td>
        </tr>
        <tr>
            <td class="fw-bold">Alasan Penyesuaian</td>
            <td>:</td>
            <td><?= htmlspecialchars($header['alasan']) ?></td>
            <td class="fw-bold">Status Dokumen</td>
            <td>:</td>
            <td>
                <span class="status-stamp status-<?= $header['status'] ?>"><?= $header['status'] ?></span>
            </td>
        </tr>
        <?php if (!empty($header['keterangan'])): ?>
        <tr>
            <td class="fw-bold">Keterangan</td>
            <td>:</td>
            <td colspan="4"><?= nl2br(htmlspecialchars($header['keterangan'])) ?></td>
        </tr>
        <?php endif; ?>
    </table>

    <!-- Tabel Rincian Barang -->
    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 30px;">No</th>
                <th style="width: 90px;">Kode</th>
                <th>Nama Barang / Deskripsi</th>
                <th style="width: 60px;">Satuan</th>
                <th style="width: 70px;">Stok Sistem</th>
                <th style="width: 70px;">Qty Fisik</th>
                <th style="width: 75px;">Selisih (+/-)</th>
                <th style="width: 70px;">Stok Akhir</th>
                <th style="width: 95px;">Estimasi Harga</th>
                <th style="width: 105px;">Subtotal Selisih</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="10" class="text-center" style="padding: 15px;">Tidak ada rincian barang.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $item): 
                    $adj = (float)$item['qty_adjustment'];
                    $adjDisplay = ($adj > 0 ? '+' : '') . number_format($adj, 0, ',', '.');
                ?>
                <tr>
                    <td class="text-center"><?= $idx + 1 ?></td>
                    <td class="font-monospace text-center"><?= htmlspecialchars($item['kode_barang']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($item['nama_barang']) ?></strong>
                        <?php if (!empty($item['keterangan'])): ?>
                            <br><small style="color:#555;">Catatan: <?= htmlspecialchars($item['keterangan']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= htmlspecialchars($item['satuan']) ?></td>
                    <td class="text-end font-monospace"><?= number_format($item['qty_sistem'], 0, ',', '.') ?></td>
                    <td class="text-end font-monospace"><?= number_format($item['qty_fisik'], 0, ',', '.') ?></td>
                    <td class="text-end font-monospace fw-bold"><?= $adjDisplay ?></td>
                    <td class="text-end font-monospace"><?= number_format($item['qty_akhir'], 0, ',', '.') ?></td>
                    <td class="text-end font-monospace">Rp <?= number_format($item['harga_satuan'], 0, ',', '.') ?></td>
                    <td class="text-end font-monospace">Rp <?= number_format($item['subtotal_adjustment'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold" style="background-color: #f9f9f9;">
                <td colspan="9" class="text-end" style="padding: 6px 10px;">TOTAL NILAI PENYESUAIAN:</td>
                <td class="text-end font-monospace" style="padding: 6px 10px;">Rp <?= number_format($totalNilaiSelisih, 0, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Tanda Tangan Pengesahan -->
    <table class="signature-table">
        <tr>
            <td>
                <div>Diajukan Oleh,</div>
                <div style="font-size: 11px; color: #555;">(Mekanik / Petugas Input)</div>
                <div class="sign-space"></div>
                <div class="fw-bold" style="text-decoration: underline;">
                    <?= htmlspecialchars($header['pembuat_nama'] ?: $header['pembuat_user'] ?: 'Petugas') ?>
                </div>
                <div style="font-size: 11px; color: #555;"><?= htmlspecialchars($header['pembuat_jabatan'] ?: 'Staff Mekanik') ?></div>
            </td>
            <td>
                <div>Diperiksa / Saksi,</div>
                <div style="font-size: 11px; color: #555;">(Supervisor / Kepala Mekanik)</div>
                <div class="sign-space"></div>
                <div class="fw-bold" style="text-decoration: underline;">( ..................................... )</div>
                <div style="font-size: 11px; color: #555;">Supervisor / Site Leader</div>
            </td>
            <td>
                <div>Disetujui Oleh,</div>
                <div style="font-size: 11px; color: #555;">(Kepala / Admin Logistik)</div>
                <div class="sign-space"></div>
                <div class="fw-bold" style="text-decoration: underline;">
                    <?= htmlspecialchars($header['approver_nama'] ?: $header['approver_user'] ?: '( Belum Disetujui )') ?>
                </div>
                <div style="font-size: 11px; color: #555;">
                    <?= htmlspecialchars($header['approver_jabatan'] ?: 'Bagian Logistik & Persediaan') ?>
                    <?php if ($header['tanggal_approved']): ?>
                        <br><small><?= date('d/m/Y H:i', strtotime($header['tanggal_approved'])) ?></small>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
