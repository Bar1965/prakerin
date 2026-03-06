# 🚀 Panduan Deploy SiPrakerin ke Hosting

## 1. Upload Files
Upload semua isi folder ke `public_html/` (atau subfolder, misal `public_html/siprakerin/`)

## 2. Buat Database di cPanel
- Login cPanel → **MySQL Databases**
- Buat database baru (misal: `usermu_prakerin`)
- Buat user baru dan assign ke database (centang **All Privileges**)
- Masuk ke **phpMyAdmin** → import file `db_prakerin_v3.sql`

## 3. Edit Konfigurasi
Buka file `config/database.php` dan ganti:
```php
$user = "usermu_prakerin_user";   // user DB dari cPanel
$pass = "passwordnya";             // password DB
$db   = "usermu_prakerin";        // nama database
```

## 4. Buat Folder Upload
Di phpMyAdmin atau File Manager, pastikan folder `uploads/absensi/` ada dan bisa ditulis:
```
public_html/siprakerin/uploads/absensi/   (chmod 755)
```

## 5. Jalankan SQL Tambahan
Di phpMyAdmin, jalankan juga:
```sql
ALTER TABLE `absensi` ADD COLUMN IF NOT EXISTS `foto_selfie` varchar(255) DEFAULT NULL AFTER `jam_masuk`;
```
> **Catatan**: Kalau dapat error `IF NOT EXISTS`, hapus bagian itu:
> `ALTER TABLE absensi ADD COLUMN foto_selfie varchar(255) DEFAULT NULL AFTER jam_masuk;`

## 6. Akun Default Login
| Role       | Username  | Password |
|------------|-----------|----------|
| Admin      | admin     | admin123 |
| Guru       | guru1     | 123456   |
| Siswa      | siswa1    | 123456   |
| Instruktur | instruktur1 | 123456 |

## ⚠️ Catatan Penting
- Fitur kamera selfie memerlukan **HTTPS** (SSL) agar browser mengizinkan akses kamera
- Untuk hosting gratis, pastikan provider mendukung PHP 7.4+ dan MySQL 5.7+
- Jika pakai subfolder (bukan root), tidak perlu ubah kode — path sudah relative

## 📱 Sosmed Sekolah di Footer
Edit file `siswa/layout/footer.php` dan `admin/layout/footer.php` untuk mengubah link sosmed sekolah.
