<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/sampah/index.php');
}
csrf_check();

$jumlah = (int)($_POST['jumlah'] ?? 0);
if ($jumlah < 1) {
    flash('error', 'Jumlah penarikan minimal Rp 1.');
    redirect(BASE . '/sampah/index.php');
}

try {
    $pdo->beginTransaction();
    // Kunci baris pengguna agar dua penarikan beruntun tidak melebihi saldo (T12).
    $st = $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE');
    $st->execute([$u['id']]);
    $saldo = saldo_warga($pdo, (int)$u['id']);
    if ($jumlah > $saldo) {
        $pdo->rollBack();
        flash('error', 'Jumlah melebihi saldo tersedia (' . rupiah($saldo) . ').');
        redirect(BASE . '/sampah/index.php');
    }
    $pdo->prepare("INSERT INTO waste_withdrawals (user_id, jumlah) VALUES (?, ?)")->execute([$u['id'], $jumlah]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Penarikan gagal diajukan. Coba lagi.');
    redirect(BASE . '/sampah/index.php');
}

flash('sukses', 'Penarikan ' . rupiah($jumlah) . ' diajukan. Tunggu pencairan petugas.');
redirect(BASE . '/sampah/index.php');
