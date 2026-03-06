<?php
session_start();
require '../config/database.php';
require '../config/helpers.php';

$username    = trim($_POST['username'] ?? '');
$password    = $_POST['password'] ?? '';
$remember_me = isset($_POST['remember_me']);

if ($username === '' || $password === '') {
    header("Location: login.php?error=1"); exit;
}

// ── Cek kolom no_hp ada atau belum (defensive: fitur_lanjutan.sql mungkin belum dijalankan) ──
$kolom_nohp = false;
$cek = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'no_hp'");
if ($cek && mysqli_num_rows($cek) > 0) $kolom_nohp = true;

// ── Ambil data user ──
$select = $kolom_nohp ? "id, nama, password, role, no_hp" : "id, nama, password, role";
$stmt = mysqli_prepare($conn, "SELECT $select FROM users WHERE username=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 's', $username);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$user || !password_verify($password, $user['password'])) {
    header("Location: login.php?error=1"); exit;
}

// ── Ingat Saya ──
if ($remember_me) {
    setcookie('remember_username', $username, time() + (30 * 24 * 60 * 60), '/', '', false, true);
} else {
    setcookie('remember_username', '', time() - 3600, '/');
}

// ── OTP — hanya jika tabel & kolom sudah ada ──
$otp_aktif      = false;
$device_trusted = false;

// Cek tabel config_app & otp_login ada
$tbl_config = mysqli_query($conn, "SHOW TABLES LIKE 'config_app'");
$tbl_otp    = mysqli_query($conn, "SHOW TABLES LIKE 'otp_login'");
$tbl_device = mysqli_query($conn, "SHOW TABLES LIKE 'trusted_device'");

if ($tbl_config && mysqli_num_rows($tbl_config) > 0) {
    $otp_aktif = getConfig($conn, 'otp_aktif') === '1';
}
if ($tbl_device && mysqli_num_rows($tbl_device) > 0) {
    $device_trusted = isDeviceTrusted($conn, $user['id']);
}

$no_hp = $user['no_hp'] ?? '';

if ($otp_aktif && !$device_trusted && !empty($no_hp)
    && $tbl_otp && mysqli_num_rows($tbl_otp) > 0) {

    $_SESSION['otp_user_id']  = $user['id'];
    $_SESSION['otp_nama']     = $user['nama'];
    $_SESSION['otp_role']     = $user['role'];
    $_SESSION['otp_no_hp']    = $no_hp;
    $_SESSION['otp_remember'] = $remember_me;

    $kode  = generateOTP($conn, $user['id']);
    $pesan = "🔐 *SiPrakerin*\nKode OTP login Anda: *$kode*\n\nBerlaku 5 menit. Jangan berikan kode ini kepada siapapun.";
    kirimWA($conn, $no_hp, $pesan);

    header("Location: otp.php"); exit;
}

// ── Login langsung ──
doLogin($conn, $user, $remember_me);

function doLogin($conn, $user, $remember_me = false) {
    // Cek tabel trusted_device sebelum memanggil trustDevice
    $tbl = mysqli_query($conn, "SHOW TABLES LIKE 'trusted_device'");
    if ($remember_me && $tbl && mysqli_num_rows($tbl) > 0) {
        trustDevice($conn, $user['id']);
    }
    $_SESSION['login']   = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['role']    = $user['role'];

    // ── Cek flag wajib ganti password (instruktur & guru) ──
    $kolom_mcp = mysqli_num_rows(mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'must_change_password'")) > 0;
    if ($kolom_mcp) {
        $smcp = mysqli_prepare($conn, "SELECT must_change_password FROM users WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($smcp, 'i', $user['id']);
        mysqli_stmt_execute($smcp);
        $rmcp = mysqli_fetch_assoc(mysqli_stmt_get_result($smcp));
        $_SESSION['must_change_password'] = !empty($rmcp['must_change_password']);
    } else {
        $_SESSION['must_change_password'] = false;
    }

    $map = [
        'admin'      => '../admin/dashboard/index.php',
        'siswa'      => '../siswa/dashboard.php',
        'instruktur' => '../instruktur/dashboard/index.php',
        'guru'       => '../guru/dashboard/index.php',
    ];
    header("Location: " . ($map[$user['role']] ?? 'login.php?error=1')); exit;
}
