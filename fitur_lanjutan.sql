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
