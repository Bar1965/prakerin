<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

$error = '';

// --- LOGIKA SIMPAN DATA OTOMATIS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama = trim($_POST['nama']);
    $nip  = trim($_POST['nip']);

    // Validasi dasar
    if (empty($nama) || empty($nip)) {
        $error = "Nama dan NIP wajib diisi!";
    } else {
        // Setup Auto-Credentials
        $username = $nip;
        $password_default = '123456';
        $password_hash = password_hash($password_default, PASSWORD_DEFAULT);

        /* =========================
           CEK USERNAME (NIP) DULU
        ========================= */
        $cek = mysqli_prepare($conn, "SELECT id FROM users WHERE username=?");
        mysqli_stmt_bind_param($cek, "s", $username);
        mysqli_stmt_execute($cek);
        $res = mysqli_stmt_get_result($cek);

        if (mysqli_fetch_assoc($res)) {
            // NIP sudah ada di tabel users
            $error = "Gagal! NIP '<strong>$nip</strong>' sudah terdaftar di sistem.";
        } else {
            /* =========================
               INSERT USERS
            ========================= */
            $stmt = mysqli_prepare($conn, "INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'guru')");
            mysqli_stmt_bind_param($stmt, "sss", $nama, $username, $password_hash);
            
            if (mysqli_stmt_execute($stmt)) {
                $user_id = mysqli_insert_id($conn);

                /* =========================
                   INSERT GURU
                ========================= */
                $stmt2 = mysqli_prepare($conn, "INSERT INTO guru (user_id, nip) VALUES (?, ?)");
                mysqli_stmt_bind_param($stmt2, "is", $user_id, $nip);
                
                if (mysqli_stmt_execute($stmt2)) {
                    header("Location: index.php?msg=sukses");
                    exit;
                } else {
                    $error = "Gagal menyimpan detail guru.";
                }
            } else {
                $error = "Gagal membuat akun user.";
            }
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
        max-width: 600px;
    }

    .form-group { margin-bottom: 1.25rem; }
    .form-group label {
        display: block; margin-bottom: 0.5rem; font-weight: 500;
        color: var(--text-main); font-size: 0.95rem;
    }

    .form-control {
        width: 100%; padding: 0.75rem 1rem; border: 1px solid #cbd5e1;
        border-radius: 8px; font-size: 0.95rem; transition: all 0.2s; outline: none;
    }
    .form-control:focus {
        border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .btn-submit {
        background: var(--primary); color: white; padding: 0.75rem 1.5rem;
        border: none; border-radius: 8px; font-weight: 600; cursor: pointer;
        transition: 0.2s; display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-submit:hover { background: var(--primary-dark, #166534); transform: translateY(-1px); }

    .btn-back {
        color: #64748b; font-weight: 500; display: flex; align-items: center; gap: 5px; font-size: 0.9rem;
    }
    .btn-back:hover { color: var(--text-main); }

    /* Alert Boxes */
    .alert-error {
        background: #fef2f2; color: #991b1b; padding: 1rem; border-radius: 8px; 
        margin-bottom: 1.5rem; border: 1px solid #fee2e2;
        display: flex; align-items: center; gap: 10px; font-size: 0.95rem;
    }
    .alert-info {
        background: #f0fdf4; color: #1e3a8a; padding: 1rem; border-radius: 8px; 
        margin-bottom: 1.5rem; border: 1px solid #bbf7d0;
        display: flex; align-items: flex-start; gap: 10px; font-size: 0.9rem; line-height: 1.5;
    }
</style>

<div class="page-header">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Tambah Guru</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Registrasi cepat akun guru pembimbing.</p>
    </div>
    <a href="index.php" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
        Kembali
    </a>
</div>

<?php if ($error): ?>
    <div class="alert-error">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span><?= $error ?></span>
    </div>
<?php endif; ?>

<div class="form-card">
    
    <div class="alert-info">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        <div>
            <strong>Info Sistem:</strong><br>
            Username login akan otomatis menggunakan <strong>NIP</strong>, dan Password default akun adalah <strong>123456</strong>. Guru dapat mengubah passwordnya nanti.
        </div>
    </div>

    <form method="POST">
        
        <div class="form-group">
            <label for="nama">Nama Lengkap & Gelar</label>
            <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: Budi Santoso, S.Kom" value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>" required autofocus>
        </div>

        <div class="form-group">
            <label for="nip">NIP / NUPTK</label>
            <input type="text" id="nip" name="nip" class="form-control" placeholder="Masukkan NIP" value="<?= isset($_POST['nip']) ? htmlspecialchars($_POST['nip']) : '' ?>" required>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Simpan & Buat Akun
            </button>
        </div>

    </form>
</div>

<?php require '../layout/footer.php'; ?>