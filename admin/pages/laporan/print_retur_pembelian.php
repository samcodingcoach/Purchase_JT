<?php
/**
 * Halaman Cetak Laporan Rekapitulasi Retur Pembelian (Print / PDF)
 * Path: admin/pages/laporan/print_retur_pembelian.php
 * Format: Pure Black & White, Terintegrasi REST API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MEKANIK, ROLE_MANAGER, ROLE_FINANCE]);

$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');
$idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
$status = trim($_GET['status'] ?? '');
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

// Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'purchasing@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Rekapitulasi Retur Pembelian - <?= htmlspecialchars($companyName) ?></title>
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
                <i class="bi bi-printer text-info me-1"></i> Cetak Laporan Rekapitulasi Retur Pembelian
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'start_date' => $startDate,
                'end_date' => $endDate,
                'id_vendor' => $idVendor,
                'status' => $status
            ]);
            ?>
            <div class="btn-group btn-group-sm me-2" role="group">
                <a href="?<?= $queryUrl ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?<?= $queryUrl ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/laporan/retur_pembelian.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<!-- CONTAINER UTAMA DOKUMEN CETAK -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>" id="printContainer">
    
    <?php if ($useKop): ?>
    <!-- KOP SURAT RESMI -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars(ltrim($companyLogo, '/')) ?>" alt="Logo" style="max-height: 55px; max-width: 140px; object-fit: contain; flex-shrink: 0;">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-dark text-white rounded p-2" style="width: 50px; height: 50px;">
                    <i class="bi bi-buildings fs-3"></i>
                </div>
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
                INTEGRATED<br>PURCHASE &amp;<br>LOGISTICS
            </div>
        </div>
    </div>
    <div class="header-divider-line"></div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN -->
    <div class="title-box-row">
        <div class="title-area">
            <div class="doc-title-main">LAPORAN REKAPITULASI RETUR PEMBELIAN</div>
            <div class="doc-title-sub">
                <div class="line-side"></div>
                <div class="text-side" id="printDocSubtitle">Periode: Semua Tanggal</div>
                <div class="line-side"></div>
            </div>
        </div>
        <div class="doc-badge-box">
            <div class="doc-badge-title">JENIS LAPORAN</div>
            <div class="doc-badge-value">RETUR VENDOR</div>
        </div>
    </div>

    <!-- TABEL UTAMA LAPORAN: No, Vendor, Kompensasi, No Retur, Status -->
    <table class="table-items-main mb-3" id="tablePrintReport">
        <thead>
            <tr>
                <th class="text-center" style="width: 35px;">No</th>
                <th style="text-align: left;">Vendor</th>
                <th class="text-center" style="width: 140px;">Kompensasi</th>
                <th class="text-center" style="width: 160px;">No Retur</th>
                <th class="text-center" style="width: 180px;">Status</th>
            </tr>
        </thead>
        <tbody id="printTableBody">
            <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-dark me-2"></div> Memuat data laporan retur pembelian...
                </td>
            </tr>
        </tbody>
        <tfoot id="printTableFoot" style="display: none;">
            <tr class="total-row">
                <td colspan="3" class="text-end pe-2">TOTAL DOKUMEN RETUR:</td>
                <td colspan="2" class="text-start font-monospace fw-bold" id="footTotalRetur">0 Dokumen</td>
            </tr>
        </tfoot>
    </table>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM STANDAR) -->
    <div class="sig-section mt-4">
        <div class="row">
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Petugas Logistik</span>
                </div>
                <div class="sig-footer-note">Tanggal: ....................</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diperiksa Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Purchasing Officer</span>
                </div>
                <div class="sig-footer-note">Tanggal: ....................</div>
            </div>
            <div class="col-4 sig-col">
                <div class="sig-header-main">Disetujui Oleh,</div>
                <div class="sig-header-sub">&nbsp;</div>
                <div class="sig-line-box">
                    <span class="sig-person-name">Manager Operasional</span>
                </div>
                <div class="sig-footer-note">Tanggal: ....................</div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH DOKUMEN -->
    <div class="footer-line-container mt-4">
        <div class="footer-right">
            <div>Dokumen dicetak pada: <?= date('d/m/Y H:i') ?> WITA | Oleh: <?= htmlspecialchars($user['nama_karyawan'] ?? $user['username'] ?? 'Petugas') ?></div>
            <div>Purchasing &amp; Logistics Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

<script>
const START_DATE = "<?= addslashes($startDate) ?>";
const END_DATE = "<?= addslashes($endDate) ?>";
const ID_VENDOR = "<?= (int)$idVendor ?>";
const STATUS_FILTER = "<?= addslashes($status) ?>";

function formatShortDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const mon = months[d.getMonth()];
    const yr = d.getFullYear();
    return `${day} ${mon} ${yr}`;
}

async function loadPrintData() {
    const tbody = document.getElementById('printTableBody');
    const tfoot = document.getElementById('printTableFoot');

    const params = new URLSearchParams({
        page: 1,
        limit: 1000
    });
    if (START_DATE) params.append('start_date', START_DATE);
    if (END_DATE) params.append('end_date', END_DATE);
    if (ID_VENDOR && ID_VENDOR > 0) params.append('id_vendor', ID_VENDOR);
    if (STATUS_FILTER) params.append('status', STATUS_FILTER);

    // Subtitle Periode
    if (START_DATE && END_DATE) {
        document.getElementById('printDocSubtitle').textContent = `Periode: ${formatShortDate(START_DATE)} s/d ${formatShortDate(END_DATE)}`;
    } else if (START_DATE) {
        document.getElementById('printDocSubtitle').textContent = `Periode Mulai: ${formatShortDate(START_DATE)}`;
    } else if (END_DATE) {
        document.getElementById('printDocSubtitle').textContent = `Periode Sampai: ${formatShortDate(END_DATE)}`;
    }

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/retur_pembelian.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            renderPrintTable(res.data.items || []);
        } else {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3">${res.message || 'Gagal memuat data.'}</td></tr>`;
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function renderPrintTable(items) {
    const tbody = document.getElementById('printTableBody');
    const tfoot = document.getElementById('printTableFoot');

    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-3">Tidak ada data retur pembelian pada kriteria ini.</td></tr>`;
        tfoot.style.display = 'none';
        return;
    }

    let html = '';
    items.forEach((it, idx) => {
        const vendorText = `${escapeHtml(it.nama_vendor || '-')} [${escapeHtml(it.kode_vendor || '-')}]`;
        const kompText = (parseInt(it.kompensasi, 10) === 1) ? 'Tukar Unit' : 'Potong Tagihan';
        const statusText = escapeHtml(it.status || '-');

        html += `
        <tr>
            <td class="text-center">${idx + 1}</td>
            <td>
                <span class="fw-semibold">${vendorText}</span>
            </td>
            <td class="text-center">${kompText}</td>
            <td class="text-center font-monospace fw-bold">${escapeHtml(it.nomor_po_retur || '-')}</td>
            <td class="text-center fw-medium">${statusText}</td>
        </tr>`;
    });

    tbody.innerHTML = html;
    document.getElementById('footTotalRetur').textContent = `${items.length} Dokumen Retur`;
    tfoot.style.display = 'table-footer-group';
}

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('DOMContentLoaded', () => {
    loadPrintData();
});
</script>

</body>
</html>
