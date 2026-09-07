<?php
/**
 * Halaman Cetak Dokumen Faktur Purchase Order (Faktur Pembelian / 3-Way Matching)
 * Path: admin/pages/faktur_po/print.php
 * Format: Terintegrasi External CSS (styles/print_document.css) & Standar B/W Siap Cetak
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_PURCHASING, ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);
$idFaktur = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idFaktur <= 0) {
    die("ID Faktur Purchase Order tidak valid.");
}

// 1. QUERY MASTER FAKTUR PURCHASE ORDER
$sqlMaster = "SELECT
	faktur_po.id_faktur,
	faktur_po.nomor_faktur, 
	faktur_po.nomor_faktur_vendor, 
	faktur_po.nomor_faktur_pajak, 
	faktur_po.tanggal_faktur_vendor, 
	faktur_po.tanggal_terima_faktur_vendor, 
	faktur_po.term_of_payment, 
	faktur_po.tanggal_jatuh_tempo, 
	purchase_order.id_po,
	purchase_order.nomor_po, 
	purchase_order.tanggal_po,
	purchase_order.total_termasuk_pajak,
	receiving_order.id_rcv,
	receiving_order.nomor_rcv, 
	receiving_order.nomor_sj AS nomor_sj_rcv,
	receiving_order.tanggal_diterima AS tanggal_rcv,
	vendor.id_vendor,
	vendor.kode_vendor,
	vendor.nama_perusahaan AS nama_vendor,
	vendor.alamat AS alamat_vendor,
	vendor.no_telepon AS telepon_vendor,
	vendor.email AS email_vendor,
	site.id_site,
	site.nama_site, 
	faktur_po.nama_bank, 
	faktur_po.nomor_rekening, 
	faktur_po.atas_nama_rekening, 
	karyawan.nama_karyawan, 
	jabatan.nama_jabatan, 
	divisi.nama_divisi, 
	faktur_po.subtotal_po, 
	faktur_po.subtotal_diterima,
	faktur_po.nilai_retur,
	faktur_po.diskon, 
	faktur_po.dpp, 
	faktur_po.rate_pajak, 
	faktur_po.nominal_pajak, 
	faktur_po.biaya_lain,
	faktur_po.total_tagihan, 
	faktur_po.terbayar,
	faktur_po.sisa_tagihan,
	faktur_po.`status`, 
	faktur_po.keterangan,
	faktur_po.created_at
FROM
	faktur_po
	INNER JOIN purchase_order ON faktur_po.id_po = purchase_order.id_po
	INNER JOIN receiving_order ON faktur_po.id_rcv = receiving_order.id_rcv
	INNER JOIN vendor ON faktur_po.id_vendor = vendor.id_vendor
	INNER JOIN site ON faktur_po.id_site = site.id_site
	INNER JOIN karyawan ON faktur_po.id_karyawan = karyawan.id_karyawan
	LEFT JOIN jabatan ON karyawan.id_jabatan = jabatan.id_jabatan
	LEFT JOIN divisi ON jabatan.id_divisi = divisi.id_divisi
WHERE faktur_po.id_faktur = ? LIMIT 1";

$stmtM = $conn->prepare($sqlMaster);
$stmtM->bind_param("i", $idFaktur);
$stmtM->execute();
$faktur = $stmtM->get_result()->fetch_assoc();
$stmtM->close();

if (!$faktur) {
    die("Dokumen Faktur Purchase Order tidak ditemukan.");
}

// 2. QUERY DETAIL BARANG FAKTUR PURCHASE ORDER
$sqlDetail = "SELECT
	faktur_po_detail.id_faktur_detail,
	faktur_po_detail.id_barang, 
	barang.kode_barang, 
	faktur_po_detail.qty_po, 
	faktur_po_detail.qty_rcv, 
	faktur_po_detail.qty_retur, 
	faktur_po_detail.satuan, 
	faktur_po_detail.harga_satuan, 
	faktur_po_detail.diskon_item, 
	faktur_po_detail.subtotal, 
	faktur_po_detail.keterangan, 
	barang.nama_barang, 
	merk_barang.nama_merk, 
	kategori_barang.nama_kategori
FROM
	faktur_po_detail
	INNER JOIN barang ON faktur_po_detail.id_barang = barang.id_barang
	LEFT JOIN merk_barang ON barang.id_merk = merk_barang.id_merk
	LEFT JOIN kategori_barang ON barang.id_kategori = kategori_barang.id_kategori
WHERE faktur_po_detail.id_faktur = ?
ORDER BY faktur_po_detail.id_faktur_detail ASC";

$stmtD = $conn->prepare($sqlDetail);
$stmtD->bind_param("i", $idFaktur);
$stmtD->execute();
$items = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtD->close();

// Ambil Data Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'purchasing@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';

// Helper Functions
function formatRupiah($num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID');
}
function formatTgl($dateStr) {
    if (!$dateStr || $dateStr === '0000-00-00') return '-';
    return date('d/m/Y', strtotime($dateStr));
}
function formatTglPanjang($dateStr) {
    if (!$dateStr || $dateStr === '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
        7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $time = strtotime($dateStr);
    $d = date('j', $time);
    $m = (int)date('n', $time);
    $y = date('Y', $time);
    return $d . ' ' . ($bulan[$m] ?? date('F', $time)) . ' ' . $y;
}
function terbilang($angka) {
    $angka = abs((float)$angka);
    $satuan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    if ($angka < 12) return " " . $satuan[(int)$angka];
    if ($angka < 20) return terbilang($angka - 10) . " Belas";
    if ($angka < 100) return terbilang($angka / 10) . " Puluh" . terbilang($angka % 10);
    if ($angka < 200) return " Seratus" . terbilang($angka - 100);
    if ($angka < 1000) return terbilang($angka / 100) . " Ratus" . terbilang($angka % 100);
    if ($angka < 2000) return " Seribu" . terbilang($angka - 1000);
    if ($angka < 1000000) return terbilang($angka / 1000) . " Ribu" . terbilang($angka % 1000);
    if ($angka < 1000000000) return terbilang($angka / 1000000) . " Juta" . terbilang($angka % 1000000);
    if ($angka < 1000000000000) return terbilang($angka / 1000000000) . " Miliar" . terbilang($angka % 1000000000);
    if ($angka < 1000000000000000) return terbilang($angka / 1000000000000) . " Triliun" . terbilang($angka % 1000000000000);
    return "";
}

$isTermasukPajak = ((int)($faktur['total_termasuk_pajak'] ?? 0) === 1);
$ratePajak = (int)$faktur['rate_pajak'];
$subtotalPo = (float)$faktur['subtotal_po'];
$subtotalRcv = (float)$faktur['subtotal_diterima'];
$nilaiRetur = (float)$faktur['nilai_retur'];
$diskon = (float)$faktur['diskon'];
$dpp = (float)$faktur['dpp'];
$nominalPajak = (float)$faktur['nominal_pajak'];
$biayaLain = (float)$faktur['biaya_lain'];
$totalTagihan = (float)$faktur['total_tagihan'];
$terbilangTotal = trim(terbilang($totalTagihan)) . " Rupiah";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faktur Purchase Order - <?= htmlspecialchars($faktur['nomor_faktur']) ?></title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Cetak Faktur Purchase Order
            </span>
            <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($faktur['nomor_faktur']) ?></span>
            <span class="badge bg-primary"><?= htmlspecialchars($faktur['status']) ?></span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?id=<?= $idFaktur ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= $idFaktur ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/faktur_po/index.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<!-- CONTAINER UTAMA DOKUMEN -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>" id="printContainer">
    
    <?php if ($useKop): ?>
    <!-- KOP SURAT (SESUAI PROFIL PERUSAHAAN) -->
    <div class="kop-container">
        <div class="kop-left">
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
                    <?php if (!empty($companyPhone)): ?>
                        <span><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($companyPhone) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($companyWa)): ?>
                        <span><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($companyWa) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($companyEmail)): ?>
                        <span><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($companyEmail) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tagline-container">
            <div class="tagline-divider"></div>
            <div class="tagline-text">
                FAKTUR &amp;<br>TAGIHAN PEMBELIAN<br>
            </div>
        </div>
    </div>
    
    <div class="header-divider-line"></div>
    <?php else: ?>
    <!-- MODE CETAK TANPA KOP -->
    <div class="no-print alert alert-secondary py-1 px-3 small text-center mb-3">
        <i class="bi bi-info-circle me-1"></i> <strong>Mode Cetak Tanpa Kop Surat Aktif</strong>: Bagian atas dikosongkan untuk dicetak pada kertas berkop resmi perusahaan.
    </div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & KOTAK NOMOR DOKUMEN -->
    <div class="title-box-row">
        <div class="title-area">
            <div class="doc-title-main">FAKTUR PEMBELIAN BARANG</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">PURCHASE &nbsp; INVOICE &nbsp; (3-WAY &nbsp; MATCHING)</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Faktur Sistem</div>
            <div class="box-row-val font-monospace"><?= htmlspecialchars($faktur['nomor_faktur']) ?></div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal Faktur</div>
            <div class="box-row-val font-monospace"><?= formatTglPanjang($faktur['tanggal_faktur_vendor']) ?></div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM (DOKUMEN ASAL, VENDOR, & TERMIN PEMBAYARAN) -->
    <div class="info-grid">
        <!-- Kolom Kiri: Vendor & Referensi Dokumen -->
        <div class="info-col-left">
            <div class="small text-muted mb-1" style="font-size: 11px;">Tagihan Dari Rekanan Vendor:</div>
            <div class="fw-bold text-dark mb-1" style="font-size: 13px;"><?= htmlspecialchars($faktur['nama_vendor']) ?></div>
            <div class="text-dark mb-2" style="font-size: 11.5px; line-height: 1.35;"><?= htmlspecialchars($faktur['alamat_vendor'] ?? '-') ?></div>
            
            <table class="table-meta-details mt-2">
                <tr>
                    <td class="lbl">No. Invoice Vendor</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold"><?= htmlspecialchars($faktur['nomor_faktur_vendor'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="lbl">No. Faktur Pajak</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace"><?= htmlspecialchars($faktur['nomor_faktur_pajak'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="lbl">No. Purchase Order (PO)</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace"><?= htmlspecialchars($faktur['nomor_po'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="lbl">No. Penerimaan (RCV)</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace"><?= htmlspecialchars($faktur['nomor_rcv'] ?: '-') ?> (SJ: <?= htmlspecialchars($faktur['nomor_sj_rcv'] ?: '-') ?>)</td>
                </tr>
            </table>
        </div>

        <!-- Kolom Kanan: Rekening & Syarat Pembayaran -->
        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Term of Payment (TOP)</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold"><?= (int)$faktur['term_of_payment'] ?> Hari</td>
                </tr>
                <tr>
                    <td class="lbl">Tgl Jatuh Tempo</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold text-dark"><?= formatTglPanjang($faktur['tanggal_jatuh_tempo']) ?></td>
                </tr>
                <tr>
                    <td class="lbl">Rekening Vendor</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace">
                        <?= htmlspecialchars($faktur['nama_bank'] ?: '-') ?> &bull; <strong><?= htmlspecialchars($faktur['nomor_rekening'] ?: '-') ?></strong><br>
                        <span class="text-muted" style="font-size: 10.5px;">a.n. <?= htmlspecialchars($faktur['atas_nama_rekening'] ?: $faktur['nama_vendor']) ?></span>
                    </td>
                </tr>
                <tr>
                    <td class="lbl">Site Tujuan</td>
                    <td class="colon">:</td>
                    <td class="val"><?= htmlspecialchars($faktur['nama_site'] ?: '-') ?></td>
                </tr>
                <tr>
                    <td class="lbl">Status Tagihan</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold font-monospace">[ <?= htmlspecialchars($faktur['status']) ?> ]</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TABEL RINCIAN BARANG (3-WAY MATCHING / BERDASARKAN PENERIMAAN BARANG) -->
    <div class="table-title fw-bold text-dark mb-1" style="font-size: 11px; text-transform: uppercase;">
        Rincian Barang &amp; Penagihan Berdasarkan Penerimaan:
    </div>
    <table class="table-items-main">
        <thead>
            <tr>
                <th style="width: 30px;">NO.</th>
                <th style="width: 75px;">KODE</th>
                <th>NAMA BARANG</th>
                <th style="width: 55px;" class="text-center">KTS PO</th>
                <th style="width: 65px;" class="text-center">KTS RCV</th>
                <th style="width: 55px;" class="text-center">RETUR</th>
                <th style="width: 55px;" class="text-center">SATUAN</th>
                <th style="width: 100px;" class="text-end">HARGA</th>
                <th style="width: 85px;" class="text-end">DISKON</th>
                <th style="width: 110px;" class="text-end">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="10" class="text-center py-3 text-muted">Tidak ada rincian barang.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $idx => $it): 
                    $qtyPo = (float)$it['qty_po'];
                    $qtyRcv = (float)$it['qty_rcv'];
                    $qtyRet = (float)$it['qty_retur'];
                    $harga = (float)$it['harga_satuan'];
                    $disc = (float)$it['diskon_item'];
                    $sub = (float)$it['subtotal'];
                ?>
                <tr>
                    <td class="text-center font-monospace"><?= $idx + 1 ?></td>
                    <td class="font-monospace"><?= htmlspecialchars($it['kode_barang'] ?: '-') ?></td>
                    <td>
                        <strong class="text-dark"><?= htmlspecialchars($it['nama_barang']) ?></strong>
                        <?php if (!empty($it['nama_kategori']) && $it['nama_kategori'] !== 'Umum'): ?>
                            <span class="text-muted small" style="font-size: 10px;">(<?= htmlspecialchars($it['nama_kategori']) ?>)</span>
                        <?php endif; ?>
                        <?php if (!empty($it['keterangan'])): ?>
                            <div class="text-muted small" style="font-size: 10px;"><?= htmlspecialchars($it['keterangan']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center font-monospace"><?= $qtyPo ?></td>
                    <td class="text-center font-monospace fw-bold text-dark"><?= $qtyRcv ?></td>
                    <td class="text-center font-monospace"><?= $qtyRet > 0 ? $qtyRet : '-' ?></td>
                    <td class="text-center"><?= htmlspecialchars($it['satuan'] ?: 'Unit') ?></td>
                    <td class="text-end font-monospace">Rp <?= number_format($harga, 0, ',', '.') ?></td>
                    <td class="text-end font-monospace"><?= $disc > 0 ? ('Rp ' . number_format($disc, 0, ',', '.')) : '-' ?></td>
                    <td class="text-end font-monospace fw-bold text-dark">Rp <?= number_format($sub, 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- GRID CATATAN & RINGKASAN FINANSIAL FAKTUR -->
    <div class="summary-notes-grid">
        <!-- Kolom Kiri: Terbilang & Catatan -->
        <div class="notes-column">
            <div class="notes-card">
                <div class="notes-title">Terbilang:</div>
                <div class="fw-bold text-dark font-monospace mb-2" style="font-size: 11.5px; line-height: 1.4;">
                    # <?= htmlspecialchars($terbilangTotal) ?> #
                </div>
                
                <?php if (!empty($faktur['keterangan'])): ?>
                    <div class="pt-2 border-top border-dark" style="font-size: 11px;">
                        <strong>Catatan Faktur:</strong><br>
                        <?= nl2br(htmlspecialchars($faktur['keterangan'])) ?>
                    </div>
                <?php endif; ?>

                <div class="pt-2 mt-2 border-top text-muted" style="font-size: 10px;">
                    * Pembayaran tagihan ditransfer resmi ke rekening vendor sesuai data yang tertera di atas.<br>
                    * Harap konfirmasi bukti transfer jika pembayaran telah berhasil diproses.
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Ringkasan Nilai Finansial -->
        <div class="summary-column">
            <div class="summary-box">
                <table class="summary-table">
                    <tr>
                        <td class="lbl">Subtotal Barang Diterima</td>
                        <td class="val">Rp <?= number_format($subtotalRcv, 0, ',', '.') ?></td>
                    </tr>
                    <?php if ($nilaiRetur > 0): ?>
                    <tr>
                        <td class="lbl">Potongan Retur PO (Credit Note)</td>
                        <td class="val" style="color: #dc3545;">- Rp <?= number_format($nilaiRetur, 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($diskon > 0): ?>
                    <tr>
                        <td class="lbl">Diskon Tambahan Faktur</td>
                        <td class="val" style="color: #dc3545;">- Rp <?= number_format($diskon, 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <td class="lbl">DPP (Dasar Pengenaan Pajak)</td>
                        <td class="val font-monospace fw-bold">Rp <?= number_format($dpp, 0, ',', '.') ?></td>
                    </tr>
                    <tr>
                        <td class="lbl">
                            PPN (<?= $ratePajak ?>%)<?= $isTermasukPajak ? ' (Inklusif)' : '' ?>:
                        </td>
                        <td class="val">Rp <?= number_format($nominalPajak, 0, ',', '.') ?></td>
                    </tr>
                    <?php if ($biayaLain > 0): ?>
                    <tr>
                        <td class="lbl">Biaya Lain-lain / Ongkir</td>
                        <td class="val">Rp <?= number_format($biayaLain, 0, ',', '.') ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="grand-total-row">
                        <td class="lbl">TOTAL TAGIHAN FAKTUR</td>
                        <td class="val">Rp <?= number_format($totalTagihan, 0, ',', '.') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM RESMI) -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Dibuat Oleh (Purchasing / Petugas) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh</div>
                <div class="sig-header-sub">
                    (<?= htmlspecialchars($faktur['nama_jabatan'] ?: 'Staff Purchasing') ?><?= !empty($faktur['nama_divisi']) ? ' - ' . htmlspecialchars($faktur['nama_divisi']) : '' ?>)
                </div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name"><?= htmlspecialchars($faktur['nama_karyawan'] ?: 'Staff') ?></span> &nbsp; )
                </div>
            </div>

            <!-- 2. Disetujui Oleh (Manager Finance / Direksi) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Disetujui Oleh</div>
                <div class="sig-header-sub">(Manager Finance / Direksi)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name">Pimpinan Perusahaan</span> &nbsp; )
                </div>
            </div>

            <!-- 3. Pihak Rekanan Vendor -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diterima / Rekanan</div>
                <div class="sig-header-sub">(Pihak Rekanan Vendor)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name"><?= htmlspecialchars($faktur['nama_vendor']) ?></span> &nbsp; )
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH -->
    <div class="footer-line-container">
        <div class="footer-right">
            <div class="fw-bold">Halaman 1 dari 1</div>
            <div>Dicetak: <?= date('d/m/Y H:i') ?></div>
            <div>Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

</body>
</html>
