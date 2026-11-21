<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_login();

$title = 'Profil';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama = trim((string)($_POST['nama'] ?? ''));
    $noHp = trim((string)($_POST['no_hp'] ?? ''));
    $lama = (string)($_POST['sandi_lama'] ?? '');
    $baru = (string)($_POST['sandi_baru'] ?? '');
    $baru2 = (string)($_POST['sandi_baru2'] ?? '');

    if (!preg_match('/^[A-Za-z ]{3,80}$/', $nama)) {
        $errors['nama'] = 'Nama 3–80 karakter, hanya huruf dan spasi.';
    }
    if ($noHp !== '' && !preg_match('/^[0-9]{9,15}$/', $noHp)) {
        $errors['no_hp'] = 'Nomor HP 9–15 digit angka.';
    }

    $gantiSandi = $baru !== '' || $baru2 !== '' || $lama !== '';
    if ($gantiSandi) {
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$u['id']]);
        if (!password_verify($lama, (string)$st->fetchColumn())) {
            $errors['sandi_lama'] = 'Sandi saat ini tidak cocok.';
        }
        if (strlen($baru) < 8 || !preg_match('/[A-Za-z]/', $baru) || !preg_match('/\d/', $baru)) {
            $errors['sandi_baru'] = 'Sandi baru minimal 8 karakter, huruf dan angka.';
        } elseif ($baru !== $baru2) {
            $errors['sandi_baru2'] = 'Ulangi sandi baru tidak sama.';
        }
    }

    if (!$errors) {
        $foto = $u['foto'] ?? null;
        if (!empty($_FILES['foto']['name'])) {
            $err = null;
            $upload = upload_foto($_FILES['foto'], $err);
            if ($err) {
                $errors['foto'] = $err;
            } else {
                $foto = $upload;
            }
        }
    }

    if (!$errors) {
        if ($gantiSandi) {
            $pdo->prepare('UPDATE users SET nama = ?, no_hp = ?, foto = ?, password_hash = ? WHERE id = ?')
                ->execute([$nama, $noHp ?: null, $foto, password_hash($baru, PASSWORD_DEFAULT), $u['id']]);
        } else {
            $pdo->prepare('UPDATE users SET nama = ?, no_hp = ?, foto = ? WHERE id = ?')
                ->execute([$nama, $noHp ?: null, $foto, $u['id']]);
        }
        $_SESSION['user']['nama'] = $nama;
        $_SESSION['user']['foto'] = $foto;
        flash('sukses', 'Profil diperbarui.');
        redirect(BASE . '/auth/profil.php');
    }
}

$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$u['id']]);
$me = $st->fetch();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="form-page">
    <div class="container">
        <div class="form-card form-card--narrow">
            <h1>Profil saya</h1>
            <p class="small">Data ini dipakai untuk menghubungi Anda terkait layanan RW.</p>

            <?php if ($errors) : ?>
                <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" data-busy>
                <?= csrf_field() ?>
                <div class="field <?= isset($errors['nama']) ? 'field--error' : '' ?>">
                    <label for="nama">Nama lengkap</label>
                    <input id="nama" name="nama" value="<?= old('nama', $me['nama']) ?>" required minlength="3" maxlength="80" pattern="[A-Za-z ]{3,80}">
                    <?php if (isset($errors['nama'])) : ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
                </div>
                <div class="field">
                    <label>Email</label>
                    <input value="<?= e($me['email']) ?>" readonly>
                    <span class="hint">Email tidak dapat diubah sendiri. Hubungi admin bila perlu.</span>
                </div>
                <div class="field <?= isset($errors['no_hp']) ? 'field--error' : '' ?>">
                    <label for="no_hp">Nomor HP</label>
                    <input id="no_hp" name="no_hp" type="tel" inputmode="numeric" value="<?= old('no_hp', (string)$me['no_hp']) ?>" maxlength="15">
                    <?php if (isset($errors['no_hp'])) : ?><span class="field__error"><?= e($errors['no_hp']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['foto']) ? 'field--error' : '' ?>">
                    <label for="foto">Foto profil <span class="hint">(opsional, JPG/PNG maks. 2 MB)</span></label>
                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png">
                    <?php if (isset($errors['foto'])) : ?><span class="field__error"><?= e($errors['foto']) ?></span><?php endif; ?>
                </div>

                <h3 class="mt-3">Ganti sandi</h3>
                <p class="small">Biarkan kosong bila tidak ingin mengganti sandi.</p>
                <div class="field <?= isset($errors['sandi_lama']) ? 'field--error' : '' ?>">
                    <label for="sandi_lama">Sandi saat ini</label>
                    <input id="sandi_lama" name="sandi_lama" type="password" autocomplete="current-password">
                    <?php if (isset($errors['sandi_lama'])) : ?><span class="field__error"><?= e($errors['sandi_lama']) ?></span><?php endif; ?>
                </div>
                <div class="form-grid">
                    <div class="field <?= isset($errors['sandi_baru']) ? 'field--error' : '' ?>">
                        <label for="sandi_baru">Sandi baru</label>
                        <input id="sandi_baru" name="sandi_baru" type="password" autocomplete="new-password">
                        <?php if (isset($errors['sandi_baru'])) : ?><span class="field__error"><?= e($errors['sandi_baru']) ?></span><?php endif; ?>
                    </div>
                    <div class="field <?= isset($errors['sandi_baru2']) ? 'field--error' : '' ?>">
                        <label for="sandi_baru2">Ulangi sandi baru</label>
                        <input id="sandi_baru2" name="sandi_baru2" type="password" autocomplete="new-password">
                        <?php if (isset($errors['sandi_baru2'])) : ?><span class="field__error"><?= e($errors['sandi_baru2']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Simpan perubahan</button>
                    <a class="btn btn--outline" href="<?= BASE ?>/dashboard/<?= e($u['role']) ?>.php">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
