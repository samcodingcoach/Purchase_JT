<?php
/**
 * Halaman Daftar Transaksi Stock Adjustment
 * Path: admin/pages/adjustment_stok/index.php
 * Khusus Role: ADMIN, LOGISTIK, MEKANIK, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_MEKANIK, ROLE_MANAGER]);

$pageTitle = 'Stock Adjustment';
$pageHeading = 'Penyesuaian Stok Barang';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-0">Penyesuaian Stok (Stock Adjustment)</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/adjustment_stok/create.php" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Buat Adjustment
            </a>
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="loadAdjustmentList(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>



    <!-- FILTER BAR -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 adjustment-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-md-3" style="min-width: 240px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari No. ADJ / Alasan / Pembuat..." oninput="debounceSearch()">
                    </div>
                </div>

                <!-- Site / Lokasi -->
                <div class="col-md-3 col-lg-2" style="min-width: 210px;">
                    <select class="form-select form-select-sm" id="filterSite" onchange="loadAdjustmentList(1)">
                        <option value="0">Semua Site / Gudang</option>
                    </select>
                </div>

                <!-- Status Filter -->
                <div class="col-md-3 col-lg-2" style="min-width: 200px;">
                    <select class="form-select form-select-sm" id="filterStatus" onchange="loadAdjustmentList(1)">
                        <option value="">Semua Status</option>
                        <option value="DRAFT">DRAFT</option>
                        <option value="PENDING">MENUNGGU APPROVAL</option>
                        <option value="APPROVED">DISETUJUI (APPROVED)</option>
                        <option value="REJECTED">DITOLAK (REJECTED)</option>
                        <option value="BATAL">BATAL</option>
                    </select>
                </div>

                <!-- Jenis Adjustment -->
                <div class="col-md-3 col-lg-2" style="min-width: 190px;">
                    <select class="form-select form-select-sm" id="filterJenis" onchange="loadAdjustmentList(1)">
                        <option value="">Semua Jenis</option>
                        <option value="SET_STOK">SET STOK (OPNAME)</option>
                        <option value="PENAMBAHAN">PENAMBAHAN</option>
                        <option value="PENGURANGAN">PENGURANGAN</option>
                    </select>
                </div>

                <!-- Reset Button -->
                <div class="col-auto ms-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 d-flex align-items-center justify-content-center" onclick="resetFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .adjustment-filter-bar .form-control,
    .adjustment-filter-bar .form-select,
    .adjustment-filter-bar .input-group-text,
    .adjustment-filter-bar .btn {
        height: 38px;
        font-size: 0.85rem;
    }
    .adjustment-filter-bar .input-group-text {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-left: 12px;
        padding-right: 12px;
    }
    .adjustment-filter-bar .form-select {
        padding-top: 0.25rem !important;
        padding-bottom: 0.25rem !important;
        padding-left: 0.75rem !important;
        padding-right: 2.25rem !important;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
        line-height: 1.5 !important;
    }
    .hover-underline:hover {
        text-decoration: underline !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    </style>

    <!-- DATA TABLE -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableAdjustment">
                    <thead class="table-light">
                        <tr class="text-muted small text-uppercase align-middle">
                            <th class="ps-3 py-2 text-center" style="width: 45px;">No</th>
                            <th class="py-2" style="min-width: 190px;">No. Adjustment &amp; Tgl</th>
                            <th class="py-2" style="min-width: 170px;">Site / Gudang</th>
                            <th class="py-2" style="min-width: 170px;">Pembuat</th>
                            <th class="py-2" style="min-width: 150px;">Alasan &amp; Jenis</th>
                            <th class="py-2 text-center" style="width: 110px;">Item / Selisih</th>
                            <th class="py-2 text-center" style="width: 130px;">Status</th>
                            <th class="pe-3 py-2 text-center" style="width: 120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="adjustmentTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data adjustment stok...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- PAGINATION FOOTER -->
        <div class="card-footer bg-white border-top py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="text-muted small" id="paginationInfo">Menampilkan 0 dari 0 data</div>
            <nav aria-label="Page navigation" id="paginationNav">
                <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
            </nav>
        </div>
    </div>
</div>

<!-- MODAL RINCIAN ADJUSTMENT STOK -->
<div class="modal fade" id="modalDetailAdjustment" tabindex="-1" aria-labelledby="modalDetailAdjustmentLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="modal-title fw-bold text-dark mb-0" id="modalDetailAdjustmentLabel">Rincian Stock Adjustment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalDetailBody">
                <!-- Diisi dinamis via JS -->
            </div>
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-between" id="modalDetailFooter">
                <!-- Tombol Action Dinamis -->
            </div>
        </div>
    </div>
</div>

<script>
const USER_ROLE = '<?= $user['role'] ?>'; // 'LOGISTIK', 'MEKANIK', 'ADMIN', 'MANAGER'
let currentPage = 1;
let currentLimit = 15;
let cachedData = [];
let debounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    loadAdjustmentList(1);
});

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadAdjustmentList(1);
    }, 400);
}


function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '0';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterJenis').value = '';
    loadAdjustmentList(1);
}

function formatRupiah(num) {
    if (num === null || num === undefined || isNaN(num)) return 'Rp 0';
    return 'Rp ' + Number(num).toLocaleString('id-ID');
}

function formatDateYMD(dateStr) {
    if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00-00 00:00:00') return '-';
    const tParts = dateStr.split(' ');
    return tParts[0];
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

async function loadAdjustmentList(page = 1) {
    currentPage = page;
    const status = encodeURIComponent(document.getElementById('filterStatus').value);
    const idSite = document.getElementById('filterSite').value;
    const jenis = encodeURIComponent(document.getElementById('filterJenis').value);
    const search = encodeURIComponent(document.getElementById('filterSearch').value.trim());

    const tbody = document.getElementById('adjustmentTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data adjustment stok...
            </td>
        </tr>
    `;

    try {
        const url = `<?= BASE_URL ?>/api/adjustment_stok/index.php?page=${currentPage}&limit=${currentLimit}&status=${status}&id_site=${idSite}&jenis=${jenis}&q=${search}`;
        const res = await fetch(url);
        const json = await res.json();

        if (!json.success) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">${escapeHtml(json.message || 'Gagal memuat data.')}</td></tr>`;
            return;
        }

        const data = json.data;
        cachedData = data.rows || [];



        // Render Dropdown Sites if empty
        if (data.filter_options && data.filter_options.sites) {
            const siteSel = document.getElementById('filterSite');
            if (siteSel.options.length <= 1) {
                data.filter_options.sites.forEach(s => {
                    const opt = document.createElement('option');
                    opt.value = s.id_site;
                    opt.textContent = s.nama_site;
                    siteSel.appendChild(opt);
                });
            }
        }

        // Render Rows
        if (cachedData.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2 text-muted"></i>
                        Tidak ada transaksi stock adjustment yang sesuai dengan filter.
                    </td>
                </tr>
            `;
            renderPagination(0, 0, 1, currentLimit);
            return;
        }

        let html = '';
        const offset = (data.pagination.page - 1) * data.pagination.limit;

        cachedData.forEach((row, idx) => {
            const no = offset + idx + 1;
            
            // Status Badge
            let statusBadge = '';
            if (row.status === 'APPROVED') statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-semibold">APPROVED</span>';
            else if (row.status === 'PENDING') statusBadge = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1 fw-semibold">MENUNGGU APPROVAL</span>';
            else if (row.status === 'REJECTED') statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-semibold">DITOLAK</span>';
            else if (row.status === 'BATAL') statusBadge = '<span class="badge bg-dark-subtle text-dark border border-dark-subtle px-2 py-1 fw-semibold">BATAL</span>';
            else statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1 fw-semibold">DRAFT</span>';

            // Jenis Badge
            let jenisBadge = '';
            if (row.jenis_adjustment === 'PENAMBAHAN') jenisBadge = '<span class="badge bg-success text-white small me-1">+ TAMBAH</span>';
            else if (row.jenis_adjustment === 'PENGURANGAN') jenisBadge = '<span class="badge bg-danger text-white small me-1">- KURANG</span>';
            else jenisBadge = '<span class="badge bg-primary text-white small me-1">SET STOK</span>';

            html += `
                <tr class="align-middle">
                    <td class="ps-3 py-2 text-center text-muted fw-semibold">${no}</td>
                    <td class="py-2">
                        <div class="fw-bold font-monospace text-primary cursor-pointer hover-underline" onclick="viewDetail(${row.id_adjustment})">${escapeHtml(row.nomor_adjustment)}</div>
                        <div class="small text-muted font-monospace">${formatDateYMD(row.tanggal_adjustment)}</div>
                    </td>
                    <td class="py-2">
                        <div class="fw-semibold text-dark">${escapeHtml(row.nama_site)}</div>
                    </td>
                    <td class="py-2">
                        <div class="fw-semibold text-dark">${escapeHtml(row.nama_pembuat)}</div>
                        <div class="small text-muted">${escapeHtml(row.jabatan_pembuat)}</div>
                    </td>
                    <td class="py-2">
                        <div class="mb-1">${jenisBadge}</div>
                        <div class="small text-dark text-truncate" style="max-width: 180px;" title="${escapeHtml(row.alasan)}">${escapeHtml(row.alasan)}</div>
                    </td>
                    <td class="py-2 text-center font-monospace">
                        <span class="badge bg-light text-dark border fw-bold">${row.total_item} Item</span>
                    </td>
                    <td class="py-2 text-center">${statusBadge}</td>
                    <td class="pe-3 py-2 text-center">
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary p-0 d-inline-flex align-items-center justify-content-center" title="Lihat Rincian" onclick="viewDetail(${row.id_adjustment})" style="width: 28px; height: 28px;">
                                <i class="bi bi-eye-fill small"></i>
                            </button>
                            <a href="<?= BASE_URL ?>/admin/pages/adjustment_stok/print.php?id=${row.id_adjustment}" target="_blank" class="btn btn-sm btn-outline-secondary p-0 d-inline-flex align-items-center justify-content-center" title="Cetak Dokumen" style="width: 28px; height: 28px;">
                                <i class="bi bi-printer small"></i>
                            </a>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
        renderPagination(data.pagination.total, data.pagination.total_pages, data.pagination.page, data.pagination.limit);

    } catch (e) {
        console.error('Error load adjustments:', e);
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Terjadi kesalahan saat memuat data.</td></tr>`;
    }
}

function renderPagination(totalRecords, totalPages, curPage, perPage) {
    const info = document.getElementById('paginationInfo');
    const list = document.getElementById('paginationList');

    if (totalRecords === 0) {
        info.innerText = 'Menampilkan 0 dari 0 data';
        list.innerHTML = '';
        return;
    }

    const start = (curPage - 1) * perPage + 1;
    const end = Math.min(curPage * perPage, totalRecords);
    info.innerText = `Menampilkan ${start} - ${end} dari ${totalRecords} data`;

    if (totalPages <= 1) {
        list.innerHTML = '';
        return;
    }

    let html = '';
    html += `<li class="page-item ${curPage === 1 ? 'disabled' : ''}"><button class="page-link" onclick="loadAdjustmentList(${curPage - 1})"><i class="bi bi-chevron-left"></i></button></li>`;
    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= curPage - 2 && p <= curPage + 2)) {
            html += `<li class="page-item ${p === curPage ? 'active' : ''}"><button class="page-link" onclick="loadAdjustmentList(${p})">${p}</button></li>`;
        } else if (p === curPage - 3 || p === curPage + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }
    html += `<li class="page-item ${curPage === totalPages ? 'disabled' : ''}"><button class="page-link" onclick="loadAdjustmentList(${curPage + 1})"><i class="bi bi-chevron-right"></i></button></li>`;
    list.innerHTML = html;
}

async function viewDetail(id) {
    const body = document.getElementById('modalDetailBody');
    const footer = document.getElementById('modalDetailFooter');
    body.innerHTML = `<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat rincian adjustment...</div>`;
    footer.innerHTML = `<button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>`;

    const modal = new bootstrap.Modal(document.getElementById('modalDetailAdjustment'));
    modal.show();

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/adjustment_stok/detail.php?id=${id}`);
        const json = await res.json();
        if (!json.success || !json.data) {
            body.innerHTML = `<div class="alert alert-danger">${escapeHtml(json.message || 'Gagal memuat detail')}</div>`;
            return;
        }

        const d = json.data;

        let statusBadge = '';
        if (d.status === 'APPROVED') statusBadge = '<span class="badge bg-success px-2 py-1">APPROVED</span>';
        else if (d.status === 'PENDING') statusBadge = '<span class="badge bg-warning text-dark px-2 py-1">MENUNGGU APPROVAL</span>';
        else if (d.status === 'REJECTED') statusBadge = '<span class="badge bg-danger px-2 py-1">DITOLAK</span>';
        else if (d.status === 'BATAL') statusBadge = '<span class="badge bg-dark px-2 py-1">BATAL</span>';
        else statusBadge = '<span class="badge bg-secondary px-2 py-1">DRAFT</span>';

        let itemsHtml = '';
        (d.items || []).forEach((it, i) => {
            const diffSign = it.qty_adjustment > 0 ? `+${it.qty_adjustment}` : `${it.qty_adjustment}`;
            const diffColor = it.qty_adjustment > 0 ? 'text-success' : (it.qty_adjustment < 0 ? 'text-danger' : 'text-muted');

            itemsHtml += `
                <tr class="align-middle">
                    <td class="text-center font-monospace">${i + 1}</td>
                    <td>
                        <div class="fw-bold font-monospace text-dark">${escapeHtml(it.kode_barang || '-')}</div>
                        <div class="small text-dark">${escapeHtml(it.nama_barang)}</div>
                    </td>
                    <td class="text-center font-monospace">${it.satuan}</td>
                    <td class="text-center font-monospace fw-semibold text-muted">${it.qty_sistem}</td>
                    <td class="text-center font-monospace fw-bold text-dark">${it.qty_fisik}</td>
                    <td class="text-center font-monospace fw-bold ${diffColor}">${diffSign}</td>
                    <td class="text-center font-monospace fw-bold text-primary">${it.qty_akhir}</td>
                    <td class="text-end font-monospace text-muted">${formatRupiah(it.harga_satuan)}</td>
                    <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(it.subtotal_adjustment)}</td>
                </tr>
            `;
        });

        body.innerHTML = `
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card bg-light border-0 rounded-3 p-3 h-100">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Dokumen</h6>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Nomor Adjustment:</span>
                            <strong class="text-primary font-monospace fs-6">${escapeHtml(d.nomor_adjustment)}</strong>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Tanggal Adjustment:</span>
                            <span class="text-dark font-monospace">${formatDateYMD(d.tanggal_adjustment)}</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Site / Lokasi Gudang:</span>
                            <strong class="text-dark">${escapeHtml(d.nama_site)}</strong>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Jenis Penyesuaian:</span>
                            <span class="badge bg-primary px-2 py-1">${escapeHtml(d.jenis_adjustment)}</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card bg-light border-0 rounded-3 p-3 h-100">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Otorisasi &amp; Status</h6>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Dibuat Oleh:</span>
                            <strong class="text-dark">${escapeHtml(d.nama_pembuat)}</strong>
                            <small class="text-muted">(${escapeHtml(d.jabatan_pembuat)})</small>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Status Dokumen:</span>
                            <div>${statusBadge}</div>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small d-block">Disetujui Oleh:</span>
                            <span class="text-dark fw-semibold">${escapeHtml(d.nama_approver || '-')}</span>
                            ${d.tanggal_approved ? `<small class="text-muted font-monospace">(${formatDateYMD(d.tanggal_approved)})</small>` : ''}
                        </div>
                        <div>
                            <span class="text-muted small d-block">Alasan / Catatan:</span>
                            <span class="text-dark">${escapeHtml(d.alasan)} ${d.keterangan ? `<br><small class="text-muted">${escapeHtml(d.keterangan)}</small>` : ''}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border rounded-3 overflow-hidden">
                <div class="card-header bg-light py-2">
                    <h6 class="fw-bold text-dark mb-0 small text-uppercase">Daftar Barang Penyesuaian (${(d.items || []).length} Item)</h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                        <thead class="table-light text-muted small text-uppercase">
                            <tr>
                                <th style="width: 40px;" class="text-center">No</th>
                                <th>Kode &amp; Nama Barang</th>
                                <th style="width: 70px;" class="text-center">Satuan</th>
                                <th style="width: 90px;" class="text-center">Stok Sistem</th>
                                <th style="width: 90px;" class="text-center">Stok Fisik</th>
                                <th style="width: 90px;" class="text-center">Selisih</th>
                                <th style="width: 90px;" class="text-center text-primary">Stok Akhir</th>
                                <th style="width: 120px;" class="text-end">Estimasi Harga</th>
                                <th style="width: 130px;" class="text-end">Total Selisih</th>
                            </tr>
                        </thead>
                        <tbody>${itemsHtml}</tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="8" class="text-end py-2 pe-3 text-uppercase">Grand Total Nilai Selisih:</td>
                                <td class="text-end font-monospace py-2 text-dark">${formatRupiah(d.total_nilai_adjustment)}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        `;

        // Action Buttons in Footer
        let buttonsHtml = `
            <div>
                <a href="<?= BASE_URL ?>/admin/pages/adjustment_stok/print.php?id=${d.id_adjustment}" target="_blank" class="btn btn-outline-dark btn-sm px-3 fw-semibold">
                    <i class="bi bi-printer me-1"></i> Cetak Berita Acara
                </a>
            </div>
            <div class="d-flex gap-2">
        `;

        // Tombol Ajukan (DRAFT -> PENDING)
        if (d.status === 'DRAFT') {
            buttonsHtml += `
                <button type="button" class="btn btn-warning btn-sm px-3 fw-semibold text-dark" onclick="processAction(${d.id_adjustment}, 'SUBMIT')">
                    <i class="bi bi-send-fill me-1"></i> Ajukan Approval
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="processAction(${d.id_adjustment}, 'BATAL')">
                    Batalkan
                </button>
            `;
        }

        // TOMBOL APPROVE & REJECT: KHUSUS ROLE LOGISTIK & ADMIN
        if (d.status === 'PENDING' && (USER_ROLE === 'LOGISTIK' || USER_ROLE === 'ADMIN')) {
            buttonsHtml += `
                <button type="button" class="btn btn-danger btn-sm px-3 fw-semibold" onclick="processAction(${d.id_adjustment}, 'REJECT')">
                    <i class="bi bi-x-circle me-1"></i> Tolak (Reject)
                </button>
                <button type="button" class="btn btn-success btn-sm px-3 fw-semibold" onclick="processAction(${d.id_adjustment}, 'APPROVE')">
                    <i class="bi bi-check-circle me-1"></i> Setujui (Approve &amp; Update Stok)
                </button>
            `;
        } else if (d.status === 'PENDING' && USER_ROLE === 'MEKANIK') {
            buttonsHtml += `<span class="badge bg-warning-subtle text-warning-emphasis border p-2 small align-self-center"><i class="bi bi-info-circle me-1"></i>Menunggu persetujuan Divisi Logistik</span>`;
        }

        buttonsHtml += `<button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button></div>`;
        footer.innerHTML = buttonsHtml;

    } catch (e) {
        console.error('Error detail adjustment:', e);
        body.innerHTML = `<div class="alert alert-danger">Terjadi kesalahan saat memuat detail.</div>`;
    }
}

async function processAction(id, actionType) {
    let confirmMsg = '';
    if (actionType === 'APPROVE') confirmMsg = 'Apakah Anda yakin ingin MENYETUJUI penyesuaian stok ini? Saldo stok fisik di gudang akan langsung diperbarui otomatis.';
    else if (actionType === 'REJECT') confirmMsg = 'Apakah Anda yakin ingin MENOLAK dokumen penyesuaian stok ini?';
    else if (actionType === 'SUBMIT') confirmMsg = 'Ajukan dokumen penyesuaian stok ini ke Divisi Logistik untuk diperiksa?';
    else if (actionType === 'BATAL') confirmMsg = 'Batalkan dokumen penyesuaian stok ini?';

    if (!confirm(confirmMsg)) return;

    let catatan = '';
    if (actionType === 'REJECT' || actionType === 'BATAL') {
        catatan = prompt('Masukkan alasan / catatan:', '') || '';
    }

    try {
        const res = await fetch('<?= BASE_URL ?>/api/adjustment_stok/action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_adjustment: id, action: actionType, catatan: catatan })
        });
        const json = await res.json();
        alert(json.message);
        if (json.success) {
            const modalEl = document.getElementById('modalDetailAdjustment');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
            loadAdjustmentList(currentPage);
        }
    } catch (e) {
        alert('Terjadi kesalahan: ' + e.message);
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
