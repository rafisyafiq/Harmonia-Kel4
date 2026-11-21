<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/sampah/setor.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT w.*, us.rw_id FROM waste_withdrawals w JOIN users us ON us.id = w.user_id WHERE w.id = ?');
$st->execute([$id]);
$w = $st->fetch();
if (!$w) {
    http_response_code(404);
    exit('Penarikan tidak ditemukan.');
}
if ($u['role'] === 'petugas' && (int)$w['rw_id'] !== (int)$u['rw_id']) {
    http_response_code(403);
    exit('Penarikan ini bukan warga RW Anda.');
}
if ($w['status'] !== 'diajukan') {
    flash('error', 'Penarikan ini sudah dicairkan.');
    redirect(BASE . '/sampah/setor.php');
}

$pdo->prepare("UPDATE waste_withdrawals SET status = 'dicairkan' WHERE id = ? AND status = 'diajukan'")->execute([$id]);
flash('sukses', 'Penarikan ' . rupiah($w['jumlah']) . ' dicairkan.');
redirect(BASE . '/sampah/setor.php');
