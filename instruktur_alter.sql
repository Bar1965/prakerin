-- ============================================================
-- Migration: Tambah kolom yang kurang di tabel instruktur
-- Jalankan di phpMyAdmin sebelum menggunakan halaman Profil Instruktur
-- ============================================================

ALTER TABLE `instruktur`
  ADD COLUMN IF NOT EXISTS `nip`        varchar(30)  DEFAULT NULL AFTER `user_id`,
  ADD COLUMN IF NOT EXISTS `pendidikan` varchar(10)  DEFAULT NULL AFTER `alamat`;

-- Verifikasi struktur akhir
DESCRIBE instruktur;
