<?php
session_start();
require '../../config/database.php';
require '../middleware/auth_admin.php';
$page_title = 'Kelompok Bimbingan Guru';

// ── Filter ──
$filter_guru   = (int)($_GET['guru_id'] ?? 0);
$filter_tempat = (int)($_GET['tempat_id'] ?? 0);
$filter_kelas  = trim($_GET['kelas'] ?? '');
$filter_status = $_GET['status'] ?? ''; // 'assigned' | 'unassigned'
$q_cari        = trim($_GET['cari'] ?? '');

// ── Daftar guru (untuk dropdown) ──
$guru_list = [];
$res = mysqli_query($conn, "SELECT g.id, u.nama FROM guru g JOIN users u ON u.id=g.user_id ORDER BY u.nama");
while ($r = mysqli_fetch_assoc($res)) $guru_list[] = $r;

// ── Daftar tempat PKL ──
$tempat_list = [];
$res = mysqli_query($conn, "SELECT id, nama_tempat FROM tempat_pkl ORDER BY nama_tempat");
while ($r = mysqli_fetch_assoc($res)) $tempat_list[] = $r;

// ── Daftar kelas unik ──
$kelas_list = [];
$res = mysqli_query($conn, "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas!='' ORDER BY kelas");
while ($r = mysqli_fetch_assoc($res)) $kelas_list[] = $r['kelas'];

// ── Statistik per guru ──
$stat_guru = [];
$res = mysqli_query($conn, "
    SELECT g.id, u.nama, COUNT(s.id) AS jml_siswa
    FROM guru g
    JOIN users u ON u.id = g.user_id
    LEFT JOIN siswa s ON s.guru_id = g.id
    GROUP BY g.id
    ORDER BY u.nama
");
while ($r = mysqli_fetch_assoc($res)) $stat_guru[$r['id']] = $r;

// ── Build WHERE untuk daftar siswa ──
$where = ["1=1"];
$params = []; $types = '';
if ($filter_guru)   { $where[] = "s.guru_id = ?"; $params[] = $filter_guru;   $types .= 'i'; }
if ($filter_tempat) { $where[] = "s.tempat_pkl_id = ?"; $params[] = $filter_tempat; $types .= 'i'; }
if ($filter_kelas)  { $where[] = "s.kelas = ?"; $params[] = $filter_kelas; $types .= 's'; }
if ($filter_status === 'assigned')   $where[] = "s.guru_id IS NOT NULL";
if ($filter_status === 'unassigned') $where[] = "s.guru_id IS NULL";
if ($q_cari) { $like = "%$q_cari%"; $where[] = "(u.nama LIKE ? OR s.nis LIKE ?)"; $params[] = $like; $params[] = $like; $types .= 'ss'; }

$where_sql = implode(' AND ', $where);

$siswa_stmt = mysqli_prepare($conn, "
    SELECT s.id, s.nis, s.kelas, s.jurusan,
           u.nama AS nama_siswa,
           tp.nama_tempat,
           gu.nama AS nama_guru,
           g.id AS guru_id_val
    FROM siswa s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN tempat_pkl tp ON tp.id = s.tempat_pkl_id
    LEFT JOIN guru g ON g.id = s.guru_id
    LEFT JOIN users gu ON gu.id = g.user_id
    WHERE $where_sql
    ORDER BY s.kelas, u.nama
");
if ($params) {
    mysqli_stmt_bind_param($siswa_stmt, $types, ...$params);
}
mysqli_stmt_execute($siswa_stmt);
$siswa_rows = mysqli_stmt_get_result($siswa_stmt);
$all_siswa = [];
while ($r = mysqli_fetch_assoc($siswa_rows)) $all_siswa[] = $r;

// ── POST: assign / unassign guru ──
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // Assign satu siswa ke guru
    if ($aksi === 'assign') {
        $sid  = (int)$_POST['siswa_id'];
        $gid  = (int)$_POST['guru_id'];
        $stmt = mysqli_prepare($conn, "UPDATE siswa SET guru_id=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ii', $gid, $sid);
        mysqli_stmt_execute($stmt);
        $sep = str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?';
        header("Location: ".$_SERVER['REQUEST_URI'].$sep."ok=assign"); exit;
    }

    // Assign massal (semua siswa terfilter)
    if ($aksi === 'assign_massal') {
        $gid      = (int)$_POST['guru_massal'];
        $siswa_ids = $_POST['siswa_ids'] ?? [];
        if ($gid && $siswa_ids) {
            $ids_safe = implode(',', array_map('intval', $siswa_ids));
            mysqli_query($conn, "UPDATE siswa SET guru_id=$gid WHERE id IN ($ids_safe)");
        }
        $sep = str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?';
        header("Location: ".$_SERVER['REQUEST_URI'].$sep."ok=massal"); exit;
    }

    // Unassign (lepas dari guru)
    if ($aksi === 'unassign') {
        $sid  = (int)$_POST['siswa_id'];
        mysqli_query($conn, "UPDATE siswa SET guru_id=NULL WHERE id=$sid");
        $sep = str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?';
        header("Location: ".$_SERVER['REQUEST_URI'].$sep."ok=unassign"); exit;
    }
}

$ok = $_GET['ok'] ?? '';
if ($ok === 'assign')   $success = 'Siswa berhasil ditetapkan ke guru pembimbing.';
if ($ok === 'massal')   $success = 'Penetapan massal berhasil disimpan!';
if ($ok === 'unassign') $success = 'Siswa berhasil dilepas dari guru pembimbing.';

require '../layout/header.php';
?>

<style>
.stat-guru-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:10px; margin-bottom:1.5rem; }
.stat-guru-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:.85rem 1rem; cursor:pointer; transition:.15s; text-decoration:none; display:block; }
.stat-guru-card:hover { border-color:#15803d; box-shadow:0 2px 10px rgba(21,128,61,.1); }
.stat-guru-card.active { border-color:#15803d; background:#f0fdf4; }
.sgc-num  { font-size:1.6rem; font-weight:800; color:#15803d; line-height:1; }
.sgc-name { font-size:.78rem; color:#0f172a; font-weight:600; margin-top:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.sgc-lbl  { font-size:.7rem; color:#94a3b8; }
.badge-unassigned { background:#fef2f2; color:#dc2626; border:1px solid #fecaca; padding:.2rem .65rem; border-radius:20px; font-size:.72rem; font-weight:600; }
.badge-assigned   { background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; padding:.2rem .65rem; border-radius:20px; font-size:.72rem; font-weight:600; }
.filter-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:1.25rem; }
.filter-bar select, .filter-bar input { font-size:.83rem; padding:.45rem .75rem; border:1.5px solid #e2e8f0; border-radius:8px; outline:none; }
.filter-bar select:focus, .filter-bar input:focus { border-color:#15803d; }
.btn-filter { background:#15803d; color:#fff; border:none; padding:.48rem 1rem; border-radius:8px; font-size:.83rem; font-weight:600; cursor:pointer; }
.btn-reset  { background:#f0fdf4; color:#374151; border:1px solid #e2e8f0; padding:.48rem 1rem; border-radius:8px; font-size:.83rem; font-weight:600; cursor:pointer; text-decoration:none; }
.tbl-action select { font-size:.78rem; padding:.3rem .5rem; border:1px solid #e2e8f0; border-radius:6px; }
</style>

<div class="page-header">
  <h1>👥 Kelompok Bimbingan Guru</h1>
  <p>Atur pembagian siswa ke masing-masing guru pembimbing PKL</p>
</div>

<?php if ($success): ?>
<div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<!-- Stat per guru -->
<div class="stat-guru-grid">
  <a href="kelompok_guru.php" class="stat-guru-card <?= !$filter_guru ? 'active' : '' ?>">
    <div class="sgc-num"><?= array_sum(array_column($stat_guru, 'jml_siswa')) ?></div>
    <div class="sgc-name">Semua Siswa</div>
    <div class="sgc-lbl"><?= count($stat_guru) ?> guru</div>
  </a>
  <?php foreach ($stat_guru as $sg): ?>
  <a href="kelompok_guru.php?guru_id=<?= $sg['id'] ?><?= $filter_tempat?"&tempat_id=$filter_tempat":'' ?><?= $filter_kelas?"&kelas=".urlencode($filter_kelas):'' ?>"
     class="stat-guru-card <?= $filter_guru===$sg['id']?'active':'' ?>">
    <div class="sgc-num"><?= $sg['jml_siswa'] ?></div>
    <div class="sgc-name" title="<?= htmlspecialchars($sg['nama']) ?>"><?= htmlspecialchars($sg['nama']) ?></div>
    <div class="sgc-lbl">siswa dibimbing</div>
  </a>
  <?php endforeach; ?>
  <?php
    $unassigned = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM siswa WHERE guru_id IS NULL"))['n']);
  ?>
  <?php if ($unassigned): ?>
  <a href="kelompok_guru.php?status=unassigned" class="stat-guru-card <?= $filter_status==='unassigned'?'active':'' ?>" style="border-color:#fecaca;">
    <div class="sgc-num" style="color:#dc2626;"><?= $unassigned ?></div>
    <div class="sgc-name">Belum Punya Pembimbing</div>
    <div class="sgc-lbl">perlu ditetapkan</div>
  </a>
  <?php endif; ?>
</div>

<!-- Filter bar -->
<form method="GET" class="filter-bar">
  <?php if ($filter_guru): ?><input type="hidden" name="guru_id" value="<?= $filter_guru ?>"><?php endif; ?>

  <input type="text" name="cari" value="<?= htmlspecialchars($q_cari) ?>" placeholder="🔍 Cari nama / NIS...">

  <select name="tempat_id">
    <option value="">Semua Perusahaan</option>
    <?php foreach ($tempat_list as $tp): ?>
    <option value="<?= $tp['id'] ?>" <?= $filter_tempat===$tp['id']?'selected':'' ?>><?= htmlspecialchars($tp['nama_tempat']) ?></option>
    <?php endforeach; ?>
  </select>

  <select name="kelas">
    <option value="">Semua Kelas</option>
    <?php foreach ($kelas_list as $kl): ?>
    <option value="<?= htmlspecialchars($kl) ?>" <?= $filter_kelas===$kl?'selected':'' ?>><?= htmlspecialchars($kl) ?></option>
    <?php endforeach; ?>
  </select>

  <select name="status">
    <option value="">Semua Status</option>
    <option value="assigned"   <?= $filter_status==='assigned'?'selected':'' ?>>✅ Sudah Ada Pembimbing</option>
    <option value="unassigned" <?= $filter_status==='unassigned'?'selected':'' ?>>⚠️ Belum Ada Pembimbing</option>
  </select>

  <button type="submit" class="btn-filter"><i class="bi bi-funnel"></i> Filter</button>
  <a href="kelompok_guru.php" class="btn-reset">Reset</a>
</form>

<!-- Assign Massal -->
<?php if (count($all_siswa) > 0 && $guru_list): ?>
<form method="POST" id="formMassal">
  <input type="hidden" name="aksi" value="assign_massal">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:.85rem;padding:.75rem 1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
    <span style="font-size:.83rem;font-weight:600;color:#374151;">
      <i class="bi bi-lightning-charge me-1"></i>Assign Massal:
    </span>
    <select name="guru_massal" class="form-select" style="width:auto;font-size:.83rem;" required>
      <option value="">— Pilih Guru —</option>
      <?php foreach ($guru_list as $g): ?>
      <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" onclick="selectAll()" style="background:#f0fdf4;border:1px solid #e2e8f0;padding:.4rem .85rem;border-radius:7px;font-size:.8rem;cursor:pointer;">Pilih Semua</button>
    <button type="submit" class="btn-primary-custom" style="padding:.45rem 1rem;font-size:.83rem;"
            onclick="return confirm('Assign siswa terpilih ke guru ini?')">
      <i class="bi bi-check2-all me-1"></i>Assign Terpilih
    </button>
    <span id="countSelected" style="font-size:.78rem;color:#64748b;"></span>
  </div>
<?php endif; ?>

<!-- Tabel siswa -->
<div class="table-card">
  <div class="table-card-header" style="justify-content:space-between;">
    <h5><i class="bi bi-people me-2"></i>Daftar Siswa
      <?php if ($filter_guru && isset($stat_guru[$filter_guru])): ?>
        — <?= htmlspecialchars($stat_guru[$filter_guru]['nama']) ?>
      <?php endif; ?>
    </h5>
    <small class="text-muted"><?= count($all_siswa) ?> siswa</small>
  </div>

  <?php if (empty($all_siswa)): ?>
  <div class="empty-state"><i class="bi bi-people"></i><p>Tidak ada siswa ditemukan.</p></div>
  <?php else: ?>
  <div style="overflow-x:auto;">
  <table class="tbl">
    <thead><tr>
      <th style="width:36px;">
        <input type="checkbox" id="chkAll" onchange="toggleAll(this)" style="accent-color:#15803d;">
      </th>
      <th>Nama Siswa</th>
      <th>NIS</th>
      <th>Kelas</th>
      <th>Perusahaan PKL</th>
      <th>Guru Pembimbing</th>
      <th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php foreach ($all_siswa as $s): ?>
    <tr>
      <td>
        <input type="checkbox" name="siswa_ids[]" value="<?= $s['id'] ?>"
               class="chk-siswa" style="accent-color:#15803d;" onchange="updateCount()">
      </td>
      <td><strong><?= htmlspecialchars($s['nama_siswa']) ?></strong></td>
      <td style="color:#64748b;font-size:.83rem;"><?= htmlspecialchars($s['nis']) ?></td>
      <td><span style="font-size:.8rem;background:#f0fdf4;padding:.2rem .6rem;border-radius:20px;"><?= htmlspecialchars($s['kelas']) ?></span></td>
      <td style="font-size:.83rem;"><?= htmlspecialchars($s['nama_tempat'] ?? '—') ?></td>
      <td>
        <?php if ($s['nama_guru']): ?>
          <span class="badge-assigned"><i class="bi bi-person-check me-1"></i><?= htmlspecialchars($s['nama_guru']) ?></span>
        <?php else: ?>
          <span class="badge-unassigned"><i class="bi bi-person-x me-1"></i>Belum Ada</span>
        <?php endif; ?>
      </td>
      <td>
        <div style="display:flex;gap:6px;align-items:center;">
          <!-- Dropdown assign langsung -->
          <form method="POST" style="display:inline-flex;gap:5px;align-items:center;">
            <input type="hidden" name="aksi" value="assign">
            <input type="hidden" name="siswa_id" value="<?= $s['id'] ?>">
            <select name="guru_id" class="tbl-action" onchange="this.form.submit()" title="Tetapkan guru pembimbing">
              <option value="">— Pilih Guru —</option>
              <?php foreach ($guru_list as $g): ?>
              <option value="<?= $g['id'] ?>" <?= $s['guru_id_val']==$g['id']?'selected':'' ?>>
                <?= htmlspecialchars($g['nama']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </form>
          <?php if ($s['guru_id_val']): ?>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="aksi" value="unassign">
            <input type="hidden" name="siswa_id" value="<?= $s['id'] ?>">
            <button type="submit" class="btn-danger-sm" title="Lepas dari guru" onclick="return confirm('Lepas siswa ini dari guru pembimbing?')">
              <i class="bi bi-x-lg"></i>
            </button>
          </form>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</div>

<?php if (count($all_siswa) > 0 && $guru_list): ?>
</form>
<?php endif; ?>

<script>
function toggleAll(chk) {
  document.querySelectorAll('.chk-siswa').forEach(c => c.checked = chk.checked);
  updateCount();
}
function selectAll() {
  document.querySelectorAll('.chk-siswa').forEach(c => c.checked = true);
  document.getElementById('chkAll').checked = true;
  updateCount();
}
function updateCount() {
  const n = document.querySelectorAll('.chk-siswa:checked').length;
  const el = document.getElementById('countSelected');
  if (el) el.textContent = n > 0 ? `${n} siswa dipilih` : '';
  const all = document.querySelectorAll('.chk-siswa').length;
  const chkAll = document.getElementById('chkAll');
  if (chkAll) chkAll.indeterminate = n > 0 && n < all;
}
</script>

<?php require '../layout/footer.php'; ?>
