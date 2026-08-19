<?php
/**
 * Master Data Barang - PT Jaya Teknis
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Master Barang';
$pageHeading = 'Master Data Barang & Material';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fs-4 fw-bold text-dark mb-1">Katalog Master Barang &amp; Material</h2>
        <p class="text-muted small mb-0">Database inventaris suku cadang, plat baja, kawat las, foto produk &amp; peralatan perkapalan</p>
    </div>
    <!-- Action Controls: Search di kiri, Tombol Tambah di paling kanan -->
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <div class="input-group input-group-sm" style="width: 260px;">
            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
            <input type="text" id="searchInput" class="form-control" placeholder="Cari nama / kode / serial..." oninput="debounceSearch()">
        </div>
        <button class="btn btn-primary btn-sm fw-semibold" onclick="openTambahBarangModal()">
            <i class="bi bi-plus-circle-fill me-1"></i> Tambah Barang
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
                    <th>Nama Barang / Material</th>
                    <th>Satuan</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="barangTableBody">
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data barang...
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

<!-- Modal Form Tambah / Edit Barang dengan 4 Tab Rapi & 2 Kolom -->
<div class="modal fade" id="barangFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="barangFormModalTitle">
                    <i class="bi bi-box-seam-fill me-2"></i>Tambah Barang Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="barangForm" onsubmit="handleSaveBarang(event)">
                <input type="hidden" id="formIdBarang" name="id_barang">
                <input type="hidden" id="formFoto1Val" name="foto1">
                <input type="hidden" id="formFoto2Val" name="foto2">
                <input type="hidden" id="formKategoriIdVal" name="id_kategori">
                <input type="hidden" id="formMerkIdVal" name="id_merk">
                <input type="hidden" id="formVendorIdVal" name="default_id_vendor">
                
                <!-- Nav Tabs 4 Kategori -->
                <div class="bg-light px-4 pt-3 border-bottom">
                    <ul class="nav nav-tabs border-bottom-0" id="barangFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold small" id="bform-tab-utama" data-bs-toggle="tab" data-bs-target="#bform-pane-utama" type="button" role="tab">
                                <i class="bi bi-tag me-1 text-primary"></i> 1. Utama
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="bform-tab-vendor" data-bs-toggle="tab" data-bs-target="#bform-pane-vendor" type="button" role="tab">
                                <i class="bi bi-truck me-1 text-primary"></i> 2. Vendor
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="bform-tab-foto" data-bs-toggle="tab" data-bs-target="#bform-pane-foto" type="button" role="tab">
                                <i class="bi bi-images me-1 text-primary"></i> 3. Foto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="bform-tab-tambahan" data-bs-toggle="tab" data-bs-target="#bform-pane-tambahan" type="button" role="tab">
                                <i class="bi bi-sliders me-1 text-primary"></i> 4. Tambahan
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="modal-body p-4">
                    <div class="tab-content" id="barangFormTabContent">
                        
                        <!-- TAB 1: UTAMA (2 KOLOM) -->
                        <div class="tab-pane fade show active" id="bform-pane-utama" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kode Barang</label>
                                    <input type="text" class="form-control" id="formKodeBarang" placeholder="Otomatis jika kosong">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nama Barang / Material <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="formNamaBarang" required placeholder="Contoh: Plat Baja Marine AH36">
                                </div>
                                
                                <!-- Searchable Kategori -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Kategori Barang <span class="text-danger">*</span></label>
                                    <div class="searchable-select-wrapper" id="kategoriSearchWrapper">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="kategoriSearchInput" required placeholder="Pilih / cari kategori..." autocomplete="off" onfocus="openKategoriDropdown()" oninput="debounceKategoriSearch()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearKategoriSelection()" title="Hapus">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="searchable-select-menu d-none" id="kategoriDropdownMenu">
                                            <div id="kategoriDropdownList">
                                                <div class="p-2 text-center text-muted small">Memuat kategori...</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text small" id="selectedKategoriLabel">Belum memilih kategori.</div>
                                </div>

                                <!-- Searchable Merk -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Merk / Brand</label>
                                    <div class="searchable-select-wrapper" id="merkSearchWrapper">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="merkSearchInput" placeholder="Pilih / cari merk..." autocomplete="off" onfocus="openMerkDropdown()" oninput="debounceMerkSearch()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearMerkSelection()" title="Hapus">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="searchable-select-menu d-none" id="merkDropdownMenu">
                                            <div id="merkDropdownList">
                                                <div class="p-2 text-center text-muted small">Memuat merk...</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text small" id="selectedMerkLabel">Belum memilih merk.</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Satuan <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formSatuanRadio" id="satuanPCS" value="PCS" checked>
                                        <label class="btn btn-outline-secondary btn-sm" for="satuanPCS">PCS</label>
                                        
                                        <input type="radio" class="btn-check" name="formSatuanRadio" id="satuanUNIT" value="UNIT">
                                        <label class="btn btn-outline-secondary btn-sm" for="satuanUNIT">UNIT</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Jenis Barang <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formJenisRadio" id="jenisPersediaan" value="1" checked>
                                        <label class="btn btn-outline-primary btn-sm" for="jenisPersediaan">Persediaan</label>
                                        
                                        <input type="radio" class="btn-check" name="formJenisRadio" id="jenisJasa" value="0">
                                        <label class="btn btn-outline-primary btn-sm" for="jenisJasa">Jasa</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: VENDOR (2 KOLOM) -->
                        <div class="tab-pane fade" id="bform-pane-vendor" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Default Vendor Rekanan (Searchable)</label>
                                    <div class="searchable-select-wrapper" id="vendorSearchWrapper">
                                        <div class="input-group">
                                            <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                            <input type="text" class="form-control" id="vendorSearchInput" placeholder="Ketik nama / kode vendor..." autocomplete="off" onfocus="openVendorDropdown()" oninput="debounceVendorSearch()">
                                            <button type="button" class="btn btn-outline-secondary" onclick="clearVendorSelection()" title="Hapus Pilihan">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </div>
                                        <div class="searchable-select-menu d-none" id="vendorDropdownMenu">
                                            <div id="vendorDropdownList">
                                                <div class="p-2 text-center text-muted small">Memuat vendor...</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-text small" id="selectedVendorLabel">Belum ada vendor rekanan yang dipilih.</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: FOTO (2 KOLOM) - PREVIEW LOKAL (UPLOAD SAAT SIMPAN) -->
                        <div class="tab-pane fade" id="bform-pane-foto" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Foto Produk 1 (Utama)</label>
                                    <input type="file" class="form-control form-control-sm mb-2" id="formFile1" accept="image/*" onchange="handleLocalFileSelect(this, 1)">
                                    <div class="border rounded bg-light p-2 text-center position-relative" style="height: 140px; display: flex; align-items: center; justify-content: center;">
                                        <img id="previewFoto1" src="" class="img-fluid rounded d-none" style="max-height: 120px; object-fit: contain;">
                                        <div id="placeholderFoto1" class="text-muted small"><i class="bi bi-image me-1"></i>Belum ada Foto 1</div>
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-1 d-none" id="btnRemoveFoto1" onclick="removeProductImage(1)" title="Hapus Foto 1">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <div class="form-text small text-muted">Gambar akan diunggah otomatis ke server saat Anda klik tombol Simpan.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Foto Produk 2 (Detail)</label>
                                    <input type="file" class="form-control form-control-sm mb-2" id="formFile2" accept="image/*" onchange="handleLocalFileSelect(this, 2)">
                                    <div class="border rounded bg-light p-2 text-center position-relative" style="height: 140px; display: flex; align-items: center; justify-content: center;">
                                        <img id="previewFoto2" src="" class="img-fluid rounded d-none" style="max-height: 120px; object-fit: contain;">
                                        <div id="placeholderFoto2" class="text-muted small"><i class="bi bi-image me-1"></i>Belum ada Foto 2</div>
                                        <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-1 d-none" id="btnRemoveFoto2" onclick="removeProductImage(2)" title="Hapus Foto 2">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                    <div class="form-text small text-muted">Gambar akan diunggah otomatis ke server saat Anda klik tombol Simpan.</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: TAMBAHAN (2 KOLOM) -->
                        <div class="tab-pane fade" id="bform-pane-tambahan" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Klasifikasi Asset</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAssetRadio" id="assetNon" value="0" checked>
                                        <label class="btn btn-outline-secondary btn-sm" for="assetNon">Bukan Asset</label>
                                        
                                        <input type="radio" class="btn-check" name="formAssetRadio" id="assetYes" value="1">
                                        <label class="btn btn-outline-warning btn-sm" for="assetYes">Asset</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Status Keaktifan</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="aktifYes" value="1" checked>
                                        <label class="btn btn-outline-success btn-sm" for="aktifYes">Aktif</label>
                                        
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="aktifNo" value="0">
                                        <label class="btn btn-outline-danger btn-sm" for="aktifNo">Non-aktif</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Serial Number / Part No.</label>
                                    <input type="text" class="form-control" id="formSerialNumber" placeholder="Nomor seri jika ada">
                                </div>
                                <div class="col-md-6" id="formCreatedAtWrapper" style="display: none;">
                                    <label class="form-label small fw-bold">Tanggal Dibuat</label>
                                    <input type="text" class="form-control bg-light" id="formCreatedAt" readonly>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Deskripsi &amp; Spesifikasi Teknis Material</label>
                                    <textarea class="form-control" id="formDeskripsi" rows="3" placeholder="Rincian dimensi ukuran, sertifikasi badan klasifikasi kapal (BKI/LR), grade material..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSaveBarang" class="btn btn-primary btn-sm fw-semibold">
                        <i class="bi bi-save me-1"></i> Simpan Data Barang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Detail Lengkap Barang (4 Tab Bersih & 2 Kolom) -->
<div class="modal fade" id="barangDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-box-seam-fill me-2"></i>Rincian Data Barang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Nav Tabs Modal Detail -->
            <div class="bg-light px-4 pt-3 border-bottom">
                <ul class="nav nav-tabs border-bottom-0" id="barangDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold small" id="bdetail-tab-utama" data-bs-toggle="tab" data-bs-target="#bdetail-pane-utama" type="button" role="tab">
                            <i class="bi bi-tag me-1 text-primary"></i> 1. Utama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="bdetail-tab-vendor" data-bs-toggle="tab" data-bs-target="#bdetail-pane-vendor" type="button" role="tab">
                            <i class="bi bi-truck me-1 text-primary"></i> 2. Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="bdetail-tab-foto" data-bs-toggle="tab" data-bs-target="#bdetail-pane-foto" type="button" role="tab">
                            <i class="bi bi-images me-1 text-primary"></i> 3. Foto
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="bdetail-tab-tambahan" data-bs-toggle="tab" data-bs-target="#bdetail-pane-tambahan" type="button" role="tab">
                            <i class="bi bi-sliders me-1 text-primary"></i> 4. Tambahan
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4">
                <div class="tab-content" id="barangDetailTabContent">
                    
                    <!-- TAB 1: UTAMA (2 KOLOM) -->
                    <div class="tab-pane fade show active" id="bdetail-pane-utama" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kode Barang</label>
                                <div class="fw-semibold font-monospace text-primary fs-6" id="modalKodeBarang">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Satuan</label>
                                <div id="modalSatuanBarang">-</div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Nama Barang / Material</label>
                                <div class="fw-bold text-dark fs-5" id="modalNamaBarang">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Kategori</label>
                                <div class="fw-semibold text-dark" id="modalKategoriBarang">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Merk / Brand</label>
                                <div class="fw-semibold text-dark" id="modalMerkBarang">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Jenis</label>
                                <div id="modalJenisBarang">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: VENDOR (2 KOLOM) -->
                    <div class="tab-pane fade" id="bdetail-pane-vendor" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Default Vendor Rekanan</label>
                                <div class="fw-semibold text-dark fs-6" id="modalDefaultVendor">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: FOTO (2 KOLOM) -->
                    <div class="tab-pane fade" id="bdetail-pane-foto" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Foto 1 (Utama)</label>
                                <div class="p-2 border rounded bg-light text-center" style="min-height: 140px; display: flex; align-items: center; justify-content: center;" id="modalFoto1Container">
                                    <span class="text-muted small">Tidak ada foto</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Foto 2 (Detail)</label>
                                <div class="p-2 border rounded bg-light text-center" style="min-height: 140px; display: flex; align-items: center; justify-content: center;" id="modalFoto2Container">
                                    <span class="text-muted small">Tidak ada foto</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: TAMBAHAN (2 KOLOM) -->
                    <div class="tab-pane fade" id="bdetail-pane-tambahan" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Klasifikasi Asset</label>
                                <div id="modalAssetBarang">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Status</label>
                                <div id="modalAktifBarang">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Serial Number / Part No.</label>
                                <div class="font-monospace text-muted" id="modalSerialBarang">-</div>
                            </div>
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Tanggal Dibuat</label>
                                <div class="text-muted small" id="modalCreatedAt">-</div>
                            </div>
                            <div class="col-12">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Deskripsi &amp; Spesifikasi</label>
                                <div class="p-3 bg-light rounded border text-muted small" id="modalDeskripsiBarang">-</div>
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
const fixedLimit = 50; // Baku 50 per halaman
let searchTimeout = null;
let vendorSearchTimeout = null;
let kategoriSearchTimeout = null;
let merkSearchTimeout = null;

let barangDataStore = [];
let vendorListCache = [];
let kategoriListCache = [];
let merkListCache = [];

// Local file storage for deferred upload on form submit
let pendingFile1 = null;
let pendingFile2 = null;

function debounceSearch() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        currentPage = 1;
        loadBarang();
    }, 300);
}

function goToPage(page) {
    currentPage = page;
    loadBarang();
}

// -------------------------------------------------------------
// 1. Searchable Kategori Select Dropdown
// -------------------------------------------------------------
function debounceKategoriSearch() {
    clearTimeout(kategoriSearchTimeout);
    kategoriSearchTimeout = setTimeout(() => {
        fetchKategoriOptions(document.getElementById('kategoriSearchInput').value.trim());
    }, 300);
}

function openKategoriDropdown() {
    document.getElementById('kategoriDropdownMenu').classList.remove('d-none');
    if (kategoriListCache.length === 0) {
        fetchKategoriOptions('');
    }
}

async function fetchKategoriOptions(query) {
    const listEl = document.getElementById('kategoriDropdownList');
    listEl.innerHTML = '<div class="p-2 text-center text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Mencari kategori...</div>';
    
    const url = `/api/master/kategori.php?limit=10&q=${encodeURIComponent(query)}`;
    const res = await apiRequest(url);
    if (res && res.success) {
        kategoriListCache = res.data.items || [];
        if (kategoriListCache.length === 0) {
            listEl.innerHTML = '<div class="p-2 text-center text-muted small">Kategori tidak ditemukan.</div>';
            return;
        }
        let html = '';
        kategoriListCache.forEach(k => {
            html += `
                <div class="searchable-select-item" onclick="selectKategoriOption(${k.id_kategori}, '${k.nama_kategori.replace(/'/g, "\\'")}', '${k.kode_kategori}')">
                    <span class="fw-semibold text-dark">${k.nama_kategori}</span>
                    <span class="badge bg-light text-muted border ms-1 font-monospace">${k.kode_kategori}</span>
                </div>
            `;
        });
        listEl.innerHTML = html;
    }
}

function selectKategoriOption(id, name, code) {
    document.getElementById('formKategoriIdVal').value = id;
    document.getElementById('kategoriSearchInput').value = name;
    document.getElementById('selectedKategoriLabel').innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Terpilih: ${name}</span>`;
    document.getElementById('kategoriDropdownMenu').classList.add('d-none');
}

function clearKategoriSelection() {
    document.getElementById('formKategoriIdVal').value = '';
    document.getElementById('kategoriSearchInput').value = '';
    document.getElementById('selectedKategoriLabel').textContent = 'Belum memilih kategori.';
}

// -------------------------------------------------------------
// 2. Searchable Merk Select Dropdown
// -------------------------------------------------------------
function debounceMerkSearch() {
    clearTimeout(merkSearchTimeout);
    merkSearchTimeout = setTimeout(() => {
        fetchMerkOptions(document.getElementById('merkSearchInput').value.trim());
    }, 300);
}

function openMerkDropdown() {
    document.getElementById('merkDropdownMenu').classList.remove('d-none');
    if (merkListCache.length === 0) {
        fetchMerkOptions('');
    }
}

async function fetchMerkOptions(query) {
    const listEl = document.getElementById('merkDropdownList');
    listEl.innerHTML = '<div class="p-2 text-center text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Mencari merk...</div>';
    
    const url = `/api/master/merk.php?limit=10&q=${encodeURIComponent(query)}`;
    const res = await apiRequest(url);
    if (res && res.success) {
        merkListCache = res.data.items || [];
        if (merkListCache.length === 0) {
            listEl.innerHTML = '<div class="p-2 text-center text-muted small">Merk tidak ditemukan.</div>';
            return;
        }
        let html = '';
        merkListCache.forEach(m => {
            html += `
                <div class="searchable-select-item" onclick="selectMerkOption(${m.id_merk}, '${m.nama_merk.replace(/'/g, "\\'")}', '${m.kode_merk}')">
                    <span class="fw-semibold text-dark">${m.nama_merk}</span>
                    <span class="badge bg-light text-muted border ms-1 font-monospace">${m.kode_merk}</span>
                </div>
            `;
        });
        listEl.innerHTML = html;
    }
}

function selectMerkOption(id, name, code) {
    document.getElementById('formMerkIdVal').value = id;
    document.getElementById('merkSearchInput').value = name;
    document.getElementById('selectedMerkLabel').innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Terpilih: ${name}</span>`;
    document.getElementById('merkDropdownMenu').classList.add('d-none');
}

function clearMerkSelection() {
    document.getElementById('formMerkIdVal').value = '';
    document.getElementById('merkSearchInput').value = '';
    document.getElementById('selectedMerkLabel').textContent = 'Belum memilih merk.';
}

// -------------------------------------------------------------
// 3. Searchable Vendor Select Dropdown
// -------------------------------------------------------------
function debounceVendorSearch() {
    clearTimeout(vendorSearchTimeout);
    vendorSearchTimeout = setTimeout(() => {
        fetchVendorOptions(document.getElementById('vendorSearchInput').value.trim());
    }, 300);
}

function openVendorDropdown() {
    document.getElementById('vendorDropdownMenu').classList.remove('d-none');
    if (vendorListCache.length === 0) {
        fetchVendorOptions('');
    }
}

async function fetchVendorOptions(query) {
    const listEl = document.getElementById('vendorDropdownList');
    listEl.innerHTML = '<div class="p-2 text-center text-muted small"><span class="spinner-border spinner-border-sm me-1"></span>Mencari vendor...</div>';
    
    const url = `/api/master/vendor.php?limit=10&q=${encodeURIComponent(query)}`;
    const res = await apiRequest(url);
    if (res && res.success) {
        vendorListCache = res.data.items || [];
        if (vendorListCache.length === 0) {
            listEl.innerHTML = '<div class="p-2 text-center text-muted small">Vendor tidak ditemukan.</div>';
            return;
        }
        let html = '';
        vendorListCache.forEach(v => {
            html += `
                <div class="searchable-select-item" onclick="selectVendorOption(${v.id_vendor}, '${v.nama_perusahaan.replace(/'/g, "\\'")}', '${v.kode_vendor}')">
                    <span class="fw-semibold text-dark">${v.nama_perusahaan}</span>
                    <span class="badge bg-light text-muted border ms-1 font-monospace">${v.kode_vendor}</span>
                    ${v.kota ? `<small class="text-muted d-block">${v.kota}</small>` : ''}
                </div>
            `;
        });
        listEl.innerHTML = html;
    }
}

function selectVendorOption(id, name, code) {
    document.getElementById('formVendorIdVal').value = id;
    document.getElementById('vendorSearchInput').value = `${name} (${code})`;
    document.getElementById('selectedVendorLabel').innerHTML = `<span class="text-success fw-semibold"><i class="bi bi-check-circle me-1"></i>Terpilih: ${name}</span>`;
    document.getElementById('vendorDropdownMenu').classList.add('d-none');
}

function clearVendorSelection() {
    document.getElementById('formVendorIdVal').value = '';
    document.getElementById('vendorSearchInput').value = '';
    document.getElementById('selectedVendorLabel').textContent = 'Belum ada vendor rekanan yang dipilih.';
}

// Close all searchable dropdowns on outside click
document.addEventListener('click', (e) => {
    // Vendor
    const vWrap = document.getElementById('vendorSearchWrapper');
    if (vWrap && !vWrap.contains(e.target)) {
        const vMenu = document.getElementById('vendorDropdownMenu');
        if (vMenu) vMenu.classList.add('d-none');
    }
    // Kategori
    const kWrap = document.getElementById('kategoriSearchWrapper');
    if (kWrap && !kWrap.contains(e.target)) {
        const kMenu = document.getElementById('kategoriDropdownMenu');
        if (kMenu) kMenu.classList.add('d-none');
    }
    // Merk
    const mWrap = document.getElementById('merkSearchWrapper');
    if (mWrap && !mWrap.contains(e.target)) {
        const mMenu = document.getElementById('merkDropdownMenu');
        if (mMenu) mMenu.classList.add('d-none');
    }
});

// -------------------------------------------------------------
// 4. Local Image Preview (Upload Dilakukan SAAT Submit Form)
// -------------------------------------------------------------
function handleLocalFileSelect(input, index) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];

    if (index === 1) pendingFile1 = file;
    if (index === 2) pendingFile2 = file;

    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById(`previewFoto${index}`);
        const placeholder = document.getElementById(`placeholderFoto${index}`);
        const btnRemove = document.getElementById(`btnRemoveFoto${index}`);
        preview.src = e.target.result;
        preview.classList.remove('d-none');
        placeholder.classList.add('d-none');
        if (btnRemove) btnRemove.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
}

function removeProductImage(index) {
    if (index === 1) pendingFile1 = null;
    if (index === 2) pendingFile2 = null;
    document.getElementById(`formFoto${index}Val`).value = '';
    document.getElementById(`formFile${index}`).value = '';
    const preview = document.getElementById(`previewFoto${index}`);
    const placeholder = document.getElementById(`placeholderFoto${index}`);
    const btnRemove = document.getElementById(`btnRemoveFoto${index}`);
    preview.src = '';
    preview.classList.add('d-none');
    placeholder.classList.remove('d-none');
    if (btnRemove) btnRemove.classList.add('d-none');
}

async function uploadSingleFileToServer(file) {
    const formData = new FormData();
    formData.append('image', file);
    formData.append('type', 'barang');

    const response = await fetch('<?= BASE_URL ?>/api/master/upload_image.php', {
        method: 'POST',
        body: formData
    });
    const res = await response.json();
    if (res && res.success) {
        return res.data.url;
    } else {
        throw new Error(res.message || 'Gagal upload gambar.');
    }
}

// -------------------------------------------------------------
// 5. Load Table Data Barang (50 per page baku)
// -------------------------------------------------------------
async function loadBarang() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('barangTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data...</td></tr>`;
    
    const url = `/api/master/barang.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        barangDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (barangDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data barang ditemukan.</td></tr>`;
            paginationInfo.textContent = 'Menampilkan 0 dari 0 data';
            paginationControls.innerHTML = '';
            return;
        }
        
        let html = '';
        barangDataStore.forEach((item, idx) => {
            const rowNumber = pag.from + idx;
            
            html += `
                <tr>
                    <td class="text-muted">${rowNumber}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${item.kode_barang || '-'}</span></td>
                    <td class="fw-bold text-dark">${item.nama_barang}</td>
                    <td><span class="badge bg-secondary-subtle text-secondary">${item.satuan}</span></td>
                    <td><span class="badge bg-primary-subtle text-primary">${item.jenis_label}</span></td>
                    <td>
                        <span class="badge ${item.aktif ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${item.aktif_label}
                        </span>
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary py-1 px-2" onclick="showBarangDetail(${idx})" title="Lihat Rincian">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-secondary py-1 px-2" onclick="openEditBarangModal(${idx})" title="Edit Data">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <button class="btn btn-outline-danger py-1 px-2" onclick="deleteBarang(${item.id_barang}, '${item.nama_barang.replace(/'/g, "\\'")}')" title="Hapus">
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
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-danger">Gagal memuat data barang.</td></tr>`;
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

// -------------------------------------------------------------
// 6. Form Modal Handlers
// -------------------------------------------------------------
function openTambahBarangModal() {
    document.getElementById('barangForm').reset();
    document.getElementById('formIdBarang').value = '';
    
    pendingFile1 = null;
    pendingFile2 = null;
    clearKategoriSelection();
    clearMerkSelection();
    clearVendorSelection();
    removeProductImage(1);
    removeProductImage(2);
    
    document.getElementById('satuanPCS').checked = true;
    document.getElementById('jenisPersediaan').checked = true;
    document.getElementById('assetNon').checked = true;
    document.getElementById('aktifYes').checked = true;
    
    document.getElementById('formCreatedAtWrapper').style.display = 'none';
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('bform-tab-utama')).show();
    document.getElementById('barangFormModalTitle').innerHTML = '<i class="bi bi-box-seam-fill me-2"></i>Tambah Barang Baru';
    const modal = new bootstrap.Modal(document.getElementById('barangFormModal'));
    modal.show();
}

function openEditBarangModal(idx) {
    const item = barangDataStore[idx];
    if (!item) return;
    
    pendingFile1 = null;
    pendingFile2 = null;
    
    document.getElementById('formIdBarang').value = item.id_barang;
    document.getElementById('formKodeBarang').value = item.kode_barang;
    document.getElementById('formNamaBarang').value = item.nama_barang;
    
    // Kategori
    if (item.id_kategori && item.nama_kategori) {
        selectKategoriOption(item.id_kategori, item.nama_kategori, '');
    } else {
        clearKategoriSelection();
    }

    // Merk
    if (item.id_merk && item.nama_merk) {
        selectMerkOption(item.id_merk, item.nama_merk, '');
    } else {
        clearMerkSelection();
    }
    
    // Satuan Radio
    if (item.satuan === 'UNIT') {
        document.getElementById('satuanUNIT').checked = true;
    } else {
        document.getElementById('satuanPCS').checked = true;
    }

    // Jenis Radio
    if (item.jenis === 0) {
        document.getElementById('jenisJasa').checked = true;
    } else {
        document.getElementById('jenisPersediaan').checked = true;
    }

    // Asset Radio
    if (item.asset === 1) {
        document.getElementById('assetYes').checked = true;
    } else {
        document.getElementById('assetNon').checked = true;
    }

    // Aktif Radio
    if (item.aktif === 0) {
        document.getElementById('aktifNo').checked = true;
    } else {
        document.getElementById('aktifYes').checked = true;
    }

    // Vendor
    if (item.default_id_vendor) {
        selectVendorOption(item.default_id_vendor, item.nama_vendor, item.kode_vendor);
    } else {
        clearVendorSelection();
    }

    // Serial & Deskripsi
    document.getElementById('formSerialNumber').value = item.serial_number || '';
    document.getElementById('formDeskripsi').value = item.deskripsi || '';

    // Created At
    document.getElementById('formCreatedAt').value = item.created_at || '-';
    document.getElementById('formCreatedAtWrapper').style.display = 'block';

    // Foto 1
    if (item.foto1) {
        document.getElementById('formFoto1Val').value = item.foto1;
        document.getElementById('previewFoto1').src = '<?= BASE_URL ?>/' + item.foto1;
        document.getElementById('previewFoto1').classList.remove('d-none');
        document.getElementById('placeholderFoto1').classList.add('d-none');
        document.getElementById('btnRemoveFoto1').classList.remove('d-none');
    } else {
        removeProductImage(1);
    }

    // Foto 2
    if (item.foto2) {
        document.getElementById('formFoto2Val').value = item.foto2;
        document.getElementById('previewFoto2').src = '<?= BASE_URL ?>/' + item.foto2;
        document.getElementById('previewFoto2').classList.remove('d-none');
        document.getElementById('placeholderFoto2').classList.add('d-none');
        document.getElementById('btnRemoveFoto2').classList.remove('d-none');
    } else {
        removeProductImage(2);
    }
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('bform-tab-utama')).show();
    document.getElementById('barangFormModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i>Edit Data Barang';
    const modal = new bootstrap.Modal(document.getElementById('barangFormModal'));
    modal.show();
}

async function handleSaveBarang(e) {
    e.preventDefault();
    const id = document.getElementById('formIdBarang').value;
    const isEdit = id !== '';
    const btnSave = document.getElementById('btnSaveBarang');
    
    const namaBarang = document.getElementById('formNamaBarang').value.trim();
    const idKategori = document.getElementById('formKategoriIdVal').value;
    
    if (!namaBarang) {
        showToast('Nama barang wajib diisi.', 'error');
        return;
    }
    if (!idKategori) {
        showToast('Kategori barang wajib dipilih.', 'error');
        bootstrap.Tab.getOrCreateInstance(document.getElementById('bform-tab-utama')).show();
        return;
    }

    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    try {
        let finalFoto1 = document.getElementById('formFoto1Val').value;
        let finalFoto2 = document.getElementById('formFoto2Val').value;

        // Upload Foto 1 ke server HANYA saat klik Simpan
        if (pendingFile1) {
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengunggah Foto 1...';
            finalFoto1 = await uploadSingleFileToServer(pendingFile1);
        }

        // Upload Foto 2 ke server HANYA saat klik Simpan
        if (pendingFile2) {
            btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengunggah Foto 2...';
            finalFoto2 = await uploadSingleFileToServer(pendingFile2);
        }

        const satuan = document.querySelector('input[name="formSatuanRadio"]:checked')?.value || 'PCS';
        const jenis = document.querySelector('input[name="formJenisRadio"]:checked')?.value || '1';
        const asset = document.querySelector('input[name="formAssetRadio"]:checked')?.value || '0';
        const aktif = document.querySelector('input[name="formAktifRadio"]:checked')?.value || '1';
        
        const payload = {
            id_barang: id,
            kode_barang: document.getElementById('formKodeBarang').value.trim(),
            nama_barang: namaBarang,
            id_kategori: idKategori,
            id_merk: document.getElementById('formMerkIdVal').value || null,
            satuan: satuan,
            jenis: parseInt(jenis),
            asset: parseInt(asset),
            aktif: parseInt(aktif),
            default_id_vendor: document.getElementById('formVendorIdVal').value || null,
            serial_number: document.getElementById('formSerialNumber').value.trim(),
            foto1: finalFoto1,
            foto2: finalFoto2,
            deskripsi: document.getElementById('formDeskripsi').value.trim(),
            _method: isEdit ? 'PUT' : 'POST'
        };
        
        btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan Data...';
        const res = await apiRequest('/api/master/barang.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        
        if (res && res.success) {
            showToast(res.message || 'Data berhasil disimpan!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('barangFormModal')).hide();
            loadBarang();
        } else {
            showToast(res.message || 'Gagal menyimpan data.', 'error');
        }
    } catch (err) {
        showToast(err.message || 'Terjadi kesalahan saat menyimpan data.', 'error');
    } finally {
        btnSave.disabled = false;
        btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data Barang';
    }
}

async function deleteBarang(id, name) {
    if (!confirm(`Apakah Anda yakin ingin menghapus barang "${name}"?`)) return;
    
    const res = await apiRequest('/api/master/barang.php', {
        method: 'POST',
        body: JSON.stringify({ id_barang: id, _method: 'DELETE' })
    });
    
    if (res && res.success) {
        showToast('Barang berhasil dihapus.', 'success');
        loadBarang();
    } else {
        showToast(res.message || 'Gagal menghapus barang.', 'error');
    }
}

function showBarangDetail(idx) {
    const item = barangDataStore[idx];
    if (!item) return;
    
    // Tab 1: Utama
    document.getElementById('modalKodeBarang').textContent = item.kode_barang || '-';
    document.getElementById('modalNamaBarang').textContent = item.nama_barang;
    document.getElementById('modalKategoriBarang').textContent = item.nama_kategori || '-';
    document.getElementById('modalMerkBarang').textContent = item.nama_merk || '-';
    document.getElementById('modalSatuanBarang').innerHTML = `<span class="badge bg-secondary-subtle text-secondary">${item.satuan}</span>`;
    document.getElementById('modalJenisBarang').innerHTML = `<span class="badge bg-primary-subtle text-primary">${item.jenis_label}</span>`;
    
    // Tab 2: Vendor
    document.getElementById('modalDefaultVendor').textContent = item.nama_vendor ? `${item.nama_vendor} (${item.kode_vendor})` : 'Belum ditentukan';
    
    // Tab 3: Foto
    const f1 = document.getElementById('modalFoto1Container');
    const f2 = document.getElementById('modalFoto2Container');
    f1.innerHTML = item.foto1 
        ? `<a href="<?= BASE_URL ?>/${item.foto1}" target="_blank"><img src="<?= BASE_URL ?>/${item.foto1}" class="img-fluid rounded border shadow-sm" style="max-height: 130px; object-fit: contain;"></a>` 
        : `<span class="text-muted small">Tidak ada foto</span>`;
    f2.innerHTML = item.foto2 
        ? `<a href="<?= BASE_URL ?>/${item.foto2}" target="_blank"><img src="<?= BASE_URL ?>/${item.foto2}" class="img-fluid rounded border shadow-sm" style="max-height: 130px; object-fit: contain;"></a>` 
        : `<span class="text-muted small">Tidak ada foto</span>`;
    
    // Tab 4: Tambahan
    document.getElementById('modalAssetBarang').innerHTML = `<span class="badge ${item.asset ? 'bg-warning-subtle text-dark' : 'bg-light text-muted border'}">${item.asset_label}</span>`;
    document.getElementById('modalAktifBarang').innerHTML = `<span class="badge ${item.aktif ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">${item.aktif_label}</span>`;
    document.getElementById('modalSerialBarang').textContent = item.serial_number || '-';
    document.getElementById('modalCreatedAt').textContent = `${item.created_at} (oleh ${item.pembuat_barang})`;
    document.getElementById('modalDeskripsiBarang').textContent = item.deskripsi || '-';
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('bdetail-tab-utama')).show();
    const modal = new bootstrap.Modal(document.getElementById('barangDetailModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', loadBarang);
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
