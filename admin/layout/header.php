<?php
function adminActive($path) {
    $uri = $_SERVER['REQUEST_URI'];
    return strpos($uri, $path) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?? 'Admin' ?> · SiPrakerin</title>
<!-- Dark Mode: terapkan sebelum CSS lain agar tidak flash -->
<script>
(function(){var t=localStorage.getItem('siprakerin_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();
</script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/darkmode.css">
<style>
:root {
  --primary: #15803d;
  --sidebar-bg: #14532d;
  --sidebar-w: 250px;
  --topbar-h: 58px;
}

body { background: #f0fdf4; font-size: 0.9rem; }

/* SIDEBAR */
.sidebar {
  width: var(--sidebar-w);
  position: fixed; top: 0; left: 0; height: 100vh;
  background: var(--sidebar-bg);
  display: flex; flex-direction: column;
  z-index: 1040; overflow-y: auto;
}
.sidebar-brand {
  padding: 1.25rem 1rem;
  display: flex; align-items: center; gap: 10px;
  border-bottom: 1px solid rgba(255,255,255,.07);
}
.sidebar-brand .brand-icon {
  width: 38px; height: 38px; border-radius: 10px;
  background: linear-gradient(135deg, #16a34a, #15803d);
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem; color: #fff; flex-shrink: 0;
}
.sidebar-brand .brand-name { font-weight: 700; color: #fff; font-size: .95rem; line-height: 1.2; }
.sidebar-brand .brand-sub  { font-size: .68rem; color: rgba(255,255,255,.35); }

.sidebar-section {
  padding: .75rem 1rem .3rem;
  font-size: .63rem; font-weight: 700; letter-spacing: .1em;
  text-transform: uppercase; color: rgba(255,255,255,.25);
}

.sidebar-link {
  display: flex; align-items: center; gap: 9px;
  padding: .55rem 1rem; margin: .1rem .5rem;
  border-radius: 8px; color: rgba(255,255,255,.5);
  font-size: .84rem; font-weight: 500;
  text-decoration: none; transition: all .18s;
}
.sidebar-link i { font-size: 1rem; }
.sidebar-link:hover  { background: rgba(255,255,255,.07); color: rgba(255,255,255,.9); }
.sidebar-link.active { background: rgba(74,222,128,.18); color: #86efac; font-weight: 600; }

.sidebar-footer {
  margin-top: auto; padding: .75rem;
  border-top: 1px solid rgba(255,255,255,.07);
}
.sidebar-user {
  display: flex; align-items: center; gap: 9px;
  padding: .6rem .75rem; border-radius: 9px;
  background: rgba(255,255,255,.04);
}
.sidebar-avatar {
  width: 34px; height: 34px; border-radius: 8px;
  background: linear-gradient(135deg, #16a34a, #15803d);
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-weight: 700; font-size: .85rem; flex-shrink: 0;
}
.sidebar-username { color: rgba(255,255,255,.85); font-weight: 600; font-size: .82rem; }
.sidebar-role     { color: rgba(255,255,255,.3); font-size: .68rem; }
.sidebar-logout {
  display: flex; align-items: center; gap: 8px;
  padding: .45rem .75rem; margin-top: .4rem;
  border-radius: 7px; color: rgba(248,113,113,.7);
  font-size: .8rem; font-weight: 500; text-decoration: none; transition: .18s;
}
.sidebar-logout:hover { background: rgba(239,68,68,.12); color: #f87171; }

/* TOPBAR */
.topbar {
  position: fixed; top: 0; left: var(--sidebar-w); right: 0;
  height: var(--topbar-h); background: #fff;
  border-bottom: 1px solid #e2e8f0;
  display: flex; align-items: center; justify-content: space-between;
  padding: 0 1.75rem; z-index: 1030;
}
.topbar-title { font-weight: 700; font-size: .95rem; color: #1e293b; }
.topbar-date  { font-size: .8rem; color: #94a3b8; background: #f0fdf4; padding: .3rem .85rem; border-radius: 8px; border: 1px solid #e2e8f0; }

/* MAIN CONTENT */
.main-wrapper { margin-left: var(--sidebar-w); padding-top: var(--topbar-h); min-height: 100vh; display: flex; flex-direction: column; }
.page-body { flex: 1; }
.page-body { padding: 1.75rem; }

/* PAGE HEADER */
.page-header { margin-bottom: 1.5rem; }
.page-header h1 { font-size: 1.4rem; font-weight: 800; color: #0f172a; }
.page-header p  { color: #64748b; font-size: .875rem; margin-top: 3px; }

/* STAT CARDS */
.stat-card {
  background: #fff; border-radius: 12px;
  border: 1px solid #e2e8f0; padding: 1.25rem 1.4rem;
  position: relative; overflow: hidden;
  transition: transform .2s, box-shadow .2s;
}
.stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.stat-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: var(--accent-color, #15803d);
}
.stat-num   { font-size: 2rem; font-weight: 800; color: var(--accent-color, #15803d); line-height: 1; }
.stat-label { font-size: .78rem; color: #94a3b8; margin-top: 6px; font-weight: 500; }
.stat-icon  { position: absolute; right: 1.1rem; top: 50%; transform: translateY(-50%); font-size: 2.2rem; opacity: .1; }

/* TABLE */
.table-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
.table-card-header {
  padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9;
  display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;
}
.table-card-header h5 { font-weight: 700; font-size: .9rem; margin: 0; color: #1e293b; }
.tbl { width: 100%; border-collapse: collapse; }
.tbl thead th {
  background: #f0fdf4; padding: .75rem 1rem;
  text-align: left; font-size: .72rem; font-weight: 700;
  color: #94a3b8; letter-spacing: .05em; text-transform: uppercase;
  border-bottom: 1px solid #e2e8f0;
}
.tbl tbody td { padding: .875rem 1rem; border-bottom: 1px solid #f8fafc; vertical-align: middle; font-size: .875rem; }
.tbl tbody tr:last-child td { border-bottom: none; }
.tbl tbody tr:hover td { background: #f0fdf4; }

/* BUTTONS */
.btn-primary-custom {
  background: #15803d; color: #fff; border: none;
  padding: .5rem 1.1rem; border-radius: 8px; font-weight: 600; font-size: .84rem;
  display: inline-flex; align-items: center; gap: 6px;
  text-decoration: none; transition: .18s; cursor: pointer;
}
.btn-primary-custom:hover { background: #166534; color: #fff; transform: translateY(-1px); }

.btn-edit-sm {
  background: #eff6ff; color: #15803d; border: 1px solid #bfdbfe;
  padding: .3rem .75rem; border-radius: 6px; font-size: .78rem; font-weight: 600;
  text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: .15s;
}
.btn-edit-sm:hover { background: #15803d; color: #fff; }

.btn-danger-sm {
  background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;
  padding: .3rem .75rem; border-radius: 6px; font-size: .78rem; font-weight: 600;
  text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: .15s;
}
.btn-danger-sm:hover { background: #dc2626; color: #fff; }

/* BADGES */
.badge-blue   { background: #eff6ff; color: #166534; }
.badge-green  { background: #f0fdf4; color: #15803d; }
.badge-yellow { background: #fefce8; color: #a16207; }
.badge-red    { background: #fef2f2; color: #dc2626; }
.badge-gray   { background: #f0fdf4; color: #64748b; }
.badge-purple { background: #f5f3ff; color: #15803d; }

/* FORM */
.form-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 1.25rem; }
.form-card-header { padding: 1rem 1.25rem; border-bottom: 1px solid #f1f5f9; font-weight: 700; font-size: .9rem; color: #1e293b; }
.form-card-body   { padding: 1.25rem; }

.form-label  { font-weight: 600; font-size: .82rem; color: #374151; margin-bottom: .35rem; }
.form-control, .form-select {
  border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: .875rem;
  padding: .55rem .85rem; transition: border-color .18s, box-shadow .18s;
}
.form-control:focus, .form-select:focus {
  border-color: #15803d; box-shadow: 0 0 0 3px rgba(21,128,61,.12); outline: none;
}
.form-hint { font-size: .75rem; color: #94a3b8; margin-top: 3px; }

/* ALERTS */
.alert-success-custom { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 8px; padding: .75rem 1rem; font-size: .875rem; }
.alert-error-custom   { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; border-radius: 8px; padding: .75rem 1rem; font-size: .875rem; }

/* EMPTY STATE */
.empty-state { text-align: center; padding: 3rem 2rem; color: #94a3b8; }
.empty-state i { font-size: 2.5rem; opacity: .4; display: block; margin-bottom: .75rem; }

/* ANIMATION */
@keyframes fadeIn { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
.fade-in { animation: fadeIn .35s ease both; }

/* ══════════════════════════════════════
   RESPONSIVE ADMIN
══════════════════════════════════════ */
@media (max-width: 992px) {
  :root { --sidebar-w: 0px; }

  .sidebar {
    width: 250px;
    transform: translateX(-100%);
    transition: transform .28s cubic-bezier(.4,0,.2,1);
  }
  .sidebar.open {
    transform: translateX(0);
    box-shadow: 4px 0 24px rgba(0,0,0,.25);
  }

  /* Overlay */
  .sb-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(0,0,0,.45); z-index: 1035;
    backdrop-filter: blur(2px);
  }
  .sb-overlay.show { display: block; }

  .topbar     { left: 0; }
  .main-wrapper { margin-left: 0; }
  .page-body  { padding: 1.25rem 1rem; }

  /* Tabel scroll horizontal */
  .table-card { overflow-x: auto; }
  .tbl        { min-width: 600px; }
}

@media (max-width: 576px) {
  .page-body { padding: 1rem .75rem; }
  .topbar    { padding: 0 1rem; }
  .topbar-date { display: none; }

  /* Stack stat cards */
  .row.g-3 > .col-6.col-md-4.col-xl-2 { width: 50%; }
  .row.g-3 > .col-12.col-lg-7,
  .row.g-3 > .col-12.col-lg-5 { width: 100%; }

  /* Footer sosmed wrap */
  footer .d-flex { flex-direction: column; gap: .5rem; }
}

@media (max-width: 992px) {
  :root { --sidebar-w: 0px; }
  .sidebar { width: 260px; transform: translateX(-100%); transition: transform .28s ease; position: fixed; z-index: 1060; }
  .sidebar.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,.25); }
  .topbar { left: 0; }
  .main-wrapper { margin-left: 0; }
  .page-body { padding: 1rem; }
}
@media (max-width: 560px) {
  .page-body { padding: .875rem .75rem; }
}
</style>
</head>
<body>

<div class="sb-overlay" id="sbOverlay"></div>
<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon" style="background:transparent;padding:0;overflow:hidden;">
      <img src="../../assets/images/logo-sekolah.png" alt="SMKN3"
           style="width:38px;height:38px;object-fit:contain;border-radius:8px;"
           onerror="this.style.display='none'">
    </div>
    <div>
      <div class="brand-name">SMKN 3 Padang</div>
      <div class="brand-sub">SiPrakerin · Admin</div>
    </div>
  </div>

  <div style="padding:.75rem 0;flex:1;">
    <div class="sidebar-section">Utama</div>
    <a href="../dashboard/index.php" class="sidebar-link <?= adminActive('dashboard') ?>">
      <i class="bi bi-grid-1x2"></i> Dashboard
    </a>

    <div class="sidebar-section">Data Master</div>
    <a href="../guru/index.php" class="sidebar-link <?= adminActive('/admin/guru') ?>">
      <i class="bi bi-person-badge"></i> Guru Pembimbing
    </a>
    <a href="../guru/kelompok_guru.php" class="sidebar-link <?= adminActive('kelompok_guru') ?>">
      <i class="bi bi-people"></i> Kelompok Bimbingan
    </a>
    <a href="../instruktur/index.php" class="sidebar-link <?= adminActive('/admin/instruktur') ?>">
      <i class="bi bi-building"></i> Instruktur DU/DI
    </a>
    <a href="../instruktur/kelompok_instruktur.php" class="sidebar-link <?= adminActive('kelompok_instruktur') ?>">
      <i class="bi bi-diagram-3"></i> Kelompok Instruktur
    </a>
    <a href="../siswa/index.php" class="sidebar-link <?= adminActive('/admin/siswa') ?>">
      <i class="bi bi-people"></i> Data Siswa
    </a>
    <a href="../kelas/index.php" class="sidebar-link <?= adminActive('/admin/kelas') ?>">
      <i class="bi bi-grid-3x3-gap"></i> Kelola Kelas
    </a>
    <a href="../tempat/index.php" class="sidebar-link <?= adminActive('tempat') ?>">
      <i class="bi bi-geo-alt"></i> Tempat PKL
    </a>

    <div class="sidebar-section">Kegiatan PKL</div>
    <a href="../jadwal/index.php" class="sidebar-link <?= adminActive('jadwal/index') ?>">
      <i class="bi bi-calendar-range"></i> Jadwal Prakerin
    </a>
    <a href="../jadwal/jam_absen.php" class="sidebar-link <?= adminActive('jam_absen') ?>">
      <i class="bi bi-clock"></i> Jam Absen
    </a>

    <div class="sidebar-section">Pengaturan</div>
    <a href="../config/wa.php" class="sidebar-link <?= adminActive('/config/wa') ?>">
      <i class="bi bi-whatsapp"></i> Konfigurasi WA OTP
    </a>
  </div>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION['nama']??'A',0,1)) ?></div>
      <div>
        <div class="sidebar-username"><?= htmlspecialchars(substr($_SESSION['nama']??'Admin',0,16)) ?></div>
        <div class="sidebar-role">Administrator</div>
      </div>
    </div>
    <a href="../../auth/logout.php" class="sidebar-logout btn-logout-admin">
      <i class="bi bi-box-arrow-right"></i> Logout
    </a>
  </div>
</div>

<!-- TOPBAR -->
<div class="topbar">
  <div class="d-flex align-items-center gap-2">
    <button class="btn-burger d-lg-none" style="background:none;border:none;font-size:1.3rem;color:#1e293b;cursor:pointer;padding:4px 6px;border-radius:6px;line-height:1;">
      <i class="bi bi-list"></i>
    </button>
    <div class="topbar-title"><?= $page_title ?? 'Dashboard' ?></div>
  </div>
  <div class="d-flex align-items-center gap-2">
    <div class="topbar-date d-none d-sm-flex"><i class="bi bi-calendar3 me-1"></i><?= date('d M Y') ?></div>
    <!-- Dark Mode Toggle -->
    <button class="dm-toggle" onclick="toggleDarkMode()" title="Mode Gelap">🌙</button>
    <!-- Bell Notifikasi -->
    <div style="position:relative;" id="notifWrap">
      <button id="notifBtn" onclick="toggleNotif()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;position:relative;color:#1e293b;font-size:1.2rem;transition:.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
        <i class="bi bi-bell"></i>
        <span id="notifBadge" style="display:none;position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border-radius:50%;width:18px;height:18px;font-size:.65rem;font-weight:700;line-height:18px;text-align:center;"></span>
      </button>
      <!-- Dropdown Notifikasi -->
      <div id="notifDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:320px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.12);z-index:2000;overflow:hidden;">
        <div style="padding:.85rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
          <span style="font-weight:700;font-size:.9rem;">Notifikasi</span>
          <button onclick="bacaSemua()" style="background:none;border:none;cursor:pointer;font-size:.75rem;color:#15803d;font-weight:600;padding:0;">Tandai semua dibaca</button>
        </div>
        <div id="notifList" style="max-height:360px;overflow-y:auto;"></div>
        <div style="padding:.6rem 1rem;border-top:1px solid #f1f5f9;text-align:center;">
          <span style="font-size:.75rem;color:#94a3b8;">SiPrakerin · Notifikasi Real-time</span>
        </div>
      </div>
    </div>
    <!-- Logout button — hanya muncul di mobile -->
    <a href="../../auth/logout.php"
       class="btn-logout-admin d-lg-none"
       style="display:inline-flex;align-items:center;gap:6px;padding:6px 12px;
              border-radius:8px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;
              font-size:.82rem;font-weight:600;text-decoration:none;transition:.15s;"
       onmouseover="this.style.background='#dc2626';this.style.color='#fff';"
       onmouseout="this.style.background='#fef2f2';this.style.color='#dc2626';">
      <i class="bi bi-box-arrow-right"></i>
      <span class="d-none d-sm-inline">Logout</span>
    </a>
  </div>
</div>

<!-- MAIN -->
<div class="main-wrapper">
  <div class="page-body fade-in">
<style>
/* Compat vars untuk halaman lama */
:root {
  --primary: #15803d;
  --primary-dark: #166534;
  --text-main: #1e293b;
  --text-muted: #64748b;
  --border: #e2e8f0;
  --bg-body: #f0fdf4;
  --surface: #ffffff;
  --radius: 12px;
  --muted: #94a3b8;
  --text: #1e293b;
  --accent: #15803d;
  --r: 12px;
}

@media (max-width: 992px) {
  :root { --sidebar-w: 0px; }
  .sidebar { width: 260px; transform: translateX(-100%); transition: transform .28s ease; position: fixed; z-index: 1060; }
  .sidebar.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,.25); }
  .topbar { left: 0; }
  .main-wrapper { margin-left: 0; }
  .page-body { padding: 1rem; }
}
@media (max-width: 560px) {
  .page-body { padding: .875rem .75rem; }
}
</style>
<script src="../../assets/darkmode.js"></script>
