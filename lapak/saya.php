<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $st = $pdo->prepare('SELECT tampil FROM businesses WHERE id = ? AND user_id = ?');
    $st->execute([$id, $u['id']]);
    $tampil = $st->fetchColumn();
    if ($tampil === false) {
        http_response_code(404);
        exit('Lapak tidak ditemukan.');
    }
    $pdo->prepare('UPDATE businesses SET tampil = ? WHERE id = ? AND user_id = ?')
        ->execute([$tampil ? 0 : 1, $id, $u['id']]);
    flash('sukses', $tampil ? 'Lapak disembunyikan dari etalase.' : 'Lapak ditampilkan kembali.');
    redirect(BASE . '/lapak/saya.php');
}

$title = 'Lapak saya';
$st = $pdo->prepare('SELECT * FROM businesses WHERE user_id = ? ORDER BY created_at DESC');
$st->execute([$u['id']]);
$data = $st->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Lapak saya</p>
                <h1>Usaha yang saya daftarkan.</h1>
                <p class="small">Lapak tampil di etalase setelah diverifikasi admin.</p>
            </div>
            <a class="btn btn--primary btn--sm" href="<?= BASE ?>/lapak/daftar.php">Daftarkan usaha</a>
        </div>

        <?php if ($data) : ?>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Usaha</th><th>Kategori</th><th>Status</th><th>Tampil</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($data as $b) : ?>
                        <tr>
                            <td><?= e($b['nama_usaha']) ?><br><span class="small muted"><?= e($b['kontak']) ?></span></td>
                            <td><?= ucfirst(e($b['kategori'])) ?></td>
                            <td><?= status_chip($b['status']) ?><?= $b['alasan_tolak'] ? '<br><span class="small muted">' . e($b['alasan_tolak']) . '</span>' : '' ?></td>
                            <td><?= $b['tampil'] ? 'Ya' : 'Tidak' ?></td>
                            <td>
                                <div class="form-actions">
                                    <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/lapak/daftar.php?id=<?= (int)$b['id'] ?>">Ubah</a>
                                    <form method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                                        <button class="btn btn--outline btn--sm" type="submit"><?= $b['tampil'] ? 'Sembunyikan' : 'Tampilkan' ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="empty"><b>Belum ada lapak.</b>Daftarkan usaha kecil Anda agar dikenal tetangga.</div>
        <?php endif; ?>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
