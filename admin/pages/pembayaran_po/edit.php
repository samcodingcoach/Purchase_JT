<?php
/**
 * Halaman Formulir Edit Pembayaran Faktur PO
 * Path: admin/pages/pembayaran_po/edit.php
 * Khusus Role: FINANCE, PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_FINANCE, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Edit Pembayaran PO';
$pageHeading = 'Perbarui Data Pembayaran Faktur';

$idDetail = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($idDetail <= 0) {
    header('Location: ' . BASE_URL . '/admin/pages/pembayaran_po/index.php');
    exit;
}

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
</style>

<div class="container-fluid px-0">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Edit Transaksi Pembayaran PO</h4>
            <div class="small text-muted mt-1" id="pageSubtitle">Memuat data pembayaran...</div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" style="height: 38px; display: inline-flex; align-items: center;">
                Kembali ke Riwayat
            </a>
        </div>
    </div>

    <form id="formEditPayment" onsubmit="event.preventDefault(); updatePayment();">
        <input type="hidden" id="editIdDetail" value="<?= $idDetail ?>">
        <input type="hidden" id="hiddenBuktiBase64" name="file_bukti_bayar_base64">

        <!-- CARD TAB MODULAR (5 TAB) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="paymentTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-3 px-3" id="tab-faktur-btn" data-bs-toggle="tab" data-bs-target="#tab-faktur" type="button" role="tab">
                            <i class="bi bi-receipt me-1 text-primary"></i> 1. Info Dokumen
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-nominal-btn" data-bs-toggle="tab" data-bs-target="#tab-nominal" type="button" role="tab">
                            <i class="bi bi-cash-coin me-1 text-primary"></i> 2. Rincian Transfer
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
                    
                    <!-- TAB 1: INFO DOKUMEN -->
                    <div class="tab-pane fade show active" id="tab-faktur" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Faktur &amp; Vendor</h6>
                                <table class="table table-sm table-borderless small mb-0">
                                    <tr>
                                        <td class="text-muted" style="width: 140px;">Kode Bayar:</td>
                                        <td><strong class="font-monospace text-primary fs-6" id="dispKodePembayaran">-</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">No. Faktur Sistem:</td>
                                        <td><strong class="font-monospace text-dark" id="dispNomorFaktur">-</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">No. Invoice Vendor:</td>
                                        <td><span class="font-monospace" id="dispNomorFakturVendor">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">No. Purchase Order:</td>
                                        <td><span class="font-monospace" id="dispNomorPo">-</span></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Nama Vendor:</td>
                                        <td><strong class="text-dark" id="dispNamaVendor">-</strong></td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Site Operasional:</td>
                                        <td id="dispNamaSite">-</td>
                                    </tr>
                                </table>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Status Tagihan &amp; Finansial</h6>
                                <div class="p-3 bg-light rounded-3 border mb-3">
                                    <div class="row g-2 text-center small">
                                        <div class="col-4">
                                            <div class="text-muted">Total Tagihan</div>
                                            <div class="fw-bold font-monospace text-dark" id="dispTotalTagihan">Rp 0</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted">Sudah Dibayar</div>
                                            <div class="fw-bold font-monospace text-success" id="dispTerbayar">Rp 0</div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-muted">Sisa Hutang</div>
                                            <div class="fw-bold font-monospace text-danger" id="dispSisaTagihan">Rp 0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-nominal')">
                                Lanjut ke Rincian Transfer
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: RINCIAN TRANSFER -->
                    <div class="tab-pane fade" id="tab-nominal" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Waktu &amp; Skema Pembayaran</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Tanggal Pembayaran</label>
                                    <input type="text" class="form-control bg-light font-monospace" id="editTanggalBayar" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Skema Pembayaran</label>
                                    <input type="text" class="form-control bg-light font-monospace fw-semibold" id="editSkema" readonly>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Nominal Transfer</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Nominal Pembayaran Transfer</label>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace fw-bold">Rp</span>
                                        <input type="text" class="form-control text-end font-monospace fw-bold fs-6 text-primary bg-light" id="editNominalPengiriman" readonly>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Biaya Admin Bank</label>
                                    <div class="input-group">
                                        <span class="input-group-text font-monospace">Rp</span>
                                        <input type="text" class="form-control text-end font-monospace" id="editBiayaAdmin" placeholder="0" oninput="handleBiayaAdminInput(this)">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-faktur')">
                                Kembali ke Info Dokumen
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
                                <h6 class="fw-bold text-dark mb-0">Rekening Vendor Tujuan Transfer</h6>
                                <div class="small text-muted">Rekening vendor yang menerima dana transfer pembayaran ini.</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Bank Tujuan Vendor (Combobox Persis Bank Pengirim) -->
                            <div class="col-sm-6 position-relative" id="editBankTujuanWrapper">
                                <label class="form-label small fw-semibold text-dark">Bank Tujuan Vendor</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace fw-semibold" id="editBankTujuan" name="bank_tujuan" placeholder="Pilih atau ketik bank..." autocomplete="off" onfocus="showBankTujuanDropdown()" oninput="filterBankTujuanDropdown()">
                                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split px-3" type="button" onclick="toggleBankTujuanDropdown(event)" title="Pilih Bank"></button>
                                </div>
                                <div class="dropdown-menu shadow-sm w-100 p-1" id="editBankTujuanMenu" style="max-height: 220px; overflow-y: auto; display: none; position: absolute; top: calc(100% + 2px); left: 0; z-index: 1050;">
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('BCA')">BCA</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('Bank Mandiri')">Bank Mandiri</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('BRI')">BRI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('BNI')">BNI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('CIMB Niaga')">CIMB Niaga</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('BSI')">BSI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('Bank Danamon')">Bank Danamon</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('Bank Permata')">Bank Permata</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('CASH')">CASH / TUNAI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-t-opt" onclick="selectBankTujuan('QRIS')">QRIS</button>
                                </div>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Nomor Rekening Tujuan Vendor</label>
                                <input type="text" class="form-control font-monospace fw-bold text-primary" id="editNorekTujuan" name="norek_tujuan" placeholder="Nomor Rekening Vendor">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Atas Nama Rekening Tujuan</label>
                                <input type="text" class="form-control fw-semibold text-dark" id="editAnPengiriman" name="an_pengiriman" placeholder="Atas Nama Pemilik Rekening Vendor">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-nominal')">
                                Kembali ke Rincian Transfer
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-rekening')">
                                Lanjut ke Kas &amp; Rekening Pengirim
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: KAS & REKENING PENGIRIM -->
                    <div class="tab-pane fade" id="tab-rekening" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Pilih Rekening Bank Resmi Perusahaan <span class="text-danger">*</span></label>
                                <select class="form-select fw-semibold" id="editSelectRekeningBankPengirim" onchange="handleSelectRekeningBankPengirimEdit(this)" required>
                                    <option value="">-- Pilih Rekening Bank Perusahaan --</option>
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Bank Asal / Kas Pengirim <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace fw-semibold bg-light" id="editBankPengirim" placeholder="Nama Bank Pengirim" readonly required>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Nomor Rekening Pengirim</label>
                                <input type="text" class="form-control font-monospace fw-bold bg-light" id="editNorekPengirim" placeholder="Nomor Rekening Kas / Tabungan" readonly>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Atas Nama Rekening Pengirim</label>
                                <input type="text" class="form-control fw-semibold bg-light" id="editAnPengirim" placeholder="Nama Pemilik Rekening / PT Jaya Teknis" readonly>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">No. Referensi / Mutasi Bank</label>
                                <input type="text" class="form-control font-monospace" id="editNoRef" placeholder="Contoh: TRF-2608-88129">
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
                                <select class="form-select" id="editSelectApprover" required>
                                    <option value="">-- Pilih Pejabat / Finance --</option>
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label small fw-semibold text-dark">Ganti / Upload Bukti Transfer Baru</label>
                                <input type="file" class="form-control" id="editFileBuktiBayar" accept="image/*,application/pdf" onchange="handleProofUpload(this)">
                                <div class="form-text small text-muted" id="editBuktiFileInfo">Format: PDF, JPG, JPEG, PNG (Maks. 5MB)</div>
                                <div id="currentProofWrapper" class="mt-2" style="display: none;"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Catatan / Keterangan Pembayaran</label>
                                <textarea class="form-control" id="editKeteranganPayment" placeholder="Catatan transaksi..."></textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-rekening')">
                                Kembali ke Kas &amp; Rekening Pengirim
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm px-4 shadow-sm fw-semibold" id="btnUpdatePayment" style="height: 38px; display: inline-flex; align-items: center;">
                                Perbarui Transaksi Pembayaran
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </form>
</div>

<script>
const idDetail = <?= $idDetail ?>;
let paymentData = null;

let rekeningBankPerusahaanList = [];

document.addEventListener('DOMContentLoaded', () => {
    loadEditData();
});

function goToTab(tabId) {
    const btn = document.querySelector(`[data-bs-target="#${tabId}"]`);
    if (btn) {
        const tab = new bootstrap.Tab(btn);
        tab.show();
    }
}

async function loadEditData() {
    try {
        // Load Approvers, Banks, and Single Detail concurrently
        const [resLookup, resBank, resDetail] = await Promise.all([
            fetch('<?= BASE_URL ?>/api/pembayaran_po/lookup_faktur.php'),
            fetch('<?= BASE_URL ?>/api/master/rekening_bank.php?all=1'),
            fetch(`<?= BASE_URL ?>/api/pembayaran_po/index.php?id_detail=${idDetail}`)
        ]);

        const resL = await resLookup.json();
        const resB = await resBank.json();
        const result = await resDetail.json();

        if (resL && resL.success && resL.data && resL.data.approvers) {
            renderApproverOptions(resL.data.approvers);
        }

        if (resB && resB.success && resB.data && resB.data.items) {
            rekeningBankPerusahaanList = resB.data.items || [];
            renderRekeningBankOptions(rekeningBankPerusahaanList);
        } else {
            renderRekeningBankOptions([]);
        }

        if (result && result.success && result.data) {
            paymentData = result.data;
            populateForm(paymentData);
        } else {
            showToast(result.message || 'Gagal memuat data pembayaran.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
    }
}

function renderRekeningBankOptions(banks) {
    const sel = document.getElementById('editSelectRekeningBankPengirim');
    if (!sel) return;

    let html = '<option value="">-- Pilih Rekening Bank Perusahaan --</option>';
    if (banks && banks.length > 0) {
        banks.forEach((b) => {
            const namaBank = escapeHtml(b.nama_bank || '');
            const noRek = escapeHtml(b.no_rekening || '');
            const anRek = escapeHtml(b.atasnama_rekening || 'PT JAYA TEKNIK');
            html += `<option value="${b.id_bank}" data-bank="${namaBank}" data-norek="${noRek}" data-an="${anRek}">
                ${namaBank} - ${noRek} (a.n ${anRek})
            </option>`;
        });
    }
    sel.innerHTML = html;
}

function handleSelectRekeningBankPengirimEdit(sel) {
    const val = sel.value;
    const selectedOpt = sel.options[sel.selectedIndex];
    const bankInput = document.getElementById('editBankPengirim');
    const norekInput = document.getElementById('editNorekPengirim');
    const anInput = document.getElementById('editAnPengirim');

    if (!val || !selectedOpt) {
        bankInput.value = '';
        norekInput.value = '';
        anInput.value = '';
        return;
    }

    const bankName = selectedOpt.getAttribute('data-bank') || '';
    const noRek = selectedOpt.getAttribute('data-norek') || '';
    const an = selectedOpt.getAttribute('data-an') || '';

    bankInput.value = bankName;
    norekInput.value = noRek;
    anInput.value = an;
}

function renderApproverOptions(approvers) {
    const sel = document.getElementById('editSelectApprover');
    sel.innerHTML = '<option value="">-- Pilih Pejabat / Finance --</option>';
    approvers.forEach(a => {
        const opt = document.createElement('option');
        opt.value = a.id_karyawan;
        opt.textContent = `${a.nama_karyawan} (${a.nama_jabatan || a.nama_divisi || 'Staff'})`;
        sel.appendChild(opt);
    });
}

function populateForm(d) {
    document.getElementById('pageSubtitle').textContent = `Kode: ${d.kode_pembayaran} | Faktur: ${d.nomor_faktur}`;
    document.getElementById('dispKodePembayaran').textContent = d.kode_pembayaran;
    document.getElementById('dispNomorFaktur').textContent = d.nomor_faktur;
    document.getElementById('dispNomorFakturVendor').textContent = d.nomor_faktur_vendor || '-';
    document.getElementById('dispNomorPo').textContent = d.nomor_po;
    document.getElementById('dispNamaVendor').textContent = d.nama_vendor;
    document.getElementById('editBankTujuan').value = d.bank_tujuan || d.bank_vendor || '';
    document.getElementById('editNorekTujuan').value = d.norek_tujuan || d.norek_vendor || '';
    document.getElementById('editAnPengiriman').value = d.an_pengiriman || d.an_vendor || d.nama_vendor || '';

    document.getElementById('dispTotalTagihan').textContent = formatRupiah(d.total_tagihan);
    document.getElementById('dispTerbayar').textContent = formatRupiah(d.total_terbayar_faktur);
    document.getElementById('dispSisaTagihan').textContent = formatRupiah(d.sisa_tagihan_faktur);

    document.getElementById('editTanggalBayar').value = formatDateTime(d.tanggal_bayar);
    document.getElementById('editSkema').value = parseInt(d.jenis_pembayaran) === 1 ? '1x Bayar (Lunas)' : 'Kredit / Termin';
    document.getElementById('editNominalPengiriman').value = formatThousands(d.nominal_pengiriman);
    document.getElementById('editBiayaAdmin').value = formatThousands(d.biaya_admin);

    document.getElementById('editBankPengirim').value = d.bank_pengirim || '';
    document.getElementById('editNorekPengirim').value = d.norek_pengirim || '';
    document.getElementById('editAnPengirim').value = d.an_pengirim || '';
    document.getElementById('editNoRef').value = d.no_ref || '';

    // Match Rekening Bank Pengirim
    const selRek = document.getElementById('editSelectRekeningBankPengirim');
    if (selRek) {
        for (let i = 0; i < selRek.options.length; i++) {
            const opt = selRek.options[i];
            const optBank = opt.getAttribute('data-bank') || '';
            const optNorek = opt.getAttribute('data-norek') || '';
            if (d.bank_pengirim && optBank.toLowerCase() === d.bank_pengirim.toLowerCase() && (!optNorek || optNorek === '-' || optNorek === (d.norek_pengirim || ''))) {
                selRek.selectedIndex = i;
                break;
            }
        }
    }

    if (d.id_karyawan_approved) {
        document.getElementById('editSelectApprover').value = d.id_karyawan_approved;
    }
    document.getElementById('editKeteranganPayment').value = d.keterangan || '';

    if (d.file_bukti_bayar) {
        const wrap = document.getElementById('currentProofWrapper');
        wrap.style.display = 'block';
        wrap.innerHTML = `<span class="small text-muted me-2">Bukti saat ini:</span> <a href="<?= BASE_URL ?>/uploads/pembayaran/${d.file_bukti_bayar}" target="_blank" class="btn btn-outline-primary btn-sm px-2 py-1"><i class="bi bi-file-earmark-check me-1"></i>Lihat File</a>`;
    }
}

function formatThousands(val) {
    let clean = String(val).replace(/[^0-9]/g, '');
    if (!clean) return '0';
    let n = parseInt(clean, 10);
    return n.toLocaleString('id-ID');
}

function parseRawNumber(val) {
    let clean = String(val).replace(/[^0-9]/g, '');
    return parseInt(clean, 10) || 0;
}

function handleBiayaAdminInput(input) {
    let raw = parseRawNumber(input.value);
    input.value = raw > 0 ? raw.toLocaleString('id-ID') : '0';
}

// Bank Tujuan Combobox (Persis Bank Pengirim)
function showBankTujuanDropdown() { document.getElementById('editBankTujuanMenu').style.display = 'block'; }
function hideBankTujuanDropdown() { document.getElementById('editBankTujuanMenu').style.display = 'none'; }
function toggleBankTujuanDropdown(e) {
    if (e) e.stopPropagation();
    const m = document.getElementById('editBankTujuanMenu');
    m.style.display = m.style.display === 'block' ? 'none' : 'block';
}
function filterBankTujuanDropdown() {
    const query = (document.getElementById('editBankTujuan').value || '').toLowerCase().trim();
    showBankTujuanDropdown();
    const items = document.querySelectorAll('.bank-t-opt');
    items.forEach(el => {
        const text = el.textContent.toLowerCase();
        el.style.display = text.includes(query) ? 'block' : 'none';
    });
}
function selectBankTujuan(val) {
    document.getElementById('editBankTujuan').value = val;
    hideBankTujuanDropdown();
}

document.addEventListener('click', (e) => {
    if (!e.target.closest('#editBankTujuanWrapper')) hideBankTujuanDropdown();
});

function handleProofUpload(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            showToast('Ukuran file maksimal 5MB.', 'warning');
            input.value = '';
            document.getElementById('hiddenBuktiBase64').value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('hiddenBuktiBase64').value = e.target.result;
            document.getElementById('editBuktiFileInfo').innerHTML = `<span class="text-success fw-semibold">File baru terpilih: ${file.name}</span>`;
        };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('hiddenBuktiBase64').value = '';
    }
}

async function updatePayment() {
    const bankPengirim = document.getElementById('editBankPengirim').value.trim();
    if (!bankPengirim) {
        showToast('Nama Bank Pengirim wajib diisi pada Tab 4.', 'warning');
        goToTab('tab-rekening');
        return;
    }

    const idApprover = document.getElementById('editSelectApprover').value;
    if (!idApprover) {
        showToast('Silakan pilih Pejabat / Finance yang menyetujui transfer secara lisan pada Tab 5.', 'warning');
        goToTab('tab-approval');
        return;
    }

    const payload = {
        id_pembayaran_detail: idDetail,
        bank_pengirim: bankPengirim,
        norek_pengirim: document.getElementById('editNorekPengirim').value.trim(),
        an_pengirim: document.getElementById('editAnPengirim').value.trim(),
        bank_tujuan: document.getElementById('editBankTujuan').value.trim(),
        norek_tujuan: document.getElementById('editNorekTujuan').value.trim(),
        an_pengiriman: document.getElementById('editAnPengiriman').value.trim(),
        no_ref: document.getElementById('editNoRef').value.trim(),
        biaya_admin: parseRawNumber(document.getElementById('editBiayaAdmin').value),
        id_karyawan_approved: parseInt(idApprover),
        file_bukti_bayar_base64: document.getElementById('hiddenBuktiBase64').value,
        keterangan: document.getElementById('editKeteranganPayment').value.trim()
    };

    const btn = document.getElementById('btnUpdatePayment');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    try {
        const res = await fetch('<?= BASE_URL ?>/api/pembayaran_po/index.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result && result.success) {
            showToast(result.message || 'Data pembayaran berhasil diperbarui!', 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php';
            }, 1200);
        } else {
            showToast(result.message || 'Gagal memperbarui data pembayaran.', 'danger');
            btn.disabled = false;
            btn.textContent = 'Perbarui Transaksi Pembayaran';
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
        btn.disabled = false;
        btn.textContent = 'Perbarui Transaksi Pembayaran';
    }
}

function formatRupiah(num) {
    const n = parseFloat(num) || 0;
    return 'Rp ' + n.toLocaleString('id-ID');
}
function formatDateTime(dateStr) {
    if (!dateStr || dateStr === '0000-00-00 00:00:00') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const hh = String(d.getHours()).padStart(2, '0');
    const mm = String(d.getMinutes()).padStart(2, '0');
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} ${hh}:${mm}`;
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
