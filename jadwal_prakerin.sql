-- ============================================================
-- MIGRASI: Fitur Jadwal Prakerin, Hari Libur, Konfirmasi DU/DI
-- Jalankan file ini di database yang sudah ada
-- ============================================================

-- 1. Jadwal Prakerin (universal / berlaku semua siswa)
CREATE TABLE IF NOT EXISTS `jadwal_prakerin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tahun_ajaran` varchar(20) NOT NULL COMMENT 'contoh: 2024/2025',
  `tanggal_mulai` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'user_id admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `jp_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Hari Libur
--    tempat_pkl_id NULL  = libur nasional (dari admin, berlaku semua)
--    tempat_pkl_id ISI   = libur khusus tempat PKL tersebut (dari DU/DI)
CREATE TABLE IF NOT EXISTS `hari_libur` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `nama_libur` varchar(150) NOT NULL,
  `tempat_pkl_id` int(11) DEFAULT NULL COMMENT 'NULL = nasional, ISI = khusus tempat PKL',
  `dibuat_oleh` int(11) DEFAULT NULL COMMENT 'user_id admin atau instruktur',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tempat_pkl_id` (`tempat_pkl_id`),
  KEY `tanggal` (`tanggal`),
  CONSTRAINT `hl_ibfk_1` FOREIGN KEY (`tempat_pkl_id`) REFERENCES `tempat_pkl` (`id`) ON DELETE CASCADE,
  CONSTRAINT `hl_ibfk_2` FOREIGN KEY (`dibuat_oleh`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Konfirmasi Aktivasi DU/DI per Tempat PKL
CREATE TABLE IF NOT EXISTS `konfirmasi_prakerin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tempat_pkl_id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `instruktur_id` int(11) NOT NULL COMMENT 'instruktur yang mengkonfirmasi',
  `dikonfirmasi_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `catatan` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tempat_jadwal` (`tempat_pkl_id`, `jadwal_id`),
  KEY `jadwal_id` (`jadwal_id`),
  KEY `instruktur_id` (`instruktur_id`),
  CONSTRAINT `kp_ibfk_1` FOREIGN KEY (`tempat_pkl_id`) REFERENCES `tempat_pkl` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kp_ibfk_2` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal_prakerin` (`id`) ON DELETE CASCADE,
  CONSTRAINT `kp_ibfk_3` FOREIGN KEY (`instruktur_id`) REFERENCES `instruktur` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
