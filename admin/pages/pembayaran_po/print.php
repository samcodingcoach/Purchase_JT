<?php
/**
 * Halaman Cetak Bukti Pembayaran Faktur PO (Payment Voucher)
 * Path: admin/pages/pembayaran_po/print.php
 * Format: Terintegrasi API & External CSS (styles/print_document.css)
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/koneksi.php';
require_once __DIR__ . '/../../../config/session.php';

$user = requireAuth([ROLE_FINANCE, ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);
$idDetail = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['id_detail']) ? (int)$_GET['id_detail'] : 0);
$useKop = !isset($_GET['kop']) || (int)$_GET['kop'] === 1;

if ($idDetail <= 0) {
    die("ID Pembayaran tidak valid.");
}

// Ambil Data Profil Perusahaan
$profile = getCompanyProfile($conn);
$companyName = !empty($profile['nama']) ? $profile['nama'] : 'PT Jaya Teknis Indonesia';
$companyAddr = !empty($profile['alamat']) ? $profile['alamat'] : 'Jl. Perak Timur No. 100, Surabaya';
$companyCity = trim((!empty($profile['kota']) ? $profile['kota'] : '') . (!empty($profile['provinsi']) ? ', ' . $profile['provinsi'] : ''));
$companyPhone = !empty($profile['telepon1']) ? $profile['telepon1'] : '';
$companyWa = !empty($profile['whatsapp']) ? $profile['whatsapp'] : '';
$companyEmail = !empty($profile['email']) ? $profile['email'] : 'finance@jayateknis.co.id';
$companyLogo = !empty($profile['picture']) ? $profile['picture'] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran Faktur PO</title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- External Print Stylesheet -->
    <link href="<?= BASE_URL ?>/styles/print_document.css" rel="stylesheet">
</head>
<body class="print-mode">

<!-- TOOLBAR KONTROL CETAK (NO PRINT) -->
<div class="container-fluid no-print py-2 bg-dark text-white mb-3 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <span class="fw-bold fs-6">
                <i class="bi bi-printer text-info me-1"></i> Cetak Bukti Pembayaran (Voucher Kas)
            </span>
            <span class="badge bg-secondary font-monospace" id="toolbarKodeBayar">...</span>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            <!-- Pilihan Switch Kop Surat -->
            <div class="btn-group btn-group-sm me-2" role="group" aria-label="Format Kop Surat">
                <a href="?id=<?= $idDetail ?>&kop=1" class="btn <?= $useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark-richtext me-1"></i> Dengan Kop
                </a>
                <a href="?id=<?= $idDetail ?>&kop=0" class="btn <?= !$useKop ? 'btn-light fw-bold text-dark' : 'btn-outline-light' ?>">
                    <i class="bi bi-file-earmark me-1"></i> Tanpa Kop
                </a>
            </div>

            <a href="<?= BASE_URL ?>/admin/pages/pembayaran_po/index.php" class="btn btn-outline-light btn-sm px-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <button type="button" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm" onclick="window.print()">
                <i class="bi bi-printer-fill me-1"></i> Cetak Dokumen (Print / PDF)
            </button>
        </div>
    </div>
</div>

<!-- CONTAINER UTAMA DOKUMEN -->
<div class="print-wrapper <?= !$useKop ? 'no-kop' : '' ?>" id="printContainer">
    
    <?php if ($useKop): ?>
    <!-- KOP SURAT (SESUAI PROFIL PERUSAHAAN) -->
    <div class="kop-container">
        <div class="kop-left">
            <?php if (!empty($companyLogo) && file_exists(__DIR__ . '/../../../' . $companyLogo)): ?>
                <img src="<?= BASE_URL ?>/<?= htmlspecialchars($companyLogo) ?>" alt="Logo" style="width: 54px; height: 54px; object-fit: contain; flex-shrink: 0;">
            <?php else: ?>
                <svg width="54" height="54" viewBox="0 0 100 100" style="flex-shrink: 0;">
                    <polygon points="50,4 92,27 92,73 50,96 8,73 8,27" fill="none" stroke="#000" stroke-width="8" stroke-linejoin="round"/>
                    <polyline points="8,27 50,50 92,27" fill="none" stroke="#000" stroke-width="8" stroke-linejoin="round"/>
                    <line x1="50" y1="50" x2="50" y2="96" stroke="#000" stroke-width="8"/>
                    <polygon points="50,22 74,35 50,48 26,35" fill="#000"/>
                    <polygon points="26,41 46,51 46,76 26,65" fill="#000"/>
                    <polygon points="74,41 54,51 54,76 74,65" fill="#000"/>
                </svg>
            <?php endif; ?>

            <div>
                <div class="company-title"><?= htmlspecialchars($companyName) ?></div>
                <div class="company-addr"><?= htmlspecialchars($companyAddr) ?><?= $companyCity ? ' - ' . htmlspecialchars($companyCity) : '' ?></div>
                <div class="company-contacts">
                    <?php if (!empty($companyPhone)): ?>
                        <span><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($companyPhone) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($companyWa)): ?>
                        <span><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($companyWa) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($companyEmail)): ?>
                        <span><i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($companyEmail) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="tagline-container">
            <div class="tagline-divider"></div>
            <div class="tagline-text">
                VOUCHER<br>PENGELUARAN<br>KAS / BANK
            </div>
        </div>
    </div>
    
    <div class="header-divider-line"></div>
    <?php else: ?>
    <!-- MODE CETAK TANPA KOP -->
    <div class="no-print alert alert-secondary py-1 px-3 small text-center mb-3">
        <i class="bi bi-info-circle me-1"></i> <strong>Mode Cetak Tanpa Kop Surat Aktif</strong>: Bagian atas dikosongkan untuk dicetak pada kertas berkop resmi perusahaan.
    </div>
    <?php endif; ?>

    <!-- JUDUL DOKUMEN & KOTAK NOMOR DOKUMEN -->
    <div class="title-box-row">
        <div class="title-area">
            <div class="doc-title-main">BUKTI PEMBAYARAN FAKTUR PO</div>
            <div class="doc-title-sub">
                <span class="line-side"></span>
                <span class="sub-text">PAYMENT &nbsp; VOUCHER</span>
                <span class="line-side"></span>
            </div>
        </div>

        <div class="doc-meta-box">
            <div class="box-row-lbl">No. Transaksi</div>
            <div class="box-row-val font-monospace" id="docKodeBayar">-</div>
            <div class="box-divider"></div>
            <div class="box-row-lbl">Tanggal Bayar</div>
            <div class="box-row-val" id="docTanggalBayar">-</div>
        </div>
    </div>

    <!-- METADATA 2 KOLOM -->
    <div class="info-grid">
        <!-- Kolom Kiri -->
        <div class="info-col-left">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">No. Faktur Sistem</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace fw-bold text-primary" id="docNomorFaktur">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Invoice Vendor</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docInvoiceVendor">-</td>
                </tr>
                <tr>
                    <td class="lbl">No. Purchase Order</td>
                    <td class="colon">:</td>
                    <td class="val font-monospace" id="docNomorPo">-</td>
                </tr>
                <tr>
                    <td class="lbl">Skema Pembayaran</td>
                    <td class="colon">:</td>
                    <td class="val" id="docJenisBayar">-</td>
                </tr>
            </table>
        </div>

        <!-- Kolom Kanan -->
        <div class="info-col-right">
            <table class="table-meta-details">
                <tr>
                    <td class="lbl">Dibayarkan Kepada</td>
                    <td class="colon">:</td>
                    <td class="val fw-bold" id="docNamaVendor">-</td>
                </tr>
                <tr>
                    <td class="lbl">Rekening Tujuan</td>
                    <td class="colon">:</td>
                    <td class="val" id="docRekeningTujuan">-</td>
                </tr>
                <tr>
                    <td class="lbl">Site Operasional</td>
                    <td class="colon">:</td>
                    <td class="val fw-semibold" id="docSite">-</td>
                </tr>
                <tr>
                    <td class="lbl">Status Pembayaran</td>
                    <td class="colon">:</td>
                    <td class="val" id="docStatusBayar">-</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- BOX JUMLAH TRANSFER -->
    <div class="p-3 border rounded-3 bg-light mb-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="text-muted small text-uppercase fw-bold" style="font-size: 10px;">Jumlah Uang Yang Ditransfer</div>
                <div class="fs-4 fw-bold text-primary font-monospace" id="docNominalTransfer">Rp 0</div>
            </div>
            <div class="text-end small text-muted">
                <div>Bank Asal: <strong class="text-dark" id="docBankAsal">-</strong> (No. Ref: <span class="font-monospace text-dark" id="docNoRef">-</span>)</div>
                <div>Biaya Admin Bank: <span class="font-monospace text-dark" id="docBiayaAdmin">Rp 0</span></div>
            </div>
        </div>
        <div class="mt-2 pt-2 border-top small text-secondary">
            <strong>Terbilang:</strong> <em id="docTerbilang"># Nol Rupiah #</em>
        </div>
    </div>

    <!-- TABEL RINCIAN FINANSIAL FAKTUR -->
    <table class="table-items-main mb-3">
        <thead>
            <tr>
                <th style="width: 25%;">TOTAL TAGIHAN FAKTUR</th>
                <th style="width: 25%;">TRANSFER PEMBAYARAN INI</th>
                <th style="width: 25%;">BIAYA ADMIN BANK</th>
                <th style="width: 25%;">SISA HUTANG FAKTUR</th>
            </tr>
        </thead>
        <tbody class="text-center font-monospace">
            <tr>
                <td id="docTabelTagihan">Rp 0</td>
                <td class="fw-bold text-primary" id="docTabelBayar">Rp 0</td>
                <td id="docTabelAdmin">Rp 0</td>
                <td class="fw-bold" id="docTabelSisa">Rp 0</td>
            </tr>
        </tbody>
    </table>

    <!-- CATATAN TRANSAKSI -->
    <div id="docCatatanContainer" class="d-none mb-3">
        <div class="catatan-penerimaan-section mb-0">
            <div class="notes-title">Catatan Transaksi:</div>
            <div class="catatan-box" id="docCatatanTransaksi"></div>
        </div>
    </div>

    <!-- LEMBAR PENGESAHAN / TANDA TANGAN (3 KOLOM) -->
    <div class="sig-section">
        <div class="row">
            <!-- 1. Dibuat Oleh (Finance) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Dibuat Oleh</div>
                <div class="sig-header-sub" id="docSigRolePembuat">(Staff Finance)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigPembuat">-</span> &nbsp; )
                </div>
            </div>

            <!-- 2. Disetujui Oleh (Approval) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Disetujui Oleh</div>
                <div class="sig-header-sub" id="docSigRoleApprover">(Manager Finance)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigApprover">-</span> &nbsp; )
                </div>
            </div>

            <!-- 3. Diterima Oleh (Vendor) -->
            <div class="col-4 sig-col">
                <div class="sig-header-main">Diterima Oleh</div>
                <div class="sig-header-sub">(Pihak Rekanan Vendor)</div>
                <div class="sig-line-box">
                    ( &nbsp; <span class="sig-person-name" id="docSigVendor">-</span> &nbsp; )
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER BAWAH -->
    <div class="footer-line-container">
        <div class="footer-right">
            <div class="fw-bold">Halaman 1 dari 1</div>
            <div>Dicetak: <?= date('d/m/Y H:i') ?></div>
            <div>Purchasing Management System - <?= htmlspecialchars($companyName) ?></div>
        </div>
    </div>

</div>

<!-- SCRIPT LOGIC MEMUAT DATA DARI API PEMBAYARAN PO -->
<script>
const ID_DETAIL = <?= $idDetail ?>;
const BASE_URL = '<?= BASE_URL ?>';

function formatRupiah(num) {
    return 'Rp ' + (parseFloat(num) || 0).toLocaleString('id-ID');
}

function escapeHtml(text) {
    if (!text) return '';
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

function terbilangIndo(angka) {
    angka = Math.abs(parseFloat(angka) || 0);
    const satuan = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    
    if (angka < 12) return " " + satuan[Math.floor(angka)];
    if (angka < 20) return terbilangIndo(angka - 10) + " Belas";
    if (angka < 100) return terbilangIndo(Math.floor(angka / 10)) + " Puluh" + terbilangIndo(angka % 10);
    if (angka < 200) return " Seratus" + terbilangIndo(angka - 100);
    if (angka < 1000) return terbilangIndo(Math.floor(angka / 100)) + " Ratus" + terbilangIndo(angka % 100);
    if (angka < 2000) return " Seribu" + terbilangIndo(angka - 1000);
    if (angka < 1000000) return terbilangIndo(Math.floor(angka / 1000)) + " Ribu" + terbilangIndo(angka % 1000);
    if (angka < 1000000000) return terbilangIndo(Math.floor(angka / 1000000)) + " Juta" + terbilangIndo(angka % 1000000);
    if (angka < 1000000000000) return terbilangIndo(Math.floor(angka / 1000000000)) + " Miliar" + terbilangIndo(angka % 1000000000);
    if (angka < 1000000000000000) return terbilangIndo(Math.floor(angka / 1000000000000)) + " Triliun" + terbilangIndo(angka % 1000000000000);
    return "";
}

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch(`${BASE_URL}/api/pembayaran_po/index.php?id=${ID_DETAIL}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();

        if (!result || !result.success || !result.data) {
            alert(result ? result.message : 'Gagal memuat data pembayaran dari API.');
            return;
        }

        const pay = result.data;

        // Toolbar
        document.getElementById('toolbarKodeBayar').textContent = pay.kode_pembayaran || '-';
        document.title = `Bukti Pembayaran - ${pay.kode_pembayaran || 'VOUCHER'}`;

        // Header Metadata
        document.getElementById('docKodeBayar').textContent = pay.kode_pembayaran || '-';
        document.getElementById('docTanggalBayar').textContent = pay.tanggal_bayar ? pay.tanggal_bayar.replace('T', ' ').substring(0, 16) : '-';

        // Details
        document.getElementById('docNomorFaktur').textContent = pay.nomor_faktur || '-';
        document.getElementById('docInvoiceVendor').textContent = pay.nomor_faktur_vendor || '-';
        document.getElementById('docNomorPo').textContent = pay.nomor_po || '-';
        document.getElementById('docJenisBayar').innerHTML = (parseInt(pay.jenis_pembayaran) === 1) 
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle">1x Lunas</span>' 
            : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle text-dark">Kredit / Termin</span>';

        document.getElementById('docNamaVendor').textContent = pay.nama_vendor || '-';
        
        const destBank = pay.bank_tujuan || pay.bank_vendor || '-';
        const destNorek = pay.norek_tujuan || pay.norek_vendor || '-';
        const destAn = pay.an_pengiriman || pay.an_vendor || pay.nama_vendor || '-';
        document.getElementById('docRekeningTujuan').innerHTML = `${escapeHtml(destBank)} &bull; <strong class="font-monospace">${escapeHtml(destNorek)}</strong><br><span class="text-muted small">a.n. ${escapeHtml(destAn)}</span>`;

        document.getElementById('docSite').textContent = pay.nama_site || '-';
        document.getElementById('docStatusBayar').innerHTML = `<span class="badge bg-primary-subtle text-primary border">${escapeHtml(pay.status_pembayaran || 'SELESAI')}</span>`;

        // Amount Box
        const nominal = parseFloat(pay.nominal_pengiriman) || 0;
        const biayaAdmin = parseFloat(pay.biaya_admin) || 0;
        const totalTagihan = parseFloat(pay.total_tagihan) || 0;
        const sisaPiutang = parseFloat(pay.sisa_piutang) || 0;

        document.getElementById('docNominalTransfer').textContent = formatRupiah(nominal);
        document.getElementById('docBankAsal').textContent = pay.bank_pengirim || '-';
        document.getElementById('docNoRef').textContent = pay.no_ref || '-';
        document.getElementById('docBiayaAdmin').textContent = formatRupiah(biayaAdmin);
        
        const terbilangStr = terbilangIndo(nominal).trim() + " Rupiah";
        document.getElementById('docTerbilang').textContent = `# ${terbilangStr} #`;

        // Financial Table
        document.getElementById('docTabelTagihan').textContent = formatRupiah(totalTagihan);
        document.getElementById('docTabelBayar').textContent = formatRupiah(nominal);
        document.getElementById('docTabelAdmin').textContent = formatRupiah(biayaAdmin);
        
        const sisaEl = document.getElementById('docTabelSisa');
        sisaEl.textContent = formatRupiah(sisaPiutang);
        sisaEl.className = `fw-bold ${sisaPiutang <= 0 ? 'text-success' : 'text-danger'}`;

        // Catatan Transaksi
        if (pay.keterangan && pay.keterangan.trim() !== '') {
            document.getElementById('docCatatanContainer').classList.remove('d-none');
            document.getElementById('docCatatanTransaksi').innerHTML = escapeHtml(pay.keterangan).replace(/\n/g, '<br>');
        }

        // Signatures (Nama, Jabatan & Divisi Dinamis)
        const jabatanPembuat = pay.jabatan_pembuat || '';
        const divisiPembuat = pay.divisi_pembuat || '';
        let rolePembuatText = '';
        if (jabatanPembuat && divisiPembuat) {
            rolePembuatText = `(${jabatanPembuat} - ${divisiPembuat})`;
        } else if (jabatanPembuat) {
            rolePembuatText = `(${jabatanPembuat})`;
        } else {
            rolePembuatText = `(Staff Finance)`;
        }
        document.getElementById('docSigRolePembuat').textContent = rolePembuatText;

        const jabatanApprover = pay.jabatan_approver || '';
        const divisiApprover = pay.divisi_approver || '';
        let roleApproverText = '';
        if (jabatanApprover && divisiApprover) {
            roleApproverText = `(${jabatanApprover} - ${divisiApprover})`;
        } else if (jabatanApprover) {
            roleApproverText = `(${jabatanApprover})`;
        } else {
            roleApproverText = `(Pimpinan / Direksi)`;
        }
        document.getElementById('docSigRoleApprover').textContent = roleApproverText;

        document.getElementById('docSigPembuat').textContent = pay.nama_pembuat || 'Staff Finance';
        document.getElementById('docSigApprover').textContent = pay.nama_approver || 'Manager Finance';
        document.getElementById('docSigVendor').textContent = pay.nama_vendor || 'Pihak Rekanan Vendor';

    } catch (e) {
        console.error('Error fetching payment print data:', e);
    }
});
</script>
</body>
</html>
