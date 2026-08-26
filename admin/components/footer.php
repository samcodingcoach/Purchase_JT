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
            &copy; <?= date('Y') ?> <strong><?= htmlspecialchars($companyName) ?></strong> &bull; Purchase Management System
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

<!-- Modal Konfirmasi Logout & Peringatan Tab Workspace Masih Terbuka -->
<div class="modal fade" id="modalConfirmLogout" tabindex="-1" aria-labelledby="modalConfirmLogoutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="modalConfirmLogoutLabel">
                    <i class="bi bi-box-arrow-right me-2"></i> Konfirmasi Keluar Sistem
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="logoutWarningTabsArea" class="d-none mb-3">
                    <div class="alert alert-warning border-0 d-flex gap-2 align-items-start py-2 px-3 mb-2">
                        <i class="bi bi-exclamation-triangle-fill fs-5 text-warning flex-shrink-0 mt-1"></i>
                        <div>
                            <strong class="d-block text-dark small">Peringatan: Tab Pekerjaan Masih Terbuka!</strong>
                            <span class="small text-muted" style="font-size: 0.8rem;">Terdapat tab halaman yang belum Anda tutup di sesi login ini:</span>
                        </div>
                    </div>
                    <div class="border rounded-3 p-2 bg-light mb-2 overflow-auto" id="logoutOpenTabsList" style="max-height: 180px;">
                        <!-- Open tabs rendered here dynamically -->
                    </div>
                </div>

                <p class="text-secondary small mb-0" id="logoutConfirmMessageText">
                    Apakah Anda yakin ingin mengakhiri sesi dan keluar dari sistem?
                </p>
            </div>
            <div class="modal-footer bg-light py-2 px-3 d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-sm px-3 fw-semibold shadow-sm" onclick="executeLogoutNow()" id="btnConfirmLogoutExecute">
                    <i class="bi bi-box-arrow-right me-1"></i> Tutup Tab &amp; Logout
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- Global Application Script -->
<script>
// Global Utility: Escape HTML Safe
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

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
            { id: 'request_order', title: 'Daftar Request Order', url: BASE_URL + '/admin/pages/request_order/index.php', icon: 'bi-file-earmark-text', closable: true },
            { id: 'ro_edit', title: 'Detail RO', url: BASE_URL + '/admin/pages/request_order/edit.php', icon: 'bi-file-earmark-text-fill', closable: true },
            { id: 'purchase_order', title: 'Purchase Order (PO)', url: BASE_URL + '/admin/pages/purchase_order/index.php', icon: 'bi-cart-check', closable: true },
            { id: 'receiving', title: 'Penerimaan Barang (Receiving)', url: BASE_URL + '/admin/pages/receiving/index.php', icon: 'bi-box-seam', closable: true },
            { id: 'receiving_create', title: 'Terima Barang Baru', url: BASE_URL + '/admin/pages/receiving/create.php', icon: 'bi-box-arrow-in-down', closable: true },
            { id: 'receiving_edit', title: 'Edit Penerimaan', url: BASE_URL + '/admin/pages/receiving/edit.php', icon: 'bi-pencil-square', closable: true },
            { id: 'ro_create', title: 'Buat RO Baru', url: BASE_URL + '/admin/pages/request_order/create.php', icon: 'bi-file-earmark-plus', closable: true },
            { id: 'proses_po', title: 'Proses PO', url: BASE_URL + '/admin/pages/request_order/proses_po.php', icon: 'bi-cart-check-fill', closable: true },
            { id: 'site', title: 'Master Site', url: BASE_URL + '/admin/pages/site/index.php', icon: 'bi-geo-alt-fill', closable: true },
            { id: 'jabatan', title: 'Master Jabatan', url: BASE_URL + '/admin/pages/jabatan/index.php', icon: 'bi-person-badge', closable: true },
            { id: 'divisi', title: 'Master Divisi', url: BASE_URL + '/admin/pages/divisi/index.php', icon: 'bi-diagram-3-fill', closable: true },
            { id: 'karyawan', title: 'Master Karyawan', url: BASE_URL + '/admin/pages/user/index.php', icon: 'bi-people-fill', closable: true },
            { id: 'vendor', title: 'Master Vendor', url: BASE_URL + '/admin/pages/vendor/index.php', icon: 'bi-truck', closable: true },
            { id: 'kategori', title: 'Kategori Barang', url: BASE_URL + '/admin/pages/kategori/index.php', icon: 'bi-tags', closable: true },
            { id: 'merk', title: 'Merk Barang', url: BASE_URL + '/admin/pages/merk/index.php', icon: 'bi-bookmark-star', closable: true },
            { id: 'barang', title: 'Katalog Barang', url: BASE_URL + '/admin/pages/barang/index.php', icon: 'bi-box-seam', closable: true },
            { id: 'smtp', title: 'Server SMTP', url: BASE_URL + '/admin/pages/smtp/index.php', icon: 'bi-envelope-at-fill', closable: true },
            { id: 'info', title: 'Informasi & Pengumuman', url: BASE_URL + '/admin/pages/info/index.php', icon: 'bi-megaphone-fill', closable: true },
            { id: 'menu', title: 'Manajemen Menu', url: BASE_URL + '/admin/pages/menu/index.php', icon: 'bi-list-check', closable: true }
        ];
    },

    getCurrentTabInfo() {
        const path = window.location.pathname.replace(/\/+$/, '');
        const known = this.getKnownTabs();

        if (path.includes('/request_order/edit.php')) {
            const urlParams = new URLSearchParams(window.location.search);
            const roId = urlParams.get('id');
            let roTitle = (typeof CURRENT_RO_NOMOR !== 'undefined' && CURRENT_RO_NOMOR) ? CURRENT_RO_NOMOR : '';
            if (!roTitle) {
                const docTitle = document.title.split('-')[0].trim();
                if (docTitle && !docTitle.toLowerCase().includes('edit request') && !docTitle.toLowerCase().includes('request order:')) {
                    roTitle = docTitle;
                }
            }
            if (!roTitle) {
                roTitle = roId ? 'RO #' + roId : 'Detail RO';
            }
            return {
                id: 'ro_edit' + (roId ? '_' + roId : ''),
                title: roTitle,
                url: window.location.href,
                icon: 'bi-file-earmark-text-fill',
                closable: true
            };
        }
        
        for (const t of known) {
            const tPath = new URL(t.url, window.location.origin).pathname.replace(/\/+$/, '');
            if (
                path === tPath || 
                (t.id === 'user_profile' && path.includes('/user/profile.php')) ||
                (t.id === 'karyawan' && path.includes('/user/index.php')) || 
                (t.id === 'profile' && path.includes('/profile/index.php')) ||
                (t.id === 'barang' && path.includes('/barang/')) || 
                (t.id === 'smtp' && path.includes('/smtp/')) || 
                (t.id === 'info' && path.includes('/info/')) || 
                (t.id === 'proses_po' && path.includes('/request_order/proses_po.php')) || 
                (t.id === 'receiving_create' && path.includes('/receiving/create.php')) ||
                (t.id === 'receiving_edit' && path.includes('/receiving/edit.php')) ||
                (t.id === 'receiving' && path.includes('/receiving/')) ||
                (t.id === 'purchase_order' && (path.includes('/purchase_order/index.php') || path.includes('/purchase_order/edit.php'))) || 
                (t.id === 'request_order' && path.includes('/request_order/index.php')) || 
                (t.id === 'ro_create' && path.includes('/create.php'))
            ) {
                return {
                    ...t,
                    url: window.location.href
                };
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

    updateCurrentTabTitle(newTitle) {
        if (!newTitle) return;
        const currentTab = this.getCurrentTabInfo();
        let openedTabs = this.getOpenedTabs();
        const existingIdx = openedTabs.findIndex(t => t.id === currentTab.id);
        if (existingIdx !== -1) {
            openedTabs[existingIdx].title = newTitle;
            this.saveOpenedTabs(openedTabs);
            const el = document.querySelector(`.app-tab-item[data-tab-id="${currentTab.id}"] .app-tab-title`);
            if (el) el.textContent = newTitle;
            const itemEl = document.querySelector(`.app-tab-item[data-tab-id="${currentTab.id}"]`);
            if (itemEl) itemEl.setAttribute('title', newTitle);
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

// Global Logout Handler dengan Peringatan Tab Masih Terbuka
let logoutModalInstance = null;

function handleLogout() {
    const openedTabs = AppTabs.getOpenedTabs ? AppTabs.getOpenedTabs() : [];
    // Filter tab workspace aktif selain Dashboard
    const activeWorkspaceTabs = openedTabs.filter(t => t.id !== 'dashboard');

    const modalEl = document.getElementById('modalConfirmLogout');
    if (!modalEl) {
        if (!confirm('Apakah Anda yakin ingin keluar dari sistem?')) return;
        executeLogoutNow();
        return;
    }

    if (!logoutModalInstance) {
        logoutModalInstance = new bootstrap.Modal(modalEl);
    }

    const warningArea = document.getElementById('logoutWarningTabsArea');
    const tabsList = document.getElementById('logoutOpenTabsList');
    const msgText = document.getElementById('logoutConfirmMessageText');

    if (activeWorkspaceTabs.length > 0) {
        warningArea.classList.remove('d-none');
        let html = '<div class="d-flex flex-column gap-1">';
        activeWorkspaceTabs.forEach(t => {
            const icon = t.icon || 'bi-window-sidebar';
            html += `
                <div class="d-flex align-items-center justify-content-between bg-white border rounded px-2 py-1 small shadow-xs">
                    <span class="text-dark fw-semibold"><i class="bi ${icon} text-primary me-2"></i>${t.title || 'Tab'}</span>
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle font-monospace" style="font-size: 0.7rem;">Tab Terbuka</span>
                </div>
            `;
        });
        html += '</div>';
        tabsList.innerHTML = html;
        msgText.innerHTML = `Keluar sekarang akan <strong>menutup otomatis ${activeWorkspaceTabs.length} tab pekerjaan di atas</strong> dan membersihkan sesi Anda.`;
    } else {
        warningArea.classList.add('d-none');
        msgText.textContent = 'Apakah Anda yakin ingin mengakhiri sesi dan keluar dari sistem?';
    }

    logoutModalInstance.show();
}

async function executeLogoutNow() {
    const btn = document.getElementById('btnConfirmLogoutExecute');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Mengeluarkan...';
    }

    // Bersihkan seluruh tab workspace di sessionStorage & localStorage agar akun berikutnya fresh
    sessionStorage.removeItem('jt_workspace_tabs');
    localStorage.removeItem('jt_workspace_tabs');
    sessionStorage.removeItem('jt_sidebar_scroll_top');

    try {
        const res = await fetch(BASE_URL + '/api/auth/logout.php', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + API_TOKEN,
                'Content-Type': 'application/json'
            }
        });
        const data = await res.json();
        window.location.href = (data && data.data && data.data.redirect_url) ? data.data.redirect_url : (BASE_URL + '/admin/login.php');
    } catch (err) {
        window.location.href = BASE_URL + '/admin/login.php';
    }
}

// Centralized Fetch API Wrapper
async function apiRequest(endpoint, options = {}) {
    if (typeof options === 'string') {
        options = { method: options };
    }
    const defaultHeaders = {
        'Authorization': 'Bearer ' + API_TOKEN,
        'Accept': 'application/json'
    };
    
    // Jangan set Content-Type jika body adalah FormData agar browser otomatis menyertakan boundary multipart
    if (!(options.body instanceof FormData)) {
        defaultHeaders['Content-Type'] = 'application/json';
    }
    
    options.headers = Object.assign({}, defaultHeaders, options.headers || {});
    if (options.body instanceof FormData && options.headers['Content-Type']) {
        delete options.headers['Content-Type'];
    }
    options.credentials = options.credentials || 'include';
    
    try {
        const response = await fetch(BASE_URL + endpoint, options);
        const text = await response.text();
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('API Response Non-JSON:', text);
            showToast('Respon server tidak valid: ' + (text.replace(/<[^>]*>/g, '').trim().substring(0, 80) || 'Format salah'), 'error');
            return { success: false, message: 'Respon server tidak valid.', raw: text };
        }
        
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
