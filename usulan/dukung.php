<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Metode tidak diizinkan.'], 405);
}
csrf_check();

$id = (int)($_POST['id'] ?? 0);
$st = $pdo->prepare('SELECT * FROM proposals WHERE id = ?');
$st->execute([$id]);
$p = $st->fetch();

if (!$p) {
    json_out(['error' => 'Usulan tidak ditemukan.'], 404);
}
if ((int)$p['rw_id'] !== (int)$u['rw_id']) {
    json_out(['error' => 'Hanya warga RW yang sama yang dapat mendukung.'], 403);
}
if ($p['status'] !== 'dibuka') {
    json_out(['error' => 'Dukungan sudah ditutup.'], 409);
}

try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('INSERT INTO proposal_votes (proposal_id, user_id) VALUES (?, ?)');
    $st->execute([$id, $u['id']]);

    $st = $pdo->prepare('SELECT COUNT(*) FROM proposal_votes WHERE proposal_id = ?');
    $st->execute([$id]);
    $jumlah = (int)$st->fetchColumn();

    $status = 'dibuka';
    if ($jumlah >= (int)$p['target_dukungan']) {
        $pdo->prepare("UPDATE proposals SET status = 'tercapai' WHERE id = ? AND status = 'dibuka'")->execute([$id]);
        $status = 'tercapai';
    }
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e->getCode() === '23000') {
        json_out(['error' => 'Anda sudah mendukung usulan ini.', 'sudah_dukung' => true], 409);
    }
    json_out(['error' => 'Dukungan gagal disimpan.'], 500);
}

json_out(['jumlah' => $jumlah, 'sudah_dukung' => true, 'status' => $status]);
