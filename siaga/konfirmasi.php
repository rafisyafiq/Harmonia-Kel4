<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['error' => 'Metode tidak diizinkan.'], 405);
}
csrf_check();

$status = (string)($_POST['status'] ?? '');
$catatan = trim((string)($_POST['catatan'] ?? ''));
$shelterId = (int)($_POST['shelter_id'] ?? 0);

if (!in_array($status, ['aman', 'butuh_bantuan'], true)) {
    json_out(['error' => 'Status konfirmasi tidak valid.'], 422);
}
if (mb_strlen($catatan) > 200) {
    json_out(['error' => 'Catatan maksimal 200 karakter.'], 422);
}
if ($shelterId) {
    $st = $pdo->prepare('SELECT 1 FROM shelters WHERE id = ? AND rw_id = ?');
    $st->execute([$shelterId, $u['rw_id']]);
    if (!$st->fetchColumn()) {
        json_out(['error' => 'Titik kumpul bukan milik RW Anda.'], 422);
    }
}

$st = $pdo->prepare('INSERT INTO safety_checks (user_id, shelter_id, status, catatan) VALUES (?, ?, ?, ?)');
$st->execute([$u['id'], $shelterId ?: null, $status, $catatan ?: null]);

json_out(['status' => $status, 'label' => STATUS_LABEL[$status], 'waktu' => date('c')]);
