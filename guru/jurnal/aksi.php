<?php
require '../middleware/auth_guru.php';
require '../../config/database.php';

$id   = (int)($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';
$ref  = $_GET['ref'] ?? '../jurnal/index.php';

if ($id <= 0 || !in_array($aksi, ['setuju','tolak'])) {
    header("Location: index.php"); exit;
}

$status = $aksi === 'setuju' ? 'disetujui' : 'ditolak';

$stmt = mysqli_prepare($conn, "UPDATE jurnal SET status = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "si", $status, $id);
mysqli_stmt_execute($stmt);

header("Location: " . $ref);
exit;
