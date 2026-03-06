<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

$id = (int)$_GET['id'];

$password = password_hash('123456', PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn,
"UPDATE users SET password=? WHERE id=?");

mysqli_stmt_bind_param($stmt,"si",$password,$id);
mysqli_stmt_execute($stmt);

header("Location:index.php?reset=success");