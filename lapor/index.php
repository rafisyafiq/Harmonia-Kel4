<?php
require_once __DIR__ . '/../config/auth.php';

$title = 'Laporan warga';
$active = 'lapor';
$kategoriList = ['lampu', 'selokan', 'sampah', 'jalan', 'lainnya'];
$statusList = ['baru', 'diproses', 'selesai'];
$fKategori = in_array($_GET['kategori'] ?? '', $kategoriList, true) ? $_GET['kategori'] : '';
$fStatus = in_array($_GET['status'] ?? '', $statusList, true) ? $_GET['status'] : '';
$fRw = (int)($_GET['rw'] ?? 0);

// Lacak status publik lewat kode laporan.
if (isset($_GET['kode']) && trim((string)$_GET['kode']) !== '') {
    if (preg_match('/^LPR-(\d{1,9})$/i', trim((string)$_GET['kode']), $m)) {
        redirect(BASE . '/lapor/detail.php?id=' . (int)$m[1]);
    }
    flash('error', 'Kode laporan tidak dikenali. Contoh: LPR-0142.');
    redirect(BASE . '/lapor/index.php');
}

$where = [];
$params = [];
if ($fKategori) {
    $where[] = 'r.kategori = ?';
    $params[] = $fKategori;
}
if ($fStatus) {
    $where[] = 'r.status = ?';
    $params[] = $fStatus;
}
if ($fRw) {
    $where[] = 'r.rw_id = ?';
    $params[] = $fRw;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$st = $pdo->prepare('SELECT COUNT(*) FROM reports r' . $whereSql);
$st->execute($params);
$total = (int)$st->fetchColumn();

$perPage = 10;
$halaman = max(1, (int)($_GET['hal'] ?? 1));
$totalHalaman = max(1, (int)ceil($total / $perPage));
$halaman = min($halaman, $totalHalaman);

$st = $pdo->prepare("SELECT r.*, rw.nama AS rw FROM reports r JOIN rw ON rw.id = r.rw_id"
    . $whereSql . ' ORDER BY r.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . (($halaman - 1) * $perPage));
$st->execute($params);
$data = $st->fetchAll();

$rwList = $pdo->query('SELECT id, nama FROM rw ORDER BY nama')->fetchAll();
$query = $_GET;
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Lapor warga · SDGs 11</p>
                <h1>Masalah lingkungan, ditangani bersama.</h1>
                <p class="lead">Semua laporan tampil tanpa nama pelapor. Simpan kode laporan untuk memantau statusnya kapan saja.</p>
            </div>
            <?php if (user() && user()['role'] === 'warga') : ?>
                <a class="btn btn--primary" href="<?= BASE ?>/lapor/buat.php">Buat laporan</a>
            <?php endif; ?>
        </div>

        <div class="dash-grid">
            <div>
                <form method="get" class="form-card" style="padding:1rem">
                    <div class="form-grid">
                        <div class="field" style="margin-bottom:0">
                            <label for="kategori">Kategori</label>
                            <select id="kategori" name="kategori" onchange="this.form.submit()">
                                <option value="">Semua kategori</option>
                                <?php foreach ($kategoriList as $k) : ?>
                                    <option value="<?= $k ?>" <?= $fKategori === $k ? 'selected' : '' ?>><?= ucfirst($k) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
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

                <?php include ROOT_DIR . '/includes/legenda_status.php'; ?>

                <?php if ($data) : ?>
                    <div class="report-list mt-2">
                        <?php foreach ($data as $r) : ?>
                            <a class="report-row" href="<?= BASE ?>/lapor/detail.php?id=<?= (int)$r['id'] ?>">
                                <span class="chip chip--netral"><?= ucfirst(e($r['kategori'])) ?></span>
                                <div class="report-row__main">
                                    <b><?= e($r['judul']) ?></b>
                                    <span class="report-row__meta"><?= kode_laporan((int)$r['id']) ?> · <?= e($r['lokasi_teks']) ?> · <?= e($r['rw']) ?> · <?= tanggal($r['created_at']) ?></span>
                                </div>
                                <div class="report-row__side"><?= status_chip($r['status']) ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalHalaman > 1) : ?>
                        <div class="form-actions mt-3">
                            <?php for ($i = 1; $i <= $totalHalaman; $i++) :
                                $q = $query;
                                $q['hal'] = $i; ?>
                                <a class="btn btn--sm <?= $i === $halaman ? 'btn--primary' : 'btn--outline' ?>"
                                   href="<?= BASE ?>/lapor/index.php?<?= e(http_build_query($q)) ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div class="empty mt-2"><b>Belum ada laporan.</b>Laporan yang masuk akan tampil di sini tanpa nama pelapor.</div>
                <?php endif; ?>
            </div>

            <div>
                <div class="panel">
                    <h3>Cari kode laporan</h3>
                    <p class="small">Masukkan kode yang Anda terima saat membuat laporan.</p>
                    <form method="get">
                        <div class="field">
                            <label for="kode" class="sr-only">Kode laporan</label>
                            <input id="kode" name="kode" placeholder="LPR-0142" maxlength="20" required>
                        </div>
                        <button class="btn btn--outline btn--sm" type="submit">Lacak laporan</button>
                    </form>
                </div>
                <div class="panel">
                    <h3>Arti status</h3>
                    <?php include ROOT_DIR . '/includes/legenda_status.php'; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
