<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';
$id=(int)($_GET['id']??0);
if($id>0){
  $s=mysqli_prepare($conn,"SELECT user_id FROM instruktur WHERE id=?");
  mysqli_stmt_bind_param($s,"i",$id); mysqli_stmt_execute($s);
  $d=mysqli_fetch_assoc(mysqli_stmt_get_result($s));
  if($d){ $del=mysqli_prepare($conn,"DELETE FROM users WHERE id=?"); mysqli_stmt_bind_param($del,"i",$d['user_id']); mysqli_stmt_execute($del); }
}
header("Location: index.php"); exit;
