<?php
/**
 * Export Rekap Semua Siswa — untuk Guru/Admin
 * Menampilkan ringkasan absensi semua siswa bimbingan dalam satu PDF
 */
session_start();
require '../config/database.php';

if (empty($_SESSION['login'])) { header("Location: ../auth/login.php"); exit; }
$role    = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];

// ── Ambil daftar siswa sesuai role ──
$siswa_list = [];

if ($role === 'guru') {
    $g = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id FROM guru WHERE user_id=$user_id LIMIT 1"));
    $guru_id = (int)($g['id'] ?? 0);
    $nama_pembuat = $_SESSION['nama'];
    $label_group  = 'Guru Pembimbing: ' . htmlspecialchars($nama_pembuat);

    $res = mysqli_query($conn, "
        SELECT s.id, s.nis, s.kelas, s.jurusan, u.nama, tp.nama_tempat,
               COUNT(a.id) AS total_abs,
               SUM(a.status='hadir') AS hadir,
               SUM(a.status='sakit') AS sakit,
               SUM(a.status='izin')  AS izin,
               SUM(a.status='alpha') AS alpha,
               COUNT(j.id) AS total_jurnal,
               SUM(j.status='disetujui') AS jurnal_ok
        FROM siswa s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN tempat_pkl tp ON tp.id = s.tempat_pkl_id
        LEFT JOIN absensi a ON a.siswa_id = s.id
        LEFT JOIN jurnal j ON j.siswa_id = s.id
        WHERE s.guru_id = $guru_id
        GROUP BY s.id
        ORDER BY s.kelas, u.nama
    ");

} elseif ($role === 'instruktur') {
    $instr = mysqli_fetch_assoc(mysqli_query($conn,"SELECT i.id, i.tempat_pkl_id, tp.nama_tempat FROM instruktur i LEFT JOIN tempat_pkl tp ON tp.id=i.tempat_pkl_id WHERE i.user_id=$user_id LIMIT 1"));
    $tempat_id    = (int)($instr['tempat_pkl_id'] ?? 0);
    $nama_pembuat = $_SESSION['nama'];
    $label_group  = 'Instruktur: ' . htmlspecialchars($nama_pembuat) . ' · ' . htmlspecialchars($instr['nama_tempat']??'');

    $res = mysqli_query($conn, "
        SELECT s.id, s.nis, s.kelas, s.jurusan, u.nama, tp.nama_tempat,
               COUNT(a.id) AS total_abs,
               SUM(a.status='hadir') AS hadir,
               SUM(a.status='sakit') AS sakit,
               SUM(a.status='izin')  AS izin,
               SUM(a.status='alpha') AS alpha,
               COUNT(j.id) AS total_jurnal,
               SUM(j.status='disetujui') AS jurnal_ok
        FROM siswa s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN tempat_pkl tp ON tp.id = s.tempat_pkl_id
        LEFT JOIN absensi a ON a.siswa_id = s.id
        LEFT JOIN jurnal j ON j.siswa_id = s.id
        WHERE s.tempat_pkl_id = $tempat_id
        GROUP BY s.id
        ORDER BY s.kelas, u.nama
    ");

} elseif ($role === 'admin') {
    $nama_pembuat = $_SESSION['nama'];
    $label_group  = 'Administrator';
    $filter_kelas = $_GET['kelas'] ?? '';
    $filter_tempat = (int)($_GET['tempat_id'] ?? 0);
    $where = [];
    if ($filter_kelas)  $where[] = "s.kelas = '".mysqli_real_escape_string($conn,$filter_kelas)."'";
    if ($filter_tempat) $where[] = "s.tempat_pkl_id = $filter_tempat";
    $where_sql = $where ? "WHERE ".implode(' AND ',$where) : "";

    $res = mysqli_query($conn, "
        SELECT s.id, s.nis, s.kelas, s.jurusan, u.nama, tp.nama_tempat,
               ug.nama AS nama_guru,
               COUNT(a.id) AS total_abs,
               SUM(a.status='hadir') AS hadir,
               SUM(a.status='sakit') AS sakit,
               SUM(a.status='izin')  AS izin,
               SUM(a.status='alpha') AS alpha,
               COUNT(j.id) AS total_jurnal,
               SUM(j.status='disetujui') AS jurnal_ok
        FROM siswa s
        JOIN users u ON u.id = s.user_id
        LEFT JOIN tempat_pkl tp ON tp.id = s.tempat_pkl_id
        LEFT JOIN guru g ON g.id = s.guru_id
        LEFT JOIN users ug ON ug.id = g.user_id
        LEFT JOIN absensi a ON a.siswa_id = s.id
        LEFT JOIN jurnal j ON j.siswa_id = s.id
        $where_sql
        GROUP BY s.id
        ORDER BY s.kelas, u.nama
    ");
} else {
    echo "<p>Akses ditolak.</p>"; exit;
}

while ($r = mysqli_fetch_assoc($res)) $siswa_list[] = $r;
$jadwal = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM jadwal_prakerin ORDER BY tanggal_mulai DESC LIMIT 1"));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rekap Keseluruhan Siswa PKL</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Arial',sans-serif; font-size:10.5pt; color:#000; background:#f0f0f0; }
  .page { width:297mm; min-height:210mm; margin:20px auto; padding:15mm 15mm 15mm 20mm; background:#fff; box-shadow:0 2px 16px rgba(0,0,0,.15); }

  .kop { display:flex; align-items:center; gap:14px; padding-bottom:8px; border-bottom:3px solid #14532d; margin-bottom:5px; }
  .kop img { width:56px; height:56px; object-fit:contain; }
  .kop-text h1 { font-size:13pt; font-weight:bold; color:#14532d; }
  .kop-text p  { font-size:8.5pt; color:#333; line-height:1.5; }

  .doc-title { text-align:center; margin:10px 0 12px; }
  .doc-title h2 { font-size:12pt; font-weight:bold; text-transform:uppercase; }
  .doc-title p  { font-size:9.5pt; color:#444; margin-top:2px; }

  table { width:100%; border-collapse:collapse; font-size:9.5pt; }
  thead th { background:#14532d; color:#fff; padding:6px 7px; text-align:center; font-size:9pt; }
  thead th.left { text-align:left; }
  tbody td { padding:5px 7px; border-bottom:1px solid #e8e8e8; vertical-align:middle; }
  tbody td.center { text-align:center; }
  tbody tr:nth-child(even) td { background:#f7f9fc; }
  tfoot td { background:#e8edf5; font-weight:bold; padding:6px 7px; }

  .pct-bar { display:inline-block; height:6px; border-radius:3px; vertical-align:middle; margin-right:4px; }
  .pct-label { font-size:8.5pt; font-weight:bold; }
  .good   { color:#15803d; }
  .medium { color:#d97706; }
  .bad    { color:#dc2626; }

  .info-meta { display:flex; justify-content:space-between; font-size:9pt; color:#555; margin-bottom:10px; }

  .doc-footer { margin-top:12px; font-size:7.5pt; color:#888; text-align:center; border-top:1px solid #eee; padding-top:6px; }

  .print-toolbar { position:fixed; bottom:24px; right:24px; display:flex; gap:10px; z-index:999; }
  .btn-print { background:#14532d; color:#fff; border:none; padding:12px 24px; border-radius:10px; font-size:13px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:8px; box-shadow:0 4px 16px rgba(0,0,0,.2); transition:.2s; }
  .btn-print:hover { background:#15803d; }
  .btn-back { background:#fff; color:#333; border:1px solid #ccc; padding:12px 20px; border-radius:10px; font-size:13px; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:8px; transition:.2s; }
  .btn-back:hover { border-color:#14532d; color:#14532d; }

  @media print {
    body { background:#fff; }
    .page { margin:0; padding:10mm 10mm 10mm 12mm; box-shadow:none; width:100%; }
    .print-toolbar { display:none !important; }
    thead th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    tfoot td  { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  }
  @page { size:A4 landscape; margin:0; }
</style>
</head>
<body>

<div class="print-toolbar">
  <a href="javascript:history.back()" class="btn-back">← Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨️ Cetak / Save PDF</button>
</div>

<div class="page">
  <div class="kop">
    <img src="../assets/images/logo-sekolah.png" alt="Logo" onerror="this.style.display='none'">
    <div class="kop-text">
      <h1>SMK NEGERI 3 PADANG</h1>
      <p>Jl. Bungo Pasang, Tabing, Padang, Sumatera Barat · Telp. (0751) 123456</p>
    </div>
  </div>

  <div class="doc-title">
    <h2>Rekap Kehadiran & Jurnal PKL — Seluruh Siswa</h2>
    <p><?= $label_group ?>
      <?= $jadwal ? ' · Tahun Ajaran '.htmlspecialchars($jadwal['tahun_ajaran']) : '' ?>
    </p>
  </div>

  <div class="info-meta">
    <span>Dicetak: <?= date('d F Y, H:i') ?> WIB</span>
    <span>Total Siswa: <?= count($siswa_list) ?> orang</span>
  </div>

  <?php if (empty($siswa_list)): ?>
  <p style="text-align:center;color:#888;padding:20px;">Belum ada data siswa.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th style="width:28px;">No</th>
        <th class="left" style="min-width:130px;">Nama Siswa</th>
        <th style="width:50px;">NIS</th>
        <th style="width:55px;">Kelas</th>
        <?php if ($role==='admin'): ?><th class="left" style="width:110px;">Guru Pembimbing</th><?php endif; ?>
        <th class="left" style="min-width:100px;">Tempat PKL</th>
        <th style="width:38px;">Hadir</th>
        <th style="width:38px;">Sakit</th>
        <th style="width:38px;">Izin</th>
        <th style="width:38px;">Alpha</th>
        <th style="width:60px;">% Hadir</th>
        <th style="width:50px;">Jurnal</th>
        <th style="width:55px;">Disetujui</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $gt_hadir=$gt_sakit=$gt_izin=$gt_alpha=$gt_jurnal=$gt_ok=0;
    foreach ($siswa_list as $i => $s):
        $total = (int)$s['total_abs'];
        $pct   = $total > 0 ? round((int)$s['hadir']/$total*100) : 0;
        $pct_class = $pct >= 90 ? 'good' : ($pct >= 75 ? 'medium' : 'bad');
        $gt_hadir += (int)$s['hadir']; $gt_sakit += (int)$s['sakit'];
        $gt_izin  += (int)$s['izin'];  $gt_alpha += (int)$s['alpha'];
        $gt_jurnal+= (int)$s['total_jurnal']; $gt_ok += (int)$s['jurnal_ok'];
    ?>
    <tr>
      <td class="center"><?= $i+1 ?></td>
      <td><strong><?= htmlspecialchars($s['nama']) ?></strong></td>
      <td class="center"><?= htmlspecialchars($s['nis']) ?></td>
      <td class="center"><?= htmlspecialchars($s['kelas']) ?></td>
      <?php if ($role==='admin'): ?><td><?= htmlspecialchars($s['nama_guru']??'-') ?></td><?php endif; ?>
      <td><?= htmlspecialchars($s['nama_tempat']??'-') ?></td>
      <td class="center good"><?= $s['hadir'] ?></td>
      <td class="center medium"><?= $s['sakit'] ?></td>
      <td class="center" style="color:#15803d;"><?= $s['izin'] ?></td>
      <td class="center bad"><?= $s['alpha'] ?></td>
      <td class="center <?= $pct_class ?>"><strong><?= $pct ?>%</strong></td>
      <td class="center"><?= $s['total_jurnal'] ?></td>
      <td class="center good"><?= $s['jurnal_ok'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="<?= $role==='admin'?6:5 ?>" style="text-align:right;">TOTAL</td>
        <td class="center"><?= $gt_hadir ?></td>
        <td class="center"><?= $gt_sakit ?></td>
        <td class="center"><?= $gt_izin ?></td>
        <td class="center"><?= $gt_alpha ?></td>
        <td class="center"><?php
          $gt_total = $gt_hadir+$gt_sakit+$gt_izin+$gt_alpha;
          echo $gt_total>0 ? round($gt_hadir/$gt_total*100).'%' : '-';
        ?></td>
        <td class="center"><?= $gt_jurnal ?></td>
        <td class="center"><?= $gt_ok ?></td>
      </tr>
    </tfoot>
  </table>
  <?php endif; ?>

  <div style="margin-top:20px;display:flex;justify-content:flex-end;">
    <div style="text-align:center;width:200px;">
      <p style="font-size:9.5pt;">Padang, <?= date('d F Y') ?></p>
      <p style="font-size:9.5pt;">Guru Pembimbing PKL</p>
      <div style="border-bottom:1px solid #000;margin:45px 20px 5px;"></div>
      <p style="font-weight:bold;font-size:10pt;"><?= htmlspecialchars($_SESSION['nama']) ?></p>
      <p style="font-size:9pt;">NIP. ___________________</p>
    </div>
  </div>

  <div class="doc-footer">Dicetak melalui SiPrakerin · SMK Negeri 3 Padang · <?= date('d F Y, H:i') ?> WIB</div>
</div>
</body>
</html>
