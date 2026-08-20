<?php
/**
 * Halaman Login Modern - PT Jaya Teknik
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
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 10% 20%, #0d233a 0%, #081523 90%);
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
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(2, 132, 199, 0.15) 0%, rgba(2, 132, 199, 0) 70%);
            top: -100px;
            right: -100px;
            border-radius: 50%;
        }

        .login-shape-2 {
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(30, 82, 136, 0.25) 0%, rgba(30, 82, 136, 0) 70%);
            bottom: -80px;
            left: -80px;
            border-radius: 50%;
        }

        .login-box {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 460px;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }

        .login-brand-header {
            background: linear-gradient(135deg, #0f2744 0%, #1e5288 100%);
            padding: 2.25rem 2rem 1.75rem 2rem;
            text-align: center;
            color: #ffffff;
            position: relative;
        }

        .brand-icon-bubble {
            width: 60px;
            height: 60px;
            margin: 0 auto 0.85rem auto;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.85rem;
            color: #38bdf8;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .login-form-body {
            padding: 2rem 2.25rem;
        }

        .form-group-custom {
            margin-bottom: 1.25rem;
        }

        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-wrapper .form-control {
            height: 48px;
            padding-left: 2.75rem;
            padding-right: 2.75rem;
            font-size: 0.92rem;
            border-radius: 10px;
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
            right: 0.75rem;
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
            height: 48px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            border: none;
            border-radius: 10px;
            font-weight: 700;
            letter-spacing: 0.3px;
            font-size: 0.95rem;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
            transition: all 0.25s ease;
        }

        .btn-login-submit:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.45);
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            color: #ffffff;
        }

        .demo-roles-container {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.85rem;
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
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-bg-shapes">
        <div class="login-shape-1"></div>
        <div class="login-shape-2"></div>
    </div>

    <div class="login-box">
        <!-- Header Branding from Database Profile -->
        <div class="login-brand-header">
            <div class="brand-icon-bubble">
                <i class="bi bi-shield-check"></i>
            </div>
            <h1 class="fs-4 fw-bold mb-1 tracking-tight"><?= htmlspecialchars($companyName) ?></h1>
            <p class="text-white-50 small mb-0 fw-medium">Sistem Permintaan Pengadaan Barang (Request Order)</p>
            <div class="mt-2 text-white-50" style="font-size: 0.75rem;">
                <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($companyAddress) ?>, <?= htmlspecialchars($companyCity) ?>
            </div>
        </div>

        <div class="login-form-body">
            <!-- Alert Error Box -->
            <div id="loginAlert" class="alert alert-danger d-none py-2 px-3 small mb-3 border-0 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-1"></i> <span id="loginAlertText"></span>
            </div>

            <form id="loginForm" onsubmit="handleLoginSubmit(event)">
                <div class="form-group-custom">
                    <label class="form-label small fw-semibold text-secondary mb-1">Email atau Username</label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" class="form-control" id="identity" name="identity" placeholder="nama / email user" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="form-group-custom">
                    <label class="form-label small fw-semibold text-secondary mb-1">Password</label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password">
                        <button type="button" class="btn-toggle-eye" onclick="togglePasswordVisibility()" title="Lihat password">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" id="btnSubmit" class="btn btn-login-submit d-flex align-items-center justify-content-center">
                        <span id="btnSpinner" class="spinner-border spinner-border-sm d-none me-2" role="status" aria-hidden="true"></span>
                        <span id="btnText"><i class="bi bi-box-arrow-in-right me-1"></i> Masuk ke Sistem</span>
                    </button>
                </div>
            </form>

            <!-- Quick Demo Account Pills for Easy Testing -->
            <div class="demo-roles-container">
                <div class="text-center text-muted small mb-2 fw-bold" style="font-size: 0.75rem;">
                    <i class="bi bi-person-badge me-1"></i> UJI COBA ROLE CEPAT:
                </div>
                <div class="d-flex flex-wrap gap-1 justify-content-center">
                    <button type="button" class="role-btn-chip" onclick="setDemoAccount('admin', 'admin123')">
                        <i class="bi bi-shield-fill text-danger me-1"></i>Admin (Super)
                    </button>
                    <button type="button" class="role-btn-chip" onclick="setDemoAccount('mekanik@jayateknis.com', 'admin123')">
                        <i class="bi bi-wrench-adjustable text-primary me-1"></i>Mekanik (KRY002)
                    </button>
                    <button type="button" class="role-btn-chip" onclick="setDemoAccount('logistik@jayateknis.com', 'admin123')">
                        <i class="bi bi-box-seam text-info me-1"></i>Logistik (KRY003)
                    </button>
                    <button type="button" class="role-btn-chip" onclick="setDemoAccount('purchasing@jayateknis.com', 'admin123')">
                        <i class="bi bi-cart-check text-success me-1"></i>Purchasing (KRY004)
                    </button>
                    <button type="button" class="role-btn-chip" onclick="setDemoAccount('manager@jayateknis.com', 'admin123')">
                        <i class="bi bi-person-workspace text-dark me-1"></i>Manager (KRY005)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

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
</script>

</body>
</html>
