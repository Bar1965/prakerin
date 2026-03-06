<?php
/**
 * SiPrakerin — Onboarding Profil Siswa
 * Wajib diisi sebelum bisa akses halaman lain
 */
session_start();
require '../../config/database.php';
if (empty($_SESSION['login']) || $_SESSION['role'] !== 'siswa') {
    header("Location: ../../auth/login.php"); exit;
}

$uid  = (int)$_SESSION['user_id'];
$step = (int)($_GET['step'] ?? 1); // 1=info pribadi, 2=orang tua, 3=foto
$err  = '';
$msg  = '';

// Ambil data siswa saat ini
$stmt = mysqli_prepare($conn, "SELECT s.*, u.nama FROM siswa s JOIN users u ON u.id=s.user_id WHERE s.user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $uid); mysqli_stmt_execute($stmt);
$s = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$sid = (int)($s['id'] ?? 0);

// ── Cek apakah sudah lengkap ──
function cekLengkap($s) {
    return !empty($s['jenis_kelamin']) && !empty($s['tempat_lahir'])
        && !empty($s['tanggal_lahir']) && !empty($s['alamat'])
        && !empty($s['no_hp']) && !empty($s['nama_ayah'])
        && !empty($s['no_hp_ortu']) && !empty($s['foto']);
}

// ══ POST HANDLER ══
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // Step 1 — Info Pribadi
    if ($aksi === 'step1') {
        $jk  = $_POST['jenis_kelamin'] ?? '';
        $tl  = trim($_POST['tempat_lahir'] ?? '');
        $tgl = $_POST['tanggal_lahir'] ?? '';
        $al  = trim($_POST['alamat'] ?? '');
        $hp  = trim($_POST['no_hp'] ?? '');
        $em  = trim($_POST['email'] ?? '');

        if (!$jk || !$tl || !$tgl || !$al || !$hp) {
            $err = 'Semua field wajib diisi.';
        } else {
            $u = mysqli_prepare($conn, "UPDATE siswa SET jenis_kelamin=?,tempat_lahir=?,tanggal_lahir=?,alamat=?,no_hp=?,email=? WHERE user_id=?");
            mysqli_stmt_bind_param($u, 'ssssssi', $jk, $tl, $tgl, $al, $hp, $em, $uid);
            mysqli_stmt_execute($u);
            header("Location: index.php?step=2"); exit;
        }
    }

    // Step 2 — Orang Tua
    if ($aksi === 'step2') {
        $na  = trim($_POST['nama_ayah'] ?? '');
        $nm  = trim($_POST['nama_ibu'] ?? '');
        $ao  = trim($_POST['alamat_ortu'] ?? '');
        $ho  = trim($_POST['no_hp_ortu'] ?? '');
        $hom = trim($_POST['no_hp_ortu2'] ?? '');

        if (!$na || !$ho) {
            $err = 'Nama ayah/wali dan nomor HP wajib diisi.';
        } else {
            // Cek kolom nama_ibu ada tidak
            $cek_ibu = mysqli_query($conn, "SHOW COLUMNS FROM siswa LIKE 'nama_ibu'");
            if (mysqli_num_rows($cek_ibu) > 0) {
                $u = mysqli_prepare($conn, "UPDATE siswa SET nama_ayah=?,nama_ibu=?,alamat_ortu=?,no_hp_ortu=?,no_hp_ortu2=? WHERE user_id=?");
                mysqli_stmt_bind_param($u, 'sssssi', $na, $nm, $ao, $ho, $hom, $uid);
            } else {
                $u = mysqli_prepare($conn, "UPDATE siswa SET nama_ayah=?,alamat_ortu=?,no_hp_ortu=? WHERE user_id=?");
                mysqli_stmt_bind_param($u, 'sssi', $na, $ao, $ho, $uid);
            }
            mysqli_stmt_execute($u);
            header("Location: index.php?step=3"); exit;
        }
    }

    // Step 3 — Foto Profil
    if ($aksi === 'step3') {
        $foto_data = $_POST['foto_crop'] ?? '';

        if (empty($foto_data) || strpos($foto_data, 'data:image/') !== 0) {
            $err = 'Foto profil wajib diisi. Ambil foto terlebih dahulu.';
        } else {
            $upload_dir = '../../uploads/profil/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            // Ekstrak base64 & simpan
            $parts      = explode(';base64,', $foto_data);
            $img_binary = base64_decode($parts[1] ?? '');
            $ext        = str_contains($parts[0], 'png') ? 'png' : 'jpg';

            // Hapus foto lama jika ada
            if (!empty($s['foto']) && file_exists('../../' . $s['foto'])) {
                @unlink('../../' . $s['foto']);
            }

            $filename = 'profil_' . $sid . '_' . time() . '.' . $ext;
            if (file_put_contents($upload_dir . $filename, $img_binary)) {
                $foto_path = 'uploads/profil/' . $filename;

                $u = mysqli_prepare($conn, "UPDATE siswa SET foto=?, profil_lengkap=1 WHERE user_id=?");
                mysqli_stmt_bind_param($u, 'si', $foto_path, $uid);
                mysqli_stmt_execute($u);

                $_SESSION['profil_lengkap'] = 1;
                header("Location: ../../siswa/dashboard.php?onboarding=1"); exit;
            } else {
                $err = 'Gagal menyimpan foto. Coba lagi.';
            }
        }
    }
}

// Refresh data
$stmt = mysqli_prepare($conn, "SELECT s.*, u.nama FROM siswa s JOIN users u ON u.id=s.user_id WHERE s.user_id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $uid); mysqli_stmt_execute($stmt);
$s = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lengkapi Profil · SiPrakerin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root { --primary:#15803d; --primary-dark:#166534; }
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family:'Inter',sans-serif; background:linear-gradient(135deg,#f0f4ff 0%,#faf5ff 100%); min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:2rem 1rem; }

  .wrap { width:100%; max-width:560px; }

  /* Header */
  .ob-header { text-align:center; margin-bottom:2rem; }
  .ob-brand { display:inline-flex; align-items:center; gap:10px; margin-bottom:1.25rem; }
  .ob-brand img { width:48px; height:48px; border-radius:12px; }
  .ob-brand span { font-size:1.1rem; font-weight:800; color:#0f172a; }
  .ob-header h1 { font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:.35rem; }
  .ob-header p  { color:#64748b; font-size:.9rem; }

  /* Progress steps */
  .steps { display:flex; align-items:center; gap:0; margin-bottom:2rem; }
  .step-item { flex:1; display:flex; flex-direction:column; align-items:center; gap:6px; position:relative; }
  .step-item:not(:last-child)::after { content:''; position:absolute; top:16px; left:60%; width:80%; height:2px; background:#e2e8f0; z-index:0; }
  .step-item.done::after, .step-item.active::after { background:#15803d; }
  .step-circle { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:.8rem; font-weight:700; z-index:1; border:2px solid #e2e8f0; background:#fff; color:#94a3b8; transition:.3s; }
  .step-item.done   .step-circle { background:#15803d; border-color:#15803d; color:#fff; }
  .step-item.active .step-circle { background:#fff; border-color:#15803d; color:#15803d; box-shadow:0 0 0 4px rgba(21,128,61,.12); }
  .step-label { font-size:.72rem; color:#94a3b8; font-weight:500; text-align:center; }
  .step-item.active .step-label, .step-item.done .step-label { color:#15803d; }

  /* Card */
  .ob-card { background:#fff; border-radius:18px; box-shadow:0 8px 30px rgba(0,0,0,.08); border:1px solid #e2e8f0; padding:2rem; animation:fadeUp .35s ease; }
  @keyframes fadeUp { from{opacity:0;transform:translateY(14px)} to{opacity:1;transform:translateY(0)} }
  .ob-card-title { font-size:1.05rem; font-weight:700; color:#0f172a; margin-bottom:.25rem; }
  .ob-card-sub   { font-size:.83rem; color:#64748b; margin-bottom:1.5rem; }

  /* Form */
  .form-group { margin-bottom:1.1rem; }
  .form-label { display:block; font-size:.82rem; font-weight:600; color:#374151; margin-bottom:.4rem; }
  .form-label .req { color:#ef4444; margin-left:2px; }
  .form-control { width:100%; padding:.6rem .85rem; border:1.5px solid #e2e8f0; border-radius:9px; font-size:.9rem; font-family:'Inter',sans-serif; outline:none; transition:.18s; color:#0f172a; }
  .form-control:focus { border-color:#15803d; box-shadow:0 0 0 3px rgba(21,128,61,.1); }
  select.form-control { cursor:pointer; }
  .row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  @media(max-width:480px) { .row2 { grid-template-columns:1fr; } }

  /* Foto */
  .foto-area { border:2px dashed #bbf7d0; border-radius:14px; padding:1.5rem; text-align:center; background:#f8faff; position:relative; overflow:hidden; }
  .foto-preview { width:140px; height:140px; border-radius:50%; object-fit:cover; border:4px solid #15803d; display:none; margin:0 auto .75rem; }
  .foto-placeholder { width:100px; height:100px; border-radius:50%; background:#dcfce7; display:flex; align-items:center; justify-content:center; margin:0 auto .75rem; font-size:2.5rem; }
  .btn-kamera { background:#15803d; color:#fff; border:none; padding:.65rem 1.5rem; border-radius:9px; font-size:.9rem; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:7px; margin-top:.5rem; transition:.18s; }
  .btn-kamera:hover { background:#166534; }
  .btn-kamera.retake { background:#f0fdf4; color:#374151; }
  .btn-kamera.retake:hover { background:#e2e8f0; }
  .video-wrap { display:none; position:relative; }
  video { width:100%; border-radius:10px; transform:scaleX(-1); max-height:260px; object-fit:cover; }
  .btn-snap { background:#ef4444; color:#fff; border:none; padding:.65rem 2rem; border-radius:9px; font-size:.9rem; font-weight:700; cursor:pointer; margin-top:.75rem; width:100%; transition:.18s; }
  .btn-snap:hover { background:#dc2626; }
  .size-note { font-size:.75rem; color:#94a3b8; margin-top:.5rem; }

  /* Button submit */
  .btn-next { width:100%; background:#15803d; color:#fff; border:none; padding:.85rem; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; margin-top:1.25rem; display:flex; align-items:center; justify-content:center; gap:8px; transition:.18s; }
  .btn-next:hover { background:#166534; transform:translateY(-1px); }
  .btn-next:disabled { background:#94a3b8; cursor:not-allowed; transform:none; }

  /* Alert */
  .alert-err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-radius:9px; padding:.75rem 1rem; font-size:.875rem; margin-bottom:1rem; display:flex; align-items:center; gap:8px; }

  /* Skip link */
  .skip-note { text-align:center; margin-top:1rem; font-size:.8rem; color:#94a3b8; }
</style>
</head>
<body>
<div class="wrap">

  <!-- Header -->
  <div class="ob-header">
    <div class="ob-brand">
      <img src="../../assets/images/logo-sekolah.png" alt="Logo" onerror="this.style.display='none'">
      <span>SiPrakerin</span>
    </div>
    <h1>Lengkapi Profil Anda 👋</h1>
    <p>Halo, <strong><?= htmlspecialchars(explode(' ', $s['nama'])[0]) ?></strong>! Sebelum mulai, isi data diri Anda dulu ya.</p>
  </div>

  <!-- Steps -->
  <div class="steps">
    <div class="step-item <?= $step > 1 ? 'done' : ($step == 1 ? 'active' : '') ?>">
      <div class="step-circle"><?= $step > 1 ? '✓' : '1' ?></div>
      <div class="step-label">Info Pribadi</div>
    </div>
    <div class="step-item <?= $step > 2 ? 'done' : ($step == 2 ? 'active' : '') ?>">
      <div class="step-circle"><?= $step > 2 ? '✓' : '2' ?></div>
      <div class="step-label">Orang Tua</div>
    </div>
    <div class="step-item <?= $step == 3 ? 'active' : '' ?>">
      <div class="step-circle">3</div>
      <div class="step-label">Foto Profil</div>
    </div>
  </div>

  <?php if ($err): ?>
  <div class="alert-err"><i class="bi bi-exclamation-triangle-fill"></i> <?= $err ?></div>
  <?php endif; ?>

  <div class="ob-card">

  <?php if ($step === 1): ?>
  <!-- ══ STEP 1: INFO PRIBADI ══ -->
  <div class="ob-card-title">📋 Data Pribadi</div>
  <div class="ob-card-sub">Isi informasi dasar untuk melengkapi profil PKL Anda.</div>
  <form method="POST">
    <input type="hidden" name="aksi" value="step1">
    <div class="form-group">
      <label class="form-label">Jenis Kelamin <span class="req">*</span></label>
      <select name="jenis_kelamin" class="form-control" required>
        <option value="">— Pilih —</option>
        <option value="pria"   <?= ($s['jenis_kelamin']??'')==='pria'   ?'selected':'' ?>>Laki-laki</option>
        <option value="wanita" <?= ($s['jenis_kelamin']??'')==='wanita' ?'selected':'' ?>>Perempuan</option>
      </select>
    </div>
    <div class="row2">
      <div class="form-group">
        <label class="form-label">Tempat Lahir <span class="req">*</span></label>
        <input type="text" name="tempat_lahir" class="form-control" required
               value="<?= htmlspecialchars($s['tempat_lahir'] ?? '') ?>" placeholder="cth: Padang">
      </div>
      <div class="form-group">
        <label class="form-label">Tanggal Lahir <span class="req">*</span></label>
        <input type="date" name="tanggal_lahir" class="form-control" required
               value="<?= $s['tanggal_lahir'] ?? '' ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Alamat Lengkap <span class="req">*</span></label>
      <textarea name="alamat" class="form-control" rows="3" required
                placeholder="Jl. ... No. ..., Kelurahan, Kecamatan, Kota"><?= htmlspecialchars($s['alamat'] ?? '') ?></textarea>
    </div>
    <div class="row2">
      <div class="form-group">
        <label class="form-label">Nomor HP/WA <span class="req">*</span></label>
        <input type="tel" name="no_hp" class="form-control" required
               value="<?= htmlspecialchars($s['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
      </div>
      <div class="form-group">
        <label class="form-label">Email <small style="color:#94a3b8;">(opsional)</small></label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($s['email'] ?? '') ?>" placeholder="email@gmail.com">
      </div>
    </div>
    <button type="submit" class="btn-next">Lanjut ke Data Orang Tua <i class="bi bi-arrow-right"></i></button>
  </form>

  <?php elseif ($step === 2): ?>
  <!-- ══ STEP 2: ORANG TUA ══ -->
  <div class="ob-card-title">👨‍👩‍👧 Data Orang Tua / Wali</div>
  <div class="ob-card-sub">Diperlukan untuk kontak darurat selama PKL berlangsung.</div>
  <form method="POST">
    <input type="hidden" name="aksi" value="step2">
    <div class="form-group">
      <label class="form-label">Nama Ayah / Wali <span class="req">*</span></label>
      <input type="text" name="nama_ayah" class="form-control" required
             value="<?= htmlspecialchars($s['nama_ayah'] ?? '') ?>" placeholder="Nama lengkap ayah/wali">
    </div>
    <div class="form-group">
      <label class="form-label">Nama Ibu <small style="color:#94a3b8;">(opsional)</small></label>
      <input type="text" name="nama_ibu" class="form-control"
             value="<?= htmlspecialchars($s['nama_ibu'] ?? '') ?>" placeholder="Nama lengkap ibu">
    </div>
    <div class="form-group">
      <label class="form-label">Alamat Orang Tua <small style="color:#94a3b8;">(jika berbeda)</small></label>
      <textarea name="alamat_ortu" class="form-control" rows="2"
                placeholder="Kosongkan jika sama dengan alamat siswa"><?= htmlspecialchars($s['alamat_ortu'] ?? '') ?></textarea>
    </div>
    <div class="row2">
      <div class="form-group">
        <label class="form-label">No. HP Orang Tua / Wali <span class="req">*</span></label>
        <input type="tel" name="no_hp_ortu" class="form-control" required
               value="<?= htmlspecialchars($s['no_hp_ortu'] ?? '') ?>" placeholder="08xxxxxxxxxx">
      </div>
      <div class="form-group">
        <label class="form-label">No. HP Darurat Lain <small style="color:#94a3b8;">(opsional)</small></label>
        <input type="tel" name="no_hp_ortu2" class="form-control"
               value="<?= htmlspecialchars($s['no_hp_ortu2'] ?? '') ?>" placeholder="08xxxxxxxxxx">
      </div>
    </div>
    <button type="submit" class="btn-next">Lanjut ke Foto Profil <i class="bi bi-arrow-right"></i></button>
  </form>

  <?php elseif ($step === 3): ?>
  <!-- ══ STEP 3: FOTO PROFIL ══ -->
  <div class="ob-card-title">📸 Foto Profil</div>
  <div class="ob-card-sub">Ambil foto selfie menggunakan kamera. Pastikan wajah terlihat jelas dan pencahayaan cukup.</div>

  <form method="POST" id="fotoForm">
    <input type="hidden" name="aksi" value="step3">
    <input type="hidden" name="foto_crop" id="fotoCropInput">

    <div class="foto-area" id="fotoArea">
      <div id="placeholder">
        <div class="foto-placeholder">👤</div>
        <div style="font-size:.875rem;color:#64748b;margin-bottom:.75rem;">Belum ada foto profil</div>
      </div>
      <img id="previewImg" class="foto-preview" src="" alt="Preview">

      <div class="video-wrap" id="videoWrap">
        <video id="video" autoplay playsinline></video>
        <button type="button" class="btn-snap" id="btnSnap">
          📷 Ambil Foto Sekarang
        </button>
      </div>

      <canvas id="canvas" style="display:none;"></canvas>
      <!-- Input file tersembunyi untuk galeri -->
      <input type="file" id="inputGaleri" accept="image/jpeg,image/png,image/webp" style="display:none;">

      <div id="btnGroup" style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:.5rem;">
        <button type="button" class="btn-kamera" onclick="startCamera()">
          <i class="bi bi-camera-fill"></i> Kamera
        </button>
        <button type="button" class="btn-kamera" style="background:#0f172a;" onclick="document.getElementById('inputGaleri').click()">
          <i class="bi bi-images"></i> Dari Galeri
        </button>
      </div>
      <div class="size-note">Format: JPG/PNG · Maks. 2MB · Otomatis dikompres</div>
    </div>

    <button type="submit" class="btn-next" id="btnSubmitFoto" disabled>
      <i class="bi bi-check-circle-fill"></i> Selesai & Masuk ke Dashboard
    </button>
  </form>
  <?php endif; ?>

  </div><!-- end ob-card -->

  <div class="skip-note">
    <?= $step > 1 ? '<a href="index.php?step='.($step-1).'" style="color:#94a3b8;text-decoration:none;">← Kembali</a>' : '' ?>
    &nbsp;Langkah <?= $step ?> dari 3
  </div>

</div><!-- end wrap -->

<?php if ($step === 3): ?>
<script>
let stream = null;

// ── Proses & tampilkan gambar (dipakai kamera & galeri) ──
function tampilkanFoto(dataUrl) {
  document.getElementById('previewImg').src = dataUrl;
  document.getElementById('previewImg').style.display = 'block';
  document.getElementById('fotoCropInput').value = dataUrl;
  document.getElementById('videoWrap').style.display = 'none';
  document.getElementById('placeholder').style.display = 'none';
  document.getElementById('btnGroup').innerHTML =
    '<button type="button" class="btn-kamera retake" onclick="retake()"><i class="bi bi-arrow-repeat"></i> Ganti Foto</button>';
  document.getElementById('btnSubmitFoto').disabled = false;
  stream?.getTracks().forEach(t => t.stop()); stream = null;
}

function compressToBase64(source, fromCanvas = false) {
  const canvas = document.getElementById('canvas');
  const size   = 400;
  canvas.width = canvas.height = size;
  const ctx = canvas.getContext('2d');

  if (fromCanvas) {
    // Sudah di-render di video canvas — crop square
    const vw = source.videoWidth, vh = source.videoHeight;
    const side = Math.min(vw, vh);
    const sx = (vw - side)/2, sy = (vh - side)/2;
    ctx.save(); ctx.scale(-1,1); ctx.translate(-size,0);
    ctx.drawImage(source, sx, sy, side, side, 0, 0, size, size);
    ctx.restore();
  } else {
    // Dari gambar (galeri)
    const iw = source.naturalWidth || source.width;
    const ih = source.naturalHeight || source.height;
    const side = Math.min(iw, ih);
    const sx = (iw - side)/2, sy = (ih - side)/2;
    ctx.drawImage(source, sx, sy, side, side, 0, 0, size, size);
  }

  let quality = 0.85;
  let dataUrl = canvas.toDataURL('image/jpeg', quality);
  while (dataUrl.length > 2 * 1024 * 1024 * 1.37 && quality > 0.3) {
    quality -= 0.1;
    dataUrl = canvas.toDataURL('image/jpeg', quality);
  }
  return dataUrl;
}

// ── Kamera ──
async function startCamera() {
  try {
    stream = await navigator.mediaDevices.getUserMedia({ video:{ facingMode:'user', width:640, height:480 } });
    document.getElementById('video').srcObject = stream;
    document.getElementById('videoWrap').style.display = 'block';
    document.getElementById('placeholder').style.display = 'none';
    document.getElementById('previewImg').style.display = 'none';
    document.getElementById('btnGroup').style.display = 'none';
  } catch(e) {
    alert('Tidak bisa akses kamera: ' + e.message);
  }
}

document.getElementById('btnSnap').addEventListener('click', () => {
  const dataUrl = compressToBase64(document.getElementById('video'), true);
  tampilkanFoto(dataUrl);
});

// ── Galeri ──
document.getElementById('inputGaleri').addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  if (file.size > 10 * 1024 * 1024) { alert('File terlalu besar (maks 10MB sebelum dikompress).'); return; }

  const reader = new FileReader();
  reader.onload = e => {
    const img = new Image();
    img.onload = () => {
      const dataUrl = compressToBase64(img, false);
      tampilkanFoto(dataUrl);
    };
    img.src = e.target.result;
  };
  reader.readAsDataURL(file);
  this.value = ''; // reset input supaya bisa pilih file sama lagi
});

function retake() {
  document.getElementById('previewImg').style.display = 'none';
  document.getElementById('fotoCropInput').value = '';
  document.getElementById('btnSubmitFoto').disabled = true;
  document.getElementById('placeholder').style.display = 'block';
  document.getElementById('btnGroup').innerHTML = `
    <button type="button" class="btn-kamera" onclick="startCamera()">
      <i class="bi bi-camera-fill"></i> Kamera
    </button>
    <button type="button" class="btn-kamera" style="background:#0f172a;" onclick="document.getElementById('inputGaleri').click()">
      <i class="bi bi-images"></i> Dari Galeri
    </button>`;
}

document.getElementById('fotoForm').addEventListener('submit', e => {
  if (!document.getElementById('fotoCropInput').value) {
    e.preventDefault();
    alert('Ambil atau pilih foto profil terlebih dahulu!');
  }
});
</script>
<?php endif; ?>
</body>
</html>
