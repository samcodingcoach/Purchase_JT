<?php
/**
 * Halaman Cetak Bukti Pengeluaran Kas / Pembayaran Faktur PO
 * Path: admin/pages/pembayaran_po/print.php
 * Format: Siap Print / PDF Resmi
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/session.php';
require_once __DIR__ . '/../../../config/koneksi.php';

$user = requireAuth([ROLE_FINANCE, ROLE_PURCHASING, ROLE_ADMIN, ROLE_MANAGER]);
$idDetail = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idDetail <= 0) {
    die("ID Pembayaran tidak valid.");
}

$sql = "SELECT ppd.*,
               pp.id_faktur, pp.status_pembayaran, pp.jenis_pembayaran,
               fp.nomor_faktur, fp.nomor_faktur_vendor, fp.tanggal_faktur_vendor,
               fp.tanggal_jatuh_tempo, fp.total_tagihan, fp.terbayar AS total_terbayar_faktur,
               fp.sisa_tagihan AS sisa_tagihan_faktur, fp.nama_bank AS bank_vendor,
               fp.nomor_rekening AS norek_vendor, fp.atas_nama_rekening AS an_vendor,
               po.nomor_po, po.tanggal_po,
               v.id_vendor, v.kode_vendor, v.nama_perusahaan AS nama_vendor, v.no_telepon AS telepon_vendor, v.alamat AS alamat_vendor,
               s.nama_site,
               k.nama_karyawan AS nama_pembuat,
               ka.nama_karyawan AS nama_approver,
               ja.nama_jabatan AS jabatan_approver
        FROM payment_purchase_detail ppd
        JOIN payment_purchase pp ON ppd.id_pembayaran = pp.id_pembayaran
        JOIN faktur_po fp ON pp.id_faktur = fp.id_faktur
        JOIN purchase_order po ON fp.id_po = po.id_po
        JOIN vendor v ON fp.id_vendor = v.id_vendor
        JOIN site s ON fp.id_site = s.id_site
        LEFT JOIN karyawan k ON ppd.id_karyawan = k.id_karyawan
        LEFT JOIN karyawan ka ON ppd.id_karyawan_approved = ka.id_karyawan
        LEFT JOIN jabatan ja ON ka.id_jabatan = ja.id_jabatan
        WHERE ppd.id_pembayaran_detail = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idDetail);
$stmt->execute();
$pay = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pay) {
    die("Data pembayaran tidak ditemukan.");
}

$companyProfile = getCompanyProfile();
$companyName = !empty($companyProfile['nama']) ? $companyProfile['nama'] : 'PT Jaya Teknik';
$companyAddress = !empty($companyProfile['alamat']) ? $companyProfile['alamat'] : 'Bengkel Las & Bubut Kapal';
$companyCity = !empty($companyProfile['kota']) ? $companyProfile['kota'] : 'Surabaya';
$companyPhone = !empty($companyProfile['telepon']) ? $companyProfile['telepon'] : '';

function terbilang($angka) {
    $angka = abs((float)$angka);
    $baca = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
    $terbilang = "";

    if ($angka < 12) {
        $terbilang = " " . $baca[(int)$angka];
    } else if ($angka < 20) {
        $terbilang = terbilang($angka - 10) . " Belas";
    } else if ($angka < 100) {
        $terbilang = terbilang($angka / 10) . " Puluh" . terbilang($angka % 10);
    } else if ($angka < 200) {
        $terbilang = " Seratus" . terbilang($angka - 100);
    } else if ($angka < 1000) {
        $terbilang = terbilang($angka / 100) . " Ratus" . terbilang($angka % 100);
    } else if ($angka < 2000) {
        $terbilang = " Seribu" . terbilang($angka - 1000);
    } else if ($angka < 1000000) {
        $terbilang = terbilang($angka / 1000) . " Ribu" . terbilang($angka % 1000);
    } else if ($angka < 1000000000) {
        $terbilang = terbilang($angka / 1000000) . " Juta" . terbilang($angka % 1000000);
    } else if ($angka < 1000000000000) {
        $terbilang = terbilang($angka / 1000000000) . " Milyar" . terbilang(fmod($angka, 1000000000));
    } else if ($angka < 1000000000000000) {
        $terbilang = terbilang($angka / 1000000000000) . " Triliun" . terbilang(fmod($angka, 1000000000000));
    }
    return $terbilang;
}

$terbilangNominal = trim(terbilang($pay['nominal_pengiriman'])) . " Rupiah";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Pembayaran - <?= htmlspecialchars($pay['kode_pembayaran']) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #212529;
            background-color: #f8f9fa;
        }
        .print-container {
            max-width: 850px;
            margin: 20px auto;
            background: #ffffff;
            padding: 35px 40px;
            box-shadow: 0 4px 18px rgba(0,0,0,0.08);
            border-radius: 6px;
        }
        .header-kop {
            border-bottom: 2.5px double #333333;
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .company-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0b4d75;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .company-sub {
            font-size: 0.82rem;
            color: #555;
            line-height: 1.35;
        }
        .doc-title {
            font-size: 1.25rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #111;
            text-align: center;
            margin-bottom: 3px;
        }
        .doc-number {
            font-size: 0.95rem;
            font-weight: 700;
            font-family: monospace;
            text-align: center;
            color: #0284c7;
            margin-bottom: 25px;
        }
        .table-info td {
            padding: 4px 6px;
            font-size: 0.86rem;
        }
        .amount-box {
            background-color: #f1f5f9;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 15px 20px;
            margin: 20px 0;
        }
        .signature-box {
            margin-top: 40px;
        }
        .sig-col {
            text-align: center;
        }
        .sig-line {
            border-bottom: 1px solid #333;
            width: 80%;
            margin: 60px auto 4px auto;
        }
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .print-container {
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<!-- FLOATING PRINT TOOLBAR -->
<div class="no-print text-center py-3 bg-white border-bottom shadow-sm sticky-top mb-3">
    <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-semibold shadow-sm me-2">
        <i class="bi bi-printer me-1"></i> Cetak Bukti Pembayaran
    </button>
    <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
        Tutup Jendela
    </button>
</div>

<div class="print-container">
    <!-- KOP PERUSAHAAN -->
    <div class="header-kop d-flex justify-content-between align-items-center">
        <div>
            <div class="company-title"><?= htmlspecialchars($companyName) ?></div>
            <div class="company-sub">
                <?= htmlspecialchars($companyAddress) ?><br>
                <?= htmlspecialchars($companyCity) ?> <?= $companyPhone ? ' | Telp: ' . htmlspecialchars($companyPhone) : '' ?>
            </div>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark border px-3 py-2 fs-6">VOUCHER KAS KELUAR</span>
        </div>
    </div>

    <!-- JUDUL DOKUMEN -->
    <div class="doc-title">BUKTI PEMBAYARAN FAKTUR PO</div>
    <div class="doc-number"><?= htmlspecialchars($pay['kode_pembayaran']) ?></div>

    <!-- INFO DOKUMEN & VENDOR -->
    <div class="row g-3 mb-3">
        <div class="col-6">
            <table class="table-info w-100">
                <tr>
                    <td class="text-muted" style="width: 130px;">Tanggal Bayar</td>
                    <td>: <strong><?= date('d/m/Y H:i', strtotime($pay['tanggal_bayar'])) ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">No. Faktur Sistem</td>
                    <td>: <strong class="font-monospace text-primary"><?= htmlspecialchars($pay['nomor_faktur']) ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">No. Invoice Vendor</td>
                    <td>: <span class="font-monospace"><?= htmlspecialchars($pay['nomor_faktur_vendor'] ?: '-') ?></span></td>
                </tr>
                <tr>
                    <td class="text-muted">No. Purchase Order</td>
                    <td>: <span class="font-monospace"><?= htmlspecialchars($pay['nomor_po']) ?></span></td>
                </tr>
                <tr>
                    <td class="text-muted">Skema Pembayaran</td>
                    <td>: <?= (int)$pay['jenis_pembayaran'] === 1 ? '<span class="badge bg-success">1x Lunas</span>' : '<span class="badge bg-warning text-dark">Kredit / Termin</span>' ?></td>
                </tr>
            </table>
        </div>

        <div class="col-6">
            <table class="table-info w-100">
                <tr>
                    <td class="text-muted" style="width: 130px;">Dibayarkan Kepada</td>
                    <td>: <strong><?= htmlspecialchars($pay['nama_vendor']) ?></strong></td>
                </tr>
                <?php
                $destBank = !empty($pay['bank_tujuan']) ? $pay['bank_tujuan'] : ($pay['bank_vendor'] ?: '-');
                $destNorek = !empty($pay['norek_tujuan']) ? $pay['norek_tujuan'] : ($pay['norek_vendor'] ?: '-');
                $destAn = !empty($pay['an_pengiriman']) ? $pay['an_pengiriman'] : ($pay['an_vendor'] ?: $pay['nama_vendor']);
                ?>
                <tr>
                    <td class="text-muted">Bank &amp; No. Rekening</td>
                    <td>: <?= htmlspecialchars($destBank) ?> &bull; <strong class="font-monospace"><?= htmlspecialchars($destNorek) ?></strong></td>
                </tr>
                <tr>
                    <td class="text-muted">Atas Nama Rekening</td>
                    <td>: <?= htmlspecialchars($destAn) ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Site Operasional</td>
                    <td>: <?= htmlspecialchars($pay['nama_site']) ?></td>
                </tr>
                <tr>
                    <td class="text-muted">Disetujui Oleh</td>
                    <td>: <strong><?= htmlspecialchars($pay['nama_approver'] ?: '-') ?></strong></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- KOTAK NOMINAL -->
    <div class="amount-box">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="small text-muted fw-bold text-uppercase">Jumlah Uang Yang Ditransfer</div>
                <div class="fs-4 fw-bold text-dark font-monospace">Rp <?= number_format($pay['nominal_pengiriman'], 0, ',', '.') ?></div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Bank Asal: <strong><?= htmlspecialchars($pay['bank_pengirim']) ?></strong> (No. Ref: <?= htmlspecialchars($pay['no_ref'] ?: '-') ?>)</div>
                <div class="small text-muted">Biaya Admin: Rp <?= number_format($pay['biaya_admin'], 0, ',', '.') ?></div>
            </div>
        </div>
        <div class="mt-2 pt-2 border-top small text-secondary">
            <strong>Terbilang:</strong> <em># <?= htmlspecialchars($terbilangNominal) ?> #</em>
        </div>
    </div>

    <!-- RINCIAN FINANSIAL FAKTUR -->
    <table class="table table-bordered table-sm small mb-4">
        <thead class="table-light text-center">
            <tr>
                <th>Total Tagihan Faktur</th>
                <th>Transfer Pembayaran Ini</th>
                <th>Biaya Admin Bank</th>
                <th>Sisa Hutang Faktur</th>
            </tr>
        </thead>
        <tbody class="text-center font-monospace">
            <tr>
                <td>Rp <?= number_format($pay['total_tagihan'], 0, ',', '.') ?></td>
                <td class="fw-bold text-primary">Rp <?= number_format($pay['nominal_pengiriman'], 0, ',', '.') ?></td>
                <td>Rp <?= number_format($pay['biaya_admin'], 0, ',', '.') ?></td>
                <td class="fw-bold <?= (float)$pay['sisa_piutang'] <= 0 ? 'text-success' : 'text-danger' ?>">
                    Rp <?= number_format($pay['sisa_piutang'], 0, ',', '.') ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($pay['keterangan'])): ?>
    <div class="small text-muted mb-4">
        <strong>Catatan Transaksi:</strong> <?= nl2br(htmlspecialchars($pay['keterangan'])) ?>
    </div>
    <?php endif; ?>

    <!-- TANDA TANGAN -->
    <div class="signature-box row">
        <div class="col-4 sig-col">
            <div class="small text-muted">Dibuat Oleh (Finance),</div>
            <div class="sig-line"></div>
            <div class="small fw-bold text-dark"><?= htmlspecialchars($pay['nama_pembuat'] ?: 'Staff Finance') ?></div>
        </div>

        <div class="col-4 sig-col">
            <div class="small text-muted">Disetujui Oleh (Pimpinan),</div>
            <div class="sig-line"></div>
            <div class="small fw-bold text-dark"><?= htmlspecialchars($pay['nama_approver'] ?: 'Manager Finance') ?></div>
            <div class="text-muted" style="font-size: 0.72rem;"><?= htmlspecialchars($pay['jabatan_approver'] ?: 'Manajemen') ?></div>
        </div>

        <div class="col-4 sig-col">
            <div class="small text-muted">Diterima Oleh (Vendor),</div>
            <div class="sig-line"></div>
            <div class="small fw-bold text-dark"><?= htmlspecialchars($pay['nama_vendor']) ?></div>
        </div>
    </div>
</div>

<script>
window.addEventListener('DOMContentLoaded', () => {
    // Auto-trigger print jika dibuka langsung
    if (window.location.search.includes('autoprint=1')) {
        window.print();
    }
});
</script>
</body>
</html>
