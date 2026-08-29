<?php
/**
 * Halaman Daftar Faktur Purchase Order (Faktur Pembelian / 3-Way Matching)
 * Path: admin/pages/faktur_po/index.php
 * Khusus Role: PURCHASING, ADMIN, MANAGER
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);

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

<!-- MODAL DETAIL FAKTUR PO -->
<div class="modal fade" id="modalDetailFaktur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom py-3">
                <div>
                    <h5 class="modal-title fw-bold text-dark mb-0" id="modalDetailTitle">Detail Dokumen Faktur</h5>
                    <div class="small text-muted font-monospace" id="modalDetailSubtitle">-</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="modalDetailBody">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border text-primary me-2"></div> Memuat rincian faktur...
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between">
                <div id="modalDetailFooterLeft"></div>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
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
        } else if (sisaHari < 0) {
            agingBadge = `<span class="badge bg-danger-subtle text-danger border px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i>Terlambat ${Math.abs(sisaHari)} Hari</span>`;
        } else if (sisaHari === 0) {
            agingBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1"><i class="bi bi-clock me-1"></i>Hari Ini</span>`;
        } else {
            agingBadge = `<span class="badge bg-info-subtle text-info-emphasis border px-2 py-1">Sisa ${sisaHari} Hari</span>`;
        }

        html += `
        <tr>
            <td class="text-center">${no}</td>
            <td>
                <strong class="font-monospace text-primary cursor-pointer hover-underline" style="cursor: pointer;" onclick="viewFakturDetail(${r.id_faktur})" title="Klik untuk lihat detail">${r.nomor_faktur}</strong>
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
                    <button type="button" class="btn btn-outline-warning btn-sm px-2 py-1 shadow-none" onclick="editFaktur(${r.id_faktur})" title="Edit Faktur">
                        <i class="bi bi-pencil"></i>
                    </button>
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
            return '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">DRAFT</span>';
        case 'BELUM DIBAYAR':
            return '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-hourglass-split me-1"></i>Belum Dibayar</span>';
        case 'SEBAGIAN DIBAYAR':
            return '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-pie-chart me-1"></i>Sebagian</span>';
        case 'LUNAS':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-all me-1"></i>Lunas</span>';
        case 'BATAL':
            return '<span class="badge bg-danger-subtle text-danger border px-2 py-1">BATAL</span>';
        default:
            return `<span class="badge bg-light text-dark border px-2 py-1">${st}</span>`;
    }
}

async function viewFakturDetail(idFaktur) {
    if (!detailModalInstance) {
        detailModalInstance = new bootstrap.Modal(document.getElementById('modalDetailFaktur'));
    }
    document.getElementById('modalDetailBody').innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary me-2"></div> Memuat rincian faktur...</div>';
    detailModalInstance.show();

    try {
        const res = await fetch(`<?= BASE_URL ?>/api/faktur_po/index.php?id=${idFaktur}`);
        const result = await res.json();

        if (result && result.success && result.data) {
            renderModalDetailContent(result.data);
        } else {
            document.getElementById('modalDetailBody').innerHTML = `<div class="alert alert-danger mb-0">${result.message || 'Gagal memuat data detail faktur.'}</div>`;
        }
    } catch (e) {
        document.getElementById('modalDetailBody').innerHTML = `<div class="alert alert-danger mb-0">Terjadi kesalahan: ${e.message}</div>`;
    }
}

function renderModalDetailContent(d) {
    document.getElementById('modalDetailTitle').textContent = `Faktur PO: ${d.nomor_faktur}`;
    document.getElementById('modalDetailSubtitle').textContent = `No. Invoice Vendor: ${d.nomor_faktur_vendor || '-'} | Status: ${d.status}`;

    const items = d.items || [];
    let itemsHtml = '';
    items.forEach((it, idx) => {
        itemsHtml += `
        <tr>
            <td class="text-center">${idx + 1}</td>
            <td class="font-monospace">${it.kode_barang || '-'}</td>
            <td><strong>${it.nama_barang}</strong></td>
            <td class="text-center font-monospace fw-bold text-success">${parseFloat(it.qty_tagih)}</td>
            <td class="text-center">${it.satuan || 'Unit'}</td>
            <td class="text-end font-monospace">${formatRupiah(it.harga_satuan)}</td>
            <td class="text-end font-monospace">${parseFloat(it.diskon_item) > 0 ? formatRupiah(it.diskon_item) : '-'}</td>
            <td class="text-end font-monospace fw-bold">${formatRupiah(it.subtotal)}</td>
        </tr>`;
    });

    const html = `
    <div class="row g-4">
        <!-- Informasi Header Faktur -->
        <div class="col-lg-6">
            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-file-earmark-text text-primary me-2"></i>Informasi Dokumen &amp; Tagihan</h6>
            <table class="table table-sm table-borderless small mb-0">
                <tr><td class="text-muted" style="width: 140px;">No. Faktur Sistem:</td><td class="font-monospace fw-bold text-primary">${d.nomor_faktur}</td></tr>
                <tr><td class="text-muted">No. Invoice Vendor:</td><td class="font-monospace fw-bold">${d.nomor_faktur_vendor || '-'}</td></tr>
                <tr><td class="text-muted">No. Faktur Pajak:</td><td class="font-monospace">${d.nomor_faktur_pajak || '-'}</td></tr>
                <tr><td class="text-muted">No. Purchase Order:</td><td class="font-monospace fw-semibold">${d.nomor_po || '-'}</td></tr>
                <tr><td class="text-muted">No. Surat Jalan RCV:</td><td class="font-monospace">${d.nomor_sj_rcv || '-'}</td></tr>
                <tr><td class="text-muted">Tgl. Invoice:</td><td>${formatDate(d.tanggal_faktur_vendor || d.tanggal_faktur)}</td></tr>
                <tr><td class="text-muted">Tgl. Jatuh Tempo:</td><td class="fw-bold text-danger">${formatDate(d.tanggal_jatuh_tempo)} (${d.term_of_payment || 0} Hari)</td></tr>
            </table>
        </div>

        <div class="col-lg-6">
            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-building text-primary me-2"></i>Informasi Vendor &amp; Rekening</h6>
            <table class="table table-sm table-borderless small mb-0">
                <tr><td class="text-muted" style="width: 140px;">Nama Vendor:</td><td class="fw-bold text-dark">${d.nama_vendor}</td></tr>
                <tr><td class="text-muted">Site / Gudang:</td><td>${d.nama_site || '-'}</td></tr>
                <tr><td class="text-muted">Nama Bank:</td><td>${d.nama_bank || '-'}</td></tr>
                <tr><td class="text-muted">Nomor Rekening:</td><td class="font-monospace fw-bold text-dark">${d.nomor_rekening || '-'}</td></tr>
                <tr><td class="text-muted">Atas Nama Rekening:</td><td>${d.atas_nama_rekening || '-'}</td></tr>
                <tr><td class="text-muted">Status Dokumen:</td><td>${getStatusBadge(d.status)}</td></tr>
                <tr><td class="text-muted">Dibuat Oleh:</td><td>${d.nama_pembuat || 'Admin'}</td></tr>
            </table>
        </div>

        <!-- Tabel Rincian Barang -->
        <div class="col-12">
            <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom"><i class="bi bi-box-seam text-primary me-2"></i>Rincian Barang Tagihan</h6>
            <div class="table-responsive border rounded-3 mb-3">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.86rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 40px;">No</th>
                            <th style="width: 120px;">Kode Barang</th>
                            <th>Nama Barang</th>
                            <th class="text-center" style="width: 80px;">Kts</th>
                            <th class="text-center" style="width: 70px;">Satuan</th>
                            <th class="text-end" style="width: 130px;">Harga Satuan</th>
                            <th class="text-end" style="width: 110px;">Diskon Item</th>
                            <th class="text-end" style="width: 140px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>${itemsHtml || '<tr><td colspan="8" class="text-center py-3 text-muted">Tidak ada data rincian barang.</td></tr>'}</tbody>
                </table>
            </div>
        </div>

        <!-- Ringkasan Finansial Tagihan -->
        <div class="col-12">
            <div class="row justify-content-end">
                <div class="col-md-6 col-lg-5">
                    <div class="card bg-light border-0 rounded-3 p-3">
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Subtotal Kontrak PO:</span><span class="font-monospace">${formatRupiah(d.subtotal_po)}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-dark fw-semibold">Subtotal Diterima (RCV):</span><span class="font-monospace fw-bold">${formatRupiah(d.subtotal_diterima)}</span></div>
                        ${parseFloat(d.nilai_retur) > 0 ? `<div class="d-flex justify-content-between mb-2 small text-danger"><span>Potongan Retur PO:</span><span class="font-monospace fw-bold">- ${formatRupiah(d.nilai_retur)}</span></div>` : ''}
                        ${parseFloat(d.diskon) > 0 ? `<div class="d-flex justify-content-between mb-2 small text-danger"><span>Diskon Tambahan Faktur:</span><span class="font-monospace fw-bold">- ${formatRupiah(d.diskon)}</span></div>` : ''}
                        <div class="d-flex justify-content-between mb-2 small pt-2 border-top"><span class="fw-bold text-dark">DPP:</span><span class="font-monospace fw-bold text-dark">${formatRupiah(d.dpp)}</span></div>
                        <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">PPN (${d.rate_pajak || 0}%):</span><span class="font-monospace">${formatRupiah(d.nominal_pajak)}</span></div>
                        ${parseFloat(d.biaya_lain) > 0 ? `<div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Biaya Lain / Ongkir:</span><span class="font-monospace">${formatRupiah(d.biaya_lain)}</span></div>` : ''}
                        <div class="d-flex justify-content-between pt-2 border-top border-2 border-dark"><span class="fw-bold fs-6 text-dark">TOTAL TAGIHAN:</span><span class="font-monospace fw-bold text-primary fs-5">${formatRupiah(d.total_tagihan)}</span></div>
                    </div>
                </div>
            </div>
        </div>

        ${d.keterangan ? `
        <div class="col-12">
            <div class="p-3 bg-light rounded-3 small">
                <strong>Catatan Faktur:</strong><br>
                ${d.keterangan.replace(/\n/g, '<br>')}
            </div>
        </div>` : ''}
    </div>`;

    document.getElementById('modalDetailBody').innerHTML = html;
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
