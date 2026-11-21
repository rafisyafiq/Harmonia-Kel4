<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/usulan/index.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
$catatan = trim((string)($_POST['catatan_petugas'] ?? ''));

$st = $pdo->prepare('SELECT * FROM proposals WHERE id = ?');
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    http_response_code(404);
    exit('Usulan tidak ditemukan.');
}
if ($u['role'] === 'petugas' && (int)$p['rw_id'] !== (int)$u['rw_id']) {
    http_response_code(403);
    exit('Usulan ini bukan warga RW Anda.');
}

$boleh = ($status === 'diteruskan' && $p['status'] === 'tercapai')
    || ($status === 'ditolak' && in_array($p['status'], ['dibuka', 'tercapai'], true));
if (!$boleh) {
    flash('error', 'Perubahan status usulan tidak valid.');
    redirect(BASE . '/usulan/detail.php?id=' . $id);
}
if ($status === 'ditolak' && $catatan === '') {
    flash('error', 'Penolakan wajib disertai catatan.');
    redirect(BASE . '/usulan/detail.php?id=' . $id);
}
if (mb_strlen($catatan) > 200) {
    flash('error', 'Catatan maksimal 200 karakter.');
    redirect(BASE . '/usulan/detail.php?id=' . $id);
}

$st = $pdo->prepare('UPDATE proposals SET status = ?, catatan_petugas = ? WHERE id = ?');
$st->execute([$status, $catatan ?: null, $id]);

flash('sukses', 'Usulan diperbarui menjadi ' . strtolower(STATUS_LABEL[$status]) . '.');
redirect(BASE . '/usulan/detail.php?id=' . $id);
