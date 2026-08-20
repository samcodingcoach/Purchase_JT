<?php
require_once __DIR__ . '/../config/koneksi.php';

// Fetch all existing jabatans
$jabatanRes = $conn->query("SELECT id_jabatan, nama_jabatan FROM jabatan");
$jabatans = [];
while($j = $jabatanRes->fetch_assoc()) {
    $jabatans[] = (int)$j['id_jabatan'];
}

// If no jabatans, fallback to [1, 2, 3, 4]
if (empty($jabatans)) {
    $jabatans = [1, 2, 3, 4];
}

// Master Menu Template
$menuTemplates = [
    [
        'kategori' => 'MENU UTAMA',
        'nama' => 'Dashboard',
        'is_parent' => 0,
        'link' => '/admin/dashboard.php',
        'icon' => 'bi-grid-1x2-fill',
        'urutan' => 1,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'OPERASIONAL',
        'nama' => 'Request Order (RO)',
        'is_parent' => 0,
        'link' => '/admin/pages/request_order/index.php',
        'icon' => 'bi-file-earmark-text-fill',
        'urutan' => 2,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'OPERASIONAL',
        'nama' => 'Buat RO Baru',
        'is_parent' => 0,
        'link' => '/admin/pages/request_order/create.php',
        'icon' => 'bi-plus-circle',
        'urutan' => 3,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'MASTER DATA',
        'nama' => 'Profil Perusahaan',
        'is_parent' => 0,
        'link' => '/admin/pages/profile/index.php',
        'icon' => 'bi-buildings',
        'urutan' => 4,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'MASTER DATA',
        'nama' => 'Master Divisi',
        'is_parent' => 0,
        'link' => '/admin/pages/divisi/index.php',
        'icon' => 'bi-diagram-3-fill',
        'urutan' => 5,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'MASTER DATA',
        'nama' => 'Master Jabatan',
        'is_parent' => 0,
        'link' => '/admin/pages/jabatan/index.php',
        'icon' => 'bi-briefcase-fill',
        'urutan' => 6,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'MASTER DATA',
        'nama' => 'Master Site',
        'is_parent' => 0,
        'link' => '/admin/pages/site/index.php',
        'icon' => 'bi-geo-alt-fill',
        'urutan' => 7,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'MASTER DATA',
        'nama' => 'Master Karyawan',
        'is_parent' => 0,
        'link' => '/admin/pages/user/index.php',
        'icon' => 'bi-people-fill',
        'urutan' => 8,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'MASTER DATA',
        'nama' => 'Master Vendor',
        'is_parent' => 0,
        'link' => '/admin/pages/vendor/index.php',
        'icon' => 'bi-truck',
        'urutan' => 9,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ],
    [
        'kategori' => 'MASTER DATA',
        'nama' => 'Master Barang',
        'is_parent' => 1,
        'link' => '#',
        'icon' => 'bi-boxes',
        'urutan' => 10,
        'akses' => 1,
        'terlihat' => 1,
        'children' => [
            [
                'nama' => 'Kategori Barang',
                'link' => '/admin/pages/kategori/index.php',
                'icon' => 'bi-tags',
                'urutan' => 1,
                'akses' => 1,
                'terlihat' => 1
            ],
            [
                'nama' => 'Merk Barang',
                'link' => '/admin/pages/merk/index.php',
                'icon' => 'bi-bookmark-star',
                'urutan' => 2,
                'akses' => 1,
                'terlihat' => 1
            ],
            [
                'nama' => 'Katalog Barang',
                'link' => '/admin/pages/barang/index.php',
                'icon' => 'bi-box-seam',
                'urutan' => 3,
                'akses' => 1,
                'terlihat' => 1
            ],
        ]
    ],
    [
        'kategori' => 'MASTER DATA',
        'nama' => 'Manajemen Menu',
        'is_parent' => 0,
        'link' => '/admin/pages/menu/index.php',
        'icon' => 'bi-list-check',
        'urutan' => 11,
        'akses' => 1,
        'terlihat' => 1,
        'children' => []
    ]
];

// Clean existing records if any
$conn->query("TRUNCATE TABLE menu_level");

$totalInserted = 0;
foreach ($jabatans as $jId) {
    foreach ($menuTemplates as $tpl) {
        $ins = $conn->prepare("INSERT INTO menu_level (id_jabatan, kategori_menu, nama_menu, is_parent, id_parent, link, icon, urutan, akses, terlihat) VALUES (?, ?, ?, ?, NULL, ?, ?, ?, ?, ?)");
        $ins->bind_param("ississiii", $jId, $tpl['kategori'], $tpl['nama'], $tpl['is_parent'], $tpl['link'], $tpl['icon'], $tpl['urutan'], $tpl['akses'], $tpl['terlihat']);
        $ins->execute();
        $parentId = $conn->insert_id;
        $ins->close();
        $totalInserted++;

        if (!empty($tpl['children'])) {
            foreach ($tpl['children'] as $child) {
                $insC = $conn->prepare("INSERT INTO menu_level (id_jabatan, kategori_menu, nama_menu, is_parent, id_parent, link, icon, urutan, akses, terlihat) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?)");
                $insC->bind_param("ississiii", $jId, $tpl['kategori'], $child['nama'], $parentId, $child['link'], $child['icon'], $child['urutan'], $child['akses'], $child['terlihat']);
                $insC->execute();
                $insC->close();
                $totalInserted++;
            }
        }
    }
}

echo "Berhasil melakukan seeding $totalInserted baris data menu dinamis ke tabel menu_level untuk " . count($jabatans) . " jabatan." . PHP_EOL;
