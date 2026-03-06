<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

// Validasi file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    header("Location: import.php?err=File+gagal+diupload");
    exit;
}

$file     = $_FILES['file']['tmp_name'];
$filename = $_FILES['file']['name'];

// Pastikan ekstensi .csv
if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'csv') {
    header("Location: import.php?err=Hanya+file+.csv+yang+diterima");
    exit;
}

// Baca CSV
$handle = fopen($file, 'r');
if (!$handle) {
    header("Location: import.php?err=Gagal+membaca+file");
    exit;
}

$baris = 0;
$imported = 0;
$skipped  = 0;

while (($row = fgetcsv($handle, 1000, ',')) !== false) {
    $baris++;
    if ($baris === 1) continue; // skip header

    // Ambil kolom
    $nama = isset($row[0]) ? trim($row[0]) : '';
    $nip  = isset($row[1]) ? trim($row[1]) : '';

    if ($nama === '' || $nip === '') continue; // skip baris kosong

    $username = $nip;
    $password = password_hash('123456', PASSWORD_DEFAULT);

    // Cek duplikat pakai prepared statement (aman dari SQL injection)
    $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
    mysqli_stmt_bind_param($cek, "s", $username);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);

    if (mysqli_stmt_num_rows($cek) > 0) {
        $skipped++;
        continue;
    }

    // Insert user
    $s1 = mysqli_prepare($conn, "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'guru')");
    mysqli_stmt_bind_param($s1, "sss", $nama, $username, $password);

    if (mysqli_stmt_execute($s1)) {
        $user_id = mysqli_insert_id($conn);

        // Insert guru
        $s2 = mysqli_prepare($conn, "INSERT INTO guru (user_id, nip) VALUES (?, ?)");
        mysqli_stmt_bind_param($s2, "is", $user_id, $nip);
        mysqli_stmt_execute($s2);

        $imported++;
    }
}

fclose($handle);

header("Location: import.php?msg=ok&imported={$imported}&skipped={$skipped}");
exit;
