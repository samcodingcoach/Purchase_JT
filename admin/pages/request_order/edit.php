<?php
/**
 * Halaman Edit / Update Request Order (RO)
 * Path: admin/pages/request_order/edit.php
 * Akses: Hanya jika RO berstatus DRAFT atau TERKIRIM
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$isMekanik = ($user['role'] === ROLE_MEKANIK);
$pageTitle = 'Edit Request Order';
$pageHeading = 'Edit Formulir Request Order (RO)';

$idRequest = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($idRequest <= 0) {
    header('Location: ' . BASE_URL . '/admin/pages/request_order/index.php');
    exit;
}

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Title -->
    <div class="mb-4">
        <h2 class="fs-4 fw-bold text-dark mb-0">
            <i class="bi bi-pencil-square text-primary me-2"></i>Edit Request Order (RO) #<span id="headerNomorRoDisplay">...</span>
        </h2>
    </div>

    <!-- Alert / Loading Notice -->
    <div id="roLoadingNotice" class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span>Memuat data Request Order...</span>
    </div>

    <!-- FORMULIR REQUEST ORDER DALAM TABS -->
    <form id="formEditRequestOrder" onsubmit="return false;" class="d-none">
        <input type="hidden" id="roIdRequest" value="<?= $idRequest ?>">

        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <!-- TAB NAVIGATION HEADER -->
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="roEditTabNav" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold text-dark py-3 px-4" id="tab-info-utama-btn" data-bs-toggle="tab" data-bs-target="#tab-info-utama" type="button" role="tab" aria-selected="true">
                            <i class="bi bi-file-earmark-text me-2 text-primary"></i>1. Informasi Utama Dokumen
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold text-dark py-3 px-4" id="tab-material-btn" data-bs-toggle="tab" data-bs-target="#tab-material" type="button" role="tab" aria-selected="false">
                            <i class="bi bi-boxes me-2 text-primary"></i>2. Daftar Kebutuhan Material
                            <span class="badge bg-primary ms-2" id="tabItemCountBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="roEditTabContent">

                    <!-- ======================================================== -->
                    <!-- TAB 1: INFORMASI UTAMA DOKUMEN (2 KOLOM RAPI)           -->
                    <!-- ======================================================== -->
                    <div class="tab-pane fade show active" id="tab-info-utama" role="tabpanel">
                        <div class="row g-4">
                            <!-- KOLOM KIRI -->
                            <div class="col-lg-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                                        <i class="bi bi-info-circle-fill text-primary me-1"></i> Data Pemohon & Dokumen
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Nomor RO</label>
                                        <input type="text" class="form-control form-control-sm font-monospace fw-bold bg-white" id="roNomor" readonly>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Tanggal Pengajuan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="roTanggal" required>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Dibuat Oleh / Pemohon <span class="text-danger">*</span></label>
                                        <?php if ($user['role'] === ROLE_ADMIN): ?>
                                            <select class="form-select form-select-sm" id="roIdKaryawan" required>
                                                <option value="">Memuat data karyawan...</option>
                                            </select>
                                        <?php else: ?>
                                            <input type="hidden" id="roIdKaryawan" value="<?= $user['id_karyawan'] ?>">
                                            <input type="text" class="form-control form-control-sm bg-white" value="<?= htmlspecialchars($user['nama']) ?> (<?= htmlspecialchars($user['role']) ?>)" readonly>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- KOLOM KANAN -->
                            <div class="col-lg-6">
                                <div class="p-3 bg-light rounded-3 border h-100">
                                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                                        <i class="bi bi-geo-alt-fill text-primary me-1"></i> Lokasi & Referensi
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Site / Workshop Tujuan <span class="text-danger">*</span></label>
                                        <select class="form-select form-select-sm" id="roIdSite" required>
                                            <option value="">Memuat data site...</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark d-block">Tingkat Prioritas Kebutuhan <span class="text-danger">*</span></label>
                                        <div class="btn-group w-100 btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="roPrioritas" id="prioritasNormal" value="NORMAL" checked>
                                            <label class="btn btn-outline-primary fw-semibold" for="prioritasNormal">
                                                <i class="bi bi-check2-circle me-1"></i> Normal (Reguler)
                                            </label>
                                            
                                            <input type="radio" class="btn-check" name="roPrioritas" id="prioritasUrgent" value="URGENT">
                                            <label class="btn btn-outline-danger fw-semibold" for="prioritasUrgent">
                                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Urgent / Mendesak
                                            </label>
                                        </div>
                                    </div>

                                    <!-- Searchable Vendor Selector -->
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold text-dark">Referensi Vendor Rekanan (Opsional)</label>
                                        <div class="ro-vendor-search-wrapper position-relative" id="roVendorSearchWrapper">
                                            <input type="hidden" id="roIdVendor" value="">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white"><i class="bi bi-truck"></i></span>
                                                <input type="text" class="form-control form-control-sm" id="roVendorSearchInput" placeholder="Cari / pilih vendor rekanan..." autocomplete="off" onfocus="openRoVendorDropdown()" onclick="openRoVendorDropdown()" oninput="debounceRoVendorSearch()">
                                                <button type="button" class="btn btn-outline-secondary" onclick="clearRoVendorSelection()" title="Hapus Pilihan">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </div>
                                            <div class="ro-vendor-dropdown d-none" id="roVendorDropdown">
                                                <div id="roVendorDropdownList">
                                                    <div class="p-2 text-center text-muted small">Memuat vendor...</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label small fw-bold text-dark">Keperluan / Catatan Pekerjaan</label>
                                        <textarea class="form-control form-control-sm" id="roKeterangan" rows="2" placeholder="Contoh: Overhaul Mesin Genset KM Samudra 02..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Navigasi Bawah Tab 1 -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-outline-secondary btn-sm px-3">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar RO
                            </a>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToTab('tab-material')">
                                Lanjut ke Daftar Material <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- ======================================================== -->
                    <!-- TAB 2: DAFTAR KEBUTUHAN MATERIAL DINAMIS                -->
                    <!-- ======================================================== -->
                    <div class="tab-pane fade" id="tab-material" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">
                                    <i class="bi bi-boxes text-primary me-1"></i> Daftar Kebutuhan Material / Barang
                                </h6>
                                <span class="text-muted small">Pilih material dari katalog atau ketik material yang dibutuhkan</span>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" onclick="addNewItemRow()">
                                <i class="bi bi-plus-circle-fill me-1"></i> Tambah Baris Material
                            </button>
                        </div>

                        <div class="table-container border rounded-3 bg-white" style="overflow: visible; position: relative;">
                            <table class="table table-bordered align-middle mb-0" id="roItemsTable">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width: 45px;" class="text-center">No</th>
                                        <th style="min-width: 320px;">Nama Barang / Material <span class="text-danger">*</span></th>
                                        <th style="width: 160px;">Kode</th>
                                        <th style="width: 140px;">Qty <span class="text-danger">*</span></th>
                                        <th style="width: 130px;">Satuan</th>
                                        <th style="width: 50px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="roItemsTableBody">
                                    <!-- Dynamic rows rendered via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- REAKTIF TOTALS RINGKASAN -->
                        <div class="card-footer bg-light p-3 border rounded-3 mt-3">
                            <div class="d-flex gap-4 text-muted small">
                                <div><i class="bi bi-list-check me-1 text-primary"></i>Total Item: <strong class="text-dark" id="summaryTotalItems">0 Jenis</strong></div>
                                <div><i class="bi bi-boxes me-1 text-primary"></i>Total Kuantitas: <strong class="text-dark" id="summaryTotalQty">0</strong></div>
                            </div>
                        </div>

                        <!-- Tombol Navigasi Bawah Tab 2 -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top flex-wrap gap-2">
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-outline-secondary btn-sm px-3">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar RO
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-info-utama')">
                                    <i class="bi bi-chevron-left me-1"></i> Info Dokumen
                                </button>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-secondary btn-sm px-3 fw-semibold" onclick="submitEditRequestOrder('DRAFT')" id="btnSaveDraftBottom">
                                    <i class="bi bi-save me-1"></i> Simpan Draft
                                </button>
                                <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="submitEditRequestOrder('TERKIRIM')" id="btnSubmitRoBottom">
                                    <i class="bi bi-send-fill me-1"></i> Perbarui & Kirim ke Logistik
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<!-- STYLING AUTOCOMPLETE & SEARCHABLE SELECT LAYER ATAS -->
<style>
.table-container {
    overflow: visible !important;
    position: relative;
}
.ro-item-row {
    position: relative;
}
.ro-item-row:focus-within {
    z-index: 1055;
}
.ro-item-search-wrapper {
    position: relative;
}
.ro-item-dropdown, .ro-vendor-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid #b6d4fe;
    border-radius: 0.375rem;
    box-shadow: 0 12px 32px rgba(0,0,0,0.2);
    z-index: 9999 !important;
    max-height: 240px;
    overflow-y: auto;
}
.ro-item-dropdown-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f1f3f5;
    transition: background 0.15s ease-in-out;
}
.ro-item-dropdown-item:hover, .ro-item-dropdown-item.active {
    background-color: #f0f7ff;
}
</style>

<script>
const CURRENT_USER_ROLE = <?= json_encode($user['role']) ?>;
const CURRENT_USER_ID_KARYAWAN = <?= json_encode($user['id_karyawan'] ?? 0) ?>;
const ID_REQUEST = <?= json_encode($idRequest) ?>;
let nextRowIndex = 0;
let masterBarangCache = [];
let masterSiteCache = [];
let masterVendorCache = [];
let masterKaryawanCache = [];
let vendorSearchTimeout = null;

document.addEventListener('DOMContentLoaded', async () => {
    await initEditPage();
});

// -------------------------------------------------------------
// 1. INISIALISASI & LOAD DATA EDIT
// -------------------------------------------------------------
async function initEditPage() {
    // 1. Preload master sites, vendors, karyawans
    await Promise.all([
        loadMasterDropdowns(),
        loadExistingRoData()
    ]);
}

async function loadMasterDropdowns() {
    // A. Sites
    const resSite = await apiRequest('/api/master/site.php?limit=100');
    if (resSite && resSite.success) {
        masterSiteCache = resSite.data.items || [];
        const siteSelect = document.getElementById('roIdSite');
        siteSelect.innerHTML = '<option value="">-- Pilih Site / Lokasi --</option>';
        masterSiteCache.forEach(s => {
            siteSelect.innerHTML += `<option value="${s.id_site}">${s.nama_site} (${s.kode_site})</option>`;
        });
    }

    // B. Vendors
    const resVendor = await apiRequest('/api/master/vendor.php?limit=100');
    if (resVendor && resVendor.success) {
        masterVendorCache = resVendor.data.items || [];
    }

    // C. Karyawan (jika admin)
    const karyawanSelect = document.getElementById('roIdKaryawan');
    if (karyawanSelect && karyawanSelect.tagName === 'SELECT') {
        const resKaryawan = await apiRequest('/api/master/karyawan.php?limit=100');
        if (resKaryawan && resKaryawan.success) {
            masterKaryawanCache = resKaryawan.data.items || [];
            karyawanSelect.innerHTML = '<option value="">-- Pilih Karyawan Pemohon --</option>';
            masterKaryawanCache.forEach(k => {
                const displayName = k.nama_karyawan || k.nama_lengkap || k.nama || 'Karyawan';
                karyawanSelect.innerHTML += `<option value="${k.id_karyawan}">${displayName} (${k.kode_karyawan || 'KRY'}) - ${k.nama_jabatan || 'Staf'}</option>`;
            });
        }
    }
}

async function loadExistingRoData() {
    const res = await apiRequest(`/api/request_order/index.php?id=${ID_REQUEST}`);
    if (!res || !res.success) {
        document.getElementById('roLoadingNotice').className = 'alert alert-danger border-0';
        document.getElementById('roLoadingNotice').innerHTML = `<i class="bi bi-x-circle me-1"></i> ${res ? res.message : 'Gagal memuat data Request Order.'}`;
        return;
    }

    const ro = res.data;

    // Cek apakah status boleh diedit
    if (!['DRAFT', 'TERKIRIM'].includes(ro.status)) {
        document.getElementById('roLoadingNotice').className = 'alert alert-warning border-0';
        document.getElementById('roLoadingNotice').innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> Dokumen RO ${ro.nomor} berstatus <strong>${ro.status}</strong> dan sudah tidak dapat diedit lagi. <a href="${BASE_URL}/admin/pages/request_order/index.php" class="alert-link ms-2">Kembali ke Daftar RO</a>`;
        return;
    }

    // Cek Hak Akses Edit:
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

    if (!canEdit) {
        document.getElementById('roLoadingNotice').className = 'alert alert-danger border-0';
        document.getElementById('roLoadingNotice').innerHTML = `<i class="bi bi-shield-lock-fill me-1"></i> Anda tidak memiliki hak akses untuk mengedit dokumen Request Order ini. <a href="${BASE_URL}/admin/pages/request_order/index.php" class="alert-link ms-2">Kembali ke Daftar RO</a>`;
        return;
    }

    // Isi data Header
    document.getElementById('headerNomorRoDisplay').textContent = ro.nomor;
    document.getElementById('roNomor').value = ro.nomor;
    
    // Tanggal
    if (ro.tanggal_ro) {
        document.getElementById('roTanggal').value = ro.tanggal_ro.substring(0, 10);
    }

    // Site & Karyawan
    if (document.getElementById('roIdKaryawan')) {
        document.getElementById('roIdKaryawan').value = ro.id_karyawan;
    }
    document.getElementById('roIdSite').value = ro.id_site;

    // Prioritas
    if (ro.prioritas === 'URGENT') {
        document.getElementById('prioritasUrgent').checked = true;
    } else {
        document.getElementById('prioritasNormal').checked = true;
    }

    // Vendor
    if (ro.id_vendor && ro.nama_vendor) {
        selectRoVendor(ro.id_vendor, ro.nama_vendor, ro.kode_vendor || '');
    }

    // Keterangan
    document.getElementById('roKeterangan').value = ro.keterangan || '';

    // Isi Baris Item
    const tbody = document.getElementById('roItemsTableBody');
    tbody.innerHTML = '';

    if (ro.items && ro.items.length > 0) {
        ro.items.forEach(it => {
            addNewItemRow(it);
        });
    } else {
        addNewItemRow();
    }

    // Tampilkan form
    document.getElementById('roLoadingNotice').classList.add('d-none');
    document.getElementById('formEditRequestOrder').classList.remove('d-none');
}

// -------------------------------------------------------------
// 2. TAB NAVIGASI HELPER
// -------------------------------------------------------------
function goToTab(tabId) {
    const triggerEl = document.querySelector(`#roEditTabNav button[data-bs-target="#${tabId}"]`);
    if (triggerEl) {
        const tab = new bootstrap.Tab(triggerEl);
        tab.show();
    }
}

// -------------------------------------------------------------
// 3. SEARCHABLE VENDOR DROPDOWN
// -------------------------------------------------------------
function openRoVendorDropdown() {
    renderRoVendorList(masterVendorCache);
    document.getElementById('roVendorDropdown')?.classList.remove('d-none');
}

function debounceRoVendorSearch() {
    clearTimeout(vendorSearchTimeout);
    vendorSearchTimeout = setTimeout(async () => {
        const query = document.getElementById('roVendorSearchInput').value.trim();
        const res = await apiRequest(`/api/master/vendor.php?limit=50&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderRoVendorList(res.data.items || []);
        }
    }, 250);
}

function renderRoVendorList(items) {
    const list = document.getElementById('roVendorDropdownList');
    if (!list) return;

    if (items.length === 0) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada vendor ditemukan.</div>`;
        return;
    }
    let html = '';
    items.forEach(v => {
        html += `
            <div class="ro-item-dropdown-item" onclick="selectRoVendor(${v.id_vendor}, '${v.nama_perusahaan.replace(/'/g, "\\'")}', '${v.kode_vendor || ''}')">
                <div class="fw-bold text-dark small">${v.nama_perusahaan}</div>
                <div class="text-muted" style="font-size: 0.72rem;">${v.kode_vendor || '-'} &bull; ${v.kota || '-'}</div>
            </div>
        `;
    });
    list.innerHTML = html;
}

function selectRoVendor(id, name, code) {
    document.getElementById('roIdVendor').value = id;
    document.getElementById('roVendorSearchInput').value = `${name} (${code || 'VND'})`;
    document.getElementById('roVendorDropdown')?.classList.add('d-none');
}

function clearRoVendorSelection() {
    document.getElementById('roIdVendor').value = '';
    document.getElementById('roVendorSearchInput').value = '';
    document.getElementById('roVendorDropdown')?.classList.add('d-none');
}

// -------------------------------------------------------------
// 4. PENGELOLAAN TABEL ITEM DINAMIS
// -------------------------------------------------------------
function addNewItemRow(data = {}) {
    nextRowIndex++;
    const rowId = `itemRow_${nextRowIndex}`;
    const tbody = document.getElementById('roItemsTableBody');

    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'ro-item-row';
    tr.innerHTML = `
        <td class="text-center text-muted fw-bold row-number"></td>
        <td>
            <div class="ro-item-search-wrapper" id="wrapper_${rowId}">
                <input type="hidden" class="item-id-barang" value="${data.id_barang || ''}">
                <input type="hidden" class="item-harga" value="0">
                <input type="text" class="form-control form-control-sm item-nama-barang" 
                       placeholder="Ketik / cari nama material..." 
                       value="${data.nama_barang || ''}" 
                       autocomplete="off" 
                       onfocus="openItemDropdown('${rowId}')" 
                       onclick="openItemDropdown('${rowId}')" 
                       oninput="handleItemSearch('${rowId}')" 
                       required>
                <div class="ro-item-dropdown d-none" id="dropdown_${rowId}"></div>
            </div>
        </td>
        <td>
            <input type="text" class="form-control form-control-sm item-kode-barang font-monospace" 
                   placeholder="Kode" value="${data.kode_barang || ''}">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm item-qty text-center fw-bold" 
                   value="${data.qty || 1}" min="0.1" step="any" required oninput="calculateGrandTotal()">
        </td>
        <td>
            <select class="form-select form-select-sm item-satuan">
                <option value="PCS" ${(!data.satuan || data.satuan === 'PCS') ? 'selected' : ''}>PCS</option>
                <option value="UNIT" ${(data.satuan === 'UNIT') ? 'selected' : ''}>UNIT</option>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeItemRow('${rowId}')" title="Hapus Baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    reindexRows();
    calculateGrandTotal();
}

function removeItemRow(rowId) {
    const rows = document.querySelectorAll('.ro-item-row');
    if (rows.length <= 1) {
        showToast('Minimal harus ada 1 baris material dalam Request Order.', 'warning');
        return;
    }
    const tr = document.getElementById(rowId);
    if (tr) tr.remove();
    reindexRows();
    calculateGrandTotal();
}

function reindexRows() {
    const rows = document.querySelectorAll('.ro-item-row');
    rows.forEach((row, idx) => {
        const numCell = row.querySelector('.row-number');
        if (numCell) numCell.textContent = idx + 1;
    });
    document.getElementById('tabItemCountBadge').textContent = rows.length;
}

// -------------------------------------------------------------
// 5. AUTOCOMPLETE & SELECTION BARANG (Min 3 Karakter, Max 10 Data)
// -------------------------------------------------------------
let itemSearchTimeout = null;

function openItemDropdown(rowId) {
    const input = document.querySelector(`#${rowId} .item-nama-barang`);
    const query = input.value.trim();
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    if (query.length < 3) {
        dropdown.innerHTML = `<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1 text-primary"></i>Ketik minimal 3 karakter untuk mencari material...</div>`;
        dropdown.classList.remove('d-none');
        return;
    }
    handleItemSearch(rowId);
}

function handleItemSearch(rowId) {
    clearTimeout(itemSearchTimeout);
    const input = document.querySelector(`#${rowId} .item-nama-barang`);
    const query = input.value.trim();
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    if (query.length < 3) {
        dropdown.innerHTML = `<div class="p-3 text-center text-muted small"><i class="bi bi-search me-1 text-primary"></i>Ketik minimal 3 karakter untuk mencari material...</div>`;
        dropdown.classList.remove('d-none');
        return;
    }

    dropdown.innerHTML = `<div class="p-3 text-center text-muted small"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Mencari material "${query}"...</div>`;
    dropdown.classList.remove('d-none');

    itemSearchTimeout = setTimeout(async () => {
        const res = await apiRequest(`/api/master/barang.php?limit=10&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderItemDropdown(rowId, (res.data.items || []).slice(0, 10), query);
        } else {
            renderItemDropdown(rowId, [], query);
        }
    }, 250);
}

function renderItemDropdown(rowId, items, query = '') {
    const dropdown = document.getElementById(`dropdown_${rowId}`);
    if (!dropdown) return;

    let html = '';
    const cleanQ = query.trim().toLowerCase();

    if (items.length > 0) {
        items.forEach(item => {
            const imgSrc = item.foto1 ? `${BASE_URL}/${item.foto1}` : '';
            const imgHtml = imgSrc 
                ? `<img src="${imgSrc}" class="rounded border me-2 flex-shrink-0" style="width: 42px; height: 42px; object-fit: cover;" onerror="this.outerHTML='<div class=\\\'rounded border bg-light text-secondary d-flex align-items-center justify-content-center me-2 flex-shrink-0\\\' style=\\\'width: 42px; height: 42px;\\\'><i class=\\\'bi bi-box-seam fs-5\\\'></i></div>'">` 
                : `<div class="rounded border bg-light text-secondary d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 42px; height: 42px;"><i class="bi bi-box-seam fs-5"></i></div>`;
            
            const namaMerk = item.nama_merk || 'Umum';
            const namaKategori = item.nama_kategori || 'Material';

            html += `
                <div class="ro-item-dropdown-item d-flex align-items-center p-2" onclick="selectMasterBarang('${rowId}', ${item.id_barang}, '${item.kode_barang.replace(/'/g, "\\'")}', '${item.nama_barang.replace(/'/g, "\\'")}', '${item.satuan}')">
                    ${imgHtml}
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-bold text-dark small mb-1 text-truncate">${item.nama_barang}</div>
                        <div class="d-flex align-items-center flex-wrap gap-1" style="font-size: 0.72rem;">
                            <span class="badge bg-light text-dark border font-monospace">${item.kode_barang || 'BRG'}</span>
                            <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-tag-fill me-1"></i>${namaMerk}</span>
                            <span class="badge bg-primary-subtle text-primary">${namaKategori}</span>
                        </div>
                    </div>
                </div>
            `;
        });
    } else {
        html += `<div class="p-3 text-center text-muted small">Tidak ada barang katalog yang cocok dengan "<strong>${query}</strong>".</div>`;
    }

    if (cleanQ) {
        html += `
            <div class="ro-item-dropdown-item text-primary bg-primary-subtle border-top border-primary-subtle py-2 px-3 d-flex align-items-center justify-content-between" onclick="useCustomItemName('${rowId}', '${query.replace(/'/g, "\\'")}')">
                <span class="small"><i class="bi bi-pencil-square me-1"></i> Gunakan Material Kustom: <strong>"${query}"</strong></span>
                <span class="badge bg-primary text-white" style="font-size: 0.68rem;">Input Manual</span>
            </div>
        `;
    }

    dropdown.innerHTML = html;
    dropdown.classList.remove('d-none');
}

function selectMasterBarang(rowId, idBarang, kode, nama, satuan) {
    const row = document.getElementById(rowId);
    if (!row) return;

    row.querySelector('.item-id-barang').value = idBarang;
    row.querySelector('.item-nama-barang').value = nama;
    row.querySelector('.item-kode-barang').value = kode;
    row.querySelector('.item-satuan').value = (satuan === 'UNIT') ? 'UNIT' : 'PCS';
    row.querySelector('.item-harga').value = 0;

    document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
    calculateGrandTotal();
}

function useCustomItemName(rowId, customName) {
    const row = document.getElementById(rowId);
    if (!row) return;

    row.querySelector('.item-id-barang').value = '';
    row.querySelector('.item-nama-barang').value = customName;
    row.querySelector('.item-harga').value = 0;
    document.getElementById(`dropdown_${rowId}`)?.classList.add('d-none');
    calculateGrandTotal();
}

// Tutup dropdown jika klik di luar
document.addEventListener('click', (e) => {
    if (!e.target.closest('.ro-item-search-wrapper')) {
        document.querySelectorAll('.ro-item-search-wrapper .ro-item-dropdown').forEach(d => d.classList.add('d-none'));
    }
    if (!e.target.closest('#roVendorSearchWrapper')) {
        document.getElementById('roVendorDropdown')?.classList.add('d-none');
    }
});

function calculateGrandTotal() {
    const rows = document.querySelectorAll('.ro-item-row');
    let totalItems = rows.length;
    let totalQty = 0;

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        totalQty += qty;
    });

    document.getElementById('summaryTotalItems').textContent = `${totalItems} Jenis`;
    document.getElementById('summaryTotalQty').textContent = totalQty.toLocaleString('id-ID');
}

// -------------------------------------------------------------
// 6. SUBMIT EDIT REQUEST ORDER
// -------------------------------------------------------------
async function submitEditRequestOrder(targetStatus = 'DRAFT') {
    const form = document.getElementById('formEditRequestOrder');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const idKaryawan = document.getElementById('roIdKaryawan')?.value;
    const idSite = document.getElementById('roIdSite').value;
    const tanggalRo = document.getElementById('roTanggal').value;
    const prioritas = document.querySelector('input[name="roPrioritas"]:checked')?.value || 'NORMAL';
    const idVendor = document.getElementById('roIdVendor').value || null;
    const keterangan = document.getElementById('roKeterangan').value.trim();

    if (!idSite) {
        showToast('Site / lokasi pengadaan wajib dipilih!', 'warning');
        goToTab('tab-info-utama');
        document.getElementById('roIdSite').focus();
        return;
    }

    // Kumpulkan baris item material
    const itemRows = document.querySelectorAll('.ro-item-row');
    if (itemRows.length === 0) {
        showToast('Harap tambahkan minimal 1 baris kebutuhan material!', 'warning');
        goToTab('tab-material');
        return;
    }

    const items = [];
    let hasError = false;

    itemRows.forEach((row, idx) => {
        const nama = row.querySelector('.item-nama-barang').value.trim();
        const idBarang = row.querySelector('.item-id-barang').value || null;
        const kode = row.querySelector('.item-kode-barang').value.trim();
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const satuan = row.querySelector('.item-satuan').value;

        if (!nama) {
            showToast(`Nama barang pada baris ke-${idx + 1} tidak boleh kosong!`, 'warning');
            hasError = true;
            return;
        }
        if (qty <= 0) {
            showToast(`Qty barang pada baris ke-${idx + 1} harus lebih dari 0!`, 'warning');
            hasError = true;
            return;
        }

        items.push({
            id_barang: idBarang,
            kode_barang: kode,
            nama_barang: nama,
            qty: qty,
            satuan: satuan,
            harga: 0
        });
    });

    if (hasError) {
        goToTab('tab-material');
        return;
    }

    const payload = {
        id_request: ID_REQUEST,
        id_karyawan: idKaryawan,
        id_site: idSite,
        tanggal_ro: tanggalRo,
        prioritas: prioritas,
        id_vendor: idVendor,
        status: targetStatus,
        keterangan: keterangan,
        items: items
    };

    // Tombol loading state
    const btnSubmit = document.getElementById('btnSubmitRoBottom');
    const btnSaveDraft = document.getElementById('btnSaveDraftBottom');
    const originalTextSubmit = btnSubmit ? btnSubmit.innerHTML : '';
    const originalTextDraft = btnSaveDraft ? btnSaveDraft.innerHTML : '';

    if (btnSubmit) btnSubmit.disabled = true;
    if (btnSaveDraft) btnSaveDraft.disabled = true;
    if (targetStatus === 'TERKIRIM' && btnSubmit) {
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengirim ke Logistik...';
    } else if (btnSaveDraft) {
        btnSaveDraft.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan Draft...';
    }

    const res = await apiRequest('/api/request_order/update.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalTextSubmit;
    }
    if (btnSaveDraft) {
        btnSaveDraft.disabled = false;
        btnSaveDraft.innerHTML = originalTextDraft;
    }

    if (res && res.success) {
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/request_order/index.php`;
        }, 800);
    } else {
        showToast(res ? res.message : 'Gagal memperbarui Request Order.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
