<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/dashboard/admin.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
$alasan = trim((string)($_POST['alasan_tolak'] ?? ''));

if (!in_array($status, ['tayang', 'ditolak'], true)) {
    flash('error', 'Status verifikasi tidak valid.');
    redirect(BASE . '/dashboard/admin.php');
}
if ($status === 'ditolak' && $alasan === '') {
    flash('error', 'Alasan penolakan wajib diisi.');
    redirect(BASE . '/dashboard/admin.php');
}
if (mb_strlen($alasan) > 200) {
    flash('error', 'Alasan maksimal 200 karakter.');
    redirect(BASE . '/dashboard/admin.php');
}

$st = $pdo->prepare('SELECT nama_usaha FROM businesses WHERE id = ?');
$st->execute([$id]);
$nama = $st->fetchColumn();
if ($nama === false) {
    http_response_code(404);
    exit('Lapak tidak ditemukan.');
}

$pdo->prepare('UPDATE businesses SET status = ?, alasan_tolak = ? WHERE id = ?')
    ->execute([$status, $status === 'ditolak' ? $alasan : null, $id]);
flash('sukses', 'Lapak "' . $nama . '" ' . ($status === 'tayang' ? 'ditayangkan.' : 'ditolak.'));
redirect(BASE . '/dashboard/admin.php');
