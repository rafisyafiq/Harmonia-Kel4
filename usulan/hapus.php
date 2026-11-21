<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/usulan/index.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM proposals WHERE id = ? AND user_id = ?');
$st->execute([$id, $u['id']]);
$p = $st->fetch();
if (!$p) {
    http_response_code(404);
    exit('Usulan tidak ditemukan.');
}

$st = $pdo->prepare('SELECT COUNT(*) FROM proposal_votes WHERE proposal_id = ?');
$st->execute([$id]);
if ((int)$st->fetchColumn() > 0) {
    flash('error', 'Usulan yang sudah didukung tidak dapat dihapus.');
    redirect(BASE . '/usulan/detail.php?id=' . $id);
}

$pdo->prepare('DELETE FROM proposals WHERE id = ?')->execute([$id]);
flash('sukses', 'Usulan dihapus.');
redirect(BASE . '/usulan/index.php');
