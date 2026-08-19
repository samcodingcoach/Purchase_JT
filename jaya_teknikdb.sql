-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2026 at 04:02 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jaya_teknik`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int(11) NOT NULL,
  `kode_barang` varchar(30) DEFAULT NULL,
  `id_merk` int(11) DEFAULT 1,
  `id_kategori` int(11) DEFAULT 1,
  `default_id_vendor` int(11) DEFAULT NULL,
  `nama_barang` varchar(60) NOT NULL DEFAULT '1',
  `jenis` tinyint(1) DEFAULT 1 COMMENT '1 = Persediaan , 0 = Jasa',
  `satuan` enum('UNIT','PCS') DEFAULT 'PCS',
  `asset` tinyint(1) DEFAULT 0 COMMENT '1 = Asset, 0 = Bukan Asset',
  `serial_number` varchar(30) DEFAULT NULL,
  `foto1` varchar(50) DEFAULT NULL,
  `foto2` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `id_karyawan` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang_hargavendor`
--

CREATE TABLE `barang_hargavendor` (
  `id_harga` int(11) NOT NULL,
  `id_barang` int(11) DEFAULT NULL,
  `id_vendor` int(11) DEFAULT NULL,
  `harga_set` double DEFAULT NULL,
  `berlaku` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barang_stok`
--

CREATE TABLE `barang_stok` (
  `id_stok` int(11) NOT NULL,
  `id_barang` int(11) NOT NULL,
  `id_site` int(11) NOT NULL,
  `stok` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `divisi`
--

CREATE TABLE `divisi` (
  `id_divisi` int(11) NOT NULL,
  `kode_divisi` varchar(10) DEFAULT NULL,
  `nama_divisi` varchar(50) DEFAULT NULL,
  `level` tinyint(1) DEFAULT NULL,
  `id_karyawan_headof` int(11) DEFAULT NULL COMMENT 'jadikan approval'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `karyawan`
--

CREATE TABLE `karyawan` (
  `id_karyawan` int(11) NOT NULL,
  `kode_karyawan` varchar(255) DEFAULT NULL,
  `nama_karyawan` varchar(255) DEFAULT NULL,
  `id_divisi` int(11) NOT NULL,
  `tanggal_bergabung` datetime DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `no_handphone` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status_karyawan` tinyint(1) DEFAULT NULL COMMENT 'Magang (Internship), PKWT (Perjanjian Kerja Waktu Tertentu), PKWTT (Perjanjian Kerja Waktu Tidak Tertentu), Pekerja paruh waktu (Part-time), Harian Lepas (Casual Workers), Freelance / Pekerja Lepas, Outsourcing / Alih Daya, Volunteer / Sukarelawan\r\n',
  `login_web` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kategori_barang`
--

CREATE TABLE `kategori_barang` (
  `id_kategori` int(11) NOT NULL,
  `kode_kategori` varchar(10) DEFAULT NULL,
  `nama_kategori` varchar(50) DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_barang`
--

INSERT INTO `kategori_barang` (`id_kategori`, `kode_kategori`, `nama_kategori`, `aktif`) VALUES
(1, 'KAT0001', 'Umum', 1);

-- --------------------------------------------------------

--
-- Table structure for table `merk_barang`
--

CREATE TABLE `merk_barang` (
  `id_merk` int(11) NOT NULL,
  `kode_merk` varchar(10) DEFAULT NULL,
  `nama_merk` varchar(30) DEFAULT NULL,
  `aktif` tinyint(4) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `merk_barang`
--

INSERT INTO `merk_barang` (`id_merk`, `kode_merk`, `nama_merk`, `aktif`) VALUES
(1, 'MRK0001', 'Umum', 1);

-- --------------------------------------------------------

--
-- Table structure for table `profile`
--

CREATE TABLE `profile` (
  `id_perusahaan` int(11) NOT NULL,
  `nama` varchar(60) DEFAULT NULL,
  `telepon1` varchar(30) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `alamat_gps` varchar(50) DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `provinsi` varchar(50) DEFAULT NULL,
  `npwp` varchar(30) DEFAULT NULL,
  `KLU` varchar(30) DEFAULT NULL,
  `NITKU` varchar(30) DEFAULT NULL,
  `pajak12` tinyint(1) DEFAULT 1,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `picture` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_order`
--

CREATE TABLE `request_order` (
  `id_request` int(11) NOT NULL,
  `nomor` varchar(20) DEFAULT NULL,
  `tanggal_ro` datetime DEFAULT current_timestamp(),
  `id_karyawan` int(11) DEFAULT NULL,
  `id_site` int(11) DEFAULT NULL,
  `status` enum('DRAFT','TERKIRIM','DISETUJUI','TIDAK DISETUJUI','BATAL') DEFAULT 'DRAFT',
  `id_vendor` int(11) DEFAULT NULL,
  `tanggal_status` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `keterangan` text DEFAULT NULL,
  `id_po` int(11) DEFAULT NULL COMMENT 'Jika sudah terbit jadi po'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_order_detail`
--

CREATE TABLE `request_order_detail` (
  `id_request_detail` int(11) NOT NULL,
  `id_request` int(11) NOT NULL,
  `kode_barang` varchar(30) DEFAULT NULL,
  `nama_barang` varchar(60) DEFAULT NULL,
  `qty` double DEFAULT NULL,
  `satuan` enum('UNIT','PCS') DEFAULT 'PCS',
  `harga` double DEFAULT 0,
  `subtotal` double DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `site`
--

CREATE TABLE `site` (
  `id_site` int(11) NOT NULL,
  `kode_site` varchar(10) DEFAULT NULL,
  `nama_site` varchar(50) DEFAULT NULL,
  `jenis_site` varchar(30) DEFAULT NULL COMMENT 'Pusat, Office, Bengkel, Logistik, Lain Lain',
  `alamat` text DEFAULT NULL,
  `alamat_gps` varchar(50) DEFAULT NULL,
  `no_hp` varchar(30) DEFAULT NULL,
  `id_karyawan_headof` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_users` int(11) NOT NULL,
  `nama_users` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(100) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor`
--

CREATE TABLE `vendor` (
  `id_vendor` int(11) NOT NULL,
  `kode_vendor` varchar(10) NOT NULL,
  `nama_perusahaan` varchar(60) NOT NULL,
  `alamat` text DEFAULT NULL,
  `gps_alamat` varchar(50) DEFAULT NULL,
  `no_telepon` varchar(255) DEFAULT NULL,
  `kota` varchar(50) DEFAULT NULL,
  `person` varchar(30) DEFAULT NULL,
  `kontak_person` varchar(30) DEFAULT NULL,
  `website` varchar(30) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `update_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `jenis_vendor` varchar(50) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `nomor_rekening` varchar(30) DEFAULT NULL,
  `nama_bank` varchar(255) DEFAULT NULL,
  `term_of_payment` int(11) DEFAULT NULL COMMENT 'days',
  `saldo_hutang_terakhir` double NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `barang_hargavendor`
--
ALTER TABLE `barang_hargavendor`
  ADD PRIMARY KEY (`id_harga`);

--
-- Indexes for table `barang_stok`
--
ALTER TABLE `barang_stok`
  ADD PRIMARY KEY (`id_stok`);

--
-- Indexes for table `divisi`
--
ALTER TABLE `divisi`
  ADD PRIMARY KEY (`id_divisi`);

--
-- Indexes for table `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id_karyawan`);

--
-- Indexes for table `kategori_barang`
--
ALTER TABLE `kategori_barang`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `merk_barang`
--
ALTER TABLE `merk_barang`
  ADD PRIMARY KEY (`id_merk`);

--
-- Indexes for table `profile`
--
ALTER TABLE `profile`
  ADD PRIMARY KEY (`id_perusahaan`);

--
-- Indexes for table `request_order`
--
ALTER TABLE `request_order`
  ADD PRIMARY KEY (`id_request`);

--
-- Indexes for table `request_order_detail`
--
ALTER TABLE `request_order_detail`
  ADD PRIMARY KEY (`id_request_detail`);

--
-- Indexes for table `site`
--
ALTER TABLE `site`
  ADD PRIMARY KEY (`id_site`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_users`);

--
-- Indexes for table `vendor`
--
ALTER TABLE `vendor`
  ADD PRIMARY KEY (`id_vendor`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang_hargavendor`
--
ALTER TABLE `barang_hargavendor`
  MODIFY `id_harga` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `barang_stok`
--
ALTER TABLE `barang_stok`
  MODIFY `id_stok` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `divisi`
--
ALTER TABLE `divisi`
  MODIFY `id_divisi` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kategori_barang`
--
ALTER TABLE `kategori_barang`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `merk_barang`
--
ALTER TABLE `merk_barang`
  MODIFY `id_merk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `request_order`
--
ALTER TABLE `request_order`
  MODIFY `id_request` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_order_detail`
--
ALTER TABLE `request_order_detail`
  MODIFY `id_request_detail` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_users` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor`
--
ALTER TABLE `vendor`
  MODIFY `id_vendor` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
