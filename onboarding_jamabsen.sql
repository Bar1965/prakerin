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
