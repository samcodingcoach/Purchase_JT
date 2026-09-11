<?php
require_once 'config/koneksi.php';

// Daftar Jabatan yang akan didaftarkan menu Laporan Rekapitulasi Retur Pembelian
// 1: Administrator, 2: Admin Logistik, 3: Kepala Mekanik, 4: Manager Cabang, 5: Staff Purchasing
$jabatanIds = [1, 2, 3, 4, 5];
$menuName = 'Laporan Retur Pembelian';
$link = '/admin/pages/laporan/retur_pembelian.php';
$kategori = 'LAPORAN';
$icon = 'bi-arrow-return-left';

foreach ($jabatanIds as $jId) {
    // Cek apakah sudah ada
    $chk = $conn->query("SELECT id_levelmenu FROM menu_level WHERE id_jabatan = $jId AND link = '$link'");
    if ($chk && $chk->num_rows > 0) {
        echo "Jabatan ID $jId: Menu sudah terdaftar.\n";
    } else {
        // Ambil urutan terakhir
        $uRes = $conn->query("SELECT COALESCE(MAX(urutan), 0) + 1 AS next_u FROM menu_level WHERE id_jabatan = $jId AND kategori_menu = '$kategori'");
        $nextU = $uRes ? (int)$uRes->fetch_assoc()['next_u'] : 1;

        $stmt = $conn->prepare("INSERT INTO menu_level (id_jabatan, kategori_menu, nama_menu, is_parent, id_parent, link, icon, urutan, akses, terlihat) VALUES (?, ?, ?, 0, 0, ?, ?, ?, 1, 1)");
        $stmt->bind_param("issssi", $jId, $kategori, $menuName, $link, $icon, $nextU);
        if ($stmt->execute()) {
            echo "Jabatan ID $jId: Berhasil mendaftarkan menu `$menuName`.\n";
        } else {
            echo "Jabatan ID $jId: Gagal - {$conn->error}\n";
        }
        $stmt->close();
    }
}
