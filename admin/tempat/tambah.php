<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

// Proses Simpan Data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nama   = trim($_POST['nama']);
    $alamat = trim($_POST['alamat']);
    $pemb   = trim($_POST['pembimbing']);
    $hp     = trim($_POST['no_hp']);
    $kuota  = (int)$_POST['kuota'];

    // Insert Query
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO tempat_pkl (nama_tempat, alamat, pembimbing_lapangan, no_hp, kuota)
         VALUES (?, ?, ?, ?, ?)"
    );

    mysqli_stmt_bind_param($stmt, "ssssi", $nama, $alamat, $pemb, $hp, $kuota);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: index.php?msg=sukses");
        exit;
    } else {
        $error = "Gagal menyimpan data.";
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
        border-bottom: 1px solid #e2e8f0; margin-top: 1.5rem;
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
    
    textarea.form-control { resize: vertical; min-height: 100px; }

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

    @media (max-width: 768px) {
        .form-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="page-header">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Tambah Tempat PKL</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Daftarkan instansi atau perusahaan mitra baru.</p>
    </div>
    <a href="index.php" class="btn-back">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg>
        Kembali
    </a>
</div>

<?php if (isset($error)): ?>
    <div style="background:#fee2e2; color:#991b1b; padding:1rem; border-radius:8px; margin-bottom:1rem;">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="POST">
        
        <div class="section-title">Informasi Umum</div>

        <div class="form-group">
            <label>Nama Instansi / Perusahaan</label>
            <input type="text" name="nama" class="form-control" placeholder="Contoh: PT. Telkom Indonesia" required>
        </div>

        <div class="form-group">
            <label>Alamat Lengkap</label>
            <textarea name="alamat" class="form-control" placeholder="Jl. Sudirman No. 1..." required></textarea>
        </div>

        <div class="section-title">Kontak & Kuota</div>

        <div class="form-grid">
            <div class="form-group">
                <label>Pembimbing Lapangan (PIC)</label>
                <input type="text" name="pembimbing" class="form-control" placeholder="Nama Pembimbing">
            </div>
            <div class="form-group">
                <label>Nomor HP / Telepon</label>
                <input type="text" name="no_hp" class="form-control" placeholder="08...">
            </div>
        </div>

        <div class="form-group" style="max-width: 200px;">
            <label>Kuota Penerimaan</label>
            <input type="number" name="kuota" class="form-control" value="0" min="0" required>
            <small style="color:#94a3b8; font-size:0.8rem;">Jumlah maksimal siswa.</small>
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