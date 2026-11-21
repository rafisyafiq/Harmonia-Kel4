<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga', 'petugas', 'admin');

$title = 'Riwayat cek kondisi';

$sql = 'SELECT f.*, us.nama AS warga, rw.nama AS rw
    FROM family_checks f JOIN users us ON us.id = f.user_id LEFT JOIN rw ON rw.id = us.rw_id';
$params = [];
if ($u['role'] === 'warga') {
    $sql .= ' WHERE f.user_id = ?';
    $params[] = $u['id'];
} elseif ($u['role'] === 'petugas') {
    $sql .= ' WHERE us.rw_id = ?';
    $params[] = $u['rw_id'];
}
$sql .= ' ORDER BY f.created_at DESC LIMIT 100';
$st = $pdo->prepare($sql);
$st->execute($params);
$data = $st->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Riwayat cek kondisi</p>
                <h1><?= $u['role'] === 'warga' ? 'Riwayat perhitungan saya.' : 'Riwayat warga.' ?></h1>
                <p class="small">Seratus perhitungan terakhir, terbaru di atas.</p>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/cek-kondisi/index.php">Cek baru</a>
        </div>

        <?php if ($data) : ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr>
                        <?php if ($u['role'] !== 'warga') : ?><th>Warga</th><th>RW</th><?php endif; ?>
                        <th>Tanggal</th><th>Anggota</th><th>Pengeluaran</th><th>Per kapita</th><th>Status</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($data as $r) : ?>
                        <tr>
                            <?php if ($u['role'] !== 'warga') : ?>
                                <td><?= e($r['warga']) ?></td>
                                <td><?= e($r['rw'] ?? '-') ?></td>
                            <?php endif; ?>
                            <td><?= tanggal($r['created_at']) ?></td>
                            <td><?= (int)$r['jumlah_orang'] ?></td>
                            <td><?= rupiah($r['pengeluaran_bulanan']) ?></td>
                            <td><?= rupiah($r['per_kapita']) ?></td>
                            <td><?= status_chip($r['status']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="empty"><b>Belum ada data.</b>Riwayat muncul setelah ada perhitungan.</div>
        <?php endif; ?>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
