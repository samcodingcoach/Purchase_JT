<?php
/**
 * Komponen Navbar / Topbar Admin - PT Jaya Teknis
 */
$pageHeading = $pageHeading ?? 'Dashboard';
$userName = $_SESSION['nama'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'MEKANIK';
?>
<div id="main-content">
    <header class="app-topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-outline-secondary d-lg-none p-1 px-2 border-0" type="button" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                <i class="bi bi-list fs-4"></i>
            </button>
            <h1 class="topbar-title mb-0 fs-5"><?= htmlspecialchars($pageHeading) ?></h1>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="d-none d-md-flex align-items-center gap-2 text-muted small">
                <span class="badge bg-light text-dark border px-2 py-1">Role: <strong><?= htmlspecialchars($userRole) ?></strong></span>
            </div>

            <div class="dropdown">
                <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2 border" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle text-primary"></i>
                    <span class="d-none d-sm-inline fw-semibold"><?= htmlspecialchars($userName) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li><h6 class="dropdown-header">Login sebagai: <?= htmlspecialchars($userRole) ?></h6></li>
                    <?php if ($userRole === ROLE_ADMIN): ?>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2" href="<?= BASE_URL ?>/admin/pages/profile/index.php">
                            <i class="bi bi-buildings"></i> Profil Perusahaan
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item text-danger d-flex align-items-center gap-2" href="javascript:void(0)" onclick="handleLogout()">
                            <i class="bi bi-box-arrow-right"></i> Keluar (Logout)
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="content-body">
