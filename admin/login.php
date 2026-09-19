<?php
/**
 * Halaman Login 2 Kolom (Pengumuman Sistem & Form Login) - PT Jaya Teknik
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/koneksi.php';

// Jika sudah login, langsung arahkan ke Dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

$companyProfile = getCompanyProfile();
$companyName = $companyProfile['nama'] ?? 'PT Jaya Teknis';
$companyAddress = $companyProfile['alamat'] ?? 'Bengkel Las & Bubut Kapal';
$companyCity = $companyProfile['kota'] ?? 'Surabaya';


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= htmlspecialchars($companyName) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Font Plus Jakarta Sans -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Custom Theme Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/app.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/responsive.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/login.css">
</head>
<body>

<div class="login-wrapper">
    <div class="login-bg-shapes">
        <div class="login-shape-1"></div>
        <div class="login-shape-2"></div>
    </div>

    <!-- 2-Column Split Container -->
    <div class="login-card-container">
        <div class="row g-0">
            
            <!-- =========================================================
                 KOLOM KIRI: PENGUMUMAN & INFORMASI SISTEM
                 ========================================================= -->
            <div class="col-lg-6 login-left-pane">
                
                <!-- Branding Header -->
                <div>
                    <div class="brand-header-box">
                        <div class="brand-icon-bubble">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0 text-white tracking-tight"><?= htmlspecialchars($companyName) ?></h4>
                            <span class="text-white-50 small fw-medium">Purchase &amp; Operational Management System</span>
                        </div>
                    </div>

                    <!-- Header Papan Pengumuman -->
                    <div class="d-flex align-items-center mb-3 pb-2 border-bottom border-white border-opacity-10">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-indicator"></span>
                            <span class="small fw-bold text-uppercase tracking-wider text-info" style="font-size: 0.78rem;">
                                Pengumuman &amp; Informasi
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Feed Pengumuman List (Scrollable) -->
                <div class="announcement-feed-wrapper">
                    <div class="announcement-scroll-area" id="announcementListContainer">
                        <div class="text-center py-4 text-white-50 small">
                            <span class="spinner-border spinner-border-sm me-2 text-info"></span>Memuat pengumuman terbaru...
                        </div>
                    </div>
                </div>

                <!-- Footer Kolom Kiri -->
                <div class="pt-3 border-top border-white border-opacity-10 mt-3 text-center text-white-50 small" style="font-size: 0.75rem;">
                    <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($companyName) ?></span>
                </div>
            </div>

            <!-- =========================================================
                 KOLOM KANAN: FORM LOGIN KE SISTEM
                 ========================================================= -->
            <div class="col-lg-6 login-right-pane">
                
                <div class="mb-4">
                    <h3 class="fw-bold text-dark mb-1">Masuk ke Akun</h3>
                    <p class="text-muted small mb-0">Silakan masukkan username/email dan password Anda.</p>
                </div>

                <!-- Alert Box Error -->
                <div id="loginAlert" class="alert alert-danger d-none py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="loginAlertText"></span>
                </div>

                <form id="loginForm" onsubmit="handleLoginSubmit(event)">
                    <!-- Email / Username Input -->
                    <div class="form-group-custom">
                        <label class="form-label small fw-semibold text-secondary mb-1">Email atau Username</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-person input-icon"></i>
                            <input type="text" class="form-control" id="identity" name="identity" placeholder="nama / email user" required autofocus autocomplete="username">
                        </div>
                    </div>

                    <!-- Password Input with Lupa Password Link -->
                    <div class="form-group-custom">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-semibold text-secondary mb-0">Password</label>
                            <!-- Link Lupa Password -->
                            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#modalResetPassword" class="small text-primary text-decoration-none fw-semibold" style="font-size: 0.8rem;">
                                Lupa Password?
                            </a>
                        </div>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                            <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility()" title="Lihat password">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="d-grid mt-4">
                        <button type="submit" id="btnSubmit" class="btn btn-login-submit d-flex align-items-center justify-content-center">
                            <span id="btnSpinner" class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                            <span id="btnText"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem</span>
                        </button>
                    </div>
                </form>

                <!-- Quick Demo Account Pills (Dinamis dari Database) -->
                <div class="demo-roles-container">
                    <div class="text-center text-muted small mb-2 fw-bold" style="font-size: 0.72rem;">
                        UJI COBA ROLE CEPAT:
                    </div>
                    <div class="d-flex flex-wrap gap-1 justify-content-center" id="demoRolesContainer">
                        <span class="spinner-border spinner-border-sm text-muted" role="status" aria-hidden="true"></span>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

<!-- =============================================================
     MODAL BACA DETAIL PENGUMUMAN DI HALAMAN LOGIN
     ============================================================= -->
<div class="modal fade" id="modalLoginAnnouncementDetail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fs-6 fw-bold">
                    <i class="bi bi-megaphone-fill me-2"></i>Informasi &amp; Pengumuman
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="loginAnnouncementModalBody">
                <!-- Rendered dynamically -->
            </div>
            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let publicAnnouncementsCache = [];
let loginAnnouncementModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    loginAnnouncementModalInstance = new bootstrap.Modal(document.getElementById('modalLoginAnnouncementDetail'));
    loadPublicAnnouncements();
    loadDemoRoles();
});

// -------------------------------------------------------------
// LOAD DEMO ROLES (API)
// -------------------------------------------------------------
async function loadDemoRoles() {
    const container = document.getElementById('demoRolesContainer');
    try {
        const response = await fetch(BASE_URL + '/api/auth/demo-roles.php');
        const res = await response.json();
        
        if (res && res.success && res.data) {
            let html = '';
            res.data.forEach(demo => {
                html += `
                    <button type="button" 
                            class="role-btn-chip" 
                            title="${escapeHtml(demo.name)} (${escapeHtml(demo.email)})"
                            onclick="setDemoAccount('${escapeHtml(demo.email)}', '${escapeHtml(demo.password)}')">
                        ${escapeHtml(demo.label)}
                    </button>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = '<span class="text-muted small">Gagal memuat roles</span>';
        }
    } catch (e) {
        container.innerHTML = '<span class="text-muted small">Gagal memuat roles</span>';
    }
}

// -------------------------------------------------------------
// LOAD PUBLIC ANNOUNCEMENTS (KOLOM KIRI)
// -------------------------------------------------------------
async function loadPublicAnnouncements() {
    const container = document.getElementById('announcementListContainer');

    try {
        const response = await fetch(BASE_URL + '/api/master/info.php?public=1&limit=10');
        const res = await response.json();
        
        if (res && res.success && res.data && res.data.items && res.data.items.length > 0) {
            publicAnnouncementsCache = res.data.items;

            let html = '';
            publicAnnouncementsCache.forEach((item, idx) => {
                html += `
                    <div class="announcement-card-item" onclick="showLoginAnnouncementDetail(${idx})">
                        <div class="d-flex justify-content-between align-items-center mb-1 gap-2">
                            <span class="text-white fw-bold small text-truncate flex-grow-1" style="font-size: 0.86rem; min-width: 0;">
                                ${escapeHtml(item.judul)}
                            </span>
                            <span class="text-white-50 font-monospace flex-shrink-0" style="font-size: 0.68rem;">${item.tanggal_format ? item.tanggal_format.split(',')[0] : ''}</span>
                        </div>
                        <p class="text-white-50 mb-2 small text-truncate" style="font-size: 0.78rem; line-height: 1.4; min-width: 0;">
                            ${escapeHtml(item.isi)}
                        </p>
                        <div class="d-flex justify-content-end align-items-center">
                            <span class="text-info small fw-semibold flex-shrink-0" style="font-size: 0.74rem;">
                                Baca Selengkapnya <i class="bi bi-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                `;
            });
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="text-center py-5 text-white-50">
                    <i class="bi bi-chat-square-dots fs-2 d-block mb-2 text-info opacity-50"></i>
                    Belum ada pengumuman baru yang diterbitkan.
                </div>
            `;
        }
    } catch (e) {
        container.innerHTML = `
            <div class="text-center py-4 text-white-50 small">
                Gagal memuat feed pengumuman.
            </div>
        `;
    }
}

function showLoginAnnouncementDetail(idx) {
    const item = publicAnnouncementsCache[idx];
    if (!item) return;

    const isImg = item.file && /\.(jpg|jpeg|png|webp|gif)$/i.test(item.file);
    const body = document.getElementById('loginAnnouncementModalBody');

    let imageHtml = '';
    if (item.file_url && isImg) {
        imageHtml = `
            <div class="rounded-3 overflow-hidden mb-3 border bg-light text-center">
                <img src="${item.file_url}" alt="Attachment" class="img-fluid" style="max-height: 240px; object-fit: contain;">
            </div>
        `;
    }

    let fileDownloadHtml = '';
    if (item.file_url && !isImg) {
        fileDownloadHtml = `
            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-3">
                <span class="small text-muted"><i class="bi bi-paperclip me-1"></i>Lampiran Dokumen</span>
                <a href="${item.file_url}" target="_blank" class="btn btn-sm btn-primary">
                    <i class="bi bi-download me-1"></i>Unduh Lampiran
                </a>
            </div>
        `;
    }

    body.innerHTML = `
        ${imageHtml}
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="badge bg-primary-subtle text-primary font-monospace" style="font-size: 0.72rem;">PENGUMUMAN RESMI</span>
            <span class="small text-muted">${item.tanggal_format}</span>
        </div>
        <h5 class="fw-bold text-dark mb-3">${escapeHtml(item.judul)}</h5>
        <div class="p-3 bg-light rounded-3 text-secondary mb-3" style="white-space: pre-wrap; line-height: 1.6; font-size: 0.92rem;">
            ${escapeHtml(item.isi)}
        </div>
        ${fileDownloadHtml}
        <div class="border-top pt-2 text-muted small d-flex justify-content-between" style="font-size: 0.75rem;">
            <span>Penerima: <strong>${item.is_all_divisi ? 'Semua Divisi' : (item.divisi_names ? item.divisi_names.join(', ') : 'Umum')}</strong></span>
            <span>Diterbitkan Oleh: <strong>${escapeHtml(item.pembuat)}</strong></span>
        </div>
    `;

    loginAnnouncementModalInstance.show();
}

// -------------------------------------------------------------
// LOGIN FORM HANDLERS
// -------------------------------------------------------------
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

function setDemoAccount(username, pass) {
    document.getElementById('identity').value = username;
    document.getElementById('password').value = pass;
    document.getElementById('loginAlert').classList.add('d-none');
}

function handleResetPasswordClick() {
    // Digantikan dengan Bootstrap Modal (data-bs-toggle)
}

async function handleLoginSubmit(event) {
    event.preventDefault();
    
    const identity = document.getElementById('identity').value.trim();
    const password = document.getElementById('password').value.trim();
    const alertBox = document.getElementById('loginAlert');
    const alertText = document.getElementById('loginAlertText');
    const btnSubmit = document.getElementById('btnSubmit');
    const btnSpinner = document.getElementById('btnSpinner');
    const btnText = document.getElementById('btnText');
    
    alertBox.classList.add('d-none');
    
    if (!identity || !password) {
        alertText.textContent = 'Harap isi email/username dan password.';
        alertBox.classList.remove('d-none');
        return;
    }
    
    // Loading UI state
    btnSubmit.disabled = true;
    btnSpinner.classList.remove('d-none');
    btnText.textContent = 'Memverifikasi...';
    
    try {
        const response = await fetch(BASE_URL + '/api/auth/login.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                username: identity,
                password: password
            })
        });
        
        const res = await response.json();
        
        if (res.success) {
            btnText.textContent = 'Berhasil! Mengalihkan...';
            // Bersihkan sisa tab workspace dari user sebelumnya
            sessionStorage.removeItem('jt_workspace_tabs');
            localStorage.removeItem('jt_workspace_tabs');
            sessionStorage.removeItem('jt_sidebar_scroll_top');
            window.location.href = res.data.redirect_url || (BASE_URL + '/admin/dashboard.php');
        } else {
            alertText.textContent = res.message || 'Login gagal. Periksa kembali kredensial Anda.';
            alertBox.classList.remove('d-none');
            btnSubmit.disabled = false;
            btnSpinner.classList.add('d-none');
            btnText.textContent = 'Masuk ke Sistem';
        }
    } catch (error) {
        alertText.textContent = 'Terjadi kesalahan koneksi saat menghubungi server.';
        alertBox.classList.remove('d-none');
        btnSubmit.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.textContent = 'Masuk ke Sistem';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}
</script>

<!-- Modal Reset Password -->
<div class="modal fade" id="modalResetPassword" tabindex="-1" aria-labelledby="modalResetPasswordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="formResetPassword" onsubmit="handleResetPasswordSubmit(event)">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalResetPasswordLabel">Reset Password Karyawan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Silakan masukkan Email terdaftar dan Tanggal Lahir Anda. Kami akan mengirimkan password sementara ke email tersebut.</p>
                    <div class="mb-3">
                        <label for="resetEmail" class="form-label small fw-semibold">Email Karyawan <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="resetEmail" name="email" required placeholder="Masukkan email terdaftar">
                    </div>
                    <div class="mb-3">
                        <label for="resetTanggalLahir" class="form-label small fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="resetTanggalLahir" name="tanggal_lahir" required>
                    </div>
                    <div class="alert alert-danger d-none py-2 small" id="resetAlertBox">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="resetAlertText"></span>
                    </div>
                    <div class="alert alert-success d-none py-2 small" id="resetSuccessBox">
                        <i class="bi bi-check-circle-fill me-1"></i> <span id="resetSuccessText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnResetSubmit">
                        <span id="btnResetSpinner" class="spinner-border spinner-border-sm d-none me-1" role="status" aria-hidden="true"></span>
                        <span id="btnResetText">Kirim Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
async function handleResetPasswordSubmit(event) {
    event.preventDefault();
    
    const email = document.getElementById('resetEmail').value.trim();
    const tglLahir = document.getElementById('resetTanggalLahir').value.trim();
    
    const alertBox = document.getElementById('resetAlertBox');
    const alertText = document.getElementById('resetAlertText');
    const successBox = document.getElementById('resetSuccessBox');
    const successText = document.getElementById('resetSuccessText');
    
    const btnSubmit = document.getElementById('btnResetSubmit');
    const btnSpinner = document.getElementById('btnResetSpinner');
    const btnText = document.getElementById('btnResetText');
    
    alertBox.classList.add('d-none');
    successBox.classList.add('d-none');
    
    if (!email || !tglLahir) {
        alertText.textContent = 'Harap lengkapi semua isian.';
        alertBox.classList.remove('d-none');
        return;
    }
    
    btnSubmit.disabled = true;
    btnSpinner.classList.remove('d-none');
    btnText.textContent = 'Memproses...';
    
    try {
        const formData = new FormData();
        formData.append('email', email);
        formData.append('tanggal_lahir', tglLahir);
        
        const response = await fetch(BASE_URL + '/api/auth/reset_password.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            successText.textContent = result.message || 'Password berhasil dikirim ke email Anda.';
            successBox.classList.remove('d-none');
            // Bersihkan form
            document.getElementById('formResetPassword').reset();
            // Optional: tutup modal setelah beberapa detik
            setTimeout(() => {
                const modalEl = document.getElementById('modalResetPassword');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) modal.hide();
                successBox.classList.add('d-none');
            }, 3500);
        } else {
            alertText.textContent = result.message || 'Gagal mengirim password.';
            alertBox.classList.remove('d-none');
        }
    } catch (error) {
        alertText.textContent = 'Terjadi kesalahan koneksi jaringan.';
        alertBox.classList.remove('d-none');
    } finally {
        btnSubmit.disabled = false;
        btnSpinner.classList.add('d-none');
        btnText.textContent = 'Kirim Password';
    }
}
</script>

</body>
</html>
