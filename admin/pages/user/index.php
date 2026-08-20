<?php
/**
 * Master Data Karyawan / User - PT Jaya Teknis
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Karyawan';
$pageHeading = 'Master Data Karyawan & Personil';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-1">Daftar Karyawan &amp; Personil</h2>
        <p class="text-muted small mb-0">Database data lengkap personil mekanik, logistik, purchasing, dan manajemen</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari nama / NIK / email..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm fw-semibold" onclick="openTambahKaryawanModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Karyawan
        </button>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover table-custom align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>NIK / Kode</th>
                    <th>Nama Karyawan</th>
                    <th>Divisi</th>
                    <th>Kontak (Email / HP)</th>
                    <th>Status Kerja</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 160px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="karyawanTableBody">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data karyawan...
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

<!-- Modal Form Tambah / Edit Karyawan dengan Tab 2-Kolom -->
<div class="modal fade" id="karyawanFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="karyawanFormModalTitle">
                    <i class="bi bi-person-plus-fill me-2"></i>Tambah Karyawan Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="karyawanForm" onsubmit="handleSaveKaryawan(event)">
                <input type="hidden" id="formIdKaryawan" name="id_karyawan">
                
                <!-- Nav Tabs Modal Form -->
                <div class="bg-light px-4 pt-3 border-bottom">
                    <ul class="nav nav-tabs border-bottom-0" id="karyawanFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold small" id="kform-tab-1" data-bs-toggle="tab" data-bs-target="#kform-pane-1" type="button" role="tab">
                                <i class="bi bi-person-lines-fill me-1 text-primary"></i> 1. Identitas &amp; Divisi
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="kform-tab-2" data-bs-toggle="tab" data-bs-target="#kform-pane-2" type="button" role="tab">
                                <i class="bi bi-shield-lock me-1 text-primary"></i> 2. Akun &amp; Kontak
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="karyawanFormTabContent">
                        
                        <!-- TAB 1: IDENTITAS & DIVISI (2 KOLOM) -->
                        <div class="tab-pane fade show active" id="kform-pane-1" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">NIK / Kode (Opsional)</label>
                                    <input type="text" class="form-control" id="formKodeKaryawan" placeholder="Otomatis digenerate jika kosong">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="formNamaKaryawan" required placeholder="Contoh: Budi Santoso">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Divisi / Bagian <span class="text-danger">*</span></label>
                                    <select class="form-select" id="formDivisi" required>
                                        <option value="">-- Pilih Divisi --</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Status Ketenagakerjaan <span class="text-danger">*</span></label>
                                    <select class="form-select" id="formStatusKaryawan" required>
                                        <option value="3">PKWTT (Karyawan Tetap)</option>
                                        <option value="2">PKWT (Kontrak Waktu Tertentu)</option>
                                        <option value="1">Magang (Internship)</option>
                                        <option value="4">Pekerja Paruh Waktu (Part-time)</option>
                                        <option value="5">Harian Lepas (Casual Workers)</option>
                                        <option value="6">Freelance / Pekerja Lepas</option>
                                        <option value="7">Outsourcing / Alih Daya</option>
                                        <option value="8">Volunteer / Sukarelawan</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: AKUN & KONTAK (2 KOLOM) -->
                        <div class="tab-pane fade" id="kform-pane-2" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Email Resmi</label>
                                    <input type="email" class="form-control" id="formEmail" placeholder="karyawan@jayateknis.com">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">No. Handphone / WhatsApp</label>
                                    <input type="text" class="form-control" id="formNoHp" placeholder="081234567890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Password Login</label>
                                    <input type="password" class="form-control" id="formPassword" placeholder="Default: 123456 jika dikosongkan">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Akses Login Web</label>
                                    <select class="form-select" id="formLoginWeb">
                                        <option value="1">Diizinkan</option>
                                        <option value="0">Tidak</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold">Status Keaktifan</label>
                                    <select class="form-select" id="formAktif">
                                        <option value="1">Aktif</option>
                                        <option value="0">Non-aktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveKaryawan" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data Karyawan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Lengkap Karyawan dengan Tab 2-Kolom (Tanpa ID Teknis Database) -->
<div class="modal fade" id="karyawanDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-person-badge-fill me-2"></i>Rincian Data Karyawan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Nav Tabs Modal Detail -->
            <div class="bg-light px-4 pt-3 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="karyawanDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold small" id="kdetail-tab-1" data-bs-toggle="tab" data-bs-target="#kdetail-pane-1" type="button" role="tab">
                            <i class="bi bi-info-circle me-1 text-primary"></i> 1. Identitas &amp; Divisi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="kdetail-tab-2" data-bs-toggle="tab" data-bs-target="#kdetail-pane-2" type="button" role="tab">
                            <i class="bi bi-shield-lock me-1 text-primary"></i> 2. Akun &amp; Kontak
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="karyawanDetailTabContent">
                    
                    <!-- TAB 1: IDENTITAS & DIVISI (2 KOLOM) -->
                    <div class="tab-pane fade show active" id="kdetail-pane-1" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">NIK / Kode Karyawan</label>
                                <div class="fw-semibold font-monospace text-primary fs-6" id="modalKodeKaryawan">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Status Keaktifan</label>
                                <div id="modalAktif">-</div>
                            </div>
                            <div class="col-md-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nama Lengkap</label>
                                <div class="fw-bold text-dark fs-5" id="modalNamaKaryawan">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Divisi / Bagian</label>
                                <div id="modalDivisi">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Status Ketenagakerjaan</label>
                                <div id="modalStatusKerja">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: AKUN & KONTAK (2 KOLOM) -->
                    <div class="tab-pane fade" id="kdetail-pane-2" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Email Resmi</label>
                                <div id="modalEmail">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">No. Handphone / WhatsApp</label>
                                <div id="modalNoHp">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Tanggal Bergabung</label>
                                <div id="modalTglBergabung">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Akses Login Web</label>
                                <div id="modalLoginWeb">-</div>
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
let karyawanDataStore = [];
let divisiListCache = [];

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadKaryawan();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadKaryawan();
}

async function loadDivisiOptions() {
    const res = await apiRequest('/api/master/divisi.php');
    if (res && res.success) {
        divisiListCache = res.data.items || [];
        const select = document.getElementById('formDivisi');
        select.innerHTML = '<option value="">-- Pilih Divisi --</option>';
        divisiListCache.forEach(d => {
            select.innerHTML += `<option value="${d.id_divisi}">${d.nama_divisi} (${d.kode_divisi})</option>`;
        });
    }
}

async function loadKaryawan() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('karyawanTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/karyawan.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        karyawanDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (karyawanDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data karyawan ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        karyawanDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_karyawan || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_karyawan}</td>
                    <td><span class="badge bg-primary-subtle text-primary">${item.nama_divisi || 'Umum'}</span></td>
                    <td class="small">
                        <div><i class="bi bi-envelope text-muted me-1"></i>${item.email || '-'}</div>
                        <div><i class="bi bi-phone text-muted me-1"></i>${item.no_handphone || '-'}</div>
                    </td>
                    <td><span class="badge bg-info-subtle text-info">${item.status_karyawan_label}</span></td>
                    <td>
                        <span class="badge ${item.aktif ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary'}">
                            ${item.aktif ? 'Aktif' : 'Non-aktif'}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary py-1 px-2" onclick="showKaryawanDetail(${idx})" title="Lihat Rincian">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary py-1 px-2" onclick="openEditKaryawanModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-outline-danger py-1 px-2" onclick="deleteKaryawan(${item.id_karyawan}, '${item.nama_karyawan.replace(/'/g, "\\'")}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Gagal memuat data karyawan.</td></tr>`;
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

function openTambahKaryawanModal() {
    document.getElementById('karyawanForm').reset();
    document.getElementById('formIdKaryawan').value = '';
    document.getElementById('formStatusKaryawan').value = 3;
    document.getElementById('formLoginWeb').value = 1;
    document.getElementById('formAktif').value = 1;
    bootstrap.Tab.getOrCreateInstance(document.getElementById('kform-tab-1')).show();
    document.getElementById('karyawanFormModalTitle').innerHTML = '<i class="bi bi-person-plus-fill me-2"></i>Tambah Karyawan Baru';
    const modal = new bootstrap.Modal(document.getElementById('karyawanFormModal'));
    modal.show();
}

function openTambahUserModal() {
    openTambahKaryawanModal();
}

function openEditKaryawanModal(idx) {
    const item = karyawanDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdKaryawan').value = item.id_karyawan;
    document.getElementById('formKodeKaryawan').value = item.kode_karyawan;
    document.getElementById('formNamaKaryawan').value = item.nama_karyawan;
    document.getElementById('formDivisi').value = item.id_divisi;
    document.getElementById('formStatusKaryawan').value = item.status_karyawan_id;
    document.getElementById('formEmail').value = item.email !== '-' ? item.email : '';
    document.getElementById('formNoHp').value = item.no_handphone !== '-' ? item.no_handphone : '';
    document.getElementById('formPassword').value = '';
    document.getElementById('formLoginWeb').value = item.login_web;
    document.getElementById('formAktif').value = item.aktif;
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('kform-tab-1')).show();
    document.getElementById('karyawanFormModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Data Karyawan';
    const modal = new bootstrap.Modal(document.getElementById('karyawanFormModal'));
    modal.show();
}

async function handleSaveKaryawan(e) {
    e.preventDefault();
    const id = document.getElementById('formIdKaryawan').value;
    const isEdit = id !== '';
    
    const payload = {
        id_karyawan: id,
        kode_karyawan: document.getElementById('formKodeKaryawan').value.trim(),
        nama_karyawan: document.getElementById('formNamaKaryawan').value.trim(),
        id_divisi: document.getElementById('formDivisi').value,
        status_karyawan: document.getElementById('formStatusKaryawan').value,
        email: document.getElementById('formEmail').value.trim(),
        no_handphone: document.getElementById('formNoHp').value.trim(),
        password: document.getElementById('formPassword').value.trim(),
        login_web: document.getElementById('formLoginWeb').value,
        aktif: document.getElementById('formAktif').value,
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/karyawan.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    if (res && res.success) {
        showToast(res.message || 'Data karyawan berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('karyawanFormModal')).hide();
        loadKaryawan();
    } else {
        showToast(res.message || 'Gagal menyimpan data karyawan.', 'error');
    }
}

async function deleteKaryawan(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus karyawan "${name}"?`)) return;
    
    const res = await apiRequest('/api/master/karyawan.php', {
        method: 'POST',
        body: JSON.stringify({ id_karyawan: id, _method: 'DELETE' })
    });
    
    if (res && res.success) {
        showToast('Karyawan berhasil dihapus.', 'success');
        loadKaryawan();
    } else {
        showToast(res.message || 'Gagal menghapus karyawan.', 'error');
    }
}

function showKaryawanDetail(idx) {
    const item = karyawanDataStore[idx];
    if (!item) return;
    
    document.getElementById('modalKodeKaryawan').textContent = item.kode_karyawan || '-';
    document.getElementById('modalNamaKaryawan').textContent = item.nama_karyawan;
    document.getElementById('modalDivisi').innerHTML = `<span class="badge bg-primary-subtle text-primary">${item.nama_divisi} (${item.kode_divisi})</span>`;
    document.getElementById('modalEmail').textContent = item.email || '-';
    document.getElementById('modalNoHp').textContent = item.no_handphone || '-';
    document.getElementById('modalStatusKerja').innerHTML = `<span class="badge bg-info-subtle text-info">${item.status_karyawan_label}</span>`;
    document.getElementById('modalTglBergabung').textContent = item.tanggal_bergabung;
    document.getElementById('modalLoginWeb').innerHTML = item.login_web ? '<span class="badge bg-success">Diizinkan</span>' : '<span class="badge bg-secondary">Tidak Diizinkan</span>';
    document.getElementById('modalAktif').innerHTML = item.aktif ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-danger">Non-aktif</span>';
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('kdetail-tab-1')).show();
    const modal = new bootstrap.Modal(document.getElementById('karyawanDetailModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    loadDivisiOptions();
    loadKaryawan();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
