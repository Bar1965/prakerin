<?php
require '../middleware/auth_admin.php';
require '../../config/database.php';
$page_title = 'Dashboard';

date_default_timezone_set('Asia/Jakarta');
$jam = (int)date('H');
$sapaan = $jam<11?'Pagi':($jam<15?'Siang':($jam<18?'Sore':'Malam'));

function qCount($conn,$sql){
  $r=mysqli_query($conn,$sql);
  return mysqli_fetch_assoc($r)['n'];
}
$st_siswa      = qCount($conn,"SELECT COUNT(*) n FROM siswa");
$st_guru       = qCount($conn,"SELECT COUNT(*) n FROM guru");
$st_instruktur = qCount($conn,"SELECT COUNT(*) n FROM instruktur");
$st_tempat     = qCount($conn,"SELECT COUNT(*) n FROM tempat_pkl");
$st_jurnal     = qCount($conn,"SELECT COUNT(*) n FROM jurnal");
$st_pending    = qCount($conn,"SELECT COUNT(*) n FROM jurnal WHERE status='pending'");

$recent = mysqli_query($conn,"
  SELECT j.tanggal,j.status,u.nama,s.kelas
  FROM jurnal j
  JOIN siswa s ON j.siswa_id=s.id
  JOIN users u ON s.user_id=u.id
  ORDER BY j.created_at DESC LIMIT 7
");

// ── Data grafik absensi 7 hari terakhir ──
$grafik_labels = [];
$grafik_hadir  = [];
$grafik_alpha  = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $grafik_labels[] = date('d M', strtotime($tgl));
    $rh = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM absensi WHERE tanggal='$tgl' AND status='hadir'"));
    $ra = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) n FROM absensi WHERE tanggal='$tgl' AND status='alpha'"));
    $grafik_hadir[] = (int)$rh['n'];
    $grafik_alpha[] = (int)$ra['n'];
}

// ── Jumlah siswa per tempat PKL ──
$tempat_data = mysqli_query($conn,"
    SELECT tp.nama_tempat, COUNT(s.id) AS jumlah
    FROM tempat_pkl tp
    LEFT JOIN siswa s ON s.tempat_pkl_id = tp.id
    GROUP BY tp.id
    ORDER BY jumlah DESC LIMIT 8
");
$tempat_labels = [];
$tempat_jumlah = [];
while ($r = mysqli_fetch_assoc($tempat_data)) {
    // Potong nama yang terlalu panjang
    $tempat_labels[] = strlen($r['nama_tempat']) > 20
        ? substr($r['nama_tempat'], 0, 18) . '…'
        : $r['nama_tempat'];
    $tempat_jumlah[] = (int)$r['jumlah'];
}

// ── Persentase kehadiran overall ──
$abs_total  = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM absensi"))['n']);
$abs_hadir  = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM absensi WHERE status='hadir'"))['n']);
$abs_sakit  = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM absensi WHERE status='sakit'"))['n']);
$abs_izin   = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM absensi WHERE status='izin'"))['n']);
$abs_alpha  = (int)(mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) n FROM absensi WHERE status='alpha'"))['n']);
$pct_hadir  = $abs_total > 0 ? round($abs_hadir/$abs_total*100) : 0;

require '../layout/header.php';
?>

<!-- WELCOME BANNER -->
<div style="background:linear-gradient(135deg,#14532d,#15803d,#16a34a);border-radius:14px;padding:1.75rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
  <div style="position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.07);"></div>
  <div style="position:absolute;right:60px;bottom:-50px;width:130px;height:130px;border-radius:50%;background:rgba(255,255,255,.05);"></div>
  <h1 style="color:#fff;font-size:1.35rem;font-weight:800;margin:0;">Selamat <?= $sapaan ?>, <?= htmlspecialchars(explode(' ',$_SESSION['nama'])[0]) ?>! 👋</h1>
  <p style="color:rgba(255,255,255,.65);font-size:.875rem;margin:.4rem 0 0;">Panel Administrator &nbsp;·&nbsp; <?= date('l, d F Y') ?></p>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['icon'=>'bi-people-fill',      'label'=>'Total Siswa',      'val'=>$st_siswa,      'color'=>'#15803d'],
    ['icon'=>'bi-person-badge-fill','label'=>'Guru Pembimbing',  'val'=>$st_guru,       'color'=>'#059669'],
    ['icon'=>'bi-building-fill',    'label'=>'Instruktur DU/DI', 'val'=>$st_instruktur, 'color'=>'#d97706'],
    ['icon'=>'bi-geo-alt-fill',     'label'=>'Tempat PKL',       'val'=>$st_tempat,     'color'=>'#dc2626'],
    ['icon'=>'bi-journal-text',     'label'=>'Total Jurnal',     'val'=>$st_jurnal,     'color'=>'#15803d'],
    ['icon'=>'bi-clock-history',    'label'=>'Jurnal Pending',   'val'=>$st_pending,    'color'=>'#ea580c'],
  ];
  foreach($cards as $c): ?>
  <div class="col-6 col-md-4 col-xl-2">
    <div class="stat-card" style="--accent-color:<?= $c['color'] ?>">
      <div class="stat-num"><?= $c['val'] ?></div>
      <div class="stat-label"><?= $c['label'] ?></div>
      <i class="bi <?= $c['icon'] ?> stat-icon"></i>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <!-- Jurnal Terbaru -->
  <div class="col-12 col-lg-7">
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="bi bi-journal-check me-2 text-primary"></i>Jurnal Terbaru</h5>
      </div>
      <?php if(mysqli_num_rows($recent)===0): ?>
        <div class="empty-state"><i class="bi bi-inbox"></i><p>Belum ada jurnal</p></div>
      <?php else: ?>
      <table class="tbl">
        <thead><tr>
          <th>Siswa</th><th>Kelas</th><th>Tanggal</th><th>Status</th>
        </tr></thead>
        <tbody>
        <?php while($r=mysqli_fetch_assoc($recent)):
          $badge = match($r['status']) {
            'disetujui' => ['badge-green','Disetujui'],
            'ditolak'   => ['badge-red','Ditolak'],
            default     => ['badge-yellow','Pending'],
          };
        ?>
        <tr>
          <td><span style="font-weight:600;"><?= htmlspecialchars($r['nama']) ?></span></td>
          <td><span class="badge <?= $badge[0] ?>"><?= $r['kelas'] ?></span></td>
          <td style="color:#64748b;"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
          <td><span class="badge <?= $badge[0] ?>"><?= $badge[1] ?></span></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>

  <!-- Akses Cepat -->
  <div class="col-12 col-lg-5">
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="bi bi-lightning-charge-fill me-2 text-warning"></i>Akses Cepat</h5>
      </div>
      <?php
      $shortcuts = [
        ['../siswa/tambah.php',      'bi-person-plus-fill', 'Tambah Siswa Baru',    'Registrasi akun siswa PKL',     '#15803d'],
        ['../guru/tambah.php',       'bi-person-badge',     'Tambah Guru',          'Tambah guru pembimbing',        '#059669'],
        ['../instruktur/tambah.php', 'bi-building-add',     'Tambah Instruktur',    'Instruktur dari DU/DI',         '#d97706'],
        ['../tempat/tambah.php',     'bi-geo-alt',          'Tambah Tempat PKL',    'Daftarkan tempat baru',         '#dc2626'],
        ['../guru/import.php',       'bi-upload',           'Import Guru (CSV)',     'Import massal dari file CSV',   '#15803d'],
        ['../jadwal/index.php',      'bi-calendar-range',   'Jadwal Prakerin',       'Atur periode & hari libur',     '#15803d'],
      ];
      foreach($shortcuts as [$url,$icon,$name,$desc,$color]): ?>
      <a href="<?= $url ?>" style="display:flex;align-items:center;gap:12px;padding:.8rem 1.25rem;border-bottom:1px solid #f8fafc;text-decoration:none;transition:.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
        <div style="width:38px;height:38px;border-radius:9px;background:<?= $color ?>18;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="bi <?= $icon ?>" style="font-size:1rem;color:<?= $color ?>;"></i>
        </div>
        <div style="flex:1;">
          <div style="font-weight:600;font-size:.84rem;color:#1e293b;"><?= $name ?></div>
          <div style="font-size:.74rem;color:#94a3b8;"><?= $desc ?></div>
        </div>
        <i class="bi bi-chevron-right" style="color:#cbd5e1;font-size:.75rem;"></i>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ══ GRAFIK ══ -->
<div class="row g-4 mt-1">

  <!-- Grafik Absensi 7 Hari -->
  <div class="col-12 col-lg-8">
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="bi bi-bar-chart-line-fill me-2" style="color:#15803d;"></i>Absensi 7 Hari Terakhir</h5>
      </div>
      <div style="padding:1.25rem;">
        <canvas id="chartAbsensi" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- Donut kehadiran + siswa per tempat -->
  <div class="col-12 col-lg-4">
    <div class="table-card mb-4">
      <div class="table-card-header">
        <h5><i class="bi bi-pie-chart-fill me-2" style="color:#059669;"></i>Kehadiran Overall</h5>
      </div>
      <div style="padding:1rem;display:flex;flex-direction:column;align-items:center;">
        <canvas id="chartDonut" width="160" height="160" style="max-width:160px;"></canvas>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;width:100%;margin-top:1rem;">
          <?php
          $donut_items = [
            ['Hadir',  $abs_hadir, '#10b981'],
            ['Sakit',  $abs_sakit, '#f59e0b'],
            ['Izin',   $abs_izin,  '#16a34a'],
            ['Alpha',  $abs_alpha, '#ef4444'],
          ];
          foreach ($donut_items as [$label, $val, $color]): ?>
          <div style="display:flex;align-items:center;gap:6px;font-size:.78rem;">
            <div style="width:10px;height:10px;border-radius:3px;background:<?= $color ?>;flex-shrink:0;"></div>
            <span style="color:#64748b;"><?= $label ?>:</span>
            <span style="font-weight:700;color:#0f172a;"><?= $val ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="margin-top:.75rem;text-align:center;">
          <div style="font-size:2rem;font-weight:800;color:#10b981;"><?= $pct_hadir ?>%</div>
          <div style="font-size:.75rem;color:#94a3b8;">Tingkat Kehadiran</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Siswa per tempat PKL -->
  <div class="col-12">
    <div class="table-card">
      <div class="table-card-header">
        <h5><i class="bi bi-building me-2" style="color:#15803d;"></i>Siswa per Tempat PKL</h5>
      </div>
      <div style="padding:1.25rem;">
        <canvas id="chartTempat" height="80"></canvas>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.color = '#64748b';

// ── Grafik Absensi Mingguan ──
new Chart(document.getElementById('chartAbsensi'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($grafik_labels) ?>,
    datasets: [
      { label:'Hadir', data: <?= json_encode($grafik_hadir) ?>, backgroundColor:'#10b981', borderRadius:6 },
      { label:'Alpha', data: <?= json_encode($grafik_alpha) ?>, backgroundColor:'#f87171', borderRadius:6 },
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:true,
    plugins:{ legend:{ position:'top' } },
    scales:{
      y:{ beginAtZero:true, ticks:{stepSize:1}, grid:{color:'#f1f5f9'} },
      x:{ grid:{display:false} }
    }
  }
});

// ── Donut Kehadiran ──
new Chart(document.getElementById('chartDonut'), {
  type: 'doughnut',
  data: {
    labels: ['Hadir','Sakit','Izin','Alpha'],
    datasets:[{
      data: [<?= $abs_hadir ?>,<?= $abs_sakit ?>,<?= $abs_izin ?>,<?= $abs_alpha ?>],
      backgroundColor:['#10b981','#f59e0b','#16a34a','#ef4444'],
      borderWidth:0, hoverOffset:6
    }]
  },
  options:{
    responsive:false,
    plugins:{ legend:{display:false}, tooltip:{callbacks:{label:ctx=>`${ctx.label}: ${ctx.parsed}`}} },
    cutout:'72%'
  }
});

// ── Siswa per Tempat PKL ──
new Chart(document.getElementById('chartTempat'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($tempat_labels) ?>,
    datasets:[{
      label:'Jumlah Siswa',
      data: <?= json_encode($tempat_jumlah) ?>,
      backgroundColor: <?= json_encode(array_map(fn($i) => ['#15803d','#16a34a','#22c55e','#4ade80','#059669','#166534','#86efac','#dcfce7'][$i % 8], range(count($tempat_jumlah)))) ?>,
      borderRadius:6
    }]
  },
  options:{
    responsive:true, maintainAspectRatio:true, indexAxis:'y',
    plugins:{ legend:{display:false} },
    scales:{ x:{ beginAtZero:true, ticks:{stepSize:1}, grid:{color:'#f1f5f9'} }, y:{ grid:{display:false} } }
  }
});
</script>

<?php require '../layout/footer.php'; ?>
