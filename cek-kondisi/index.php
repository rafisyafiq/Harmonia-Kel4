<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

$title = 'Cek Kondisi Keluarga';
$batas = (int)setting('garis_batas_per_kapita');
$ambang = (float)setting('ambang_dipantau_kali', '1.5');

$st = $pdo->prepare('SELECT * FROM family_checks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$st->execute([$u['id']]);
$riwayat = $st->fetchAll();

$hasil = null;
if (isset($_GET['hasil'])) {
    $st = $pdo->prepare('SELECT * FROM family_checks WHERE id = ? AND user_id = ?');
    $st->execute([(int)$_GET['hasil'], $u['id']]);
    $hasil = $st->fetch() ?: null;
}
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="form-page">
    <div class="container">
        <div class="dash-grid">
            <div>
                <p class="eyebrow">Sdgs 1 · tanpa Kemiskinan</p>
                <h1>Cek Kondisi Keluarga</h1>
                <p class="lead">Masukkan jumlah anggota keluarga dan pengeluaran bulanan. Sistem menghitung pengeluaran per orang dan membandingkannya dengan garis batas RW.</p>

                <form class="form-card mt-3" method="post" action="<?= BASE ?>/cek-kondisi/hitung.php" id="form-cek"
                      data-batas="<?= $batas ?>" data-ambang="<?= e((string)$ambang) ?>"
                      data-ajukan="<?= BASE ?>/bantuan/ajukan.php" novalidate>
                    <?= csrf_field() ?>
                    <div class="form-grid">
                        <div class="field <?= isset($errors['jumlah_orang']) ? 'field--error' : '' ?>">
                            <label for="jumlah_orang">Jumlah Anggota Keluarga</label>
                            <input id="jumlah_orang" name="jumlah_orang" type="number" min="1" max="20" step="1" required value="<?= old('jumlah_orang', '3') ?>">
                            <span class="hint">1–20 orang.</span>
                        </div>
                        <div class="field">
                            <label for="pengeluaran_bulanan">Pengeluaran Bulanan (Rp)</label>
                            <input id="pengeluaran_bulanan" name="pengeluaran_bulanan" type="number" min="1" max="100000000" step="1" required value="<?= old('pengeluaran_bulanan', '2000000') ?>">
                            <span class="hint">Total kebutuhan dasar sebulan.</span>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button class="btn btn--primary" type="submit">Hitung Sekarang</button>
                    </div>
                    <p class="form-help mt-2">Hasil hanya perkiraan awal. Keputusan bantuan tetap ada pada petugas RW.</p>
                    <noscript><div class="alert alert--info mt-2">Aktifkan JavaScript untuk melihat hasil tanpa memuat ulang halaman.</div></noscript>
                </form>

                <div id="hasil-cek" class="panel mt-3" <?= $hasil ? '' : 'hidden' ?>>
                    <h3>Hasil Perhitungan</h3>
                    <p class="small muted">Pengeluaran per orang per bulan</p>
                    <p style="font:500 2rem var(--serif);color:var(--navy)" id="hasil-nilai"><?= $hasil ? rupiah($hasil['per_kapita']) : '' ?></p>
                    <div class="progress" aria-hidden="true"><span id="hasil-bar" style="width:0"></span></div>
                    <p class="mt-2" id="hasil-status"><?= $hasil ? status_chip($hasil['status']) : '' ?></p>
                    <p class="small" id="hasil-catatan"></p>
                    <p id="hasil-aksi" <?= $hasil && $hasil['status'] !== 'cukup' ? '' : 'hidden' ?>>
                        <a class="btn btn--primary btn--sm" id="hasil-tombol" href="<?= BASE ?>/bantuan/ajukan.php<?= $hasil ? '?check_id=' . (int)$hasil['id'] : '' ?>">Ajukan Bantuan</a>
                    </p>
                </div>

                <div class="panel mt-3">
                    <h3>Garis Batas yang Dipakai</h3>
                    <ul class="list-plain">
                        <li><span>Rentan</span><span class="small muted">di bawah <?= rupiah($batas) ?> per orang</span></li>
                        <li><span>Perlu Dipantau</span><span class="small muted"><?= rupiah($batas) ?> – <?= rupiah((int)round($batas * $ambang)) ?></span></li>
                        <li><span>Cukup</span><span class="small muted">≥ <?= rupiah((int)round($batas * $ambang)) ?></span></li>
                    </ul>
                    <p class="small muted mt-2">Angka contoh BPS, dapat diubah admin. Data Anda hanya terlihat oleh Anda dan petugas RW.</p>
                </div>
            </div>

            <div>
                <div class="panel">
                    <h3>Riwayat Terakhir</h3>
                    <?php if ($riwayat) : ?>
                        <ul class="list-plain">
                            <?php foreach ($riwayat as $r) : ?>
                                <li>
                                    <span><?= rupiah($r['per_kapita']) ?><br><span class="small muted"><?= tanggal($r['created_at']) ?> · <?= (int)$r['jumlah_orang'] ?> orang</span></span>
                                    <?= status_chip($r['status']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="small mt-2"><a href="<?= BASE ?>/cek-kondisi/riwayat.php">Lihat Semua Riwayat →</a></p>
                    <?php else : ?>
                        <div class="empty"><b>Belum Ada Riwayat</b>Hasil perhitungan akan tersimpan di sini.</div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <h3>Pertanyaan Umum</h3>
                    <?php include ROOT_DIR . '/includes/faq.php'; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
$scripts = ['/assets/js/cek-kondisi.js'];
include ROOT_DIR . '/includes/footer.php';
