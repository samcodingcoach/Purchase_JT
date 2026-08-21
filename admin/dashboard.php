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

<!-- Stat Cards Overview -->
<div class="row g-3 mb-4" id="statsRow">
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

<!-- Quick Action & Recent RO Table Placeholder -->
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
    // Akan dimaksimalkan saat modul RO di Fase 4 dan 5
}
document.addEventListener('DOMContentLoaded', loadDashboardStats);
</script>

<?php
require_once __DIR__ . '/components/footer.php';
?>
