<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/dashboard/admin.php');
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$catatan = trim((string)($_POST['catatan_batal'] ?? ''));

$st = $pdo->prepare('SELECT * FROM waste_deposits WHERE id = ?');
$st->execute([$id]);
$d = $st->fetch();
if (!$d) {
    http_response_code(404);
    exit('Setoran tidak ditemukan.');
}
if ($catatan === '') {
    flash('error', 'Alasan pembatalan wajib diisi.');
    redirect(BASE . '/dashboard/admin.php');
}
if (mb_strlen($catatan) > 200) {
    flash('error', 'Alasan maksimal 200 karakter.');
    redirect(BASE . '/dashboard/admin.php');
}
if ($d['dibatalkan']) {
    flash('error', 'Setoran ini sudah dibatalkan.');
    redirect(BASE . '/dashboard/admin.php');
}

try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
    $st->execute([$d['user_id']]);
    if (saldo_warga($pdo, (int)$d['user_id']) - (int)$d['nilai'] < 0) {
        $pdo->rollBack();
        flash('error', 'Pembatalan ditolak: saldo warga menjadi negatif.');
        redirect(BASE . '/dashboard/admin.php');
    }
    $pdo->prepare('UPDATE waste_deposits SET dibatalkan = 1, catatan_batal = ? WHERE id = ?')
        ->execute([$catatan, $id]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Pembatalan gagal. Coba lagi.');
    redirect(BASE . '/dashboard/admin.php');
}

flash('sukses', 'Setoran dibatalkan dan saldo warga disesuaikan.');
redirect(BASE . '/dashboard/admin.php');
