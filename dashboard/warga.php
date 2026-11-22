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
$st = $pdo->prepare('SELECT COUNT(*) FROM sembako_claims WHERE user_id = ?');
$st->execute([$u['id']]);
$sembako = (int)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM proposal_votes WHERE user_id = ?');
$st->execute([$u['id']]);
$dukungan = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM aid_requests WHERE user_id = ? AND status IN ('diajukan','diverifikasi')");
$st->execute([$u['id']]);
$aktif = (int)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM reports WHERE user_id = ?');
$st->execute([$u['id']]);
$totalLaporan = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status IN ('baru','diproses')");
$st->execute([$u['id']]);
$diproses = (int)$st->fetchColumn();

$st = $pdo->prepare("SELECT COUNT(*) FROM reports WHERE user_id = ? AND status = 'selesai'");
$st->execute([$u['id']]);
$selesai = (int)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM proposals WHERE user_id = ?');
$st->execute([$u['id']]);
$usulanSaya = (int)$st->fetchColumn();

$title = 'Dashboard Warga';
$aksi = [
    ['Cek kondisi keluarga', '/cek-kondisi/index.php', 'Perbarui data pengeluaran keluarga.'],
    ['Ajukan bantuan', '/bantuan/ajukan.php', 'Mulai pengajuan baru.'],
    ['Lapor masalah', '/lapor/buat.php', 'Kirim laporan dengan foto.'],
    ['Lihat saldo sampah', '/sampah/index.php', 'Riwayat setoran dan penarikan.'],
    ['Bantuan sosial', '/sembako/index.php', 'Bagikan dan ambil sembako.'],
];
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Dashboard Warga</p>
                <h1>Halo, <?= e($u['nama']) ?></h1>
                <p class="small">Ringkasan layanan yang Anda gunakan. Data hanya terlihat oleh Anda dan petugas RW.</p>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/index.php">Ke Beranda</a>
        </div>

        <div class="panel" style="margin-bottom:1.5rem">
            <h2 style="font-size:1.5rem">Ada Masalah di Lingkunganmu?</h2>
            <p>Buat laporan atau ajukan usulan baru. Pantau statusnya di sini.</p>
            <div class="form-actions">
                <?php if (file_exists(ROOT_DIR . '/lapor/buat.php')) : ?>
                    <a class="btn btn--primary" href="<?= BASE ?>/lapor/buat.php">Buat Laporan</a>
                <?php endif; ?>
                <?php if (file_exists(ROOT_DIR . '/usulan/buat.php')) : ?>
                    <a class="btn btn--outline" href="<?= BASE ?>/usulan/buat.php">Ajukan Usulan</a>
                <?php endif; ?>
            </div>
            <p class="small mt-2">
                <a href="<?= BASE ?>/lapak/index.php">Lapak</a> · <a href="<?= BASE ?>/capaian/index.php">Capaian</a>
            </p>
        </div>

        <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr))">
            <div class="stat"><span>Laporan Saya</span><b><?= $totalLaporan ?></b></div>
            <div class="stat"><span>Sedang Diproses</span><b><?= $diproses ?></b></div>
            <div class="stat"><span>Selesai</span><b><?= $selesai ?></b></div>
            <div class="stat"><span>Usulan Saya</span><b><?= $usulanSaya ?></b></div>
        </div>

        <div class="dash-grid">
            <div>
                <div class="panel">
                    <h3>Pengajuan Bantuan Terakhir</h3>
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
                            <p class="small mt-2"><a href="<?= BASE ?>/bantuan/index.php">Lihat Semua Pengajuan →</a></p>
                        <?php endif; ?>
                    <?php else : ?>
                        <div class="empty"><b>Belum Ada Pengajuan</b>Mulai dengan mengecek kondisi keluarga, lalu ajukan bantuan bila perlu.</div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <h3>Laporan Saya</h3>
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
                        <div class="empty"><b>Belum Ada Laporan</b>Laporkan masalah lingkungan di sekitar Anda.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="panel">
                    <h3>Aksi Cepat</h3>
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
                    <h3>Pengaturan Akun</h3>
                    <p class="small">Ubah nama, nomor HP, atau sandi lewat halaman profil.</p>
                    <a class="btn btn--outline btn--sm" href="<?= BASE ?>/auth/profil.php">Buka Profil</a>
                </div>
                <div class="panel">
                    <h3>Ringkasan Lain</h3>
                    <ul class="list-plain">
                        <li><span>Pengajuan Aktif</span><b><?= $aktif ?></b></li>
                        <li><span>Saldo Bank Sampah</span><b><?= rupiah($saldo) ?></b></li>
                        <li><span>Paket Sembako Diambil</span><b><?= $sembako ?></b></li>
                        <li><span>Usulan Didukung</span><b><?= $dukungan ?></b></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>