<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id == 0) {
    header("Location: index.php");
    exit;
}

// Query Data Lengkap
$query = "
SELECT 
    s.*,
    u.nama, u.username, u.created_at,
    CASE 
        WHEN s.jenis_kelamin IS NULL OR s.alamat IS NULL THEN 'Incomplete' 
        ELSE 'Active' 
    END AS status_akun
FROM siswa s
JOIN users u ON s.user_id = u.id
WHERE s.id = ?
LIMIT 1
";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) { header("Location: index.php"); exit; }

// Format Nomor WA (Ganti 08 jadi 628)
$no_wa = $data['no_hp'] ? preg_replace('/^0/', '62', $data['no_hp']) : '';

require '../layout/header.php';
?>

<style>
    /* --- HEADER PROFILE --- */
    .profile-header {
        background: white; border-radius: 12px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid var(--border);
        margin-bottom: 1.5rem;
    }
    
    .cover-image {
        height: 150px;
        background: linear-gradient(120deg, #15803d, #818cf8);
        position: relative;
    }
    
    .profile-body {
        padding: 0 2rem 2rem; position: relative;
        display: flex; justify-content: space-between; align-items: flex-end;
        flex-wrap: wrap; gap: 20px;
    }

    .profile-main { display: flex; align-items: flex-end; gap: 20px; margin-top: -60px; }
    
    .avatar-box {
        width: 130px; height: 130px; border-radius: 50%;
        border: 5px solid white; background: white;
        overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .avatar-box img { width: 100%; height: 100%; object-fit: cover; }
    
    .profile-names { margin-bottom: 15px; }
    .profile-names h2 { font-size: 1.5rem; font-weight: 700; color: var(--text-main); margin: 0; }
    .profile-names p { color: #64748b; margin: 2px 0 0; font-size: 0.95rem; }

    .profile-actions { display: flex; gap: 10px; margin-bottom: 15px; }

    /* --- TABS MENU --- */
    .tabs-container {
        background: white; border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid var(--border);
        overflow: hidden;
    }
    
    .tabs-header {
        display: flex; border-bottom: 1px solid #f1f5f9;
        padding: 0 1rem; background: #f8fafc;
    }
    
    .tab-btn {
        padding: 1rem 1.5rem; border: none; background: none;
        font-weight: 600; color: #64748b; cursor: pointer;
        border-bottom: 2px solid transparent; transition: 0.2s;
    }
    .tab-btn:hover { color: var(--primary); }
    .tab-btn.active { color: var(--primary); border-bottom-color: var(--primary); }

    .tab-content { display: none; padding: 2rem; animation: fadeIn 0.3s ease; }
    .tab-content.active { display: block; }

    /* Data List Style */
    .detail-row {
        display: flex; border-bottom: 1px solid #f1f5f9; padding: 12px 0;
    }
    .detail-row:last-child { border-bottom: none; }
    .detail-label { width: 200px; color: #64748b; font-weight: 500; }
    .detail-value { flex: 1; color: var(--text-main); font-weight: 600; }

    /* Tombol Khusus */
    .btn-wa { background: #25D366; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; text-decoration: none; display: flex; align-items: center; gap: 6px; font-size: 0.9rem; }
    .btn-wa:hover { background: #1ebc57; transform: translateY(-1px); }

    /* Responsif */
    @media (max-width: 768px) {
        .profile-body { flex-direction: column; align-items: center; text-align: center; }
        .profile-main { flex-direction: column; align-items: center; margin-top: -60px; }
        .profile-names { margin-bottom: 20px; }
        .detail-row { flex-direction: column; gap: 5px; text-align: left; }
        .detail-label { width: 100%; font-size: 0.85rem; }
    }
</style>

<div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 700; color: var(--text-main);">Profil Siswa</h1>
        <p style="color: #64748b; font-size: 0.9rem;">Manajemen data siswa secara detail.</p>
    </div>
    <a href="index.php" class="btn-back" style="color: #64748b; display: flex; align-items: center; gap: 5px; font-weight: 500;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><path d="M12 19l-7-7 7-7"></path></svg> Kembali
    </a>
</div>

<div class="profile-header">
    <div class="cover-image"></div>
    
    <div class="profile-body">
        <div class="profile-main">
            <div class="avatar-box">
                <?php if (!empty($data['foto'])): ?>
                    <img src="../../uploads/<?= $data['foto'] ?>" alt="Foto">
                <?php else: ?>
                    <div style="width:100%; height:100%; background:#e2e8f0; display:flex; align-items:center; justify-content:center; color:#94a3b8;">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                <?php endif; ?>
            </div>
            <div class="profile-names">
                <h2><?= htmlspecialchars($data['nama']) ?></h2>
                <p>
                    <?= $data['kelas'] ?> • <?= $data['jurusan'] ?>
                </p>
                <div style="margin-top: 8px;">
                    <span style="background:#e0f2fe; color:#0284c7; padding:4px 10px; border-radius:20px; font-size:0.75rem; font-weight:700;">SISWA AKTIF</span>
                </div>
            </div>
        </div>

        <div class="profile-actions">
            <?php if ($no_wa): ?>
                <a href="https://wa.me/<?= $no_wa ?>" target="_blank" class="btn-wa">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    WhatsApp
                </a>
            <?php endif; ?>
            
            <a href="edit.php?id=<?= $data['id'] ?>" class="btn-submit" style="background: white; color: var(--text-main); border: 1px solid #cbd5e1; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 0.9rem;">
                Edit Profil
            </a>
        </div>
    </div>
</div>

<div class="tabs-container">
    <div class="tabs-header">
        <button class="tab-btn active" onclick="openTab(event, 'data-diri')">Data Diri</button>
        <button class="tab-btn" onclick="openTab(event, 'data-sekolah')">Akademik</button>
        <button class="tab-btn" onclick="openTab(event, 'data-ortu')">Orang Tua</button>
        <button class="tab-btn" onclick="openTab(event, 'akun-login')">Akun Login</button>
    </div>

    <div id="data-diri" class="tab-content active">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--primary);">Biodata Lengkap</h3>
        
        <div class="detail-row">
            <div class="detail-label">Jenis Kelamin</div>
            <div class="detail-value"><?= $data['jenis_kelamin'] ?: '-' ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Tempat, Tanggal Lahir</div>
            <div class="detail-value">
                <?= ($data['tempat_lahir'] ?: '-') . ', ' . ($data['tanggal_lahir'] ? date('d F Y', strtotime($data['tanggal_lahir'])) : '-') ?>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Alamat Domisili</div>
            <div class="detail-value"><?= $data['alamat'] ?: '-' ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Nomor Handphone</div>
            <div class="detail-value"><?= $data['no_hp'] ?: '-' ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Email</div>
            <div class="detail-value"><?= $data['email'] ?: '-' ?></div>
        </div>
    </div>

    <div id="data-sekolah" class="tab-content">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--primary);">Informasi Sekolah</h3>
        
        <div class="detail-row">
            <div class="detail-label">NIS (Nomor Induk)</div>
            <div class="detail-value"><?= $data['nis'] ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">NISN</div>
            <div class="detail-value"><?= $data['nisn'] ?: '-' ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Kelas Saat Ini</div>
            <div class="detail-value"><?= $data['kelas'] ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Jurusan</div>
            <div class="detail-value"><?= $data['jurusan'] ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Tempat PKL</div>
            <div class="detail-value" style="color: var(--primary);">
                <?= $data['tempat_pkl'] ? '🏢 '.$data['tempat_pkl'] : 'Belum ditempatkan' ?>
            </div>
        </div>
    </div>

    <div id="data-ortu" class="tab-content">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: var(--primary);">Kontak Wali / Orang Tua</h3>
        
        <div class="detail-row">
            <div class="detail-label">Nama Ayah / Wali</div>
            <div class="detail-value"><?= $data['nama_ayah'] ?? '-' ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Nomor HP Orang Tua</div>
            <div class="detail-value">
                <?= $data['no_hp_ortu'] ?: '-' ?>
                <?php if ($data['no_hp_ortu']): ?>
                    <a href="https://wa.me/<?= preg_replace('/^0/', '62', $data['no_hp_ortu']) ?>" target="_blank" style="margin-left:10px; font-size:0.8rem; color:#25D366; text-decoration:none;">(Chat WA)</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="akun-login" class="tab-content">
        <div style="background: #fff7ed; border-left: 4px solid #f97316; padding: 1rem; margin-bottom: 1.5rem; border-radius: 0 8px 8px 0;">
            <p style="margin: 0; color: #9a3412; font-size: 0.9rem;">
                <strong>Informasi Keamanan:</strong> Gunakan data ini untuk siswa login ke dalam sistem.
            </p>
        </div>

        <div class="detail-row">
            <div class="detail-label">Username</div>
            <div class="detail-value">
                <code style="background:#f0fdf4; padding:4px 8px; border-radius:4px; color:#ef4444;"><?= $data['username'] ?></code>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Terdaftar Sejak</div>
            <div class="detail-value"><?= date('d F Y, H:i', strtotime($data['created_at'])) ?> WIB</div>
        </div>
        
        <div style="margin-top: 2rem;">
            <a href="edit.php?id=<?= $data['id'] ?>" class="btn-submit" style="background:#ef4444; color:white; padding:10px 20px; text-decoration:none; border-radius:6px; font-size:0.9rem;">
                Reset Password / Edit Akun
            </a>
        </div>
    </div>

</div>


<script>
    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;
        
        // Sembunyikan semua konten tab
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }
        
        // Hapus class active dari semua tombol
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        
        // Tampilkan tab yang dipilih
        document.getElementById(tabName).style.display = "block";
        document.getElementById(tabName).classList.add("active"); // Trigger animasi
        evt.currentTarget.className += " active";
    }
</script>
<?php require '../layout/footer.php'; ?>
