<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

$title = 'Daftarkan usaha';
$kategoriList = ['makanan' => 'Makanan', 'jasa' => 'Jasa', 'kerajinan' => 'Kerajinan', 'pertanian' => 'Pertanian'];
$errors = [];
$editId = (int)($_GET['id'] ?? 0);
$lama = null;

if ($editId) {
    $st = $pdo->prepare('SELECT * FROM businesses WHERE id = ? AND user_id = ?');
    $st->execute([$editId, $u['id']]);
    $lama = $st->fetch();
    if (!$lama) {
        http_response_code(404);
        exit('Lapak tidak ditemukan atau bukan milik Anda.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $editId = (int)($_POST['id'] ?? 0);
    $nama = trim((string)($_POST['nama_usaha'] ?? ''));
    $kategori = (string)($_POST['kategori'] ?? '');
    $deskripsi = trim((string)($_POST['deskripsi'] ?? ''));
    $kontak = trim((string)($_POST['kontak'] ?? ''));
    $foto = $lama['foto'] ?? null;

    if ($editId) {
        $st = $pdo->prepare('SELECT * FROM businesses WHERE id = ? AND user_id = ?');
        $st->execute([$editId, $u['id']]);
        $lama = $st->fetch();
        if (!$lama) {
            http_response_code(404);
            exit('Lapak tidak ditemukan.');
        }
        $foto = $lama['foto'];
    }

    if (mb_strlen($nama) < 3 || mb_strlen($nama) > 80) {
        $errors['nama_usaha'] = 'Nama usaha 3–80 karakter.';
    }
    if (!isset($kategoriList[$kategori])) {
        $errors['kategori'] = 'Pilih kategori usaha.';
    }
    if (mb_strlen($deskripsi) < 20 || mb_strlen($deskripsi) > 500) {
        $errors['deskripsi'] = 'Deskripsi 20–500 karakter.';
    }
    if (!preg_match('/^[0-9]{9,15}$/', $kontak)) {
        $errors['kontak'] = 'Kontak 9–15 digit angka.';
    }
    if (!empty($_FILES['foto']['name'])) {
        $err = null;
        $upload = upload_foto($_FILES['foto'], $err);
        if ($err) {
            $errors['foto'] = $err;
        } else {
            $foto = $upload;
        }
    }
    if (!$foto) {
        $errors['foto'] = 'Foto usaha wajib diunggah.';
    }

    if (!$errors) {
        if ($editId) {
            $st = $pdo->prepare("UPDATE businesses SET nama_usaha = ?, kategori = ?, deskripsi = ?, kontak = ?, foto = ?, status = 'menunggu', alasan_tolak = NULL WHERE id = ? AND user_id = ?");
            $st->execute([$nama, $kategori, $deskripsi, $kontak, $foto, $editId, $u['id']]);
            flash('sukses', 'Perubahan disimpan. Lapak menunggu verifikasi ulang admin.');
        } else {
            $st = $pdo->prepare("INSERT INTO businesses (user_id, nama_usaha, kategori, deskripsi, foto, kontak) VALUES (?, ?, ?, ?, ?, ?)");
            $st->execute([$u['id'], $nama, $kategori, $deskripsi, $foto, $kontak]);
            flash('sukses', 'Lapak didaftarkan. Admin akan memverifikasi sebelum tampil.');
        }
        redirect(BASE . '/lapak/saya.php');
    }
}

$nilai = fn(string $key) => $_POST[$key] ?? ($lama[$key] ?? '');
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="form-page">
    <div class="container">
        <div class="form-card form-card--narrow">
            <h1><?= $editId ? 'Ubah lapak.' : 'Daftarkan usaha.' ?></h1>
            <p class="small">Setiap perubahan nama, deskripsi, atau foto membuat lapak menunggu verifikasi ulang. Tombol sembunyikan tidak.</p>

            <?php if ($errors) : ?>
                <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" data-busy>
                <?= csrf_field() ?>
                <?php if ($editId) : ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>

                <div class="field <?= isset($errors['nama_usaha']) ? 'field--error' : '' ?>">
                    <label for="nama_usaha">Nama usaha</label>
                    <input id="nama_usaha" name="nama_usaha" value="<?= e((string)$nilai('nama_usaha')) ?>" required minlength="3" maxlength="80">
                    <?php if (isset($errors['nama_usaha'])) : ?><span class="field__error"><?= e($errors['nama_usaha']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['kategori']) ? 'field--error' : '' ?>">
                    <label for="kategori">Kategori</label>
                    <select id="kategori" name="kategori" required>
                        <option value="">Pilih kategori</option>
                        <?php foreach ($kategoriList as $k => $label) : ?>
                            <option value="<?= $k ?>" <?= $nilai('kategori') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['kategori'])) : ?><span class="field__error"><?= e($errors['kategori']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['deskripsi']) ? 'field--error' : '' ?>">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" required minlength="20" maxlength="500" data-counter="hitung-deskripsi"><?= e((string)$nilai('deskripsi')) ?></textarea>
                    <span class="field__count" id="hitung-deskripsi">0 karakter</span>
                    <?php if (isset($errors['deskripsi'])) : ?><span class="field__error"><?= e($errors['deskripsi']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['kontak']) ? 'field--error' : '' ?>">
                    <label for="kontak">Kontak WhatsApp/HP</label>
                    <input id="kontak" name="kontak" type="tel" inputmode="numeric" value="<?= e((string)$nilai('kontak')) ?>" required maxlength="15">
                    <?php if (isset($errors['kontak'])) : ?><span class="field__error"><?= e($errors['kontak']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['foto']) ? 'field--error' : '' ?>">
                    <label for="foto">Foto usaha <?= $editId ? '<span class="hint">(kosongkan bila tidak diganti)</span>' : '' ?></label>
                    <input id="foto" name="foto" type="file" accept="image/jpeg,image/png" <?= $editId ? '' : 'required' ?>>
                    <?php if (isset($errors['foto'])) : ?><span class="field__error"><?= e($errors['foto']) ?></span><?php endif; ?>
                </div>

                <div class="form-actions">
                    <button class="btn btn--primary" type="submit"><?= $editId ? 'Simpan perubahan' : 'Daftarkan' ?></button>
                    <a class="btn btn--outline" href="<?= BASE ?>/lapak/saya.php">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
