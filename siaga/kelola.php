<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

$title = 'Kelola Titik Kumpul';
$rwId = $u['role'] === 'admin' ? null : (int)$u['rw_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = (string)($_POST['aksi'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus') {
        $sql = 'DELETE FROM shelters WHERE id = ?';
        $params = [$id];
        if ($rwId !== null) {
            $sql .= ' AND rw_id = ?';
            $params[] = $rwId;
        }
        $pdo->prepare($sql)->execute($params);
        flash('sukses', 'Titik kumpul dihapus.');
        redirect(BASE . '/siaga/kelola.php');
    }

    $nama = trim((string)($_POST['nama'] ?? ''));
    $alamat = trim((string)($_POST['alamat_teks'] ?? ''));
    $kapasitas = (int)($_POST['kapasitas'] ?? 0);
    $kontakNama = trim((string)($_POST['kontak_nama'] ?? ''));
    $kontakHp = trim((string)($_POST['kontak_hp'] ?? ''));
    $targetRw = $rwId ?? (int)($_POST['rw_id'] ?? 0);

    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 80) {
        $errors['nama'] = 'Nama 3–80 karakter.';
    }
    if (mb_strlen($alamat) < 5 || mb_strlen($alamat) > 150) {
        $errors['alamat_teks'] = 'Alamat 5–150 karakter.';
    }
    if ($kapasitas < 1) {
        $errors['kapasitas'] = 'Kapasitas harus lebih dari 0.';
    }
    if (mb_strlen($kontakNama) < 3 || mb_strlen($kontakNama) > 80) {
        $errors['kontak_nama'] = 'Nama kontak 3–80 karakter.';
    }
    if (!preg_match('/^[0-9]{9,15}$/', $kontakHp)) {
        $errors['kontak_hp'] = 'Kontak HP 9–15 digit angka.';
    }
    if ($rwId === null && !$targetRw) {
        $errors['rw_id'] = 'Pilih RW.';
    }

    if (!$errors) {
        if ($id) {
            $sql = 'UPDATE shelters SET rw_id = ?, nama = ?, alamat_teks = ?, kapasitas = ?, kontak_nama = ?, kontak_hp = ? WHERE id = ?';
            $params = [$targetRw, $nama, $alamat, $kapasitas, $kontakNama, $kontakHp, $id];
            if ($rwId !== null) {
                $sql .= ' AND rw_id = ?';
                $params[] = $rwId;
            }
            $pdo->prepare($sql)->execute($params);
            flash('sukses', 'Titik kumpul diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO shelters (rw_id, nama, alamat_teks, kapasitas, kontak_nama, kontak_hp) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$targetRw, $nama, $alamat, $kapasitas, $kontakNama, $kontakHp]);
            flash('sukses', 'Titik kumpul ditambahkan.');
        }
        redirect(BASE . '/siaga/kelola.php');
    }
}

$sql = 'SELECT s.*, rw.nama AS rw FROM shelters s JOIN rw ON rw.id = s.rw_id';
$params = [];
if ($rwId !== null) {
    $sql .= ' WHERE s.rw_id = ?';
    $params[] = $rwId;
}
$sql .= ' ORDER BY rw.nama, s.nama';
$st = $pdo->prepare($sql);
$st->execute($params);
$data = $st->fetchAll();
$rwList = $pdo->query('SELECT id, nama FROM rw ORDER BY nama')->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Siaga · Petugas</p>
                <h1>Kelola Titik Kumpul</h1>
                <p class="small"><?= $rwId !== null ? 'Hanya titik kumpul RW Anda.' : 'Semua RW.' ?></p>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/siaga/ringkasan.php">Ringkasan</a>
        </div>

        <?php if ($errors) : ?>
            <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
        <?php endif; ?>

        <div class="dash-grid">
            <div>
                <div class="panel">
                    <h3>Titik Kumpul Terdaftar</h3>
                    <?php if ($data) : ?>
                        <div class="table-wrap">
                            <table class="data">
                                <thead><tr><th>Nama</th><th>RW</th><th>Kapasitas</th><th>Kontak</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($data as $s) : ?>
                                    <tr>
                                        <td><?= e($s['nama']) ?><br><span class="small muted"><?= e($s['alamat_teks']) ?></span></td>
                                        <td><?= e($s['rw']) ?></td>
                                        <td><?= (int)$s['kapasitas'] ?></td>
                                        <td><?= e($s['kontak_nama']) ?><br><span class="small muted"><?= e($s['kontak_hp']) ?></span></td>
                                        <td>
                                            <form method="post" onsubmit="return confirm('Hapus titik kumpul ini?')">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="aksi" value="hapus">
                                                <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                                                <button class="btn btn--danger btn--sm" type="submit">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="empty"><b>Belum Ada Titik Kumpul</b>Tambahkan lewat formulir di samping.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="panel">
                    <h3>Tambah Titik Kumpul</h3>
                    <form method="post" data-busy>
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="simpan">
                        <?php if ($rwId === null) : ?>
                            <div class="field <?= isset($errors['rw_id']) ? 'field--error' : '' ?>">
                                <label for="rw_id">RW</label>
                                <select id="rw_id" name="rw_id" required>
                                    <option value="">Pilih RW</option>
                                    <?php foreach ($rwList as $r) : ?>
                                        <option value="<?= (int)$r['id'] ?>"><?= e($r['nama']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['rw_id'])) : ?><span class="field__error"><?= e($errors['rw_id']) ?></span><?php endif; ?>
                            </div>
                        <?php endif; ?>
                        <div class="field <?= isset($errors['nama']) ? 'field--error' : '' ?>">
                            <label for="nama">Nama</label>
                            <input id="nama" name="nama" value="<?= old('nama') ?>" required minlength="3" maxlength="80">
                            <?php if (isset($errors['nama'])) : ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
                        </div>
                        <div class="field <?= isset($errors['alamat_teks']) ? 'field--error' : '' ?>">
                            <label for="alamat_teks">Alamat</label>
                            <input id="alamat_teks" name="alamat_teks" value="<?= old('alamat_teks') ?>" required minlength="5" maxlength="150">
                            <?php if (isset($errors['alamat_teks'])) : ?><span class="field__error"><?= e($errors['alamat_teks']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-grid">
                            <div class="field <?= isset($errors['kapasitas']) ? 'field--error' : '' ?>">
                                <label for="kapasitas">Kapasitas</label>
                                <input id="kapasitas" name="kapasitas" type="number" min="1" required value="<?= old('kapasitas', '50') ?>">
                                <?php if (isset($errors['kapasitas'])) : ?><span class="field__error"><?= e($errors['kapasitas']) ?></span><?php endif; ?>
                            </div>
                            <div class="field <?= isset($errors['kontak_hp']) ? 'field--error' : '' ?>">
                                <label for="kontak_hp">HP Kontak</label>
                                <input id="kontak_hp" name="kontak_hp" type="tel" inputmode="numeric" required maxlength="15" value="<?= old('kontak_hp') ?>">
                                <?php if (isset($errors['kontak_hp'])) : ?><span class="field__error"><?= e($errors['kontak_hp']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <div class="field <?= isset($errors['kontak_nama']) ? 'field--error' : '' ?>">
                            <label for="kontak_nama">Nama Kontak</label>
                            <input id="kontak_nama" name="kontak_nama" value="<?= old('kontak_nama') ?>" required minlength="3" maxlength="80">
                            <?php if (isset($errors['kontak_nama'])) : ?><span class="field__error"><?= e($errors['kontak_nama']) ?></span><?php endif; ?>
                        </div>
                        <button class="btn btn--primary" type="submit">Tambah</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
