<?php
/**
 * Halaman Cetak Surat Penerimaan Barang (Receiving Report untuk Vendor)
 * Path: admin/pages/receiving/print.php
 * Format: Siap Print / PDF resmi perusahaan
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$idRcv = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idRcv <= 0) {
    die("ID Penerimaan Barang tidak valid.");
}

// Ambil Data Header
$stmt = $conn->prepare("SELECT ro.id_rcv, ro.nomor_rcv, ro.nomor_sj, ro.tanggal_rcv, ro.tanggal_diterima,
                               ro.file_sj, ro.keterangan as catatan_rcv, ro.status as status_rcv,
                               ro.print, ro.print_date,
                               po.id_po, po.nomor_po, po.tanggal_po,
                               v.id_vendor, v.kode_vendor, v.nama_perusahaan as nama_vendor, v.no_telepon as telepon_vendor, v.alamat as alamat_vendor,
                               s.id_site, s.nama_site, s.kode_site, s.alamat as alamat_site,
                               k.nama_karyawan as nama_penerima
                        FROM receiving_order ro
                        LEFT JOIN purchase_order po ON ro.id_po = po.id_po
                        LEFT JOIN vendor v ON po.id_vendor = v.id_vendor
                        LEFT JOIN site s ON po.id_site = s.id_site
                        LEFT JOIN karyawan k ON ro.id_karyawan = k.id_karyawan
                        WHERE ro.id_rcv = ? LIMIT 1");
$stmt->bind_param("i", $idRcv);
$stmt->execute();
$rcv = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rcv) {
    die("Dokumen Penerimaan Barang tidak ditemukan.");
}

// Ambil Detail Items
$stmtItems = $conn->prepare("SELECT rod.id_rcv_detail, rod.id_rcv, rod.id_barang, rod.qty as qty_diterima, 
                                    rod.status_qc, rod.keterangan as keterangan_item,
                                    b.kode_barang, b.nama_barang, b.satuan,
                                    kat.nama_kategori, mrk.nama_merk,
                                    pod.qty as qty_po
                             FROM receiving_order_detail rod
                             LEFT JOIN barang b ON rod.id_barang = b.id_barang
                             LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
                             LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
                             LEFT JOIN purchase_order_detail pod ON (pod.id_po = ? AND pod.id_barang = rod.id_barang)
                             WHERE rod.id_rcv = ?
                             ORDER BY rod.id_rcv_detail ASC");
$idPo = (int)$rcv['id_po'];
$stmtItems->bind_param("ii", $idPo, $idRcv);
$stmtItems->execute();
$items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Penerimaan Barang - <?= htmlspecialchars($rcv['nomor_rcv']) ?></title>
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
            letter-spacing: 1px;
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
            width: 140px;
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
            margin-top: 30px;
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
            min-width: 170px;
            padding-top: 4px;
        }
        @media print {
            body {
                background: #ffffff;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                box-shadow: none;
                margin: 0;
                padding: 10px 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<!-- Toolbar Atas (Tidak ikut dicetak) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="fw-bold">
            <i class="bi bi-printer me-2 text-info"></i> Cetak Surat Penerimaan Barang (SPB)
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/receiving/index.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" onclick="doPrintReceiving()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<div class="print-container">
    <!-- KOP PERUSAHAAN -->
    <div class="header-kop d-flex justify-content-between align-items-center">
        <div>
            <div class="company-title">PT JAYA TEKNIS INDONESIA</div>
            <div class="company-sub">
                Marine &amp; Industrial Engineering Services, Procurement &amp; Logistics<br>
                Jl. Pelabuhan Utama No. 88, Kawasan Industri Dok Maritim &bull; Telp: (021) 555-8822 &bull; Email: logistics@jayateknis.co.id
            </div>
        </div>
        <div class="text-end">
            <div class="border border-dark px-3 py-1 text-center font-monospace fw-bold" style="font-size: 0.85rem;">
                DOKUMEN LOGISTIK
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                Dicetak: <?= date('d/m/Y H:i') ?>
            </div>
        </div>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title">
        SURAT PENERIMAAN BARANG (RECEIVING REPORT)
    </div>

    <!-- INFORMASI UTAMA PENERIMAAN -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <table class="info-table">
                <tr>
                    <td class="label-col">No. Receiving (RCV)</td>
                    <td>: <strong class="font-monospace text-primary"><?= htmlspecialchars($rcv['nomor_rcv']) ?></strong></td>
                </tr>
                <tr>
                    <td class="label-col">No. Purchase Order</td>
                    <td>: <span class="font-monospace fw-bold"><?= htmlspecialchars($rcv['nomor_po'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="label-col">No. Surat Jalan Vendor</td>
                    <td>: <span class="font-monospace fw-bold"><?= htmlspecialchars($rcv['nomor_sj'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="label-col">Tanggal Penerimaan</td>
                    <td>: <?= $rcv['tanggal_diterima'] ? date('d F Y', strtotime($rcv['tanggal_diterima'])) : date('d F Y', strtotime($rcv['tanggal_rcv'])) ?></td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <table class="info-table">
                <tr>
                    <td class="label-col">Vendor Pengirim</td>
                    <td>: <strong><?= htmlspecialchars($rcv['nama_vendor'] ?: '-') ?></strong></td>
                </tr>
                <tr>
                    <td class="label-col">Site / Lokasi Tujuan</td>
                    <td>: <?= htmlspecialchars($rcv['nama_site'] ?: '-') ?> (<?= htmlspecialchars($rcv['kode_site'] ?: 'SITE') ?>)</td>
                </tr>
                <tr>
                    <td class="label-col">Petugas Logistik</td>
                    <td>: <?= htmlspecialchars($rcv['nama_penerima'] ?: 'Petugas Logistik') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Catatan Penerimaan</td>
                    <td>: <?= htmlspecialchars($rcv['catatan_rcv'] ?: '-') ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL RINCIAN FISIK BARANG (MURNI FISIK & QC, TANPA HARGA) -->
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 100px;">Kode Barang</th>
                <th>Deskripsi Barang / Material</th>
                <th style="width: 75px;">Qty PO</th>
                <th style="width: 85px;">Qty Diterima</th>
                <th style="width: 65px;">Satuan</th>
                <th style="width: 95px;">Status QC</th>
                <th style="width: 160px;">Keterangan / Kondisi Fisik</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="text-center py-3 text-muted">Tidak ada rincian barang fisik.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $it): ?>
                    <?php 
                        $statusQc = (int)($it['status_qc'] ?? 1);
                        $qcText = ($statusQc === 1) ? 'BAIK (Passed)' : 'CACAT / RUSAK';
                        $qcClass = ($statusQc === 1) ? 'text-success fw-bold' : 'text-danger fw-bold';
                    ?>
                    <tr>
                        <td class="text-center font-monospace"><?= $idx + 1 ?></td>
                        <td class="font-monospace text-center small"><?= htmlspecialchars($it['kode_barang'] ?: '-') ?></td>
                        <td>
                            <strong><?= htmlspecialchars($it['nama_barang'] ?: '') ?></strong>
                            <?php if ($it['nama_kategori'] || $it['nama_merk']): ?>
                                <div class="text-muted small" style="font-size: 0.75rem;">
                                    <?= htmlspecialchars($it['nama_kategori'] ?: '') ?> <?= $it['nama_merk'] ? ' &bull; ' . htmlspecialchars($it['nama_merk']) : '' ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="text-center font-monospace"><?= (float)$it['qty_po'] ?></td>
                        <td class="text-center font-monospace fw-bold fs-6"><?= (float)$it['qty_diterima'] ?></td>
                        <td class="text-center small"><?= htmlspecialchars($it['satuan'] ?: 'PCS') ?></td>
                        <td class="text-center small <?= $qcClass ?>"><?= $qcText ?></td>
                        <td class="small"><?= htmlspecialchars($it['keterangan_item'] ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- LEMBAR PENGESAHAN & TANDA TANGAN -->
    <div class="signature-box">
        <div class="row text-center">
            <div class="col-4">
                <div class="signature-title">Diserahkan Oleh,<br><span class="text-muted fw-normal">(Vendor / Ekspedisi)</span></div>
                <div class="signature-line">
                    (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
                </div>
            </div>
            <div class="col-4">
                <div class="signature-title">Diterima Oleh,<br><span class="text-muted fw-normal">(Petugas Logistik)</span></div>
                <div class="signature-line">
                    ( <?= htmlspecialchars($rcv['nama_penerima'] ?: 'Petugas Logistik') ?> )
                </div>
            </div>
            <div class="col-4">
                <div class="signature-title">Mengetahui,<br><span class="text-muted fw-normal">(Kepala Gudang / Site Manager)</span></div>
                <div class="signature-line">
                    (&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;)
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ID_RCV = <?= $idRcv ?>;

async function doPrintReceiving() {
    // 1. Update status print di database (kunci dokumen dari edit)
    try {
        await fetch('<?= BASE_URL ?>/api/receiving/mark_print.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id_rcv: ID_RCV })
        });
    } catch (e) {
        console.error('Error marking print:', e);
    }

    // 2. Buka dialog cetak browser
    window.print();
}
</script>
</body>
</html>
