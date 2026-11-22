<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

$title = 'Ringkasan Siaga';
$rwId = $u['role'] === 'admin' ? null : (int)$u['rw_id'];

$sql = "SELECT us.rw_id, rw.nama AS rw, s.status
    FROM users us
    JOIN rw ON rw.id = us.rw_id
    LEFT JOIN safety_checks s ON s.id = (
        SELECT MAX(id) FROM safety_checks
        WHERE user_id = us.id AND created_at >= NOW() - INTERVAL 24 HOUR
    )
    WHERE us.role = 'warga'";
$params = [];
if ($rwId !== null) {
    $sql .= ' AND us.rw_id = ?';
    $params[] = $rwId;
}
$st = $pdo->prepare($sql);
$st->execute($params);

$rekap = [];
foreach ($st->fetchAll() as $row) {
    $id = (int)$row['rw_id'];
    $rekap[$id] ??= ['rw' => $row['rw'], 'total' => 0, 'aman' => 0, 'butuh' => 0, 'belum' => 0];
    $rekap[$id]['total']++;
    if ($row['status'] === 'aman') {
        $rekap[$id]['aman']++;
    } elseif ($row['status'] === 'butuh_bantuan') {
        $rekap[$id]['butuh']++;
    } else {
        $rekap[$id]['belum']++;
    }
}

$sql = "SELECT us.nama, us.no_hp, rw.nama AS rw, s.catatan, s.created_at
    FROM users us
    JOIN rw ON rw.id = us.rw_id
    JOIN safety_checks s ON s.id = (
        SELECT MAX(id) FROM safety_checks
        WHERE user_id = us.id AND created_at >= NOW() - INTERVAL 24 HOUR
    )
    WHERE us.role = 'warga' AND s.status = 'butuh_bantuan'";
$params = [];
if ($rwId !== null) {
    $sql .= ' AND us.rw_id = ?';
    $params[] = $rwId;
}
$sql .= ' ORDER BY s.created_at DESC';
$st = $pdo->prepare($sql);
$st->execute($params);
$bantuan = $st->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Siaga · 24 Jam Terakhir</p>
                <h1>Rekap Keselamatan Warga</h1>
                <p class="small">Hanya konfirmasi terbaru tiap warga dalam 24 jam yang dihitung.</p>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/siaga/index.php">Halaman Siaga</a>
        </div>

        <?php if ($bantuan) : ?>
            <div class="alert alert--error" role="alert"><b><?= count($bantuan) ?> warga butuh bantuan.</b> Hubungi mereka lebih dulu.</div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Nama</th><th>RW</th><th>Catatan</th><th>Waktu</th><th>Kontak</th></tr></thead>
                    <tbody>
                    <?php foreach ($bantuan as $b) : ?>
                        <tr>
                            <td><?= e($b['nama']) ?></td>
                            <td><?= e($b['rw']) ?></td>
                            <td><?= e($b['catatan'] ?: '-') ?></td>
                            <td><?= tanggal($b['created_at'], true) ?></td>
                            <td><?= $b['no_hp'] ? '<a href="tel:' . e($b['no_hp']) . '">' . e($b['no_hp']) . '</a>' : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="empty"><b>Tidak Ada yang Butuh Bantuan</b>Belum ada konfirmasi "butuh bantuan" dalam 24 jam.</div>
        <?php endif; ?>

        <div class="panel mt-3">
            <h3>Rekap per RW</h3>
            <?php if ($rekap) : ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>RW</th><th>Warga</th><th>Aman</th><th>Butuh Bantuan</th><th>Belum Konfirmasi</th></tr></thead>
                        <tbody>
                        <?php foreach ($rekap as $r) : ?>
                            <tr>
                                <td><?= e($r['rw']) ?></td>
                                <td><?= $r['total'] ?></td>
                                <td><?= $r['aman'] ?></td>
                                <td><?= $r['butuh'] ?></td>
                                <td><?= $r['belum'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <p class="small muted">Belum ada data warga.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
