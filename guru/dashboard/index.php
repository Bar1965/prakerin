<?php
require '../middleware/auth_guru.php';
require '../../config/database.php';

$page_title = 'Dashboard';
$pw_changed = isset($_GET['pw_changed']);
$user_id    = $_SESSION['user_id'];

$stmtG = mysqli_prepare($conn, "SELECT id FROM guru WHERE user_id = ?");
mysqli_stmt_bind_param($stmtG, "i", $user_id);
mysqli_stmt_execute($stmtG);
$guru    = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtG));
$guru_id = $guru['id'] ?? 0;

$q1 = mysqli_prepare($conn, "SELECT COUNT(*) t FROM siswa WHERE guru_id = ?");
mysqli_stmt_bind_param($q1, "i", $guru_id); mysqli_stmt_execute($q1);
$total_siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($q1))['t'];

$q2 = mysqli_prepare($conn, "SELECT COUNT(*) t FROM jurnal j JOIN siswa s ON j.siswa_id=s.id WHERE s.guru_id=? AND j.status='pending'");
mysqli_stmt_bind_param($q2, "i", $guru_id); mysqli_stmt_execute($q2);
$total_pending = mysqli_fetch_assoc(mysqli_stmt_get_result($q2))['t'];

$q3 = mysqli_prepare($conn, "SELECT COUNT(*) t FROM jurnal j JOIN siswa s ON j.siswa_id=s.id WHERE s.guru_id=?");
mysqli_stmt_bind_param($q3, "i", $guru_id); mysqli_stmt_execute($q3);
$total_jurnal = mysqli_fetch_assoc(mysqli_stmt_get_result($q3))['t'];

// 5 jurnal terbaru pending
$q4 = mysqli_prepare($conn, "
    SELECT j.id, j.tanggal, j.kegiatan, u.nama
    FROM jurnal j JOIN siswa s ON j.siswa_id=s.id JOIN users u ON s.user_id=u.id
    WHERE s.guru_id=? AND j.status='pending'
    ORDER BY j.created_at DESC LIMIT 5
");
mysqli_stmt_bind_param($q4, "i", $guru_id); mysqli_stmt_execute($q4);
$pending_list = mysqli_stmt_get_result($q4);

date_default_timezone_set('Asia/Jakarta');
$jam = date('H');
$sapaan = $jam<11 ? 'Selamat Pagi' : ($jam<15 ? 'Selamat Siang' : ($jam<18 ? 'Selamat Sore' : 'Selamat Malam'));

require '../layout/header.php';
?>
<?php if ($pw_changed): ?>
<div style="background:#f0fdf4;border:1.5px solid #86efac;color:#166534;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1rem;display:flex;align-items:center;gap:.6rem;font-size:.9rem;font-weight:500;">
  <i class="bi bi-check-circle-fill" style="font-size:1.1rem"></i>
  Password berhasil diperbarui. Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!
</div>
<?php endif; ?>
<style>
.welcome { background:linear-gradient(135deg,#10b981,#059669);color:white;border-radius:14px;padding:1.75rem;margin-bottom:1.5rem;position:relative;overflow:hidden; }
.welcome h1{font-size:1.5rem;margin-bottom:4px;}
.welcome p{opacity:0.9;font-size:0.9rem;}
.w-deco{position:absolute;background:rgba(255,255,255,0.1);border-radius:50%;}

.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat{background:white;border-radius:12px;border:1px solid var(--border);padding:1.1rem 1.25rem;display:flex;align-items:center;gap:12px;}
.stat-icon{width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0;}
.stat h4{font-size:1.6rem;font-weight:700;line-height:1;}
.stat p{font-size:0.78rem;color:#64748b;margin-top:2px;}

.pending-list{background:white;border-radius:12px;border:1px solid var(--border);overflow:hidden;}
.pending-item{display:flex;justify-content:space-between;align-items:center;padding:0.9rem 1.25rem;border-bottom:1px solid #f1f5f9;gap:12px;}
.pending-item:last-child{border-bottom:none;}
.quick-links{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-top:1.5rem;}
.quick-link{background:white;border:1px solid var(--border);border-radius:12px;padding:1.25rem;text-align:center;transition:0.2s;}
.quick-link:hover{border-color:var(--primary);transform:translateY(-3px);}
.quick-link .icon{font-size:1.8rem;margin-bottom:6px;}
.quick-link h4{font-weight:600;font-size:0.88rem;}
</style>

<div class="fade-in">
<div class="welcome">
    <div class="w-deco" style="width:100px;height:100px;top:-20px;right:-20px;"></div>
    <div class="w-deco" style="width:160px;height:160px;bottom:-60px;right:50px;"></div>
    <h1><?= $sapaan ?>, <?= htmlspecialchars($_SESSION['nama']) ?>! 👋</h1>
    <p>Selamat datang di portal guru pembimbing PKL.</p>
</div>

<div class="stats">
    <div class="stat">
        <div class="stat-icon" style="background:#f0fdf4;color:#16a34a;">👨‍🎓</div>
        <div><h4><?= $total_siswa ?></h4><p>Siswa Bimbingan</p></div>
    </div>
    <div class="stat">
        <div class="stat-icon" style="background:#ecfdf5;color:#10b981;">📝</div>
        <div><h4><?= $total_jurnal ?></h4><p>Total Jurnal</p></div>
    </div>
    <div class="stat">
        <div class="stat-icon" style="background:#fffbeb;color:#f59e0b;">⏳</div>
        <div><h4 style="color:<?= $total_pending>0?'#f59e0b':'inherit' ?>"><?= $total_pending ?></h4><p>Menunggu Review</p></div>
    </div>
</div>

<?php if ($total_pending > 0): ?>
<h3 style="font-size:1rem;font-weight:700;margin-bottom:0.75rem;">⏳ Jurnal Menunggu Review</h3>
<div class="pending-list">
    <?php while ($p = mysqli_fetch_assoc($pending_list)): ?>
    <div class="pending-item">
        <div>
            <div style="font-weight:600;font-size:0.9rem;"><?= htmlspecialchars($p['nama']) ?></div>
            <div style="font-size:0.8rem;color:#94a3b8;margin-top:2px;"><?= date('d M Y', strtotime($p['tanggal'])) ?></div>
        </div>
        <div style="display:flex;gap:6px;">
            <a href="../jurnal/aksi.php?id=<?= $p['id'] ?>&aksi=setuju&ref=../dashboard/index.php"
               style="background:#10b981;color:white;padding:4px 10px;border-radius:6px;font-size:0.8rem;font-weight:600;"
               onclick="return confirm('Setujui?')">✔</a>
            <a href="../jurnal/aksi.php?id=<?= $p['id'] ?>&aksi=tolak&ref=../dashboard/index.php"
               style="background:#ef4444;color:white;padding:4px 10px;border-radius:6px;font-size:0.8rem;font-weight:600;"
               onclick="return confirm('Tolak?')">✖</a>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<div class="quick-links">
    <a href="../monitoring/index.php" class="quick-link">
        <div class="icon">👥</div><h4>Monitoring Siswa</h4>
    </a>
    <a href="../jurnal/index.php" class="quick-link">
        <div class="icon">📋</div><h4>Semua Jurnal</h4>
    </a>
    <a href="../monitoring/rekap_excel.php" class="quick-link">
        <div class="icon">📊</div><h4>Rekap Excel</h4>
    </a>
    <a href="../profil/index.php" class="quick-link">
        <div class="icon">👤</div><h4>Profil Saya</h4>
    </a>
</div>
</div>

<?php require '../layout/footer.php'; ?>
