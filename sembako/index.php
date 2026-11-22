<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');
refresh_sembako($pdo);



$title = 'Bantuan Sosial';
$st = $pdo->prepare("SELECT f.*, us.nama AS donor FROM sembako_shares f JOIN users us ON us.id = f.donor_id
    WHERE f.rw_id = ? AND f.status = 'tersedia' ORDER BY f.batas_ambil ASC");
$st->execute([$u['rw_id']]);
$tersedia = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM sembako_shares WHERE donor_id = ? ORDER BY created_at DESC LIMIT 20');
$st->execute([$u['id']]);
$milikSaya = $st->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Bantuan Sosial · Sdgs 1 &amp; 11</p>
                <h1>Berbagi Sembako, Kurangi Terbuang</h1>
                <p class="small">Hanya untuk warga RW Anda. Satu orang maksimal 2 porsi per postingan.</p>
            </div>
            <a class="btn btn--primary btn--sm" href="<?= BASE ?>/sembako/bagikan.php">Bagikan Sembako</a>
        </div>

        <h2 class="mt-2">Tersedia Sekarang</h2>
        <?php if ($tersedia) : ?>
            <div class="lapak-grid mt-2">
                <?php foreach ($tersedia as $f) : ?>
                    <article class="food-card">
                        <div class="food-card__meta"><span class="chip chip--ok"><?= (int)$f['porsi_sisa'] ?> porsi</span><span>s/d <?= tanggal($f['batas_ambil'], true) ?></span></div>
                        <h3><?= e($f['nama_makanan']) ?></h3>
                        <p class="small"><?= e($f['kondisi'] ?: 'Kondisi tidak dicatat') ?></p>
                        <p class="small muted">Donor: <?= e($f['donor']) ?></p>

                        <?php if ((int)$f['donor_id'] === (int)$u['id']) : ?>
                            <p class="small muted">Postingan Anda sendiri.</p>
                        <?php else : ?>
                            <div class="form-actions">
                                <label class="sr-only" for="porsi-<?= (int)$f['id'] ?>">Porsi</label>
                                <select id="porsi-<?= (int)$f['id'] ?>" data-porsi="<?= (int)$f['id'] ?>">
                                    <option value="1">1 Porsi</option>
                                    <?php if ((int)$f['porsi_sisa'] >= 2) : ?><option value="2">2 Porsi</option><?php endif; ?>
                                </select>
                                <button class="btn btn--primary btn--sm" type="button"
                                        data-ambil="<?= (int)$f['id'] ?>"
                                        data-url="<?= BASE ?>/sembako/ambil.php"
                                        data-csrf="<?= csrf_token() ?>"
                                        data-sisa="<?= (int)$f['porsi_sisa'] ?>">Ambil</button>
                            </div>
                            <p class="small" id="kontak-<?= (int)$f['id'] ?>"></p>
                            <p class="small" id="pesan-<?= (int)$f['id'] ?>"></p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="empty mt-2"><b>Belum Ada Sembako Tersedia</b>Bagikan sembako layak berlebih untuk tetangga.</div>
        <?php endif; ?>

        <div class="panel mt-3">
            <h3>Postingan Saya</h3>
            <?php if ($milikSaya) : ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Sembako</th><th>Porsi</th><th>Batas Ambil</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($milikSaya as $f) : ?>
                            <tr>
                                <td><?= e($f['nama_makanan']) ?></td>
                                <td><?= (int)$f['porsi_sisa'] ?> / <?= (int)$f['porsi_total'] ?></td>
                                <td><?= tanggal($f['batas_ambil'], true) ?></td>
                                <td><?= status_chip($f['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="small muted">Anda belum pernah membagikan sembako.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
$scripts = ['/assets/js/sembako.js'];
include ROOT_DIR . '/includes/footer.php';
