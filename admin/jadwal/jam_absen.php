<?php
session_start();
require '../../config/database.php';
require '../../config/helpers.php';
require '../middleware/auth_admin.php';

$page_title = 'Pengaturan Jam Absen';
$success = $error = '';

// ── Ambil config saat ini ──
$jam_masuk_def  = getConfig($conn, 'jam_masuk_default')  ?? '07:00';
$jam_pulang_def = getConfig($conn, 'jam_pulang_default') ?? '16:00';
$batas_menit    = getConfig($conn, 'batas_masuk_menit')  ?? '30';
$pengingat_aktif = getConfig($conn, 'pengingat_absen_aktif') ?? '1';
$pengingat_jam  = getConfig($conn, 'pengingat_jam')      ?? '06:30';

// ── Ambil override per tempat PKL ──
$override_list = [];
$res = mysqli_query($conn, "
    SELECT jat.*, tp.nama_tempat
    FROM jam_absen_tempat jat
    JOIN tempat_pkl tp ON tp.id = jat.tempat_pkl_id
    ORDER BY tp.nama_tempat
");
if ($res) while ($r = mysqli_fetch_assoc($res)) $override_list[$r['tempat_pkl_id']] = $r;

// ── Semua tempat PKL ──
$tempat_list = [];
$res = mysqli_query($conn, "SELECT id, nama_tempat FROM tempat_pkl ORDER BY nama_tempat");
while ($r = mysqli_fetch_assoc($res)) $tempat_list[] = $r;

// ══ POST ══
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan_default') {
        $jm = $_POST['jam_masuk']  ?? '07:00';
        $jp = $_POST['jam_pulang'] ?? '16:00';
        $bm = (int)($_POST['batas_menit'] ?? 30);
        $pa = $_POST['pengingat_aktif'] ?? '0';
        $pj = $_POST['pengingat_jam'] ?? '06:30';

        $fields = [
            'jam_masuk_default'   => $jm,
            'jam_pulang_default'  => $jp,
            'batas_masuk_menit'   => $bm,
            'pengingat_absen_aktif' => $pa,
            'pengingat_jam'       => $pj,
        ];
        foreach ($fields as $k => $v) {
            $s = mysqli_prepare($conn, "INSERT INTO config_app (key_name, key_value) VALUES (?,?) ON DUPLICATE KEY UPDATE key_value=?");
            mysqli_stmt_bind_param($s, 'sss', $k, $v, $v);
            mysqli_stmt_execute($s);
        }
        header("Location: jam_absen.php?ok=1"); exit;
    }

    if ($aksi === 'hapus_override') {
        $tid = (int)($_POST['tempat_id'] ?? 0);
        if ($tid) mysqli_query($conn, "DELETE FROM jam_absen_tempat WHERE tempat_pkl_id=$tid");
        header("Location: jam_absen.php?ok=hapus"); exit;
    }
}

if ($_GET['ok'] ?? '' === '1')     $success = 'Pengaturan jam absen berhasil disimpan!';
if ($_GET['ok'] ?? '' === 'hapus') $success = 'Override jam absen berhasil dihapus.';

require '../layout/header.php';
?>

<div style="max-width:780px;">
<div class="page-header">
  <h1>⏰ Pengaturan Jam Absen</h1>
  <p>Atur jam masuk & pulang default. DU/DI bisa override per tempat PKL.</p>
</div>

<?php if ($success): ?>
<div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<!-- Pengaturan Default -->
<div class="form-card mb-4">
  <div class="form-card-header"><i class="bi bi-sliders me-2"></i>Jam Absen Default (Berlaku Semua Tempat PKL)</div>
  <div class="form-card-body">
    <form method="POST">
      <input type="hidden" name="aksi" value="simpan_default">
      <div class="row g-3">
        <div class="col-6 col-md-3">
          <label class="form-label">Jam Masuk</label>
          <input type="time" name="jam_masuk" class="form-control" value="<?= $jam_masuk_def ?>" required>
          <div class="form-hint">Batas awal absen masuk</div>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Jam Pulang</label>
          <input type="time" name="jam_pulang" class="form-control" value="<?= $jam_pulang_def ?>" required>
          <div class="form-hint">Batas awal absen pulang</div>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Toleransi Terlambat</label>
          <div style="display:flex;align-items:center;gap:8px;">
            <input type="number" name="batas_menit" class="form-control" value="<?= $batas_menit ?>" min="0" max="120" style="width:80px;">
            <span style="color:#64748b;font-size:.85rem;">menit</span>
          </div>
          <div class="form-hint">Siswa masih dihitung hadir</div>
        </div>

        <div class="col-12"><hr style="border-color:#f1f5f9;"></div>

        <div class="col-6 col-md-3">
          <label class="form-label">Pengingat Absen</label>
          <select name="pengingat_aktif" class="form-select">
            <option value="1" <?= $pengingat_aktif==='1'?'selected':'' ?>>✅ Aktif</option>
            <option value="0" <?= $pengingat_aktif==='0'?'selected':'' ?>>❌ Nonaktif</option>
          </select>
        </div>
        <div class="col-6 col-md-3">
          <label class="form-label">Jam Kirim Pengingat</label>
          <input type="time" name="pengingat_jam" class="form-control" value="<?= $pengingat_jam ?>">
          <div class="form-hint">WA/notif dikirim jam ini</div>
        </div>
      </div>

      <div style="margin-top:1.25rem;padding:.85rem 1rem;background:#f8fafc;border-radius:9px;font-size:.83rem;color:#64748b;border:1px solid #e2e8f0;">
        <i class="bi bi-info-circle me-1"></i>
        Pengingat absen dikirim otomatis ke semua siswa yang belum absen pada hari aktif prakerin.
        Pastikan konfigurasi WA API sudah diisi di <a href="../config/wa.php" style="color:#15803d;">Konfigurasi WA OTP</a>.
      </div>

      <button type="submit" class="btn-primary-custom mt-3">
        <i class="bi bi-save me-1"></i> Simpan Pengaturan Default
      </button>
    </form>
  </div>
</div>

<!-- Override per Tempat PKL -->
<div class="table-card">
  <div class="table-card-header">
    <h5><i class="bi bi-building me-2"></i>Override Jam per Tempat PKL</h5>
    <small class="text-muted">DU/DI bisa set jam berbeda. Kosong = pakai jam default.</small>
  </div>
  <?php if (!$tempat_list): ?>
  <div class="empty-state">Belum ada tempat PKL terdaftar.</div>
  <?php else: ?>
  <table class="tbl">
    <thead><tr>
      <th>Tempat PKL</th>
      <th>Jam Masuk</th>
      <th>Jam Pulang</th>
      <th>Toleransi</th>
      <th>Status</th>
      <th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($tempat_list as $tp):
      $ov = $override_list[$tp['id']] ?? null;
    ?>
    <tr>
      <td><strong><?= htmlspecialchars($tp['nama_tempat']) ?></strong></td>
      <td><?= $ov ? date('H:i', strtotime($ov['jam_masuk'])) : '<span style="color:#94a3b8;">'.$jam_masuk_def.' (default)</span>' ?></td>
      <td><?= $ov ? date('H:i', strtotime($ov['jam_pulang'])) : '<span style="color:#94a3b8;">'.$jam_pulang_def.' (default)</span>' ?></td>
      <td><?= $ov ? $ov['batas_masuk_menit'].' mnt' : '<span style="color:#94a3b8;">'.$batas_menit.' mnt</span>' ?></td>
      <td>
        <?php if ($ov): ?>
          <span class="badge badge-blue" style="padding:.25rem .7rem;border-radius:20px;font-size:.72rem;">Override DU/DI</span>
        <?php else: ?>
          <span class="badge badge-gray" style="padding:.25rem .7rem;border-radius:20px;font-size:.72rem;">Default</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if ($ov): ?>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="aksi" value="hapus_override">
          <input type="hidden" name="tempat_id" value="<?= $tp['id'] ?>">
          <button type="submit" class="btn-danger-sm"
                  onclick="return confirm('Reset ke jam default?')">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
          </button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
</div>

<?php require '../layout/footer.php'; ?>
