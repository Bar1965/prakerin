<?php
session_start();
if (isset($_SESSION['login'])) {
    header("Location: ../index.php"); exit;
}
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk — SiPrakerin SMKN 3 Padang</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --green-900:#14532d; --green-800:#166534; --green-700:#15803d;
  --green-600:#16a34a; --green-500:#22c55e; --green-100:#dcfce7;
  --green-50:#f0fdf4; --text:#0f172a; --muted:#64748b;
  --border:#e2e8f0; --danger:#dc2626; --danger-bg:#fef2f2;
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
body {
  font-family:'Plus Jakarta Sans',sans-serif; color:var(--text);
  min-height:100vh; display:grid; grid-template-columns:1fr 1fr;
}
a{text-decoration:none;color:inherit;}

/* ── PANEL KIRI (branding) ── */
.panel-left {
  background:linear-gradient(160deg, var(--green-800) 0%, var(--green-600) 100%);
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  padding:3rem; text-align:center; position:relative; overflow:hidden;
}
.panel-left::before {
  content:''; position:absolute; top:-20%; left:-20%;
  width:500px; height:500px; border-radius:50%;
  background:rgba(255,255,255,.05);
}
.panel-left::after {
  content:''; position:absolute; bottom:-15%; right:-15%;
  width:380px; height:380px; border-radius:50%;
  border:2px solid rgba(255,255,255,.08);
}
.brand-logo {
  width:100px; height:100px; object-fit:contain;
  filter:drop-shadow(0 4px 16px rgba(0,0,0,.3));
  margin-bottom:1.5rem; position:relative; z-index:1;
  animation:logoIn .8s ease both;
}
.brand-logo-fallback {
  width:90px; height:90px; background:rgba(255,255,255,.15);
  border-radius:20px; display:flex; align-items:center; justify-content:center;
  font-size:2.5rem; margin:0 auto 1.5rem; position:relative; z-index:1;
}
.brand-name {
  color:#fff; font-size:2rem; font-weight:800;
  letter-spacing:-.03em; position:relative; z-index:1;
  animation:fadeUp .6s .1s ease both;
}
.brand-school {
  color:rgba(255,255,255,.7); font-size:.85rem; font-weight:500;
  margin:.4rem 0 2rem; position:relative; z-index:1;
  animation:fadeUp .6s .2s ease both;
}
.brand-divider {
  width:40px; height:2px; background:rgba(255,255,255,.25);
  border-radius:2px; margin:.75rem auto;
}
.brand-tagline {
  color:rgba(255,255,255,.8); font-size:.88rem; line-height:1.7;
  max-width:280px; position:relative; z-index:1;
  animation:fadeUp .6s .3s ease both;
}
.brand-features {
  display:flex; flex-direction:column; gap:.6rem;
  margin-top:2rem; position:relative; z-index:1;
  animation:fadeUp .6s .4s ease both;
}
.brand-feat {
  display:flex; align-items:center; gap:.65rem;
  color:rgba(255,255,255,.85); font-size:.82rem;
  background:rgba(255,255,255,.08); border-radius:8px;
  padding:.5rem .85rem; border:1px solid rgba(255,255,255,.1);
}

/* ── PANEL KANAN (form) ── */
.panel-right {
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  padding:3rem 2.5rem; background:#fafafa;
}
.form-wrap { width:100%; max-width:380px; }
.back-link {
  display:inline-flex; align-items:center; gap:5px;
  color:var(--muted); font-size:.8rem; font-weight:500;
  margin-bottom:2rem; transition:.15s;
}
.back-link:hover { color:var(--green-700); }
.form-title { font-size:1.6rem; font-weight:800; letter-spacing:-.025em; margin-bottom:.3rem; }
.form-sub   { color:var(--muted); font-size:.875rem; margin-bottom:2rem; }

.alert-err {
  background:var(--danger-bg); color:var(--danger);
  border:1px solid #fecaca; border-radius:9px;
  padding:.75rem 1rem; font-size:.85rem; font-weight:600;
  display:flex; align-items:center; gap:7px; margin-bottom:1.25rem;
}
.form-group { margin-bottom:1.15rem; }
label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.4rem; }
.input-wrap { position:relative; }
.input-wrap svg { position:absolute; left:.85rem; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none; }
input[type=text], input[type=password] {
  width:100%; padding:.7rem 1rem .7rem 2.5rem;
  border:1.5px solid var(--border); border-radius:9px;
  font-family:inherit; font-size:.9rem; color:var(--text);
  transition:.18s; background:#fff; outline:none;
}
input:focus { border-color:var(--green-600); box-shadow:0 0 0 3px rgba(22,163,74,.12); }
.eye-btn {
  position:absolute; right:.85rem; top:50%; transform:translateY(-50%);
  background:none; border:none; cursor:pointer; color:var(--muted);
  padding:2px; transition:.15s;
}
.eye-btn:hover { color:var(--text); }

.remember-row {
  display:flex; align-items:center; gap:7px;
  font-size:.83rem; color:var(--muted); margin-bottom:1.25rem; cursor:pointer;
}
.remember-row input { accent-color:var(--green-600); width:15px; height:15px; cursor:pointer; }

.btn-login {
  width:100%; background:var(--green-700); color:#fff;
  border:none; padding:.82rem; border-radius:9px;
  font-family:inherit; font-size:.95rem; font-weight:700;
  cursor:pointer; transition:.2s; display:flex; align-items:center; justify-content:center; gap:7px;
  box-shadow:0 4px 14px rgba(21,128,61,.25);
}
.btn-login:hover { background:var(--green-800); transform:translateY(-1px); box-shadow:0 6px 18px rgba(21,128,61,.3); }

.form-footer { text-align:center; margin-top:2rem; color:var(--muted); font-size:.8rem; }

@keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
@keyframes logoIn { from{opacity:0;transform:scale(.7)} to{opacity:1;transform:scale(1)} }

/* ── MOBILE ── */
@media(max-width:700px){
  body { grid-template-columns:1fr; }
  .panel-left { padding:2.5rem 2rem; min-height:220px; }
  .brand-features { display:none; }
  .panel-right { padding:2rem 1.5rem; }
}
</style>
</head>
<body>

<!-- PANEL KIRI -->
<div class="panel-left">
  <img src="../assets/images/logo-sekolah.png" alt="Logo SMKN 3 Padang" class="brand-logo"
       onerror="this.style.display='none';document.querySelector('.brand-logo-fallback').style.display='flex'">
  <div class="brand-logo-fallback" style="display:none">🏫</div>
  <div class="brand-name">SiPrakerin</div>
  <div class="brand-school">SMK Negeri 3 Padang</div>
  <div class="brand-divider"></div>
  <div class="brand-tagline">Sistem Informasi Praktik Kerja Lapangan — monitoring digital yang mudah dan terintegrasi.</div>
  <div class="brand-features">
    <div class="brand-feat">📋 Absensi & Jurnal Digital</div>
    <div class="brand-feat">🔔 Notifikasi WhatsApp</div>
    <div class="brand-feat">🖨️ Export PDF Siap Cetak</div>
    <div class="brand-feat">📊 Dashboard & Grafik</div>
  </div>
</div>

<!-- PANEL KANAN -->
<div class="panel-right">
  <div class="form-wrap">
    <a href="../index.php" class="back-link">
      <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      Kembali ke Beranda
    </a>

    <div class="form-title">Selamat Datang 👋</div>
    <div class="form-sub">Masuk ke portal SiPrakerin SMKN 3 Padang</div>

    <?php if ($error): ?>
    <div class="alert-err">
      <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      Username atau password salah. Coba lagi.
    </div>
    <?php endif; ?>

    <form action="login_proses.php" method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <div class="input-wrap">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <input type="text" id="username" name="username" placeholder="Masukkan username"
                 value="<?= htmlspecialchars($_COOKIE['remember_username'] ?? '') ?>"
                 required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-wrap">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          <input type="password" id="password" name="password" placeholder="••••••••" required>
          <button type="button" class="eye-btn" onclick="togglePwd()" id="eyeBtn">
            <svg id="eyeIcon" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <label class="remember-row">
        <input type="checkbox" name="remember_me" value="1"
               <?= isset($_COOKIE['remember_username']) ? 'checked' : '' ?>>
        Ingat saya di perangkat ini
      </label>

      <button type="submit" class="btn-login">
        Masuk ke Sistem
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
      </button>
    </form>

    <div class="form-footer">
      &copy; <?= date('Y') ?> SMK Negeri 3 Padang &nbsp;·&nbsp; SiPrakerin
    </div>
  </div>
</div>

<script>
function togglePwd() {
  const inp = document.getElementById('password');
  const ico = document.getElementById('eyeIcon');
  if (inp.type === 'password') {
    inp.type = 'text';
    ico.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>';
  } else {
    inp.type = 'password';
    ico.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
  }
}
</script>
</body>
</html>
