<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

$title = 'Bagikan makanan';
$errors = [];
$defaultBatas = date('Y-m-d\TH:i', strtotime('+6 hours'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama = trim((string)($_POST['nama_makanan'] ?? ''));
    $porsi = (int)($_POST['porsi_total'] ?? 0);
    $kondisi = trim((string)($_POST['kondisi'] ?? ''));
    $batas = trim((string)($_POST['batas_ambil'] ?? ''));

    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 100) {
        $errors['nama_makanan'] = 'Nama makanan 3–100 karakter.';
    }
    if ($porsi < 1 || $porsi > 100) {
        $errors['porsi_total'] = 'Porsi 1–100.';
    }
    if (mb_strlen($kondisi) > 100) {
        $errors['kondisi'] = 'Kondisi maksimal 100 karakter.';
    }
    $waktu = $batas !== '' ? strtotime($batas) : false;
    if (!$waktu || $waktu <= time() || $waktu > time() + 86400) {
        $errors['batas_ambil'] = 'Batas ambil harus di masa depan dan maksimal 24 jam dari sekarang.';
    }

    if (!$errors) {
        $st = $pdo->prepare('INSERT INTO food_shares (donor_id, rw_id, nama_makanan, kondisi, porsi_total, porsi_sisa, batas_ambil) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([$u['id'], $u['rw_id'], $nama, $kondisi ?: null, $porsi, $porsi, date('Y-m-d H:i:s', $waktu)]);
        flash('sukses', 'Makanan dibagikan. Warga RW Anda dapat mengambilnya sampai batas waktu.');
        redirect(BASE . '/pangan/index.php');
    }
}
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="form-page">
    <div class="container">
        <div class="form-card form-card--narrow">
            <h1>Bagikan makanan.</h1>
            <p class="small">Hanya makanan layak konsumsi. Porsi akan berkurang otomatis saat diambil warga lain.</p>

            <?php if ($errors) : ?>
                <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
            <?php endif; ?>

            <form method="post" data-busy>
                <?= csrf_field() ?>
                <div class="field <?= isset($errors['nama_makanan']) ? 'field--error' : '' ?>">
                    <label for="nama_makanan">Nama makanan</label>
                    <input id="nama_makanan" name="nama_makanan" value="<?= old('nama_makanan') ?>" required minlength="3" maxlength="100">
                    <?php if (isset($errors['nama_makanan'])) : ?><span class="field__error"><?= e($errors['nama_makanan']) ?></span><?php endif; ?>
                </div>
                <div class="form-grid">
                    <div class="field <?= isset($errors['porsi_total']) ? 'field--error' : '' ?>">
                        <label for="porsi_total">Jumlah porsi</label>
                        <input id="porsi_total" name="porsi_total" type="number" min="1" max="100" required value="<?= old('porsi_total', '5') ?>">
                        <?php if (isset($errors['porsi_total'])) : ?><span class="field__error"><?= e($errors['porsi_total']) ?></span><?php endif; ?>
                    </div>
                    <div class="field <?= isset($errors['batas_ambil']) ? 'field--error' : '' ?>">
                        <label for="batas_ambil">Batas ambil</label>
                        <input id="batas_ambil" name="batas_ambil" type="datetime-local" required value="<?= old('batas_ambil', $defaultBatas) ?>">
                        <?php if (isset($errors['batas_ambil'])) : ?><span class="field__error"><?= e($errors['batas_ambil']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="field <?= isset($errors['kondisi']) ? 'field--error' : '' ?>">
                    <label for="kondisi">Kondisi <span class="hint">(opsional)</span></label>
                    <input id="kondisi" name="kondisi" value="<?= old('kondisi') ?>" maxlength="100" placeholder="Dimasak hari ini, masih layak konsumsi">
                    <?php if (isset($errors['kondisi'])) : ?><span class="field__error"><?= e($errors['kondisi']) ?></span><?php endif; ?>
                </div>
                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Bagikan</button>
                    <a class="btn btn--outline" href="<?= BASE ?>/pangan/index.php">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
