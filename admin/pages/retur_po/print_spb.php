<?php
/**
 * Halaman Cetak Surat Penerimaan Barang Pengganti Retur (SPB Retur)
 * Path: admin/pages/retur_po/print_spb.php
 * Format: Siap Print / PDF resmi perusahaan
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$idRetur = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idRetur <= 0) {
    die("ID Dokumen Retur PO tidak valid.");
}

// Ambil Data Header Retur PO
$sql = "SELECT rp.*,
               v.kode_vendor, v.nama_perusahaan as nama_vendor, v.no_telepon as telepon_vendor, v.alamat as alamat_vendor,
               s.nama_site, s.kode_site, s.alamat as alamat_site,
               po.nomor_po, po.tanggal_po,
               rcv.nomor_rcv, rcv.tanggal_rcv, rcv.nomor_sj as nomor_sj_rcv,
               kp.nama_karyawan as nama_pembuat,
               ka.nama_karyawan as nama_penyetuju
        FROM retur_po rp
        LEFT JOIN vendor v ON rp.id_vendor = v.id_vendor
        LEFT JOIN site s ON rp.id_site = s.id_site
        LEFT JOIN purchase_order po ON rp.id_po = po.id_po
        LEFT JOIN receiving_order rcv ON rp.id_rcv = rcv.id_rcv
        LEFT JOIN karyawan kp ON rp.id_karyawan = kp.id_karyawan
        LEFT JOIN karyawan ka ON rp.id_karyawan_approved = ka.id_karyawan
        WHERE rp.id_po_retur = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idRetur);
$stmt->execute();
$retur = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$retur) {
    die("Dokumen Retur PO tidak ditemukan.");
}

// Ambil Data Detail Barang Retur
$sqlItems = "SELECT rpd.*, b.kode_barang, b.nama_barang, b.satuan as satuan_master,
                    kat.nama_kategori, mrk.nama_merk
             FROM retur_po_detail rpd
             LEFT JOIN barang b ON rpd.id_barang = b.id_barang
             LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
             LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
             WHERE rpd.id_po_retur = ?
             ORDER BY rpd.id_po_retur_detail ASC";

$stmtItems = $conn->prepare($sqlItems);
$stmtItems->bind_param("i", $idRetur);
$stmtItems->execute();
$items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();

$autoPrint = isset($_GET['autoprint']) && $_GET['autoprint'] == '1';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPB Retur - <?= htmlspecialchars($retur['nomor_po_retur']) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
            background-color: #f8f9fa;
        }
        .print-container {
            max-width: 900px;
            margin: 20px auto;
            background: #ffffff;
            padding: 35px 40px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
            border-radius: 6px;
        }
        .header-kop {
            border-bottom: 2.5px double #333333;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .company-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #0b4d75;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .company-sub {
            font-size: 0.82rem;
            color: #555;
            line-height: 1.35;
        }
        .doc-title {
            text-align: center;
            font-size: 1.15rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 15px 0 20px 0;
            text-decoration: underline;
            color: #111;
        }
        .info-table td {
            padding: 3px 6px;
            font-size: 0.86rem;
            vertical-align: top;
        }
        .info-table td.label-col {
            font-weight: 600;
            color: #444;
            width: 145px;
        }
        .item-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-top: 15px;
            margin-bottom: 20px;
        }
        .item-table th {
            background-color: #f1f4f8;
            border: 1px solid #c2c9d1;
            padding: 7px 8px;
            font-weight: 700;
            text-align: center;
            font-size: 0.82rem;
        }
        .item-table td {
            border: 1px solid #c2c9d1;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .signature-box {
            margin-top: 35px;
            page-break-inside: avoid;
        }
        .signature-title {
            font-size: 0.84rem;
            font-weight: 600;
            margin-bottom: 60px;
        }
        .signature-line {
            font-size: 0.85rem;
            font-weight: 700;
            border-top: 1px solid #333;
            display: inline-block;
            min-width: 160px;
            padding-top: 4px;
        }
        @media print {
            body {
                background: #ffffff;
                color: #000;
            }
            .print-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- Floating Action Toolbar (Screen Only) -->
    <div class="container print-container no-print mb-2 d-flex justify-content-between align-items-center py-2 px-3 bg-light border rounded">
        <a href="<?= BASE_URL ?>/admin/pages/retur_po/detail.php?id=<?= $idRetur ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail Retur
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm">
                <i class="bi bi-printer-fill me-1"></i> Cetak SPB Retur
            </button>
        </div>
    </div>

    <!-- Official Printable Document -->
    <div class="print-container">
        
        <!-- Header Kop Surat -->
        <div class="header-kop d-flex align-items-center justify-content-between">
            <div>
                <div class="company-title">PT. JAYA TEKNIK INDONESIA</div>
                <div class="company-sub">
                    Sistem Manajemen Logistik &amp; Pengadaan Barang Terpadu<br>
                    Kantor Operasional: Komplek Industri Maritim &amp; Dok Galangan Terpadu<br>
                    Telp: (021) 8899-7766 | Email: logistics@jayateknik.co.id
                </div>
            </div>
            <div class="text-end">
                <div class="border border-dark p-2 rounded text-center" style="min-width: 170px;">
                    <span class="small fw-bold text-muted d-block" style="font-size: 0.72rem;">STATUS DOKUMEN</span>
                    <span class="fw-bold text-success" style="font-size: 0.95rem;">SELESAI / DITERIMA</span>
                </div>
            </div>
        </div>

        <!-- Judul Dokumen -->
        <div class="doc-title">
            SURAT PENERIMAAN BARANG PENGGANTI RETUR (SPB RETUR)
        </div>

        <!-- Metadata Section (2 Kolom) -->
        <div class="row g-3">
            <div class="col-6">
                <table class="info-table w-100">
                    <tr>
                        <td class="label-col">No. SPB Retur</td>
                        <td>: <strong class="font-monospace text-dark">SPB-RET-<?= htmlspecialchars($retur['nomor_po_retur']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label-col">No. Retur PO</td>
                        <td>: <strong class="font-monospace text-primary"><?= htmlspecialchars($retur['nomor_po_retur']) ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label-col">No. SJ Pengembalian</td>
                        <td>: <span class="font-monospace fw-semibold"><?= htmlspecialchars($retur['nomor_sj_retur'] ?: '-') ?></span></td>
                    </tr>
                    <tr>
                        <td class="label-col">Ref. PO Asal</td>
                        <td>: <span class="font-monospace"><?= htmlspecialchars($retur['nomor_po'] ?: '-') ?></span></td>
                    </tr>
                    <tr>
                        <td class="label-col">Ref. RCV Asal</td>
                        <td>: <span class="font-monospace"><?= htmlspecialchars($retur['nomor_rcv'] ?: '-') ?></span></td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <table class="info-table w-100">
                    <tr>
                        <td class="label-col">Tanggal Selesai</td>
                        <td>: <?= date('d/m/Y') ?></td>
                    </tr>
                    <tr>
                        <td class="label-col">Vendor</td>
                        <td>: <strong><?= htmlspecialchars($retur['nama_vendor'] ?: '-') ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label-col">PIC Vendor</td>
                        <td>: <?= htmlspecialchars($retur['pic_vendor'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td class="label-col">Lokasi Site / Gudang</td>
                        <td>: <strong><?= htmlspecialchars($retur['nama_site'] ?: '-') ?></strong></td>
                    </tr>
                    <tr>
                        <td class="label-col">Skema Kompensasi</td>
                        <td>: <?= ((int)$retur['kompensasi'] === 1) ? '<span class="badge bg-primary text-white">Tukar Unit (Ganti Baru)</span>' : '<span class="badge bg-warning text-dark">Potong Tagihan</span>' ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-3 mb-1 small text-muted">
            Telah diterima barang pengganti kondisi baru / bagus dari vendor sesuai rincian klaim retur sebagai berikut:
        </div>

        <!-- Tabel Rincian Barang Pengganti Diterima -->
        <table class="item-table">
            <thead>
                <tr>
                    <th style="width: 35px;">No</th>
                    <th style="width: 120px;">Kode Barang</th>
                    <th>Nama Barang</th>
                    <th style="width: 85px;">KTS Retur</th>
                    <th style="width: 110px;">KTS Diterima</th>
                    <th style="width: 75px;">Satuan</th>
                    <th style="width: 130px;">Kondisi Fisik</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($items)): ?>
                    <?php 
                    $no = 1; 
                    $totalRetur = 0;
                    $totalGanti = 0;
                    foreach ($items as $it): 
                        $qtyGanti = (float)($it['qty_diganti'] > 0 ? $it['qty_diganti'] : $it['qty_retur']);
                        $totalRetur += (float)$it['qty_retur'];
                        $totalGanti += $qtyGanti;
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td class="font-monospace text-center"><?= htmlspecialchars($it['kode_barang'] ?: '-') ?></td>
                            <td>
                                <strong><?= htmlspecialchars($it['nama_barang']) ?></strong>
                                <?php if (!empty($it['nama_kategori'])): ?>
                                    <div class="small text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($it['nama_kategori']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center font-monospace"><?= (float)$it['qty_retur'] ?></td>
                            <td class="text-center font-monospace fw-bold text-success"><?= $qtyGanti ?></td>
                            <td class="text-center"><?= htmlspecialchars($it['satuan'] ?: $it['satuan_master'] ?: 'Unit') ?></td>
                            <td class="text-center text-success small fw-semibold">
                                <i class="bi bi-check-circle-fill me-1"></i> Baik (Ganti Baru)
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="fw-bold" style="background-color: #fafbfc;">
                        <td colspan="3" class="text-end pe-3">TOTAL KUANTITAS:</td>
                        <td class="text-center font-monospace"><?= $totalRetur ?></td>
                        <td class="text-center font-monospace text-success"><?= $totalGanti ?></td>
                        <td colspan="2"></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-3 text-muted">Tidak ada rincian barang.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Catatan Keterangan -->
        <div class="border p-2 rounded mb-4" style="background-color: #fdfdfd; font-size: 0.82rem;">
            <strong>Catatan Tambahan:</strong><br>
            <?= nl2br(htmlspecialchars($retur['keterangan'] ?: 'Barang pengganti telah diterima dalam kondisi baik, lengkap, dan stok fisik gudang telah diperbarui ke sistem inventori.')) ?>
        </div>

        <!-- Kolom Tanda Tangan (3 Pihak) -->
        <div class="row text-center signature-box">
            <div class="col-4">
                <div class="signature-title">Yang Menyerahkan,<br><span class="text-muted small">(Driver / Vendor)</span></div>
                <div class="signature-line">( <?= htmlspecialchars($retur['pic_vendor'] ?: '..................................') ?> )</div>
            </div>
            <div class="col-4">
                <div class="signature-title">Diterima Oleh,<br><span class="text-muted small">(Logistik / Site Gudang)</span></div>
                <div class="signature-line">( <?= htmlspecialchars($retur['nama_pembuat'] ?: 'Petugas Logistik') ?> )</div>
            </div>
            <div class="col-4">
                <div class="signature-title">Mengetahui,<br><span class="text-muted small">(Purchasing / Manager)</span></div>
                <div class="signature-line">( <?= htmlspecialchars($retur['nama_penyetuju'] ?: 'Kepala Bagian') ?> )</div>
            </div>
        </div>

    </div>

    <?php if ($autoPrint): ?>
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            window.print();
        });
    </script>
    <?php endif; ?>

</body>
</html>
