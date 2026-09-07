<?php
/**
 * Halaman Edit Purchase Order (PO)
 * Path: admin/pages/purchase_order/edit.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection: Khusus Purchasing, Manager, dan Admin (Logistik diblokir)
$user = requireAuth([ROLE_ADMIN, ROLE_PURCHASING, ROLE_MANAGER]);
$idPo = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idPo <= 0) {
    header('Location: ' . BASE_URL . '/admin/pages/purchase_order/index.php');
    exit;
}

$pageTitle = 'Edit Purchase Order #' . $idPo;
$pageHeading = 'Edit Purchase Order (PO)';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Title -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">
            Edit Purchase Order <span id="headerNomorPoDisplay" class="font-monospace text-primary">...</span>
        </h4>
    </div>

    <!-- Alert Loading Notice / Status Warning -->
    <div id="poAlertNotice" class="alert alert-info border-0 shadow-sm d-flex align-items-center gap-2 mb-4">
        <div class="spinner-border spinner-border-sm text-primary"></div>
        <span>Memuat data Purchase Order...</span>
    </div>

    <!-- FORMULIR EDIT PURCHASE ORDER -->
    <form id="formEditPo" onsubmit="handleUpdatePo(event)" class="d-none" novalidate>
        <input type="hidden" id="editIdPo" value="<?= $idPo ?>">

        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <!-- TAB NAVIGATION HEADER -->
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="poEditTabNav" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-3 px-3" id="tab-info-btn" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Data PO &amp; Vendor
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-shipping-btn" data-bs-toggle="tab" data-bs-target="#tab-shipping" type="button" role="tab">
                            <i class="bi bi-truck me-1 text-primary"></i> 2. Pengiriman &amp; Pembayaran
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-pricing-btn" data-bs-toggle="tab" data-bs-target="#tab-pricing" type="button" role="tab">
                            <i class="bi bi-boxes me-1 text-primary"></i> 3. Rincian Barang &amp; Biaya
                            <span class="badge bg-primary ms-1" id="tabItemCountBadge">0</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- TAB CONTENTS -->
            <div class="card-body p-4">
                <div class="tab-content" id="poEditTabContent">
                    
                    <!-- TAB 1: DATA PO & VENDOR (HANYA 2 KOLOM) -->
                    <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Nomor PO (Permanen)</label>
                                <input type="text" class="form-control form-control-sm font-monospace bg-light fw-bold text-primary" id="editNomorPo" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Tanggal PO <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" id="editTanggalPo" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Vendor</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold text-dark" id="editNamaVendor" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Prioritas Pengadaan</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-bold font-monospace text-dark" id="editPrioritas" readonly title="Prioritas ditetapkan dari Request Order dan tidak dapat diubah">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Referensi Request Order (RO)</label>
                                <input type="text" class="form-control form-control-sm bg-light font-monospace" id="editNomorRo" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Pembuat (Purchasing)</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="editNamaPembuat" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Status Dokumen PO</label>
                                <select class="form-select form-select-sm" id="editStatusPo">
                                    <option value="DRAFT">Draft</option>
                                    <option value="REVIEW INTERNAL">Review Internal</option>
                                    <option value="DISETUJUI INTERNAL">Disetujui Internal</option>
                                    <option value="TIDAK DISETUJUI INTERNAL">Tidak Disetujui Internal</option>
                                    <option value="REVIEW VENDOR">Review Vendor</option>
                                    <option value="DIPROSES VENDOR">Diproses Vendor</option>
                                    <option value="BATAL">Batal</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Disetujui Oleh</label>
                                <input type="text" class="form-control form-control-sm bg-light" id="editNamaApprover" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Catatan / Keterangan PO</label>
                                <textarea class="form-control form-control-sm" id="editKeterangan" rows="3" placeholder="Instruksi tambahan untuk vendor..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: PENGIRIMAN & PEMBAYARAN -->
                    <div class="tab-pane fade" id="tab-shipping" role="tabpanel">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Site Tujuan</label>
                                <input type="text" class="form-control form-control-sm bg-light fw-semibold text-dark" id="editNamaSite" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Metode Pengiriman</label>
                                <select class="form-select form-select-sm" id="editPengiriman">
                                    <option value="Vendor">Vendor (Dikirim Vendor)</option>
                                    <option value="Expedisi">Expedisi (Jasa Logistik Luar)</option>
                                    <option value="Internal">Internal (Armada PT Jaya Teknis)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Estimasi Tanggal Tiba</label>
                                <input type="date" class="form-control form-control-sm" id="editTanggalPengiriman">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Term of Payment (T.O.P - Hari)</label>
                                <div class="input-group input-group-sm">
                                    <input type="number" class="form-control form-control-sm font-monospace" id="editTop" min="0" max="180" step="1" oninput="updateTopBadge(this.value)">
                                    <span class="input-group-text bg-light">Hari</span>
                                </div>
                                <div class="mt-1" id="badgeTopKeterangan"></div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark">Alamat Lengkap Pengiriman</label>
                                <textarea class="form-control form-control-sm" id="editAlamat" rows="3" placeholder="Alamat gudang / dok tujuan..."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: RINCIAN BARANG & BIAYA -->
                    <div class="tab-pane fade" id="tab-pricing" role="tabpanel">
                        <div class="table-responsive border rounded-3 mb-4">
                            <table class="table table-hover align-middle mb-0" id="tablePricingItems">
                                <thead class="table-light small text-muted">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th>Nama Barang &amp; Kode</th>
                                        <th style="width: 100px;" class="text-center">Qty</th>
                                        <th style="width: 150px;">Harga Satuan (Rp)</th>
                                        <th style="width: 130px;">Diskon Item (Rp)</th>
                                        <th style="width: 160px;" class="text-end">Subtotal (Rp)</th>
                                    </tr>
                                </thead>
                                <tbody id="tablePricingItemsBody">
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Memuat rincian barang...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Financial Calculation Settings & Totals -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card bg-light border-0 rounded-3 p-3 h-100">
                                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders me-1 text-primary"></i> Pengaturan Pajak &amp; Diskon Global</h6>
                                    
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Diskon Global PO (Rp)</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white">Rp</span>
                                            <input type="text" class="form-control form-control-sm font-monospace text-end" id="editDiskonPo" value="0" oninput="handleGlobalDiskonInput(this)">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Tarif Pajak PPN</label>
                                        <select class="form-select form-select-sm" id="editPajakRate" onchange="calculateAllTotals()">
                                            <option value="0">Tanpa PPN (0%)</option>
                                            <option value="11">PPN 11%</option>
                                            <option value="12">PPN 12%</option>
                                        </select>
                                    </div>

                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="editTotalTermasukPajak" onchange="calculateAllTotals()">
                                        <label class="form-check-label small text-dark" for="editTotalTermasukPajak">
                                            Harga Barang Sudah Termasuk PPN (Inklusif)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-white shadow-xs">
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Subtotal Barang:</span>
                                        <span class="fw-semibold text-dark font-monospace" id="displaySubtotalBarang">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">Diskon Akhir:</span>
                                        <span class="fw-semibold text-danger font-monospace" id="displayDiskonPo">- Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted">DPP (Dasar Pengenaan Pajak):</span>
                                        <span class="fw-semibold text-dark font-monospace" id="displayDpp">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2 small">
                                        <span class="text-muted" id="displayLabelPpn">PPN (12%):</span>
                                        <span class="fw-semibold text-dark font-monospace" id="displayNominalPajak">Rp 0</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">GRAND TOTAL:</span>
                                        <span class="fs-5 fw-bold text-primary font-monospace" id="displayGrandTotal">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER ACTION BUTTONS -->
            <div class="card-footer bg-light p-3 border-top d-flex justify-content-end align-items-center gap-2">
                <a href="<?= BASE_URL ?>/admin/pages/purchase_order/index.php" class="btn btn-secondary btn-sm px-3">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" id="btnSubmitUpdatePo">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Purchase Order
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<script>
const ID_PO = <?= $idPo ?>;
const CURRENT_USER_ROLE = '<?= strtoupper($user['role'] ?? '') ?>';
let poDataCache = null;

document.addEventListener('DOMContentLoaded', async () => {
    await loadExistingPoData();
});

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

    let cleanVal = el.value.replace(/[^0-9]/g, '');
    let num = parseInt(cleanVal, 10);
    if (isNaN(num) || num < 0) num = 0;

    el.value = num > 0 ? num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".") : '0';

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

function handleGlobalDiskonInput(el) {
    handleThousandInput(el, () => calculateAllTotals());
}

// -------------------------------------------------------------
// LOAD EXISTING PO DATA
// -------------------------------------------------------------
async function loadExistingPoData() {
    const res = await apiRequest(`/api/purchase_order/index.php?id=${ID_PO}`);
    if (!res || !res.success || !res.data) {
        document.getElementById('poAlertNotice').className = 'alert alert-danger border-0';
        document.getElementById('poAlertNotice').innerHTML = `
            <i class="bi bi-x-circle-fill me-2"></i> ${res ? res.message : 'Purchase Order tidak ditemukan.'}
        `;
        return;
    }

    poDataCache = res.data;
    const po = poDataCache;

    // Cek Status Penguncian Sesuai Role
    const currentStatusUpper = (po.status || '').toUpperCase();
    let isLocked = false;
    let lockReason = '';

    if (['DIPROSES VENDOR', 'DITERIMA', 'BATAL'].includes(currentStatusUpper)) {
        isLocked = true;
        lockReason = `Dokumen <strong>${escapeHtml(po.nomor_po || '')}</strong> berstatus <strong>${escapeHtml(po.status || '')}</strong> dan data formulir sudah tidak dapat diubah lagi.`;
    } else if (CURRENT_USER_ROLE === 'LOGISTIK') {
        isLocked = true;
        lockReason = `Bagian <strong>Logistik</strong> tidak memiliki wewenang untuk mengedit dokumen Purchase Order.`;
    }

    if (isLocked) {
        document.getElementById('poAlertNotice').className = 'alert alert-warning border-0';
        document.getElementById('poAlertNotice').innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-lock-fill fs-3 text-warning"></i>
                <div>
                    <h6 class="fw-bold text-dark mb-1">Dokumen Purchase Order Terkunci</h6>
                    <div class="small text-secondary">${lockReason}</div>
                </div>
            </div>
        `;
        return;
    }

    // Sembunyikan Alert Loading & Tampilkan Form
    document.getElementById('poAlertNotice').classList.add('d-none');
    document.getElementById('formEditPo').classList.remove('d-none');

    // Header Display
    document.getElementById('headerNomorPoDisplay').textContent = po.nomor_po || '-';
    document.getElementById('editNomorPo').value = po.nomor_po || '';
    document.getElementById('editTanggalPo').value = po.tanggal_po ? po.tanggal_po.split(' ')[0] : '';
    document.getElementById('editPrioritas').value = (po.prioritas || 'NORMAL').toUpperCase();
    document.getElementById('editNamaVendor').value = po.nama_vendor || '-';
    document.getElementById('editNomorRo').value = po.nomor_ro ? `RO: ${po.nomor_ro}` : 'Tanpa RO';
    document.getElementById('editStatusPo').value = po.status || 'DRAFT';
    document.getElementById('editNamaPembuat').value = po.nama_pembuat || 'Staff Purchasing';
    document.getElementById('editNamaApprover').value = po.nama_approver || 'Menunggu Persetujuan';
    document.getElementById('editKeterangan').value = po.keterangan || '';

    // Tab 2 Shipping
    document.getElementById('editNamaSite').value = po.nama_site ? `${po.nama_site} (${po.kode_site || '-'})` : '-';
    document.getElementById('editPengiriman').value = po.pengiriman || 'Vendor';
    document.getElementById('editTanggalPengiriman').value = po.tanggal_pengiriman ? po.tanggal_pengiriman.split(' ')[0] : '';
    document.getElementById('editTop').value = po.term_of_payment !== null ? po.term_of_payment : 30;
    updateTopBadge(document.getElementById('editTop').value);
    document.getElementById('editAlamat').value = po.alamat || (po.alamat_site || '');

    // Tab 3 Pricing Settings
    document.getElementById('editDiskonPo').value = formatThousand(po.diskon || 0);
    document.getElementById('editPajakRate').value = po.pajak || 0;
    document.getElementById('editTotalTermasukPajak').checked = (parseInt(po.total_termasuk_pajak) === 1);

    // Render Items
    const items = po.items || [];
    document.getElementById('tabItemCountBadge').textContent = items.length;

    let itemsHtml = '';
    items.forEach((item, idx) => {
        const itemQty = parseFloat(item.qty) || 0;
        const itemHarga = parseFloat(item.harga) || 0;
        const itemDiskon = parseFloat(item.diskon) || 0;
        const itemSubtotal = maxZero((itemQty * itemHarga) - itemDiskon);

        itemsHtml += `
            <tr data-detail-id="${item.id_po_detail}">
                <td class="text-center font-monospace small text-muted">${idx + 1}</td>
                <td>
                    <div class="fw-bold text-dark mb-1">${escapeHtml(item.nama_barang || '')}</div>
                    <div class="d-flex flex-wrap gap-1 align-items-center">
                        <span class="badge bg-light text-muted border font-monospace" style="font-size: 0.68rem;">${escapeHtml(item.kode_barang || 'BRG')}</span>
                        ${item.nama_kategori ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">${escapeHtml(item.nama_kategori)}</span>` : ''}
                        ${item.nama_merk ? `<span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">${escapeHtml(item.nama_merk)}</span>` : ''}
                    </div>
                    <input type="hidden" class="item-detail-id" value="${item.id_po_detail}">
                </td>
                <td class="text-center">
                    <span class="fw-bold font-monospace">${itemQty}</span>
                    <span class="small text-muted d-block" style="font-size: 0.72rem;">${escapeHtml(item.satuan || 'PCS')}</span>
                    <input type="hidden" class="item-qty" value="${itemQty}">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light font-monospace" style="font-size: 0.75rem;">Rp</span>
                        <input type="text" class="form-control form-control-sm font-monospace text-end item-harga" value="${formatThousand(itemHarga)}" oninput="handlePriceInput(this)" placeholder="0" required>
                    </div>
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light font-monospace" style="font-size: 0.75rem;">Rp</span>
                        <input type="text" class="form-control form-control-sm font-monospace text-end item-diskon" value="${formatThousand(itemDiskon)}" oninput="handleDiscountInput(this)" placeholder="0">
                    </div>
                </td>
                <td class="text-end font-monospace fw-bold text-dark item-subtotal-display">
                    ${formatRupiah(itemSubtotal)}
                </td>
            </tr>
        `;
    });

    document.getElementById('tablePricingItemsBody').innerHTML = itemsHtml;
    calculateAllTotals();
}

// -------------------------------------------------------------
// KALKULASI TOTAL BIAYA PO
// -------------------------------------------------------------
function calculateRowSubtotal(inputEl) {
    const row = inputEl.closest('tr');
    const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
    const harga = parseThousandNumber(row.querySelector('.item-harga').value);
    const diskon = parseThousandNumber(row.querySelector('.item-diskon').value);

    const subtotal = maxZero((qty * harga) - diskon);
    row.querySelector('.item-subtotal-display').textContent = formatRupiah(subtotal);
    calculateAllTotals();
}

function calculateAllTotals() {
    let subtotalBarang = 0;
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga')?.value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon')?.value);
        subtotalBarang += maxZero((qty * harga) - diskon);
    });

    const diskonPo = parseThousandNumber(document.getElementById('editDiskonPo').value);
    const ratePajak = parseFloat(document.getElementById('editPajakRate').value) || 0;
    const isInclusive = document.getElementById('editTotalTermasukPajak').checked;

    const dasarSetelahDiskon = maxZero(subtotalBarang - diskonPo);
    let dpp = 0;
    let nominalPajak = 0;
    let grandTotal = 0;

    if (ratePajak > 0) {
        if (isInclusive) {
            dpp = dasarSetelahDiskon / (1 + (ratePajak / 100));
            nominalPajak = dasarSetelahDiskon - dpp;
            grandTotal = dasarSetelahDiskon;
        } else {
            dpp = dasarSetelahDiskon;
            nominalPajak = (dpp * ratePajak) / 100;
            grandTotal = dpp + nominalPajak;
        }
    } else {
        dpp = dasarSetelahDiskon;
        nominalPajak = 0;
        grandTotal = dpp;
    }

    document.getElementById('displaySubtotalBarang').textContent = formatRupiah(subtotalBarang);
    document.getElementById('displayDiskonPo').textContent = '- ' + formatRupiah(diskonPo);
    document.getElementById('displayDpp').textContent = formatRupiah(dpp);
    document.getElementById('displayLabelPpn').textContent = `PPN (${ratePajak}%)${isInclusive ? ' (Inklusif)' : ''}:`;
    document.getElementById('displayNominalPajak').textContent = formatRupiah(nominalPajak);
    document.getElementById('displayGrandTotal').textContent = formatRupiah(grandTotal);
}

function updateTopBadge(val) {
    const num = parseInt(val) || 0;
    const badge = document.getElementById('badgeTopKeterangan');
    if (num === 0) {
        badge.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle">C.O.D (Cash On Delivery)</span>';
    } else {
        badge.innerHTML = `<span class="badge bg-light text-dark border">Tempo ${num} Hari</span>`;
    }
}

// -------------------------------------------------------------
// SUBMIT UPDATE PURCHASE ORDER
// -------------------------------------------------------------
async function handleUpdatePo(event) {
    event.preventDefault();

    const tanggalPo = document.getElementById('editTanggalPo').value;
    if (!tanggalPo) {
        showToast('Tanggal PO wajib diisi.', 'warning');
        goToTab('tab-info-btn');
        document.getElementById('editTanggalPo').focus();
        return;
    }

    const items = [];
    document.querySelectorAll('#tablePricingItemsBody tr').forEach(row => {
        const idDetail = parseInt(row.querySelector('.item-detail-id')?.value) || 0;
        const qty = parseFloat(row.querySelector('.item-qty')?.value) || 0;
        const harga = parseThousandNumber(row.querySelector('.item-harga')?.value);
        const diskon = parseThousandNumber(row.querySelector('.item-diskon')?.value);
        if (idDetail > 0) {
            items.push({
                id_po_detail: idDetail,
                qty: qty,
                harga: harga,
                diskon: diskon
            });
        }
    });

    const payload = {
        id_po: ID_PO,
        tanggal_po: tanggalPo,
        prioritas: document.getElementById('editPrioritas').value,
        status: document.getElementById('editStatusPo').value,
        keterangan: document.getElementById('editKeterangan').value,
        pengiriman: document.getElementById('editPengiriman').value,
        tanggal_pengiriman: document.getElementById('editTanggalPengiriman').value,
        term_of_payment: parseInt(document.getElementById('editTop').value) || 0,
        alamat: document.getElementById('editAlamat').value,
        diskon: parseThousandNumber(document.getElementById('editDiskonPo').value),
        pajak: parseInt(document.getElementById('editPajakRate').value) || 0,
        total_termasuk_pajak: document.getElementById('editTotalTermasukPajak').checked ? 1 : 0,
        items: items
    };

    const btn = document.getElementById('btnSubmitUpdatePo');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Menyimpan Perubahan...';

    const res = await apiRequest('/api/purchase_order/update.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Purchase Order';

    if (res && res.success) {
        showToast(res.message, 'success');
        setTimeout(() => {
            window.location.href = `${BASE_URL}/admin/pages/purchase_order/index.php`;
        }, 1200);
    } else {
        showToast(res ? res.message : 'Gagal memperbarui Purchase Order.', 'danger');
    }
}

function goToTab(tabBtnId) {
    const tabEl = document.getElementById(tabBtnId);
    if (tabEl) {
        const tabTrigger = new bootstrap.Tab(tabEl);
        tabTrigger.show();
    }
}

function maxZero(num) {
    return num < 0 ? 0 : num;
}

function formatRupiah(amount) {
    return 'Rp ' + Math.round(amount).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>
