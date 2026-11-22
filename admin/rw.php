<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('admin');

$title = 'Kelola RW';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = (string)($_POST['aksi'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE rw_id = ?');
        $st->execute([$id]);
        $adaUser = (int)$st->fetchColumn();
        $st = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE rw_id = ?');
        $st->execute([$id]);
        $adaLaporan = (int)$st->fetchColumn();
        if ($adaUser || $adaLaporan) {
            flash('error', 'RW tidak dapat dihapus: masih ada ' . $adaUser . ' pengguna dan ' . $adaLaporan . ' laporan.');
            redirect(BASE . '/admin/rw.php');
        }
        try {
            $pdo->prepare('DELETE FROM rw WHERE id = ?')->execute([$id]);
            flash('sukses', 'RW dihapus.');
        } catch (PDOException $e) {
            flash('error', 'RW masih dipakai data lain dan tidak dapat dihapus.');
        }
        redirect(BASE . '/admin/rw.php');
    }

    $nama = trim((string)($_POST['nama'] ?? ''));
    $kelurahan = trim((string)($_POST['kelurahan'] ?? ''));
    if (mb_strlen($nama) < 2 || mb_strlen($nama) > 30) {
        $errors['nama'] = 'Nama RW 2–30 karakter.';
    }
    if (mb_strlen($kelurahan) < 3 || mb_strlen($kelurahan) > 60) {
        $errors['kelurahan'] = 'Kelurahan 3–60 karakter.';
    }
    if (!$errors) {
        if ($id) {
            $pdo->prepare('UPDATE rw SET nama = ?, kelurahan = ? WHERE id = ?')->execute([$nama, $kelurahan, $id]);
            flash('sukses', 'RW diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO rw (nama, kelurahan) VALUES (?, ?)')->execute([$nama, $kelurahan]);
            flash('sukses', 'RW ditambahkan.');
        }
        redirect(BASE . '/admin/rw.php');
    }
}

$data = $pdo->query('SELECT rw.*,
        (SELECT COUNT(*) FROM users us WHERE us.rw_id = rw.id) AS jumlah_user
    FROM rw ORDER BY rw.nama')->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Administrasi</p>
                <h1>Kelola RW</h1>
                <p class="small">RW yang masih punya pengguna atau laporan tidak dapat dihapus.</p>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/dashboard/admin.php">Dashboard</a>
        </div>

        <?php if ($errors) : ?>
            <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
        <?php endif; ?>

        <div class="panel">
            <h3>Tambah RW</h3>
            <form method="post" class="form-grid" data-busy>
                <?= csrf_field() ?>
                <div class="field <?= isset($errors['nama']) ? 'field--error' : '' ?>">
                    <label for="nama">Nama RW</label>
                    <input id="nama" name="nama" required minlength="2" maxlength="30" value="<?= old('nama') ?>" placeholder="RW 25">
                    <?php if (isset($errors['nama'])) : ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['kelurahan']) ? 'field--error' : '' ?>">
                    <label for="kelurahan">Kelurahan</label>
                    <input id="kelurahan" name="kelurahan" required minlength="3" maxlength="60" value="<?= old('kelurahan') ?>">
                    <?php if (isset($errors['kelurahan'])) : ?><span class="field__error"><?= e($errors['kelurahan']) ?></span><?php endif; ?>
                </div>
                <div class="field field--full">
                    <button class="btn btn--primary" type="submit">Tambah RW</button>
                </div>
            </form>
        </div>

        <div class="table-wrap mt-3">
            <table class="data">
                <thead><tr><th>RW</th><th>Kelurahan</th><th>Pengguna</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($data as $r) : ?>
                    <tr>
                        <td colspan="2">
                            <form method="post" class="form-actions" style="margin:0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <input name="nama" value="<?= e($r['nama']) ?>" aria-label="Nama RW" required maxlength="30" style="max-width:130px">
                                <input name="kelurahan" value="<?= e($r['kelurahan']) ?>" aria-label="Kelurahan" required maxlength="60" style="max-width:200px">
                        </td>
                        <td><?= (int)$r['jumlah_user'] ?></td>
                        <td>
                                <button class="btn btn--ghost btn--sm" type="submit">Simpan</button>
                                <button class="btn btn--danger btn--sm" type="submit" name="aksi" value="hapus"
                                        onclick="return confirm('Hapus <?= e($r['nama']) ?>?')">Hapus</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
