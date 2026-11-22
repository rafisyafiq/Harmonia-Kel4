<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('admin');

$title = 'Harga Sampah';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = (string)($_POST['aksi'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($aksi === 'hapus') {
        $st = $pdo->prepare('SELECT COUNT(*) FROM waste_deposits WHERE waste_type_id = ?');
        $st->execute([$id]);
        if ((int)$st->fetchColumn() > 0) {
            flash('error', 'Jenis sampah tidak dapat dihapus karena sudah punya setoran.');
            redirect(BASE . '/admin/harga-sampah.php');
        }
        $pdo->prepare('DELETE FROM waste_types WHERE id = ?')->execute([$id]);
        flash('sukses', 'Jenis sampah dihapus.');
        redirect(BASE . '/admin/harga-sampah.php');
    }

    $nama = trim((string)($_POST['nama'] ?? ''));
    $harga = (int)($_POST['harga_per_kg'] ?? 0);
    if (mb_strlen($nama) < 2 || mb_strlen($nama) > 40) {
        $errors['nama'] = 'Nama jenis 2–40 karakter.';
    }
    if ($harga < 1) {
        $errors['harga_per_kg'] = 'Harga harus lebih dari 0.';
    }
    if (!$errors) {
        try {
            if ($id) {
                $pdo->prepare('UPDATE waste_types SET nama = ?, harga_per_kg = ? WHERE id = ?')->execute([$nama, $harga, $id]);
                flash('sukses', 'Harga diperbarui.');
            } else {
                $pdo->prepare('INSERT INTO waste_types (nama, harga_per_kg) VALUES (?, ?)')->execute([$nama, $harga]);
                flash('sukses', 'Jenis sampah ditambahkan.');
            }
        } catch (PDOException $e) {
            flash('error', 'Nama jenis sampah sudah ada.');
        }
        redirect(BASE . '/admin/harga-sampah.php');
    }
}

$data = $pdo->query('SELECT wt.*, (SELECT COUNT(*) FROM waste_deposits d WHERE d.waste_type_id = wt.id) AS setoran
    FROM waste_types wt ORDER BY wt.nama')->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Administrasi</p>
                <h1>Harga Bank Sampah</h1>
                <p class="small">Perubahan harga hanya berlaku untuk setoran baru. Setoran lama menyimpan nilainya.</p>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/dashboard/admin.php">Dashboard</a>
        </div>

        <?php if ($errors) : ?>
            <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
        <?php endif; ?>

        <div class="panel">
            <h3>Tambah Jenis</h3>
            <form method="post" class="form-grid" data-busy>
                <?= csrf_field() ?>
                <div class="field <?= isset($errors['nama']) ? 'field--error' : '' ?>">
                    <label for="nama">Nama Jenis</label>
                    <input id="nama" name="nama" required minlength="2" maxlength="40" value="<?= old('nama') ?>">
                    <?php if (isset($errors['nama'])) : ?><span class="field__error"><?= e($errors['nama']) ?></span><?php endif; ?>
                </div>
                <div class="field <?= isset($errors['harga_per_kg']) ? 'field--error' : '' ?>">
                    <label for="harga_per_kg">Harga per kg (Rp)</label>
                    <input id="harga_per_kg" name="harga_per_kg" type="number" min="1" required value="<?= old('harga_per_kg') ?>">
                    <?php if (isset($errors['harga_per_kg'])) : ?><span class="field__error"><?= e($errors['harga_per_kg']) ?></span><?php endif; ?>
                </div>
                <div class="field field--full">
                    <button class="btn btn--primary" type="submit">Tambah</button>
                </div>
            </form>
        </div>

        <div class="table-wrap mt-3">
            <table class="data">
                <thead><tr><th>Jenis</th><th>Harga/Kg</th><th>Setoran</th><th>Aksi</th></tr></thead>
                <tbody>
                <?php foreach ($data as $d) : ?>
                    <tr>
                        <td colspan="2">
                            <form method="post" class="form-actions" style="margin:0">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                                <input name="nama" value="<?= e($d['nama']) ?>" aria-label="Nama jenis" required maxlength="40" style="max-width:180px">
                                <input name="harga_per_kg" type="number" min="1" value="<?= (int)$d['harga_per_kg'] ?>" aria-label="Harga per kg" required style="max-width:140px">
                        </td>
                        <td><?= (int)$d['setoran'] ?></td>
                        <td>
                                <button class="btn btn--ghost btn--sm" type="submit">Simpan</button>
                                <button class="btn btn--danger btn--sm" type="submit" name="aksi" value="hapus"
                                        onclick="return confirm('Hapus jenis ini?')">Hapus</button>
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
