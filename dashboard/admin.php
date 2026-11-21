<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('admin');

$count = fn(string $sql, array $p = []) => (int)(function () use ($pdo, $sql, $p) {
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchColumn();
})();

$totalUser = $count('SELECT COUNT(*) FROM users');
$totalPetugas = $count("SELECT COUNT(*) FROM users WHERE role = 'petugas'");
$totalRw = $count('SELECT COUNT(*) FROM rw');
$menunggu = $count("SELECT COUNT(*) FROM businesses WHERE status = 'menunggu'");

$lapak = $pdo->query("SELECT b.*, us.nama AS pemilik FROM businesses b JOIN users us ON us.id = b.user_id
    WHERE b.status = 'menunggu' ORDER BY b.created_at ASC LIMIT 10")->fetchAll();
$harga = $pdo->query('SELECT * FROM waste_types ORDER BY nama')->fetchAll();

$title = 'Dashboard admin';
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Dashboard admin</p>
                <h1>Tata kelola sistem.</h1>
                <p class="small">Pengguna, RW, harga sampah, titik kumpul, dan garis batas bantuan.</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat"><span>Total pengguna</span><b><?= $totalUser ?></b></div>
            <div class="stat"><span>Petugas RW</span><b><?= $totalPetugas ?></b></div>
            <div class="stat"><span>RW terdaftar</span><b><?= $totalRw ?></b></div>
            <div class="stat"><span>Lapak menunggu</span><b><?= $menunggu ?></b></div>
        </div>

        <div class="dash-grid">
            <div class="panel">
                <h3>Verifikasi lapak usaha</h3>
                <?php if ($lapak) : ?>
                    <div class="table-wrap">
                        <table class="data">
                            <thead><tr><th>Usaha</th><th>Pemilik</th><th>Kategori</th><th>Status</th><th>Aksi</th></tr></thead>
                            <tbody>
                            <?php foreach ($lapak as $l) : ?>
                                <tr>
                                    <td><?= e($l['nama_usaha']) ?></td>
                                    <td><?= e($l['pemilik']) ?></td>
                                    <td><?= ucfirst(e($l['kategori'])) ?></td>
                                    <td><?= status_chip($l['status']) ?></td>
                                    <td>
                                        <form method="post" action="<?= BASE ?>/lapak/verifikasi.php" class="form-actions" style="margin:0;flex-wrap:wrap">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                                            <input name="alasan_tolak" placeholder="Alasan bila ditolak" maxlength="200" aria-label="Alasan penolakan" style="max-width:180px">
                                            <button class="btn btn--ghost btn--sm" type="submit" name="status" value="tayang">Tayangkan</button>
                                            <button class="btn btn--danger btn--sm" type="submit" name="status" value="ditolak">Tolak</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <div class="empty"><b>Tidak ada lapak menunggu.</b>Semua pendaftaran sudah diproses.</div>
                <?php endif; ?>
            </div>

            <div>
                <div class="panel">
                    <h3>Administrasi</h3>
                    <ul class="list-plain">
                        <li><span>Pengguna</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/admin/pengguna.php">Kelola</a></li>
                        <li><span>RW</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/admin/rw.php">Kelola</a></li>
                        <li><span>Harga sampah</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/admin/harga-sampah.php">Kelola</a></li>
                        <li><span>Pengaturan</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/admin/pengaturan.php">Buka</a></li>
                    </ul>
                </div>
                <div class="panel">
                    <h3>Operasional</h3>
                    <ul class="list-plain">
                        <li><span>Catat setoran sampah</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/sampah/setor.php">Buka</a></li>
                        <li><span>Titik kumpul siaga</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/siaga/kelola.php">Kelola</a></li>
                        <li><span>Pengumuman &amp; agenda</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/pengumuman/kelola.php">Kelola</a></li>
                    </ul>
                </div>
                <div class="panel">
                    <h3>Garis batas bantuan</h3>
                    <p class="small">Dipakai fitur cek kondisi keluarga. Nilai contoh BPS, bukan penetapan resmi.</p>
                    <ul class="list-plain">
                        <li><span>Per kapita</span><b><?= rupiah((int)setting('garis_batas_per_kapita')) ?></b></li>
                        <li><span>Ambang perlu dipantau</span><b><?= e(setting('ambang_dipantau_kali')) ?>× garis batas</b></li>
                    </ul>
                </div>
                <div class="panel">
                    <h3>Harga sampah</h3>
                    <ul class="list-plain">
                        <?php foreach ($harga as $h) : ?>
                            <li><span><?= e($h['nama']) ?></span><b><?= rupiah($h['harga_per_kg']) ?>/kg</b></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
