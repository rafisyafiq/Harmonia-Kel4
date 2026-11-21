<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

$rwId = $u['role'] === 'admin' ? null : (int)$u['rw_id'];

$sql = "SELECT a.*, us.nama AS warga, rw.nama AS rw
    FROM aid_requests a JOIN users us ON us.id = a.user_id LEFT JOIN rw ON rw.id = us.rw_id
    WHERE a.status IN ('diajukan','diverifikasi')";
$params = [];
if ($rwId !== null) {
    $sql .= ' AND us.rw_id = ?';
    $params[] = $rwId;
}
$sql .= ' ORDER BY FIELD(a.status, "diajukan") DESC, a.created_at ASC LIMIT 20';
$st = $pdo->prepare($sql);
$st->execute($params);
$pengajuan = $st->fetchAll();

$sql = "SELECT r.*, rw.nama AS rw FROM reports r JOIN rw ON rw.id = r.rw_id
    WHERE r.status IN ('baru','diproses')";
$params = [];
if ($rwId !== null) {
    $sql .= ' AND r.rw_id = ?';
    $params[] = $rwId;
}
$sql .= ' ORDER BY FIELD(r.status, "baru") DESC, r.created_at ASC LIMIT 20';
$st = $pdo->prepare($sql);
$st->execute($params);
$laporan = $st->fetchAll();

// Hanya konfirmasi terbaru per warga dalam 24 jam terakhir (T-3, T-26).
$sql = "SELECT s.status, COUNT(*) n FROM safety_checks s
    JOIN users us ON us.id = s.user_id
    WHERE s.id IN (SELECT MAX(id) FROM safety_checks
        WHERE created_at >= NOW() - INTERVAL 24 HOUR GROUP BY user_id)";
$params = [];
if ($rwId !== null) {
    $sql .= ' AND us.rw_id = ?';
    $params[] = $rwId;
}
$sql .= ' GROUP BY s.status';
$st = $pdo->prepare($sql);
$st->execute($params);
$bantuan = $st->fetchAll(PDO::FETCH_KEY_PAIR);

$rwLabel = 'semua RW';
if ($rwId !== null) {
    $st = $pdo->prepare('SELECT nama FROM rw WHERE id = ?');
    $st->execute([$rwId]);
    $rwLabel = $st->fetchColumn() ?: 'RW';
}

$title = 'Dashboard petugas';
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Dashboard petugas · <?= e($rwLabel) ?></p>
                <h1>Antrean layanan warga.</h1>
                <p class="small">Pengajuan dan laporan diurutkan dari yang paling lama menunggu.</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat"><span>Pengajuan menunggu</span><b><?= count($pengajuan) ?></b></div>
            <div class="stat"><span>Laporan belum tuntas</span><b><?= count($laporan) ?></b></div>
            <div class="stat"><span>Warga aman (konfirmasi)</span><b><?= (int)($bantuan['aman'] ?? 0) ?></b></div>
            <div class="stat"><span>Butuh bantuan</span><b><?php $n = (int)($bantuan['butuh_bantuan'] ?? 0); ?>
                <?php if (file_exists(ROOT_DIR . '/siaga/ringkasan.php')) : ?>
                    <a href="<?= BASE ?>/siaga/ringkasan.php"><?= $n ?></a>
                <?php else : ?>
                    <?= $n ?>
                <?php endif; ?>
            </b></div>
        </div>

        <div class="panel">
            <h3>Antrean pengajuan bantuan</h3>
            <?php if ($pengajuan) : ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Warga</th><th>RW</th><th>Jenis</th><th>Diajukan</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach ($pengajuan as $a) : ?>
                            <tr>
                                <td><?= e($a['warga']) ?></td>
                                <td><?= e($a['rw'] ?? '-') ?></td>
                                <td><?= ucfirst(e($a['jenis'])) ?></td>
                                <td><?= tanggal($a['created_at']) ?></td>
                                <td><?= status_chip($a['status']) ?></td>
                                <td>
                                    <?php if (file_exists(ROOT_DIR . '/bantuan/detail.php')) : ?>
                                        <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/bantuan/detail.php?id=<?= (int)$a['id'] ?>">Proses</a>
                                    <?php else : ?>
                                        <span class="small muted">Menunggu modul</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="empty"><b>Antrean kosong.</b>Tidak ada pengajuan yang menunggu.</div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <h3>Antrean laporan warga</h3>
            <?php if ($laporan) : ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Judul</th><th>Kategori</th><th>Lokasi</th><th>Dibuat</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($laporan as $r) : ?>
                            <tr>
                                <td><?= e($r['judul']) ?></td>
                                <td><?= ucfirst(e($r['kategori'])) ?></td>
                                <td><?= e($r['lokasi_teks']) ?><br><span class="small muted"><?= e($r['rw']) ?></span></td>
                                <td><?= tanggal($r['created_at']) ?></td>
                                <td><?= status_chip($r['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="empty"><b>Tidak ada laporan tertunda.</b>Semua laporan sudah selesai.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
