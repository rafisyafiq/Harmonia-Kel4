<?php
require_once __DIR__ . '/../config/auth.php';

$title = 'Lapak Usaha Warga';
$active = 'lapak';

$kategori = ['makanan', 'jasa', 'kerajinan', 'pertanian'];
$pilih = in_array($_GET['kategori'] ?? '', $kategori, true) ? $_GET['kategori'] : '';
$fRw = (int)($_GET['rw'] ?? 0);
$sql = "SELECT b.*, rw.nama AS rw FROM businesses b
    JOIN users us ON us.id = b.user_id LEFT JOIN rw ON rw.id = us.rw_id
    WHERE b.status = 'tayang' AND b.tampil = 1";
$params = [];
if ($pilih) {
    $sql .= ' AND b.kategori = ?';
    $params[] = $pilih;
}
if ($fRw) {
    $sql .= ' AND us.rw_id = ?';
    $params[] = $fRw;
}
$sql .= ' ORDER BY b.created_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$lapak = $st->fetchAll();
$rwList = $pdo->query('SELECT id, nama FROM rw ORDER BY nama')->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Lapak Warga</p>
                <h1>Usaha Tetangga, Dekat dari Rumah</h1>
                <p class="lead">Etalase usaha kecil warga yang sudah diverifikasi admin. Hubungi pemilik langsung untuk memesan.</p>
            </div>
            <?php if (user() && user()['role'] === 'warga' && file_exists(ROOT_DIR . '/lapak/daftar.php')) : ?>
                <a class="btn btn--primary" href="<?= BASE ?>/lapak/daftar.php">Daftarkan Usaha</a>
            <?php endif; ?>
        </div>

        <form method="get" class="form-card mt-3" style="padding:1rem">
            <div class="form-grid">
                <div class="field" style="margin-bottom:0">
                    <label for="kategori">Filter Kategori</label>
                    <select id="kategori" name="kategori" onchange="this.form.submit()">
                        <option value="">Semua Kategori</option>
                        <?php foreach ($kategori as $k) : ?>
                            <option value="<?= $k ?>" <?= $pilih === $k ? 'selected' : '' ?>><?= ucfirst($k) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0">
                    <label for="rw">Filter RW</label>
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

        <?php if ($lapak) : ?>
            <div class="lapak-grid mt-3">
                <?php foreach ($lapak as $l) : ?>
                    <article class="lapak">
                        <img class="lapak__img" src="<?= foto_url($l['foto']) ?>" alt="Foto <?= e($l['nama_usaha']) ?>" loading="lazy">
                        <div class="lapak__body">
                            <span class="chip chip--accent"><?= ucfirst(e($l['kategori'])) ?></span>
                            <h4><?= e($l['nama_usaha']) ?></h4>
                            <p><?= e($l['deskripsi']) ?></p>
                            <div class="lapak__meta">
                                <span><?= e($l['rw'] ?? 'RW') ?></span>
                                <a href="tel:<?= e($l['kontak']) ?>" class="small"><b><?= e($l['kontak']) ?></b></a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="empty mt-3"><b>Belum Ada Lapak di Kategori Ini</b>Lapak warga akan tampil setelah diverifikasi admin.</div>
        <?php endif; ?>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
