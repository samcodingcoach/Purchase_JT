<?php
/**
 * Halaman Formulir Catat Pembayaran Faktur PO (Payment Purchase)
 * Path: admin/pages/pembayaran_po/create.php
 * Khusus Role: FINANCE, PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

// Auth Protection: Khusus Finance, Admin, dan Manager
$user = requireAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Catat Pembayaran PO';
$pageHeading = 'Formulir Pembayaran Faktur Pembelian';

// Ambil parameter id_faktur jika dibuka langsung dari faktur detail
$preselectedIdFaktur = isset($_GET['id_faktur']) ? intval($_GET['id_faktur']) : 0;

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
/* Standardize Height of all form inputs & controls */
.form-control,
.form-control-sm,
.form-select,
.form-select-sm,
.input-group > .form-control,
.input-group > .btn,
.input-group > .input-group-text,
.input-group-sm > .form-control,
.input-group-sm > .btn,
.input-group-sm > .input-group-text,
.custom-select-trigger {
    height: 38px !important;
    min-height: 38px !important;
    font-size: 0.875rem !important;
}

textarea.form-control {
    height: auto !important;
    min-height: 100px !important;
}

.faktur-opt-item {
    transition: background-color 0.15s ease;
    cursor: pointer !important;
}
.faktur-opt-item:hover {
    background-color: #f1f5f9 !important;
}
</style>

<div class="container-fluid px-0">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Catat Pembayaran Faktur PO</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" style="height: 38px; display: inline-flex; align-items: center;">
                Kembali ke Riwayat
            </a>
        </div>
    </div>

    <form id="formPayment" onsubmit="event.preventDefault(); submitPayment();">
        <input type="hidden" id="selectedIdFaktur" name="id_faktur" value="<?= $preselectedIdFaktur ?>" required>
        <input type="hidden" id="hiddenBuktiBase64" name="file_bukti_bayar_base64">

        <!-- CARD TAB MODULAR SESUAI KONTEKS & FUNGSI (5 TAB) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="paymentTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-3 px-3" id="tab-faktur-btn" data-bs-toggle="tab" data-bs-target="#tab-faktur" type="button" role="tab">
                            <i class="bi bi-receipt me-1 text-primary"></i> 1. Tagihan &amp; Faktur
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-nominal-btn" data-bs-toggle="tab" data-bs-target="#tab-nominal" type="button" role="tab">
                            <i class="bi bi-cash-coin me-1 text-primary"></i> 2. Rincian Pembayaran
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-vendor-rek-btn" data-bs-toggle="tab" data-bs-target="#tab-vendor-rek" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 3. Rekening Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-rekening-btn" data-bs-toggle="tab" data-bs-target="#tab-rekening" type="button" role="tab">
                            <i class="bi bi-bank me-1 text-primary"></i> 4. Kas &amp; Rekening Pengirim
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-approval-btn" data-bs-toggle="tab" data-bs-target="#tab-approval" type="button" role="tab">
                            <i class="bi bi-shield-check me-1 text-primary"></i> 5. Approval &amp; Bukti
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="paymentTabContent">
                    
                    <!-- TAB 1: TAGIHAN & FAKTUR -->
                    <div class="tab-pane fade show active" id="tab-faktur" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Pilih Dokumen Faktur Tagihan</h6>

                                <div class="mb-3 position-relative" id="fakturSelectWrapper">
                                    <label class="form-label small fw-semibold text-dark">
                                        Dokumen Faktur PO Belum Lunas <span class="text-danger">*</span>
                                    </label>

                                    <!-- Trigger Display Box -->
                                    <div class="custom-select-trigger d-flex align-items-center justify-content-between p-2 px-3 border rounded-3 bg-white cursor-pointer shadow-sm" id="fakturTriggerBox" onclick="toggleFakturDropdown(event)" style="cursor: pointer;">
                                        <div id="fakturSelectedDisplay" class="text-truncate me-2">
                                            <span class="text-muted">Cari Faktur PO (No. Faktur, Vendor, Invoice)...</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0 me-1" id="fakturClearBtn" onclick="clearFakturSelection(event)" style="display: none;" title="Hapus Pilihan">
                                                Hapus
                                            </button>
                                            <span class="text-muted small">&#9662;</span>
                                        </div>
                                    </div>

                                    <!-- Dropdown Menu -->
                                    <div class="faktur-dropdown-menu shadow-lg border rounded-3 p-2 bg-white" id="fakturDropdownMenu" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 1050;">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light text-muted">Cari</span>
                                            <input type="text" class="form-control form-control-sm" id="fakturSearchInput" placeholder="Ketik No. Faktur, Vendor, atau No. Invoice..." autocomplete="off" oninput="filterFakturList()">
                                        </div>
                                        <div class="overflow-auto" id="fakturOptionsContainer" style="max-height: 250px;">
                                            <!-- Populated dynamically by JS -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Status &amp; Nilai Tagihan</h6>

                                <div id="fakturSummaryContainer" class="p-3 bg-light rounded-3 border">
                                    <div class="row g-2 small mb-3">
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">No. Faktur Sistem:</span>
                                            <strong class="font-monospace text-primary" id="dispNomorFaktur">-</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">No. Invoice Vendor:</span>
                                            <strong class="font-monospace text-dark" id="dispNomorFakturVendor">-</strong>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Nama Vendor:</span>
                                            <span class="fw-bold text-dark" id="dispNamaVendor">-</span>
                                        </div>
                                        <div class="col-sm-6">
                                            <span class="text-muted d-block">Jatuh Tempo (TOP):</span>
                                            <strong class="text-danger" id="dispTanggalJatuhTempo">-</strong>
                                        </div>
                                    </div>

                                    <!-- KOTAK FINANSIAL -->
                                    <div class="row g-2 pt-3 border-top text-center">
                                        <div class="col-4">
                                            <div class="text-muted small">Total Tagihan</div>
                                            <div class="fw-bold font-monospace fs-6 text-dark" id="dispTotalTagihan">Rp 0</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted small">Sudah Dibayar</div>
                                            <div class="fw-bold font-monospace fs-6 text-success" id="dispTerbayar">Rp 0</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted small">Sisa Tagihan</div>
                                            <div class="fw-bold font-monospace fs-6 text-danger" id="dispSisaTagihan">Rp 0</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- RIWAYAT ANGSURAN SEBELUMNYA -->
                                <div id="fakturHistoryContainer" class="mt-3" style="display: none;">
                                    <div class="fw-semibold text-dark small mb-2">Riwayat Pembayaran Sebelumnya:</div>
                                    <div class="table-responsive border rounded-3 bg-white">
                                        <table class="table table-sm table-striped small mb-0">
                                            <thead class="table-light text-secondary">
                                                <tr>
                                                    <th>Kode</th>
                                                    <th>Tgl Bayar</th>
                                                    <th class="text-end">Nominal</th>
                                                    <th>Sisa Hutang</th>
                                                </tr>
                                            </thead>
                                            <tbody id="fakturHistoryTableBody">
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-nominal')">
                                Lanjut ke Rincian Pembayaran
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: RINCIAN PEMBAYARAN -->
                    <div class="tab-pane fade" id="tab-nominal" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Skema &amp; Waktu Pembayaran</h6>

                                <!-- SKEMA PEMBAYARAN -->
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Skema Pembayaran <span class="text-danger">*</span></label>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="jenis_pembayaran" id="jenisLunas" value="1" checked onchange="handleJenisPembayaranChange()">
                                            <label class="btn btn-outline-success w-100 p-2 text-start rounded-3" for="jenisLunas">
                                                <div class="fw-bold small">1x Bayar (Lunas)</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">Bayar penuh sisa tagihan</div>
                                            </label>
                                        </div>
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="jenis_pembayaran" id="jenisKredit" value="0" onchange="handleJenisPembayaranChange()">
                                            <label class="btn btn-outline-warning w-100 p-2 text-start rounded-3" for="jenisKredit">
                                                <div class="fw-bold small">Kredit / Sebagian</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">Angsuran (Maks. s/d TOP)</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Tanggal Pembayaran <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="tanggalBayar" name="tanggal_bayar" value="<?= date('Y-m-d') ?>" required onchange="validateDueDateLimit()">
                                    <div class="form-text small text-muted">Untuk pembayaran kredit/sebagian, tanggal bayar tidak boleh melewati tanggal jatuh tempo TOP Faktur.</div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Nominal &amp; Kalkulasi Finansial</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Nominal Pembayaran Transfer <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace fw-bold">Rp</span>
                                        <input type="text" class="form-control text-end font-monospace fw-bold fs-6 text-primary" id="nominalPengiriman" placeholder="0" required oninput="handleNominalInput(this)">
                                    </div>
                                    <div class="form-text small text-muted">Otomatis berpemisah ribuan saat diketik.</div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Biaya Admin Bank (Jika Ada)</label>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace">Rp</span>
                                        <input type="text" class="form-control text-end font-monospace" id="biayaAdmin" value="0" placeholder="0" oninput="handleBiayaAdminInput(this)">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Estimasi Sisa Tagihan Sesudah Transaksi</label>
                                    <input type="text" class="form-control font-monospace fw-bold bg-light" id="displaySisaSesudah" value="Rp 0" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-faktur')">
                                Kembali ke Tagihan &amp; Faktur
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-vendor-rek')">
                                Lanjut ke Rekening Vendor
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: REKENING VENDOR (TUJUAN TRANSFER) -->
                    <div class="tab-pane fade" id="tab-vendor-rek" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Rekening Vendor (Tujuan Transfer Pembayaran)</h6>
                                <div class="small text-muted">Secara default diambil dari data Faktur PO dan dapat dialihkan ke Rekening Master Vendor atau diubah manual.</div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="applyRekeningFromFaktur()" title="Gunakan rekening yang tercatat pada Faktur PO">
                                    <i class="bi bi-receipt me-1"></i> Rekening Faktur
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm px-3" onclick="applyRekeningFromMasterVendor()" title="Gunakan rekening default dari Master Vendor">
                                    <i class="bi bi-building me-1"></i> Rekening Master Vendor
                                </button>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Bank Tujuan Vendor (Combobox Persis Bank Asal) -->
                            <div class="col-sm-6 position-relative" id="bankTujuanWrapper">
                                <label class="form-label small fw-semibold text-dark">Bank Tujuan Vendor</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace fw-semibold" id="bankTujuan" name="bank_tujuan" placeholder="Pilih atau ketik bank..." autocomplete="off" onfocus="showBankTujuanDropdown()" oninput="filterBankTujuanDropdown()">
                                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split px-3" type="button" onclick="toggleBankTujuanDropdown(event)" title="Pilih Bank"></button>
                                </div>
                                <div class="dropdown-menu shadow-sm w-100 p-1" id="bankTujuanMenu" style="max-height: 220px; overflow-y: auto; display: none; position: absolute; top: calc(100% + 2px); left: 0; z-index: 1050;">
                                    <!-- Populated dynamically by updateBankTujuanOptions() -->
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Nomor Rekening Tujuan Vendor</label>
                                <input type="text" class="form-control font-monospace fw-bold text-primary" id="norekTujuan" name="norek_tujuan" placeholder="Nomor Rekening Vendor">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Atas Nama Rekening Tujuan</label>
                                <input type="text" class="form-control fw-semibold text-dark" id="anPengiriman" name="an_pengiriman" placeholder="Atas Nama Pemilik Rekening Vendor">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-nominal')">
                                Kembali ke Rincian Pembayaran
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-rekening')">
                                Lanjut ke Kas &amp; Rekening Pengirim
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: KAS & REKENING PENGIRIM -->
                    <div class="tab-pane fade" id="tab-rekening" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Rekening Asal Pengirim (Kas Perusahaan)</h6>
                        
                        <div class="row g-3">
                            <div class="col-sm-6 position-relative" id="bankPengirimWrapper">
                                <label class="form-label small fw-semibold text-dark">Bank Asal / Kas Pengirim <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace fw-semibold" id="bankPengirim" name="bank_pengirim" placeholder="Pilih atau ketik bank..." autocomplete="off" onfocus="showBankPengirimDropdown()" oninput="filterBankPengirimDropdown()" required>
                                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split px-3" type="button" onclick="toggleBankPengirimDropdown(event)" title="Pilih Bank"></button>
                                </div>
                                <div class="dropdown-menu shadow-sm w-100 p-1" id="bankPengirimMenu" style="max-height: 220px; overflow-y: auto; display: none; position: absolute; top: calc(100% + 2px); left: 0; z-index: 1050;">
                                    <button type="button" class="dropdown-item py-1 small rounded bank-p-opt" onclick="selectBankPengirim('BCA')">BCA</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-p-opt" onclick="selectBankPengirim('Bank Mandiri')">Bank Mandiri</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-p-opt" onclick="selectBankPengirim('BRI')">BRI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-p-opt" onclick="selectBankPengirim('BNI')">BNI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-p-opt" onclick="selectBankPengirim('CIMB Niaga')">CIMB Niaga</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-p-opt" onclick="selectBankPengirim('BSI')">BSI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-p-opt" onclick="selectBankPengirim('CASH')">CASH / TUNAI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-p-opt" onclick="selectBankPengirim('QRIS')">QRIS</button>
                                    <div id="noBankPengirimFound" class="text-muted small px-3 py-2 d-none">Gunakan nama bank yang diketik manual.</div>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Nomor Rekening Pengirim</label>
                                <input type="text" class="form-control font-monospace fw-bold" id="norekPengirim" name="norek_pengirim" placeholder="Nomor Rekening Kas / Tabungan">
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Atas Nama Rekening Pengirim</label>
                                <input type="text" class="form-control fw-semibold" id="anPengirim" name="an_pengirim" placeholder="Nama Pemilik Rekening / PT Jaya Teknis">
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">No. Referensi / Mutasi Bank</label>
                                <input type="text" class="form-control font-monospace" id="noRef" name="no_ref" placeholder="Contoh: TRF-2608-88129">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-vendor-rek')">
                                Kembali ke Rekening Vendor
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-approval')">
                                Lanjut ke Approval &amp; Bukti
                            </button>
                        </div>
                    </div>

                    <!-- TAB 5: APPROVAL & BUKTI -->
                    <div class="tab-pane fade" id="tab-approval" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Persetujuan Lisan &amp; Lampiran Bukti Transfer</h6>

                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Disetujui Oleh (Lisan) <span class="text-danger">*</span></label>
                                <select class="form-select" id="selectApprover" name="id_karyawan_approved" required>
                                    <option value="">-- Pilih Pejabat / Finance --</option>
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Upload Bukti Transfer Bank (PDF/Foto)</label>
                                <input type="file" class="form-control" id="fileBuktiBayar" accept="image/*,application/pdf" onchange="handleProofUpload(this)">
                                <div class="form-text small text-muted" id="buktiFileInfo">Format: PDF, JPG, JPEG, PNG (Maks. 5MB)</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Catatan / Keterangan Pembayaran</label>
                                <textarea class="form-control" id="keteranganPayment" name="keterangan" rows="3" placeholder="Catatan opsional mengenai pembayaran ini..."></textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-rekening')">
                                Kembali ke Kas &amp; Rekening Pengirim
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSubmitPayment" style="height: 38px; display: inline-flex; align-items: center;">
                                <i class="bi bi-check-circle-fill me-1"></i> Simpan Transaksi Pembayaran
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<script>
let fakturList = [];
let approverList = [];
let currentSelectedFaktur = null;

document.addEventListener('DOMContentLoaded', () => {
    loadLookupData();

    document.addEventListener('click', (e) => {
        const fakturWrapper = document.getElementById('fakturSelectWrapper');
        if (fakturWrapper && !fakturWrapper.contains(e.target)) {
            hideFakturDropdown();
        }

        const bankWrapper = document.getElementById('bankPengirimWrapper');
        if (bankWrapper && !bankWrapper.contains(e.target)) {
            hideBankPengirimDropdown();
        }
    });
});

function goToTab(tabId) {
    const btn = document.querySelector(`[data-bs-target="#${tabId}"]`);
    if (btn) {
        const tab = new bootstrap.Tab(btn);
        tab.show();
    }
}

async function loadLookupData() {
    try {
        const res = await fetch('<?= BASE_URL ?>/api/pembayaran_po/lookup_faktur.php');
        const result = await res.json();

        if (result && result.success && result.data) {
            fakturList = result.data.faktur_list || [];
            approverList = result.data.approvers || [];

            renderFakturOptions(fakturList);
            renderApproverOptions(approverList);
            updateBankTujuanOptions('');

            // Cek jika ada preselected id_faktur dari query parameter
            const preselectedId = parseInt(document.getElementById('selectedIdFaktur').value);
            if (preselectedId > 0) {
                selectFaktur(preselectedId);
            }
        }
    } catch (e) {
        console.error('Gagal memuat data lookup faktur:', e);
    }
}

function renderApproverOptions(approvers) {
    const sel = document.getElementById('selectApprover');
    sel.innerHTML = '<option value="">-- Pilih Pejabat / Finance --</option>';
    approvers.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id_karyawan;
        opt.textContent = `${a.nama_karyawan} (${a.nama_jabatan || a.nama_divisi || 'Staff'})`;
        sel.appendChild(opt);
    });
}

// -------------------------------------------------------------
// FAKTUR SELECTOR (SEARCHABLE DROPDOWN)
// -------------------------------------------------------------
function toggleFakturDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('fakturDropdownMenu');
    if (menu.style.display === 'block') {
        hideFakturDropdown();
    } else {
        showFakturDropdown();
    }
}

function showFakturDropdown() {
    document.getElementById('fakturDropdownMenu').style.display = 'block';
    document.getElementById('fakturSearchInput').focus();
}

function hideFakturDropdown() {
    document.getElementById('fakturDropdownMenu').style.display = 'none';
}

function renderFakturOptions(list) {
    const container = document.getElementById('fakturOptionsContainer');
    if (!list || list.length === 0) {
        container.innerHTML = '<div class="text-muted small p-3 text-center">Tidak ada faktur belum lunas.</div>';
        return;
    }

    let html = '';
    list.forEach(f => {
        const sisaTagihan = parseFloat(f.sisa_tagihan) || 0;
        html += `
        <div class="faktur-opt-item p-2 px-3 border-bottom rounded-2" onclick="selectFaktur(${f.id_faktur})">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <strong class="font-monospace text-primary">${f.nomor_faktur}</strong>
                <span class="badge bg-danger-subtle text-danger border font-monospace">Sisa: ${formatRupiah(sisaTagihan)}</span>
            </div>
            <div class="small fw-semibold text-dark">${f.nama_vendor}</div>
            <div class="small text-muted d-flex justify-content-between">
                <span>Inv: ${f.nomor_faktur_vendor || '-'} | PO: ${f.nomor_po}</span>
                <span class="text-danger">Tempo: ${formatDate(f.tanggal_jatuh_tempo)}</span>
            </div>
        </div>`;
    });
    container.innerHTML = html;
}

function filterFakturList() {
    const query = document.getElementById('fakturSearchInput').value.toLowerCase().trim();
    const filtered = fakturList.filter(f => {
        return (f.nomor_faktur || '').toLowerCase().includes(query) ||
               (f.nomor_faktur_vendor || '').toLowerCase().includes(query) ||
               (f.nama_vendor || '').toLowerCase().includes(query) ||
               (f.nomor_po || '').toLowerCase().includes(query);
    });
    renderFakturOptions(filtered);
}

async function selectFaktur(idFaktur) {
    hideFakturDropdown();
    document.getElementById('selectedIdFaktur').value = idFaktur;

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/pembayaran_po/lookup_faktur.php?id_faktur=${idFaktur}`);
        const result = await res.json();

        if (result && result.success && result.data && result.data.faktur) {
            const f = result.data.faktur;
            currentSelectedFaktur = f;

            // Update Trigger Display
            document.getElementById('fakturSelectedDisplay').innerHTML = `
                <strong class="font-monospace text-primary">${f.nomor_faktur}</strong>
                <span class="text-muted ms-1">| ${f.nama_vendor} | Sisa: ${formatRupiah(f.sisa_tagihan)}</span>
            `;
            document.getElementById('fakturClearBtn').style.display = 'inline-block';

            // Show Summary
            document.getElementById('dispNomorFaktur').textContent = f.nomor_faktur;
            document.getElementById('dispNomorFakturVendor').textContent = f.nomor_faktur_vendor || '-';
            document.getElementById('dispNamaVendor').textContent = f.nama_vendor;
            document.getElementById('dispTanggalJatuhTempo').textContent = `${formatDate(f.tanggal_jatuh_tempo)} (${f.term_of_payment || 0} Hari TOP)`;
            
            // Set Default Nilai Rekening Vendor Tujuan
            resetRekeningVendorToDefault();

            document.getElementById('dispTotalTagihan').textContent = formatRupiah(f.total_tagihan);
            document.getElementById('dispTerbayar').textContent = formatRupiah(f.terbayar);
            document.getElementById('dispSisaTagihan').textContent = formatRupiah(f.sisa_tagihan);

            // History Table
            const histBody = document.getElementById('fakturHistoryTableBody');
            if (f.history_pembayaran && f.history_pembayaran.length > 0) {
                let histHtml = '';
                f.history_pembayaran.forEach(h => {
                    histHtml += `
                    <tr>
                        <td class="font-monospace text-primary">${h.kode_pembayaran}</td>
                        <td>${formatDate(h.tanggal_bayar)}</td>
                        <td class="text-end font-monospace fw-semibold">${formatRupiah(h.nominal_pengiriman)}</td>
                        <td class="font-monospace text-muted">${formatRupiah(h.sisa_piutang)}</td>
                    </tr>`;
                });
                histBody.innerHTML = histHtml;
                document.getElementById('fakturHistoryContainer').style.display = 'block';
            } else {
                document.getElementById('fakturHistoryContainer').style.display = 'none';
            }

            // Sync Nominal Pembayaran jika 1x Bayar (Lunas)
            handleJenisPembayaranChange();
        }
    } catch (e) {
        console.error('Gagal mengambil detail faktur terpilih:', e);
    }
}

function applyRekeningFromFaktur() {
    if (!currentSelectedFaktur) return;
    const f = currentSelectedFaktur;

    // Prioritas 1: Data dari Faktur PO
    let vendorBank = f.nama_bank || '';
    let vendorNorek = f.nomor_rekening || '';
    let vendorAn = f.atas_nama_rekening || f.nama_vendor || '';

    // Jika di faktur kosong, fallback ke master vendor
    if (!vendorBank && f.bank_vendor_master) {
        vendorBank = f.bank_vendor_master;
        vendorNorek = f.norek_vendor_master || '';
    }

    updateBankTujuanOptions(f.nama_bank || '', f.bank_vendor_master || '');
    document.getElementById('bankTujuan').value = vendorBank;
    document.getElementById('norekTujuan').value = vendorNorek;
    document.getElementById('anPengiriman').value = vendorAn;
}

function applyRekeningFromMasterVendor() {
    if (!currentSelectedFaktur) return;
    const f = currentSelectedFaktur;

    // Prioritas 2: Data dari Master Vendor
    let vendorBank = f.bank_vendor_master || f.nama_bank || '';
    let vendorNorek = f.norek_vendor_master || f.nomor_rekening || '';
    let vendorAn = f.atas_nama_rekening || f.nama_vendor || '';

    updateBankTujuanOptions(f.nama_bank || '', f.bank_vendor_master || '');
    document.getElementById('bankTujuan').value = vendorBank;
    document.getElementById('norekTujuan').value = vendorNorek;
    document.getElementById('anPengiriman').value = vendorAn;
}

function resetRekeningVendorToDefault() {
    applyRekeningFromFaktur();
}

function clearFakturSelection(e) {
    if (e) e.stopPropagation();
    currentSelectedFaktur = null;
    document.getElementById('selectedIdFaktur').value = '';
    document.getElementById('fakturSelectedDisplay').innerHTML = '<span class="text-muted">Cari Faktur PO (No. Faktur, Vendor, Invoice)...</span>';
    document.getElementById('fakturClearBtn').style.display = 'none';
    
    document.getElementById('dispNomorFaktur').textContent = '-';
    document.getElementById('dispNomorFakturVendor').textContent = '-';
    document.getElementById('dispNamaVendor').textContent = '-';
    document.getElementById('dispTanggalJatuhTempo').textContent = '-';
    document.getElementById('bankTujuan').value = '';
    document.getElementById('norekTujuan').value = '';
    document.getElementById('anPengiriman').value = '';
    updateBankTujuanOptions('', '');

    document.getElementById('dispTotalTagihan').textContent = 'Rp 0';
    document.getElementById('dispTerbayar').textContent = 'Rp 0';
    document.getElementById('dispSisaTagihan').textContent = 'Rp 0';

    document.getElementById('fakturHistoryContainer').style.display = 'none';
    document.getElementById('nominalPengiriman').value = '';
    document.getElementById('displaySisaSesudah').value = 'Rp 0';
}

// -------------------------------------------------------------
// AUTO THOUSAND SEPARATOR & FINANSIAL LOGIC
// -------------------------------------------------------------
function formatThousands(val) {
    let clean = String(val).replace(/[^0-9]/g, '');
    if (!clean) return '';
    let n = parseInt(clean, 10);
    return n.toLocaleString('id-ID');
}

function parseRawNumber(val) {
    let clean = String(val).replace(/[^0-9]/g, '');
    return parseInt(clean, 10) || 0;
}

function handleNominalInput(input) {
    let raw = parseRawNumber(input.value);
    input.value = raw > 0 ? raw.toLocaleString('id-ID') : '';
    calculateRemainingBalance();
}

function handleBiayaAdminInput(input) {
    let raw = parseRawNumber(input.value);
    input.value = raw > 0 ? raw.toLocaleString('id-ID') : '0';
}

function handleJenisPembayaranChange() {
    const isLunas = document.getElementById('jenisLunas').checked;
    const nominalInput = document.getElementById('nominalPengiriman');

    if (!currentSelectedFaktur) return;

    const sisa = parseFloat(currentSelectedFaktur.sisa_tagihan) || 0;

    if (isLunas) {
        nominalInput.value = sisa > 0 ? sisa.toLocaleString('id-ID') : '0';
        nominalInput.readOnly = true;
    } else {
        nominalInput.readOnly = false;
        let currentVal = parseRawNumber(nominalInput.value);
        if (currentVal >= sisa || currentVal === 0) {
            let half = Math.round(sisa / 2);
            nominalInput.value = half.toLocaleString('id-ID');
        }
    }
    calculateRemainingBalance();
    validateDueDateLimit();
}

function calculateRemainingBalance() {
    if (!currentSelectedFaktur) return;
    const sisaTagihan = parseFloat(currentSelectedFaktur.sisa_tagihan) || 0;
    const nominalBayar = parseRawNumber(document.getElementById('nominalPengiriman').value);
    const sisaSesudah = Math.max(0, sisaTagihan - nominalBayar);
    
    document.getElementById('displaySisaSesudah').value = formatRupiah(sisaSesudah);
}

function validateDueDateLimit() {
    const isKredit = document.getElementById('jenisKredit').checked;
    const tglBayar = document.getElementById('tanggalBayar').value;

    if (isKredit && currentSelectedFaktur && currentSelectedFaktur.tanggal_jatuh_tempo && tglBayar) {
        const dBayar = new Date(tglBayar);
        const dTempo = new Date(currentSelectedFaktur.tanggal_jatuh_tempo);

        if (dBayar > dTempo) {
            showToast(`Peringatan: Tanggal pembayaran (${formatDate(tglBayar)}) melewati Tanggal Jatuh Tempo TOP Faktur (${formatDate(currentSelectedFaktur.tanggal_jatuh_tempo)}).`, 'warning');
            document.getElementById('tanggalBayar').value = currentSelectedFaktur.tanggal_jatuh_tempo;
        }
    }
}

// -------------------------------------------------------------
// BANK PENGIRIM COMBOBOX
// -------------------------------------------------------------
function showBankPengirimDropdown() {
    document.getElementById('bankPengirimMenu').style.display = 'block';
}
function hideBankPengirimDropdown() {
    document.getElementById('bankPengirimMenu').style.display = 'none';
}
function toggleBankPengirimDropdown(e) {
    if (e) e.stopPropagation();
    const m = document.getElementById('bankPengirimMenu');
    m.style.display = m.style.display === 'block' ? 'none' : 'block';
}
function filterBankPengirimDropdown() {
    const query = (document.getElementById('bankPengirim').value || '').toLowerCase().trim();
    showBankPengirimDropdown();
    const items = document.querySelectorAll('.bank-p-opt');
    let count = 0;
    items.forEach(el => {
        const text = el.textContent.toLowerCase();
        if (text.includes(query)) {
            el.style.display = 'block';
            count++;
        } else {
            el.style.display = 'none';
        }
    });

    const noFound = document.getElementById('noBankPengirimFound');
    if (noFound) {
        if (count === 0) {
            noFound.classList.remove('d-none');
        } else {
            noFound.classList.add('d-none');
        }
    }
}
function selectBankPengirim(val) {
    document.getElementById('bankPengirim').value = val;
    hideBankPengirimDropdown();
}

// -------------------------------------------------------------
// BANK TUJUAN VENDOR COMBOBOX (PERSIS BANK PENGIRIM & DINAMIS DARI VENDOR)
// -------------------------------------------------------------
const DEFAULT_BANK_LIST = [
    'BCA', 'Bank Mandiri', 'BRI', 'BNI', 'CIMB Niaga', 
    'BSI', 'Bank Danamon', 'Bank Permata', 'Bank Jatim', 'Bank BJB',
    'Bank Mega', 'Bank Sinarmas', 'Bank BTN', 'Bank OCBC NISP',
    'CASH', 'QRIS'
];

function updateBankTujuanOptions(fakturBank, masterBank) {
    const menu = document.getElementById('bankTujuanMenu');
    if (!menu) return;

    let html = '';

    // 1. Opsi dari Faktur PO (Prioritas 1)
    if (fakturBank && fakturBank.trim() !== '') {
        const fBankTrim = fakturBank.trim();
        html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt fw-bold text-primary bg-primary-subtle mb-1" onclick="selectBankTujuan('${escapeHtml(fBankTrim)}')">
            <i class="bi bi-receipt me-1"></i> ${escapeHtml(fBankTrim)} <span class="badge bg-primary ms-1" style="font-size: 0.65rem;">Dari Faktur</span>
        </button>`;
    }

    // 2. Opsi dari Master Vendor (Prioritas 2 / Pilihan Alternatif)
    if (masterBank && masterBank.trim() !== '' && masterBank.trim().toLowerCase() !== (fakturBank || '').trim().toLowerCase()) {
        const mBankTrim = masterBank.trim();
        html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt fw-bold text-success bg-success-subtle mb-1" onclick="selectBankTujuan('${escapeHtml(mBankTrim)}')">
            <i class="bi bi-building me-1"></i> ${escapeHtml(mBankTrim)} <span class="badge bg-success ms-1" style="font-size: 0.65rem;">Master Vendor</span>
        </button>`;
    }

    // 3. Daftar Bank Umum Standar
    DEFAULT_BANK_LIST.forEach(b => {
        const isFaktur = fakturBank && (b.toLowerCase() === fakturBank.trim().toLowerCase());
        const isMaster = masterBank && (b.toLowerCase() === masterBank.trim().toLowerCase());
        if (!isFaktur && !isMaster) {
            html += `<button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('${escapeHtml(b)}')">${escapeHtml(b)}</button>`;
        }
    });

    html += `<div id="noBankTujuanFound" class="text-muted small px-3 py-2 d-none">Gunakan nama bank yang diketik manual.</div>`;
    menu.innerHTML = html;
}

function showBankTujuanDropdown() {
    document.getElementById('bankTujuanMenu').style.display = 'block';
}
function hideBankTujuanDropdown() {
    document.getElementById('bankTujuanMenu').style.display = 'none';
}
function toggleBankTujuanDropdown(e) {
    if (e) e.stopPropagation();
    const m = document.getElementById('bankTujuanMenu');
    m.style.display = m.style.display === 'block' ? 'none' : 'block';
}
function filterBankTujuanDropdown() {
    const query = (document.getElementById('bankTujuan').value || '').toLowerCase().trim();
    showBankTujuanDropdown();
    const items = document.querySelectorAll('.bank-t-opt');
    let count = 0;
    items.forEach(el => {
        const text = el.textContent.toLowerCase();
        if (text.includes(query)) {
            el.style.display = 'block';
            count++;
        } else {
            el.style.display = 'none';
        }
    });

    const noFound = document.getElementById('noBankTujuanFound');
    if (noFound) {
        if (count === 0) {
            noFound.classList.remove('d-none');
        } else {
            noFound.classList.add('d-none');
        }
    }
}
function selectBankTujuan(val) {
    document.getElementById('bankTujuan').value = val;
    hideBankTujuanDropdown();
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#bankPengirimWrapper')) hideBankPengirimDropdown();
    if (!e.target.closest('#bankTujuanWrapper')) hideBankTujuanDropdown();
    if (!e.target.closest('#fakturSelectWrapper')) hideFakturDropdown();
});

// -------------------------------------------------------------
// UPLOAD BUKTI BAYAR HANDLER
// -------------------------------------------------------------
function handleProofUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            showToast('Ukuran file maksimal 5MB.', 'warning');
            input.value = '';
            document.getElementById('hiddenBuktiBase64').value = '';
            document.getElementById('buktiFileInfo').textContent = 'Format: PDF, JPG, JPEG, PNG (Maks. 5MB)';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('hiddenBuktiBase64').value = e.target.result;
            document.getElementById('buktiFileInfo').innerHTML = `<span class="text-success fw-semibold">File terpilih: ${file.name}</span>`;
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('hiddenBuktiBase64').value = '';
        document.getElementById('buktiFileInfo').textContent = 'Format: PDF, JPG, JPEG, PNG (Maks. 5MB)';
    }
}

// -------------------------------------------------------------
// SUBMIT PAYMENT
// -------------------------------------------------------------
async function submitPayment() {
    const idFaktur = parseInt(document.getElementById('selectedIdFaktur').value);
    if (!idFaktur) {
        showToast('Silakan pilih dokumen Faktur PO terlebih dahulu pada Tab 1.', 'warning');
        goToTab('tab-faktur');
        return;
    }

    const nominal = parseRawNumber(document.getElementById('nominalPengiriman').value);
    if (nominal <= 0) {
        showToast('Nominal pembayaran harus lebih besar dari 0.', 'warning');
        goToTab('tab-nominal');
        document.getElementById('nominalPengiriman').focus();
        return;
    }

    const bankPengirim = document.getElementById('bankPengirim').value.trim();
    if (!bankPengirim) {
        showToast('Nama Bank Pengirim wajib diisi pada Tab 3.', 'warning');
        goToTab('tab-rekening');
        document.getElementById('bankPengirim').focus();
        return;
    }

    const idApprover = document.getElementById('selectApprover').value;
    if (!idApprover) {
        showToast('Silakan pilih Pejabat / Finance yang menyetujui transfer secara lisan pada Tab 4.', 'warning');
        goToTab('tab-approval');
        document.getElementById('selectApprover').focus();
        return;
    }

    const payload = {
        id_faktur: idFaktur,
        jenis_pembayaran: document.getElementById('jenisLunas').checked ? 1 : 0,
        tanggal_bayar: document.getElementById('tanggalBayar').value,
        nominal_pengiriman: nominal,
        biaya_admin: parseRawNumber(document.getElementById('biayaAdmin').value),
        bank_pengirim: bankPengirim,
        norek_pengirim: document.getElementById('norekPengirim').value.trim(),
        an_pengirim: document.getElementById('anPengirim').value.trim(),
        bank_tujuan: document.getElementById('bankTujuan').value.trim(),
        norek_tujuan: document.getElementById('norekTujuan').value.trim(),
        an_pengiriman: document.getElementById('anPengiriman').value.trim(),
        no_ref: document.getElementById('noRef').value.trim(),
        id_karyawan_approved: parseInt(idApprover),
        file_bukti_bayar_base64: document.getElementById('hiddenBuktiBase64').value,
        keterangan: document.getElementById('keteranganPayment').value.trim()
    };

    const btn = document.getElementById('btnSubmitPayment');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res = await fetch('<?= BASE_URL ?>/api/pembayaran_po/index.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result && result.success) {
            showToast(result.message || 'Pembayaran berhasil dicatat!', 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php';
            }, 1200);
        } else {
            showToast(result.message || 'Gagal menyimpan transaksi pembayaran.', 'danger');
            btn.disabled = false;
            btn.textContent = 'Simpan Transaksi Pembayaran';
        }
    } catch (e) {
        showToast('Terjadi kesalahan jaringan: ' + e.message, 'danger');
        btn.disabled = false;
        btn.textContent = 'Simpan Transaksi Pembayaran';
    }
}

function formatDate(dateStr) {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
}

function formatRupiah(num) {
    const n = parseFloat(num) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
