<?php
require 'middleware/auth_instruktur.php';
require '../config/database.php';

$page_title = 'Ganti Password';
$user_id    = $_SESSION['user_id'];
$error      = '';
$success    = '';

// ── Proses form ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new  = $_POST['new_password']  ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($new !== $conf) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($conn,
            "UPDATE users SET password=?, must_change_password=0 WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['must_change_password'] = false;
            header("Location: dashboard/index.php?pw_changed=1");
            exit;
        } else {
            $error = 'Gagal menyimpan password. Coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ganti Password · SiPrakerin</title>
<script>(function(){var t=localStorage.getItem('siprakerin_theme');if(t==='dark')document.documentElement.setAttribute('data-theme','dark');})();</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/darkmode.css">
<style>
  :root {
    --primary: #15803d; --primary-dark: #166534;
    --bg: #f0fdf4; --surface: #fff; --border: #e2e8f0;
    --text: #0f172a; --muted: #64748b; --r: 14px;
    --danger: #dc2626; --warning: #d97706;
  }
  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    font-family: 'Plus Jakarta Sans', sans-serif;
    background: var(--bg); color: var(--text);
    min-height: 100vh; display: flex; align-items: center; justify-content: center;
    padding: 1rem;
  }
  .card {
    background: var(--surface, #fff); border-radius: var(--r, 14px);
    box-shadow: 0 8px 32px rgba(0,0,0,.10);
    width: 100%; max-width: 440px; overflow: hidden;
    border: 1px solid var(--border, #e2e8f0);
  }
  .card-header {
    background: linear-gradient(135deg, #14532d 0%, #15803d 100%);
    padding: 2rem 2rem 1.5rem;
    color: #fff; text-align: center;
  }
  .card-header .icon {
    width: 56px; height: 56px; background: rgba(255,255,255,.15);
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    margin: 0 auto 1rem; font-size: 1.6rem;
  }
  .card-header h1 { font-size: 1.25rem; font-weight: 700; margin-bottom: .3rem; }
  .card-header p  { font-size: .85rem; opacity: .85; }
  .card-body { padding: 2rem; }
  .alert {
    padding: .85rem 1rem; border-radius: 10px; font-size: .875rem;
    margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: .6rem;
  }
  .alert-warning {
    background: #fffbeb; color: #92400e; border: 1px solid #fde68a;
  }
  .alert-danger  {
    background: #fef2f2; color: #991b1b; border: 1px solid #fecaca;
  }
  .form-group { margin-bottom: 1.1rem; }
  label { display: block; font-size: .82rem; font-weight: 600; color: var(--muted); margin-bottom: .4rem; }
  .input-wrap { position: relative; }
  input[type=password], input[type=text] {
    width: 100%; padding: .7rem 2.8rem .7rem 1rem;
    border: 1.5px solid var(--border); border-radius: 10px;
    font-family: inherit; font-size: .9rem; color: var(--text);
    transition: border-color .2s, box-shadow .2s;
    background: #f8fafc;
  }
  input:focus {
    outline: none; border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(21,128,61,.12);
    background: #fff;
  }
  .toggle-pw {
    position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
    cursor: pointer; color: var(--muted); font-size: 1rem;
    background: none; border: none; padding: 0;
    transition: color .15s;
  }
  .toggle-pw:hover { color: var(--primary); }
  .hint { font-size: .78rem; color: var(--muted); margin-top: .35rem; }
  .strength-bar {
    height: 4px; border-radius: 4px; background: #e2e8f0;
    margin-top: .4rem; overflow: hidden;
  }
  .strength-bar span {
    display: block; height: 100%; border-radius: 4px;
    transition: width .3s, background .3s;
    width: 0;
  }
  .btn {
    width: 100%; padding: .85rem; border: none; border-radius: 10px;
    font-family: inherit; font-size: .95rem; font-weight: 700;
    cursor: pointer; transition: all .2s; display: flex; align-items: center;
    justify-content: center; gap: .5rem; margin-top: .5rem;
  }
  .btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff; box-shadow: 0 4px 12px rgba(21,128,61,.25);
  }
  .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(21,128,61,.35); }
  .btn-primary:active { transform: translateY(0); }
  .footer-note {
    text-align: center; font-size: .8rem; color: var(--muted);
    padding: 1rem 2rem 1.5rem; border-top: 1px solid var(--border);
  }
  .footer-note a { color: var(--primary); font-weight: 600; }
</style>
</head>
<body>

<div class="card">
  <div class="card-header">
    <div class="icon"><i class="bi bi-shield-lock-fill"></i></div>
    <h1>Buat Password Baru</h1>
    <p>Halo, <strong><?= htmlspecialchars($_SESSION['nama']) ?></strong>! Demi keamanan akun, silakan ganti password default Anda sekarang.</p>
  </div>
  <div class="card-body">

    <div class="alert alert-warning">
      <i class="bi bi-exclamation-triangle-fill" style="flex-shrink:0;margin-top:1px"></i>
      <span>Anda <strong>tidak dapat mengakses</strong> fitur lain sebelum mengganti password.</span>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="bi bi-x-circle-fill" style="flex-shrink:0;margin-top:1px"></i>
      <span><?= htmlspecialchars($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label for="new_password">Password Baru</label>
        <div class="input-wrap">
          <input type="password" id="new_password" name="new_password"
                 placeholder="Minimal 8 karakter" required autocomplete="new-password">
          <button type="button" class="toggle-pw" onclick="togglePw('new_password', this)">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <div class="strength-bar"><span id="strength-bar-fill"></span></div>
        <div class="hint" id="strength-label">Minimal 8 karakter, gunakan kombinasi huruf &amp; angka.</div>
      </div>

      <div class="form-group">
        <label for="confirm_password">Konfirmasi Password</label>
        <div class="input-wrap">
          <input type="password" id="confirm_password" name="confirm_password"
                 placeholder="Ulangi password baru" required autocomplete="new-password">
          <button type="button" class="toggle-pw" onclick="togglePw('confirm_password', this)">
            <i class="bi bi-eye"></i>
          </button>
        </div>
        <div class="hint" id="match-hint"></div>
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-lg"></i> Simpan Password Baru
      </button>
    </form>
  </div>
  <div class="footer-note">
    Bukan Anda? <a href="../auth/logout.php">Logout</a>
  </div>
</div>

<script>
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  const isHidden = inp.type === 'password';
  inp.type = isHidden ? 'text' : 'password';
  btn.querySelector('i').className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
}

// Strength meter
const newPw  = document.getElementById('new_password');
const confPw = document.getElementById('confirm_password');
const bar    = document.getElementById('strength-bar-fill');
const label  = document.getElementById('strength-label');
const matchH = document.getElementById('match-hint');

newPw.addEventListener('input', function() {
  const v = this.value;
  let score = 0;
  if (v.length >= 8)  score++;
  if (v.length >= 12) score++;
  if (/[A-Z]/.test(v)) score++;
  if (/[0-9]/.test(v)) score++;
  if (/[^A-Za-z0-9]/.test(v)) score++;

  const levels = [
    { w: '0%',   bg: '#e2e8f0', text: 'Minimal 8 karakter, gunakan kombinasi huruf &amp; angka.' },
    { w: '25%',  bg: '#dc2626', text: '⚠ Lemah — tambahkan huruf besar atau angka.' },
    { w: '50%',  bg: '#d97706', text: '😐 Cukup — coba lebih panjang.' },
    { w: '75%',  bg: '#2563eb', text: '👍 Bagus!' },
    { w: '100%', bg: '#16a34a', text: '💪 Kuat!' },
  ];
  const l = levels[Math.min(score, 4)];
  bar.style.width = l.w;
  bar.style.background = l.bg;
  label.innerHTML = l.text;

  checkMatch();
});

confPw.addEventListener('input', checkMatch);

function checkMatch() {
  if (!confPw.value) { matchH.textContent = ''; return; }
  if (newPw.value === confPw.value) {
    matchH.innerHTML = '<span style="color:#16a34a">✓ Password cocok</span>';
  } else {
    matchH.innerHTML = '<span style="color:#dc2626">✗ Belum cocok</span>';
  }
}
</script>

</body>
</html>
