<?php
require_once __DIR__ . '/../config/auth.php';

$title = 'Usulan warga';
$active = 'usulan';
$statusList = ['dibuka', 'tercapai', 'diteruskan', 'ditolak'];
$fStatus = in_array($_GET['status'] ?? '', $statusList, true) ? $_GET['status'] : '';
$fRw = (int)($_GET['rw'] ?? 0);

$sql = 'SELECT p.*, rw.nama AS rw,
        (SELECT COUNT(*) FROM proposal_votes v WHERE v.proposal_id = p.id) AS dukungan
    FROM proposals p JOIN rw ON rw.id = p.rw_id';
$where = [];
$params = [];
if ($fStatus) {
    $where[] = 'p.status = ?';
    $params[] = $fStatus;
}
if ($fRw) {
    $where[] = 'p.rw_id = ?';
    $params[] = $fRw;
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY FIELD(p.status, 'dibuka', 'tercapai', 'diteruskan', 'ditolak'), p.created_at DESC";
$st = $pdo->prepare($sql);
$st->execute($params);
$data = $st->fetchAll();

$rwList = $pdo->query('SELECT id, nama FROM rw ORDER BY nama')->fetchAll();
$u = user();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Usulan warga</p>
                <h1>Usulkan perbaikan, kumpulkan dukungan.</h1>
                <p class="lead">Usulan dengan dukungan penuh diteruskan petugas RW. Satu warga satu dukungan per usulan.</p>
            </div>
            <?php if ($u && $u['role'] === 'warga') : ?>
                <a class="btn btn--primary" href="<?= BASE ?>/usulan/buat.php">Buat usulan</a>
            <?php endif; ?>
        </div>

        <form method="get" class="form-card" style="padding:1rem">
            <div class="form-grid">
                <div class="field" style="margin-bottom:0">
                    <label for="status">Status</label>
                    <select id="status" name="status" onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        <?php foreach ($statusList as $s) : ?>
                            <option value="<?= $s ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= e(STATUS_LABEL[$s]) ?></option>
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

        <?php if ($data) : ?>
            <div class="lapak-grid mt-3">
                <?php foreach ($data as $p) :
                    $jumlah = (int)$p['dukungan'];
                    $target = max(1, (int)$p['target_dukungan']);
                    $persen = min(100, (int)round($jumlah / $target * 100)); ?>
                    <article class="panel">
                        <div class="lapak__meta"><span class="chip chip--accent"><?= e($p['rw']) ?></span><?= status_chip($p['status']) ?></div>
                        <h3 class="mt-2"><?= e($p['judul']) ?></h3>
                        <p class="small"><?= e(mb_strimwidth($p['deskripsi'], 0, 140, '…')) ?></p>
                        <div class="progress" aria-hidden="true"><span style="width:<?= $persen ?>%"></span></div>
                        <p class="small mt-1"><b><?= $jumlah ?></b> dari <?= $target ?> dukungan</p>
                        <a class="btn btn--outline btn--sm" href="<?= BASE ?>/usulan/detail.php?id=<?= (int)$p['id'] ?>">Lihat usulan</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="empty mt-3"><b>Belum ada usulan.</b>Usulan warga akan tampil di sini.</div>
        <?php endif; ?>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
