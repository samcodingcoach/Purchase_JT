<?php
/**
 * Sidebar Component - PT Jaya Teknis
 */
$currentUser = getCurrentUser();
$userRole = $currentUser['role'] ?? '';
$userName = $currentUser['nama'] ?? 'User';
$currentUri = ($_SERVER['SCRIPT_NAME'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? '');
$companyProfile = getCompanyProfile();
$companyName = !empty($companyProfile['nama']) ? $companyProfile['nama'] : 'PT Jaya Teknik';
$companyPicture = !empty($companyProfile['picture']) ? $companyProfile['picture'] : '';

$isBarangActive = (strpos($currentUri, 'barang') !== false || strpos($currentUri, 'kategori') !== false || strpos($currentUri, 'merk') !== false);
?>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar-wrapper app-sidebar" id="sidebarWrapper">
    <!-- Brand / Logo Header Dinamis dari Tabel Profile -->
    <div class="sidebar-brand">
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="brand-link">
            <div class="brand-icon">
                <?php if ($companyPicture): ?>
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyPicture) ?>" alt="Logo" class="rounded" style="width: 100%; height: 100%; object-fit: contain;">
                <?php else: ?>
                    <i class="bi bi-shield-check text-info"></i>
                <?php endif; ?>
            </div>
            <div class="brand-text">
                <span class="brand-title"><?= htmlspecialchars($companyName) ?></span>
                <span class="brand-subtitle">Purchasing &amp; RO System</span>
            </div>
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>
        <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar-link <?= (strpos($currentUri, 'dashboard') !== false) ? 'active' : '' ?>" data-menu-title="Dashboard">
            <i class="bi bi-grid-1x2-fill"></i>
            <span>Dashboard</span>
        </a>

        <div class="nav-section-title mt-2">Operasional</div>
        
        <!-- Request Order Menu (Mekanik, Logistik, Purchasing, Admin) -->
        <a href="<?= BASE_URL ?>/admin/pages/request_order/index.php" class="sidebar-link <?= (strpos($currentUri, 'request_order') !== false && strpos($currentUri, 'create.php') === false) ? 'active' : '' ?>" data-menu-title="Request Order">
            <i class="bi bi-file-earmark-text-fill"></i>
            <span>Request Order (RO)</span>
        </a>

        <?php if (in_array($userRole, [ROLE_MEKANIK, ROLE_ADMIN])): ?>
        <a href="<?= BASE_URL ?>/admin/pages/request_order/create.php" class="sidebar-link <?= (strpos($currentUri, 'create.php') !== false) ? 'active' : '' ?>" style="padding-left: 2rem;" data-menu-title="Buat RO Baru">
            <i class="bi bi-plus-circle"></i>
            <span>Buat RO Baru</span>
        </a>
        <?php endif; ?>

        <?php if ($userRole === ROLE_ADMIN): ?>
        <div class="nav-section-title mt-2">Master Data</div>
        
        <!-- 1. Profil Perusahaan -->
        <a href="<?= BASE_URL ?>/admin/pages/profile/index.php" class="sidebar-link <?= (strpos($currentUri, 'profile') !== false) ? 'active' : '' ?>" data-menu-title="Profil Perusahaan">
            <i class="bi bi-buildings"></i>
            <span>Profil Perusahaan</span>
        </a>

        <!-- 2. Master Divisi -->
        <a href="<?= BASE_URL ?>/admin/pages/divisi/index.php" class="sidebar-link <?= (strpos($currentUri, 'divisi') !== false) ? 'active' : '' ?>" data-menu-title="Master Divisi">
            <i class="bi bi-diagram-3-fill"></i>
            <span>Master Divisi</span>
        </a>

        <!-- 3. Master Jabatan -->
        <a href="<?= BASE_URL ?>/admin/pages/jabatan/index.php" class="sidebar-link <?= (strpos($currentUri, 'jabatan') !== false) ? 'active' : '' ?>" data-menu-title="Master Jabatan">
            <i class="bi bi-briefcase-fill"></i>
            <span>Master Jabatan</span>
        </a>

        <!-- 3. Master Site -->
        <a href="<?= BASE_URL ?>/admin/pages/site/index.php" class="sidebar-link <?= (strpos($currentUri, 'site') !== false) ? 'active' : '' ?>" data-menu-title="Master Site">
            <i class="bi bi-geo-alt-fill"></i>
            <span>Master Site</span>
        </a>

        <!-- 4. Master Karyawan -->
        <a href="<?= BASE_URL ?>/admin/pages/user/index.php" class="sidebar-link <?= (strpos($currentUri, 'pages/user') !== false || strpos($currentUri, 'pages/karyawan') !== false) ? 'active' : '' ?>" data-menu-title="Master Karyawan">
            <i class="bi bi-people-fill"></i>
            <span>Master Karyawan</span>
        </a>

        <!-- 5. Master Vendor -->
        <a href="<?= BASE_URL ?>/admin/pages/vendor/index.php" class="sidebar-link <?= (strpos($currentUri, 'vendor') !== false) ? 'active' : '' ?>" data-menu-title="Master Vendor">
            <i class="bi bi-truck"></i>
            <span>Master Vendor</span>
        </a>

        <!-- 6. Kelompok Master Barang (Expand & Collapse) -->
        <a class="sidebar-link <?= $isBarangActive ? 'active' : '' ?>" data-bs-toggle="collapse" href="javascript:void(0)" data-bs-target="#collapseBarang" role="button" aria-expanded="<?= $isBarangActive ? 'true' : 'false' ?>" aria-controls="collapseBarang" data-menu-title="Master Barang">
            <div class="d-flex align-items-center">
                <i class="bi bi-boxes"></i>
                <span>Master Barang</span>
            </div>
            <i class="bi bi-chevron-down collapse-arrow"></i>
        </a>
        <div class="collapse <?= $isBarangActive ? 'show' : '' ?>" id="collapseBarang">
            <div class="sidebar-submenu">
                <a href="<?= BASE_URL ?>/admin/pages/kategori/index.php" class="sidebar-sublink <?= (strpos($currentUri, 'kategori') !== false) ? 'active' : '' ?>" data-menu-title="Kategori Barang">
                    <i class="bi bi-tags"></i>
                    <span>Kategori Barang</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/pages/merk/index.php" class="sidebar-sublink <?= (strpos($currentUri, 'merk') !== false) ? 'active' : '' ?>" data-menu-title="Merk Barang">
                    <i class="bi bi-bookmark-star"></i>
                    <span>Merk Barang</span>
                </a>
                <a href="<?= BASE_URL ?>/admin/pages/barang/index.php" class="sidebar-sublink <?= (strpos($currentUri, 'pages/barang') !== false) ? 'active' : '' ?>" data-menu-title="Katalog Barang">
                    <i class="bi bi-box-seam"></i>
                    <span>Katalog Barang</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </nav>
</aside>
