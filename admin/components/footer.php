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
// Sidebar Toggle for Mobile View
function toggleSidebar() {
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    if (sidebar && backdrop) {
        sidebar.classList.toggle('show');
        backdrop.classList.toggle('show');
    }
}

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

// Sidebar Scroll Position Memory & Preservation
document.addEventListener('DOMContentLoaded', () => {
    const sidebarNav = document.querySelector('.sidebar-nav');
    if (sidebarNav) {
        // Pulihkan posisi scroll dari sessionStorage
        const savedScrollPos = sessionStorage.getItem('jt_sidebar_scroll_top');
        if (savedScrollPos !== null) {
            sidebarNav.scrollTop = parseInt(savedScrollPos, 10);
        } else {
            // Jika belum ada riwayat scroll, pastikan menu aktif terlihat
            const activeItem = sidebarNav.querySelector('.sidebar-link.active, .sidebar-sublink.active');
            if (activeItem) {
                activeItem.scrollIntoView({ block: 'nearest', behavior: 'auto' });
            }
        }

        // Simpan posisi scroll setiap kali pengguna melakukan scroll
        sidebarNav.addEventListener('scroll', () => {
            sessionStorage.setItem('jt_sidebar_scroll_top', sidebarNav.scrollTop);
        }, { passive: true });

        // Simpan posisi scroll sebelum halaman berpindah
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
