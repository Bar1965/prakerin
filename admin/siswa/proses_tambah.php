<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit;
}

$nama       = trim($_POST['nama']);
$username   = trim($_POST['username']);
$password   = $_POST['password'];
$nis        = trim($_POST['nis']);
$nisn       = trim($_POST['nisn']);
$kelas      = trim($_POST['kelas']);
$jurusan    = trim($_POST['jurusan']);
$tempat_pkl = trim($_POST['tempat_pkl']);

/* =========================
   VALIDASI DASAR
========================= */
if ($nama == '' || $username == '' || $password == '' || $nis == '') {
    header("Location: tambah.php?error=kosong");
    exit;
}

/* =========================
   CEK USERNAME DUPLIKAT
========================= */
$cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
mysqli_stmt_bind_param($cek, "s", $username);
mysqli_stmt_execute($cek);
$result = mysqli_stmt_get_result($cek);

if (mysqli_num_rows($result) > 0) {
    header("Location: tambah.php?error=username");
    exit;
}

/* =========================
   HASH PASSWORD
========================= */
$password_hash = password_hash($password, PASSWORD_DEFAULT);

/* =========================
   INSERT USERS
========================= */
$stmt = mysqli_prepare($conn,
    "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'siswa')"
);

if (!$stmt) {
    header("Location: tambah.php?error=sistem");
    exit;
}

mysqli_stmt_bind_param($stmt, "sss", $nama, $username, $password_hash);

if (!mysqli_stmt_execute($stmt)) {
    header("Location: tambah.php?error=sistem");
    exit;
}

$user_id = mysqli_insert_id($conn);

/* =========================
   INSERT SISWA
========================= */
$stmt2 = mysqli_prepare($conn,
    "INSERT INTO siswa (user_id, nis, nisn, kelas, jurusan, tempat_pkl)
     VALUES (?, ?, ?, ?, ?, ?)"
);

mysqli_stmt_bind_param($stmt2, "isssss",
    $user_id, $nis, $nisn, $kelas, $jurusan, $tempat_pkl
);

if (!mysqli_stmt_execute($stmt2)) {
    header("Location: tambah.php?error=sistem");
    exit;
}

/* =========================
   SUCCESS
========================= */
header("Location: index.php?success=1");
exit;
