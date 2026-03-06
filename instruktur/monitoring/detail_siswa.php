<?php
require '../middleware/auth_instruktur.php';
require '../../config/database.php';

$siswa_id = (int)($_GET['id'] ?? 0);
if ($siswa_id <= 0) { header("Location: index.php"); exit; }

// Validasi: siswa ini memang bimbingan guru ini
$user_id = $_SESSION['user_id'];
$stmtG = mysqli_prepare($conn, "SELECT id FROM instruktur WHERE user_id = ?");
mysqli_stmt_bind_param($stmtG, "i", $user_id);
mysqli_stmt_execute($stmtG);
$guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtG));
$guru_id = $guru['id'] ?? 0;

$stmtCek = mysqli_prepare($conn, "SELECT id FROM siswa WHERE id = ? AND guru_id = ?");
mysqli_stmt_bind_param($stmtCek, "ii", $siswa_id, $guru_id);
mysqli_stmt_execute($stmtCek);
if (!mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCek))) {
    header("Location: index.php"); exit;
}

// Data siswa lengkap
$stmtS = mysqli_prepare($conn, "
    SELECT s.*, u.nama, u.username,
           tp.nama_tempat, tp.pembimbing_lapangan, tp.no_hp as hp_tempat
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id = tp.id
    WHERE s.id = ?
");
mysqli_stmt_bind_param($stmtS, "i", $siswa_id);
mysqli_stmt_execute($stmtS);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtS));

// Filter bulan jika ada
$filter_bulan = $_GET['bulan'] ?? '';
$where_bulan  = $filter_bulan ? "AND DATE_FORMAT(j.tanggal,'%Y-%m') = ?" : '';

// Semua jurnal siswa ini
$sql_j = "
    SELECT * FROM jurnal
    WHERE siswa_id = ? $where_bulan
    ORDER BY tanggal ASC
";
$stmtJ = mysqli_prepare($conn, $sql_j);
if ($filter_bulan) {
    mysqli_stmt_bind_param($stmtJ, "is", $siswa_id, $filter_bulan);
} else {
    mysqli_stmt_bind_param($stmtJ, "i", $siswa_id);
}
mysqli_stmt_execute($stmtJ);
$jurnal_list = mysqli_stmt_get_result($stmtJ);
$jurnals = [];
while ($j = mysqli_fetch_assoc($jurnal_list)) $jurnals[] = $j;

// Statistik jurnal (selalu keseluruhan, bukan per filter)
$stmtStat = mysqli_prepare($conn, "
    SELECT
        COUNT(*) as total,
        SUM(status='disetujui') as ok,
        SUM(status='pending') as pending,
        SUM(status='ditolak') as tolak,
        MIN(tanggal) as mulai,
        MAX(tanggal) as akhir
    FROM jurnal WHERE siswa_id = ?
");
mysqli_stmt_bind_param($stmtStat, "i", $siswa_id);
mysqli_stmt_execute($stmtStat);
$stat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtStat));

// Daftar bulan yang ada jurnalnya (untuk filter dropdown)
$stmtBln = mysqli_prepare($conn, "
    SELECT DISTINCT DATE_FORMAT(tanggal,'%Y-%m') as bln,
                    DATE_FORMAT(tanggal,'%M %Y') as label
    FROM jurnal WHERE siswa_id = ? ORDER BY bln
");
mysqli_stmt_bind_param($stmtBln, "i", $siswa_id);
mysqli_stmt_execute($stmtBln);
$bulan_list = mysqli_stmt_get_result($stmtBln);

$pct = $stat['total'] > 0 ? round($stat['ok'] / $stat['total'] * 100) : 0;
$page_title = 'Detail: '.$siswa['nama'];

require '../layout/header.php';
?>
<style>
.profile-strip {
    background:white; border-radius:14px; border:1px solid var(--border);
    padding:1.5rem; margin-bottom:1.5rem; display:flex; gap:1.5rem;
    align-items:center; flex-wrap:wrap;
}
.big-avatar {
    width:72px;height:72px;border-radius:16px;
    background:linear-gradient(135deg,#15803d,#818cf8);
    display:flex;align-items:center;justify-content:center;
    color:white;font-weight:700;font-size:1.8rem;flex-shrink:0;
}
.info-chips { display:flex;gap:8px;flex-wrap:wrap;margin-top:8px; }
.chip { background:#f0fdf4;padding:4px 10px;border-radius:20px;font-size:0.78rem;color:#475569;font-weight:500; }

.stat-row { display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:1rem;margin-bottom:1.5rem; }
.stat-box { background:white;border-radius:12px;border:1px solid var(--border);padding:1rem;text-align:center; }
.stat-box .num { font-size:1.8rem;font-weight:700;line-height:1; }
.stat-box .lbl { font-size:0.78rem;color:#94a3b8;margin-top:4px; }

.progress-big { background:#f0fdf4;border-radius:8px;height:12px;overflow:hidden;margin-bottom:1.5rem; }
.progress-big-fill { height:100%;border-radius:8px;transition:width 0.8s ease; }

/* TIMELINE JURNAL */
.timeline { position:relative;padding-left:30px; }
.timeline::before { content:'';position:absolute;left:8px;top:0;bottom:0;width:2px;background:#e2e8f0; }

.tl-item { position:relative;margin-bottom:1.25rem; }
.tl-dot {
    position:absolute;left:-26px;top:12px;
    width:14px;height:14px;border-radius:50%;border:3px solid white;
    box-shadow:0 0 0 2px var(--border);
}
.tl-dot.ok     { background:#10b981;box-shadow:0 0 0 2px #10b981; }
.tl-dot.pending{ background:#f59e0b;box-shadow:0 0 0 2px #f59e0b; }
.tl-dot.tolak  { background:#ef4444;box-shadow:0 0 0 2px #ef4444; }

.tl-card {
    background:white;border:1px solid var(--border);border-radius:12px;
    padding:1rem 1.25rem;transition:0.2s;
}
.tl-card:hover { border-color:var(--primary);box-shadow:0 4px 12px rgba(21,128,61,0.08); }
.tl-header { display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.6rem;flex-wrap:wrap;gap:8px; }
.tl-date { font-weight:700;font-size:0.9rem; }
.tl-day { font-size:0.75rem;color:#94a3b8; }
.tl-kegiatan { font-size:0.92rem;color:#374151;line-height:1.6;white-space:pre-wrap; }
.tl-catatan { background:#fffbeb;border-left:3px solid #f59e0b;padding:8px 12px;border-radius:0 8px 8px 0;margin-top:0.75rem;font-size:0.85rem;color:#92400e; }

.empty-state { text-align:center;padding:3rem;background:white;border-radius:14px;border:1px solid var(--border); }

.filter-bar { display:flex;gap:10px;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap; }
</style>

<div class="fade-in">

<!-- BREADCRUMB -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
    <div>
        <a href="index.php" style="color:#64748b;font-size:0.88rem;display:flex;align-items:center;gap:5px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
            Monitoring Siswa
        </a>
        <h1 style="font-size:1.4rem;font-weight:700;margin-top:4px;"><?= htmlspecialchars($siswa['nama']) ?></h1>
    </div>
    <a href="rekap_excel.php?siswa_id=<?= $siswa_id ?>" class="btn btn-success">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
        Export Excel
    </a>
</div>

<!-- PROFIL STRIP -->
<div class="profile-strip">
    <div class="big-avatar">
        <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
    </div>
    <div style="flex:1;">
        <div style="font-size:1.15rem;font-weight:700;"><?= htmlspecialchars($siswa['nama']) ?></div>
        <div class="info-chips">
            <span class="chip">NIS: <?= $siswa['nis'] ?></span>
            <span class="chip"><?= $siswa['kelas'] ?></span>
            <span class="chip"><?= $siswa['jurusan'] ?? '-' ?></span>
            <?php if ($siswa['nama_tempat']): ?>
            <span class="chip">🏢 <?= htmlspecialchars($siswa['nama_tempat']) ?></span>
            <?php endif; ?>
            <?php if ($siswa['no_hp']): ?>
            <a href="https://wa.me/<?= preg_replace('/^0/','62',$siswa['no_hp']) ?>" target="_blank"
               class="chip" style="background:#dcfce7;color:#166534;text-decoration:none;">
                📱 <?= $siswa['no_hp'] ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($siswa['mulai'] ?? null): ?>
    <div style="text-align:right;font-size:0.82rem;color:#64748b;">
        <div>Mulai: <strong><?= date('d M Y', strtotime($stat['mulai'])) ?></strong></div>
        <div>Terakhir: <strong><?= date('d M Y', strtotime($stat['akhir'])) ?></strong></div>
    </div>
    <?php endif; ?>
</div>

<!-- STATISTIK -->
<div class="stat-row">
    <div class="stat-box">
        <div class="num" style="color:#16a34a;"><?= $stat['total'] ?></div>
        <div class="lbl">Total Jurnal</div>
    </div>
    <div class="stat-box">
        <div class="num" style="color:#10b981;"><?= $stat['ok'] ?></div>
        <div class="lbl">Disetujui</div>
    </div>
    <div class="stat-box">
        <div class="num" style="color:#f59e0b;"><?= $stat['pending'] ?></div>
        <div class="lbl">Pending</div>
    </div>
    <div class="stat-box">
        <div class="num" style="color:#ef4444;"><?= $stat['tolak'] ?></div>
        <div class="lbl">Ditolak</div>
    </div>
    <div class="stat-box">
        <div class="num" style="color:<?= $pct>=80?'#10b981':($pct>=50?'#f59e0b':'#ef4444') ?>;"><?= $pct ?>%</div>
        <div class="lbl">Approval Rate</div>
    </div>
</div>

<!-- PROGRESS BAR -->
<?php $bar_color = $pct>=80?'#10b981':($pct>=50?'#f59e0b':'#ef4444'); ?>
<div style="background:white;border-radius:12px;border:1px solid var(--border);padding:1.25rem;margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;font-size:0.88rem;">
        <span style="font-weight:600;">Progress Keseluruhan PKL</span>
        <span style="font-weight:700;color:<?= $bar_color ?>;"><?= $pct ?>% jurnal disetujui</span>
    </div>
    <div class="progress-big">
        <div class="progress-big-fill" style="width:<?= $pct ?>%;background:<?= $bar_color ?>;"></div>
    </div>
    <div style="display:flex;gap:1.5rem;font-size:0.78rem;color:#94a3b8;margin-top:6px;">
        <span>🟢 <?= $stat['ok'] ?> disetujui</span>
        <span>🟡 <?= $stat['pending'] ?> pending</span>
        <span>🔴 <?= $stat['tolak'] ?> ditolak</span>
    </div>
</div>

<!-- TIMELINE JURNAL -->
<div style="background:white;border-radius:14px;border:1px solid var(--border);padding:1.5rem;">
    <div class="filter-bar">
        <h3 style="font-size:1rem;font-weight:700;flex:1;">📋 Riwayat Jurnal Harian</h3>
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="id" value="<?= $siswa_id ?>">
            <select name="bulan" class="form-control" style="width:auto;padding:0.4rem 0.8rem;font-size:0.85rem;" onchange="this.form.submit()">
                <option value="">Semua Bulan</option>
                <?php
                $bulan_arr = [];
                while ($b = mysqli_fetch_assoc($bulan_list)) $bulan_arr[] = $b;
                foreach ($bulan_arr as $b):
                ?>
                <option value="<?= $b['bln'] ?>" <?= $filter_bulan==$b['bln']?'selected':'' ?>><?= $b['label'] ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($filter_bulan): ?>
            <a href="?id=<?= $siswa_id ?>" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($jurnals)): ?>
    <div class="empty-state">
        <div style="font-size:2.5rem;margin-bottom:0.75rem;">📭</div>
        <p style="color:#94a3b8;">Belum ada jurnal <?= $filter_bulan ? 'di bulan ini' : 'sama sekali' ?>.</p>
    </div>
    <?php else: ?>
    <div class="timeline">
        <?php foreach ($jurnals as $j):
            $dot_class = $j['status'] === 'disetujui' ? 'ok' : ($j['status'] === 'pending' ? 'pending' : 'tolak');
            $hari_indo = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            $nama_hari = $hari_indo[date('w', strtotime($j['tanggal']))];
        ?>
        <div class="tl-item">
            <div class="tl-dot <?= $dot_class ?>"></div>
            <div class="tl-card">
                <div class="tl-header">
                    <div>
                        <div class="tl-date"><?= date('d F Y', strtotime($j['tanggal'])) ?></div>
                        <div class="tl-day"><?= $nama_hari ?></div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <?php
                        $badge = match($j['status']) {
                            'disetujui' => ['badge-success','✅ Disetujui'],
                            'pending'   => ['badge-warning','⏳ Pending'],
                            'ditolak'   => ['badge-danger', '❌ Ditolak'],
                            default     => ['badge-neutral','?']
                        };
                        ?>
                        <span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span>
                        <?php if ($j['status'] === 'pending'): ?>
                        <div style="display:flex;gap:4px;">
                            <a href="../jurnal/aksi.php?id=<?= $j['id'] ?>&aksi=setuju"
                               class="btn btn-sm" style="background:#10b981;color:white;padding:4px 10px;"
                               onclick="return confirm('Setujui jurnal ini?')">✔ Setuju</a>
                            <a href="../jurnal/aksi.php?id=<?= $j['id'] ?>&aksi=tolak"
                               class="btn btn-sm" style="background:#ef4444;color:white;padding:4px 10px;"
                               onclick="return confirm('Tolak jurnal ini?')">✖ Tolak</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tl-kegiatan"><?= htmlspecialchars($j['kegiatan']) ?></div>
                <?php if ($j['catatan_guru']): ?>
                <div class="tl-catatan">
                    💬 <strong>Catatan Guru:</strong> <?= htmlspecialchars($j['catatan_guru']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</div><!-- fade-in -->
<?php require '../layout/footer.php'; ?>
