<?php
session_start();
require 'config/database.php';

// Redirect jika sudah login
if (!empty($_SESSION['login']) && !empty($_SESSION['role'])) {
    $map = ['admin'=>'admin/dashboard/index.php','siswa'=>'siswa/dashboard.php',
            'instruktur'=>'instruktur/dashboard/index.php','guru'=>'guru/dashboard/index.php'];
    header("Location: ".($map[$_SESSION['role']]??'auth/login.php')); exit;
}

// Statistik real dari DB
function safeCount($conn, $sql) {
    $r = mysqli_query($conn, $sql);
    return $r ? (int)(mysqli_fetch_assoc($r)['n'] ?? 0) : 0;
}
$st_siswa   = safeCount($conn, "SELECT COUNT(*) n FROM siswa");
$st_tempat  = safeCount($conn, "SELECT COUNT(*) n FROM tempat_pkl");
$st_jurnal  = safeCount($conn, "SELECT COUNT(*) n FROM jurnal WHERE status='disetujui'");
$st_hadir   = safeCount($conn, "SELECT COUNT(*) n FROM absensi WHERE status='hadir'");
$pct_hadir  = 0;
$total_abs  = safeCount($conn, "SELECT COUNT(*) n FROM absensi");
if ($total_abs > 0) $pct_hadir = round($st_hadir / $total_abs * 100);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SiPrakerin — SMK Negeri 3 Padang</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
:root {
  --green-900: #14532d;
  --green-800: #166534;
  --green-700: #15803d;
  --green-600: #16a34a;
  --green-500: #22c55e;
  --green-400: #4ade80;
  --green-100: #dcfce7;
  --green-50:  #f0fdf4;
  --gold:      #f59e0b;
  --gold-light:#fef3c7;
  --text:      #0f172a;
  --muted:     #64748b;
  --border:    #e2e8f0;
  --white:     #ffffff;
  --bg:        #f8fafb;
}

*, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
html { scroll-behavior:smooth; }
body { font-family:'Plus Jakarta Sans',sans-serif; color:var(--text); background:var(--bg); overflow-x:hidden; }
a { text-decoration:none; color:inherit; }

/* ─── NAVBAR ─── */
.nav {
  position:fixed; top:0; left:0; right:0; z-index:100;
  padding:0 2rem;
  background:rgba(255,255,255,.88);
  backdrop-filter:blur(16px);
  border-bottom:1px solid rgba(22,163,74,.12);
  display:flex; align-items:center; justify-content:space-between;
  height:64px;
}
.nav-brand { display:flex; align-items:center; gap:10px; }
.nav-brand img { width:48px; height:48px; object-fit:contain; }
.nav-brand-text { line-height:1.1; }
.nav-brand-text .name { font-weight:800; font-size:.95rem; color:var(--green-800); }
.nav-brand-text .sub  { font-size:.68rem; color:var(--muted); letter-spacing:.02em; }
.nav-links { display:flex; align-items:center; gap:2rem; }
.nav-links a { font-size:.875rem; font-weight:500; color:var(--muted); transition:.15s; }
.nav-links a:hover { color:var(--green-700); }
.btn-masuk {
  background:var(--green-700); color:#fff !important;
  padding:.55rem 1.4rem; border-radius:8px; font-weight:700;
  font-size:.875rem; transition:.2s;
  box-shadow:0 2px 8px rgba(21,128,61,.25);
}
.btn-masuk:hover { background:var(--green-800); transform:translateY(-1px); box-shadow:0 4px 14px rgba(21,128,61,.3); }
.hamburger { display:none; background:none; border:none; cursor:pointer; padding:4px; }
.hamburger span { display:block; width:22px; height:2.5px; background:var(--text); border-radius:2px; margin:4px 0; transition:.25s; }

/* ─── HERO ─── */
.hero {
  min-height:100vh;
  background:
    radial-gradient(ellipse 80% 60% at 50% -10%, rgba(22,163,74,.13) 0%, transparent 70%),
    radial-gradient(ellipse 40% 40% at 90% 80%, rgba(245,158,11,.08) 0%, transparent 60%),
    var(--bg);
  display:flex; flex-direction:column; align-items:center; justify-content:center;
  padding:100px 2rem 60px;
  text-align:center; position:relative; overflow:hidden;
}

/* Dekorasi background */
.hero::before {
  content:'';
  position:absolute; top:10%; right:-5%; width:420px; height:420px;
  border:1.5px solid rgba(22,163,74,.12); border-radius:50%;
  animation:spin 30s linear infinite;
}
.hero::after {
  content:'';
  position:absolute; bottom:5%; left:-8%; width:300px; height:300px;
  border:1.5px dashed rgba(22,163,74,.1); border-radius:50%;
  animation:spin 20s linear infinite reverse;
}
@keyframes spin { to { transform:rotate(360deg); } }

.hero-badge {
  display:inline-flex; align-items:center; gap:7px;
  background:var(--green-100); color:var(--green-800);
  border:1px solid rgba(22,163,74,.25); border-radius:20px;
  padding:.4rem 1rem; font-size:.78rem; font-weight:700;
  margin-bottom:1.75rem; letter-spacing:.03em;
  animation:fadeUp .6s ease both;
}
.hero-badge::before { content:'●'; color:var(--green-500); font-size:.6rem; }

.hero h1 {
  font-size:clamp(2.4rem, 6vw, 4.2rem);
  font-weight:800; line-height:1.08;
  letter-spacing:-.03em; margin-bottom:1.5rem;
  animation:fadeUp .6s .1s ease both;
}
.hero h1 .line2 {
  display:block;
  background:linear-gradient(135deg,var(--green-700),var(--green-500));
  -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text;
}

.hero-desc {
  font-size:1.05rem; color:var(--muted); max-width:520px;
  line-height:1.7; margin:0 auto 2.25rem;
  animation:fadeUp .6s .2s ease both;
}

.hero-cta {
  display:flex; gap:12px; justify-content:center; flex-wrap:wrap;
  animation:fadeUp .6s .3s ease both;
}
.btn-hero-primary {
  background:var(--green-700); color:#fff;
  padding:.9rem 2.25rem; border-radius:10px;
  font-weight:700; font-size:1rem;
  display:inline-flex; align-items:center; gap:8px;
  box-shadow:0 4px 16px rgba(21,128,61,.3);
  transition:.2s;
}
.btn-hero-primary:hover { background:var(--green-800); transform:translateY(-2px); box-shadow:0 8px 24px rgba(21,128,61,.35); }
.btn-hero-outline {
  background:transparent; color:var(--text);
  border:1.5px solid var(--border); padding:.9rem 1.75rem;
  border-radius:10px; font-weight:600; font-size:1rem;
  display:inline-flex; align-items:center; gap:8px;
  transition:.2s;
}
.btn-hero-outline:hover { border-color:var(--green-500); color:var(--green-700); background:var(--green-50); }

/* ─── STATS BAR ─── */
.stats-bar {
  display:flex; justify-content:center; gap:0; flex-wrap:wrap;
  margin-top:4rem; max-width:720px; margin-inline:auto;
  background:#fff; border:1px solid var(--border);
  border-radius:16px; overflow:hidden;
  box-shadow:0 4px 20px rgba(0,0,0,.06);
  animation:fadeUp .6s .4s ease both;
}
.stat-item {
  flex:1; min-width:140px; padding:1.4rem 1.25rem;
  border-right:1px solid var(--border); text-align:center;
  position:relative; transition:.15s;
}
.stat-item:last-child { border-right:none; }
.stat-item:hover { background:var(--green-50); }
.stat-num {
  font-size:2rem; font-weight:800; color:var(--green-700);
  line-height:1; font-family:'DM Mono',monospace;
  display:block;
}
.stat-label { font-size:.72rem; color:var(--muted); font-weight:600; margin-top:4px; letter-spacing:.03em; text-transform:uppercase; display:block; }
.stat-item .stat-badge {
  position:absolute; top:8px; right:8px;
  background:var(--green-100); color:var(--green-800);
  font-size:.6rem; font-weight:700; padding:.15rem .4rem;
  border-radius:4px;
}

/* ─── SEKOLAH SECTION ─── */
.sekolah {
  padding:6rem 2rem;
  background:#fff;
  position:relative;
}
.sekolah-inner {
  max-width:1080px; margin:0 auto;
  display:grid; grid-template-columns:1fr 1fr; gap:4rem; align-items:center;
}
.sekolah-visual {
  position:relative;
}
.sekolah-card-main {
  background:linear-gradient(135deg,var(--green-800),var(--green-600));
  border-radius:20px; padding:2.5rem;
  color:#fff; text-align:center;
  box-shadow:0 20px 50px rgba(22,163,74,.25);
}
.sekolah-card-main img {
  width:120px; height:120px; object-fit:contain;
  margin-bottom:1rem;
  filter:drop-shadow(0 2px 8px rgba(0,0,0,.15));
}
.sekolah-card-main h3 { font-size:1.1rem; font-weight:800; line-height:1.3; }
.sekolah-card-main p  { font-size:.82rem; opacity:.8; margin-top:.5rem; }

.floating-chip {
  position:absolute; background:#fff; border:1px solid var(--border);
  border-radius:12px; padding:.7rem 1rem;
  box-shadow:0 8px 20px rgba(0,0,0,.08);
  display:flex; align-items:center; gap:8px;
  font-size:.8rem; font-weight:600; white-space:nowrap;
}
.chip-1 { top:-16px; right:-20px; animation:float 4s ease-in-out infinite; }
.chip-2 { bottom:-12px; left:-20px; animation:float 4s 1.5s ease-in-out infinite; }
@keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.chip-dot { width:8px; height:8px; border-radius:50%; }

.sekolah-info { }
.sekolah-tag {
  display:inline-block; background:var(--green-100); color:var(--green-800);
  font-size:.72rem; font-weight:700; padding:.3rem .85rem;
  border-radius:20px; letter-spacing:.04em; text-transform:uppercase;
  margin-bottom:1rem; border:1px solid rgba(22,163,74,.2);
}
.sekolah-info h2 {
  font-size:2rem; font-weight:800; line-height:1.2;
  letter-spacing:-.025em; margin-bottom:1rem;
}
.sekolah-info h2 span { color:var(--green-700); }
.sekolah-info p {
  color:var(--muted); line-height:1.75; font-size:.95rem;
  margin-bottom:1.5rem;
}
.visi-misi {
  display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:1.25rem;
}
.vm-card {
  background:var(--green-50); border:1px solid rgba(22,163,74,.15);
  border-radius:10px; padding:.9rem 1rem;
}
.vm-card .vm-title { font-size:.7rem; font-weight:800; color:var(--green-700); letter-spacing:.06em; text-transform:uppercase; margin-bottom:.3rem; }
.vm-card p { font-size:.8rem; color:var(--muted); line-height:1.55; }

/* ─── FITUR SECTION ─── */
.fitur {
  padding:6rem 2rem;
  background:var(--bg);
  text-align:center;
}
.section-tag {
  display:inline-block; background:var(--green-100); color:var(--green-800);
  font-size:.72rem; font-weight:700; padding:.3rem .85rem;
  border-radius:20px; letter-spacing:.04em; text-transform:uppercase;
  margin-bottom:1rem; border:1px solid rgba(22,163,74,.2);
}
.fitur h2 {
  font-size:2rem; font-weight:800; letter-spacing:-.025em; margin-bottom:.75rem;
}
.fitur-desc { color:var(--muted); max-width:480px; margin:0 auto 3rem; font-size:.95rem; line-height:1.7; }
.fitur-grid {
  display:grid; grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
  gap:1.25rem; max-width:1080px; margin:0 auto;
}
.fitur-card {
  background:#fff; border:1px solid var(--border); border-radius:16px;
  padding:1.75rem; text-align:left; transition:.2s;
  position:relative; overflow:hidden;
}
.fitur-card::before {
  content:''; position:absolute; inset:0;
  background:linear-gradient(135deg,var(--green-50) 0%,transparent 60%);
  opacity:0; transition:.25s;
}
.fitur-card:hover { border-color:rgba(22,163,74,.3); transform:translateY(-4px); box-shadow:0 12px 30px rgba(22,163,74,.1); }
.fitur-card:hover::before { opacity:1; }
.fitur-icon {
  width:48px; height:48px; border-radius:12px;
  background:var(--green-100); display:flex; align-items:center; justify-content:center;
  font-size:1.5rem; margin-bottom:1.1rem;
}
.fitur-card h3 { font-size:1rem; font-weight:700; margin-bottom:.5rem; }
.fitur-card p  { font-size:.83rem; color:var(--muted); line-height:1.6; }

/* ─── ROLE SECTION ─── */
.roles {
  padding:5rem 2rem;
  background:linear-gradient(135deg,var(--green-800) 0%,var(--green-600) 100%);
  text-align:center; position:relative; overflow:hidden;
}
.roles::before {
  content:''; position:absolute; top:-50%; left:-20%;
  width:600px; height:600px; border-radius:50%;
  background:rgba(255,255,255,.04);
}
.roles h2 { color:#fff; font-size:1.75rem; font-weight:800; margin-bottom:.5rem; }
.roles-sub { color:rgba(255,255,255,.7); font-size:.9rem; margin-bottom:2.5rem; }
.roles-grid {
  display:flex; justify-content:center; flex-wrap:wrap; gap:1rem;
  max-width:900px; margin:0 auto;
}
.role-card {
  background:rgba(255,255,255,.12); backdrop-filter:blur(8px);
  border:1px solid rgba(255,255,255,.2); border-radius:14px;
  padding:1.75rem 2rem; flex:1; min-width:180px; max-width:220px;
  color:#fff; transition:.2s;
}
.role-card:hover { background:rgba(255,255,255,.2); transform:translateY(-4px); }
.role-emoji { font-size:2.2rem; display:block; margin-bottom:.75rem; }
.role-name { font-weight:700; font-size:1rem; margin-bottom:.4rem; }
.role-desc { font-size:.78rem; opacity:.75; line-height:1.55; }

/* ─── FOOTER ─── */
footer {
  background:var(--green-900); color:rgba(255,255,255,.7);
  padding:2.5rem 2rem; text-align:center;
}
footer .f-brand { display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:1rem; }
footer .f-brand img { width:40px; height:40px; object-fit:contain; opacity:.9; }
footer .f-brand span { font-weight:800; font-size:1rem; color:#fff; }
footer p { font-size:.8rem; line-height:1.6; }
footer .f-divider { width:40px; height:2px; background:rgba(255,255,255,.15); margin:.85rem auto; border-radius:2px; }

/* ─── ANIMASI ─── */
@keyframes fadeUp {
  from { opacity:0; transform:translateY(24px); }
  to   { opacity:1; transform:translateY(0); }
}

/* ─── RESPONSIVE ─── */
@media(max-width:768px){
  .nav-links { display:none; position:fixed; inset:0; background:#fff; flex-direction:column; justify-content:center; align-items:center; gap:2rem; z-index:99; }
  .nav-links.open { display:flex; }
  .hamburger { display:block; z-index:200; position:relative; }
  .sekolah-inner { grid-template-columns:1fr; gap:2.5rem; }
  .sekolah-card-main { max-width:320px; margin:0 auto; }
  .chip-1,.chip-2 { display:none; }
  .visi-misi { grid-template-columns:1fr; }
  .stats-bar { border-radius:12px; }
  .stat-item { padding:1.1rem .9rem; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav">
  <div class="nav-brand">
    <img src="assets/images/logo-sekolah.png" alt="Logo SMKN 3 Padang"
         onerror="this.style.display='none'">
    <div class="nav-brand-text">
      <div class="name">SiPrakerin</div>
      <div class="sub">SMK Negeri 3 Padang</div>
    </div>
  </div>

  <button class="hamburger" id="burgerBtn" onclick="toggleNav()">
    <span></span><span></span><span></span>
  </button>

  <div class="nav-links" id="navLinks">
    <a href="#sekolah" onclick="closeNav()">Tentang Sekolah</a>
    <a href="#fitur"   onclick="closeNav()">Fitur Sistem</a>
    <a href="#roles"   onclick="closeNav()">Pengguna</a>
    <a href="auth/login.php" class="btn-masuk">Masuk ke Sistem</a>
  </div>
</nav>

<!-- HERO -->
<section class="hero" id="home">
  <div class="hero-badge">SISTEM INFORMASI PRAKERIN 2024/2025</div>

  <h1>
    Monitoring PKL<br>
    <span class="line2">SMK Negeri 3 Padang</span>
  </h1>

  <p class="hero-desc">
    Platform digital terintegrasi untuk mengelola Praktik Kerja Lapangan —
    dari absensi, jurnal harian, hingga penilaian akhir siswa.
  </p>

  <div class="hero-cta">
    <a href="auth/login.php" class="btn-hero-primary">
      Masuk ke Sistem
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
    </a>
    <a href="#sekolah" class="btn-hero-outline">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 8 12 12 14 14"/></svg>
      Pelajari Lebih Lanjut
    </a>
  </div>

  <!-- Statistik real -->
  <div class="stats-bar">
    <div class="stat-item">
      <span class="stat-num" id="ctr-siswa">0</span>
      <span class="stat-label">Siswa PKL</span>
    </div>
    <div class="stat-item">
      <span class="stat-num" id="ctr-tempat">0</span>
      <span class="stat-label">Perusahaan</span>
    </div>
    <div class="stat-item">
      <span class="stat-num" id="ctr-jurnal">0</span>
      <span class="stat-label">Jurnal Disetujui</span>
    </div>
    <div class="stat-item">
      <span class="stat-num" id="ctr-hadir">0%</span>
      <span class="stat-label">Tingkat Kehadiran</span>
      <span class="stat-badge">Live</span>
    </div>
  </div>
</section>

<!-- SEKOLAH -->
<section class="sekolah" id="sekolah">
  <div class="sekolah-inner">
    <div class="sekolah-visual">
      <div class="sekolah-card-main">
        <img src="assets/images/logo-sekolah.png" alt="Logo SMKN 3 Padang"
             onerror="this.src='';this.style.fontSize='3rem';this.alt='🏫'">
        <h3>SMK Negeri 3 Padang</h3>
        <p>Jl. Bungo Pasang, Tabing<br>Padang, Sumatera Barat</p>
      </div>
      <div class="floating-chip chip-1">
        <div class="chip-dot" style="background:#22c55e;"></div>
        Sistem Aktif
      </div>
      <div class="floating-chip chip-2">
        🏆 Akreditasi A
      </div>
    </div>

    <div class="sekolah-info">
      <div class="sekolah-tag">Tentang Sekolah</div>
      <h2>Mempersiapkan <span>Generasi Unggul</span> Dunia Industri</h2>
      <p>
        SMK Negeri 3 Padang adalah sekolah kejuruan terkemuka di Sumatera Barat yang berfokus
        pada pengembangan kompetensi teknis dan profesional siswa. Program PKL dirancang untuk
        menjembatani dunia pendidikan dengan dunia industri nyata.
      </p>
      <div class="visi-misi">
        <div class="vm-card">
          <div class="vm-title">🎯 Visi</div>
          <p>Menjadi sekolah kejuruan unggulan yang menghasilkan lulusan berkarakter, kompeten, dan berdaya saing global.</p>
        </div>
        <div class="vm-card">
          <div class="vm-title">🚀 Misi PKL</div>
          <p>Menyiapkan siswa melalui pengalaman kerja nyata bersama mitra industri terpilih di Sumatera Barat.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FITUR -->
<section class="fitur" id="fitur">
  <div class="section-tag">Fitur Unggulan</div>
  <h2>Semua Kebutuhan PKL dalam Satu Platform</h2>
  <p class="fitur-desc">Dari absensi digital hingga rekap PDF siap cetak — semua tersedia.</p>

  <div class="fitur-grid">
    <div class="fitur-card">
      <div class="fitur-icon">📋</div>
      <h3>Absensi Digital</h3>
      <p>Siswa absen langsung dari smartphone. Admin bisa set jam masuk-pulang, DU/DI bisa override per perusahaan.</p>
    </div>
    <div class="fitur-card">
      <div class="fitur-icon">📔</div>
      <h3>Jurnal Harian</h3>
      <p>Input kegiatan harian, validasi oleh guru pembimbing dan instruktur DU/DI secara real-time.</p>
    </div>
    <div class="fitur-card">
      <div class="fitur-icon">🖨️</div>
      <h3>Export PDF</h3>
      <p>Rekap absensi dan jurnal siap cetak dengan kop sekolah dan tanda tangan, tinggal klik cetak.</p>
    </div>
    <div class="fitur-card">
      <div class="fitur-icon">🔔</div>
      <h3>Notifikasi WA</h3>
      <p>Pengingat absen otomatis via WhatsApp, notifikasi jurnal masuk, dan konfirmasi DU/DI.</p>
    </div>
    <div class="fitur-card">
      <div class="fitur-icon">📊</div>
      <h3>Dashboard Grafik</h3>
      <p>Visualisasi absensi mingguan, tingkat kehadiran, dan sebaran siswa per perusahaan.</p>
    </div>
    <div class="fitur-card">
      <div class="fitur-icon">🔐</div>
      <h3>OTP Login</h3>
      <p>Keamanan berlapis dengan verifikasi OTP WhatsApp saat login dari perangkat baru.</p>
    </div>
  </div>
</section>

<!-- ROLES -->
<section class="roles" id="roles">
  <h2>Untuk Semua Pihak yang Terlibat</h2>
  <p class="roles-sub">Setiap pengguna punya dashboard dan fitur yang sesuai perannya</p>
  <div class="roles-grid">
    <div class="role-card">
      <span class="role-emoji">🎓</span>
      <div class="role-name">Siswa</div>
      <div class="role-desc">Absensi, jurnal harian, dan pantau perkembangan PKL</div>
    </div>
    <div class="role-card">
      <span class="role-emoji">👨‍🏫</span>
      <div class="role-name">Guru Pembimbing</div>
      <div class="role-desc">Monitoring & validasi jurnal siswa bimbingan</div>
    </div>
    <div class="role-card">
      <span class="role-emoji">🏭</span>
      <div class="role-name">Instruktur DU/DI</div>
      <div class="role-desc">Konfirmasi PKL dan atur jam absen perusahaan</div>
    </div>
    <div class="role-card">
      <span class="role-emoji">🏫</span>
      <div class="role-name">Administrator</div>
      <div class="role-desc">Kelola data, jadwal, laporan, dan konfigurasi sistem</div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="f-brand">
    <img src="assets/images/logo-sekolah.png" alt="Logo" onerror="this.style.display='none'">
    <span>SiPrakerin · SMK Negeri 3 Padang</span>
  </div>
  <p>Sistem Informasi Praktik Kerja Lapangan<br>Jl. Bungo Pasang, Tabing, Padang, Sumatera Barat</p>
  <div class="f-divider"></div>
  <p>&copy; <?= date('Y') ?> SMK Negeri 3 Padang. Hak cipta dilindungi.</p>
</footer>

<script>
// ── Counter animasi ──
function animateCount(el, target, suffix='', duration=1600) {
  let start = 0, step = target / (duration / 16);
  const run = () => {
    start = Math.min(start + step, target);
    el.textContent = Math.floor(start).toLocaleString('id') + suffix;
    if (start < target) requestAnimationFrame(run);
  };
  run();
}

// Jalankan saat stats-bar masuk viewport
const statsBar = document.querySelector('.stats-bar');
let counted = false;
const obs = new IntersectionObserver(entries => {
  if (entries[0].isIntersecting && !counted) {
    counted = true;
    animateCount(document.getElementById('ctr-siswa'),  <?= $st_siswa ?>);
    animateCount(document.getElementById('ctr-tempat'), <?= $st_tempat ?>);
    animateCount(document.getElementById('ctr-jurnal'), <?= $st_jurnal ?>);
    animateCount(document.getElementById('ctr-hadir'),  <?= $pct_hadir ?>, '%');
  }
}, { threshold:.3 });
if (statsBar) obs.observe(statsBar);

// ── Nav toggle mobile ──
function toggleNav() {
  document.getElementById('navLinks').classList.toggle('open');
}
function closeNav() {
  document.getElementById('navLinks').classList.remove('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('nav')) closeNav();
});

// ── Scroll reveal ──
const revealEls = document.querySelectorAll('.fitur-card, .role-card, .vm-card');
const revObs = new IntersectionObserver(entries => {
  entries.forEach((e, i) => {
    if (e.isIntersecting) {
      e.target.style.animation = `fadeUp .5s ${i*0.06}s ease both`;
      revObs.unobserve(e.target);
    }
  });
}, { threshold:.1 });
revealEls.forEach(el => revObs.observe(el));
</script>
</body>
</html>
