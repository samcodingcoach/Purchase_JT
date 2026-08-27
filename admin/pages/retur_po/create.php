<?php
/**
 * Halaman Buat Dokumen Retur Purchase Order (Retur PO)
 * Path: admin/pages/retur_po/create.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_LOGISTIK, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Buat Retur PO';
$pageHeading = 'Formulir Pengajuan Retur PO';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Pengajuan Retur Purchase Order (PO)</h4>
            <p class="text-muted small mb-0">Formulir klaim pengembalian barang cacat / rusak ke pihak vendor rekanan.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/retur_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <!-- FORM DUA TAB -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="returTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold py-3 px-4" id="tab-info-btn" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">
                        <i class="bi bi-file-earmark-text me-2"></i>1. Informasi Dokumen &amp; Vendor
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-4" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
                        <i class="bi bi-box-seam me-2"></i>2. Rincian Barang yang Diretur <span class="badge bg-danger ms-1" id="badgeItemCount">0</span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <form id="formRetur" onsubmit="submitRetur(event)">
                <div class="tab-content" id="returTabContent">
                    
                    <!-- TAB 1: INFORMASI DOKUMEN -->
                    <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                        <div class="row g-4">
                            <!-- Kolom Kiri: Sumber Dokumen -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-link-45deg text-primary me-2"></i>Dokumen Asal Penerimaan (Receiving)</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Pilih Dokumen Penerimaan (RCV) <span class="text-danger">*</span></label>
                                    <select class="form-select form-select-sm" id="selectRcv" onchange="onRcvSelected()" required>
                                        <option value="">-- Pilih Dokumen Penerimaan Barang --</option>
                                    </select>
                                    <div class="form-text small" id="rcvHelp">Pilih nomor penerimaan barang yang memiliki barang cacat/rusak.</div>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Nomor Purchase Order (PO)</label>
                                        <input type="text" class="form-control form-control-sm bg-light font-monospace" id="displayNoPo" placeholder="-" readonly>
                                        <input type="hidden" id="inputPoId">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">Nomor Surat Jalan Vendor</label>
                                        <input type="text" class="form-control form-control-sm bg-light" id="displayNoSj" placeholder="-" readonly>
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label small fw-semibold text-muted">Site / Lokasi Fisik Barang</label>
                                    <input type="text" class="form-control form-control-sm bg-light" id="displaySite" placeholder="-" readonly>
                                    <input type="hidden" id="inputSiteId">
                                </div>

                                <div class="mt-3">
                                    <label class="form-label small fw-semibold text-dark">Tanggal Pengajuan Retur <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control form-control-sm" id="inputTanggalRetur" value="<?= date('Y-m-d') ?>" required>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Vendor & Skema Kompensasi -->
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-building text-primary me-2"></i>Informasi Vendor &amp; Skema Klaim</h6>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-muted">Vendor Rekanan</label>
                                    <input type="text" class="form-control form-control-sm bg-light fw-bold text-dark" id="displayVendor" placeholder="-" readonly>
                                    <input type="hidden" id="inputVendorId">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">PIC Vendor yang Dihubungi <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="inputPicVendor" placeholder="Nama sales/kontak person vendor" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Skema Kompensasi yang Disepakati <span class="text-danger">*</span></label>
                                    <div class="d-flex gap-3 mt-1">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="kompensasi" id="kompTukarUnit" value="1" checked>
                                            <label class="form-check-label small fw-semibold" for="kompTukarUnit">
                                                <i class="bi bi-arrow-repeat text-primary me-1"></i> Tukar Unit (Ganti Barang Baru)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="kompensasi" id="kompPotongTagihan" value="0">
                                            <label class="form-check-label small fw-semibold" for="kompPotongTagihan">
                                                <i class="bi bi-cash-coin text-warning-emphasis me-1"></i> Potong Tagihan (Credit Note)
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-text small">Tukar Unit: Vendor mengirimkan barang baru. Potong Tagihan: Mengurangi nominal pelunasan invoice.</div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">No. SJ Pengembalian (Opsional)</label>
                                        <input type="text" class="form-control form-control-sm" id="inputNoSjRetur" placeholder="SJ-RET-XXXX">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-semibold text-muted">No. Nota Retur Pajak (Opsional)</label>
                                        <input type="text" class="form-control form-control-sm" id="inputNoNotaPajak" placeholder="Untuk e-Faktur Pajak">
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="form-label small fw-semibold text-muted">Keterangan / Catatan Tambahan</label>
                                    <textarea class="form-control form-control-sm" id="inputKeterangan" rows="2" placeholder="Catatan pengembalian untuk pihak vendor..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="goToTab2()">
                                Lanjut ke Rincian Barang <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: RINCIAN BARANG -->
                    <div class="tab-pane fade" id="tab-items" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-list-check text-primary me-2"></i>Daftar Material yang Dikembalikan</h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openAddItemModal()">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Item Lain dari RCV
                            </button>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered align-middle mb-0" id="tableReturItems">
                                <thead class="table-light text-muted small text-uppercase">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">No</th>
                                        <th style="min-width: 200px;">Nama Barang</th>
                                        <th style="width: 110px;" class="text-center">Qty Retur</th>
                                        <th style="width: 80px;" class="text-center">Satuan</th>
                                        <th style="width: 130px;" class="text-end">Harga Satuan</th>
                                        <th style="width: 130px;" class="text-end">Subtotal</th>
                                        <th style="min-width: 160px;">Alasan Retur</th>
                                        <th style="min-width: 140px;">Keterangan &amp; Foto</th>
                                        <th style="width: 50px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody">
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="bi bi-box-arrow-in-left fs-3 d-block mb-2 text-secondary"></i>
                                            Silakan pilih Dokumen Penerimaan (RCV) pada Tab 1 terlebih dahulu.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- KOTAK RINGKASAN BIAYA -->
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="card bg-light border rounded-3 p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Subtotal Nilai Retur (DPP):</span>
                                        <span class="fw-bold font-monospace" id="summarySubtotal">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-muted small">Pajak PPN (<span id="summaryRatePajak">0</span>%):</span>
                                        <span class="fw-bold font-monospace text-primary" id="summaryNominalPajak">Rp 0</span>
                                    </div>
                                    <hr class="my-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-dark">Grand Total Nilai Klaim:</span>
                                        <span class="fs-5 fw-bold text-success font-monospace" id="summaryGrandTotal">Rp 0</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab1()">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Tab 1
                            </button>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm px-3" onclick="submitReturForm('DRAFT')">
                                    <i class="bi bi-save me-1"></i> Simpan Draft
                                </button>
                                <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="submitReturForm('MENUNGGU KONFIRMASI VENDOR')">
                                    <i class="bi bi-send-check me-1"></i> Terbitkan Retur PO
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL TAMBAH ITEM LAIN DARI RCV -->
<div class="modal fade" id="modalAddOtherItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>Pilih Material dari Penerimaan (RCV)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="list-group" id="rcvAvailableItemsList">
                    <!-- Items populated dynamically -->
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentRcvData = null;
let returItems = [];
let ratePajak = 0;

document.addEventListener('DOMContentLoaded', () => {
    loadRcvOptions();
});

async function loadRcvOptions() {
    try {
        const res = await apiRequest('/api/retur_po/receiving_lookup.php', 'GET');
        if (res && res.success && res.data) {
            const select = document.getElementById('selectRcv');
            select.innerHTML = '<option value="">-- Pilih Dokumen Penerimaan Barang --</option>';
            res.data.forEach(item => {
                const damagedBadge = item.damaged_count > 0 ? ` [ ⚠️ ${item.damaged_count} Barang Cacat ]` : '';
                select.innerHTML += `<option value="${item.id_rcv}">${item.nomor_rcv} — PO: ${item.nomor_po} (${item.nama_vendor})${damagedBadge}</option>`;
            });
        }
    } catch (e) {
        console.error('Gagal memuat opsi RCV:', e);
    }
}

async function onRcvSelected() {
    const idRcv = document.getElementById('selectRcv').value;
    if (!idRcv) {
        resetRcvDisplay();
        return;
    }

    try {
        const res = await apiRequest(`/api/retur_po/receiving_lookup.php?id_rcv=${idRcv}`, 'GET');
        if (res && res.success && res.data) {
            currentRcvData = res.data;
            const h = res.data.header;

            // Populate Tab 1
            document.getElementById('displayNoPo').value = h.nomor_po || '-';
            document.getElementById('inputPoId').value = h.id_po || '';
            document.getElementById('displayNoSj').value = h.nomor_sj || '-';
            document.getElementById('displaySite').value = h.nama_site || '-';
            document.getElementById('inputSiteId').value = h.id_site || '';
            document.getElementById('displayVendor').value = h.nama_vendor || '-';
            document.getElementById('inputVendorId').value = h.id_vendor || '';
            document.getElementById('inputPicVendor').value = h.pic_vendor || '';
            ratePajak = parseInt(h.rate_pajak) || 0;
            document.getElementById('summaryRatePajak').textContent = ratePajak;

            // Populate Tab 2 dengan Barang Cacat (status_qc = 0)
            returItems = [];
            if (res.data.damaged_items && res.data.damaged_items.length > 0) {
                res.data.damaged_items.forEach(d => {
                    returItems.push({
                        id_barang: d.id_barang,
                        kode_barang: d.kode_barang,
                        nama_barang: d.nama_barang,
                        qty_max: parseFloat(d.qty_rcv) || 1,
                        qty_retur: parseFloat(d.qty_rcv) || 1,
                        satuan: d.master_satuan || 'PCS',
                        harga_satuan: parseFloat(d.harga_satuan) || 0,
                        alasan_retur: 'RUSAK_FISIK',
                        keterangan_kerusakan: d.ket_rcv || 'Kondisi rusak saat penerimaan barang di gudang',
                        foto_base64: '',
                        foto_name: ''
                    });
                });
            } else if (res.data.items && res.data.items.length > 0) {
                // Jika tidak ada QC rusak, masukkan item pertama sebagai default
                const first = res.data.items[0];
                returItems.push({
                    id_barang: first.id_barang,
                    kode_barang: first.kode_barang,
                    nama_barang: first.nama_barang,
                    qty_max: parseFloat(first.qty_rcv) || 1,
                    qty_retur: parseFloat(first.qty_rcv) || 1,
                    satuan: first.master_satuan || 'PCS',
                    harga_satuan: parseFloat(first.harga_satuan) || 0,
                    alasan_retur: 'CACAT_PRODUKSI',
                    keterangan_kerusakan: '',
                    foto_base64: '',
                    foto_name: ''
                });
            }

            renderItemsTable();
        }
    } catch (e) {
        showToast('Gagal memuat detail penerimaan: ' + e.message, 'danger');
    }
}

function resetRcvDisplay() {
    currentRcvData = null;
    returItems = [];
    document.getElementById('displayNoPo').value = '-';
    document.getElementById('inputPoId').value = '';
    document.getElementById('displayNoSj').value = '-';
    document.getElementById('displaySite').value = '-';
    document.getElementById('inputSiteId').value = '';
    document.getElementById('displayVendor').value = '-';
    document.getElementById('inputVendorId').value = '';
    document.getElementById('inputPicVendor').value = '';
    ratePajak = 0;
    renderItemsTable();
}

function renderItemsTable() {
    const tbody = document.getElementById('itemsBody');
    document.getElementById('badgeItemCount').textContent = returItems.length;

    if (!returItems || returItems.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-5 text-muted">
                    <i class="bi bi-box-arrow-in-left fs-3 d-block mb-2 text-secondary"></i>
                    Belum ada material yang dipilih untuk diretur.
                </td>
            </tr>`;
        updateSummary();
        return;
    }

    let html = '';
    returItems.forEach((item, idx) => {
        const subtotal = item.qty_retur * item.harga_satuan;
        html += `
        <tr>
            <td class="text-center text-muted fw-semibold">${idx + 1}</td>
            <td>
                <div class="fw-bold text-dark">${item.nama_barang}</div>
                <div class="font-monospace text-muted small">${item.kode_barang || '-'}</div>
            </td>
            <td>
                <input type="number" step="any" min="0.01" max="${item.qty_max}" class="form-control form-control-sm text-center fw-bold" value="${item.qty_retur}" onchange="updateItemQty(${idx}, this.value)">
                <div class="text-muted text-center" style="font-size: 0.75rem;">Maks: ${item.qty_max}</div>
            </td>
            <td class="text-center fw-semibold text-muted small">${item.satuan}</td>
            <td class="text-end font-monospace">${formatRupiah(item.harga_satuan)}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(subtotal)}</td>
            <td>
                <select class="form-select form-select-sm" onchange="updateItemReason(${idx}, this.value)">
                    <option value="RUSAK_FISIK" ${item.alasan_retur === 'RUSAK_FISIK' ? 'selected' : ''}>Rusak Fisik / Pengiriman</option>
                    <option value="CACAT_PRODUKSI" ${item.alasan_retur === 'CACAT_PRODUKSI' ? 'selected' : ''}>Cacat Pabrik / Vendor</option>
                    <option value="SALAH_SPESIFIKASI" ${item.alasan_retur === 'SALAH_SPESIFIKASI' ? 'selected' : ''}>Salah Spesifikasi</option>
                    <option value="KURANG_PENGIRIMAN" ${item.alasan_retur === 'KURANG_PENGIRIMAN' ? 'selected' : ''}>Kurang Kuantitas</option>
                    <option value="KADALUARSA_EXP" ${item.alasan_retur === 'KADALUARSA_EXP' ? 'selected' : ''}>Kadaluarsa / Expired</option>
                </select>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm mb-1" placeholder="Catatan kerusakan..." value="${item.keterangan_kerusakan}" onchange="updateItemRemarks(${idx}, this.value)">
                <div class="d-flex align-items-center gap-1">
                    <input type="file" class="form-control form-control-sm" accept="image/*" onchange="handleItemPhotoUpload(${idx}, this)">
                </div>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm p-1" onclick="removeItem(${idx})" title="Hapus Baris">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
    updateSummary();
}

function updateItemQty(idx, val) {
    const num = parseFloat(val) || 0;
    if (num > returItems[idx].qty_max) {
        showToast(`Qty retur tidak boleh melebihi kuantitas penerimaan (${returItems[idx].qty_max})`, 'warning');
        returItems[idx].qty_retur = returItems[idx].qty_max;
    } else {
        returItems[idx].qty_retur = Math.max(0.01, num);
    }
    renderItemsTable();
}

function updateItemReason(idx, val) {
    returItems[idx].alasan_retur = val;
}

function updateItemRemarks(idx, val) {
    returItems[idx].keterangan_kerusakan = val;
}

function handleItemPhotoUpload(idx, input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
            returItems[idx].foto_base64 = e.target.result;
            returItems[idx].foto_name = file.name;
        };
        reader.readAsDataURL(file);
    }
}

function removeItem(idx) {
    returItems.splice(idx, 1);
    renderItemsTable();
}

function openAddItemModal() {
    if (!currentRcvData || !currentRcvData.items) {
        showToast('Pilih dokumen RCV pada Tab 1 terlebih dahulu.', 'warning');
        return;
    }

    const list = document.getElementById('rcvAvailableItemsList');
    list.innerHTML = '';

    currentRcvData.items.forEach(it => {
        const alreadyInTable = returItems.some(r => r.id_barang == it.id_barang);
        const disabled = alreadyInTable ? 'disabled opacity-50' : '';
        const badge = alreadyInTable ? '<span class="badge bg-secondary ms-2">Sudah Masuk</span>' : '';

        list.innerHTML += `
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ${disabled}" onclick="addOtherItem(${it.id_barang})">
                <div>
                    <div class="fw-semibold">${it.nama_barang}</div>
                    <div class="small text-muted font-monospace">${it.kode_barang} &bull; Diterima: ${it.qty_rcv} ${it.master_satuan}</div>
                </div>
                <div>${badge}</div>
            </button>`;
    });

    const modal = new bootstrap.Modal(document.getElementById('modalAddOtherItem'));
    modal.show();
}

function addOtherItem(idBarang) {
    const itemToAdd = currentRcvData.items.find(i => i.id_barang == idBarang);
    if (itemToAdd && !returItems.some(r => r.id_barang == idBarang)) {
        returItems.push({
            id_barang: itemToAdd.id_barang,
            kode_barang: itemToAdd.kode_barang,
            nama_barang: itemToAdd.nama_barang,
            qty_max: parseFloat(itemToAdd.qty_rcv) || 1,
            qty_retur: parseFloat(itemToAdd.qty_rcv) || 1,
            satuan: itemToAdd.master_satuan || 'PCS',
            harga_satuan: parseFloat(itemToAdd.harga_satuan) || 0,
            alasan_retur: itemToAdd.status_qc == 0 ? 'RUSAK_FISIK' : 'CACAT_PRODUKSI',
            keterangan_kerusakan: itemToAdd.ket_rcv || '',
            foto_base64: '',
            foto_name: ''
        });
        renderItemsTable();
    }
    bootstrap.Modal.getInstance(document.getElementById('modalAddOtherItem')).hide();
}

function updateSummary() {
    let subtotal = 0;
    returItems.forEach(i => {
        subtotal += (i.qty_retur * i.harga_satuan);
    });

    const nominalPajak = subtotal * (ratePajak / 100);
    const grandTotal = subtotal + nominalPajak;

    document.getElementById('summarySubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('summaryNominalPajak').textContent = formatRupiah(nominalPajak);
    document.getElementById('summaryGrandTotal').textContent = formatRupiah(grandTotal);
}

function goToTab2() {
    const rcv = document.getElementById('selectRcv').value;
    const pic = document.getElementById('inputPicVendor').value.trim();

    if (!rcv) {
        showToast('Harap pilih Dokumen Penerimaan (RCV) terlebih dahulu.', 'warning');
        return;
    }
    if (!pic) {
        showToast('Harap isi PIC Vendor yang bertanggung jawab.', 'warning');
        document.getElementById('inputPicVendor').focus();
        return;
    }

    const tab = new bootstrap.Tab(document.getElementById('tab-items-btn'));
    tab.show();
}

function goToTab1() {
    const tab = new bootstrap.Tab(document.getElementById('tab-info-btn'));
    tab.show();
}

async function submitReturForm(targetStatus) {
    const idPo = document.getElementById('inputPoId').value;
    const idRcv = document.getElementById('selectRcv').value;
    const idVendor = document.getElementById('inputVendorId').value;
    const idSite = document.getElementById('inputSiteId').value;
    const picVendor = document.getElementById('inputPicVendor').value.trim();
    const kompensasi = document.querySelector('input[name="kompensasi"]:checked').value;
    const tanggalRetur = document.getElementById('inputTanggalRetur').value;
    const noSjRetur = document.getElementById('inputNoSjRetur').value.trim();
    const noNotaPajak = document.getElementById('inputNoNotaPajak').value.trim();
    const keterangan = document.getElementById('inputKeterangan').value.trim();

    if (!idRcv || !idPo || !idVendor) {
        showToast('Dokumen penerimaan dan vendor wajib dipilih.', 'warning');
        goToTab1();
        return;
    }

    if (!picVendor) {
        showToast('PIC Vendor wajib diisi.', 'warning');
        goToTab1();
        return;
    }

    if (returItems.length === 0) {
        showToast('Harap tambahkan minimal 1 item barang yang akan diretur.', 'warning');
        return;
    }

    const payload = {
        id_po: parseInt(idPo),
        id_rcv: parseInt(idRcv),
        id_vendor: parseInt(idVendor),
        id_site: parseInt(idSite),
        pic_vendor: picVendor,
        kompensasi: parseInt(kompensasi),
        tanggal_po_retur: tanggalRetur,
        rate_pajak: ratePajak,
        nomor_sj_retur: noSjRetur,
        nomor_nota_retur_pajak: noNotaPajak,
        keterangan: keterangan,
        status: targetStatus,
        items: returItems
    };

    try {
        const res = await apiRequest('/api/retur_po/index.php', 'POST', payload);
        if (res && res.success) {
            showToast(res.message || 'Dokumen Retur PO berhasil dibuat!', 'success');
            setTimeout(() => {
                window.location.href = `<?= BASE_URL ?>/admin/pages/retur_po/detail.php?id=${res.data.id_po_retur}`;
            }, 1200);
        } else {
            showToast(res.message || 'Gagal membuat dokumen Retur PO.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan sistem: ' + e.message, 'danger');
    }
}

function formatRupiah(num) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
