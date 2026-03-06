<?php
session_start();
require '../../config/database.php';
if(empty($_SESSION['login'])||$_SESSION['role']!=='admin'){header("Location: ../../auth/login.php");exit;}
$page_title = 'Kelola Kelas';

$msg=''; $err='';

// TAMBAH
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='tambah'){
  $nama   = trim($_POST['nama_kelas']??'');
  $tingkat= trim($_POST['tingkat']??'');
  $jurusan= trim($_POST['jurusan']??'');
  if(!$nama||!$tingkat){ $err="Nama kelas dan tingkat wajib diisi!"; }
  else {
    $ins = mysqli_prepare($conn,"INSERT INTO kelas(nama_kelas,tingkat,jurusan) VALUES(?,?,?)");
    mysqli_stmt_bind_param($ins,"sss",$nama,$tingkat,$jurusan);
    if(mysqli_stmt_execute($ins)) $msg="Kelas <strong>$nama</strong> berhasil ditambahkan!";
    else $err="Gagal menambah kelas. Nama kelas mungkin sudah ada.";
  }
}

// HAPUS
if(isset($_GET['hapus'])){
  $id = (int)$_GET['hapus'];
  // Cek apakah ada siswa yang pakai kelas ini
  $ck = mysqli_prepare($conn,"SELECT COUNT(*) c FROM siswa s JOIN kelas k ON s.kelas=k.nama_kelas WHERE k.id=?");
  mysqli_stmt_bind_param($ck,"i",$id); mysqli_stmt_execute($ck);
  $jml = mysqli_fetch_assoc(mysqli_stmt_get_result($ck))['c'];
  if($jml>0){ $err="Tidak bisa hapus — ada $jml siswa di kelas ini."; }
  else {
    $del = mysqli_prepare($conn,"DELETE FROM kelas WHERE id=?");
    mysqli_stmt_bind_param($del,"i",$id);
    if(mysqli_stmt_execute($del)) $msg="Kelas berhasil dihapus.";
    else $err="Gagal menghapus kelas.";
  }
}

// EDIT
if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='edit'){
  $id     = (int)($_POST['id']??0);
  $nama   = trim($_POST['nama_kelas']??'');
  $tingkat= trim($_POST['tingkat']??'');
  $jurusan= trim($_POST['jurusan']??'');
  if(!$nama||!$tingkat){ $err="Nama kelas dan tingkat wajib diisi!"; }
  else {
    // Update juga di tabel siswa
    $upd = mysqli_prepare($conn,"UPDATE kelas SET nama_kelas=?,tingkat=?,jurusan=? WHERE id=?");
    mysqli_stmt_bind_param($upd,"sssi",$nama,$tingkat,$jurusan,$id);
    if(mysqli_stmt_execute($upd)){
      // Sync nama kelas di tabel siswa (opsional, kalau mau nama ikut berubah)
      $msg="Kelas berhasil diperbarui!";
    } else $err="Gagal update. Nama kelas mungkin sudah ada.";
  }
}

// Ambil semua kelas
$qk = mysqli_query($conn,"
  SELECT k.*, 
    (SELECT COUNT(*) FROM siswa s WHERE s.kelas COLLATE utf8mb4_unicode_ci = k.nama_kelas COLLATE utf8mb4_unicode_ci) jml_siswa
  FROM kelas k ORDER BY k.tingkat, k.nama_kelas
");
$kelas_list=[];
while($k=mysqli_fetch_assoc($qk)) $kelas_list[]=$k;

require '../layout/header.php';
?>

<?php if($msg): ?><div style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:9px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.84rem;"><i class="bi bi-check-circle me-2"></i><?= $msg ?></div><?php endif; ?>
<?php if($err): ?><div style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:9px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.84rem;"><i class="bi bi-exclamation-circle me-2"></i><?= $err ?></div><?php endif; ?>

<div class="row g-3">
  <!-- FORM TAMBAH -->
  <div class="col-12 col-lg-4">
    <div class="card-si" style="position:sticky;top:calc(58px + 1rem);">
      <div class="card-si-header">
        <i class="bi bi-plus-circle-fill" style="color:#15803d;"></i> Tambah Kelas Baru
      </div>
      <div style="padding:1.1rem;">
        <form method="POST">
          <input type="hidden" name="action" value="tambah">
          <div class="mb-3">
            <label class="lbl">Nama Kelas <span style="color:#dc2626;">*</span></label>
            <input type="text" name="nama_kelas" class="inp" placeholder="Contoh: XI RPL 3" required>
            <div style="font-size:.71rem;color:#94a3b8;margin-top:3px;">Tulis lengkap, misal: XI TKJ 1</div>
          </div>
          <div class="mb-3">
            <label class="lbl">Tingkat <span style="color:#dc2626;">*</span></label>
            <select name="tingkat" class="inp" required>
              <option value="">-- Pilih --</option>
              <option value="X">X (Sepuluh)</option>
              <option value="XI">XI (Sebelas)</option>
              <option value="XII">XII (Dua Belas)</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="lbl">Jurusan</label>
            <input type="text" name="jurusan" class="inp" placeholder="Contoh: Rekayasa Perangkat Lunak"
                   list="jurusan-list">
            <datalist id="jurusan-list">
              <option value="Rekayasa Perangkat Lunak">
              <option value="Teknik Komputer dan Jaringan">
              <option value="Multimedia">
              <option value="Akuntansi">
              <option value="Administrasi Perkantoran">
              <option value="Teknik Kendaraan Ringan">
            </datalist>
          </div>
          <button type="submit" class="btn-primary-si" style="width:100%;justify-content:center;">
            <i class="bi bi-plus-lg"></i> Tambah Kelas
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- DAFTAR KELAS -->
  <div class="col-12 col-lg-8">
    <!-- Ringkasan per tingkat -->
    <div style="display:flex;gap:.5rem;margin-bottom:.85rem;flex-wrap:wrap;">
      <?php
      $per_tingkat = ['X'=>0,'XI'=>0,'XII'=>0];
      foreach($kelas_list as $k) $per_tingkat[$k['tingkat']]++;
      $clrs = ['X'=>'#15803d','XI'=>'#15803d','XII'=>'#15803d'];
      foreach($per_tingkat as $t=>$jml):
      ?>
      <div style="background:#fff;border:1px solid #e2e8f0;border-radius:9px;padding:.5rem .9rem;
           display:flex;align-items:center;gap:7px;">
        <span style="font-weight:800;font-size:1rem;color:<?= $clrs[$t] ?>;"><?= $jml ?></span>
        <span style="font-size:.75rem;color:#64748b;">Kelas <?= $t ?></span>
      </div>
      <?php endforeach; ?>
      <div style="background:#fff;border:1px solid #e2e8f0;border-radius:9px;padding:.5rem .9rem;
           display:flex;align-items:center;gap:7px;">
        <span style="font-weight:800;font-size:1rem;color:#1e293b;"><?= count($kelas_list) ?></span>
        <span style="font-size:.75rem;color:#64748b;">Total Kelas</span>
      </div>
    </div>

    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
      <div style="padding:.85rem 1.1rem;border-bottom:1px solid #f1f5f9;font-weight:700;font-size:.875rem;display:flex;align-items:center;gap:8px;">
        <i class="bi bi-list-ul" style="color:#15803d;"></i> Daftar Kelas
      </div>
      <table style="width:100%;border-collapse:collapse;">
        <thead>
          <tr style="background:#f8fafc;">
            <th style="padding:.65rem 1rem;font-size:.7rem;font-weight:700;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase;border-bottom:1px solid #e2e8f0;text-align:left;">Nama Kelas</th>
            <th style="padding:.65rem 1rem;font-size:.7rem;font-weight:700;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase;border-bottom:1px solid #e2e8f0;text-align:left;">Tingkat</th>
            <th style="padding:.65rem 1rem;font-size:.7rem;font-weight:700;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase;border-bottom:1px solid #e2e8f0;text-align:left;">Jurusan</th>
            <th style="padding:.65rem 1rem;font-size:.7rem;font-weight:700;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase;border-bottom:1px solid #e2e8f0;text-align:center;">Siswa</th>
            <th style="padding:.65rem 1rem;font-size:.7rem;font-weight:700;color:#94a3b8;letter-spacing:.05em;text-transform:uppercase;border-bottom:1px solid #e2e8f0;text-align:center;">Aksi</th>
          </tr>
        </thead>
        <tbody>
        <?php if(empty($kelas_list)): ?>
        <tr><td colspan="5" style="text-align:center;padding:2.5rem;color:#94a3b8;">
          <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:.4rem;"></i>
          Belum ada data kelas.
        </td></tr>
        <?php endif; ?>
        <?php foreach($kelas_list as $k):
          $tc = ['X'=>'#dcfce7','XI'=>'#dcfce7','XII'=>'#ede9fe'];
          $tv = ['X'=>'#166534','XI'=>'#166534','XII'=>'#15803d'];
          $bg = $tc[$k['tingkat']]??'#f1f5f9';
          $fg = $tv[$k['tingkat']]??'#475569';
        ?>
        <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
          <td style="padding:.8rem 1rem;">
            <span style="font-weight:700;font-size:.875rem;"><?= htmlspecialchars($k['nama_kelas']) ?></span>
          </td>
          <td style="padding:.8rem 1rem;">
            <span style="background:<?= $bg ?>;color:<?= $fg ?>;padding:3px 9px;border-radius:6px;font-size:.72rem;font-weight:700;"><?= $k['tingkat'] ?></span>
          </td>
          <td style="padding:.8rem 1rem;font-size:.82rem;color:#64748b;">
            <?= htmlspecialchars($k['jurusan']??'-') ?>
          </td>
          <td style="padding:.8rem 1rem;text-align:center;">
            <span style="font-weight:700;font-size:.9rem;color:<?= $k['jml_siswa']>0?'#15803d':'#94a3b8' ?>;"><?= $k['jml_siswa'] ?></span>
          </td>
          <td style="padding:.8rem 1rem;text-align:center;">
            <div style="display:flex;gap:.35rem;justify-content:center;">
              <button onclick="bukaEdit(<?= $k['id'] ?>,'<?= htmlspecialchars($k['nama_kelas'],ENT_QUOTES) ?>','<?= $k['tingkat'] ?>','<?= htmlspecialchars($k['jurusan']??'',ENT_QUOTES) ?>')"
                      style="background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;padding:.3rem .65rem;border-radius:7px;font-size:.75rem;font-weight:600;cursor:pointer;transition:.15s;"
                      onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                <i class="bi bi-pencil"></i> Edit
              </button>
              <?php if($k['jml_siswa']==0): ?>
              <a href="?hapus=<?= $k['id'] ?>"
                 onclick="return confirm('Hapus kelas <?= htmlspecialchars($k['nama_kelas'],ENT_QUOTES) ?>?')"
                 style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:.3rem .65rem;border-radius:7px;font-size:.75rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;gap:4px;transition:.15s;"
                 onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fef2f2'">
                <i class="bi bi-trash"></i> Hapus
              </a>
              <?php else: ?>
              <span style="background:#f0fdf4;color:#94a3b8;border:1px solid #e2e8f0;padding:.3rem .65rem;border-radius:7px;font-size:.72rem;"
                    title="Ada siswa di kelas ini">
                <i class="bi bi-lock"></i>
              </span>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL EDIT -->
<div id="modalEdit" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;
     align-items:center;justify-content:center;padding:1rem;">
  <div style="background:#fff;border-radius:14px;width:100%;max-width:420px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.2);">
    <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;font-weight:700;display:flex;justify-content:space-between;align-items:center;">
      <span><i class="bi bi-pencil me-2" style="color:#15803d;"></i>Edit Kelas</span>
      <button onclick="tutupEdit()" style="background:none;border:none;font-size:1.25rem;color:#94a3b8;cursor:pointer;">×</button>
    </div>
    <form method="POST" style="padding:1.1rem;">
      <input type="hidden" name="action" value="edit">
      <input type="hidden" name="id" id="editId">
      <div class="mb-3">
        <label class="lbl">Nama Kelas</label>
        <input type="text" name="nama_kelas" id="editNama" class="inp" required>
      </div>
      <div class="mb-3">
        <label class="lbl">Tingkat</label>
        <select name="tingkat" id="editTingkat" class="inp" required>
          <option value="X">X (Sepuluh)</option>
          <option value="XI">XI (Sebelas)</option>
          <option value="XII">XII (Dua Belas)</option>
        </select>
      </div>
      <div class="mb-3">
        <label class="lbl">Jurusan</label>
        <input type="text" name="jurusan" id="editJurusan" class="inp" list="jurusan-list">
      </div>
      <div style="display:flex;gap:.5rem;justify-content:flex-end;">
        <button type="button" onclick="tutupEdit()" style="background:#f0fdf4;color:#475569;border:none;padding:.5rem 1rem;border-radius:8px;font-weight:600;cursor:pointer;">Batal</button>
        <button type="submit" class="btn-primary-si"><i class="bi bi-check-lg"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<style>
.card-si { background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden; }
.card-si-header { padding:.85rem 1.1rem;border-bottom:1px solid #f1f5f9;font-weight:700;font-size:.875rem;display:flex;align-items:center;gap:8px; }
.lbl { font-weight:600;font-size:.8rem;color:#374151;margin-bottom:.3rem;display:block; }
.inp { width:100%;border:1.5px solid #e2e8f0;border-radius:9px;padding:.5rem .85rem;font-size:.875rem;font-family:inherit;outline:none;transition:.18s; }
.inp:focus { border-color:#15803d;box-shadow:0 0 0 3px rgba(21,128,61,.1); }
.btn-primary-si { background:#15803d;color:#fff;border:none;padding:.5rem 1.1rem;border-radius:9px;font-weight:700;font-size:.83rem;display:inline-flex;align-items:center;gap:6px;cursor:pointer;transition:.18s;font-family:inherit; }
.btn-primary-si:hover { background:#166534; }
</style>

<script>
function bukaEdit(id, nama, tingkat, jurusan){
  document.getElementById('editId').value = id;
  document.getElementById('editNama').value = nama;
  document.getElementById('editTingkat').value = tingkat;
  document.getElementById('editJurusan').value = jurusan;
  document.getElementById('modalEdit').style.display = 'flex';
}
function tutupEdit(){
  document.getElementById('modalEdit').style.display = 'none';
}
// Tutup modal kalau klik backdrop
document.getElementById('modalEdit').addEventListener('click', function(e){
  if(e.target === this) tutupEdit();
});
</script>

<?php require '../layout/footer.php'; ?>
