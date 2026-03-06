<?php
/**
 * API Notifikasi — dipanggil via fetch() dari topbar bell
 * GET  ?action=list   → ambil notifikasi
 * POST ?action=read   → tandai dibaca (id atau semua)
 */
session_start();
require '../config/database.php';
require '../config/helpers.php';

header('Content-Type: application/json');

if (empty($_SESSION['login'])) {
    echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); exit;
}

$uid    = (int)$_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

if ($action === 'list') {
    $notifs = [];
    $res    = getNotifikasi($conn, $uid, 15);
    while ($r = mysqli_fetch_assoc($res)) {
        $notifs[] = [
            'id'         => $r['id'],
            'judul'      => $r['judul'],
            'pesan'      => $r['pesan'],
            'tipe'       => $r['tipe'],
            'link'       => $r['link'],
            'dibaca'     => (bool)$r['dibaca'],
            'waktu'      => waktuRelatif($r['created_at']),
        ];
    }
    $unread = countNotifBelumDibaca($conn, $uid);
    echo json_encode(['ok'=>true,'notifs'=>$notifs,'unread'=>$unread]);

} elseif ($action === 'read') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
    tandaiDibaca($conn, $uid, $id);
    echo json_encode(['ok'=>true]);

} else {
    echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
}

function waktuRelatif($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Baru saja';
    if ($diff < 3600)   return (int)($diff/60) . ' menit lalu';
    if ($diff < 86400)  return (int)($diff/3600) . ' jam lalu';
    if ($diff < 604800) return (int)($diff/86400) . ' hari lalu';
    return date('d M Y', strtotime($datetime));
}
