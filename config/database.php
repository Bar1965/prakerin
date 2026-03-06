<?php
/**
 * SiPrakerin — Konfigurasi Database
 * ══════════════════════════════════════
 * LOKAL (XAMPP):
 *   $host = "localhost";
 *   $user = "root";
 *   $pass = "";
 *
 * HOSTING (cPanel):
 *   $host = "localhost";          // biasanya tetap localhost
 *   $user = "nama_user_cpanel";   // dari cPanel → MySQL Databases
 *   $pass = "password_db_cpanel"; // password yang dibuat di cPanel
 *   $db   = "nama_db_cpanel";     // format: username_namadb
 */

// ── Konfigurasi otomatis: baca dari environment variable (Docker)
//    atau fallback ke nilai default (XAMPP/cPanel) ──
// DB_HOST default: 127.0.0.1 (all-in-one Docker), 'db' (docker-compose), 'localhost' (XAMPP/cPanel)
$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'siprakerin';
$pass = getenv('DB_PASS') ?: 'siprakerin123';
$db   = getenv('DB_NAME') ?: 'db_prakerin';

$conn = mysqli_connect($host, $user, $pass, $db);

if(!$conn){
    die("Koneksi database gagal. Periksa konfigurasi di config/database.php");
}

// Set charset & collation konsisten — cegah collation mismatch error
mysqli_set_charset($conn, "utf8mb4");
mysqli_query($conn, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_query($conn, "SET collation_connection = utf8mb4_unicode_ci");
?>
