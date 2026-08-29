<?php
/**
 * Halaman Daftar Faktur Purchase Order (Faktur Pembelian / 3-Way Matching)
 * Path: admin/pages/faktur_po/index.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection: Khusus Purchasing, Finance, Admin, dan Manager
$user = requireAuth([ROLE_PURCHASING, ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Faktur Purchase Order';
$pageHeading = 'Daftar Faktur Pembelian';

// Ambil daftar Site untuk dropdown filter
$sites = [];
$resSites = $conn->query("SELECT id_site, nama_site, kode_site FROM site ORDER BY nama_site ASC");
if ($resSites) {
    while ($row = $resSites->fetch_assoc()) {
        $sites[] = $row;
    }
}

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
.filter-item-control,
.filter-select,
.filter-btn {
    height: 38px !important;
    font-size: 0.875rem !important;
    line-height: 1.5 !important;
}
.filter-item-control {
    display: flex;
    align-items: center;
}
.filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Faktur Purchase Order (PO)</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/faktur_po/create.php" class="btn btn-primary px-3 shadow-sm fw-semibold filter-btn">
                <i class="bi bi-plus-circle-fill me-1"></i> Buat Faktur PO Baru
            </a>
        </div>
    </div>

    <!-- FILTER & DATA TABLE CARD -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <!-- Search Input (Flex Grow) -->
                <div class="flex-grow-1" style="min-width: 260px;">
                    <div class="input-group" style="height: 38px;">
                        <span class="input-group-text bg-white text-muted border-end-0 filter-item-control"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0 filter-item-control" id="filterSearch" placeholder="Cari No. Faktur, Invoice Vendor, PO, RCV, Vendor..." oninput="debounceLoadFaktur()">
                    </div>
                </div>
                <!-- Site Filter -->
                <div style="min-width: 220px;">
                    <select class="form-select filter-select" id="filterSite" onchange="loadFakturList(1)">
                        <option value="">Semua Site / Gudang</option>
                        <?php foreach ($sites as $s): ?>
                            <option value="<?= $s['id_site'] ?>"><?= htmlspecialchars($s['nama_site']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Status Filter -->
                <div style="min-width: 200px;">
                    <select class="form-select filter-select" id="filterStatus" onchange="loadFakturList(1)">
                        <option value="">Semua Status Tagihan</option>
                        <option value="DRAFT">DRAFT</option>
                        <option value="BELUM DIBAYAR">BELUM DIBAYAR</option>
                        <option value="SEBAGIAN DIBAYAR">SEBAGIAN DIBAYAR</option>
                        <option value="LUNAS">LUNAS</option>
                        <option value="BATAL">BATAL</option>
                    </select>
                </div>
                <!-- Reset Button -->
                <div>
                    <button type="button" class="btn btn-outline-secondary px-3 shadow-none filter-btn" onclick="resetFilters()" title="Reset Filter">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableFaktur" style="font-size: 0.86rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">No</th>
                            <th style="width: 140px;">No. Faktur</th>
                            <th style="width: 120px;">Tanggal</th>
                            <th>Vendor</th>
                            <th style="width: 120px;">Jatuh Tempo</th>
                            <th class="text-center" style="width: 130px;">Aging</th>
                            <th class="text-center" style="width: 130px;">Status</th>
                            <th class="text-end" style="width: 150px;">Total Tagihan</th>
                            <th class="text-center" style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="fakturTableBody">
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data faktur...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAGINATION FOOTER -->
        <div class="card-footer bg-white border-top p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small" id="paginationInfo">Menampilkan 0 dari 0 faktur</div>
            <div id="paginationControls"></div>
        </div>
    </div>
</div>

<!-- MODAL DETAIL FAKTUR PO (5 TAB SERAGAM SESUAI FORM BUAT/EDIT) -->
<div class="modal fade" id="modalDetailFaktur" tabindex="-1" aria-labelledby="modalDetailFakturLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <!-- MODAL HEADER DENGAN 5 NAV TABS -->
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalDetailFakturLabel">
                            Rincian Faktur Purchase Order
                        </h5>
                        <div class="small text-muted font-monospace mt-1" id="modalDetailSubtitle">-</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Nav Tabs Modal (5 Tab Lengkap & Seragam) -->
                <ul class="nav nav-tabs border-bottom-0" id="modalDetailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="modal-tab-dokumen" data-bs-toggle="tab" data-bs-target="#modal-pane-dokumen" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Dokumen Asal
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-vendor" data-bs-toggle="tab" data-bs-target="#modal-pane-vendor" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 2. Vendor &amp; Rekening Bank
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-tagihan" data-bs-toggle="tab" data-bs-target="#modal-pane-tagihan" type="button" role="tab">
                            <i class="bi bi-receipt me-1 text-primary"></i> 3. Tagihan &amp; Pajak
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-rincian" data-bs-toggle="tab" data-bs-target="#modal-pane-rincian" type="button" role="tab">
                            <i class="bi bi-box-seam me-1 text-primary"></i> 4. Rincian Barang
                            <span class="badge bg-primary text-white ms-1" id="modalItemCountBadge">0 Item</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="modal-tab-catatan" data-bs-toggle="tab" data-bs-target="#modal-pane-catatan" type="button" role="tab">
                            <i class="bi bi-card-text me-1 text-primary"></i> 5. Catatan &amp; Finansial
                        </button>
                    </li>
                </ul>
            </div>

            <!-- MODAL BODY DENGAN 5 TAB CONTENT (TANPA ICON DI DALAM KONTEN TAB) -->
            <div class="modal-body p-4">
                <div class="tab-content" id="modalDetailTabContent">
                    
                    <!-- TAB 1: DOKUMEN ASAL -->
                    <div class="tab-pane fade show active" id="modal-pane-dokumen" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Dokumen Penerimaan Barang</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Dokumen Penerimaan (RCV):</span>
                                        <strong class="text-primary font-monospace fs-6" id="detailNomorRcv">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">No. Surat Jalan Vendor (RCV):</span>
                                        <strong class="text-dark font-monospace fs-6" id="detailNomorSjRcv">-</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Tanggal Penerimaan di Gudang:</span>
                                        <span class="text-dark font-monospace" id="detailTanggalRcv">-</span>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Referensi Purchase Order (PO) &amp; Lokasi</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">No. Purchase Order (PO):</span>
                                        <strong class="text-primary font-monospace fs-6" id="detailNomorPo">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Lokasi Site / Gudang Tujuan:</span>
                                        <strong class="text-dark" id="detailSite">-</strong>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Petugas Pembuat Dokumen:</span>
                                        <strong class="text-dark" id="detailPetugas">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: VENDOR & REKENING BANK -->
                    <div class="tab-pane fade" id="modal-pane-vendor" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Informasi Vendor Rekanan</h6>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Nama Vendor:</span>
                                        <strong class="text-dark fs-6" id="detailVendor">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Kontak / Telepon Vendor:</span>
                                        <span class="text-dark" id="detailTeleponVendor">-</span>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Status Tagihan Dokumen:</span>
                                        <div id="detailStatusBadge">-</div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Informasi Rekening Bank Tujuan Transfer</h6>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Nama Bank:</span>
                                        <strong class="text-dark fs-6" id="detailNamaBank">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Nomor Rekening:</span>
                                        <div class="font-monospace text-primary fw-bold fs-5" id="detailNomorRekening">-</div>
                                    </div>
                                    <div>
                                        <span class="text-muted small d-block">Atas Nama Rekening:</span>
                                        <strong class="text-dark" id="detailAtasNamaRekening">-</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: TAGIHAN & PAJAK -->
                    <div class="tab-pane fade" id="modal-pane-tagihan" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Nomor &amp; Waktu Tagihan</h6>
                                    
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Nomor Faktur Sistem:</span>
                                        <strong class="text-primary font-monospace fs-6" id="detailNomorFaktur">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">Nomor Faktur / Invoice Vendor:</span>
                                        <strong class="text-dark font-monospace fs-6" id="detailNomorInvoiceVendor">-</strong>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted small d-block">No. Seri e-Faktur Pajak:</span>
                                        <span class="text-dark font-monospace fw-semibold" id="detailNomorFakturPajak">-</span>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Tanggal Invoice:</span>
                                            <span class="text-dark font-monospace" id="detailTanggalFaktur">-</span>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted small d-block">Tanggal Jatuh Tempo:</span>
                                            <strong class="text-danger font-monospace" id="detailJatuhTempo">-</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Lampiran Berkas Tagihan</h6>
                                    
                                    <div class="mb-3">
                                        <span class="text-muted small d-block">Berkas Invoice / Tagihan Vendor:</span>
                                        <div id="detailFileInvoiceContainer" class="mt-1">
                                            <a href="#" id="detailFileInvoiceLink" target="_blank" class="btn btn-outline-primary btn-sm px-3 py-1">
                                                Buka Berkas Tagihan Vendor
                                            </a>
                                        </div>
                                        <div id="detailFileInvoiceNone" class="text-muted small mt-1 d-none">Tidak ada lampiran invoice.</div>
                                    </div>

                                    <div>
                                        <span class="text-muted small d-block">Berkas e-Faktur Pajak:</span>
                                        <div id="detailFilePajakContainer" class="mt-1">
                                            <a href="#" id="detailFilePajakLink" target="_blank" class="btn btn-outline-primary btn-sm px-3 py-1">
                                                Buka Berkas e-Faktur Pajak
                                            </a>
                                        </div>
                                        <div id="detailFilePajakNone" class="text-muted small mt-1 d-none">Tidak ada lampiran faktur pajak.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: RINCIAN BARANG -->
                    <div class="tab-pane fade" id="modal-pane-rincian" role="tabpanel">
                        <div class="table-responsive border rounded-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light small text-muted text-uppercase align-middle">
                                    <tr class="align-middle">
                                        <th style="width: 40px;" class="text-center align-middle">No</th>
                                        <th style="width: 120px;" class="align-middle">Kode</th>
                                        <th class="align-middle">Nama Barang</th>
                                        <th style="width: 90px;" class="text-center align-middle text-success">Qty Tagih</th>
                                        <th style="width: 80px;" class="text-center align-middle">Satuan</th>
                                        <th style="width: 130px;" class="text-end align-middle">Harga Satuan</th>
                                        <th style="width: 110px;" class="text-end align-middle">Diskon Item</th>
                                        <th style="width: 140px;" class="text-end align-middle">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="detailFakturItemsBody">
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">Memuat rincian barang...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB 5: CATATAN & FINANSIAL -->
                    <div class="tab-pane fade" id="modal-pane-catatan" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3">Catatan Faktur Pembelian</h6>
                                    <p class="mb-0 small text-dark" id="detailCatatan" style="white-space: pre-wrap;">-</p>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="card bg-light border-0 rounded-3 p-3">
                                    <h6 class="fw-bold text-dark mb-3">Ringkasan Finansial Tagihan</h6>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Subtotal Kontrak PO (Ref):</span>
                                        <span class="font-monospace" id="finSubtotalPo">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-dark fw-semibold">Subtotal Barang Diterima (RCV):</span>
                                        <span class="font-monospace fw-bold text-dark" id="finSubtotalDiterima">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small text-danger d-none" id="finRowNilaiRetur">
                                        <span>Potongan Retur PO:</span>
                                        <span class="font-monospace fw-bold" id="finNilaiRetur">- Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small text-danger d-none" id="finRowDiskon">
                                        <span>Diskon Tambahan Faktur:</span>
                                        <span class="font-monospace fw-bold" id="finDiskon">- Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small pt-2 border-top">
                                        <span class="fw-bold text-dark">DPP (Dasar Pengenaan Pajak):</span>
                                        <span class="font-monospace fw-bold text-dark fs-6" id="finDpp">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted" id="finLabelPpn">PPN:</span>
                                        <span class="font-monospace" id="finNominalPajak">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small d-none" id="finRowBiayaLain">
                                        <span class="text-muted">Biaya Lain-lain / Ongkir:</span>
                                        <span class="font-monospace" id="finBiayaLain">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 border-top border-2 border-dark">
                                        <span class="fw-bold fs-6 text-dark">TOTAL TAGIHAN:</span>
                                        <span class="font-monospace fw-bold text-primary fs-5" id="finTotalTagihan">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- MODAL FOOTER -->
            <div class="modal-footer bg-light py-2 px-4 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
const CURRENT_USER_ROLE = '<?= strtoupper($user['role'] ?? '') ?>';
const CAN_PAY_ROLE = ['FINANCE', 'ADMIN', 'MANAGER'].includes(CURRENT_USER_ROLE);
let currentPage = 1;
let debounceTimer = null;
let detailModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    loadFakturList(1);
    const modalEl = document.getElementById('modalDetailFaktur');
    if (modalEl) {
        detailModalInstance = new bootstrap.Modal(modalEl);
    }
});

function debounceLoadFaktur() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadFakturList(1);
    }, 350);
}

function resetFilters() {
    document.getElementById('filterSearch').value = '';
    document.getElementById('filterSite').value = '';
    document.getElementById('filterStatus').value = '';
    loadFakturList(1);
}

async function loadFakturList(page = 1) {
    currentPage = page;
    const search = encodeURIComponent(document.getElementById('filterSearch').value.trim());
    const siteId = document.getElementById('filterSite').value;
    const status = encodeURIComponent(document.getElementById('filterStatus').value);

    let url = `<?= BASE_URL ?>/api/faktur_po/index.php?page=${page}&limit=10`;
    if (search) url += `&q=${search}`;
    if (siteId) url += `&site_id=${siteId}`;
    if (status) url += `&status=${status}`;

    try {
        const res = await fetch(url);
        const result = await res.json();

        if (result && result.success) {
            renderTable(result.data.rows, result.data.pagination);
        } else {
            showEmptyTable(result.message || 'Gagal memuat data faktur.');
        }
    } catch (e) {
        showEmptyTable('Terjadi kesalahan jaringan: ' + e.message);
    }
}

function renderTable(rows, pagination) {
    const tbody = document.getElementById('fakturTableBody');
    if (!rows || rows.length === 0) {
        showEmptyTable('Tidak ada data faktur pembelian yang sesuai.');
        document.getElementById('paginationInfo').textContent = 'Menampilkan 0 dari 0 faktur';
        document.getElementById('paginationControls').innerHTML = '';
        return;
    }

    let html = '';
    const startNo = (pagination.page - 1) * pagination.limit;

    rows.forEach((r, idx) => {
        const no = startNo + idx + 1;
        const totalTagihan = parseFloat(r.total_tagihan) || 0;
        const tglFaktur = r.tanggal_faktur_vendor || r.tanggal_faktur;

        // Aging calculation
        const sisaHari = parseInt(r.sisa_hari_tempo);
        let agingBadge = '';
        if (r.status === 'LUNAS') {
            agingBadge = '<span class="badge bg-success-subtle text-success border px-2 py-1"><i class="bi bi-check-circle me-1"></i>Lunas</span>';
        } else if (isNaN(sisaHari)) {
            agingBadge = '<span class="badge bg-light text-muted border px-2 py-1">-</span>';
        } else if (sisaHari < 0) {
            agingBadge = `<span class="badge bg-danger-subtle text-danger border px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i>Terlambat ${Math.abs(sisaHari)} Hari</span>`;
        } else if (sisaHari === 0) {
            agingBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1"><i class="bi bi-clock me-1"></i>Hari Ini</span>`;
        } else {
            agingBadge = `<span class="badge bg-info-subtle text-info-emphasis border px-2 py-1">Sisa ${sisaHari} Hari</span>`;
        }

        const isLocked = ['SEBAGIAN DIBAYAR', 'LUNAS', 'BATAL'].includes(r.status) || (parseFloat(r.terbayar) > 0);
        const editBtnHtml = isLocked
            ? `<button type="button" class="btn btn-outline-secondary btn-sm px-2 py-1 shadow-none opacity-50" disabled title="Faktur berstatus ${r.status} dan terkunci dari perubahan"><i class="bi bi-pencil"></i></button>`
            : `<a href="<?= BASE_URL ?>/admin/pages/faktur_po/edit.php?id=${r.id_faktur}" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-none" title="Edit Faktur"><i class="bi bi-pencil"></i></a>`;

        const canPay = CAN_PAY_ROLE && !['LUNAS', 'BATAL', 'DRAFT'].includes(r.status) && (parseFloat(r.sisa_tagihan) > 0);
        const payBtnHtml = canPay
            ? `<a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/create.php?id_faktur=${r.id_faktur}" class="btn btn-outline-success btn-sm px-2 py-1 shadow-none" title="Catat Pembayaran ke Vendor"><i class="bi bi-cash-coin"></i></a>`
            : '';

        // Cek apakah data sudah pernah diedit/diupdate (created_at != updated_at)
        const isUpdated = r.updated_at && r.created_at && (r.updated_at !== r.created_at);
        const updatedBadge = isUpdated 
            ? `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-1 ms-1 d-inline-flex align-items-center" title="Faktur telah diedit (Diperbarui: ${formatDate(r.updated_at)})"><i class="bi bi-pencil-fill" style="font-size: 0.65rem;"></i></span>` 
            : '';

        html += `
        <tr>
            <td class="text-center">${no}</td>
            <td>
                <div class="d-inline-flex align-items-center flex-wrap gap-1">
                    <strong class="font-monospace text-primary cursor-pointer hover-underline" style="cursor: pointer;" onclick="viewFakturDetail(${r.id_faktur})" title="Klik untuk lihat detail">${r.nomor_faktur}</strong>
                    ${updatedBadge}
                </div>
            </td>
            <td>
                <span class="text-dark">${formatDate(tglFaktur)}</span>
            </td>
            <td>
                <div class="fw-semibold text-dark">${r.nama_vendor}</div>
            </td>
            <td>
                <span class="text-dark">${formatDate(r.tanggal_jatuh_tempo)}</span>
            </td>
            <td class="text-center">
                ${agingBadge}
            </td>
            <td class="text-center">
                ${getStatusBadge(r.status)}
            </td>
            <td class="text-end font-monospace fw-bold text-dark fs-6">
                ${formatRupiah(totalTagihan)}
            </td>
            <td class="text-center">
                <div class="d-flex justify-content-center gap-1">
                    <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-none" onclick="viewFakturDetail(${r.id_faktur})" title="View Detail Faktur">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${payBtnHtml}
                    ${editBtnHtml}
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;

    // Info & Pagination
    document.getElementById('paginationInfo').textContent = `Menampilkan ${rows.length} dari ${pagination.total} faktur (Halaman ${pagination.page} dari ${pagination.total_pages || 1})`;
    renderPaginationControls(pagination);
}

function showEmptyTable(message) {
    document.getElementById('fakturTableBody').innerHTML = `
        <tr>
            <td colspan="9" class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                ${message}
            </td>
        </tr>`;
}

function renderPaginationControls(p) {
    const container = document.getElementById('paginationControls');
    if (!container) return;

    const totalPages = Math.max(1, parseInt(p.total_pages) || 1);
    const currPage = parseInt(p.page) || 1;

    let html = '<ul class="pagination pagination-sm mb-0">';
    html += `<li class="page-item ${currPage <= 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="loadFakturList(${currPage - 1})" ${currPage <= 1 ? 'disabled' : ''}><i class="bi bi-chevron-left"></i></button>
             </li>`;

    for (let i = 1; i <= totalPages; i++) {
        if (i === 1 || i === totalPages || (i >= currPage - 1 && i <= currPage + 1)) {
            html += `<li class="page-item ${i === currPage ? 'active' : ''}">
                        <button class="page-link" onclick="loadFakturList(${i})">${i}</button>
                     </li>`;
        } else if (i === currPage - 2 || i === currPage + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${currPage >= totalPages ? 'disabled' : ''}">
                <button class="page-link" onclick="loadFakturList(${currPage + 1})" ${currPage >= totalPages ? 'disabled' : ''}><i class="bi bi-chevron-right"></i></button>
             </li>`;
    html += '</ul>';
    container.innerHTML = html;
}

function getStatusBadge(st) {
    switch (st) {
        case 'DRAFT':
            return '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="bi bi-file-earmark me-1"></i>Draft</span>';
        case 'BELUM DIBAYAR':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Belum Dibayar</span>';
        case 'SEBAGIAN DIBAYAR':
            return '<span class="badge bg-info-subtle text-info-emphasis border px-2 py-1"><i class="bi bi-pie-chart me-1"></i>Sebagian Dibayar</span>';
        case 'LUNAS':
            return '<span class="badge bg-success-subtle text-success border px-2 py-1"><i class="bi bi-check-all me-1"></i>Lunas</span>';
        case 'BATAL':
            return '<span class="badge bg-danger-subtle text-danger border px-2 py-1"><i class="bi bi-x-circle me-1"></i>Batal</span>';
        default:
            return `<span class="badge bg-light text-dark border px-2 py-1">${st}</span>`;
    }
}

async function viewFakturDetail(idFaktur) {
    if (!detailModalInstance) {
        detailModalInstance = new bootstrap.Modal(document.getElementById('modalDetailFaktur'));
    }
    
    // Reset tab ke tab 1
    const firstTabBtn = document.getElementById('modal-tab-dokumen');
    if (firstTabBtn) {
        bootstrap.Tab.getInstance(firstTabBtn)?.show() || new bootstrap.Tab(firstTabBtn).show();
    }

    detailModalInstance.show();

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/faktur_po/index.php?id=${idFaktur}`);
        const result = await res.json();

        if (result && result.success && result.data) {
            renderModalDetailContent(result.data);
        } else {
            alert(result.message || 'Gagal memuat data detail faktur.');
        }
    } catch (e) {
        alert('Terjadi kesalahan: ' + e.message);
    }
}

function renderModalDetailContent(d) {
    const isUpdated = d.updated_at && d.created_at && (d.updated_at !== d.created_at);
    const subtitleHtml = `No. Faktur: ${d.nomor_faktur || '-'} | Invoice Vendor: ${d.nomor_faktur_vendor || '-'}` +
        (isUpdated ? ` <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle py-1 px-1 ms-2 d-inline-flex align-items-center" title="Faktur telah diedit (Diperbarui: ${formatDate(d.updated_at)})"><i class="bi bi-pencil-fill" style="font-size: 0.65rem;"></i></span>` : '');
    document.getElementById('modalDetailSubtitle').innerHTML = subtitleHtml;

    // 1. Tab 1: Dokumen Asal
    document.getElementById('detailNomorRcv').textContent = d.nomor_rcv ? `RCV: ${d.nomor_rcv}` : '-';
    document.getElementById('detailNomorSjRcv').textContent = d.nomor_sj_rcv || '-';
    document.getElementById('detailTanggalRcv').textContent = formatDate(d.tanggal_rcv_diterima || d.created_at);
    document.getElementById('detailNomorPo').textContent = d.nomor_po ? `PO: ${d.nomor_po}` : '-';
    document.getElementById('detailSite').textContent = d.nama_site || '-';
    document.getElementById('detailPetugas').textContent = d.nama_pembuat || 'Admin';

    // 2. Tab 2: Vendor & Rekening Bank
    document.getElementById('detailVendor').textContent = d.nama_vendor || '-';
    document.getElementById('detailTeleponVendor').textContent = d.telepon_vendor ? `Telp: ${d.telepon_vendor}` : '-';
    document.getElementById('detailStatusBadge').innerHTML = getStatusBadge(d.status);
    document.getElementById('detailNamaBank').textContent = d.nama_bank || '-';
    document.getElementById('detailNomorRekening').textContent = d.nomor_rekening || '-';
    document.getElementById('detailAtasNamaRekening').textContent = d.atas_nama_rekening || '-';

    // 3. Tab 3: Tagihan & Pajak
    document.getElementById('detailNomorFaktur').textContent = d.nomor_faktur || '-';
    document.getElementById('detailNomorInvoiceVendor').textContent = d.nomor_faktur_vendor || '-';
    document.getElementById('detailNomorFakturPajak').textContent = d.nomor_faktur_pajak || '-';
    document.getElementById('detailTanggalFaktur').textContent = formatDate(d.tanggal_faktur_vendor || d.tanggal_faktur);
    document.getElementById('detailJatuhTempo').textContent = `${formatDate(d.tanggal_jatuh_tempo)} (${d.term_of_payment || 0} Hari)`;

    // File Lampiran
    const fileInvContainer = document.getElementById('detailFileInvoiceContainer');
    const fileInvNone = document.getElementById('detailFileInvoiceNone');
    if (d.file_faktur_vendor) {
        fileInvContainer.classList.remove('d-none');
        fileInvNone.classList.add('d-none');
        document.getElementById('detailFileInvoiceLink').href = `<?= BASE_URL ?>/uploads/faktur/${d.file_faktur_vendor}`;
    } else {
        fileInvContainer.classList.add('d-none');
        fileInvNone.classList.remove('d-none');
    }

    const filePajakContainer = document.getElementById('detailFilePajakContainer');
    const filePajakNone = document.getElementById('detailFilePajakNone');
    if (d.file_faktur_pajak) {
        filePajakContainer.classList.remove('d-none');
        filePajakNone.classList.add('d-none');
        document.getElementById('detailFilePajakLink').href = `<?= BASE_URL ?>/uploads/faktur/${d.file_faktur_pajak}`;
    } else {
        filePajakContainer.classList.add('d-none');
        filePajakNone.classList.remove('d-none');
    }

    // 4. Tab 4: Rincian Barang
    const items = d.items || [];
    document.getElementById('modalItemCountBadge').textContent = `${items.length} Item`;
    let itemsHtml = '';
    items.forEach((it, idx) => {
        itemsHtml += `
        <tr>
            <td class="text-center">${idx + 1}</td>
            <td class="font-monospace">${it.kode_barang || '-'}</td>
            <td>
                <strong>${it.nama_barang}</strong>
                ${it.nama_kategori ? `<div class="small text-muted" style="font-size:0.75rem;">${it.nama_kategori}</div>` : ''}
            </td>
            <td class="text-center font-monospace fw-bold text-success">${parseFloat(it.qty_tagih)}</td>
            <td class="text-center">${it.satuan || 'Unit'}</td>
            <td class="text-end font-monospace">${formatRupiah(it.harga_satuan)}</td>
            <td class="text-end font-monospace">${parseFloat(it.diskon_item) > 0 ? formatRupiah(it.diskon_item) : '-'}</td>
            <td class="text-end font-monospace fw-bold">${formatRupiah(it.subtotal)}</td>
        </tr>`;
    });
    document.getElementById('detailFakturItemsBody').innerHTML = itemsHtml || '<tr><td colspan="8" class="text-center py-3 text-muted">Tidak ada data rincian barang.</td></tr>';

    // 5. Tab 5: Catatan & Finansial
    document.getElementById('detailCatatan').textContent = d.keterangan || 'Tidak ada catatan khusus.';
    document.getElementById('finSubtotalPo').textContent = formatRupiah(d.subtotal_po);
    document.getElementById('finSubtotalDiterima').textContent = formatRupiah(d.subtotal_diterima);

    const nilaiRetur = parseFloat(d.nilai_retur) || 0;
    const rowRetur = document.getElementById('finRowNilaiRetur');
    if (nilaiRetur > 0) {
        rowRetur.classList.remove('d-none');
        document.getElementById('finNilaiRetur').textContent = `- ${formatRupiah(nilaiRetur)}`;
    } else {
        rowRetur.classList.add('d-none');
    }

    const diskon = parseFloat(d.diskon) || 0;
    const rowDiskon = document.getElementById('finRowDiskon');
    if (diskon > 0) {
        rowDiskon.classList.remove('d-none');
        document.getElementById('finDiskon').textContent = `- ${formatRupiah(diskon)}`;
    } else {
        rowDiskon.classList.add('d-none');
    }

    document.getElementById('finDpp').textContent = formatRupiah(d.dpp);
    document.getElementById('finLabelPpn').textContent = `PPN (${d.rate_pajak || 0}%):`;
    document.getElementById('finNominalPajak').textContent = formatRupiah(d.nominal_pajak);

    const biayaLain = parseFloat(d.biaya_lain) || 0;
    const rowBiayaLain = document.getElementById('finRowBiayaLain');
    if (biayaLain > 0) {
        rowBiayaLain.classList.remove('d-none');
        document.getElementById('finBiayaLain').textContent = formatRupiah(biayaLain);
    } else {
        rowBiayaLain.classList.add('d-none');
    }

    document.getElementById('finTotalTagihan').textContent = formatRupiah(d.total_tagihan);
}

function editFaktur(idFaktur) {
    window.location.href = `<?= BASE_URL ?>/admin/pages/faktur_po/create.php?id=${idFaktur}`;
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d)) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${String(d.getDate()).padStart(2, '0')} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function formatRupiah(num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
