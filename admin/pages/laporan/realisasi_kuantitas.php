<?php
/**
 * Halaman Laporan Realisasi Kuantitas PO vs Penerimaan & Retur
 * Path: admin/pages/laporan/realisasi_kuantitas.php
 * Khusus Role: ADMIN, FINANCE, MANAGER, PURCHASING, LOGISTIK
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_FINANCE, ROLE_MANAGER, ROLE_PURCHASING, ROLE_LOGISTIK]);

$pageTitle = 'Laporan Realisasi Kuantitas';
$pageHeading = 'Laporan Realisasi Kuantitas';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Realisasi Kuantitas</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadRealisasiReport()">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
            <button type="button" class="btn btn-primary filter-btn px-3 shadow-sm fw-semibold" onclick="printReport()">
                <i class="bi bi-printer-fill me-1"></i> Cetak / PDF
            </button>
        </div>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Vendor Filter -->
                <div style="width: 280px;">
                    <select class="form-select filter-select" id="filterVendor" onchange="loadRealisasiReport()">
                        <option value="">Semua Vendor</option>
                    </select>
                </div>

                <!-- Reset Button (Icon Only) -->
                <div>
                    <button type="button" class="btn btn-outline-secondary filter-btn" title="Reset Filter" onclick="resetFilters()" style="width: 38px; height: 38px; padding: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableSummary">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-3 align-middle" style="width: 50px;">No</th>
                            <th class="py-3 align-middle">Nama Vendor</th>
                            <th class="text-end py-3 align-middle" style="width: 150px;">KTS PO</th>
                            <th class="text-end py-3 align-middle" style="width: 170px;">KTS RCV</th>
                            <th class="text-end py-3 align-middle" style="width: 170px;">KTS RTN</th>
                            <th class="text-end pe-3 py-3 align-middle" style="width: 160px;">Realisasi (%)</th>
                        </tr>
                    </thead>
                    <tbody id="summaryTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data realisasi kuantitas...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold align-middle" id="summaryTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="2" class="ps-3 py-3 text-uppercase align-middle">Grand Total</td>
                            <td class="text-end py-3 align-middle font-monospace" id="footQtyPo">0</td>
                            <td class="text-end py-3 align-middle font-monospace" id="footQtyRcv">0</td>
                            <td class="text-end py-3 align-middle font-monospace" id="footQtyRetur">0</td>
                            <td class="text-end pe-3 py-3 align-middle text-primary fs-6 font-monospace" id="footPersen">0.00%</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportData = null;

function resetFilters() {
    document.getElementById('filterVendor').value = '';
    loadRealisasiReport();
}

function printReport() {
    const vendor = document.getElementById('filterVendor').value;

    const params = new URLSearchParams({
        id_vendor: vendor
    });

    window.open(`<?= BASE_URL ?>/admin/pages/laporan/print_realisasi_kuantitas.php?${params.toString()}`, '_blank');
}

async function loadRealisasiReport() {
    const sumTbody = document.getElementById('summaryTableBody');
    const sumTfoot = document.getElementById('summaryTableFoot');

    sumTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted align-middle"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data rekapitulasi...</td></tr>`;
    sumTfoot.style.display = 'none';

    const vendor = document.getElementById('filterVendor').value;

    const params = new URLSearchParams({
        id_vendor: vendor
    });

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/realisasi_kuantitas.php?${params.toString()}`);
        const res = await response.json();

        if (res.success && res.data) {
            currentReportData = res.data;
            populateVendorDropdown(res.data.vendors_list, vendor);
            renderSummaryTable(res.data.summary, res.data.grand_total);
        } else {
            sumTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger align-middle">${res.message || 'Gagal memuat laporan.'}</td></tr>`;
        }
    } catch (err) {
        sumTbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
    }
}

function populateVendorDropdown(vendors, currentSelected) {
    const sel = document.getElementById('filterVendor');
    if (!vendors || sel.options.length > 1) return;

    vendors.forEach(v => {
        const opt = document.createElement('option');
        opt.value = v.id_vendor;
        opt.textContent = v.nama_perusahaan;
        if (String(v.id_vendor) === String(currentSelected)) opt.selected = true;
        sel.appendChild(opt);
    });
}

function renderSummaryTable(summary, grand) {
    const tbody = document.getElementById('summaryTableBody');
    const tfoot = document.getElementById('summaryTableFoot');

    if (!summary || summary.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted align-middle">Tidak ada data realisasi kuantitas pada filter ini.</td></tr>`;
        tfoot.style.display = 'none';
        return;
    }

    let html = '';
    summary.forEach((item, idx) => {
        html += `
        <tr class="align-middle">
            <td class="ps-3 text-muted align-middle">${idx + 1}</td>
            <td class="fw-semibold text-dark align-middle">
                ${escapeHtml(item.nama_perusahaan || '-')}
            </td>
            <td class="text-end font-monospace align-middle">${Number(item.qty_po || 0).toLocaleString('id-ID')}</td>
            <td class="text-end font-monospace align-middle">${Number(item.qty_rcv || 0).toLocaleString('id-ID')}</td>
            <td class="text-end font-monospace text-secondary align-middle">${Number(item.qty_retur || 0).toLocaleString('id-ID')}</td>
            <td class="text-end pe-3 font-monospace fw-bold text-dark align-middle">${item.formatted_persentase || '0.00%'}</td>
        </tr>`;
    });

    tbody.innerHTML = html;

    if (grand) {
        document.getElementById('footQtyPo').textContent = Number(grand.qty_po || 0).toLocaleString('id-ID');
        document.getElementById('footQtyRcv').textContent = Number(grand.qty_rcv || 0).toLocaleString('id-ID');
        document.getElementById('footQtyRetur').textContent = Number(grand.qty_retur || 0).toLocaleString('id-ID');
        document.getElementById('footPersen').textContent = grand.formatted_persentase || '0.00%';
        tfoot.style.display = 'table-footer-group';
    }
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
    loadRealisasiReport();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
