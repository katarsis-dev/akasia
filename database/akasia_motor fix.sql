-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 07, 2026 at 06:48 PM
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
-- Database: `akasia_motor`
--

-- --------------------------------------------------------

--
-- Table structure for table `antrean_harian`
--

CREATE TABLE `antrean_harian` (
  `id` int(11) NOT NULL,
  `tanggal_servis` date NOT NULL,
  `nomor_terakhir` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jenis_layanan`
--

CREATE TABLE `jenis_layanan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `estimasi_durasi` int(11) NOT NULL DEFAULT 0 COMMENT 'Durasi dalam menit',
  `estimasi_biaya_jasa` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 jika layanan bersifat dinamis/custom',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Aktif, 0 = Nonaktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jenis_layanan`
--

INSERT INTO `jenis_layanan` (`id`, `nama`, `deskripsi`, `estimasi_durasi`, `estimasi_biaya_jasa`, `is_custom`, `is_active`) VALUES
(6, 'Paket Servis Ringan', 'Paket Servis Ringan merupakan Servis berkala dasar untuk menjaga performa motor tetap optimal.', 45, 75000.00, 0, 1),
(7, 'Paket Servis Sedang', 'Paket Servis Sedang merupakan perawatan lebih menyeluruh untuk menjaga performa dan keamanan kendaraan anda', 90, 120000.00, 0, 1),
(8, 'Paket Servis Berat', 'Paket Servis Berat adalah perawatan menyeluruh dan mendalam untuk menjaga performa kendaraan tetap maksimal dan mencegah kerusakan lebih lanjut', 150, 200000.00, 0, 1),
(9, 'Servis Umum', 'Pelanggan dapat memilih sendiri kegiatan servis sesuai kebutuhan atau keluhan kendaraan.', 0, 0.00, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kategori_motor`
--

CREATE TABLE `kategori_motor` (
  `id_kategori` int(11) NOT NULL,
  `nama_kategori` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori_motor`
--

INSERT INTO `kategori_motor` (`id_kategori`, `nama_kategori`) VALUES
(1, 'Matic'),
(2, 'Cub'),
(3, 'Sport'),
(4, 'Trail'),
(5, 'Touring'),
(6, 'Custom');

-- --------------------------------------------------------

--
-- Table structure for table `kegiatan_servis`
--

CREATE TABLE `kegiatan_servis` (
  `id` int(11) NOT NULL,
  `jenis_layanan_id` int(11) NOT NULL,
  `nama_kegiatan` varchar(150) NOT NULL,
  `estimasi_durasi` int(11) NOT NULL DEFAULT 0 COMMENT 'Durasi dalam menit',
  `estimasi_biaya` decimal(12,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Aktif, 0 = Nonaktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kegiatan_servis`
--

INSERT INTO `kegiatan_servis` (`id`, `jenis_layanan_id`, `nama_kegiatan`, `estimasi_durasi`, `estimasi_biaya`, `is_active`) VALUES
(35, 6, 'Pemeriksaan & Pergantian Oli Mesin', 0, 0.00, 1),
(36, 6, 'Pemeriksaan Busi', 0, 0.00, 1),
(37, 6, 'Pembersihan Busi', 0, 0.00, 1),
(38, 6, 'Pemeriksaan Filter Udara', 0, 0.00, 1),
(39, 6, 'Pembersihan Filter Udara', 0, 0.00, 1),
(40, 6, 'Pemeriksaan Sistem Rem Depan', 0, 0.00, 1),
(41, 6, 'Pemeriksaan Sistem Rem Belakang', 0, 0.00, 1),
(42, 6, 'Penyetelan Rem Depan & Belakang', 0, 0.00, 1),
(43, 6, 'Pemeriksaan Tekanan & Kondisi Ban', 0, 0.00, 1),
(44, 6, 'Pemeriksaan Aki', 0, 0.00, 1),
(45, 6, 'Pemeriksaan Lampu dan Klakson', 0, 0.00, 1),
(46, 6, 'Pengencangan Baut dan Mur', 0, 0.00, 1),
(47, 6, 'Pemeriksaan Rantai dan Pelumasan', 0, 0.00, 1),
(48, 6, 'Pemeriksaan Kabel dan Soket Kelistrikan', 0, 0.00, 1),
(49, 6, 'Pemeriksaan Kebocoran Umum', 0, 0.00, 1),
(50, 7, 'Pemeriksaan Kondisi Oli Mesin', 0, 0.00, 1),
(51, 7, 'Pemeriksaan Kebocoran Oli', 0, 0.00, 1),
(52, 7, 'Pemeriksaan Busi', 0, 0.00, 1),
(53, 7, 'Pemeriksaan Filter Udara', 0, 0.00, 1),
(54, 7, 'Pembersihan Filter Udara', 0, 0.00, 1),
(55, 7, 'Pemeriksaan Sistem Rem Depan', 0, 0.00, 1),
(56, 7, 'Pemeriksaan Sistem Rem Belakang', 0, 0.00, 1),
(57, 7, 'Penyetelan Rem', 0, 0.00, 1),
(58, 7, 'Pemeriksaan Tekanan Ban', 0, 0.00, 1),
(59, 7, 'Pemeriksaan Kondisi Ban', 0, 0.00, 1),
(60, 7, 'Pemeriksaan Aki', 0, 0.00, 1),
(61, 7, 'Pemeriksaan Lampu dan Klakson', 0, 0.00, 1),
(62, 7, 'Pengencangan Baut dan Mur', 0, 0.00, 1),
(63, 7, 'Pemeriksaan Rantai dan Pelumasan', 0, 0.00, 1),
(64, 7, 'Pemeriksaan Kabel dan Socket Kelistrikan', 0, 0.00, 1),
(65, 7, 'Pemeriksaan Kebocoran Umum', 0, 0.00, 1),
(66, 7, 'Pemeriksaan Sistem Bahan Bakar', 0, 0.00, 1),
(67, 7, 'Pembersihan Sistem Bahan Bakar(injektor/karburator)', 0, 0.00, 1),
(68, 7, 'Pemeriksaan dan Penyetelan Putaran Idle', 0, 0.00, 1),
(69, 7, 'Pemeriksaan Throtle Body', 0, 0.00, 1),
(70, 7, 'Pemeriksaan Sistem Pendingin (Jika Ada)', 0, 0.00, 1),
(71, 7, 'Pemeriksaan Sistem Transmisi (Jika Ada)', 0, 0.00, 1),
(72, 7, 'Pemeriksaan Sistem Pengapian', 0, 0.00, 1),
(73, 7, 'Pemeriksaan Komponen Penggerak (Vbelt,Roller,Gear)', 0, 0.00, 1),
(74, 7, 'Pemeriksaan Suspensi Depan dan Belakang', 0, 0.00, 1),
(75, 7, 'Pemeriksaan Stang Kemudi', 0, 0.00, 1),
(76, 7, 'Pemeriksaan Bearing Roda', 0, 0.00, 1),
(77, 7, 'Pemeriksaan Kebocoran Mesin', 0, 0.00, 1),
(78, 8, 'Pemeriksaan Kondisi Oli Mesin', 0, 0.00, 1),
(79, 8, 'Pemeriksaan Kebocoran Oli', 0, 0.00, 1),
(80, 8, 'Pemeriksaan & Pembersihan Busi', 0, 0.00, 1),
(81, 8, 'Pemeriksaan dan Pembersihan Filter Udara', 0, 0.00, 1),
(82, 8, 'Pemeriksaan dan Penyetelan Sistem Rem Depan dan Belakang', 0, 0.00, 1),
(83, 8, 'Pemeriksaan Tekanan dan Kondisi Ban', 0, 0.00, 1),
(84, 8, 'Pemeriksaan Aki', 0, 0.00, 1),
(85, 8, 'Pemeriksaan Lampu dan Klakson', 0, 0.00, 1),
(86, 8, 'Pengencangan Baut dan Mur', 0, 0.00, 1),
(87, 8, 'Pemeriksaan Rantai dan Pelumasan', 0, 0.00, 1),
(88, 8, 'Pemeriksaan Kabel dan Soket Kelistrikan', 0, 0.00, 1),
(89, 8, 'Pemeriksaan Kebocoran Umum', 0, 0.00, 1),
(90, 8, 'Pemeriksaan Sistem Bahan Bakar', 0, 0.00, 1),
(91, 8, 'Pembersihan Sistem Bahan Bakar(injektor/karburator)', 0, 0.00, 1),
(92, 8, 'Pemeriksaan dan Penyetelan Putaran Idle', 0, 0.00, 1),
(93, 8, 'Pemeriksaan Throtle Body', 0, 0.00, 1),
(94, 8, 'Pemeriksaan Sistem Pendingin(Jika Ada)', 0, 0.00, 1),
(95, 8, 'Pemeriksaan Sistem Transmisi (Jika Ada)', 0, 0.00, 1),
(96, 8, 'Pemeriksaan Sistem Pengapian', 0, 0.00, 1),
(97, 8, 'Pemeriksaan Komponen Penggerak', 0, 0.00, 1),
(98, 8, 'Pemeriksaan Suspensi Depan dan Belakang', 0, 0.00, 1),
(99, 8, 'Pemeriksaan Stang Kemudi', 0, 0.00, 1),
(100, 8, 'Pemeriksaan Bearing Roda', 0, 0.00, 1),
(101, 8, 'Pemeriksaan Kebocoran Mesin', 0, 0.00, 1),
(102, 8, 'Penggantian Oli Mesin', 0, 0.00, 1),
(103, 8, 'Penggantian Filter Oli', 0, 0.00, 1),
(104, 8, 'Penggantian Busi', 0, 0.00, 1),
(105, 8, 'Penggantian Filter Udara', 0, 0.00, 1),
(106, 8, 'Penggantian Minyak Rem', 0, 0.00, 1),
(107, 8, 'Pembersihan Throtle Body/Karbon Total', 0, 0.00, 1),
(108, 8, 'Pembersihan Ruang Bakar', 0, 0.00, 1),
(109, 8, 'Pemeriksaan Kompresi Mesin', 0, 0.00, 1),
(110, 8, 'Pemeriksaan dan Pembersihan Klep/Valve', 0, 0.00, 1),
(111, 8, 'Pemeriksaan Sistem Kopling (Jika Ada)', 0, 0.00, 1),
(112, 8, 'Pemeriksaan Sistem Starter', 0, 0.00, 1),
(113, 8, 'Pemeriksaan Sistem Pengisian (Spul,Kiprok, dan arus pengisian Aki)', 0, 0.00, 1),
(114, 8, 'Pemeriksaan Caliper Rem', 0, 0.00, 1),
(115, 8, 'Pemeriksaan Bearing Leher Kemudi', 0, 0.00, 1),
(116, 8, 'Test Ride/Uji Jalan', 0, 0.00, 1),
(117, 9, 'Pemeriksaan & Penggantian Oli Mesin', 15, 10000.00, 1),
(118, 9, 'Pemeriksaan dan Servis Busi', 15, 10000.00, 1),
(119, 9, 'Pemeriksaan dan Servis Filter Udara', 15, 10.00, 1),
(120, 9, 'Pemeriksaan & Servis Sistem Rem Depan', 20, 10000.00, 1),
(121, 9, 'Pemeriksaan & Servis Sistem Rem Belakang', 20, 10000.00, 1),
(122, 9, 'Pemeriksaan dan Ganti Ban', 15, 10000.00, 1),
(123, 9, 'Pemeriksaan & Servis Aki', 15, 15000.00, 1),
(124, 9, 'Pemeriksaan Lampu & Klakson', 10, 10000.00, 1),
(125, 9, 'Pengencangan Baut dan Mur', 10, 10000.00, 1),
(126, 9, 'Pemeriksaan & Servis Rantai', 15, 15000.00, 1),
(127, 9, 'Pemeriksaan Sistem Kelistrikan', 30, 35000.00, 1),
(128, 9, 'Servis Karburator', 30, 35000.00, 1),
(129, 9, 'Servis Throttle Body/Injektor', 45, 40000.00, 1),
(130, 9, 'Pemeriksaan & Penyetelan Putaran Idle', 15, 15000.00, 1),
(131, 9, 'Pemeriksaan Sistem Pendingin', 15, 15000.00, 1),
(132, 9, 'Pemeriksaan Sistem Transmisi', 20, 20000.00, 1),
(133, 9, 'Pemeriksaan Sistem Pengapian', 20, 25000.00, 1),
(134, 9, 'Servis CVT', 45, 45000.00, 1),
(135, 9, 'Pemeriksaan Suspensi', 20, 15000.00, 1),
(136, 9, 'Pemeriksaan Stang Kemudi', 20, 20000.00, 1),
(137, 9, 'Pemeriksaan Bearing Roda', 30, 25000.00, 1),
(138, 9, 'Pemeriksaan Kompresi Mesin', 20, 25000.00, 1),
(139, 9, 'Setel Klep', 60, 40000.00, 1),
(140, 9, 'Pemeriksaan Sistem Kopling', 30, 25000.00, 1),
(141, 9, 'Pemeriksaan Sistem Starter', 20, 20000.00, 1),
(142, 9, 'Pemeriksaan Sistem Pengisian (Spul,Kiprok, dan arus pengisian Aki)', 20, 25000.00, 1),
(143, 9, 'Servis Kaliper Rem', 30, 25000.00, 1),
(144, 9, 'Servis Bearing Leher Kemudi', 45, 35000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `kendaraan`
--

CREATE TABLE `kendaraan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `merk_kendaraan` varchar(50) NOT NULL,
  `jenis_kendaraan` varchar(50) NOT NULL,
  `model_kendaraan` varchar(100) NOT NULL,
  `tahun_kendaraan` year(4) DEFAULT NULL,
  `warna` varchar(30) DEFAULT NULL,
  `no_plat` varchar(20) NOT NULL,
  `no_rangka` varchar(50) NOT NULL,
  `no_mesin` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kendaraan`
--

INSERT INTO `kendaraan` (`id`, `user_id`, `merk_kendaraan`, `jenis_kendaraan`, `model_kendaraan`, `tahun_kendaraan`, `warna`, `no_plat`, `no_rangka`, `no_mesin`, `created_at`, `updated_at`) VALUES
(3, 14, 'Honda', 'Matic', 'Beat FI', '2023', 'Putih', 'AG 5909 RAG', 'Y5676Y', 'Y7667YT', '2026-07-06 18:59:58', '2026-07-06 18:59:58');

-- --------------------------------------------------------

--
-- Table structure for table `mekanik`
--

CREATE TABLE `mekanik` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `status` enum('tersedia','sibuk','libur') NOT NULL DEFAULT 'tersedia',
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mekanik`
--

INSERT INTO `mekanik` (`id`, `nama`, `no_hp`, `status`, `foto`, `created_at`) VALUES
(1, 'SHEVA PRIAT', '6282134219804', 'tersedia', NULL, '2026-05-16 18:04:59'),
(2, 'ANDI NUGROHO', '6285432178431', 'tersedia', NULL, '2026-05-16 18:04:59'),
(3, 'PURNAMA', '6281433875213', 'tersedia', NULL, '2026-05-16 18:04:59'),
(4, 'WIDODO', '6281452109841', 'tersedia', NULL, '2026-06-20 05:01:02'),
(6, 'SAYUDI', '62118291829128', 'tersedia', NULL, '2026-07-01 16:59:14');

-- --------------------------------------------------------

--
-- Table structure for table `merk_motor`
--

CREATE TABLE `merk_motor` (
  `id_merk` int(11) NOT NULL,
  `nama_merk` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `merk_motor`
--

INSERT INTO `merk_motor` (`id_merk`, `nama_merk`) VALUES
(1, 'Honda'),
(2, 'Yamaha'),
(3, 'Suzuki'),
(4, 'Kawasaki'),
(5, 'Vespa'),
(6, 'TVS'),
(7, 'Benelli');

-- --------------------------------------------------------

--
-- Table structure for table `model_motor`
--

CREATE TABLE `model_motor` (
  `id_model` int(11) NOT NULL,
  `id_merk` int(11) NOT NULL,
  `id_kategori` int(11) NOT NULL,
  `nama_model` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `model_motor`
--

INSERT INTO `model_motor` (`id_model`, `id_merk`, `id_kategori`, `nama_model`) VALUES
(1, 1, 1, 'Beat FI'),
(2, 1, 1, 'Beat Street'),
(3, 1, 1, 'Genio'),
(4, 1, 1, 'Scoopy'),
(5, 1, 1, 'Vario 125'),
(6, 1, 1, 'Vario 150'),
(7, 1, 1, 'Vario 160'),
(8, 1, 1, 'PCX 150'),
(9, 1, 1, 'PCX 160'),
(10, 1, 1, 'ADV 150'),
(11, 1, 1, 'ADV 160'),
(12, 1, 1, 'Stylo 160'),
(13, 1, 1, 'Lainnya'),
(14, 1, 2, 'Revo Fit'),
(15, 1, 2, 'Revo X'),
(16, 1, 2, 'Supra X 125'),
(17, 1, 2, 'Supra GTR 150'),
(18, 1, 2, 'Blade 125'),
(19, 1, 2, 'Lainnya'),
(20, 1, 3, 'CB150 Verza'),
(21, 1, 3, 'CB150R Streetfire'),
(22, 1, 3, 'Sonic 150R'),
(23, 1, 3, 'CBR150R'),
(24, 1, 3, 'CBR250R'),
(25, 1, 3, 'CBR250RR'),
(26, 1, 3, 'Lainnya'),
(27, 1, 4, 'CRF150L'),
(28, 1, 4, 'CRF250L'),
(29, 1, 4, 'CRF250 Rally'),
(30, 1, 4, 'Lainnya'),
(31, 1, 5, 'CB150X'),
(32, 1, 5, 'CRF250 Rally'),
(33, 1, 5, 'Lainnya'),
(34, 2, 1, 'Mio Sporty'),
(35, 2, 1, 'Mio J'),
(36, 2, 1, 'Mio M3'),
(37, 2, 1, 'Mio Z'),
(38, 2, 1, 'Mio S'),
(39, 2, 1, 'Fino'),
(40, 2, 1, 'Gear 125'),
(41, 2, 1, 'FreeGo'),
(42, 2, 1, 'X-Ride'),
(43, 2, 1, 'Lexi'),
(44, 2, 1, 'Fazzio'),
(45, 2, 1, 'Grand Filano'),
(46, 2, 1, 'Aerox 155'),
(47, 2, 1, 'NMAX 155'),
(48, 2, 1, 'XMAX 250'),
(49, 2, 1, 'Lainnya'),
(50, 2, 2, 'Vega Force'),
(51, 2, 2, 'Jupiter Z1'),
(52, 2, 2, 'MX King 150'),
(53, 2, 2, 'Lainnya'),
(54, 2, 3, 'Vixion'),
(55, 2, 3, 'Vixion R'),
(56, 2, 3, 'R15'),
(57, 2, 3, 'MT-15'),
(58, 2, 3, 'XSR155'),
(59, 2, 3, 'R25'),
(60, 2, 3, 'MT-25'),
(61, 2, 3, 'Lainnya'),
(62, 2, 4, 'WR155R'),
(63, 2, 4, 'Lainnya'),
(64, 2, 5, 'XSR155'),
(65, 2, 5, 'WR155R'),
(66, 2, 5, 'Lainnya'),
(67, 3, 1, 'Nex II'),
(68, 3, 1, 'Address FI'),
(69, 3, 1, 'Avenis 125'),
(70, 3, 1, 'Burgman Street 125 EX'),
(71, 3, 1, 'Lainnya'),
(72, 3, 2, 'Smash FI'),
(73, 3, 2, 'Shooter'),
(74, 3, 2, 'Satria F150'),
(75, 3, 2, 'Lainnya'),
(76, 3, 3, 'GSX-R150'),
(77, 3, 3, 'GSX-S150'),
(78, 3, 3, 'Gixxer SF 250'),
(79, 3, 3, 'Lainnya'),
(80, 3, 5, 'V-Strom 250SX'),
(81, 3, 5, 'Lainnya'),
(82, 4, 3, 'Ninja 150 R'),
(83, 4, 3, 'Ninja 150 RR'),
(84, 4, 3, 'Ninja RR Mono'),
(85, 4, 3, 'Ninja 250 FI'),
(86, 4, 3, 'Z125 Pro'),
(87, 4, 3, 'Z250'),
(88, 4, 3, 'Lainnya'),
(89, 4, 4, 'KLX 150'),
(90, 4, 4, 'KLX 230'),
(91, 4, 4, 'KLX 250'),
(92, 4, 4, 'D-Tracker 150'),
(93, 4, 4, 'D-Tracker X'),
(94, 4, 4, 'Lainnya'),
(95, 4, 5, 'Versys X250'),
(96, 4, 5, 'Lainnya'),
(97, 5, 1, 'LX 125'),
(98, 5, 1, 'S 125'),
(99, 5, 1, 'Primavera 150'),
(100, 5, 1, 'Sprint 150'),
(101, 5, 1, 'GTS 150'),
(102, 5, 1, 'Lainnya'),
(103, 5, 5, 'GTS Touring 150'),
(104, 5, 5, 'Lainnya'),
(105, 6, 1, 'Dazz'),
(106, 6, 1, 'Ntorq 125'),
(107, 6, 1, 'Callisto 110'),
(108, 6, 1, 'Callisto 125'),
(109, 6, 1, 'Lainnya'),
(110, 6, 2, 'Neo XR'),
(111, 6, 2, 'Rockz'),
(112, 6, 2, 'Lainnya'),
(113, 6, 3, 'Apache RTR 160'),
(114, 6, 3, 'Apache RTR 200'),
(115, 6, 3, 'Lainnya'),
(116, 7, 1, 'Panarea 125'),
(117, 7, 1, 'Lainnya'),
(118, 7, 3, 'TNT 135'),
(119, 7, 3, 'TNT 25'),
(120, 7, 3, 'Leoncino 250'),
(121, 7, 3, 'Lainnya'),
(122, 7, 4, 'TRK 251'),
(123, 7, 4, 'Lainnya');

-- --------------------------------------------------------

--
-- Table structure for table `notifikasi_wa`
--

CREATE TABLE `notifikasi_wa` (
  `id` int(11) NOT NULL,
  `reservasi_id` int(11) NOT NULL,
  `no_tujuan` varchar(20) NOT NULL,
  `pesan` text NOT NULL,
  `jenis` enum('Konfirmasi Reservasi','Pengingat Jadwal','Antrian Dipanggil','Callback Pending','Perubahan Status','Servis Selesai','Notifikasi Admin') NOT NULL,
  `status` enum('Terkirim','Gagal') DEFAULT 'Terkirim',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifikasi_wa`
--

INSERT INTO `notifikasi_wa` (`id`, `reservasi_id`, `no_tujuan`, `pesan`, `jenis`, `status`, `sent_at`) VALUES
(1, 14, '6285732781320', '*RESERVASI BARU MASUK*\r\n\r\nHalo Admin,\r\nAda reservasi baru yang menunggu konfirmasi:\r\n\r\n*Nama:* Yonatan Vertiko Febriansa\r\n*Kendaraan:* Beat FI (AG 5909 RAG)\r\n*Layanan:* Paket Servis Berat\r\n*Tanggal Servis:* 08-07-2026\r\n\r\nSilakan cek dan konfirmasi melalui link berikut:\r\nhttps://akasia-motor.my.id/admin/index.php?page=status_servis&stage=menunggu_konfirmasi&id=14', 'Notifikasi Admin', 'Terkirim', '2026-07-08 09:12:54'),
(2, 14, '6285608002134', 'Halo Bapak/Ibu Yonatan Vertiko Febriansa,\n\nMohon maaf, reservasi servis kendaraan dengan nomor antrean A001 tidak dapat diproses dan telah dibatalkan.\nAlasan: test\n\nSilakan melakukan reservasi ulang atau menghubungi pihak bengkel untuk informasi lebih lanjut.\n\nTerima kasih.', 'Perubahan Status', 'Terkirim', '2026-07-08 09:14:22'),
(3, 15, '6285732781320', '*RESERVASI BARU MASUK*\r\n\r\nHalo Admin,\r\nAda reservasi baru yang menunggu konfirmasi:\r\n\r\n*Nama:* Yonatan Vertiko Febriansa\r\n*Kendaraan:* Beat FI (AG 5909 RAG)\r\n*Layanan:* Paket Servis Berat\r\n*Tanggal Servis:* 08-07-2026\r\n\r\nSilakan cek dan konfirmasi melalui link berikut:\r\nhttps://akasia-motor.my.id/admin/index.php?page=status_servis&stage=menunggu_konfirmasi&id=15', 'Notifikasi Admin', 'Terkirim', '2026-07-08 09:14:44'),
(4, 15, '6285608002134', 'Halo Bapak/Ibu Yonatan Vertiko Febriansa,\n\nMohon maaf, reservasi servis kendaraan dengan nomor antrean A001 tidak dapat diproses dan telah dibatalkan.\nAlasan: test\n\nSilakan melakukan reservasi ulang atau menghubungi pihak bengkel untuk informasi lebih lanjut.\n\nTerima kasih.', 'Perubahan Status', 'Terkirim', '2026-07-08 09:15:08');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_bengkel` varchar(100) NOT NULL,
  `alamat` text DEFAULT NULL,
  `no_whatsapp` varchar(20) DEFAULT NULL,
  `jam_buka` time DEFAULT NULL,
  `jam_tutup` time DEFAULT NULL,
  `jam_istirahat_mulai` time DEFAULT NULL,
  `jam_istirahat_selesai` time DEFAULT NULL,
  `hari_operasional` varchar(100) DEFAULT NULL,
  `token_fonnte` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_bengkel`, `alamat`, `no_whatsapp`, `jam_buka`, `jam_tutup`, `jam_istirahat_mulai`, `jam_istirahat_selesai`, `hari_operasional`, `token_fonnte`, `updated_at`) VALUES
(1, 'Akasia Motor', 'Jl. Raya Kemantren No.22, Putuk Rejo, Kemantren, Kec. Jabung, Kabupaten Malang, Jawa Timur 65154', '6285732781320', '08:00:00', '17:00:00', '12:00:00', '13:00:00', 'Senin - Sabtu', 'rUzCbTZvt2VcAgpyCJ7d', '2026-07-06 06:39:26');

-- --------------------------------------------------------

--
-- Table structure for table `reservasi`
--

CREATE TABLE `reservasi` (
  `id` int(11) NOT NULL,
  `no_antrian` varchar(10) NOT NULL,
  `user_id` int(11) NOT NULL,
  `mekanik_id` int(11) DEFAULT NULL,
  `jenis_layanan_id` int(11) NOT NULL,
  `jenis_reservasi` enum('Online','Walk-in') DEFAULT 'Online',
  `no_plat` varchar(20) NOT NULL,
  `jenis_kendaraan` varchar(50) DEFAULT NULL,
  `tipe_model` varchar(50) DEFAULT NULL,
  `tahun` year(4) DEFAULT NULL,
  `warna` varchar(30) DEFAULT NULL,
  `keluhan` text DEFAULT NULL,
  `kehadiran` enum('Belum Hadir','Hadir') DEFAULT 'Belum Hadir',
  `status` enum('menunggu_konfirmasi','dikonfirmasi','menunggu_antrean','diproses','pending','selesai','dibatalkan') NOT NULL DEFAULT 'menunggu_konfirmasi',
  `alasan_status` text DEFAULT NULL,
  `catatan_mekanik` text DEFAULT NULL,
  `hasil_servis` text DEFAULT NULL,
  `biaya_jasa` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_biaya` decimal(12,2) NOT NULL DEFAULT 0.00,
  `catatan_tambahan` text DEFAULT NULL,
  `tanggal_servis` date DEFAULT NULL,
  `waktu_konfirmasi` datetime DEFAULT NULL,
  `waktu_hadir` datetime DEFAULT NULL,
  `waktu_pending` datetime DEFAULT NULL,
  `waktu_mulai_servis` datetime DEFAULT NULL,
  `estimasi_durasi` int(11) DEFAULT 0,
  `waktu_selesai` datetime DEFAULT NULL,
  `reminder_status` enum('belum_dikirim','sudah_dikirim') NOT NULL DEFAULT 'belum_dikirim',
  `reminder_sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservasi`
--

INSERT INTO `reservasi` (`id`, `no_antrian`, `user_id`, `mekanik_id`, `jenis_layanan_id`, `jenis_reservasi`, `no_plat`, `jenis_kendaraan`, `tipe_model`, `tahun`, `warna`, `keluhan`, `kehadiran`, `status`, `alasan_status`, `catatan_mekanik`, `hasil_servis`, `biaya_jasa`, `total_biaya`, `catatan_tambahan`, `tanggal_servis`, `waktu_konfirmasi`, `waktu_hadir`, `waktu_pending`, `waktu_mulai_servis`, `estimasi_durasi`, `waktu_selesai`, `reminder_status`, `reminder_sent_at`, `created_at`, `updated_at`) VALUES
(14, 'X-14', 14, NULL, 8, 'Online', 'AG 5909 RAG', 'Honda - Matic', 'Beat FI', '2023', NULL, '', 'Belum Hadir', 'dibatalkan', 'test', NULL, NULL, 200000.00, 200000.00, '', '2026-07-08', NULL, NULL, NULL, NULL, 150, NULL, 'belum_dikirim', NULL, '2026-07-08 09:12:53', '2026-07-08 09:14:21'),
(15, 'X-15', 14, NULL, 8, 'Online', 'AG 5909 RAG', 'Honda - Matic', 'Beat FI', '2023', NULL, '', 'Belum Hadir', 'dibatalkan', 'test', NULL, NULL, 200000.00, 200000.00, '', '2026-07-08', NULL, NULL, NULL, NULL, 150, NULL, 'belum_dikirim', NULL, '2026-07-08 09:14:43', '2026-07-08 09:15:05');

-- --------------------------------------------------------

--
-- Table structure for table `reservasi_kegiatan`
--

CREATE TABLE `reservasi_kegiatan` (
  `id` int(11) NOT NULL,
  `reservasi_id` int(11) NOT NULL,
  `kegiatan_servis_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservasi_kegiatan`
--

INSERT INTO `reservasi_kegiatan` (`id`, `reservasi_id`, `kegiatan_servis_id`) VALUES
(1, 14, 78),
(2, 14, 79),
(3, 14, 80),
(4, 14, 81),
(5, 14, 82),
(6, 14, 83),
(7, 14, 84),
(8, 14, 85),
(9, 14, 86),
(10, 14, 87),
(11, 14, 88),
(12, 14, 89),
(13, 14, 90),
(14, 14, 91),
(15, 14, 92),
(16, 14, 93),
(17, 14, 94),
(18, 14, 95),
(19, 14, 96),
(20, 14, 97),
(21, 14, 98),
(22, 14, 99),
(23, 14, 100),
(24, 14, 101),
(25, 14, 102),
(26, 14, 103),
(27, 14, 104),
(28, 14, 105),
(29, 14, 106),
(30, 14, 107),
(31, 14, 108),
(32, 14, 109),
(33, 14, 110),
(34, 14, 111),
(35, 14, 112),
(36, 14, 113),
(37, 14, 114),
(38, 14, 115),
(39, 14, 116),
(40, 15, 78),
(41, 15, 79),
(42, 15, 80),
(43, 15, 81),
(44, 15, 82),
(45, 15, 83),
(46, 15, 84),
(47, 15, 85),
(48, 15, 86),
(49, 15, 87),
(50, 15, 88),
(51, 15, 89),
(52, 15, 90),
(53, 15, 91),
(54, 15, 92),
(55, 15, 93),
(56, 15, 94),
(57, 15, 95),
(58, 15, 96),
(59, 15, 97),
(60, 15, 98),
(61, 15, 99),
(62, 15, 100),
(63, 15, 101),
(64, 15, 102),
(65, 15, 103),
(66, 15, 104),
(67, 15, 105),
(68, 15, 106),
(69, 15, 107),
(70, 15, 108),
(71, 15, 109),
(72, 15, 110),
(73, 15, 111),
(74, 15, 112),
(75, 15, 113),
(76, 15, 114),
(77, 15, 115),
(78, 15, 116);

-- --------------------------------------------------------

--
-- Table structure for table `reservasi_sparepart`
--

CREATE TABLE `reservasi_sparepart` (
  `id` int(11) NOT NULL,
  `reservasi_id` int(11) NOT NULL,
  `nama_item` varchar(150) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `harga` decimal(12,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `template_whatsapp`
--

CREATE TABLE `template_whatsapp` (
  `id` int(11) NOT NULL,
  `kode_template` varchar(50) NOT NULL,
  `nama_template` varchar(100) NOT NULL,
  `isi_pesan` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `template_whatsapp`
--

INSERT INTO `template_whatsapp` (`id`, `kode_template`, `nama_template`, `isi_pesan`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'reservasi_dikonfirmasi', 'Reservasi Dikonfirmasi', 'Halo Bapak/Ibu {nama},\r\n\r\nReservasi servis kendaraan Anda dengan nomor antrean {antrean}\r\nuntuk tanggal {tanggal}\r\ntelah berhasil dikonfirmasi oleh Bengkel Akasia Motor.\r\n\r\nSilakan datang ke bengkel sesuai tanggal reservasi dan lakukan konfirmasi kehadiran kepada admin bengkel.\r\n\r\nTerima kasih telah menggunakan layanan Bengkel Akasia Motor.', 1, '2026-05-26 07:54:59', '2026-05-27 10:33:17'),
(2, 'reservasi_dibatalkan', 'Reservasi Dibatalkan', 'Halo Bapak/Ibu {nama},\n\nMohon maaf, reservasi servis kendaraan dengan nomor antrean {antrean} tidak dapat diproses dan telah dibatalkan.\nAlasan: {alasan}\n\nSilakan melakukan reservasi ulang atau menghubungi pihak bengkel untuk informasi lebih lanjut.\n\nTerima kasih.', 1, '2026-05-26 07:54:59', '2026-06-09 09:48:00'),
(3, 'kehadiran_dikonfirmasi', 'Kehadiran Dikonfirmasi', 'Halo Bapak/Ibu {nama},\r\n\r\nKehadiran Anda di Bengkel Akasia Motor telah berhasil dikonfirmasi.\r\nSaat ini kendaraan Anda sedang masuk dalam daftar antrean servis.\r\n\r\nSilakan menunggu hingga proses servis dimulai.\r\n\r\nTerima kasih.', 1, '2026-05-26 07:54:59', '2026-05-27 10:33:26'),
(4, 'pending_kehadiran', 'Pending Kehadiran', 'Halo Bapak/Ibu {nama},\r\n\r\nReservasi servis kendaraan dengan nomor antrean {antrean}\r\nsementara dipindahkan ke status pending karena pelanggan belum hadir di bengkel.\r\n\r\nSilakan datang ke Bengkel Akasia Motor untuk melanjutkan antrean servis.\r\n\r\nTerima kasih.', 1, '2026-05-26 07:54:59', '2026-05-27 10:33:31'),
(5, 'proses_servis_dimulai', 'Proses Servis Dimulai', 'Halo Bapak/Ibu {nama},\r\n\r\nKendaraan Anda dengan nomor antrean {antrean}\r\nsaat ini sedang dalam proses servis di Bengkel Akasia Motor.\r\n\r\nMekanik yang menangani:\r\n{mekanik}\r\n\r\nTerima kasih.', 1, '2026-05-26 07:54:59', '2026-05-27 10:33:37'),
(6, 'callback_antrean', 'Callback Antrean', 'Halo Bapak/Ibu {nama},\r\n\r\nAntrean servis kendaraan Anda telah dipanggil kembali oleh Bengkel Akasia Motor.\r\n\r\nSilakan menuju area pelayanan servis untuk melanjutkan proses servis kendaraan Anda.\r\n\r\nTerima kasih.', 1, '2026-05-26 07:54:59', '2026-05-27 10:33:41'),
(7, 'servis_selesai', 'Servis Selesai', 'Halo Bapak/Ibu {nama},\r\n\r\nServis kendaraan Anda di Bengkel Akasia Motor telah selesai dikerjakan.\r\n\r\nNomor antrean:\r\n{antrean}\r\nTotal biaya servis:\r\nRp {total}\r\n\r\nSilakan datang ke bengkel untuk pengambilan kendaraan.\r\n\r\nTerima kasih telah menggunakan layanan Bengkel Akasia Motor.', 1, '2026-05-26 07:54:59', '2026-05-27 10:33:47'),
(8, 'reservasi_dialihkan', 'Reservasi Dialihkan', 'Halo Bapak/Ibu {nama},\n\nMohon maaf, reservasi servis kendaraan Anda terpaksa kami alihkan dengan alasan: {alasan}\n\nBerikut detail antrean baru Anda:\nTanggal: {tanggal_baru}\nNomor Antrean: {antrean_baru}\n\nSilakan datang ke bengkel sesuai tanggal baru dan lakukan konfirmasi kehadiran.\n\nTerima kasih telah menggunakan layanan Bengkel Akasia Motor.', 1, '2026-06-09 09:48:00', '2026-06-09 09:48:00'),
(9, 'notif_admin_reservasi_baru', 'Notifikasi Admin (Reservasi Baru)', '*RESERVASI BARU MASUK*\r\n\r\nHalo Admin,\r\nAda reservasi baru yang menunggu konfirmasi:\r\n\r\n*Nama:* {nama}\r\n*Kendaraan:* {kendaraan} ({no_plat})\r\n*Layanan:* {layanan}\r\n*Tanggal Servis:* {tanggal}\r\n\r\nSilakan cek dan konfirmasi melalui link berikut:\r\n{link}', 1, '2026-06-09 09:53:36', '2026-06-18 00:05:48'),
(10, 'reminder_jadwal_servis', 'Reminder Jadwal Servis', 'Halo Bapak/Ibu {nama},\n\nIni pengingat jadwal servis kendaraan Anda di Bengkel Akasia Motor.\n\nNomor antrean: {antrean}\nTanggal servis: {tanggal}\nLayanan: {layanan}\n\nSilakan datang sesuai jadwal agar proses servis dapat berjalan lancar.\n\nTerima kasih.', 1, '2026-06-18 00:58:12', '2026-06-18 00:58:12'),
(11, 'perubahan_nomor_antrean', 'Perubahan Nomor Antrean', 'Kabar Baik Pelanggan {nama}!\n\nNomor antrean Anda pada {tanggal} maju dari {antrean_lama} menjadi {antrean_baru}.\n\nSilakan cek antrean terbaru Anda di bengkel.\n\nTerima kasih.', 1, '2026-07-07 15:39:01', '2026-07-07 15:39:01');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `no_whatsapp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `role` enum('owner','admin','pelanggan') DEFAULT 'pelanggan',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `no_whatsapp`, `alamat`, `role`, `created_at`) VALUES
(1, 'Administrator', 'admin@akasiamotor.com', '$2a$12$oZAcRGy8sZFFnktJU6KvgO/4TzA75HhJbwYHDcrASaaYjLHs4lIyO', '6285732781320', 'Malang', 'admin', '2026-05-27 19:56:33'),
(2, 'Owner Akasia', 'owner@akasiamotor.com', '$2a$12$oZAcRGy8sZFFnktJU6KvgO/4TzA75HhJbwYHDcrASaaYjLHs4lIyO', '6285732781320', 'Malang', 'owner', '2026-05-27 19:56:33'),
(14, 'Yonatan Vertiko Febriansa', 'yonatanbrian6@gmail.com', '$2y$10$CjxV.zVIa8wRXFipEq1pXuR4DEpwBYqsWyALWoe3UVoIlbzkpihuq', '085608002134', 'Jl S.Supriadi Sukun Malang', 'pelanggan', '2026-07-06 06:36:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `antrean_harian`
--
ALTER TABLE `antrean_harian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_antrean_harian_tanggal` (`tanggal_servis`);

--
-- Indexes for table `jenis_layanan`
--
ALTER TABLE `jenis_layanan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kategori_motor`
--
ALTER TABLE `kategori_motor`
  ADD PRIMARY KEY (`id_kategori`);

--
-- Indexes for table `kegiatan_servis`
--
ALTER TABLE `kegiatan_servis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jenis_layanan_id` (`jenis_layanan_id`);

--
-- Indexes for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_kendaraan_user_id` (`user_id`);

--
-- Indexes for table `mekanik`
--
ALTER TABLE `mekanik`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `merk_motor`
--
ALTER TABLE `merk_motor`
  ADD PRIMARY KEY (`id_merk`);

--
-- Indexes for table `model_motor`
--
ALTER TABLE `model_motor`
  ADD PRIMARY KEY (`id_model`),
  ADD KEY `id_merk` (`id_merk`),
  ADD KEY `id_kategori` (`id_kategori`);

--
-- Indexes for table `notifikasi_wa`
--
ALTER TABLE `notifikasi_wa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservasi_id` (`reservasi_id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reservasi`
--
ALTER TABLE `reservasi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_reservasi_tanggal_no_antrian` (`tanggal_servis`,`no_antrian`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `mekanik_id` (`mekanik_id`),
  ADD KEY `jenis_layanan_id` (`jenis_layanan_id`),
  ADD KEY `idx_reservasi_tanggal_servis_no_antrian` (`tanggal_servis`,`no_antrian`);

--
-- Indexes for table `reservasi_kegiatan`
--
ALTER TABLE `reservasi_kegiatan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservasi_id` (`reservasi_id`),
  ADD KEY `kegiatan_servis_id` (`kegiatan_servis_id`);

--
-- Indexes for table `reservasi_sparepart`
--
ALTER TABLE `reservasi_sparepart`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reservasi_sparepart_reservasi_id` (`reservasi_id`);

--
-- Indexes for table `template_whatsapp`
--
ALTER TABLE `template_whatsapp`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_template_whatsapp_kode` (`kode_template`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `antrean_harian`
--
ALTER TABLE `antrean_harian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `jenis_layanan`
--
ALTER TABLE `jenis_layanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `kategori_motor`
--
ALTER TABLE `kategori_motor`
  MODIFY `id_kategori` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `kegiatan_servis`
--
ALTER TABLE `kegiatan_servis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `kendaraan`
--
ALTER TABLE `kendaraan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `mekanik`
--
ALTER TABLE `mekanik`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `merk_motor`
--
ALTER TABLE `merk_motor`
  MODIFY `id_merk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `model_motor`
--
ALTER TABLE `model_motor`
  MODIFY `id_model` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `notifikasi_wa`
--
ALTER TABLE `notifikasi_wa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `reservasi`
--
ALTER TABLE `reservasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `reservasi_kegiatan`
--
ALTER TABLE `reservasi_kegiatan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `reservasi_sparepart`
--
ALTER TABLE `reservasi_sparepart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `template_whatsapp`
--
ALTER TABLE `template_whatsapp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `kegiatan_servis`
--
ALTER TABLE `kegiatan_servis`
  ADD CONSTRAINT `kegiatan_servis_ibfk_1` FOREIGN KEY (`jenis_layanan_id`) REFERENCES `jenis_layanan` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kendaraan`
--
ALTER TABLE `kendaraan`
  ADD CONSTRAINT `kendaraan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_motor`
--
ALTER TABLE `model_motor`
  ADD CONSTRAINT `model_motor_ibfk_1` FOREIGN KEY (`id_merk`) REFERENCES `merk_motor` (`id_merk`),
  ADD CONSTRAINT `model_motor_ibfk_2` FOREIGN KEY (`id_kategori`) REFERENCES `kategori_motor` (`id_kategori`);

--
-- Constraints for table `notifikasi_wa`
--
ALTER TABLE `notifikasi_wa`
  ADD CONSTRAINT `notifikasi_wa_ibfk_1` FOREIGN KEY (`reservasi_id`) REFERENCES `reservasi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservasi`
--
ALTER TABLE `reservasi`
  ADD CONSTRAINT `reservasi_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservasi_ibfk_2` FOREIGN KEY (`mekanik_id`) REFERENCES `mekanik` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `reservasi_ibfk_3` FOREIGN KEY (`jenis_layanan_id`) REFERENCES `jenis_layanan` (`id`);

--
-- Constraints for table `reservasi_kegiatan`
--
ALTER TABLE `reservasi_kegiatan`
  ADD CONSTRAINT `reservasi_kegiatan_ibfk_1` FOREIGN KEY (`reservasi_id`) REFERENCES `reservasi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservasi_kegiatan_ibfk_2` FOREIGN KEY (`kegiatan_servis_id`) REFERENCES `kegiatan_servis` (`id`);

--
-- Constraints for table `reservasi_sparepart`
--
ALTER TABLE `reservasi_sparepart`
  ADD CONSTRAINT `fk_reservasi_sparepart_reservasi` FOREIGN KEY (`reservasi_id`) REFERENCES `reservasi` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
