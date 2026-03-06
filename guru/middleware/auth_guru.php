<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'guru') {
    header("Location: ../../auth/login.php");
    exit;
}

// ── Wajib ganti password — intercept semua halaman guru ──
$current_page = basename($_SERVER['PHP_SELF']);
if (!empty($_SESSION['must_change_password']) && $current_page !== 'ganti_password.php') {
    header("Location: ../../guru/ganti_password.php");
    exit;
}
