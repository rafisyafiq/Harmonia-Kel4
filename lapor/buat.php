<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

$title = 'Buat laporan';
$kategoriList = ['lampu' => 'Lampu jalan', 'selokan' => 'Selokan', 'sampah' => 'Sampah', 'jalan' => 'Jalan', 'lainnya' => 'Lainnya'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $kategori = (string)($_POST['kategori'] ?? '');
    $judul = trim((string)($_POST['judul'] ?? ''));
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $lokasi = trim((string)($_POST['lokasi_teks'] ?? ''));
    $foto = null;

    if (!isset($kategoriList[$kategori])) {
        $errors['kategori'] = 'Pilih kategori laporan.';
    }
    if (mb_strlen($judul) < 5 || mb_strlen($judul) > 100) {
        $errors['judul'] = 'Judul 5–100 karakter.';
    }
    if (mb_strlen($deskripsi) < 20 || mb_strlen($deskripsi) > 1000) {
        $errors['deskripsi'] = 'Deskripsi 20–1000 karakter.';
    }
    if (mb_strlen($lokasi) < 5 || mb_strlen($lokasi) > 150) {
        $errors['lokasi_teks'] = 'Lokasi 5–150 karakter.';
    }
    if (!empty($_FILES['foto']['name'])) {
        $err = null;
        $foto = upload_foto($_FILES['foto'], $err);
        if ($err) {
            $errors['foto'] = $err;
        }
    }

    if (!$errors) {
        $mirip = 0;
        if ($kategori && $lokasi) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM reports
                WHERE kategori = ? AND lokasi_teks LIKE ? AND created_at >= NOW() - INTERVAL 7 DAY");
            $st->execute([$kategori, '%' . $lokasi . '%']);
            $mirip = (int)$st->fetchColumn();
        }
        $st = $pdo->prepare('INSERT INTO reports (user_id, rw_id, kategori, judul, deskripsi, lokasi_teks, foto) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([$u['id'], $u['rw_id'], $kategori, $judul, $deskripsi, $lokasi, $foto]);
        $id = (int)$pdo->lastInsertId();
        log_status($pdo, 'report', $id, 'baru', null, (int)$u['id']);

        if ($mirip > 0) {
            flash('info', 'Laporan serupa di lokasi yang sama sudah ada. Laporan Anda tetap dikirim; petugas akan menggabungkan bila perlu.');
        }
        flash('sukses', 'Laporan terkirim. Kode Anda: ' . kode_laporan($id) . '. Simpan kode ini untuk melacak status.');
        redirect(BASE . '/lapor/detail.php?id=' . $id);
    }
}
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="form-page">
    <div class="container">
        <div class="form-card form-card--narrow">
            <h1>Buat laporan.</h1>
            <p class="small">Laporan tampil di halaman publik tanpa nama pelapor. RW otomatis diambil dari akun Anda (<?= e($u['rw_id'] ? 'RW ' . str_pad((string)$u['rw_id'], 2, '0', STR_PAD_LEFT) : '-') ?>).</p>

            <?php if ($errors) : ?>
                <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" data-busy>
                <?= csrf_field() ?>
                <div class="field <?= isset($errors['kategori']) ? 'field--error' : '' ?>">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($kategoriList as $k => $label) : ?>
                            <option value="<?= $k ?>" <?= ($_POST['kategori'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['kategori'])) : ?><span class="field__error"><?= e($errors['kategori']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['judul']) ? 'field--error' : '' ?>">
                    <label for="judul">Judul</label>
                    <input id="judul" name="judul" value="<?= old('judul') ?>" required minlength="5" maxlength="100">
                    <?php if (isset($errors['judul'])) : ?><span class="field__error"><?= e($errors['judul']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['deskripsi']) ? 'field--error' : '' ?>">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" required minlength="20" maxlength="1000" data-counter="hitung-deskripsi"><?= old('deskripsi') ?></textarea>
                    <span class="field__count" id="hitung-deskripsi">0 karakter</span>
                    <?php if (isset($errors['deskripsi'])) : ?><span class="field__error"><?= e($errors['deskripsi']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['lokasi_teks']) ? 'field--error' : '' ?>">
                    <label for="lokasi_teks">Lokasi</label>
                    <input id="lokasi_teks" name="lokasi_teks" value="<?= old('lokasi_teks') ?>" required minlength="5" maxlength="150" placeholder="Jl. Melati RT 02/RW 05">
                    <?php if (isset($errors['lokasi_teks'])) : ?><span class="field__error"><?= e($errors['lokasi_teks']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['foto']) ? 'field--error' : '' ?>">
                    <label for="foto">Foto <span class="hint">(opsional, JPG/PNG maks. 2 MB)</span></label>
                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png">
                    <?php if (isset($errors['foto'])) : ?><span class="field__error"><?= e($errors['foto']) ?></span><?php endif; ?>
                </div>

                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Kirim laporan</button>
                    <a class="btn btn--outline" href="<?= BASE ?>/lapor/index.php">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
