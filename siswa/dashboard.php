<?php
session_start();
require '../config/database.php';
require '../config/onboarding_check.php';
if(empty($_SESSION['login'])||$_SESSION['role']!=='siswa'){header("Location: ../auth/login.php");exit;}
$page_title = 'Dashboard';
$uid = $_SESSION['user_id'];
date_default_timezone_set('Asia/Jakarta');

// Data siswa lengkap
$ss = mysqli_prepare($conn,"
  SELECT s.*,u.nama,
    tp.nama_tempat, tp.alamat as alamat_pkl, 
    gu.nama as nama_guru,
    iu.nama as nama_instruktur
  FROM siswa s
  JOIN users u ON s.user_id=u.id
  LEFT JOIN tempat_pkl tp ON s.tempat_pkl_id=tp.id
  LEFT JOIN guru g ON s.guru_id=g.id
  LEFT JOIN users gu ON g.user_id=gu.id
  LEFT JOIN instruktur i ON s.instruktur_id=i.id
  LEFT JOIN users iu ON i.user_id=iu.id
  WHERE s.user_id=?
");
mysqli_stmt_bind_param($ss,"i",$uid); mysqli_stmt_execute($ss);
$s = mysqli_fetch_assoc(mysqli_stmt_get_result($ss));
$sid = $s['id'] ?? 0;

// Jurnal stats
$sq = mysqli_prepare($conn,"SELECT COUNT(*) t, SUM(status='disetujui') ok, SUM(status='pending') p, SUM(status='ditolak') x FROM jurnal WHERE siswa_id=?");
mysqli_stmt_bind_param($sq,"i",$sid); mysqli_stmt_execute($sq);
$j = mysqli_fetch_assoc(mysqli_stmt_get_result($sq));

// Absensi stats
$sa = mysqli_prepare($conn,"SELECT COUNT(*) t, SUM(status='hadir') h, SUM(status='sakit') s, SUM(status='izin') i FROM absensi WHERE siswa_id=?");
mysqli_stmt_bind_param($sa,"i",$sid); mysqli_stmt_execute($sa);
$ab = mysqli_fetch_assoc(mysqli_stmt_get_result($sa));

// Jurnal pending terbaru
$qp = mysqli_prepare($conn,"SELECT tanggal, LEFT(kegiatan,80) keg, status FROM jurnal WHERE siswa_id=? ORDER BY tanggal DESC LIMIT 4");
mysqli_stmt_bind_param($qp,"i",$sid); mysqli_stmt_execute($qp);
$jurnals_recent = mysqli_stmt_get_result($qp);

// Sudah absen hari ini?
$today = date('Y-m-d');
$ck = mysqli_prepare($conn,"SELECT status, jam_masuk FROM absensi WHERE siswa_id=? AND tanggal=?");
mysqli_stmt_bind_param($ck,"is",$sid,$today); mysqli_stmt_execute($ck);
$absen_hari_ini = mysqli_fetch_assoc(mysqli_stmt_get_result($ck));

$h = (int)date('H');
$sap = $h<11?'Pagi':($h<15?'Siang':($h<18?'Sore':'Malam'));
$pct_j = $j['t']>0 ? round($j['ok']/$j['t']*100) : 0;
$pct_a = $ab['t']>0 ? round($ab['h']/$ab['t']*100) : 0;

$sisa_hari = null;

require '../siswa/layout/header.php';
?>

<!-- HERO GREETING -->
<div style="background:linear-gradient(135deg, var(--teal-dark) 0%, var(--teal) 50%, #22c55e 100%);
     border-radius:16px; padding:1.75rem 2rem; margin-bottom:1.5rem;
     position:relative; overflow:hidden; color:#fff;">
  <!-- decorative circles -->
  <div style="position:absolute;right:-30px;top:-30px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);pointer-events:none;"></div>
  <div style="position:absolute;right:80px;bottom:-55px;width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,.04);pointer-events:none;"></div>
  <div style="position:absolute;right:1.75rem;top:50%;transform:translateY(-50%);font-size:4rem;opacity:.12;pointer-events:none;">🎓</div>

  <div style="position:relative;z-index:1;max-width:600px;">
    <div style="font-size:.75rem;font-weight:600;opacity:.65;letter-spacing:.06em;text-transform:uppercase;margin-bottom:.35rem;">
      <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle;"></i>
      Selamat <?= $sap ?>
    </div>
    <h1 style="font-size:1.5rem;font-weight:800;margin:0 0 .35rem;letter-spacing:-.02em;">
      <?= htmlspecialchars($s['nama']) ?>
    </h1>
    <div style="font-size:.82rem;opacity:.75;display:flex;align-items:center;gap:.85rem;flex-wrap:wrap;">
      <span><i class="bi bi-building me-1"></i><?= $s['nama_tempat'] ? htmlspecialchars($s['nama_tempat']) : 'Belum ditempatkan' ?></span>
      <span><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($s['kelas']) ?></span>
    </div>

    <!-- absen notif -->
    <?php if(!$absen_hari_ini): ?>
    <a href="absensi/index.php" style="display:inline-flex;align-items:center;gap:7px;margin-top:1rem;
       background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
       backdrop-filter:blur(6px);color:#fff;padding:.45rem 1rem;border-radius:8px;
       font-size:.78rem;font-weight:700;transition:.18s;"
       onmouseover="this.style.background='rgba(255,255,255,.25)'"
       onmouseout="this.style.background='rgba(255,255,255,.15)'">
      <i class="bi bi-camera-fill"></i> Belum absen — Absen Sekarang
    </a>
    <?php else: ?>
    <div style="display:inline-flex;align-items:center;gap:7px;margin-top:1rem;
         background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
         color:rgba(255,255,255,.9);padding:.4rem .9rem;border-radius:8px;font-size:.78rem;font-weight:600;">
      <i class="bi bi-check-circle-fill"></i>
      Sudah absen — <?= ucfirst($absen_hari_ini['status']) ?>
      <?= $absen_hari_ini['jam_masuk'] ? ' · '.date('H:i',strtotime($absen_hari_ini['jam_masuk'])).' WIB' : '' ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- STAT ROW -->
<div class="row g-3 mb-4">
  <?php
  $stats = [
    ['Jurnal Masuk',    $j['t'],     'bi-journal-text',      '#15803d'],
    ['Disetujui',       $j['ok'],    'bi-check-circle-fill', '#16a34a'],
    ['Absensi Tercatat',$ab['t'],    'bi-calendar2-check',   '#15803d'],
    ['Kehadiran',       $pct_a.'%',  'bi-person-check-fill', $pct_a>=90?'#16a34a':($pct_a>=75?'#d97706':'#dc2626')],
  ];
  foreach($stats as [$lbl,$val,$ico,$clr]):
  ?>
  <div class="col-6 col-xl-3">
    <div class="stat-box" style="--accent-c:<?= $clr ?>">
      <div class="stat-num"><?= $val ?></div>
      <div class="stat-lbl"><?= $lbl ?></div>
      <i class="bi <?= $ico ?> stat-icon"></i>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">

  <!-- INFO PKL -->
  <div class="col-12 col-lg-7">
    <div class="si-card h-100">
      <div class="si-card-header">
        <i class="bi bi-building-fill" style="color:var(--teal);"></i>
        Informasi PKL Kamu
        <?php if($s['nama_tempat']): ?>
        <span class="badge-teal ms-auto"><i class="bi bi-circle-fill me-1" style="font-size:.45rem;"></i>AKTIF</span>
        <?php else: ?>
        <span class="badge-gray ms-auto">Belum Ditempatkan</span>
        <?php endif; ?>
      </div>
      <div class="p-3">
        <?php if($s['nama_tempat']): ?>
        <div class="row g-3">
          <?php
          $info = [
            ['bi-building','Tempat PKL',        $s['nama_tempat'],             '#15803d'],
            ['bi-person-badge','Guru Pembimbing',$s['nama_guru']??'-',          '#15803d'],
            ['bi-person-workspace','Instruktur', $s['nama_instruktur']??'-',    '#0369a1'],
            ['bi-geo-alt','Alamat PKL',          $s['alamat_pkl']??'-',         '#d97706'],
          ];
          if($sisa_hari !== null){
            $info[] = ['bi-clock','Sisa Waktu PKL',
              $sisa_hari>0 ? "$sisa_hari Hari Lagi" : 'Selesai', $sisa_hari>0?'#dc2626':'#16a34a'];
          }
          foreach($info as [$ico,$lbl,$val,$clr]):
          ?>
          <div class="col-6">
            <div style="display:flex;gap:9px;align-items:flex-start;">
              <div style="width:32px;height:32px;border-radius:8px;flex-shrink:0;
                          background:<?= $clr ?>18;display:flex;align-items:center;justify-content:center;">
                <i class="bi <?= $ico ?>" style="color:<?= $clr ?>;font-size:.88rem;"></i>
              </div>
              <div>
                <div style="font-size:.68rem;color:var(--mist);font-weight:500;margin-bottom:2px;"><?= $lbl ?></div>
                <div style="font-weight:700;font-size:.84rem;color:var(--ink);"><?= htmlspecialchars($val) ?></div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align:center;padding:2rem;color:var(--mist);">
          <i class="bi bi-building-slash" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
          Belum ditempatkan. Hubungi admin atau guru pembimbing.
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- AKSI CEPAT -->
  <div class="col-12 col-lg-5">
    <div class="si-card h-100">
      <div class="si-card-header">
        <i class="bi bi-lightning-charge-fill" style="color:var(--amber);"></i>
        Aksi Cepat
      </div>
      <div style="padding:.5rem;">
        <?php
        $menus = [
          ['absensi/index.php',   'bi-camera-fill',       'Absensi + Selfie',  'Catat kehadiran hari ini',  'var(--teal)',   'var(--teal-pale)'],
          ['jurnal/index.php',    'bi-journal-plus',      'Isi Jurnal',        'Input kegiatan harian',     '#15803d',       '#f5f3ff'],
          ['profil/index.php',    'bi-person-circle',     'Profil Saya',       'Data diri & info PKL',      '#0369a1',       '#f0f9ff'],
          ['penilaian/index.php', 'bi-patch-check-fill',  'Lihat Nilai',       'Nilai dari pembimbing',     '#d97706',       '#fffbeb'],
        ];
        foreach($menus as [$url,$ico,$name,$desc,$clr,$bg]):
        ?>
        <a href="<?= $url ?>" style="display:flex;align-items:center;gap:11px;padding:.7rem .85rem;
           border-radius:10px;text-decoration:none;transition:.15s;margin-bottom:2px;"
           onmouseover="this.style.background='<?= $bg ?>'" onmouseout="this.style.background=''">
          <div style="width:38px;height:38px;border-radius:10px;background:<?= $bg ?>;
               display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid <?= $clr ?>22;">
            <i class="bi <?= $ico ?>" style="color:<?= $clr ?>;font-size:1rem;"></i>
          </div>
          <div style="flex:1;">
            <div style="font-weight:700;font-size:.84rem;color:var(--ink);"><?= $name ?></div>
            <div style="font-size:.72rem;color:var(--mist);margin-top:1px;"><?= $desc ?></div>
          </div>
          <i class="bi bi-chevron-right" style="color:var(--border);font-size:.75rem;"></i>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- JURNAL TERBARU + PROGRESS -->
<div class="row g-3">
  <div class="col-12 col-lg-7">
    <div class="si-card">
      <div class="si-card-header">
        <i class="bi bi-journal-richtext" style="color:var(--teal);"></i>
        Jurnal Terbaru
        <a href="jurnal/index.php" style="margin-left:auto;font-size:.76rem;color:var(--teal);font-weight:600;">
          Lihat Semua <i class="bi bi-arrow-right"></i>
        </a>
      </div>
      <?php $cnt=0; while($jn=mysqli_fetch_assoc($jurnals_recent)): $cnt++;
        $badge = match($jn['status']){
          'disetujui'=>['badge-green','Disetujui'],
          'ditolak'  =>['badge-red','Ditolak'],
          default    =>['badge-yellow','Pending'],
        };
      ?>
      <div style="display:flex;align-items:center;gap:12px;padding:.8rem 1.1rem;border-bottom:1px solid #f8fafc;">
        <div style="width:36px;height:36px;border-radius:9px;background:var(--teal-pale);
             display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="bi bi-file-text" style="color:var(--teal);font-size:.88rem;"></i>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-weight:700;font-size:.83rem;"><?= date('d M Y',strtotime($jn['tanggal'])) ?></div>
          <div style="font-size:.74rem;color:var(--mist);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($jn['keg']) ?>...</div>
        </div>
        <span class="<?= $badge[0] ?>"><?= $badge[1] ?></span>
      </div>
      <?php endwhile;
      if($cnt===0): ?>
      <div style="text-align:center;padding:2.5rem;color:var(--mist);">
        <i class="bi bi-journal" style="font-size:2rem;display:block;margin-bottom:.5rem;opacity:.35;"></i>
        Belum ada jurnal. Yuk mulai isi!
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- PROGRESS -->
  <div class="col-12 col-lg-5">
    <div class="si-card">
      <div class="si-card-header">
        <i class="bi bi-bar-chart-fill" style="color:var(--teal);"></i>
        Progress PKL
      </div>
      <div style="padding:1.1rem;">
        <?php
        $progs = [
          ['Jurnal Disetujui', $pct_j, $j['ok'].' / '.$j['t'].' jurnal',      $pct_j>=80?'#16a34a':($pct_j>=50?'#d97706':'#dc2626')],
          ['Tingkat Kehadiran',$pct_a, $ab['h'].' / '.$ab['t'].' hari hadir', $pct_a>=90?'#16a34a':($pct_a>=75?'#d97706':'#dc2626')],
        ];
        foreach($progs as [$lbl,$pct,$sub,$clr]):
        ?>
        <div style="margin-bottom:1.25rem;">
          <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
            <span style="font-weight:600;font-size:.82rem;"><?= $lbl ?></span>
            <span style="font-weight:800;font-size:.82rem;color:<?= $clr ?>;"><?= $pct ?>%</span>
          </div>
          <div class="prog-track">
            <div class="prog-fill" style="width:<?= $pct ?>%;background:<?= $clr ?>;"></div>
          </div>
          <div style="font-size:.72rem;color:var(--mist);margin-top:3px;"><?= $sub ?></div>
        </div>
        <?php endforeach; ?>

        <div style="background:var(--teal-pale);border-radius:10px;padding:.85rem;margin-top:.5rem;">
          <div style="font-size:.72rem;color:var(--teal-dark);font-weight:600;margin-bottom:.4rem;">
            <i class="bi bi-info-circle me-1"></i> Ringkasan
          </div>
          <div style="display:flex;gap:1.5rem;font-size:.78rem;flex-wrap:wrap;">
            <span><span style="font-weight:800;color:var(--teal);"><?= $j['p'] ?></span> <span style="color:var(--slate);">jurnal pending</span></span>
            <span><span style="font-weight:800;color:#dc2626;"><?= $ab['t']-$ab['h'] ?></span> <span style="color:var(--slate);">tidak hadir</span></span>
          </div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require '../siswa/layout/footer.php'; ?>
