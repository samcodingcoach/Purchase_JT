<?php
/**
 * Master Data Vendor - PT Jaya Teknis
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Vendor';
$pageHeading = 'Master Data Vendor & Supplier';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-1">Daftar Rekanan Vendor &amp; Supplier</h2>
        <p class="text-muted small mb-0">Database rekanan supplier plat baja, gas, kawat las &amp; permesinan kapal</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari vendor / kota / kontak..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm fw-semibold" onclick="openTambahVendorModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Vendor
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode</th>
                    <th>Nama Perusahaan</th>
                    <th>Kontak &amp; Telepon</th>
                    <th>Kota</th>
                    <th>Jenis Rekanan</th>
                    <th>Term (TOP)</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="vendorTableBody">
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data vendor...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Pagination Footer -->
    <div class="card-footer bg-white py-3 d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 border-top">
        <div class="text-muted small" id="paginationInfo">
            Menampilkan data...
        </div>
        <nav aria-label="Navigasi Halaman">
            <ul class="pagination pagination-sm mb-0" id="paginationControls">
            </ul>
        </nav>
    </div>
</div>

<!-- Modal Form Tambah / Edit Vendor dengan Tab 2-Kolom -->
<div class="modal fade" id="vendorFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="vendorFormModalTitle">
                    <i class="bi bi-buildings-fill me-2"></i>Tambah Vendor Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="vendorForm" onsubmit="handleSaveVendor(event)">
                <input type="hidden" id="formIdVendor" name="id_vendor">
                
                <!-- Nav Tabs Modal Form -->
                <div class="bg-light px-4 pt-3 border-bottom">
                    <ul class="nav nav-tabs border-bottom-0" id="vendorFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold small" id="vform-tab-1" data-bs-toggle="tab" data-bs-target="#vform-pane-1" type="button" role="tab">
                                <i class="bi bi-person-lines-fill me-1 text-primary"></i> 1. Profil &amp; PIC
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="vform-tab-2" data-bs-toggle="tab" data-bs-target="#vform-pane-2" type="button" role="tab">
                                <i class="bi bi-credit-card-2-front me-1 text-primary"></i> 2. Keuangan &amp; Domisili
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="vendorFormTabContent">
                        
                        <!-- TAB 1: PROFIL & PIC (2 KOLOM) -->
                        <div class="tab-pane fade show active" id="vform-pane-1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kode Vendor (Opsional)</label>
                                    <input type="text" class="form-control" id="formKodeVendor" placeholder="Otomatis digenerate jika kosong">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Perusahaan / Supplier <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="formNamaPerusahaan" required placeholder="Contoh: PT Baja Maritim Nusantara">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Contact Person (PIC)</label>
                                    <input type="text" class="form-control" id="formPerson" placeholder="Nama petugas / marketing">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jabatan PIC</label>
                                    <input type="text" class="form-control" id="formKontakPerson" placeholder="Contoh: Sales Manager">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor Telepon / Kontak</label>
                                    <input type="text" class="form-control" id="formNoTelepon" placeholder="Contoh: 031-778811 / 0812...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Email Resmi</label>
                                    <input type="email" class="form-control" id="formEmail" placeholder="vendor@domain.com">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: KEUANGAN & DOMISILI (2 KOLOM) -->
                        <div class="tab-pane fade" id="vform-pane-2" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kota</label>
                                    <input type="text" class="form-control" id="formKota" placeholder="Contoh: Surabaya">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Jenis Vendor</label>
                                    <input type="text" class="form-control" id="formJenisVendor" placeholder="Contoh: Distributor Kawat Las">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Term of Payment (Hari)</label>
                                    <input type="number" class="form-control" id="formTop" value="30" min="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Status Keaktifan</label>
                                    <select class="form-select" id="formAktif">
                                        <option value="1">Aktif</option>
                                        <option value="0">Non-aktif</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Bank</label>
                                    <input type="text" class="form-control" id="formNamaBank" placeholder="BCA / Mandiri / BNI">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor Rekening</label>
                                    <input type="text" class="form-control" id="formNomorRekening" placeholder="Nomor rekening transfer">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label small fw-bold">Website / Link Company</label>
                                    <input type="text" class="form-control" id="formWebsite" placeholder="https://...">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Alamat Kantor / Workshop</label>
                                    <textarea class="form-control" id="formAlamat" rows="3" placeholder="Alamat fisik perusahaan"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Keterangan / Catatan Rekanan</label>
                                    <textarea class="form-control" id="formKeterangan" rows="3" placeholder="Catatan keandalan suplai, syarat minimal order, dll"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveVendor" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data Vendor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Lengkap Vendor dengan Tab 2-Kolom (Tanpa ID Teknis Database) -->
<div class="modal fade" id="vendorDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-buildings-fill me-2"></i>Rincian Data Rekanan Vendor
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Nav Tabs Modal Detail -->
            <div class="bg-light px-4 pt-3 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="vendorDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold small" id="vdetail-tab-1" data-bs-toggle="tab" data-bs-target="#vdetail-pane-1" type="button" role="tab">
                            <i class="bi bi-info-circle me-1 text-primary"></i> 1. Profil &amp; PIC
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="vdetail-tab-2" data-bs-toggle="tab" data-bs-target="#vdetail-pane-2" type="button" role="tab">
                            <i class="bi bi-credit-card-2-front me-1 text-primary"></i> 2. Keuangan &amp; Domisili
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="vendorDetailTabContent">
                    
                    <!-- TAB 1: PROFIL & PIC (2 KOLOM) -->
                    <div class="tab-pane fade show active" id="vdetail-pane-1" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kode Vendor</label>
                                <div class="fw-semibold font-monospace text-primary fs-6" id="modalKodeVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Status Rekanan</label>
                                <div id="modalAktifVendor">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nama Perusahaan / Supplier</label>
                                <div class="fw-bold text-dark fs-5" id="modalNamaVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Contact Person (PIC)</label>
                                <div id="modalPersonVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nomor Telepon / Kontak</label>
                                <div id="modalTeleponVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Email Resmi</label>
                                <div id="modalEmailVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Website</label>
                                <div id="modalWebsiteVendor">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: KEUANGAN & DOMISILI (2 KOLOM) -->
                    <div class="tab-pane fade" id="vdetail-pane-2" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kota &amp; Wilayah</label>
                                <div id="modalKotaVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Jenis Vendor</label>
                                <div id="modalJenisVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Rekening Bank</label>
                                <div id="modalRekeningVendor">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Term of Payment (TOP)</label>
                                <div id="modalTopVendor">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Alamat Kantor / Workshop</label>
                                <div class="p-2 bg-light rounded border small" id="modalAlamatVendor">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Keterangan / Catatan Rekanan</label>
                                <div class="p-2 bg-light rounded border text-muted small" id="modalKeteranganVendor">-</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
const fixedLimit = 50;
let searchTimeout = null;
let vendorDataStore = [];

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadVendor();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadVendor();
}

async function loadVendor() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('vendorTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/vendor.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        vendorDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (vendorDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-muted">Tidak ada data vendor ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        vendorDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_vendor || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_perusahaan}</td>
                    <td class="small">
                        <div><i class="bi bi-telephone text-muted me-1"></i>${item.no_telepon || '-'}</div>
                        ${item.person ? `<div><i class="bi bi-person text-muted me-1"></i>${item.person}</div>` : ''}
                    </td>
                    <td class="text-muted">${item.kota || '-'}</td>
                    <td><span class="badge bg-info-subtle text-info">${item.jenis_vendor || 'Supplier'}</span></td>
                    <td><span class="badge bg-light text-dark border">${item.term_of_payment} Hari</span></td>
                    <td>
                        <span class="badge ${item.aktif ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'}">
                            ${item.aktif ? 'Aktif' : 'Non-aktif'}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary py-1 px-2" onclick="showVendorDetail(${idx})" title="Lihat Rincian">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary py-1 px-2" onclick="openEditVendorModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-outline-danger py-1 px-2" onclick="deleteVendor(${item.id_vendor}, '${item.nama_perusahaan.replace(/'/g, "\\'")}')" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        paginationInfo.textContent = `Menampilkan ${pag.from} - ${pag.to} dari ${pag.total_records} data (Total: ${pag.total_pages} Halaman)`;
        renderPagination(pag);
    } else {
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-4 text-danger">Gagal memuat data vendor.</td></tr>`;
    }
}

function renderPagination(pag) {
    const controls = document.getElementById('paginationControls');
    let html = '';
    
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
    
    html += `
        <li class="page-item ${pag.page >= pag.total_pages ? 'disabled' : ''}">
            <a class="page-link" href="javascript:void(0)" onclick="goToPage(${pag.page + 1})">Next &raquo;</a>
        </li>
    `;
    
    controls.innerHTML = html;
}

function openTambahVendorModal() {
    document.getElementById('vendorForm').reset();
    document.getElementById('formIdVendor').value = '';
    document.getElementById('formTop').value = 30;
    document.getElementById('formAktif').value = 1;
    bootstrap.Tab.getOrCreateInstance(document.getElementById('vform-tab-1')).show();
    document.getElementById('vendorFormModalTitle').innerHTML = '<i class="bi bi-buildings-fill me-2"></i>Tambah Vendor Baru';
    const modal = new bootstrap.Modal(document.getElementById('vendorFormModal'));
    modal.show();
}

function openEditVendorModal(idx) {
    const item = vendorDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdVendor').value = item.id_vendor;
    document.getElementById('formKodeVendor').value = item.kode_vendor;
    document.getElementById('formNamaPerusahaan').value = item.nama_perusahaan;
    document.getElementById('formPerson').value = item.person !== '-' ? item.person : '';
    document.getElementById('formKontakPerson').value = item.kontak_person !== '-' ? item.kontak_person : '';
    document.getElementById('formNoTelepon').value = item.no_telepon !== '-' ? item.no_telepon : '';
    document.getElementById('formEmail').value = item.email !== '-' ? item.email : '';
    document.getElementById('formKota').value = item.kota !== '-' ? item.kota : '';
    document.getElementById('formJenisVendor').value = item.jenis_vendor !== '-' ? item.jenis_vendor : '';
    document.getElementById('formTop').value = item.term_of_payment;
    document.getElementById('formNamaBank').value = item.nama_bank !== '-' ? item.nama_bank : '';
    document.getElementById('formNomorRekening').value = item.nomor_rekening !== '-' ? item.nomor_rekening : '';
    document.getElementById('formWebsite').value = item.website !== '-' ? item.website : '';
    document.getElementById('formAktif').value = item.aktif;
    document.getElementById('formAlamat').value = item.alamat !== '-' ? item.alamat : '';
    document.getElementById('formKeterangan').value = item.keterangan !== '-' ? item.keterangan : '';
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('vform-tab-1')).show();
    document.getElementById('vendorFormModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Data Vendor';
    const modal = new bootstrap.Modal(document.getElementById('vendorFormModal'));
    modal.show();
}

async function handleSaveVendor(e) {
    e.preventDefault();
    const id = document.getElementById('formIdVendor').value;
    const isEdit = id !== '';
    
    const payload = {
        id_vendor: id,
        kode_vendor: document.getElementById('formKodeVendor').value.trim(),
        nama_perusahaan: document.getElementById('formNamaPerusahaan').value.trim(),
        person: document.getElementById('formPerson').value.trim(),
        kontak_person: document.getElementById('formKontakPerson').value.trim(),
        no_telepon: document.getElementById('formNoTelepon').value.trim(),
        email: document.getElementById('formEmail').value.trim(),
        kota: document.getElementById('formKota').value.trim(),
        jenis_vendor: document.getElementById('formJenisVendor').value.trim(),
        term_of_payment: document.getElementById('formTop').value,
        nama_bank: document.getElementById('formNamaBank').value.trim(),
        nomor_rekening: document.getElementById('formNomorRekening').value.trim(),
        website: document.getElementById('formWebsite').value.trim(),
        aktif: document.getElementById('formAktif').value,
        alamat: document.getElementById('formAlamat').value.trim(),
        keterangan: document.getElementById('formKeterangan').value.trim(),
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/vendor.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    if (res && res.success) {
        showToast(res.message || 'Data vendor berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('vendorFormModal')).hide();
        loadVendor();
    } else {
        showToast(res.message || 'Gagal menyimpan data vendor.', 'error');
    }
}

async function deleteVendor(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus vendor "${name}"?`)) return;
    
    const res = await apiRequest('/api/master/vendor.php', {
        method: 'POST',
        body: JSON.stringify({ id_vendor: id, _method: 'DELETE' })
    });
    
    if (res && res.success) {
        showToast('Vendor berhasil dihapus.', 'success');
        loadVendor();
    } else {
        showToast(res.message || 'Gagal menghapus vendor.', 'error');
    }
}

function showVendorDetail(idx) {
    const item = vendorDataStore[idx];
    if (!item) return;
    
    document.getElementById('modalKodeVendor').textContent = item.kode_vendor || '-';
    document.getElementById('modalNamaVendor').textContent = item.nama_perusahaan;
    document.getElementById('modalPersonVendor').textContent = item.person ? `${item.person} (${item.kontak_person || 'PIC'})` : '-';
    document.getElementById('modalTeleponVendor').textContent = item.no_telepon || '-';
    document.getElementById('modalEmailVendor').textContent = item.email || '-';
    document.getElementById('modalWebsiteVendor').textContent = item.website || '-';
    document.getElementById('modalKotaVendor').textContent = item.kota || '-';
    document.getElementById('modalJenisVendor').innerHTML = `<span class="badge bg-info-subtle text-info">${item.jenis_vendor}</span>`;
    document.getElementById('modalRekeningVendor').textContent = item.nomor_rekening ? `${item.nama_bank} - ${item.nomor_rekening}` : '-';
    document.getElementById('modalTopVendor').textContent = `${item.term_of_payment} Hari`;
    document.getElementById('modalAktifVendor').innerHTML = item.aktif ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Non-aktif</span>';
    document.getElementById('modalAlamatVendor').textContent = item.alamat;
    document.getElementById('modalKeteranganVendor').textContent = item.keterangan;
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('vdetail-tab-1')).show();
    const modal = new bootstrap.Modal(document.getElementById('vendorDetailModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', loadVendor);
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
