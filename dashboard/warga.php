<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

$st = $pdo->prepare('SELECT * FROM aid_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$st->execute([$u['id']]);
$pengajuan = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM reports WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$st->execute([$u['id']]);
$laporan = $st->fetchAll();

$saldo = saldo_warga($pdo, (int)$u['id']);

$st = $pdo->prepare('SELECT COUNT(*) FROM proposal_votes WHERE user_id = ?');
$st->execute([$u['id']]);
$dukungan = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM aid_requests WHERE user_id = ? AND status IN ('diajukan','diverifikasi')");
$st->execute([$u['id']]);
$aktif = (int)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE user_id = ?');
$st->execute([$u['id']]);
$totalLaporan = (int)$st->fetchColumn();

$title = 'Dashboard warga';
$aksi = [
    ['Cek kondisi keluarga', '/cek-kondisi/index.php', 'Perbarui data pengeluaran keluarga.'],
    ['Ajukan bantuan', '/bantuan/ajukan.php', 'Mulai pengajuan baru.'],
    ['Lapor masalah', '/lapor/buat.php', 'Kirim laporan dengan foto.'],
    ['Lihat saldo sampah', '/sampah/index.php', 'Riwayat setoran dan penarikan.'],
];
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Dashboard warga</p>
                <h1>Halo, <?= e($u['nama']) ?>.</h1>
                <p class="small">Ringkasan layanan yang Anda gunakan. Data hanya terlihat oleh Anda dan petugas RW.</p>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/index.php">Ke beranda</a>
        </div>

        <div class="stat-grid">
            <div class="stat"><span>Pengajuan aktif</span><b><?= $aktif ?></b></div>
            <div class="stat"><span>Laporan saya</span><b><?= $totalLaporan ?></b></div>
            <div class="stat"><span>Saldo bank sampah</span><b><?= rupiah($saldo) ?></b></div>
            <div class="stat"><span>Usulan didukung</span><b><?= $dukungan ?></b></div>
        </div>

        <div class="dash-grid">
            <div>
                <div class="panel">
                    <h3>Pengajuan bantuan terakhir</h3>
                    <?php if ($pengajuan) : ?>
                        <div class="table-wrap">
                            <table class="data">
                                <thead><tr><th>Jenis</th><th>Tanggal</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($pengajuan as $p) : ?>
                                    <tr>
                                        <td><?= ucfirst(e($p['jenis'])) ?></td>
                                        <td><?= tanggal($p['created_at']) ?></td>
                                        <td><?= status_chip($p['status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (file_exists(ROOT_DIR . '/bantuan/index.php')) : ?>
                            <p class="small mt-2"><a href="<?= BASE ?>/bantuan/index.php">Lihat semua pengajuan →</a></p>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="empty"><b>Belum ada pengajuan.</b>Mulai dengan mengecek kondisi keluarga, lalu ajukan bantuan bila perlu.</div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <h3>Laporan saya</h3>
                    <?php if ($laporan) : ?>
                        <ul class="list-plain">
                            <?php foreach ($laporan as $l) : ?>
                                <li>
                                    <span><?= e($l['judul']) ?><br><span class="small muted"><?= e($l['lokasi_teks']) ?> · <?= tanggal($l['created_at']) ?></span></span>
                                    <?= status_chip($l['status']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <div class="empty"><b>Belum ada laporan.</b>Laporkan masalah lingkungan di sekitar Anda.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="panel">
                    <h3>Aksi cepat</h3>
                    <ul class="list-plain">
                        <?php foreach ($aksi as [$label, $file, $desc]) :
                            if (!file_exists(ROOT_DIR . $file)) {
                                continue;
                            } ?>
                            <li><span><?= e($label) ?><br><span class="small muted"><?= e($desc) ?></span></span><a class="btn btn--ghost btn--sm" href="<?= BASE . $file ?>">Buka</a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="panel">
                    <h3>Pengaturan akun</h3>
                    <p class="small">Ubah nama, nomor HP, atau sandi lewat halaman profil.</p>
                    <a class="btn btn--outline btn--sm" href="<?= BASE ?>/auth/profil.php">Buka profil</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
