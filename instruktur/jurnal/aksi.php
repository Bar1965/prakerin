<?php
require '../middleware/auth_instruktur.php';
require '../../config/database.php';

$id   = (int)($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';
$ref  = $_GET['ref'] ?? '../jurnal/index.php';

if ($id <= 0 || !in_array($aksi, ['setuju','tolak'])) {
    header("Location: index.php"); exit;
}

// Instruktur update kolom status_instruktur (bukan status — itu milik guru)
$status  = $aksi === 'setuju' ? 'disetujui' : 'ditolak';
$catatan = trim($_GET['catatan'] ?? '');

$stmt = mysqli_prepare($conn, "UPDATE jurnal SET status_instruktur = ?, catatan_instruktur = ? WHERE id = ?");
mysqli_stmt_bind_param($stmt, "ssi", $status, $catatan, $id);
mysqli_stmt_execute($stmt);

header("Location: " . $ref);
exit;
