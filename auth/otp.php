<?php
session_start();
require '../config/database.php';
require '../config/helpers.php';

// Kalau tidak ada sesi OTP pending, redirect ke login
if (empty($_SESSION['otp_user_id'])) {
    header("Location: login.php"); exit;
}

$user_id    = (int)$_SESSION['otp_user_id'];
$nama       = $_SESSION['otp_nama'] ?? '';
$no_hp      = $_SESSION['otp_no_hp'] ?? '';
$remember   = $_SESSION['otp_remember'] ?? false;
$error      = '';
$kirim_ulang = false;

// ── Kirim ulang OTP ──
if (isset($_GET['resend'])) {
    $kode  = generateOTP($conn, $user_id);
    $pesan = "🔐 *SiPrakerin*\nKode OTP login Anda: *$kode*\n\nBerlaku 5 menit. Jangan berikan kode ini kepada siapapun.";
    kirimWA($conn, $no_hp, $pesan);
    $kirim_ulang = true;
}

// ── Verifikasi OTP ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode_input  = trim(implode('', $_POST['otp'] ?? []));
    $trust_check = isset($_POST['trust_device']);

    if (strlen($kode_input) !== 6 || !ctype_digit($kode_input)) {
        $error = 'Kode OTP harus 6 angka.';
    } elseif (!verifikasiOTP($conn, $user_id, $kode_input)) {
        $error = 'Kode OTP salah atau sudah kedaluwarsa.';
    } else {
        // OTP benar — login
        $stmt = mysqli_prepare($conn, "SELECT id, nama, role, no_hp FROM users WHERE id=? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'i', $user_id);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        // Percayai device ini jika dicentang
        if ($trust_check) trustDevice($conn, $user_id);

        // Bersihkan sesi OTP
        unset($_SESSION['otp_user_id'], $_SESSION['otp_nama'], $_SESSION['otp_role'],
              $_SESSION['otp_no_hp'], $_SESSION['otp_remember']);

        $_SESSION['login']   = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['role']    = $user['role'];

        $map = ['admin'=>'../admin/dashboard/index.php','siswa'=>'../siswa/dashboard.php','instruktur'=>'../instruktur/dashboard/index.php','guru'=>'../guru/dashboard/index.php'];
        header("Location: " . ($map[$user['role']] ?? 'login.php'));
        exit;
    }
}

// Sensor nomor HP: 08xx-xxxx-x789 → 08**-****-*789
$no_sensor = preg_replace('/(\d{2})\d+(\d{3})/', '$1' . str_repeat('*', max(0, strlen($no_hp)-5)) . '$2', $no_hp);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verifikasi OTP · SiPrakerin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root { --primary:#15803d; --green:#25d366; }
  * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
  body { background:#f8fafc; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:1.5rem; }
  .card { background:#fff; border-radius:18px; box-shadow:0 10px 30px rgba(0,0,0,.1); border:1px solid #e2e8f0; padding:2.5rem; width:100%; max-width:420px; animation:fadeIn .4s ease; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(-16px)} to{opacity:1;transform:translateY(0)} }
  .brand { text-align:center; margin-bottom:1.75rem; }
  .brand-icon { width:64px; height:64px; background:linear-gradient(135deg,#15803d,#15803d); border-radius:16px; display:inline-flex; align-items:center; justify-content:center; font-size:1.8rem; margin-bottom:.75rem; }
  .brand h2 { font-size:1.25rem; font-weight:800; color:#0f172a; }
  .brand p { color:#64748b; font-size:.875rem; margin-top:.3rem; }
  .wa-badge { display:inline-flex; align-items:center; gap:6px; background:#f0fdf4; color:#15803d; border:1px solid #bbf7d0; border-radius:20px; padding:.35rem .9rem; font-size:.8rem; font-weight:600; margin-top:.5rem; }
  .otp-group { display:flex; gap:8px; justify-content:center; margin:1.5rem 0; }
  .otp-input { width:46px; height:56px; border:2px solid #e2e8f0; border-radius:10px; text-align:center; font-size:1.4rem; font-weight:700; color:#0f172a; outline:none; transition:.18s; -moz-appearance:textfield; }
  .otp-input::-webkit-inner-spin-button { -webkit-appearance:none; }
  .otp-input:focus { border-color:#15803d; box-shadow:0 0 0 3px rgba(21,128,61,.12); }
  .otp-input.filled { border-color:#15803d; background:#eff6ff; }
  .btn-verify { width:100%; background:#15803d; color:#fff; border:none; padding:.85rem; border-radius:10px; font-size:1rem; font-weight:700; cursor:pointer; transition:.18s; display:flex; align-items:center; justify-content:center; gap:8px; }
  .btn-verify:hover { background:#166534; transform:translateY(-1px); }
  .btn-verify:disabled { background:#94a3b8; cursor:not-allowed; transform:none; }
  .resend-row { text-align:center; margin-top:1.1rem; font-size:.85rem; color:#64748b; }
  .resend-row a { color:#15803d; font-weight:600; text-decoration:none; }
  .resend-row a:hover { text-decoration:underline; }
  .trust-row { display:flex; align-items:center; gap:8px; margin:.85rem 0; font-size:.85rem; color:#64748b; }
  .trust-row input { accent-color:#15803d; width:16px; height:16px; }
  .alert-err { background:#fef2f2; color:#991b1b; border:1px solid #fecaca; border-radius:8px; padding:.75rem 1rem; font-size:.875rem; margin-bottom:1rem; display:flex; align-items:center; gap:8px; }
  .alert-ok  { background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; border-radius:8px; padding:.75rem 1rem; font-size:.875rem; margin-bottom:1rem; display:flex; align-items:center; gap:8px; }
  .back-link { text-align:center; margin-top:1.1rem; }
  .back-link a { color:#94a3b8; font-size:.82rem; text-decoration:none; }
  .back-link a:hover { color:#64748b; }
  .timer { font-weight:700; color:#15803d; }
</style>
</head>
<body>
<div class="card">
  <div class="brand">
    <div class="brand-icon">🔐</div>
    <h2>Verifikasi OTP</h2>
    <p>Halo, <strong><?= htmlspecialchars(explode(' ', $nama)[0]) ?></strong>! Kode dikirim ke WhatsApp</p>
    <div class="wa-badge">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="#25d366"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.558 4.118 1.531 5.845L.057 23.885l6.195-1.625A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.75A9.742 9.742 0 016.7 20.08l-.337-.2-3.677.964.982-3.587-.22-.348A9.75 9.75 0 1112 21.75z"/></svg>
      <?= htmlspecialchars($no_sensor) ?>
    </div>
  </div>

  <?php if ($error): ?>
  <div class="alert-err">⚠️ <?= $error ?></div>
  <?php endif; ?>

  <?php if ($kirim_ulang): ?>
  <div class="alert-ok">✅ Kode OTP baru sudah dikirim ke WhatsApp Anda.</div>
  <?php endif; ?>

  <form method="POST" id="otpForm">
    <div class="otp-group">
      <?php for ($i = 0; $i < 6; $i++): ?>
      <input type="number" name="otp[]" class="otp-input" maxlength="1" min="0" max="9"
             inputmode="numeric" autocomplete="one-time-code" required>
      <?php endfor; ?>
    </div>

    <div class="trust-row">
      <input type="checkbox" name="trust_device" id="trustDevice" value="1">
      <label for="trustDevice">Percayai perangkat ini selama 30 hari</label>
    </div>

    <button type="submit" class="btn-verify" id="btnVerify">
      ✅ Verifikasi & Masuk
    </button>
  </form>

  <div class="resend-row">
    Tidak dapat kode? &nbsp;
    <span id="timerWrap">Kirim ulang dalam <span class="timer" id="countdown">60</span>s</span>
    <span id="resendWrap" style="display:none;">
      <a href="otp.php?resend=1">Kirim Ulang</a>
    </span>
  </div>

  <div class="back-link">
    <a href="../auth/login.php">← Batal dan kembali ke login</a>
  </div>
</div>

<script>
// ── Auto-focus & auto-advance OTP inputs ──
const inputs = document.querySelectorAll('.otp-input');
inputs.forEach((inp, i) => {
  inp.addEventListener('input', e => {
    const val = e.target.value.replace(/[^0-9]/g,'');
    e.target.value = val.slice(-1);
    if (val && i < inputs.length - 1) inputs[i+1].focus();
    e.target.classList.toggle('filled', !!val);
  });
  inp.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !e.target.value && i > 0) inputs[i-1].focus();
    if (e.key === 'ArrowLeft'  && i > 0) inputs[i-1].focus();
    if (e.key === 'ArrowRight' && i < inputs.length-1) inputs[i+1].focus();
  });
  inp.addEventListener('paste', e => {
    e.preventDefault();
    const digits = (e.clipboardData.getData('text') || '').replace(/\D/g,'').slice(0,6);
    digits.split('').forEach((d, j) => {
      if (inputs[j]) { inputs[j].value = d; inputs[j].classList.add('filled'); }
    });
    const next = Math.min(digits.length, inputs.length - 1);
    inputs[next].focus();
  });
});
inputs[0].focus();

// ── Countdown kirim ulang ──
let secs = 60;
const countEl  = document.getElementById('countdown');
const timerWrap  = document.getElementById('timerWrap');
const resendWrap = document.getElementById('resendWrap');
const timer = setInterval(() => {
  secs--;
  countEl.textContent = secs;
  if (secs <= 0) {
    clearInterval(timer);
    timerWrap.style.display  = 'none';
    resendWrap.style.display = 'inline';
  }
}, 1000);
</script>
</body>
</html>
