<?php
// session_start() dipanggil di halaman pemanggil, bukan di sini
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'admin') {
    // FIX: Pakai path relatif agar jalan di semua hosting
    header("Location: ../../auth/login.php");
    exit;
}
