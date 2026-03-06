<?php
session_start();
require '../../config/database.php';
require '../../config/onboarding_check.php';
if(empty($_SESSION['login'])||$_SESSION['role']!=='siswa'){header("Location: ../../auth/login.php");exit;}
$page_title = 'Absensi Harian';
$uid = $_SESSION['user_id'];
date_default_timezone_set('Asia/Jakarta');

$ss = mysqli_prepare($conn,"SELECT id, tempat_pkl_id FROM siswa WHERE user_id=?");
mysqli_stmt_bind_param($ss,"i",$uid); mysqli_stmt_execute($ss);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($ss));
$sid        = (int)($siswa['id'] ?? 0);
$tempat_id  = (int)($siswa['tempat_pkl_id'] ?? 0);

$msg=''; $err='';
$today = date('Y-m-d');
date_default_timezone_set('Asia/Jakarta');

// ── Ambil jam absen (override DU/DI atau default admin) ──
require_once '../../config/helpers.php';
$jam_config = getJamAbsen($conn, $tempat_id);
$jam_masuk_batas  = $jam_config['jam_masuk'];
$jam_pulang_batas = $jam_config['jam_pulang'];
$toleransi        = (int)$jam_config['batas_masuk_menit'];
$jam_sekarang     = date('H:i');

// Cek apakah waktu absen valid
$bisa_absen_jam = $jam_sekarang >= $jam_masuk_batas;

// ── Kirim pengingat absen jika belum absen ──
cekDanKirimPengingat($conn, $sid, $uid);

// ══ CEK JADWAL AKTIF ══
$jadwal = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM jadwal_prakerin ORDER BY tanggal_mulai DESC LIMIT 1"
));

// ══ CEK KONFIRMASI DU/DI ══
$sudah_konfirmasi = false;
if ($jadwal && $tempat_id) {
    $jid = (int)$jadwal['id'];
    $kres = mysqli_query($conn,
        "SELECT id FROM konfirmasi_prakerin WHERE tempat_pkl_id=$tempat_id AND jadwal_id=$jid LIMIT 1"
    );
    $sudah_konfirmasi = mysqli_num_rows($kres) > 0;
}

// ══ CEK HARI LIBUR (nasional + khusus tempat siswa) ══
$is_libur = false;
$nama_libur = '';
if ($tempat_id) {
    $lr = mysqli_query($conn,
        "SELECT nama_libur FROM hari_libur
         WHERE tanggal='$today'
           AND (tempat_pkl_id IS NULL OR tempat_pkl_id=$tempat_id)
         LIMIT 1"
    );
} else {
    $lr = mysqli_query($conn,
        "SELECT nama_libur FROM hari_libur WHERE tanggal='$today' AND tempat_pkl_id IS NULL LIMIT 1"
    );
}
if ($lr && $row_libur = mysqli_fetch_assoc($lr)) {
    $is_libur = true;
    $nama_libur = $row_libur['nama_libur'];
}

// ══ CEK STATUS JADWAL ══
$dalam_periode = false;
if ($jadwal) {
    $dalam_periode = ($today >= $jadwal['tanggal_mulai'] && $today <= $jadwal['tanggal_selesai']);
}

// Bisa absensi jika: ada jadwal, dalam periode, DU/DI sudah konfirmasi, bukan hari libur, jam sudah tepat
$bisa_absensi = $jadwal && $dalam_periode && $sudah_konfirmasi && !$is_libur && $bisa_absen_jam;

$ck = mysqli_prepare($conn,"SELECT * FROM absensi WHERE siswa_id=? AND tanggal=?");
mysqli_stmt_bind_param($ck,"is",$sid,$today); mysqli_stmt_execute($ck);
$absen_hari_ini = mysqli_fetch_assoc(mysqli_stmt_get_result($ck));

if($_SERVER['REQUEST_METHOD']==='POST' && !$absen_hari_ini){
  // Validasi ulang sebelum simpan
  if (!$bisa_absensi) {
    $err = "Absensi tidak dapat dilakukan saat ini.";
  } else {
    $status    = $_POST['status'] ?? 'hadir';
    $ket       = trim($_POST['keterangan'] ?? '');
    $foto_path = null;

    $foto_data = $_POST['foto_selfie'] ?? '';
    if(!empty($foto_data) && strpos($foto_data,'data:image/')===0){
      $upload_dir = '../../uploads/absensi/';
      if(!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
      $img_binary = base64_decode(explode(';base64,', $foto_data)[1] ?? '');
      $filename   = 'abs_'.$sid.'_'.date('Ymd_His').'.jpg';
      if(file_put_contents($upload_dir.$filename, $img_binary)){
        $foto_path = 'uploads/absensi/'.$filename;
      }
    }

    $has_foto  = mysqli_num_rows(mysqli_query($conn,"SHOW COLUMNS FROM absensi LIKE 'foto_selfie'")) > 0;
$has_lokasi= mysqli_num_rows(mysqli_query($conn,"SHOW COLUMNS FROM absensi LIKE 'latitude'"))    > 0;
    if($has_foto){
      $ins = mysqli_prepare($conn,"INSERT INTO absensi(siswa_id,tanggal,status,keterangan,jam_masuk,foto_selfie) VALUES(?,?,?,?,NOW(),?)");
      mysqli_stmt_bind_param($ins,"issss",$sid,$today,$status,$ket,$foto_path);
    } else {
      $ins = mysqli_prepare($conn,"INSERT INTO absensi(siswa_id,tanggal,status,keterangan,jam_masuk) VALUES(?,?,?,?,NOW())");
      mysqli_stmt_bind_param($ins,"isss",$sid,$today,$status,$ket);
    }

    if(mysqli_stmt_execute($ins)){
      $msg = "Absensi berhasil dicatat!";
      $absen_hari_ini = ['status'=>$status,'keterangan'=>$ket,'jam_masuk'=>date('H:i:s'),'foto_selfie'=>$foto_path];
    } else { $err = "Gagal mencatat absensi."; }
  }
}

$sq = mysqli_prepare($conn,"SELECT COUNT(*) t,SUM(status='hadir') h,SUM(status='sakit') s,SUM(status='izin') i,SUM(status='alpha') a FROM absensi WHERE siswa_id=?");
mysqli_stmt_bind_param($sq,"i",$sid); mysqli_stmt_execute($sq);
$stat = mysqli_fetch_assoc(mysqli_stmt_get_result($sq));

$bulan = $_GET['bulan'] ?? '';
$wsql  = $bulan ? "AND DATE_FORMAT(tanggal,'%Y-%m')=?" : "";
$qa    = mysqli_prepare($conn,"SELECT * FROM absensi WHERE siswa_id=? $wsql ORDER BY tanggal DESC");
if($bulan){ mysqli_stmt_bind_param($qa,"is",$sid,$bulan); } else { mysqli_stmt_bind_param($qa,"i",$sid); }
mysqli_stmt_execute($qa); $absensi_list = mysqli_stmt_get_result($qa);

$qb = mysqli_prepare($conn,"SELECT DISTINCT DATE_FORMAT(tanggal,'%Y-%m') bln,DATE_FORMAT(tanggal,'%M %Y') lbl FROM absensi WHERE siswa_id=? ORDER BY bln DESC");
mysqli_stmt_bind_param($qb,"i",$sid); mysqli_stmt_execute($qb);
$bln_res = mysqli_stmt_get_result($qb);
$bulan_list=[]; while($b=mysqli_fetch_assoc($bln_res)) $bulan_list[]=$b;

$pct_hadir = $stat['t']>0 ? round($stat['h']/$stat['t']*100) : 0;
$hari_id   = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];

require '../layout/header.php';
?>

<?php if($msg): ?><div class="alert-ok"><i class="bi bi-check-circle me-2"></i><?= $msg ?></div><?php endif; ?>
<?php if($err): ?><div class="alert-err"><i class="bi bi-exclamation-circle me-2"></i><?= $err ?></div><?php endif; ?>

<?php if ($jadwal && $dalam_periode && $sudah_konfirmasi && !$is_libur && !$bisa_absen_jam): ?>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:10px;color:#166534;">
  <i class="bi bi-clock" style="font-size:1.3rem;"></i>
  <div>
    <div style="font-weight:700;font-size:.9rem;">Belum Waktunya Absen</div>
    <div style="font-size:.8rem;margin-top:2px;">
      Absensi dibuka mulai pukul <strong><?= $jam_masuk_batas ?> WIB</strong>.
      Sekarang <?= $jam_sekarang ?> WIB.
      <?php if ($jam_config['source'] === 'override'): ?>
        <span style="font-size:.75rem;opacity:.7;">(jam khusus dari DU/DI)</span>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- BANNER STATUS JADWAL & KONFIRMASI -->
<?php if (!$jadwal): ?>
<div style="background:#fefce8;border:1px solid #fde68a;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:10px;color:#a16207;">
  <i class="bi bi-calendar-x" style="font-size:1.3rem;"></i>
  <div>
    <div style="font-weight:700;font-size:.9rem;">Jadwal Prakerin Belum Ditetapkan</div>
    <div style="font-size:.8rem;margin-top:2px;">Admin sekolah belum menetapkan jadwal. Absensi belum bisa dilakukan.</div>
  </div>
</div>
<?php elseif (!$dalam_periode): ?>
<div style="background:#f0fdf4;border:1px solid #e2e8f0;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:10px;color:#475569;">
  <i class="bi bi-calendar-event" style="font-size:1.3rem;"></i>
  <div>
    <div style="font-weight:700;font-size:.9rem;">
      <?= $today < $jadwal['tanggal_mulai'] ? 'Prakerin Belum Dimulai' : 'Periode Prakerin Telah Selesai' ?>
    </div>
    <div style="font-size:.8rem;margin-top:2px;">
      Periode: <?= date('d M Y', strtotime($jadwal['tanggal_mulai'])) ?> – <?= date('d M Y', strtotime($jadwal['tanggal_selesai'])) ?>
    </div>
  </div>
</div>
<?php elseif (!$sudah_konfirmasi): ?>
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:10px;color:#166534;">
  <i class="bi bi-hourglass-split" style="font-size:1.3rem;"></i>
  <div>
    <div style="font-weight:700;font-size:.9rem;">Menunggu Konfirmasi DU/DI</div>
    <div style="font-size:.8rem;margin-top:2px;">Instruktur di tempat PKL Anda belum mengkonfirmasi dimulainya prakerin. Hubungi instruktur Anda.</div>
  </div>
</div>
<?php elseif ($is_libur): ?>
<div style="background:#fdf4ff;border:1px solid #e9d5ff;border-radius:10px;padding:1rem 1.25rem;margin-bottom:1rem;display:flex;align-items:center;gap:10px;color:#15803d;">
  <i class="bi bi-calendar-heart" style="font-size:1.3rem;"></i>
  <div>
    <div style="font-weight:700;font-size:.9rem;">Hari Libur — <?= htmlspecialchars($nama_libur) ?></div>
    <div style="font-size:.8rem;margin-top:2px;">Tidak ada absensi hari ini. Selamat menikmati libur! 🎉</div>
  </div>
</div>
<?php endif; ?>

<!-- STAT -->
<div class="row g-2 mb-3">
  <?php
  $sc = [
    ['Total',     $stat['t'],              '#15803d', 'bi-calendar2-check'],
    ['Hadir',     $stat['h'],              '#16a34a', 'bi-person-check'],
    ['Sakit/Izin',$stat['s']+$stat['i'],   '#d97706', 'bi-bandaid'],
    ['Alpha',     $stat['a'],              '#dc2626', 'bi-x-circle'],
  ];
  foreach($sc as [$lbl,$val,$clr,$ico]):
  ?>
  <div class="col-6 col-md-3">
    <div class="stat-box" style="--accent-c:<?= $clr ?>">
      <div class="stat-num"><?= $val ?></div>
      <div class="stat-lbl"><?= $lbl ?></div>
      <i class="bi <?= $ico ?> stat-icon"></i>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- PROGRESS -->
<div class="si-card mb-3 p-3">
  <div style="display:flex;justify-content:space-between;margin-bottom:5px;font-size:.82rem;">
    <span style="font-weight:700;">Tingkat Kehadiran</span>
    <span style="font-weight:800;color:<?= $pct_hadir>=90?'#16a34a':($pct_hadir>=75?'#d97706':'#dc2626') ?>"><?= $pct_hadir ?>%</span>
  </div>
  <div class="prog-track">
    <div class="prog-fill" style="width:<?= $pct_hadir ?>%;background:<?= $pct_hadir>=90?'#16a34a':($pct_hadir>=75?'#d97706':'#dc2626') ?>;"></div>
  </div>
</div>

<div class="row g-3">
  <!-- FORM ABSENSI -->
  <div class="col-12 col-lg-5">
    <div class="si-card" style="position:sticky;top:calc(var(--topbar-h) + 1rem);">
      <div class="si-card-header">
        <i class="bi bi-calendar2-check-fill" style="color:var(--teal);"></i>
        Absensi Hari Ini
      </div>
      <div class="p-3">
        <div style="font-size:.8rem;color:var(--mist);margin-bottom:1rem;">
          <i class="bi bi-calendar3 me-1"></i><?= $hari_id[date('l')]??'' ?>, <?= date('d F Y') ?>
          &nbsp;·&nbsp; <span id="jam-live" style="font-weight:700;color:var(--teal);font-family:'JetBrains Mono',monospace;"></span>
        </div>

        <?php if($absen_hari_ini): ?>
        <!-- Sudah absen -->
        <div style="background:linear-gradient(135deg,var(--teal-dark),var(--teal));border-radius:12px;padding:1.25rem;color:#fff;text-align:center;">
          <div style="font-size:3rem;margin-bottom:.4rem;">
            <?= match($absen_hari_ini['status']){'hadir'=>'✅','sakit'=>'🤒','izin'=>'📋','alpha'=>'❌',default=>'📌'} ?>
          </div>
          <div style="font-size:1.1rem;font-weight:800;">Sudah Absen: <?= ucfirst($absen_hari_ini['status']) ?></div>
          <?php $jm = $absen_hari_ini['jam_masuk']??''; if($jm): ?>
          <div style="opacity:.8;font-size:.82rem;margin-top:3px;">Jam <?= date('H:i',strtotime($jm)) ?> WIB</div>
          <?php endif; ?>
          <?php if(!empty($absen_hari_ini['keterangan'])): ?>
          <div style="opacity:.7;font-size:.78rem;margin-top:3px;"><?= htmlspecialchars($absen_hari_ini['keterangan']) ?></div>
          <?php endif; ?>
          <?php if(!empty($absen_hari_ini['latitude']) && !empty($absen_hari_ini['longitude'])): ?>
          <div style="opacity:.8;font-size:.76rem;margin-top:5px;">
            <i class="bi bi-geo-alt-fill me-1"></i>
            <?= htmlspecialchars($absen_hari_ini['alamat_lokasi'] ?? 'Lokasi tercatat') ?>
            <a href="https://maps.google.com/?q=<?= $absen_hari_ini['latitude'] ?>,<?= $absen_hari_ini['longitude'] ?>"
               target="_blank" style="color:rgba(255,255,255,.8);margin-left:6px;font-size:.73rem;">
              Lihat Peta →
            </a>
          </div>
          <?php endif; ?>
        </div>
        <?php
        $fp = $absen_hari_ini['foto_selfie'] ?? '';
        if($fp && file_exists('../../'.$fp)):
        ?>
        <div style="margin-top:.85rem;text-align:center;">
          <div style="font-size:.74rem;color:var(--mist);margin-bottom:.4rem;"><i class="bi bi-camera me-1"></i>Foto Selfie</div>
          <img src="../../<?= htmlspecialchars($fp) ?>"
               style="width:110px;height:110px;object-fit:cover;border-radius:12px;border:3px solid var(--border);cursor:pointer;"
               onclick="lihatFoto('../../<?= htmlspecialchars($fp) ?>')" alt="Selfie">
        </div>
        <?php endif; ?>

        <?php elseif (!$bisa_absensi): ?>
        <!-- Tidak bisa absensi hari ini -->
        <div style="text-align:center;padding:1.5rem 1rem;color:#94a3b8;">
          <div style="font-size:2.8rem;margin-bottom:.6rem;">
            <?php
              if (!$jadwal) echo '📅';
              elseif (!$dalam_periode) echo '⏳';
              elseif (!$sudah_konfirmasi) echo '🔒';
              elseif ($is_libur) echo '🎉';
            ?>
          </div>
          <div style="font-weight:700;font-size:.9rem;color:#475569;margin-bottom:.3rem;">
            <?php
              if (!$jadwal) echo 'Jadwal belum ditetapkan';
              elseif (!$dalam_periode) echo ($today < $jadwal['tanggal_mulai'] ? 'Prakerin belum dimulai' : 'Periode prakerin selesai');
              elseif (!$sudah_konfirmasi) echo 'DU/DI belum konfirmasi';
              elseif ($is_libur) echo 'Hari libur — ' . htmlspecialchars($nama_libur);
            ?>
          </div>
          <div style="font-size:.78rem;color:#94a3b8;">Absensi tidak tersedia hari ini</div>
        </div>

        <?php else: ?>
        <!-- Form absensi + kamera -->
        <form method="POST" id="formAbsensi">
          <input type="hidden" name="foto_selfie"    id="fotoSelfieInput">
          <input type="hidden" name="latitude"       id="inputLat">
          <input type="hidden" name="longitude"      id="inputLng">
          <input type="hidden" name="alamat_lokasi"  id="inputAlamat">

          <!-- LOKASI GPS -->
          <div id="lokasiBox" style="margin-bottom:.85rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.7rem .9rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
              <div style="font-weight:700;font-size:.78rem;color:#15803d;display:flex;align-items:center;gap:5px;">
                <i class="bi bi-geo-alt-fill"></i> Lokasi GPS
              </div>
              <button type="button" id="btnAmbilLokasi"
                      style="background:#15803d;color:#fff;border:none;padding:.3rem .75rem;border-radius:7px;font-size:.75rem;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:4px;">
                <i class="bi bi-crosshair2"></i> Deteksi Lokasi
              </button>
            </div>
            <div id="lokasiStatus" style="font-size:.75rem;color:#64748b;margin-top:.4rem;">
              Klik "Deteksi Lokasi" untuk mengambil koordinat GPS Anda.
            </div>
            <div id="lokasiDetail" style="display:none;margin-top:.5rem;">
              <div id="lokasiAlamat" style="font-size:.78rem;font-weight:600;color:#0f172a;"></div>
              <div id="lokasiKoord"  style="font-size:.7rem;color:#64748b;font-family:monospace;margin-top:2px;"></div>
              <div id="lokasiPeta" style="margin-top:.5rem;border-radius:8px;overflow:hidden;height:120px;"></div>
            </div>
          </div>

          <!-- KAMERA AREA -->
          <div style="margin-bottom:.85rem;">
            <div style="font-weight:700;font-size:.78rem;color:var(--ink);margin-bottom:.45rem;">
              <i class="bi bi-camera-fill me-1" style="color:var(--teal);"></i>Foto Selfie <span style="color:var(--mist);font-weight:400;">(opsional)</span>
            </div>
            <div id="camArea" style="position:relative;background:#f8fafc;border-radius:12px;overflow:hidden;aspect-ratio:4/3;
                 display:flex;align-items:center;justify-content:center;border:2px dashed var(--border);">
              <video id="videoEl" autoplay playsinline muted
                     style="width:100%;height:100%;object-fit:cover;display:none;transform:scaleX(-1);"></video>
              <canvas id="canvasEl" style="width:100%;height:100%;object-fit:cover;display:none;"></canvas>
              <div id="camPh" style="text-align:center;color:var(--mist);padding:1rem;">
                <i class="bi bi-camera" style="font-size:2.5rem;display:block;margin-bottom:.4rem;opacity:.4;"></i>
                <div style="font-size:.76rem;">Klik "Buka Kamera" untuk selfie</div>
              </div>
            </div>
            <div style="display:flex;gap:.4rem;margin-top:.5rem;">
              <button type="button" id="btnBuka" class="btn-ghost-si" style="flex:1;justify-content:center;">
                <i class="bi bi-camera"></i> Buka Kamera
              </button>
              <button type="button" id="btnAmbil" class="btn-teal" style="flex:1;justify-content:center;display:none;">
                <i class="bi bi-camera-fill"></i> Ambil
              </button>
              <button type="button" id="btnUlang" class="btn-ghost-si" style="flex:1;justify-content:center;display:none;">
                <i class="bi bi-arrow-counterclockwise"></i> Ulang
              </button>
            </div>
            <div id="tsBox" style="display:none;background:var(--teal-pale);border:1px solid var(--teal-light);
                 border-radius:8px;padding:.4rem .75rem;margin-top:.4rem;font-size:.74rem;color:var(--teal-dark);">
              <i class="bi bi-check-circle me-1"></i>Foto berhasil: <span id="tsVal"></span>
            </div>
          </div>

          <!-- STATUS -->
          <div style="font-weight:700;font-size:.78rem;margin-bottom:.4rem;">
            <i class="bi bi-clipboard-check me-1" style="color:var(--teal);"></i>Status Kehadiran
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:.4rem;margin-bottom:.75rem;">
            <?php foreach([['hadir','bi-check-circle-fill','Hadir','#16a34a'],['sakit','bi-thermometer','Sakit','#d97706'],['izin','bi-file-earmark','Izin','#15803d'],['alpha','bi-x-circle-fill','Alpha','#dc2626']] as [$v,$ic,$lb,$cl]): ?>
            <label id="lbl_<?= $v ?>" onclick="pilihStatus('<?= $v ?>')"
                   style="display:flex;align-items:center;gap:8px;padding:.6rem .75rem;
                          border:2px solid var(--border);border-radius:9px;cursor:pointer;transition:.15s;">
              <input type="radio" name="status" value="<?= $v ?>" <?= $v==='hadir'?'checked':'' ?> style="display:none;">
              <i class="bi <?= $ic ?>" style="color:<?= $cl ?>;font-size:1rem;"></i>
              <span style="font-weight:600;font-size:.82rem;"><?= $lb ?></span>
            </label>
            <?php endforeach; ?>
          </div>

          <div id="ketGroup" style="display:none;margin-bottom:.75rem;">
            <label class="form-label">Keterangan</label>
            <textarea name="keterangan" class="form-control" rows="2" placeholder="Contoh: Demam, izin ke dokter..."></textarea>
          </div>

          <button type="submit" class="btn-teal" style="width:100%;justify-content:center;">
            <i class="bi bi-check-lg"></i> Simpan Absensi
          </button>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- RIWAYAT -->
  <div class="col-12 col-lg-7">
    <div class="si-card">
      <div class="si-card-header">
        <i class="bi bi-clock-history" style="color:var(--teal);"></i>
        Riwayat Absensi
        <div class="ms-auto" style="display:flex;gap:6px;align-items:center;">
          <a href="../../export/absensi.php?siswa_id=<?= $sid ?><?= $bulan ? '&bulan='.$bulan : '' ?>" target="_blank"
             style="display:inline-flex;align-items:center;gap:5px;padding:.3rem .75rem;background:#14532d;color:#fff;border-radius:6px;font-size:.76rem;font-weight:600;text-decoration:none;white-space:nowrap;">
            🖨️ Export PDF
          </a>
          <form method="GET" style="display:flex;gap:.4rem;">
            <select name="bulan" onchange="this.form.submit()" class="form-select form-select-sm" style="min-width:130px;font-size:.78rem;">
              <option value="">Semua Bulan</option>
              <?php foreach($bulan_list as $b): ?>
              <option value="<?= $b['bln'] ?>" <?= $bulan===$b['bln']?'selected':'' ?>><?= $b['lbl'] ?></option>
              <?php endforeach; ?>
            </select>
            <?php if($bulan): ?><a href="?" class="btn-ghost-si" style="font-size:.76rem;">Reset</a><?php endif; ?>
          </form>
        </div>
      </div>
      <div class="si-table-wrap"><table class="si-table">
        <thead><tr>
          <th>Tanggal</th><th>Hari</th><th>Status</th><th>Jam</th><th style="text-align:center">Selfie</th>
        </tr></thead>
        <tbody>
        <?php
        $has_foto  = mysqli_num_rows(mysqli_query($conn,"SHOW COLUMNS FROM absensi LIKE 'foto_selfie'")) > 0;
$has_lokasi= mysqli_num_rows(mysqli_query($conn,"SHOW COLUMNS FROM absensi LIKE 'latitude'"))    > 0;
        $cnt = 0;
        while($a=mysqli_fetch_assoc($absensi_list)): $cnt++;
          $badge = match($a['status']){
            'hadir'=>'badge-green','sakit'=>'badge-yellow','izin'=>'badge-blue','alpha'=>'badge-red',default=>'badge-gray'
          };
          $hh = $hari_id[date('l',strtotime($a['tanggal']))]??'';
          $fp = $has_foto ? ($a['foto_selfie']??'') : '';
        ?>
        <tr>
          <td>
            <div style="font-weight:700;font-size:.84rem;"><?= date('d M Y',strtotime($a['tanggal'])) ?></div>
            <?php if($a['keterangan']): ?><div style="font-size:.72rem;color:var(--mist);"><?= htmlspecialchars($a['keterangan']) ?></div><?php endif; ?>
          </td>
          <td style="font-size:.8rem;color:var(--slate);"><?= $hh ?></td>
          <td><span class="<?= $badge ?>"><?= ucfirst($a['status']) ?></span></td>
          <td style="font-size:.78rem;color:var(--slate);font-family:'JetBrains Mono',monospace;">
            <?= $a['jam_masuk'] ? date('H:i',strtotime($a['jam_masuk'])) : '-' ?>
          </td>
          <td style="font-size:.75rem;">
            <?php
            $has_lok = isset($a['latitude']) && $a['latitude'];
            if ($has_lok):
              $maps_url = "https://maps.google.com/?q={$a['latitude']},{$a['longitude']}";
            ?>
            <a href="<?= $maps_url ?>" target="_blank"
               style="display:inline-flex;align-items:center;gap:3px;color:#15803d;font-weight:600;font-size:.72rem;text-decoration:none;background:#f0fdf4;padding:.2rem .5rem;border-radius:5px;border:1px solid #bbf7d0;">
              <i class="bi bi-geo-alt-fill"></i> Peta
            </a>
            <?php if(!empty($a['alamat_lokasi'])): ?>
            <div style="color:#64748b;font-size:.68rem;margin-top:2px;max-width:100px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"
                 title="<?= htmlspecialchars($a['alamat_lokasi']) ?>">
              <?= htmlspecialchars($a['alamat_lokasi']) ?>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <span style="color:#cbd5e1;font-size:.72rem;">—</span>
            <?php endif; ?>
          </td>
          <td style="text-align:center;">
            <?php if($fp && file_exists('../../'.$fp)): ?>
            <img src="../../<?= htmlspecialchars($fp) ?>"
                 style="width:38px;height:38px;object-fit:cover;border-radius:7px;cursor:pointer;border:2px solid var(--border);"
                 onclick="lihatFoto('../../<?= htmlspecialchars($fp) ?>')" title="Lihat foto">
            <?php else: ?>
            <span style="color:var(--border);font-size:.75rem;">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile;
        if($cnt===0): ?>
        <tr><td colspan="5" style="text-align:center;padding:2rem;color:var(--mist);">
          <i class="bi bi-inbox" style="font-size:1.5rem;display:block;margin-bottom:.4rem;"></i>
          Belum ada data absensi.
        </td></tr>
        <?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>

<!-- Modal Foto -->
<div id="fotoModal" onclick="this.style.display='none'"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:9999;
            align-items:center;justify-content:center;cursor:pointer;">
  <div style="text-align:center;">
    <img id="fotoModalImg" style="max-width:88vw;max-height:80vh;border-radius:14px;object-fit:contain;box-shadow:0 20px 60px rgba(0,0,0,.5);">
    <div style="color:rgba(255,255,255,.5);font-size:.78rem;margin-top:.6rem;">Klik di mana saja untuk menutup</div>
  </div>
</div>

<script>
// JAM LIVE
(function tick(){
  const el = document.getElementById('jam-live');
  if(el){
    const now = new Date();
    el.textContent = now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'})+' WIB';
  }
  setTimeout(tick, 1000);
})();

// PILIH STATUS
function pilihStatus(val){
  ['hadir','sakit','izin','alpha'].forEach(v=>{
    const el = document.getElementById('lbl_'+v);
    if(!el) return;
    const active = v===val;
    el.style.borderColor = active ? 'var(--teal)' : 'var(--border)';
    el.style.background  = active ? 'var(--teal-pale)' : '';
  });
  document.querySelectorAll('input[name=status]').forEach(r=>r.checked=r.value===val);
  const kg = document.getElementById('ketGroup');
  if(kg) kg.style.display = val!=='hadir' ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded',()=>pilihStatus('hadir'));

// KAMERA
let camStream = null;
const videoEl = document.getElementById('videoEl');
const canvasEl = document.getElementById('canvasEl');
const camPh   = document.getElementById('camPh');
const btnBuka  = document.getElementById('btnBuka');
const btnAmbil = document.getElementById('btnAmbil');
const btnUlang = document.getElementById('btnUlang');
const tsBox    = document.getElementById('tsBox');
const tsVal    = document.getElementById('tsVal');
const fotoInput= document.getElementById('fotoSelfieInput');

if(btnBuka) btnBuka.addEventListener('click', async ()=>{
  try {
    camStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:640,height:480},audio:false});
    videoEl.srcObject = camStream;
    videoEl.style.display = 'block'; camPh.style.display = 'none';
    btnBuka.style.display = 'none'; btnAmbil.style.display = '';
  } catch(e){ alert('Kamera tidak bisa dibuka: '+e.message); }
});

if(btnAmbil) btnAmbil.addEventListener('click', ()=>{
  const ctx = canvasEl.getContext('2d');
  canvasEl.width  = videoEl.videoWidth  || 640;
  canvasEl.height = videoEl.videoHeight || 480;
  // Mirror flip
  ctx.translate(canvasEl.width, 0); ctx.scale(-1,1);
  ctx.drawImage(videoEl, 0, 0);
  ctx.setTransform(1,0,0,1,0,0);
  // Timestamp overlay
  const now = new Date();
  const ts  = now.toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'})
              +' '+now.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  ctx.fillStyle='rgba(0,0,0,.55)';
  ctx.fillRect(0, canvasEl.height-36, canvasEl.width, 36);
  ctx.fillStyle='#fff'; ctx.font='bold 13px Arial';
  ctx.fillText('📍 SiPrakerin SMKN3 · '+ts, 10, canvasEl.height-12);

  if(camStream) camStream.getTracks().forEach(t=>t.stop());
  videoEl.style.display='none'; canvasEl.style.display='block';
  fotoInput.value = canvasEl.toDataURL('image/jpeg', 0.85);
  btnAmbil.style.display='none'; btnUlang.style.display='';
  if(tsBox){ tsBox.style.display='block'; if(tsVal) tsVal.textContent=ts; }
});

if(btnUlang) btnUlang.addEventListener('click', async ()=>{
  canvasEl.style.display='none'; fotoInput.value='';
  tsBox && (tsBox.style.display='none');
  btnUlang.style.display='none';
  try {
    camStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:640,height:480},audio:false});
    videoEl.srcObject=camStream; videoEl.style.display='block';
    btnAmbil.style.display='';
  } catch(e){ alert('Kamera gagal dibuka ulang: '+e.message); }
});

function lihatFoto(src){
  document.getElementById('fotoModalImg').src=src;
  document.getElementById('fotoModal').style.display='flex';
}

// ── GPS LOKASI ──
const btnAmbilLokasi = document.getElementById('btnAmbilLokasi');
const lokasiStatus   = document.getElementById('lokasiStatus');
const lokasiDetail   = document.getElementById('lokasiDetail');
const lokasiAlamat   = document.getElementById('lokasiAlamat');
const lokasiKoord    = document.getElementById('lokasiKoord');
const lokasiPeta     = document.getElementById('lokasiPeta');

if (btnAmbilLokasi) {
  btnAmbilLokasi.addEventListener('click', () => {
    if (!navigator.geolocation) {
      lokasiStatus.textContent = '❌ Browser tidak mendukung GPS.';
      return;
    }
    lokasiStatus.innerHTML = '<span style="color:#d97706;">⏳ Mengambil lokasi...</span>';
    btnAmbilLokasi.disabled = true;

    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        const acc = Math.round(pos.coords.accuracy);

        document.getElementById('inputLat').value = lat;
        document.getElementById('inputLng').value = lng;

        lokasiStatus.innerHTML = `<span style="color:#15803d;">✅ Lokasi berhasil diambil (akurasi ±${acc}m)</span>`;
        lokasiKoord.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
        lokasiDetail.style.display = 'block';

        // Reverse geocode pakai Nominatim (gratis, no API key)
        try {
          const res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&accept-language=id`,
            { headers: { 'Accept-Language': 'id' } }
          );
          const data = await res.json();
          const alamat = data.display_name || `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
          // Ambil bagian penting saja
          const ad = data.address || {};
          const pendek = [ad.road||ad.pedestrian, ad.suburb||ad.neighbourhood, ad.city||ad.town||ad.village, ad.state]
                         .filter(Boolean).slice(0,3).join(', ');
          const tampil = pendek || alamat;
          lokasiAlamat.textContent = '📍 ' + tampil;
          document.getElementById('inputAlamat').value = tampil;

          // Embed peta OpenStreetMap (iframe ringan)
          const zoom = acc < 50 ? 17 : acc < 200 ? 15 : 13;
          lokasiPeta.innerHTML = `
            <iframe
              src="https://www.openstreetmap.org/export/embed.html?bbox=${lng-0.001},${lat-0.001},${lng+0.001},${lat+0.001}&layer=mapnik&marker=${lat},${lng}"
              style="width:100%;height:120px;border:none;border-radius:8px;"
              loading="lazy">
            </iframe>
            <div style="text-align:right;margin-top:3px;">
              <a href="https://maps.google.com/?q=${lat},${lng}" target="_blank"
                 style="font-size:.7rem;color:#15803d;font-weight:600;">
                Buka di Google Maps →
              </a>
            </div>`;
        } catch(e) {
          const tampil = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
          lokasiAlamat.textContent = '📍 ' + tampil;
          document.getElementById('inputAlamat').value = tampil;
          lokasiPeta.innerHTML = `
            <a href="https://maps.google.com/?q=${lat},${lng}" target="_blank"
               style="display:block;text-align:center;padding:.5rem;background:#f0fdf4;border-radius:8px;color:#15803d;font-weight:600;font-size:.8rem;text-decoration:none;">
              🗺️ Buka di Google Maps
            </a>`;
        }
        btnAmbilLokasi.textContent = '✅ Lokasi Terambil';
        btnAmbilLokasi.style.background = '#166534';
      },
      (err) => {
        const msg = {
          1: 'Izin lokasi ditolak. Aktifkan izin lokasi di browser.',
          2: 'Lokasi tidak dapat ditentukan.',
          3: 'Waktu habis. Coba lagi.'
        }[err.code] || 'Gagal mengambil lokasi.';
        lokasiStatus.innerHTML = `<span style="color:#dc2626;">❌ ${msg}</span>`;
        btnAmbilLokasi.disabled = false;
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
  });
}
</script>
<?php require '../layout/footer.php'; ?>
