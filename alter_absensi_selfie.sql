-- Jalankan query ini di phpMyAdmin untuk menambah kolom foto selfie pada tabel absensi
-- dan membuat folder uploads/absensi/ dapat diakses

ALTER TABLE `absensi` 
  ADD COLUMN `foto_selfie` varchar(255) DEFAULT NULL 
  COMMENT 'Path foto selfie absensi siswa' 
  AFTER `jam_masuk`;

-- ============================================================
-- Migration: Tambah kolom lokasi GPS di tabel absensi
-- ============================================================
ALTER TABLE `absensi`
  ADD COLUMN IF NOT EXISTS `latitude`     decimal(10,8) DEFAULT NULL AFTER `foto_selfie`,
  ADD COLUMN IF NOT EXISTS `longitude`    decimal(11,8) DEFAULT NULL AFTER `latitude`,
  ADD COLUMN IF NOT EXISTS `alamat_lokasi` varchar(255) DEFAULT NULL AFTER `longitude`;
