<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/bantuan/index.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
$catatan = trim((string)($_POST['catatan'] ?? ''));

$st = $pdo->prepare('SELECT a.*, us.rw_id AS warga_rw FROM aid_requests a JOIN users us ON us.id = a.user_id WHERE a.id = ?');
$st->execute([$id]);
$a = $st->fetch();
if (!$a) {
    http_response_code(404);
    exit('Pengajuan tidak ditemukan.');
}
if ($u['role'] === 'petugas' && (int)$a['warga_rw'] !== (int)$u['rw_id']) {
    http_response_code(403);
    exit('Pengajuan ini bukan warga RW Anda.');
}

$transisi = ['diajukan' => ['diverifikasi'], 'diverifikasi' => ['disetujui', 'ditolak']];
if (!in_array($status, $transisi[$a['status']] ?? [], true)) {
    flash('error', 'Status tidak bisa diubah ke ' . $status . ' dari ' . $a['status'] . '.');
    redirect(BASE . '/bantuan/detail.php?id=' . $id);
}
if ($status === 'ditolak' && $catatan === '') {
    flash('error', 'Penolakan wajib disertai catatan.');
    redirect(BASE . '/bantuan/detail.php?id=' . $id);
}
if (mb_strlen($catatan) > 500) {
    flash('error', 'Catatan maksimal 500 karakter.');
    redirect(BASE . '/bantuan/detail.php?id=' . $id);
}

$st = $pdo->prepare('UPDATE aid_requests SET status = ?, catatan_petugas = ?, processed_by = ? WHERE id = ?');
$st->execute([$status, $catatan ?: null, $u['id'], $id]);
log_status($pdo, 'aid', $id, $status, $catatan ?: null, (int)$u['id']);

flash('sukses', 'Pengajuan ' . kode_pengajuan($id) . ' diperbarui menjadi ' . strtolower(STATUS_LABEL[$status] ?? $status) . '.');
redirect(BASE . '/bantuan/detail.php?id=' . $id);
