<?php
/**
 * Komponen Footer Admin - PT Jaya Teknik
 */
$companyProfile = getCompanyProfile();
$companyName = !empty($companyProfile['nama']) ? $companyProfile['nama'] : 'PT Jaya Teknik';
$companyAddress = !empty($companyProfile['alamat']) ? $companyProfile['alamat'] : '';
$companyCity = !empty($companyProfile['kota']) ? $companyProfile['kota'] : '';
$fullLocation = trim($companyAddress . ($companyCity ? ', ' . $companyCity : ''));
?>
    </main> <!-- End .content-body -->

    <footer class="py-3 px-4 bg-white border-top text-muted small text-center text-md-start d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <div>
            &copy; <?= date('Y') ?> <strong><?= htmlspecialchars($companyName) ?></strong> &bull; Modul Request Order (Purchasing)
        </div>
        <div class="text-muted">
            <i class="bi bi-geo-alt me-1 text-primary"></i><?= htmlspecialchars($fullLocation ?: 'Sistem Internal Galangan & Bengkel Kapal') ?>
        </div>
    </footer>
</div> <!-- End #main-content -->
</div> <!-- End #app-wrapper -->

<!-- Toast Notification Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="appToast" class="toast align-items-center text-white border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="toastMessage">
                <i class="bi bi-info-circle-fill fs-5" id="toastIcon"></i>
                <span id="toastText">Notifikasi</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Global Application Script -->
<script>
// Mobile Sidebar Toggle
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarWrapper') || document.querySelector('.app-sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar && backdrop) {
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
    }
}

// Desktop Collapsible Sidebar (Expand / Collapse)
function toggleSidebarCollapse() {
    const wrapper = document.getElementById('app-wrapper');
    if (!wrapper) return;
    wrapper.classList.toggle('sidebar-collapsed');
    const isCollapsed = wrapper.classList.contains('sidebar-collapsed');
    localStorage.setItem('jt_sidebar_collapsed', isCollapsed ? '1' : '0');
}

// Inisialisasi awal status collapsed sidebar
(function() {
    if (localStorage.getItem('jt_sidebar_collapsed') === '1') {
        document.getElementById('app-wrapper')?.classList.add('sidebar-collapsed');
    }
})();

// Multi-Tab Workspace Manager (ERP Style)
const AppTabs = {
    storageKey: 'jt_workspace_tabs',
    
    getKnownTabs() {
        return [
            { id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false },
            { id: 'request_order', title: 'Request Order', url: BASE_URL + '/admin/pages/request_order/index.php', icon: 'bi-file-earmark-text-fill', closable: true },
            { id: 'ro_create', title: 'Buat RO Baru', url: BASE_URL + '/admin/pages/request_order/create.php', icon: 'bi-plus-circle', closable: true },
            { id: 'profile', title: 'Profil Perusahaan', url: BASE_URL + '/admin/pages/profile/index.php', icon: 'bi-buildings', closable: true },
            { id: 'divisi', title: 'Master Divisi', url: BASE_URL + '/admin/pages/divisi/index.php', icon: 'bi-diagram-3-fill', closable: true },
            { id: 'jabatan', title: 'Master Jabatan', url: BASE_URL + '/admin/pages/jabatan/index.php', icon: 'bi-briefcase-fill', closable: true },
            { id: 'site', title: 'Master Site', url: BASE_URL + '/admin/pages/site/index.php', icon: 'bi-geo-alt-fill', closable: true },
            { id: 'karyawan', title: 'Master Karyawan', url: BASE_URL + '/admin/pages/user/index.php', icon: 'bi-people-fill', closable: true },
            { id: 'vendor', title: 'Master Vendor', url: BASE_URL + '/admin/pages/vendor/index.php', icon: 'bi-truck', closable: true },
            { id: 'kategori', title: 'Kategori Barang', url: BASE_URL + '/admin/pages/kategori/index.php', icon: 'bi-tags', closable: true },
            { id: 'merk', title: 'Merk Barang', url: BASE_URL + '/admin/pages/merk/index.php', icon: 'bi-bookmark-star', closable: true },
            { id: 'barang', title: 'Katalog Barang', url: BASE_URL + '/admin/pages/barang/index.php', icon: 'bi-box-seam', closable: true }
        ];
    },

    getCurrentTabInfo() {
        const path = window.location.pathname.replace(/\/+$/, '');
        const known = this.getKnownTabs();
        
        for (const t of known) {
            const tPath = new URL(t.url, window.location.origin).pathname.replace(/\/+$/, '');
            if (path === tPath || (t.id === 'karyawan' && path.includes('/user/')) || (t.id === 'barang' && path.includes('/barang/')) || (t.id === 'ro_create' && path.includes('/create.php'))) {
                return t;
            }
        }
        
        return {
            id: 'tab_' + Math.abs(path.split('').reduce((a,b)=>{a=((a<<5)-a)+b.charCodeAt(0);return a&a},0)),
            title: document.title.split('-')[0].trim() || 'Halaman',
            url: window.location.href,
            icon: 'bi-window-sidebar',
            closable: true
        };
    },

    getOpenedTabs() {
        try {
            const raw = sessionStorage.getItem(this.storageKey);
            let tabs = raw ? JSON.parse(raw) : [];
            if (!Array.isArray(tabs) || tabs.length === 0) {
                tabs = [{ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false }];
            }
            return tabs;
        } catch (e) {
            return [{ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false }];
        }
    },

    saveOpenedTabs(tabs) {
        sessionStorage.setItem(this.storageKey, JSON.stringify(tabs));
    },

    init() {
        const container = document.getElementById('appTabsContainer');
        if (!container) return;

        const currentTab = this.getCurrentTabInfo();
        let openedTabs = this.getOpenedTabs();

        // Dashboard selalu ada di awal
        if (!openedTabs.some(t => t.id === 'dashboard')) {
            openedTabs.unshift({ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false });
        }

        // Tambahkan tab aktif saat ini jika belum terdaftar
        const existingIdx = openedTabs.findIndex(t => t.id === currentTab.id);
        if (existingIdx === -1) {
            openedTabs.push(currentTab);
        } else {
            openedTabs[existingIdx].url = currentTab.url;
        }
        this.saveOpenedTabs(openedTabs);

        // Render Bar Tabs
        let html = '';
        openedTabs.forEach(tab => {
            const isActive = tab.id === currentTab.id;
            html += `
                <a href="${tab.url}" class="app-tab-item ${isActive ? 'active' : ''}" data-tab-id="${tab.id}" title="${tab.title}">
                    <i class="bi ${tab.icon || 'bi-file-earmark'} tab-icon"></i>
                    <span class="app-tab-title">${tab.title}</span>
                    ${tab.closable ? `<span class="app-tab-close" onclick="AppTabs.closeTab('${tab.id}', event)" title="Tutup Tab"><i class="bi bi-x"></i></span>` : ''}
                </a>
            `;
        });
        container.innerHTML = html;

        // Auto Scroll ke tab aktif
        const activeEl = container.querySelector('.app-tab-item.active');
        if (activeEl) {
            activeEl.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
        }
    },

    closeTab(tabId, event) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        let openedTabs = this.getOpenedTabs();
        const currentTab = this.getCurrentTabInfo();
        const targetIdx = openedTabs.findIndex(t => t.id === tabId);
        
        if (targetIdx === -1) return;

        const isClosingActive = (tabId === currentTab.id);
        openedTabs.splice(targetIdx, 1);
        this.saveOpenedTabs(openedTabs);

        if (isClosingActive) {
            // Pindah ke tab tetangga atau Dashboard
            const nextTab = openedTabs[targetIdx] || openedTabs[targetIdx - 1] || openedTabs[0];
            if (nextTab) {
                window.location.href = nextTab.url;
            } else {
                window.location.href = BASE_URL + '/admin/dashboard.php';
            }
        } else {
            this.init();
        }
    },

    closeOtherTabs() {
        const currentTab = this.getCurrentTabInfo();
        let openedTabs = [{ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false }];
        if (currentTab.id !== 'dashboard') {
            openedTabs.push(currentTab);
        }
        this.saveOpenedTabs(openedTabs);
        this.init();
    },

    closeAllTabs() {
        const openedTabs = [{ id: 'dashboard', title: 'Dashboard', url: BASE_URL + '/admin/dashboard.php', icon: 'bi-grid-1x2-fill', closable: false }];
        this.saveOpenedTabs(openedTabs);
        window.location.href = BASE_URL + '/admin/dashboard.php';
    }
};

// Global Toast Notification Helper
function showToast(message, type = 'success') {
    const toastEl = document.getElementById('appToast');
    const toastText = document.getElementById('toastText');
    const toastIcon = document.getElementById('toastIcon');
    
    if (!toastEl || !toastText) return;
    
    toastText.textContent = message;
    
    toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-primary');
    toastIcon.className = 'fs-5 bi ';
    
    if (type === 'success') {
        toastEl.classList.add('bg-success');
        toastIcon.classList.add('bi-check-circle-fill');
    } else if (type === 'error' || type === 'danger') {
        toastEl.classList.add('bg-danger');
        toastIcon.classList.add('bi-exclamation-triangle-fill');
    } else if (type === 'warning') {
        toastEl.classList.add('bg-warning', 'text-dark');
        toastIcon.classList.add('bi-exclamation-circle-fill');
    } else {
        toastEl.classList.add('bg-primary');
        toastIcon.classList.add('bi-info-circle-fill');
    }
    
    const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
}

// Global Logout Handler
async function handleLogout() {
    if (!confirm('Apakah Anda yakin ingin keluar dari sistem?')) return;
    
    try {
        const res = await fetch(BASE_URL + '/api/auth/logout.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + API_TOKEN,
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = data.data.redirect_url || (BASE_URL + '/admin/login.php');
        } else {
            showToast(data.message || 'Gagal logout.', 'error');
        }
    } catch (err) {
        window.location.href = BASE_URL + '/admin/login.php';
    }
}

// Centralized Fetch API Wrapper
async function apiRequest(endpoint, options = {}) {
    const defaultHeaders = {
        'Authorization': 'Bearer ' + API_TOKEN,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
    
    options.headers = Object.assign({}, defaultHeaders, options.headers || {});
    
    try {
        const response = await fetch(BASE_URL + endpoint, options);
        const data = await response.json();
        
        if (response.status === 401) {
            showToast('Sesi Anda telah berakhir. Mengalihkan ke login...', 'warning');
            setTimeout(() => { window.location.href = BASE_URL + '/admin/login.php'; }, 1500);
            return null;
        }
        
        return data;
    } catch (error) {
        console.error('API Request Error:', error);
        showToast('Terjadi kesalahan koneksi ke server.', 'error');
        return { success: false, message: 'Kesalahan koneksi ke server.' };
    }
}

// Inisialisasi Event Listener
document.addEventListener('DOMContentLoaded', () => {
    // Inisialisasi Workspace Multi-Tab
    AppTabs.init();

    // Pulihkan posisi scroll sidebar
    const sidebarNav = document.querySelector('.sidebar-nav');
    if (sidebarNav) {
        const savedScrollPos = sessionStorage.getItem('jt_sidebar_scroll_top');
        if (savedScrollPos !== null) {
            sidebarNav.scrollTop = parseInt(savedScrollPos, 10);
        } else {
            const activeItem = sidebarNav.querySelector('.sidebar-link.active, .sidebar-sublink.active');
            if (activeItem) {
                activeItem.scrollIntoView({ block: 'nearest', behavior: 'auto' });
            }
        }

        let scrollTimer;
        sidebarNav.addEventListener('scroll', () => {
            sidebarNav.classList.add('is-scrolling');
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                sidebarNav.classList.remove('is-scrolling');
            }, 800);
            sessionStorage.setItem('jt_sidebar_scroll_top', sidebarNav.scrollTop);
        }, { passive: true });

        const sidebarLinks = sidebarNav.querySelectorAll('a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', () => {
                sessionStorage.setItem('jt_sidebar_scroll_top', sidebarNav.scrollTop);
            });
        });
    }
});
</script>
</body>
</html>
