<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'instruktur') {
    header("Location: ../../auth/login.php");
    exit;
}

// ── Wajib ganti password — intercept semua halaman instruktur ──
$current_page = basename($_SERVER['PHP_SELF']);
if (!empty($_SESSION['must_change_password']) && $current_page !== 'ganti_password.php') {
    header("Location: ../../instruktur/ganti_password.php");
    exit;
}
