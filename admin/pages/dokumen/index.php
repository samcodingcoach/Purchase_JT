<?php
/**
 * Halaman Manajemen & Arsip Lampiran Dokumen - PT Jaya Teknis
 * Path: admin/pages/dokumen/index.php
 */
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

// Auth Protection
$user = requireAuth([ROLE_ADMIN, ROLE_MEKANIK, ROLE_LOGISTIK, ROLE_PURCHASING, ROLE_MANAGER]);
$pageTitle = 'Arsip & Lampiran Dokumen';
$pageHeading = 'Arsip & Lampiran Dokumen';

// Include Header, Sidebar & Navbar Components
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../../components/sidebar.php';
require_once __DIR__ . '/../../components/navbar.php';
?>

<!-- KONTEN UTAMA -->
<div class="container-fluid px-0">
    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-0">Arsip &amp; Lampiran Dokumen</h4>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm px-3 fw-semibold shadow-sm" onclick="openModalUploadDokumen('PURCHASE', 'PAGE_INDEX')">
                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Upload Dokumen Baru
            </button>
        </div>
    </div>

    <style>
    .doc-filter-bar .form-control,
    .doc-filter-bar .form-select,
    .doc-filter-bar .input-group-text,
    .doc-filter-bar .btn {
        height: 38px;
        font-size: 0.85rem;
    }
    .doc-filter-bar .input-group-text {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-left: 12px;
        padding-right: 12px;
    }
    </style>

    <!-- FILTER & SEARCH CARD -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4 doc-filter-bar">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-md-4 col-12">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-start-0" id="searchKeyword" placeholder="Cari No. Dokumen, Label Nama, atau Pengunggah..." oninput="debounceSearch()">
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <select class="form-select" id="filterTipe" onchange="loadTableDokumen(1)">
                        <option value="">-- Semua Tipe Dokumen --</option>
                        <option value="REQUEST">REQUEST ORDER</option>
                        <option value="PURCHASE">PURCHASE ORDER</option>
                        <option value="RECEIVING">RECEIVING (PENERIMAAN)</option>
                        <option value="RETUR PO">RETUR PO</option>
                        <option value="FAKTUR PO">FAKTUR PO</option>
                        <option value="PAYMENT PO">PAYMENT PO (PEMBAYARAN)</option>
                        <option value="MUTASI BARANG">MUTASI BARANG</option>
                        <option value="ADJUSTMENT STOK">ADJUSTMENT STOK</option>
                    </select>
                </div>
                <div class="col-md-3 col-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-calendar3"></i></span>
                        <input type="date" class="form-control border-start-0" id="filterTanggal" onchange="loadTableDokumen(1)">
                    </div>
                </div>
                <div class="col-md-2 col-12 text-end">
                    <button type="button" class="btn btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center" onclick="resetFilterDokumen()">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL LIST DOKUMEN -->
    <div class="card border-0 shadow-sm rounded-3 bg-white">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0" id="tableDokumenMain" style="font-size: 0.86rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 45px;">No</th>
                            <th style="width: 140px;">Tipe Dokumen</th>
                            <th style="width: 170px;">Nomor Transaksi</th>
                            <th>Nama / Keterangan Dokumen</th>
                            <th style="width: 130px;">Tipe Berkas</th>
                            <th style="width: 110px;">Tanggal</th>
                            <th style="width: 180px;">Diupload Oleh</th>
                            <th class="text-center" style="width: 80px;">Unduh</th>
                            <th class="text-center" style="width: 110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyDokumenMain">
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat daftar arsip dokumen...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small text-muted" id="paginationInfo">Menampilkan 0 data</div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="paginationList"></ul>
            </nav>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../components/footer.php'; ?>

<script>
let currentPage = 1;
let debounceTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    loadTableDokumen(1);
});

function debounceSearch() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        loadTableDokumen(1);
    }, 300);
}

function resetFilterDokumen() {
    document.getElementById('searchKeyword').value = '';
    document.getElementById('filterTipe').value = '';
    document.getElementById('filterTanggal').value = '';
    loadTableDokumen(1);
}

async function loadTableDokumen(page = 1) {
    currentPage = page;
    const tbody = document.getElementById('tbodyDokumenMain');
    const badgeTotal = document.getElementById('badgeTotalRecords');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationList = document.getElementById('paginationList');

    const q = document.getElementById('searchKeyword').value.trim();
    const tipe = document.getElementById('filterTipe').value;
    const tgl = document.getElementById('filterTanggal').value;

    tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Memuat data...</td></tr>`;

    try {
        const url = `${BASE_URL}/api/dokumen/list_all.php?page=${page}&limit=15&q=${encodeURIComponent(q)}&tipe=${encodeURIComponent(tipe)}&tanggal=${encodeURIComponent(tgl)}`;
        const res = await fetch(url);
        const json = await res.json();

        if (json.success && json.data) {
            const items = json.data.items || [];
            const total = json.data.pagination.total_records || 0;
            const totalPages = json.data.pagination.total_pages || 1;

            if (badgeTotal) badgeTotal.innerText = `${total} Data`;
            if (paginationInfo) paginationInfo.innerText = `Menampilkan ${items.length} dari ${total} total arsip dokumen`;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5 text-muted"><i class="bi bi-folder2-open d-block fs-2 mb-2 opacity-50"></i>Tidak ada data dokumen ditemukan.</td></tr>`;
                if (paginationList) paginationList.innerHTML = '';
                return;
            }

            let html = '';
            items.forEach((item, idx) => {
                const no = (page - 1) * 15 + idx + 1;
                let badgeTipe = '';
                switch (item.tipe_dokumen) {
                    case 'REQUEST': badgeTipe = '<span class="badge bg-secondary text-dark border">REQUEST ORDER</span>'; break;
                    case 'PURCHASE': badgeTipe = '<span class="badge bg-primary-subtle text-primary border">PURCHASE ORDER</span>'; break;
                    case 'RECEIVING': badgeTipe = '<span class="badge bg-info-subtle text-info-emphasis border">RECEIVING</span>'; break;
                    case 'RETUR PO': badgeTipe = '<span class="badge bg-danger-subtle text-danger border">RETUR PO</span>'; break;
                    case 'FAKTUR PO': badgeTipe = '<span class="badge bg-warning-subtle text-warning-emphasis border">FAKTUR PO</span>'; break;
                    case 'PAYMENT PO': badgeTipe = '<span class="badge bg-success-subtle text-success border">PAYMENT PO</span>'; break;
                    case 'MUTASI BARANG': badgeTipe = '<span class="badge bg-dark-subtle text-dark border">MUTASI</span>'; break;
                    case 'ADJUSTMENT STOK': badgeTipe = '<span class="badge bg-primary text-white">ADJUSTMENT</span>'; break;
                    default: badgeTipe = `<span class="badge bg-light text-dark border">${escapeHtml(item.tipe_dokumen || '-')}</span>`;
                }

                let badgeExt = '';
                let isExt = !item.file && item.external_url;
                if (isExt) {
                    badgeExt = `<span class="badge bg-warning-subtle text-warning-emphasis border"><i class="bi bi-google me-1"></i>Google Drive</span>`;
                } else {
                    const ext = (item.file_ext || '').toUpperCase();
                    let color = 'secondary';
                    if (ext === 'PDF') color = 'danger';
                    else if (['JPG','JPEG','PNG','WEBP'].includes(ext)) color = 'primary';
                    else if (['XLS','XLSX'].includes(ext)) color = 'success';
                    else if (['DOC','DOCX'].includes(ext)) color = 'info';
                    else if (['ZIP','RAR'].includes(ext)) color = 'dark';
                    badgeExt = `<span class="badge bg-${color}-subtle text-${color} border font-monospace">${ext || 'FILE'}</span>`;
                }

                const lockBadge = item.is_protected ? `<span class="badge bg-danger-subtle text-danger ms-1" title="Dilindungi Password"><i class="bi bi-lock-fill"></i></span>` : '';

                html += `
                    <tr>
                        <td class="text-center text-muted fw-semibold">${no}</td>
                        <td>${badgeTipe}</td>
                        <td>
                            <strong class="font-monospace text-primary">${escapeHtml(item.nomor_dokumen || '-')}</strong>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">${escapeHtml(item.nama_dokumen || '-')} ${lockBadge}</div>
                        </td>
                        <td>${badgeExt}</td>
                        <td class="small font-monospace">${item.tanggal_dokumen || '-'}</td>
                        <td>
                            <div class="small fw-semibold text-dark">${escapeHtml(item.nama_karyawan || 'Internal / Sistem')}</div>
                            <div class="text-muted" style="font-size: 0.72rem;">${escapeHtml(item.nama_jabatan || '')}</div>
                        </td>
                        <td class="text-center font-monospace small">${item.unduh || 0}x</td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="handleOpenDokumen(${item.id_dokumen}, ${item.is_protected ? 'true' : 'false'}, '${item.file_url ? item.file_url.replace(/'/g, "\\'") : ''}', '${item.external_url ? item.external_url.replace(/'/g, "\\'") : ''}', 'PAGE_INDEX')" title="Buka / Download">
                                    <i class="bi ${isExt ? 'bi-box-arrow-up-right' : 'bi-download'}"></i>
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteDokumenFromIndex(${item.id_dokumen}, '${escapeHtml(item.nama_dokumen || '')}')" title="Hapus Dokumen">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;

            // Render pagination
            let pagHtml = '';
            for (let i = 1; i <= totalPages; i++) {
                pagHtml += `<li class="page-item ${i === page ? 'active' : ''}"><button class="page-link" onclick="loadTableDokumen(${i})">${i}</button></li>`;
            }
            paginationList.innerHTML = pagHtml;
        } else {
            tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5 text-danger">Gagal memuat daftar dokumen: ${json.message || ''}</td></tr>`;
        }
    } catch (e) {
        console.error('Error load all dokumen:', e);
        tbody.innerHTML = `<tr><td colspan="9" class="text-center py-5 text-danger">Terjadi kesalahan saat memuat data dari server.</td></tr>`;
    }
}

async function deleteDokumenFromIndex(idDokumen, namaDokumen) {
    if (!confirm(`Apakah Anda yakin ingin menghapus arsip dokumen "${namaDokumen}"?`)) {
        return;
    }

    try {
        const res = await fetch(`${BASE_URL}/api/dokumen/index.php?action=delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id_dokumen: parseInt(idDokumen) })
        });
        const json = await res.json();

        if (json.success) {
            showToast(json.message || 'Dokumen berhasil dihapus.', 'success');
            loadTableDokumen(currentPage);
        } else {
            showToast(json.message || 'Gagal menghapus dokumen.', 'danger');
        }
    } catch (e) {
        showToast('Terjadi kesalahan koneksi saat menghapus dokumen.', 'danger');
    }
}
</script>
