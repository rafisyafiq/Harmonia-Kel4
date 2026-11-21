<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

$title = 'Ajukan bantuan';
$jenisList = ['pangan' => 'Pangan', 'tunai' => 'Tunai', 'kesehatan' => 'Kesehatan', 'pendidikan' => 'Pendidikan'];
$errors = [];

$checkId = (int)($_POST['check_id'] ?? $_GET['check_id'] ?? 0);
$check = null;
if ($checkId) {
    $st = $pdo->prepare('SELECT * FROM family_checks WHERE id = ? AND user_id = ?');
    $st->execute([$checkId, $u['id']]);
    $check = $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $jenis = (string)($_POST['jenis'] ?? '');
    $alasan = trim((string)($_POST['alasan'] ?? ''));
    $checkId = (int)($_POST['check_id'] ?? 0);

    if (!isset($jenisList[$jenis])) {
        $errors['jenis'] = 'Pilih jenis bantuan.';
    }
    $panjang = mb_strlen($alasan);
    if ($panjang < 20 || $panjang > 500) {
        $errors['alasan'] = 'Alasan 20–500 karakter (sekarang ' . $panjang . ').';
    }
    if ($checkId) {
        $st = $pdo->prepare('SELECT 1 FROM family_checks WHERE id = ? AND user_id = ?');
        $st->execute([$checkId, $u['id']]);
        if (!$st->fetchColumn()) {
            $errors['check_id'] = 'Hasil cek kondisi tidak ditemukan.';
        }
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $st = $pdo->prepare("SELECT id FROM aid_requests WHERE user_id = ? AND jenis = ? AND status IN ('diajukan','diverifikasi') FOR UPDATE");
            $st->execute([$u['id'], $jenis]);
            if ($st->fetchColumn()) {
                $pdo->rollBack();
                $errors['jenis'] = 'Masih ada pengajuan ' . strtolower($jenisList[$jenis]) . ' yang aktif. Tunggu keputusan petugas.';
            } else {
                $st = $pdo->prepare('INSERT INTO aid_requests (user_id, check_id, jenis, alasan) VALUES (?, ?, ?, ?)');
                $st->execute([$u['id'], $checkId ?: null, $jenis, $alasan]);
                $id = (int)$pdo->lastInsertId();
                log_status($pdo, 'aid', $id, 'diajukan', null, (int)$u['id']);
                $pdo->commit();
                flash('sukses', 'Pengajuan terkirim. Pantau statusnya di halaman pengajuan.');
                redirect(BASE . '/bantuan/index.php');
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors['umum'] = 'Pengajuan gagal disimpan. Coba lagi.';
        }
    }
}
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="form-page">
    <div class="container">
        <div class="form-card form-card--narrow">
            <h1>Ajukan bantuan.</h1>
            <p class="small">Satu jenis bantuan hanya boleh punya satu pengajuan aktif. Petugas RW akan memverifikasi sebelum mengambil keputusan.</p>

            <?php if ($errors) : ?>
                <div class="alert alert--error" role="alert"><?= e($errors['umum'] ?? 'Periksa kembali isian yang ditandai.') ?></div>
            <?php endif; ?>

            <?php if ($check) : ?>
                <div class="alert alert--info" role="status">
                    Hasil cek terakhir: <?= rupiah($check['per_kapita']) ?> per orang · <?= status_chip($check['status']) ?>
                    <input type="hidden" name="check_id" form="form-ajukan" value="<?= (int)$check['id'] ?>">
                </div>
            <?php endif; ?>

            <form id="form-ajukan" method="post" data-busy>
                <?= csrf_field() ?>
                <?php if (!$check) : ?>
                    <input type="hidden" name="check_id" value="<?= (int)$checkId ?>">
                <?php endif; ?>

                <div class="field <?= isset($errors['jenis']) ? 'field--error' : '' ?>">
                    <label for="jenis">Jenis bantuan</label>
                    <select id="jenis" name="jenis" required>
                        <option value="">Pilih jenis</option>
                        <?php foreach ($jenisList as $k => $label) : ?>
                            <option value="<?= $k ?>" <?= ($_POST['jenis'] ?? '') === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['jenis'])) : ?><span class="field__error"><?= e($errors['jenis']) ?></span><?php endif; ?>
                </div>

                <div class="field <?= isset($errors['alasan']) ? 'field--error' : '' ?>">
                    <label for="alasan">Alasan pengajuan</label>
                    <textarea id="alasan" name="alasan" required minlength="20" maxlength="500" data-counter="hitung-alasan"><?= old('alasan') ?></textarea>
                    <span class="field__count" id="hitung-alasan">0 karakter</span>
                    <?php if (isset($errors['alasan'])) : ?><span class="field__error"><?= e($errors['alasan']) ?></span><?php endif; ?>
                </div>

                <div class="form-actions">
                    <button class="btn btn--primary" type="submit">Kirim pengajuan</button>
                    <a class="btn btn--outline" href="<?= BASE ?>/bantuan/index.php">Kembali</a>
                </div>
            </form>

            <div class="mt-3">
                <h3>Pertanyaan umum</h3>
                <?php include ROOT_DIR . '/includes/faq.php'; ?>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
