<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_login();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT a.*, us.nama AS warga, us.rw_id AS warga_rw, rw.nama AS rw,
        fc.per_kapita, fc.status AS cek_status
    FROM aid_requests a
    JOIN users us ON us.id = a.user_id
    LEFT JOIN rw ON rw.id = us.rw_id
    LEFT JOIN family_checks fc ON fc.id = a.check_id
    WHERE a.id = ?");
$st->execute([$id]);
$a = $st->fetch();

if (!$a) {
    http_response_code(404);
    exit('Pengajuan tidak ditemukan.');
}
$boleh = $u['role'] === 'admin'
    || (int)$a['user_id'] === (int)$u['id']
    || ($u['role'] === 'petugas' && (int)$a['warga_rw'] === (int)$u['rw_id']);
if (!$boleh) {
    http_response_code(403);
    exit('Anda tidak berhak melihat pengajuan ini.');
}

$st = $pdo->prepare('SELECT l.*, us.nama AS oleh FROM status_logs l
    LEFT JOIN users us ON us.id = l.by_user
    WHERE l.ref_type = ? AND l.ref_id = ? ORDER BY l.created_at ASC, l.id ASC');
$st->execute(['aid', $id]);
$logs = $st->fetchAll();

$langkah = ['diajukan', 'diverifikasi', $a['status'] === 'ditolak' ? 'ditolak' : 'disetujui'];
$posisi = array_search($a['status'], $langkah, true);
$bisaProses = in_array($u['role'], ['petugas', 'admin'], true) && in_array($a['status'], ['diajukan', 'diverifikasi'], true);
$next = $a['status'] === 'diajukan' ? ['diverifikasi' => 'Verifikasi'] : ['disetujui' => 'Setujui', 'ditolak' => 'Tolak'];

$title = 'Detail Pengajuan ' . kode_pengajuan($id);
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Pengajuan <?= kode_pengajuan($id) ?></p>
                <h1><?= ucwords(e($a['jenis'])) ?> · <?= e($a['warga']) ?></h1>
                <p class="small">Diajukan <?= tanggal($a['created_at'], true) ?> · <?= e($a['rw'] ?? '-') ?></p>
            </div>
            <div class="form-actions">
                <button class="btn btn--outline btn--sm" type="button" onclick="window.print()">Cetak</button>
                <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/bantuan/index.php">Kembali</a>
            </div>
        </div>

        <ol class="stepper">
            <?php foreach ($langkah as $i => $l) : ?>
                <li class="<?= $i < $posisi ? 'is-done' : ($i === $posisi ? 'is-now' : '') ?>"><?= e(STATUS_LABEL[$l] ?? $l) ?></li>
            <?php endforeach; ?>
        </ol>

        <div class="dash-grid">
            <div>
                <div class="panel">
                    <h3>Alasan Pengajuan</h3>
                    <p><?= nl2br(e($a['alasan'])) ?></p>
                    <?php if ($a['catatan_petugas']) : ?>
                        <div class="alert alert--info"><b>Catatan Petugas:</b> <?= nl2br(e($a['catatan_petugas'])) ?></div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <h3>Riwayat Status</h3>
                    <?php if ($logs) : ?>
                        <ul class="timeline">
                            <?php foreach ($logs as $l) : ?>
                                <li>
                                    <?= status_chip($l['status']) ?>
                                    <time><?= tanggal($l['created_at'], true) ?><?= $l['oleh'] ? ' · ' . e($l['oleh']) : '' ?></time>
                                    <?php if ($l['catatan']) : ?><span class="small"><?= nl2br(e($l['catatan'])) ?></span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="small muted">Belum ada catatan perubahan status.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <?php if ($u['role'] !== 'warga') : ?>
                    <div class="panel">
                        <h3>Data Verifikasi</h3>
                        <ul class="list-plain">
                            <li><span>Warga</span><b><?= e($a['warga']) ?></b></li>
                            <li><span>RW</span><b><?= e($a['rw'] ?? '-') ?></b></li>
                            <li><span>Hasil Cek per Kapita</span><b><?= $a['per_kapita'] !== null ? rupiah($a['per_kapita']) : '-' ?></b></li>
                            <li><span>Status Cek</span><span><?= $a['cek_status'] ? status_chip($a['cek_status']) : '-' ?></span></li>
                        </ul>
                        <p class="small muted mt-2">Periksa kebenaran data sebelum mengambil keputusan.</p>
                    </div>
                <?php endif; ?>

                <?php if ($bisaProses) : ?>
                    <div class="panel">
                        <h3>Proses Pengajuan</h3>
                        <form method="post" action="<?= BASE ?>/bantuan/proses.php" data-busy>
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <div class="field">
                                <label for="status">Keputusan</label>
                                <select id="status" name="status" required>
                                    <?php foreach ($next as $val => $label) : ?>
                                        <option value="<?= $val ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="catatan">Catatan <span class="hint">(wajib bila menolak)</span></label>
                                <textarea id="catatan" name="catatan" maxlength="500" data-counter="hitung-catatan"></textarea>
                                <span class="field__count" id="hitung-catatan">0 karakter</span>
                            </div>
                            <div class="form-actions">
                                <button class="btn btn--primary" type="submit">Simpan Keputusan</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
