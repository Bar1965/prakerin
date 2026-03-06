<?php
session_start();
require '../../config/database.php';
require '../../config/helpers.php';
require '../middleware/auth_instruktur.php';

$page_title = 'Jam Absen Tempat PKL';
$uid = (int)$_SESSION['user_id'];

// Ambil tempat PKL instruktur
$instr = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT i.id, i.tempat_pkl_id, tp.nama_tempat
     FROM instruktur i LEFT JOIN tempat_pkl tp ON tp.id=i.tempat_pkl_id
     WHERE i.user_id=$uid LIMIT 1"
));
$tempat_id = (int)($instr['tempat_pkl_id'] ?? 0);

// Config default dari admin
$jam_masuk_def  = getConfig($conn, 'jam_masuk_default')  ?? '07:00';
$jam_pulang_def = getConfig($conn, 'jam_pulang_default') ?? '16:00';
$batas_def      = getConfig($conn, 'batas_masuk_menit')  ?? '30';

// Override milik tempat ini
$override = null;
if ($tempat_id) {
    $r = mysqli_query($conn, "SELECT * FROM jam_absen_tempat WHERE tempat_pkl_id=$tempat_id LIMIT 1");
    if ($r) $override = mysqli_fetch_assoc($r);
}

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tempat_id) {
    $jm = $_POST['jam_masuk']  ?? $jam_masuk_def;
    $jp = $_POST['jam_pulang'] ?? $jam_pulang_def;
    $bm = (int)($_POST['batas_menit'] ?? $batas_def);

    if ($jm >= $jp) {
        $error = 'Jam masuk harus lebih awal dari jam pulang.';
    } else {
        $stmt = mysqli_prepare($conn,
            "INSERT INTO jam_absen_tempat (tempat_pkl_id, jam_masuk, jam_pulang, batas_masuk_menit, dibuat_oleh)
             VALUES (?,?,?,?,?)
             ON DUPLICATE KEY UPDATE jam_masuk=VALUES(jam_masuk), jam_pulang=VALUES(jam_pulang), batas_masuk_menit=VALUES(batas_masuk_menit), dibuat_oleh=VALUES(dibuat_oleh)"
        );
        mysqli_stmt_bind_param($stmt, 'issii', $tempat_id, $jm, $jp, $bm, $uid);
        mysqli_stmt_execute($stmt);
        header("Location: jam_absen.php?ok=1"); exit;
    }
}
if ($_GET['ok'] ?? '' === '1') $success = 'Jam absen berhasil disimpan!';

require '../layout/header.php';
?>

<div style="max-width:540px;">
<div class="page-header">
  <h1>⏰ Jam Absen Tempat PKL</h1>
  <p>Override jam masuk & pulang khusus untuk <strong><?= htmlspecialchars($instr['nama_tempat'] ?? 'tempat PKL Anda') ?></strong></p>
</div>

<?php if ($success): ?>
<div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-error-custom mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
<?php endif; ?>

<?php if (!$tempat_id): ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:2rem;text-align:center;color:#94a3b8;">
  <i class="bi bi-building" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
  Akun Anda belum terhubung ke tempat PKL.
</div>
<?php else: ?>

<!-- Info Default -->
<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.9rem 1.1rem;margin-bottom:1.25rem;font-size:.84rem;color:#64748b;">
  <i class="bi bi-info-circle me-1"></i>
  <strong>Jam default dari sekolah:</strong>
  Masuk <?= $jam_masuk_def ?> · Pulang <?= $jam_pulang_def ?> · Toleransi <?= $batas_def ?> menit.
  Anda bisa override untuk tempat PKL Anda saja.
</div>

<div class="form-card">
  <div class="form-card-header">
    <i class="bi bi-clock me-2"></i>Atur Jam Absen
    <?php if ($override): ?>
    <span style="margin-left:auto;font-size:.73rem;background:#dcfce7;color:#166534;padding:.2rem .65rem;border-radius:20px;">
      Sudah dioverride
    </span>
    <?php endif; ?>
  </div>
  <div class="form-card-body">
    <form method="POST">
      <div class="row g-3">
        <div class="col-6">
          <label class="form-label">Jam Masuk</label>
          <input type="time" name="jam_masuk" class="form-control"
                 value="<?= $override ? substr($override['jam_masuk'],0,5) : $jam_masuk_def ?>" required>
          <div class="form-hint">Siswa absen mulai jam ini</div>
        </div>
        <div class="col-6">
          <label class="form-label">Jam Pulang</label>
          <input type="time" name="jam_pulang" class="form-control"
                 value="<?= $override ? substr($override['jam_pulang'],0,5) : $jam_pulang_def ?>" required>
          <div class="form-hint">Absen pulang mulai jam ini</div>
        </div>
        <div class="col-12">
          <label class="form-label">Toleransi Terlambat</label>
          <div style="display:flex;align-items:center;gap:10px;">
            <input type="number" name="batas_menit" class="form-control"
                   value="<?= $override['batas_masuk_menit'] ?? $batas_def ?>"
                   min="0" max="120" style="width:90px;">
            <span style="color:#64748b;font-size:.85rem;">menit</span>
          </div>
          <div class="form-hint">Siswa masih dihitung hadir jika terlambat dalam batas ini</div>
        </div>
      </div>
      <div style="margin-top:1rem;padding:.8rem 1rem;background:#fffbea;border:1px solid #fde68a;border-radius:8px;font-size:.8rem;color:#92400e;">
        <i class="bi bi-exclamation-triangle me-1"></i>
        Setting ini hanya berlaku untuk siswa di <strong><?= htmlspecialchars($instr['nama_tempat']) ?></strong>.
        Siswa di tempat lain tidak terpengaruh.
      </div>
      <button type="submit" class="btn-primary-custom mt-3 w-100">
        <i class="bi bi-save me-1"></i> Simpan Jam Absen
      </button>
    </form>
  </div>
</div>
<?php endif; ?>
</div>

<?php require '../layout/footer.php'; ?>
