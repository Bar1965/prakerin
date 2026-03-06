<?php
/**
 * Export PDF Rekap Absensi Siswa
 * Akses: admin (semua), guru (bimbingannya), instruktur (tempat PKL-nya), siswa (diri sendiri)
 */
session_start();
require '../config/database.php';

if (empty($_SESSION['login'])) { header("Location: ../auth/login.php"); exit; }

$role    = $_SESSION['role'];
$user_id = (int)$_SESSION['user_id'];

// ── Parameter ──
$siswa_id  = (int)($_GET['siswa_id'] ?? 0);
$bulan     = $_GET['bulan'] ?? '';   // format: 2024-01 (opsional, kosong = semua)
$mode      = $_GET['mode'] ?? 'siswa'; // siswa | kelas | tempat

// ── Validasi akses berdasarkan role ──
$siswa = null;

if ($role === 'siswa') {
    // Siswa hanya bisa export milik sendiri
    $s = mysqli_prepare($conn, "SELECT s.*, u.nama, tp.nama_tempat, g.user_id AS guru_user_id,
            ug.nama AS nama_guru
            FROM siswa s JOIN users u ON u.id=s.user_id
            LEFT JOIN tempat_pkl tp ON tp.id=s.tempat_pkl_id
            LEFT JOIN guru g ON g.id=s.guru_id
            LEFT JOIN users ug ON ug.id=g.user_id
            WHERE s.user_id=? LIMIT 1");
    mysqli_stmt_bind_param($s, 'i', $user_id);
    mysqli_stmt_execute($s);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    $siswa_id = $siswa['id'] ?? 0;

} elseif ($role === 'guru') {
    // Guru hanya bisa export siswa bimbingannya
    $s = mysqli_prepare($conn, "SELECT s.*, u.nama, tp.nama_tempat,
            ug.nama AS nama_guru
            FROM siswa s JOIN users u ON u.id=s.user_id
            LEFT JOIN tempat_pkl tp ON tp.id=s.tempat_pkl_id
            LEFT JOIN guru g ON g.id=s.guru_id
            LEFT JOIN users ug ON ug.id=g.user_id
            WHERE s.id=? AND g.user_id=? LIMIT 1");
    mysqli_stmt_bind_param($s, 'ii', $siswa_id, $user_id);
    mysqli_stmt_execute($s);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    if (!$siswa) { echo "<p>Akses ditolak.</p>"; exit; }

} elseif ($role === 'instruktur') {
    // Instruktur hanya bisa export siswa di tempat PKL-nya
    $instr = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT tempat_pkl_id FROM instruktur WHERE user_id=$user_id LIMIT 1"
    ));
    $s = mysqli_prepare($conn, "SELECT s.*, u.nama, tp.nama_tempat,
            ug.nama AS nama_guru
            FROM siswa s JOIN users u ON u.id=s.user_id
            LEFT JOIN tempat_pkl tp ON tp.id=s.tempat_pkl_id
            LEFT JOIN guru g ON g.id=s.guru_id
            LEFT JOIN users ug ON ug.id=g.user_id
            WHERE s.id=? AND s.tempat_pkl_id=? LIMIT 1");
    mysqli_stmt_bind_param($s, 'ii', $siswa_id, (int)($instr['tempat_pkl_id']??0));
    mysqli_stmt_execute($s);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    if (!$siswa) { echo "<p>Akses ditolak.</p>"; exit; }

} elseif ($role === 'admin') {
    $s = mysqli_prepare($conn, "SELECT s.*, u.nama, tp.nama_tempat,
            ug.nama AS nama_guru
            FROM siswa s JOIN users u ON u.id=s.user_id
            LEFT JOIN tempat_pkl tp ON tp.id=s.tempat_pkl_id
            LEFT JOIN guru g ON g.id=s.guru_id
            LEFT JOIN users ug ON ug.id=g.user_id
            WHERE s.id=? LIMIT 1");
    mysqli_stmt_bind_param($s, 'i', $siswa_id);
    mysqli_stmt_execute($s);
    $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    if (!$siswa) { echo "<p>Data tidak ditemukan.</p>"; exit; }
} else {
    echo "<p>Akses ditolak.</p>"; exit;
}

// ── Ambil data absensi ──
if ($bulan) {
    $sq = mysqli_prepare($conn,
        "SELECT * FROM absensi WHERE siswa_id=? AND DATE_FORMAT(tanggal,'%Y-%m')=? ORDER BY tanggal ASC");
    mysqli_stmt_bind_param($sq, 'is', $siswa_id, $bulan);
} else {
    $sq = mysqli_prepare($conn,
        "SELECT * FROM absensi WHERE siswa_id=? ORDER BY tanggal ASC");
    mysqli_stmt_bind_param($sq, 'i', $siswa_id);
}
mysqli_stmt_execute($sq);
$absensi_list = mysqli_stmt_get_result($sq);
$absensi_rows = [];
while ($r = mysqli_fetch_assoc($absensi_list)) $absensi_rows[] = $r;

// ── Hitung statistik ──
$total  = count($absensi_rows);
$hadir  = count(array_filter($absensi_rows, fn($r) => $r['status']==='hadir'));
$sakit  = count(array_filter($absensi_rows, fn($r) => $r['status']==='sakit'));
$izin   = count(array_filter($absensi_rows, fn($r) => $r['status']==='izin'));
$alpha  = count(array_filter($absensi_rows, fn($r) => $r['status']==='alpha'));
$pct    = $total > 0 ? round($hadir/$total*100) : 0;

// ── Ambil jadwal prakerin (untuk periode) ──
$jadwal = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM jadwal_prakerin ORDER BY tanggal_mulai DESC LIMIT 1"
));

$judul_bulan = $bulan ? date('F Y', strtotime($bulan.'-01')) : 'Seluruh Periode PKL';
$tgl_cetak   = date('d F Y');
$hari_id     = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa',
                'Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rekap Absensi — <?= htmlspecialchars($siswa['nama']) ?></title>
<style>
  /* ── PRINT & SCREEN ── */
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Arial', sans-serif; font-size: 11pt; color: #000; background: #f0f0f0; }

  .page {
    width: 210mm; min-height: 297mm;
    margin: 20px auto; padding: 20mm 20mm 20mm 25mm;
    background: #fff; box-shadow: 0 2px 16px rgba(0,0,0,.15);
  }

  /* ── KOP SEKOLAH ── */
  .kop { display:flex; align-items:center; gap:16px; padding-bottom:10px; border-bottom:3px solid #14532d; margin-bottom:6px; }
  .kop img { width:64px; height:64px; object-fit:contain; }
  .kop-text { flex:1; }
  .kop-text h1 { font-size:14pt; font-weight:bold; color:#14532d; margin-bottom:2px; }
  .kop-text p  { font-size:9pt; color:#333; line-height:1.5; }
  .kop-sub { text-align:center; font-size:10pt; font-weight:bold; color:#14532d; letter-spacing:.05em; margin-bottom:14px; padding-bottom:4px; border-bottom:1px solid #14532d; }

  /* ── JUDUL DOKUMEN ── */
  .doc-title { text-align:center; margin-bottom:14px; }
  .doc-title h2 { font-size:13pt; font-weight:bold; text-transform:uppercase; letter-spacing:.08em; }
  .doc-title p  { font-size:10pt; color:#444; margin-top:3px; }

  /* ── INFO SISWA ── */
  .info-box { border:1px solid #ccc; border-radius:4px; padding:10px 14px; margin-bottom:14px; background:#fafafa; }
  .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:4px 20px; }
  .info-row { display:flex; gap:4px; font-size:10pt; }
  .info-label { color:#555; min-width:120px; }
  .info-val   { font-weight:bold; }

  /* ── TABEL ABSENSI ── */
  table { width:100%; border-collapse:collapse; margin-bottom:14px; font-size:10pt; }
  thead th { background:#14532d; color:#fff; padding:7px 8px; text-align:left; font-size:9.5pt; }
  tbody td { padding:5px 8px; border-bottom:1px solid #e8e8e8; vertical-align:top; }
  tbody tr:nth-child(even) td { background:#f7f9fc; }
  tbody tr:hover td { background:#eef2f8; }
  .status-hadir { color:#15803d; font-weight:bold; }
  .status-sakit { color:#d97706; font-weight:bold; }
  .status-izin  { color:#15803d; font-weight:bold; }
  .status-alpha { color:#dc2626; font-weight:bold; }

  /* ── STATISTIK ── */
  .stat-row { display:flex; gap:10px; margin-bottom:16px; }
  .stat-box { flex:1; border:1px solid #ddd; border-radius:6px; padding:10px; text-align:center; }
  .stat-box .num  { font-size:22pt; font-weight:bold; line-height:1; }
  .stat-box .lbl  { font-size:8.5pt; color:#555; margin-top:3px; }
  .stat-hadir .num { color:#15803d; }
  .stat-sakit .num { color:#d97706; }
  .stat-izin  .num { color:#15803d; }
  .stat-alpha .num { color:#dc2626; }
  .stat-pct   .num { color:#14532d; }

  /* ── TANDA TANGAN ── */
  .ttd-section { display:flex; justify-content:space-between; margin-top:24px; }
  .ttd-box { text-align:center; width:180px; }
  .ttd-box .ttd-line { border-bottom:1px solid #000; margin:50px 20px 6px; }
  .ttd-box p { font-size:9.5pt; }
  .ttd-box .ttd-name { font-weight:bold; font-size:10pt; margin-top:3px; }

  /* ── FOOTER ── */
  .doc-footer { margin-top:16px; font-size:8pt; color:#888; text-align:center; border-top:1px solid #eee; padding-top:8px; }

  /* ── TOMBOL (disembunyikan saat print) ── */
  .print-toolbar {
    position:fixed; bottom:24px; right:24px; display:flex; gap:10px; z-index:999;
  }
  .btn-print {
    background:#14532d; color:#fff; border:none; padding:12px 24px;
    border-radius:10px; font-size:13px; font-weight:700; cursor:pointer;
    display:flex; align-items:center; gap:8px; box-shadow:0 4px 16px rgba(0,0,0,.2);
    transition:.2s;
  }
  .btn-print:hover { background:#15803d; transform:translateY(-2px); }
  .btn-back {
    background:#fff; color:#333; border:1px solid #ccc; padding:12px 20px;
    border-radius:10px; font-size:13px; font-weight:600; cursor:pointer;
    display:flex; align-items:center; gap:8px; box-shadow:0 2px 8px rgba(0,0,0,.1);
    text-decoration:none; transition:.2s;
  }
  .btn-back:hover { border-color:#14532d; color:#14532d; }

  /* ── PRINT MODE ── */
  @media print {
    body { background:#fff; }
    .page { margin:0; padding:15mm 15mm 15mm 20mm; box-shadow:none; width:100%; }
    .print-toolbar { display:none !important; }
    tbody tr:hover td { background:inherit; }
    thead th { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .stat-box { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
  }
  @page { size:A4; margin:0; }
</style>
</head>
<body>

<!-- Tombol toolbar -->
<div class="print-toolbar">
  <a href="javascript:history.back()" class="btn-back">← Kembali</a>
  <button class="btn-print" onclick="window.print()">🖨️ Cetak / Save PDF</button>
</div>

<div class="page">

  <!-- KOP SEKOLAH -->
  <div class="kop">
    <img src="../assets/images/logo-sekolah.png" alt="Logo SMKN 3 Padang"
         onerror="this.style.display='none'">
    <div class="kop-text">
      <h1>SMK NEGERI 3 PADANG</h1>
      <p>
        Jl. Bungo Pasang, Tabing, Padang, Sumatera Barat<br>
        Telp. (0751) 123456 &nbsp;·&nbsp; smkn3padang@sch.id
      </p>
    </div>
  </div>
  <div style="text-align:right;font-size:8pt;color:#888;margin:4px 0 10px;">
    Sistem Informasi Praktik Kerja Industri (SiPrakerin)
  </div>

  <!-- JUDUL -->
  <div class="doc-title">
    <h2>Rekap Absensi Praktik Kerja Lapangan (PKL)</h2>
    <p><?= $judul_bulan ?>
      <?php if ($jadwal): ?>
        &nbsp;·&nbsp; Tahun Ajaran <?= htmlspecialchars($jadwal['tahun_ajaran']) ?>
      <?php endif; ?>
    </p>
  </div>

  <!-- INFO SISWA -->
  <div class="info-box">
    <div class="info-grid">
      <div class="info-row"><span class="info-label">Nama Siswa</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['nama']) ?></span></div>
      <div class="info-row"><span class="info-label">Kelas</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['kelas']) ?></span></div>
      <div class="info-row"><span class="info-label">NIS</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['nis']) ?></span></div>
      <div class="info-row"><span class="info-label">Jurusan</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['jurusan'] ?? '-') ?></span></div>
      <div class="info-row"><span class="info-label">Tempat PKL</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['nama_tempat'] ?? '-') ?></span></div>
      <div class="info-row"><span class="info-label">Guru Pembimbing</span><span>:</span><span class="info-val"><?= htmlspecialchars($siswa['nama_guru'] ?? '-') ?></span></div>
      <?php if ($jadwal): ?>
      <div class="info-row"><span class="info-label">Periode PKL</span><span>:</span><span class="info-val"><?= date('d M Y', strtotime($jadwal['tanggal_mulai'])) ?> s/d <?= date('d M Y', strtotime($jadwal['tanggal_selesai'])) ?></span></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- STATISTIK -->
  <div class="stat-row">
    <div class="stat-box stat-hadir"><div class="num"><?= $hadir ?></div><div class="lbl">Hadir</div></div>
    <div class="stat-box stat-sakit"><div class="num"><?= $sakit ?></div><div class="lbl">Sakit</div></div>
    <div class="stat-box stat-izin" ><div class="num"><?= $izin  ?></div><div class="lbl">Izin</div></div>
    <div class="stat-box stat-alpha"><div class="num"><?= $alpha ?></div><div class="lbl">Alpha</div></div>
    <div class="stat-box stat-pct"  ><div class="num"><?= $pct ?>%</div><div class="lbl">Kehadiran</div></div>
  </div>

  <!-- TABEL ABSENSI -->
  <?php if (empty($absensi_rows)): ?>
  <p style="text-align:center;color:#888;padding:20px;">Belum ada data absensi.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th style="width:30px;">No</th>
        <th style="width:80px;">Tanggal</th>
        <th style="width:70px;">Hari</th>
        <th style="width:60px;">Jam Masuk</th>
        <th style="width:65px;">Status</th>
        <th>Keterangan</th>
      </tr>
    </thead>
    <tbody>
    <?php $no=1; foreach ($absensi_rows as $abs):
      $tgl_str = $abs['tanggal'];
      $hari    = $hari_id[date('l', strtotime($tgl_str))] ?? '';
      $jam     = $abs['jam_masuk'] ? date('H:i', strtotime($abs['jam_masuk'])) : '-';
      $st_class = 'status-'.$abs['status'];
      $st_label = ['hadir'=>'Hadir','sakit'=>'Sakit','izin'=>'Izin','alpha'=>'Alpha'][$abs['status']] ?? $abs['status'];
    ?>
    <tr>
      <td style="text-align:center;"><?= $no++ ?></td>
      <td><?= date('d/m/Y', strtotime($tgl_str)) ?></td>
      <td><?= $hari ?></td>
      <td style="text-align:center;"><?= $jam ?></td>
      <td class="<?= $st_class ?>"><?= $st_label ?></td>
      <td><?= htmlspecialchars($abs['keterangan'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr style="background:#f0f4fa;">
        <td colspan="4" style="font-weight:bold;padding:6px 8px;">Total: <?= $total ?> hari tercatat</td>
        <td colspan="2" style="font-weight:bold;padding:6px 8px;">Kehadiran: <?= $pct ?>%</td>
      </tr>
    </tfoot>
  </table>
  <?php endif; ?>

  <!-- TANDA TANGAN -->
  <div class="ttd-section">
    <div class="ttd-box">
      <p>Padang, <?= $tgl_cetak ?></p>
      <p>Siswa Bersangkutan</p>
      <div class="ttd-line"></div>
      <p class="ttd-name"><?= htmlspecialchars($siswa['nama']) ?></p>
      <p style="font-size:9pt;">NIS. <?= htmlspecialchars($siswa['nis']) ?></p>
    </div>
    <div class="ttd-box">
      <p>Mengetahui,</p>
      <p>Guru Pembimbing PKL</p>
      <div class="ttd-line"></div>
      <p class="ttd-name"><?= htmlspecialchars($siswa['nama_guru'] ?? '________________________') ?></p>
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

  <div class="doc-footer">
    Dicetak melalui SiPrakerin · SMK Negeri 3 Padang · <?= date('d F Y, H:i') ?> WIB
  </div>
</div>

</body>
</html>
