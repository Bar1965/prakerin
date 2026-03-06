<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

$error = '';

/* 1. Ambil daftar tempat PKL untuk Dropdown */
$tempat_list = mysqli_query($conn, "SELECT id, nama_tempat FROM tempat_pkl ORDER BY nama_tempat ASC");

/* 2. Proses Simpan Data */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama          = trim($_POST['nama']);
    $username      = trim($_POST['username']);
    $password      = $_POST['password'];
    $nis           = trim($_POST['nis']);
    $kelas         = trim($_POST['kelas']);
    $jurusan       = trim($_POST['jurusan']);
    
    // Logika NULL jika memilih "Belum Ditentukan"
    $tempat_pkl_id = !empty($_POST['tempat_pkl_id']) ? (int)$_POST['tempat_pkl_id'] : NULL;

    // Cek Username Ganda
    $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ? LIMIT 1");
    mysqli_stmt_bind_param($cek, "s", $username);
    mysqli_stmt_execute($cek);
    mysqli_stmt_store_result($cek);

    if (mysqli_stmt_num_rows($cek) > 0) {
        $error = "Username '$username' sudah digunakan!";
    } else {
        // Hash Password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // A. Insert ke tabel USERS
        $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'siswa')");
        mysqli_stmt_bind_param($stmt, "sss", $nama, $username, $hash);
        
        if (mysqli_stmt_execute($stmt)) {
            $user_id = mysqli_insert_id($conn);

            // B. Insert ke tabel SISWA (Perhatikan parameter 'isssi' -> integer, string, string, string, integer)
            $stmt2 = mysqli_prepare($conn, "INSERT INTO siswa (user_id, nis, kelas, jurusan, tempat_pkl_id) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, "isssi", $user_id, $nis, $kelas, $jurusan, $tempat_pkl_id);
            
            if (mysqli_stmt_execute($stmt2)) {
                header("Location: index.php");
                exit;
            } else {
                $error = "Gagal menyimpan data siswa (Database Error).";
            }
        } else {
            $error = "Gagal membuat akun user.";
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

    /* Alert Error */
    .alert-error {
        background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; 
        margin-bottom: 1.5rem; border: 1px solid #fee2e2;
        display: flex; align-items: center; gap: 10px;
    }

    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="page-header">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Tambah Siswa</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Registrasi akun dan penempatan PKL.</p>
    </div>
    <a href="index.php" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
        Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert-error">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><strong>Gagal!</strong> <?= $error ?></span>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST">
        
        <div class="section-title">Informasi Akun</div>
        
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" placeholder="Nama Siswa" required>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Username (Login)</label>
                <input type="text" name="username" class="form-control" placeholder="NISN / Username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" placeholder="Buat Password" required>
            </div>
        </div>

        <div class="section-title">Data Sekolah & PKL</div>

        <div class="form-grid">
            <div class="form-group">
                <label>NIS</label>
                <input type="text" name="nis" class="form-control" placeholder="Nomor Induk Siswa" required>
            </div>
            <div class="form-group">
                <label>Kelas</label>
                <select name="kelas" class="form-control" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php
                    $qkls = mysqli_query($conn,"SELECT nama_kelas,tingkat FROM kelas ORDER BY tingkat,nama_kelas");
                    if($qkls && mysqli_num_rows($qkls)>0){
                      $grp='';
                      while($kl=mysqli_fetch_assoc($qkls)){
                        if($kl['tingkat']!==$grp){ if($grp) echo '</optgroup>'; echo '<optgroup label="Tingkat '.$kl['tingkat'].'">'; $grp=$kl['tingkat']; }
                        echo '<option value="'.htmlspecialchars($kl['nama_kelas']).'">'.htmlspecialchars($kl['nama_kelas']).'</option>';
                      }
                      if($grp) echo '</optgroup>';
                    } else {
                      // Fallback kalau tabel kelas belum ada
                      foreach(['X RPL 1','X RPL 2','XI RPL 1','XI RPL 2','XII RPL 1','XII RPL 2'] as $k)
                        echo '<option value="'.$k.'">'.$k.'</option>';
                    }
                    ?>
                </select>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>Jurusan</label>
                <input type="text" name="jurusan" class="form-control" value="Rekayasa Perangkat Lunak" placeholder="Jurusan">
            </div>
            
            <div class="form-group">
                <label>Tempat PKL</label>
                <select name="tempat_pkl_id" class="form-control">
                    <option value="">-- Belum Ditentukan --</option>
                    <?php 
                    // Reset pointer jika query pernah dijalankan sebelumnya (best practice)
                    mysqli_data_seek($tempat_list, 0); 
                    while($t = mysqli_fetch_assoc($tempat_list)): 
                    ?>
                        <option value="<?= $t['id'] ?>">
                            <?= htmlspecialchars($t['nama_tempat']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <small style="color:#94a3b8; font-size:0.8rem;">Pilih instansi jika sudah ditentukan.</small>
            </div>
        </div>

        <div style="margin-top: 2rem; text-align: right;">
            <button type="submit" class="btn-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Simpan Data
            </button>
        </div>

    </form>
</div>

<?php require '../layout/footer.php'; ?>