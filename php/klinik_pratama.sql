-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 02 Jul 2026 pada 21.37
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `klinik_pratama`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `adminusers`
--

CREATE TABLE `adminusers` (
  `id` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `username` varchar(60) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(30) NOT NULL DEFAULT 'Administrasi',
  `hak_akses` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`hak_akses`)),
  `status` varchar(20) NOT NULL DEFAULT 'Aktif',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `adminusers`
--

INSERT INTO `adminusers` (`id`, `nama`, `username`, `password`, `role`, `hak_akses`, `status`, `created_at`) VALUES
('u1', 'Super Administrator', 'admin', 'admin123', 'Superadmin', '[\"dashboard\",\"pasien\",\"dokter\",\"layanan\",\"pendaftaran\",\"jadwal\",\"rekammedis\",\"obat\",\"pesan\",\"billing\",\"users\"]', 'Aktif', '2026-06-28 13:32:54'),
('u2', 'Staf Administrasi', 'staf', 'staf123', 'Administrasi', '[\"dashboard\",\"pasien\",\"pendaftaran\",\"billing\"]', 'Aktif', '2026-06-28 13:32:54'),
('u3', 'Perawat Jaga', 'perawat', 'perawat123', 'Perawat', '[\"dashboard\",\"pasien\",\"rekammedis\",\"jadwal\"]', 'Aktif', '2026-06-28 13:32:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `billing`
--

CREATE TABLE `billing` (
  `id` varchar(20) NOT NULL,
  `no_bill` varchar(30) NOT NULL,
  `pasien_id` varchar(20) DEFAULT NULL,
  `pasien_nama` varchar(150) DEFAULT NULL,
  `pendaftaran_id` varchar(20) DEFAULT NULL,
  `no_reg` varchar(30) DEFAULT NULL,
  `layanan_nama` varchar(100) DEFAULT NULL,
  `dokter_nama` varchar(150) DEFAULT NULL,
  `tanggal` varchar(20) NOT NULL,
  `item_list` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`item_list`)),
  `total_biaya` decimal(12,0) NOT NULL DEFAULT 0,
  `metode_pembayaran` varchar(50) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Belum Lunas',
  `tanggal_bayar` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `billing`
--

INSERT INTO `billing` (`id`, `no_bill`, `pasien_id`, `pasien_nama`, `pendaftaran_id`, `no_reg`, `layanan_nama`, `dokter_nama`, `tanggal`, `item_list`, `total_biaya`, `metode_pembayaran`, `status`, `tanggal_bayar`, `created_at`) VALUES
('b1', 'BILL-2025-001', 'p1', 'Budi Santoso', 'reg1', 'REG-2025-1001', 'Poli Umum', 'dr. Ahmad Santoso', '2026-06-28', '[{\"nama\":\"Biaya Konsultasi\",\"jumlah\":1,\"harga\":100000},{\"nama\":\"Paracetamol 500mg\",\"jumlah\":10,\"harga\":2000}]', 120000, 'Tunai', 'Lunas', NULL, '2026-06-28 13:32:54'),
('b2', 'BILL-2025-002', 'p2', 'Sri Wahyuni', 'reg2', 'REG-2025-1002', 'Poli Anak', 'dr. Siti Aminah, Sp.A', '2026-06-28', '[{\"nama\":\"Biaya Konsultasi Anak\",\"jumlah\":1,\"harga\":150000}]', 150000, NULL, 'Belum Lunas', NULL, '2026-06-28 13:32:54'),
('b3', 'BILL-2025-003', 'p3', 'Rizky Ramadhan', 'reg3', 'REG-2025-1003', 'Poli Gigi', 'drg. Budi Wijaya', '2026-06-28', '[{\"nama\":\"Biaya Konsultasi Gigi\",\"jumlah\":1,\"harga\":120000},{\"nama\":\"Tambal Gigi Komposit\",\"jumlah\":1,\"harga\":250000},{\"nama\":\"Amoxicillin 500mg\",\"jumlah\":15,\"harga\":5000}]', 445000, 'Transfer Bank', 'Lunas', NULL, '2026-06-28 13:32:54'),
('b4', 'BILL-2025-004', 'p4', 'Dewi Kartika', 'reg4', 'REG-2025-1004', 'Poli Umum', 'dr. Heru Prasetyo', '2026-06-28', '[{\"nama\":\"Biaya Konsultasi\",\"jumlah\":1,\"harga\":100000}]', 100000, NULL, 'Belum Lunas', NULL, '2026-06-28 13:32:54'),
('b5', 'BILL-2025-005', 'p5', 'Agus Purnomo', 'reg5', 'REG-2025-0997', 'Laboratorium', 'dr. Ahmad Santoso', '2026-06-27', '[{\"nama\":\"Darah Lengkap\",\"jumlah\":1,\"harga\":85000},{\"nama\":\"Gula Darah Puasa\",\"jumlah\":1,\"harga\":35000}]', 120000, 'BPJS', 'Lunas', NULL, '2026-06-28 13:32:54'),
('b6', 'BILL-2025-006', 'p6', 'Nurul Hidayah', 'reg6', 'REG-2025-0998', 'Poli Gigi', 'drg. Budi Wijaya', '2026-06-27', '[{\"nama\":\"Biaya Konsultasi Gigi\",\"jumlah\":1,\"harga\":120000},{\"nama\":\"Scaling Gigi\",\"jumlah\":1,\"harga\":200000}]', 320000, 'Debit', 'Lunas', NULL, '2026-06-28 13:32:54'),
('b7', 'BILL-2025-007', 'p7', 'Eko Prasetyo', 'reg7', 'REG-2025-0999', 'Poli Umum', 'dr. Heru Prasetyo', '2026-06-27', '[{\"nama\":\"Biaya Konsultasi\",\"jumlah\":1,\"harga\":100000},{\"nama\":\"Asam Mefenamat 500mg\",\"jumlah\":10,\"harga\":3500}]', 135000, 'Tunai', 'Lunas', NULL, '2026-06-28 13:32:54'),
('b8', 'BILL-2025-008', 'p8', 'Linda Susanti', 'reg8', 'REG-2025-0995', 'Poli Anak', 'dr. Siti Aminah, Sp.A', '2026-06-26', '[{\"nama\":\"Biaya Konsultasi Anak\",\"jumlah\":1,\"harga\":150000},{\"nama\":\"Imunisasi DPT\",\"jumlah\":1,\"harga\":100000}]', 250000, 'Tunai', 'Lunas', NULL, '2026-06-28 13:32:54'),
('mqxeys06ih1n', 'BILL-2026-1878', 'mqxeyryq38dd', 'nasichatun', 'mqxeyrzqkl25', 'REG-2026-9310', 'Poli Gigi', 'drg. Budi Wijaya', '2026-06-30', '[{\"nama\":\"Biaya Konsultasi Poli Gigi\",\"jumlah\":1,\"harga\":100000}]', 100000, 'Tunai', 'Lunas', '2026-06-28 06:35:15', '2026-06-28 06:34:31');

-- --------------------------------------------------------

--
-- Struktur dari tabel `dokter`
--

CREATE TABLE `dokter` (
  `id` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `spesialisasi` varchar(100) NOT NULL,
  `layanan_id` varchar(20) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `hari` varchar(100) DEFAULT NULL,
  `jam` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Aktif',
  `bio` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `dokter`
--

INSERT INTO `dokter` (`id`, `nama`, `spesialisasi`, `layanan_id`, `telepon`, `email`, `hari`, `jam`, `status`, `bio`, `created_at`) VALUES
('d1', 'dr. Ahmad Santoso', 'Dokter Umum', 'l1', '0812-1111-2222', 'ahmad@klinik.id', 'Senin, Rabu, Jumat', '08:00-14:00', 'Aktif', 'Dokter umum berpengalaman 10 tahun. Lulus dari Universitas Indonesia dan telah menangani ribuan pasien dengan penuh dedikasi.', '2026-06-28 13:32:54'),
('d2', 'dr. Siti Aminah, Sp.A', 'Spesialis Anak', 'l3', '0812-3333-4444', 'siti@klinik.id', 'Selasa, Kamis, Sabtu', '09:00-15:00', 'Aktif', 'Spesialis anak dengan pengalaman lebih dari 8 tahun. Ahli dalam tumbuh kembang anak dan imunisasi.', '2026-06-28 13:32:54'),
('d3', 'drg. Budi Wijaya', 'Dokter Gigi', 'l2', '0812-5555-6666', 'budi@klinik.id', 'Senin, Rabu, Sabtu', '10:00-16:00', 'Aktif', 'Dokter gigi spesialis restoratif dan estetik dengan keahlian dalam pemasangan implan dan mahkota gigi.', '2026-06-28 13:32:54'),
('d4', 'dr. Heru Prasetyo', 'Dokter Umum Senior', 'l1', '0812-7777-8888', 'heru@klinik.id', 'Selasa, Jumat', '07:00-13:00', 'Aktif', 'Dokter umum senior dengan 15 tahun pengalaman. Spesialis dalam penanganan penyakit dalam dan manajemen penyakit kronis.', '2026-06-28 13:32:54'),
('d5', 'dr. Rina Kusuma', 'Radiologi', 'l5', '0812-9999-0000', 'rina@klinik.id', 'Senin s.d. Jumat', '08:00-17:00', 'Aktif', 'Ahli radiologi terpercaya dengan keahlian dalam interpretasi USG, rontgen, dan CT-Scan.', '2026-06-28 13:32:54'),
('mqxf27expu9a', 'dr. Reza', 'Sp.A', 'l6', '086372581691', 'reza@gmail.com', 'Senin, Rabu, Kamis', '08:00-11:00', 'Aktif', '', '2026-06-28 13:37:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
--

CREATE TABLE `jadwal` (
  `id` varchar(20) NOT NULL,
  `dokter_id` varchar(20) NOT NULL,
  `dokter_nama` varchar(150) DEFAULT NULL,
  `hari` varchar(20) NOT NULL,
  `jam_mulai` varchar(10) NOT NULL,
  `jam_selesai` varchar(10) NOT NULL,
  `kuota` int(11) NOT NULL DEFAULT 20,
  `status` varchar(20) NOT NULL DEFAULT 'Aktif',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `dokter_id`, `dokter_nama`, `hari`, `jam_mulai`, `jam_selesai`, `kuota`, `status`, `created_at`) VALUES
('j1', 'd1', 'dr. Ahmad Santoso', 'Senin', '08:00', '14:00', 20, 'Aktif', '2026-06-28 13:32:54'),
('j10', 'd4', 'dr. Heru Prasetyo', 'Selasa', '07:00', '13:00', 18, 'Aktif', '2026-06-28 13:32:54'),
('j11', 'd4', 'dr. Heru Prasetyo', 'Jumat', '07:00', '13:00', 18, 'Aktif', '2026-06-28 13:32:54'),
('j12', 'd5', 'dr. Rina Kusuma', 'Senin', '08:00', '17:00', 25, 'Aktif', '2026-06-28 13:32:54'),
('j13', 'd5', 'dr. Rina Kusuma', 'Selasa', '08:00', '17:00', 25, 'Aktif', '2026-06-28 13:32:54'),
('j14', 'd5', 'dr. Rina Kusuma', 'Rabu', '08:00', '17:00', 25, 'Aktif', '2026-06-28 13:32:54'),
('j15', 'd5', 'dr. Rina Kusuma', 'Kamis', '08:00', '17:00', 25, 'Aktif', '2026-06-28 13:32:54'),
('j16', 'd5', 'dr. Rina Kusuma', 'Jumat', '08:00', '17:00', 25, 'Aktif', '2026-06-28 13:32:54'),
('j2', 'd1', 'dr. Ahmad Santoso', 'Rabu', '08:00', '14:00', 20, 'Aktif', '2026-06-28 13:32:54'),
('j3', 'd1', 'dr. Ahmad Santoso', 'Jumat', '08:00', '13:00', 15, 'Aktif', '2026-06-28 13:32:54'),
('j4', 'd2', 'dr. Siti Aminah, Sp.A', 'Selasa', '09:00', '15:00', 15, 'Aktif', '2026-06-28 13:32:54'),
('j5', 'd2', 'dr. Siti Aminah, Sp.A', 'Kamis', '09:00', '15:00', 15, 'Aktif', '2026-06-28 13:32:54'),
('j6', 'd2', 'dr. Siti Aminah, Sp.A', 'Sabtu', '08:00', '12:00', 10, 'Aktif', '2026-06-28 13:32:54'),
('j7', 'd3', 'drg. Budi Wijaya', 'Senin', '10:00', '16:00', 12, 'Aktif', '2026-06-28 13:32:54'),
('j8', 'd3', 'drg. Budi Wijaya', 'Rabu', '10:00', '16:00', 12, 'Aktif', '2026-06-28 13:32:54'),
('j9', 'd3', 'drg. Budi Wijaya', 'Sabtu', '09:00', '13:00', 8, 'Aktif', '2026-06-28 13:32:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `layanan`
--

CREATE TABLE `layanan` (
  `id` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon` varchar(60) DEFAULT 'medical_services',
  `warna` varchar(20) DEFAULT 'blue',
  `status` varchar(20) NOT NULL DEFAULT 'Aktif',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `layanan`
--

INSERT INTO `layanan` (`id`, `nama`, `deskripsi`, `icon`, `warna`, `status`, `created_at`) VALUES
('l1', 'Poli Umum', 'Pelayanan kesehatan dasar, konsultasi keluhan umum, dan pemeriksaan fisik menyeluruh.', 'medical_services', 'blue', 'Aktif', '2026-06-28 13:32:54'),
('l2', 'Poli Gigi', 'Perawatan gigi dan mulut komprehensif, scaling, tambal gigi, dan cabut gigi.', 'dentistry', 'green', 'Aktif', '2026-06-28 13:32:54'),
('l3', 'Poli Anak', 'Layanan kesehatan spesialis untuk tumbuh kembang anak, imunisasi, dan penanganan penyakit.', 'child_care', 'purple', 'Aktif', '2026-06-28 13:32:54'),
('l4', 'Laboratorium', 'Pemeriksaan darah, urin, dan berbagai tes diagnostik medis dengan hasil akurat.', 'science', 'yellow', 'Aktif', '2026-06-28 13:32:54'),
('l5', 'Radiologi', 'Rontgen, USG, dan pemeriksaan radiologi modern dengan teknologi terkini.', 'radiology', 'red', 'Aktif', '2026-06-28 13:32:54'),
('l6', 'IGD 24 Jam', 'Instalasi Gawat Darurat siaga 24 jam untuk penanganan kasus darurat medis.', 'emergency', 'orange', 'Aktif', '2026-06-28 13:32:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `obat`
--

CREATE TABLE `obat` (
  `id` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `kategori` varchar(80) DEFAULT NULL,
  `satuan` varchar(30) DEFAULT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `harga` decimal(12,0) NOT NULL DEFAULT 0,
  `status` varchar(30) NOT NULL DEFAULT 'Tersedia',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `obat`
--

INSERT INTO `obat` (`id`, `nama`, `kategori`, `satuan`, `stok`, `harga`, `status`, `created_at`) VALUES
('o1', 'Paracetamol 500mg', 'Analgesik', 'Tablet', 500, 2000, 'Tersedia', '2026-06-28 13:32:54'),
('o10', 'Salbutamol 4mg', 'Bronkodilator', 'Tablet', 120, 3500, 'Tersedia', '2026-06-28 13:32:54'),
('o2', 'Amoxicillin 500mg', 'Antibiotik', 'Kapsul', 300, 5000, 'Tersedia', '2026-06-28 13:32:54'),
('o3', 'Antasida Doen', 'Antasida', 'Tablet', 200, 1500, 'Tersedia', '2026-06-28 13:32:54'),
('o4', 'Cetirizine 10mg', 'Antihistamin', 'Tablet', 150, 3000, 'Tersedia', '2026-06-28 13:32:54'),
('o5', 'Metformin 500mg', 'Antidiabetik', 'Tablet', 0, 4000, 'Habis', '2026-06-28 13:32:54'),
('o6', 'Asam Mefenamat 500mg', 'Analgesik', 'Tablet', 400, 3500, 'Tersedia', '2026-06-28 13:32:54'),
('o7', 'Amlodipine 5mg', 'Antihipertensi', 'Tablet', 280, 6000, 'Tersedia', '2026-06-28 13:32:54'),
('o8', 'Vitamin C 500mg', 'Vitamin', 'Tablet', 500, 2500, 'Tersedia', '2026-06-28 13:32:54'),
('o9', 'OBH Combi Sirup', 'Antitusif', 'Botol', 60, 28000, 'Tersedia', '2026-06-28 13:32:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pasien`
--

CREATE TABLE `pasien` (
  `id` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `nik` varchar(16) NOT NULL,
  `ttl` varchar(20) NOT NULL,
  `jk` varchar(20) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pasien`
--

INSERT INTO `pasien` (`id`, `nama`, `nik`, `ttl`, `jk`, `telepon`, `email`, `alamat`, `created_at`) VALUES
('mqxeyryq38dd', 'nasichatun', '1111111111111111', '2006-01-05', 'Perempuan', '085325457178', '', 'kalisapu, slawi', '2026-06-28 13:34:31'),
('p1', 'Budi Santoso', '3271011234567890', '1985-05-12', 'Laki-laki', '0812-0001-0001', 'budi.s@email.com', 'Jl. Melati No. 10, Jakarta Selatan', '2026-06-28 13:32:54'),
('p2', 'Sri Wahyuni', '3271025678901234', '1990-08-25', 'Perempuan', '0812-0002-0002', 'sri.w@email.com', 'Jl. Mawar No. 5, Depok', '2026-06-28 13:32:54'),
('p3', 'Rizky Ramadhan', '3271039012345678', '2000-01-15', 'Laki-laki', '0812-0003-0003', 'rizky.r@email.com', 'Jl. Anggrek No. 15, Bogor', '2026-06-28 13:32:54'),
('p4', 'Dewi Kartika', '3271044321098765', '1995-03-20', 'Perempuan', '0813-0004-0004', 'dewi.k@email.com', 'Jl. Dahlia No. 8, Bekasi', '2026-06-28 13:32:54'),
('p5', 'Agus Purnomo', '3271058765432109', '1978-11-07', 'Laki-laki', '0813-0005-0005', 'agus.p@email.com', 'Jl. Kenanga No. 22, Tangerang', '2026-06-28 13:32:54'),
('p6', 'Nurul Hidayah', '3271062109876543', '1992-07-14', 'Perempuan', '0814-0006-0006', 'nurul.h@email.com', 'Jl. Seruni No. 3, Jakarta Barat', '2026-06-28 13:32:54'),
('p7', 'Eko Prasetyo', '3271073456789012', '1988-02-28', 'Laki-laki', '0814-0007-0007', 'eko.p@email.com', 'Jl. Cempaka No. 17, Depok', '2026-06-28 13:32:54'),
('p8', 'Linda Susanti', '3271087890123456', '1997-09-03', 'Perempuan', '0815-0008-0008', 'linda.s@email.com', 'Jl. Flamboyan No. 11, Bogor', '2026-06-28 13:32:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pendaftaran`
--

CREATE TABLE `pendaftaran` (
  `id` varchar(20) NOT NULL,
  `no_reg` varchar(30) NOT NULL,
  `pasien_id` varchar(20) NOT NULL,
  `pasien_nama` varchar(150) DEFAULT NULL,
  `layanan_id` varchar(20) DEFAULT NULL,
  `layanan_nama` varchar(100) DEFAULT NULL,
  `dokter_id` varchar(20) DEFAULT NULL,
  `dokter_nama` varchar(150) DEFAULT NULL,
  `tanggal` varchar(20) NOT NULL,
  `keluhan` text DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Menunggu',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `pendaftaran`
--

INSERT INTO `pendaftaran` (`id`, `no_reg`, `pasien_id`, `pasien_nama`, `layanan_id`, `layanan_nama`, `dokter_id`, `dokter_nama`, `tanggal`, `keluhan`, `status`, `created_at`) VALUES
('mqxeyrzqkl25', 'REG-2026-9310', 'mqxeyryq38dd', 'nasichatun', 'l2', 'Poli Gigi', 'd3', 'drg. Budi Wijaya', '2026-06-30', 'sakit gigi', 'Menunggu', '2026-06-28 06:34:31'),
('reg1', 'REG-2025-1001', 'p1', 'Budi Santoso', 'l1', 'Poli Umum', 'd1', 'dr. Ahmad Santoso', '2026-06-28', 'Demam dan batuk sudah 3 hari, disertai pilek dan sakit tenggorokan.', 'Menunggu', '2026-06-28 13:32:54'),
('reg2', 'REG-2025-1002', 'p2', 'Sri Wahyuni', 'l3', 'Poli Anak', 'd2', 'dr. Siti Aminah, Sp.A', '2026-06-28', 'Anak demam tinggi 39 derajat, tidak mau makan, rewel sejak kemarin.', 'Diproses', '2026-06-28 13:32:54'),
('reg3', 'REG-2025-1003', 'p3', 'Rizky Ramadhan', 'l2', 'Poli Gigi', 'd3', 'drg. Budi Wijaya', '2026-06-28', 'Sakit gigi geraham kanan bawah, sudah 2 hari terasa berdenyut.', 'Selesai', '2026-06-28 13:32:54'),
('reg4', 'REG-2025-1004', 'p4', 'Dewi Kartika', 'l1', 'Poli Umum', 'd4', 'dr. Heru Prasetyo', '2026-06-28', 'Kontrol tekanan darah tinggi, minta resep obat rutin.', 'Menunggu', '2026-06-28 13:32:54'),
('reg5', 'REG-2025-0997', 'p5', 'Agus Purnomo', 'l4', 'Laboratorium', 'd1', 'dr. Ahmad Santoso', '2026-06-27', 'Cek darah lengkap dan gula darah puasa atas rujukan dokter.', 'Selesai', '2026-06-28 13:32:54'),
('reg6', 'REG-2025-0998', 'p6', 'Nurul Hidayah', 'l2', 'Poli Gigi', 'd3', 'drg. Budi Wijaya', '2026-06-27', 'Scaling dan pemeriksaan rutin gigi, karang gigi terasa menumpuk.', 'Selesai', '2026-06-28 13:32:54'),
('reg7', 'REG-2025-0999', 'p7', 'Eko Prasetyo', 'l1', 'Poli Umum', 'd4', 'dr. Heru Prasetyo', '2026-06-27', 'Nyeri sendi lutut kanan, susah berjalan dan bengkak sejak seminggu.', 'Selesai', '2026-06-28 13:32:54'),
('reg8', 'REG-2025-0995', 'p8', 'Linda Susanti', 'l3', 'Poli Anak', 'd2', 'dr. Siti Aminah, Sp.A', '2026-06-26', 'Imunisasi rutin DPT dan konsultasi tumbuh kembang anak usia 18 bulan.', 'Selesai', '2026-06-28 13:32:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesan`
--

CREATE TABLE `pesan` (
  `id` varchar(20) NOT NULL,
  `nama` varchar(150) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `subjek` varchar(200) DEFAULT NULL,
  `pesan` text NOT NULL,
  `dibaca` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `rekammedis`
--

CREATE TABLE `rekammedis` (
  `id` varchar(20) NOT NULL,
  `pasien_id` varchar(20) NOT NULL,
  `pasien_nama` varchar(150) DEFAULT NULL,
  `dokter_id` varchar(20) DEFAULT NULL,
  `dokter_nama` varchar(150) DEFAULT NULL,
  `tanggal` varchar(20) NOT NULL,
  `keluhan` text DEFAULT NULL,
  `diagnosis` text NOT NULL,
  `tekanan_darah` varchar(20) DEFAULT NULL,
  `suhu` varchar(10) DEFAULT NULL,
  `berat` varchar(10) DEFAULT NULL,
  `obat_id` varchar(20) DEFAULT NULL,
  `obat_nama` varchar(150) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `rekammedis`
--

INSERT INTO `rekammedis` (`id`, `pasien_id`, `pasien_nama`, `dokter_id`, `dokter_nama`, `tanggal`, `keluhan`, `diagnosis`, `tekanan_darah`, `suhu`, `berat`, `obat_id`, `obat_nama`, `catatan`, `created_at`) VALUES
('mqxf4kr2ftgm', 'mqxeyryq38dd', 'nasichatun', 'mqxf27expu9a', 'dr. Reza', '2026-06-28', 'sakit gigi', 'sakit gigi', '120/80', '36.5', '46', 'o5', 'Metformin 500mg', 'jangan gamon', '2026-06-28 06:39:02'),
('rm1', 'p3', 'Rizky Ramadhan', 'd3', 'drg. Budi Wijaya', '2026-06-28', 'Nyeri berdenyut pada gigi geraham kanan bawah sejak 2 hari.', 'Karies gigi molar kanan bawah grade II', '120/80', '36.5', '70', 'o2', 'Amoxicillin 500mg', 'Pasien diminta kontrol 1 minggu lagi untuk cek kondisi tambalan.', '2026-06-28 13:32:54'),
('rm2', 'p7', 'Eko Prasetyo', 'd1', 'dr. Ahmad Santoso', '2026-06-27', 'Kontrol gula darah rutin. Tidak ada keluhan. Patuh minum obat.', 'Diabetes Melitus Tipe 2 terkontrol', '130/85', '36.7', '75', 'o5', 'Metformin 500mg', 'Gula darah terkontrol. Kontrol 1 bulan lagi.', '2026-06-28 13:32:54'),
('rm3', 'p7', 'Eko Prasetyo', 'd4', 'dr. Heru Prasetyo', '2026-06-27', 'Nyeri lutut kanan sejak 1 minggu. Nyeri saat naik tangga, bengkak minimal.', 'Osteoarthritis genu dextra ringan', '145/90', '36.8', '88', NULL, NULL, 'Dirujuk rontgen lutut kanan. Kompres air hangat.', '2026-06-28 13:32:54');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `adminusers`
--
ALTER TABLE `adminusers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_username` (`username`);

--
-- Indeks untuk tabel `billing`
--
ALTER TABLE `billing`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_no_bill` (`no_bill`);

--
-- Indeks untuk tabel `dokter`
--
ALTER TABLE `dokter`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `obat`
--
ALTER TABLE `obat`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pasien`
--
ALTER TABLE `pasien`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_nik` (`nik`);

--
-- Indeks untuk tabel `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_no_reg` (`no_reg`);

--
-- Indeks untuk tabel `pesan`
--
ALTER TABLE `pesan`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `rekammedis`
--
ALTER TABLE `rekammedis`
  ADD PRIMARY KEY (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
