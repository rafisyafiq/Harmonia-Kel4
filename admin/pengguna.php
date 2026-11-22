<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('admin');

$title = 'Kelola Pengguna';
$errors = [];
$rwList = $pdo->query('SELECT id, nama FROM rw ORDER BY nama')->fetchAll();
$roleList = ['warga', 'petugas', 'admin'];
$sandiBaru = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = (string)($_POST['aksi'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'tambah' || $aksi === 'update') {
        $nama = trim((string)($_POST['nama'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $role = (string)($_POST['role'] ?? '');
        $rwId = (int)($_POST['rw_id'] ?? 0) ?: null;

        if ($aksi === 'tambah') {
            if (mb_strlen($nama) < 3 || mb_strlen($nama) > 80) {
                $errors['nama'] = 'Nama 3–80 karakter.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 100) {
                $errors['email'] = 'Email tidak valid.';
            } else {
                $st = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
                $st->execute([$email]);
                if ($st->fetchColumn()) {
                    $errors['email'] = 'Email sudah dipakai.';
                }
            }
        }
        if (!in_array($role, $roleList, true)) {
            $errors['role'] = 'Peran tidak valid.';
        }
        if ($role === 'petugas' && !$rwId) {
            $errors['rw_id'] = 'Petugas wajib punya RW.';
        }
        if ($role === 'warga' && !$rwId) {
            $errors['rw_id'] = 'Warga wajib punya RW.';
        }

        if (!$errors) {
            if ($aksi === 'tambah') {
                $sandiBaru = 'Hrm' . random_int(100000, 999999);
                $st = $pdo->prepare('INSERT INTO users (nama, email, password_hash, role, rw_id) VALUES (?, ?, ?, ?, ?)');
                $st->execute([$nama, $email, password_hash($sandiBaru, PASSWORD_DEFAULT), $role, $rwId]);
                flash('sukses', 'Pengguna ditambahkan. Sandi sementara: ' . $sandiBaru . ' (catat sekarang, tidak ditampilkan lagi).');
            } else {
                $pdo->prepare('UPDATE users SET role = ?, rw_id = ? WHERE id = ?')->execute([$role, $rwId, $id]);
                flash('sukses', 'Peran dan RW pengguna diperbarui.');
            }
            redirect(BASE . '/admin/pengguna.php');
        }
    } elseif ($aksi === 'reset') {
        if ($id === (int)$u['id']) {
            flash('error', 'Gunakan halaman profil untuk mengganti sandi sendiri.');
            redirect(BASE . '/admin/pengguna.php');
        }
        $sandiBaru = 'Hrm' . random_int(100000, 999999);
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($sandiBaru, PASSWORD_DEFAULT), $id]);
        flash('sukses', 'Sandi direset. Sandi sementara: ' . $sandiBaru . ' (catat sekarang, tidak ditampilkan lagi).');
        redirect(BASE . '/admin/pengguna.php');
    } elseif ($aksi === 'hapus') {
        if ($id === (int)$u['id']) {
            flash('error', 'Anda tidak dapat menghapus akun sendiri.');
            redirect(BASE . '/admin/pengguna.php');
        }
        foreach (['reports', 'aid_requests', 'waste_deposits'] as $tabel) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM $tabel WHERE user_id = ?");
            $st->execute([$id]);
            if ((int)$st->fetchColumn() > 0) {
                flash('error', 'Pengguna tidak dapat dihapus karena masih punya data di ' . $tabel . '.');
                redirect(BASE . '/admin/pengguna.php');
            }
        }
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('sukses', 'Pengguna dihapus.');
        redirect(BASE . '/admin/pengguna.php');
    }
}

$q = trim((string)($_GET['q'] ?? ''));
$fRole = in_array($_GET['role'] ?? '', $roleList, true) ? $_GET['role'] : '';
$fRw = (int)($_GET['rw'] ?? 0);

$sql = 'SELECT us.*, rw.nama AS rw FROM users us LEFT JOIN rw ON rw.id = us.rw_id';
$where = [];
$params = [];
if ($q !== '') {
    $where[] = '(us.nama LIKE ? OR us.email LIKE ?)';
    $params[] = '%' . $q . '%';
    $params[] = '%' . $q . '%';
}
if ($fRole) {
    $where[] = 'us.role = ?';
    $params[] = $fRole;
}
if ($fRw) {
    $where[] = 'us.rw_id = ?';
    $params[] = $fRw;
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY us.role, us.nama LIMIT 200';
$st = $pdo->prepare($sql);
$st->execute($params);
$data = $st->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Administrasi</p>
                <h1>Kelola Pengguna</h1>
                <p class="small">Tambah petugas/admin, ubah peran dan RW, reset sandi, atau hapus pengguna tanpa data.</p>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/dashboard/admin.php">Dashboard</a>
        </div>

        <?php if ($errors) : ?>
            <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
        <?php endif; ?>

        <div class="panel">
            <h3>Tambah Pengguna</h3>
            <form method="post" class="form-grid" data-busy>
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="tambah">
                <div class="field <?= isset($errors['nama']) ? 'field--error' : '' ?>">
                    <label for="nama">Nama</label>
                    <input id="nama" name="nama" required minlength="3" maxlength="80" value="<?= old('nama') ?>">
                    <?php if (isset($errors['nama'])) : ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['email']) ? 'field--error' : '' ?>">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" required maxlength="100" value="<?= old('email') ?>">
                    <?php if (isset($errors['email'])) : ?><span class="field__error"><?= e($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['role']) ? 'field--error' : '' ?>">
                    <label for="role">Peran</label>
                    <select id="role" name="role" required>
                        <?php foreach ($roleList as $r) : ?>
                            <option value="<?= $r ?>" <?= ($_POST['role'] ?? '') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['role'])) : ?><span class="field__error"><?= e($errors['role']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['rw_id']) ? 'field--error' : '' ?>">
                    <label for="rw_id">RW</label>
                    <select id="rw_id" name="rw_id">
                        <option value="">— tanpa RW —</option>
                        <?php foreach ($rwList as $r) : ?>
                            <option value="<?= (int)$r['id'] ?>" <?= (int)($_POST['rw_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['rw_id'])) : ?><span class="field__error"><?= e($errors['rw_id']) ?></span><?php endif; ?>
                </div>
                <div class="field field--full">
                    <button class="btn btn--primary" type="submit">Tambah Pengguna</button>
                </div>
            </form>
        </div>

        <form method="get" class="form-card mt-3" style="padding:1rem">
            <div class="form-grid">
                <div class="field" style="margin-bottom:0">
                    <label for="q">Cari Nama/Email</label>
                    <input id="q" name="q" value="<?= e($q) ?>">
                </div>
                <div class="field" style="margin-bottom:0">
                    <label for="fr">Peran</label>
                    <select id="fr" name="role" onchange="this.form.submit()">
                        <option value="">Semua Peran</option>
                        <?php foreach ($roleList as $r) : ?>
                            <option value="<?= $r ?>" <?= $fRole === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0">
                    <label for="frw">RW</label>
                    <select id="frw" name="rw" onchange="this.form.submit()">
                        <option value="">Semua RW</option>
                        <?php foreach ($rwList as $r) : ?>
                            <option value="<?= (int)$r['id'] ?>" <?= $fRw === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <noscript><button class="btn btn--outline btn--sm mt-2" type="submit">Terapkan</button></noscript>
        </form>

        <div class="table-wrap table-wrap--scroll mt-3">
            <table class="data">
                <thead><tr><th>Nama</th><th>Email</th><th>Peran</th><th>RW</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($data as $us) : ?>
                    <tr>
                        <td><?= e($us['nama']) ?><?= (int)$us['id'] === (int)$u['id'] ? ' <span class="chip chip--accent">Anda</span>' : '' ?></td>
                        <td><?= e($us['email']) ?></td>
                        <td colspan="3">
                            <form method="post" class="form-actions" style="margin:0;flex-wrap:wrap">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="update">
                                <input type="hidden" name="id" value="<?= (int)$us['id'] ?>">
                                <select name="role" aria-label="Peran <?= e($us['nama']) ?>">
                                    <?php foreach ($roleList as $r) : ?>
                                        <option value="<?= $r ?>" <?= $us['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <select name="rw_id" aria-label="RW <?= e($us['nama']) ?>">
                                    <option value="">— tanpa RW —</option>
                                    <?php foreach ($rwList as $r) : ?>
                                        <option value="<?= (int)$r['id'] ?>" <?= (int)$us['rw_id'] === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn--ghost btn--sm" type="submit">Simpan</button>
                                <button class="btn btn--outline btn--sm" type="submit" name="aksi" value="reset" formnovalidate>Reset Sandi</button>
                                <button class="btn btn--danger btn--sm" type="submit" name="aksi" value="hapus" formnovalidate
                                        onclick="return confirm('Hapus pengguna ini?')">Hapus</button>
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
