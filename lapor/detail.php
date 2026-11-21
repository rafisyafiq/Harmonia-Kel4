<?php
require_once __DIR__ . '/../config/auth.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT r.*, rw.nama AS rw FROM reports r JOIN rw ON rw.id = r.rw_id WHERE r.id = ?');
$st->execute([$id]);
$r = $st->fetch();
if (!$r) {
    http_response_code(404);
    exit('Laporan tidak ditemukan. Periksa kembali kode laporan.');
}

$u = user();
$bisaProses = $u && in_array($u['role'], ['petugas', 'admin'], true)
    && ($u['role'] === 'admin' || (int)$u['rw_id'] === (int)$r['rw_id'])
    && in_array($r['status'], ['baru', 'diproses'], true);

$st = $pdo->prepare('SELECT l.*, us.nama AS oleh FROM status_logs l
    LEFT JOIN users us ON us.id = l.by_user
    WHERE l.ref_type = ? AND l.ref_id = ? ORDER BY l.created_at ASC, l.id ASC');
$st->execute(['report', $id]);
$logs = $st->fetchAll();

$langkah = ['baru', 'diproses', 'selesai'];
$posisi = array_search($r['status'], $langkah, true);
$next = $r['status'] === 'baru' ? ['diproses' => 'Mulai proses'] : ['selesai' => 'Tandai selesai'];

$title = 'Laporan ' . kode_laporan($id);
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow"><?= kode_laporan($id) ?> · <?= e($r['rw']) ?></p>
                <h1><?= e($r['judul']) ?></h1>
                <p class="small"><?= ucfirst(e($r['kategori'])) ?> · <?= e($r['lokasi_teks']) ?> · <?= tanggal($r['created_at'], true) ?></p>
            </div>
            <div class="form-actions">
                <button class="btn btn--outline btn--sm" type="button" onclick="window.print()">Cetak</button>
                <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/lapor/index.php">Kembali</a>
            </div>
        </div>

        <ol class="stepper">
            <?php foreach ($langkah as $i => $l) : ?>
                <li class="<?= $i < $posisi ? 'is-done' : ($i === $posisi ? 'is-now' : '') ?>"><?= e(STATUS_LABEL[$l]) ?></li>
            <?php endforeach; ?>
        </ol>

        <div class="dash-grid">
            <div>
                <div class="panel">
                    <h3>Deskripsi</h3>
                    <p><?= nl2br(e($r['deskripsi'])) ?></p>
                    <p class="small muted">Pelapor tidak ditampilkan demi privasi.</p>
                </div>

                <div class="panel">
                    <h3>Foto</h3>
                    <div class="form-grid">
                        <figure style="margin:0">
                            <img src="<?= foto_url($r['foto']) ?>" alt="Foto laporan" style="border-radius:var(--radius-sm)">
                            <figcaption class="small muted mt-1">Kondisi awal</figcaption>
                        </figure>
                        <?php if ($r['foto_selesai']) : ?>
                            <figure style="margin:0">
                                <img src="<?= foto_url($r['foto_selesai']) ?>" alt="Foto hasil perbaikan" style="border-radius:var(--radius-sm)">
                                <figcaption class="small muted mt-1">Hasil perbaikan</figcaption>
                            </figure>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="panel">
                    <h3>Riwayat status</h3>
                    <ul class="timeline">
                        <?php foreach ($logs as $l) : ?>
                            <li>
                                <?= status_chip($l['status']) ?>
                                <time><?= tanggal($l['created_at'], true) ?><?= $l['oleh'] ? ' · ' . e($l['oleh']) : '' ?></time>
                                <?php if ($l['catatan']) : ?><span class="small"><?= nl2br(e($l['catatan'])) ?></span><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div>
                <?php if ($bisaProses) : ?>
                    <div class="panel">
                        <h3>Proses laporan</h3>
                        <form method="post" action="<?= BASE ?>/lapor/proses.php" enctype="multipart/form-data" data-busy>
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <div class="field">
                                <label for="status">Status berikutnya</label>
                                <select id="status" name="status" required>
                                    <?php foreach ($next as $val => $label) : ?>
                                        <option value="<?= $val ?>"><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="catatan">Catatan</label>
                                <textarea id="catatan" name="catatan" maxlength="500" data-counter="hitung-catatan"></textarea>
                                <span class="field__count" id="hitung-catatan">0 karakter</span>
                            </div>
                            <div class="field">
                                <label for="foto_selesai">Foto hasil <span class="hint">(dipakai saat selesai)</span></label>
                                <input id="foto_selesai" name="foto_selesai" type="file" accept="image/jpeg,image/png">
                            </div>
                            <div class="form-actions">
                                <button class="btn btn--primary" type="submit">Simpan</button>
                            </div>
                        </form>
                    </div>
                <?php elseif ($u && $u['role'] === 'petugas' && (int)$u['rw_id'] !== (int)$r['rw_id']) : ?>
                    <div class="panel"><p class="small muted">Laporan ini milik RW lain. Hanya petugas RW terkait yang dapat memproses.</p></div>
                <?php endif; ?>

                <div class="panel">
                    <h3>Ringkasan</h3>
                    <ul class="list-plain">
                        <li><span>Kode</span><b><?= kode_laporan($id) ?></b></li>
                        <li><span>RW</span><b><?= e($r['rw']) ?></b></li>
                        <li><span>Status</span><span><?= status_chip($r['status']) ?></span></li>
                        <li><span>Diperbarui</span><b><?= tanggal($r['updated_at']) ?></b></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
