-- ============================================================
--  must_change_password.sql
--  Tambah kolom flag "wajib ganti password" di tabel users
--  Dijalankan SEKALI setelah deploy
-- ============================================================

-- Tambah kolom (aman dijalankan ulang)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0;

-- Aktifkan flag untuk SEMUA akun instruktur & guru yang masih pakai password default
-- (password default: '123456' → hash berbeda tiap server, jadi kita flag semua dulu)
UPDATE `users`
  SET `must_change_password` = 1
  WHERE `role` IN ('instruktur', 'guru');

-- Catatan: setelah user ganti password, flag otomatis di-reset ke 0 oleh aplikasi
