<?php
session_start();
require '../../config/database.php';
require '../../config/helpers.php';
require '../middleware/auth_admin.php';

$page_title = 'Konfigurasi WA OTP';
$success = $error = '';

// Simpan config
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'simpan_config') {
        $fields = ['wa_api_token','wa_api_url','wa_sender','otp_aktif'];
        foreach ($fields as $f) {
            $val = trim($_POST[$f] ?? '');
            $s   = mysqli_prepare($conn, "INSERT INTO config_app (key_name, key_value) VALUES (?,?) ON DUPLICATE KEY UPDATE key_value=?");
            mysqli_stmt_bind_param($s, 'sss', $f, $val, $val);
            mysqli_stmt_execute($s);
        }
        $success = 'Konfigurasi berhasil disimpan!';
    }

    if ($aksi === 'test_wa') {
        $no    = trim($_POST['test_no'] ?? '');
        $token = getConfig($conn, 'wa_api_token');
        if (!$no || !$token) {
            $error = 'Isi dulu token API dan nomor tujuan.';
        } else {
            $ok = kirimWA($conn, $no, "✅ *SiPrakerin* — Test berhasil! Konfigurasi WhatsApp API Anda sudah berjalan dengan baik.");
            if ($ok) $success = "Pesan test berhasil dikirim ke $no!";
            else     $error   = "Gagal kirim pesan. Cek token API dan pastikan nomor benar.";
        }
    }
}

// Ambil config saat ini
$cfg = [];
$res = mysqli_query($conn, "SELECT key_name, key_value FROM config_app");
while ($r = mysqli_fetch_assoc($res)) $cfg[$r['key_name']] = $r['key_value'];

require '../layout/header.php';
?>

<div style="max-width:680px;">
<div class="page-header">
  <h1>📱 Konfigurasi WhatsApp OTP</h1>
  <p>Atur integrasi WhatsApp API untuk fitur OTP login</p>
</div>

<?php if ($success): ?>
<div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-error-custom mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
<?php endif; ?>

<!-- Panduan -->
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1.5rem;">
  <div style="font-weight:700;color:#166534;margin-bottom:.5rem;font-size:.9rem;">📖 Cara Setup Fonnte (Gratis)</div>
  <ol style="font-size:.83rem;color:#14532d;margin:0;padding-left:1.25rem;line-height:1.9;">
    <li>Daftar di <a href="https://fonnte.com" target="_blank" style="color:#15803d;font-weight:600;">fonnte.com</a></li>
    <li>Scan QR dengan WhatsApp yang akan dipakai sebagai pengirim</li>
    <li>Salin <strong>Token API</strong> dari dashboard Fonnte</li>
    <li>Isi form di bawah, lalu test kirim pesan</li>
  </ol>
</div>

<div class="form-card">
  <div class="form-card-header"><i class="bi bi-gear me-2"></i>Pengaturan API</div>
  <div class="form-card-body">
    <form method="POST">
      <input type="hidden" name="aksi" value="simpan_config">

      <div class="row g-3">
        <div class="col-12">
          <div class="mb-3">
            <label class="form-label">Status OTP</label>
            <select name="otp_aktif" class="form-select">
              <option value="1" <?= ($cfg['otp_aktif']??'1')==='1'?'selected':'' ?>>✅ Aktif — OTP dikirim ke WA saat login browser baru</option>
              <option value="0" <?= ($cfg['otp_aktif']??'1')==='0'?'selected':'' ?>>❌ Nonaktif — Login langsung tanpa OTP</option>
            </select>
            <div class="form-hint">Jika nonaktif, semua login tidak memerlukan OTP.</div>
          </div>
        </div>

        <div class="col-12">
          <div class="mb-3">
            <label class="form-label">Token API Fonnte</label>
            <input type="text" name="wa_api_token" class="form-control font-monospace"
                   value="<?= htmlspecialchars($cfg['wa_api_token'] ?? '') ?>"
                   placeholder="Paste token dari dashboard Fonnte...">
            <div class="form-hint">Didapat dari dashboard Fonnte setelah scan QR.</div>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <div class="mb-3">
            <label class="form-label">URL API <small class="text-muted">(biarkan jika pakai Fonnte)</small></label>
            <input type="text" name="wa_api_url" class="form-control"
                   value="<?= htmlspecialchars($cfg['wa_api_url'] ?? 'https://api.fonnte.com/send') ?>">
          </div>
        </div>

        <div class="col-12 col-md-6">
          <div class="mb-3">
            <label class="form-label">Nomor Pengirim <small class="text-muted">(opsional)</small></label>
            <input type="text" name="wa_sender" class="form-control"
                   value="<?= htmlspecialchars($cfg['wa_sender'] ?? '') ?>"
                   placeholder="08xxxxxxxxxx">
          </div>
        </div>
      </div>

      <button type="submit" class="btn-primary-custom">
        <i class="bi bi-save me-1"></i> Simpan Konfigurasi
      </button>
    </form>
  </div>
</div>

<!-- Test Kirim -->
<div class="form-card mt-3">
  <div class="form-card-header"><i class="bi bi-send me-2"></i>Test Kirim Pesan WA</div>
  <div class="form-card-body">
    <form method="POST">
      <input type="hidden" name="aksi" value="test_wa">
      <div style="display:flex;gap:10px;align-items:flex-end;">
        <div style="flex:1;">
          <label class="form-label">Nomor Tujuan (format: 08xxxxxxxxxx)</label>
          <input type="text" name="test_no" class="form-control" placeholder="08123456789" required>
        </div>
        <button type="submit" class="btn-primary-custom" style="background:#25d366;white-space:nowrap;">
          <i class="bi bi-whatsapp me-1"></i> Kirim Test
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Info no_hp users -->
<div class="form-card mt-3" style="border-color:#fde68a;background:#fefce8;">
  <div class="form-card-body">
    <div style="display:flex;gap:10px;align-items:flex-start;">
      <i class="bi bi-info-circle-fill" style="color:#d97706;font-size:1.1rem;flex-shrink:0;margin-top:2px;"></i>
      <div style="font-size:.84rem;color:#92400e;">
        <strong>Penting:</strong> Pastikan setiap user (guru, instruktur, siswa) sudah memiliki
        <strong>nomor HP/WA</strong> yang tersimpan di profil mereka. OTP hanya dikirim jika nomor HP terisi.
        Jika nomor HP kosong, login akan langsung masuk tanpa OTP.
      </div>
    </div>
  </div>
</div>

</div>

<?php require '../layout/footer.php'; ?>
