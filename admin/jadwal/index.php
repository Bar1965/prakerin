<?php
session_start();
require '../../config/database.php';
require '../middleware/auth_admin.php';

$page_title = 'Jadwal Prakerin';
$success = $error = '';

// ── Ambil jadwal aktif (yang mencakup hari ini atau paling baru) ──
$jadwal_aktif = null;
$res = mysqli_query($conn, "SELECT * FROM jadwal_prakerin ORDER BY tanggal_mulai DESC LIMIT 1");
if ($res) $jadwal_aktif = mysqli_fetch_assoc($res);

// ── Ambil semua hari libur nasional (tempat_pkl_id IS NULL) ──
$libur_nasional = [];
$res = mysqli_query($conn, "SELECT * FROM hari_libur WHERE tempat_pkl_id IS NULL ORDER BY tanggal ASC");
while ($r = mysqli_fetch_assoc($res)) $libur_nasional[] = $r;

// ── Ambil hari libur khusus per tempat PKL ──
$libur_tempat = [];
$res = mysqli_query($conn, "
    SELECT hl.*, tp.nama_tempat
    FROM hari_libur hl
    JOIN tempat_pkl tp ON tp.id = hl.tempat_pkl_id
    ORDER BY hl.tanggal ASC
");
while ($r = mysqli_fetch_assoc($res)) $libur_tempat[] = $r;

// ── Ambil status konfirmasi DU/DI ──
$konfirmasi_list = [];
if ($jadwal_aktif) {
    $jid = (int)$jadwal_aktif['id'];
    $res = mysqli_query($conn, "
        SELECT kp.*, tp.nama_tempat, u.nama AS nama_instruktur
        FROM konfirmasi_prakerin kp
        JOIN tempat_pkl tp ON tp.id = kp.tempat_pkl_id
        JOIN instruktur i ON i.id = kp.instruktur_id
        JOIN users u ON u.id = i.user_id
        WHERE kp.jadwal_id = $jid
        ORDER BY kp.dikonfirmasi_at ASC
    ");
    while ($r = mysqli_fetch_assoc($res)) $konfirmasi_list[$r['tempat_pkl_id']] = $r;
}

// ── Semua tempat PKL ──
$tempat_list = [];
$res = mysqli_query($conn, "SELECT id, nama_tempat FROM tempat_pkl ORDER BY nama_tempat");
while ($r = mysqli_fetch_assoc($res)) $tempat_list[] = $r;

// ═══════════════════════════════════════
// POST HANDLERS
// ═══════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // --- Simpan / Update Jadwal ---
    if ($aksi === 'simpan_jadwal') {
        $ta      = trim($_POST['tahun_ajaran'] ?? '');
        $mulai   = $_POST['tanggal_mulai'] ?? '';
        $selesai = $_POST['tanggal_selesai'] ?? '';
        $ket     = trim($_POST['keterangan'] ?? '');

        if ($ta && $mulai && $selesai && $mulai < $selesai) {
            $uid = (int)$_SESSION['user_id'];
            if ($jadwal_aktif) {
                $id = (int)$jadwal_aktif['id'];
                $stmt = mysqli_prepare($conn,
                    "UPDATE jadwal_prakerin SET tahun_ajaran=?, tanggal_mulai=?, tanggal_selesai=?, keterangan=? WHERE id=?"
                );
                mysqli_stmt_bind_param($stmt, 'ssssi', $ta, $mulai, $selesai, $ket, $id);
            } else {
                $stmt = mysqli_prepare($conn,
                    "INSERT INTO jadwal_prakerin (tahun_ajaran, tanggal_mulai, tanggal_selesai, keterangan, created_by) VALUES (?,?,?,?,?)"
                );
                mysqli_stmt_bind_param($stmt, 'ssssi', $ta, $mulai, $selesai, $ket, $uid);
            }
            mysqli_stmt_execute($stmt);
            $success = 'Jadwal prakerin berhasil disimpan!';
            header("Location: index.php?ok=jadwal");
            exit;
        } else {
            $error = 'Lengkapi semua field dan pastikan tanggal mulai < selesai.';
        }
    }

    // --- Tambah Hari Libur Nasional ---
    if ($aksi === 'tambah_libur') {
        $tgl  = $_POST['tanggal'] ?? '';
        $nama = trim($_POST['nama_libur'] ?? '');
        $uid  = (int)$_SESSION['user_id'];
        if ($tgl && $nama) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO hari_libur (tanggal, nama_libur, tempat_pkl_id, dibuat_oleh) VALUES (?, ?, NULL, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'ssi', $tgl, $nama, $uid);
            mysqli_stmt_execute($stmt);
            header("Location: index.php?ok=libur");
            exit;
        }
    }

    // --- Hapus Hari Libur ---
    if ($aksi === 'hapus_libur') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            mysqli_query($conn, "DELETE FROM hari_libur WHERE id = $id");
        }
        header("Location: index.php?ok=hapus");
        exit;
    }
}

$ok = $_GET['ok'] ?? '';
if ($ok === 'jadwal') $success = 'Jadwal prakerin berhasil disimpan!';
if ($ok === 'libur')  $success = 'Hari libur berhasil ditambahkan!';
if ($ok === 'hapus')  $success = 'Hari libur berhasil dihapus.';

require '../layout/header.php';
?>

<div class="page-header">
    <h1>📅 Jadwal Prakerin</h1>
    <p>Atur tanggal pelaksanaan PKL dan kelola hari libur</p>
</div>

<?php if ($success): ?>
<div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-error-custom mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
<?php endif; ?>

<!-- ══ FORM JADWAL ══ -->
<div class="row g-4 mb-4">
  <div class="col-12 col-lg-5">
    <div class="form-card">
      <div class="form-card-header"><i class="bi bi-calendar-range me-2"></i>Periode Prakerin</div>
      <div class="form-card-body">
        <form method="POST">
          <input type="hidden" name="aksi" value="simpan_jadwal">
          <div class="mb-3">
            <label class="form-label">Tahun Ajaran</label>
            <input type="text" name="tahun_ajaran" class="form-control"
                   placeholder="cth: 2024/2025"
                   value="<?= htmlspecialchars($jadwal_aktif['tahun_ajaran'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" class="form-control"
                   value="<?= $jadwal_aktif['tanggal_mulai'] ?? '' ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" class="form-control"
                   value="<?= $jadwal_aktif['tanggal_selesai'] ?? '' ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Keterangan <small class="text-muted">(opsional)</small></label>
            <textarea name="keterangan" class="form-control" rows="2"
                      placeholder="cth: Prakerin Gelombang 1"><?= htmlspecialchars($jadwal_aktif['keterangan'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn-primary-custom w-100">
            <i class="bi bi-save me-1"></i>
            <?= $jadwal_aktif ? 'Perbarui Jadwal' : 'Simpan Jadwal' ?>
          </button>
        </form>

        <?php if ($jadwal_aktif): ?>
        <!-- Info jadwal aktif -->
        <?php
            $today = date('Y-m-d');
            $mulai   = $jadwal_aktif['tanggal_mulai'];
            $selesai = $jadwal_aktif['tanggal_selesai'];
            if ($today < $mulai) { $badge = 'badge-yellow'; $status = 'Belum Dimulai'; }
            elseif ($today > $selesai) { $badge = 'badge-gray'; $status = 'Selesai'; }
            else { $badge = 'badge-green'; $status = 'Sedang Berlangsung'; }
        ?>
        <div style="margin-top:1.25rem;padding:1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
            <div style="font-size:.75rem;color:#64748b;margin-bottom:.5rem;font-weight:600;">STATUS JADWAL AKTIF</div>
            <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
                <div>
                    <div style="font-weight:700;font-size:.9rem;"><?= htmlspecialchars($jadwal_aktif['tahun_ajaran']) ?></div>
                    <div style="font-size:.8rem;color:#64748b;">
                        <?= date('d M Y', strtotime($mulai)) ?> – <?= date('d M Y', strtotime($selesai)) ?>
                    </div>
                </div>
                <span class="badge <?= $badge ?>" style="padding:.3rem .8rem;border-radius:20px;font-size:.75rem;"><?= $status ?></span>
            </div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ══ FORM HARI LIBUR NASIONAL ══ -->
  <div class="col-12 col-lg-7">
    <div class="form-card">
      <div class="form-card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-calendar-x me-2"></i>Hari Libur Nasional</span>
        <small class="text-muted"><?= count($libur_nasional) ?> hari libur</small>
      </div>
      <div class="form-card-body">
        <!-- Tambah libur -->
        <form method="POST" class="mb-3">
          <input type="hidden" name="aksi" value="tambah_libur">
          <div class="row g-2 align-items-end">
            <div class="col-5">
              <label class="form-label">Tanggal</label>
              <input type="date" name="tanggal" class="form-control" required>
            </div>
            <div class="col-5">
              <label class="form-label">Nama Hari Libur</label>
              <input type="text" name="nama_libur" class="form-control" placeholder="cth: Hari Natal" required>
            </div>
            <div class="col-2">
              <button type="submit" class="btn-primary-custom w-100" style="padding:.55rem;">
                <i class="bi bi-plus-lg"></i>
              </button>
            </div>
          </div>
        </form>

        <!-- List libur nasional -->
        <?php if ($libur_nasional): ?>
        <div style="max-height:280px;overflow-y:auto;border-radius:8px;border:1px solid #e2e8f0;">
          <table class="tbl">
            <thead><tr>
              <th>Tanggal</th><th>Nama Libur</th><th style="width:50px;"></th>
            </tr></thead>
            <tbody>
            <?php foreach ($libur_nasional as $lib): ?>
            <tr>
              <td><?= date('d M Y', strtotime($lib['tanggal'])) ?></td>
              <td><?= htmlspecialchars($lib['nama_libur']) ?></td>
              <td>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="aksi" value="hapus_libur">
                  <input type="hidden" name="id" value="<?= $lib['id'] ?>">
                  <button type="submit" class="btn-danger-sm btn-hapus"
                          data-nama="<?= htmlspecialchars($lib['nama_libur']) ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <div class="empty-state" style="padding:1.5rem;">
          <i class="bi bi-calendar-x" style="font-size:2rem;opacity:.3;display:block;margin-bottom:.5rem;"></i>
          Belum ada hari libur nasional
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══ STATUS KONFIRMASI DU/DI ══ -->
<div class="table-card mb-4">
  <div class="table-card-header">
    <h5><i class="bi bi-building-check me-2"></i>Status Konfirmasi DU/DI</h5>
    <?php if ($jadwal_aktif): ?>
    <small class="text-muted">Jadwal: <?= htmlspecialchars($jadwal_aktif['tahun_ajaran']) ?></small>
    <?php endif; ?>
  </div>
  <?php if (!$jadwal_aktif): ?>
  <div class="empty-state"><i class="bi bi-calendar-plus"></i>Buat jadwal prakerin terlebih dahulu</div>
  <?php elseif (!$tempat_list): ?>
  <div class="empty-state"><i class="bi bi-building"></i>Belum ada tempat PKL terdaftar</div>
  <?php else: ?>
  <table class="tbl">
    <thead><tr>
      <th>Tempat PKL</th>
      <th>Status Konfirmasi</th>
      <th>Dikonfirmasi Oleh</th>
      <th>Waktu</th>
    </tr></thead>
    <tbody>
    <?php foreach ($tempat_list as $tp):
        $k = $konfirmasi_list[$tp['id']] ?? null;
    ?>
    <tr>
      <td><span style="font-weight:600;"><?= htmlspecialchars($tp['nama_tempat']) ?></span></td>
      <td>
        <?php if ($k): ?>
          <span class="badge badge-green" style="padding:.3rem .85rem;border-radius:20px;font-size:.75rem;">
            <i class="bi bi-check-circle-fill me-1"></i>Sudah Dikonfirmasi
          </span>
        <?php else: ?>
          <span class="badge badge-yellow" style="padding:.3rem .85rem;border-radius:20px;font-size:.75rem;">
            <i class="bi bi-hourglass me-1"></i>Menunggu Konfirmasi
          </span>
        <?php endif; ?>
      </td>
      <td><?= $k ? htmlspecialchars($k['nama_instruktur']) : '<span style="color:#94a3b8;">—</span>' ?></td>
      <td><?= $k ? date('d M Y, H:i', strtotime($k['dikonfirmasi_at'])) : '<span style="color:#94a3b8;">—</span>' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<!-- ══ HARI LIBUR KHUSUS PER TEMPAT ══ -->
<?php if ($libur_tempat): ?>
<div class="table-card">
  <div class="table-card-header">
    <h5><i class="bi bi-calendar2-x me-2"></i>Hari Libur Khusus Tempat PKL</h5>
    <small class="text-muted">Ditambahkan oleh DU/DI masing-masing</small>
  </div>
  <table class="tbl">
    <thead><tr>
      <th>Tempat PKL</th><th>Tanggal</th><th>Keterangan</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($libur_tempat as $lib): ?>
    <tr>
      <td><?= htmlspecialchars($lib['nama_tempat']) ?></td>
      <td><?= date('d M Y', strtotime($lib['tanggal'])) ?></td>
      <td><?= htmlspecialchars($lib['nama_libur']) ?></td>
      <td>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="aksi" value="hapus_libur">
          <input type="hidden" name="id" value="<?= $lib['id'] ?>">
          <button type="submit" class="btn-danger-sm btn-hapus"
                  data-nama="libur <?= htmlspecialchars($lib['nama_libur']) ?>">
            <i class="bi bi-trash"></i>
          </button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php require '../layout/footer.php'; ?>
