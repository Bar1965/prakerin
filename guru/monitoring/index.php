<?php
require '../middleware/auth_guru.php';
require '../../config/database.php';

$page_title = 'Monitoring Siswa';
$user_id = $_SESSION['user_id'];

// Ambil guru_id
$stmt = mysqli_prepare($conn, "SELECT id FROM guru WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$guru_id = $guru['id'] ?? 0;

// Ambil semua siswa bimbingan + statistik jurnal
$stmt2 = mysqli_prepare($conn, "
    SELECT 
        s.id, s.nis, s.kelas, s.jurusan, s.no_hp,
        u.nama,
        tp.nama_tempat,
        COUNT(j.id)                                      AS total_jurnal,
        SUM(j.status = 'disetujui')                      AS jurnal_ok,
        SUM(j.status = 'pending')                        AS jurnal_pending,
        SUM(j.status = 'ditolak')                        AS jurnal_tolak,
        MAX(j.tanggal)                                   AS jurnal_terakhir
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id = tp.id
    LEFT JOIN jurnal j ON j.siswa_id = s.id
    WHERE s.guru_id = ?
    GROUP BY s.id
    ORDER BY s.kelas, u.nama
");
mysqli_stmt_bind_param($stmt2, "i", $guru_id);
mysqli_stmt_execute($stmt2);
$siswa_list = mysqli_stmt_get_result($stmt2);

require '../layout/header.php';
?>

<style>
.stats-mini { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:1rem; margin-bottom:2rem; }
.stat-mini-card {
    background:white; border-radius:12px; border:1px solid var(--border);
    padding:1.1rem 1.25rem; display:flex; align-items:center; gap:12px;
}
.stat-icon { width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;flex-shrink:0; }
.stat-mini-card h4 { font-size:1.5rem;font-weight:700;line-height:1; }
.stat-mini-card p { font-size:0.78rem;color:#64748b;margin-top:2px; }

.student-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1.25rem; }
.student-card {
    background:white; border-radius:14px; border:1px solid var(--border);
    overflow:hidden; transition:0.3s;
}
.student-card:hover { transform:translateY(-4px); box-shadow:0 10px 20px -5px rgba(0,0,0,0.1); border-color:var(--primary); }

.card-top { padding:1.25rem; border-bottom:1px solid #f1f5f9; display:flex; justify-content:space-between; align-items:flex-start; }
.student-avatar {
    width:44px;height:44px;border-radius:12px;
    background:linear-gradient(135deg,#15803d,#818cf8);
    display:flex;align-items:center;justify-content:center;
    color:white;font-weight:700;font-size:1.1rem;flex-shrink:0;
}

.progress-bar-wrap { background:#f0fdf4; border-radius:6px; height:8px; margin-top:6px; overflow:hidden; }
.progress-bar-fill { height:100%; border-radius:6px; transition:width 0.6s ease; }

.card-stats { display:grid; grid-template-columns:repeat(3,1fr); text-align:center; }
.card-stat { padding:0.85rem 0.5rem; border-right:1px solid #f1f5f9; }
.card-stat:last-child { border-right:none; }
.card-stat .num { font-size:1.3rem; font-weight:700; }
.card-stat .lbl { font-size:0.72rem; color:#94a3b8; margin-top:2px; }

.card-footer { padding:0.85rem 1.25rem; background:#f8fafc; display:flex; gap:8px; }
.btn-sm { padding:0.4rem 0.85rem; font-size:0.82rem; border-radius:6px; font-weight:600; }
</style>

<?php
// Hitung total untuk statistik header
$tot_siswa = mysqli_num_rows($siswa_list);
mysqli_data_seek($siswa_list, 0);

$tot_jurnal = 0; $tot_pending = 0;
$rows = [];
while ($r = mysqli_fetch_assoc($siswa_list)) {
    $rows[] = $r;
    $tot_jurnal  += $r['total_jurnal'];
    $tot_pending += $r['jurnal_pending'];
}
?>

<div class="fade-in">
<div class="page-header">
    <div>
        <h1>Monitoring Siswa PKL</h1>
        <p>Pantau perkembangan jurnal dan kehadiran semua siswa bimbingan Anda.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="../../export/rekap_semua.php" target="_blank"
           style="display:inline-flex;align-items:center;gap:6px;padding:.5rem 1rem;background:#14532d;color:#fff;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;transition:.15s;"
           onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#14532d'">
            🖨️ Export PDF Rekap Semua
        </a>
        <a href="rekap_excel.php" class="btn btn-success">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            Rekap ke Excel
        </a>
    </div>
</div>

<!-- STATISTIK MINI -->
<div class="stats-mini">
    <div class="stat-mini-card">
        <div class="stat-icon" style="background:#f0fdf4;color:#16a34a;">👨‍🎓</div>
        <div><h4><?= $tot_siswa ?></h4><p>Total Siswa</p></div>
    </div>
    <div class="stat-mini-card">
        <div class="stat-icon" style="background:#ecfdf5;color:#10b981;">📝</div>
        <div><h4><?= $tot_jurnal ?></h4><p>Total Jurnal</p></div>
    </div>
    <div class="stat-mini-card">
        <div class="stat-icon" style="background:#fffbeb;color:#f59e0b;">⏳</div>
        <div><h4><?= $tot_pending ?></h4><p>Menunggu Review</p></div>
    </div>
</div>

<!-- GRID KARTU SISWA -->
<?php if (empty($rows)): ?>
    <div style="text-align:center;padding:4rem;background:white;border-radius:14px;border:1px solid var(--border);">
        <div style="font-size:3rem;margin-bottom:1rem;">👨‍🎓</div>
        <h3 style="color:#64748b;">Belum ada siswa bimbingan</h3>
        <p style="color:#94a3b8;margin-top:0.5rem;">Hubungi admin untuk menambahkan siswa ke daftar bimbingan Anda.</p>
    </div>
<?php else: ?>
<div class="student-grid">
<?php foreach ($rows as $s):
    $pct = $s['total_jurnal'] > 0 ? round(($s['jurnal_ok'] / $s['total_jurnal']) * 100) : 0;
    $bar_color = $pct >= 80 ? '#10b981' : ($pct >= 50 ? '#f59e0b' : '#ef4444');
    $initials = implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $s['nama']), 0, 2)));
?>
<div class="student-card">
    <div class="card-top">
        <div style="display:flex;gap:12px;align-items:flex-start;">
            <div class="student-avatar"><?= strtoupper($initials) ?></div>
            <div>
                <div style="font-weight:700;font-size:0.95rem;"><?= htmlspecialchars($s['nama']) ?></div>
                <div style="font-size:0.8rem;color:#64748b;"><?= $s['kelas'] ?> · <?= htmlspecialchars($s['nis']) ?></div>
                <div style="font-size:0.78rem;color:#94a3b8;margin-top:3px;">
                    🏢 <?= $s['nama_tempat'] ? htmlspecialchars($s['nama_tempat']) : 'Belum ditempatkan' ?>
                </div>
            </div>
        </div>
        <?php if ($s['jurnal_pending'] > 0): ?>
            <span class="badge badge-warning"><?= $s['jurnal_pending'] ?> pending</span>
        <?php else: ?>
            <span class="badge badge-success">Up to date</span>
        <?php endif; ?>
    </div>

    <!-- PROGRESS -->
    <div style="padding:0.85rem 1.25rem;border-bottom:1px solid #f1f5f9;">
        <div style="display:flex;justify-content:space-between;font-size:0.8rem;margin-bottom:5px;">
            <span style="color:#64748b;">Progress Jurnal Disetujui</span>
            <span style="font-weight:700;color:<?= $bar_color ?>;"><?= $pct ?>%</span>
        </div>
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>;"></div>
        </div>
        <?php if ($s['jurnal_terakhir']): ?>
        <div style="font-size:0.75rem;color:#94a3b8;margin-top:5px;">
            Jurnal terakhir: <?= date('d M Y', strtotime($s['jurnal_terakhir'])) ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- STATS -->
    <div class="card-stats">
        <div class="card-stat">
            <div class="num" style="color:#16a34a;"><?= $s['total_jurnal'] ?></div>
            <div class="lbl">Total</div>
        </div>
        <div class="card-stat">
            <div class="num" style="color:#10b981;"><?= $s['jurnal_ok'] ?></div>
            <div class="lbl">Disetujui</div>
        </div>
        <div class="card-stat">
            <div class="num" style="color:#ef4444;"><?= $s['jurnal_tolak'] ?></div>
            <div class="lbl">Ditolak</div>
        </div>
    </div>

    <!-- FOOTER ACTIONS -->
    <div class="card-footer">
        <a href="detail_siswa.php?id=<?= $s['id'] ?>" class="btn btn-primary btn-sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            Lihat Detail
        </a>
        <a href="../../export/absensi.php?siswa_id=<?= $s['id'] ?>" target="_blank"
           class="btn btn-sm" style="background:#14532d;color:#fff;">
            🖨️ PDF Absensi
        </a>
        <a href="../../export/jurnal.php?siswa_id=<?= $s['id'] ?>" target="_blank"
           class="btn btn-sm" style="background:#374151;color:#fff;">
            📋 PDF Jurnal
        </a>
        <?php if ($s['no_hp']): ?>
        <a href="https://wa.me/<?= preg_replace('/^0/', '62', $s['no_hp']) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:white;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            WhatsApp
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>

<?php require '../layout/footer.php'; ?>
