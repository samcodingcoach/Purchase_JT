<?php
/**
 * Halaman Edit Profil Karyawan / Pengguna yang Sedang Login
 * Path: admin/pages/user/profile.php
 * Akses: Seluruh Role yang Login (Admin, Mekanik, Logistik, Purchasing, Manager)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth: Dapat diakses oleh seluruh pengguna yang sedang login
$user = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Edit Profil Saya';
$pageHeading = 'Pengaturan Profil & Akun Karyawan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <!-- Header Halaman -->
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">
            <i class="bi bi-person-gear text-primary me-2"></i>Edit Profil Saya
        </h4>
        <span class="text-muted small">Kelola data pribadi, identitas diri, dan keamanan akun Anda</span>
    </div>

    <!-- Loading Skeleton Card -->
    <div id="profileLoadingSkeleton" class="card border-0 shadow-sm rounded-3 p-4 mb-4">
        <div class="row g-4">
            <div class="col-lg-4 text-center">
                <div class="skeleton-shimmer rounded-circle mb-3" style="width: 100px; height: 100px;"></div>
                <div class="skeleton-shimmer mb-2" style="width: 160px; height: 20px;"></div>
                <div class="skeleton-shimmer" style="width: 100px; height: 24px; border-radius: 12px;"></div>
            </div>
            <div class="col-lg-8">
                <div class="skeleton-shimmer mb-3" style="width: 100%; height: 38px;"></div>
                <div class="skeleton-shimmer mb-3" style="width: 100%; height: 38px;"></div>
                <div class="skeleton-shimmer mb-3" style="width: 100%; height: 38px;"></div>
            </div>
        </div>
    </div>

    <!-- Main Profile Card Content -->
    <div id="profileContentArea" class="row g-4 d-none">
        <!-- Kolom Kiri: Kartu Identitas & Jabatan Karyawan -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden text-center">
                <div class="bg-primary bg-gradient py-4 px-3 text-white">
                    <div class="d-inline-flex align-items-center justify-content-center bg-white text-primary rounded-circle shadow fw-bold fs-2 mb-2" 
                         style="width: 84px; height: 84px;" id="avatarInitialDisplay">
                        U
                    </div>
                    <h5 class="fw-bold mb-1" id="displayNamaUser">Memuat...</h5>
                    <span class="badge bg-light text-primary fw-semibold px-3 py-1" id="displayRoleBadge">ROLE</span>
                </div>

                <div class="card-body p-3 text-start">
                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3 small text-uppercase">
                        <i class="bi bi-briefcase text-primary me-1"></i> Informasi Penugasan
                    </h6>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Kode Karyawan / NIK:</span>
                        <strong class="text-dark font-monospace" id="infoKodeKaryawan">-</strong>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Jabatan:</span>
                        <span class="fw-semibold text-dark" id="infoJabatan">-</span>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Divisi / Departemen:</span>
                        <span class="fw-semibold text-dark" id="infoDivisi">-</span>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Site / Penempatan:</span>
                        <span class="badge bg-secondary-subtle text-secondary font-monospace" id="infoSite">-</span>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Jenis Kelamin:</span>
                        <span class="fw-semibold text-dark" id="infoJenisKelamin">-</span>
                    </div>

                    <div class="mb-2">
                        <span class="text-muted small d-block">Tempat, Tanggal Lahir:</span>
                        <span class="fw-semibold text-dark" id="infoTtl">-</span>
                    </div>

                    <div>
                        <span class="text-muted small d-block">Status Karyawan:</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle" id="infoStatusKaryawan">Tetap</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Formulir Edit Profil & Password -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="bi bi-pencil-square text-primary me-1"></i> Perbarui Informasi Pribadi &amp; Kata Sandi
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form id="formEditProfile" onsubmit="handleSaveProfile(event)">
                        <!-- Data Pribadi & Kontak -->
                        <h6 class="fw-bold text-dark mb-3 small text-uppercase">
                            <i class="bi bi-person-lines-fill text-primary me-1"></i> Data Identitas &amp; Kontak
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="inputNama" required placeholder="Nama Lengkap Anda">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Tempat Lahir</label>
                                <input type="text" class="form-control form-control-sm" id="inputTempatLahir" placeholder="Contoh: Surabaya">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Tanggal Lahir</label>
                                <input type="date" class="form-control form-control-sm" id="inputTanggalLahir">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Jenis Kelamin</label>
                                <select class="form-select form-select-sm" id="inputJenisKelamin">
                                    <option value="">-- Pilih Jenis Kelamin --</option>
                                    <option value="1">Laki-laki (Pria)</option>
                                    <option value="2">Perempuan (Wanita)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Alamat Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-sm" id="inputEmail" required placeholder="nama@jayateknis.com">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">No. Handphone / WhatsApp</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="inputNoHp" placeholder="08xxxxxxxxxx">
                            </div>
                        </div>

                        <!-- Keamanan Akun / Ganti Password -->
                        <h6 class="fw-bold text-dark border-top pt-4 mb-3 small text-uppercase">
                            <i class="bi bi-shield-lock text-primary me-1"></i> Ganti Kata Sandi <span class="text-muted fw-normal text-lowercase">(kosongkan jika tidak ingin mengubah password)</span>
                        </h6>

                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Password Saat Ini</label>
                                <input type="password" class="form-control form-control-sm" id="inputPasswordLama" placeholder="Masukkan kata sandi saat ini untuk konfirmasi">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Password Baru</label>
                                <input type="password" class="form-control form-control-sm" id="inputPasswordBaru" placeholder="Minimal 5 karakter">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Ulangi Password Baru</label>
                                <input type="password" class="form-control form-control-sm" id="inputKonfirmasiPassword" placeholder="Ketik ulang kata sandi baru">
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <div class="d-flex justify-content-end gap-2 border-top pt-3">
                            <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSubmitProfile">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    await loadUserProfileData();
});

// Helper Format Tanggal Indonesia
function formatTglIndo(tglStr) {
    if (!tglStr) return '';
    const date = new Date(tglStr);
    if (isNaN(date.getTime())) return tglStr;
    const bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return `${date.getDate()} ${bulan[date.getMonth()]} ${date.getFullYear()}`;
}

// -------------------------------------------------------------
// 1. LOAD DATA PROFIL DARI API
// -------------------------------------------------------------
async function loadUserProfileData() {
    const res = await apiRequest('/api/auth/profile.php');
    if (!res || !res.success) {
        showToast(res ? res.message : 'Gagal memuat profil.', 'danger');
        return;
    }

    const data = res.data;

    // Tampilkan Data ke Kartu Identitas
    const nama = data.nama_karyawan || data.nama || 'Pengguna';
    document.getElementById('displayNamaUser').textContent = nama;
    document.getElementById('avatarInitialDisplay').textContent = nama.charAt(0).toUpperCase();
    document.getElementById('displayRoleBadge').textContent = (data.role || 'USER').toUpperCase();

    document.getElementById('infoKodeKaryawan').textContent = data.kode_karyawan || '-';
    document.getElementById('infoJabatan').textContent = data.nama_jabatan || '-';
    document.getElementById('infoDivisi').textContent = data.nama_divisi || '-';
    document.getElementById('infoSite').textContent = data.nama_site || '-';
    document.getElementById('infoStatusKaryawan').textContent = data.status_karyawan_label || 'PKWTT (Perjanjian Kerja Waktu Tidak Tertentu)';

    // Jenis Kelamin & TTL
    const jk = parseInt(data.jenis_kelamin);
    document.getElementById('infoJenisKelamin').textContent = jk === 1 ? 'Laki-laki (Pria)' : (jk === 2 ? 'Perempuan (Wanita)' : '-');

    const tempat = data.tempat_lahir || '';
    const tgl = data.tanggal_lahir ? formatTglIndo(data.tanggal_lahir) : '';
    let ttl = '-';
    if (tempat && tgl) ttl = `${tempat}, ${tgl}`;
    else if (tempat) ttl = tempat;
    else if (tgl) ttl = tgl;
    document.getElementById('infoTtl').textContent = ttl;

    // Tampilkan Data ke Form Input
    document.getElementById('inputNama').value = nama;
    document.getElementById('inputTempatLahir').value = data.tempat_lahir || '';
    document.getElementById('inputTanggalLahir').value = data.tanggal_lahir || '';
    document.getElementById('inputJenisKelamin').value = data.jenis_kelamin || '';
    document.getElementById('inputEmail').value = data.email || '';
    document.getElementById('inputNoHp').value = data.no_handphone || '';

    // Sembunyikan Skeleton & Tampilkan Konten
    document.getElementById('profileLoadingSkeleton').classList.add('d-none');
    document.getElementById('profileContentArea').classList.remove('d-none');
}

// -------------------------------------------------------------
// 2. SIMPAN PERUBAHAN PROFIL
// -------------------------------------------------------------
async function handleSaveProfile(e) {
    e.preventDefault();

    const nama = document.getElementById('inputNama').value.trim();
    const tempatLahir = document.getElementById('inputTempatLahir').value.trim();
    const tanggalLahir = document.getElementById('inputTanggalLahir').value;
    const jenisKelamin = document.getElementById('inputJenisKelamin').value;
    const email = document.getElementById('inputEmail').value.trim();
    const noHp = document.getElementById('inputNoHp').value.trim();
    const passLama = document.getElementById('inputPasswordLama').value;
    const passBaru = document.getElementById('inputPasswordBaru').value;
    const passKonf = document.getElementById('inputKonfirmasiPassword').value;

    if (!nama) {
        showToast('Nama lengkap tidak boleh kosong!', 'warning');
        return;
    }
    if (!email) {
        showToast('Alamat email tidak boleh kosong!', 'warning');
        return;
    }

    if (passBaru) {
        if (passBaru.length < 5) {
            showToast('Password baru minimal 5 karakter!', 'warning');
            return;
        }
        if (passBaru !== passKonf) {
            showToast('Konfirmasi kata sandi baru tidak cocok!', 'warning');
            return;
        }
    }

    const payload = {
        nama_karyawan: nama,
        tempat_lahir: tempatLahir,
        tanggal_lahir: tanggalLahir,
        jenis_kelamin: jenisKelamin,
        email: email,
        no_handphone: noHp,
        password_lama: passLama,
        password_baru: passBaru,
        konfirmasi_password: passKonf
    };

    const btnSubmit = document.getElementById('btnSubmitProfile');
    const originalText = btnSubmit.innerHTML;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';

    const res = await apiRequest('/api/auth/profile.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btnSubmit.disabled = false;
    btnSubmit.innerHTML = originalText;

    if (res && res.success) {
        showToast(res.message, 'success');
        document.getElementById('inputPasswordLama').value = '';
        document.getElementById('inputPasswordBaru').value = '';
        document.getElementById('inputKonfirmasiPassword').value = '';

        // Segarkan informasi di kartu identitas kiri
        document.getElementById('displayNamaUser').textContent = nama;
        document.getElementById('avatarInitialDisplay').textContent = nama.charAt(0).toUpperCase();
        
        const jkNum = parseInt(jenisKelamin);
        document.getElementById('infoJenisKelamin').textContent = jkNum === 1 ? 'Laki-laki (Pria)' : (jkNum === 2 ? 'Perempuan (Wanita)' : '-');

        const tglIndo = tanggalLahir ? formatTglIndo(tanggalLahir) : '';
        let ttl = '-';
        if (tempatLahir && tglIndo) ttl = `${tempatLahir}, ${tglIndo}`;
        else if (tempatLahir) ttl = tempatLahir;
        else if (tglIndo) ttl = tglIndo;
        document.getElementById('infoTtl').textContent = ttl;
    } else {
        showToast(res ? res.message : 'Gagal memperbarui profil.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
