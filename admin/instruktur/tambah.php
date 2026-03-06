<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';
$page_title = 'Tambah Instruktur';
$error = '';

$tempat_list = mysqli_query($conn,"SELECT id,nama_tempat FROM tempat_pkl ORDER BY nama_tempat");

if($_SERVER['REQUEST_METHOD']==='POST'){
  $nama      = trim($_POST['nama']);
  $jabatan   = trim($_POST['jabatan']);
  $no_hp     = trim($_POST['no_hp']);
  $tempat_id = (int)$_POST['tempat_pkl_id'] ?: null;
  $username  = trim($_POST['username']);

  $cek=mysqli_prepare($conn,"SELECT id FROM users WHERE username=?");
  mysqli_stmt_bind_param($cek,"s",$username); mysqli_stmt_execute($cek);
  mysqli_stmt_store_result($cek);

  if(mysqli_stmt_num_rows($cek)>0){
    $error="Username '$username' sudah dipakai!";
  } else {
    $hash=password_hash('123456',PASSWORD_DEFAULT);
    $s1=mysqli_prepare($conn,"INSERT INTO users(nama,username,password,role) VALUES(?,?,?,'instruktur')");
    mysqli_stmt_bind_param($s1,"sss",$nama,$username,$hash);
    if(mysqli_stmt_execute($s1)){
      $uid=mysqli_insert_id($conn);
      $s2=mysqli_prepare($conn,"INSERT INTO instruktur(user_id,jabatan,no_hp,tempat_pkl_id) VALUES(?,?,?,?)");
      mysqli_stmt_bind_param($s2,"issi",$uid,$jabatan,$no_hp,$tempat_id);
      mysqli_stmt_execute($s2);
      header("Location: index.php?msg=ok"); exit;
    } else { $error="Gagal menyimpan."; }
  }
}
require '../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center page-header">
  <div><h1>Tambah Instruktur</h1><p>Daftarkan instruktur dari dunia usaha/industri.</p></div>
  <a href="index.php" class="btn-edit-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if($error): ?><div class="alert-error-custom mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="form-card" style="max-width:640px;">
  <div class="form-card-header"><i class="bi bi-building me-2"></i>Data Instruktur</div>
  <div class="form-card-body">
    <div class="alert-success-custom mb-3" style="background:#eff6ff;color:#14532d;border-color:#bfdbfe;">
      <i class="bi bi-info-circle me-2"></i>Password default akun: <strong>123456</strong>. Username bisa diisi NIP atau nama tanpa spasi.
    </div>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Nama Lengkap</label>
        <input name="nama" type="text" class="form-control" placeholder="Nama instruktur" required value="<?= htmlspecialchars($_POST['nama']??'') ?>">
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Jabatan di Perusahaan</label>
          <input name="jabatan" type="text" class="form-control" placeholder="Contoh: HRD Manager" value="<?= htmlspecialchars($_POST['jabatan']??'') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">No. HP</label>
          <input name="no_hp" type="text" class="form-control" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($_POST['no_hp']??'') ?>">
        </div>
      </div>
      <div class="mb-3 mt-3">
        <label class="form-label">Tempat PKL</label>
        <select name="tempat_pkl_id" class="form-select">
          <option value="">-- Pilih Tempat PKL --</option>
          <?php while($t=mysqli_fetch_assoc($tempat_list)): ?>
          <option value="<?= $t['id'] ?>" <?= (($_POST['tempat_pkl_id']??'')==$t['id'])?'selected':'' ?>><?= htmlspecialchars($t['nama_tempat']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="mb-3">
        <label class="form-label">Username Akun</label>
        <input name="username" type="text" class="form-control" placeholder="Username untuk login" required value="<?= htmlspecialchars($_POST['username']??'') ?>">
        <div class="form-hint">Username harus unik, tidak boleh ada spasi.</div>
      </div>
      <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn-primary-custom"><i class="bi bi-check-lg"></i> Simpan</button>
        <a href="index.php" class="btn-edit-sm">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require '../layout/footer.php'; ?>
