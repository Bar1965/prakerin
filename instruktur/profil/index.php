<?php
require '../middleware/auth_instruktur.php';
require '../../config/database.php';

$page_title = 'Profil Saya';
$user_id    = $_SESSION['user_id'];

// ── Deteksi kolom yang ada di tabel instruktur ──
$has_nip       = mysqli_num_rows(mysqli_query($conn,"SHOW COLUMNS FROM instruktur LIKE 'nip'")) > 0;
$has_pendidikan= mysqli_num_rows(mysqli_query($conn,"SHOW COLUMNS FROM instruktur LIKE 'pendidikan'")) > 0;
$has_jabatan   = mysqli_num_rows(mysqli_query($conn,"SHOW COLUMNS FROM instruktur LIKE 'jabatan'")) > 0;

// Bangun SELECT dinamis berdasarkan kolom yang tersedia
$extra_cols = "";
if ($has_nip)        $extra_cols .= ", i.nip";
if ($has_pendidikan) $extra_cols .= ", i.pendidikan";
if ($has_jabatan)    $extra_cols .= ", i.jabatan";

// ── Ambil data INSTRUKTUR ──
$stmt = mysqli_prepare($conn, "
    SELECT i.id as instr_id, i.no_hp, i.alamat, i.tempat_lahir,
           i.tanggal_lahir, i.jenis_kelamin,
           i.tempat_pkl_id, tp.nama_tempat,
           u.nama, u.username, u.created_at
           $extra_cols
    FROM instruktur i
    JOIN users u ON i.user_id = u.id
    LEFT JOIN tempat_pkl tp ON tp.id = i.tempat_pkl_id
    WHERE i.user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$instr = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Fallback jika record instruktur tidak ditemukan
if (!$instr) {
    $fb = mysqli_prepare($conn, "SELECT nama,username,created_at FROM users WHERE id=?");
    mysqli_stmt_bind_param($fb, "i", $user_id);
    mysqli_stmt_execute($fb);
    $u = mysqli_fetch_assoc(mysqli_stmt_get_result($fb)) ?? [];
    $instr = [
        'instr_id'=>0,'nip'=>'','no_hp'=>'','alamat'=>'','tempat_lahir'=>'',
        'tanggal_lahir'=>'','jenis_kelamin'=>'','pendidikan'=>'',
        'jabatan'=>'','tempat_pkl_id'=>null,'nama_tempat'=>'',
        'nama'=>$u['nama']??'','username'=>$u['username']??'',
        'created_at'=>$u['created_at']??date('Y-m-d'),
    ];
}
// Pastikan key opsional selalu ada
if (!isset($instr['nip']))        $instr['nip']        = '';
if (!isset($instr['pendidikan'])) $instr['pendidikan'] = '';
if (!isset($instr['jabatan']))    $instr['jabatan']    = '';
$instr_id = $instr['instr_id'];

// ── Statistik ──
function safeCount2($conn,$sql,$id){
    $s=mysqli_prepare($conn,$sql); mysqli_stmt_bind_param($s,"i",$id);
    mysqli_stmt_execute($s);
    return (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($s))['t']??0);
}
$jml_siswa   = safeCount2($conn,"SELECT COUNT(*) t FROM siswa WHERE instruktur_id=?",$instr_id);
$jml_pending = safeCount2($conn,"SELECT COUNT(*) t FROM jurnal j JOIN siswa s ON j.siswa_id=s.id WHERE s.instruktur_id=? AND j.status='pending'",$instr_id);

$msg = $error = '';

// ── POST ──
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'profil') {
        $nama    = trim($_POST['nama']??'');
        $no_hp   = trim($_POST['no_hp']??'');
        $alamat  = trim($_POST['alamat']??'');
        $tl      = trim($_POST['tempat_lahir']??'');
        $tgl     = $_POST['tanggal_lahir'] ?: null;
        $jk      = $_POST['jenis_kelamin']??'';
        $pend    = trim($_POST['pendidikan']??'');
        $jabatan = trim($_POST['jabatan']??'');

        $u1 = mysqli_prepare($conn,"UPDATE users SET nama=? WHERE id=?");
        mysqli_stmt_bind_param($u1,"si",$nama,$user_id); mysqli_stmt_execute($u1);

        if ($has_jabatan && $has_pendidikan) {
            $u2 = mysqli_prepare($conn,"UPDATE instruktur SET no_hp=?,alamat=?,tempat_lahir=?,tanggal_lahir=?,jenis_kelamin=?,pendidikan=?,jabatan=? WHERE user_id=?");
            mysqli_stmt_bind_param($u2,"sssssssi",$no_hp,$alamat,$tl,$tgl,$jk,$pend,$jabatan,$user_id);
        } elseif ($has_pendidikan) {
            $u2 = mysqli_prepare($conn,"UPDATE instruktur SET no_hp=?,alamat=?,tempat_lahir=?,tanggal_lahir=?,jenis_kelamin=?,pendidikan=? WHERE user_id=?");
            mysqli_stmt_bind_param($u2,"ssssssi",$no_hp,$alamat,$tl,$tgl,$jk,$pend,$user_id);
        } elseif ($has_jabatan) {
            $u2 = mysqli_prepare($conn,"UPDATE instruktur SET no_hp=?,alamat=?,tempat_lahir=?,tanggal_lahir=?,jenis_kelamin=?,jabatan=? WHERE user_id=?");
            mysqli_stmt_bind_param($u2,"ssssssi",$no_hp,$alamat,$tl,$tgl,$jk,$jabatan,$user_id);
        } else {
            $u2 = mysqli_prepare($conn,"UPDATE instruktur SET no_hp=?,alamat=?,tempat_lahir=?,tanggal_lahir=?,jenis_kelamin=? WHERE user_id=?");
            mysqli_stmt_bind_param($u2,"sssssi",$no_hp,$alamat,$tl,$tgl,$jk,$user_id);
        }
        mysqli_stmt_execute($u2);
        $_SESSION['nama'] = $nama;
        $msg = "Profil berhasil diperbarui!";

        // Refresh data
        $r = mysqli_prepare($conn,"SELECT i.*,i.id as instr_id,tp.nama_tempat,u.nama,u.username,u.created_at FROM instruktur i JOIN users u ON i.user_id=u.id LEFT JOIN tempat_pkl tp ON tp.id=i.tempat_pkl_id WHERE i.user_id=?");
        mysqli_stmt_bind_param($r,"i",$user_id); mysqli_stmt_execute($r);
        $instr = mysqli_fetch_assoc(mysqli_stmt_get_result($r)) ?: $instr;
        $instr_id = $instr['id'] ?? $instr_id;

    } elseif ($action === 'password') {
        $old  = $_POST['old_password']??'';
        $new  = $_POST['new_password']??'';
        $conf = $_POST['confirm_password']??'';
        $ps   = mysqli_prepare($conn,"SELECT password FROM users WHERE id=?");
        mysqli_stmt_bind_param($ps,"i",$user_id); mysqli_stmt_execute($ps);
        $pd   = mysqli_fetch_assoc(mysqli_stmt_get_result($ps));

        if (!password_verify($old,$pd['password']))    $error="Password lama tidak sesuai!";
        elseif (strlen($new)<6)                         $error="Password baru minimal 6 karakter!";
        elseif ($new!==$conf)                           $error="Konfirmasi password tidak cocok!";
        else {
            $hash=password_hash($new,PASSWORD_DEFAULT);
            $pu=mysqli_prepare($conn,"UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($pu,"si",$hash,$user_id); mysqli_stmt_execute($pu);
            $msg="Password berhasil diubah!";
        }
    }
}

require '../layout/header.php';
?>
<style>
.profil-wrapper{display:grid;grid-template-columns:280px 1fr;gap:1.5rem;align-items:start;}
@media(max-width:900px){.profil-wrapper{grid-template-columns:1fr;}}
.profil-card{background:#fff;border-radius:14px;border:1px solid var(--border);overflow:hidden;position:sticky;top:76px;}
.profil-cover{height:88px;background:linear-gradient(135deg,var(--green-800),var(--green-500));}
.profil-body{padding:0 1.25rem 1.25rem;text-align:center;}
.profil-avatar{width:78px;height:78px;border-radius:50%;border:4px solid #fff;background:linear-gradient(135deg,var(--green-600),var(--green-400));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1.9rem;margin:-38px auto .75rem;}
.profil-stat-row{display:flex;border-top:1px solid #f1f5f9;margin-top:1rem;}
.profil-stat{flex:1;padding:.75rem .5rem;text-align:center;border-right:1px solid #f1f5f9;}
.profil-stat:last-child{border-right:none;}
.profil-stat .n{font-size:1.3rem;font-weight:800;color:var(--primary);}
.profil-stat .l{font-size:.72rem;color:#94a3b8;margin-top:2px;}
.info-row{display:flex;gap:10px;padding:7px 0;border-bottom:1px solid #f8fafc;font-size:.83rem;}
.info-row:last-child{border-bottom:none;}
.section-sep{font-size:.72rem;font-weight:700;color:var(--green-700);text-transform:uppercase;letter-spacing:.06em;margin:1.2rem 0 .75rem;padding-bottom:.4rem;border-bottom:1px solid var(--green-100);}
</style>

<div class="fade">

<?php if ($msg): ?>
<div class="alert-ok" style="margin-bottom:1rem;"><span>✅</span> <?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-err" style="margin-bottom:1rem;"><span>⚠️</span> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="page-header">
  <div>
    <h1>Profil Saya</h1>
    <p>Lengkapi data diri untuk memudahkan koordinasi dengan siswa dan sekolah.</p>
  </div>
</div>

<div class="profil-wrapper">

  <!-- KARTU KIRI -->
  <div>
    <div class="profil-card">
      <div class="profil-cover"></div>
      <div class="profil-body">
        <div class="profil-avatar"><?= strtoupper(substr($instr['nama']??'I',0,1)) ?></div>
        <div style="font-weight:800;font-size:1.05rem;"><?= htmlspecialchars($instr['nama']??'-') ?></div>
        <div style="font-size:.82rem;color:#64748b;margin-top:3px;">Instruktur DU/DI</div>
        <?php if (!empty($instr['nama_tempat'])): ?>
        <div style="background:var(--green-50);color:var(--green-700);border:1px solid var(--green-200);padding:3px 12px;border-radius:20px;font-size:.73rem;font-weight:600;display:inline-block;margin-top:8px;">
          🏭 <?= htmlspecialchars($instr['nama_tempat']) ?>
        </div>
        <?php endif; ?>
        <div class="profil-stat-row">
          <div class="profil-stat"><div class="n"><?= $jml_siswa ?></div><div class="l">Siswa</div></div>
          <div class="profil-stat"><div class="n" style="color:<?= $jml_pending>0?'#d97706':'var(--primary)' ?>;"><?= $jml_pending ?></div><div class="l">Pending</div></div>
        </div>
      </div>
      <div style="padding:0 1.25rem 1.25rem;">
        <?php
        $ca = $instr['created_at']??'';
        $infos=[
          ['👤','Username',  $instr['username']??'-'],
          ['📅','Bergabung', ($ca&&$ca!='0000-00-00')?date('d M Y',strtotime($ca)):'-'],
          ['📱','No. HP',    $instr['no_hp']?:'-'],
          ['🏭','Perusahaan',$instr['nama_tempat']?:'-'],
          ['🎓','Pendidikan',$instr['pendidikan']?:'-'],
          ['💼','Jabatan',   $instr['jabatan']??'-'],
        ];
        foreach($infos as [$icon,$lbl,$val]):
        ?>
        <div class="info-row">
          <span style="width:20px;flex-shrink:0;"><?=$icon?></span>
          <span style="color:#94a3b8;width:85px;flex-shrink:0;"><?=$lbl?></span>
          <span style="font-weight:600;color:var(--text);word-break:break-all;"><?=htmlspecialchars((string)$val)?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- PANEL KANAN -->
  <div>

    <div class="form-card">
      <div class="form-card-header">
        <span style="font-size:1.2rem;">👤</span>
        <span style="font-weight:700;">Data Pribadi & Profesional</span>
      </div>
      <div class="form-card-body">
        <form method="POST">
          <input type="hidden" name="action" value="profil">

          <div class="section-sep">Informasi Dasar</div>
          <div class="form-group">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="nama" class="form-control"
                   value="<?=htmlspecialchars($instr['nama']??'')?>" required placeholder="Nama lengkap Anda">
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">NIP / ID Instruktur</label>
              <input type="text" class="form-control" value="<?=htmlspecialchars($instr['nip']??'')?>"
                     disabled style="background:#f8fafc;color:#94a3b8;">
              <div class="form-hint">Hubungi admin untuk mengubah NIP</div>
            </div>
            <div class="form-group">
              <label class="form-label">Jenis Kelamin</label>
              <select name="jenis_kelamin" class="form-control">
                <option value="">-- Pilih --</option>
                <option value="pria"   <?=($instr['jenis_kelamin']??'')==='pria'?'selected':''?>>Laki-laki</option>
                <option value="wanita" <?=($instr['jenis_kelamin']??'')==='wanita'?'selected':''?>>Perempuan</option>
              </select>
            </div>
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Tempat Lahir</label>
              <input type="text" name="tempat_lahir" class="form-control"
                     value="<?=htmlspecialchars($instr['tempat_lahir']??'')?>" placeholder="Kota kelahiran">
            </div>
            <div class="form-group">
              <label class="form-label">Tanggal Lahir</label>
              <input type="date" name="tanggal_lahir" class="form-control"
                     value="<?=$instr['tanggal_lahir']??''?>">
            </div>
          </div>

          <div class="section-sep">Kontak</div>
          <div class="form-group">
            <label class="form-label">Nomor HP / WhatsApp</label>
            <input type="text" name="no_hp" class="form-control"
                   value="<?=htmlspecialchars($instr['no_hp']??'')?>" placeholder="08xxxxxxxxxx">
          </div>
          <div class="form-group">
            <label class="form-label">Alamat</label>
            <textarea name="alamat" class="form-control" rows="3"
                      placeholder="Alamat lengkap..."><?=htmlspecialchars($instr['alamat']??'')?></textarea>
          </div>

          <div class="section-sep">Informasi Profesional</div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Pendidikan Terakhir</label>
              <select name="pendidikan" class="form-control">
                <option value="">-- Pilih --</option>
                <?php foreach(['SMA/SMK','D3','S1','S2','S3'] as $p): ?>
                <option value="<?=$p?>" <?=($instr['pendidikan']??'')===$p?'selected':''?>><?=$p?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Jabatan di Perusahaan</label>
              <input type="text" name="jabatan" class="form-control"
                     value="<?=htmlspecialchars($instr['jabatan']??'')?>" placeholder="Contoh: Supervisor IT">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Tempat PKL / Perusahaan</label>
            <input type="text" class="form-control" disabled
                   value="<?=htmlspecialchars($instr['nama_tempat']??'Belum ditetapkan')?>"
                   style="background:#f8fafc;color:#94a3b8;">
            <div class="form-hint">Ditetapkan oleh admin sekolah</div>
          </div>

          <div style="text-align:right;margin-top:.5rem;">
            <button type="submit" class="btn btn-primary-custom">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/></svg>
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="form-card">
      <div class="form-card-header">
        <span style="font-size:1.2rem;">🔒</span>
        <span style="font-weight:700;">Ganti Password</span>
      </div>
      <div class="form-card-body">
        <form method="POST">
          <input type="hidden" name="action" value="password">
          <div class="form-group">
            <label class="form-label">Password Lama</label>
            <input type="password" name="old_password" class="form-control" placeholder="Password saat ini" required>
          </div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">Password Baru</label>
              <input type="password" name="new_password" class="form-control" placeholder="Min. 6 karakter" required minlength="6">
            </div>
            <div class="form-group">
              <label class="form-label">Konfirmasi Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
            </div>
          </div>
          <div style="text-align:right;">
            <button type="submit" class="btn btn-outline">
              <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              Ubah Password
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>
</div>

<?php require '../layout/footer.php'; ?>
