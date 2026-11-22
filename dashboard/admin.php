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

$lapak = $pdo->query("SELECT b.*, us.nama AS pemilik FROM businesses b JOIN users us ON us.id = b.user_id
    WHERE b.status = 'menunggu' ORDER BY b.created_at ASC LIMIT 10")->fetchAll();
$harga = $pdo->query('SELECT * FROM waste_types ORDER BY nama')->fetchAll();
$aktivitas = $pdo->query("SELECT sl.*, us.nama AS pelaku FROM status_logs sl LEFT JOIN users us ON us.id = sl.by_user ORDER BY sl.created_at DESC LIMIT 6")->fetchAll();

$title = 'Dashboard Admin';
$hideNavMenu = true;
$bodyClass = 'hide-nav';
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash-layout container">
    <aside class="dash-sidebar">
        <div class="dash-sidebar__brand">
            <img src="<?= BASE ?>/assets/images/Logo.png" alt="Logo Harmonia">
            <span>HARMONIA</span>
        </div>
        <nav class="dash-sidebar__nav" aria-label="Menu admin">
            <a href="<?= BASE ?>/dashboard/admin.php" class="is-active">Dashboard</a>
            <a href="<?= BASE ?>/admin/pengguna.php">Pengguna</a>
            <a href="<?= BASE ?>/admin/rw.php">RW</a>
            <a href="<?= BASE ?>/admin/harga-sampah.php">Harga Sampah</a>
            <a href="<?= BASE ?>/admin/pengaturan.php">Pengaturan</a>
            <a href="<?= BASE ?>/pengumuman/kelola.php">Pengumuman</a>
            <a href="<?= BASE ?>/lapak/verifikasi.php">Verifikasi Lapak</a>
        </nav>
        <div class="dash-sidebar__help">
            <b>Butuh Bantuan?</b>
            Tim Harmonia siap membantu 24/7 untuk operasional RW Anda.
        </div>
    </aside>

    <div>
        <div class="dash__head">
            <div>
                <p class="eyebrow">Dashboard Admin</p>
                <h1>Tata Kelola Sistem</h1>
                <p class="small">Pengguna, RW, harga sampah, titik kumpul, dan garis batas bantuan.</p>
            </div>
        </div>

        <div class="dash-stat-grid">
            <div class="glass-panel dash-stat-card">
                <div>
                    <span class="small muted">Total Pengguna</span>
                    <div class="dash-stat-card__val"><?= $totalUser ?></div>
                    <span class="small muted">Warga &amp; Petugas</span>
                </div>
                <div class="dash-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
            </div>
            <div class="glass-panel dash-stat-card">
                <div>
                    <span class="small muted">Petugas RW</span>
                    <div class="dash-stat-card__val"><?= $totalPetugas ?></div>
                    <span class="small muted">Aktif di Sistem</span>
                </div>
                <div class="dash-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
            </div>
            <div class="glass-panel dash-stat-card">
                <div>
                    <span class="small muted">RW Terdaftar</span>
                    <div class="dash-stat-card__val"><?= $totalRw ?></div>
                    <span class="small muted">Lingkungan Aktif</span>
                </div>
                <div class="dash-stat-card__icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 7h6M9 12h6"/></svg>
                </div>
            </div>
        </div>

        <div class="glass-panel" style="margin-bottom:1.25rem">
            <h3>Aktivitas Terbaru</h3>
            <?php if ($aktivitas) : ?>
                <ul class="activity-feed">
                    <?php foreach ($aktivitas as $a) :
                        $dot = 'info';
                        if (in_array($a['status'], ['disetujui', 'tayang', 'selesai'], true)) $dot = 'ok';
                        elseif (in_array($a['status'], ['ditolak', 'batal'], true)) $dot = 'bad';
                    ?>
                        <li class="activity-item">
                            <span class="activity-dot activity-dot--<?= $dot ?>"></span>
                            <div>
                                <b><?= e($a['pelaku'] ?? 'Sistem') ?></b> mengubah <?= e($a['ref_type']) ?> #<?= (int)$a['ref_id'] ?> ke <?= e($a['status']) ?>
                                <br><span class="small muted"><?= tanggal($a['created_at'], true) ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else : ?>
                <div class="empty"><b>Belum Ada Aktivitas</b></div>
            <?php endif; ?>
        </div>

        <div class="glass-panel" style="margin-bottom:1.25rem">
            <h3>Verifikasi Lapak Usaha</h3>
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
                <div class="empty"><b>Tidak Ada Lapak Menunggu</b>Semua pendaftaran sudah diproses.</div>
            <?php endif; ?>
        </div>

        <div class="dash-grid">
            <div class="glass-panel">
                <h3>Administrasi</h3>
                <ul class="list-plain">
                    <li><span>Pengguna</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/admin/pengguna.php">Kelola</a></li>
                    <li><span>RW</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/admin/rw.php">Kelola</a></li>
                    <li><span>Harga Sampah</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/admin/harga-sampah.php">Kelola</a></li>
                    <li><span>Pengaturan</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/admin/pengaturan.php">Buka</a></li>
                </ul>
            </div>
            <div class="glass-panel">
                <h3>Harga Sampah</h3>
                <ul class="list-plain">
                    <?php foreach ($harga as $h) : ?>
                        <li><span><?= e($h['nama']) ?></span><b><?= rupiah($h['harga_per_kg']) ?>/kg</b></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="glass-panel">
                <h3>Operasional</h3>
                <ul class="list-plain">
                    <li><span>Catat Setoran Sampah</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/sampah/setor.php">Buka</a></li>
                    <li><span>Titik Kumpul Siaga</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/siaga/kelola.php">Kelola</a></li>
                    <li><span>Pengumuman &amp; Agenda</span><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/pengumuman/kelola.php">Kelola</a></li>
                </ul>
            </div>
            <div class="glass-panel">
                <h3>Garis Batas Bantuan</h3>
                <p class="small">Dipakai fitur cek kondisi keluarga. Nilai contoh BPS, bukan penetapan resmi.</p>
                <ul class="list-plain">
                    <li><span>Per Kapita</span><b><?= rupiah((int)setting('garis_batas_per_kapita')) ?></b></li>
                    <li><span>Ambang Perlu Dipantau</span><b><?= e(setting('ambang_dipantau_kali')) ?>× garis batas</b></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>