<?php
/**
 * Dashboard Utama - PT Jaya Teknis
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

$user = requireAuth();
$pageTitle = 'Dashboard';
$pageHeading = 'Dashboard Purchasing';

$role = $user['role'];
$nama = $user['nama'];

require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/components/sidebar.php';
require_once __DIR__ . '/components/navbar.php';
?>

<!-- Banner Welcome (Bersih Tanpa Label & Tombol) -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-primary text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%) !important;">
            <div class="card-body p-4">
                <h2 class="fs-4 fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($nama) ?>!</h2>
                <p class="mb-0 text-white-50 small">Purchase Management System</p>
            </div>
        </div>
    </div>
</div>

<?php if ($role === ROLE_ADMIN): ?>
<!-- =============================================================
     STATISTIK MASTER DATA & INVENTARIS (KHUSUS LOGIN ADMIN)
     ============================================================= -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            <i class="bi bi-grid-fill me-2 text-primary"></i>Ringkasan Master Data &amp; Inventaris
        </h5>
    </div>
    
    <div class="row g-3">
        <!-- 1. Jumlah Karyawan Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/user/index.php" class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Karyawan Aktif</div>
                    <div class="stat-value" id="statKaryawanAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 2. Jumlah Barang Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card">
                <div class="stat-icon success"><i class="bi bi-box-seam-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Barang Aktif</div>
                    <div class="stat-value text-success" id="statBarangAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 3. Jumlah Barang Tidak Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card">
                <div class="stat-icon secondary"><i class="bi bi-archive-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Barang Tidak Aktif</div>
                    <div class="stat-value text-muted" id="statBarangNonAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 4. Total Stok -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="stat-card">
                <div class="stat-icon info"><i class="bi bi-boxes"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Total Stok Material (Semua Site)</div>
                    <div class="stat-value text-info" id="statTotalStok"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 5. Jumlah Vendor Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/vendor/index.php" class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-truck"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Vendor Aktif</div>
                    <div class="stat-value text-warning" id="statVendorAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>

        <!-- 6. Jumlah Site Aktif -->
        <div class="col-12 col-sm-6 col-xl-4">
            <a href="<?= BASE_URL ?>/admin/pages/site/index.php" class="stat-card">
                <div class="stat-icon teal"><i class="bi bi-geo-alt-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Jumlah Site Aktif</div>
                    <div class="stat-value text-teal" id="statSiteAktif"><span class="spinner-border spinner-border-sm text-muted"></span></div>
                </div>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- =============================================================
     STATISTIK OPERASIONAL REQUEST ORDER (RO)
     ============================================================= -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            <i class="bi bi-file-earmark-text-fill me-2 text-primary"></i>Status Permintaan Request Order
        </h5>
    </div>

    <div class="row g-3" id="statsRoRow">
        <?php if ($role === ROLE_MEKANIK): ?>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-file-earmark-text"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Draft Saya</div>
                    <div class="stat-value" id="statDraft">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-clock-history"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Menunggu Logistik</div>
                    <div class="stat-value" id="statSubmitted">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Selesai Diproses (Diterima)</div>
                    <div class="stat-value text-success" id="statApproved">0</div>
                </div>
            </div>
        </div>

        <?php elseif ($role === ROLE_LOGISTIK): ?>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-inbox-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Request Masuk</div>
                    <div class="stat-value" id="statSubmitted">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-arrow-repeat"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Sedang Diproses</div>
                    <div class="stat-value" id="statProcessing">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-patch-check-fill"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Selesai (Diterima)</div>
                    <div class="stat-value text-success" id="statApproved">0</div>
                </div>
            </div>
        </div>

        <?php else: // Admin / Purchasing / Manager ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon primary"><i class="bi bi-files"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Total Draft</div>
                    <div class="stat-value" id="statDraft">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon warning"><i class="bi bi-inbox"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Menunggu Logistik</div>
                    <div class="stat-value" id="statSubmitted">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon info"><i class="bi bi-gear-wide-connected"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Sedang Diproses</div>
                    <div class="stat-value text-info" id="statProcessing">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-check-all"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Selesai (Diterima)</div>
                    <div class="stat-value text-success" id="statApproved">0</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($role === ROLE_MEKANIK): ?>
<!-- =============================================================
     KHUSUS MEKANIK: DAFTAR RO BELUM DITERIMA (CARD GRID)
     ============================================================= -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fs-6 fw-bold text-dark mb-0">
            <i class="bi bi-hourglass-split me-2 text-primary"></i>Request Order Saya yang Belum Diterima (In Progress)
        </h5>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-sm btn-outline-primary fw-semibold">
            Lihat Semua RO &rarr;
        </a>
    </div>

    <!-- Grid Card RO Khusus Mekanik -->
    <div class="row g-3" id="pendingRoGrid">
        <div class="col-12 text-center py-5 text-muted bg-white rounded-3 border">
            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat daftar Request Order...
        </div>
    </div>
</div>

<?php else: ?>
<!-- =============================================================
     ROLE SELAIN MEKANIK (ADMIN / LOGISTIK / PURCHASING / MANAGER):
     TABEL MONITORING REQUEST ORDER TERBARU
     ============================================================= -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fs-6 fw-bold text-dark">
            <i class="bi bi-clock-history me-1 text-primary"></i> Request Order Terbaru
        </h5>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-sm btn-outline-primary fw-semibold">
            Lihat Semua RO &rarr;
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-custom mb-0">
                <thead class="table-light small text-muted text-uppercase">
                    <tr>
                        <th style="width: 140px;">No. RO</th>
                        <th style="width: 110px;">Tanggal</th>
                        <th>Peminta</th>
                        <th>Site / Workshop</th>
                        <th>Vendor Referensi</th>
                        <th style="width: 150px;" class="text-center">Status</th>
                        <th class="text-end" style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="recentRoBody">
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data Request Order...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const USER_ROLE = '<?= $role ?>';

async function loadDashboardStats() {
    try {
        const res = await apiRequest('/api/dashboard/stats.php');
        if (res && res.success) {
            const d = res.data;
            
            // Render Master Data Stats (Khusus Admin)
            if (d.master_admin) {
                const ma = d.master_admin;
                const elKaryawan = document.getElementById('statKaryawanAktif');
                const elBarangAktif = document.getElementById('statBarangAktif');
                const elBarangNonAktif = document.getElementById('statBarangNonAktif');
                const elTotalStok = document.getElementById('statTotalStok');
                const elVendorAktif = document.getElementById('statVendorAktif');
                const elSiteAktif = document.getElementById('statSiteAktif');

                if (elKaryawan) elKaryawan.textContent = ma.karyawan_aktif.toLocaleString('id-ID');
                if (elBarangAktif) elBarangAktif.textContent = ma.barang_aktif.toLocaleString('id-ID');
                if (elBarangNonAktif) elBarangNonAktif.textContent = ma.barang_nonaktif.toLocaleString('id-ID');
                if (elTotalStok) elTotalStok.textContent = ma.total_stok.toLocaleString('id-ID');
                if (elVendorAktif) elVendorAktif.textContent = ma.vendor_aktif.toLocaleString('id-ID');
                if (elSiteAktif) elSiteAktif.textContent = ma.site_aktif.toLocaleString('id-ID');
            }

            // Render Request Order Stats
            if (d.request_order) {
                const ro = d.request_order;
                const elDraft = document.getElementById('statDraft');
                const elSub = document.getElementById('statSubmitted');
                const elProc = document.getElementById('statProcessing');
                const elApp = document.getElementById('statApproved');

                if (elDraft) elDraft.textContent = ro.draft.toLocaleString('id-ID');
                if (elSub) elSub.textContent = ro.submitted.toLocaleString('id-ID');
                if (elProc) elProc.textContent = ro.processing.toLocaleString('id-ID');
                if (elApp) elApp.textContent = ro.approved.toLocaleString('id-ID');
            }
        }
    } catch (err) {
        console.error('Error loading dashboard stats:', err);
    }
}

// Khusus Mekanik: Load Card Grid
async function loadPendingRoCards() {
    const grid = document.getElementById('pendingRoGrid');
    if (!grid) return;

    try {
        const res = await apiRequest('/api/dashboard/pending_ro.php');
        if (!res || !res.success || !Array.isArray(res.data) || res.data.length === 0) {
            grid.innerHTML = `
                <div class="col-12 text-center py-5 text-muted bg-white rounded-3 border">
                    <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                    <h6 class="fw-bold text-dark mb-1">Semua Request Order Telah Selesai Diterima!</h6>
                    <p class="small text-muted mb-0">Tidak ada Request Order aktif yang belum diproses atau belum diterima.</p>
                </div>
            `;
            return;
        }

        let html = '';
        res.data.forEach(ro => {
            const noRo = escapeHtml(ro.nomor || '-');
            const tgl = ro.tanggal_ro ? ro.tanggal_ro.split(' ')[0] : '-';
            const peminta = escapeHtml(ro.nama_karyawan || 'Peminta');
            const jabatan = escapeHtml(ro.nama_jabatan || (ro.nama_divisi || ''));
            const site = escapeHtml(ro.nama_site || '-');
            const totalItem = parseInt(ro.total_item) || 0;
            const totalQty = parseFloat(ro.total_qty) || 0;
            const status = ro.status || 'DRAFT';
            const isUrgent = (ro.prioritas === 'URGENT' || ro.prioritas === 'TINGGI');

            // Status Badge
            let badgeHtml = '';
            if (status === 'DRAFT') {
                badgeHtml = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1"><i class="bi bi-pencil me-1"></i>Draft</span>';
            } else if (status === 'TERKIRIM') {
                badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Menunggu Logistik</span>';
            } else if (status === 'DISETUJUI LOGISTIK') {
                badgeHtml = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1"><i class="bi bi-cart-plus me-1"></i>Menunggu Purchasing</span>';
            } else if (status === 'DISETUJUI PURCHASING') {
                badgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check2-circle me-1"></i>PO Terbit</span>';
            } else {
                badgeHtml = `<span class="badge bg-light text-dark border px-2 py-1">${escapeHtml(status)}</span>`;
            }

            html += `
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm rounded-3 transition-hover" style="border-top: 3px solid #1e5288 !important;">
                        <div class="card-body p-3 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <span class="font-monospace fw-bold text-primary fs-6">${noRo}</span>
                                    <div class="small text-muted font-monospace" style="font-size: 0.75rem;">
                                        <i class="bi bi-calendar-event me-1"></i>${tgl}
                                    </div>
                                </div>
                                <div>
                                    ${badgeHtml}
                                </div>
                            </div>

                            <div class="bg-light rounded-3 p-2 my-2 small">
                                <div class="d-flex align-items-center mb-1 text-truncate">
                                    <i class="bi bi-person-fill text-muted me-2"></i>
                                    <strong class="text-dark me-1">${peminta}</strong>
                                    ${jabatan ? `<span class="text-muted" style="font-size: 0.72rem;">(${jabatan})</span>` : ''}
                                </div>
                                <div class="d-flex align-items-center text-truncate">
                                    <i class="bi bi-geo-alt-fill text-muted me-2"></i>
                                    <span class="text-secondary">${site}</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center small text-muted mb-3 mt-1">
                                <div>
                                    <i class="bi bi-boxes me-1 text-primary"></i>
                                    <span class="fw-semibold text-dark">${totalItem}</span> Item (${totalQty} qty)
                                </div>
                                ${isUrgent ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.68rem;"><i class="bi bi-exclamation-triangle-fill me-1"></i>Urgent</span>` : ''}
                                ${ro.nomor_po ? `<span class="badge bg-light text-primary border font-monospace" style="font-size: 0.7rem;">PO: ${escapeHtml(ro.nomor_po)}</span>` : ''}
                            </div>

                            <div class="mt-auto pt-2 border-top d-flex justify-content-end">
                                <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-outline-primary btn-sm px-3 py-1 fw-semibold w-100" style="font-size: 0.8rem;">
                                    Lihat Rincian RO &rarr;
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        grid.innerHTML = html;
    } catch (err) {
        console.error('Error loading pending RO cards:', err);
        grid.innerHTML = '<div class="col-12 text-center py-4 text-danger bg-white rounded-3 border">Gagal memuat data Request Order.</div>';
    }
}

// Khusus Non-Mekanik: Load Tabel RO Terbaru
async function loadRecentRoTable() {
    const tbody = document.getElementById('recentRoBody');
    if (!tbody) return;

    try {
        const res = await apiRequest('/api/request_order/index.php?limit=8');
        if (!res || !res.success || !res.data || !Array.isArray(res.data.items) || res.data.items.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="bi bi-inbox fs-4 d-block mb-1"></i>
                        Belum ada data Request Order.
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        res.data.items.forEach(ro => {
            const noRo = escapeHtml(ro.nomor || '-');
            const tgl = ro.tanggal_ro ? ro.tanggal_ro.split(' ')[0] : '-';
            const peminta = escapeHtml(ro.nama_karyawan || '-');
            const site = escapeHtml(ro.nama_site || '-');
            const vendor = escapeHtml(ro.nama_vendor || '-');
            const status = ro.status || 'DRAFT';

            let badgeHtml = '';
            if (status === 'DRAFT') {
                badgeHtml = '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Draft</span>';
            } else if (status === 'TERKIRIM') {
                badgeHtml = '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">Menunggu Logistik</span>';
            } else if (status === 'DISETUJUI LOGISTIK') {
                badgeHtml = '<span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">Menunggu Purchasing</span>';
            } else if (status === 'DISETUJUI PURCHASING') {
                badgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">PO Terbit</span>';
            } else if (status === 'DITERIMA FULL') {
                badgeHtml = '<span class="badge bg-success text-white border px-2 py-1">Diterima Full</span>';
            } else if (status === 'DITERIMA SEBAGIAN') {
                badgeHtml = '<span class="badge bg-warning text-dark border px-2 py-1">Diterima Sebagian</span>';
            } else {
                badgeHtml = `<span class="badge bg-light text-dark border px-2 py-1">${escapeHtml(status)}</span>`;
            }

            html += `
                <tr>
                    <td><strong class="font-monospace text-primary">${noRo}</strong></td>
                    <td class="small text-muted font-monospace">${tgl}</td>
                    <td class="fw-semibold text-dark">${peminta}</td>
                    <td><span class="badge bg-light text-dark border font-monospace">${site}</span></td>
                    <td class="small text-muted">${vendor}</td>
                    <td class="text-center">${badgeHtml}</td>
                    <td class="text-end">
                        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-outline-primary btn-sm py-0 px-2" style="font-size: 0.75rem;">
                            Detail
                        </a>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = html;
    } catch (err) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-danger">Gagal memuat data Request Order.</td></tr>';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardStats();
    if (USER_ROLE === 'MEKANIK') {
        loadPendingRoCards();
    } else {
        loadRecentRoTable();
    }
});
</script>

<?php
require_once __DIR__ . '/components/footer.php';
?>
