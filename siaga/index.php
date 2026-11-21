<?php
require_once __DIR__ . '/../config/auth.php';

$title = 'Siaga bencana';
$active = 'siaga';
$u = user();
$rwList = $pdo->query('SELECT id, nama, kelurahan FROM rw ORDER BY nama')->fetchAll();
$rwId = (int)($_GET['rw'] ?? ($u['rw_id'] ?? 0));
if (!$rwId && $rwList) {
    $rwId = (int)$rwList[0]['id'];
}

$st = $pdo->prepare('SELECT s.*, rw.nama AS rw FROM shelters s JOIN rw ON rw.id = s.rw_id WHERE s.rw_id = ? ORDER BY s.nama');
$st->execute([$rwId]);
$shelters = $st->fetchAll();

$terakhir = null;
if ($u) {
    $st = $pdo->prepare('SELECT * FROM safety_checks WHERE user_id = ? ORDER BY created_at DESC LIMIT 1');
    $st->execute([$u['id']]);
    $terakhir = $st->fetch() ?: null;
}
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Siaga bencana · SDGs 11</p>
                <h1>Titik kumpul dan konfirmasi keselamatan.</h1>
                <p class="lead">Pilih RW untuk melihat titik kumpul terdekat. Warga yang masuk dapat mengabari petugas apakah sudah aman atau butuh bantuan.</p>
            </div>
            <form method="get">
                <div class="field" style="margin-bottom:0">
                    <label for="rw">RW</label>
                    <select id="rw" name="rw" onchange="this.form.submit()">
                        <?php foreach ($rwList as $r) : ?>
                            <option value="<?= (int)$r['id'] ?>" <?= $rwId === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['nama']) ?> · <?= e($r['kelurahan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <noscript><button class="btn btn--outline btn--sm mt-1" type="submit">Lihat</button></noscript>
            </form>
        </div>

        <div class="dash-grid">
            <div>
                <h2>Titik kumpul</h2>
                <?php if ($shelters) : ?>
                    <div class="lapak-grid mt-2">
                        <?php foreach ($shelters as $s) : ?>
                            <article class="panel">
                                <span class="chip chip--info"><?= e($s['rw']) ?></span>
                                <h3 class="mt-2"><?= e($s['nama']) ?></h3>
                                <p class="small"><?= e($s['alamat_teks']) ?></p>
                                <ul class="list-plain">
                                    <li><span>Kapasitas</span><b><?= (int)$s['kapasitas'] ?> orang</b></li>
                                    <li><span>Kontak</span><a href="tel:<?= e($s['kontak_hp']) ?>"><?= e($s['kontak_nama']) ?> · <?= e($s['kontak_hp']) ?></a></li>
                                </ul>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="empty mt-2"><b>Belum ada titik kumpul.</b>Petugas RW dapat menambahkannya lewat halaman kelola.</div>
                <?php endif; ?>
            </div>

            <div>
                <?php if ($u && $u['role'] === 'warga') : ?>
                    <div class="panel">
                        <h3>Konfirmasi keselamatan</h3>
                        <?php if ($terakhir) : ?>
                            <p class="small">Konfirmasi terakhir: <?= status_chip($terakhir['status']) ?> · <?= tanggal($terakhir['created_at'], true) ?></p>
                        <?php endif; ?>
                        <div class="field">
                            <label for="catatan">Catatan <span class="hint">(opsional, maks. 200)</span></label>
                            <input id="catatan" maxlength="200" placeholder="Mis. lansia perlu transportasi">
                        </div>
                        <div class="form-actions">
                            <button class="btn btn--primary btn--sm" type="button"
                                    data-siaga="aman" data-url="<?= BASE ?>/siaga/konfirmasi.php" data-csrf="<?= csrf_token() ?>">Saya aman</button>
                            <button class="btn btn--danger btn--sm" type="button"
                                    data-siaga="butuh_bantuan" data-url="<?= BASE ?>/siaga/konfirmasi.php" data-csrf="<?= csrf_token() ?>">Butuh bantuan</button>
                        </div>
                        <p class="small mt-2" id="pesan-siaga" role="status"></p>
                        <p class="small muted">Konfirmasi hanya berlaku 24 jam. Kirim ulang bila keadaan berubah.</p>
                    </div>
                <?php elseif (!$u) : ?>
                    <div class="panel">
                        <h3>Warga terdaftar?</h3>
                        <p class="small">Masuk untuk mengonfirmasi keselamatan dan melihat riwayat konfirmasi Anda.</p>
                        <a class="btn btn--primary btn--sm" href="<?= BASE ?>/auth/login.php">Masuk</a>
                    </div>
                <?php endif; ?>

                <?php if ($u && in_array($u['role'], ['petugas', 'admin'], true)) : ?>
                    <div class="panel">
                        <h3>Panel petugas</h3>
                        <p class="small">Lihat rekap warga aman, butuh bantuan, dan belum konfirmasi.</p>
                        <a class="btn btn--outline btn--sm" href="<?= BASE ?>/siaga/ringkasan.php">Buka ringkasan</a>
                        <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/siaga/kelola.php">Kelola titik kumpul</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$scripts = ['/assets/js/siaga.js'];
include ROOT_DIR . '/includes/footer.php';
