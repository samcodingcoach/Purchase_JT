<?php
/**
 * Halaman Laporan Rekapitulasi Retur Pembelian
 * Path: admin/pages/laporan/retur_pembelian.php
 * Khusus Role: LOGISTIK, PURCHASING, MEKANIK, ADMIN, MANAGER, FINANCE
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection: Logistik, Purchasing, Mekanik, Admin, Manager, Finance
$user = requireAuth([ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MEKANIK, ROLE_ADMIN, ROLE_MANAGER, ROLE_FINANCE]);

$pageTitle = 'Laporan Rekapitulasi Retur Pembelian';
$pageHeading = 'Laporan Rekapitulasi Retur Pembelian';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Laporan Rekapitulasi Retur Pembelian</h4>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary filter-btn px-3" onclick="loadReport(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
            <button type="button" class="btn btn-primary filter-btn px-3 shadow-sm fw-semibold" onclick="printReport()">
                <i class="bi bi-printer-fill me-1"></i> Cetak
        </div>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Searchable Vendor Filter Dropdown (Persis sama seperti di hutang_vendor.php) -->
                <div class="position-relative" style="width: 320px; max-width: 100%;">
                    <input type="hidden" id="filterVendor" value="">
                    <div class="form-control filter-control d-flex align-items-center justify-content-between bg-white cursor-pointer px-3" 
                         id="vendorDropdownBtn" 
                         onclick="toggleVendorDropdown(event)" 
                         style="height: 38px; cursor: pointer; user-select: none; background-image: none;">
                        <span id="vendorDropdownLabel" class="text-truncate text-dark" style="max-width: calc(100% - 20px);">Semua Vendor</span>
                        <i class="bi bi-chevron-down text-muted small ms-1" id="vendorDropdownIcon"></i>
                    </div>
                    <div class="dropdown-menu shadow border p-2 w-100" 
                         id="vendorDropdownMenu" 
                         style="display: none; position: absolute; top: 100%; left: 0; z-index: 1050; margin-top: 4px; max-height: 300px;">
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0" id="searchVendorInput" placeholder="Cari nama / kode vendor..." onkeyup="filterVendorList(this.value)" autocomplete="off">
                        </div>
                        <div class="overflow-auto" id="vendorOptionsList" style="max-height: 210px;">
                            <!-- Options populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Tanggal Mulai -->
                <div style="min-width: 140px; flex: 1 1 140px;">
                    <input type="date" class="form-control filter-select" id="filterStartDate" placeholder="Tgl Mulai Retur" onchange="loadReport(1)">
                </div>

                <!-- Tanggal Selesai -->
                <div style="min-width: 140px; flex: 1 1 140px;">
                    <input type="date" class="form-control filter-select" id="filterEndDate" placeholder="Tgl Selesai Retur" onchange="loadReport(1)">
                </div>

                <!-- Filter Status -->
                <div style="min-width: 180px; flex: 1 1 180px;">
                    <select class="form-select filter-select" id="filterStatus" onchange="loadReport(1)">
                        <option value="">Semua Status</option>
                        <option value="DRAFT">DRAFT</option>
                        <option value="MENUNGGU KONFIRMASI VENDOR">MENUNGGU KONFIRMASI VENDOR</option>
                        <option value="DISETUJUI VENDOR">DISETUJUI VENDOR</option>
                        <option value="TIDAK DISETUJUI VENDOR">TIDAK DISETUJUI VENDOR</option>
                        <option value="DIKIRIM KE VENDOR">DIKIRIM KE VENDOR</option>
                        <option value="DITERIMA">DITERIMA</option>
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
                <table class="table table-hover align-middle mb-0" id="tableReturReport">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-3 text-center align-middle" style="width: 50px;">No</th>
                            <th class="py-3 align-middle" style="min-width: 250px;">Vendor</th>
                            <th class="text-center py-3 align-middle" style="width: 160px;">Kompensasi</th>
                            <th class="py-3 align-middle" style="width: 180px;">No Retur</th>
                            <th class="text-center py-3 align-middle" style="width: 200px;">Status</th>
                            <th class="text-center pe-3 py-3 align-middle" style="width: 80px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="returReportTableBody">
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted align-middle">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data laporan retur pembelian...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINATION FOOTER (Standar Sistem) -->
        <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top" id="paginationContainer" style="display: none !important;">
            <div class="text-muted small" id="paginationInfo">
                Menampilkan data...
            </div>
            <nav aria-label="Navigasi Halaman">
                <ul class="pagination pagination-sm mb-0" id="paginationControls">
                    <!-- Pagination links -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- ============================================================= -->
<!-- MODAL POPUP DETAIL RETUR PEMBELIAN (TABBED UI) -->
<!-- ============================================================= -->
<div class="modal fade" id="modalDetailRetur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 900px;">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- Modal Header dengan Nav Tabs Seragam -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="modalHeaderNoRetur">
                            RET-0000-0000
                        </h5>
                        <span id="modalHeaderStatusBadge" class="fw-semibold small text-dark">-</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Nav Tabs Modal Sesuai Pola Sistem -->
                <ul class="nav nav-tabs border-bottom-0" id="modalReturTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="modal-tab-retur-dokumen" data-bs-toggle="tab" data-bs-target="#modal-pane-retur-dokumen" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Dokumen &amp; Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-retur-rincian" data-bs-toggle="tab" data-bs-target="#modal-pane-retur-rincian" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> 2. Rincian Barang
                            <span class="badge bg-primary text-white ms-1" id="modalTotalItemBadge">0</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-retur-catatan" data-bs-toggle="tab" data-bs-target="#modal-pane-retur-catatan" type="button" role="tab">
                            <i class="bi bi-chat-left-text me-1 text-primary"></i> 3. Catatan &amp; Keterangan
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN 3 TAB PANE -->
            <div class="modal-body p-4">
                <div class="tab-content" id="modalReturTabContent">
                    
                    <!-- TAB 1: DOKUMEN & VENDOR -->
                    <div class="tab-pane fade show active" id="modal-pane-retur-dokumen" role="tabpanel">
                        <div class="row g-3">
                            <!-- Kolom Kiri: Identitas Retur & Referensi -->
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Identitas Retur</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Tanggal Retur:</span>
                                        <strong class="text-dark font-monospace" id="modalTanggalRetur">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Skema Kompensasi:</span>
                                        <strong class="text-dark" id="modalKompensasiBadge">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Referensi Purchase Order (PO):</span>
                                        <span class="font-monospace fw-semibold text-primary" id="modalNoPo">-</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Dokumen Penerimaan (RCV):</span>
                                        <span class="font-monospace fw-semibold text-dark" id="modalNoRcv">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Data Vendor & Penginput -->
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Vendor &amp; Lokasi</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Nama Vendor:</span>
                                        <strong class="text-dark fs-6" id="modalVendorNama">-</strong>
                                        <div class="text-muted small font-monospace" id="modalVendorKode">-</div>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Site / Lokasi:</span>
                                        <strong class="text-dark" id="modalSite">-</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Petugas &amp; Penyetuju:</span>
                                        <span class="text-dark" id="modalPembuat">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: RINCIAN BARANG -->
                    <div class="tab-pane fade" id="modal-pane-retur-rincian" role="tabpanel">
                        <div class="table-responsive border rounded-3 bg-white">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">No</th>
                                        <th style="min-width: 120px;">Kode Barang</th>
                                        <th style="min-width: 200px;">Nama Barang</th>
                                        <th style="min-width: 110px;" class="text-center">Serial No</th>
                                        <th style="min-width: 110px;" class="text-end">Jumlah</th>
                                        <th style="min-width: 160px;">Alasan Retur</th>
                                    </tr>
                                </thead>
                                <tbody id="modalTableItemsBody">
                                    <!-- Dynamic Items -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: CATATAN & KETERANGAN -->
                    <div class="tab-pane fade" id="modal-pane-retur-catatan" role="tabpanel">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <h6 class="fw-bold text-dark mb-2">Catatan / Keterangan Tambahan</h6>
                            <p class="mb-0 text-dark" id="modalKeterangan">-</p>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-white border-top py-2 px-4 d-flex justify-content-between">
                <div>
                    <a href="#" target="_blank" class="btn btn-outline-secondary btn-sm" id="btnModalPrintSpb" style="display: none;">
                        <i class="bi bi-printer me-1"></i> Cetak SPB Retur
                    </a>
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<style>
.filter-select {
    padding-top: 0.15rem !important;
    padding-bottom: 0.35rem !important;
    height: 38px !important;
    font-size: 0.875rem !important;
}
.filter-btn {
    height: 38px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 0.875rem !important;
}
.hover-bg-light:hover {
    background-color: #f8f9fa;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
let allVendorsList = [];
let currentPage = 1;
const fixedLimit = 20;

function formatShortDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const mon = months[d.getMonth()];
    const yr = d.getFullYear();
    return `${day} ${mon} ${yr}`;
}

function formatAlasanRetur(alasan) {
    switch (alasan) {
        case 'RUSAK_FISIK': return 'Rusak Fisik';
        case 'CACAT_PRODUKSI': return 'Cacat Produksi';
        case 'SALAH_SPESIFIKASI': return 'Salah Spesifikasi';
        case 'KURANG_PENGIRIMAN': return 'Kurang Pengiriman';
        case 'KADALUARSA_EXP': return 'Kadaluarsa / Exp';
        default: return alasan || '-';
    }
}

// -------------------------------------------------------------
// VENDOR DROPDOWN LOGIC (SAMA DENGAN HUTANG_VENDOR.PHP)
// -------------------------------------------------------------
function toggleVendorDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('vendorDropdownMenu');
    const isOpen = menu.style.display === 'block';
    
    if (isOpen) {
        closeVendorDropdown();
    } else {
        menu.style.display = 'block';
        document.getElementById('vendorDropdownBtn').classList.add('border-primary', 'shadow-sm');
        const sInput = document.getElementById('searchVendorInput');
        sInput.value = '';
        filterVendorList('');
        setTimeout(() => sInput.focus(), 50);
    }
}

function closeVendorDropdown() {
    const menu = document.getElementById('vendorDropdownMenu');
    if (menu) menu.style.display = 'none';
    const btn = document.getElementById('vendorDropdownBtn');
    if (btn) btn.classList.remove('border-primary', 'shadow-sm');
}

document.addEventListener('click', function(e) {
    const btn = document.getElementById('vendorDropdownBtn');
    const menu = document.getElementById('vendorDropdownMenu');
    if (menu && menu.style.display === 'block') {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            closeVendorDropdown();
        }
    }
});

function filterVendorList(query) {
    const q = (query || '').toLowerCase().trim();
    const listContainer = document.getElementById('vendorOptionsList');
    const currentVal = document.getElementById('filterVendor').value;
    
    let html = `
        <div class="dropdown-item py-2 px-2 rounded-2 text-truncate cursor-pointer ${currentVal === '' ? 'active fw-bold' : ''}" 
             onclick="selectVendor('', 'Semua Vendor')" 
             style="cursor: pointer; font-size: 0.875rem;" title="Semua Vendor">
            <i class="bi bi-people me-2"></i>Semua Vendor
        </div>
    `;
    
    let matchCount = 0;
    allVendorsList.forEach(v => {
        const text = (v.kode_vendor ? `[${v.kode_vendor}] ` : '') + (v.nama_perusahaan || '');
        if (!q || text.toLowerCase().includes(q)) {
            matchCount++;
            const isSel = String(v.id_vendor) === String(currentVal);
            html += `
                <div class="dropdown-item py-2 px-2 rounded-2 text-truncate cursor-pointer ${isSel ? 'active fw-bold' : ''}" 
                     onclick="selectVendor('${v.id_vendor}', '${escapeHtml(text)}')" 
                     style="cursor: pointer; font-size: 0.875rem;" title="${escapeHtml(text)}">
                    ${escapeHtml(text)}
                </div>
            `;
        }
    });
    
    if (matchCount === 0 && q !== '') {
        html += `<div class="p-2 text-muted text-center small">Tidak ada vendor yang cocok.</div>`;
    }
    
    listContainer.innerHTML = html;
}

function selectVendor(id, label) {
    document.getElementById('filterVendor').value = id;
    document.getElementById('vendorDropdownLabel').textContent = label;
    document.getElementById('vendorDropdownLabel').title = label;
    closeVendorDropdown();
    loadReport(1);
}

function resetFilters() {
    document.getElementById('filterVendor').value = '';
    document.getElementById('vendorDropdownLabel').textContent = 'Semua Vendor';
    document.getElementById('vendorDropdownLabel').title = 'Semua Vendor';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    document.getElementById('filterStatus').value = '';
    closeVendorDropdown();
    loadReport(1);
}

function goToPage(page) {
    currentPage = page;
    loadReport(page);
}

// -------------------------------------------------------------
// LOAD REPORT DATA
// -------------------------------------------------------------
async function loadReport(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('returReportTableBody');
    const pagContainer = document.getElementById('paginationContainer');

    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-5 text-muted align-middle">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data laporan...
            </td>
        </tr>
    `;

    const idVendor = document.getElementById('filterVendor').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams({
        page: currentPage,
        limit: fixedLimit
    });
    if (idVendor) params.append('id_vendor', idVendor);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (status) params.append('status', status);

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/laporan/retur_pembelian.php?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (json.success && json.data) {
            if (json.data.vendors && allVendorsList.length === 0) {
                allVendorsList = json.data.vendors;
                filterVendorList('');
            }

            renderTable(json.data.items || [], json.data.pagination);
            renderPaginationControls(json.data.pagination);
        } else {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger align-middle">${json.message || 'Gagal memuat data.'}</td></tr>`;
            pagContainer.style.setProperty('display', 'none', 'important');
        }
    } catch (err) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-5 text-danger align-middle">Terjadi kesalahan: ${err.message}</td></tr>`;
        pagContainer.style.setProperty('display', 'none', 'important');
    }
}

function renderTable(items, pagination) {
    const tbody = document.getElementById('returReportTableBody');
    const pagContainer = document.getElementById('paginationContainer');

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5 text-muted align-middle">
                    <i class="bi bi-inbox fs-2 text-muted d-block mb-2"></i>
                    Tidak ada data retur pembelian yang sesuai filter.
                </td>
            </tr>
        `;
        pagContainer.style.setProperty('display', 'none', 'important');
        return;
    }

    let html = '';
    const startIndex = pagination ? (pagination.per_page * (pagination.current_page - 1)) : 0;

    items.forEach((it, idx) => {
        const no = startIndex + idx + 1;
        // Format Vendor: Nama Vendor [kode_vendor]
        const vendorText = `${escapeHtml(it.nama_vendor || '-')} <span class="text-muted font-monospace">[${escapeHtml(it.kode_vendor || '-')}]</span>`;
        // Format Kompensasi teks biasa
        const kompensasiText = (parseInt(it.kompensasi, 10) === 1) ? 'Tukar Unit' : 'Potong Tagihan';
        // Status sesuai enum murni (tanpa badge / improve)
        const statusText = escapeHtml(it.status || '-');

        html += `
            <tr class="align-middle">
                <!-- 1. No -->
                <td class="ps-3 text-center text-muted font-monospace small">${no}</td>

                <!-- 2. Vendor: PT ABC [kode_vendor] -->
                <td>
                    <span class="fw-semibold text-dark">${vendorText}</span>
                </td>

                <!-- 3. Kompensasi -->
                <td class="text-center text-dark">
                    ${kompensasiText}
                </td>

                <!-- 4. No Retur -->
                <td>
                    <span class="font-monospace fw-bold text-dark">${escapeHtml(it.nomor_po_retur || '-')}</span>
                </td>

                <!-- 5. Status: Sesuai ENUM Murni -->
                <td class="text-center text-dark">
                    ${statusText}
                </td>

                <!-- 6. Aksi: Cukup Icon Mata Saja -->
                <td class="text-center pe-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" title="Lihat Detail" onclick="showDetailModal(${it.id_po_retur})" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="bi bi-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    pagContainer.style.removeProperty('display');
    pagContainer.style.display = 'flex';
}

function renderPaginationControls(pag) {
    const info = document.getElementById('paginationInfo');
    const controls = document.getElementById('paginationControls');

    if (!pag || pag.total_items === 0) {
        info.textContent = 'Menampilkan 0 dari 0 data';
        controls.innerHTML = '';
        return;
    }

    const startItem = (pag.current_page - 1) * pag.per_page + 1;
    const endItem = Math.min(pag.current_page * pag.per_page, pag.total_items);
    info.textContent = `Menampilkan ${startItem} - ${endItem} dari ${pag.total_items} data (Total: ${pag.total_pages} Halaman)`;

    if (pag.total_pages <= 1) {
        controls.innerHTML = '';
        return;
    }

    let html = `
        <li class="page-item ${pag.current_page <= 1 ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.current_page - 1})">&laquo; Prev</a>
        </li>
    `;
    
    const startPage = Math.max(1, pag.current_page - 2);
    const endPage = Math.min(pag.total_pages, pag.current_page + 2);
    
    if (startPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(1)">1</a></li>`;
        if (startPage > 2) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
    }
    
    for (let p = startPage; p <= endPage; p++) {
        html += `
            <li class="page-item ${p === pag.current_page ? 'active' : ''}">
                <a class="page-link" href="javascript:void(0)" onclick="goToPage(${p})">${p}</a>
            </li>
        `;
    }
    
    if (endPage < pag.total_pages) {
        if (endPage < pag.total_pages - 1) html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        html += `<li class="page-item"><a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.total_pages})">${pag.total_pages}</a></li>`;
    }
    
    html += `
        <li class="page-item ${pag.current_page >= pag.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.current_page + 1})">Next &raquo;</a>
        </li>
    `;

    controls.innerHTML = html;
}

// -------------------------------------------------------------
// SHOW DETAIL POPUP MODAL
// -------------------------------------------------------------
async function showDetailModal(idRetur) {
    try {
        const res = await fetch(`<?= BASE_URL ?>/api/laporan/retur_pembelian.php?id_po_retur=${idRetur}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const json = await res.json();

        if (json.success && json.data) {
            const d = json.data;

            document.getElementById('modalHeaderNoRetur').textContent = d.nomor_po_retur || '-';
            // Status murni tanpa badge improve
            document.getElementById('modalHeaderStatusBadge').textContent = d.status || '-';

            document.getElementById('modalVendorNama').textContent = d.nama_vendor || '-';
            document.getElementById('modalVendorKode').textContent = `Kode: ${d.kode_vendor || '-'} | Telp: ${d.telepon_vendor || '-'}`;
            document.getElementById('modalNoPo').textContent = d.nomor_po ? `${d.nomor_po} (${formatShortDate(d.tanggal_po)})` : '-';
            document.getElementById('modalNoRcv').textContent = d.nomor_rcv ? `${d.nomor_rcv} / SJ: ${d.nomor_sj_rcv || '-'}` : '-';

            document.getElementById('modalTanggalRetur').textContent = d.tanggal_formatted || '-';
            document.getElementById('modalKompensasiBadge').textContent = (parseInt(d.kompensasi, 10) === 1) ? 'Tukar Unit' : 'Potong Tagihan';
            document.getElementById('modalSite').textContent = d.nama_site || '-';
            document.getElementById('modalPembuat').textContent = `${d.nama_pembuat || '-'} (Penyetuju: ${d.nama_penyetuju || '-'})`;

            document.getElementById('modalKeterangan').textContent = d.keterangan || 'Tidak ada catatan tambahan.';

            // Tombol Cetak SPB di Modal
            const btnSpb = document.getElementById('btnModalPrintSpb');
            if (['DISETUJUI VENDOR', 'DIKIRIM KE VENDOR', 'DITERIMA'].includes(d.status)) {
                btnSpb.href = `<?= BASE_URL ?>/admin/pages/retur_po/print_spb.php?id=${d.id_po_retur}`;
                btnSpb.style.display = 'inline-block';
            } else {
                btnSpb.style.display = 'none';
            }

            // Items Table
            const tItems = document.getElementById('modalTableItemsBody');
            document.getElementById('modalTotalItemBadge').textContent = `${(d.items || []).length} Item`;

            if (d.items && d.items.length > 0) {
                let itHtml = '';
                d.items.forEach((it, idx) => {
                    itHtml += `
                        <tr>
                            <td class="text-center text-muted small">${idx + 1}</td>
                            <td class="font-monospace fw-semibold text-primary">${escapeHtml(it.kode_barang || '-')}</td>
                            <td>
                                <div class="fw-bold text-dark">${escapeHtml(it.nama_barang || '-')}</div>
                                ${it.keterangan_kerusakan ? `<div class="text-muted small fst-italic">${escapeHtml(it.keterangan_kerusakan)}</div>` : ''}
                            </td>
                            <td class="font-monospace small text-center">${escapeHtml(it.serial_number || '-')}</td>
                            <td class="text-end font-monospace fw-bold fs-6 text-dark">${Number(it.qty_retur || 0).toLocaleString('id-ID')} <span class="small text-muted">${escapeHtml(it.satuan || 'PCS')}</span></td>
                            <td>
                                <span class="text-dark small">${formatAlasanRetur(it.alasan_retur)}</span>
                            </td>
                        </tr>
                    `;
                });
                tItems.innerHTML = itHtml;
            } else {
                tItems.innerHTML = `<tr><td colspan="6" class="text-center py-3 text-muted small">Tidak ada rincian barang retur.</td></tr>`;
            }

            // Reset Tab ke Tab 1 (Dokumen & Vendor)
            const firstTabEl = document.getElementById('modal-tab-retur-dokumen');
            if (firstTabEl) {
                const tabInstance = bootstrap.Tab.getOrCreateInstance(firstTabEl);
                tabInstance.show();
            }

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailRetur'));
            modal.show();

        } else {
            alert(json.message || 'Gagal mengambil rincian retur.');
        }
    } catch (err) {
        alert('Terjadi kesalahan: ' + err.message);
    }
}

// -------------------------------------------------------------
// PRINT REPORT ACTION
// -------------------------------------------------------------
function printReport() {
    const idVendor = document.getElementById('filterVendor').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;
    const status = document.getElementById('filterStatus').value;

    const params = new URLSearchParams();
    if (idVendor) params.append('id_vendor', idVendor);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (status) params.append('status', status);

    window.open(`<?= BASE_URL ?>/admin/pages/laporan/print_retur_pembelian.php?${params.toString()}`, '_blank');
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
    loadReport(1);
});
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
