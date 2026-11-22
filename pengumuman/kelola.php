<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

$title = 'Kelola Pengumuman';
$rwId = $u['role'] === 'admin' ? null : (int)$u['rw_id'];

$sql = 'SELECT a.*, rw.nama AS rw FROM announcements a LEFT JOIN rw ON rw.id = a.rw_id';
$params = [];
if ($rwId !== null) {
    $sql .= ' WHERE a.rw_id = ?';
    $params[] = $rwId;
}
$sql .= ' ORDER BY a.sematkan DESC, a.created_at DESC LIMIT 100';
$st = $pdo->prepare($sql);
$st->execute($params);
$data = $st->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Pengumuman &amp; Agenda</p>
                <h1>Kelola Informasi RW</h1>
                <p class="small"><?= $rwId !== null ? 'Anda mengelola RW sendiri.' : 'Admin dapat mengelola semua RW dan pengumuman semua RW.' ?></p>
            </div>
            <div class="form-actions">
                <a class="btn btn--primary btn--sm" href="<?= BASE ?>/pengumuman/form.php">Tambah</a>
                <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/pengumuman/index.php">Lihat Publik</a>
            </div>
        </div>

        <?php if ($data) : ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Judul</th><th>Jenis</th><th>RW</th><th>Agenda</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($data as $a) : ?>
                        <tr>
                            <td><?= e($a['judul']) ?><?= $a['sematkan'] ? ' <span class="chip chip--accent">Disematkan</span>' : '' ?><?= $a['siaga'] ? ' <span class="chip chip--warn">Siaga</span>' : '' ?></td>
                            <td><?= ucfirst(e($a['tipe'])) ?></td>
                            <td><?= e($a['rw'] ?? 'Semua RW') ?></td>
                            <td><?= $a['tanggal_acara'] ? tanggal($a['tanggal_acara'], true) : '-' ?></td>
                            <td><?= $a['berlaku_sampai'] && $a['berlaku_sampai'] < date('Y-m-d') ? '<span class="chip chip--netral">Lewat</span>' : '<span class="chip chip--ok">Aktif</span>' ?></td>
                            <td>
                                <div class="form-actions">
                                    <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/pengumuman/form.php?id=<?= (int)$a['id'] ?>">Ubah</a>
                                    <form method="post" action="<?= BASE ?>/pengumuman/hapus.php" onsubmit="return confirm('Hapus pengumuman ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                        <button class="btn btn--danger btn--sm" type="submit">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="empty"><b>Belum Ada Pengumuman</b>Tambahkan pengumuman atau agenda pertama.</div>
        <?php endif; ?>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
