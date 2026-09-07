<?php
/**
 * Halaman Daftar Purchase Order (PO)
 * Path: admin/pages/purchase_order/index.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Khusus Purchasing, Manager, dan Admin (Logistik diblokir)
$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Daftar Purchase Order (PO)';
$pageHeading = 'Purchase Order (PO)';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<!-- KONTEN UTAMA -->
<div class="container-fluid px-0">
    <!-- HEADER HALAMAN -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Purchase Order (PO)</h4>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" onclick="loadPoList(1)">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh Data
            </button>
        </div>
    </div>

    <!-- FILTER & PENCARIAN -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 po-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-3 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterSearch" placeholder="Cari No. PO / Vendor / Pembuat..." autocomplete="off">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="col-md-2 col-lg-2">
                    <select class="form-select form-select-sm" id="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="DRAFT">Draft</option>
                        <option value="REVIEW INTERNAL">Review Internal</option>
                        <option value="DISETUJUI INTERNAL">Disetujui Internal</option>
                        <option value="DIPROSES VENDOR">Diproses Vendor</option>
                        <option value="BATAL">Batal</option>
                    </select>
                </div>

                <!-- Site Filter -->
                <div class="col-md-2 col-lg-2">
                    <select class="form-select form-select-sm" id="filterSite">
                        <option value="">Semua Site / Lokasi</option>
                    </select>
                </div>

                <!-- Range Tanggal: Dari Tanggal -->
                <div class="col-6 col-md-2" style="min-width: 170px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Dari Tanggal" onclick="const el=document.getElementById('filterStartDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterStartDate" placeholder="mm / dd / yyyy" title="Dari Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="applyFilters()">
                    </div>
                </div>

                <!-- Range Tanggal: Sampai Tanggal -->
                <div class="col-6 col-md-2" style="min-width: 170px;">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted cursor-pointer" title="Sampai Tanggal" onclick="const el=document.getElementById('filterEndDate'); el.type='date'; el.showPicker?.(); el.focus();"><i class="bi bi-calendar-check"></i></span>
                        <input type="text" class="form-control form-control-sm" id="filterEndDate" placeholder="mm / dd / yyyy" title="Sampai Tanggal" onfocus="this.type='date'; this.showPicker && this.showPicker()" onblur="if(!this.value) this.type='text'" onchange="applyFilters()">
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
    .po-filter-bar .form-control,
    .po-filter-bar .form-select,
    .po-filter-bar .input-group-text,
    .po-filter-bar .btn {
        height: 36px;
        font-size: 0.85rem;
    }
    .po-filter-bar .input-group-text {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-left: 10px;
        padding-right: 10px;
    }
    .po-filter-bar .form-select {
        padding-top: 0.15rem !important;
        padding-bottom: 0.35rem !important;
        line-height: 1.5 !important;
    }
    .po-filter-bar .form-control {
        padding-top: 0.22rem !important;
        padding-bottom: 0.28rem !important;
        line-height: 1.5 !important;
    }
    .po-filter-bar input[type="date"] {
        -moz-appearance: textfield !important;
        appearance: none !important;
        padding-top: 0.22rem !important;
        padding-bottom: 0.28rem !important;
    }
    .po-filter-bar input[type="date"]::-webkit-calendar-picker-indicator {
        display: none !important;
        -webkit-appearance: none !important;
        opacity: 0 !important;
        width: 0 !important;
        height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .cursor-pointer {
        cursor: pointer;
    }
    .table-container {
        overflow: visible !important;
        position: relative;
    }
    </style>

    <!-- TABEL DATA PURCHASE ORDER -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="poTable">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th style="width: 45px;" class="text-center">No</th>
                            <th style="min-width: 140px;">Nomor</th>
                            <th style="min-width: 170px;">Vendor</th>
                            <th style="width: 110px;" class="text-center">Prioritas</th>
                            <th style="width: 150px;" class="text-center">Status</th>
                            <th style="min-width: 140px;">Site</th>
                            <th style="min-width: 130px;" class="text-end">Total</th>
                            <th style="width: 75px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="poTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Purchase Order...
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

<!-- MODAL DETAIL PURCHASE ORDER (DILENGKAPI TAB PEMISAH FUNGSI) -->
<div class="modal fade" id="modalDetailPo" tabindex="-1" aria-labelledby="modalDetailPoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- MODAL HEADER DENGAN STATUS & TAB INTEGRASI -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2 mb-0" id="modalDetailPoLabel">
                            <i class="bi bi-cart-check-fill text-primary"></i>
                            <span id="detailNomorPo">PO-XXXX-XXXX</span>
                        </h5>
                        <div class="text-muted small" style="font-size: 0.78rem;">Rincian Lengkap Dokumen Purchase Order</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <div id="detailStatusBadgeHeader"></div>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <!-- Nav Tabs Modal Sesuai Fungsi -->
                <ul class="nav nav-tabs border-bottom-0 flex-nowrap" id="modalPoTabNav" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="tab-m-info-btn" data-bs-toggle="tab" data-bs-target="#tab-m-info" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Informasi PO &amp; Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="tab-m-shipping-btn" data-bs-toggle="tab" data-bs-target="#tab-m-shipping" type="button" role="tab">
                            <i class="bi bi-truck me-1 text-primary"></i> 2. Pengiriman &amp; Pembayaran
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="tab-m-items-btn" data-bs-toggle="tab" data-bs-target="#tab-m-items" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> 3. Rincian Barang &amp; Biaya
                            <span class="badge bg-primary text-white ms-1 font-monospace" id="modalItemCountBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN 3 TAB PANE -->
            <div class="modal-body p-4">
                <div class="tab-content" id="modalPoTabContent">
                    
                    <!-- TAB 1: INFORMASI PO & VENDOR -->
                    <div class="tab-pane fade show active" id="tab-m-info" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-1 text-primary"></i> Data Purchase Order</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Tanggal PO:</span>
                                        <strong class="text-dark font-monospace" id="detailTanggalPo">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Referensi Request Order (RO):</span>
                                        <span class="badge bg-secondary-subtle text-dark border font-monospace" id="detailReferensiRo">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Prioritas:</span>
                                        <span id="detailPrioritasBadge">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Pembuat (Purchasing):</span>
                                        <strong class="text-dark" id="detailPembuat">-</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Disetujui Oleh:</span>
                                        <strong class="text-dark" id="detailApprover">-</strong>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-building me-1 text-primary"></i> Vendor</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Nama Perusahaan:</span>
                                        <strong class="text-dark fs-6" id="detailVendor">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Kontak / Telepon:</span>
                                        <span class="text-dark fw-semibold" id="detailTeleponVendor">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Email:</span>
                                        <span class="text-dark" id="detailEmailVendor">-</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Alamat Vendor:</span>
                                        <span class="text-secondary small" id="detailAlamatVendor">-</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="bg-light p-3 rounded-3 border-0">
                                    <span class="text-muted small d-block mb-1 fw-bold"><i class="bi bi-chat-left-text me-1 text-primary"></i> Catatan / Keterangan PO:</span>
                                    <p class="mb-0 small text-dark" id="detailCatatan">-</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PENGIRIMAN & PEMBAYARAN -->
                    <div class="tab-pane fade" id="tab-m-shipping" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-geo-alt me-1 text-primary"></i> Lokasi &amp; Pengiriman</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Site Tujuan:</span>
                                        <strong class="text-dark" id="detailSite">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Metode Pengiriman:</span>
                                        <span class="badge bg-info-subtle text-info border border-info-subtle" id="detailPengiriman">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Estimasi Tanggal Tiba:</span>
                                        <span class="text-dark font-monospace" id="detailTanggalKirim">-</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Alamat Lengkap Pengiriman:</span>
                                        <span class="text-secondary small" id="detailAlamatKirim">-</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-credit-card me-1 text-primary"></i> Ketentuan Pembayaran</h6>
                                    
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Term of Payment (T.O.P):</span>
                                        <span class="badge bg-light text-dark border fs-6 mt-1" id="detailTop">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: RINCIAN BARANG & BIAYA -->
                    <div class="tab-pane fade" id="tab-m-items" role="tabpanel">
                        <!-- Tabel Item Barang PO -->
                        <div class="table-responsive mb-3 border rounded-3">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light small text-muted">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th>Nama Barang &amp; Kode</th>
                                        <th style="width: 90px;" class="text-center">Qty</th>
                                        <th style="width: 120px;" class="text-end">Harga Satuan</th>
                                        <th style="width: 100px;" class="text-end">Diskon</th>
                                        <th style="width: 130px;" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="detailItemsTableBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-3 text-muted">Memuat daftar barang...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Financial Calculation Card -->
                        <div class="row justify-content-end">
                            <div class="col-md-7">
                                <div class="card bg-light border-0 rounded-3 p-3">
                                    <div class="d-flex justify-content-between mb-1 small text-muted">
                                        <span>Subtotal Barang:</span>
                                        <span class="fw-semibold text-dark font-monospace" id="calcSubtotalBarang">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-1 small text-muted">
                                        <span>Diskon Global PO:</span>
                                        <span class="fw-semibold text-danger font-monospace" id="calcDiskonPo">- Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small text-muted">
                                        <span>PPN (<span id="calcRatePajak">0</span>%):</span>
                                        <span class="fw-semibold text-dark font-monospace" id="calcNominalPajak">Rp 0</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between fs-6 fw-bold text-dark">
                                        <span>Grand Total PO:</span>
                                        <span class="text-primary font-monospace" id="calcGrandTotal">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<!-- Client-side Logic Script for Purchase Order List -->
<script>
const CURRENT_USER_ROLE = '<?= strtoupper($user['role'] ?? '') ?>';
let currentPage = 1;
let currentLimit = 10;
let modalDetailInstance = null;

document.addEventListener('DOMContentLoaded', async () => {
    modalDetailInstance = new bootstrap.Modal(document.getElementById('modalDetailPo'));

    // Inisialisasi Filter Site
    await loadSiteOptions();

    // Event Listener Filter
    document.getElementById('filterSearch').addEventListener('input', debounce(() => loadPoList(1), 350));
    document.getElementById('filterStatus').addEventListener('change', () => loadPoList(1));
    document.getElementById('filterSite').addEventListener('change', () => loadPoList(1));
    document.getElementById('filterStartDate').addEventListener('change', () => loadPoList(1));
    document.getElementById('filterEndDate').addEventListener('change', () => loadPoList(1));

    // Load Data Awal
    loadPoList(1);
});

// -------------------------------------------------------------
// LOAD DAFTAR SITE FILTER
// -------------------------------------------------------------
async function loadSiteOptions() {
    try {
        const res = await apiRequest('/api/master/site.php?limit=100');
        if (res && res.success && res.data) {
            const select = document.getElementById('filterSite');
            const items = res.data.items || res.data;
            let options = '<option value="">Semua Site / Lokasi</option>';
            if (Array.isArray(items)) {
                items.forEach(site => {
                    options += `<option value="${site.id_site}">${escapeHtml(site.nama_site)} (${escapeHtml(site.kode_site || 'SITE')})</option>`;
                });
            }
            select.innerHTML = options;
        }
    } catch (e) {
        console.error('Gagal memuat site filter:', e);
    }
}

// -------------------------------------------------------------
// LOAD DATA DAFTAR PURCHASE ORDER
// -------------------------------------------------------------
async function loadPoList(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('poTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Purchase Order...
            </td>
        </tr>
    `;

    const search = document.getElementById('filterSearch').value.trim();
    const status = document.getElementById('filterStatus').value;
    const siteId = document.getElementById('filterSite').value;
    const startDate = document.getElementById('filterStartDate').value;
    const endDate = document.getElementById('filterEndDate').value;

    const params = new URLSearchParams({
        page: currentPage,
        limit: currentLimit
    });

    if (search) params.append('q', search);
    if (status) params.append('status', status);
    if (siteId) params.append('site_id', siteId);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);

    const res = await apiRequest(`/api/purchase_order/index.php?${params.toString()}`);

    if (!res || !res.success || !res.data) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-danger">
                    <i class="bi bi-exclamation-circle me-1"></i> ${res ? res.message : 'Gagal memuat data Purchase Order.'}
                </td>
            </tr>
        `;
        return;
    }

    const items = res.data.items || [];
    const pagination = res.data.pagination || { total_records: 0, total_pages: 1, current_page: 1 };

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-2 d-block mb-2 text-muted"></i>
                    Belum ada dokumen Purchase Order yang ditemukan.
                </td>
            </tr>
        `;
        renderPagination(pagination);
        return;
    }

    let rowsHtml = '';
    const startIndex = (pagination.current_page - 1) * pagination.limit;

    items.forEach((item, idx) => {
        const no = startIndex + idx + 1;
        const statusBadge = renderStatusBadge(item.status);
        const nomorPo = item.nomor_po ? escapeHtml(item.nomor_po) : '-';
        const vendorName = item.nama_vendor ? escapeHtml(item.nama_vendor) : '<span class="text-muted italic">Vendor Umum</span>';
        const siteName = item.nama_site ? escapeHtml(item.nama_site) : '-';
        const tgl = item.tanggal_po ? item.tanggal_po.split(' ')[0] : '-';
        const nilaiFormatted = item.grand_total_formatted || ('Rp ' + formatNumber(item.grand_total || 0));

        const prio = (item.prioritas || 'NORMAL').toUpperCase();
        const prioBadge = (prio === 'URGENT') 
            ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2 py-1"><i class="bi bi-lightning-fill me-1"></i>URGENT</span>'
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace px-2 py-1">NORMAL</span>';

        const statusUpper = (item.status || '').toUpperCase();
        let isEditable = false;
        let lockTooltip = 'PO Terkunci';

        if (CURRENT_USER_ROLE === 'LOGISTIK') {
            isEditable = false;
            lockTooltip = 'Bagian Logistik tidak memiliki wewenang mengedit Purchase Order';
        } else if (['DIPROSES VENDOR', 'DITERIMA', 'BATAL'].includes(statusUpper)) {
            isEditable = false;
            lockTooltip = `PO berstatus ${statusUpper} (Data formulir terkunci)`;
        } else {
            // Status DRAFT, REVIEW INTERNAL, DISETUJUI INTERNAL, TIDAK DISETUJUI INTERNAL, REVIEW VENDOR
            isEditable = true;
        }

        const editBtnHtml = isEditable 
            ? `<a href="${BASE_URL}/admin/pages/purchase_order/edit.php?id=${item.id_po}" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-xs text-dark" title="Edit Purchase Order"><i class="bi bi-pencil-fill"></i></a>`
            : `<button type="button" class="btn btn-light btn-sm px-2 py-1 text-muted border opacity-50" disabled title="${lockTooltip}"><i class="bi bi-lock-fill"></i></button>`;

        let receiveBtnHtml = '';
        if (statusUpper === 'DIPROSES VENDOR') {
            receiveBtnHtml = `
                <button type="button" class="btn btn-outline-success btn-sm px-2 py-1 shadow-xs" onclick="confirmReceivePo(${item.id_po}, '${escapeHtml(item.nomor_po)}')" title="Update Status: Barang Diterima">
                    <i class="bi bi-box-seam-fill"></i>
                </button>
            `;
        }

        rowsHtml += `
            <tr>
                <td class="text-center font-monospace text-muted small">${no}</td>
                <td>
                    <div class="fw-bold font-monospace text-primary">${nomorPo}</div>
                </td>
                <td>
                    <div class="fw-semibold text-dark">${vendorName}</div>
                </td>
                <td class="text-center">
                    ${prioBadge}
                </td>
                <td class="text-center">
                    ${statusBadge}
                </td>
                <td class="small text-dark">${siteName}</td>
                <td class="text-end font-monospace fw-bold text-dark">
                    ${nilaiFormatted}
                </td>
                <td class="text-center">
                    <div class="d-inline-flex gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-xs" onclick="openDetailModal(${item.id_po})" title="Lihat Rincian PO">
                            <i class="bi bi-eye-fill"></i>
                        </button>
                        ${editBtnHtml}
                        ${receiveBtnHtml}
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = rowsHtml;
    renderPagination(pagination);
}

// -------------------------------------------------------------
// RENDER STATUS BADGE
// -------------------------------------------------------------
function renderStatusBadge(status) {
    const st = (status || '').toUpperCase();
    switch (st) {
        case 'DRAFT':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-pencil me-1"></i>DRAFT</span>';
        case 'REVIEW INTERNAL':
            return '<span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>REVIEW INTERNAL</span>';
        case 'DISETUJUI INTERNAL':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>DISETUJUI INTERNAL</span>';
        case 'DIPROSES VENDOR':
            return '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bi bi-truck me-1"></i>DIPROSES VENDOR</span>';
        case 'DITERIMA':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-box-seam me-1"></i>DITERIMA</span>';
        case 'BATAL':
            return '<span class="badge bg-dark-subtle text-muted border border-dark-subtle px-2 py-1"><i class="bi bi-slash-circle me-1"></i>BATAL</span>';
        case 'TIDAK DISETUJUI INTERNAL':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle me-1"></i>DITOLAK</span>';
        default:
            return `<span class="badge bg-light text-dark border px-2 py-1">${escapeHtml(status)}</span>`;
    }
}

// -------------------------------------------------------------
// PAGINATION RENDERING
// -------------------------------------------------------------
function renderPagination(pagination) {
    const info = document.getElementById('paginationInfo');
    const list = document.getElementById('paginationList');

    const totalRecords = pagination.total_records || 0;
    const totalPages = pagination.total_pages || 1;
    const current = pagination.current_page || 1;

    info.textContent = `Menampilkan ${totalRecords} data Purchase Order (Halaman ${current} dari ${totalPages})`;

    if (totalPages <= 1) {
        list.innerHTML = '';
        return;
    }

    let paginationHtml = '';
    paginationHtml += `
        <li class="page-item ${current === 1 ? 'disabled' : ''}">
            <button class="page-link" onclick="loadPoList(${current - 1})">&laquo;</button>
        </li>
    `;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= current - 2 && p <= current + 2)) {
            paginationHtml += `
                <li class="page-item ${p === current ? 'active' : ''}">
                    <button class="page-link" onclick="loadPoList(${p})">${p}</button>
                </li>
            `;
        } else if (p === current - 3 || p === current + 3) {
            paginationHtml += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    paginationHtml += `
        <li class="page-item ${current === totalPages ? 'disabled' : ''}">
            <button class="page-link" onclick="loadPoList(${current + 1})">&raquo;</button>
        </li>
    `;

    list.innerHTML = paginationHtml;
}

// -------------------------------------------------------------
// BUKA MODAL DETAIL PURCHASE ORDER
// -------------------------------------------------------------
async function openDetailModal(idPo) {
    modalDetailInstance.show();

    // Reset ke Tab 1
    const firstTabEl = document.getElementById('tab-m-info-btn');
    if (firstTabEl) {
        const tabTrigger = new bootstrap.Tab(firstTabEl);
        tabTrigger.show();
    }

    document.getElementById('detailNomorPo').textContent = 'Memuat...';
    document.getElementById('detailStatusBadgeHeader').innerHTML = '';
    document.getElementById('detailTanggalPo').textContent = '-';
    document.getElementById('detailReferensiRo').textContent = '-';
    document.getElementById('detailPrioritasBadge').innerHTML = '-';
    document.getElementById('detailPembuat').textContent = '-';
    document.getElementById('detailApprover').textContent = '-';
    document.getElementById('detailVendor').textContent = '-';
    document.getElementById('detailTeleponVendor').textContent = '-';
    document.getElementById('detailEmailVendor').textContent = '-';
    document.getElementById('detailAlamatVendor').textContent = '-';
    document.getElementById('detailCatatan').textContent = '-';

    document.getElementById('detailSite').textContent = '-';
    document.getElementById('detailPengiriman').textContent = '-';
    document.getElementById('detailTanggalKirim').textContent = '-';
    document.getElementById('detailAlamatKirim').textContent = '-';
    document.getElementById('detailTop').textContent = '-';

    document.getElementById('calcSubtotalBarang').textContent = 'Rp 0';
    document.getElementById('calcDiskonPo').textContent = '- Rp 0';
    document.getElementById('calcRatePajak').textContent = '0';
    document.getElementById('calcNominalPajak').textContent = 'Rp 0';
    document.getElementById('calcGrandTotal').textContent = 'Rp 0';
    document.getElementById('modalItemCountBadge').textContent = '0';

    document.getElementById('detailItemsTableBody').innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-3 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat rincian barang...
            </td>
        </tr>
    `;

    const res = await apiRequest(`/api/purchase_order/index.php?id=${idPo}`);
    if (!res || !res.success || !res.data) {
        showToast(res ? res.message : 'Gagal memuat detail Purchase Order.', 'danger');
        modalDetailInstance.hide();
        return;
    }

    const po = res.data;

    // Header Info
    document.getElementById('detailNomorPo').textContent = po.nomor_po || '-';
    document.getElementById('detailStatusBadgeHeader').innerHTML = renderStatusBadge(po.status);
    
    // Tab 1 Info
    document.getElementById('detailTanggalPo').textContent = po.tanggal_po ? po.tanggal_po.split(' ')[0] : '-';
    document.getElementById('detailReferensiRo').textContent = po.nomor_ro ? `RO: ${po.nomor_ro}` : 'Tanpa RO';
    
    const prio = (po.prioritas || 'NORMAL').toUpperCase();
    document.getElementById('detailPrioritasBadge').innerHTML = (prio === 'URGENT')
        ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle font-monospace px-2 py-1"><i class="bi bi-lightning-fill me-1"></i>URGENT</span>'
        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle font-monospace px-2 py-1">NORMAL</span>';
        
    document.getElementById('detailPembuat').textContent = po.nama_pembuat || 'Staff Purchasing';
    document.getElementById('detailApprover').textContent = po.nama_approver || 'Menunggu Persetujuan';
    
    // Vendor Info
    document.getElementById('detailVendor').textContent = po.nama_vendor ? `${po.nama_vendor} (${po.kode_vendor || 'VND'})` : '-';
    document.getElementById('detailTeleponVendor').textContent = po.telepon_vendor || 'Tidak ada nomor telepon';
    document.getElementById('detailEmailVendor').textContent = po.email_vendor || 'Tidak ada alamat email';
    document.getElementById('detailAlamatVendor').textContent = po.alamat_vendor || 'Alamat vendor tidak tersedia';
    document.getElementById('detailCatatan').textContent = po.keterangan ? po.keterangan : 'Tidak ada catatan khusus.';

    // Tab 2 Shipping
    document.getElementById('detailSite').textContent = po.nama_site ? `${po.nama_site} (${po.kode_site || '-'})` : '-';
    document.getElementById('detailPengiriman').textContent = po.pengiriman ? `Kirim via ${po.pengiriman}` : 'Belum ditentukan';
    document.getElementById('detailTanggalKirim').textContent = po.tanggal_pengiriman ? po.tanggal_pengiriman.split(' ')[0] : 'Sesuai jadwal';
    document.getElementById('detailAlamatKirim').textContent = po.alamat || (po.alamat_site || 'Alamat Site');
    
    const topNum = parseInt(po.term_of_payment) || 0;
    document.getElementById('detailTop').textContent = (topNum === 0) ? 'C.O.D (Cash On Delivery)' : `Tempo ${topNum} Hari`;

    // Tab 3 Financial Calculation
    const subtotalBarang = parseFloat(po.subtotal_barang) || 0;
    const diskonPo = parseFloat(po.nominal_diskon) || 0;
    const ratePajak = parseFloat(po.rate_pajak) || 0;
    const nominalPajak = parseFloat(po.nominal_pajak) || 0;
    const grandTotal = parseFloat(po.grand_total) || 0;

    document.getElementById('calcSubtotalBarang').textContent = formatRupiah(subtotalBarang);
    document.getElementById('calcDiskonPo').textContent = '- ' + formatRupiah(diskonPo);
    document.getElementById('calcRatePajak').textContent = ratePajak;
    document.getElementById('calcNominalPajak').textContent = formatRupiah(nominalPajak);
    document.getElementById('calcGrandTotal').textContent = formatRupiah(grandTotal);

    // Render Items
    const items = po.items || [];
    document.getElementById('modalItemCountBadge').textContent = items.length;

    if (items.length === 0) {
        document.getElementById('detailItemsTableBody').innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-3 text-muted">Tidak ada rincian barang dalam dokumen ini.</td>
            </tr>
        `;
        return;
    }

    let itemsHtml = '';
    items.forEach((item, idx) => {
        const itemQty = parseFloat(item.qty) || 0;
        const itemHarga = parseFloat(item.harga) || 0;
        const itemDiskon = parseFloat(item.diskon) || 0;
        const itemSubtotal = parseFloat(item.subtotal) || 0;

        itemsHtml += `
            <tr>
                <td class="text-center font-monospace text-muted small">${idx + 1}</td>
                <td>
                    <div class="fw-bold text-dark">${escapeHtml(item.nama_barang || '')}</div>
                    <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">${escapeHtml(item.kode_barang || 'BRG')}</span>
                        ${item.nama_kategori ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-tag me-1"></i>${escapeHtml(item.nama_kategori)}</span>` : ''}
                        ${item.nama_merk ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-bookmark me-1"></i>${escapeHtml(item.nama_merk)}</span>` : ''}
                    </div>
                </td>
                <td class="text-center">
                    <span class="fw-bold font-monospace">${itemQty}</span>
                    <span class="small text-muted d-block" style="font-size: 0.72rem;">${escapeHtml(item.satuan || 'PCS')}</span>
                </td>
                <td class="text-end font-monospace">${formatRupiah(itemHarga)}</td>
                <td class="text-end font-monospace text-danger">${itemDiskon > 0 ? ('- ' + formatRupiah(itemDiskon)) : '0'}</td>
                <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(itemSubtotal)}</td>
            </tr>
        `;
    });

    document.getElementById('detailItemsTableBody').innerHTML = itemsHtml;
}

// -------------------------------------------------------------
// RESET FILTERS
// -------------------------------------------------------------
function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterStatus').value = '';
    document.getElementById('filterSite').value = '';
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    loadPoList(1);
}

// -------------------------------------------------------------
// KONFIRMASI UPDATE STATUS DITERIMA (PENERIMAAN BARANG)
// -------------------------------------------------------------
async function confirmReceivePo(idPo, nomorPo) {
    if (!confirm(`Konfirmasi Penerimaan Barang untuk dokumen ${nomorPo}?\n\nStatus PO akan diperbarui menjadi 'DITERIMA' dan tercatat dalam sistem.`)) {
        return;
    }

    const res = await apiRequest('/api/purchase_order/receive.php', {
        method: 'POST',
        body: JSON.stringify({ id_po: idPo })
    });

    if (res && res.success) {
        showToast(res.message, 'success');
        loadPoList(currentPage);
    } else {
        showToast(res ? res.message : 'Gagal memperbarui status PO.', 'danger');
    }
}

// -------------------------------------------------------------
// HELPER UTILITIES
// -------------------------------------------------------------
function formatRupiah(amount) {
    return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function formatNumber(num) {
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
