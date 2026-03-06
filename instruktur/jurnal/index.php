<?php
require '../middleware/auth_instruktur.php';
require '../../config/database.php';

$page_title = 'Monitoring Jurnal';
$user_id    = $_SESSION['user_id'];

$stmtG = mysqli_prepare($conn, "SELECT id FROM instruktur WHERE user_id = ?");
mysqli_stmt_bind_param($stmtG, "i", $user_id);
mysqli_stmt_execute($stmtG);
$guru    = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtG));
$guru_id = $guru['id'] ?? 0;

// Filter status
$filter = $_GET['status'] ?? 'pending';
$where  = in_array($filter, ['pending','disetujui','ditolak']) ? "AND j.status_instruktur = '$filter'" : '';

$stmt = mysqli_prepare($conn, "
    SELECT j.*, u.nama as nama_siswa, s.kelas, s.nis
    FROM jurnal j
    JOIN siswa s ON j.siswa_id = s.id
    JOIN users u ON s.user_id = u.id
    WHERE s.instruktur_id = ? $where
    ORDER BY j.tanggal DESC
");
mysqli_stmt_bind_param($stmt, "i", $guru_id);
mysqli_stmt_execute($stmt);
$jurnals = mysqli_stmt_get_result($stmt);

require '../layout/header.php';
?>
<style>
.filter-tabs { display:flex;gap:8px;margin-bottom:1.5rem;flex-wrap:wrap; }
.filter-tab { padding:0.5rem 1.1rem;border-radius:20px;font-weight:600;font-size:0.85rem;border:1.5px solid var(--border);background:white;color:var(--text-muted);transition:0.2s;cursor:pointer;text-decoration:none; }
.filter-tab.active,.filter-tab:hover { border-color:var(--primary);color:var(--primary);background:#eff6ff; }
.filter-tab.active { background:var(--primary);color:white; }

.jurnal-table { width:100%;border-collapse:collapse;background:white;border-radius:12px;overflow:hidden;border:1px solid var(--border); }
.jurnal-table th { background:#f8fafc;padding:0.85rem 1rem;text-align:left;font-size:0.82rem;color:#64748b;font-weight:600;border-bottom:1px solid var(--border); }
.jurnal-table td { padding:0.85rem 1rem;border-bottom:1px solid #f8fafc;font-size:0.88rem;vertical-align:top; }
.jurnal-table tr:last-child td { border-bottom:none; }
.jurnal-table tr:hover td { background:#f8fafc; }
.kegiatan-text { max-width:350px;color:#374151;line-height:1.5; }

.btn-approve { background:#ecfdf5;color:#059669;border:1px solid #a7f3d0;padding:4px 10px;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;transition:0.2s; }
.btn-approve:hover { background:#059669;color:white; }
.btn-reject { background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:4px 10px;border-radius:6px;font-size:0.8rem;font-weight:600;cursor:pointer;transition:0.2s; }
.btn-reject:hover { background:#dc2626;color:white; }
</style>

<div class="fade-in">
<div class="page-header">
    <div>
        <h1>Monitoring Jurnal</h1>
        <p>Review dan setujui jurnal harian siswa bimbingan Anda.</p>
    </div>
</div>

<div class="filter-tabs">
    <?php
    $tabs = [
        'pending'   => '⏳ Menunggu Review',
        'disetujui' => '✅ Disetujui',
        'ditolak'   => '❌ Ditolak',
        ''          => '📋 Semua',
    ];
    foreach ($tabs as $val => $lbl):
    ?>
    <a href="?status=<?= $val ?>" class="filter-tab <?= $filter===$val?'active':'' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
</div>

<table class="jurnal-table">
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Siswa</th>
            <th>Kegiatan</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>
    </thead>
    <tbody>
    <?php $count = 0; while ($j = mysqli_fetch_assoc($jurnals)): $count++; ?>
    <tr>
        <td>
            <div style="font-weight:600;white-space:nowrap;"><?= date('d M Y', strtotime($j['tanggal'])) ?></div>
            <div style="font-size:0.76rem;color:#94a3b8;"><?= date('l', strtotime($j['tanggal'])) ?></div>
        </td>
        <td>
            <div style="font-weight:600;"><?= htmlspecialchars($j['nama_siswa']) ?></div>
            <div style="font-size:0.78rem;color:#64748b;"><?= $j['kelas'] ?> · <?= $j['nis'] ?></div>
        </td>
        <td><div class="kegiatan-text"><?= nl2br(htmlspecialchars($j['kegiatan'])) ?></div></td>
        <td>
            <?php
            $badges = [
                'pending'   => ['badge-warning','⏳ Pending'],
                'disetujui' => ['badge-success','✅ Disetujui'],
                'ditolak'   => ['badge-danger', '❌ Ditolak'],
            ];
            [$bc,$bl] = $badges[$j['status']] ?? ['badge-neutral','?'];
            ?>
            <span class="badge <?= $bc ?>"><?= $bl ?></span>
        </td>
        <td>
            <?php if ($j['status'] === 'pending'): ?>
            <div style="display:flex;flex-direction:column;gap:4px;">
                <a href="aksi.php?id=<?= $j['id'] ?>&aksi=setuju&ref=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                   class="btn-approve" onclick="return confirm('Setujui jurnal ini?')">✔ Setuju</a>
                <a href="aksi.php?id=<?= $j['id'] ?>&aksi=tolak&ref=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                   class="btn-reject" onclick="return confirm('Tolak jurnal ini?')">✖ Tolak</a>
            </div>
            <?php else: ?>
            <span style="color:#94a3b8;font-size:0.8rem;">—</span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endwhile; ?>
    <?php if ($count === 0): ?>
    <tr>
        <td colspan="5" style="text-align:center;padding:3rem;color:#94a3b8;">
            <?= $filter === 'pending' ? '🎉 Tidak ada jurnal yang menunggu review!' : 'Tidak ada data.' ?>
        </td>
    </tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?php require '../layout/footer.php'; ?>
