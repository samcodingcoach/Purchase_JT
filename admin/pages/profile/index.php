<?php
/**
 * Pengaturan Profil Perusahaan (Master Data) - PT Jaya Teknis
 * Path: admin/pages/profile/index.php
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Profil Perusahaan';
$pageHeading = 'Pengaturan Profil & Identitas Perusahaan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="container-fluid px-0">
    <div class="mb-4">
        <h4 class="fw-bold text-dark mb-0">
            <i class="bi bi-buildings text-primary me-2"></i>Profil Perusahaan
        </h4>
        <span class="text-muted small">Kelola identitas resmi, legalitas pajak, dan kontak operasional perusahaan</span>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3 pt-2" id="companyProfileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active fw-semibold text-dark py-3 px-4" id="tab-umum-btn" data-bs-toggle="tab" data-bs-target="#panel-umum" type="button" role="tab">
                        <i class="bi bi-buildings me-2 text-primary"></i>1. Identitas &amp; Kontak
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-dark py-3 px-4" id="tab-pajak-btn" data-bs-toggle="tab" data-bs-target="#panel-pajak" type="button" role="tab">
                        <i class="bi bi-receipt-cutoff me-2 text-primary"></i>2. Legalitas &amp; Pajak
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link fw-semibold text-dark py-3 px-4" id="tab-lokasi-btn" data-bs-toggle="tab" data-bs-target="#panel-lokasi" type="button" role="tab">
                        <i class="bi bi-geo-alt me-2 text-primary"></i>3. Alamat &amp; Lokasi
                    </button>
                </li>
            </ul>
        </div>

        <form id="companyProfileForm" onsubmit="handleSaveCompanyProfile(event)">
            <div class="card-body p-4">
                <div class="tab-content" id="companyProfileTabContent">
                    
                    <!-- TAB 1: IDENTITAS & KONTAK -->
                    <div class="tab-pane fade show active" id="panel-umum" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-1 text-primary"></i> Data Utama &amp; Komunikasi</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nama Perusahaan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="profNama" required placeholder="PT Jaya Teknis">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email Resmi</label>
                                <input type="email" class="form-control form-control-sm" id="profEmail" placeholder="info@jayateknis.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor Telepon Kantor</label>
                                <input type="text" class="form-control form-control-sm" id="profTelepon" placeholder="031-889900">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Nomor WhatsApp Operasional</label>
                                <input type="text" class="form-control form-control-sm" id="profWhatsapp" placeholder="081234567890">
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: LEGALITAS & PAJAK -->
                    <div class="tab-pane fade" id="panel-pajak" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check me-1 text-primary"></i> Identitas Pajak &amp; Legalitas</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NPWP Perusahaan</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="profNpwp" placeholder="00.000.000.0-000.000">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">KLU (Klasifikasi Lapangan Usaha)</label>
                                <input type="text" class="form-control form-control-sm" id="profKlu" placeholder="Kode KLU Pajak">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">NITKU</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="profNitku" placeholder="Nomor Identitas Tempat Kegiatan Usaha">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tarif PPN Standar (%)</label>
                                <select class="form-select form-select-sm" id="profPajak12">
                                    <option value="1">PPN 12% (Berlaku)</option>
                                    <option value="0">Non-PPN / PPN 11%</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: ALAMAT & LOKASI -->
                    <div class="tab-pane fade" id="panel-lokasi" role="tabpanel">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-pin-map me-1 text-primary"></i> Alamat Kantor &amp; Workshop Pusat</h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold">Alamat Lengkap Kantor</label>
                                <textarea class="form-control form-control-sm" id="profAlamat" rows="2" placeholder="Jl. Raya Pelabuhan No. 123..."></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Kota / Kabupaten</label>
                                <input type="text" class="form-control form-control-sm" id="profKota" placeholder="Surabaya">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Provinsi</label>
                                <input type="text" class="form-control form-control-sm" id="profProvinsi" placeholder="Jawa Timur">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Koordinat GPS / Link Google Maps</label>
                                <input type="text" class="form-control form-control-sm font-monospace" id="profAlamatGps" placeholder="-7.250445, 112.768845">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php if ($user['role'] === ROLE_ADMIN): ?>
            <div class="card-footer bg-light p-3 d-flex justify-content-end border-top">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm" id="btnSaveCompanyProfile">
                    <i class="bi bi-save me-1"></i> Simpan Profil Perusahaan
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    await loadCompanyProfileData();
});

async function loadCompanyProfileData() {
    const res = await apiRequest('/api/master/profile.php');
    if (res && res.success && res.data && res.data.profile) {
        const p = res.data.profile;
        document.getElementById('profNama').value = p.nama || '';
        document.getElementById('profEmail').value = p.email || '';
        document.getElementById('profTelepon').value = p.telepon1 || '';
        document.getElementById('profWhatsapp').value = p.whatsapp || '';
        document.getElementById('profNpwp').value = p.npwp || '';
        document.getElementById('profKlu').value = p.KLU || '';
        document.getElementById('profNitku').value = p.NITKU || '';
        document.getElementById('profPajak12').value = (p.pajak12 !== undefined) ? p.pajak12 : 1;
        document.getElementById('profAlamat').value = p.alamat || '';
        document.getElementById('profKota').value = p.kota || '';
        document.getElementById('profProvinsi').value = p.provinsi || '';
        document.getElementById('profAlamatGps').value = p.alamat_gps || '';
    }
}

async function handleSaveCompanyProfile(e) {
    e.preventDefault();

    const payload = {
        nama: document.getElementById('profNama').value.trim(),
        email: document.getElementById('profEmail').value.trim(),
        telepon1: document.getElementById('profTelepon').value.trim(),
        whatsapp: document.getElementById('profWhatsapp').value.trim(),
        npwp: document.getElementById('profNpwp').value.trim(),
        KLU: document.getElementById('profKlu').value.trim(),
        NITKU: document.getElementById('profNitku').value.trim(),
        pajak12: parseInt(document.getElementById('profPajak12').value) || 0,
        alamat: document.getElementById('profAlamat').value.trim(),
        kota: document.getElementById('profKota').value.trim(),
        provinsi: document.getElementById('profProvinsi').value.trim(),
        alamat_gps: document.getElementById('profAlamatGps').value.trim()
    };

    const btn = document.getElementById('btnSaveCompanyProfile');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...';
    }

    const res = await apiRequest('/api/master/profile.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-save me-1"></i> Simpan Profil Perusahaan';
    }

    if (res && res.success) {
        showToast('Profil perusahaan berhasil disimpan.', 'success');
    } else {
        showToast(res ? res.message : 'Gagal menyimpan profil perusahaan.', 'danger');
    }
}
</script>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>
