<?php
/**
 * Export PDF Rekap Jurnal Kegiatan Harian Siswa
 */
session_start();
require '../config/database.php';

if (empty($_SESSION['login'])) { header("Location: ../auth/login.php"); exit; }

$role    = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];
$siswa_id = (int)($_GET['siswa_id'] ?? 0);
$status_filter = $_GET['status'] ?? ''; // kosong = semua, disetujui, pending

// ── Validasi akses ──
$siswa = null;
if ($role === 'siswa') {
    $s = mysqli_prepare($conn, "SELECT s.*,u.nama,tp.nama_tempat,ug.nama AS nama_guru FROM siswa s JOIN users u ON u.id=s.user_id LEFT JOIN tempat_pkl tp ON tp.id=s.tempat_pkl_id LEFT JOIN guru g ON g.id=s.guru_id LEFT JOIN users ug ON ug.id=g.user_id WHERE s.user_id=? LIMIT 1");
    mysqli_stmt_bind_param($s,'i',$user_id); mysqli_stmt_execute($s);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    $siswa_id = $siswa['id'] ?? 0;
} elseif ($role === 'guru') {
    $s = mysqli_prepare($conn, "SELECT s.*,u.nama,tp.nama_tempat,ug.nama AS nama_guru FROM siswa s JOIN users u ON u.id=s.user_id LEFT JOIN tempat_pkl tp ON tp.id=s.tempat_pkl_id LEFT JOIN guru g ON g.id=s.guru_id LEFT JOIN users ug ON ug.id=g.user_id WHERE s.id=? AND g.user_id=? LIMIT 1");
    mysqli_stmt_bind_param($s,'ii',$siswa_id,$user_id); mysqli_stmt_execute($s);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    if (!$siswa) { echo "<p>Akses ditolak.</p>"; exit; }
} elseif ($role === 'instruktur') {
    $instr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT tempat_pkl_id FROM instruktur WHERE user_id=$user_id LIMIT 1"));
    $s = mysqli_prepare($conn, "SELECT s.*,u.nama,tp.nama_tempat,ug.nama AS nama_guru FROM siswa s JOIN users u ON u.id=s.user_id LEFT JOIN tempat_pkl tp ON tp.id=s.tempat_pkl_id LEFT JOIN guru g ON g.id=s.guru_id LEFT JOIN users ug ON ug.id=g.user_id WHERE s.id=? AND s.tempat_pkl_id=? LIMIT 1");
    mysqli_stmt_bind_param($s,'ii',$siswa_id,(int)($instr['tempat_pkl_id']??0)); mysqli_stmt_execute($s);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    if (!$siswa) { echo "<p>Akses ditolak.</p>"; exit; }
} elseif ($role === 'admin') {
    $s = mysqli_prepare($conn, "SELECT s.*,u.nama,tp.nama_tempat,ug.nama AS nama_guru FROM siswa s JOIN users u ON u.id=s.user_id LEFT JOIN tempat_pkl tp ON tp.id=s.tempat_pkl_id LEFT JOIN guru g ON g.id=s.guru_id LEFT JOIN users ug ON ug.id=g.user_id WHERE s.id=? LIMIT 1");
    mysqli_stmt_bind_param($s,'i',$siswa_id); mysqli_stmt_execute($s);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    if (!$siswa) { echo "<p>Data tidak ditemukan.</p>"; exit; }
} else { echo "<p>Akses ditolak.</p>"; exit; }

// ── Ambil jurnal ──
$where_status = $status_filter ? "AND j.status=?" : "";
$sq = mysqli_prepare($conn, "SELECT j.*, u.nama FROM jurnal j JOIN siswa s ON s.id=j.siswa_id JOIN users u ON u.id=s.user_id WHERE j.siswa_id=? $where_status ORDER BY j.tanggal ASC");
if ($status_filter) { mysqli_stmt_bind_param($sq,'is',$siswa_id,$status_filter); }
else                { mysqli_stmt_bind_param($sq,'i',$siswa_id); }
mysqli_stmt_execute($sq);
$jurnal_rows = [];
while ($r = mysqli_fetch_assoc(mysqli_stmt_get_result($sq))) $jurnal_rows[] = $r;

$total      = count($jurnal_rows);
$disetujui  = count(array_filter($jurnal_rows, fn($r)=>$r['status']==='disetujui'));
$pending    = count(array_filter($jurnal_rows, fn($r)=>$r['status']==='pending'));
$ditolak    = count(array_filter($jurnal_rows, fn($r)=>$r['status']==='ditolak'));

$jadwal  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM jadwal_prakerin ORDER BY tanggal_mulai DESC LIMIT 1"));
$hari_id = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Jurnal — <?= htmlspecialchars($siswa['nama']) ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Arial',sans-serif; font-size:11pt; color:#000; background:#f0f0f0; }
  .page { width:210mm; min-height:297mm; margin:20px auto; padding:20mm 20mm 20mm 25mm; background:#fff; box-shadow:0 2px 16px rgba(0,0,0,.15); }

  .kop { display:flex; align-items:center; gap:16px; padding-bottom:10px; border-bottom:3px solid #14532d; margin-bottom:6px; }
  .kop img { width:64px; height:64px; object-fit:contain; }
  .kop-text h1 { font-size:14pt; font-weight:bold; color:#14532d; margin-bottom:2px; }
  .kop-text p  { font-size:9pt; color:#333; line-height:1.5; }

  .doc-title { text-align:center; margin-bottom:14px; }
  .doc-title h2 { font-size:13pt; font-weight:bold; text-transform:uppercase; letter-spacing:.08em; }
  .doc-title p  { font-size:10pt; color:#444; margin-top:3px; }

  .info-box { border:1px solid #ccc; border-radius:4px; padding:10px 14px; margin-bottom:14px; background:#fafafa; }
  .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:4px 20px; }
  .info-row { display:flex; gap:4px; font-size:10pt; }
  .info-label { color:#555; min-width:130px; }
  .info-val   { font-weight:bold; }

  .stat-row { display:flex; gap:10px; margin-bottom:16px; }
  .stat-box { flex:1; border:1px solid #ddd; border-radius:6px; padding:10px; text-align:center; }
  .stat-box .num { font-size:20pt; font-weight:bold; line-height:1; }
  .stat-box .lbl { font-size:8.5pt; color:#555; margin-top:3px; }

  /* Jurnal entries */
  .jurnal-entry { border:1px solid #dde; border-radius:6px; margin-bottom:10px; overflow:hidden; page-break-inside:avoid; }
  .jurnal-header { background:#14532d; color:#fff; padding:6px 12px; display:flex; justify-content:space-between; align-items:center; }
  .jurnal-header .tgl { font-weight:bold; font-size:10pt; }
  .jurnal-header .badge { font-size:8.5pt; padding:2px 10px; border-radius:12px; font-weight:bold; }
  .badge-ok    { background:#16a34a; }
  .badge-pend  { background:#d97706; }
  .badge-tolak { background:#dc2626; }
  .jurnal-body { padding:8px 12px; font-size:10pt; line-height:1.65; }
  .jurnal-catatan { background:#fffbea; border-left:3px solid #d97706; padding:5px 10px; margin-top:6px; font-size:9.5pt; color:#555; border-radius:0 4px 4px 0; }
  .jurnal-catatan-ok { background:#f0fdf4; border-left:3px solid #16a34a; }

  .ttd-section { display:flex; justify-content:space-between; margin-top:24px; }
  .ttd-box { text-align:center; width:180px; }
  .ttd-box .ttd-line { border-bottom:1px solid #000; margin:50px 20px 6px; }
  .ttd-box p { font-size:9.5pt; }
  .ttd-box .ttd-name { font-weight:bold; font-size:10pt; margin-top:3px; }

  .doc-footer { margin-top:16px; font-size:8pt; color:#888; text-align:center; border-top:1px solid #eee; padding-top:8px; }

  .print-toolbar { position:fixed; bottom:24px; right:24px; display:flex; gap:10px; z-index:999; }
  .btn-print { background:#14532d; color:#fff; border:none; padding:12px 24px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 16px rgba(0,0,0,.2); transition:.2s; }
  .btn-print:hover { background:#15803d; transform:translateY(-2px); }
  .btn-back { background:#fff; color:#333; border:1px solid #ccc; padding:12px 20px; border-radius:10px; font-size:13px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 2px 8px rgba(0,0,0,.1); text-decoration:none; transition:.2s; }
  .btn-back:hover { border-color:#14532d; color:#14532d; }

  @media print {
    body { background:#fff; }
    .page { margin:0; padding:15mm 15mm 15mm 20mm; box-shadow:none; width:100%; }
    .print-toolbar { display:none !important; }
    .jurnal-header { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .badge { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  }
  @page { size:A4; margin:0; }
</style>
</head>
<body>

<div class="print-toolbar">
  <a href="javascript:history.back()" class="btn-back">← Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨️ Cetak / Save PDF</button>
</div>

<div class="page">
  <!-- KOP -->
  <div class="kop">
    <img src="../assets/images/logo-sekolah.png" alt="Logo" onerror="this.style.display='none'">
    <div class="kop-text">
      <h1>SMK NEGERI 3 PADANG</h1>
      <p>Jl. Bungo Pasang, Tabing, Padang, Sumatera Barat<br>Telp. (0751) 123456 &nbsp;·&nbsp; smkn3padang@sch.id</p>
    </div>
  </div>
  <div style="text-align:right;font-size:8pt;color:#888;margin:4px 0 10px;">Sistem Informasi Praktik Kerja Industri (SiPrakerin)</div>

  <!-- JUDUL -->
  <div class="doc-title">
    <h2>Rekap Jurnal Kegiatan Harian PKL</h2>
    <p>Seluruh Periode PKL <?= $jadwal ? '· Tahun Ajaran '.htmlspecialchars($jadwal['tahun_ajaran']) : '' ?></p>
  </div>

  <!-- INFO SISWA -->
  <div class="info-box">
    <div class="info-grid">
      <div class="info-row"><span class="info-label">Nama Siswa</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['nama']) ?></span></div>
      <div class="info-row"><span class="info-label">Kelas</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['kelas']) ?></span></div>
      <div class="info-row"><span class="info-label">NIS</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['nis']) ?></span></div>
      <div class="info-row"><span class="info-label">Jurusan</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['jurusan']??'-') ?></span></div>
      <div class="info-row"><span class="info-label">Tempat PKL</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['nama_tempat']??'-') ?></span></div>
      <div class="info-row"><span class="info-label">Guru Pembimbing</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['nama_guru']??'-') ?></span></div>
    </div>
  </div>

  <!-- STAT -->
  <div class="stat-row">
    <div class="stat-box"><div class="num" style="color:#14532d;"><?= $total ?></div><div class="lbl">Total Jurnal</div></div>
    <div class="stat-box"><div class="num" style="color:#16a34a;"><?= $disetujui ?></div><div class="lbl">Disetujui</div></div>
    <div class="stat-box"><div class="num" style="color:#d97706;"><?= $pending ?></div><div class="lbl">Pending</div></div>
    <div class="stat-box"><div class="num" style="color:#dc2626;"><?= $ditolak ?></div><div class="lbl">Ditolak</div></div>
  </div>

  <!-- JURNAL ENTRIES -->
  <?php if (empty($jurnal_rows)): ?>
  <p style="text-align:center;color:#888;padding:20px;">Belum ada data jurnal.</p>
  <?php else: ?>
  <?php foreach ($jurnal_rows as $i => $j):
    $tgl_fmt = date('d F Y', strtotime($j['tanggal']));
    $hari    = $hari_id[date('l', strtotime($j['tanggal']))] ?? '';
    $badge_class = ['disetujui'=>'badge-ok','pending'=>'badge-pend','ditolak'=>'badge-tolak'][$j['status']] ?? '';
    $badge_label = ['disetujui'=>'✓ Disetujui','pending'=>'⏳ Pending','ditolak'=>'✗ Ditolak'][$j['status']] ?? $j['status'];
  ?>
  <div class="jurnal-entry">
    <div class="jurnal-header">
      <span class="tgl"><?= $i+1 ?>. <?= $hari ?>, <?= $tgl_fmt ?></span>
      <span class="badge <?= $badge_class ?>"><?= $badge_label ?></span>
    </div>
    <div class="jurnal-body">
      <?= nl2br(htmlspecialchars($j['kegiatan'])) ?>
      <?php if (!empty($j['catatan_guru'])): ?>
      <div class="jurnal-catatan <?= $j['status']==='disetujui'?'jurnal-catatan-ok':'' ?>">
        <strong>Catatan Guru:</strong> <?= htmlspecialchars($j['catatan_guru']) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <!-- TTD -->
  <div class="ttd-section">
    <div class="ttd-box">
      <p>Padang, <?= date('d F Y') ?></p>
      <p>Siswa Bersangkutan</p>
      <div class="ttd-line"></div>
      <p class="ttd-name"><?= htmlspecialchars($siswa['nama']) ?></p>
      <p style="font-size:9pt;">NIS. <?= htmlspecialchars($siswa['nis']) ?></p>
    </div>
    <div class="ttd-box">
      <p>Memeriksa,</p>
      <p>Guru Pembimbing PKL</p>
      <div class="ttd-line"></div>
      <p class="ttd-name"><?= htmlspecialchars($siswa['nama_guru']??'________________________') ?></p>
      <p style="font-size:9pt;">NIP. ___________________</p>
    </div>
    <div class="ttd-box">
      <p>Menyetujui,</p>
      <p>Kepala Sekolah</p>
      <div class="ttd-line"></div>
      <p class="ttd-name">________________________</p>
      <p style="font-size:9pt;">NIP. ___________________</p>
    </div>
  </div>

  <div class="doc-footer">Dicetak melalui SiPrakerin · SMK Negeri 3 Padang · <?= date('d F Y, H:i') ?> WIB</div>
</div>
</body>
</html>
