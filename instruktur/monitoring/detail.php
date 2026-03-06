<?php
require '../middleware/auth_instruktur.php';
require '../../config/database.php';
$page_title = 'Detail Peserta';
$user_id    = $_SESSION['user_id'];
$siswa_id   = (int)($_GET['id'] ?? 0);

$stmtI = mysqli_prepare($conn, "SELECT id FROM instruktur WHERE user_id=?");
mysqli_stmt_bind_param($stmtI,"i",$user_id); mysqli_stmt_execute($stmtI);
$instr    = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtI));
$instr_id = $instr['id'] ?? 0;

// Validasi kepemilikan
$cek = mysqli_prepare($conn,"SELECT id FROM siswa WHERE id=? AND instruktur_id=?");
mysqli_stmt_bind_param($cek,"ii",$siswa_id,$instr_id); mysqli_stmt_execute($cek);
if (!mysqli_fetch_assoc(mysqli_stmt_get_result($cek))) { header("Location: index.php"); exit; }

$stmtS = mysqli_prepare($conn,"SELECT s.*,u.nama,tp.nama_tempat FROM siswa s JOIN users u ON s.user_id=u.id LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id=tp.id WHERE s.id=?");
mysqli_stmt_bind_param($stmtS,"i",$siswa_id); mysqli_stmt_execute($stmtS);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtS));

$filter_bulan = $_GET['bulan'] ?? '';
$wb = $filter_bulan ? "AND DATE_FORMAT(tanggal,'%Y-%m')=?" : '';
$stmtJ = mysqli_prepare($conn,"SELECT * FROM jurnal WHERE siswa_id=? $wb ORDER BY tanggal");
if($filter_bulan) mysqli_stmt_bind_param($stmtJ,"is",$siswa_id,$filter_bulan);
else mysqli_stmt_bind_param($stmtJ,"i",$siswa_id);
mysqli_stmt_execute($stmtJ);
$jurnals = []; $jr = mysqli_stmt_get_result($stmtJ);
while($j=mysqli_fetch_assoc($jr)) $jurnals[]=$j;

$stmtSt = mysqli_prepare($conn,"SELECT COUNT(*) total,SUM(status_instruktur='disetujui') ok,SUM(status_instruktur='pending') pend,SUM(status_instruktur='ditolak') tolak,MIN(tanggal) mulai,MAX(tanggal) akhir FROM jurnal WHERE siswa_id=?");
mysqli_stmt_bind_param($stmtSt,"i",$siswa_id); mysqli_stmt_execute($stmtSt);
$stat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtSt));
$pct  = $stat['total']>0 ? round($stat['ok']/$stat['total']*100) : 0;

$stmtB = mysqli_prepare($conn,"SELECT DISTINCT DATE_FORMAT(tanggal,'%Y-%m') bln,DATE_FORMAT(tanggal,'%M %Y') label FROM jurnal WHERE siswa_id=? ORDER BY bln");
mysqli_stmt_bind_param($stmtB,"i",$siswa_id); mysqli_stmt_execute($stmtB);
$bulans=[]; $br=mysqli_stmt_get_result($stmtB);
while($b=mysqli_fetch_assoc($br)) $bulans[]=$b;

require '../layout/header.php';
?>
<style>
.back-link{display:inline-flex;align-items:center;gap:6px;color:var(--text-muted);font-size:0.83rem;margin-bottom:1.1rem;}
.back-link:hover{color:var(--accent);}
.profile-bar{background:var(--bg-card);border:1px solid var(--border);border-radius:14px;padding:1.25rem;margin-bottom:1.25rem;display:flex;gap:1.25rem;align-items:center;flex-wrap:wrap;}
.big-av{width:64px;height:64px;border-radius:14px;background:var(--accent);display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:1.7rem;flex-shrink:0;}
.chip{background:var(--bg-card2);border:1px solid var(--border);padding:3px 10px;border-radius:12px;font-size:0.74rem;color:var(--text-muted);}
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(110px,1fr));gap:1rem;margin-bottom:1.25rem;}
.sbox{background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:1rem;text-align:center;}
.sbox .n{font-size:1.6rem;font-weight:700;line-height:1;}
.sbox .l{font-size:0.72rem;color:var(--text-muted);margin-top:3px;}
.tl{position:relative;padding-left:26px;}
.tl::before{content:'';position:absolute;left:7px;top:0;bottom:0;width:2px;background:var(--border);}
.tl-item{position:relative;margin-bottom:1rem;}
.tl-dot{position:absolute;left:-21px;top:13px;width:12px;height:12px;border-radius:50%;border:2px solid var(--bg);}
.dot-ok{background:#22c55e;}
.dot-pend{background:#f59e0b;}
.dot-no{background:#ef4444;}
.tl-card{background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:1rem 1.1rem;transition:0.2s;}
.tl-card:hover{border-color:var(--accent);}
.tl-head{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.6rem;flex-wrap:wrap;gap:8px;}
.tl-body{font-size:0.88rem;color:#cbd5e1;line-height:1.65;white-space:pre-wrap;}
.tl-note{background:rgba(249,115,22,0.08);border-left:3px solid var(--accent);padding:7px 10px;border-radius:0 8px 8px 0;margin-top:8px;font-size:0.8rem;color:#fdba74;}
</style>

<div class="fade">
<a href="index.php" class="back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
    Peserta PKL
</a>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.1rem;">
    <h1 style="font-size:1.3rem;font-weight:700;"><?= htmlspecialchars($siswa['nama']) ?></h1>
    <a href="rekap_excel.php?siswa_id=<?= $siswa_id ?>" class="btn btn-green">⬇ Export Excel</a>
</div>

<div class="profile-bar">
    <div class="big-av"><?= strtoupper(substr($siswa['nama'],0,1)) ?></div>
    <div style="flex:1;">
        <div style="display:flex;gap:7px;flex-wrap:wrap;margin-bottom:6px;">
            <span class="chip">NIS: <?= $siswa['nis'] ?></span>
            <span class="chip"><?= $siswa['kelas'] ?></span>
            <span class="chip"><?= $siswa['jurusan']??'-' ?></span>
            <span class="chip">🏢 <?= $siswa['nama_tempat']?htmlspecialchars($siswa['nama_tempat']):'-' ?></span>
        </div>
        <?php if($stat['mulai']): ?>
        <div style="font-size:0.76rem;color:var(--text-muted);">PKL: <?= date('d M Y',strtotime($stat['mulai'])) ?> — <?= date('d M Y',strtotime($stat['akhir'])) ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="stat-row">
    <div class="sbox"><div class="n" style="color:#60a5fa;"><?= $stat['total'] ?></div><div class="l">Total</div></div>
    <div class="sbox"><div class="n" style="color:#4ade80;"><?= $stat['ok'] ?></div><div class="l">Disetujui</div></div>
    <div class="sbox"><div class="n" style="color:#fbbf24;"><?= $stat['pend'] ?></div><div class="l">Pending</div></div>
    <div class="sbox"><div class="n" style="color:#f87171;"><?= $stat['tolak'] ?></div><div class="l">Ditolak</div></div>
    <div class="sbox"><div class="n" style="color:<?= $pct>=80?'#4ade80':($pct>=50?'#fbbf24':'#f87171') ?>;"><?= $pct ?>%</div><div class="l">Approval</div></div>
</div>

<div class="card" style="margin-bottom:1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
        <span style="font-size:0.88rem;font-weight:600;">Progress PKL</span>
        <form method="GET" style="display:flex;gap:8px;">
            <input type="hidden" name="id" value="<?= $siswa_id ?>">
            <select name="bulan" class="form-control" style="width:auto;font-size:0.8rem;padding:4px 8px;" onchange="this.form.submit()">
                <option value="">Semua Bulan</option>
                <?php foreach($bulans as $b): ?>
                <option value="<?= $b['bln'] ?>" <?= $filter_bulan==$b['bln']?'selected':'' ?>><?= $b['label'] ?></option>
                <?php endforeach; ?>
            </select>
            <?php if($filter_bulan): ?><a href="?id=<?= $siswa_id ?>" class="btn btn-ghost" style="padding:4px 10px;font-size:0.8rem;">Reset</a><?php endif; ?>
        </form>
    </div>
    <div style="background:var(--bg-card2);border-radius:6px;height:10px;overflow:hidden;">
        <?php $bc=$pct>=80?'#22c55e':($pct>=50?'#f59e0b':'#ef4444'); ?>
        <div style="height:100%;width:<?= $pct ?>%;background:<?= $bc ?>;border-radius:6px;transition:width 0.8s;"></div>
    </div>
</div>

<!-- TIMELINE -->
<div class="card">
    <h3 style="font-size:0.9rem;font-weight:700;margin-bottom:1.1rem;">📋 Riwayat Jurnal</h3>
    <?php if(empty($jurnals)): ?>
    <p style="text-align:center;color:var(--text-muted);padding:2rem 0;">Belum ada jurnal.</p>
    <?php else: ?>
    <div class="tl">
    <?php foreach($jurnals as $j):
        $dc = $j['status_instruktur']==='disetujui'?'dot-ok':($j['status_instruktur']==='pending'?'dot-pend':'dot-no');
        $days=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $hari=$days[date('w',strtotime($j['tanggal']))];
        $bs = match($j['status_instruktur']){
            'disetujui' => ['badge-ok','✅ Disetujui'],
            'pending'   => ['badge-warn','⏳ Pending'],
            'ditolak'   => ['badge-danger','❌ Ditolak'],
            default     => ['badge-neutral','?']
        };
    ?>
    <div class="tl-item">
        <div class="tl-dot <?= $dc ?>"></div>
        <div class="tl-card">
            <div class="tl-head">
                <div>
                    <span style="font-weight:700;font-size:0.88rem;"><?= date('d F Y',strtotime($j['tanggal'])) ?></span>
                    <span style="color:var(--text-muted);font-size:0.75rem;margin-left:6px;"><?= $hari ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:7px;">
                    <span class="badge <?= $bs[0] ?>"><?= $bs[1] ?></span>
                    <?php if($j['status_instruktur']==='pending'): ?>
                    <a href="../jurnal/aksi.php?id=<?= $j['id'] ?>&aksi=setuju&ref=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-xs btn-green" style="padding:3px 9px;font-size:0.75rem;" onclick="return confirm('Setujui?')">✔</a>
                    <a href="../jurnal/aksi.php?id=<?= $j['id'] ?>&aksi=tolak&ref=<?= urlencode($_SERVER['REQUEST_URI']) ?>"  class="btn btn-xs" style="padding:3px 9px;font-size:0.75rem;background:#dc2626;color:white;" onclick="return confirm('Tolak?')">✖</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="tl-body"><?= htmlspecialchars($j['kegiatan']) ?></div>
            <?php if($j['catatan_instruktur']): ?>
            <div class="tl-note">🏭 <strong>Catatan Anda:</strong> <?= htmlspecialchars($j['catatan_instruktur']) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
</div>
<?php require '../layout/footer.php'; ?>
