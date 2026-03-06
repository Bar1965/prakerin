<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';
$page_title = 'Import Guru';
$msg = $_GET['msg'] ?? '';
$err = $_GET['err'] ?? '';
$imported = (int)($_GET['imported'] ?? 0);
$skipped  = (int)($_GET['skipped']  ?? 0);
require '../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-start page-header">
  <div>
    <h1>Import Guru</h1>
    <p>Upload file CSV untuk menambah data guru secara massal.</p>
  </div>
  <a href="index.php" class="btn-edit-sm"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<?php if($msg==='ok'): ?>
  <div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i><?= $imported ?> guru ditambahkan, <?= $skipped ?> dilewati (sudah ada).</div>
<?php endif; ?>
<?php if($err): ?>
  <div class="alert-error-custom mb-3"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<div class="form-card" style="max-width:640px;">
  <div class="form-card-header"><i class="bi bi-upload me-2"></i>Upload File CSV</div>
  <div class="form-card-body">

    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:1rem;margin-bottom:1.25rem;font-size:.85rem;">
      <div style="font-weight:600;color:#14532d;margin-bottom:.5rem;"><i class="bi bi-info-circle me-1"></i>Format CSV yang diperlukan:</div>
      <code style="display:block;background:#fff;border:1px solid #e2e8f0;padding:6px 10px;border-radius:6px;margin-bottom:.4rem;font-size:.82rem;">nama,nip</code>
      <div style="color:#64748b;font-size:.78rem;margin-bottom:.3rem;">Contoh data:</div>
      <code style="display:block;background:#fff;border:1px solid #e2e8f0;padding:4px 10px;border-radius:6px;font-size:.78rem;margin-bottom:.2rem;">Budi Santoso S.Kom,197501012005011001</code>
      <code style="display:block;background:#fff;border:1px solid #e2e8f0;padding:4px 10px;border-radius:6px;font-size:.78rem;margin-bottom:.5rem;">Siti Rahayu S.Pd,198003152008012002</code>
      <div style="color:#64748b;font-size:.78rem;"><i class="bi bi-exclamation-triangle me-1"></i>Username = NIP &nbsp;|&nbsp; Password default = <strong>123456</strong> &nbsp;|&nbsp; Guru yang sudah ada akan dilewati.</div>
    </div>

    <form action="import_proses.php" method="POST" enctype="multipart/form-data">
      <div id="dropZone" style="border:2px dashed #e2e8f0;border-radius:10px;padding:2.5rem;text-align:center;cursor:pointer;transition:.2s;background:#f8fafc;" onclick="document.getElementById('csvFile').click()">
        <i class="bi bi-file-earmark-csv" style="font-size:2.5rem;color:#94a3b8;display:block;margin-bottom:.5rem;"></i>
        <div style="font-weight:600;color:#374151;">Klik untuk pilih file CSV</div>
        <div style="font-size:.82rem;color:#94a3b8;margin-top:.25rem;">atau drag & drop di sini (.csv)</div>
        <div id="dzChosen" style="font-size:.84rem;color:#15803d;font-weight:600;margin-top:.5rem;display:none;"></div>
        <input type="file" name="file" id="csvFile" accept=".csv,text/csv" required style="display:none;">
      </div>

      <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn-primary-custom"><i class="bi bi-upload"></i> Import Sekarang</button>
        <a href="index.php" class="btn-edit-sm">Batal</a>
      </div>
    </form>
  </div>
</div>

<script>
const dz = document.getElementById('dropZone');
const cf = document.getElementById('csvFile');
const dc = document.getElementById('dzChosen');
cf.addEventListener('change', () => { if(cf.files[0]){dc.textContent='✓ '+cf.files[0].name;dc.style.display='block';} });
dz.addEventListener('dragover', e=>{e.preventDefault();dz.style.borderColor='#15803d';dz.style.background='#f0fdf4';});
dz.addEventListener('dragleave', ()=>{dz.style.borderColor='#e2e8f0';dz.style.background='#f8fafc';});
dz.addEventListener('drop', e=>{
  e.preventDefault();dz.style.borderColor='#e2e8f0';dz.style.background='#f8fafc';
  const f=e.dataTransfer.files[0];
  if(f&&f.name.endsWith('.csv')){const dt=new DataTransfer();dt.items.add(f);cf.files=dt.files;dc.textContent='✓ '+f.name;dc.style.display='block';}
  else{alert('Hanya file .csv yang diterima!');}
});
</script>

<?php require '../layout/footer.php'; ?>
