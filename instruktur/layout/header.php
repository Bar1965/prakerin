<?php
function instrActive($kw){ return strpos($_SERVER['REQUEST_URI'],$kw)!==false?'active':''; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title??'Instruktur' ?> · SiPrakerin</title>
<!-- Dark Mode: terapkan sebelum CSS lain agar tidak flash -->
<script>
(function(){var t=localStorage.getItem('siprakerin_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/darkmode.css">
<style>
:root{
  --primary:#15803d; --primary-dark:#166534; --primary-light:#16a34a;
  --primary-glow:rgba(21,128,61,.15);
  --green-900:#14532d; --green-800:#166534; --green-700:#15803d;
  --green-600:#16a34a; --green-500:#22c55e; --green-400:#4ade80;
  --green-200:#bbf7d0; --green-100:#dcfce7; --green-50:#f0fdf4;
  --sidebar-bg:#14532d; --sidebar-w:252px; --topbar-h:58px;
  --text:#0f172a; --muted:#64748b; --border:#e2e8f0;
  --bg:#f0fdf4; --surface:#ffffff; --r:12px;
  /* alias lama */
  --brand:#14532d; --accent:#16a34a; --text-main:#0f172a;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);font-size:.9rem;}
a{text-decoration:none;color:inherit;transition:.15s;}

/* ── SIDEBAR ── */
.sidebar{
  width:var(--sidebar-w);position:fixed;top:0;left:0;height:100vh;
  background:var(--sidebar-bg);display:flex;flex-direction:column;
  z-index:100;overflow-y:auto;
  scrollbar-width:thin;scrollbar-color:rgba(255,255,255,.1) transparent;
}
.sb-logo-area{
  padding:1.1rem 1rem;border-bottom:1px solid rgba(255,255,255,.08);
  display:flex;align-items:center;gap:10px;flex-shrink:0;
}
.sb-logo-area img{width:36px;height:36px;object-fit:contain;border-radius:8px;}
.sb-title{font-weight:800;color:#fff;font-size:.9rem;}
.sb-sub{font-size:.65rem;color:rgba(255,255,255,.4);margin-top:1px;}

.sb-nav{padding:.6rem .6rem;flex:1;}
.sb-group-label{padding:.6rem .6rem .2rem;font-size:.6rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.25);}
.sb-item{
  display:flex;align-items:center;gap:9px;padding:.52rem .75rem;margin-bottom:2px;
  border-radius:8px;color:rgba(255,255,255,.52);font-weight:500;font-size:.835rem;
}
.sb-item:hover{background:rgba(255,255,255,.08);color:rgba(255,255,255,.9);}
.sb-item.active{
  background:rgba(74,222,128,.18);color:#86efac;font-weight:700;
  border-left:3px solid #4ade80;margin-left:calc(.6rem - 3px);
}
.sb-item svg{flex-shrink:0;}

.sb-user-block{padding:.75rem;border-top:1px solid rgba(255,255,255,.08);flex-shrink:0;}
.sb-user-card{
  background:rgba(255,255,255,.06);border-radius:10px;padding:.65rem .85rem;
  display:flex;align-items:center;gap:9px;margin-bottom:.4rem;
}
.sb-ava{
  width:34px;height:34px;border-radius:8px;
  background:linear-gradient(135deg,var(--green-600),var(--green-400));
  display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.85rem;flex-shrink:0;
}
.sb-un{color:rgba(255,255,255,.88);font-weight:700;font-size:.82rem;}
.sb-ur{color:rgba(255,255,255,.3);font-size:.67rem;}
.sb-logout-btn{
  display:flex;align-items:center;gap:7px;padding:.5rem .75rem;border-radius:8px;
  color:rgba(248,113,113,.65);font-size:.8rem;font-weight:500;width:100%;transition:.18s;
}
.sb-logout-btn:hover{background:rgba(239,68,68,.1);color:#fca5a5;}

/* ── TOPBAR ── */
.topbar{
  position:fixed;top:0;left:var(--sidebar-w);right:0;height:var(--topbar-h);
  background:var(--surface);border-bottom:1px solid rgba(21,128,61,.1);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 1.75rem;z-index:50;box-shadow:0 1px 0 rgba(21,128,61,.06);
}
.topbar-title{font-weight:700;font-size:.95rem;color:var(--text);}
.topbar-pill{
  background:var(--green-100);color:var(--green-800);padding:.3rem .9rem;
  border-radius:20px;font-size:.75rem;font-weight:700;border:1px solid var(--green-200);
}

/* ── MAIN ── */
.main{margin-left:var(--sidebar-w);min-height:100vh;padding-top:var(--topbar-h);}
.content{padding:1.75rem;}

/* ── PAGE HEADER ── */
.page-header{margin-bottom:1.5rem;display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;}
.page-header h1{font-size:1.35rem;font-weight:800;letter-spacing:-.02em;}
.page-header p{color:var(--muted);font-size:.875rem;margin-top:3px;}

/* ── CARDS & STATS ── */
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);}
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:.85rem;margin-bottom:1.5rem;}
.stat-b{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:1.1rem 1.25rem;transition:.2s;}
.stat-b:hover{box-shadow:0 8px 20px rgba(21,128,61,.1);transform:translateY(-2px);}
.stat-b .n{font-size:1.8rem;font-weight:800;letter-spacing:-.03em;color:var(--primary);}
.stat-b .l{font-size:.75rem;color:var(--muted);font-weight:500;margin-top:3px;}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;gap:7px;padding:.5rem 1rem;border-radius:8px;font-weight:600;font-size:.82rem;border:none;cursor:pointer;font-family:inherit;transition:.18s;text-decoration:none;}
.btn-primary-custom,.btn-brand{background:var(--primary);color:#fff;}
.btn-primary-custom:hover,.btn-brand:hover{background:var(--primary-dark);color:#fff;}
.btn-accent{background:var(--green-500);color:#fff;}
.btn-accent:hover{background:var(--green-600);}
.btn-outline{background:#fff;border:1.5px solid var(--border);color:var(--text);}
.btn-outline:hover{border-color:var(--primary);color:var(--primary);background:var(--green-50);}
.btn-sm{padding:.35rem .8rem;font-size:.78rem;}

.btn-edit-sm{background:var(--green-50);color:var(--green-700);border:1px solid var(--green-200);padding:.3rem .75rem;border-radius:7px;font-size:.78rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;transition:.15s;}
.btn-edit-sm:hover{background:var(--primary);color:#fff;border-color:var(--primary);}
.btn-danger-sm{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:.3rem .75rem;border-radius:7px;font-size:.78rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;transition:.15s;cursor:pointer;font-family:inherit;}
.btn-danger-sm:hover{background:#dc2626;color:#fff;}
.btn-ghost-si{background:transparent;color:var(--muted);border:1px solid var(--border);padding:.3rem .75rem;border-radius:7px;font-size:.78rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;transition:.15s;}
.btn-ghost-si:hover{border-color:var(--primary);color:var(--primary);background:var(--green-50);}

/* ── BADGES ── */
.badge{padding:.22rem .7rem;border-radius:20px;font-size:.72rem;font-weight:700;display:inline-flex;align-items:center;gap:4px;}
.badge-green,.bg-green,.badge-success{background:var(--green-100);color:var(--green-800);border:1px solid var(--green-200);}
.badge-yellow,.bg-amber,.badge-warning{background:#fef9c3;color:#92400e;border:1px solid #fde68a;}
.badge-red,.bg-red,.badge-danger{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;}
.badge-blue,.bg-blue,.badge-info{background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;}
.badge-gray,.bg-slate{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;}

/* ── TABLE ── */
.table-card{background:var(--surface);border-radius:14px;border:1px solid var(--border);overflow:hidden;}
.table-card-header{padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
.table-card-header h5{font-weight:700;font-size:.9rem;margin:0;}
.tbl{width:100%;border-collapse:collapse;}
.tbl thead th{background:var(--green-50);padding:.7rem 1rem;text-align:left;font-size:.7rem;font-weight:700;color:var(--green-700);letter-spacing:.06em;text-transform:uppercase;border-bottom:1px solid var(--green-100);}
.tbl tbody td{padding:.825rem 1rem;border-bottom:1px solid #f8fafc;vertical-align:middle;font-size:.875rem;}
.tbl tbody tr:last-child td{border-bottom:none;}
.tbl tbody tr:hover td{background:var(--green-50);}

/* ── FORM ── */
.form-card{background:var(--surface);border-radius:14px;border:1px solid var(--border);margin-bottom:1.25rem;}
.form-card-header{padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;font-weight:700;font-size:.9rem;display:flex;align-items:center;gap:8px;}
.form-card-body{padding:1.25rem;}
.form-group{margin-bottom:1rem;}
.form-group label,.form-label{display:block;font-weight:600;font-size:.82rem;margin-bottom:.35rem;color:#374151;}
.form-control,.form-select{width:100%;padding:.6rem .9rem;border:1.5px solid var(--border);border-radius:9px;font-size:.875rem;outline:none;transition:.18s;font-family:inherit;background:#fff;}
.form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-glow);}
textarea.form-control{resize:vertical;min-height:85px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;}
.form-hint{font-size:.74rem;color:var(--muted);margin-top:3px;}
@media(max-width:600px){.form-grid{grid-template-columns:1fr;}}

/* ── ALERTS ── */
.alert-ok,.alert-success-custom{background:var(--green-50);color:var(--green-800);border:1px solid var(--green-200);padding:.85rem 1rem;border-radius:9px;font-size:.875rem;margin-bottom:1rem;display:flex;align-items:center;gap:8px;}
.alert-err,.alert-error-custom{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;padding:.85rem 1rem;border-radius:9px;font-size:.875rem;margin-bottom:1rem;}

/* ── TIMELINE ── */
.tl{position:relative;padding-left:28px;}
.tl::before{content:'';position:absolute;left:7px;top:6px;bottom:6px;width:2px;background:var(--border);}
.tl-item{position:relative;margin-bottom:1rem;}
.tl-dot{position:absolute;left:-23px;top:13px;width:12px;height:12px;border-radius:50%;border:2.5px solid #fff;box-shadow:0 0 0 2px var(--border);}
.tl-dot.ok{background:#22c55e;box-shadow:0 0 0 2px #22c55e;}
.tl-dot.pend{background:#eab308;box-shadow:0 0 0 2px #eab308;}
.tl-dot.tolak{background:#ef4444;box-shadow:0 0 0 2px #ef4444;}
.tl-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:.9rem 1.1rem;}
.tl-card:hover{border-color:var(--primary);box-shadow:0 4px 12px rgba(21,128,61,.1);}
.tl-meta{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem;flex-wrap:wrap;gap:6px;}
.tl-date{font-weight:700;font-size:.875rem;}
.tl-body{font-size:.875rem;color:#374151;line-height:1.6;white-space:pre-wrap;}
.tl-note{background:var(--green-50);border-left:3px solid #22c55e;padding:6px 10px;border-radius:0 6px 6px 0;margin-top:.6rem;font-size:.82rem;color:#166534;}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:3rem 2rem;color:var(--muted);}
.empty-state svg,.empty-state i{font-size:2.5rem;opacity:.3;display:block;margin:0 auto .75rem;}

/* ── WELCOME BANNER ── */
.hero,.welcome-banner{
  background:linear-gradient(135deg,var(--green-800),var(--green-600));
  border-radius:14px;padding:1.6rem 2rem;color:#fff;position:relative;overflow:hidden;margin-bottom:1.25rem;
}
.hero h1,.welcome-banner h1{font-size:1.35rem;font-weight:800;}
.hero p,.welcome-banner p{opacity:.7;font-size:.875rem;margin-top:4px;}
.hero-ring{position:absolute;right:-50px;top:-50px;width:180px;height:180px;border-radius:50%;border:30px solid rgba(255,255,255,.07);}
.hero-ring2{position:absolute;right:30px;bottom:-60px;width:140px;height:140px;border-radius:50%;border:25px solid rgba(255,255,255,.05);}

/* ── BURGER & OVERLAY ── */
.btn-burger{display:none;background:none;border:none;font-size:1.4rem;cursor:pointer;padding:4px 8px;border-radius:8px;line-height:1;color:var(--text);}
.btn-burger:hover{background:rgba(0,0,0,.06);}
.sb-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:99;backdrop-filter:blur(2px);}
.sb-overlay.show{display:block;}

/* ── RESPONSIVE ── */
@media(max-width:768px){
  :root{--sidebar-w:0px;}
  .sidebar{transform:translateX(-100%);transition:transform .28s cubic-bezier(.4,0,.2,1);z-index:100;}
  .sidebar.open{transform:translateX(0);box-shadow:4px 0 24px rgba(0,0,0,.2);width:260px;}
  .main{margin-left:0;}
  .topbar{left:0;}
  .content{padding:1rem;}
  .btn-burger{display:inline-flex;align-items:center;justify-content:center;}
}
@media(max-width:480px){.content{padding:.75rem;}}

/* ── SCROLLBAR ── */
::-webkit-scrollbar{width:6px;height:6px;}
::-webkit-scrollbar-track{background:transparent;}
::-webkit-scrollbar-thumb{background:var(--green-200);border-radius:6px;}
::-webkit-scrollbar-thumb:hover{background:var(--green-400);}

/* ── ANIMATIONS ── */
@keyframes up{from{opacity:0;transform:translateY(12px);}to{opacity:1;transform:translateY(0);}}
.fade,.fade-in{animation:up .35s ease;}
</style>
</head>
<body>
<div class="sb-overlay" id="sbOverlay"></div>

<div class="sidebar">
  <div class="sb-logo-area">
    <img src="../../assets/images/logo-sekolah.png" alt="SMKN3"
         onerror="this.style.display='none'">
    <div><div class="sb-title">SMKN 3 Padang</div><div class="sb-sub">SiPrakerin · Instruktur</div></div>
  </div>
  <div class="sb-nav">
    <div class="sb-group-label">Menu</div>
    <a href="../dashboard/index.php" class="sb-item <?= instrActive('dashboard') ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <div class="sb-group-label">Bimbingan</div>
    <a href="../monitoring/index.php" class="sb-item <?= instrActive('monitoring') ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/></svg>
      Daftar Siswa
    </a>
    <a href="../jurnal/index.php" class="sb-item <?= instrActive('/instruktur/jurnal') ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/></svg>
      Jurnal Masuk
    </a>
    <div class="sb-group-label">Prakerin</div>
    <a href="../prakerin/index.php" class="sb-item <?= instrActive('/instruktur/prakerin/index') ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><path d="M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9c1.86 0 3.59.57 5.03 1.53"/><path d="M18 2v4h4"/></svg>
      Aktivasi Prakerin
    </a>
    <a href="../prakerin/jam_absen.php" class="sb-item <?= instrActive('jam_absen') ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      Jam Absen
    </a>
    <div class="sb-group-label">Akun</div>
    <a href="../profil/index.php" class="sb-item <?= instrActive('profil') ?>">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
      Profil Saya
    </a>
  </div>
  <div class="sb-user-block">
    <div class="sb-user-card">
      <div class="sb-ava"><?= strtoupper(substr($_SESSION['nama']??'I',0,1)) ?></div>
      <div><div class="sb-un"><?= htmlspecialchars(substr($_SESSION['nama']??'',0,16)) ?></div><div class="sb-ur">Instruktur DU/DI</div></div>
    </div>
    <a href="../../auth/logout.php" class="sb-logout-btn">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Logout
    </a>
  </div>
</div>

<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:10px;">
      <button class="btn-burger" id="burgerBtn" onclick="openSidebar()">&#9776;</button>
      <div class="topbar-title"><?= $page_title??'Dashboard' ?></div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;">
      <button class="dm-toggle" onclick="toggleDarkMode()" title="Mode Gelap">🌙</button>
      <span class="topbar-pill d-none d-md-inline">Instruktur DU/DI</span>
      <a href="../../auth/logout.php"
         style="display:none;align-items:center;gap:6px;padding:6px 12px;border-radius:8px;
                background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);
                font-size:.82rem;font-weight:600;text-decoration:none;" id="mobileLogout">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Logout
      </a>
    </div>
  </div>
  <div class="content fade">
<script>
function openSidebar(){
  document.querySelector('.sidebar').classList.add('open');
  document.getElementById('sbOverlay').classList.add('show');
}
function closeSidebar(){
  document.querySelector('.sidebar').classList.remove('open');
  document.getElementById('sbOverlay').classList.remove('show');
}
document.getElementById('sbOverlay').addEventListener('click', closeSidebar);
// Show mobile logout button
if(window.innerWidth<=768){
  var ml=document.getElementById('mobileLogout');
  if(ml) ml.style.display='inline-flex';
}
</script>
<script src="../../assets/darkmode.js"></script>
