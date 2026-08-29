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

<div class="container-fluid px-0">
    <!-- HEADER & ACTION -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Faktur Purchase Order (PO)</h4>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/admin/pages/faktur_po/create.php" class="btn btn-primary btn-sm px-3 shadow-sm fw-semibold">
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
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control form-control-sm border-start-0" id="filterSearch" placeholder="Cari No. Faktur, Invoice Vendor, PO, RCV, Vendor..." oninput="debounceLoadFaktur()">
                    </div>
                </div>
                <!-- Site Filter (Lebih Panjang & Luwes) -->
                <div style="min-width: 220px;">
                    <select class="form-select form-select-sm" id="filterSite" onchange="loadFakturList(1)">
                        <option value="">Semua Site / Gudang</option>
                        <?php foreach ($sites as $s): ?>
                            <option value="<?= $s['id_site'] ?>"><?= htmlspecialchars($s['nama_site']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Status Filter (Lebih Panjang & Luwes) -->
                <div style="min-width: 200px;">
                    <select class="form-select form-select-sm" id="filterStatus" onchange="loadFakturList(1)">
                        <option value="">Semua Status Tagihan</option>
                        <option value="DRAFT">DRAFT</option>
                        <option value="BELUM DIBAYAR">BELUM DIBAYAR</option>
                        <option value="SEBAGIAN DIBAYAR">SEBAGIAN DIBAYAR</option>
                        <option value="LUNAS">LUNAS</option>
                    </select>
                </div>
                <!-- Reset Button -->
                <div>
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3 shadow-none" onclick="resetFilters()" title="Reset Filter">
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
                            <th style="width: 140px;">Nomor Faktur</th>
                            <th style="width: 150px;">Invoice Vendor</th>
                            <th style="width: 160px;">Ref. PO &amp; RCV</th>
                            <th>Vendor Rekanan &amp; Site</th>
                            <th style="width: 150px;">Jatuh Tempo (Aging)</th>
                            <th class="text-end" style="width: 150px;">Total Tagihan</th>
                            <th class="text-center" style="width: 130px;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="fakturTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
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

<script>
let currentPage = 1;
let debounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    loadFakturList(1);
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

        // Aging calculation
        const sisaHari = parseInt(r.sisa_hari_tempo);
        let agingBadge = '';
        if (r.status === 'LUNAS') {
            agingBadge = '<span class="badge bg-success-subtle text-success border px-2 py-1"><i class="bi bi-check-circle me-1"></i>Lunas</span>';
        } else if (sisaHari < 0) {
            agingBadge = `<span class="badge bg-danger-subtle text-danger border px-2 py-1"><i class="bi bi-exclamation-octagon me-1"></i>Terlambat ${Math.abs(sisaHari)} Hari</span>`;
        } else if (sisaHari === 0) {
            agingBadge = `<span class="badge bg-warning-subtle text-warning-emphasis border px-2 py-1"><i class="bi bi-clock me-1"></i>Jatuh Tempo Hari Ini</span>`;
        } else {
            agingBadge = `<span class="badge bg-info-subtle text-info-emphasis border px-2 py-1">Sisa ${sisaHari} Hari</span>`;
        }

        html += `
        <tr>
            <td class="text-center">${no}</td>
            <td>
                <strong class="font-monospace text-primary d-block">${r.nomor_faktur}</strong>
                <span class="text-muted small" style="font-size:0.75rem;">Tgl: ${formatDate(r.tanggal_faktur)}</span>
            </td>
            <td>
                <span class="font-monospace fw-semibold text-dark">${r.nomor_faktur_vendor || '-'}</span>
                ${r.nomor_faktur_pajak ? `<div class="small text-muted font-monospace" style="font-size:0.75rem;">FP: ${r.nomor_faktur_pajak}</div>` : ''}
            </td>
            <td>
                <span class="font-monospace small d-block">PO: <strong>${r.nomor_po}</strong></span>
                <span class="font-monospace small text-muted">RCV: ${r.nomor_rcv}</span>
            </td>
            <td>
                <div class="fw-semibold text-dark">${r.nama_vendor}</div>
                <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i>${r.nama_site}</div>
            </td>
            <td>
                <div class="small fw-semibold text-dark">${formatDate(r.tanggal_jatuh_tempo)}</div>
                <div class="mt-1">${agingBadge}</div>
            </td>
            <td class="text-end font-monospace fw-bold text-dark fs-6">
                ${formatRupiah(totalTagihan)}
            </td>
            <td class="text-center">
                ${getStatusBadge(r.status)}
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
            <td colspan="8" class="text-center py-4 text-muted">
                <i class="bi bi-inbox fs-3 d-block mb-1 text-secondary"></i>
                ${message}
            </td>
        </tr>`;
}

function renderPaginationControls(p) {
    const container = document.getElementById('paginationControls');
    if (!container || p.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = '<ul class="pagination pagination-sm mb-0">';
    html += `<li class="page-item ${p.page <= 1 ? 'disabled' : ''}">
                <button class="page-link" onclick="loadFakturList(${p.page - 1})"><i class="bi bi-chevron-left"></i></button>
             </li>`;

    for (let i = 1; i <= p.total_pages; i++) {
        if (i === 1 || i === p.total_pages || (i >= p.page - 1 && i <= p.page + 1)) {
            html += `<li class="page-item ${i === p.page ? 'active' : ''}">
                        <button class="page-link" onclick="loadFakturList(${i})">${i}</button>
                     </li>`;
        } else if (i === p.page - 2 || i === p.page + 2) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `<li class="page-item ${p.page >= p.total_pages ? 'disabled' : ''}">
                <button class="page-link" onclick="loadFakturList(${p.page + 1})"><i class="bi bi-chevron-right"></i></button>
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
