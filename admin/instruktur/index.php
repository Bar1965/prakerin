<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';
$page_title = 'Instruktur DU/DI';

$result = mysqli_query($conn,"
  SELECT i.id, i.jabatan, i.no_hp,
         u.nama, u.username,
         tp.nama_tempat,
         (SELECT COUNT(*) FROM siswa WHERE instruktur_id=i.id) AS jml_siswa
  FROM instruktur i
  JOIN users u ON i.user_id=u.id
  LEFT JOIN tempat_pkl tp ON i.tempat_pkl_id=tp.id
  ORDER BY u.nama ASC
");
require '../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-start page-header">
  <div>
    <h1>Instruktur DU/DI</h1>
    <p>Manajemen instruktur dari perusahaan / dunia industri.</p>
  </div>
  <a href="tambah.php" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Tambah Instruktur</a>
</div>

<div class="table-card">
  <div class="table-card-header">
    <h5><i class="bi bi-building me-2"></i>Daftar Instruktur (<?= mysqli_num_rows($result) ?> data)</h5>
  </div>
  <div style="overflow-x:auto;"><table class="tbl">
    <thead><tr>
      <th style="width:45px;text-align:center">No</th>
      <th>Nama Instruktur</th>
      <th>Tempat PKL</th>
      <th>Jabatan</th>
      <th>No. HP</th>
      <th style="text-align:center">Siswa</th>
      <th style="text-align:center;width:130px">Aksi</th>
    </tr></thead>
    <tbody>
    <?php if(mysqli_num_rows($result)===0): ?>
      <tr><td colspan="7"><div class="empty-state"><i class="bi bi-building-x"></i><p>Belum ada instruktur.</p></div></td></tr>
    <?php else: $no=1; while($r=mysqli_fetch_assoc($result)): ?>
    <tr>
      <td style="text-align:center;color:#94a3b8;"><?= $no++ ?></td>
      <td>
        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($r['nama']) ?></div>
        <div style="font-size:.74rem;color:#94a3b8;"><?= htmlspecialchars($r['username']) ?></div>
      </td>
      <td style="font-size:.84rem;"><?= $r['nama_tempat'] ? htmlspecialchars($r['nama_tempat']) : '<span style="color:#cbd5e1">-</span>' ?></td>
      <td style="font-size:.84rem;"><?= $r['jabatan'] ? htmlspecialchars($r['jabatan']) : '<span style="color:#cbd5e1">-</span>' ?></td>
      <td style="font-size:.84rem;"><?= $r['no_hp'] ? htmlspecialchars($r['no_hp']) : '<span style="color:#cbd5e1">-</span>' ?></td>
      <td style="text-align:center;">
        <span class="badge <?= $r['jml_siswa']>0 ? 'badge-purple' : 'badge-gray' ?>"><?= $r['jml_siswa'] ?> siswa</span>
      </td>
      <td style="text-align:center;">
        <div class="d-flex gap-1 justify-content-center">
          <a href="hapus.php?id=<?= $r['id'] ?>" class="btn-danger-sm btn-hapus" data-nama="instruktur <?= htmlspecialchars($r['nama']) ?>"><i class="bi bi-trash"></i> Hapus</a>
        </div>
      </td>
    </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table></div>


<?php require '../layout/footer.php'; ?>
