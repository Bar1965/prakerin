-- Use the correct database
USE db_prakerin;

-- ============================================================
--  db_prakerin.sql - VERSI DIPERBAIKI
--  SiPrakerin - SMK Negeri 3 Padang
--  Perbaikan:
--  [1] Hapus kolom duplikat `tempat_pkl` (varchar) di tabel siswa
--  [2] Tambah kolom `nama_ayah` yang dipanggil di detail.php
--  [3] Fix status jurnal: konsisten pakai 'pending'
--  [4] Tambah data sample guru
--  [5] Fix data sample siswa (user siswa1 sekarang punya data siswa)
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================================
-- TABEL: users
-- ============================================================
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','siswa','guru') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data Sample Users
-- Password semua: admin123 (admin), 123456 (guru & siswa)
INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`, `created_at`) VALUES
(1,  'Administrator',       'admin',    '$2y$10$uwlAnrCQIbZJ24PurTfVWe0XpxGKEfu4BjM8VZ85B5F9tp17nYFPq', 'admin', '2026-02-07 05:20:12'),
-- Guru (password: 123456)
(2,  'Budi Santoso, S.Kom', '197501012005011001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6', 'guru',  '2026-02-07 05:20:15'),
(3,  'Siti Rahayu, S.Pd',   '198003152008012002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6', 'guru',  '2026-02-07 05:20:16'),
-- Siswa (password: 123456)
(4,  'Ahmad Fauzi',         '12345',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6', 'siswa', '2026-02-07 05:20:20'),
(5,  'Rina Wulandari',      '12346',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6', 'siswa', '2026-02-07 05:20:21'),
(6,  'Deni Kurniawan',      '12347',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6', 'siswa', '2026-02-07 05:20:22');

-- ============================================================
-- TABEL: tempat_pkl
-- ============================================================
CREATE TABLE `tempat_pkl` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_tempat` varchar(150) NOT NULL,
  `alamat` text DEFAULT NULL,
  `pembimbing_lapangan` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `kuota` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `tempat_pkl` (`id`, `nama_tempat`, `alamat`, `pembimbing_lapangan`, `no_hp`, `kuota`) VALUES
(1, 'PT. Telkom Indonesia',       'Jl. Sudirman No. 1, Padang',         'Andi Wijaya',    '08112345678', 5),
(2, 'Dinas Komunikasi Kota Padang','Jl. Bagindo Aziz Chan No. 2, Padang','Hendra Saputra', '08223456789', 3),
(3, 'CV. Mitra Digital',           'Jl. Pemuda No. 15, Padang',          'Rini Puspita',   '08334567890', 4);

-- ============================================================
-- TABEL: guru
-- ============================================================
CREATE TABLE `guru` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('pria','wanita') DEFAULT NULL,
  `pendidikan` enum('D3','S1','S2','S3') DEFAULT NULL,
  `mata_pelajaran` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `guru_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `guru` (`id`, `user_id`, `nip`) VALUES
(1, 2, '197501012005011001'),
(2, 3, '198003152008012002');

-- ============================================================
-- TABEL: siswa
-- FIX [1]: Hapus kolom `tempat_pkl` varchar (duplikat), pakai `tempat_pkl_id` FK saja
-- FIX [2]: Tambah kolom `nama_ayah` (dipakai di detail.php)
-- ============================================================
CREATE TABLE `siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `kelas` varchar(20) NOT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `guru_id` int(11) DEFAULT NULL,
  `tempat_pkl_id` int(11) DEFAULT NULL,
  -- Data Pribadi
  `jenis_kelamin` enum('pria','wanita') DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  -- Data Orang Tua (FIX: tambah kolom yang dipakai di detail.php)
  `nama_ayah` varchar(100) DEFAULT NULL,
  `alamat_ortu` text DEFAULT NULL,
  `no_hp_ortu` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `tempat_pkl_id` (`tempat_pkl_id`),
  KEY `guru_id` (`guru_id`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`)      ON DELETE CASCADE,
  CONSTRAINT `siswa_ibfk_2` FOREIGN KEY (`tempat_pkl_id`) REFERENCES `tempat_pkl` (`id`) ON DELETE SET NULL,
  CONSTRAINT `siswa_ibfk_3` FOREIGN KEY (`guru_id`)       REFERENCES `guru` (`id`)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FIX [5]: Data sample siswa yang lengkap dan terelasi benar
INSERT INTO `siswa` (`id`, `user_id`, `nis`, `nisn`, `kelas`, `jurusan`, `guru_id`, `tempat_pkl_id`, `jenis_kelamin`, `tempat_lahir`, `tanggal_lahir`, `alamat`, `no_hp`, `email`, `nama_ayah`, `no_hp_ortu`) VALUES
(1, 4, '12345', '0012345678', 'XI RPL 1', 'Rekayasa Perangkat Lunak', 1, 1, 'pria',   'Padang', '2008-03-15', 'Jl. Khatib Sulaiman No. 5, Padang', '08512345671', 'ahmad.f@email.com', 'Fauzi Rahman',  '08612345671'),
(2, 5, '12346', '0012345679', 'XI RPL 1', 'Rekayasa Perangkat Lunak', 1, 2, 'wanita', 'Padang', '2008-07-22', 'Jl. Andalas No. 12, Padang',        '08512345672', 'rina.w@email.com',  'Wulan Sari',    '08612345672'),
(3, 6, '12347', '0012345680', 'XI RPL 2', 'Rekayasa Perangkat Lunak', 2, 3, 'pria',   'Padang', '2008-11-05', 'Jl. Veteran No. 8, Padang',          '08512345673', 'deni.k@email.com',  'Kurnia Halim',  '08612345673');

-- ============================================================
-- TABEL: jurnal
-- FIX [3]: Status default 'pending' (konsisten dengan enum & kode PHP)
-- ============================================================
CREATE TABLE `jurnal` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `kegiatan` text NOT NULL,
  `status` enum('pending','disetujui','ditolak') DEFAULT 'pending',
  `catatan_guru` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `siswa_id` (`siswa_id`),
  CONSTRAINT `jurnal_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data sample jurnal
INSERT INTO `jurnal` (`siswa_id`, `tanggal`, `kegiatan`, `status`, `catatan_guru`) VALUES
(1, '2026-02-10', 'Orientasi tempat PKL, perkenalan dengan staf dan pembimbing lapangan. Mempelajari SOP perusahaan.', 'disetujui', 'Bagus, terus semangat!'),
(1, '2026-02-11', 'Membantu tim IT dalam instalasi software dan konfigurasi jaringan lokal kantor.', 'disetujui', NULL),
(1, '2026-02-12', 'Belajar troubleshooting komputer, menangani 3 keluhan pengguna tentang koneksi internet.', 'pending', NULL),
(2, '2026-02-10', 'Hari pertama PKL di Dinas Kominfo. Pengenalan struktur organisasi dan tugas bagian IT.', 'disetujui', 'Pertahankan!'),
(2, '2026-02-11', 'Membantu penginputan data arsip digital dan pemeliharaan website resmi dinas.', 'pending', NULL),
(3, '2026-02-10', 'Orientasi di CV. Mitra Digital. Perkenalan dengan tim developer dan project yang sedang berjalan.', 'disetujui', 'Aktif bertanya ya!'),
(3, '2026-02-11', 'Belajar dasar-dasar HTML dan CSS dari senior developer. Membuat halaman statis sederhana.', 'ditolak', 'Tolong perjelas kegiatan yang dilakukan ya.');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================
-- UPDATE v3: Tambah role instruktur & tabel instruktur
-- ============================================================

-- Update enum role di users agar support instruktur
ALTER TABLE `users` MODIFY `role` enum('admin','siswa','guru','instruktur') NOT NULL;

-- Tabel instruktur (mirip guru tapi terikat ke tempat_pkl)
CREATE TABLE IF NOT EXISTS `instruktur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tempat_pkl_id` int(11) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `jenis_kelamin` enum('pria','wanita') DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `tempat_pkl_id` (`tempat_pkl_id`),
  CONSTRAINT `instruktur_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `instruktur_ibfk_2` FOREIGN KEY (`tempat_pkl_id`) REFERENCES `tempat_pkl` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kolom instruktur_id di siswa (instruktur perusahaan yang membimbing)
ALTER TABLE `siswa` ADD COLUMN IF NOT EXISTS `instruktur_id` int(11) DEFAULT NULL;
ALTER TABLE `siswa` ADD CONSTRAINT `siswa_ibfk_4` FOREIGN KEY (`instruktur_id`) REFERENCES `instruktur` (`id`) ON DELETE SET NULL;

-- Sample data instruktur (password: 123456)
INSERT INTO `users` (`id`, `nama`, `username`, `password`, `role`) VALUES
(7, 'Andi Wijaya', 'instruktur1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6', 'instruktur'),
(8, 'Rini Puspita', 'instruktur2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6', 'instruktur');

INSERT INTO `instruktur` (`user_id`, `tempat_pkl_id`, `jabatan`) VALUES
(7, 1, 'Senior Network Engineer'),
(8, 3, 'Web Developer');

-- Hubungkan siswa ke instruktur
UPDATE `siswa` SET `instruktur_id` = 1 WHERE `id` IN (1,2);
UPDATE `siswa` SET `instruktur_id` = 2 WHERE `id` = 3;

-- Tambah catatan_instruktur di jurnal (instruktur juga bisa beri catatan)
ALTER TABLE `jurnal` ADD COLUMN IF NOT EXISTS `status_instruktur` enum('pending','disetujui','ditolak') DEFAULT 'pending';
ALTER TABLE `jurnal` ADD COLUMN IF NOT EXISTS `catatan_instruktur` text DEFAULT NULL;

-- ============================================================
-- TAMBAHAN: Tabel instruktur, absensi, penilaian
-- ============================================================

-- Tabel instruktur (dari dunia usaha/industri)
CREATE TABLE IF NOT EXISTS `instruktur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `nip_nrk` varchar(30) DEFAULT NULL,
  `tempat_pkl_id` int(11) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `instr_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `instr_ibfk_2` FOREIGN KEY (`tempat_pkl_id`) REFERENCES `tempat_pkl` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kolom instruktur_id di tabel siswa (jika belum ada)
ALTER TABLE `siswa` ADD COLUMN IF NOT EXISTS `instruktur_id` int(11) DEFAULT NULL;
ALTER TABLE `siswa` ADD CONSTRAINT IF NOT EXISTS `siswa_instr_fk` FOREIGN KEY (`instruktur_id`) REFERENCES `instruktur` (`id`) ON DELETE SET NULL;

-- Tabel absensi
CREATE TABLE IF NOT EXISTS `absensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('hadir','sakit','izin','alpha') DEFAULT 'hadir',
  `keterangan` text DEFAULT NULL,
  `jam_masuk` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `siswa_tgl` (`siswa_id`,`tanggal`),
  KEY `siswa_id` (`siswa_id`),
  CONSTRAINT `abs_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel penilaian
CREATE TABLE IF NOT EXISTS `penilaian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `penilai_id` int(11) NOT NULL,
  `aspek` varchar(100) DEFAULT 'Penilaian Umum',
  `nilai_akhir` decimal(5,2) NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `siswa_id` (`siswa_id`),
  CONSTRAINT `nil_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nil_ibfk_2` FOREIGN KEY (`penilai_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tambah role instruktur ke enum users jika belum
-- (Jalankan manual jika error):
-- ALTER TABLE users MODIFY role enum('admin','siswa','guru','instruktur') NOT NULL;

-- Sample data instruktur
INSERT IGNORE INTO `users` (`id`,`nama`,`username`,`password`,`role`) VALUES
(10,'Rizky Pratama, S.T.','instruktur1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6','instruktur'),
(11,'Dian Marlina','instruktur2','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC2/..XNLBJBu1jNxW6','instruktur');

INSERT IGNORE INTO `instruktur` (`id`,`user_id`,`jabatan`,`nip_nrk`,`tempat_pkl_id`) VALUES
(1,10,'Technical Support','EMP-2024-001',1),
(2,11,'HRD Officer','EMP-2024-002',2);

-- Update siswa sample agar ada instruktur_id
UPDATE siswa SET instruktur_id=1 WHERE id=1;
UPDATE siswa SET instruktur_id=1 WHERE id=2;
UPDATE siswa SET instruktur_id=2 WHERE id=3;

-- Sample absensi
INSERT IGNORE INTO `absensi` (`siswa_id`,`tanggal`,`status`,`jam_masuk`) VALUES
(1,'2026-02-10','hadir','2026-02-10 08:00:00'),
(1,'2026-02-11','hadir','2026-02-11 07:55:00'),
(1,'2026-02-12','sakit',NULL),
(2,'2026-02-10','hadir','2026-02-10 08:05:00'),
(2,'2026-02-11','hadir','2026-02-11 08:00:00'),
(3,'2026-02-10','hadir','2026-02-10 07:50:00'),
(3,'2026-02-11','izin',NULL);

-- ============================================================
-- TABEL: kelas (master kelas, bisa dikelola admin)
-- Jalankan query ini di phpMyAdmin
-- ============================================================
CREATE TABLE IF NOT EXISTS `kelas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) NOT NULL,
  `tingkat` enum('X','XI','XII') NOT NULL DEFAULT 'XI',
  `jurusan` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_kelas` (`nama_kelas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data awal kelas
INSERT IGNORE INTO `kelas` (`nama_kelas`, `tingkat`, `jurusan`) VALUES
('X RPL 1',  'X',   'Rekayasa Perangkat Lunak'),
('X RPL 2',  'X',   'Rekayasa Perangkat Lunak'),
('XI RPL 1', 'XI',  'Rekayasa Perangkat Lunak'),
('XI RPL 2', 'XI',  'Rekayasa Perangkat Lunak'),
('XII RPL 1','XII', 'Rekayasa Perangkat Lunak'),
('XII RPL 2','XII', 'Rekayasa Perangkat Lunak'),
('X TKJ 1',  'X',   'Teknik Komputer dan Jaringan'),
('X TKJ 2',  'X',   'Teknik Komputer dan Jaringan'),
('XI TKJ 1', 'XI',  'Teknik Komputer dan Jaringan'),
('XI TKJ 2', 'XI',  'Teknik Komputer dan Jaringan'),
('XII TKJ 1','XII', 'Teknik Komputer dan Jaringan'),
('XII TKJ 2','XII', 'Teknik Komputer dan Jaringan');
