<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

// Ambil ID dari URL (Diamankan dengan int)
$id = (int)($_GET['id'] ?? 0);

// Ambil data guru pakai prepared statement
$stmt_get = mysqli_prepare($conn, "
    SELECT g.id, g.nip, u.nama, u.username, u.id as user_id
    FROM guru g
    JOIN users u ON g.user_id = u.id
    WHERE g.id = ?
");
mysqli_stmt_bind_param($stmt_get, "i", $id);
mysqli_stmt_execute($stmt_get);
$query = mysqli_stmt_get_result($stmt_get);

$data = mysqli_fetch_assoc($query);

// Jika data tidak ditemukan, kembalikan ke index
if (!$data) {
    header("Location: index.php");
    exit;
}

// --- PROSES UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama     = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $nip      = trim($_POST['nip']);
    $password = $_POST['password'];

    // Update table users pakai prepared statement
    $upd1 = mysqli_prepare($conn, "UPDATE users SET nama=?, username=? WHERE id=?");
    mysqli_stmt_bind_param($upd1, "ssi", $nama, $username, $data['user_id']);
    mysqli_stmt_execute($upd1);

    // Jika password diisi → update password
    if (!empty($password)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $upd2 = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($upd2, "si", $hash, $data['user_id']);
        mysqli_stmt_execute($upd2);
    }

    // Update table guru
    $upd3 = mysqli_prepare($conn, "UPDATE guru SET nip=? WHERE id=?");
    mysqli_stmt_bind_param($upd3, "si", $nip, $id);
    mysqli_stmt_execute($upd3);

    header("Location: index.php");
    exit;
}

require '../layout/header.php';
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }

    .form-card {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border: 1px solid var(--border);
        max-width: 600px;
    }

    .form-group {
        margin-bottom: 1.25rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: var(--text-main);
        font-size: 0.95rem;
    }

    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
        outline: none;
    }

    .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .btn-submit {
        background: var(--primary);
        color: white;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-submit:hover {
        background: var(--primary-dark, #166534);
        transform: translateY(-1px);
    }

    .btn-back {
        color: #64748b;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.9rem;
    }
    .btn-back:hover { color: var(--text-main); }
    
    /* Helper text untuk password */
    .text-helper {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-top: 5px;
        display: block;
    }
</style>

<div class="page-header">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Edit Data Guru</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Perbarui informasi guru pembimbing.</p>
    </div>
    <a href="index.php" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
        Kembali
    </a>
</div>

<div class="form-card">
    <form method="POST">
        
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" 
                   value="<?= htmlspecialchars($data['nama']) ?>" required>
        </div>

        <div class="form-group">
            <label>NIP / NUPTK</label>
            <input type="text" name="nip" class="form-control" 
                   value="<?= htmlspecialchars($data['nip']) ?>">
        </div>

        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" class="form-control" 
                   value="<?= htmlspecialchars($data['username']) ?>" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••">
            <span class="text-helper">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                Biarkan kosong jika tidak ingin mengganti password.
            </span>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn-submit">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                Simpan Perubahan
            </button>
        </div>

    </form>
</div>

<?php require '../layout/footer.php'; ?>