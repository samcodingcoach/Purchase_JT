<?php
/**
 * Halaman Verifikasi & Proses Request Order (RO) ke Purchase Order (PO)
 * Path: admin/pages/request_order/proses_po.php
 * Akses: PURCHASING, MANAGER, ADMIN
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Proses ke Purchase Order';
$pageHeading = 'Verifikasi & Proses PO';

$idRequest = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$idRequest) {
    header('Location: ' . BASE_URL . '/admin/pages/request_order/index.php');
    exit;
}

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0 pb-5">
    
    <!-- HEADER HALAMAN BERSIH -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">
            <i class="bi bi-cart-check-fill text-primary me-2"></i>Proses ke Purchase Order (PO)
        </h4>
    </div>

    <!-- SKELETON LOADING -->
    <div id="loadingWrapper" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <h6 class="text-muted fw-semibold">Memuat formulir Purchase Order...</h6>
    </div>

    <!-- MAIN FORM WRAPPER (1 KOLOM PENUH DENGAN 3 TAB FUNGSIONAL) -->
    <div id="mainContentWrapper" class="d-none">
        
        <form id="formProsesPo" onsubmit="handleApproveToPo(event)">
            
            <div class="card border-0 shadow-sm rounded-3">
                
                <!-- NAV TABS HEADER (3 TAB FUNGSIONAL) -->
                <div class="card-header bg-white border-bottom p-0">
                    <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="poProcessTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-semibold small" id="tab-po" data-bs-toggle="tab" data-bs-target="#pane-po" type="button" role="tab">
                                <i class="bi bi-building text-primary me-1"></i> 1. Data Purchase Order (PO)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="tab-pengiriman" data-bs-toggle="tab" data-bs-target="#pane-pengiriman" type="button" role="tab">
                                <i class="bi bi-truck text-primary me-1"></i> 2. Pengiriman
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-semibold small" id="tab-biaya" data-bs-toggle="tab" data-bs-target="#pane-biaya" type="button" role="tab">
                                <i class="bi bi-boxes text-primary me-1"></i> 3. Rincian Barang &amp; Biaya
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="card-body p-4">
                    <div class="tab-content">
                        
                        <!-- =========================================================
                             TAB 1: DATA PURCHASE ORDER (PO) - 2 KOLOM RAPI
                             ========================================================= -->
                        <div class="tab-pane fade show active" id="pane-po" role="tabpanel">
                            
                            <div class="row g-4">
                                
                                <!-- BARIS 1: NOMOR PO (KIRI) - VENDOR READONLY (KANAN) -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor Purchase Order <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light font-monospace"><i class="bi bi-upc-scan"></i></span>
                                        <input type="text" class="form-control font-monospace fw-bold" id="inputNomorPo" name="nomor_po" required placeholder="PO-2608-0001">
                                        <button type="button" class="btn btn-outline-secondary" onclick="fetchNextPoNumber()" title="Generate Ulang Nomor">
                                            <i class="bi bi-arrow-clockwise"></i>
                                        </button>
                                    </div>
                                    <div class="form-text small text-muted">Format: PO-YYMM-XXXX (Otomatis terisi).</div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Vendor</label>
                                    <input type="hidden" id="inputVendorId" name="id_vendor" required>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                                        <input type="text" class="form-control bg-light fw-bold text-dark" id="displayVendorName" readonly placeholder="Memuat vendor dari RO...">
                                    </div>
                                </div>

                                <!-- BARIS 2: TANGGAL PO (KIRI) - T.O.P (KANAN) -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Tanggal Purchase Order <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                                        <input type="date" class="form-control" id="inputTanggalPo" name="tanggal_po" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-bold mb-0">T.O.P (Term of Payment)</label>
                                        <span class="badge bg-success font-monospace d-none" id="badgeTopKeterangan" style="font-size: 0.72rem;">C.O.D</span>
                                    </div>
                                    <div class="input-group">
                                        <input type="number" class="form-control font-monospace" id="inputTop" name="term_of_payment" value="30" min="0" max="180" oninput="updateTopKeterangan(this.value)">
                                        <span class="input-group-text bg-light">Hari</span>
                                    </div>
                                    <div class="form-text small text-muted" id="hintTopText">
                                        * Jika 0 hari berarti C.O.D (Cash On Delivery).
                                    </div>
                                </div>

                                <!-- BARIS 3: NOMOR RO (KIRI) - PRIORITAS (KANAN) -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Nomor RO</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-file-earmark-text"></i></span>
                                        <input type="text" class="form-control bg-light font-monospace fw-bold text-dark" id="displayRoNomor" readonly placeholder="Memuat Nomor RO...">
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Prioritas</label>
                                    <input type="hidden" id="inputPrioritas" name="prioritas" value="NORMAL">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-flag"></i></span>
                                        <input type="text" class="form-control bg-light fw-bold text-dark font-monospace" id="displayRoPrioritas" readonly placeholder="NORMAL">
                                    </div>
                                </div>

                                <!-- BARIS 4: CATATAN PO (FULL WIDTH) -->
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Catatan / Instruksi Purchase Order</label>
                                    <textarea class="form-control" id="inputKeteranganPo" name="keterangan" rows="3" placeholder="Contoh: Lampirkan faktur pajak asli &amp; surat jalan saat serah terima barang..."></textarea>
                                </div>

                            </div>

                        </div>

                        <!-- =========================================================
                             TAB 2: PENGIRIMAN (METODE, ESTIMASI TANGGAL, ALAMAT SITE)
                             ========================================================= -->
                        <div class="tab-pane fade" id="pane-pengiriman" role="tabpanel">
                            
                            <div class="row g-4">
                                
                                <!-- Metode Pengiriman -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Metode Pengiriman</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-box-seam"></i></span>
                                        <select class="form-select" id="selectPengiriman" name="pengiriman">
                                            <option value="Vendor">Vendor (Diantar oleh Vendor)</option>
                                            <option value="Expedisi">Expedisi (Jasa Kurir / Ekspedisi Logistik)</option>
                                            <option value="Internal">Internal (Armada Perusahaan Sendiri)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Estimasi Tanggal Pengiriman -->
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Estimasi Tanggal Pengiriman</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-calendar-check"></i></span>
                                        <input type="date" class="form-control" id="inputTanggalPengiriman" name="tanggal_pengiriman">
                                    </div>
                                </div>

                                <!-- Alamat Pengiriman (Default dari Site Alamat) -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label small fw-bold mb-0">Alamat Pengiriman</label>
                                        <span class="small text-muted font-monospace" id="labelSiteName">Site: -</span>
                                    </div>
                                    <textarea class="form-control" id="inputAlamatPengiriman" name="alamat" rows="3" placeholder="Alamat lengkap penerimaan barang di lokasi site..."></textarea>
                                </div>

                            </div>

                        </div>

                        <!-- =========================================================
                             TAB 3: RINCIAN BARANG & BIAYA
                             ========================================================= -->
                        <div class="tab-pane fade" id="pane-biaya" role="tabpanel">
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">
                                        <i class="bi bi-boxes text-primary me-2"></i>Rincian Barang &amp; Penetapan Harga Satuan
                                    </h6>
                                    <span class="text-muted small">
                                        Daftar barang diambil otomatis dari permohonan. Lengkapi harga satuan &amp; diskon vendor.
                                    </span>
                                </div>
                                <span class="badge bg-secondary-subtle text-secondary font-monospace" id="roTotalItemsBadge">0 Item</span>
                            </div>

                            <!-- Tabel Input Harga Barang -->
                            <div class="table-responsive border rounded-3 mb-4">
                                <table class="table table-bordered align-middle mb-0" id="tablePricingItems">
                                    <thead class="table-light small text-muted text-uppercase">
                                        <tr>
                                            <th class="text-center" style="width: 45px;">#</th>
                                            <th style="min-width: 250px;">Barang &amp; Spesifikasi</th>
                                            <th class="text-center" style="width: 90px;">Qty</th>
                                            <th style="width: 170px;">Harga Satuan (Rp)</th>
                                            <th style="width: 140px;">Diskon Item (Rp)</th>
                                            <th class="text-end" style="width: 160px;">Subtotal (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablePricingItemsBody">
                                        <!-- Rendered dynamically -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pengaturan Pajak, Diskon Akhir & Ringkasan Total -->
                            <div class="row g-4 justify-content-between align-items-start">
                                
                                <!-- Pengaturan Pajak & Diskon Akhir -->
                                <div class="col-md-6">
                                    <div class="p-3 border rounded-3 bg-light">
                                        <h6 class="fw-bold text-dark small mb-3">Pengaturan Pajak &amp; Diskon Akhir PO:</h6>
                                        
                                        <!-- Checkbox Pajak, Pilihan Tarif (11% / 12%), & Checkbox Total Termasuk Pajak -->
                                        <div class="mb-3 p-3 bg-white rounded border">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                                <div class="form-check m-0">
                                                    <input class="form-check-input" type="checkbox" id="checkEnablePajak" checked onchange="togglePajak(this.checked)">
                                                    <label class="form-check-label fw-bold small text-dark" for="checkEnablePajak">
                                                        Kena Pajak PPN
                                                    </label>
                                                </div>
                                                <div class="d-flex align-items-center gap-1" id="wrapperPajakRate">
                                                    <label class="small text-muted mb-0 me-1" style="font-size: 0.78rem;">Tarif:</label>
                                                    <select class="form-select form-select-sm font-monospace py-0 px-2" id="selectPajakRate" style="width: 85px; height: 28px; font-size: 0.8rem;" onchange="onPajakRateChange()">
                                                        <option value="12" selected>12%</option>
                                                        <option value="11">11%</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="checkTermasukPajak" onchange="calculateAllTotals()">
                                                <label class="form-check-label small text-dark" for="checkTermasukPajak">
                                                    Total Termasuk Pajak (Tax Inclusive)
                                                </label>
                                            </div>
                                            <input type="hidden" id="inputPajakPpn" name="pajak" value="12">
                                            <input type="hidden" id="inputTotalTermasukPajak" name="total_termasuk_pajak" value="0">
                                        </div>

                                        <!-- Diskon Akhir: Nominal (Rp) atau Persentase (%) -->
                                        <div class="p-3 bg-white rounded border">
                                            <div class="row g-2 align-items-center">
                                                <div class="col-sm-5">
                                                    <label class="form-label small fw-bold mb-1">Tipe Diskon Akhir:</label>
                                                    <select class="form-select form-select-sm" id="selectDiskonType" onchange="onDiskonTypeChange()">
                                                        <option value="nominal">Nominal (Rp)</option>
                                                        <option value="percent">Persentase (%)</option>
                                                    </select>
                                                </div>
                                                <div class="col-sm-7">
                                                    <label class="form-label small fw-bold mb-1" id="labelNilaiDiskon">Nilai Diskon (Rp):</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light font-monospace" id="addonDiskonPrefix">Rp</span>
                                                        <input type="number" class="form-control font-monospace" id="inputDiskonNilai" value="0" min="0" step="any" oninput="calculateAllTotals()">
                                                        <span class="input-group-text bg-light font-monospace d-none" id="addonDiskonSuffix">%</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                <!-- Ringkasan Total Biaya -->
                                <div class="col-md-5">
                                    <div class="p-3 border rounded-3 bg-white shadow-xs">
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Subtotal Barang:</span>
                                            <span class="fw-semibold text-dark font-monospace" id="summarySubtotal">Rp 0</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">Diskon Akhir:</span>
                                            <span class="fw-semibold text-danger font-monospace" id="summaryDiskon">- Rp 0</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted">DPP (Dasar Pengenaan Pajak):</span>
                                            <span class="fw-semibold text-dark font-monospace" id="summaryDpp">Rp 0</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 small">
                                            <span class="text-muted" id="labelSummaryPpn">PPN (12%):</span>
                                            <span class="fw-semibold text-dark font-monospace" id="summaryPpn">Rp 0</span>
                                        </div>
                                        <hr class="my-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-dark">GRAND TOTAL:</span>
                                            <span class="fs-5 fw-bold text-primary font-monospace" id="summaryGrandTotal">Rp 0</span>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>
                </div>

                <!-- FOOTER BAR AKSI KEPUTUSAN PURCHASING -->
                <div class="card-footer bg-light p-3 border-top d-flex justify-content-end align-items-center flex-wrap gap-2">
                    <!-- Tombol Tolak -->
                    <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold" onclick="openRejectModal()">
                        <i class="bi bi-x-circle me-1"></i> Tolak (Tidak Disetujui)
                    </button>
                    <!-- Tombol Batal -->
                    <button type="button" class="btn btn-outline-dark btn-sm px-3 fw-semibold" onclick="openCancelModal()">
                        <i class="bi bi-slash-circle me-1"></i> Batalkan RO
                    </button>
                    <!-- Tombol Simpan sebagai Draft -->
                    <button type="button" class="btn btn-warning btn-sm px-3 fw-semibold text-dark shadow-sm" id="btnSaveDraftPo" onclick="handleSaveDraftPo()">
                        <i class="bi bi-file-earmark-diff me-1"></i> Simpan sebagai Draft
                    </button>
                    <!-- Tombol Setujui & Terbitkan PO -->
                    <button type="submit" id="btnSubmitPo" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Setujui &amp; Terbitkan Purchase Order
                    </button>
                </div>

            </div>

        </form>

    </div>

</div>

<!-- =============================================================
     MODAL KONFIRMASI TOLAK RO (TIDAK DISETUJUI)
     ============================================================= -->
<div class="modal fade" id="modalRejectRo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-x-circle-fill me-2"></i>Tolak Request Order (Tidak Disetujui)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Mohon masukkan alasan penolakan Request Order ini. Catatan akan tersimpan dan dapat dilihat oleh pemohon:
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectAlasanText" rows="4" required placeholder="Contoh: Anggaran unit belum disetujui / Stok di gudang masih mencukupi..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm px-4 fw-semibold" id="btnConfirmReject" onclick="submitRejectRo()">
                    <i class="bi bi-x-circle me-1"></i> Konfirmasi Tolak
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =============================================================
     MODAL KONFIRMASI BATALKAN RO
     ============================================================= -->
<div class="modal fade" id="modalCancelRo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-slash-circle me-2"></i>Batalkan Request Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="small text-muted mb-3">
                    Apakah Anda yakin ingin membatalkan dokumen Request Order ini?
                </p>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Alasan Pembatalan <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="cancelAlasanText" rows="4" required placeholder="Tuliskan alasan pembatalan..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-dark btn-sm px-4 fw-semibold" id="btnConfirmCancel" onclick="submitCancelRo()">
                    <i class="bi bi-slash-circle me-1"></i> Konfirmasi Pembatalan
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<!-- Client-side Logic Script for PO Process -->
<script>
const ID_REQUEST = <?= $idRequest ?>;
let roDataCache = null;
let modalRejectInstance = null;
let modalCancelInstance = null;
let calculatedDiskonNominal = 0;

document.addEventListener('DOMContentLoaded', async () => {
    modalRejectInstance = new bootstrap.Modal(document.getElementById('modalRejectRo'));
    modalCancelInstance = new bootstrap.Modal(document.getElementById('modalCancelRo'));

    await Promise.all([
        fetchNextPoNumber(),
        loadRoDetails()
    ]);
});

// -------------------------------------------------------------
// UPDATE T.O.P KETERANGAN (0 = C.O.D)
// -------------------------------------------------------------
function updateTopKeterangan(val) {
    const num = parseInt(val) || 0;
    const badge = document.getElementById('badgeTopKeterangan');
    const hint = document.getElementById('hintTopText');

    if (num === 0) {
        badge.classList.remove('d-none');
        badge.textContent = 'C.O.D (Cash On Delivery)';
        hint.innerHTML = '<span class="text-success fw-bold"><i class="bi bi-check-circle me-1"></i>0 Hari = Pembayaran Tunai / C.O.D (Cash On Delivery)</span>';
    } else {
        badge.classList.add('d-none');
        hint.innerHTML = '* Jika 0 hari berarti C.O.D (Cash On Delivery).';
    }
}

// -------------------------------------------------------------
// PENGATURAN PAJAK (11% / 12%) & TIPE DISKON
// -------------------------------------------------------------
function togglePajak(enable) {
    const rateSelect = document.getElementById('selectPajakRate');
    rateSelect.disabled = !enable;
    const selectedRate = parseInt(rateSelect.value) || 12;
    document.getElementById('inputPajakPpn').value = enable ? selectedRate : 0;
    calculateAllTotals();
}

function onPajakRateChange() {
    const selectedRate = parseInt(document.getElementById('selectPajakRate').value) || 12;
    if (document.getElementById('checkEnablePajak').checked) {
        document.getElementById('inputPajakPpn').value = selectedRate;
    }
    calculateAllTotals();
}

function onDiskonTypeChange() {
    const type = document.getElementById('selectDiskonType').value;
    const prefix = document.getElementById('addonDiskonPrefix');
    const suffix = document.getElementById('addonDiskonSuffix');
    const label = document.getElementById('labelNilaiDiskon');

    if (type === 'percent') {
        prefix.classList.add('d-none');
        suffix.classList.remove('d-none');
        label.textContent = 'Nilai Diskon (%):';
    } else {
        prefix.classList.remove('d-none');
        suffix.classList.add('d-none');
        label.textContent = 'Nilai Diskon (Rp):';
    }
    calculateAllTotals();
}

// -------------------------------------------------------------
// LOAD RO DETAILS
// -------------------------------------------------------------
async function loadRoDetails() {
    const res = await apiRequest(`/api/request_order/index.php?id=${ID_REQUEST}`);
    if (!res || !res.success) {
        showToast(res ? res.message : 'Gagal memuat detail Request Order.', 'danger');
        document.getElementById('loadingWrapper').innerHTML = `
            <div class="alert alert-danger d-inline-block px-4 py-3">
                <i class="bi bi-exclamation-octagon me-2"></i> ${res ? res.message : 'Request Order tidak ditemukan.'}
            </div>
        `;
        return;
    }

    roDataCache = res.data;
    renderRoData();
}

function renderRoData() {
    const ro = roDataCache;

    // 0. Nomor RO (Readonly)
    document.getElementById('displayRoNomor').value = ro.nomor || '-';

    // 1. Vendor (Dari Logistik - Readonly)
    document.getElementById('inputVendorId').value = ro.id_vendor || '';
    if (ro.nama_vendor) {
        document.getElementById('displayVendorName').value = `${ro.nama_vendor} (${ro.kode_vendor || 'VND'})`;
    } else {
        document.getElementById('displayVendorName').value = 'Belum ditentukan oleh Logistik';
    }

    // 2. Prioritas PO (Readonly)
    const prio = ro.prioritas ? ro.prioritas.toUpperCase() : 'NORMAL';
    document.getElementById('inputPrioritas').value = prio;
    document.getElementById('displayRoPrioritas').value = prio;

    // 3. Tab Pengiriman: Alamat Pengiriman default dari Site.alamat
    if (ro.nama_site) {
        document.getElementById('labelSiteName').textContent = `Site: ${ro.nama_site} (${ro.kode_site || '-'})`;
    }
    if (ro.alamat_site) {
        document.getElementById('inputAlamatPengiriman').value = ro.alamat_site;
    } else if (ro.nama_site) {
        document.getElementById('inputAlamatPengiriman').value = `Lokasi ${ro.nama_site}`;
    }

    // 4. Inisialisasi T.O.P info
    updateTopKeterangan(document.getElementById('inputTop').value);

    // 5. Render Items di Tab Pricing (Lengkap dengan Kategori, Merk, & Total Stok)
    const items = ro.items || [];
    document.getElementById('roTotalItemsBadge').textContent = `${items.length} Item Barang`;

    let pricingHtml = '';
    items.forEach((item, idx) => {
        // Ambil harga_set dari barang_hargavendor yang berlaku paling akhir (atau fallback ke harga RO)
        const defaultHarga = parseFloat(item.harga_set) > 0 ? parseFloat(item.harga_set) : (parseFloat(item.harga) || 0);
        const subtotal = item.qty * defaultHarga;
        const totalStok = parseInt(item.total_stok) || 0;

        pricingHtml += `
            <tr data-item-id="${item.id_barang}">
                <td class="text-center font-monospace small text-muted">${idx + 1}</td>
                <td>
                    <div class="fw-bold text-dark mb-1">${escapeHtml(item.nama_barang)}</div>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">${escapeHtml(item.kode_barang || 'BRG')}</span>
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-tag me-1"></i>${escapeHtml(item.nama_kategori || 'Material')}</span>
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;"><i class="bi bi-bookmark me-1"></i>${escapeHtml(item.nama_merk || 'Umum')}</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle font-monospace" style="font-size: 0.68rem;">
                            <i class="bi bi-boxes me-1"></i>Stok: ${totalStok} ${escapeHtml(item.satuan)}
                        </span>
                    </div>
                    <input type="hidden" class="item-id-barang" value="${item.id_barang}">
                </td>
                <td class="text-center">
                    <span class="fw-bold font-monospace">${item.qty}</span>
                    <span class="small text-muted d-block" style="font-size: 0.72rem;">${escapeHtml(item.satuan)}</span>
                    <input type="hidden" class="item-qty" value="${item.qty}">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light font-monospace" style="font-size: 0.75rem;">Rp</span>
                        <input type="number" class="form-control form-control-sm font-monospace item-harga" value="${defaultHarga}" min="0" step="100" oninput="calculateRowSubtotal(this)" required>
                    </div>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm font-monospace item-diskon" value="0" min="0" step="100" oninput="calculateRowSubtotal(this)">
                </td>
                <td class="text-end font-monospace fw-bold text-dark item-subtotal-display">
                    ${formatRupiah(subtotal)}
                </td>
            </tr>
        `;
    });

    document.getElementById('tablePricingItemsBody').innerHTML = pricingHtml;

    // Hitung kalkulasi awal
    calculateAllTotals();

    // Tampilkan Konten Utama
    document.getElementById('loadingWrapper').classList.add('d-none');
    document.getElementById('mainContentWrapper').classList.remove('d-none');

    // Jika status bukan TERKIRIM atau DRAFT, nonaktifkan tombol submit
    if (ro.status === 'DISETUJUI' || ro.status === 'TIDAK DISETUJUI' || ro.status === 'BATAL') {
        document.getElementById('btnSubmitPo').disabled = true;
        document.getElementById('btnSubmitPo').innerHTML = '<i class="bi bi-lock-fill me-1"></i> RO Sudah Selesai Diproses';
        const btnDraft = document.getElementById('btnSaveDraftPo');
        if (btnDraft) btnDraft.classList.add('d-none');
    }
}

// -------------------------------------------------------------
// FETCH NEXT PO NUMBER
// -------------------------------------------------------------
async function fetchNextPoNumber() {
    const res = await apiRequest('/api/purchase_order/get_next_number.php');
    if (res && res.success && res.data && res.data.nomor_po) {
        document.getElementById('inputNomorPo').value = res.data.nomor_po;
    }
}

// -------------------------------------------------------------
// KALKULASI HARGA & TOTAL (PAJAK 12%, INKLUSIF/EKSKLUSIF, DISKON RP/%)
// -------------------------------------------------------------
function calculateRowSubtotal(inputEl) {
    const row = inputEl.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const harga = parseFloat(row.querySelector('.item-harga').value) || 0;
    const diskon = parseFloat(row.querySelector('.item-diskon').value) || 0;

    let subtotal = (qty * harga) - diskon;
    if (subtotal < 0) subtotal = 0;

    row.querySelector('.item-subtotal-display').textContent = formatRupiah(subtotal);
    calculateAllTotals();
}

function calculateAllTotals() {
    let subtotalBarang = 0;
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const harga = parseFloat(row.querySelector('.item-harga').value) || 0;
        const diskon = parseFloat(row.querySelector('.item-diskon').value) || 0;
        let rowSubtotal = (qty * harga) - diskon;
        if (rowSubtotal < 0) rowSubtotal = 0;
        subtotalBarang += rowSubtotal;
    });

    // Kalkulasi Diskon Akhir (Nominal vs Persentase)
    const diskonType = document.getElementById('selectDiskonType').value;
    const diskonInput = parseFloat(document.getElementById('inputDiskonNilai').value) || 0;
    let diskonAkhirNominal = 0;
    if (diskonType === 'percent') {
        diskonAkhirNominal = (subtotalBarang * diskonInput) / 100;
    } else {
        diskonAkhirNominal = diskonInput;
    }
    if (diskonAkhirNominal > subtotalBarang) diskonAkhirNominal = subtotalBarang;
    if (diskonAkhirNominal < 0) diskonAkhirNominal = 0;
    calculatedDiskonNominal = diskonAkhirNominal;

    const dasarSetelahDiskon = subtotalBarang - diskonAkhirNominal;

    // Pengaturan Pajak PPN (11% / 12%) & Inklusif
    const isPajakEnabled = document.getElementById('checkEnablePajak').checked;
    const selectedRate = parseInt(document.getElementById('selectPajakRate').value) || 12;
    const ppnRate = isPajakEnabled ? selectedRate : 0;
    document.getElementById('inputPajakPpn').value = ppnRate;

    const isTermasukPajak = isPajakEnabled && document.getElementById('checkTermasukPajak').checked;
    document.getElementById('inputTotalTermasukPajak').value = isTermasukPajak ? 1 : 0;

    let dpp = 0;
    let ppnAmount = 0;
    let grandTotal = 0;

    if (ppnRate > 0) {
        if (isTermasukPajak) {
            // Tax Inclusive: Nilai setelah diskon sudah merupakan Grand Total
            grandTotal = dasarSetelahDiskon;
            dpp = grandTotal / (1 + (ppnRate / 100));
            ppnAmount = grandTotal - dpp;
        } else {
            // Tax Exclusive: DPP adalah nilai setelah diskon, PPN ditambahkan
            dpp = dasarSetelahDiskon;
            ppnAmount = (dpp * ppnRate) / 100;
            grandTotal = dpp + ppnAmount;
        }
    } else {
        dpp = dasarSetelahDiskon;
        ppnAmount = 0;
        grandTotal = dpp;
    }

    document.getElementById('summarySubtotal').textContent = formatRupiah(subtotalBarang);
    document.getElementById('summaryDiskon').textContent = `- ${formatRupiah(diskonAkhirNominal)}`;
    document.getElementById('summaryDpp').textContent = formatRupiah(dpp);
    document.getElementById('labelSummaryPpn').textContent = `PPN (${ppnRate}%)${isTermasukPajak ? ' (Inklusif)' : ''}:`;
    document.getElementById('summaryPpn').textContent = formatRupiah(ppnAmount);
    document.getElementById('summaryGrandTotal').textContent = formatRupiah(grandTotal);
}

// -------------------------------------------------------------
// SUBMIT: APPROVE & TERBITKAN PURCHASE ORDER
// -------------------------------------------------------------
async function handleApproveToPo(e) {
    e.preventDefault();

    const idVendor = document.getElementById('inputVendorId').value;
    if (!idVendor) {
        showToast('Vendor Rekanan belum ditentukan pada permohonan Request Order.', 'warning');
        return;
    }

    if (!confirm('Apakah Anda yakin ingin menyetujui Request Order ini dan menerbitkan Purchase Order resmi?')) {
        return;
    }

    const btn = document.getElementById('btnSubmitPo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses PO...';

    // Kumpulkan item barang dari tab pricing
    const items = [];
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const idBarang = parseInt(row.querySelector('.item-id-barang').value);
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const harga = parseFloat(row.querySelector('.item-harga').value) || 0;
        const diskon = parseFloat(row.querySelector('.item-diskon').value) || 0;
        items.push({
            id_barang: idBarang,
            qty: qty,
            harga: harga,
            diskon: diskon,
            kena_pajak: document.getElementById('checkEnablePajak').checked ? 1 : 0
        });
    });

    const payload = {
        action: 'approve',
        id_request: ID_REQUEST,
        nomor_po: document.getElementById('inputNomorPo').value.trim(),
        tanggal_po: document.getElementById('inputTanggalPo').value,
        prioritas: document.getElementById('inputPrioritas').value,
        id_vendor: parseInt(idVendor),
        term_of_payment: parseInt(document.getElementById('inputTop').value) || 0,
        pengiriman: document.getElementById('selectPengiriman').value,
        tanggal_pengiriman: document.getElementById('inputTanggalPengiriman').value || null,
        alamat: document.getElementById('inputAlamatPengiriman').value.trim(),
        pajak: parseInt(document.getElementById('inputPajakPpn').value) || 0,
        total_termasuk_pajak: document.getElementById('checkTermasukPajak').checked ? 1 : 0,
        diskon: calculatedDiskonNominal || 0,
        keterangan: document.getElementById('inputKeteranganPo').value.trim(),
        items: items
    };

    const res = await apiRequest('/api/purchase_order/process_ro.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Setujui &amp; Terbitkan Purchase Order';

    if (res && res.success) {
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/request_order/index.php`;
        }, 1500);
    } else {
        showToast(res ? res.message : 'Gagal memproses Purchase Order.', 'error');
    }
}

// -------------------------------------------------------------
// SUBMIT: SIMPAN SEBAGAI DRAFT (NOMOR DRF-PO-YYMM-01)
// -------------------------------------------------------------
async function handleSaveDraftPo() {
    const idVendor = document.getElementById('inputVendorId').value;
    if (!idVendor) {
        showToast('Vendor Rekanan belum ditentukan pada permohonan Request Order.', 'warning');
        return;
    }

    const btn = document.getElementById('btnSaveDraftPo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan Draft...';

    // 1. Ambil nomor draft PO (DRF-PO-YYMM-01)
    let draftNomor = document.getElementById('inputNomorPo').value.trim();
    if (!draftNomor.startsWith('DRF-PO-')) {
        const resDraftNum = await apiRequest('/api/purchase_order/get_next_number.php?draft=1');
        if (resDraftNum && resDraftNum.success && resDraftNum.data && resDraftNum.data.nomor_po) {
            draftNomor = resDraftNum.data.nomor_po;
            document.getElementById('inputNomorPo').value = draftNomor;
        }
    }

    // 2. Kumpulkan item barang dari tab pricing
    const items = [];
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const idBarang = parseInt(row.querySelector('.item-id-barang').value);
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const harga = parseFloat(row.querySelector('.item-harga').value) || 0;
        const diskon = parseFloat(row.querySelector('.item-diskon').value) || 0;
        items.push({
            id_barang: idBarang,
            qty: qty,
            harga: harga,
            diskon: diskon,
            kena_pajak: document.getElementById('checkEnablePajak').checked ? 1 : 0
        });
    });

    const payload = {
        action: 'draft',
        id_request: ID_REQUEST,
        nomor_po: draftNomor,
        tanggal_po: document.getElementById('inputTanggalPo').value,
        prioritas: document.getElementById('inputPrioritas').value,
        id_vendor: parseInt(idVendor),
        term_of_payment: parseInt(document.getElementById('inputTop').value) || 0,
        pengiriman: document.getElementById('selectPengiriman').value,
        tanggal_pengiriman: document.getElementById('inputTanggalPengiriman').value || null,
        alamat: document.getElementById('inputAlamatPengiriman').value.trim(),
        pajak: parseInt(document.getElementById('inputPajakPpn').value) || 0,
        total_termasuk_pajak: document.getElementById('checkTermasukPajak').checked ? 1 : 0,
        diskon: calculatedDiskonNominal || 0,
        keterangan: document.getElementById('inputKeteranganPo').value.trim(),
        items: items
    };

    const res = await apiRequest('/api/purchase_order/process_ro.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-file-earmark-diff me-1"></i> Simpan sebagai Draft';

    if (res && res.success) {
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/request_order/index.php`;
        }, 1500);
    } else {
        showToast(res ? res.message : 'Gagal menyimpan draft Purchase Order.', 'error');
    }
}

// -------------------------------------------------------------
// SUBMIT: TOLAK RO (TIDAK DISETUJUI)
// -------------------------------------------------------------
function openRejectModal() {
    document.getElementById('rejectAlasanText').value = '';
    modalRejectInstance.show();
}

async function submitRejectRo() {
    const alasan = document.getElementById('rejectAlasanText').value.trim();
    if (!alasan) {
        showToast('Wajib memasukkan alasan penolakan.', 'warning');
        document.getElementById('rejectAlasanText').focus();
        return;
    }

    const btn = document.getElementById('btnConfirmReject');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menolak...';

    const res = await apiRequest('/api/purchase_order/process_ro.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'reject',
            id_request: ID_REQUEST,
            alasan: alasan
        })
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Konfirmasi Tolak';

    if (res && res.success) {
        modalRejectInstance.hide();
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/request_order/index.php`;
        }, 1200);
    } else {
        showToast(res ? res.message : 'Gagal menolak Request Order.', 'error');
    }
}

// -------------------------------------------------------------
// SUBMIT: BATALKAN RO
// -------------------------------------------------------------
function openCancelModal() {
    document.getElementById('cancelAlasanText').value = '';
    modalCancelInstance.show();
}

async function submitCancelRo() {
    const alasan = document.getElementById('cancelAlasanText').value.trim();
    if (!alasan) {
        showToast('Wajib memasukkan alasan pembatalan.', 'warning');
        document.getElementById('cancelAlasanText').focus();
        return;
    }

    const btn = document.getElementById('btnConfirmCancel');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Membatalkan...';

    const res = await apiRequest('/api/purchase_order/process_ro.php', {
        method: 'POST',
        body: JSON.stringify({
            action: 'cancel',
            id_request: ID_REQUEST,
            alasan: alasan
        })
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-slash-circle me-1"></i> Konfirmasi Pembatalan';

    if (res && res.success) {
        modalCancelInstance.hide();
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/request_order/index.php`;
        }, 1200);
    } else {
        showToast(res ? res.message : 'Gagal membatalkan Request Order.', 'error');
    }
}

// -------------------------------------------------------------
// HELPER FORMAT RUPIAH
// -------------------------------------------------------------
function formatRupiah(amount) {
    return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
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
