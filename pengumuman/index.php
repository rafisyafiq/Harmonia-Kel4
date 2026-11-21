<?php
require_once __DIR__ . '/../config/auth.php';

$title = 'Pengumuman & agenda';
$active = 'pengumuman';
$tipeList = ['pengumuman', 'agenda', 'tips'];
$fTipe = in_array($_GET['tipe'] ?? '', $tipeList, true) ? $_GET['tipe'] : '';
$fRw = (int)($_GET['rw'] ?? 0);

$sql = 'SELECT a.*, rw.nama AS rw FROM announcements a LEFT JOIN rw ON rw.id = a.rw_id
    WHERE (a.berlaku_sampai IS NULL OR a.berlaku_sampai >= CURDATE())';
$params = [];
if ($fTipe) {
    $sql .= ' AND a.tipe = ?';
    $params[] = $fTipe;
}
if ($fRw) {
    $sql .= ' AND a.rw_id = ?';
    $params[] = $fRw;
}
$sql .= ' ORDER BY a.sematkan DESC, a.created_at DESC LIMIT 30';
$st = $pdo->prepare($sql);
$st->execute($params);
$data = $st->fetchAll();

$st = $pdo->prepare("SELECT a.*, rw.nama AS rw FROM announcements a LEFT JOIN rw ON rw.id = a.rw_id
    WHERE a.tipe = 'agenda' AND a.tanggal_acara >= NOW()
      AND (a.berlaku_sampai IS NULL OR a.berlaku_sampai >= CURDATE())
    ORDER BY a.tanggal_acara ASC LIMIT 5");
$st->execute();
$agenda = $st->fetchAll();

$rwList = $pdo->query('SELECT id, nama FROM rw ORDER BY nama')->fetchAll();
$u = user();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Informasi RW</p>
                <h1>Pengumuman &amp; agenda.</h1>
                <p class="lead">Jadwal angkut sampah, pembagian pangan, posyandu, pelatihan usaha, dan peringatan cuaca dari petugas RW.</p>
            </div>
            <?php if ($u && in_array($u['role'], ['petugas', 'admin'], true)) : ?>
                <a class="btn btn--primary" href="<?= BASE ?>/pengumuman/kelola.php">Kelola pengumuman</a>
            <?php endif; ?>
        </div>

        <form method="get" class="form-card" style="padding:1rem">
            <div class="form-grid">
                <div class="field" style="margin-bottom:0">
                    <label for="tipe">Jenis</label>
                    <select id="tipe" name="tipe" onchange="this.form.submit()">
                        <option value="">Semua jenis</option>
                        <?php foreach ($tipeList as $t) : ?>
                            <option value="<?= $t ?>" <?= $fTipe === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0">
                    <label for="rw">RW</label>
                    <select id="rw" name="rw" onchange="this.form.submit()">
                        <option value="">Semua RW</option>
                        <?php foreach ($rwList as $r) : ?>
                            <option value="<?= (int)$r['id'] ?>" <?= $fRw === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <noscript><button class="btn btn--outline btn--sm mt-2" type="submit">Terapkan</button></noscript>
        </form>

        <div class="dash-grid mt-3">
            <div>
                <?php if ($data) : ?>
                    <div class="lapak-grid">
                        <?php foreach ($data as $a) : ?>
                            <article class="panel">
                                <div class="lapak__meta">
                                    <span class="chip chip--<?= $a['tipe'] === 'agenda' ? 'info' : ($a['tipe'] === 'tips' ? 'accent' : 'netral') ?>"><?= ucfirst(e($a['tipe'])) ?></span>
                                    <span><?= e($a['rw'] ?? 'Semua RW') ?></span>
                                </div>
                                <h3 class="mt-2"><?= e($a['judul']) ?></h3>
                                <p class="small"><?= e(mb_strimwidth($a['isi'], 0, 140, '…')) ?></p>
                                <?php if ($a['tanggal_acara']) : ?>
                                    <p class="small muted"><?= tanggal($a['tanggal_acara'], true) ?><?= $a['lokasi'] ? ' · ' . e($a['lokasi']) : '' ?></p>
                                <?php endif; ?>
                                <a class="btn btn--outline btn--sm" href="<?= BASE ?>/pengumuman/detail.php?id=<?= (int)$a['id'] ?>">Baca</a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty"><b>Belum ada pengumuman.</b>Informasi dari petugas RW akan tampil di sini.</div>
                <?php endif; ?>
            </div>

            <div>
                <div class="panel">
                    <h3>Agenda mendatang</h3>
                    <?php if ($agenda) : ?>
                        <ul class="list-plain">
                            <?php foreach ($agenda as $a) : ?>
                                <li>
                                    <span><a href="<?= BASE ?>/pengumuman/detail.php?id=<?= (int)$a['id'] ?>"><?= e($a['judul']) ?></a><br><span class="small muted"><?= tanggal($a['tanggal_acara'], true) ?><?= $a['lokasi'] ? ' · ' . e($a['lokasi']) : '' ?></span></span>
                                    <span class="chip chip--netral"><?= e($a['rw'] ?? 'Semua') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <p class="small muted">Belum ada agenda mendatang.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
