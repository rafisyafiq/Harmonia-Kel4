<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/pengumuman/kelola.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT rw_id FROM announcements WHERE id = ?');
$st->execute([$id]);
$rw = $st->fetchColumn();
if ($rw === false) {
    http_response_code(404);
    exit('Pengumuman tidak ditemukan.');
}
if ($u['role'] === 'petugas' && (int)$rw !== (int)$u['rw_id']) {
    http_response_code(403);
    exit('Pengumuman ini bukan milik RW Anda.');
}

$pdo->prepare('DELETE FROM announcements WHERE id = ?')->execute([$id]);
flash('sukses', 'Pengumuman dihapus.');
redirect(BASE . '/pengumuman/kelola.php');
