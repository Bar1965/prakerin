<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

// Inisialisasi variabel error
$error = '';

/* Amankan ID */
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: index.php");
    exit;
}

/* Ambil data siswa + tempat */
$stmt = mysqli_prepare($conn, "
    SELECT s.id, s.nis, s.kelas, s.jurusan, s.tempat_pkl_id, s.guru_id,
           s.instruktur_id,
           u.nama, u.username, u.id as user_id
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header("Location: index.php");
    exit;
}

/* Ambil daftar tempat PKL */
$tempat_list = mysqli_query($conn, "SELECT id, nama_tempat FROM tempat_pkl ORDER BY nama_tempat ASC");

/* Ambil daftar guru pembimbing */
$guru_list = [];
$rg = mysqli_query($conn, "SELECT g.id, u.nama FROM guru g JOIN users u ON u.id=g.user_id ORDER BY u.nama");
while ($r = mysqli_fetch_assoc($rg)) $guru_list[] = $r;

/* Ambil daftar instruktur DU/DI */
$instr_list = [];
$ri = mysqli_query($conn, "SELECT i.id, u.nama, tp.nama_tempat FROM instruktur i JOIN users u ON u.id=i.user_id LEFT JOIN tempat_pkl tp ON tp.id=i.tempat_pkl_id ORDER BY u.nama");
while ($r = mysqli_fetch_assoc($ri)) $instr_list[] = $r;

/* PROSES UPDATE */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $nis      = trim($_POST['nis']);
    $kelas    = trim($_POST['kelas']);
    $jurusan  = trim($_POST['jurusan']);
    $password = $_POST['password'];

    $tempat_pkl_id = !empty($_POST['tempat_pkl_id']) ? (int)$_POST['tempat_pkl_id'] : NULL;
    $guru_id       = !empty($_POST['guru_id'])       ? (int)$_POST['guru_id']       : NULL;
    $instruktur_id = !empty($_POST['instruktur_id']) ? (int)$_POST['instruktur_id'] : NULL;

    /* Cek duplicate username (kecuali dirinya sendiri) */
    $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username=? AND id!=?");
    mysqli_stmt_bind_param($cek, "si", $username, $data['user_id']);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);

    if (mysqli_stmt_num_rows($cek) > 0) {
        $error = "Username '$username' sudah digunakan user lain!";
    } else {

        /* Update users */
        $stmt1 = mysqli_prepare($conn, "UPDATE users SET nama=?, username=? WHERE id=?");
        mysqli_stmt_bind_param($stmt1, "ssi", $nama, $username, $data['user_id']);
        mysqli_stmt_execute($stmt1);

        /* Update password jika diisi */
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($stmt2, "si", $hash, $data['user_id']);
            mysqli_stmt_execute($stmt2);
        }

        /* Update siswa */
        $stmt3 = mysqli_prepare($conn, "UPDATE siswa SET nis=?, kelas=?, jurusan=?, tempat_pkl_id=?, guru_id=?, instruktur_id=? WHERE id=?");
        mysqli_stmt_bind_param($stmt3, "sssiiii", $nis, $kelas, $jurusan, $tempat_pkl_id, $guru_id, $instruktur_id, $id);
        
        if (mysqli_stmt_execute($stmt3)) {
            header("Location: index.php?msg=updated");
            exit;
        } else {
            $error = "Gagal mengupdate data siswa.";
        }
    }
}

require '../layout/header.php';
?>

<style>
    .page-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
    }
    
    .form-card {
        background: white; padding: 2rem; border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid var(--border);
        max-width: 800px;
    }

    .section-title {
        font-size: 0.85rem; font-weight: 700; color: var(--primary);
        text-transform: uppercase; letter-spacing: 0.5px;
        margin-bottom: 1rem; padding-bottom: 0.5rem;
        border-bottom: 1px solid #e2e8f0; margin-top: 2rem;
    }
    .section-title:first-child { margin-top: 0; }

    .form-grid {
        display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;
    }

    .form-group { margin-bottom: 1rem; }
    .form-group label {
        display: block; margin-bottom: 0.4rem; font-weight: 500;
        color: var(--text-main); font-size: 0.9rem;
    }
    
    .form-control {
        width: 100%; padding: 0.6rem 0.9rem; border: 1px solid #cbd5e1;
        border-radius: 8px; font-size: 0.95rem; outline: none; transition: 0.2s;
        background-color: white;
    }
    .form-control:focus {
        border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .btn-submit {
        background: var(--primary); color: white; padding: 0.75rem 1.5rem;
        border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
        display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;
    }
    .btn-submit:hover { background: var(--primary-dark, #166534); transform: translateY(-1px); }

    .btn-back {
        color: #64748b; font-weight: 500; display: flex; align-items: center; gap: 5px; font-size: 0.9rem;
    }
    .btn-back:hover { color: var(--text-main); }
    
    .text-helper { font-size: 0.8rem; color: #94a3b8; margin-top: 4px; display: block; }
    .alert-error { background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; border: 1px solid #fee2e2; }

    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="page-header">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Edit Data Siswa</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Perbarui informasi akun atau akademik siswa.</p>
    </div>
    <a href="index.php" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
        Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert-error">
        <strong>Gagal!</strong> <?= $error ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST">

        <div class="section-title">Informasi Akun</div>

        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" 
                   value="<?= htmlspecialchars($data['nama']) ?>" required>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" 
                       value="<?= htmlspecialchars($data['username']) ?>" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••">
                <span class="text-helper">Biarkan kosong jika tidak ingin mengubah password.</span>
            </div>
        </div>

        <div class="section-title">Data Akademik</div>

        <div class="form-grid">
            <div class="form-group">
                <label>NIS</label>
                <input type="text" name="nis" class="form-control" 
                       value="<?= htmlspecialchars($data['nis']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Kelas</label>
                <select name="kelas" class="form-control" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    $qkls2 = mysqli_query($conn,"SELECT nama_kelas,tingkat FROM kelas ORDER BY tingkat,nama_kelas");
                    $list_kelas = [];
                    if($qkls2 && mysqli_num_rows($qkls2)>0){
                      while($kl=mysqli_fetch_assoc($qkls2)) $list_kelas[]=['nama'=>$kl['nama_kelas'],'tingkat'=>$kl['tingkat']];
                    } else {
                      foreach(['X RPL 1','X RPL 2','XI RPL 1','XI RPL 2','XII RPL 1','XII RPL 2'] as $k)
                        $list_kelas[]=['nama'=>$k,'tingkat'=>substr($k,0,strpos($k,' '))];
                    }
                    $grp='';
                    foreach ($list_kelas as $kl) {
                        $k=$kl['nama']; $t=$kl['tingkat'];
                        if($t!==$grp){ if($grp) echo '</optgroup>'; echo '<optgroup label="Tingkat '.$t.'">'; $grp=$t; }
                        $selected = ($data['kelas'] == $k) ? 'selected' : '';
                        echo "<option value='$k' $selected>$k</option>";
                    }
                    if($grp) echo '</optgroup>';
                    ?>
                </select>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Jurusan</label>
                <input type="text" name="jurusan" class="form-control" 
                       value="<?= htmlspecialchars($data['jurusan']) ?>">
            </div>

            <div class="form-group">
                <label>Tempat PKL</label>
                <select name="tempat_pkl_id" class="form-control">
                    <option value="">-- Belum Ditentukan --</option>
                    <?php 
                    mysqli_data_seek($tempat_list, 0); 
                    while($t = mysqli_fetch_assoc($tempat_list)): 
                        $is_selected = ($data['tempat_pkl_id'] == $t['id']) ? 'selected' : '';
                    ?>
                        <option value="<?= $t['id'] ?>" <?= $is_selected ?>>
                            <?= htmlspecialchars($t['nama_tempat']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Guru Pembimbing</label>
                <select name="guru_id" class="form-control">
                    <option value="">-- Belum Ditentukan --</option>
                    <?php foreach ($guru_list as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= ($data['guru_id'] ?? 0) == $g['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nama']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Instruktur DU/DI</label>
                <select name="instruktur_id" class="form-control">
                    <option value="">-- Belum Ditentukan --</option>
                    <?php foreach ($instr_list as $il): ?>
                    <option value="<?= $il['id'] ?>" <?= ($data['instruktur_id'] ?? 0) == $il['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($il['nama']) ?><?= $il['nama_tempat'] ? " ({$il['nama_tempat']})" : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Simpan Perubahan
            </button>
        </div>

    </form>
</div>

<?php require '../layout/footer.php'; ?>