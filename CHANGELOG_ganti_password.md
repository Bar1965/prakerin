# 📝 CHANGELOG — Fitur Wajib Ganti Password

## Daftar File yang Diedit / Dibuat

### 🆕 File Baru

| File | Keterangan |
|------|------------|
| `instruktur/ganti_password.php` | Halaman wajib ganti password untuk instruktur |
| `guru/ganti_password.php` | Halaman wajib ganti password untuk guru |
| `must_change_password.sql` | Migrasi SQL — jalankan sekali di server existing |

### ✏️ File yang Diubah

| File | Perubahan |
|------|-----------|
| `auth/login_proses.php` | Tambah pengecekan kolom `must_change_password` setelah login berhasil; set `$_SESSION['must_change_password']` |
| `instruktur/middleware/auth_instruktur.php` | Tambah intercept redirect ke `ganti_password.php` jika flag aktif |
| `guru/middleware/auth_guru.php` | Tambah intercept redirect ke `ganti_password.php` jika flag aktif |
| `instruktur/dashboard/index.php` | Tambah variabel `$pw_changed` dan banner sukses setelah ganti password |
| `guru/dashboard/index.php` | Tambah variabel `$pw_changed` dan banner sukses setelah ganti password |
| `config/database.php` | Konfigurasi baca dari environment variable (Docker) |
| `init-db/02_patches.sql` | Tambah migration `must_change_password` untuk Docker fresh install |

---

## Cara Kerja

```
Login instruktur/guru
        ↓
login_proses.php → cek kolom must_change_password di DB
        ↓
Jika = 1 → $_SESSION['must_change_password'] = true
        ↓
Redirect ke dashboard → middleware intercept
        ↓
Redirect ke ganti_password.php
        ↓
User isi password baru (min. 8 karakter)
        ↓
DB: must_change_password = 0
Session flag dihapus
        ↓
Redirect ke dashboard + banner sukses ✓
```

---

## Deploy ke Server Existing (bukan Docker fresh install)

Jalankan SQL migrasi ini **sekali** di phpMyAdmin atau mysql CLI:

```sql
-- Dari file must_change_password.sql
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `must_change_password` TINYINT(1) NOT NULL DEFAULT 0;

UPDATE `users`
  SET `must_change_password` = 1
  WHERE `role` IN ('instruktur', 'guru');
```

Untuk Docker fresh install: sudah otomatis lewat `init-db/02_patches.sql`.

---

# 🌙 CHANGELOG — Fitur Dark Mode

## File Baru

| File | Keterangan |
|------|------------|
| `assets/darkmode.css` | Semua CSS dark mode untuk 4 role |
| `assets/darkmode.js` | Toggle logic + anti-flash init |

## File yang Diubah

| File | Perubahan |
|------|-----------|
| `admin/layout/header.php` | Tambah anti-flash script, link darkmode.css/js, tombol toggle di topbar |
| `guru/layout/header.php` | Sama seperti atas |
| `instruktur/layout/header.php` | Sama seperti atas |
| `siswa/layout/header.php` | Sama seperti atas |
| `instruktur/ganti_password.php` | Tambah dark mode support |
| `guru/ganti_password.php` | Tambah dark mode support |

## Cara Kerja

- Preferensi disimpan di `localStorage` (`siprakerin_theme = 'dark'|'light'`)
- Anti-flash script di `<head>` sebelum CSS memastikan tema teraplikasi sebelum halaman render
- `data-theme="dark"` di `<html>` mengaktifkan semua CSS vars dark
- Tombol 🌙/☀️ muncul di topbar semua role
