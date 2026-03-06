<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

// FIX: Cast ke int untuk mencegah SQL Injection
$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php");
    exit;
}

$stmt = mysqli_prepare($conn, "DELETE FROM tempat_pkl WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

header("Location: index.php?msg=deleted");
exit;
