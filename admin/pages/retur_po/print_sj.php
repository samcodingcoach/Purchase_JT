<?php
/**
 * Halaman Cetak Surat Jalan Pengembalian Barang (Surat Jalan Retur PO ke Vendor)
 * Path: admin/pages/retur_po/print_sj.php
 * Format: Siap Print / PDF resmi perusahaan
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$idRetur = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idRetur <= 0) {
    die("ID Retur PO tidak valid.");
}

// Ambil Data Header Retur PO
$stmt = $conn->prepare("
    SELECT rp.*,
           po.nomor_po, po.tanggal_po,
           ro.nomor_rcv, ro.tanggal_rcv, ro.nomor_sj as nomor_sj_vendor_awal,
           v.id_vendor, v.nama_perusahaan as nama_vendor, v.no_telepon as telepon_vendor, v.alamat as alamat_vendor,
           s.id_site, s.nama_site, s.kode_site, s.alamat as alamat_site,
           k_pembuat.nama_karyawan as nama_pembuat,
           k_app.nama_karyawan as nama_penyetuju
    FROM retur_po rp
    LEFT JOIN purchase_order po ON rp.id_po = po.id_po
    LEFT JOIN receiving_order ro ON rp.id_rcv = ro.id_rcv
    LEFT JOIN vendor v ON rp.id_vendor = v.id_vendor
    LEFT JOIN site s ON rp.id_site = s.id_site
    LEFT JOIN karyawan k_pembuat ON rp.id_karyawan = k_pembuat.id_karyawan
    LEFT JOIN karyawan k_app ON rp.id_karyawan_approved = k_app.id_karyawan
    WHERE rp.id_po_retur = ? LIMIT 1
");
$stmt->bind_param("i", $idRetur);
$stmt->execute();
$retur = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$retur) {
    die("Dokumen Retur PO tidak ditemukan.");
}

// Ambil Detail Barang yang Diretur
$stmtItems = $conn->prepare("
    SELECT rpd.*,
           b.kode_barang, b.nama_barang, b.satuan as master_satuan,
           kat.nama_kategori, mrk.nama_merk
    FROM retur_po_detail rpd
    LEFT JOIN barang b ON rpd.id_barang = b.id_barang
    LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
    LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
    WHERE rpd.id_po_retur = ?
    ORDER BY rpd.id_po_retur_detail ASC
");
$stmtItems->bind_param("i", $idRetur);
$stmtItems->execute();
$items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtItems->close();

$nomorSj = !empty($retur['nomor_sj_retur']) ? $retur['nomor_sj_retur'] : ('SJ-RET-' . date('ymd', strtotime($retur['tanggal_po_retur'])) . sprintf('%03d', $retur['id_po_retur']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Jalan Retur - <?= htmlspecialchars($nomorSj) ?></title>
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
            font-size: 1.35rem;
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
            font-size: 1.2rem;
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
            width: 150px;
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
            margin-bottom: 65px;
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
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="fw-bold">
            <i class="bi bi-printer me-2 text-info"></i> Cetak Surat Jalan Pengembalian Barang (Retur PO)
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/retur_po/detail.php?id=<?= $idRetur ?>" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Detail
            </a>
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Surat Jalan (Print / PDF)
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
                SURAT JALAN PENGANTAR
            </div>
            <div class="small text-muted mt-1" style="font-size: 0.75rem;">
                Dicetak: <?= date('d/m/Y H:i') ?>
            </div>
        </div>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title">
        SURAT JALAN PENGEMBALIAN BARANG (RETUR)
    </div>

    <!-- INFORMASI UTAMA PENGIRIMAN & REFERENSI -->
    <div class="row g-2 mb-3">
        <div class="col-6">
            <table class="info-table">
                <tr>
                    <td class="label-col">No. Surat Jalan Retur</td>
                    <td>: <strong class="font-monospace text-primary fs-6"><?= htmlspecialchars($nomorSj) ?></strong></td>
                </tr>
                <tr>
                    <td class="label-col">No. Dokumen Retur PO</td>
                    <td>: <span class="font-monospace fw-bold"><?= htmlspecialchars($retur['nomor_po_retur'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="label-col">No. Purchase Order (PO)</td>
                    <td>: <span class="font-monospace fw-bold"><?= htmlspecialchars($retur['nomor_po'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="label-col">No. Penerimaan (RCV)</td>
                    <td>: <span class="font-monospace fw-bold"><?= htmlspecialchars($retur['nomor_rcv'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="label-col">Tanggal Pengiriman</td>
                    <td>: <?= date('d F Y', strtotime($retur['tanggal_po_retur'])) ?></td>
                </tr>
            </table>
        </div>
        <div class="col-6">
            <table class="info-table">
                <tr>
                    <td class="label-col">Tujuan Vendor</td>
                    <td>: <strong><?= htmlspecialchars($retur['nama_vendor'] ?: '-') ?></strong></td>
                </tr>
                <tr>
                    <td class="label-col">PIC Vendor</td>
                    <td>: <?= htmlspecialchars($retur['pic_vendor'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="label-col">Alamat / Kontak</td>
                    <td>: <?= htmlspecialchars($retur['alamat_vendor'] ?: '-') ?> (<?= htmlspecialchars($retur['telepon_vendor'] ?: '-') ?>)</td>
                </tr>
                <tr>
                    <td class="label-col">Asal Site / Gudang</td>
                    <td>: <?= htmlspecialchars($retur['nama_site'] ?: '-') ?> (<?= htmlspecialchars($retur['kode_site'] ?: 'SITE') ?>)</td>
                </tr>
                <tr>
                    <td class="label-col">Armada Pengiriman</td>
                    <td>: <strong>Armada <?= htmlspecialchars($retur['pengiriman_retur'] ?: 'Vendor') ?></strong></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="p-2 mb-3 bg-light border rounded" style="font-size: 0.83rem;">
        <strong>Catatan Pengiriman:</strong> Harap diperiksa kondisi fisik barang retur sesuai rincian di bawah ini. Dokumen ini sah sebagai tanda terima serah terima fisik barang rusak/cacat yang dikembalikan ke pihak Vendor.
    </div>

    <!-- TABEL BARANG YANG DIRETUR -->
    <table class="item-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 110px;">Kode Barang</th>
                <th>Nama Barang &amp; Deskripsi</th>
                <th style="width: 80px;">Qty Retur</th>
                <th style="width: 70px;">Satuan</th>
                <th style="width: 220px;">Alasan &amp; Kondisi Kerusakan Fisik</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="text-center py-3 text-muted">Tidak ada rincian barang retur.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $it): ?>
                    <tr>
                        <td class="text-center fw-semibold"><?= $idx + 1 ?></td>
                        <td class="font-monospace text-center small"><?= htmlspecialchars($it['kode_barang'] ?: '-') ?></td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($it['nama_barang']) ?></div>
                            <div class="text-muted small">
                                <?= htmlspecialchars($it['nama_kategori'] ?: '') ?> <?= !empty($it['nama_merk']) ? '&bull; Merk: ' . htmlspecialchars($it['nama_merk']) : '' ?>
                            </div>
                        </td>
                        <td class="text-center fw-bold font-monospace fs-6"><?= floatval($it['qty_retur']) ?></td>
                        <td class="text-center small"><?= htmlspecialchars($it['satuan'] ?: $it['master_satuan']) ?></td>
                        <td class="small">
                            <strong><?= htmlspecialchars(str_replace('_', ' ', $it['alasan_retur'] ?: '-')) ?></strong>
                            <?php if (!empty($it['keterangan_kerusakan'])): ?>
                                <br><span class="text-muted"><?= htmlspecialchars($it['keterangan_kerusakan']) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SKEMA KOMPENSASI & CATATAN TAMBAHAN -->
    <div class="row g-2 mb-3" style="font-size: 0.85rem;">
        <div class="col-7">
            <div class="border p-2 rounded h-100 bg-white">
                <div class="fw-bold text-dark mb-1"><i class="bi bi-info-circle me-1"></i>Skema Kompensasi Vendor:</div>
                <div class="text-dark">
                    <?= ($retur['kompensasi'] == 1) 
                        ? '<strong>Tukar Unit (Ganti Barang Baru)</strong> &mdash; Vendor akan mengirimkan unit pengganti kondisi baru.' 
                        : '<strong>Potong Tagihan (Credit Note)</strong> &mdash; Nilai retur memotong sisa tagihan/invoice PO terkait.' ?>
                </div>
                <?php if (!empty($retur['keterangan'])): ?>
                    <div class="mt-2 text-muted small"><strong>Catatan Khusus:</strong> <?= htmlspecialchars($retur['keterangan']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-5">
            <div class="border p-2 rounded h-100 bg-white">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">Biaya Pengiriman Retur:</span>
                    <span class="fw-bold font-monospace">Rp <?= number_format($retur['biaya_retur'] ?: 0, 0, ',', '.') ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">No. Nota Retur Pajak:</span>
                    <span class="font-monospace fw-semibold"><?= htmlspecialchars($retur['nomor_nota_retur_pajak'] ?: '-') ?></span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Status Otorisasi:</span>
                    <span class="fw-bold text-success"><?= htmlspecialchars($retur['nama_penyetuju'] ? 'Disetujui' : 'Draft Pengajuan') ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- TANDA TANGAN 3 PIHAK -->
    <div class="signature-box">
        <div class="row text-center">
            <div class="col-4">
                <div class="signature-title">Yang Menyerahkan (Logistik)</div>
                <div>
                    <span class="signature-line">( <?= htmlspecialchars($retur['nama_pembuat'] ?: 'Petugas Logistik') ?> )</span>
                </div>
                <div class="small text-muted mt-1">Tgl: <?= date('d/m/Y') ?></div>
            </div>
            <div class="col-4">
                <div class="signature-title">Yang Mengantar (Kurir/Armada)</div>
                <div>
                    <span class="signature-line">( ............................................ )</span>
                </div>
                <div class="small text-muted mt-1">Armada <?= htmlspecialchars($retur['pengiriman_retur'] ?: 'Pengirim') ?></div>
            </div>
            <div class="col-4">
                <div class="signature-title">Penerima (Pihak Vendor)</div>
                <div>
                    <span class="signature-line">( <?= htmlspecialchars($retur['pic_vendor'] ?: '............................................') ?> )</span>
                </div>
                <div class="small text-muted mt-1">Tanda Tangan &amp; Cap Basah</div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    // Check if auto-print requested via query string ?autoprint=1
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autoprint') === '1') {
        setTimeout(() => {
            window.print();
        }, 600);
    }
});
</script>

</body>
</html>
