<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

// FIX: Cast ke int untuk mencegah SQL Injection
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

// Ambil user_id dari guru pakai prepared statement
$stmt = mysqli_prepare($conn, "SELECT user_id FROM guru WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($data) {
    // Hapus dari users (guru akan terhapus via ON DELETE CASCADE atau hapus manual)
    $del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($del, "i", $data['user_id']);
    mysqli_stmt_execute($del);
}

header("Location: index.php?msg=deleted");
exit;
