<?php
/**
 * Halaman Detail & Otorisasi Retur Purchase Order (Retur PO)
 * Path: admin/pages/retur_po/detail.php
 * Khusus Role: LOGISTIK, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_LOGISTIK, ROLE_ADMIN, ROLE_MANAGER]);
$idRetur = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($idRetur <= 0) {
    header("Location: " . BASE_URL . "/admin/pages/retur_po/index.php");
    exit;
}

$pageTitle = 'Detail Retur PO';
$pageHeading = 'Detail & Tindak Lanjut Retur PO';

// Include Header & Layout Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0" id="detailContainer">
    <!-- TOP HEADER -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold text-dark font-monospace mb-0" id="headerNomorRetur">-</h4>
                <span id="headerStatusBadge"></span>
            </div>
            <p class="text-muted small mb-0">Dokumen Pengembalian Barang Rusak / Cacat ke Vendor Rekanan</p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <a href="<?= BASE_URL ?>/admin/pages/retur_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold" onclick="openUpdateStatusModal()">
                <i class="bi bi-arrow-repeat me-1"></i> Tindak Lanjut / Ubah Status
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm px-3 shadow-sm" id="btnDeleteDraft" onclick="deleteDraft()" style="display: none;">
                <i class="bi bi-trash me-1"></i> Hapus Draft
            </button>
        </div>
    </div>

    <!-- MAIN CARD WITH 5 TABS (CONSISTENT WITH CREATE.PHP) -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="returDetailTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold py-3 px-3" id="tab-info-btn" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">
                        <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Informasi Utama
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-3" id="tab-vendor-btn" data-bs-toggle="tab" data-bs-target="#tab-vendor" type="button" role="tab">
                        <i class="bi bi-building me-1 text-primary"></i> 2. Vendor
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-3" id="tab-pengiriman-btn" data-bs-toggle="tab" data-bs-target="#tab-pengiriman" type="button" role="tab">
                        <i class="bi bi-truck me-1 text-primary"></i> 3. Pengiriman
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-3" id="tab-items-btn" data-bs-toggle="tab" data-bs-target="#tab-items" type="button" role="tab">
                        <i class="bi bi-box-seam me-1 text-primary"></i> 4. Rincian Barang <span class="badge bg-danger ms-1" id="badgeItemCount">0</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold py-3 px-3" id="tab-persetujuan-btn" data-bs-toggle="tab" data-bs-target="#tab-persetujuan" type="button" role="tab">
                        <i class="bi bi-check2-circle me-1 text-primary"></i> 5. Persetujuan
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-3 p-md-4">
            <div class="tab-content" id="returDetailTabContent">

                <!-- ========================================================= -->
                <!-- TAB 1: INFORMASI UTAMA -->
                <!-- ========================================================= -->
                <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-link-45deg text-primary me-2"></i>Dokumen Asal Penerimaan (Receiving)</h6>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Nomor Penerimaan Barang (RCV):</span>
                                    <span class="fw-bold font-monospace fs-6 text-dark" id="infoNoRcv">-</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Nomor Purchase Order (PO):</span>
                                    <span class="fw-bold font-monospace fs-6 text-dark" id="infoNoPo">-</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">No. Surat Jalan Asal Vendor:</span>
                                    <span class="fw-semibold text-dark" id="infoNoSjVendor">-</span>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Site / Gudang Lokasi Fisik:</span>
                                    <span class="badge bg-light text-dark border px-2 py-1" id="infoSite">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-info-circle text-primary me-2"></i>Identitas Retur &amp; Skema</h6>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Nomor Retur PO:</span>
                                    <span class="fw-bold font-monospace fs-6 text-primary" id="infoNomorReturCard">-</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Tanggal Pengajuan Retur:</span>
                                    <span class="fw-semibold text-dark" id="infoTanggalRetur">-</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted small d-block mb-1">Skema Kompensasi:</span>
                                    <div id="infoKompensasiBadge" class="fw-bold fs-6">-</div>
                                </div>
                                <div>
                                    <span class="text-muted small d-block mb-1">Status Dokumen:</span>
                                    <div id="infoStatusCardBadge"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TAB 2: VENDOR -->
                <!-- ========================================================= -->
                <div class="tab-pane fade" id="tab-vendor" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-building text-primary me-2"></i>Perusahaan Vendor</h6>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Nama Vendor Rekanan:</span>
                                    <span class="fw-bold text-dark fs-6" id="infoVendor">-</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">No. Telepon / WhatsApp:</span>
                                    <span class="fw-semibold font-monospace text-dark" id="infoTeleponVendor">-</span>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Email Resmi Vendor:</span>
                                    <span class="text-dark" id="infoEmailVendor">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-person-badge text-primary me-2"></i>Person In Charge (PIC) Vendor</h6>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Nama PIC Vendor:</span>
                                    <span class="fw-bold text-dark fs-6" id="infoPicVendor">-</span>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Keterangan Vendor:</span>
                                    <span class="text-muted small">Pihak perwakilan vendor rekanan yang telah dihubungi dan mengonfirmasi proses klaim pengembalian barang.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TAB 3: PENGIRIMAN -->
                <!-- ========================================================= -->
                <div class="tab-pane fade" id="tab-pengiriman" role="tabpanel">
                    <div class="row g-4">
                        <!-- Jalur & Armada Pengiriman -->
                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-truck text-primary me-2"></i>Metode &amp; Armada Pengiriman</h6>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Jalur &amp; Penanggung Jawab Pengiriman:</span>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6 font-monospace" id="infoPengiriman">-</span>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Biaya Pengiriman Retur (IDR):</span>
                                    <span class="fw-bold fs-5 font-monospace text-dark" id="infoBiayaRetur">Rp 0</span>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Catatan Pengiriman:</span>
                                    <span class="text-dark small" id="infoKeterangan">-</span>
                                </div>
                            </div>
                        </div>

                        <!-- Dokumen Surat Jalan & Faktur Pajak -->
                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-card-checklist text-primary me-2"></i>Dokumen Surat Jalan &amp; Faktur</h6>
                                <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <span class="text-muted small d-block">No. Surat Jalan Pengembalian / Retur Fisik:</span>
                                        <span class="fw-bold font-monospace fs-6 text-dark" id="infoNoSjRetur">-</span>
                                    </div>
                                    <a href="<?= BASE_URL ?>/admin/pages/retur_po/print_sj.php?id=<?= $idRetur ?>" target="_blank" class="btn btn-outline-primary btn-sm px-3 fw-semibold shadow-sm">
                                        <i class="bi bi-printer me-1"></i> SJ Retur
                                    </a>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">No. Nota Retur Pajak (e-Faktur):</span>
                                    <span class="fw-bold font-monospace fs-6 text-dark" id="infoNoNotaPajak">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TAB 4: RINCIAN BARANG -->
                <!-- ========================================================= -->
                <div class="tab-pane fade" id="tab-items" role="tabpanel">
                    <!-- Ringkasan Finansial -->
                    <div class="row g-3 mb-4">
                        <div class="col-lg-4 col-md-6">
                            <div class="p-3 border rounded-3 bg-light h-100">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Subtotal Nilai Barang (DPP):</span>
                                    <span class="fw-bold font-monospace text-dark" id="infoSubtotal">Rp 0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Pajak PPN (<span id="infoRatePajak">0</span>%):</span>
                                    <span class="fw-bold font-monospace text-primary" id="infoNominalPajak">Rp 0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="p-3 border rounded-3 bg-light h-100">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">Biaya Pengiriman Retur:</span>
                                    <span class="fw-bold font-monospace text-dark" id="infoBiayaReturTab4">Rp 0</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">Armada Pengiriman:</span>
                                    <span class="fw-semibold text-primary small" id="infoPengirimanTab4">-</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12">
                            <div class="p-3 border rounded-3 bg-primary-subtle border-primary-subtle h-100 d-flex flex-column justify-content-center">
                                <span class="text-primary-emphasis small fw-bold d-block">Total Nilai Klaim Retur:</span>
                                <span class="fs-4 fw-bold text-primary font-monospace" id="infoGrandTotal">Rp 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Rincian Barang -->
                    <div class="table-responsive border rounded-3">
                        <table class="table table-hover align-middle mb-0" id="tableDetailItems">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th style="width: 45px;" class="text-center">No</th>
                                    <th style="min-width: 220px;">Nama Barang</th>
                                    <th style="width: 110px;" class="text-center">Qty Retur</th>
                                    <th style="width: 90px;" class="text-center">Satuan</th>
                                    <th style="width: 140px;" class="text-end">Harga Satuan</th>
                                    <th style="width: 150px;" class="text-end">Subtotal</th>
                                    <th style="width: 80px;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="itemsDetailBody">
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat rincian barang...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TAB 5: PERSETUJUAN -->
                <!-- ========================================================= -->
                <div class="tab-pane fade" id="tab-persetujuan" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-white">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-shield-check text-success me-2"></i>Pejabat Penyetuju (Approval)</h6>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Pejabat Penyetuju:</span>
                                    <span class="fw-bold text-dark fs-6" id="metaPenyetuju">-</span>
                                </div>
                                <div>
                                    <span class="text-muted small d-block">Diajukan oleh Logistik:</span>
                                    <span class="fw-semibold text-dark" id="metaPembuat">-</span> pada <span id="metaTanggal">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="border rounded-3 p-3 h-100 bg-light">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-arrow-repeat text-primary me-2"></i>Status Dokumen Terkini</h6>
                                <div class="mb-3">
                                    <span class="text-muted small d-block mb-1">Status Retur PO:</span>
                                    <div id="infoStatusLargeBadge"></div>
                                </div>
                                <div class="mt-4 pt-2 border-top">
                                    <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" onclick="openUpdateStatusModal()">
                                        <i class="bi bi-pencil-square me-1"></i> Perbarui Status Dokumen
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- MODAL UPDATE STATUS & TRACKING -->
<div class="modal fade" id="modalUpdateStatus" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-arrow-repeat text-primary me-2"></i>Tindak Lanjut &amp; Status Retur PO</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formUpdateStatus" onsubmit="submitStatusUpdate(event)">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">Status Dokumen Retur <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="updateStatusSelect" onchange="onUpdateStatusChange()" required>
                            <option value="DRAFT">DRAFT (Draft Pengajuan)</option>
                            <option value="MENUNGGU KONFIRMASI VENDOR">MENUNGGU KONFIRMASI VENDOR</option>
                            <option value="DISETUJUI VENDOR">DISETUJUI VENDOR (Klaim Diterima)</option>
                            <option value="TIDAK DISETUJUI VENDOR">TIDAK DISETUJUI VENDOR (Klaim Ditolak)</option>
                            <option value="DIKIRIM KE VENDOR">DIKIRIM KE VENDOR (Barang Dalam Perjalanan)</option>
                            <option value="DITERIMA">DITERIMA / SELESAI (Kompensasi Tuntas)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">PIC Vendor</label>
                        <input type="text" class="form-control form-control-sm" id="updatePicVendor" placeholder="Nama PIC vendor yang menyetujui">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">No. SJ Pengembalian</label>
                            <input type="text" class="form-control form-control-sm" id="updateNoSjRetur" placeholder="SJ-RET-XXXX">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">No. Nota Retur Pajak</label>
                            <input type="text" class="form-control form-control-sm" id="updateNoNotaPajak" placeholder="Untuk e-Faktur">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Catatan Perkembangan Klaim</label>
                        <textarea class="form-control form-control-sm" id="updateKeterangan" rows="2" placeholder="Tuliskan catatan respon vendor..."></textarea>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-2 border-top">
                        <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                            <i class="bi bi-check2-circle me-1"></i> Simpan Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL RINCIAN & TINDAK LANJUT BARANG (ACTION POPUP) -->
<div class="modal fade" id="modalItemAction" tabindex="-1" aria-labelledby="modalItemActionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-white pt-3 pb-0 px-4 border-bottom flex-column align-items-stretch">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-box-seam-fill text-primary fs-5"></i>
                        <h5 class="modal-title fw-bold text-dark font-monospace mb-0" id="modalItemActionNama">
                            Nama Barang
                        </h5>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace" id="modalItemActionKode">-</span>
                        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>

                <ul class="nav nav-tabs border-bottom-0" id="modalItemActionTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-dark small py-2 px-3" id="tab-item-info-btn" data-bs-toggle="tab" data-bs-target="#tab-item-info" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Informasi &amp; Kerusakan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="tab-item-foto-btn" data-bs-toggle="tab" data-bs-target="#tab-item-foto" type="button" role="tab">
                            <i class="bi bi-image me-1 text-primary"></i> 2. Bukti Foto
                            <span class="badge bg-primary text-white ms-1" id="modalItemActionFotoBadge" style="display:none;">1</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation" id="tabItemGantiNav">
                        <button class="nav-link fw-bold text-dark small py-2 px-3" id="tab-item-ganti-btn" data-bs-toggle="tab" data-bs-target="#tab-item-ganti" type="button" role="tab">
                            <i class="bi bi-arrow-repeat me-1 text-primary"></i> 3. Unit Pengganti
                        </button>
                    </li>
                </ul>
            </div>

            <div class="modal-body p-4 bg-light">
                <input type="hidden" id="modalItemActionIdDetail">
                <div class="tab-content" id="modalItemActionTabContent">
                    
                    <!-- TAB 1: INFORMASI KERUSAKAN -->
                    <div class="tab-pane fade show active" id="tab-item-info" role="tabpanel">
                        <div class="card bg-white border-0 shadow-sm rounded-3 p-3">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-muted d-block mb-1">Qty Retur</label>
                                    <span class="fw-bold fs-6 font-monospace text-dark" id="modalItemActionQty">-</span>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-muted d-block mb-1">Harga Satuan</label>
                                    <span class="fw-bold font-monospace text-dark" id="modalItemActionHarga">-</span>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold text-muted d-block mb-1">Subtotal Nilai</label>
                                    <span class="fw-bold font-monospace text-success" id="modalItemActionSubtotal">-</span>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-muted d-block mb-1">Alasan Pengembalian</label>
                                <div id="modalItemActionAlasanBadge">-</div>
                            </div>
                            <div>
                                <label class="form-label small fw-semibold text-muted d-block mb-1">Keterangan / Rincian Kerusakan Fisik</label>
                                <div class="p-3 bg-light rounded-2 text-dark small border" id="modalItemActionKet">-</div>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: BUKTI FOTO -->
                    <div class="tab-pane fade" id="tab-item-foto" role="tabpanel">
                        <div class="card bg-white border-0 shadow-sm rounded-3 p-3 text-center">
                            <div id="modalItemActionFotoWrapper" style="display:none;">
                                <img id="modalItemActionFotoImg" src="" alt="Bukti Foto Kerusakan" class="img-fluid rounded border shadow-sm" style="max-height: 380px; object-fit: contain;">
                            </div>
                            <div id="modalItemActionFotoEmpty" class="py-5 text-muted">
                                <i class="bi bi-image fs-1 d-block mb-2 text-secondary"></i>
                                Tidak ada lampiran foto bukti kerusakan fisik untuk barang ini.
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: UNIT PENGGANTI -->
                    <div class="tab-pane fade" id="tab-item-ganti" role="tabpanel">
                        <div class="card bg-white border-0 shadow-sm rounded-3 p-3">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-box-arrow-in-down text-primary me-2"></i>Penerimaan Unit Pengganti dari Vendor</h6>
                            <div id="gantiTukarUnitSection">
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Jumlah Unit Barang Pengganti yang Telah Diterima</label>
                                    <div class="input-group" style="max-width: 250px;">
                                        <input type="number" step="any" min="0" class="form-control fw-bold font-monospace text-center fs-6" id="modalItemActionInputQtyGanti" value="0">
                                        <span class="input-group-text fw-semibold text-muted font-monospace" id="modalItemActionMaxQty">/ 0 Unit</span>
                                    </div>
                                    <div class="form-text small text-muted">Masukkan jumlah barang pengganti kondisi baru/bagus yang telah dikirimkan oleh vendor.</div>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold" onclick="saveItemUnitPengganti()">
                                    <i class="bi bi-save me-1"></i> Simpan Jumlah Pengganti
                                </button>
                            </div>
                            <div id="gantiPotongTagihanSection" style="display:none;" class="p-3 bg-light rounded text-muted small">
                                <i class="bi bi-info-circle me-1 text-primary"></i> Dokumen ini menggunakan skema <strong>Potong Tagihan (Credit Note)</strong>, sehingga tidak ada pengiriman unit pengganti fisik dari vendor.
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer bg-white py-3 px-4 border-top">
                <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold ms-auto" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ZOOM FOTO BUKTI -->
<div class="modal fade" id="modalZoomFoto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <h6 class="modal-title fw-bold"><i class="bi bi-image text-primary me-2"></i>Foto Bukti Kerusakan Fisik</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="zoomImg" src="" class="img-fluid rounded" style="max-height: 500px;" alt="Bukti Foto">
            </div>
        </div>
    </div>
</div>

<script>
const returId = <?= $idRetur ?>;
let returDetailData = null;
let activeItemDetail = null;

document.addEventListener('DOMContentLoaded', () => {
    loadReturDetail();
});

async function loadReturDetail() {
    try {
        const res = await apiRequest(`/api/retur_po/index.php?id=${returId}`, 'GET');
        if (res && res.success && res.data) {
            returDetailData = res.data;
            renderDetail(res.data);
        } else {
            showToast(res.message || 'Gagal memuat detail Retur PO.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
    }
}

function renderDetail(d) {
    document.getElementById('headerNomorRetur').textContent = d.nomor_po_retur || '-';
    document.getElementById('headerStatusBadge').innerHTML = getStatusBadge(d.status);
    
    // Tab 1: Informasi Utama
    document.getElementById('infoNoPo').textContent = d.nomor_po || '-';
    document.getElementById('infoNoRcv').textContent = d.nomor_rcv || '-';
    document.getElementById('infoNoSjVendor').textContent = d.nomor_sj || '-';
    document.getElementById('infoSite').textContent = d.nama_site || '-';
    document.getElementById('infoNomorReturCard').textContent = d.nomor_po_retur || '-';
    document.getElementById('infoTanggalRetur').textContent = formatShortDate(d.tanggal_po_retur);
    if (document.getElementById('infoStatusCardBadge')) {
        document.getElementById('infoStatusCardBadge').innerHTML = getStatusBadge(d.status);
    }

    // Tab 2: Vendor
    document.getElementById('infoVendor').textContent = d.nama_vendor || '-';
    document.getElementById('infoPicVendor').textContent = d.pic_vendor || '-';
    document.getElementById('infoTeleponVendor').textContent = d.telepon_vendor || '-';
    document.getElementById('infoEmailVendor').textContent = d.email_vendor || '-';

    // Tab 3: Pengiriman
    document.getElementById('infoPengiriman').textContent = d.pengiriman_retur ? `Armada ${d.pengiriman_retur}` : '-';
    document.getElementById('infoBiayaRetur').textContent = formatRupiah(d.biaya_retur || 0);
    document.getElementById('infoNoSjRetur').textContent = d.nomor_sj_retur || '-';
    document.getElementById('infoNoNotaPajak').textContent = d.nomor_nota_retur_pajak || '-';
    document.getElementById('infoKeterangan').textContent = d.keterangan || 'Tidak ada catatan tambahan.';

    // Tab 4: Rincian Barang
    const isTukarUnit = (d.kompensasi == 1);
    document.getElementById('infoKompensasiBadge').innerHTML = isTukarUnit
        ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2"><i class="bi bi-arrow-repeat me-1"></i>Tukar Unit (Ganti Barang Baru)</span>'
        : '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2"><i class="bi bi-cash-coin me-1"></i>Potong Tagihan (Credit Note)</span>';

    const subtotal = parseFloat(d.total) || 0;
    const ratePajak = parseInt(d.rate_pajak) || 0;
    const nominalPajak = parseFloat(d.nominal_pajak) || (subtotal * (ratePajak / 100));
    const grandTotal = subtotal + nominalPajak;

    document.getElementById('infoRatePajak').textContent = ratePajak;
    document.getElementById('infoSubtotal').textContent = formatRupiah(subtotal);
    document.getElementById('infoNominalPajak').textContent = formatRupiah(nominalPajak);
    document.getElementById('infoGrandTotal').textContent = formatRupiah(grandTotal);
    if (document.getElementById('infoBiayaReturTab4')) {
        document.getElementById('infoBiayaReturTab4').textContent = formatRupiah(d.biaya_retur || 0);
    }
    if (document.getElementById('infoPengirimanTab4')) {
        document.getElementById('infoPengirimanTab4').textContent = d.pengiriman_retur ? `Armada ${d.pengiriman_retur}` : '-';
    }

    // Tab 5: Persetujuan
    document.getElementById('metaPembuat').textContent = d.nama_pembuat || 'Logistik';
    document.getElementById('metaTanggal').textContent = formatShortDate(d.tanggal_po_retur);
    document.getElementById('metaPenyetuju').textContent = d.nama_penyetuju ? `${d.nama_penyetuju} (Disetujui)` : 'Belum Disetujui';
    if (document.getElementById('infoStatusLargeBadge')) {
        document.getElementById('infoStatusLargeBadge').innerHTML = getStatusBadge(d.status);
    }

    // Tombol Hapus Draft
    if (d.status === 'DRAFT') {
        document.getElementById('btnDeleteDraft').style.display = 'inline-block';
    } else {
        document.getElementById('btnDeleteDraft').style.display = 'none';
    }

    // Render Items Table
    const tbody = document.getElementById('itemsDetailBody');
    const badgeCount = document.getElementById('badgeItemCount');
    if (!d.items || d.items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada rincian barang.</td></tr>`;
        if (badgeCount) badgeCount.textContent = '0';
        return;
    }

    if (badgeCount) badgeCount.textContent = d.items.length;

    let html = '';
    d.items.forEach((it, idx) => {
        html += `
        <tr>
            <td class="text-center text-muted fw-semibold">${idx + 1}</td>
            <td>
                <div class="fw-bold text-dark">${it.nama_barang}</div>
                <div class="font-monospace text-muted small">${it.kode_barang || '-'}</div>
            </td>
            <td class="text-center fw-bold font-monospace">${it.qty_retur}</td>
            <td class="text-center fw-semibold text-muted small">${it.satuan}</td>
            <td class="text-end font-monospace">${formatRupiah(it.harga_satuan)}</td>
            <td class="text-end font-monospace fw-bold text-dark">${formatRupiah(it.subtotal)}</td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 shadow-sm" onclick="openItemActionModal(${idx})" title="Lihat Rincian, Bukti Foto & Unit Pengganti">
                    <i class="bi bi-eye"></i>
                </button>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;
}

function openItemActionModal(idx) {
    if (!returDetailData || !returDetailData.items || !returDetailData.items[idx]) return;
    const it = returDetailData.items[idx];
    activeItemDetail = it;

    document.getElementById('modalItemActionIdDetail').value = it.id_po_retur_detail;
    document.getElementById('modalItemActionNama').textContent = it.nama_barang || '-';
    document.getElementById('modalItemActionKode').textContent = it.kode_barang || '-';
    document.getElementById('modalItemActionQty').textContent = `${it.qty_retur} ${it.satuan}`;
    document.getElementById('modalItemActionHarga').textContent = formatRupiah(it.harga_satuan);
    document.getElementById('modalItemActionSubtotal').textContent = formatRupiah(it.subtotal);
    document.getElementById('modalItemActionAlasanBadge').innerHTML = getReasonBadge(it.alasan_retur);
    document.getElementById('modalItemActionKet').textContent = it.keterangan_kerusakan || 'Tidak ada keterangan tambahan.';

    // Bukti Foto
    const fotoWrapper = document.getElementById('modalItemActionFotoWrapper');
    const fotoEmpty = document.getElementById('modalItemActionFotoEmpty');
    const fotoBadge = document.getElementById('modalItemActionFotoBadge');
    if (it.foto_url) {
        document.getElementById('modalItemActionFotoImg').src = it.foto_url;
        fotoWrapper.style.display = 'block';
        fotoEmpty.style.display = 'none';
        fotoBadge.style.display = 'inline-block';
    } else {
        fotoWrapper.style.display = 'none';
        fotoEmpty.style.display = 'block';
        fotoBadge.style.display = 'none';
    }

    // Unit Pengganti
    const isTukarUnit = (returDetailData && returDetailData.kompensasi == 1);
    if (isTukarUnit) {
        document.getElementById('gantiTukarUnitSection').style.display = 'block';
        document.getElementById('gantiPotongTagihanSection').style.display = 'none';
        document.getElementById('modalItemActionInputQtyGanti').value = parseFloat(it.qty_diganti) || 0;
        document.getElementById('modalItemActionMaxQty').textContent = `/ ${it.qty_retur} ${it.satuan}`;
    } else {
        document.getElementById('gantiTukarUnitSection').style.display = 'none';
        document.getElementById('gantiPotongTagihanSection').style.display = 'block';
    }

    // Reset ke tab 1 modal item
    const firstTab = new bootstrap.Tab(document.getElementById('tab-item-info-btn'));
    firstTab.show();

    const modal = new bootstrap.Modal(document.getElementById('modalItemAction'));
    modal.show();
}

async function saveItemUnitPengganti() {
    if (!activeItemDetail) return;
    const idDetail = activeItemDetail.id_po_retur_detail;
    const val = document.getElementById('modalItemActionInputQtyGanti').value;
    const qty = parseFloat(val) || 0;

    const payload = {
        id_po_retur: returId,
        items: [{ id_po_retur_detail: idDetail, qty_diganti: qty }]
    };

    try {
        const res = await apiRequest('/api/retur_po/index.php', {
            method: 'PUT',
            body: JSON.stringify(payload)
        });
        if (res && res.success) {
            showToast('Kuantitas unit pengganti berhasil disimpan!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalItemAction')).hide();
            loadReturDetail();
        } else {
            showToast(res.message || 'Gagal menyimpan unit pengganti.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
    }
}

function getReasonBadge(reason) {
    switch (reason) {
        case 'RUSAK_FISIK':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Rusak Fisik / Kirim</span>';
        case 'CACAT_PRODUKSI':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Cacat Pabrik Vendor</span>';
        case 'SALAH_SPESIFIKASI':
            return '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Salah Spesifikasi</span>';
        case 'KURANG_PENGIRIMAN':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Kurang Kuantitas</span>';
        case 'KADALUARSA_EXP':
            return '<span class="badge bg-dark-subtle text-dark border border-dark-subtle px-2 py-1">Kadaluarsa</span>';
        default:
            return `<span class="badge bg-light text-dark">${reason || '-'}</span>`;
    }
}

function getStatusBadge(status) {
    switch (status) {
        case 'DRAFT':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1"><i class="bi bi-pencil me-1"></i>Draft</span>';
        case 'MENUNGGU KONFIRMASI VENDOR':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1"><i class="bi bi-hourglass-split me-1"></i>Menunggu Vendor</span>';
        case 'DISETUJUI VENDOR':
            return '<span class="badge bg-info-subtle text-info border border-info-subtle px-3 py-1"><i class="bi bi-check2-circle me-1"></i>Disetujui Vendor</span>';
        case 'TIDAK DISETUJUI VENDOR':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1"><i class="bi bi-x-circle me-1"></i>Ditolak Vendor</span>';
        case 'DIKIRIM KE VENDOR':
            return '<span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1"><i class="bi bi-truck me-1"></i>Dikirim ke Vendor</span>';
        case 'DITERIMA':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1"><i class="bi bi-check-circle-fill me-1"></i>Selesai / Diterima</span>';
        default:
            return `<span class="badge bg-light text-dark">${status || '-'}</span>`;
    }
}

function openUpdateStatusModal() {
    if (!returDetailData) return;
    document.getElementById('updateStatusSelect').value = returDetailData.status || 'MENUNGGU KONFIRMASI VENDOR';
    document.getElementById('updatePicVendor').value = returDetailData.pic_vendor || '';
    document.getElementById('updateNoSjRetur').value = returDetailData.nomor_sj_retur || '';
    document.getElementById('updateNoNotaPajak').value = returDetailData.nomor_nota_retur_pajak || '';
    document.getElementById('updateKeterangan').value = returDetailData.keterangan || '';
    onUpdateStatusChange();

    const modal = new bootstrap.Modal(document.getElementById('modalUpdateStatus'));
    modal.show();
}

function onUpdateStatusChange() {
    const st = document.getElementById('updateStatusSelect').value;
    const sjInput = document.getElementById('updateNoSjRetur');
    if ((st === 'DISETUJUI VENDOR' || st === 'DIKIRIM KE VENDOR') && !sjInput.value.trim()) {
        const now = new Date();
        const yy = String(now.getFullYear()).slice(-2);
        const mm = String(now.getMonth() + 1).padStart(2, '0');
        const idPad = String(returId).padStart(4, '0');
        sjInput.value = `SJ-RET-${yy}${mm}-${idPad}`;
    }
}

async function submitStatusUpdate(e) {
    e.preventDefault();
    const currentId = returId || (returDetailData ? returDetailData.id_po_retur : parseInt(new URLSearchParams(window.location.search).get('id')));
    const selectedStatus = document.getElementById('updateStatusSelect').value;
    const nomorSjInput = document.getElementById('updateNoSjRetur').value.trim();

    const payload = {
        id_po_retur: currentId,
        id: currentId,
        status: selectedStatus,
        pic_vendor: document.getElementById('updatePicVendor').value.trim(),
        nomor_sj_retur: nomorSjInput,
        nomor_nota_retur_pajak: document.getElementById('updateNoNotaPajak').value.trim(),
        keterangan: document.getElementById('updateKeterangan').value.trim()
    };

    try {
        const res = await apiRequest(`/api/retur_po/index.php?id=${currentId}`, {
            method: 'PUT',
            body: JSON.stringify(payload)
        });
        if (res && res.success) {
            showToast(res.message || 'Status Retur PO berhasil diperbarui!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalUpdateStatus')).hide();
            await loadReturDetail();

            // Jika status DISETUJUI VENDOR atau DIKIRIM KE VENDOR, langsung buka print view template Surat Jalan Retur
            if (selectedStatus === 'DISETUJUI VENDOR' || selectedStatus === 'DIKIRIM KE VENDOR') {
                const printUrl = `<?= BASE_URL ?>/admin/pages/retur_po/print_sj.php?id=${currentId}&autoprint=1`;
                window.open(printUrl, '_blank');
            }
        } else {
            showToast(res.message || 'Gagal memperbarui status.', 'danger');
        }
    } catch (err) {
        showToast('Terjadi kesalahan: ' + err.message, 'danger');
    }
}

function zoomFoto(url) {
    document.getElementById('zoomImg').src = url;
    const modal = new bootstrap.Modal(document.getElementById('modalZoomFoto'));
    modal.show();
}

async function deleteDraft() {
    if (!confirm('Apakah Anda yakin ingin menghapus draft Retur PO ini?')) return;
    try {
        const res = await apiRequest(`/api/retur_po/index.php?id=${returId}`, 'DELETE');
        if (res && res.success) {
            showToast(res.message || 'Draft berhasil dihapus.', 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/admin/pages/retur_po/index.php';
            }, 1000);
        } else {
            showToast(res.message || 'Gagal menghapus draft.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
    }
}

function formatShortDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const month = months[d.getMonth()];
    const year = d.getFullYear();
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');
    return `${day} ${month} ${year} ${hours}:${minutes}`;
}

function formatRupiah(num) {
    return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(num || 0);
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
