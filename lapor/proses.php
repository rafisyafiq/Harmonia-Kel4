<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/lapor/index.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$status = (string)($_POST['status'] ?? '');
$catatan = trim((string)($_POST['catatan'] ?? ''));

$st = $pdo->prepare('SELECT * FROM reports WHERE id = ?');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) {
    http_response_code(404);
    exit('Laporan tidak ditemukan.');
}
if ($u['role'] === 'petugas' && (int)$r['rw_id'] !== (int)$u['rw_id']) {
    http_response_code(403);
    exit('Laporan ini bukan warga RW Anda.');
}

$transisi = ['baru' => ['diproses'], 'diproses' => ['selesai']];
if (!in_array($status, $transisi[$r['status']] ?? [], true)) {
    flash('error', 'Status laporan tidak bisa mundur atau melompat.');
    redirect(BASE . '/lapor/detail.php?id=' . $id);
}
if (mb_strlen($catatan) > 500) {
    flash('error', 'Catatan maksimal 500 karakter.');
    redirect(BASE . '/lapor/detail.php?id=' . $id);
}

$fotoSelesai = $r['foto_selesai'];
if ($status === 'selesai' && !empty($_FILES['foto_selesai']['name'])) {
    $err = null;
    $upload = upload_foto($_FILES['foto_selesai'], $err);
    if ($err) {
        flash('error', $err);
        redirect(BASE . '/lapor/detail.php?id=' . $id);
    }
    $fotoSelesai = $upload;
}

$st = $pdo->prepare('UPDATE reports SET status = ?, foto_selesai = ?, handled_by = ? WHERE id = ?');
$st->execute([$status, $fotoSelesai, $u['id'], $id]);
log_status($pdo, 'report', $id, $status, $catatan ?: null, (int)$u['id']);

flash('sukses', 'Laporan ' . kode_laporan($id) . ' diperbarui menjadi ' . strtolower(STATUS_LABEL[$status]) . '.');
redirect(BASE . '/lapor/detail.php?id=' . $id);
