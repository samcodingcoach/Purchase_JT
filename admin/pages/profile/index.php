<?php
/**
 * Pengaturan Profil Perusahaan - PT Jaya Teknis
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_ADMIN]);
$pageTitle = 'Profil Perusahaan';
$pageHeading = 'Pengaturan Profil & Identitas Perusahaan';

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-bottom">
        <ul class="nav nav-tabs card-header-tabs" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="tab-umum" data-bs-toggle="tab" data-bs-target="#panel-umum" type="button" role="tab">
                    <i class="bi bi-buildings me-2 text-primary"></i>1. Identitas &amp; Kontak
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-pajak" data-bs-toggle="tab" data-bs-target="#panel-pajak" type="button" role="tab">
                    <i class="bi bi-receipt-cutoff me-2 text-primary"></i>2. Legalitas &amp; Pajak
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="tab-lokasi" data-bs-toggle="tab" data-bs-target="#panel-lokasi" type="button" role="tab">
                    <i class="bi bi-geo-alt me-2 text-primary"></i>3. Alamat &amp; Lokasi
                </button>
            </li>
        </ul>
    </div>

    <form id="profileForm" onsubmit="handleSaveProfile(event)">
        <div class="card-body p-4">
            <div class="tab-content" id="profileTabContent">
                
                <!-- TAB 1: IDENTITAS & KONTAK (2 KOLOM) -->
                <div class="tab-pane fade show active" id="panel-umum" role="tabpanel">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-1 text-primary"></i> Data Utama &amp; Komunikasi</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nama Perusahaan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="profNama" required placeholder="PT Jaya Teknis">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Resmi</label>
                            <input type="email" class="form-control" id="profEmail" placeholder="info@jayateknis.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor Telepon Kantor</label>
                            <input type="text" class="form-control" id="profTelepon" placeholder="031-889900">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor WhatsApp Operasional</label>
                            <input type="text" class="form-control" id="profWhatsapp" placeholder="081234567890">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Kota</label>
                            <input type="text" class="form-control" id="profKota" placeholder="Surabaya">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Provinsi</label>
                            <input type="text" class="form-control" id="profProvinsi" placeholder="Jawa Timur">
                        </div>
                    </div>
                </div>

                <!-- TAB 2: LEGALITAS & PAJAK (2 KOLOM) -->
                <div class="tab-pane fade" id="panel-pajak" role="tabpanel">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-file-earmark-text me-1 text-primary"></i> Pajak &amp; Nomor Induk Wajib Pajak</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor Pokok Wajib Pajak (NPWP)</label>
                            <input type="text" class="form-control" id="profNpwp" placeholder="01.234.567.8-604.000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Klasifikasi Lapangan Usaha (KLU)</label>
                            <input type="text" class="form-control" id="profKlu" placeholder="KLU33151">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Nomor Identitas Tempat Kegiatan Usaha (NITKU)</label>
                            <input type="text" class="form-control" id="profNitku" placeholder="0123456789012345">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Status PPN 12%</label>
                            <select class="form-select" id="profPajak12">
                                <option value="1">Aktif (Kena Pajak PPN 12%)</option>
                                <option value="0">Non-Aktif (Tanpa PPN)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: ALAMAT & LOKASI (2 KOLOM) -->
                <div class="tab-pane fade" id="panel-lokasi" role="tabpanel">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-geo-alt me-1 text-primary"></i> Domisili &amp; Lokasi Workshop</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Alamat Kantor / Galangan Utama</label>
                            <textarea class="form-control" id="profAlamat" rows="4" placeholder="Jl. Perak Timur No. 100, Tanjung Perak, Surabaya"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Koordinat GPS / Link Google Maps</label>
                            <textarea class="form-control" id="profAlamatGps" rows="4" placeholder="-7.2000, 112.7300 atau https://maps.google.com/..."></textarea>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer bg-light py-3 d-flex justify-content-end gap-2 border-top">
            <button type="submit" id="btnSaveProfile" class="btn btn-primary px-4 fw-semibold">
                <i class="bi bi-check-circle-fill me-1"></i> Simpan Perubahan Profil
            </button>
        </div>
    </form>
</div>

<script>
async function loadCompanyProfile() {
    const res = await apiRequest('/api/master/profile.php');
    if (res && res.success && res.data.profile) {
        const p = res.data.profile;
        document.getElementById('profNama').value = p.nama || '';
        document.getElementById('profEmail').value = p.email || '';
        document.getElementById('profTelepon').value = p.telepon1 || '';
        document.getElementById('profWhatsapp').value = p.whatsapp || '';
        document.getElementById('profKota').value = p.kota || '';
        document.getElementById('profProvinsi').value = p.provinsi || '';
        document.getElementById('profNpwp').value = p.npwp || '';
        document.getElementById('profKlu').value = p.KLU || '';
        document.getElementById('profNitku').value = p.NITKU || '';
        document.getElementById('profPajak12').value = p.pajak12 !== undefined ? p.pajak12 : 1;
        document.getElementById('profAlamat').value = p.alamat || '';
        document.getElementById('profAlamatGps').value = p.alamat_gps || '';
    }
}

async function handleSaveProfile(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveProfile');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const payload = {
        nama: document.getElementById('profNama').value.trim(),
        email: document.getElementById('profEmail').value.trim(),
        telepon1: document.getElementById('profTelepon').value.trim(),
        whatsapp: document.getElementById('profWhatsapp').value.trim(),
        kota: document.getElementById('profKota').value.trim(),
        provinsi: document.getElementById('profProvinsi').value.trim(),
        npwp: document.getElementById('profNpwp').value.trim(),
        KLU: document.getElementById('profKlu').value.trim(),
        NITKU: document.getElementById('profNitku').value.trim(),
        pajak12: document.getElementById('profPajak12').value,
        alamat: document.getElementById('profAlamat').value.trim(),
        alamat_gps: document.getElementById('profAlamatGps').value.trim(),
        _method: 'POST'
    };

    const res = await apiRequest('/api/master/profile.php', {
        method: 'POST',
        body: JSON.stringify(payload)
    });

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Simpan Perubahan Profil';

    if (res && res.success) {
        showToast('Profil perusahaan berhasil diperbarui!', 'success');
        setTimeout(() => { location.reload(); }, 800);
    } else {
        showToast(res.message || 'Gagal menyimpan profil perusahaan.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', loadCompanyProfile);
</script>

<?php
require_once __DIR__ . '/../../components/footer.php';
?>
