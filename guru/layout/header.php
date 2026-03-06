<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'SiPrakerin' ?> - Portal Guru</title>
    <!-- Dark Mode: terapkan sebelum CSS lain agar tidak flash -->
    <script>
    (function(){var t=localStorage.getItem('siprakerin_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/darkmode.css">
    <style>
        :root {
            --primary: #15803d; --primary-dark: #166534;
            --green: #10b981; --amber: #f59e0b; --red: #ef4444;
            --bg-body: #f0fdf4; --text-main: #0f172a;
            --text-muted: #64748b; --border: #e2e8f0;
            --sidebar-width: 260px;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; }
        body { background: var(--bg-body); color: var(--text-main); font-size: 0.95rem; }
        a { text-decoration: none; color: inherit; transition: 0.2s; }

        .wrapper { display: flex; min-height: 100vh; }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width); background: white;
            border-right: 1px solid var(--border);
            position: fixed; height: 100vh; top: 0; left: 0;
            display: flex; flex-direction: column; z-index: 50;
            overflow-y: auto;
        }
        .sidebar-header {
            padding: 1.25rem 1.5rem; display: flex; align-items: center; gap: 12px;
            border-bottom: 1px solid var(--border); flex-shrink: 0;
        }
        .sidebar-menu { padding: 1rem; flex: 1; }
        .menu-label {
            margin: 12px 0 4px 12px; font-size: 0.7rem;
            color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .menu-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 14px; margin-bottom: 2px;
            color: var(--text-muted); border-radius: 8px; font-weight: 500; font-size: 0.9rem;
        }
        .menu-item:hover { background: #f0fdf4; color: var(--primary); }
        .menu-item.active { background: #eff6ff; color: var(--primary); font-weight: 600; }
        .menu-item svg { flex-shrink: 0; }
        .sidebar-footer { padding: 1rem; border-top: 1px solid var(--border); flex-shrink: 0; }

        /* MAIN */
        .main-content {
            flex: 1; margin-left: var(--sidebar-width);
            padding: 2rem; display: flex; flex-direction: column; min-height: 100vh;
        }
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border);
        }

        /* UTILITIES */
        .btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 0.6rem 1.25rem; border-radius: 8px; font-weight: 600;
            font-size: 0.9rem; border: none; cursor: pointer; transition: 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-1px); }
        .btn-success { background: var(--green); color: white; }
        .btn-success:hover { background: #059669; }
        .btn-outline { background: white; color: var(--text-main); border: 1px solid var(--border); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        .card { background: white; border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem; }

        .badge { padding: 3px 10px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef9c3; color: #854d0e; }
        .badge-danger  { background: #fee2e2; color: #991b1b; }
        .badge-neutral { background: #f0fdf4; color: #64748b; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .page-header h1 { font-size: 1.5rem; font-weight: 700; }
        .page-header p { color: #64748b; font-size: 0.9rem; margin-top: 2px; }

        .form-group { margin-bottom: 1.25rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; }
        .form-control {
            width: 100%; padding: 0.6rem 0.9rem; border: 1px solid #cbd5e1;
            border-radius: 8px; font-size: 0.95rem; outline: none; transition: 0.2s; background: white;
        }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(21,128,61,0.1); }
        textarea.form-control { resize: vertical; min-height: 90px; }

        .alert-success { background: #f0fdf4; color: #166534; padding: 1rem; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; border: 1px solid #fee2e2; margin-bottom: 1rem; }

        @keyframes fadeIn { from { opacity:0; transform:translateY(16px); } to { opacity:1; transform:translateY(0); } }
        .fade-in { animation: fadeIn 0.5s ease; }

        /* ── BURGER BUTTON ── */
        .btn-burger {
            display: none; background: none; border: none;
            font-size: 1.5rem; cursor: pointer; padding: 4px 8px;
            border-radius: 8px; line-height: 1; color: var(--text-main);
        }
        .btn-burger:hover { background: #f0fdf4; }

        /* ── OVERLAY ── */
        .sb-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 45;
            backdrop-filter: blur(2px);
        }
        .sb-overlay.show { display: block; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            :root { --sidebar-width: 0px; }
            .sidebar {
                transform: translateX(-100%);
                transition: transform .28s cubic-bezier(.4,0,.2,1);
                z-index: 50;
            }
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 4px 0 24px rgba(0,0,0,.2);
                width: 260px;
            }
            .main-content { margin-left: 0; padding: 1rem; }
            .btn-burger { display: inline-flex; align-items: center; justify-content: center; }
        }
        @media (max-width: 480px) {
            .main-content { padding: .75rem; }
        }
    </style>
</head>
<body>
<div class="sb-overlay" id="sbOverlay"></div>
<div class="wrapper">

<?php
function guruIsActive($keyword) {
    $url = $_SERVER['REQUEST_URI'];
    return strpos($url, $keyword) !== false ? 'active' : '';
}
?>

<div class="sidebar">
    <div class="sidebar-header">
        <img src="../../assets/images/logo-sekolah.png" alt="SMKN3"
             style="width:40px;height:40px;object-fit:contain;border-radius:8px;flex-shrink:0;"
             onerror="this.style.display='none'">
        <div>
            <div style="font-weight:700;color:var(--primary);font-size:0.95rem;line-height:1.2;">SMKN 3 Padang</div>
            <div style="font-size:0.68rem;color:#64748b;">SiPrakerin · Guru</div>
        </div>
    </div>

    <div class="sidebar-menu">
        <a href="../dashboard/index.php" class="menu-item <?= guruIsActive('dashboard') ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            Dashboard
        </a>

        <div class="menu-label">Bimbingan PKL</div>

        <a href="../monitoring/index.php" class="menu-item <?= guruIsActive('monitoring') ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Daftar Siswa
        </a>

        <a href="../jurnal/index.php" class="menu-item <?= guruIsActive('/guru/jurnal') ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
            Monitoring Jurnal
        </a>

        <div class="menu-label">Akun Saya</div>

        <a href="../profil/index.php" class="menu-item <?= guruIsActive('profil') ?>">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            Profil Saya
        </a>
    </div>

    <div class="sidebar-footer">
        <div style="background:#f0fdf4;border-radius:10px;padding:12px;margin-bottom:10px;display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.9rem;flex-shrink:0;">
                <?= substr($_SESSION['nama'] ?? 'G', 0, 1) ?>
            </div>
            <div style="overflow:hidden;">
                <div style="font-weight:600;font-size:0.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></div>
                <div style="font-size:0.72rem;color:#64748b;">Guru Pembimbing</div>
            </div>
        </div>
        <a href="../../auth/logout.php" class="menu-item btn-logout" style="color:#ef4444;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
            Logout
        </a>
    </div>
</div>

<div class="main-content">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:10px;">
            <button class="btn-burger" id="burgerBtn" onclick="openSidebar()" aria-label="Buka menu">&#9776;</button>
            <div style="font-weight:600;color:var(--text-muted);font-size:0.9rem;">
                <?= $page_title ?? 'Portal Guru' ?>
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <!-- Dark Mode Toggle -->
            <button class="dm-toggle" onclick="toggleDarkMode()" title="Mode Gelap">🌙</button>
            <!-- Bell Notifikasi -->
            <div style="position:relative;" id="notifWrap">
              <button id="notifBtn" onclick="toggleNotif()" style="background:none;border:none;cursor:pointer;padding:6px;border-radius:8px;position:relative;color:#374151;font-size:1.2rem;transition:.15s;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='none'">
                🔔
                <span id="notifBadge" style="display:none;position:absolute;top:2px;right:2px;background:#ef4444;color:#fff;border-radius:50%;width:17px;height:17px;font-size:.62rem;font-weight:700;line-height:17px;text-align:center;"></span>
              </button>
              <div id="notifDropdown" style="display:none;position:absolute;right:0;top:calc(100% + 8px);width:300px;background:#fff;border:1px solid #e2e8f0;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.12);z-index:2000;overflow:hidden;">
                <div style="padding:.85rem 1rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
                  <span style="font-weight:700;font-size:.88rem;">Notifikasi</span>
                  <button onclick="bacaSemua()" style="background:none;border:none;cursor:pointer;font-size:.73rem;color:var(--primary);font-weight:600;">Tandai semua dibaca</button>
                </div>
                <div id="notifList" style="max-height:320px;overflow-y:auto;"></div>
              </div>
            </div>
            <a href="../profil/index.php" style="display:flex;align-items:center;gap:8px;padding:6px 12px;border-radius:8px;border:1px solid var(--border);background:white;">
                <div style="width:32px;height:32px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center;color:white;font-weight:700;font-size:0.85rem;">
                    <?= substr($_SESSION['nama'] ?? 'G', 0, 1) ?>
                </div>
                <span style="font-weight:500;font-size:0.9rem;" class="d-none-mobile"><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
            </a>
            <!-- Logout — hanya muncul di mobile -->
            <a href="../../auth/logout.php" class="btn-logout btn-logout-mobile"
               style="display:none;align-items:center;gap:6px;padding:6px 12px;
                      border-radius:8px;background:#fef2f2;color:#ef4444;border:1px solid #fecaca;
                      font-size:0.82rem;font-weight:600;text-decoration:none;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Logout
            </a>
        </div>
    </div>
<style>
@media(max-width:768px){
  .btn-logout-mobile { display:inline-flex !important; }
  .d-none-mobile { display:none !important; }
}
</style>

<script src="../../assets/darkmode.js"></script>
