<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('admin');

$title = 'Pengaturan';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $batas = (int)($_POST['garis_batas_per_kapita'] ?? 0);
    $ambang = (float)str_replace(',', '.', (string)($_POST['ambang_dipantau_kali'] ?? '0'));

    if ($batas < 1) {
        $errors['garis_batas_per_kapita'] = 'Garis batas harus lebih dari 0.';
    }
    if ($ambang < 1.0 || $ambang > 3.0) {
        $errors['ambang_dipantau_kali'] = 'Ambang 1,0–3,0 kali garis batas.';
    }

    if (!$errors) {
        $st = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)');
        $st->execute(['garis_batas_per_kapita', (string)$batas]);
        $st->execute(['ambang_dipantau_kali', (string)$ambang]);
        flash('sukses', 'Pengaturan disimpan. Cek kondisi dan beranda langsung memakai nilai baru.');
        redirect(BASE . '/admin/pengaturan.php');
    }
}

$batas = (int)setting('garis_batas_per_kapita');
$ambang = setting('ambang_dipantau_kali', '1.5');
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Administrasi</p>
                <h1>Pengaturan layanan.</h1>
                <p class="small">Nilai ini dipakai fitur cek kondisi keluarga dan beranda.</p>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/dashboard/admin.php">Dashboard</a>
        </div>

        <?php if ($errors) : ?>
            <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
        <?php endif; ?>

        <div class="form-card form-card--narrow">
            <form method="post" data-busy>
                <?= csrf_field() ?>
                <div class="field <?= isset($errors['garis_batas_per_kapita']) ? 'field--error' : '' ?>">
                    <label for="garis_batas_per_kapita">Garis batas per kapita (Rp)</label>
                    <input id="garis_batas_per_kapita" name="garis_batas_per_kapita" type="number" min="1" required value="<?= old('garis_batas_per_kapita', (string)$batas) ?>">
                    <span class="hint">Contoh BPS, bukan penetapan resmi.</span>
                    <?php if (isset($errors['garis_batas_per_kapita'])) : ?><span class="field__error"><?= e($errors['garis_batas_per_kapita']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['ambang_dipantau_kali']) ? 'field--error' : '' ?>">
                    <label for="ambang_dipantau_kali">Ambang "perlu dipantau" (kali garis batas)</label>
                    <input id="ambang_dipantau_kali" name="ambang_dipantau_kali" type="text" inputmode="decimal" required value="<?= old('ambang_dipantau_kali', $ambang) ?>">
                    <span class="hint">Antara 1,0 dan 3,0. Contoh: 1,5.</span>
                    <?php if (isset($errors['ambang_dipantau_kali'])) : ?><span class="field__error"><?= e($errors['ambang_dipantau_kali']) ?></span><?php endif; ?>
                </div>
                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Simpan pengaturan</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
