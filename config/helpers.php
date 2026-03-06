<?php
/**
 * SiPrakerin — Helper Functions
 * OTP WhatsApp, Notifikasi, Device Trust
 */

// ══════════════════════════════════════
// KONFIGURASI WA API (dari DB)
// ══════════════════════════════════════
function getConfig($conn, $key) {
    // Aman jika tabel config_app belum ada
    $cek = mysqli_query($conn, "SHOW TABLES LIKE 'config_app'");
    if (!$cek || mysqli_num_rows($cek) === 0) return null;

    $s = mysqli_prepare($conn, "SELECT key_value FROM config_app WHERE key_name=? LIMIT 1");
    mysqli_stmt_bind_param($s, 's', $key);
    mysqli_stmt_execute($s);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    return $r['key_value'] ?? null;
}

// ══════════════════════════════════════
// KIRIM WA via Fonnte
// ══════════════════════════════════════
function kirimWA($conn, $no_hp, $pesan) {
    $token  = getConfig($conn, 'wa_api_token');
    $url    = getConfig($conn, 'wa_api_url') ?: 'https://api.fonnte.com/send';

    if (empty($token) || empty($no_hp)) return false;

    // Format nomor: hilangkan 0 di depan, tambah 62
    $no_hp = preg_replace('/[^0-9]/', '', $no_hp);
    if (substr($no_hp, 0, 1) === '0') $no_hp = '62' . substr($no_hp, 1);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'target'  => $no_hp,
            'message' => $pesan,
            'delay'   => 1,
        ]),
        CURLOPT_HTTPHEADER => ["Authorization: $token"],
        CURLOPT_TIMEOUT    => 10,
    ]);
    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) return false;
    $data = json_decode($res, true);
    return !empty($data['status']);
}

// ══════════════════════════════════════
// OTP — Generate & Simpan
// ══════════════════════════════════════
function generateOTP($conn, $user_id) {
    // Hapus OTP lama yang belum dipakai
    mysqli_query($conn, "DELETE FROM otp_login WHERE user_id=$user_id AND digunakan=0");

    $kode       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expired_at = date('Y-m-d H:i:s', time() + 300); // 5 menit

    $s = mysqli_prepare($conn,
        "INSERT INTO otp_login (user_id, kode, expired_at) VALUES (?,?,?)"
    );
    mysqli_stmt_bind_param($s, 'iss', $user_id, $kode, $expired_at);
    mysqli_stmt_execute($s);

    return $kode;
}

// ══════════════════════════════════════
// OTP — Verifikasi
// ══════════════════════════════════════
function verifikasiOTP($conn, $user_id, $kode_input) {
    $now = date('Y-m-d H:i:s');
    $s   = mysqli_prepare($conn,
        "SELECT id FROM otp_login
         WHERE user_id=? AND kode=? AND digunakan=0 AND expired_at > ?
         ORDER BY id DESC LIMIT 1"
    );
    mysqli_stmt_bind_param($s, 'iss', $user_id, $kode_input, $now);
    mysqli_stmt_execute($s);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

    if ($r) {
        // Tandai OTP sudah dipakai
        mysqli_query($conn, "UPDATE otp_login SET digunakan=1 WHERE id={$r['id']}");
        return true;
    }
    return false;
}

// ══════════════════════════════════════
// DEVICE TRUST — Cek apakah browser ini sudah dipercaya
// ══════════════════════════════════════
function isDeviceTrusted($conn, $user_id) {
    $token = $_COOKIE['siprakerin_device'] ?? '';
    if (empty($token)) return false;

    $token = preg_replace('/[^a-f0-9]/', '', $token); // sanitize
    if (strlen($token) !== 64) return false;

    $s = mysqli_prepare($conn,
        "SELECT id FROM trusted_device WHERE user_id=? AND device_token=? LIMIT 1"
    );
    mysqli_stmt_bind_param($s, 'is', $user_id, $token);
    mysqli_stmt_execute($s);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

    if ($r) {
        // Update last_used
        mysqli_query($conn, "UPDATE trusted_device SET last_used=NOW() WHERE id={$r['id']}");
        return true;
    }
    return false;
}

// ══════════════════════════════════════
// DEVICE TRUST — Simpan device baru
// ══════════════════════════════════════
function trustDevice($conn, $user_id) {
    $token = bin2hex(random_bytes(32)); // 64 karakter hex
    $ua    = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '';

    $s = mysqli_prepare($conn,
        "INSERT INTO trusted_device (user_id, device_token, user_agent, ip_address)
         VALUES (?,?,?,?)
         ON DUPLICATE KEY UPDATE last_used=NOW()"
    );
    mysqli_stmt_bind_param($s, 'isss', $user_id, $token, $ua, $ip);
    mysqli_stmt_execute($s);

    // Simpan ke cookie 30 hari
    setcookie(
        'siprakerin_device', $token,
        time() + (30 * 24 * 3600),
        '/', '', false, true // HttpOnly
    );
}

// ══════════════════════════════════════
// NOTIFIKASI — Buat notifikasi baru
// ══════════════════════════════════════
function buatNotifikasi($conn, $user_id, $judul, $pesan, $tipe = 'sistem', $link = null) {
    $s = mysqli_prepare($conn,
        "INSERT INTO notifikasi (user_id, judul, pesan, tipe, link) VALUES (?,?,?,?,?)"
    );
    mysqli_stmt_bind_param($s, 'issss', $user_id, $judul, $pesan, $tipe, $link);
    return mysqli_stmt_execute($s);
}

// ══════════════════════════════════════
// NOTIFIKASI — Ambil notif belum dibaca
// ══════════════════════════════════════
function getNotifikasi($conn, $user_id, $limit = 10) {
    $s = mysqli_prepare($conn,
        "SELECT * FROM notifikasi WHERE user_id=? ORDER BY dibaca ASC, created_at DESC LIMIT ?"
    );
    mysqli_stmt_bind_param($s, 'ii', $user_id, $limit);
    mysqli_stmt_execute($s);
    return mysqli_stmt_get_result($s);
}

function countNotifBelumDibaca($conn, $user_id) {
    $s = mysqli_prepare($conn,
        "SELECT COUNT(*) n FROM notifikasi WHERE user_id=? AND dibaca=0"
    );
    mysqli_stmt_bind_param($s, 'i', $user_id);
    mysqli_stmt_execute($s);
    return (int)(mysqli_fetch_assoc(mysqli_stmt_get_result($s))['n'] ?? 0);
}

// ══════════════════════════════════════
// NOTIFIKASI — Tandai dibaca
// ══════════════════════════════════════
function tandaiDibaca($conn, $user_id, $notif_id = null) {
    if ($notif_id) {
        $s = mysqli_prepare($conn,
            "UPDATE notifikasi SET dibaca=1 WHERE id=? AND user_id=?"
        );
        mysqli_stmt_bind_param($s, 'ii', $notif_id, $user_id);
    } else {
        $s = mysqli_prepare($conn,
            "UPDATE notifikasi SET dibaca=1 WHERE user_id=?"
        );
        mysqli_stmt_bind_param($s, 'i', $user_id);
    }
    mysqli_stmt_execute($s);
}

// ══════════════════════════════════════
// NOTIFIKASI OTOMATIS — Jurnal baru masuk
// Dipanggil saat siswa submit jurnal
// ══════════════════════════════════════
function notifJurnalBaru($conn, $siswa_id, $jurnal_id, $tanggal) {
    // Ambil data siswa, guru, instruktur
    $s = mysqli_prepare($conn,
        "SELECT u.nama, s.guru_id, s.instruktur_id,
                gu.user_id AS guru_user_id,
                iu.user_id AS instr_user_id
         FROM siswa s
         JOIN users u ON u.id = s.user_id
         LEFT JOIN guru g ON g.id = s.guru_id
         LEFT JOIN users gu ON gu.id = g.user_id
         LEFT JOIN instruktur i ON i.id = s.instruktur_id
         LEFT JOIN users iu ON iu.id = i.user_id
         WHERE s.id = ? LIMIT 1"
    );
    mysqli_stmt_bind_param($s, 'i', $siswa_id);
    mysqli_stmt_execute($s);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($s));
    if (!$data) return;

    $nama_siswa = $data['nama'];
    $tgl_fmt    = date('d M Y', strtotime($tanggal));

    // Notif ke Guru
    if ($data['guru_user_id']) {
        buatNotifikasi(
            $conn, $data['guru_user_id'],
            'Jurnal Baru Masuk',
            "$nama_siswa mengumpulkan jurnal tanggal $tgl_fmt",
            'jurnal',
            '../jurnal/index.php'
        );
    }

    // Notif ke Instruktur
    if ($data['instr_user_id']) {
        buatNotifikasi(
            $conn, $data['instr_user_id'],
            'Jurnal Baru Masuk',
            "$nama_siswa mengumpulkan jurnal tanggal $tgl_fmt",
            'jurnal',
            '../jurnal/index.php'
        );
    }
}

// ══════════════════════════════════════
// NOTIFIKASI OTOMATIS — DU/DI konfirmasi
// ══════════════════════════════════════
function notifKonfirmasiDUDI($conn, $tempat_id, $nama_tempat) {
    // Notif ke semua admin
    $admins = mysqli_query($conn,
        "SELECT id FROM users WHERE role='admin'"
    );
    while ($a = mysqli_fetch_assoc($admins)) {
        buatNotifikasi(
            $conn, $a['id'],
            'DU/DI Konfirmasi Prakerin',
            "$nama_tempat telah mengkonfirmasi dimulainya prakerin",
            'konfirmasi',
            '../jadwal/index.php'
        );
    }
}

// ══════════════════════════════════════
// JAM ABSEN — Ambil jam untuk siswa tertentu
// (cek override DU/DI, fallback ke default admin)
// ══════════════════════════════════════
function getJamAbsen($conn, $tempat_pkl_id) {
    $default = [
        'jam_masuk'        => getConfig($conn, 'jam_masuk_default')  ?? '07:00',
        'jam_pulang'       => getConfig($conn, 'jam_pulang_default') ?? '16:00',
        'batas_masuk_menit'=> (int)(getConfig($conn, 'batas_masuk_menit') ?? 30),
        'source'           => 'default',
    ];

    if (!$tempat_pkl_id) return $default;

    $cek = mysqli_query($conn, "SHOW TABLES LIKE 'jam_absen_tempat'");
    if (!$cek || mysqli_num_rows($cek) === 0) return $default;

    $tid = (int)$tempat_pkl_id;
    $r   = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT * FROM jam_absen_tempat WHERE tempat_pkl_id=$tid LIMIT 1"
    ));
    if ($r) {
        return [
            'jam_masuk'         => substr($r['jam_masuk'], 0, 5),
            'jam_pulang'        => substr($r['jam_pulang'], 0, 5),
            'batas_masuk_menit' => (int)$r['batas_masuk_menit'],
            'source'            => 'override',
        ];
    }
    return $default;
}

// ══════════════════════════════════════
// PENGINGAT ABSEN — Cek & kirim ke siswa
// yang belum absen hari ini
// Dipanggil saat siswa login atau dari cron
// ══════════════════════════════════════
function cekDanKirimPengingat($conn, $siswa_id, $user_id) {
    $aktif = getConfig($conn, 'pengingat_absen_aktif');
    if ($aktif !== '1') return;

    $today = date('Y-m-d');

    // Cek tabel reminder_log
    $cek_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'reminder_log'");
    if (!$cek_tbl || mysqli_num_rows($cek_tbl) === 0) return;

    // Sudah dikirim hari ini?
    $rl = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM reminder_log WHERE siswa_id=$siswa_id AND tanggal='$today' LIMIT 1"
    ));
    if ($rl) return;

    // Sudah absen hari ini?
    $ab = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT id FROM absensi WHERE siswa_id=$siswa_id AND tanggal='$today' LIMIT 1"
    ));
    if ($ab) return;

    // Ambil no HP siswa
    $s = mysqli_fetch_assoc(mysqli_query($conn,
        "SELECT s.no_hp, u.nama FROM siswa s JOIN users u ON u.id=s.user_id WHERE s.id=$siswa_id LIMIT 1"
    ));
    if (!$s) return;

    $nama  = explode(' ', $s['nama'])[0];
    $hari  = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu',
              'Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'][date('l')] ?? '';
    $tgl   = date('d F Y');

    // Kirim notif in-app
    buatNotifikasi($conn, $user_id,
        '⏰ Jangan Lupa Absen!',
        "Hai $nama! Hari ini $hari, $tgl. Kamu belum absen. Segera catat kehadiranmu sekarang.",
        'absensi',
        '../absensi/index.php'
    );

    // Kirim WA (jika ada nomor HP)
    if (!empty($s['no_hp'])) {
        $pesan = "⏰ *Pengingat Absen PKL*\n\nHai *$nama*! Jangan lupa absen hari ini ya.\n📅 $hari, $tgl\n\nSilakan buka SiPrakerin dan catat kehadiranmu. 😊";
        kirimWA($conn, $s['no_hp'], $pesan);
    }

    // Catat di log agar tidak kirim ulang
    mysqli_query($conn, "INSERT IGNORE INTO reminder_log (siswa_id, tanggal) VALUES ($siswa_id, '$today')");
}
