<?php
require_once __DIR__ . '/../config/auth.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT a.*, rw.nama AS rw FROM announcements a LEFT JOIN rw ON rw.id = a.rw_id WHERE a.id = ?');
$st->execute([$id]);
$a = $st->fetch();
if (!$a || ($a['berlaku_sampai'] && $a['berlaku_sampai'] < date('Y-m-d'))) {
    http_response_code(404);
    exit('Pengumuman tidak ditemukan atau sudah tidak berlaku.');
}

$title = $a['judul'];
$u = user();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="form-card form-card--narrow">
            <div class="lapak__meta">
                <span class="chip chip--accent"><?= ucfirst(e($a['tipe'])) ?></span>
                <span><?= e($a['rw'] ?? 'Semua RW') ?></span>
            </div>
            <h1 class="mt-2"><?= e($a['judul']) ?></h1>
            <p class="small muted"><?= tanggal($a['created_at'], true) ?><?= $a['berlaku_sampai'] ? ' · berlaku sampai ' . tanggal($a['berlaku_sampai']) : '' ?></p>

            <?php if ($a['tanggal_acara']) : ?>
                <div class="alert alert--info">
                    <b>Agenda:</b> <?= tanggal($a['tanggal_acara'], true) ?><?= $a['lokasi'] ? ' · ' . e($a['lokasi']) : '' ?>
                </div>
            <?php endif; ?>

            <p><?= nl2br(e($a['isi'])) ?></p>

            <div class="form-actions mt-3">
                <a class="btn btn--outline btn--sm" href="<?= BASE ?>/pengumuman/index.php">Kembali</a>
                <?php if ($u && in_array($u['role'], ['petugas', 'admin'], true)) : ?>
                    <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/pengumuman/form.php?id=<?= (int)$a['id'] ?>">Ubah</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
