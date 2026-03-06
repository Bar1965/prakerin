<?php
// Fungsi untuk mengecek menu aktif
// Menambahkan class 'active' jika URL mengandung kata kunci
function isActive($keyword) {
    // Ambil URL saat ini (misal: /admin/siswa/index.php)
    $current_url = $_SERVER['REQUEST_URI'];
    
    // Cek apakah kata kunci (misal: 'siswa') ada di URL
    if (strpos($current_url, $keyword) !== false) {
        return 'active';
    }
    return '';
}
?>

<div class="sidebar">
<div class="sidebar-header" style="
        display: flex; 
        align-items: center; 
        gap: 12px; 
        padding: 1.5rem;
    ">
        <img src="../../assets/images/logo-sekolah.png" alt="Logo" style="
            width: 45px; 
            height: 45px; 
            object-fit: contain;
        ">

        <div style="display: flex; flex-direction: column;">
            <span style="
                font-size: 1.1rem; 
                font-weight: 700; 
                color: var(--primary); 
                line-height: 1.2;
            ">
                SiPrakerin
            </span>
            
            <small style="
                font-size: 0.7rem; 
                color: #64748b; 
                font-weight: 500;
            ">
                SMK Negeri 3 Padang
            </small>
        </div>
    </div>

    <div class="sidebar-menu">
        <a href="../dashboard/index.php" class="menu-item <?= isActive('dashboard') ?>">
            <span style="font-size: 1.1em;">🏠</span> Dashboard
        </a>

        <div style="margin: 15px 0 5px 15px; font-size: 0.75rem; color: #94a3b8; font-weight: 700;">DATA MASTER</div>

        <a href="../guru/index.php" class="menu-item <?= isActive('guru') ?>">
            <span>👨‍🏫</span> Data Guru
        </a>
        <a href="../siswa/index.php" class="menu-item <?= isActive('siswa') ?>">
            <span>👨‍🎓</span> Data Siswa
        </a>
        <a href="../tempat/index.php" class="menu-item <?= isActive('tempat') ?>">
            <span>🏢</span> Tempat PKL
        </a>

        <div style="margin: 15px 0 5px 15px; font-size: 0.75rem; color: #94a3b8; font-weight: 700;">KEGIATAN</div>

        <a href="../penempatan/index.php" class="menu-item <?= isActive('penempatan') ?>">
            <span>📌</span> Penempatan
        </a>
        <a href="../jurnal/index.php" class="menu-item <?= isActive('jurnal') ?>">
            <span>📝</span> Monitoring Jurnal
        </a>
    </div>

    <div class="sidebar-footer">
        <a href="../../auth/logout.php" class="btn-logout-confirm menu-item" style="color: #ef4444;">
            <span>🚪</span> Logout
        </a>
    </div>
</div>

<div class="main-content">
    
    <nav style="
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border);
    ">
        
        <div class="title" style="font-size: 1.1rem; font-weight: 600; color: var(--text-main);">
            Panel Administrator
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="text-align: right;">
                <span style="display: block; font-weight: 600; font-size: 0.9rem;">
                    <?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?>
                </span>
                <span style="display: block; font-size: 0.75rem; color: var(--text-muted);">
                    Administrator
                </span>
            </div>
            <div style="
                width: 40px; 
                height: 40px; 
                background: #e2e8f0; 
                border-radius: 50%; 
                display: flex; 
                align-items: center; 
                justify-content: center;
                font-weight: bold;
                color: var(--text-muted);
            ">
                <?= substr($_SESSION['nama'] ?? 'A', 0, 1) ?>
            </div>
        </div>
    </nav>