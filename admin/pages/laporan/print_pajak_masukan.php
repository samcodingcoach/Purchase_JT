<?php
/**
 * Halaman Cetak Rekapitulasi Laporan Pajak Masukan (Print / PDF)
 * Path: admin/pages/laporan/print_pajak_masukan.php
 * Format: Standard Black & White Corporate Header & Clean Table
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER]);

$tahun = isset($_GET['tahun']) && is_numeric($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$bulan = isset($_GET['bulan']) && is_numeric($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$idVendor = isset($_GET['id_vendor']) && is_numeric($_GET['id_vendor']) ? (int)$_GET['id_vendor'] : 0;
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

// Nama Bulan Indo
$namaBulan = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
    5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];
$periodeText = ($bulan > 0 && isset($namaBulan[$bulan])) 
    ? "Masa Pajak: {$namaBulan[$bulan]} {$tahun}" 
    : "Tahun Pajak: {$tahun} (Semua Masa)";

// Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'finance@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekapitulasi Pajak Masukan - <?= htmlspecialchars($companyName) ?></title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
    <style>
        @media print {
            .table-pajak th, .table-pajak td {
                padding: 4px 6px !important;
                font-size: 10px !important;
            }
            .table-pajak tfoot td {
                font-weight: bold !important;
                font-size: 10.5px !important;
                background-color: #f8f9fa !important;
                -webkit-print-color-adjust: exact;
            }
        }
        .table-pajak th {
            background-color: #f1f3f5 !important;
            font-size: 11px;
            text-transform: uppercase;
        }
        .table-pajak td {
            font-size: 11.5px;
        }
    </style>
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Cetak Rekapitulasi Pajak Masukan (PPN / PPnBM)
            </span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <?php
            $queryUrl = http_build_query([
                'tahun' => $tahun,
                'bulan' => $bulan,
                'id_vendor' => $idVendor
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

            <a href="<?= BASE_URL ?>/admin/pages/laporan/pajak_masukan.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<!-- DOKUMEN UTAMA -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>">
    
    <?php if ($useKop): ?>
    <!-- KOP PERUSAHAAN RESMI -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if (!empty($companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyLogo) ?>" alt="Logo Perusahaan" style="max-height: 55px; max-width: 140px; object-fit: contain;">
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center bg-dark text-white rounded p-2" style="width: 50px; height: 50px;">
                    <i class="bi bi-buildings fs-3"></i>
                </div>
            <?php endif; ?>
            <div>
                <div class="company-title"><?= htmlspecialchars($companyName) ?></div>
                <div class="company-addr"><?= htmlspecialchars($companyAddr) ?><?= !empty($companyCity) ? ', ' . htmlspecialchars($companyCity) : '' ?></div>
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
                LAPORAN PAJAK MASUKAN<br>
                <span>DIVISI KEUANGAN &amp; PERPAJAKAN</span>
            </div>
        </div>
    </div>
    
    <div class="header-divider-line"></div>
    <?php else: ?>
    <!-- MODE CETAK TANPA KOP -->
    <div class="no-print alert alert-secondary py-1 px-3 small text-center mb-3">
        <i class="bi bi-info-circle me-1"></i> <strong>Mode Cetak Tanpa Kop Surat Aktif</strong>
    </div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & INFO PERIODE -->
    <div class="title-box-row mb-3">
        <div class="title-area">
            <div class="doc-title-main">REKAPITULASI PAJAK MASUKAN (PPN &amp; PPnBM)</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text"><?= strtoupper($periodeText) ?></span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">Tanggal Cetak</div>
            <div class="box-row-val font-monospace"><?= date('d/m/Y H:i') ?></div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Dicetak Oleh</div>
            <div class="box-row-val"><?= htmlspecialchars($user['nama'] ?? 'Finance') ?></div>
        </div>
    </div>

    <!-- TABEL DATA REKAPITULASI PAJAK MASUKAN -->
    <div class="table-responsive">
        <table class="table table-bordered table-pajak align-middle mb-0">
            <thead>
                <tr class="text-center align-middle">
                    <th style="width: 30px;">No</th>
                    <th style="width: 130px;">Tanggal &amp; No. PO</th>
                    <th style="width: 140px;">No. Faktur Pajak</th>
                    <th>Nama Rekanan Vendor</th>
                    <th style="width: 65px;">Tarif</th>
                    <th style="width: 105px;" class="text-end">DPP</th>
                    <th style="width: 100px;" class="text-end">PPN Masukan</th>
                    <th style="width: 95px;" class="text-end">PPnBM</th>
                    <th style="width: 110px;" class="text-end">Total Tagihan</th>
                </tr>
            </thead>
            <tbody id="printTableBody">
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data laporan pajak masukan...
                    </td>
                </tr>
            </tbody>
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="5" class="text-end py-2">GRAND TOTAL:</td>
                    <td class="text-end font-monospace py-2" id="printGrandDpp">Rp 0</td>
                    <td class="text-end font-monospace text-dark py-2" id="printGrandPpn">Rp 0</td>
                    <td class="text-end font-monospace text-dark py-2" id="printGrandPpnbm">Rp 0</td>
                    <td class="text-end font-monospace text-primary py-2" id="printGrandTotalTagihan">Rp 0</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- TANDA TANGAN / OTORISASI -->
    <div class="row mt-4 pt-3 text-center" style="font-size: 11.5px; page-break-inside: avoid;">
        <div class="col-4">
            <div class="text-muted mb-1">Dibuat Oleh:</div>
            <div class="fw-bold text-dark mb-4">Staff Perpajakan / Finance</div>
            <div style="height: 50px;"></div>
            <div class="fw-bold text-decoration-underline text-dark">( <?= htmlspecialchars($user['nama'] ?? 'Staff Finance') ?> )</div>
        </div>
        <div class="col-4">
            <div class="text-muted mb-1">Diperiksa Oleh:</div>
            <div class="fw-bold text-dark mb-4">Supervisor / Akunting</div>
            <div style="height: 50px;"></div>
            <div class="fw-bold text-decoration-underline text-dark">( ........................................ )</div>
        </div>
        <div class="col-4">
            <div class="text-muted mb-1">Mengetahui &amp; Menyetujui:</div>
            <div class="fw-bold text-dark mb-4">Finance Manager</div>
            <div style="height: 50px;"></div>
            <div class="fw-bold text-decoration-underline text-dark">( ........................................ )</div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadPrintData();
});

async function loadPrintData() {
    const params = new URLSearchParams({
        tahun: '<?= $tahun ?>',
        bulan: '<?= $bulan ?>',
        id_vendor: '<?= $idVendor ?>',
        all: '1'
    });

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/laporan/pajak_masukan.php?${params.toString()}`);
        const result = await res.json();

        if (result && result.success && result.data) {
            renderPrintRows(result.data.rows, result.data.grand_totals);
        } else {
            showPrintEmpty(result.message || 'Gagal memuat data laporan.');
        }
    } catch (e) {
        showPrintEmpty('Terjadi kesalahan: ' + e.message);
    }
}

function renderPrintRows(rows, totals) {
    const tbody = document.getElementById('printTableBody');
    if (!rows || rows.length === 0) {
        showPrintEmpty('Tidak ada data faktur pajak masukan pada periode ini.');
        return;
    }

    let html = '';
    rows.forEach((r, idx) => {
        const ratePpnText = parseFloat(r.rate_ppn) > 0 ? `${parseFloat(r.rate_ppn)}%` : '0%';
        const ratePpnbmText = parseFloat(r.rate_ppnbm) > 0 ? `${parseFloat(r.rate_ppnbm)}%` : '-';
        const tarifCombined = (parseFloat(r.rate_ppnbm) > 0) ? `${parseFloat(r.rate_ppn)} / ${parseFloat(r.rate_ppnbm)}` : `${parseFloat(r.rate_ppn)}%`;

        html += `
        <tr>
            <td class="text-center font-monospace">${idx + 1}</td>
            <td>
                <div class="fw-bold text-dark">${formatTgl(r.tanggal_faktur_pajak)}</div>
                <div class="font-monospace text-muted" style="font-size: 10px;">${escapeHtml(r.nomor_po || '-')}</div>
            </td>
            <td>
                <span class="font-monospace fw-bold text-dark">${escapeHtml(r.nomor_faktur_pajak || '-')}</span>
                ${r.nomor_faktur_vendor ? `<div class="text-muted" style="font-size: 9.5px;">Inv: ${escapeHtml(r.nomor_faktur_vendor)}</div>` : ''}
            </td>
            <td>
                <strong class="text-dark">${escapeHtml(r.vendor || '-')}</strong>
                ${r.npwp_vendor && r.npwp_vendor !== '-' ? `<div class="text-muted font-monospace" style="font-size: 9.5px;">NPWP: ${escapeHtml(r.npwp_vendor)}</div>` : ''}
            </td>
            <td class="text-center font-monospace">${tarifCombined}</td>
            <td class="text-end font-monospace">${formatRupiah(r.dpp)}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(r.ppn_masukan)}</td>
            <td class="text-end font-monospace">${parseFloat(r.ppnbm) > 0 ? formatRupiah(r.ppnbm) : '-'}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(r.total_tagihan)}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    // Totals
    if (totals) {
        document.getElementById('printGrandDpp').textContent = formatRupiah(totals.grand_total_dpp);
        document.getElementById('printGrandPpn').textContent = formatRupiah(totals.grand_total_ppn);
        document.getElementById('printGrandPpnbm').textContent = formatRupiah(totals.grand_total_ppnbm);
        document.getElementById('printGrandTotalTagihan').textContent = formatRupiah(totals.grand_total_tagihan);
    }
}

function showPrintEmpty(msg) {
    document.getElementById('printTableBody').innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-4 text-muted">${msg}</td>
        </tr>`;
}

function formatTgl(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function formatRupiah(val) {
    const n = parseFloat(val) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>

</body>
</html>
