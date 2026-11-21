<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE . '/cek-kondisi/index.php');
}
csrf_check();

$json = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
$jumlah = (int)($_POST['jumlah_orang'] ?? 0);
$pengeluaran = (int)($_POST['pengeluaran_bulanan'] ?? 0);
$errors = [];

if ($jumlah < 1 || $jumlah > 20) {
    $errors['jumlah_orang'] = 'Jumlah anggota 1–20 orang.';
}
if ($pengeluaran < 1 || $pengeluaran > 100000000) {
    $errors['pengeluaran_bulanan'] = 'Pengeluaran 1–100.000.000 rupiah.';
}

if ($errors) {
    if ($json) {
        json_out(['errors' => $errors], 422);
    }
    flash('error', reset($errors));
    redirect(BASE . '/cek-kondisi/index.php');
}

$perKapita = intdiv($pengeluaran, $jumlah);
$status = klasifikasi_kondisi($perKapita);

$st = $pdo->prepare('INSERT INTO family_checks (user_id, jumlah_orang, pengeluaran_bulanan, per_kapita, status) VALUES (?, ?, ?, ?, ?)');
$st->execute([$u['id'], $jumlah, $pengeluaran, $perKapita, $status]);
$id = (int)$pdo->lastInsertId();

if (!$json) {
    redirect(BASE . '/cek-kondisi/index.php?hasil=' . $id);
}

json_out([
    'per_kapita' => $perKapita,
    'status' => $status,
    'batas' => (int)setting('garis_batas_per_kapita'),
    'check_id' => $id,
]);
