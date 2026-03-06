<?php
session_start();
require '../../config/database.php';
require '../middleware/auth_instruktur.php';

$page_title = 'Aktivasi Prakerin';

// ── Data instruktur & tempat PKL ──
$user_id = (int)$_SESSION['user_id'];
$instr = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT i.*, tp.nama_tempat, tp.id AS tempat_id
     FROM instruktur i
     LEFT JOIN tempat_pkl tp ON tp.id = i.tempat_pkl_id
     WHERE i.user_id = $user_id LIMIT 1"
));
$tempat_id = (int)($instr['tempat_id'] ?? 0);

// ── Jadwal aktif terbaru ──
$jadwal = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT * FROM jadwal_prakerin ORDER BY tanggal_mulai DESC LIMIT 1"
));

// ── Cek sudah konfirmasi? ──
$sudah_konfirmasi = false;
$konfirmasi = null;
if ($jadwal && $tempat_id) {
    $jid = (int)$jadwal['id'];
    $konfirmasi = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT kp.*, u.nama AS nama_konfirmasi
         FROM konfirmasi_prakerin kp
         JOIN instruktur i ON i.id = kp.instruktur_id
         JOIN users u ON u.id = i.user_id
         WHERE kp.tempat_pkl_id = $tempat_id AND kp.jadwal_id = $jid LIMIT 1"
    ));
    $sudah_konfirmasi = !empty($konfirmasi);
}

// ── Hari libur khusus tempat ini ──
$libur_saya = [];
if ($tempat_id) {
    $res = mysqli_query($conn,
        "SELECT * FROM hari_libur WHERE tempat_pkl_id = $tempat_id ORDER BY tanggal ASC"
    );
    while ($r = mysqli_fetch_assoc($res)) $libur_saya[] = $r;
}

// ── Hari libur nasional ──
$libur_nasional = [];
$res = mysqli_query($conn, "SELECT * FROM hari_libur WHERE tempat_pkl_id IS NULL ORDER BY tanggal ASC");
while ($r = mysqli_fetch_assoc($res)) $libur_nasional[] = $r;

// ═══════════════ POST HANDLERS ═══════════════
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // --- Konfirmasi Aktivasi ---
    if ($aksi === 'konfirmasi' && $jadwal && $tempat_id && !$sudah_konfirmasi) {
        $today = date('Y-m-d');
        if ($today < $jadwal['tanggal_mulai']) {
            $error = 'Prakerin belum dimulai. Konfirmasi baru bisa dilakukan pada atau setelah ' . date('d M Y', strtotime($jadwal['tanggal_mulai'])) . '.';
        } else {
            $instr_id = (int)$instr['id'];
            $jid = (int)$jadwal['id'];
            $catatan = trim($_POST['catatan'] ?? '');
            $stmt = mysqli_prepare($conn,
                "INSERT INTO konfirmasi_prakerin (tempat_pkl_id, jadwal_id, instruktur_id, catatan) VALUES (?,?,?,?)"
            );
            mysqli_stmt_bind_param($stmt, 'iiis', $tempat_id, $jid, $instr_id, $catatan);
            if (mysqli_stmt_execute($stmt)) {
                // Kirim notifikasi ke semua admin
                require_once '../../config/helpers.php';
                notifKonfirmasiDUDI($conn, $tempat_id, $instr['nama_tempat'] ?? 'Tempat PKL');
                header("Location: index.php?ok=1");
                exit;
            } else {
                $error = 'Gagal menyimpan konfirmasi. Coba lagi.';
            }
        }
    }

    // --- Tambah Hari Libur Khusus ---
    if ($aksi === 'tambah_libur' && $tempat_id) {
        $tgl  = $_POST['tanggal'] ?? '';
        $nama = trim($_POST['nama_libur'] ?? '');
        if ($tgl && $nama) {
            $stmt = mysqli_prepare($conn,
                "INSERT INTO hari_libur (tanggal, nama_libur, tempat_pkl_id, dibuat_oleh) VALUES (?,?,?,?)"
            );
            mysqli_stmt_bind_param($stmt, 'ssii', $tgl, $nama, $tempat_id, $user_id);
            mysqli_stmt_execute($stmt);
            header("Location: index.php?ok=libur");
            exit;
        }
    }

    // --- Hapus Hari Libur Khusus (hanya milik sendiri) ---
    if ($aksi === 'hapus_libur' && $tempat_id) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            mysqli_query($conn,
                "DELETE FROM hari_libur WHERE id=$id AND tempat_pkl_id=$tempat_id"
            );
        }
        header("Location: index.php?ok=hapus");
        exit;
    }
}

if ($_GET['ok'] ?? '' === '1')     $success = 'Prakerin berhasil diaktifkan! Siswa sekarang bisa melakukan absensi.';
if ($_GET['ok'] ?? '' === 'libur') $success = 'Hari libur berhasil ditambahkan.';
if ($_GET['ok'] ?? '' === 'hapus') $success = 'Hari libur berhasil dihapus.';

// ── Hitung status ──
$today = date('Y-m-d');
$status_jadwal = null;
if ($jadwal) {
    if ($today < $jadwal['tanggal_mulai']) $status_jadwal = 'belum';
    elseif ($today > $jadwal['tanggal_selesai']) $status_jadwal = 'selesai';
    else $status_jadwal = 'berlangsung';
}

require '../layout/header.php';
?>

<div style="max-width:820px;">

<div class="page-header">
  <h1 style="font-size:1.4rem;font-weight:800;">🏭 Aktivasi Prakerin</h1>
  <p style="color:#64748b;margin-top:3px;">Konfirmasi dimulainya PKL di tempat Anda</p>
</div>

<?php if ($success): ?>
<div style="background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;">
  <i class="bi bi-check-circle-fill"></i> <?= $success ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:10px;padding:.85rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;">
  <i class="bi bi-exclamation-triangle-fill"></i> <?= $error ?>
</div>
<?php endif; ?>

<!-- ══ CARD JADWAL ══ -->
<?php if (!$jadwal): ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:2.5rem;text-align:center;color:#94a3b8;margin-bottom:1.5rem;">
  <i class="bi bi-calendar-x" style="font-size:2.5rem;opacity:.4;display:block;margin-bottom:.75rem;"></i>
  <div style="font-weight:600;margin-bottom:.25rem;">Jadwal Belum Ditetapkan</div>
  <div style="font-size:.85rem;">Admin sekolah belum menetapkan jadwal prakerin. Harap tunggu.</div>
</div>
<?php else: ?>

<!-- Info Jadwal -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.4rem;margin-bottom:1.5rem;">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
    <div>
      <div style="font-size:.72rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.07em;margin-bottom:.4rem;">Jadwal Prakerin Aktif</div>
      <div style="font-weight:800;font-size:1.1rem;color:#0f172a;"><?= htmlspecialchars($jadwal['tahun_ajaran']) ?></div>
      <div style="font-size:.88rem;color:#64748b;margin-top:3px;">
        <?= date('d M Y', strtotime($jadwal['tanggal_mulai'])) ?>
        &nbsp;→&nbsp;
        <?= date('d M Y', strtotime($jadwal['tanggal_selesai'])) ?>
      </div>
      <?php if ($jadwal['keterangan']): ?>
      <div style="font-size:.8rem;color:#94a3b8;margin-top:4px;"><?= htmlspecialchars($jadwal['keterangan']) ?></div>
      <?php endif; ?>
    </div>
    <div>
      <?php
        $badge_map = [
          'belum'       => ['bg'=>'#fefce8','color'=>'#a16207','border'=>'#fde68a','icon'=>'bi-hourglass','label'=>'Belum Dimulai'],
          'berlangsung' => ['bg'=>'#f0fdf4','color'=>'#15803d','border'=>'#bbf7d0','icon'=>'bi-play-circle-fill','label'=>'Sedang Berlangsung'],
          'selesai'     => ['bg'=>'#f1f5f9','color'=>'#64748b','border'=>'#e2e8f0','icon'=>'bi-check2-all','label'=>'Selesai'],
        ];
        $b = $badge_map[$status_jadwal];
      ?>
      <span style="background:<?= $b['bg'] ?>;color:<?= $b['color'] ?>;border:1px solid <?= $b['border'] ?>;
                   padding:.4rem 1rem;border-radius:20px;font-size:.8rem;font-weight:700;display:inline-flex;align-items:center;gap:6px;">
        <i class="bi <?= $b['icon'] ?>"></i><?= $b['label'] ?>
      </span>
    </div>
  </div>
</div>

<!-- ══ PANEL KONFIRMASI ══ -->
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:1.5rem;">
  <div style="padding:1rem 1.4rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:8px;">
    <i class="bi bi-building-check" style="color:#15803d;font-size:1.1rem;"></i>
    <span style="font-weight:700;color:#0f172a;">Konfirmasi Tempat PKL Saya</span>
    <?php if ($instr['nama_tempat']): ?>
    <span style="margin-left:auto;font-size:.78rem;color:#64748b;background:#f0fdf4;padding:.2rem .7rem;border-radius:20px;">
      <?= htmlspecialchars($instr['nama_tempat']) ?>
    </span>
    <?php endif; ?>
  </div>
  <div style="padding:1.4rem;">
    <?php if (!$tempat_id): ?>
      <div style="text-align:center;color:#94a3b8;padding:1rem;">
        <i class="bi bi-building" style="font-size:2rem;opacity:.4;display:block;margin-bottom:.5rem;"></i>
        Akun Anda belum terhubung ke tempat PKL. Hubungi admin.
      </div>

    <?php elseif ($sudah_konfirmasi): ?>
      <!-- Sudah dikonfirmasi -->
      <div style="display:flex;align-items:flex-start;gap:14px;">
        <div style="width:52px;height:52px;background:#f0fdf4;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
          <i class="bi bi-check-circle-fill" style="color:#16a34a;font-size:1.6rem;"></i>
        </div>
        <div>
          <div style="font-weight:700;font-size:1rem;color:#15803d;">Prakerin Sudah Diaktifkan!</div>
          <div style="font-size:.85rem;color:#64748b;margin-top:3px;">
            Dikonfirmasi oleh <strong><?= htmlspecialchars($konfirmasi['nama_konfirmasi']) ?></strong>
            pada <?= date('d M Y, H:i', strtotime($konfirmasi['dikonfirmasi_at'])) ?>
          </div>
          <?php if ($konfirmasi['catatan']): ?>
          <div style="margin-top:.6rem;padding:.6rem .9rem;background:#f8fafc;border-radius:8px;font-size:.82rem;color:#374151;border-left:3px solid #15803d;">
            <?= htmlspecialchars($konfirmasi['catatan']) ?>
          </div>
          <?php endif; ?>
          <div style="margin-top:.85rem;padding:.75rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;font-size:.83rem;color:#15803d;">
            <i class="bi bi-info-circle me-1"></i>
            Siswa di tempat PKL ini sudah bisa melakukan absensi harian.
          </div>
        </div>
      </div>

    <?php elseif ($status_jadwal === 'belum'): ?>
      <!-- Belum waktunya -->
      <div style="text-align:center;padding:1rem 0;color:#a16207;">
        <i class="bi bi-hourglass-split" style="font-size:2.2rem;display:block;margin-bottom:.6rem;"></i>
        <div style="font-weight:700;margin-bottom:.3rem;">Prakerin Belum Dimulai</div>
        <div style="font-size:.85rem;color:#64748b;">
          Tombol konfirmasi akan muncul mulai tanggal
          <strong><?= date('d M Y', strtotime($jadwal['tanggal_mulai'])) ?></strong>
        </div>
      </div>

    <?php elseif ($status_jadwal === 'selesai'): ?>
      <div style="text-align:center;padding:1rem 0;color:#64748b;">
        <i class="bi bi-calendar-check" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
        <div style="font-weight:600;">Periode prakerin sudah selesai.</div>
      </div>

    <?php else: ?>
      <!-- Bisa konfirmasi -->
      <div style="margin-bottom:1.1rem;padding:.85rem 1rem;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:9px;font-size:.85rem;color:#166534;">
        <i class="bi bi-info-circle me-1"></i>
        Hari ini adalah hari aktif prakerin. Konfirmasi bahwa kegiatan PKL di tempat Anda sudah dimulai agar siswa bisa absensi.
      </div>
      <form method="POST">
        <input type="hidden" name="aksi" value="konfirmasi">
        <div class="mb-3">
          <label style="font-weight:600;font-size:.85rem;color:#374151;display:block;margin-bottom:.4rem;">
            Catatan <small style="font-weight:400;color:#94a3b8;">(opsional)</small>
          </label>
          <textarea name="catatan" class="form-control" rows="2"
                    placeholder="cth: Prakerin dimulai pukul 08.00 WIB"></textarea>
        </div>
        <button type="submit"
                onclick="return confirm('Konfirmasi bahwa prakerin di tempat Anda sudah dimulai hari ini?')"
                style="background:linear-gradient(135deg,#15803d,#15803d);color:#fff;border:none;
                       padding:.75rem 1.75rem;border-radius:10px;font-weight:700;font-size:.95rem;
                       cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:.2s;"
                onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
          <i class="bi bi-check2-circle" style="font-size:1.1rem;"></i>
          Konfirmasi — Prakerin Sudah Dimulai
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<!-- ══ HARI LIBUR KHUSUS ══ -->
<?php if ($tempat_id): ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:1.5rem;">
  <div style="padding:1rem 1.4rem;border-bottom:1px solid #f1f5f9;">
    <span style="font-weight:700;color:#0f172a;"><i class="bi bi-calendar-x me-2" style="color:#15803d;"></i>Hari Libur Khusus Tempat Saya</span>
    <div style="font-size:.78rem;color:#94a3b8;margin-top:2px;">Siswa tidak bisa absensi pada hari libur ini</div>
  </div>
  <div style="padding:1.25rem;">
    <!-- Tambah libur -->
    <form method="POST" style="margin-bottom:1rem;">
      <input type="hidden" name="aksi" value="tambah_libur">
      <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end;">
        <div>
          <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Tanggal</label>
          <input type="date" name="tanggal" class="form-control" style="font-size:.85rem;" required>
        </div>
        <div style="flex:1;min-width:160px;">
          <label style="font-size:.8rem;font-weight:600;color:#374151;display:block;margin-bottom:.3rem;">Nama Libur</label>
          <input type="text" name="nama_libur" class="form-control" style="font-size:.85rem;"
                 placeholder="cth: Libur internal perusahaan" required>
        </div>
        <button type="submit" class="btn-primary-custom" style="padding:.55rem 1rem;">
          <i class="bi bi-plus-lg"></i> Tambah
        </button>
      </div>
    </form>

    <?php if ($libur_saya): ?>
    <div style="border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
      <table class="tbl" style="min-width:unset;">
        <thead><tr><th>Tanggal</th><th>Keterangan</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($libur_saya as $lib): ?>
        <tr>
          <td style="white-space:nowrap;"><?= date('d M Y', strtotime($lib['tanggal'])) ?></td>
          <td><?= htmlspecialchars($lib['nama_libur']) ?></td>
          <td>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="aksi" value="hapus_libur">
              <input type="hidden" name="id" value="<?= $lib['id'] ?>">
              <button type="submit" class="btn-danger-sm"
                      onclick="return confirm('Hapus hari libur ini?')">
                <i class="bi bi-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:1rem;color:#94a3b8;font-size:.85rem;">
      Belum ada hari libur khusus ditambahkan
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ══ LIBUR NASIONAL (read-only) ══ -->
<?php if ($libur_nasional): ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
  <div style="padding:1rem 1.4rem;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;">
    <span style="font-weight:700;color:#0f172a;"><i class="bi bi-calendar2-event me-2" style="color:#15803d;"></i>Libur Nasional</span>
    <span style="font-size:.75rem;color:#94a3b8;background:#f0fdf4;padding:.2rem .7rem;border-radius:20px;">Ditetapkan Admin</span>
  </div>
  <div style="padding:1rem 1.25rem;">
    <div style="display:flex;flex-wrap:wrap;gap:8px;">
      <?php foreach ($libur_nasional as $lib): ?>
      <span style="background:#f5f3ff;color:#15803d;border:1px solid #ddd6fe;padding:.3rem .85rem;border-radius:20px;font-size:.78rem;font-weight:500;">
        <?= date('d M Y', strtotime($lib['tanggal'])) ?> — <?= htmlspecialchars($lib['nama_libur']) ?>
      </span>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

</div><!-- end max-width -->

<?php require '../layout/footer.php'; ?>
