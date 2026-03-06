<?php
session_start();
require '../../config/database.php';
require '../../config/onboarding_check.php';
if(empty($_SESSION['login'])||$_SESSION['role']!=='siswa'){header("Location: ../../auth/login.php");exit;}
$page_title = 'Jurnal Harian';
$uid = $_SESSION['user_id'];

$ss = mysqli_prepare($conn,"SELECT id FROM siswa WHERE user_id=?");
mysqli_stmt_bind_param($ss,"i",$uid); mysqli_stmt_execute($ss);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($ss));
$sid   = $siswa['id'] ?? 0;

$msg=''; $err='';
$today = date('Y-m-d');

if($_SERVER['REQUEST_METHOD']==='POST'){
  $tgl = trim($_POST['tanggal']??'');
  $keg = trim($_POST['kegiatan']??'');
  if(!$tgl||!$keg){ $err="Tanggal dan kegiatan wajib diisi!"; }
  else {
    $ck = mysqli_prepare($conn,"SELECT id FROM jurnal WHERE siswa_id=? AND tanggal=?");
    mysqli_stmt_bind_param($ck,"is",$sid,$tgl); mysqli_stmt_execute($ck);
    mysqli_stmt_store_result($ck);
    if(mysqli_stmt_num_rows($ck)>0){ $err="Jurnal tanggal $tgl sudah ada!"; }
    else {
      $ins = mysqli_prepare($conn,"INSERT INTO jurnal(siswa_id,tanggal,kegiatan) VALUES(?,?,?)");
      mysqli_stmt_bind_param($ins,"iss",$sid,$tgl,$keg);
      if(mysqli_stmt_execute($ins)){ $msg="Jurnal berhasil disimpan! ✓"; }
      else { $err="Gagal menyimpan jurnal."; }
    }
  }
}

$bulan = $_GET['bulan']??'';
$wsql  = $bulan ? "AND DATE_FORMAT(tanggal,'%Y-%m')=?" : "";
$qj    = mysqli_prepare($conn,"SELECT * FROM jurnal WHERE siswa_id=? $wsql ORDER BY tanggal DESC");
if($bulan){ mysqli_stmt_bind_param($qj,"is",$sid,$bulan); } else { mysqli_stmt_bind_param($qj,"i",$sid); }
mysqli_stmt_execute($qj); $jurnals = mysqli_stmt_get_result($qj);

$qs = mysqli_prepare($conn,"SELECT COUNT(*) t,SUM(status='disetujui') ok,SUM(status='pending') p,SUM(status='ditolak') x FROM jurnal WHERE siswa_id=?");
mysqli_stmt_bind_param($qs,"i",$sid); mysqli_stmt_execute($qs);
$stat = mysqli_fetch_assoc(mysqli_stmt_get_result($qs));

$qb = mysqli_prepare($conn,"SELECT DISTINCT DATE_FORMAT(tanggal,'%Y-%m') bln,DATE_FORMAT(tanggal,'%M %Y') lbl FROM jurnal WHERE siswa_id=? ORDER BY bln DESC");
mysqli_stmt_bind_param($qb,"i",$sid); mysqli_stmt_execute($qb);
$bln_res = mysqli_stmt_get_result($qb);
$bulan_list=[]; while($b=mysqli_fetch_assoc($bln_res)) $bulan_list[]=$b;

$hari_id = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
require '../layout/header.php';
?>

<?php if($msg): ?><div class="alert-ok"><i class="bi bi-check-circle me-2"></i><?= $msg ?></div><?php endif; ?>
<?php if($err): ?><div class="alert-err"><i class="bi bi-exclamation-circle me-2"></i><?= $err ?></div><?php endif; ?>

<!-- STAT -->
<div class="row g-2 mb-3">
  <?php foreach([
    ['Total',     $stat['t'],  'bi-journal-text',     'var(--teal)'],
    ['Disetujui', $stat['ok'], 'bi-check-circle-fill','#16a34a'],
    ['Pending',   $stat['p'],  'bi-hourglass-split',  '#d97706'],
    ['Ditolak',   $stat['x'],  'bi-x-circle-fill',    '#dc2626'],
  ] as [$lbl,$val,$ico,$clr]): ?>
  <div class="col-6 col-md-3">
    <div class="stat-box" style="--accent-c:<?= $clr ?>">
      <div class="stat-num"><?= $val ?></div>
      <div class="stat-lbl"><?= $lbl ?></div>
      <i class="bi <?= $ico ?> stat-icon"></i>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <!-- FORM INPUT -->
  <div class="col-12 col-lg-4">
    <div class="si-card" style="position:sticky;top:calc(var(--topbar-h) + 1rem);">
      <div class="si-card-header">
        <i class="bi bi-journal-plus" style="color:var(--teal);"></i>
        Tambah Jurnal
      </div>
      <div class="p-3">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Tanggal Kegiatan</label>
            <input type="date" name="tanggal" class="form-control" value="<?= $today ?>" max="<?= $today ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Kegiatan Hari Ini</label>
            <textarea name="kegiatan" class="form-control" rows="6"
              placeholder="Ceritakan detail kegiatan PKL kamu hari ini..." required></textarea>
            <div class="form-hint">Tulis dengan jelas agar mudah disetujui pembimbing.</div>
          </div>
          <button type="submit" class="btn-teal" style="width:100%;justify-content:center;">
            <i class="bi bi-send-fill"></i> Simpan Jurnal
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- LIST JURNAL -->
  <div class="col-12 col-lg-8">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;flex-wrap:wrap;gap:.5rem;">
      <span style="font-weight:700;font-size:.9rem;"><i class="bi bi-list-ul me-1" style="color:var(--teal);"></i>Riwayat Jurnal</span>
      <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
        <a href="../../export/jurnal.php?siswa_id=<?= $sid ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:5px;padding:.3rem .75rem;background:#14532d;color:#fff;border-radius:6px;font-size:.76rem;font-weight:600;text-decoration:none;">
          🖨️ Export PDF Jurnal
        </a>
        <form method="GET" style="display:flex;gap:.4rem;">
        <select name="bulan" onchange="this.form.submit()" class="form-select form-select-sm" style="min-width:140px;font-size:.78rem;">
          <option value="">Semua Bulan</option>
          <?php foreach($bulan_list as $b): ?>
          <option value="<?= $b['bln'] ?>" <?= $bulan===$b['bln']?'selected':'' ?>><?= $b['lbl'] ?></option>
          <?php endforeach; ?>
        </select>
        <?php if($bulan): ?><a href="?" class="btn-ghost-si" style="font-size:.76rem;padding:.3rem .65rem;">×</a><?php endif; ?>
      </form>
      </div><!-- end flex wrapper -->
    </div>

    <?php $cnt=0; while($jn=mysqli_fetch_assoc($jurnals)): $cnt++;
      [$bc,$bl] = match($jn['status']){
        'disetujui'=>['badge-green','Disetujui'],
        'ditolak'  =>['badge-red','Ditolak'],
        default    =>['badge-yellow','Pending'],
      };
      $hh = $hari_id[date('l',strtotime($jn['tanggal']))]??'';
    ?>
    <div class="si-card mb-2" style="transition:.15s;">
      <div style="padding:.75rem 1rem;border-bottom:1px solid #f8fafc;
           display:flex;justify-content:space-between;align-items:center;gap:.5rem;">
        <div style="display:flex;align-items:center;gap:9px;">
          <div style="width:34px;height:34px;border-radius:9px;background:var(--teal-pale);
               display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-file-earmark-text" style="color:var(--teal);font-size:.88rem;"></i>
          </div>
          <div>
            <div style="font-weight:700;font-size:.84rem;"><?= date('d M Y',strtotime($jn['tanggal'])) ?></div>
            <div style="font-size:.7rem;color:var(--mist);"><?= $hh ?></div>
          </div>
        </div>
        <span class="<?= $bc ?>"><?= $bl ?></span>
      </div>
      <div style="padding:.75rem 1rem;font-size:.83rem;color:var(--ink-2);line-height:1.7;white-space:pre-wrap;"><?= htmlspecialchars($jn['kegiatan']) ?></div>
      <?php if(!empty($jn['catatan_guru'])): ?>
      <div style="margin:0 1rem .75rem;background:#fffbeb;border-left:3px solid #f59e0b;
           padding:.5rem .85rem;border-radius:0 8px 8px 0;font-size:.78rem;color:#854d0e;">
        <i class="bi bi-chat-left-text me-1"></i><strong>Catatan guru:</strong> <?= htmlspecialchars($jn['catatan_guru']) ?>
      </div>
      <?php endif; ?>
    </div>
    <?php endwhile;
    if($cnt===0): ?>
    <div class="si-card" style="text-align:center;padding:3rem;color:var(--mist);">
      <i class="bi bi-journal" style="font-size:2.5rem;display:block;margin-bottom:.75rem;opacity:.3;"></i>
      Belum ada jurnal. Yuk isi sekarang!
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require '../layout/footer.php'; ?>
