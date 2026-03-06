<?php
require '../middleware/auth_guru.php';
require '../../config/database.php';

$page_title = 'Profil Saya';
$user_id    = $_SESSION['user_id'];

// Ambil data guru + user
$stmt = mysqli_prepare($conn, "
    SELECT g.id as guru_id, g.nip, g.no_hp, g.alamat, g.tempat_lahir,
           g.tanggal_lahir, g.jenis_kelamin, g.pendidikan, g.mata_pelajaran,
           u.nama, u.username, u.created_at
    FROM guru g
    JOIN users u ON g.user_id = u.id
    WHERE g.user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

$msg   = '';
$error = '';

// Proses update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if ($action === 'profil') {
        $nama        = trim($_POST['nama']);
        $no_hp       = trim($_POST['no_hp']);
        $alamat      = trim($_POST['alamat']);
        $tempat_lahir= trim($_POST['tempat_lahir']);
        $tgl_lahir   = $_POST['tanggal_lahir'] ?: null;
        $jk          = $_POST['jenis_kelamin'];
        $pendidikan  = trim($_POST['pendidikan']);
        $mapel       = trim($_POST['mata_pelajaran']);

        // Update nama di tabel users
        $u1 = mysqli_prepare($conn, "UPDATE users SET nama = ? WHERE id = ?");
        mysqli_stmt_bind_param($u1, "si", $nama, $user_id);
        mysqli_stmt_execute($u1);

        // Update detail di tabel guru
        $u2 = mysqli_prepare($conn, "
            UPDATE guru SET no_hp=?, alamat=?, tempat_lahir=?, tanggal_lahir=?,
                            jenis_kelamin=?, pendidikan=?, mata_pelajaran=?
            WHERE user_id=?
        ");
        mysqli_stmt_bind_param($u2, "sssssssi",
            $no_hp, $alamat, $tempat_lahir, $tgl_lahir, $jk, $pendidikan, $mapel, $user_id
        );
        mysqli_stmt_execute($u2);

        $_SESSION['nama'] = $nama;
        $msg = "Profil berhasil diperbarui!";

        // Refresh data
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt); // reset
        $stmt2 = mysqli_prepare($conn, "
            SELECT g.id as guru_id, g.nip, g.no_hp, g.alamat, g.tempat_lahir,
                   g.tanggal_lahir, g.jenis_kelamin, g.pendidikan, g.mata_pelajaran,
                   u.nama, u.username, u.created_at
            FROM guru g JOIN users u ON g.user_id = u.id WHERE g.user_id = ?
        ");
        mysqli_stmt_bind_param($stmt2, "i", $user_id);
        mysqli_stmt_execute($stmt2);
        $guru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2));

    } elseif ($action === 'password') {
        $old_pass  = $_POST['old_password'];
        $new_pass  = $_POST['new_password'];
        $conf_pass = $_POST['confirm_password'];

        // Ambil password lama
        $pstmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id=?");
        mysqli_stmt_bind_param($pstmt, "i", $user_id);
        mysqli_stmt_execute($pstmt);
        $udata = mysqli_fetch_assoc(mysqli_stmt_get_result($pstmt));

        if (!password_verify($old_pass, $udata['password'])) {
            $error = "Password lama tidak sesuai!";
        } elseif (strlen($new_pass) < 6) {
            $error = "Password baru minimal 6 karakter!";
        } elseif ($new_pass !== $conf_pass) {
            $error = "Konfirmasi password tidak cocok!";
        } else {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $pstmt2 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
            mysqli_stmt_bind_param($pstmt2, "si", $hash, $user_id);
            mysqli_stmt_execute($pstmt2);
            $msg = "Password berhasil diubah!";
        }
    }
}

require '../layout/header.php';
?>

<style>
.profil-wrapper { display:grid; grid-template-columns:280px 1fr; gap:1.5rem; align-items:start; }
@media(max-width:900px){ .profil-wrapper{grid-template-columns:1fr;} }

.profil-card {
    background:white;border-radius:14px;border:1px solid var(--border);overflow:hidden;position:sticky;top:1.5rem;
}
.profil-cover {
    height:90px;
    background:linear-gradient(135deg,#15803d,#818cf8);
}
.profil-body { padding:0 1.25rem 1.5rem; text-align:center; }
.profil-avatar {
    width:80px;height:80px;border-radius:50%;
    border:4px solid white;
    background:linear-gradient(135deg,#15803d,#6366f1);
    display:flex;align-items:center;justify-content:center;
    color:white;font-weight:700;font-size:2rem;
    margin:-40px auto 0.75rem;
}
.profil-stat-row { display:flex;border-top:1px solid #f1f5f9;margin-top:1rem; }
.profil-stat { flex:1;padding:0.75rem 0.5rem;text-align:center;border-right:1px solid #f1f5f9; }
.profil-stat:last-child{border-right:none;}
.profil-stat .n { font-size:1.3rem;font-weight:700;color:var(--primary); }
.profil-stat .l { font-size:0.72rem;color:#94a3b8;margin-top:2px; }

.form-card { background:white;border-radius:14px;border:1px solid var(--border);margin-bottom:1.5rem; }
.form-card-header {
    padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;
    display:flex;align-items:center;gap:10px;
}
.form-card-header h3 { font-size:1rem;font-weight:700; }
.form-card-body { padding:1.5rem; }
.form-grid { display:grid;grid-template-columns:1fr 1fr;gap:1rem; }
@media(max-width:600px){ .form-grid{grid-template-columns:1fr;} }
.section-sep { font-size:0.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.5px;margin:1.25rem 0 0.75rem;padding-bottom:0.4rem;border-bottom:1px solid #f1f5f9; }
</style>

<div class="fade-in">
<div class="page-header">
    <div>
        <h1>Profil Saya</h1>
        <p>Lengkapi data diri Anda untuk memudahkan koordinasi dengan siswa dan sekolah.</p>
    </div>
</div>

<?php if ($msg): ?>
<div class="alert-success">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
    <?= htmlspecialchars($msg) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="profil-wrapper">

    <!-- PANEL KIRI: KARTU PROFIL -->
    <div>
        <div class="profil-card">
            <div class="profil-cover"></div>
            <div class="profil-body">
                <div class="profil-avatar"><?= strtoupper(substr($guru['nama'],0,1)) ?></div>
                <div style="font-weight:700;font-size:1.1rem;"><?= htmlspecialchars($guru['nama']) ?></div>
                <div style="font-size:0.83rem;color:#64748b;margin-top:3px;">Guru Pembimbing PKL</div>
                <?php if ($guru['nip']): ?>
                <div style="background:#f0fdf4;color:var(--primary);padding:4px 12px;border-radius:20px;font-size:0.78rem;font-weight:600;display:inline-block;margin-top:8px;">NIP: <?= $guru['nip'] ?></div>
                <?php endif; ?>

                <?php
                // Hitung jumlah siswa bimbingan
                $qs = mysqli_prepare($conn,"SELECT COUNT(*) t FROM siswa WHERE guru_id=?");
                mysqli_stmt_bind_param($qs,"i",$guru['guru_id']);
                mysqli_stmt_execute($qs);
                $jml_siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($qs))['t'];

                $qj = mysqli_prepare($conn,"SELECT COUNT(*) t FROM jurnal j JOIN siswa s ON j.siswa_id=s.id WHERE s.guru_id=? AND j.status='pending'");
                mysqli_stmt_bind_param($qj,"i",$guru['guru_id']);
                mysqli_stmt_execute($qj);
                $jml_pending = mysqli_fetch_assoc(mysqli_stmt_get_result($qj))['t'];
                ?>

                <div class="profil-stat-row">
                    <div class="profil-stat">
                        <div class="n"><?= $jml_siswa ?></div>
                        <div class="l">Siswa</div>
                    </div>
                    <div class="profil-stat">
                        <div class="n" style="color:<?= $jml_pending>0?'#f59e0b':'#10b981' ?>;"><?= $jml_pending ?></div>
                        <div class="l">Pending</div>
                    </div>
                </div>
            </div>

            <!-- Info ringkas -->
            <div style="padding:0 1.25rem 1.25rem;">
                <?php
                $info_items = [
                    ['📚', 'Username', $guru['username']],
                    ['📅', 'Bergabung', date('d M Y', strtotime($guru['created_at']))],
                    ['📱', 'No. HP', $guru['no_hp'] ?: '-'],
                    ['🏫', 'Mata Pelajaran', $guru['mata_pelajaran'] ?: '-'],
                    ['🎓', 'Pendidikan', $guru['pendidikan'] ?: '-'],
                ];
                foreach ($info_items as [$icon, $lbl, $val]):
                ?>
                <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid #f8fafc;font-size:0.85rem;">
                    <span style="width:22px;flex-shrink:0;"><?= $icon ?></span>
                    <span style="color:#64748b;width:100px;flex-shrink:0;"><?= $lbl ?></span>
                    <span style="font-weight:500;color:#0f172a;"><?= htmlspecialchars($val) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- PANEL KANAN: FORM -->
    <div>

        <!-- FORM DATA DIRI -->
        <div class="form-card">
            <div class="form-card-header">
                <span style="font-size:1.3rem;">👤</span>
                <h3>Data Pribadi & Profesional</h3>
            </div>
            <div class="form-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="profil">

                    <div class="section-sep">Informasi Dasar</div>
                    <div class="form-group">
                        <label>Nama Lengkap & Gelar</label>
                        <input type="text" name="nama" class="form-control"
                               value="<?= htmlspecialchars($guru['nama']) ?>" required placeholder="Contoh: Budi Santoso, S.Kom">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>NIP / NUPTK</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($guru['nip'] ?? '') ?>" disabled
                                   style="background:#f8fafc;color:#94a3b8;" title="NIP tidak bisa diubah di sini">
                            <small style="color:#94a3b8;font-size:0.78rem;">Hubungi admin untuk ubah NIP</small>
                        </div>
                        <div class="form-group">
                            <label>Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-control">
                                <option value="">-- Pilih --</option>
                                <option value="pria" <?= $guru['jenis_kelamin']==='pria'?'selected':'' ?>>Laki-laki</option>
                                <option value="wanita" <?= $guru['jenis_kelamin']==='wanita'?'selected':'' ?>>Perempuan</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Tempat Lahir</label>
                            <input type="text" name="tempat_lahir" class="form-control"
                                   value="<?= htmlspecialchars($guru['tempat_lahir'] ?? '') ?>" placeholder="Kota Kelahiran">
                        </div>
                        <div class="form-group">
                            <label>Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" class="form-control"
                                   value="<?= $guru['tanggal_lahir'] ?? '' ?>">
                        </div>
                    </div>

                    <div class="section-sep">Kontak</div>
                    <div class="form-group">
                        <label>Nomor HP / WhatsApp</label>
                        <input type="text" name="no_hp" class="form-control"
                               value="<?= htmlspecialchars($guru['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Alamat Rumah</label>
                        <textarea name="alamat" class="form-control" placeholder="Alamat lengkap..."><?= htmlspecialchars($guru['alamat'] ?? '') ?></textarea>
                    </div>

                    <div class="section-sep">Informasi Profesional</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Pendidikan Terakhir</label>
                            <select name="pendidikan" class="form-control">
                                <option value="">-- Pilih --</option>
                                <?php foreach(['D3','S1','S2','S3'] as $p): ?>
                                <option value="<?=$p?>" <?= $guru['pendidikan']===$p?'selected':'' ?>><?=$p?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Mata Pelajaran Diampu</label>
                            <input type="text" name="mata_pelajaran" class="form-control"
                                   value="<?= htmlspecialchars($guru['mata_pelajaran'] ?? '') ?>" placeholder="Contoh: Pemrograman Web">
                        </div>
                    </div>

                    <div style="text-align:right;margin-top:0.5rem;">
                        <button type="submit" class="btn btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- FORM GANTI PASSWORD -->
        <div class="form-card">
            <div class="form-card-header">
                <span style="font-size:1.3rem;">🔒</span>
                <h3>Ganti Password</h3>
            </div>
            <div class="form-card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="password">
                    <div class="form-group">
                        <label>Password Lama</label>
                        <input type="password" name="old_password" class="form-control" placeholder="Masukkan password saat ini" required>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" class="form-control" placeholder="Min. 6 karakter" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password</label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Ulangi password baru" required>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <button type="submit" class="btn btn-outline">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                            Ubah Password
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
</div>

<?php require '../layout/footer.php'; ?>
