<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Metode tidak diizinkan.'], 405);
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$porsi = (int)($_POST['porsi'] ?? 1);
if ($porsi < 1 || $porsi > 2) {
    json_out(['error' => 'Porsi 1–2.'], 422);
}

try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT * FROM sembako_shares WHERE id = ? FOR UPDATE');
    $st->execute([$id]);
    $f = $st->fetch();

    if (!$f) {
        $pdo->rollBack();
        json_out(['error' => 'Postingan tidak ditemukan.'], 404);
    }
    if ($f['status'] !== 'tersedia' || strtotime($f['batas_ambil']) <= time()) {
        $pdo->rollBack();
        json_out(['error' => 'Sembako ini sudah tidak tersedia.'], 409);
    }
    if ((int)$f['rw_id'] !== (int)$u['rw_id']) {
        $pdo->rollBack();
        json_out(['error' => 'Hanya warga RW yang sama.'], 403);
    }
    if ((int)$f['donor_id'] === (int)$u['id']) {
        $pdo->rollBack();
        json_out(['error' => 'Tidak bisa mengambil postingan sendiri.'], 422);
    }

    $st = $pdo->prepare('SELECT COALESCE(SUM(porsi), 0) FROM sembako_claims WHERE share_id = ? AND user_id = ?');
    $st->execute([$id, $u['id']]);
    $sudah = (int)$st->fetchColumn();
    if ($sudah + $porsi > 2) {
        $pdo->rollBack();
        json_out(['error' => 'Batas klaim 2 porsi per postingan.'], 422);
    }
    if ($porsi > (int)$f['porsi_sisa']) {
        $pdo->rollBack();
        json_out(['error' => 'Porsi tersisa tidak mencukupi.'], 409);
    }

    $sisa = (int)$f['porsi_sisa'] - $porsi;
    $pdo->prepare('INSERT INTO sembako_claims (share_id, user_id, porsi) VALUES (?, ?, ?)')->execute([$id, $u['id'], $porsi]);
    $pdo->prepare('UPDATE sembako_shares SET porsi_sisa = ?, status = ? WHERE id = ?')
        ->execute([$sisa, $sisa === 0 ? 'habis' : 'tersedia', $id]);
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_out(['error' => 'Klaim gagal disimpan.'], 500);
}

$st = $pdo->prepare('SELECT no_hp FROM users WHERE id = ?');
$st->execute([$f['donor_id']]);
$kontak = (string)($st->fetchColumn() ?: '');

json_out([
    'porsi_sisa' => $sisa,
    'status' => $sisa === 0 ? 'habis' : 'tersedia',
    'kontak' => $kontak,
]);
