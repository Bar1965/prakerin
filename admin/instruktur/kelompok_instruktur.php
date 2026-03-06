<?php
session_start();
require '../../config/database.php';
require '../middleware/auth_admin.php';
$page_title = 'Kelompok Instruktur DU/DI';

// ── Filter ──
$filter_instr  = (int)($_GET['instr_id'] ?? 0);
$filter_tempat = (int)($_GET['tempat_id'] ?? 0);
$filter_kelas  = trim($_GET['kelas'] ?? '');
$filter_status = $_GET['status'] ?? '';
$q_cari        = trim($_GET['cari'] ?? '');

// ── Daftar instruktur ──
$instr_list = [];
$res = mysqli_query($conn, "
    SELECT i.id, u.nama, tp.nama_tempat
    FROM instruktur i
    JOIN users u ON u.id = i.user_id
    LEFT JOIN tempat_pkl tp ON tp.id = i.tempat_pkl_id
    ORDER BY u.nama
");
while ($r = mysqli_fetch_assoc($res)) $instr_list[] = $r;

// ── Daftar tempat PKL ──
$tempat_list = [];
$res = mysqli_query($conn, "SELECT id, nama_tempat FROM tempat_pkl ORDER BY nama_tempat");
while ($r = mysqli_fetch_assoc($res)) $tempat_list[] = $r;

// ── Daftar kelas ──
$kelas_list = [];
$res = mysqli_query($conn, "SELECT DISTINCT kelas FROM siswa WHERE kelas IS NOT NULL AND kelas!='' ORDER BY kelas");
while ($r = mysqli_fetch_assoc($res)) $kelas_list[] = $r['kelas'];

// ── Statistik per instruktur ──
$stat_instr = [];
$res = mysqli_query($conn, "
    SELECT i.id, u.nama, tp.nama_tempat, COUNT(s.id) AS jml_siswa
    FROM instruktur i
    JOIN users u ON u.id = i.user_id
    LEFT JOIN tempat_pkl tp ON tp.id = i.tempat_pkl_id
    LEFT JOIN siswa s ON s.instruktur_id = i.id
    GROUP BY i.id ORDER BY u.nama
");
while ($r = mysqli_fetch_assoc($res)) $stat_instr[$r['id']] = $r;

// ── Build WHERE ──
$where = ["1=1"]; $params = []; $types = '';
if ($filter_instr)  { $where[] = "s.instruktur_id = ?"; $params[] = $filter_instr;  $types .= 'i'; }
if ($filter_tempat) { $where[] = "s.tempat_pkl_id = ?"; $params[] = $filter_tempat; $types .= 'i'; }
if ($filter_kelas)  { $where[] = "s.kelas = ?";         $params[] = $filter_kelas;  $types .= 's'; }
if ($filter_status === 'assigned')   $where[] = "s.instruktur_id IS NOT NULL";
if ($filter_status === 'unassigned') $where[] = "s.instruktur_id IS NULL";
if ($q_cari) {
    $like = "%$q_cari%";
    $where[] = "(u.nama LIKE ? OR s.nis LIKE ?)";
    $params[] = $like; $params[] = $like; $types .= 'ss';
}
$where_sql = implode(' AND ', $where);

$siswa_stmt = mysqli_prepare($conn, "
    SELECT s.id, s.nis, s.kelas,
           u.nama AS nama_siswa,
           tp.nama_tempat,
           ui.nama AS nama_instruktur,
           i.id AS instr_id_val
    FROM siswa s
    JOIN users u ON u.id = s.user_id
    LEFT JOIN tempat_pkl tp ON tp.id = s.tempat_pkl_id
    LEFT JOIN instruktur i ON i.id = s.instruktur_id
    LEFT JOIN users ui ON ui.id = i.user_id
    WHERE $where_sql
    ORDER BY s.kelas, u.nama
");
if ($params) mysqli_stmt_bind_param($siswa_stmt, $types, ...$params);
mysqli_stmt_execute($siswa_stmt);
$rows = mysqli_stmt_get_result($siswa_stmt);
$all_siswa = [];
while ($r = mysqli_fetch_assoc($rows)) $all_siswa[] = $r;

// ── POST aksi ──
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'assign') {
        $sid = (int)$_POST['siswa_id'];
        $iid = (int)$_POST['instr_id'];
        $st  = mysqli_prepare($conn, "UPDATE siswa SET instruktur_id=? WHERE id=?");
        mysqli_stmt_bind_param($st, 'ii', $iid, $sid);
        mysqli_stmt_execute($st);
        $sep = str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?';
        header("Location: ".$_SERVER['REQUEST_URI'].$sep."ok=assign"); exit;
    }

    if ($aksi === 'assign_massal') {
        $iid       = (int)$_POST['instr_massal'];
        $siswa_ids = $_POST['siswa_ids'] ?? [];
        if ($iid && $siswa_ids) {
            $ids_safe = implode(',', array_map('intval', $siswa_ids));
            mysqli_query($conn, "UPDATE siswa SET instruktur_id=$iid WHERE id IN ($ids_safe)");
        }
        $sep = str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?';
        header("Location: ".$_SERVER['REQUEST_URI'].$sep."ok=massal"); exit;
    }

    if ($aksi === 'unassign') {
        $sid = (int)$_POST['siswa_id'];
        mysqli_query($conn, "UPDATE siswa SET instruktur_id=NULL WHERE id=$sid");
        $sep = str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?';
        header("Location: ".$_SERVER['REQUEST_URI'].$sep."ok=unassign"); exit;
    }

    // Assign otomatis — siswa ke instruktur sesuai tempat PKL
    if ($aksi === 'auto_assign') {
        mysqli_query($conn, "
            UPDATE siswa s
            JOIN instruktur i ON i.tempat_pkl_id = s.tempat_pkl_id
            SET s.instruktur_id = i.id
            WHERE s.instruktur_id IS NULL AND s.tempat_pkl_id IS NOT NULL
        ");
        $sep = str_contains($_SERVER['REQUEST_URI'], '?') ? '&' : '?';
        header("Location: ".$_SERVER['REQUEST_URI'].$sep."ok=auto"); exit;
    }
}

$ok = $_GET['ok'] ?? '';
if ($ok === 'assign')   $success = 'Siswa berhasil ditetapkan ke instruktur.';
if ($ok === 'massal')   $success = 'Penetapan massal berhasil disimpan!';
if ($ok === 'unassign') $success = 'Siswa berhasil dilepas dari instruktur.';
if ($ok === 'auto')     $success = 'Auto-assign berhasil! Siswa ditetapkan berdasarkan tempat PKL.';

require '../layout/header.php';
?>

<style>
.stat-instr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:10px;margin-bottom:1.5rem;}
.stat-instr-card{background:#fff;border:1px solid var(--border);border-radius:10px;padding:.85rem 1rem;cursor:pointer;transition:.15s;text-decoration:none;display:block;}
.stat-instr-card:hover{border-color:var(--primary);box-shadow:0 2px 10px rgba(21,128,61,.1);}
.stat-instr-card.active{border-color:var(--primary);background:var(--green-50);}
.sic-num{font-size:1.6rem;font-weight:800;color:var(--primary);line-height:1;}
.sic-name{font-size:.78rem;color:var(--text);font-weight:600;margin-top:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.sic-sub{font-size:.68rem;color:var(--muted);}
.badge-unassigned{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600;}
.badge-assigned{background:var(--green-50);color:var(--green-700);border:1px solid var(--green-200);padding:.2rem .65rem;border-radius:20px;font-size:.72rem;font-weight:600;}
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:1.25rem;}
.filter-bar select,.filter-bar input{font-size:.83rem;padding:.45rem .75rem;border:1.5px solid var(--border);border-radius:8px;outline:none;font-family:inherit;}
.filter-bar select:focus,.filter-bar input:focus{border-color:var(--primary);}
.tbl-sel{font-size:.78rem;padding:.3rem .5rem;border:1px solid var(--border);border-radius:6px;font-family:inherit;}
.auto-banner{background:linear-gradient(135deg,var(--green-50),#fff);border:1px solid var(--green-200);border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
</style>

<div class="page-header">
  <div>
    <h1>🏭 Kelompok Instruktur DU/DI</h1>
    <p>Tetapkan instruktur DU/DI untuk setiap siswa PKL</p>
  </div>
</div>

<?php if ($success): ?>
<div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<!-- Auto-assign banner -->
<div class="auto-banner">
  <div>
    <div style="font-weight:700;font-size:.9rem;">⚡ Auto-Assign Berdasarkan Tempat PKL</div>
    <div style="font-size:.8rem;color:var(--muted);margin-top:2px;">
      Secara otomatis tetapkan instruktur ke siswa yang belum punya instruktur, berdasarkan tempat PKL yang sama.
    </div>
  </div>
  <form method="POST">
    <input type="hidden" name="aksi" value="auto_assign">
    <button type="submit" class="btn-primary-custom"
            onclick="return confirm('Auto-assign instruktur ke semua siswa yang belum ditetapkan?')">
      <i class="bi bi-lightning-charge"></i> Jalankan Auto-Assign
    </button>
  </form>
</div>

<!-- Stat cards per instruktur -->
<div class="stat-instr-grid">
  <a href="kelompok_instruktur.php" class="stat-instr-card <?= !$filter_instr ? 'active' : '' ?>">
    <div class="sic-num"><?= array_sum(array_column($stat_instr, 'jml_siswa')) ?></div>
    <div class="sic-name">Semua Siswa</div>
    <div class="sic-sub"><?= count($stat_instr) ?> instruktur</div>
  </a>
  <?php foreach ($stat_instr as $si): ?>
  <a href="kelompok_instruktur.php?instr_id=<?= $si['id'] ?><?= $filter_tempat?"&tempat_id=$filter_tempat":'' ?>"
     class="stat-instr-card <?= $filter_instr===$si['id']?'active':'' ?>">
    <div class="sic-num"><?= $si['jml_siswa'] ?></div>
    <div class="sic-name" title="<?= htmlspecialchars($si['nama']) ?>"><?= htmlspecialchars($si['nama']) ?></div>
    <div class="sic-sub"><?= htmlspecialchars($si['nama_tempat'] ?? '-') ?></div>
  </a>
  <?php endforeach; ?>
  <?php
    $unassigned = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM siswa WHERE instruktur_id IS NULL"))['n']);
  ?>
  <?php if ($unassigned): ?>
  <a href="kelompok_instruktur.php?status=unassigned"
     class="stat-instr-card <?= $filter_status==='unassigned'?'active':'' ?>" style="border-color:#fecaca;">
    <div class="sic-num" style="color:#dc2626;"><?= $unassigned ?></div>
    <div class="sic-name">Belum Ada Instruktur</div>
    <div class="sic-sub">perlu ditetapkan</div>
  </a>
  <?php endif; ?>
</div>

<!-- Filter bar -->
<form method="GET" class="filter-bar">
  <?php if ($filter_instr): ?><input type="hidden" name="instr_id" value="<?= $filter_instr ?>"><?php endif; ?>
  <input type="text" name="cari" value="<?= htmlspecialchars($q_cari) ?>" placeholder="🔍 Cari nama / NIS...">
  <select name="tempat_id">
    <option value="">Semua Perusahaan</option>
    <?php foreach ($tempat_list as $tp): ?>
    <option value="<?= $tp['id'] ?>" <?= $filter_tempat===$tp['id']?'selected':''?>><?= htmlspecialchars($tp['nama_tempat']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="kelas">
    <option value="">Semua Kelas</option>
    <?php foreach ($kelas_list as $kl): ?>
    <option value="<?= htmlspecialchars($kl) ?>" <?= $filter_kelas===$kl?'selected':''?>><?= htmlspecialchars($kl) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">Semua Status</option>
    <option value="assigned"   <?= $filter_status==='assigned'?'selected':''?>>✅ Sudah Ada Instruktur</option>
    <option value="unassigned" <?= $filter_status==='unassigned'?'selected':''?>>⚠️ Belum Ada Instruktur</option>
  </select>
  <button type="submit" class="btn-primary-custom" style="padding:.48rem 1rem;font-size:.83rem;">
    <i class="bi bi-funnel"></i> Filter
  </button>
  <a href="kelompok_instruktur.php" class="btn-ghost-si">Reset</a>
</form>

<!-- Assign massal -->
<?php if (count($all_siswa) > 0 && $instr_list): ?>
<form method="POST" id="formMassal">
  <input type="hidden" name="aksi" value="assign_massal">
  <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:.85rem;padding:.75rem 1rem;background:var(--green-50);border-radius:10px;border:1px solid var(--green-200);">
    <span style="font-size:.83rem;font-weight:700;color:var(--green-800);">
      <i class="bi bi-lightning-charge me-1"></i>Assign Massal:
    </span>
    <select name="instr_massal" style="font-size:.83rem;padding:.4rem .75rem;border:1.5px solid var(--border);border-radius:8px;" required>
      <option value="">— Pilih Instruktur —</option>
      <?php foreach ($instr_list as $il): ?>
      <option value="<?= $il['id'] ?>"><?= htmlspecialchars($il['nama']) ?> <?= $il['nama_tempat'] ? "({$il['nama_tempat']})" : '' ?></option>
      <?php endforeach; ?>
    </select>
    <button type="button" onclick="selectAll()" style="background:#fff;border:1px solid var(--border);padding:.4rem .85rem;border-radius:7px;font-size:.8rem;cursor:pointer;">Pilih Semua</button>
    <button type="submit" class="btn-primary-custom" style="padding:.45rem 1rem;font-size:.83rem;"
            onclick="return confirm('Assign siswa terpilih ke instruktur ini?')">
      <i class="bi bi-check2-all me-1"></i>Assign Terpilih
    </button>
    <span id="countSelected" style="font-size:.78rem;color:var(--muted);"></span>
  </div>
<?php endif; ?>

<!-- Tabel siswa -->
<div class="table-card">
  <div class="table-card-header">
    <h5><i class="bi bi-people me-2"></i>Daftar Siswa
      <?php if ($filter_instr && isset($stat_instr[$filter_instr])): ?>
        — <?= htmlspecialchars($stat_instr[$filter_instr]['nama']) ?>
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
      <th style="width:36px;"><input type="checkbox" id="chkAll" onchange="toggleAll(this)" style="accent-color:var(--primary);"></th>
      <th>Nama Siswa</th>
      <th>NIS</th>
      <th>Kelas</th>
      <th>Perusahaan PKL</th>
      <th>Instruktur DU/DI</th>
      <th>Aksi</th>
    </tr></thead>
    <tbody>
    <?php foreach ($all_siswa as $s): ?>
    <tr>
      <td><input type="checkbox" name="siswa_ids[]" value="<?= $s['id'] ?>" class="chk-siswa" style="accent-color:var(--primary);" onchange="updateCount()"></td>
      <td><strong><?= htmlspecialchars($s['nama_siswa']) ?></strong></td>
      <td style="color:var(--muted);font-size:.83rem;"><?= htmlspecialchars($s['nis']) ?></td>
      <td><span style="font-size:.8rem;background:var(--green-50);color:var(--green-700);padding:.2rem .6rem;border-radius:20px;"><?= htmlspecialchars($s['kelas']) ?></span></td>
      <td style="font-size:.83rem;"><?= htmlspecialchars($s['nama_tempat'] ?? '—') ?></td>
      <td>
        <?php if ($s['nama_instruktur']): ?>
          <span class="badge-assigned"><i class="bi bi-person-check me-1"></i><?= htmlspecialchars($s['nama_instruktur']) ?></span>
        <?php else: ?>
          <span class="badge-unassigned"><i class="bi bi-person-x me-1"></i>Belum Ada</span>
        <?php endif; ?>
      </td>
      <td>
        <div style="display:flex;gap:6px;align-items:center;">
          <form method="POST" style="display:inline-flex;gap:5px;align-items:center;">
            <input type="hidden" name="aksi" value="assign">
            <input type="hidden" name="siswa_id" value="<?= $s['id'] ?>">
            <select name="instr_id" class="tbl-sel" onchange="this.form.submit()" title="Tetapkan instruktur">
              <option value="">— Pilih —</option>
              <?php foreach ($instr_list as $il): ?>
              <option value="<?= $il['id'] ?>" <?= $s['instr_id_val']==$il['id']?'selected':''?>>
                <?= htmlspecialchars($il['nama']) ?> <?= $il['nama_tempat']?"({$il['nama_tempat']})":'' ?>
              </option>
              <?php endforeach; ?>
            </select>
          </form>
          <?php if ($s['instr_id_val']): ?>
          <form method="POST" style="display:inline;">
            <input type="hidden" name="aksi" value="unassign">
            <input type="hidden" name="siswa_id" value="<?= $s['id'] ?>">
            <button type="submit" class="btn-danger-sm" title="Lepas dari instruktur"
                    onclick="return confirm('Lepas siswa ini dari instruktur?')">
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

<?php if (count($all_siswa) > 0 && $instr_list): ?>
</form>
<?php endif; ?>

<script>
function toggleAll(chk){document.querySelectorAll('.chk-siswa').forEach(c=>c.checked=chk.checked);updateCount();}
function selectAll(){document.querySelectorAll('.chk-siswa').forEach(c=>c.checked=true);document.getElementById('chkAll').checked=true;updateCount();}
function updateCount(){
  const n=document.querySelectorAll('.chk-siswa:checked').length;
  const el=document.getElementById('countSelected');
  if(el) el.textContent=n>0?`${n} siswa dipilih`:'';
  const all=document.querySelectorAll('.chk-siswa').length;
  const ca=document.getElementById('chkAll');
  if(ca) ca.indeterminate=n>0&&n<all;
}
</script>

<?php require '../layout/footer.php'; ?>
