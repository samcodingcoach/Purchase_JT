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

<!-- Banner Welcome -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card bg-primary text-white border-0 shadow-sm" style="background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%) !important;">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <span class="badge bg-white text-dark mb-2 px-2 py-1 fw-bold">PORTAL PURCHASING &bull; <?= htmlspecialchars($role) ?></span>
                        <h2 class="fs-4 fw-bold mb-1">Selamat Datang, <?= htmlspecialchars($nama) ?>!</h2>
                        <p class="mb-0 text-white-50 small">Purchase Management System</p>
                    </div>
                    <?php if ($role === ROLE_MEKANIK || $role === ROLE_ADMIN): ?>
                    <a href="<?= BASE_URL ?>/admin/pages/request_order/create.php" class="btn btn-light fw-semibold text-primary px-3 py-2">
                        <i class="bi bi-plus-circle-fill me-1 text-primary"></i> Buat Request Order Baru
                    </a>
                    <?php endif; ?>
                </div>
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
                <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Selesai Diproses</div>
                    <div class="stat-value" id="statApproved">0</div>
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
                    <div class="stat-label">Ready for PO</div>
                    <div class="stat-value" id="statApproved">0</div>
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
                    <div class="stat-label">Proses Logistik</div>
                    <div class="stat-value" id="statProcessing">0</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="stat-card">
                <div class="stat-icon success"><i class="bi bi-check-all"></i></div>
                <div class="stat-details">
                    <div class="stat-label">Ready for PO</div>
                    <div class="stat-value" id="statApproved">0</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Action & Recent RO Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0 fs-6 fw-bold text-dark">
            <i class="bi bi-clock-history me-1 text-primary"></i> Request Order Terbaru
        </h5>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="btn btn-sm btn-outline-primary">
            Lihat Semua RO &rarr;
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-custom mb-0">
                <thead>
                    <tr>
                        <th>No. RO</th>
                        <th>Tanggal</th>
                        <th>Peminta</th>
                        <th>Site / Workshop</th>
                        <th>Vendor Referensi</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody id="recentRoBody">
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="bi bi-info-circle me-1"></i> Data Request Order akan muncul di sini.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
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

document.addEventListener('DOMContentLoaded', loadDashboardStats);
</script>

<?php
require_once __DIR__ . '/components/footer.php';
?>
