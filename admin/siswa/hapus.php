<?php
session_start();
require '../../config/database.php';

if ($_SESSION['role'] !== 'admin') exit;

$id = $_GET['id'];

/* Ambil user_id dulu */
$stmt = mysqli_prepare($conn, "SELECT user_id FROM siswa WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if ($data) {
    $user_id = $data['user_id'];

    // Hapus dari users (akan cascade ke siswa)
    $stmt2 = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt2, "i", $user_id);
    mysqli_stmt_execute($stmt2);
}

header("Location: index.php");
exit;
