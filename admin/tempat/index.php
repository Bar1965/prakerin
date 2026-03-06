<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';
$page_title = 'Tempat PKL';
$msg = $_GET['msg'] ?? '';

$result = mysqli_query($conn,"SELECT * FROM tempat_pkl ORDER BY nama_tempat ASC");
require '../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-start page-header">
  <div>
    <h1>Tempat PKL</h1>
    <p>Kelola daftar tempat praktik kerja lapangan siswa.</p>
  </div>
  <a href="tambah.php" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Tambah Tempat</a>
</div>

<?php if($msg==='ok'): ?><div class="alert-success-custom mb-3"><i class="bi bi-check-circle me-2"></i>Data berhasil disimpan.</div><?php endif; ?>

<div class="table-card">
  <div class="table-card-header">
    <h5><i class="bi bi-geo-alt me-2"></i>Daftar Tempat PKL (<?= mysqli_num_rows($result) ?> data)</h5>
  </div>
  <div style="overflow-x:auto;"><table class="tbl">
    <thead><tr>
      <th style="width:45px;text-align:center">No</th>
      <th>Nama Tempat</th>
      <th>Alamat</th>
      <th>Pembimbing Lapangan</th>
      <th>No. HP</th>
      <th style="text-align:center">Kuota</th>
      <th style="text-align:center;width:130px">Aksi</th>
    </tr></thead>
    <tbody>
    <?php if(mysqli_num_rows($result)===0): ?>
      <tr><td colspan="7"><div class="empty-state"><i class="bi bi-geo-alt"></i><p>Belum ada tempat PKL.</p></div></td></tr>
    <?php else: $no=1; while($r=mysqli_fetch_assoc($result)): ?>
    <tr>
      <td style="text-align:center;color:#94a3b8;"><?= $no++ ?></td>
      <td style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($r['nama_tempat']) ?></td>
      <td style="font-size:.84rem;color:#64748b;max-width:200px;"><?= htmlspecialchars($r['alamat']??'-') ?></td>
      <td style="font-size:.84rem;"><?= htmlspecialchars($r['pembimbing_lapangan']??'-') ?></td>
      <td style="font-size:.84rem;"><?= htmlspecialchars($r['no_hp']??'-') ?></td>
      <td style="text-align:center;">
        <span class="badge badge-blue"><?= $r['kuota'] ?> orang</span>
      </td>
      <td style="text-align:center;">
        <div class="d-flex gap-1 justify-content-center">
          <a href="edit.php?id=<?= $r['id'] ?>" class="btn-edit-sm"><i class="bi bi-pencil"></i> Edit</a>
          <a href="hapus.php?id=<?= $r['id'] ?>" class="btn-danger-sm btn-hapus" data-nama="tempat PKL <?= htmlspecialchars($r['nama_tempat']) ?>"><i class="bi bi-trash"></i> Hapus</a>
        </div>
      </td>
    </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table></div>


<?php require '../layout/footer.php'; ?>
