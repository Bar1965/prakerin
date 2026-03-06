<?php
function siswaActive($kw){ return strpos($_SERVER['REQUEST_URI'],$kw)!==false?'active':''; }
// Hitung kedalaman path untuk URL relatif
$depth = substr_count($_SERVER['PHP_SELF'], '/') - 2;
$base  = str_repeat('../', $depth);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title??'Siswa' ?> — SiPrakerin</title>
<!-- Dark Mode: terapkan sebelum CSS lain agar tidak flash -->
<script>
(function(){var t=localStorage.getItem('siprakerin_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();
</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>assets/darkmode.css">
<style>
/* ══════════════════════════════════════
   TOKENS
══════════════════════════════════════ */
:root {
  --teal:        #15803d;
  --teal-dark:   #166534;
  --teal-light:  #dcfce7;
  --teal-pale:   #f0fdf4;
  --amber:       #f59e0b;
  --rose:        #f43f5e;
  --ink:         #0f172a;
  --ink-2:       #1e293b;
  --slate:       #64748b;
  --mist:        #94a3b8;
  --border:      #e2e8f0;
  --bg:          #f8fafc;
  --surface:     #ffffff;
  --sidebar-w:   248px;
  --topbar-h:    58px;
  --r:           12px;
  /* compat */
  --primary:     #15803d;
  --primary-light:#dcfce7;
  --primary-dark:#166534;
  --text:        #0f172a;
  --muted:       #94a3b8;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { height: 100%; }
body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--bg); color: var(--ink); font-size: .875rem; min-height: 100%; }
a { text-decoration: none; color: inherit; transition: .15s; }

/* ══════════════════════════════════════
   SIDEBAR
══════════════════════════════════════ */
.sidebar {
  width: var(--sidebar-w); position: fixed; top: 0; left: 0; height: 100vh;
  background: var(--ink-2); display: flex; flex-direction: column;
  z-index: 1040; overflow-y: auto; overflow-x: hidden;
}

/* Brand */
.sb-brand {
  padding: 1.1rem 1.1rem 1rem;
  display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid rgba(255,255,255,.07);
}
.sb-logo {
  width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
  background: linear-gradient(135deg, var(--teal), #22c55e);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; color: #fff;
}
.sb-title  { font-weight: 800; color: #fff; font-size: .92rem; letter-spacing: -.01em; }
.sb-subtitle { font-size: .62rem; color: rgba(255,255,255,.3); margin-top: 1px; }

/* User chip */
.sb-user {
  margin: .75rem .65rem .1rem;
  background: rgba(255,255,255,.05);
  border-radius: 10px; padding: .65rem .75rem;
  display: flex; align-items: center; gap: 9px;
}
.sb-ava {
  width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
  background: linear-gradient(135deg, var(--teal), #22c55e);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 800; font-size: .88rem;
}
.sb-name { color: rgba(255,255,255,.85); font-weight: 700; font-size: .8rem; }
.sb-role { color: rgba(255,255,255,.3); font-size: .64rem; margin-top: 1px; }

/* Nav */
.sb-nav { padding: .5rem .5rem; flex: 1; }
.sb-section {
  padding: .6rem .7rem .2rem;
  font-size: .6rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: rgba(255,255,255,.22);
}
.sb-link {
  display: flex; align-items: center; gap: 9px;
  padding: .55rem .75rem; margin-bottom: 2px; border-radius: 9px;
  color: rgba(255,255,255,.45); font-weight: 500; font-size: .82rem;
}
.sb-link i { font-size: .95rem; flex-shrink: 0; }
.sb-link:hover { background: rgba(255,255,255,.07); color: rgba(255,255,255,.85); }
.sb-link.active {
  background: rgba(21,128,61,.2);
  color: #86efac;
  font-weight: 700;
}
.sb-link.active i { color: var(--teal); }

/* Footer */
.sb-footer {
  padding: .65rem; border-top: 1px solid rgba(255,255,255,.07);
}
.sb-logout {
  display: flex; align-items: center; gap: 8px;
  padding: .5rem .75rem; border-radius: 9px;
  color: rgba(248,113,113,.65); font-size: .8rem; font-weight: 600;
}
.sb-logout:hover { background: rgba(239,68,68,.1); color: #f87171; }

/* ══════════════════════════════════════
   TOPBAR
══════════════════════════════════════ */
.topbar {
  position: fixed; top: 0; left: var(--sidebar-w); right: 0;
  height: var(--topbar-h); background: var(--surface);
  border-bottom: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 1.5rem; z-index: 1030; gap: 1rem;
}
.topbar-title { font-weight: 700; font-size: .9rem; color: var(--ink); }
.topbar-right { display: flex; align-items: center; gap: .65rem; flex-shrink: 0; }
.topbar-chip {
  background: var(--bg); border: 1px solid var(--border);
  border-radius: 8px; padding: .3rem .8rem;
  font-size: .76rem; color: var(--slate); font-weight: 500;
  display: flex; align-items: center; gap: 5px;
}
.topbar-ava {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, var(--teal), #22c55e);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 700; font-size: .8rem; flex-shrink: 0;
}

/* ══════════════════════════════════════
   MAIN WRAPPER
══════════════════════════════════════ */
.main-wrap { margin-left: var(--sidebar-w); padding-top: var(--topbar-h); min-height: 100vh; display: flex; flex-direction: column; }
.page-body { flex: 1; }
.page-body { padding: 1.5rem; flex: 1; }

/* ══════════════════════════════════════
   COMPONENTS
══════════════════════════════════════ */
/* Card */
.si-card {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--r); overflow: hidden;
}
.si-card-header {
  padding: .85rem 1.1rem; border-bottom: 1px solid #f1f5f9;
  display: flex; align-items: center; gap: 8px;
  font-weight: 700; font-size: .875rem; color: var(--ink);
}

/* Stat mini */
.stat-box {
  background: var(--surface); border: 1px solid var(--border);
  border-radius: var(--r); padding: 1rem 1.1rem;
  position: relative; overflow: hidden;
}
.stat-box::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: var(--accent-c, var(--teal));
}
.stat-num  { font-size: 1.75rem; font-weight: 800; color: var(--accent-c, var(--teal)); line-height: 1; }
.stat-lbl  { font-size: .72rem; color: var(--mist); margin-top: 4px; font-weight: 500; }
.stat-icon { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); font-size: 1.8rem; opacity: .08; }

/* Badge */
.badge-teal   { background: var(--teal-light); color: var(--teal-dark); padding: 3px 9px; border-radius: 6px; font-size: .7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }
.badge-green  { background: #dcfce7; color: #15803d; padding: 3px 9px; border-radius: 6px; font-size: .7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }
.badge-yellow { background: #fef9c3; color: #854d0e; padding: 3px 9px; border-radius: 6px; font-size: .7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }
.badge-red    { background: #fee2e2; color: #991b1b; padding: 3px 9px; border-radius: 6px; font-size: .7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }
.badge-blue   { background: #dbeafe; color: #166534; padding: 3px 9px; border-radius: 6px; font-size: .7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }
.badge-gray   { background: #f0fdf4; color: #475569; padding: 3px 9px; border-radius: 6px; font-size: .7rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; }

/* Buttons */
.btn-teal {
  background: var(--teal); color: #fff; border: none;
  padding: .5rem 1.1rem; border-radius: 9px; font-weight: 700; font-size: .82rem;
  display: inline-flex; align-items: center; gap: 6px; cursor: pointer; transition: .18s;
  text-decoration: none; font-family: inherit;
}
.btn-teal:hover { background: var(--teal-dark); color: #fff; transform: translateY(-1px); }
.btn-ghost-si {
  background: transparent; color: var(--slate);
  border: 1.5px solid var(--border); padding: .45rem .9rem;
  border-radius: 9px; font-weight: 600; font-size: .8rem;
  display: inline-flex; align-items: center; gap: 5px; cursor: pointer; transition: .15s;
  text-decoration: none; font-family: inherit;
}
.btn-ghost-si:hover { border-color: var(--teal); color: var(--teal); }

/* Table */
.si-table { width: 100%; border-collapse: collapse; }
.si-table thead th {
  background: #f0fdf4; padding: .7rem 1rem; text-align: left;
  font-size: .7rem; font-weight: 700; color: var(--mist);
  letter-spacing: .06em; text-transform: uppercase;
  border-bottom: 1px solid var(--border);
}
.si-table tbody td { padding: .8rem 1rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; }
.si-table tbody tr:last-child td { border-bottom: none; }
.si-table tbody tr:hover td { background: #f0fdf4; }

/* Form */
.form-label { font-weight: 600; font-size: .8rem; color: var(--ink-2); margin-bottom: .3rem; display: block; }
.form-control, .form-select {
  width: 100%; border: 1.5px solid var(--border); border-radius: 9px;
  padding: .55rem .85rem; font-size: .875rem; font-family: inherit;
  outline: none; transition: border-color .18s, box-shadow .18s;
  background: var(--surface); color: var(--ink);
}
.form-control:focus, .form-select:focus {
  border-color: var(--teal); box-shadow: 0 0 0 3px rgba(21,128,61,.12);
}
textarea.form-control { resize: vertical; min-height: 80px; }
.form-hint { font-size: .72rem; color: var(--mist); margin-top: 3px; }

/* Alert */
.alert-ok  { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 9px; padding: .75rem 1rem; font-size: .84rem; margin-bottom: 1rem; }
.alert-err { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 9px; padding: .75rem 1rem; font-size: .84rem; margin-bottom: 1rem; }

/* Progress */
.prog-track { background: #e2e8f0; border-radius: 6px; height: 8px; overflow: hidden; }
.prog-fill  { height: 100%; border-radius: 6px; transition: width .7s ease; }

/* Animations */
@keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
.fade-up { animation: fadeUp .4s ease both; }
@keyframes fadeIn { from{opacity:0} to{opacity:1} }

/* ══════════════════════════════════════
   FOOTER
══════════════════════════════════════ */
.site-footer {
  background: var(--ink-2); color: rgba(255,255,255,.55);
  padding: 2rem 1.5rem 1.25rem;
  border-top: 1px solid rgba(255,255,255,.06);
  font-size: .78rem;
}
.footer-inner { max-width: 960px; margin: 0 auto; }
.footer-top {
  display: flex; justify-content: space-between; align-items: flex-start;
  gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1.25rem;
}
.footer-brand-name { font-weight: 800; color: #fff; font-size: .95rem; margin-bottom: .2rem; }
.footer-brand-sub  { font-size: .72rem; color: rgba(255,255,255,.35); }
.footer-sosmed-title { font-weight: 700; color: rgba(255,255,255,.7); font-size: .8rem; margin-bottom: .6rem; }
.sosmed-list { display: flex; gap: .5rem; flex-wrap: wrap; }
.sosmed-btn {
  display: inline-flex; align-items: center; gap: 6px;
  padding: .35rem .75rem; border-radius: 8px;
  background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
  color: rgba(255,255,255,.6); font-size: .76rem; font-weight: 600;
  transition: .18s;
}
.sosmed-btn:hover { background: rgba(21,128,61,.25); border-color: var(--teal); color: #86efac; }
.footer-bottom {
  border-top: 1px solid rgba(255,255,255,.07);
  padding-top: .85rem;
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem;
}
.footer-copy { font-size: .73rem; color: rgba(255,255,255,.25); }

/* ══════════════════════════════════════
   RESPONSIVE
══════════════════════════════════════ */
@media (max-width: 768px) {
  :root { --sidebar-w: 0px; }

  /* Sidebar slide in dari kiri */
  .sidebar {
    width: 270px;
    transform: translateX(-100%);
    transition: transform .28s cubic-bezier(.4,0,.2,1);
    box-shadow: none;
  }
  .sidebar.open {
    transform: translateX(0);
    box-shadow: 6px 0 30px rgba(0,0,0,.35);
  }

  /* Overlay gelap */
  .sb-overlay {
    display: none;
    position: fixed; inset: 0;
    background: rgba(0,0,0,.55);
    z-index: 1035;
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
  }
  .sb-overlay.show { display: block; }

  /* Topbar & main */
  .topbar    { left: 0; }
  .main-wrap { margin-left: 0; }
  .page-body { padding: 1rem .875rem; }

  /* Tampilkan tombol burger */
  #burgerBtn { display: block !important; }

  /* Kolom full di mobile */
  .col-lg-4, .col-lg-5, .col-lg-7, .col-lg-8 { width: 100% !important; flex: 0 0 100% !important; max-width: 100% !important; }

  /* Stat grid 2 kolom */
  .col-md-3 { width: 50% !important; flex: 0 0 50% !important; max-width: 50% !important; }
}

@media (max-width: 480px) {
  .page-body { padding: .875rem .75rem; }
  .si-card-header { font-size: .82rem; }
  .stat-num { font-size: 1.45rem; }
  .topbar-title { font-size: .82rem; }

  /* Stat grid tetap 2 kolom di hp kecil */
  .col-6 { width: 50% !important; }

  /* Tabel scroll horizontal */
  .si-card { overflow-x: auto; }
  .si-table { min-width: 480px; }
}
</style>
</head>
<body>

<div class="sb-overlay" id="sbOverlay"></div>
<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
  <div class="sb-brand">
    <!-- Logo Sekolah -->
    <div style="width:40px;height:40px;border-radius:10px;overflow:hidden;flex-shrink:0;
         background:#fff;display:flex;align-items:center;justify-content:center;
         border:2px solid rgba(255,255,255,.15);">
      <img src="<?= $base ?>../../assets/images/logo-sekolah.png" alt="SMKN3"
           style="width:36px;height:36px;object-fit:contain;"
           onerror="this.style.display='none'">
    </div>
    <div>
      <div class="sb-title">SMKN 3 Padang</div>
      <div class="sb-subtitle">SiPrakerin · Portal Siswa</div>
    </div>
  </div>

  <div class="sb-user">
    <div class="sb-ava"><?= strtoupper(substr($_SESSION['nama']??'S',0,1)) ?></div>
    <div>
      <div class="sb-name"><?= htmlspecialchars(substr($_SESSION['nama']??'',0,17)) ?></div>
      <div class="sb-role">Siswa PKL</div>
    </div>
  </div>

  <div class="sb-nav">
    <div class="sb-section">Menu Utama</div>
    <a href="<?= $base ?>siswa/dashboard.php" class="sb-link <?= siswaActive('dashboard') ?>">
      <i class="bi bi-grid-1x2-fill"></i> Dashboard
    </a>

    <div class="sb-section">Aktivitas PKL</div>
    <a href="<?= $base ?>siswa/profil/index.php" class="sb-link <?= siswaActive('profil') ?>">
      <i class="bi bi-person-circle"></i> Profil
    </a>
    <a href="<?= $base ?>siswa/absensi/index.php" class="sb-link <?= siswaActive('absensi') ?>">
      <i class="bi bi-calendar2-check-fill"></i> Absensi
    </a>
    <a href="<?= $base ?>siswa/jurnal/index.php" class="sb-link <?= siswaActive('jurnal') ?>">
      <i class="bi bi-journal-richtext"></i> Jurnal Harian
    </a>
    <a href="<?= $base ?>siswa/penilaian/index.php" class="sb-link <?= siswaActive('penilaian') ?>">
      <i class="bi bi-patch-check-fill"></i> Penilaian
    </a>
  </div>

  <div class="sb-footer">
    <a href="<?= $base ?>auth/logout.php" class="sb-logout btn-logout-siswa">
      <i class="bi bi-box-arrow-right"></i> Keluar
    </a>
  </div>
</div>

<!-- TOPBAR -->
<div class="topbar">
  <div class="d-flex align-items-center gap-2">
    <button class="btn-burger" onclick="openSidebar()" style="background:none;border:none;font-size:1.4rem;color:var(--ink);padding:.2rem .4rem;cursor:pointer;display:none;" id="burgerBtn">
      <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title"><?= $page_title ?? 'Portal Siswa' ?></div>
  </div>
  <div class="topbar-right">
    <!-- Dark Mode Toggle -->
    <button class="dm-toggle" onclick="toggleDarkMode()" title="Mode Gelap">🌙</button>
    <div class="topbar-chip d-none d-sm-flex">
      <i class="bi bi-calendar3" style="color:var(--teal);"></i>
      <?= date('d M Y') ?>
    </div>
    <div class="topbar-ava"><?= strtoupper(substr($_SESSION['nama']??'S',0,1)) ?></div>
    <!-- Logout — hanya muncul di mobile -->
    <a href="<?= $base ?>auth/logout.php" class="btn-logout-siswa"
       id="topbarLogoutSiswa"
       style="display:none;align-items:center;gap:6px;padding:6px 10px;
              border-radius:8px;background:#fef2f2;color:#ef4444;border:1px solid #fecaca;
              font-size:.82rem;font-weight:600;text-decoration:none;">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>
<style>
@media(max-width:768px){
  #topbarLogoutSiswa { display:inline-flex !important; }
}
</style>

<!-- MAIN -->
<div class="main-wrap">
  <div class="page-body fade-up">

<script>
// Sidebar functions harus ada di header agar tersedia saat tombol diklik
function openSidebar(){
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('sbOverlay');
  if(sb) sb.classList.add('open');
  if(ov) ov.classList.add('show');
  document.body.style.overflow = 'hidden';
}
function closeSidebar(){
  var sb = document.getElementById('sidebar');
  var ov = document.getElementById('sbOverlay');
  if(sb) sb.classList.remove('open');
  if(ov) ov.classList.remove('show');
  document.body.style.overflow = '';
}
</script>

<script src="<?= $base ?>assets/darkmode.js"></script>
