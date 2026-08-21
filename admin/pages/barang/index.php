<?php
/**
 * Master Data Barang - PT Jaya Teknik
 * Terintegrasi dengan Stok per Site (barang_stok) & Harga per Vendor (barang_hargavendor)
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
        <h2 class="fs-4 fw-bold text-dark mb-0">Katalog Master Barang &amp; Material</h2>
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
                    <th>Total Stok</th>
                    <th>Jenis</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="barangTableBody">
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
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

<!-- Modal Form Tambah / Edit Barang dengan Tab Rapi (2 Kolom) -->
<div class="modal fade" id="barangFormModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 850px;">
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
                
                <!-- Nav Tabs Modal Form (6 Tab Terfokus) -->
                <div class="bg-light px-4 pt-3 border-bottom">
                    <ul class="nav nav-tabs border-bottom-0" id="barangFormTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold small" id="bform-tab-utama" data-bs-toggle="tab" data-bs-target="#bform-pane-utama" type="button" role="tab">
                                <i class="bi bi-tag me-1 text-primary"></i> Utama
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="bform-tab-vendor" data-bs-toggle="tab" data-bs-target="#bform-pane-vendor" type="button" role="tab">
                                <i class="bi bi-truck me-1 text-primary"></i> Vendor
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="bform-tab-harga" data-bs-toggle="tab" data-bs-target="#bform-pane-harga" type="button" role="tab">
                                <i class="bi bi-cash-coin me-1 text-primary"></i> Harga
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="bform-tab-stok" data-bs-toggle="tab" data-bs-target="#bform-pane-stok" type="button" role="tab">
                                <i class="bi bi-boxes me-1 text-primary"></i> Stok
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="bform-tab-foto" data-bs-toggle="tab" data-bs-target="#bform-pane-foto" type="button" role="tab">
                                <i class="bi bi-images me-1 text-primary"></i> Foto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="bform-tab-tambahan" data-bs-toggle="tab" data-bs-target="#bform-pane-tambahan" type="button" role="tab">
                                <i class="bi bi-sliders me-1 text-primary"></i> Tambahan
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
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Satuan <span class="text-danger">*</span></label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formSatuanRadio" id="satuanPCS" value="PCS" checked onchange="updateStokSatuanLabels()">
                                        <label class="btn btn-outline-secondary btn-sm" for="satuanPCS">PCS</label>
                                        
                                        <input type="radio" class="btn-check" name="formSatuanRadio" id="satuanUNIT" value="UNIT" onchange="updateStokSatuanLabels()">
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
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: HARGA VENDOR -->
                        <div class="tab-pane fade" id="bform-pane-harga" role="tabpanel">
                            <!-- RED NOTICE: JANGAN HAPUS JIKA PERNAH DITRANSAKSIKAN -->
                            <div class="alert alert-danger d-flex align-items-center py-2 px-3 small mb-3 border-0 shadow-sm">
                                <i class="bi bi-exclamation-triangle-fill fs-5 me-2 flex-shrink-0 text-danger"></i>
                                <div>
                                    <strong>PERHATIAN:</strong> Jangan menghapus baris referensi harga jika vendor tersebut sudah pernah digunakan dalam transaksi pembelian (PO / RO). Anda cukup menambahkan baris harga baru dengan tanggal berlaku yang lebih baru.
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <span class="fw-bold small text-dark">Daftar Harga per Vendor Rekanan</span>
                                    <div class="text-muted small">Atur referensi harga satuan dan tanggal berlakunya per vendor</div>
                                </div>
                                <button type="button" class="btn btn-outline-primary btn-sm fw-semibold" onclick="addHargaRow()">
                                    <i class="bi bi-plus-circle me-1"></i> Tambah Harga Vendor
                                </button>
                            </div>
                            <div class="table-responsive border rounded bg-white">
                                <table class="table table-sm table-bordered align-middle mb-0" id="tableHargaForm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Vendor Rekanan</th>
                                            <th style="width: 190px;">Harga Satuan (Rp)</th>
                                            <th style="width: 170px;">Berlaku Mulai</th>
                                            <th class="text-center" style="width: 50px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody id="hargaRowsContainer">
                                        <!-- Dynamic Harga Rows -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 4: STOK PER SITE -->
                        <div class="tab-pane fade" id="bform-pane-stok" role="tabpanel">
                            <!-- INFO NOTICE: HANYA UNTUK STOK AWAL -->
                            <div class="alert alert-warning d-flex align-items-center py-2 px-3 small mb-3 border-0 shadow-sm">
                                <i class="bi bi-info-circle-fill fs-5 me-2 flex-shrink-0 text-warning"></i>
                                <div>
                                    <strong>INFORMASI STOK AWAL:</strong> Pengisian stok manual di bawah ini <u>hanya diperuntukkan untuk saldo stok awal</u> inventaris. Pergerakan mutasi stok barang selanjutnya akan bertambah/berkurang secara otomatis melalui alur <strong>Purchasing & Receiving (Penerimaan Barang)</strong> dan <strong>Pengeluaran / Penjualan</strong>.
                                </div>
                            </div>

                            <div class="mb-3">
                                <span class="fw-bold small text-dark">Alokasi Stok Fisik per Lokasi Site / Workshop</span>
                                <div class="text-muted small">Tentukan jumlah persediaan unit/material pada setiap site operasional</div>
                            </div>
                            <div class="table-responsive border rounded bg-white">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Lokasi Site / Bengkel Workshop</th>
                                            <th>Jenis Site</th>
                                            <th style="width: 180px;">Stok Fisik (<span class="stokSatuanLabel">PCS</span>)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stokRowsContainer">
                                        <tr><td colspan="3" class="text-center py-3 text-muted small">Memuat daftar site...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB 5: FOTO (2 KOLOM) -->
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
                                    <div class="form-text small text-muted">File akan diunggah otomatis saat Anda menekan tombol Simpan Data.</div>
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
                                    <div class="form-text small text-muted">File akan diunggah otomatis saat Anda menekan tombol Simpan Data.</div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 6: TAMBAHAN (2 KOLOM) -->
                        <div class="tab-pane fade" id="bform-pane-tambahan" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Klasifikasi Asset</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAssetRadio" id="assetNon" value="0" checked>
                                        <label class="btn btn-outline-secondary btn-sm" for="assetNon">Bukan Asset</label>
                                        
                                        <input type="radio" class="btn-check" name="formAssetRadio" id="assetYes" value="1">
                                        <label class="btn btn-outline-primary btn-sm" for="assetYes">Asset</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold d-block">Status Keaktifan</label>
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="barangAktifYes" value="1" checked>
                                        <label class="btn btn-outline-success btn-sm" for="barangAktifYes">Aktif</label>
                                        
                                        <input type="radio" class="btn-check" name="formAktifRadio" id="barangAktifNo" value="0">
                                        <label class="btn btn-outline-danger btn-sm" for="barangAktifNo">Non-aktif</label>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Serial Number / Part Number</label>
                                    <input type="text" class="form-control" id="formSerialNumber" placeholder="Contoh: SN-8890283-A">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tanggal Pembuatan Data</label>
                                    <input type="text" class="form-control bg-light" id="formCreatedAtDisplay" readonly value="<?= date('d-m-Y H:i') ?>">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold">Deskripsi &amp; Spesifikasi Teknis Material</label>
                                    <textarea class="form-control" id="formDeskripsi" rows="3" placeholder="Rincian dimensi, ketebalan, grade standar marine, sertifikasi material, dll"></textarea>
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

<!-- Modal Detail Lengkap Barang (5 Tab Terfokus) -->
<div class="modal fade" id="barangDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 850px;">
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
                            <i class="bi bi-tag me-1 text-primary"></i> Utama
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="bdetail-tab-stok" data-bs-toggle="tab" data-bs-target="#bdetail-pane-stok" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> Stok Site
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="bdetail-tab-harga" data-bs-toggle="tab" data-bs-target="#bdetail-pane-harga" type="button" role="tab">
                            <i class="bi bi-cash-coin me-1 text-primary"></i> Harga Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="bdetail-tab-foto" data-bs-toggle="tab" data-bs-target="#bdetail-pane-foto" type="button" role="tab">
                            <i class="bi bi-images me-1 text-primary"></i> Foto
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold small" id="bdetail-tab-tambahan" data-bs-toggle="tab" data-bs-target="#bdetail-pane-tambahan" type="button" role="tab">
                            <i class="bi bi-sliders me-1 text-primary"></i> Tambahan
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
                            <div class="col-md-6">
                                <label class="text-muted small fw-bold text-uppercase d-block mb-1">Default Vendor</label>
                                <div class="fw-semibold text-dark" id="modalDefaultVendor">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: STOK SITE DETAIL -->
                    <div class="tab-pane fade" id="bdetail-pane-stok" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-dark">Rincian Persediaan Stok per Site</span>
                            <span class="badge bg-primary fs-6" id="modalTotalStokBadge">Total: 0 PCS</span>
                        </div>
                        <div class="table-responsive border rounded bg-white">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Lokasi Site / Workshop</th>
                                        <th>Jenis Site</th>
                                        <th class="text-end">Jumlah Stok</th>
                                    </tr>
                                </thead>
                                <tbody id="modalStokSiteList">
                                    <!-- Dynamic Stok List -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 3: HARGA VENDOR DETAIL -->
                    <div class="tab-pane fade" id="bdetail-pane-harga" role="tabpanel">
                        <div class="mb-3">
                            <span class="fw-bold text-dark">Daftar Referensi Harga per Vendor Rekanan</span>
                        </div>
                        <div class="table-responsive border rounded bg-white">
                            <table class="table table-sm table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Vendor Rekanan</th>
                                        <th>Harga Satuan (Rp)</th>
                                        <th>Tanggal Berlaku</th>
                                    </tr>
                                </thead>
                                <tbody id="modalHargaVendorList">
                                    <!-- Dynamic Vendor Price List -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 4: FOTO (2 KOLOM) -->
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

                    <!-- TAB 5: TAMBAHAN (2 KOLOM) -->
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
let siteListCache = [];

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

async function loadDependencies() {
    // 1. Kategori
    const resK = await apiRequest('/api/master/kategori.php?limit=100');
    if (resK && resK.success) kategoriListCache = resK.data.items || [];

    // 2. Merk
    const resM = await apiRequest('/api/master/merk.php?limit=100');
    if (resM && resM.success) merkListCache = resM.data.items || [];

    // 3. Vendor
    const resV = await apiRequest('/api/master/vendor.php?limit=100');
    if (resV && resV.success) vendorListCache = resV.data.items || [];

    // 4. Site (Hanya site yang berstatus tempat penyimpanan stok)
    const resS = await apiRequest('/api/master/site.php?penyimpanan_stok=1&limit=100');
    if (resS && resS.success) {
        siteListCache = resS.data.items || [];
        renderStokFormRows();
    }
}

function getSelectedSatuan() {
    return document.querySelector('input[name="formSatuanRadio"]:checked')?.value || 'PCS';
}

function updateStokSatuanLabels() {
    const currentSatuan = getSelectedSatuan();
    document.querySelectorAll('.stokSatuanLabel').forEach(el => el.textContent = currentSatuan);
    document.querySelectorAll('.stokSatuanSuffix').forEach(el => el.textContent = currentSatuan);
}

function renderStokFormRows(existingStokMap = {}) {
    const container = document.getElementById('stokRowsContainer');
    if (!container) return;
    if (siteListCache.length === 0) {
        container.innerHTML = '<tr><td colspan="3" class="text-center py-2 text-muted small">Tidak ada data site tersedia.</td></tr>';
        return;
    }

    const currentSatuan = getSelectedSatuan();
    document.querySelectorAll('.stokSatuanLabel').forEach(el => el.textContent = currentSatuan);

    let html = '';
    siteListCache.forEach(s => {
        const qty = existingStokMap[s.id_site] !== undefined ? existingStokMap[s.id_site] : 0;
        html += `
            <tr>
                <td class="fw-semibold text-dark">
                    <i class="bi bi-geo-alt me-1 text-primary"></i>${s.nama_site} 
                    <span class="badge bg-light text-muted border font-monospace ms-1">${s.kode_site || ''}</span>
                </td>
                <td><span class="badge bg-secondary-subtle text-secondary">${s.jenis_site || 'Site'}</span></td>
                <td>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control site-stok-input" data-site-id="${s.id_site}" value="${qty}" min="0" placeholder="0">
                        <span class="input-group-text bg-light stokSatuanSuffix">${currentSatuan}</span>
                    </div>
                </td>
            </tr>
        `;
    });
    container.innerHTML = html;
}

function addHargaRow(idVendor = '', hargaSet = '', berlaku = '') {
    const container = document.getElementById('hargaRowsContainer');
    const rowId = 'hrow_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
    const today = new Date().toISOString().split('T')[0];

    let options = '<option value="">-- Pilih Vendor Rekanan --</option>';
    vendorListCache.forEach(v => {
        const sel = (v.id_vendor == idVendor) ? 'selected' : '';
        options += `<option value="${v.id_vendor}" ${sel}>${v.nama_perusahaan} (${v.kode_vendor || 'VND'})</option>`;
    });

    const tr = document.createElement('tr');
    tr.id = rowId;
    tr.className = 'harga-vendor-row';
    tr.innerHTML = `
        <td>
            <select class="form-select form-select-sm harga-vendor-select" required>
                ${options}
            </select>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">Rp</span>
                <input type="number" class="form-control harga-vendor-price" value="${hargaSet}" min="0" placeholder="0" required>
            </div>
        </td>
        <td>
            <input type="date" class="form-control form-control-sm harga-vendor-date" value="${berlaku || today}" required>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeHargaRow('${rowId}')" title="Hapus Baris">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    container.appendChild(tr);
}

function removeHargaRow(rowId) {
    const row = document.getElementById(rowId);
    if (row) row.remove();
}

async function loadBarang() {
    const q = document.getElementById('searchInput').value.trim();
    const tbody = document.getElementById('barangTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Memuat data katalog barang...</td></tr>`;
    
    const url = `/api/master/barang.php?page=${currentPage}&limit=${fixedLimit}&q=${encodeURIComponent(q)}`;
    const res = await apiRequest(url);
    
    if (res && res.success) {
        barangDataStore = res.data.items || [];
        const pag = res.data.pagination;
        
        if (barangDataStore.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted">Tidak ada data barang yang cocok.</td></tr>`;
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
                    <td>
                        <span class="badge bg-primary-subtle text-primary font-monospace fw-bold">
                            ${item.total_stok || 0} ${item.satuan}
                        </span>
                    </td>
                    <td><span class="badge ${item.jenis === 1 ? 'bg-info-subtle text-dark' : 'bg-warning-subtle text-dark'}">${item.jenis_label}</span></td>
                    <td>
                        <span class="badge ${item.aktif === 1 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                            ${item.aktif === 1 ? 'Aktif' : 'Non-aktif'}
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
        tbody.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-danger">Gagal memuat data barang.</td></tr>`;
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
// SEARCHABLE SELECT: KATEGORI
// -------------------------------------------------------------
function openKategoriDropdown() {
    const query = document.getElementById('kategoriSearchInput').value.trim();
    renderKategoriList(kategoriListCache, query);
    document.getElementById('kategoriDropdownMenu').classList.remove('d-none');
}

function debounceKategoriSearch() {
    clearTimeout(kategoriSearchTimeout);
    kategoriSearchTimeout = setTimeout(async () => {
        const query = document.getElementById('kategoriSearchInput').value.trim();
        const res = await apiRequest(`/api/master/kategori.php?limit=100&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderKategoriList(res.data.items || [], query);
        }
    }, 250);
}

function renderKategoriList(items, searchQuery = '') {
    const list = document.getElementById('kategoriDropdownList');
    const cleanQuery = searchQuery.trim();
    let html = '';

    const hasExactMatch = items.some(k => k.nama_kategori.toLowerCase() === cleanQuery.toLowerCase());

    if (items.length === 0 && !cleanQuery) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada kategori ditemukan.</div>`;
        return;
    }

    if (items.length > 0) {
        items.forEach(k => {
            html += `<div class="searchable-select-item" onclick="selectKategori(${k.id_kategori}, '${k.nama_kategori.replace(/'/g, "\\'")}')">
                        <span class="fw-bold text-dark">${k.nama_kategori}</span>
                     </div>`;
        });
    } else {
        html += `<div class="p-2 text-center text-muted small">Kategori <em>"${cleanQuery}"</em> belum terdaftar.</div>`;
    }

    // Tombol Tambah Baru jika tidak ada di list
    if (cleanQuery && !hasExactMatch) {
        const escapedQuery = cleanQuery.replace(/'/g, "\\'");
        html += `
            <div class="searchable-select-item text-primary bg-primary-subtle border-top border-primary-subtle py-2 d-flex align-items-center justify-content-between" onclick="quickCreateKategori('${escapedQuery}')">
                <span><i class="bi bi-plus-circle-fill me-1"></i> Tambah Baru: <strong>"${cleanQuery}"</strong></span>
                <span class="badge bg-primary text-white" style="font-size: 0.7rem;">Simpan Otomatis</span>
            </div>
        `;
    }

    list.innerHTML = html;
}

async function quickCreateKategori(name) {
    const list = document.getElementById('kategoriDropdownList');
    list.innerHTML = `<div class="p-2 text-center text-muted small"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Menyimpan kategori baru "${name}"...</div>`;

    const res = await apiRequest('/api/master/kategori.php', {
        method: 'POST',
        body: JSON.stringify({ nama_kategori: name, aktif: 1 })
    });

    if (res && res.success) {
        const newId = res.data?.id_kategori || (res.data?.id || 0);
        // Refresh cache
        const resK = await apiRequest('/api/master/kategori.php?limit=100');
        if (resK && resK.success) kategoriListCache = resK.data.items || [];
        
        selectKategori(newId, name);
        showToast(`Kategori "${name}" berhasil ditambahkan dan dipilih!`, 'success');
    } else {
        showToast(res.message || 'Gagal menambahkan kategori baru', 'error');
        renderKategoriList(kategoriListCache, name);
    }
}

function selectKategori(id, name) {
    document.getElementById('formKategoriIdVal').value = id;
    document.getElementById('kategoriSearchInput').value = name;
    document.getElementById('kategoriDropdownMenu').classList.add('d-none');
}

function clearKategoriSelection() {
    document.getElementById('formKategoriIdVal').value = '';
    document.getElementById('kategoriSearchInput').value = '';
}

// -------------------------------------------------------------
// SEARCHABLE SELECT: MERK
// -------------------------------------------------------------
function openMerkDropdown() {
    const query = document.getElementById('merkSearchInput').value.trim();
    renderMerkList(merkListCache, query);
    document.getElementById('merkDropdownMenu').classList.remove('d-none');
}

function debounceMerkSearch() {
    clearTimeout(merkSearchTimeout);
    merkSearchTimeout = setTimeout(async () => {
        const query = document.getElementById('merkSearchInput').value.trim();
        const res = await apiRequest(`/api/master/merk.php?limit=100&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderMerkList(res.data.items || [], query);
        }
    }, 250);
}

function renderMerkList(items, searchQuery = '') {
    const list = document.getElementById('merkDropdownList');
    const cleanQuery = searchQuery.trim();
    let html = '';

    const hasExactMatch = items.some(m => m.nama_merk.toLowerCase() === cleanQuery.toLowerCase());

    if (items.length === 0 && !cleanQuery) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada merk ditemukan.</div>`;
        return;
    }

    if (items.length > 0) {
        items.forEach(m => {
            html += `<div class="searchable-select-item" onclick="selectMerk(${m.id_merk}, '${m.nama_merk.replace(/'/g, "\\'")}')">
                        <span class="fw-bold text-dark">${m.nama_merk}</span>
                     </div>`;
        });
    } else {
        html += `<div class="p-2 text-center text-muted small">Merk <em>"${cleanQuery}"</em> belum terdaftar.</div>`;
    }

    // Tombol Tambah Baru jika tidak ada di list
    if (cleanQuery && !hasExactMatch) {
        const escapedQuery = cleanQuery.replace(/'/g, "\\'");
        html += `
            <div class="searchable-select-item text-primary bg-primary-subtle border-top border-primary-subtle py-2 d-flex align-items-center justify-content-between" onclick="quickCreateMerk('${escapedQuery}')">
                <span><i class="bi bi-plus-circle-fill me-1"></i> Tambah Baru: <strong>"${cleanQuery}"</strong></span>
                <span class="badge bg-primary text-white" style="font-size: 0.7rem;">Simpan Otomatis</span>
            </div>
        `;
    }

    list.innerHTML = html;
}

async function quickCreateMerk(name) {
    const list = document.getElementById('merkDropdownList');
    list.innerHTML = `<div class="p-2 text-center text-muted small"><span class="spinner-border spinner-border-sm text-primary me-2"></span>Menyimpan merk baru "${name}"...</div>`;

    const res = await apiRequest('/api/master/merk.php', {
        method: 'POST',
        body: JSON.stringify({ nama_merk: name, aktif: 1 })
    });

    if (res && res.success) {
        const newId = res.data?.id_merk || (res.data?.id || 0);
        // Refresh cache
        const resM = await apiRequest('/api/master/merk.php?limit=100');
        if (resM && resM.success) merkListCache = resM.data.items || [];
        
        selectMerk(newId, name);
        showToast(`Merk "${name}" berhasil ditambahkan dan dipilih!`, 'success');
    } else {
        showToast(res.message || 'Gagal menambahkan merk baru', 'error');
        renderMerkList(merkListCache, name);
    }
}

function selectMerk(id, name) {
    document.getElementById('formMerkIdVal').value = id;
    document.getElementById('merkSearchInput').value = name;
    document.getElementById('merkDropdownMenu').classList.add('d-none');
}

function clearMerkSelection() {
    document.getElementById('formMerkIdVal').value = '';
    document.getElementById('merkSearchInput').value = '';
}

// -------------------------------------------------------------
// SEARCHABLE SELECT: VENDOR
// -------------------------------------------------------------
function openVendorDropdown() {
    renderVendorList(vendorListCache);
    document.getElementById('vendorDropdownMenu').classList.remove('d-none');
}

function debounceVendorSearch() {
    clearTimeout(vendorSearchTimeout);
    vendorSearchTimeout = setTimeout(async () => {
        const query = document.getElementById('vendorSearchInput').value.trim();
        const res = await apiRequest(`/api/master/vendor.php?limit=100&q=${encodeURIComponent(query)}`);
        if (res && res.success) {
            renderVendorList(res.data.items || []);
        }
    }, 250);
}

function renderVendorList(items) {
    const list = document.getElementById('vendorDropdownList');
    if (items.length === 0) {
        list.innerHTML = `<div class="p-2 text-center text-muted small">Tidak ada vendor ditemukan.</div>`;
        return;
    }
    let html = '';
    items.forEach(v => {
        html += `<div class="searchable-select-item" onclick="selectVendor(${v.id_vendor}, '${v.nama_perusahaan.replace(/'/g, "\\'")}', '${v.kode_vendor || ''}')">
                    <div class="fw-bold text-dark">${v.nama_perusahaan}</div>
                    <div class="text-muted small" style="font-size: 0.75rem;">${v.kode_vendor || '-'} &bull; ${v.kota || '-'}</div>
                 </div>`;
    });
    list.innerHTML = html;
}

function selectVendor(id, name, code) {
    document.getElementById('formVendorIdVal').value = id;
    document.getElementById('vendorSearchInput').value = `${name} (${code || 'VND'})`;
    document.getElementById('vendorDropdownMenu').classList.add('d-none');
}

function clearVendorSelection() {
    document.getElementById('formVendorIdVal').value = '';
    document.getElementById('vendorSearchInput').value = '';
}

// Klik di luar dropdown untuk menutup
document.addEventListener('click', (e) => {
    if (!e.target.closest('#kategoriSearchWrapper')) {
        document.getElementById('kategoriDropdownMenu')?.classList.add('d-none');
    }
    if (!e.target.closest('#merkSearchWrapper')) {
        document.getElementById('merkDropdownMenu')?.classList.add('d-none');
    }
    if (!e.target.closest('#vendorSearchWrapper')) {
        document.getElementById('vendorDropdownMenu')?.classList.add('d-none');
    }
});

// -------------------------------------------------------------
// LOCAL FILE PREVIEW
// -------------------------------------------------------------
function handleLocalFileSelect(input, slot) {
    const file = input.files[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
        showToast('Ukuran gambar maksimal 5 MB.', 'error');
        input.value = '';
        return;
    }

    if (slot === 1) pendingFile1 = file;
    if (slot === 2) pendingFile2 = file;

    const reader = new FileReader();
    reader.onload = function(e) {
        const preview = document.getElementById(`previewFoto${slot}`);
        const placeholder = document.getElementById(`placeholderFoto${slot}`);
        const btnRemove = document.getElementById(`btnRemoveFoto${slot}`);
        
        preview.src = e.target.result;
        preview.classList.remove('d-none');
        placeholder.classList.add('d-none');
        btnRemove.classList.remove('d-none');
    };
    reader.readAsDataURL(file);
}

function removeProductImage(slot) {
    if (slot === 1) {
        pendingFile1 = null;
        document.getElementById('formFile1').value = '';
        document.getElementById('formFoto1Val').value = '';
        document.getElementById('previewFoto1').src = '';
        document.getElementById('previewFoto1').classList.add('d-none');
        document.getElementById('placeholderFoto1').classList.remove('d-none');
        document.getElementById('btnRemoveFoto1').classList.add('d-none');
    } else {
        pendingFile2 = null;
        document.getElementById('formFile2').value = '';
        document.getElementById('formFoto2Val').value = '';
        document.getElementById('previewFoto2').src = '';
        document.getElementById('previewFoto2').classList.add('d-none');
        document.getElementById('placeholderFoto2').classList.remove('d-none');
        document.getElementById('btnRemoveFoto2').classList.add('d-none');
    }
}

// -------------------------------------------------------------
// MODAL CRUD
// -------------------------------------------------------------
function openTambahBarangModal() {
    document.getElementById('barangForm').reset();
    document.getElementById('formIdBarang').value = '';
    
    clearKategoriSelection();
    clearMerkSelection();
    clearVendorSelection();
    removeProductImage(1);
    removeProductImage(2);

    document.getElementById('satuanPCS').checked = true;
    document.getElementById('jenisPersediaan').checked = true;
    document.getElementById('assetNon').checked = true;
    document.getElementById('barangAktifYes').checked = true;
    
    // Clear & Init Harga & Stok Form
    document.getElementById('hargaRowsContainer').innerHTML = '';
    renderStokFormRows({});
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('bform-tab-utama')).show();
    document.getElementById('barangFormModalTitle').innerHTML = '<i class="bi bi-box-seam-fill me-2"></i>Tambah Barang Baru';
    const modal = new bootstrap.Modal(document.getElementById('barangFormModal'));
    modal.show();
}

function openEditBarangModal(idx) {
    const item = barangDataStore[idx];
    if (!item) return;
    
    document.getElementById('formIdBarang').value = item.id_barang;
    document.getElementById('formKodeBarang').value = item.kode_barang;
    document.getElementById('formNamaBarang').value = item.nama_barang;
    document.getElementById('formSerialNumber').value = item.serial_number;
    document.getElementById('formDeskripsi').value = item.deskripsi;
    document.getElementById('formCreatedAtDisplay').value = item.created_at || '-';

    // Kategori
    if (item.id_kategori && item.nama_kategori) {
        selectKategori(item.id_kategori, item.nama_kategori);
    } else {
        clearKategoriSelection();
    }

    // Merk
    if (item.id_merk && item.nama_merk) {
        selectMerk(item.id_merk, item.nama_merk);
    } else {
        clearMerkSelection();
    }

    // Vendor
    if (item.default_id_vendor && item.nama_vendor) {
        selectVendor(item.default_id_vendor, item.nama_vendor, item.kode_vendor);
    } else {
        clearVendorSelection();
    }

    // Satuan
    if (item.satuan === 'UNIT') {
        document.getElementById('satuanUNIT').checked = true;
    } else {
        document.getElementById('satuanPCS').checked = true;
    }
    updateStokSatuanLabels();

    // Jenis
    if (item.jenis === 0) {
        document.getElementById('jenisJasa').checked = true;
    } else {
        document.getElementById('jenisPersediaan').checked = true;
    }

    // Asset
    if (item.asset === 1) {
        document.getElementById('assetYes').checked = true;
    } else {
        document.getElementById('assetNon').checked = true;
    }

    // Aktif
    if (item.aktif === 1) {
        document.getElementById('barangAktifYes').checked = true;
    } else {
        document.getElementById('barangAktifNo').checked = true;
    }

    // Foto 1
    removeProductImage(1);
    if (item.foto1) {
        document.getElementById('formFoto1Val').value = item.foto1;
        const p1 = document.getElementById('previewFoto1');
        p1.src = `${BASE_URL}/${item.foto1}`;
        p1.classList.remove('d-none');
        document.getElementById('placeholderFoto1').classList.add('d-none');
        document.getElementById('btnRemoveFoto1').classList.remove('d-none');
    }

    // Foto 2
    removeProductImage(2);
    if (item.foto2) {
        document.getElementById('formFoto2Val').value = item.foto2;
        const p2 = document.getElementById('previewFoto2');
        p2.src = `${BASE_URL}/${item.foto2}`;
        p2.classList.remove('d-none');
        document.getElementById('placeholderFoto2').classList.add('d-none');
        document.getElementById('btnRemoveFoto2').classList.remove('d-none');
    }

    // Load Stok Map
    const stokMap = {};
    if (item.stok_per_site && Array.isArray(item.stok_per_site)) {
        item.stok_per_site.forEach(stk => {
            stokMap[stk.id_site] = stk.stok;
        });
    }
    renderStokFormRows(stokMap);

    // Load Harga Vendor Rows
    const hargaContainer = document.getElementById('hargaRowsContainer');
    hargaContainer.innerHTML = '';
    if (item.harga_vendors && Array.isArray(item.harga_vendors) && item.harga_vendors.length > 0) {
        item.harga_vendors.forEach(h => {
            addHargaRow(h.id_vendor, h.harga_set, h.berlaku);
        });
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
        return;
    }

    btnSave.disabled = true;
    btnSave.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengunggah & Menyimpan...';

    // 1. Upload Pending Foto 1 jika ada
    let foto1Final = document.getElementById('formFoto1Val').value;
    if (pendingFile1) {
        const formData1 = new FormData();
        formData1.append('image', pendingFile1);
        formData1.append('foto', pendingFile1);
        formData1.append('type', 'barang');
        try {
            const resUp1 = await fetch(BASE_URL + '/api/master/upload_image.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Authorization': 'Bearer ' + API_TOKEN
                },
                body: formData1
            });
            const dataUp1 = await resUp1.json();
            if (dataUp1 && dataUp1.success && dataUp1.data) {
                foto1Final = dataUp1.data.url || dataUp1.data.file_path || dataUp1.data.filename;
            } else {
                console.error('Upload foto1 gagal:', dataUp1?.message);
            }
        } catch (err) {
            console.error('Upload foto1 network error:', err);
        }
    }

    // 2. Upload Pending Foto 2 jika ada
    let foto2Final = document.getElementById('formFoto2Val').value;
    if (pendingFile2) {
        const formData2 = new FormData();
        formData2.append('image', pendingFile2);
        formData2.append('foto', pendingFile2);
        formData2.append('type', 'barang');
        try {
            const resUp2 = await fetch(BASE_URL + '/api/master/upload_image.php', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Authorization': 'Bearer ' + API_TOKEN
                },
                body: formData2
            });
            const dataUp2 = await resUp2.json();
            if (dataUp2 && dataUp2.success && dataUp2.data) {
                foto2Final = dataUp2.data.url || dataUp2.data.file_path || dataUp2.data.filename;
            } else {
                console.error('Upload foto2 gagal:', dataUp2?.message);
            }
        } catch (err) {
            console.error('Upload foto2 network error:', err);
        }
    }

    const satuanVal = document.querySelector('input[name="formSatuanRadio"]:checked')?.value || 'PCS';
    const jenisVal = document.querySelector('input[name="formJenisRadio"]:checked')?.value || '1';
    const assetVal = document.querySelector('input[name="formAssetRadio"]:checked')?.value || '0';
    const aktifVal = document.querySelector('input[name="formAktifRadio"]:checked')?.value || '1';

    // Kumpulkan Alokasi Stok per Site (Selalu simpan ke database meskipun 0 atau null)
    const stokSites = [];
    document.querySelectorAll('.site-stok-input').forEach(inp => {
        const siteId = parseInt(inp.dataset.siteId, 10);
        const val = inp.value.trim();
        const qty = (val !== '' && !isNaN(val)) ? Math.max(0, parseInt(val, 10)) : 0;
        if (siteId > 0) {
            stokSites.push({ id_site: siteId, stok: qty });
        }
    });

    // Kumpulkan Daftar Harga per Vendor
    const hargaVendors = [];
    document.querySelectorAll('.harga-vendor-row').forEach(row => {
        const selV = row.querySelector('.harga-vendor-select');
        const inpP = row.querySelector('.harga-vendor-price');
        const inpD = row.querySelector('.harga-vendor-date');
        const vId = selV ? parseInt(selV.value, 10) : 0;
        const price = inpP ? parseFloat(inpP.value) : 0;
        const bDate = inpD ? inpD.value : '';
        if (vId > 0 && price > 0) {
            hargaVendors.push({ id_vendor: vId, harga_set: price, berlaku: bDate });
        }
    });

    const payload = {
        id_barang: id,
        kode_barang: document.getElementById('formKodeBarang').value.trim(),
        nama_barang: namaBarang,
        id_kategori: idKategori,
        id_merk: document.getElementById('formMerkIdVal').value || 1,
        default_id_vendor: document.getElementById('formVendorIdVal').value || null,
        satuan: satuanVal,
        jenis: parseInt(jenisVal),
        asset: parseInt(assetVal),
        aktif: parseInt(aktifVal),
        serial_number: document.getElementById('formSerialNumber').value.trim(),
        deskripsi: document.getElementById('formDeskripsi').value.trim(),
        foto1: foto1Final,
        foto2: foto2Final,
        stok_sites: stokSites,
        harga_vendors: hargaVendors,
        _method: isEdit ? 'PUT' : 'POST'
    };
    
    const res = await apiRequest('/api/master/barang.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });
    
    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Data Barang';
    
    if (res && res.success) {
        showToast(res.message || 'Data barang berhasil disimpan!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('barangFormModal')).hide();
        loadBarang();
    } else {
        showToast(res.message || 'Gagal menyimpan data barang.', 'error');
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
    document.getElementById('modalSatuanBarang').innerHTML = `<span class="badge bg-secondary-subtle text-secondary">${item.satuan}</span>`;
    document.getElementById('modalKategoriBarang').textContent = item.nama_kategori || 'Umum';
    document.getElementById('modalMerkBarang').textContent = item.nama_merk || 'Umum';
    document.getElementById('modalJenisBarang').innerHTML = `<span class="badge ${item.jenis === 1 ? 'bg-info-subtle text-dark' : 'bg-warning-subtle text-dark'}">${item.jenis_label}</span>`;
    document.getElementById('modalDefaultVendor').textContent = item.nama_vendor ? `${item.nama_vendor} (${item.kode_vendor || 'VND'})` : '-';
    
    // Tab 2: Stok Site
    document.getElementById('modalTotalStokBadge').textContent = `Total Stok: ${item.total_stok || 0} ${item.satuan}`;
    const stokList = document.getElementById('modalStokSiteList');
    if (item.stok_per_site && item.stok_per_site.length > 0) {
        let sHtml = '';
        item.stok_per_site.forEach(s => {
            sHtml += `
                <tr>
                    <td class="fw-semibold"><i class="bi bi-geo-alt me-1 text-primary"></i>${s.nama_site}</td>
                    <td><span class="badge bg-light text-muted border">${s.kode_site || '-'}</span></td>
                    <td class="text-end font-monospace fw-bold ${s.stok > 0 ? 'text-primary' : 'text-muted'}">${s.stok} ${item.satuan}</td>
                </tr>
            `;
        });
        stokList.innerHTML = sHtml;
    } else {
        stokList.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-muted small">Belum ada alokasi stok di site manapun.</td></tr>`;
    }

    // Tab 3: Harga Vendor
    const hargaList = document.getElementById('modalHargaVendorList');
    if (item.harga_vendors && item.harga_vendors.length > 0) {
        let hHtml = '';
        item.harga_vendors.forEach(h => {
            hHtml += `
                <tr>
                    <td class="fw-semibold text-dark">${h.nama_vendor}</td>
                    <td class="font-monospace text-success fw-bold">${h.harga_formatted}</td>
                    <td class="text-muted small">${h.berlaku_formatted}</td>
                </tr>
            `;
        });
        hargaList.innerHTML = hHtml;
    } else {
        hargaList.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-muted small">Belum ada data referensi harga vendor.</td></tr>`;
    }

    // Tab 4: Foto
    const f1Cont = document.getElementById('modalFoto1Container');
    if (item.foto1) {
        f1Cont.innerHTML = `<img src="${BASE_URL}/${item.foto1}" class="img-fluid rounded" style="max-height: 160px; object-fit: contain;">`;
    } else {
        f1Cont.innerHTML = `<span class="text-muted small"><i class="bi bi-image me-1"></i>Tidak ada Foto 1</span>`;
    }

    const f2Cont = document.getElementById('modalFoto2Container');
    if (item.foto2) {
        f2Cont.innerHTML = `<img src="${BASE_URL}/${item.foto2}" class="img-fluid rounded" style="max-height: 160px; object-fit: contain;">`;
    } else {
        f2Cont.innerHTML = `<span class="text-muted small"><i class="bi bi-image me-1"></i>Tidak ada Foto 2</span>`;
    }

    // Tab 5: Tambahan
    document.getElementById('modalAssetBarang').innerHTML = `<span class="badge ${item.asset === 1 ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary'}">${item.asset_label}</span>`;
    document.getElementById('modalAktifBarang').innerHTML = `<span class="badge ${item.aktif === 1 ? 'bg-success' : 'bg-danger'}">${item.aktif_label}</span>`;
    document.getElementById('modalSerialBarang').textContent = item.serial_number || '-';
    document.getElementById('modalCreatedAt').textContent = item.created_at || '-';
    document.getElementById('modalDeskripsiBarang').textContent = item.deskripsi || '-';
    
    bootstrap.Tab.getOrCreateInstance(document.getElementById('bdetail-tab-utama')).show();
    const modal = new bootstrap.Modal(document.getElementById('barangDetailModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', () => {
    loadDependencies();
    loadBarang();
});
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
