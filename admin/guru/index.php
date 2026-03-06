<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';
$page_title = 'Data Guru';

$query = mysqli_query($conn,"
  SELECT g.id, u.nama, u.username, g.nip
  FROM guru g JOIN users u ON g.user_id=u.id
  ORDER BY u.nama ASC
");
require '../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-start page-header">
  <div>
    <h1>Data Guru Pembimbing</h1>
    <p>Kelola data guru pembimbing PKL siswa.</p>
  </div>
  <div class="d-flex gap-2">
    <a href="import.php" class="btn-edit-sm"><i class="bi bi-upload"></i> Import CSV</a>
    <a href="tambah.php" class="btn-primary-custom"><i class="bi bi-plus-lg"></i> Tambah Guru</a>
  </div>
</div>

<div class="table-card">
  <div class="table-card-header">
    <h5><i class="bi bi-person-badge me-2"></i>Daftar Guru (<?= mysqli_num_rows($query) ?> data)</h5>
  </div>
  <div style="overflow-x:auto;"><table class="tbl">
    <thead><tr>
      <th style="width:45px;text-align:center">No</th>
      <th>Nama Lengkap</th>
      <th>NIP / NUPTK</th>
      <th>Username</th>
      <th style="width:130px;text-align:center">Aksi</th>
    </tr></thead>
    <tbody>
    <?php if(mysqli_num_rows($query)===0): ?>
      <tr><td colspan="5"><div class="empty-state"><i class="bi bi-person-x"></i><p>Belum ada data guru.</p></div></td></tr>
    <?php else: $no=1; while($r=mysqli_fetch_assoc($query)): ?>
    <tr>
      <td style="text-align:center;color:#94a3b8;"><?= $no++ ?></td>
      <td>
        <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($r['nama']) ?></div>
        <div style="font-size:.75rem;color:#94a3b8;"><?= htmlspecialchars($r['username']) ?></div>
      </td>
      <td>
        <?php if(!empty($r['nip'])): ?>
          <code style="background:#f0fdf4;padding:3px 8px;border-radius:5px;font-size:.78rem;color:#475569;"><?= htmlspecialchars($r['nip']) ?></code>
        <?php else: ?>
          <span style="color:#cbd5e1;">-</span>
        <?php endif; ?>
      </td>
      <td style="color:#64748b;font-size:.84rem;"><?= htmlspecialchars($r['username']) ?></td>
      <td style="text-align:center;">
        <div class="d-flex gap-1 justify-content-center">
          <a href="edit.php?id=<?= $r['id'] ?>" class="btn-edit-sm"><i class="bi bi-pencil"></i> Edit</a>
          <a href="hapus.php?id=<?= $r['id'] ?>" class="btn-danger-sm btn-hapus" data-nama="guru <?= htmlspecialchars($r['nama']) ?>"><i class="bi bi-trash"></i> Hapus</a>
        </div>
      </td>
    </tr>
    <?php endwhile; endif; ?>
    </tbody>
  </table></div>


<?php require '../layout/footer.php'; ?>
