<?php
/**
 * Halaman Login 2 Kolom (Pengumuman Sistem & Form Login) - PT Jaya Teknik
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/session.php';

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

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0b1c2e;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 10% 20%, #0e2945 0%, #071320 90%);
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }

        .login-bg-shapes {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            pointer-events: none;
            overflow: hidden;
            z-index: 1;
        }

        .login-shape-1 {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(2, 132, 199, 0.18) 0%, rgba(2, 132, 199, 0) 70%);
            top: -150px;
            right: -150px;
            border-radius: 50%;
        }

        .login-shape-2 {
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(30, 82, 136, 0.25) 0%, rgba(30, 82, 136, 0) 70%);
            bottom: -120px;
            left: -120px;
            border-radius: 50%;
        }

        /* 2-Column Split Container */
        .login-card-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1060px;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.55), 0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        /* Left Column: Pengumuman & Branding */
        .login-left-pane {
            background: linear-gradient(155deg, #091e34 0%, #0e2c4d 50%, #153e6b 100%);
            color: #ffffff;
            padding: 2.75rem 2.25rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            min-width: 0;
            overflow-x: hidden;
        }

        .brand-header-box {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.75rem;
            min-width: 0;
        }

        .brand-icon-bubble {
            width: 48px;
            height: 48px;
            background: rgba(56, 189, 248, 0.15);
            border: 1px solid rgba(56, 189, 248, 0.35);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #38bdf8;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            flex-shrink: 0;
        }

        .announcement-feed-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .announcement-scroll-area {
            max-height: 380px;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 6px;
            min-width: 0;
        }

        /* Custom Scrollbar */
        .announcement-scroll-area::-webkit-scrollbar {
            width: 5px;
        }
        .announcement-scroll-area::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }
        .announcement-scroll-area::-webkit-scrollbar-thumb {
            background: rgba(56, 189, 248, 0.3);
            border-radius: 4px;
        }
        .announcement-scroll-area::-webkit-scrollbar-thumb:hover {
            background: rgba(56, 189, 248, 0.5);
        }

        .announcement-card-item {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 14px;
            padding: 1rem 1.15rem;
            margin-bottom: 0.85rem;
            transition: all 0.25s ease;
            cursor: pointer;
            backdrop-filter: blur(8px);
            min-width: 0;
            word-wrap: break-word;
        }

        .announcement-card-item:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(56, 189, 248, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .announcement-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            font-size: 0.7rem;
            font-weight: 600;
            border-radius: 20px;
            background: rgba(56, 189, 248, 0.12);
            color: #7dd3fc;
            border: 1px solid rgba(56, 189, 248, 0.28);
            max-width: 170px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .pulse-indicator {
            width: 8px;
            height: 8px;
            background-color: #38bdf8;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.7);
            animation: pulse-ring 1.8s infinite cubic-bezier(0.66, 0, 0, 1);
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(56, 189, 248, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); }
        }

        /* Right Column: Form Login */
        .login-right-pane {
            background: #ffffff;
            padding: 3.5rem 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-group-custom {
            margin-bottom: 1.35rem;
        }

        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-wrapper .form-control {
            height: 50px;
            padding-left: 2.85rem;
            padding-right: 2.85rem;
            font-size: 0.94rem;
            border-radius: 12px;
            border: 1.5px solid #cbd5e1;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }

        .input-icon-wrapper .form-control:focus {
            border-color: #0284c7;
            background-color: #ffffff;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
        }

        .input-icon-wrapper .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.15rem;
            color: #94a3b8;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 5;
        }

        .input-icon-wrapper:focus-within .input-icon {
            color: #0284c7;
        }

        .btn-toggle-eye {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 1.15rem;
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 5;
            transition: color 0.2s ease;
        }

        .btn-toggle-eye:hover {
            color: #0284c7;
        }

        .btn-login-submit {
            height: 50px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: none;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 0.96rem;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
            transition: all 0.25s ease;
        }

        .btn-login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(2, 132, 199, 0.45);
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            color: #ffffff;
        }

        .demo-roles-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            margin-top: 1.5rem;
        }

        .role-btn-chip {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.35rem 0.65rem;
            font-size: 0.78rem;
            font-weight: 600;
            color: #334155;
            transition: all 0.15s ease;
            cursor: pointer;
        }

        .role-btn-chip:hover {
            background: #e0f2fe;
            border-color: #0284c7;
            color: #0369a1;
        }

        @media (max-width: 991.98px) {
            .login-left-pane {
                padding: 2.25rem 2rem;
            }
            .login-right-pane {
                padding: 2.5rem 2rem;
            }
            .announcement-scroll-area {
                max-height: 250px;
            }
        }
    </style>
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
                            <a href="javascript:void(0)" onclick="handleForgotPasswordClick()" class="small text-primary text-decoration-none fw-semibold" style="font-size: 0.8rem;">
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

                <!-- Quick Demo Account Pills -->
                <div class="demo-roles-container">
                    <div class="text-center text-muted small mb-2 fw-bold" style="font-size: 0.72rem;">
                        UJI COBA ROLE CEPAT:
                    </div>
                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                        <button type="button" class="role-btn-chip" onclick="setDemoAccount('admin', 'admin123')">Admin</button>
                        <button type="button" class="role-btn-chip" onclick="setDemoAccount('mekanik@jayateknis.com', 'admin123')">Mekanik</button>
                        <button type="button" class="role-btn-chip" onclick="setDemoAccount('logistik@jayateknis.com', 'admin123')">Logistik</button>
                        <button type="button" class="role-btn-chip" onclick="setDemoAccount('purchasing@jayateknis.com', 'admin123')">Purchasing</button>
                        <button type="button" class="role-btn-chip" onclick="setDemoAccount('finance@jayateknis.com', 'admin123')">Finance</button>
                        <button type="button" class="role-btn-chip" onclick="setDemoAccount('manager@jayateknis.com', 'admin123')">Manager</button>
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
});

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

function handleForgotPasswordClick() {
    alert('Untuk reset password, silakan hubungi Administrator IT atau gunakan email pemulihan terdaftar.');
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

</body>
</html>
