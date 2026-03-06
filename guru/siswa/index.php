<?php
require '../middleware/auth_guru.php';
require '../../config/database.php';

$user_id = $_SESSION['user_id'];

$guru = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT id FROM guru WHERE user_id=$user_id"
));

$guru_id = $guru['id'];

$siswa = mysqli_query($conn,"
    SELECT s.id, s.nis, s.kelas, u.nama
    FROM siswa s
    JOIN users u ON s.user_id=u.id
    WHERE s.guru_id=$guru_id
    ORDER BY s.kelas
");
?>

<h2>Siswa Bimbingan</h2>

<table border="1" cellpadding="8">
<tr>
    <th>Nama</th>
    <th>NIS</th>
    <th>Kelas</th>
    <th>Aksi</th>
</tr>

<?php while($row=mysqli_fetch_assoc($siswa)): ?>
<tr>
    <td><?= htmlspecialchars($row['nama']) ?></td>
    <td><?= $row['nis'] ?></td>
    <td><?= $row['kelas'] ?></td>
    <td>
        <a href="../jurnal/index.php?siswa_id=<?= $row['id'] ?>">
            Lihat Jurnal
        </a>
    </td>
</tr>
<?php endwhile; ?>
</table>