<?php
require_once __DIR__ . '/../config/auth.php';

if (user()) {
    redirect(BASE . '/dashboard/' . user()['role'] . '.php');
}

$title = 'Daftar';
$errors = [];
$rwList = $pdo->query('SELECT id, nama, kelurahan FROM rw ORDER BY nama')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama = trim((string)($_POST['nama'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $sandi = (string)($_POST['sandi'] ?? '');
    $sandi2 = (string)($_POST['sandi2'] ?? '');
    $rwId = (int)($_POST['rw_id'] ?? 0);
    $noHp = trim((string)($_POST['no_hp'] ?? ''));

    if (!preg_match('/^[A-Za-z ]{3,80}$/', $nama)) {
        $errors['nama'] = 'Nama 3–80 karakter, hanya huruf dan spasi.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
        $errors['email'] = 'Masukkan alamat email yang valid.';
    } elseif (strlen($email) > 0) {
        $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetchColumn()) {
            $errors['email'] = 'Email ini sudah terdaftar. Silakan masuk.';
        }
    }
    if (strlen($sandi) < 8 || !preg_match('/[A-Za-z]/', $sandi) || !preg_match('/\d/', $sandi)) {
        $errors['sandi'] = 'Sandi minimal 8 karakter dan memuat huruf serta angka.';
    } elseif ($sandi !== $sandi2) {
        $errors['sandi2'] = 'Ulangi sandi tidak sama.';
    }
    if (!array_filter($rwList, fn($r) => (int)$r['id'] === $rwId)) {
        $errors['rw_id'] = 'Pilih RW tempat tinggal.';
    }
    if ($noHp !== '' && !preg_match('/^[0-9]{9,15}$/', $noHp)) {
        $errors['no_hp'] = 'Nomor HP 9–15 digit angka.';
    }

    if (!$errors) {
        $st = $pdo->prepare('INSERT INTO users (nama, email, password_hash, role, rw_id, no_hp) VALUES (?, ?, ?, ?, ?, ?)');
        $st->execute([$nama, $email, password_hash($sandi, PASSWORD_DEFAULT), 'warga', $rwId, $noHp ?: null]);

        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$pdo->lastInsertId(), 'nama' => $nama, 'role' => 'warga', 'rw_id' => $rwId];
        $_SESSION['last_activity'] = time();
        flash('sukses', 'Akun berhasil dibuat. Selamat datang, ' . $nama . '.');
        redirect(BASE . '/dashboard/warga.php');
    }
}
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="auth">
    <div class="container auth__grid">
        <div class="auth__aside">
            <p class="eyebrow eyebrow--light">Akun warga</p>
            <h2>Layanan RW dalam satu akun.</h2>
            <p>Akun warga dipakai untuk mengajukan bantuan, melapor, mengusulkan, dan mencatat setoran sampah. Data kondisi keluarga hanya terlihat oleh Anda dan petugas RW.</p>
            <ul>
                <li>Status pengajuan dan laporan bisa dipantau kapan saja.</li>
                <li>Riwayat setoran sampah dan saldo tersimpan rapi.</li>
                <li>Lapak usaha warga dapat didaftarkan setelah akun dibuat.</li>
            </ul>
        </div>

        <form class="form-card" method="post" novalidate data-busy>
            <?= csrf_field() ?>
            <h2>Daftar akun</h2>
            <p class="small">Sudah punya akun? <a href="<?= BASE ?>/auth/login.php">Masuk di sini</a>.</p>

            <?php if ($errors) : ?>
                <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
            <?php endif; ?>

            <div class="field <?= isset($errors['nama']) ? 'field--error' : '' ?>">
                <label for="nama">Nama lengkap</label>
                <input id="nama" name="nama" value="<?= old('nama') ?>" autocomplete="name" required minlength="3" maxlength="80" pattern="[A-Za-z ]{3,80}">
                <?php if (isset($errors['nama'])) : ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
            </div>

            <div class="field <?= isset($errors['email']) ? 'field--error' : '' ?>">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required maxlength="100">
                <?php if (isset($errors['email'])) : ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
            </div>

            <div class="form-grid">
                <div class="field <?= isset($errors['sandi']) ? 'field--error' : '' ?>">
                    <label for="sandi">Sandi</label>
                    <input id="sandi" name="sandi" type="password" autocomplete="new-password" required minlength="8">
                    <span class="hint">Minimal 8 karakter, huruf dan angka.</span>
                    <?php if (isset($errors['sandi'])) : ?><span class="field__error"><?= e($errors['sandi']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['sandi2']) ? 'field--error' : '' ?>">
                    <label for="sandi2">Ulangi sandi</label>
                    <input id="sandi2" name="sandi2" type="password" autocomplete="new-password" required>
                    <?php if (isset($errors['sandi2'])) : ?><span class="field__error"><?= e($errors['sandi2']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-grid">
                <div class="field <?= isset($errors['rw_id']) ? 'field--error' : '' ?>">
                    <label for="rw_id">RW tempat tinggal</label>
                    <select id="rw_id" name="rw_id" required>
                        <option value="">Pilih RW</option>
                        <?php foreach ($rwList as $r) : ?>
                            <option value="<?= (int)$r['id'] ?>" <?= (int)($_POST['rw_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>>
                                <?= e($r['nama']) ?> · <?= e($r['kelurahan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['rw_id'])) : ?><span class="field__error"><?= e($errors['rw_id']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['no_hp']) ? 'field--error' : '' ?>">
                    <label for="no_hp">Nomor HP <span class="hint">(opsional)</span></label>
                    <input id="no_hp" name="no_hp" type="tel" inputmode="numeric" value="<?= old('no_hp') ?>" maxlength="15">
                    <?php if (isset($errors['no_hp'])) : ?><span class="field__error"><?= e($errors['no_hp']) ?></span><?php endif; ?>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Buat akun</button>
                <a class="btn btn--outline" href="<?= BASE ?>/index.php">Kembali</a>
            </div>
        </form>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
