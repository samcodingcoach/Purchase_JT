-- Seed Data Awal untuk PT Jaya Teknis (Request Order Module)
-- Database: jaya_teknik / jaya_teknis

-- 1. Divisi
INSERT IGNORE INTO `divisi` (`id_divisi`, `kode_divisi`, `nama_divisi`, `level`) VALUES
(1, 'DIV01', 'Manajemen', 1),
(2, 'DIV02', 'Logistik & Gudang', 2),
(3, 'DIV03', 'Purchasing & Pengadaan', 2),
(4, 'DIV04', 'Bengkel Las & Bubut (Mekanik)', 3),
(5, 'DIV05', 'IT & Administrasi', 2);

-- 2. Karyawan (Password hash untuk 'admin123', 'mekanik123', 'logistik123', 'purchasing123', 'manager123')
INSERT IGNORE INTO `karyawan` (`id_karyawan`, `kode_karyawan`, `nama_karyawan`, `id_divisi`, `tanggal_bergabung`, `aktif`, `email`, `no_handphone`, `password`, `status_karyawan`, `login_web`) VALUES
(1, 'KRY001', 'Bambang Admin', 5, NOW(), 1, 'admin@jayateknis.com', '081234567890', '$2y$10$wTfZvW1L6pD7fW1yA0pP.O9r5hN3yE1oO5k8V3v4X5u8Y1a2b3c4d', 3, 1),
(2, 'KRY002', 'Budi Mekanik', 4, NOW(), 1, 'mekanik@jayateknis.com', '081234567891', '$2y$10$wTfZvW1L6pD7fW1yA0pP.O9r5hN3yE1oO5k8V3v4X5u8Y1a2b3c4d', 3, 1),
(3, 'KRY003', 'Agus Logistik', 2, NOW(), 1, 'logistik@jayateknis.com', '081234567892', '$2y$10$wTfZvW1L6pD7fW1yA0pP.O9r5hN3yE1oO5k8V3v4X5u8Y1a2b3c4d', 3, 1),
(4, 'KRY004', 'Siti Purchasing', 3, NOW(), 1, 'purchasing@jayateknis.com', '081234567893', '$2y$10$wTfZvW1L6pD7fW1yA0pP.O9r5hN3yE1oO5k8V3v4X5u8Y1a2b3c4d', 3, 1),
(5, 'KRY005', 'Hendra Manager', 1, NOW(), 1, 'manager@jayateknis.com', '081234567894', '$2y$10$wTfZvW1L6pD7fW1yA0pP.O9r5hN3yE1oO5k8V3v4X5u8Y1a2b3c4d', 3, 1);

-- 3. Users Login
INSERT IGNORE INTO `users` (`id_users`, `nama_users`, `email`, `password`, `aktif`) VALUES
(1, 'admin', 'admin@jayateknis.com', '$2y$10$fV27y1UuRj2qD4fC7eM5xeuY1C2D3E4F5G6H7I8J9K0L1M2N3O4P5', 1),
(2, 'mekanik', 'mekanik@jayateknis.com', '$2y$10$fV27y1UuRj2qD4fC7eM5xeuY1C2D3E4F5G6H7I8J9K0L1M2N3O4P5', 1),
(3, 'logistik', 'logistik@jayateknis.com', '$2y$10$fV27y1UuRj2qD4fC7eM5xeuY1C2D3E4F5G6H7I8J9K0L1M2N3O4P5', 1),
(4, 'purchasing', 'purchasing@jayateknis.com', '$2y$10$fV27y1UuRj2qD4fC7eM5xeuY1C2D3E4F5G6H7I8J9K0L1M2N3O4P5', 1),
(5, 'manager', 'manager@jayateknis.com', '$2y$10$fV27y1UuRj2qD4fC7eM5xeuY1C2D3E4F5G6H7I8J9K0L1M2N3O4P5', 1);

-- 4. Site / Lokasi Workshop
INSERT IGNORE INTO `site` (`id_site`, `kode_site`, `nama_site`, `jenis_site`, `alamat`, `no_hp`) VALUES
(1, 'SIT01', 'Galangan Utama Dok 1', 'Bengkel', 'Jl. Pelabuhan Maritim No. 12, Surabaya', '031-889901'),
(2, 'SIT02', 'Workshop Bubut & Las Fabrikasi', 'Bengkel', 'Kawasan Industri Gresik Blok B-4', '031-889902'),
(3, 'SIT03', 'Gudang Logistik & Suku Cadang', 'Logistik', 'Jl. Dermaga Barat No. 8, Surabaya', '031-889903'),
(4, 'SIT04', 'Kantor Pusat & Operasional', 'Office', 'Jl. Perak Timur No. 100, Surabaya', '031-889900');

-- 5. Vendor / Rekanan
INSERT IGNORE INTO `vendor` (`id_vendor`, `kode_vendor`, `nama_perusahaan`, `alamat`, `no_telepon`, `kota`, `person`, `jenis_vendor`, `aktif`, `term_of_payment`) VALUES
(1, 'VND001', 'PT Baja Maritim Nusantara', 'Kawasan Industri Rungkut Surabaya', '031-778811', 'Surabaya', 'Bpk. Gunawan', 'supplier plat baja & profil kapal', 1, 30),
(2, 'VND002', 'CV Sumber Teknik Las & Gas', 'Jl. Kalianak Barat No. 45, Surabaya', '031-778822', 'Surabaya', 'Ibu Ratna', 'distributor kawat las & perlengkapan welding', 1, 14),
(3, 'VND003', 'PT Indo Bearing & Seal Sejahtera', 'Jl. Dupak Rukun No. 88, Surabaya', '031-778833', 'Surabaya', 'Bpk. Tony', 'bearing propeller & mechanical seal', 1, 30),
(4, 'VND004', 'PT Samudera Marine Supply', 'Jl. Tanjung Perak Barat No. 20, Surabaya', '031-778844', 'Surabaya', 'Bpk. Eko', 'sparepart mesin diesel kapal & valve', 1, 45);

-- 6. Barang Suku Cadang & Material
INSERT IGNORE INTO `barang` (`id_barang`, `kode_barang`, `id_merk`, `id_kategori`, `default_id_vendor`, `nama_barang`, `jenis`, `satuan`, `asset`, `deskripsi`, `id_karyawan`) VALUES
(1, 'BRG001', 1, 1, 2, 'Kawat Las LB-52 3.2mm Kobe Steel', 1, 'PCS', 0, 'Kawat las elektroda untuk lambung kapal & konstruksi berat', 1),
(2, 'BRG002', 1, 1, 1, 'Plat Baja Marine Grade AH36 12mm x 5ft x 20ft', 1, 'UNIT', 0, 'Plat baja standar lambung kapal bersertifikat BKI/LR', 1),
(3, 'BRG003', 1, 1, 1, 'Pipa Seamless Carbon Steel Sch 80 4 Inch', 1, 'PCS', 0, 'Pipa jalur bahan bakar & pendingin kapal', 1),
(4, 'BRG004', 1, 1, 2, 'Mata Bubut Sandvik Coromant CNMG 120408', 1, 'PCS', 0, 'Insert bubut bubut as propeller dan shaft', 1),
(5, 'BRG005', 1, 1, 3, 'Bearing Spherical Roller SKF 22220 EK', 1, 'PCS', 0, 'Bearing poros baling-baling kapal', 1),
(6, 'BRG006', 1, 1, 4, 'Bronze Marine Globe Valve 3 Inch Flange PN16', 1, 'PCS', 0, 'Valve laut tahan korosi air asin', 1),
(7, 'BRG007', 1, 1, 2, 'Mata Bor HSS Morse Taper 25mm Nachi', 1, 'PCS', 0, 'Mata bor mesin radial drill bengkel bubut', 1),
(8, 'BRG008', 1, 1, 4, 'Cat Antifouling Jotun SeaForce 90 20L', 1, 'UNIT', 0, 'Cat bawah lambung kapal pencegah teritip', 1);
