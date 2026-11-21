<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

$title = 'Buat usulan';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $judul = trim((string)($_POST['judul'] ?? ''));
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $target = (int)($_POST['target_dukungan'] ?? 50);

    if (mb_strlen($judul) < 5 || mb_strlen($judul) > 100) {
        $errors['judul'] = 'Judul 5–100 karakter.';
    }
    if (mb_strlen($deskripsi) < 20 || mb_strlen($deskripsi) > 1000) {
        $errors['deskripsi'] = 'Deskripsi 20–1000 karakter.';
    }
    if ($target < 10 || $target > 500) {
        $errors['target_dukungan'] = 'Target dukungan 10–500.';
    }

    if (!$errors) {
        $st = $pdo->prepare('INSERT INTO proposals (user_id, rw_id, judul, deskripsi, target_dukungan) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$u['id'], $u['rw_id'], $judul, $deskripsi, $target]);
        $id = (int)$pdo->lastInsertId();
        flash('sukses', 'Usulan dibuat. Ajak warga RW Anda untuk mendukung.');
        redirect(BASE . '/usulan/detail.php?id=' . $id);
    }
}
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="form-page">
    <div class="container">
        <div class="form-card form-card--narrow">
            <h1>Buat usulan.</h1>
            <p class="small">Usulan hanya terlihat untuk warga RW Anda. Bila dukungan mencapai target, petugas dapat meneruskannya.</p>

            <?php if ($errors) : ?>
                <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
            <?php endif; ?>

            <form method="post" data-busy>
                <?= csrf_field() ?>
                <div class="field <?= isset($errors['judul']) ? 'field--error' : '' ?>">
                    <label for="judul">Judul usulan</label>
                    <input id="judul" name="judul" value="<?= old('judul') ?>" required minlength="5" maxlength="100">
                    <?php if (isset($errors['judul'])) : ?><span class="field__error"><?= e($errors['judul']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['deskripsi']) ? 'field--error' : '' ?>">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" required minlength="20" maxlength="1000" data-counter="hitung-deskripsi"><?= old('deskripsi') ?></textarea>
                    <span class="field__count" id="hitung-deskripsi">0 karakter</span>
                    <?php if (isset($errors['deskripsi'])) : ?><span class="field__error"><?= e($errors['deskripsi']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['target_dukungan']) ? 'field--error' : '' ?>">
                    <label for="target_dukungan">Target dukungan</label>
                    <input id="target_dukungan" name="target_dukungan" type="number" min="10" max="500" step="1" required value="<?= old('target_dukungan', '50') ?>">
                    <span class="hint">10–500 warga.</span>
                    <?php if (isset($errors['target_dukungan'])) : ?><span class="field__error"><?= e($errors['target_dukungan']) ?></span><?php endif; ?>
                </div>

                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Kirim usulan</button>
                    <a class="btn btn--outline" href="<?= BASE ?>/usulan/index.php">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
