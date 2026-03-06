USE db_prakerin;

-- Patch: selfie column
ALTER TABLE `absensi` ADD COLUMN IF NOT EXISTS `foto_selfie` varchar(255) DEFAULT NULL AFTER `jam_masuk`;
-- ============================================================
-- Migration: Tambah kolom yang kurang di tabel instruktur
-- Jalankan di phpMyAdmin sebelum menggunakan halaman Profil Instruktur
-- ============================================================

ALTER TABLE `instruktur`
  ADD COLUMN IF NOT EXISTS `nip`        varchar(30)  DEFAULT NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `pendidikan` varchar(10)  DEFAULT NULL AFTER `alamat`;

-- Verifikasi struktur akhir
DESCRIBE instruktur;
-- ============================================================
-- FIX: Collation Mismatch
-- Jalankan di phpMyAdmin → tab SQL
-- Menyamakan semua tabel ke utf8mb4_unicode_ci
-- ============================================================

-- Ubah collation database
ALTER DATABASE `db_prakerin`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- Ubah semua tabel sekaligus
ALTER TABLE `users`        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `siswa`        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `guru`         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `instruktur`   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `kelas`        CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `tempat_pkl`   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `jurnal`       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `absensi`      CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `penilaian`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Tabel fitur baru (jalankan hanya jika sudah ada)
ALTER TABLE `jadwal_prakerin`    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `hari_libur`         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `konfirmasi_prakerin` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `notifikasi`         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `config_app`         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `otp_login`          CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `trusted_device`     CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `jam_absen_tempat`   CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `reminder_log`       CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verifikasi (opsional — lihat hasilnya)
SELECT table_name, table_collation
FROM information_schema.tables
WHERE table_schema = 'db_prakerin'
ORDER BY table_name;
-- ============================================================
-- MIGRASI: Jam Absen, Onboarding Flag, Reminder
-- Jalankan di phpMyAdmin (setelah fitur_lanjutan.sql)
-- ============================================================

-- 1. Flag profil sudah lengkap (untuk onboarding)
ALTER TABLE `siswa`
  ADD COLUMN IF NOT EXISTS `profil_lengkap` tinyint(1) DEFAULT 0 COMMENT '0=belum, 1=sudah isi profil';

-- 2. Foto profil di users (untuk guru & instruktur)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `foto` varchar(255) DEFAULT NULL;

-- 3. Jam absen default (dari admin) — disimpan di config_app
-- Jika config_app belum ada, buat dulu
CREATE TABLE IF NOT EXISTS `config_app` (
  `key_name` varchar(50) NOT NULL,
  `key_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `config_app` (`key_name`, `key_value`) VALUES
  ('jam_masuk_default', '07:00'),
  ('jam_pulang_default', '16:00'),
  ('batas_masuk_menit', '30'),   -- toleransi keterlambatan masuk (menit)
  ('pengingat_absen_aktif', '1'),
  ('pengingat_jam', '06:30')     -- jam kirim notif WA pengingat
ON DUPLICATE KEY UPDATE `key_name` = `key_name`;

-- 4. Jam absen override per tempat PKL (dari DU/DI)
CREATE TABLE IF NOT EXISTS `jam_absen_tempat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tempat_pkl_id` int(11) NOT NULL,
  `jam_masuk` time NOT NULL DEFAULT '07:00:00',
  `jam_pulang` time NOT NULL DEFAULT '16:00:00',
  `batas_masuk_menit` int(11) DEFAULT 30 COMMENT 'Toleransi terlambat dalam menit',
  `dibuat_oleh` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tempat_pkl_id` (`tempat_pkl_id`),
  CONSTRAINT `jat_ibfk_1` FOREIGN KEY (`tempat_pkl_id`) REFERENCES `tempat_pkl` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Log pengiriman reminder (hindari double kirim)
CREATE TABLE IF NOT EXISTS `reminder_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `dikirim_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `siswa_tgl` (`siswa_id`, `tanggal`),
  CONSTRAINT `rl_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `siswa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
-- ============================================================
-- MIGRASI: OTP WhatsApp + Notifikasi + Device Trust
-- Jalankan di phpMyAdmin setelah jadwal_prakerin.sql
-- ============================================================

-- 1. Kolom no_hp di tabel users (untuk kirim OTP WA)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `no_hp` varchar(20) DEFAULT NULL COMMENT 'Nomor WA untuk OTP',
  ADD COLUMN IF NOT EXISTS `email` varchar(100) DEFAULT NULL;

-- 2. OTP Login
CREATE TABLE IF NOT EXISTS `otp_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `kode` varchar(6) NOT NULL,
  `expired_at` datetime NOT NULL,
  `digunakan` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `otp_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Device yang sudah dipercaya (tidak perlu OTP lagi)
CREATE TABLE IF NOT EXISTS `trusted_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `device_token` varchar(64) NOT NULL COMMENT 'Token unik disimpan di cookie',
  `user_agent` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `last_used` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_device` (`user_id`, `device_token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `td_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Notifikasi in-app
CREATE TABLE IF NOT EXISTS `notifikasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL COMMENT 'Penerima notifikasi',
  `judul` varchar(150) NOT NULL,
  `pesan` text NOT NULL,
  `tipe` enum('jurnal','absensi','konfirmasi','sistem') DEFAULT 'sistem',
  `link` varchar(255) DEFAULT NULL COMMENT 'URL tujuan saat diklik',
  `dibaca` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `dibaca` (`dibaca`),
  CONSTRAINT `notif_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Config WA API (disimpan di DB supaya admin bisa ganti dari panel)
CREATE TABLE IF NOT EXISTS `config_app` (
  `key_name` varchar(50) NOT NULL,
  `key_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `config_app` (`key_name`, `key_value`) VALUES
  ('wa_api_token', ''),
  ('wa_api_url', 'https://api.fonnte.com/send'),
  ('wa_sender', ''),
  ('otp_aktif', '1')
ON DUPLICATE KEY UPDATE `key_name` = `key_name`;

-- ── must_change_password flag ──────────────────────────────────────────────
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0;

UPDATE `users`
  SET `must_change_password` = 1
  WHERE `role` IN ('instruktur', 'guru');
