<?php
/**
 * Halaman Cetak Surat Penerimaan Barang (Receiving Report untuk Vendor)
 * Path: admin/pages/receiving/print.php
 * Desain: 100% Sesuai Template Standar Resmi Perusahaan dengan Data Profil Dinamis
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$idRcv = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idRcv <= 0) {
    die("ID Penerimaan Barang tidak valid.");
}

// Ambil Data Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Prabukan Utara No. 88, Kawasan Industri Deltamas - Cikarang';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : (!empty($profile['whatsapp']) ? $profile['whatsapp'] : '(021) 555-6822');
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'logistik@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';

// Ambil Data Header Receiving
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

function formatTanggalIndo($tanggal) {
    if (!$tanggal || $tanggal === '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $time = strtotime($tanggal);
    $d = date('j', $time);
    $m = (int)date('n', $time);
    $y = date('Y', $time);
    return $d . ' ' . ($bulan[$m] ?? date('F', $time)) . ' ' . $y;
}

$tglTerima = $rcv['tanggal_diterima'] ?: $rcv['tanggal_rcv'];
$tglIndo = formatTanggalIndo($tglTerima);
$printTimestamp = date('d/m/Y H:i');

$totalQtyPo = 0;
$totalQtyRcv = 0;
foreach ($items as $it) {
    $totalQtyPo += (float)($it['qty_po'] ?? 0);
    $totalQtyRcv += (float)($it['qty_diterima'] ?? 0);
}
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
        @page {
            size: A4 portrait;
            margin: <?= $useKop ? '10mm 12mm 10mm 12mm' : '30mm 12mm 10mm 12mm' ?>;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000000;
            background-color: #f0f2f5;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-wrapper {
            max-width: 860px;
            margin: 20px auto;
            background: #ffffff;
            padding: <?= $useKop ? '28px 36px 30px 36px' : '15px 36px 30px 36px' ?>;
            box-shadow: 0 4px 22px rgba(0,0,0,0.1);
            border-radius: 4px;
            box-sizing: border-box;
        }

        /* HEADER KOP SURAT */
        .kop-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 8px;
        }
        .kop-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .company-title {
            font-size: 19px;
            font-weight: 900;
            color: #000000;
            letter-spacing: 0.3px;
            line-height: 1.15;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .company-sub {
            font-size: 11px;
            font-weight: 600;
            color: #222222;
            line-height: 1.3;
        }
        .company-addr {
            font-size: 11px;
            color: #333333;
            line-height: 1.3;
        }
        .company-contacts {
            font-size: 10.5px;
            color: #111111;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .company-contacts i {
            font-size: 10px;
            margin-right: 3px;
        }

        .tagline-container {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-left: 15px;
        }
        .tagline-divider {
            width: 1.5px;
            height: 52px;
            background-color: #000000;
        }
        .tagline-text {
            font-size: 11.5px;
            font-weight: 800;
            font-style: italic;
            line-height: 1.15;
            color: #111111;
            letter-spacing: 0.2px;
            text-align: left;
        }

        .header-divider-line {
            width: 100%;
            height: 1px;
            background-color: #222222;
            margin-top: 2px;
            margin-bottom: 15px;
        }

        /* SECTION JUDUL & KOTAK DOKUMEN */
        .title-box-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .title-area {
            flex-grow: 1;
            text-align: left;
        }
        .doc-title-main {
            font-size: 21px;
            font-weight: 900;
            letter-spacing: 0.8px;
            color: #000000;
            line-height: 1.1;
            text-transform: uppercase;
        }
        .doc-title-sub {
            display: flex;
            align-items: center;
            margin-top: 5px;
            width: 85%;
        }
        .doc-title-sub .line-side {
            flex-grow: 1;
            height: 1px;
            background-color: #333333;
        }
        .doc-title-sub .sub-text {
            padding: 0 12px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 4px;
            color: #111111;
        }

        /* KOTAK NO DOKUMEN & TANGGAL */
        .doc-meta-box {
            width: 180px;
            border: 1.5px solid #000000;
            text-align: center;
            background-color: #ffffff;
            flex-shrink: 0;
        }
        .doc-meta-box .box-row-lbl {
            font-size: 10.5px;
            color: #333333;
            padding: 3px 4px;
            background-color: #ffffff;
        }
        .doc-meta-box .box-row-val {
            font-size: 12.5px;
            font-weight: 800;
            padding: 2px 4px 4px 4px;
            color: #000000;
        }
        .doc-meta-box .box-divider {
            border-top: 1px solid #000000;
        }

        /* METADATA 2 KOLOM */
        .info-grid {
            display: flex;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-col-left {
            width: 50%;
            padding-right: 15px;
        }
        .info-col-right {
            width: 50%;
            padding-left: 15px;
            border-left: 1px solid #d0d0d0;
        }
        .table-meta-details {
            width: 100%;
            border-collapse: collapse;
        }
        .table-meta-details td {
            padding: 3px 0;
            font-size: 11.5px;
            vertical-align: top;
        }
        .table-meta-details td.lbl {
            width: 135px;
            font-weight: 600;
            color: #111111;
            white-space: nowrap;
        }
        .table-meta-details td.colon {
            width: 12px;
            text-align: center;
            font-weight: 600;
        }
        .table-meta-details td.val {
            color: #000000;
        }

        /* TABEL BARANG */
        .table-items-main {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            margin-bottom: 24px;
        }
        .table-items-main th {
            background-color: #d9d9d9 !important;
            border: 1px solid #555555;
            padding: 7px 4px;
            font-weight: 800;
            text-align: center;
            vertical-align: middle;
            font-size: 11.5px;
            color: #000000;
            text-transform: uppercase;
        }
        .table-items-main td {
            border: 1px solid #555555;
            padding: 6px 6px;
            vertical-align: middle;
            color: #000000;
        }
        .table-items-main .total-row td {
            background-color: #e5e7eb !important;
            font-weight: 800;
            border: 1px solid #555555;
            padding: 7px 6px;
        }

        /* KOTAK CATATAN PENERIMAAN */
        .catatan-penerimaan-section {
            margin-top: -12px;
            margin-bottom: 22px;
        }
        .catatan-title {
            font-size: 11px;
            font-weight: 800;
            color: #000000;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .catatan-box {
            border: 1px solid #000000;
            border-radius: 4px;
            min-height: 52px;
            height: 52px;
            padding: 6px 10px;
            font-size: 11px;
            color: #111111;
            background-color: #ffffff;
            box-sizing: border-box;
        }

        /* TANDA TANGAN (3 KOLOM) */
        .sig-section {
            margin-top: 15px;
            margin-bottom: 26px;
            page-break-inside: avoid;
        }
        .sig-col {
            text-align: center;
        }
        .sig-header-main {
            font-size: 12px;
            font-weight: 800;
            color: #000000;
            margin-bottom: 2px;
        }
        .sig-header-sub {
            font-size: 11px;
            color: #222222;
            margin-bottom: 55px;
        }
        .sig-line-box {
            display: inline-block;
            min-width: 165px;
            border-bottom: 1px solid #000000;
            padding-bottom: 3px;
        }
        .sig-person-name {
            font-size: 11.5px;
            font-weight: 800;
            color: #000000;
        }
        .sig-footer-note {
            font-size: 10px;
            color: #333333;
            margin-top: 4px;
        }

        /* FOOTER BAWAH */
        .footer-line-container {
            border-top: 1px solid #000000;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 10px;
            color: #444444;
        }
        .footer-motto {
            font-style: italic;
            font-weight: 600;
            color: #555555;
            font-size: 11px;
        }
        .footer-right {
            text-align: right;
            line-height: 1.35;
        }

        /* PRINT MEDIA RULES */
        @media print {
            body {
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .print-wrapper {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .table-items-main th {
                background-color: #d9d9d9 !important;
            }
            .table-items-main .total-row td {
                background-color: #e5e7eb !important;
            }
        }
    </style>
</head>
<body>

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Cetak Surat Penerimaan Barang (SPB)
            </span>
            <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($rcv['nomor_rcv']) ?></span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?id=<?= $idRcv ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= $idRcv ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/receiving/index.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="doPrintReceiving()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<div class="print-wrapper">
    
    <?php if ($useKop): ?>
    <!-- KOP SURAT (SESUAI PROFIL PERUSAHAAN) -->
    <div class="kop-container">
        <div class="kop-left">
            <!-- LOGO: JIKA ADA GAMBAR PROFIL PAKAI GAMBAR, JIKA TIDAK PAKAI SVG KUBUS -->
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/../../../uploads/profile/' . $companyLogo)): ?>
                <img src="<?= BASE_URL ?>/uploads/profile/<?= htmlspecialchars($companyLogo) ?>" alt="Logo" style="width: 54px; height: 54px; object-fit: contain; flex-shrink: 0;">
            <?php else: ?>
                <svg width="54" height="54" viewBox="0 0 100 100" style="flex-shrink: 0;">
                    <polygon points="50,4 92,27 92,73 50,96 8,73 8,27" fill="none" stroke="#000" stroke-width="8" stroke-linejoin="round"/>
                    <polyline points="8,27 50,50 92,27" fill="none" stroke="#000" stroke-width="8" stroke-linejoin="round"/>
                    <line x1="50" y1="50" x2="50" y2="96" stroke="#000" stroke-width="8"/>
                    <polygon points="50,22 74,35 50,48 26,35" fill="#000"/>
                    <polygon points="26,41 46,51 46,76 26,65" fill="#000"/>
                    <polygon points="74,41 54,51 54,76 74,65" fill="#000"/>
                </svg>
            <?php endif; ?>

            <div>
                <div class="company-title"><?= htmlspecialchars($companyName) ?></div>
                <div class="company-addr"><?= htmlspecialchars($companyAddr) ?><?= $companyCity ? ' - ' . htmlspecialchars($companyCity) : '' ?></div>
                <div class="company-contacts">
                    <span><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($companyPhone) ?></span>
                    <span><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($companyEmail) ?></span>
                    <span><i class="bi bi-globe"></i> www.jayateknis.co.id</span>
                </div>
            </div>
        </div>

        <div class="tagline-container">
            <div class="tagline-divider"></div>
            <div class="tagline-text">
                SOLUSI<br>LOGISTIK<br>UNTUK<br>INDUSTRI
            </div>
        </div>
    </div>
    
    <div class="header-divider-line"></div>
    <?php else: ?>
    <!-- MODE CETAK TANPA KOP (DIKOSONGKAN UNTUK KERTAS BERKOP RESMI) -->
    <div class="no-print alert alert-secondary py-1 px-3 small text-center mb-3">
        <i class="bi bi-info-circle me-1"></i> <strong>Mode Cetak Tanpa Kop Surat Aktif</strong>: Bagian atas dikosongkan untuk dicetak pada kertas berkop resmi perusahaan.
    </div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & KOTAK NOMOR DOKUMEN -->
    <div class="title-box-row">
        <div class="title-area">
            <div class="doc-title-main">SURAT PENERIMAAN BARANG</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">RECEIVING &nbsp; REPORT</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Dokumen</div>
            <div class="box-row-val font-monospace"><?= htmlspecialchars($rcv['nomor_rcv']) ?></div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal</div>
            <div class="box-row-val"><?= $tglIndo ?></div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM -->
    <div class="info-grid">
        <!-- Kolom Kiri -->
        <div class="info-col-left">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">No. Purchase Order</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold"><?= htmlspecialchars($rcv['nomor_po'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="lbl">No. Surat Jalan</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace"><?= htmlspecialchars($rcv['nomor_sj'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="lbl">Vendor / Pengirim</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold"><?= htmlspecialchars($rcv['nama_vendor'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="lbl">Alamat Vendor</td>
                    <td class="colon">:</td>
                    <td class="val"><?= nl2br(htmlspecialchars($rcv['alamat_vendor'] ?: '-')) ?></td>
                </tr>
            </table>
        </div>

        <!-- Kolom Kanan -->
        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Lokasi Tujuan</td>
                    <td class="colon">:</td>
                    <td class="val"><?= htmlspecialchars($rcv['nama_site'] ?: '-') ?> (<?= htmlspecialchars($rcv['kode_site'] ?: 'SIT03') ?>)</td>
                </tr>
                <tr>
                    <td class="lbl">Petugas Logistik</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold"><?= htmlspecialchars($rcv['nama_penerima'] ?: 'Agus Logistik') ?></td>
                </tr>
                <tr>
                    <td class="lbl">Tanggal Penerimaan</td>
                    <td class="colon">:</td>
                    <td class="val"><?= $tglIndo ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL DAFTAR BARANG / MATERIAL -->
    <table class="table-items-main">
        <thead>
            <tr>
                <th style="width: 40px;">NO.</th>
                <th style="width: 85px;">KODE</th>
                <th>NM BARANG</th>
                <th style="width: 60px;">PO</th>
                <th style="width: 60px;">RCV</th>
                <th style="width: 75px;">SATUAN</th>
                <th style="width: 60px;">QC</th>
                <th style="width: 185px;">KET</th>
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
                        $qcText = ($statusQc === 1) ? 'BAIK' : 'CACAT';
                        $qtyPoVal = (float)$it['qty_po'];
                        $qtyRcvVal = (float)$it['qty_diterima'];
                        $ketText = !empty($it['keterangan_item']) ? $it['keterangan_item'] : '-';
                    ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td class="text-center font-monospace"><?= htmlspecialchars($it['kode_barang'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($it['nama_barang'] ?: '') ?></td>
                        <td class="text-center font-monospace"><?= $qtyPoVal ?></td>
                        <td class="text-center font-monospace fw-bold"><?= $qtyRcvVal ?></td>
                        <td class="text-center"><?= strtoupper(htmlspecialchars($it['satuan'] ?: 'PCS')) ?></td>
                        <td class="text-center fw-bold"><?= $qcText ?></td>
                        <td><?= htmlspecialchars($ketText) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- BARIS TOTAL -->
            <tr class="total-row">
                <td colspan="3" class="text-center fw-bold">TOTAL</td>
                <td class="text-center font-monospace fw-bold"><?= $totalQtyPo ?></td>
                <td class="text-center font-monospace fw-bold"><?= $totalQtyRcv ?></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- KOTAK CATATAN PENERIMAAN (UNTUK TULISAN TANGAN / KETERANGAN RESMI) -->
    <div class="catatan-penerimaan-section">
        <div class="catatan-title">CATATAN PENERIMAAN :</div>
        <div class="catatan-box">
            <?= !empty($rcv['catatan_rcv']) ? nl2br(htmlspecialchars($rcv['catatan_rcv'])) : '' ?>
        </div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM) -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Diserahkan Oleh -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diserahkan Oleh</div>
                <div class="sig-header-sub">(Vendor / Ekspedisi)</div>
                <div class="sig-line-box">
                    ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
                </div>
                <div class="sig-footer-note">Nama &amp; Tanda Tangan</div>
            </div>

            <!-- 2. Diterima Oleh -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diterima Oleh</div>
                <div class="sig-header-sub">(Petugas Logistik)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name"><?= htmlspecialchars($rcv['nama_penerima'] ?: 'Agus Logistik') ?></span> &nbsp; )
                </div>
                <div class="sig-footer-note">Nama &amp; Tanda Tangan</div>
            </div>

            <!-- 3. Mengetahui -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Mengetahui</div>
                <div class="sig-header-sub">(Kepala Gudang / Site Manager)</div>
                <div class="sig-line-box">
                    ( &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; )
                </div>
                <div class="sig-footer-note">Nama &amp; Tanda Tangan</div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH -->
    <div class="footer-line-container">
        <div class="footer-motto">
            Good Material. Stronger Tomorrow.
        </div>
        <div class="footer-right">
            <div class="fw-bold">Halaman 1 dari 1</div>
            <div>Dicetak: <?= $printTimestamp ?></div>
            <div>Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

<script>
const ID_RCV = <?= $idRcv ?>;

async function doPrintReceiving() {
    // 1. Update status print di database (kunci dokumen dan migrasikan stok)
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
