<?php
/**
 * Middleware Onboarding Siswa
 * Include file ini di SEMUA halaman siswa (setelah auth check)
 * Redirect ke onboarding jika profil belum lengkap
 *
 * Usage: require '../../config/onboarding_check.php';
 */

// Hanya berlaku untuk role siswa
if (($_SESSION['role'] ?? '') !== 'siswa') return;

// Kalau sudah diketahui lengkap dari session, skip query
if (!empty($_SESSION['profil_lengkap'])) return;

// Hindari redirect loop
$current_path = $_SERVER['PHP_SELF'] ?? '';
if (str_contains($current_path, '/onboarding/')) return;

// Query ke DB
global $conn;
$uid  = (int)($_SESSION['user_id'] ?? 0);

// Cek kolom profil_lengkap ada (defensive)
$cek_kol = mysqli_query($conn, "SHOW COLUMNS FROM siswa LIKE 'profil_lengkap'");
if (!$cek_kol || mysqli_num_rows($cek_kol) === 0) return; // kolom belum ada, skip

$stmt = mysqli_prepare($conn, "SELECT profil_lengkap, no_hp, foto, nama_ayah, no_hp_ortu FROM siswa WHERE user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$row) return;

// Cek kelengkapan
$lengkap = $row['profil_lengkap']
    || (!empty($row['no_hp']) && !empty($row['foto']) && !empty($row['nama_ayah']) && !empty($row['no_hp_ortu']));

if ($lengkap) {
    $_SESSION['profil_lengkap'] = 1;
    return;
}

// Belum lengkap — redirect ke onboarding
// Tentukan step yang belum selesai
if (empty($row['no_hp'])) {
    header("Location: /SiPrakerin_fixed/siswa/onboarding/index.php?step=1"); exit;
} elseif (empty($row['nama_ayah']) || empty($row['no_hp_ortu'])) {
    header("Location: /SiPrakerin_fixed/siswa/onboarding/index.php?step=2"); exit;
} elseif (empty($row['foto'])) {
    header("Location: /SiPrakerin_fixed/siswa/onboarding/index.php?step=3"); exit;
}
