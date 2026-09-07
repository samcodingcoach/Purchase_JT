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
$pageHeading = 'Verifikasi & Proses Purchase Order';

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
        <h4 class="fw-bold text-dark mb-0">Proses ke Purchase Order (PO)</h4>
    </div>

    <!-- SKELETON LOADING -->
    <div id="loadingWrapper" class="text-center py-5">
        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
        <h6 class="text-muted fw-semibold">Memuat formulir Purchase Order...</h6>
    </div>

    <!-- MAIN FORM WRAPPER (1 KOLOM PENUH DENGAN 3 TAB FUNGSIONAL) -->
    <div id="mainContentWrapper" class="d-none">
        
        <!-- BANNER DOKUMEN DIKUNCI / SUDAH TERBIT PO -->
        <div id="roLockedBannerContainer" class="d-none"></div>

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
                                    <h6 class="fw-bold mb-0 text-dark">Rincian Barang &amp; Penetapan Harga Satuan</h6>
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
                                                        <input type="text" class="form-control font-monospace text-end" id="inputDiskonNilai" value="0" oninput="handleDiskonNilaiInput(this)">
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
                <div class="card-footer bg-light p-3 border-top d-flex justify-content-end align-items-center flex-wrap gap-2" id="poFooterActions">
                    <!-- Tombol Tolak -->
                    <button type="button" class="btn btn-outline-danger btn-sm px-3 fw-semibold" id="btnRejectPo" onclick="openRejectModal()">
                        <i class="bi bi-x-circle me-1"></i> Tolak (Tidak Disetujui)
                    </button>
                    <!-- Tombol Simpan sebagai Draft -->
                    <button type="button" class="btn btn-warning btn-sm px-3 fw-semibold text-dark shadow-sm" id="btnSaveDraftPo" onclick="handleSaveDraftPo()">
                        <i class="bi bi-file-earmark-diff me-1"></i> Simpan sebagai Draft
                    </button>
                    <!-- Tombol Setujui & Terbitkan PO -->
                    <button type="submit" id="btnSubmitPo" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Setujui &amp; Terbitkan Purchase Order
                    </button>
                    <!-- Tombol Print Request Order yang Sudah Disetujui (Muncul saat RO sudah terbit PO / selesai) -->
                    <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm d-none" id="btnPrintApprovedRo" onclick="printApprovedRo()">
                        <i class="bi bi-printer-fill me-1"></i> Print Request Order (Disetujui)
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
     MODAL VERIFIKASI & OTORISASI PENERBITAN PURCHASE ORDER
     ============================================================= -->
<div class="modal fade" id="modalVerifyPo" tabindex="-1" aria-labelledby="modalVerifyPoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white py-3 px-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-shield-check fs-4"></i>
                    <div>
                        <h5 class="modal-title fs-6 fw-bold mb-0" id="modalVerifyPoLabel">
                            Verifikasi &amp; Konfirmasi Penerbitan Purchase Order
                        </h5>
                        <div class="small opacity-75">Periksa dan centang 6 poin parameter transaksi sebelum menerbitkan PO resmi</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light">
                
                <!-- Ringkasan Dokumen & Nilai Transaksi -->
                <div class="card border-0 shadow-xs rounded-3 p-3 bg-white mb-3">
                    <div class="row g-2 align-items-center">
                        <div class="col-sm-6">
                            <span class="text-muted small d-block">Nomor Purchase Order (PO):</span>
                            <strong class="text-dark font-monospace fs-6" id="verifyPoNumberDisplay">PO-XXXX-XXXX</strong>
                            <div class="text-muted small mt-1">Vendor: <span class="fw-semibold text-dark" id="verifyVendorNameDisplay">-</span></div>
                        </div>
                        <div class="col-sm-6 text-sm-end">
                            <span class="text-muted small d-block">Grand Total Transaksi:</span>
                            <strong class="text-success font-monospace fs-5" id="verifyGrandTotalDisplay">Rp 0</strong>
                        </div>
                    </div>
                </div>

                <!-- 6 CHECKLIST VERIFIKASI PARAMETER -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark small mb-0">
                        <i class="bi bi-card-checklist text-primary me-1"></i> Checklist Verifikasi Wajib (6 Poin) <span class="text-danger">*</span>
                    </h6>
                    <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 small" style="font-size: 0.75rem;" onclick="toggleCheckAllVerify(true)">Centang Semua</button>
                </div>

                <div class="d-flex flex-column gap-2 mb-3">
                    
                    <!-- 1. T.O.P (Term of Payment) -->
                    <div class="card border p-2 bg-white shadow-xs">
                        <div class="form-check m-0 d-flex align-items-start gap-2">
                            <input class="form-check-input verify-check-item mt-1" type="checkbox" id="checkVerifyTop" onchange="checkVerifyCompleteness()">
                            <label class="form-check-label w-100 cursor-pointer" for="checkVerifyTop">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <strong class="text-dark small">1. Term of Payment (T.O.P)</strong>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" id="verifyValTop">0 Hari</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">Jangka waktu dan termin pembayaran ke vendor sudah tepat sesuai kesepakatan.</div>
                            </label>
                        </div>
                    </div>

                    <!-- 2. Metode Pengiriman -->
                    <div class="card border p-2 bg-white shadow-xs">
                        <div class="form-check m-0 d-flex align-items-start gap-2">
                            <input class="form-check-input verify-check-item mt-1" type="checkbox" id="checkVerifyPengiriman" onchange="checkVerifyCompleteness()">
                            <label class="form-check-label w-100 cursor-pointer" for="checkVerifyPengiriman">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <strong class="text-dark small">2. Metode Pengiriman</strong>
                                    <span class="badge bg-secondary-subtle text-secondary border font-monospace" id="verifyValPengiriman">Vendor</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">Metode logistik pengiriman barang (Vendor/Expedisi/Internal) dan alamat site tujuan sudah benar.</div>
                            </label>
                        </div>
                    </div>

                    <!-- 3. Estimasi Tanggal Pengiriman -->
                    <div class="card border p-2 bg-white shadow-xs">
                        <div class="form-check m-0 d-flex align-items-start gap-2">
                            <input class="form-check-input verify-check-item mt-1" type="checkbox" id="checkVerifyTanggalPengiriman" onchange="checkVerifyCompleteness()">
                            <label class="form-check-label w-100 cursor-pointer" for="checkVerifyTanggalPengiriman">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <strong class="text-dark small">3. Estimasi Tanggal Pengiriman oleh Vendor</strong>
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle font-monospace" id="verifyValTanggalPengiriman">-</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">Estimasi kesanggupan tanggal pengiriman oleh vendor telah dikonfirmasi dan sesuai jadwal operasional.</div>
                            </label>
                        </div>
                    </div>

                    <!-- 4. Total QTY -->
                    <div class="card border p-2 bg-white shadow-xs">
                        <div class="form-check m-0 d-flex align-items-start gap-2">
                            <input class="form-check-input verify-check-item mt-1" type="checkbox" id="checkVerifyTotalQty" onchange="checkVerifyCompleteness()">
                            <label class="form-check-label w-100 cursor-pointer" for="checkVerifyTotalQty">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <strong class="text-dark small">4. Total QTY (Kuantitas &amp; Rincian Barang)</strong>
                                    <span class="badge bg-dark-subtle text-dark border font-monospace" id="verifyValTotalQty">0 Qty (0 Item)</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">Kuantitas seluruh baris barang, satuan (PCS/UNIT), serta harga satuan dan diskon telah dihitung akurat.</div>
                            </label>
                        </div>
                    </div>

                    <!-- 5. Kena Pajak (PPN) -->
                    <div class="card border p-2 bg-white shadow-xs">
                        <div class="form-check m-0 d-flex align-items-start gap-2">
                            <input class="form-check-input verify-check-item mt-1" type="checkbox" id="checkVerifyKenaPajak" onchange="checkVerifyCompleteness()">
                            <label class="form-check-label w-100 cursor-pointer" for="checkVerifyKenaPajak">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <strong class="text-dark small">5. Status Kena Pajak (PPN)</strong>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle font-monospace" id="verifyValKenaPajak">PPN (12%)</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">Status pengenaan Pajak Pertambahan Nilai (PPN) dan tarif pajak per item telah diverifikasi.</div>
                            </label>
                        </div>
                    </div>

                    <!-- 6. Total Termasuk Pajak -->
                    <div class="card border p-2 bg-white shadow-xs">
                        <div class="form-check m-0 d-flex align-items-start gap-2">
                            <input class="form-check-input verify-check-item mt-1" type="checkbox" id="checkVerifyTermasukPajak" onchange="checkVerifyCompleteness()">
                            <label class="form-check-label w-100 cursor-pointer" for="checkVerifyTermasukPajak">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                    <strong class="text-dark small">6. Total Termasuk Pajak (Include / Exclude)</strong>
                                    <span class="badge bg-light text-dark border font-monospace" id="verifyValTermasukPajak">Belum Termasuk Pajak</span>
                                </div>
                                <div class="text-muted" style="font-size: 0.78rem;">Skema perhitungan DPP, Pajak, dan Grand Total (apakah harga include atau exclude pajak) sudah tepat.</div>
                            </label>
                        </div>
                    </div>

                </div>

                <!-- INPUT PASSWORD KONFIRMASI OTORISASI -->
                <div class="card border-0 shadow-xs rounded-3 p-3 bg-white">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="bi bi-key-fill text-warning me-1"></i> Input Password Akun Anda untuk Otorisasi <span class="text-danger">*</span>
                    </label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light"><i class="bi bi-lock-fill text-muted"></i></span>
                        <input type="password" class="form-control" id="inputVerifyPassword" 
                               placeholder="Masukkan password login Anda untuk konfirmasi penerbitan..." 
                               autocomplete="current-password" 
                               oninput="this.classList.remove('is-invalid'); checkVerifyCompleteness();" 
                               onkeydown="if(event.key === 'Enter') { event.preventDefault(); if(!document.getElementById('btnFinalSubmitPo').disabled) submitFinalApprovedPo(); }"
                               required>
                        <button class="btn btn-outline-secondary" type="button" onclick="toggleVerifyPasswordVisibility()" title="Lihat/Sembunyikan Password">
                            <i class="bi bi-eye" id="toggleVerifyEyeIcon"></i>
                        </button>
                    </div>
                    <div class="invalid-feedback d-block text-danger small mt-1 d-none" id="verifyPasswordErrorText">
                        <i class="bi bi-exclamation-circle me-1"></i>Password otorisasi salah. Silakan coba lagi.
                    </div>
                    <div class="form-text text-muted" style="font-size: 0.78rem;">
                        Penerbitan PO adalah dokumen legal sah. Masukkan password akun login Anda sebagai verifikasi identitas resmi.
                    </div>
                </div>

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-white py-3 px-4 border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i> Batal
                </button>
                <button type="button" class="btn btn-success btn-sm px-4 fw-bold shadow-sm" id="btnFinalSubmitPo" onclick="submitFinalApprovedPo()" disabled>
                    <i class="bi bi-check2-circle me-1"></i> Konfirmasi &amp; Terbitkan PO Sekarang
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
let modalVerifyPoInstance = null;
let calculatedDiskonNominal = 0;

document.addEventListener('DOMContentLoaded', async () => {
    modalRejectInstance = new bootstrap.Modal(document.getElementById('modalRejectRo'));
    modalVerifyPoInstance = new bootstrap.Modal(document.getElementById('modalVerifyPo'));

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

// -------------------------------------------------------------
// HELPER THOUSAND SEPARATOR & NUMERIC PARSING
// -------------------------------------------------------------
function parseThousandNumber(val) {
    if (typeof val === 'number') return Math.max(0, val);
    if (!val) return 0;
    const clean = String(val).replace(/\./g, '').replace(/,/g, '.').replace(/[^0-9.]/g, '');
    const num = parseFloat(clean);
    return isNaN(num) || num < 0 ? 0 : num;
}

function formatThousand(val) {
    const num = Math.round(parseThousandNumber(val));
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function handleThousandInput(el, onComplete) {
    let cursorPos = el.selectionStart;
    let originalLen = el.value.length;

    // Bersihkan karakter non-angka (sekaligus cegah tanda minus)
    let cleanVal = el.value.replace(/[^0-9]/g, '');
    let num = parseInt(cleanVal, 10);
    if (isNaN(num) || num < 0) num = 0;

    el.value = num > 0 ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '0';

    // Sesuaikan posisi kursor agar tetap natural saat mengetik
    let newLen = el.value.length;
    cursorPos = cursorPos + (newLen - originalLen);
    if (cursorPos < 0) cursorPos = 0;
    try { el.setSelectionRange(cursorPos, cursorPos); } catch(e){}

    if (typeof onComplete === 'function') {
        onComplete();
    }
}

function handlePriceInput(el) {
    handleThousandInput(el, () => calculateRowSubtotal(el));
}

function handleDiscountInput(el) {
    handleThousandInput(el, () => calculateRowSubtotal(el));
}

function handleDiskonNilaiInput(el) {
    const diskonType = document.getElementById('selectDiskonType').value;
    if (diskonType === 'percent') {
        let cleanVal = el.value.replace(/[^0-9.]/g, '');
        let num = parseFloat(cleanVal);
        if (isNaN(num) || num < 0) num = 0;
        if (num > 100) num = 100;
        el.value = num;
        calculateAllTotals();
    } else {
        handleThousandInput(el, () => calculateAllTotals());
    }
}

function onDiskonTypeChange() {
    const type = document.getElementById('selectDiskonType').value;
    const prefix = document.getElementById('addonDiskonPrefix');
    const suffix = document.getElementById('addonDiskonSuffix');
    const label = document.getElementById('labelNilaiDiskon');
    const input = document.getElementById('inputDiskonNilai');

    if (type === 'percent') {
        prefix.classList.add('d-none');
        suffix.classList.remove('d-none');
        label.textContent = 'Nilai Diskon (%):';
        input.value = '0';
    } else {
        prefix.classList.remove('d-none');
        suffix.classList.add('d-none');
        label.textContent = 'Nilai Diskon (Rp):';
        input.value = '0';
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

    // 4. Inisialisasi T.O.P default dari vendor.term_of_payment (namun tetap dapat diubah)
    const inputTopEl = document.getElementById('inputTop');
    if (inputTopEl) {
        if (typeof ro.vendor_term_of_payment !== 'undefined' && ro.vendor_term_of_payment !== null && ro.vendor_term_of_payment !== '') {
            inputTopEl.value = parseInt(ro.vendor_term_of_payment);
        } else {
            inputTopEl.value = 30;
        }
        updateTopKeterangan(inputTopEl.value);
    }

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
                        <input type="text" class="form-control form-control-sm font-monospace text-end item-harga" value="${formatThousand(defaultHarga)}" oninput="handlePriceInput(this)" placeholder="0" required>
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm font-monospace text-end item-diskon" value="0" oninput="handleDiscountInput(this)" placeholder="0">
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

    // Cek apakah RO sudah selesai diproses ke PO atau berstatus akhir
    const isPoApproved = (ro.status === 'DISETUJUI PURCHASING' || (ro.id_po && parseInt(ro.id_po) > 0));
    const isLocked = isPoApproved || ['TIDAK DISETUJUI PURCHASING', 'BATAL'].includes(ro.status);

    if (isLocked) {
        // Nonaktifkan semua input formulir
        document.querySelectorAll('#formProsesPo input, #formProsesPo textarea, #formProsesPo select, #formProsesPo button').forEach(el => {
            if (el.id !== 'btnPrintApprovedRo') {
                el.disabled = true;
            }
        });

        // Sembunyikan semua tombol aksi proses PO
        const btnReject = document.getElementById('btnRejectPo');
        const btnCancel = document.getElementById('btnCancelPo');
        const btnDraft = document.getElementById('btnSaveDraftPo');
        const btnSubmit = document.getElementById('btnSubmitPo');

        if (btnReject) btnReject.classList.add('d-none');
        if (btnCancel) btnCancel.classList.add('d-none');
        if (btnDraft) btnDraft.classList.add('d-none');
        if (btnSubmit) btnSubmit.classList.add('d-none');

        // Tampilkan tombol Print RO Disetujui
        const btnPrint = document.getElementById('btnPrintApprovedRo');
        if (btnPrint) btnPrint.classList.remove('d-none');

        // Tampilkan Banner Terkunci
        const bannerContainer = document.getElementById('roLockedBannerContainer');
        if (bannerContainer) {
            bannerContainer.classList.remove('d-none');
            if (isPoApproved) {
                bannerContainer.innerHTML = `
                    <div class="alert alert-info py-2 px-3 small mb-3 border-0 rounded-3 shadow-xs d-flex align-items-center">
                        <i class="bi bi-info-circle-fill me-2 text-primary"></i>
                        <span>PO telah terbit untuk RO <strong>${escapeHtml(ro.nomor || '')}</strong>. Dokumen ini bersifat <em>Read-Only</em>.</span>
                    </div>
                `;
            } else {
                bannerContainer.innerHTML = `
                    <div class="alert alert-secondary py-2 px-3 small mb-3 border-0 rounded-3 shadow-xs d-flex align-items-center">
                        <i class="bi bi-lock-fill me-2 text-secondary"></i>
                        <span>Status RO: <strong>${escapeHtml(ro.status || '')}</strong>. Dokumen ini bersifat <em>Read-Only</em>.</span>
                    </div>
                `;
            }
        }
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
    const harga = parseThousandNumber(row.querySelector('.item-harga').value);
    const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);

    let subtotal = (qty * harga) - diskon;
    if (subtotal < 0) subtotal = 0;

    row.querySelector('.item-subtotal-display').textContent = formatRupiah(subtotal);
    calculateAllTotals();
}

function calculateAllTotals() {
    let subtotalBarang = 0;
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga').value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);
        let rowSubtotal = (qty * harga) - diskon;
        if (rowSubtotal < 0) rowSubtotal = 0;
        subtotalBarang += rowSubtotal;
    });

    // Kalkulasi Diskon Akhir (Nominal vs Persentase)
    const diskonType = document.getElementById('selectDiskonType').value;
    const diskonRaw = document.getElementById('inputDiskonNilai').value;
    const diskonInput = (diskonType === 'percent') ? (parseFloat(diskonRaw) || 0) : parseThousandNumber(diskonRaw);
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
// 1. POPUP VERIFIKASI SEBELUM TERBIT PO (6 CHECKLIST + PASSWORD)
// -------------------------------------------------------------
function handleApproveToPo(e) {
    if (e) e.preventDefault();

    const idVendor = document.getElementById('inputVendorId').value;
    if (!idVendor) {
        showToast('Vendor belum ditentukan pada permohonan Request Order.', 'warning');
        return;
    }

    const nomorPo = document.getElementById('inputNomorPo').value.trim();
    if (!nomorPo) {
        showToast('Nomor Purchase Order wajib diisi.', 'warning');
        return;
    }

    // Kumpulkan item barang dari tab pricing untuk verifikasi kuantitas
    let totalQtyCount = 0;
    let itemCount = 0;
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        totalQtyCount += qty;
        itemCount++;
    });

    if (itemCount === 0) {
        showToast('Rincian barang permohonan tidak ditemukan.', 'warning');
        return;
    }

    // 1. Ekstrak Nilai untuk 6 Poin Checklist
    const topVal = parseInt(document.getElementById('inputTop')?.value) || 0;
    const pengirimanVal = document.getElementById('selectPengiriman')?.value || 'Vendor';
    const tanggalKirimVal = document.getElementById('inputTanggalPengiriman')?.value || '';
    const isKenaPajak = document.getElementById('checkEnablePajak')?.checked || false;
    const pajakRate = parseInt(document.getElementById('inputPajakPpn')?.value) || 0;
    const isTermasukPajak = document.getElementById('checkTermasukPajak')?.checked || false;

    // 2. Isi Ringkasan Modal
    if (document.getElementById('verifyPoNumberDisplay')) document.getElementById('verifyPoNumberDisplay').textContent = nomorPo;
    if (document.getElementById('verifyVendorNameDisplay')) {
        document.getElementById('verifyVendorNameDisplay').textContent = document.getElementById('displayVendorName')?.value || 'Vendor Rekanan';
    }
    if (document.getElementById('verifyGrandTotalDisplay')) {
        document.getElementById('verifyGrandTotalDisplay').textContent = document.getElementById('summaryGrandTotal')?.textContent || 'Rp 0';
    }

    // 3. Isi Nilai 6 Poin Checklist
    if (document.getElementById('verifyValTop')) document.getElementById('verifyValTop').textContent = topVal === 0 ? '0 Hari (C.O.D / Tunai)' : `${topVal} Hari`;
    if (document.getElementById('verifyValPengiriman')) document.getElementById('verifyValPengiriman').textContent = pengirimanVal;
    if (document.getElementById('verifyValTanggalPengiriman')) document.getElementById('verifyValTanggalPengiriman').textContent = tanggalKirimVal ? tanggalKirimVal : 'Sesuai Jadwal Standar';
    if (document.getElementById('verifyValTotalQty')) document.getElementById('verifyValTotalQty').textContent = `${totalQtyCount} Qty (${itemCount} Item Barang)`;
    if (document.getElementById('verifyValKenaPajak')) document.getElementById('verifyValKenaPajak').textContent = isKenaPajak ? `Kena Pajak PPN (${pajakRate}%)` : 'Bebas Pajak (Non-PPN)';
    if (document.getElementById('verifyValTermasukPajak')) document.getElementById('verifyValTermasukPajak').textContent = isTermasukPajak ? 'Sudah Termasuk Pajak (Inklusif)' : 'Belum Termasuk Pajak (Eksklusif)';

    // 4. Reset Checkbox & Input Password
    document.querySelectorAll('.verify-check-item').forEach(cb => cb.checked = false);
    const pwInput = document.getElementById('inputVerifyPassword');
    if (pwInput) {
        pwInput.value = '';
        pwInput.classList.remove('is-invalid');
    }
    const errText = document.getElementById('verifyPasswordErrorText');
    if (errText) {
        errText.classList.add('d-none');
    }
    document.getElementById('btnFinalSubmitPo').disabled = true;

    // 5. Buka Modal Verifikasi
    modalVerifyPoInstance.show();
}

function checkVerifyCompleteness() {
    const checkboxes = document.querySelectorAll('.verify-check-item');
    let allChecked = true;
    checkboxes.forEach(cb => {
        if (!cb.checked) allChecked = false;
    });

    const passwordVal = document.getElementById('inputVerifyPassword').value.trim();
    const hasPassword = passwordVal.length > 0;

    const btn = document.getElementById('btnFinalSubmitPo');
    if (btn) {
        btn.disabled = !(allChecked && hasPassword);
    }
}

function toggleCheckAllVerify(checkAll) {
    document.querySelectorAll('.verify-check-item').forEach(cb => {
        cb.checked = checkAll;
    });
    checkVerifyCompleteness();
}

function toggleVerifyPasswordVisibility() {
    const input = document.getElementById('inputVerifyPassword');
    const icon = document.getElementById('toggleVerifyEyeIcon');
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}

// -------------------------------------------------------------
// 2. SUBMIT FINAL PO SETELAH OTORISASI PASSWORD
// -------------------------------------------------------------
async function submitFinalApprovedPo() {
    const passwordVal = document.getElementById('inputVerifyPassword').value.trim();
    if (!passwordVal) {
        showToast('Password otorisasi wajib dimasukkan.', 'warning');
        document.getElementById('inputVerifyPassword').focus();
        return;
    }

    const checkboxes = document.querySelectorAll('.verify-check-item');
    for (let cb of checkboxes) {
        if (!cb.checked) {
            showToast('Semua 6 poin parameter checklist wajib diverifikasi dan dicentang.', 'warning');
            return;
        }
    }

    const btn = document.getElementById('btnFinalSubmitPo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memverifikasi &amp; Menerbitkan PO...';

    // Kumpulkan item barang dari tab pricing
    const items = [];
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const idBarang = parseInt(row.querySelector('.item-id-barang').value);
        const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga').value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);
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
        confirm_password: passwordVal,
        nomor_po: document.getElementById('inputNomorPo').value.trim(),
        tanggal_po: document.getElementById('inputTanggalPo').value,
        prioritas: document.getElementById('inputPrioritas').value,
        id_vendor: parseInt(document.getElementById('inputVendorId').value),
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
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Konfirmasi &amp; Terbitkan PO Sekarang';

    if (res && res.success) {
        modalVerifyPoInstance.hide();
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/request_order/index.php`;
        }, 1500);
    } else {
        const errorMsg = res && res.message ? res.message : 'Gagal memproses Purchase Order.';
        showToast(errorMsg, 'error');
        const pwInput = document.getElementById('inputVerifyPassword');
        const errText = document.getElementById('verifyPasswordErrorText');
        if (pwInput) {
            pwInput.classList.add('is-invalid');
            pwInput.focus();
            pwInput.select();
        }
        if (errText) {
            errText.innerHTML = `<i class="bi bi-exclamation-circle me-1"></i>${escapeHtml(errorMsg)}`;
            errText.classList.remove('d-none');
        }
    }
}

// -------------------------------------------------------------
// SUBMIT: SIMPAN SEBAGAI DRAFT (NOMOR DRF-PO-YYMM-01)
// -------------------------------------------------------------
async function handleSaveDraftPo() {
    const idVendor = document.getElementById('inputVendorId').value;
    if (!idVendor) {
        showToast('Vendor belum ditentukan pada permohonan Request Order.', 'warning');
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
        const harga = parseThousandNumber(row.querySelector('.item-harga').value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);
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

// -------------------------------------------------------------
// PRINT REQUEST ORDER YANG SUDAH DISETUJUI
// -------------------------------------------------------------
function printApprovedRo() {
    // Fungsi print akan diimplementasikan nanti sesuai kebutuhan
}
</script>
