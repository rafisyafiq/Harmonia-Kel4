<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('warga');

$title = 'Bank Sampah';
$saldo = saldo_warga($pdo, (int)$u['id']);

$st = $pdo->prepare('SELECT d.*, w.nama AS jenis FROM waste_deposits d
    JOIN waste_types w ON w.id = d.waste_type_id
    WHERE d.user_id = ? ORDER BY d.created_at DESC LIMIT 20');
$st->execute([$u['id']]);
$setoran = $st->fetchAll();

$st = $pdo->prepare('SELECT * FROM waste_withdrawals WHERE user_id = ? ORDER BY created_at DESC LIMIT 20');
$st->execute([$u['id']]);
$penarikan = $st->fetchAll();

$harga = $pdo->query('SELECT * FROM waste_types ORDER BY nama')->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Bank Sampah · Sdgs 11</p>
                <h1>Saldo Sampah Saya</h1>
                <p class="small">Setoran dicatat petugas RW. Penarikan diajukan dari halaman ini lalu dicairkan petugas.</p>
            </div>
        </div>

        <div class="stat-grid">
            <div class="stat"><span>Saldo Tersedia</span><b><?= rupiah($saldo) ?></b></div>
            <div class="stat"><span>Setoran Tercatat</span><b><?= count($setoran) ?></b></div>
            <div class="stat"><span>Penarikan Diajukan</span><b><?= count(array_filter($penarikan, fn($p) => $p['status'] === 'diajukan')) ?></b></div>
            <div class="stat"><span>Jenis Sampah</span><b><?= count($harga) ?></b></div>
        </div>

        <div class="dash-grid">
            <div>
                <div class="panel">
                    <h3>Riwayat Setoran</h3>
                    <?php if ($setoran) : ?>
                        <div class="table-wrap">
                            <table class="data">
                                <thead><tr><th>Tanggal</th><th>Jenis</th><th>Berat</th><th>Nilai</th><th>Status</th></tr></thead>
                                <tbody>
                                <?php foreach ($setoran as $d) : ?>
                                    <tr>
                                        <td><?= tanggal($d['created_at']) ?></td>
                                        <td><?= e($d['jenis']) ?></td>
                                        <td><?= number_format((float)$d['berat_kg'], 2, ',', '.') ?> kg</td>
                                        <td><?= rupiah($d['nilai']) ?></td>
                                        <td><?= $d['dibatalkan'] ? '<span class="chip chip--warn">Dibatalkan</span>' : '<span class="chip chip--ok">Sah</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="empty"><b>Belum Ada Setoran</b>Setor sampah terpilah ke petugas RW.</div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <h3>Riwayat Penarikan</h3>
                    <?php if ($penarikan) : ?>
                        <ul class="list-plain">
                            <?php foreach ($penarikan as $p) : ?>
                                <li><span><?= rupiah($p['jumlah']) ?><br><span class="small muted"><?= tanggal($p['created_at']) ?></span></span><?= status_chip($p['status']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <div class="empty"><b>Belum Ada Penarikan</b>Ajukan pencairan bila saldo mencukupi.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <div class="panel">
                    <h3>Ajukan Penarikan</h3>
                    <form method="post" action="<?= BASE ?>/sampah/tarik.php" data-busy>
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="jumlah">Jumlah (Rp)</label>
                            <input id="jumlah" name="jumlah" type="number" min="1" max="<?= max(0, $saldo) ?>" step="1" required>
                            <span class="hint">Maksimal <?= rupiah($saldo) ?>.</span>
                        </div>
                        <button class="btn btn--primary btn--sm" type="submit">Ajukan Penarikan</button>
                    </form>
                    <p class="small muted mt-2">Saldo baru berkurang setelah petugas mencairkan.</p>
                </div>

                <div class="panel">
                    <h3>Harga per Kilogram</h3>
                    <ul class="list-plain">
                        <?php foreach ($harga as $h) : ?>
                            <li><span><?= e($h['nama']) ?></span><b><?= rupiah($h['harga_per_kg']) ?></b></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
