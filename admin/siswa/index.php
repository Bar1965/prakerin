<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';
$page_title = 'Data Siswa';

$filter_kelas = $_GET['kelas'] ?? '';
$where = '';
if($filter_kelas !== ''){
  $kelas_safe = mysqli_real_escape_string($conn,$filter_kelas);
  $where = "WHERE s.kelas='$kelas_safe'";
}

$result = mysqli_query($conn,"
  SELECT s.*, u.nama, u.username, tp.nama_tempat,
    CASE WHEN s.jenis_kelamin IS NULL OR s.tanggal_lahir IS NULL OR s.alamat IS NULL OR s.no_hp IS NULL
    THEN 'Belum Lengkap' ELSE 'Lengkap' END AS status_profil
  FROM siswa s
  JOIN users u ON s.user_id=u.id
  LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id=tp.id
  $where ORDER BY s.kelas ASC, u.nama ASC
");

require '../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-end page-header flex-wrap gap-2">
  <div>
    <h1>Data Siswa</h1>
    <p>Kelola data siswa PKL, kelas, dan status profil.</p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <form method="GET" class="m-0">
      <select name="kelas" class="form-select form-select-sm" onchange="this.form.submit()" style="min-width:160px;">
        <option value="">Semua Kelas</option>
        <?php
        $kq = mysqli_query($conn,"SELECT DISTINCT kelas FROM siswa ORDER BY kelas ASC");
        while($k=mysqli_fetch_assoc($kq)) {
          $sel = ($filter_kelas==$k['kelas'])?'selected':'';
          echo "<option value='{$k['kelas']}' $sel>{$k['kelas']}</option>";
        }
        ?>
      </select>
    </form>
    <a href="tambah.php" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Tambah Siswa</a>
  </div>
</div>

<div class="table-card">
  <div class="table-card-header">
    <h5><i class="bi bi-people me-2"></i>Daftar Siswa (<?= mysqli_num_rows($result) ?> data)</h5>
  </div>
  <div style="overflow-x:auto;"><table class="tbl">
    <thead><tr>
      <th style="width:45px;text-align:center">No</th>
      <th>Nama Siswa</th>
      <th>NIS</th>
      <th>Kelas & Jurusan</th>
      <th>Tempat PKL</th>
      <th>Profil</th>
      <th style="width:130px;text-align:center">Aksi</th>
    </tr></thead>
    <tbody>
    <?php if(mysqli_num_rows($result)===0): ?>
      <tr><td colspan="7"><div class="empty-state"><i class="bi bi-people"></i><p>Belum ada data siswa.</p></div></td></tr>
    <?php else: $no=1; while($r=mysqli_fetch_assoc($result)): ?>
    <tr>
      <td style="text-align:center;color:#94a3b8;"><?= $no++ ?></td>
      <td>
        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($r['nama']) ?></div>
        <div style="font-size:.74rem;color:#94a3b8;"><?= htmlspecialchars($r['username']) ?></div>
      </td>
      <td style="font-size:.84rem;"><?= htmlspecialchars($r['nis']) ?></td>
      <td>
        <div style="font-weight:500;font-size:.84rem;"><?= htmlspecialchars($r['kelas']) ?></div>
        <div style="font-size:.74rem;color:#94a3b8;"><?= htmlspecialchars($r['jurusan']??'') ?></div>
      </td>
      <td style="font-size:.84rem;">
        <?php if(!empty($r['nama_tempat'])): ?>
          <span class="badge badge-blue"><i class="bi bi-building me-1"></i><?= htmlspecialchars($r['nama_tempat']) ?></span>
        <?php else: ?>
          <span class="badge badge-gray">Belum ada</span>
        <?php endif; ?>
      </td>
      <td>
        <?php if($r['status_profil']==='Lengkap'): ?>
          <span class="badge badge-green"><i class="bi bi-check-circle me-1"></i>Lengkap</span>
        <?php else: ?>
          <span class="badge badge-yellow"><i class="bi bi-exclamation-circle me-1"></i>Belum</span>
        <?php endif; ?>
      </td>
      <td style="text-align:center;">
        <div class="d-flex gap-1 justify-content-center">
          <a href="detail.php?id=<?= $r['id'] ?>" class="btn-edit-sm" style="background:#f0fdf4;color:#15803d;border-color:#bbf7d0;"><i class="bi bi-eye"></i></a>
          <a href="edit.php?id=<?= $r['id'] ?>" class="btn-edit-sm"><i class="bi bi-pencil"></i></a>
          <a href="hapus.php?id=<?= $r['id'] ?>" class="btn-danger-sm btn-hapus" data-nama="siswa <?= htmlspecialchars($r['nama']) ?>"><i class="bi bi-trash"></i></a>
        </div>
      </td>
    </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table></div>


<?php require '../layout/footer.php'; ?>
