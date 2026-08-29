<?php
/**
 * Halaman Edit Dokumen Faktur Purchase Order (Faktur Pembelian)
 * Path: admin/pages/faktur_po/edit.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

$pageTitle = 'Edit Faktur PO';
$pageHeading = 'Formulir Edit Faktur Pembelian';

$idFaktur = isset($_GET['id']) ? intval($_GET['id']) : (isset($_GET['id_faktur']) ? intval($_GET['id_faktur']) : 0);

if ($idFaktur <= 0) {
    header('Location: ' . BASE_URL . '/admin/pages/faktur_po/index.php');
    exit;
}

// Ambil Detail Faktur PO
$sql = "SELECT fp.*,
               po.nomor_po, po.tanggal_po,
               rcv.nomor_rcv, rcv.nomor_sj AS nomor_sj_rcv, rcv.tanggal_diterima AS tanggal_rcv_diterima,
               rp.nomor_po_retur, rp.kompensasi AS retur_kompensasi, rp.total AS retur_total,
               v.kode_vendor, v.nama_perusahaan AS nama_vendor, v.no_telepon AS telepon_vendor, v.email AS email_vendor,
               s.nama_site, s.kode_site
        FROM faktur_po fp
        JOIN purchase_order po ON fp.id_po = po.id_po
        JOIN receiving_order rcv ON fp.id_rcv = rcv.id_rcv
        LEFT JOIN retur_po rp ON (rp.id_rcv = fp.id_rcv OR rp.id_po = fp.id_po)
        JOIN vendor v ON fp.id_vendor = v.id_vendor
        JOIN site s ON fp.id_site = s.id_site
        WHERE fp.id_faktur = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idFaktur);
$stmt->execute();
$faktur = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$faktur) {
    header('Location: ' . BASE_URL . '/admin/pages/faktur_po/index.php');
    exit;
}

// Cek aturan bisnis: Faktur dengan status SEBAGIAN DIBAYAR, LUNAS, atau BATAL tidak boleh diedit
$statusUpper = strtoupper((string)$faktur['status']);
$isLocked = in_array($statusUpper, ['SEBAGIAN DIBAYAR', 'LUNAS', 'BATAL']) || floatval($faktur['terbayar']) > 0;

// Ambil Rincian Barang Faktur
$sqlD = "SELECT fpd.*, b.kode_barang, b.nama_barang, b.satuan AS satuan_master,
                kat.nama_kategori, mrk.nama_merk
         FROM faktur_po_detail fpd
         JOIN barang b ON fpd.id_barang = b.id_barang
         LEFT JOIN kategori_barang kat ON b.id_kategori = kat.id_kategori
         LEFT JOIN merk_barang mrk ON b.id_merk = mrk.id_merk
         WHERE fpd.id_faktur = ?
         ORDER BY fpd.id_faktur_detail ASC";
$stmtD = $conn->prepare($sqlD);
$stmtD->bind_param("i", $idFaktur);
$stmtD->execute();
$items = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtD->close();

$faktur['items'] = $items;

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<style>
/* Standardize Height of all form inputs & input-groups */
.form-control,
.form-control-sm,
.form-select,
.form-select-sm,
.input-group > .form-control,
.input-group > .btn,
.input-group > .input-group-text,
.input-group-sm > .form-control,
.input-group-sm > .btn,
.input-group-sm > .input-group-text {
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
            <h4 class="fw-bold text-dark mb-0">Edit Faktur Purchase Order (PO)</h4>
            <div class="small text-muted mt-1">
                No. Faktur Sistem: <strong class="font-monospace text-primary"><?= htmlspecialchars($faktur['nomor_faktur']) ?></strong> | Status Saat Ini: <span class="badge bg-secondary"><?= $faktur['status'] ?></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/faktur_po/index.php" class="btn btn-outline-secondary btn-sm px-3 shadow-sm" style="height: 38px; display: inline-flex; align-items: center;">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
            </a>
        </div>
    </div>

    <?php if ($isLocked): ?>
        <div class="alert alert-warning border-0 shadow-sm rounded-3 d-flex align-items-center mb-4 p-3">
            <i class="bi bi-lock-fill text-warning-emphasis fs-4 me-3"></i>
            <div>
                <strong class="d-block text-warning-emphasis">Dokumen Faktur Terkunci (Hanya Baca)</strong>
                Dokumen faktur ini berstatus <strong><?= $faktur['status'] ?></strong> (atau telah memiliki riwayat pembayaran). Perubahan dokumen faktur tidak diizinkan.
            </div>
        </div>
    <?php endif; ?>

    <form id="formEditFaktur" onsubmit="event.preventDefault();">
        <input type="hidden" id="editIdFaktur" name="id_faktur" value="<?= $faktur['id_faktur'] ?>">
        <input type="hidden" id="selectRcv" name="id_rcv" value="<?= $faktur['id_rcv'] ?>">
        <input type="hidden" id="hiddenIdPo" name="id_po" value="<?= $faktur['id_po'] ?>">
        <input type="hidden" id="hiddenIdVendor" name="id_vendor" value="<?= $faktur['id_vendor'] ?>">
        <input type="hidden" id="hiddenIdSite" name="id_site" value="<?= $faktur['id_site'] ?>">

        <!-- CARD 1: 5 TAB MODULAR -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="fakturTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold py-3 px-3" id="tab-dokumen-btn" data-bs-toggle="tab" data-bs-target="#tab-dokumen" type="button" role="tab">
                            <i class="bi bi-file-earmark-text me-1 text-primary"></i> 1. Dokumen Asal
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-vendor-btn" data-bs-toggle="tab" data-bs-target="#tab-vendor" type="button" role="tab">
                            <i class="bi bi-building me-1 text-primary"></i> 2. Vendor &amp; Rekening Bank
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-tagihan-btn" data-bs-toggle="tab" data-bs-target="#tab-tagihan" type="button" role="tab">
                            <i class="bi bi-receipt me-1 text-primary"></i> 3. Tagihan &amp; Pajak
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-barang-btn" data-bs-toggle="tab" data-bs-target="#tab-barang" type="button" role="tab">
                            <i class="bi bi-box-seam me-1 text-primary"></i> 4. Rincian Barang <span class="badge bg-primary ms-1" id="badgeItemCount"><?= count($items) ?> Item</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold py-3 px-3" id="tab-catatan-btn" data-bs-toggle="tab" data-bs-target="#tab-catatan" type="button" role="tab">
                            <i class="bi bi-card-text me-1 text-primary"></i> 5. Catatan Faktur
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="fakturTabContent">
                    
                    <!-- TAB 1: DOKUMEN ASAL -->
                    <div class="tab-pane fade show active" id="tab-dokumen" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Dokumen Penerimaan Barang</h6>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">Dokumen Penerimaan (RCV)</label>
                                    <input type="text" class="form-control font-monospace bg-light fw-bold text-primary" value="<?= htmlspecialchars($faktur['nomor_rcv']) ?>" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold text-dark">No. Surat Jalan Vendor (RCV)</label>
                                    <input type="text" class="form-control font-monospace bg-light" value="<?= htmlspecialchars($faktur['nomor_sj_rcv'] ?: '-') ?>" readonly>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Referensi Dokumen</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">No. Purchase Order (PO)</label>
                                        <input type="text" class="form-control font-monospace bg-light fw-bold text-primary" value="<?= htmlspecialchars($faktur['nomor_po']) ?>" readonly>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Penerimaan di Gudang</label>
                                        <input type="text" class="form-control bg-light" value="<?= date('d/m/Y', strtotime($faktur['tanggal_rcv_diterima'] ?: $faktur['created_at'])) ?>" readonly>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Lokasi Site / Gudang</label>
                                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($faktur['nama_site']) ?>" readonly>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-vendor')">
                                Lanjut ke Vendor &amp; Rekening Bank <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 2: VENDOR & REKENING BANK -->
                    <div class="tab-pane fade" id="tab-vendor" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Informasi Rekening Bank Tujuan Transfer</h6>
                        <div class="row g-3">
                            <div class="col-sm-4 position-relative" id="bankComboboxWrapper">
                                <label class="form-label small fw-semibold text-dark">Nama Bank / Metode Bayar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace fw-semibold" id="namaBank" name="nama_bank" value="<?= htmlspecialchars($faktur['nama_bank'] ?: '') ?>" placeholder="Pilih atau ketik bank..." autocomplete="off" onfocus="showBankDropdown()" oninput="filterBankDropdown()" <?= $isLocked ? 'readonly' : '' ?>>
                                    <button class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split px-3" type="button" onclick="toggleBankDropdown(event)" title="Pilih Bank" <?= $isLocked ? 'disabled' : '' ?>></button>
                                </div>
                                <?php if (!$isLocked): ?>
                                <!-- Dropdown List Bank -->
                                <div class="dropdown-menu shadow-sm w-100 p-1" id="bankDropdownMenu" style="max-height: 220px; overflow-y: auto; display: none; position: absolute; top: calc(100% + 2px); left: 0; z-index: 1050;">
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('CASH')">CASH</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('QRIS')">QRIS</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BRI')">BRI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('Bank Mandiri')">Bank Mandiri</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BCA')">BCA</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BNI')">BNI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BTN')">BTN</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BRIS')">BRIS</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('BSI')">BSI</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('CIMB Niaga')">CIMB Niaga</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('OCBC')">OCBC</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('Bank Permata')">Bank Permata</button>
                                    <button type="button" class="dropdown-item py-1 small rounded bank-opt" onclick="selectBank('Danamon')">Danamon</button>
                                    <div id="noBankFound" class="text-muted small px-3 py-2 d-none">Tekan Enter atau gunakan nama yang diketik manual.</div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-sm-8">
                                <label class="form-label small fw-semibold text-dark">Nomor Rekening</label>
                                <input type="text" class="form-control font-monospace fw-bold" id="nomorRekening" name="nomor_rekening" value="<?= htmlspecialchars($faktur['nomor_rekening'] ?: '') ?>" placeholder="Nomor Rekening Vendor" <?= $isLocked ? 'readonly' : '' ?>>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-dark">Atas Nama Rekening</label>
                                <input type="text" class="form-control fw-semibold" id="atasNamaRekening" name="atas_nama_rekening" value="<?= htmlspecialchars($faktur['atas_nama_rekening'] ?: '') ?>" placeholder="Nama Pemilik Rekening sesuai Invoice" <?= $isLocked ? 'readonly' : '' ?>>
                                <div class="form-text small text-muted">Data rekening otomatis dimuat dari master vendor, namun dapat disesuaikan jika tertulis nomor rekening khusus pada lembar invoice vendor.</div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-dokumen')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Dokumen Asal
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-tagihan')">
                                Lanjut ke Tagihan &amp; Pajak <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 3: TAGIHAN & PAJAK -->
                    <div class="tab-pane fade" id="tab-tagihan" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Nomor &amp; Waktu Tagihan Vendor</h6>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Nomor Faktur / Invoice Vendor <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm font-monospace fw-bold text-dark" id="nomorFakturVendor" name="nomor_faktur_vendor" value="<?= htmlspecialchars($faktur['nomor_faktur_vendor'] ?: '') ?>" placeholder="Contoh: INV-2026/08/991" required <?= $isLocked ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">No. Seri e-Faktur Pajak</label>
                                        <input type="text" class="form-control form-control-sm font-monospace" id="nomorFakturPajak" name="nomor_faktur_pajak" value="<?= htmlspecialchars($faktur['nomor_faktur_pajak'] ?: '') ?>" placeholder="Contoh: 010.000-26.12345678" <?= $isLocked ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Invoice Vendor <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="tanggalFaktur" name="tanggal_faktur" value="<?= $faktur['tanggal_faktur_vendor'] ?: date('Y-m-d') ?>" onchange="calculateDueDate()" required <?= $isLocked ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Terima Fisik Tagihan <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control form-control-sm" id="tanggalTerimaFaktur" name="tanggal_terima_faktur" value="<?= $faktur['tanggal_terima_faktur_vendor'] ?: date('Y-m-d') ?>" required <?= $isLocked ? 'readonly' : '' ?>>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Terms of Payment (TOP)</label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" min="0" class="form-control form-control-sm text-center fw-bold" id="termOfPayment" name="term_of_payment" value="<?= $faktur['term_of_payment'] ?: 30 ?>" oninput="calculateDueDate()" <?= $isLocked ? 'readonly' : '' ?>>
                                            <span class="input-group-text">Hari</span>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label small fw-semibold text-dark">Tanggal Jatuh Tempo</label>
                                        <input type="text" class="form-control form-control-sm fw-bold text-danger bg-light" id="displayJatuhTempo" readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Lampiran Berkas Tagihan (Opsional)</h6>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Ganti Scan Invoice / Tagihan Vendor</label>
                                        <input type="file" class="form-control form-control-sm" id="fileFakturVendor" accept="image/*,application/pdf" onchange="handleFileBase64(this, 'hiddenFileVendorBase64')" <?= $isLocked ? 'disabled' : '' ?>>
                                        <input type="hidden" id="hiddenFileVendorBase64" name="file_faktur_vendor_base64">
                                        <?php if (!empty($faktur['file_faktur_vendor'])): ?>
                                            <div class="form-text small text-primary mt-1"><i class="bi bi-file-earmark-check me-1"></i>Berkas saat ini: <a href="<?= BASE_URL ?>/uploads/faktur/<?= htmlspecialchars($faktur['file_faktur_vendor']) ?>" target="_blank"><?= htmlspecialchars($faktur['file_faktur_vendor']) ?></a></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-semibold text-dark">Ganti Scan e-Faktur Pajak</label>
                                        <input type="file" class="form-control form-control-sm" id="fileFakturPajak" accept="image/*,application/pdf" onchange="handleFileBase64(this, 'hiddenFilePajakBase64')" <?= $isLocked ? 'disabled' : '' ?>>
                                        <input type="hidden" id="hiddenFilePajakBase64" name="file_faktur_pajak_base64">
                                        <?php if (!empty($faktur['file_faktur_pajak'])): ?>
                                            <div class="form-text small text-primary mt-1"><i class="bi bi-file-earmark-check me-1"></i>Berkas saat ini: <a href="<?= BASE_URL ?>/uploads/faktur/<?= htmlspecialchars($faktur['file_faktur_pajak']) ?>" target="_blank"><?= htmlspecialchars($faktur['file_faktur_pajak']) ?></a></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-vendor')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Vendor &amp; Bank
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-barang')">
                                Lanjut ke Rincian Barang <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 4: RINCIAN BARANG -->
                    <div class="tab-pane fade" id="tab-barang" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="fw-bold text-dark mb-0">Rincian Kuantitas &amp; Nilai Barang Tagihan</h6>
                        </div>
                        
                        <div class="table-responsive mb-0 border rounded-3">
                            <table class="table table-hover align-middle mb-0" id="tableMatchingItems" style="font-size: 0.86rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center" style="width: 40px;">No</th>
                                        <th style="width: 120px;">Kode Barang</th>
                                        <th>Nama Barang</th>
                                        <th class="text-center" style="width: 95px;">Kts</th>
                                        <th class="text-center" style="width: 80px;">Satuan</th>
                                        <th class="text-end" style="width: 140px;">Harga Satuan</th>
                                        <th class="text-end" style="width: 120px;">Diskon Item</th>
                                        <th class="text-end" style="width: 150px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody id="matchingItemsBody">
                                    <?php foreach ($items as $idx => $it): ?>
                                        <tr>
                                            <td class="text-center"><?= $idx + 1 ?></td>
                                            <td class="font-monospace text-center"><?= htmlspecialchars($it['kode_barang'] ?: '-') ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($it['nama_barang']) ?></strong>
                                                <?php if (!empty($it['nama_kategori'])): ?>
                                                    <div class="small text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($it['nama_kategori']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center font-monospace fw-bold text-success fs-6"><?= (float)$it['qty_tagih'] ?></td>
                                            <td class="text-center"><?= htmlspecialchars($it['satuan'] ?: 'Unit') ?></td>
                                            <td class="text-end font-monospace"><?= 'Rp ' . number_format((float)$it['harga_satuan'], 0, ',', '.') ?></td>
                                            <td class="text-end font-monospace text-muted"><?= (float)$it['diskon_item'] > 0 ? 'Rp ' . number_format((float)$it['diskon_item'], 0, ',', '.') : '-' ?></td>
                                            <td class="text-end font-monospace fw-bold text-dark"><?= 'Rp ' . number_format((float)$it['subtotal'], 0, ',', '.') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-tagihan')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Tagihan &amp; Pajak
                            </button>
                            <button type="button" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" onclick="goToTab('tab-catatan')">
                                Lanjut ke Catatan Faktur <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </div>
                    </div>

                    <!-- TAB 5: CATATAN FAKTUR -->
                    <div class="tab-pane fade" id="tab-catatan" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Catatan &amp; Instruksi Khusus Faktur</h6>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-dark">Catatan Faktur Pembelian (Internal &amp; Pelunasan)</label>
                            <textarea class="form-control" id="keteranganFaktur" name="keterangan" rows="6" placeholder="Tambahkan catatan..." <?= $isLocked ? 'readonly' : '' ?>><?= htmlspecialchars($faktur['keterangan'] ?: '') ?></textarea>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-start">
                            <button type="button" class="btn btn-outline-secondary btn-sm px-3" onclick="goToTab('tab-barang')">
                                <i class="bi bi-arrow-left me-1"></i> Kembali ke Rincian Barang
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- CARD 2: RINGKASAN FINANSIAL & PEMBAYARAN -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-bottom p-3">
                <h6 class="fw-bold text-dark mb-0">Ringkasan Finansial Tagihan</h6>
            </div>
            
            <div class="card-body p-4">
                <div class="row justify-content-end">
                    <div class="col-lg-6 col-md-8">
                        <div class="card bg-light border-0 rounded-3 p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-muted">Subtotal Kontrak PO (Ref):</span>
                                <span class="font-monospace fw-semibold" id="displaySubtotalPo">Rp <?= number_format((float)$faktur['subtotal_po'], 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-dark fw-semibold">Subtotal Barang Diterima (RCV):</span>
                                <span class="font-monospace fw-bold text-dark" id="displaySubtotalDiterima">Rp <?= number_format((float)$faktur['subtotal_diterima'], 0, ',', '.') ?></span>
                            </div>
                            <?php if ((float)$faktur['nilai_retur'] > 0): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 small text-danger" id="rowNilaiRetur">
                                    <span>Potongan Retur PO (Credit Note):</span>
                                    <span class="font-monospace fw-bold" id="displayNilaiRetur">- Rp <?= number_format((float)$faktur['nilai_retur'], 0, ',', '.') ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <span class="text-muted">Diskon Tambahan Faktur:</span>
                                <div class="input-group input-group-sm" style="max-width: 170px;">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" step="any" class="form-control form-control-sm text-end font-monospace" id="inputDiskon" value="<?= (float)$faktur['diskon'] ?>" oninput="calculateFinancials()" <?= $isLocked ? 'readonly' : '' ?>>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small pt-2 border-top">
                                <span class="fw-bold text-dark">DPP (Dasar Pengenaan Pajak):</span>
                                <span class="font-monospace fw-bold text-dark fs-6" id="displayDpp">Rp <?= number_format((float)$faktur['dpp'], 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2 small">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted">Tarif PPN:</span>
                                    <select class="form-select form-select-sm py-0" id="selectRatePajak" style="width: 80px;" onchange="calculateFinancials()" <?= $isLocked ? 'disabled' : '' ?>>
                                        <option value="0" <?= ((int)$faktur['rate_pajak'] === 0) ? 'selected' : '' ?>>0%</option>
                                        <option value="11" <?= ((int)$faktur['rate_pajak'] === 11) ? 'selected' : '' ?>>11%</option>
                                        <option value="12" <?= ((int)$faktur['rate_pajak'] === 12) ? 'selected' : '' ?>>12%</option>
                                    </select>
                                </div>
                                <span class="font-monospace fw-semibold" id="displayNominalPajak">Rp <?= number_format((float)$faktur['nominal_pajak'], 0, ',', '.') ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-3 small">
                                <span class="text-muted">Biaya Lain-lain / Ongkir:</span>
                                <div class="input-group input-group-sm" style="max-width: 170px;">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" min="0" step="any" class="form-control form-control-sm text-end font-monospace" id="inputBiayaLain" value="<?= (float)$faktur['biaya_lain'] ?>" oninput="calculateFinancials()" <?= $isLocked ? 'readonly' : '' ?>>
                                </div>
                            </div>

                            <!-- GRAND TOTAL -->
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top border-2 border-dark">
                                <span class="fw-bold text-dark fs-6">TOTAL TAGIHAN:</span>
                                <span class="font-monospace fw-bold text-primary fs-5" id="displayTotalTagihan">Rp <?= number_format((float)$faktur['total_tagihan'], 0, ',', '.') ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!$isLocked): ?>
                    <!-- Tombol Aksi Simpan Edit -->
                    <div class="mt-4 pt-3 border-top d-flex justify-content-end align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm fw-semibold" id="btnSaveDraft" onclick="submitEditFaktur('DRAFT')">
                            Simpan Sebagai Draft
                        </button>
                        <button type="button" class="btn btn-primary btn-sm px-4 shadow-sm fw-semibold" id="btnSaveFaktur" onclick="submitEditFaktur('BELUM DIBAYAR')">
                            Simpan Perubahan Faktur
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<script>
const initialFakturData = <?= json_encode($faktur, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

document.addEventListener('DOMContentLoaded', () => {
    calculateDueDate();
    calculateFinancials();

    document.addEventListener('click', (e) => {
        const bankWrapper = document.getElementById('bankComboboxWrapper');
        if (bankWrapper && !bankWrapper.contains(e.target)) {
            hideBankDropdown();
        }
    });
});

function showBankDropdown() {
    const menu = document.getElementById('bankDropdownMenu');
    if (menu) menu.style.display = 'block';
}

function hideBankDropdown() {
    const menu = document.getElementById('bankDropdownMenu');
    if (menu) menu.style.display = 'none';
}

function toggleBankDropdown(e) {
    if (e) e.stopPropagation();
    const menu = document.getElementById('bankDropdownMenu');
    if (menu) {
        menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
    }
}

function filterBankDropdown() {
    const query = (document.getElementById('namaBank').value || '').toLowerCase().trim();
    const menu = document.getElementById('bankDropdownMenu');
    if (menu) menu.style.display = 'block';

    const items = document.querySelectorAll('.bank-opt');
    let visibleCount = 0;
    items.forEach(el => {
        const text = el.textContent.toLowerCase();
        if (text.includes(query)) {
            el.style.display = 'block';
            visibleCount++;
        } else {
            el.style.display = 'none';
        }
    });

    const noFound = document.getElementById('noBankFound');
    if (noFound) {
        if (visibleCount === 0) {
            noFound.classList.remove('d-none');
        } else {
            noFound.classList.add('d-none');
        }
    }
}

function selectBank(val) {
    document.getElementById('namaBank').value = val;
    hideBankDropdown();
}

function calculateDueDate() {
    const tgl = document.getElementById('tanggalFaktur').value;
    const top = parseInt(document.getElementById('termOfPayment').value) || 0;
    const display = document.getElementById('displayJatuhTempo');

    if (!tgl) {
        display.value = '-';
        return;
    }
    const d = new Date(tgl);
    d.setDate(d.getDate() + top);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    display.value = `${dd}/${mm}/${yyyy} (${top} Hari)`;
}

function calculateFinancials() {
    const subPo = parseFloat(initialFakturData.subtotal_po) || 0;
    const subRcv = parseFloat(initialFakturData.subtotal_diterima) || 0;
    const nilaiRetur = parseFloat(initialFakturData.nilai_retur) || 0;
    const diskon = parseFloat(document.getElementById('inputDiskon').value) || 0;
    const biayaLain = parseFloat(document.getElementById('inputBiayaLain').value) || 0;
    const ratePajak = parseInt(document.getElementById('selectRatePajak').value) || 0;

    const dpp = Math.max(0, subRcv - nilaiRetur - diskon);
    const nominalPajak = dpp * (ratePajak / 100);
    const grandTotal = dpp + nominalPajak + biayaLain;

    document.getElementById('displaySubtotalPo').textContent = formatRupiah(subPo);
    document.getElementById('displaySubtotalDiterima').textContent = formatRupiah(subRcv);
    if (document.getElementById('displayNilaiRetur')) {
        document.getElementById('displayNilaiRetur').textContent = `- ${formatRupiah(nilaiRetur)}`;
    }
    document.getElementById('displayDpp').textContent = formatRupiah(dpp);
    document.getElementById('displayNominalPajak').textContent = formatRupiah(nominalPajak);
    document.getElementById('displayTotalTagihan').textContent = formatRupiah(grandTotal);
}

function goToTab(tabId) {
    const btn = document.querySelector(`[data-bs-target="#${tabId}"]`);
    if (btn) {
        const tab = new bootstrap.Tab(btn);
        tab.show();
    }
}

function handleFileBase64(input, targetHiddenId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById(targetHiddenId).value = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        document.getElementById(targetHiddenId).value = '';
    }
}

async function submitEditFaktur(statusDokumen) {
    const nomorFakturVendor = document.getElementById('nomorFakturVendor').value.trim();
    if (!nomorFakturVendor) {
        showToast('Nomor Faktur / Invoice Vendor wajib diisi pada Tab 3.', 'warning');
        goToTab('tab-tagihan');
        document.getElementById('nomorFakturVendor').focus();
        return;
    }

    const diskon = parseFloat(document.getElementById('inputDiskon').value) || 0;
    const biayaLain = parseFloat(document.getElementById('inputBiayaLain').value) || 0;
    const ratePajak = parseInt(document.getElementById('selectRatePajak').value) || 0;
    const subRcv = parseFloat(initialFakturData.subtotal_diterima) || 0;
    const subPo = parseFloat(initialFakturData.subtotal_po) || 0;
    const nilaiRetur = parseFloat(initialFakturData.nilai_retur) || 0;
    const dpp = Math.max(0, subRcv - nilaiRetur - diskon);
    const nominalPajak = dpp * (ratePajak / 100);
    const grandTotal = dpp + nominalPajak + biayaLain;

    const payload = {
        id_faktur: parseInt(document.getElementById('editIdFaktur').value),
        id_rcv: parseInt(document.getElementById('selectRcv').value),
        id_po: parseInt(document.getElementById('hiddenIdPo').value),
        id_vendor: parseInt(document.getElementById('hiddenIdVendor').value),
        id_site: parseInt(document.getElementById('hiddenIdSite').value),
        nomor_faktur_vendor: nomorFakturVendor,
        nomor_faktur_pajak: document.getElementById('nomorFakturPajak').value.trim(),
        tanggal_faktur: document.getElementById('tanggalFaktur').value,
        tanggal_terima_faktur: document.getElementById('tanggalTerimaFaktur').value,
        term_of_payment: parseInt(document.getElementById('termOfPayment').value) || 0,
        nama_bank: document.getElementById('namaBank').value.trim(),
        nomor_rekening: document.getElementById('nomorRekening').value.trim(),
        atas_nama_rekening: document.getElementById('atasNamaRekening').value.trim(),
        subtotal_po: subPo,
        subtotal_diterima: subRcv,
        nilai_retur: nilaiRetur,
        diskon: diskon,
        dpp: dpp,
        rate_pajak: ratePajak,
        nominal_pajak: nominalPajak,
        biaya_lain: biayaLain,
        total_tagihan: grandTotal,
        status: statusDokumen,
        keterangan: document.getElementById('keteranganFaktur').value.trim(),
        file_faktur_vendor_base64: document.getElementById('hiddenFileVendorBase64').value,
        file_faktur_pajak_base64: document.getElementById('hiddenFilePajakBase64').value,
        items: (initialFakturData.items || []).map(it => ({
            id_barang: it.id_barang,
            qty_po: parseFloat(it.qty_po) || 0,
            qty_rcv: parseFloat(it.qty_rcv) || 0,
            qty_retur: parseFloat(it.qty_retur) || 0,
            qty_tagih: parseFloat(it.qty_tagih) || 0,
            satuan: it.satuan || it.satuan_master || 'Unit',
            harga_satuan: parseFloat(it.harga_satuan) || 0,
            diskon_item: parseFloat(it.diskon_item) || 0,
            subtotal: parseFloat(it.subtotal) || 0,
            keterangan: it.keterangan || ''
        }))
    };

    const btnSave = document.getElementById('btnSaveFaktur');
    const btnDraft = document.getElementById('btnSaveDraft');
    if (btnSave) btnSave.disabled = true;
    if (btnDraft) btnDraft.disabled = true;

    try {
        const res = await fetch('<?= BASE_URL ?>/api/faktur_po/index.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const result = await res.json();

        if (result && result.success) {
            showToast(result.message || 'Perubahan Faktur PO berhasil disimpan!', 'success');
            setTimeout(() => {
                window.location.href = '<?= BASE_URL ?>/admin/pages/faktur_po/index.php';
            }, 1200);
        } else {
            showToast(result.message || 'Gagal menyimpan perubahan Faktur PO.', 'danger');
            if (btnSave) btnSave.disabled = false;
            if (btnDraft) btnDraft.disabled = false;
        }
    } catch (e) {
        showToast('Terjadi kesalahan: ' + e.message, 'danger');
        if (btnSave) btnSave.disabled = false;
        if (btnDraft) btnDraft.disabled = false;
    }
}

function formatRupiah(num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
