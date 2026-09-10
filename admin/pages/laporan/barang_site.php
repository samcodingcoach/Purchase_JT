<?php
/**
 * Halaman Laporan Data Barang Berdasarkan Site
 * Path: admin/pages/laporan/barang_site.php
 * Khusus Role: ADMIN, LOGISTIK, PURCHASING, FINANCE, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_FINANCE, ROLE_MANAGER]);

$pageTitle = 'Laporan Data Barang per Site';
$pageHeading = 'Laporan Data Barang Berdasarkan Site';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
.filter-select {
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    padding-right: 2.25rem !important;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Data Barang per Site</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="goToPage(1)">
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
                <!-- Search Box -->
                <div style="min-width: 220px; flex: 1 1 220px;">
                    <div class="input-group">
                        <span class="input-group-text bg-white text-muted border-end-0" style="height: 38px;"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control filter-control border-start-0 ps-0" id="filterSearch" placeholder="Cari Kode, Nama Barang, Deskripsi..." onkeyup="debounceSearch()" style="height: 38px;">
                    </div>
                </div>

                <!-- Filter Site -->
                <div style="width: 240px; max-width: 100%;">
                    <select class="form-select filter-select" id="filterSite" onchange="goToPage(1)" style="height: 38px;">
                        <option value="0">Semua Lokasi Site</option>
                    </select>
                </div>

                <!-- Filter Jenis Barang -->
                <div style="width: 170px;">
                    <select class="form-select filter-select" id="filterJenis" onchange="goToPage(1)" style="height: 38px;">
                        <option value="">Semua Jenis</option>
                        <option value="1">Persediaan</option>
                        <option value="0">Jasa</option>
                    </select>
                </div>

                <!-- Filter Asset -->
                <div style="width: 160px;">
                    <select class="form-select filter-select" id="filterAsset" onchange="goToPage(1)" style="height: 38px;">
                        <option value="">Semua Status</option>
                        <option value="1">Aset (Ya)</option>
                        <option value="0">Bukan Aset (Tidak)</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <div>
                    <button type="button" class="btn btn-outline-secondary filter-btn" title="Reset Filter" onclick="resetFilters()" style="width: 38px; height: 38px; padding: 0;">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableBarangSite">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-3 align-middle text-center" style="width: 45px;">No</th>
                            <th class="py-3 align-middle" style="width: 120px;">Kode</th>
                            <th class="py-3 align-middle">Nama Barang &amp; Deskripsi</th>
                            <th class="py-3 align-middle" style="width: 220px;">Lokasi Site</th>
                            <th class="py-3 align-middle text-center" style="width: 110px;">Jenis</th>
                            <th class="py-3 align-middle text-center" style="width: 90px;">Aset</th>
                            <th class="text-end py-3 align-middle" style="width: 140px;">Stok Fisik</th>
                            <th class="text-center pe-3 py-3 align-middle" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="barangSiteTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data barang per site...
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold align-middle" id="barangSiteTableFoot" style="display: none;">
                        <tr class="align-middle">
                            <td colspan="6" class="ps-3 py-3 text-uppercase align-middle">Total Kuantitas Stok (Halaman Ini)</td>
                            <td class="text-end py-3 align-middle font-monospace text-success fs-6" id="footTotalStok">0</td>
                            <td class="pe-3 py-3 text-center">-</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Pagination Footer (Sesuai Standar Halaman Project) -->
        <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top" id="paginationContainer" style="display: none;">
            <div class="text-muted small" id="paginationInfo">
                Menampilkan data...
            </div>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- MODAL DETAIL & FOTO BARANG -->
<div class="modal fade" id="modalDetailBarang" tabindex="-1" aria-labelledby="modalDetailBarangLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-bottom py-3">
                <h5 class="modal-title fw-bold text-dark" id="modalDetailBarangLabel">
                    <i class="bi bi-box-seam text-primary me-2"></i> Detail Data Barang
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalDetailBody">
                <!-- Content Populated via JS -->
            </div>
            <div class="modal-footer bg-light border-top py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentReportData = null;
let searchTimer = null;
let currentPage = 1;
const fixedLimit = 25;

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        currentPage = 1;
        loadBarangSiteReport();
    }, 300);
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '0';
    document.getElementById('filterJenis').value = '';
    document.getElementById('filterAsset').value = '';
    currentPage = 1;
    loadBarangSiteReport();
}

function goToPage(page) {
    currentPage = page;
    loadBarangSiteReport();
}

async function loadBarangSiteReport() {
    const tbody = document.getElementById('barangSiteTableBody');
    const tfoot = document.getElementById('barangSiteTableFoot');
    const pagContainer = document.getElementById('paginationContainer');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-4 text-muted align-middle">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data barang per site...
            </td>
        </tr>
    `;

    const search = document.getElementById('filterSearch').value.trim();
    const idSite = document.getElementById('filterSite').value;
    const jenis = document.getElementById('filterJenis').value;
    const asset = document.getElementById('filterAsset').value;

    const params = new URLSearchParams();
    params.append('page', currentPage);
    params.append('limit', fixedLimit);
    if (search) params.append('search', search);
    if (idSite && idSite !== '0') params.append('id_site', idSite);
    if (jenis !== '') params.append('jenis', jenis);
    if (asset !== '') params.append('asset', asset);

    try {
        const response = await fetch(`<?= BASE_URL ?>/api/laporan/barang_site.php?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const res = await response.json();

        if (res.success && res.data) {
            currentReportData = res.data;
            renderSiteOptions(res.data.sites || []);
            renderTable(res.data.items || [], res.data.pagination);
            renderPaginationControls(res.data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger align-middle">${res.message || 'Gagal memuat data.'}</td></tr>`;
            tfoot.style.display = 'none';
            pagContainer.style.display = 'none';
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
        tfoot.style.display = 'none';
        pagContainer.style.display = 'none';
    }
}

function renderSiteOptions(sites) {
    const select = document.getElementById('filterSite');
    const currentVal = select.value;
    
    // Only populate if only has 1 default option
    if (select.options.length <= 1) {
        sites.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id_site;
            opt.textContent = s.nama_site;
            opt.title = `${s.nama_site} (${s.jenis_site || 'Site'})`;
            select.appendChild(opt);
        });
    }
    select.value = currentVal;
}

function renderTable(items, pagination) {
    const tbody = document.getElementById('barangSiteTableBody');
    const tfoot = document.getElementById('barangSiteTableFoot');
    const pagContainer = document.getElementById('paginationContainer');

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted align-middle">Tidak ada data barang yang sesuai dengan filter.</td></tr>`;
        tfoot.style.display = 'none';
        pagContainer.style.display = 'none';
        return;
    }

    let html = '';
    let pageTotalStok = 0;
    const startIndex = pagination ? (pagination.from - 1) : 0;

    items.forEach((item, idx) => {
        pageTotalStok += (parseFloat(item.stok) || 0);

        const jenisBadge = item.jenis_barang === 'Persediaan'
            ? '<span class="badge bg-primary-subtle text-primary font-monospace">Persediaan</span>'
            : '<span class="badge bg-secondary-subtle text-secondary font-monospace">Jasa</span>';

        const assetBadge = item.asset === 'Ya'
            ? '<span class="badge bg-warning-subtle text-warning font-monospace"><i class="bi bi-check-circle me-1"></i>Aset</span>'
            : '<span class="badge bg-light text-muted border font-monospace">Bukan</span>';

        html += `
            <tr class="align-middle">
                <td class="ps-3 text-center text-muted font-monospace small">${startIndex + idx + 1}</td>
                <td class="font-monospace fw-bold text-dark">${escapeHtml(item.kode_barang)}</td>
                <td>
                    <div class="fw-bold text-dark">${escapeHtml(item.nama_barang)}</div>
                    ${item.deskripsi ? `<div class="text-muted small text-truncate" style="max-width: 380px;">${escapeHtml(item.deskripsi)}</div>` : ''}
                </td>
                <td>
                    <div class="fw-semibold text-dark">${escapeHtml(item.nama_site)}</div>
                </td>
                <td class="text-center">${jenisBadge}</td>
                <td class="text-center">${assetBadge}</td>
                <td class="text-end font-monospace">
                    <strong class="text-dark fs-6">${Number(item.stok || 0).toLocaleString('id-ID')}</strong>
                    <span class="text-muted small ms-1">${escapeHtml(item.satuan || 'PCS')}</span>
                </td>
                <td class="pe-3 text-center">
                    <button type="button" class="btn btn-sm btn-outline-primary p-1 px-2" onclick="showDetailModal(${idx})" title="Lihat Detail & Foto">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('footTotalStok').textContent = pageTotalStok.toLocaleString('id-ID');
    tfoot.style.display = 'table-footer-group';
    pagContainer.style.display = 'flex';
}

function renderPaginationControls(pag) {
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');

    if (!pag || pag.total_records === 0) {
        info.textContent = 'Menampilkan 0 dari 0 data';
        controls.innerHTML = '';
        return;
    }

    info.textContent = `Menampilkan ${pag.from} - ${pag.to} dari ${pag.total_records} data (Total: ${pag.total_pages} Halaman)`;

    if (pag.total_pages <= 1) {
        controls.innerHTML = '';
        return;
    }

    let html = '';
    
    // Tombol Prev
    html += `
        <li class="page-item ${pag.page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page - 1})">&laquo; Prev</a>
        </li>
    `;
    
    const startPage = Math.max(1, pag.page - 2);
    const endPage = Math.min(pag.total_pages, pag.page + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    
    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === pag.page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${p})">${p}</a>
            </li>
        `;
    }
    
    if (endPage < pag.total_pages) {
        if (endPage < pag.total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.total_pages})">${pag.total_pages}</a></li>`;
    }
    
    // Tombol Next
    html += `
        <li class="page-item ${pag.page >= pag.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page + 1})">Next &raquo;</a>
        </li>
    `;
    
    controls.innerHTML = html;
}

function showDetailModal(idx) {
    if (!currentReportData || !currentReportData.items || !currentReportData.items[idx]) return;
    const item = currentReportData.items[idx];

    let photosHtml = '';
    if (item.foto1_url || item.foto2_url) {
        photosHtml += '<div class="row g-3 mb-3">';
        if (item.foto1_url) {
            photosHtml += `
                <div class="col-md-6 text-center">
                    <div class="border rounded p-2 bg-light">
                        <div class="small fw-bold text-muted mb-2">Foto Utama (1)</div>
                        <img src="${item.foto1_url}" alt="Foto 1" class="img-fluid rounded" style="max-height: 220px; object-fit: contain;">
                    </div>
                </div>
            `;
        }
        if (item.foto2_url) {
            photosHtml += `
                <div class="col-md-6 text-center">
                    <div class="border rounded p-2 bg-light">
                        <div class="small fw-bold text-muted mb-2">Foto Detail (2)</div>
                        <img src="${item.foto2_url}" alt="Foto 2" class="img-fluid rounded" style="max-height: 220px; object-fit: contain;">
                    </div>
                </div>
            `;
        }
        photosHtml += '</div>';
    } else {
        photosHtml = '<div class="alert alert-light border text-center text-muted small py-3 mb-3"><i class="bi bi-image me-1"></i> Tidak ada foto terlampir untuk barang ini.</div>';
    }

    const modalBody = document.getElementById('modalDetailBody');
    modalBody.innerHTML = `
        ${photosHtml}
        <div class="row g-3">
            <div class="col-md-6">
                <div class="bg-light p-3 rounded-3 border">
                    <div class="mb-2">
                        <span class="text-muted small d-block">Kode Barang:</span>
                        <span class="font-monospace fw-bold fs-6 text-primary">${escapeHtml(item.kode_barang)}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Nama Barang:</span>
                        <strong class="text-dark fs-6">${escapeHtml(item.nama_barang)}</strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Deskripsi / Spesifikasi:</span>
                        <span class="text-dark">${escapeHtml(item.deskripsi || '-')}</span>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Serial Number:</span>
                        <span class="font-monospace fw-semibold">${escapeHtml(item.serial_number || '-')}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="bg-light p-3 rounded-3 border">
                    <div class="mb-2">
                        <span class="text-muted small d-block">Lokasi Site Operasional:</span>
                        <strong class="text-dark fs-6">${escapeHtml(item.nama_site)}</strong>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Jumlah Stok Fisik:</span>
                        <strong class="text-success fs-5 font-monospace">${Number(item.stok || 0).toLocaleString('id-ID')}</strong>
                        <span class="fw-bold text-dark ms-1">${escapeHtml(item.satuan || 'PCS')}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted small d-block">Jenis Barang:</span>
                        <span class="badge bg-primary-subtle text-primary font-monospace">${escapeHtml(item.jenis_barang)}</span>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Klasifikasi Aset:</span>
                        <span class="badge ${item.asset === 'Ya' ? 'bg-warning-subtle text-warning' : 'bg-secondary-subtle text-secondary'} font-monospace">${item.asset === 'Ya' ? 'Ya (Aset Perusahaan)' : 'Tidak (Bukan Aset)'}</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    const modal = new bootstrap.Modal(document.getElementById('modalDetailBarang'));
    modal.show();
}

function printReport() {
    const search = document.getElementById('filterSearch').value.trim();
    const idSite = document.getElementById('filterSite').value;
    const jenis = document.getElementById('filterJenis').value;
    const asset = document.getElementById('filterAsset').value;

    const params = new URLSearchParams();
    params.append('limit', '0'); // Ambil seluruh data untuk dicetak
    if (search) params.append('search', search);
    if (idSite && idSite !== '0') params.append('id_site', idSite);
    if (jenis !== '') params.append('jenis', jenis);
    if (asset !== '') params.append('asset', asset);

    const printUrl = `<?= BASE_URL ?>/admin/pages/laporan/print_barang_site.php?${params.toString()}`;
    window.open(printUrl, '_blank');
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
    goToPage(1);
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
