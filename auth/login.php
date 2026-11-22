<?php
require_once __DIR__ . '/../config/auth.php';

if (user()) {
    redirect(BASE . '/dashboard/' . user()['role'] . '.php');
}

$title = 'Masuk';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim((string)($_POST['email'] ?? ''));
    $sandi = (string)($_POST['sandi'] ?? '');

    if ($email === '' || $sandi === '') {
        $error = 'Email dan sandi wajib diisi.';
    } else {
        $st = $pdo->prepare('SELECT id, nama, password_hash, role, rw_id FROM users WHERE email = ?');
        $st->execute([$email]);
        $row = $st->fetch();
        if ($row && password_verify($sandi, $row['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$row['id'],
                'nama' => $row['nama'],
                'role' => $row['role'],
                'rw_id' => $row['rw_id'] !== null ? (int)$row['rw_id'] : null,
            ];
            $_SESSION['last_activity'] = time();
            flash('sukses', 'Selamat datang kembali, ' . $row['nama'] . '.');
            redirect(BASE . '/dashboard/' . $row['role'] . '.php');
        }
        $error = 'Email atau sandi salah.'; // pesan umum, tidak menyebut field
    }
}
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="auth">
    <div class="container auth__grid">
        <div class="auth__aside">
            <p class="eyebrow eyebrow--light">Masuk</p>
            <h2>Satu Akun, Semua Layanan RW</h2>
            <p>Gunakan akun warga untuk layanan pribadi. Akun petugas dan admin dibuat oleh admin RW.</p>
            <ul>
                <li>Warga: cek kondisi, ajukan bantuan, lapor, dan setor sampah.</li>
                <li>Petugas: proses antrean pengajuan dan laporan di RW-nya.</li>
                <li>Admin: kelola pengguna, harga sampah, dan pengaturan.</li>
            </ul>
        </div>

        <form class="form-card" method="post" data-busy>
            <?= csrf_field() ?>
            <h2>Masuk</h2>
            <p class="small">Belum punya akun? <a href="<?= BASE ?>/auth/register.php">Daftar sebagai Warga</a>.</p>

            <?php if ($error) : ?>
                <div class="alert alert--error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= old('email') ?>" autocomplete="email" required>
            </div>
            <div class="field">
                <label for="sandi">Sandi</label>
                <input id="sandi" name="sandi" type="password" autocomplete="current-password" required>
            </div>

            <div class="form-actions">
                <button class="btn btn--primary" type="submit">Masuk</button>
                <a class="btn btn--outline" href="<?= BASE ?>/index.php">Kembali</a>
            </div>
        </form>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
