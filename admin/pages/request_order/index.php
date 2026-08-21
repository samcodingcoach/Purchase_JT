<?php
/**
 * Halaman Daftar Request Order (RO)
 * Path: admin/pages/request_order/index.php
 * Decoupled Architecture: All data communication via /api/request_order/index.php
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Daftar Request Order';
$pageHeading = 'Daftar Request Order (RO)';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<!-- KONTEN UTAMA -->
<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">
            <i class="bi bi-file-earmark-text text-primary me-2"></i>Daftar Request Order (RO)
        </h4>
    </div>

    <!-- FILTER & PENCARIAN -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 ro-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-3 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari No. RO / Pemohon..." autocomplete="off">
                    </div>
                </div>

                <!-- Site Filter (Lebar ditambah) -->
                <div class="col-md-3 col-lg-3" style="min-width: 210px;">
                    <select class="form-select form-select-sm" id="filterSite">
                        <option value="">Semua Site / Lokasi</option>
                    </select>
                </div>

                <!-- Range Tanggal: Dari Tanggal (Lebar ditambah) -->
                <div class="col-6 col-md-2" style="min-width: 200px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted small" style="width: 58px; justify-content: center;">Dari</span>
                        <input type="date" class="form-control form-control-sm" id="filterStartDate" title="Dari Tanggal">
                    </div>
                </div>

                <!-- Range Tanggal: Sampai Tanggal (Lebar ditambah) -->
                <div class="col-6 col-md-2" style="min-width: 200px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light text-muted small" style="width: 58px; justify-content: center;">Sampai</span>
                        <input type="date" class="form-control form-control-sm" id="filterEndDate" title="Sampai Tanggal">
                    </div>
                </div>

                <!-- Tombol Reset (Rata Kanan) -->
                <div class="col-auto ms-auto">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="resetFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .ro-filter-bar .form-control,
    .ro-filter-bar .form-select,
    .ro-filter-bar .input-group-text,
    .ro-filter-bar .btn {
        height: 36px;
        font-size: 0.85rem;
    }
    .table-container {
        overflow: visible !important;
        position: relative;
    }
    </style>

    <!-- TABEL DATA REQUEST ORDER -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-container" style="overflow: visible; position: relative;">
                <table class="table table-hover align-middle mb-0" id="roTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 140px;">Nomor RO</th>
                            <th style="width: 120px;">Tanggal</th>
                            <th style="width: 100px;">Waktu</th>
                            <th style="min-width: 160px;">Pemohon</th>
                            <th style="min-width: 140px;">Site</th>
                            <th style="width: 160px;" class="text-center">Status</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="roTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Request Order...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- FOOTER PAGINATION -->
        <div class="card-footer bg-white border-0 py-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="text-muted small" id="paginationInfo">Menampilkan 0 data</div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL REQUEST ORDER -->
<div class="modal fade" id="modalDetailRo" tabindex="-1" aria-labelledby="modalDetailRoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light py-3">
                <div>
                    <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="modalDetailRoLabel">
                        <i class="bi bi-file-text-fill text-primary"></i>
                        <span id="detailNomorRo">RO-XXXX-XXXX</span>
                    </h5>
                    <div class="text-muted small" id="detailHeaderSub">Informasi Rincian Request Order</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Info Grid -->
                <div class="card bg-light border-0 rounded-3 p-3 mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted small d-block">Tanggal Pengajuan:</span>
                                <strong class="text-dark" id="detailTanggalRo">-</strong>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block">Pemohon:</span>
                                <strong class="text-dark" id="detailPemohon">-</strong>
                                <div class="text-muted" style="font-size: 0.75rem;" id="detailJabatanDivisi">-</div>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Referensi Vendor Rekanan:</span>
                                <span class="text-dark fw-semibold" id="detailVendor">-</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted small d-block">Site / Workshop Tujuan:</span>
                                <span class="badge bg-secondary-subtle text-secondary px-2 py-1 fs-6" id="detailSite">-</span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted small d-block">Status & Prioritas:</span>
                                <div class="d-flex gap-2 align-items-center mt-1">
                                    <span id="detailBadgeStatus">-</span>
                                    <span id="detailBadgePrioritas">-</span>
                                </div>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Keperluan / Catatan:</span>
                                <div class="text-dark small fst-italic" id="detailKeterangan">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabel Rincian Material Barang -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-box-seam text-primary me-1"></i> Rincian Material / Barang</h6>
                    <span class="badge bg-primary" id="detailBadgeItemCount">0 Item</span>
                </div>
                <div class="table-responsive border rounded-3 bg-white mb-3">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr>
                                <th style="width: 40px;" class="text-center">No</th>
                                <th>Nama Barang / Material</th>
                                <th style="width: 140px;">Kode</th>
                                <th style="width: 110px;" class="text-center">Qty</th>
                                <th style="width: 100px;">Satuan</th>
                            </tr>
                        </thead>
                        <tbody id="detailItemsTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- JAVASCRIPT LOGIC (DECOUPLED VIA RESTFUL API) -->
<script>
const CURRENT_USER_ROLE = <?= json_encode($user['role']) ?>;
const CURRENT_USER_ID_KARYAWAN = <?= json_encode($user['id_karyawan'] ?? 0) ?>;
let currentPage = 1;
let currentLimit = 15;
let searchDebounceTimer = null;
let masterSiteList = [];

document.addEventListener('DOMContentLoaded', async () => {
    await loadMasterSites();
    await loadRequestOrders();

    // Event listeners
    const searchInput = document.getElementById('filterSearch');
    searchInput.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            currentPage = 1;
            loadRequestOrders();
        }, 300);
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            clearTimeout(searchDebounceTimer);
            currentPage = 1;
            loadRequestOrders();
        }
    });

    document.getElementById('filterSite').addEventListener('change', () => { currentPage = 1; loadRequestOrders(); });
    document.getElementById('filterStartDate').addEventListener('change', () => { currentPage = 1; loadRequestOrders(); });
    document.getElementById('filterEndDate').addEventListener('change', () => { currentPage = 1; loadRequestOrders(); });
});

// -------------------------------------------------------------
// 1. LOAD MASTER SITES
// -------------------------------------------------------------
async function loadMasterSites() {
    const res = await apiRequest('/api/master/site.php?limit=100');
    if (res && res.success) {
        masterSiteList = res.data.items || [];
        const select = document.getElementById('filterSite');
        masterSiteList.forEach(s => {
            select.innerHTML += `<option value="${s.id_site}">${s.nama_site} (${s.kode_site})</option>`;
        });
    }
}

// -------------------------------------------------------------
// 2. LOAD LIST REQUEST ORDER
// -------------------------------------------------------------
async function loadRequestOrders(page = currentPage) {
    currentPage = page;
    const tbody = document.getElementById('roTableBody');
    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Request Order...</td></tr>`;

    const search = document.getElementById('filterSearch').value.trim();
    const siteId = document.getElementById('filterSite').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    let url = `/api/request_order/index.php?page=${currentPage}&limit=${currentLimit}`;
    if (search) url += `&q=${encodeURIComponent(search)}`;
    if (siteId) url += `&site_id=${encodeURIComponent(siteId)}`;
    if (startDate) url += `&start_date=${encodeURIComponent(startDate)}`;
    if (endDate) url += `&end_date=${encodeURIComponent(endDate)}`;

    const res = await apiRequest(url);

    if (res && res.success) {
        renderTableRows(res.data.items || [], (currentPage - 1) * currentLimit);
        renderPagination(res.data.pagination || {});
    } else {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-1"></i> Gagal memuat data. Silakan coba lagi.</td></tr>`;
    }
}

function renderTableRows(items, offset) {
    const tbody = document.getElementById('roTableBody');
    if (!items || items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-inbox fs-3 d-block mb-2 text-secondary"></i>Belum ada data Request Order yang sesuai dengan filter.</td></tr>`;
        return;
    }

    let html = '';
    items.forEach((ro, idx) => {
        const no = offset + idx + 1;
        
        // Status Badge
        let statusBadge = '';
        if (ro.status === 'DRAFT') {
            statusBadge = `<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="bi bi-pencil me-1"></i>Draft</span>`;
        } else if (ro.status === 'TERKIRIM') {
            statusBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Menunggu Logistik</span>`;
        } else if (ro.status === 'DISETUJUI') {
            statusBadge = `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Disetujui</span>`;
        } else if (ro.status === 'TIDAK DISETUJUI') {
            statusBadge = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>Ditolak</span>`;
        } else if (ro.status === 'BATAL') {
            statusBadge = `<span class="badge bg-dark-subtle text-dark border px-2 py-1"><i class="bi bi-slash-circle me-1"></i>Batal</span>`;
        }

        // Format Tanggal & Waktu Terpisah
        let tanggalStr = '-';
        let waktuStr = '-';
        if (ro.tanggal_ro) {
            const d = new Date(ro.tanggal_ro.replace(/-/g, '/'));
            if (!isNaN(d.getTime())) {
                const day = String(d.getDate()).padStart(2, '0');
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const year = d.getFullYear();
                const hours = String(d.getHours()).padStart(2, '0');
                const minutes = String(d.getMinutes()).padStart(2, '0');
                const seconds = String(d.getSeconds()).padStart(2, '0');
                tanggalStr = `${day}/${month}/${year}`;
                waktuStr = `${hours}:${minutes}:${seconds}`;
            }
        }

        // Cek Hak Edit:
        // - DRAFT: Hanya pembuatnya sendiri (atau Admin)
        // - TERKIRIM: Pembuatnya sendiri ATAU tim Logistik, Purchasing, Manager, Admin
        const isOwner = (ro.id_karyawan == CURRENT_USER_ID_KARYAWAN);
        const isStaffLogistikOrAdmin = ['LOGISTIK', 'PURCHASING', 'MANAGER', 'ADMIN'].includes(CURRENT_USER_ROLE.toUpperCase());

        let canEdit = false;
        if (ro.status === 'DRAFT') {
            canEdit = isOwner || CURRENT_USER_ROLE.toUpperCase() === 'ADMIN';
        } else if (ro.status === 'TERKIRIM') {
            canEdit = isOwner || isStaffLogistikOrAdmin;
        }

        html += `
            <tr>
                <td class="text-center text-muted fw-semibold small">${no}</td>
                <td>
                    <span class="fw-bold text-dark font-monospace">${ro.nomor}</span>
                </td>
                <td class="small text-dark font-monospace">${tanggalStr}</td>
                <td class="small text-muted font-monospace">${waktuStr}</td>
                <td>
                    <span class="fw-semibold text-dark small">${ro.nama_karyawan}</span>
                </td>
                <td>
                    <span class="badge bg-light text-dark border font-monospace">${ro.nama_site}</span>
                </td>
                <td class="text-center">${statusBadge}</td>
                <td class="text-center">
                    <div class="d-flex justify-content-center align-items-center gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1" onclick="viewDetailRo(${ro.id_request})" title="Lihat Detail Dokumen">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        ${canEdit ? `
                            <a href="${BASE_URL}/admin/pages/request_order/edit.php?id=${ro.id_request}" class="btn btn-outline-warning btn-sm px-2 py-1" title="Edit / Update RO">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                        ` : ''}
                        ${(ro.status === 'DRAFT' && (isOwner || CURRENT_USER_ROLE.toUpperCase() === 'ADMIN')) ? `
                            <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1" onclick="deleteDraftRo(${ro.id_request}, '${ro.nomor}')" title="Hapus Draft">
                                <i class="bi bi-trash-fill"></i>
                            </button>
                        ` : ''}
                        ${(ro.status === 'TERKIRIM' && (isOwner || isStaffLogistikOrAdmin)) ? `
                            <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1" onclick="cancelRo(${ro.id_request}, '${ro.nomor}')" title="Batalkan RO">
                                <i class="bi bi-x-circle-fill"></i>
                            </button>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// -------------------------------------------------------------
// 4. DETAIL REQUEST ORDER MODAL
// -------------------------------------------------------------
async function viewDetailRo(idRequest) {
    const res = await apiRequest(`/api/request_order/index.php?id=${idRequest}`);
    if (!res || !res.success) {
        showToast('Gagal memuat detail Request Order.', 'danger');
        return;
    }

    const ro = res.data;
    document.getElementById('detailNomorRo').textContent = ro.nomor;
    document.getElementById('detailTanggalRo').textContent = ro.tanggal_ro;
    document.getElementById('detailPemohon').textContent = `${ro.nama_karyawan} (${ro.kode_karyawan || 'KRY'})`;
    document.getElementById('detailJabatanDivisi').textContent = `${ro.nama_jabatan || '-'} &bull; ${ro.nama_divisi || '-'}`;
    document.getElementById('detailSite').textContent = `${ro.nama_site} (${ro.kode_site})`;
    document.getElementById('detailVendor').textContent = ro.nama_vendor ? `${ro.nama_vendor} (${ro.kode_vendor || '-'})` : 'Tidak Ada (Umum)';
    document.getElementById('detailKeterangan').textContent = ro.keterangan || 'Tidak ada catatan khusus.';

    // Status Badge
    let statusBadge = '';
    if (ro.status === 'DRAFT') statusBadge = `<span class="badge bg-secondary">Draft</span>`;
    else if (ro.status === 'TERKIRIM') statusBadge = `<span class="badge bg-warning text-dark">Menunggu Logistik</span>`;
    else if (ro.status === 'DISETUJUI') statusBadge = `<span class="badge bg-success">Disetujui</span>`;
    else if (ro.status === 'TIDAK DISETUJUI') statusBadge = `<span class="badge bg-danger">Ditolak</span>`;
    else if (ro.status === 'BATAL') statusBadge = `<span class="badge bg-dark">Batal</span>`;
    document.getElementById('detailBadgeStatus').innerHTML = statusBadge;

    // Prioritas Badge
    const prioritasBadge = (ro.prioritas === 'URGENT') 
        ? `<span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent</span>`
        : `<span class="badge bg-primary"><i class="bi bi-check-circle me-1"></i>Normal</span>`;
    document.getElementById('detailBadgePrioritas').innerHTML = prioritasBadge;

    // Render Items
    document.getElementById('detailBadgeItemCount').textContent = `${ro.total_items} Jenis Item (${ro.total_qty} Kuantitas)`;
    const tbodyItems = document.getElementById('detailItemsTableBody');
    let itemsHtml = '';

    if (ro.items && ro.items.length > 0) {
        ro.items.forEach((item, idx) => {
            const imgSrc = item.foto1 ? `${BASE_URL}/${item.foto1}` : '';
            const imgHtml = imgSrc 
                ? `<img src="${imgSrc}" class="rounded border me-2 flex-shrink-0" style="width: 36px; height: 36px; object-fit: cover;">` 
                : `<div class="rounded border bg-light text-secondary d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 36px; height: 36px;"><i class="bi bi-box-seam"></i></div>`;

            itemsHtml += `
                <tr>
                    <td class="text-center text-muted fw-bold small">${idx + 1}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${imgHtml}
                            <div>
                                <div class="fw-bold text-dark small">${item.nama_barang}</div>
                                <div class="text-muted" style="font-size: 0.72rem;">
                                    <span class="badge bg-secondary-subtle text-secondary me-1">${item.nama_merk || 'Umum'}</span>
                                    <span class="badge bg-primary-subtle text-primary">${item.nama_kategori || 'Material'}</span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="font-monospace small">${item.kode_barang || '-'}</td>
                    <td class="text-center fw-bold text-dark font-monospace">${item.qty}</td>
                    <td class="small"><span class="badge bg-light text-dark border">${item.satuan}</span></td>
                </tr>
            `;
        });
    } else {
        itemsHtml = `<tr><td colspan="5" class="text-center py-3 text-muted">Tidak ada rincian material.</td></tr>`;
    }
    tbodyItems.innerHTML = itemsHtml;

    const modal = new bootstrap.Modal(document.getElementById('modalDetailRo'));
    modal.show();
}

// -------------------------------------------------------------
// 5. HAPUS & BATALKAN RO
// -------------------------------------------------------------
async function deleteDraftRo(idRequest, nomorRo) {
    if (!confirm(`Apakah Anda yakin ingin menghapus Draft Request Order "${nomorRo}"?`)) return;

    const res = await apiRequest(`/api/request_order/index.php?id=${idRequest}`, {
        method: 'DELETE'
    });
    if (res && res.success) {
        showToast(res.message, 'success');
        loadRequestOrders();
    } else {
        showToast(res ? res.message : 'Gagal menghapus draft RO.', 'danger');
    }
}

async function cancelRo(idRequest, nomorRo) {
    if (!confirm(`Apakah Anda yakin ingin membatalkan Request Order "${nomorRo}"?`)) return;

    const res = await apiRequest(`/api/request_order/index.php?action=cancel`, {
        method: 'POST',
        body: JSON.stringify({ id_request: idRequest })
    });
    if (res && res.success) {
        showToast(res.message, 'success');
        loadRequestOrders();
    } else {
        showToast(res ? res.message : 'Gagal membatalkan RO.', 'danger');
    }
}

// -------------------------------------------------------------
// 6. PAGINATION & RESET FILTERS
// -------------------------------------------------------------
function renderPagination(p) {
    const info = document.getElementById('paginationInfo');
    const list = document.getElementById('paginationList');
    if (!p || p.total_records === 0) {
        info.textContent = 'Menampilkan 0 data';
        list.innerHTML = '';
        return;
    }

    const start = (p.current_page - 1) * p.limit + 1;
    const end = Math.min(p.current_page * p.limit, p.total_records);
    info.textContent = `Menampilkan ${start} - ${end} dari ${p.total_records} data`;

    let html = '';
    html += `<li class="page-item ${p.current_page === 1 ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadRequestOrders(${p.current_page - 1})"><i class="bi bi-chevron-left"></i></a></li>`;
    
    for (let i = 1; i <= p.total_pages; i++) {
        if (i === 1 || i === p.total_pages || (i >= p.current_page - 1 && i <= p.current_page + 1)) {
            html += `<li class="page-item ${i === p.current_page ? 'active' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadRequestOrders(${i})">${i}</a></li>`;
        } else if (i === p.current_page - 2 || i === p.current_page + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${p.current_page === p.total_pages ? 'disabled' : ''}"><a class="page-link" href="javascript:void(0)" onclick="loadRequestOrders(${p.current_page + 1})"><i class="bi bi-chevron-right"></i></a></li>`;
    list.innerHTML = html;
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    currentPage = 1;
    loadRequestOrders();
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
