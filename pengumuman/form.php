<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

$title = 'Form pengumuman';
$tipeList = ['pengumuman' => 'Pengumuman', 'agenda' => 'Agenda', 'tips' => 'Tips'];
$errors = [];
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$lama = null;

if ($id) {
    $st = $pdo->prepare('SELECT * FROM announcements WHERE id = ?');
    $st->execute([$id]);
    $lama = $st->fetch();
    if (!$lama) {
        http_response_code(404);
        exit('Pengumuman tidak ditemukan.');
    }
    if ($u['role'] === 'petugas' && (int)$lama['rw_id'] !== (int)$u['rw_id']) {
        http_response_code(403);
        exit('Pengumuman ini bukan milik RW Anda.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $tipe = (string)($_POST['tipe'] ?? '');
    $judul = trim((string)($_POST['judul'] ?? ''));
    $isi = trim((string)($_POST['isi'] ?? ''));
    $tanggalAcara = trim((string)($_POST['tanggal_acara'] ?? ''));
    $lokasi = trim((string)($_POST['lokasi'] ?? ''));
    $berlaku = trim((string)($_POST['berlaku_sampai'] ?? ''));
    $sematkan = isset($_POST['sematkan']) ? 1 : 0;
    $siaga = $u['role'] === 'admin' && isset($_POST['siaga']) ? 1 : 0;
    $rwTarget = $u['role'] === 'admin' ? ((int)($_POST['rw_id'] ?? 0) ?: null) : (int)$u['rw_id'];

    if (!isset($tipeList[$tipe])) {
        $errors['tipe'] = 'Pilih jenis pengumuman.';
    }
    if (mb_strlen($judul) < 5 || mb_strlen($judul) > 120) {
        $errors['judul'] = 'Judul 5–120 karakter.';
    }
    if (mb_strlen($isi) < 20 || mb_strlen($isi) > 1500) {
        $errors['isi'] = 'Isi 20–1500 karakter.';
    }
    if (mb_strlen($lokasi) > 120) {
        $errors['lokasi'] = 'Lokasi maksimal 120 karakter.';
    }
    $waktuAcara = null;
    if ($tipe === 'agenda') {
        $waktuAcara = $tanggalAcara !== '' ? strtotime($tanggalAcara) : false;
        if (!$waktuAcara || $waktuAcara <= time()) {
            $errors['tanggal_acara'] = 'Agenda wajib punya tanggal di masa depan.';
        }
    }
    $batasBerlaku = null;
    if ($berlaku !== '') {
        $b = strtotime($berlaku);
        if (!$b || date('Y-m-d', $b) < date('Y-m-d')) {
            $errors['berlaku_sampai'] = 'Tanggal berlaku tidak boleh di masa lalu.';
        } else {
            $batasBerlaku = date('Y-m-d', $b);
        }
    }
    if ($u['role'] === 'petugas' && !$rwTarget) {
        $errors['rw_id'] = 'Akun petugas belum punya RW. Hubungi admin.';
    }

    if (!$errors) {
        $nilai = [
            $rwTarget,
            $tipe,
            $judul,
            $isi,
            $waktuAcara ? date('Y-m-d H:i:s', $waktuAcara) : null,
            $lokasi ?: null,
            $siaga,
            $sematkan,
            $batasBerlaku,
        ];
        if ($id) {
            $sql = 'UPDATE announcements SET rw_id = ?, tipe = ?, judul = ?, isi = ?, tanggal_acara = ?, lokasi = ?, siaga = ?, sematkan = ?, berlaku_sampai = ? WHERE id = ?';
            $nilai[] = $id;
            $pdo->prepare($sql)->execute($nilai);
            flash('sukses', 'Pengumuman diperbarui.');
        } else {
            $pdo->prepare('INSERT INTO announcements (author_id, rw_id, tipe, judul, isi, tanggal_acara, lokasi, siaga, sematkan, berlaku_sampai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute(array_merge([$u['id']], $nilai));
            flash('sukses', 'Pengumuman dibuat.');
        }
        redirect(BASE . '/pengumuman/kelola.php');
    }
}

$val = fn(string $key, string $default = '') => $_POST[$key] ?? ($lama[$key] ?? $default);
$rwList = $pdo->query('SELECT id, nama FROM rw ORDER BY nama')->fetchAll();
$tanggalAcaraVal = $val('tanggal_acara') ? date('Y-m-d\TH:i', strtotime((string)$val('tanggal_acara'))) : '';
$berlakuVal = $val('berlaku_sampai') ? date('Y-m-d', strtotime((string)$val('berlaku_sampai'))) : '';
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="form-page">
    <div class="container">
        <div class="form-card form-card--narrow">
            <h1><?= $id ? 'Ubah pengumuman.' : 'Pengumuman baru.' ?></h1>
            <p class="small">Pengumuman tampil untuk warga RW terkait. Pilih "Semua RW" hanya bila Anda admin.</p>

            <?php if ($errors) : ?>
                <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
            <?php endif; ?>

            <form method="post" data-busy>
                <?= csrf_field() ?>
                <?php if ($id) : ?><input type="hidden" name="id" value="<?= $id ?>"><?php endif; ?>

                <div class="form-grid">
                    <div class="field <?= isset($errors['tipe']) ? 'field--error' : '' ?>">
                        <label for="tipe">Jenis</label>
                        <select id="tipe" name="tipe" required>
                            <?php foreach ($tipeList as $k => $label) : ?>
                                <option value="<?= $k ?>" <?= $val('tipe', 'pengumuman') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['tipe'])) : ?><span class="field__error"><?= e($errors['tipe']) ?></span><?php endif; ?>
                    </div>
                    <?php if ($u['role'] === 'admin') : ?>
                        <div class="field <?= isset($errors['rw_id']) ? 'field--error' : '' ?>">
                            <label for="rw_id">RW</label>
                            <select id="rw_id" name="rw_id">
                                <option value="">Semua RW</option>
                                <?php foreach ($rwList as $r) : ?>
                                    <option value="<?= (int)$r['id'] ?>" <?= (int)$val('rw_id', '0') === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['rw_id'])) : ?><span class="field__error"><?= e($errors['rw_id']) ?></span><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="field <?= isset($errors['judul']) ? 'field--error' : '' ?>">
                    <label for="judul">Judul</label>
                    <input id="judul" name="judul" value="<?= e((string)$val('judul')) ?>" required minlength="5" maxlength="120">
                    <?php if (isset($errors['judul'])) : ?><span class="field__error"><?= e($errors['judul']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['isi']) ? 'field--error' : '' ?>">
                    <label for="isi">Isi</label>
                    <textarea id="isi" name="isi" required minlength="20" maxlength="1500" data-counter="hitung-isi"><?= e((string)$val('isi')) ?></textarea>
                    <span class="field__count" id="hitung-isi">0 karakter</span>
                    <?php if (isset($errors['isi'])) : ?><span class="field__error"><?= e($errors['isi']) ?></span><?php endif; ?>
                </div>

                <div class="form-grid">
                    <div class="field <?= isset($errors['tanggal_acara']) ? 'field--error' : '' ?>">
                        <label for="tanggal_acara">Tanggal acara <span class="hint">(wajib untuk agenda)</span></label>
                        <input id="tanggal_acara" name="tanggal_acara" type="datetime-local" value="<?= e($tanggalAcaraVal) ?>">
                        <?php if (isset($errors['tanggal_acara'])) : ?><span class="field__error"><?= e($errors['tanggal_acara']) ?></span><?php endif; ?>
                    </div>
                    <div class="field <?= isset($errors['berlaku_sampai']) ? 'field--error' : '' ?>">
                        <label for="berlaku_sampai">Berlaku sampai <span class="hint">(opsional)</span></label>
                        <input id="berlaku_sampai" name="berlaku_sampai" type="date" value="<?= e($berlakuVal) ?>">
                        <?php if (isset($errors['berlaku_sampai'])) : ?><span class="field__error"><?= e($errors['berlaku_sampai']) ?></span><?php endif; ?>
                    </div>
                </div>

                <div class="field <?= isset($errors['lokasi']) ? 'field--error' : '' ?>">
                    <label for="lokasi">Lokasi <span class="hint">(opsional)</span></label>
                    <input id="lokasi" name="lokasi" value="<?= e((string)$val('lokasi')) ?>" maxlength="120">
                    <?php if (isset($errors['lokasi'])) : ?><span class="field__error"><?= e($errors['lokasi']) ?></span><?php endif; ?>
                </div>

                <div class="field">
                    <label><input type="checkbox" name="sematkan" value="1" <?= $val('sematkan') ? 'checked' : '' ?>> Sematkan di atas</label>
                    <?php if ($u['role'] === 'admin') : ?>
                        <label><input type="checkbox" name="siaga" value="1" <?= $val('siaga') ? 'checked' : '' ?>> Tandai siaga (pita di atas semua halaman)</label>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Simpan</button>
                    <a class="btn btn--outline" href="<?= BASE ?>/pengumuman/kelola.php">Kembali</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
