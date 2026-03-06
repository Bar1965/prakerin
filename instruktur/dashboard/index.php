<?php
require '../middleware/auth_instruktur.php';
require '../../config/database.php';
$page_title='Dashboard';
$user_id=$_SESSION['user_id'];
$pw_changed = isset($_GET['pw_changed']);

$si=mysqli_prepare($conn,"SELECT i.id FROM instruktur i WHERE i.user_id=?");
mysqli_stmt_bind_param($si,"i",$user_id); mysqli_stmt_execute($si);
$instr=mysqli_fetch_assoc(mysqli_stmt_get_result($si));
$instr_id=$instr['id']??0;

function qN($conn,$sql,$id){
  $s=mysqli_prepare($conn,$sql); mysqli_stmt_bind_param($s,"i",$id);
  mysqli_stmt_execute($s); return mysqli_fetch_assoc(mysqli_stmt_get_result($s))['n'];
}
$tot_siswa  =qN($conn,"SELECT COUNT(*) n FROM siswa WHERE instruktur_id=?",$instr_id);
$tot_jurnal =qN($conn,"SELECT COUNT(*) n FROM jurnal j JOIN siswa s ON j.siswa_id=s.id WHERE s.instruktur_id=?",$instr_id);
$tot_pending=qN($conn,"SELECT COUNT(*) n FROM jurnal j JOIN siswa s ON j.siswa_id=s.id WHERE s.instruktur_id=? AND j.status='pending'",$instr_id);

$q_pend=mysqli_prepare($conn,"
  SELECT j.id,j.tanggal,u.nama FROM jurnal j
  JOIN siswa s ON j.siswa_id=s.id JOIN users u ON s.user_id=u.id
  WHERE s.instruktur_id=? AND j.status='pending'
  ORDER BY j.created_at DESC LIMIT 5
");
mysqli_stmt_bind_param($q_pend,"i",$instr_id); mysqli_stmt_execute($q_pend);
$pendings=mysqli_stmt_get_result($q_pend);

date_default_timezone_set('Asia/Jakarta');
$h=(int)date('H');
$sap=$h<11?'Pagi':($h<15?'Siang':($h<18?'Sore':'Malam'));
require '../layout/header.php';
?>
<?php if ($pw_changed): ?>
<div style="background:#f0fdf4;border:1.5px solid #86efac;color:#166534;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.6rem;font-size:.9rem;font-weight:500;">
  <i class="bi bi-check-circle-fill" style="font-size:1.1rem"></i>
  Password berhasil diperbarui. Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!
</div>
<?php endif; ?>
<style>
.hero{
  background:linear-gradient(135deg,var(--green-800),var(--green-600));
  border-radius:14px;padding:1.6rem 2rem;color:#fff;position:relative;overflow:hidden;margin-bottom:1.25rem;
}
.hero h1{font-size:1.35rem;font-weight:800;}
.hero p{opacity:.7;font-size:.875rem;margin-top:4px;}
.hero-ring{
  position:absolute;right:-50px;top:-50px;width:180px;height:180px;border-radius:50%;
  border:30px solid rgba(255,255,255,.07);
}
.hero-ring2{
  position:absolute;right:30px;bottom:-60px;width:140px;height:140px;border-radius:50%;
  border:25px solid rgba(255,255,255,.05);
}
.pending-item{
  display:flex;align-items:center;justify-content:space-between;
  padding:.8rem 1.1rem;border-bottom:1px solid var(--border);gap:12px;
}
.pending-item:last-child{border-bottom:none;}
.btn-yes{background:#dcfce7;color:#166534;border:none;padding:4px 10px;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit;}
.btn-yes:hover{background:#166534;color:#fff;}
.btn-no{background:#fee2e2;color:#b91c1c;border:none;padding:4px 10px;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;font-family:inherit;}
.btn-no:hover{background:#b91c1c;color:#fff;}
.qc-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:.85rem;margin-top:1.25rem;}
.qc-card{background:#fff;border:1.5px solid var(--border);border-radius:12px;padding:1.1rem;text-align:center;transition:.2s;font-size:.85rem;font-weight:600;}
.qc-card:hover{border-color:var(--primary);transform:translateY(-3px);box-shadow:0 8px 16px rgba(21,128,61,.12);}
.qc-card .qi{font-size:1.6rem;margin-bottom:6px;}
</style>

<div class="hero">
  <div class="hero-ring"></div><div class="hero-ring2"></div>
  <h1>Selamat <?= $sap ?>, <?= htmlspecialchars(explode(' ',$_SESSION['nama'])[0]) ?>! 👋</h1>
  <p>Portal Instruktur DU/DI · <?= date('d F Y') ?></p>
</div>

<div class="stat-row">
  <div class="stat-b"><div class="n"><?= $tot_siswa ?></div><div class="l">Siswa Bimbingan</div></div>
  <div class="stat-b"><div class="n"><?= $tot_jurnal ?></div><div class="l">Total Jurnal</div></div>
  <div class="stat-b"><div class="n" style="color:<?= $tot_pending?'#d97706':'inherit' ?>"><?= $tot_pending ?></div><div class="l">Perlu Direview</div></div>
</div>

<?php if($tot_pending>0): ?>
<div class="card" style="margin-bottom:1.25rem;">
  <div style="padding:.9rem 1.1rem;border-bottom:1px solid var(--border);font-weight:700;font-size:.9rem;">⏳ Jurnal Menunggu Review</div>
  <?php while($p=mysqli_fetch_assoc($pendings)): ?>
  <div class="pending-item">
    <div>
      <div style="font-weight:600;font-size:.875rem;"><?= htmlspecialchars($p['nama']) ?></div>
      <div style="font-size:.75rem;color:var(--muted);"><?= date('d M Y',strtotime($p['tanggal'])) ?></div>
    </div>
    <div style="display:flex;gap:5px;">
      <a href="../jurnal/aksi.php?id=<?= $p['id'] ?>&aksi=setuju&ref=../dashboard/index.php" class="btn-yes" onclick="return confirm('Setujui?')">✔ OK</a>
      <a href="../jurnal/aksi.php?id=<?= $p['id'] ?>&aksi=tolak&ref=../dashboard/index.php" class="btn-no" onclick="return confirm('Tolak?')">✖</a>
    </div>
  </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>

<div class="qc-grid">
  <a href="../monitoring/index.php" class="qc-card"><div class="qi">👥</div>Daftar Siswa</a>
  <a href="../jurnal/index.php" class="qc-card"><div class="qi">📋</div>Review Jurnal</a>
  <a href="../monitoring/rekap_excel.php" class="qc-card"><div class="qi">📊</div>Rekap Excel</a>
  <a href="../profil/index.php" class="qc-card"><div class="qi">👤</div>Profil Saya</a>
</div>

<?php require '../layout/footer.php'; ?>
