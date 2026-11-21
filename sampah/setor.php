<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_role('petugas', 'admin');

$title = 'Catat setoran sampah';
$errors = [];
$rwId = $u['role'] === 'admin' ? null : (int)$u['rw_id'];

$sql = "SELECT us.id, us.nama, rw.nama AS rw FROM users us LEFT JOIN rw ON rw.id = us.rw_id WHERE us.role = 'warga'";
$params = [];
if ($rwId !== null) {
    $sql .= ' AND us.rw_id = ?';
    $params[] = $rwId;
}
$sql .= ' ORDER BY us.nama';
$st = $pdo->prepare($sql);
$st->execute($params);
$warga = $st->fetchAll();
$jenis = $pdo->query('SELECT * FROM waste_types ORDER BY nama')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $userId = (int)($_POST['user_id'] ?? 0);
    $typeId = (int)($_POST['waste_type_id'] ?? 0);
    $beratRaw = str_replace(',', '.', trim((string)($_POST['berat_kg'] ?? '')));
    $berat = is_numeric($beratRaw) ? (float)$beratRaw : 0;

    if (!array_filter($warga, fn($w) => (int)$w['id'] === $userId)) {
        $errors['user_id'] = 'Pilih warga di RW Anda.';
    }
    $harga = null;
    foreach ($jenis as $j) {
        if ((int)$j['id'] === $typeId) {
            $harga = (int)$j['harga_per_kg'];
        }
    }
    if ($harga === null) {
        $errors['waste_type_id'] = 'Pilih jenis sampah.';
    }
    if ($berat < 0.1 || $berat > 100 || !preg_match('/^\d{1,3}(\.\d{1,2})?$/', $beratRaw)) {
        $errors['berat_kg'] = 'Berat 0,1–100 kg, maksimal 2 desimal.';
    }

    if (!$errors) {
        $nilai = (int)round($berat * $harga);
        $st = $pdo->prepare('INSERT INTO waste_deposits (user_id, waste_type_id, berat_kg, nilai, dicatat_oleh) VALUES (?, ?, ?, ?, ?)');
        $st->execute([$userId, $typeId, number_format($berat, 2, '.', ''), $nilai, $u['id']]);
        flash('sukses', 'Setoran ' . number_format($berat, 2, ',', '.') . ' kg tercatat senilai ' . rupiah($nilai) . '.');
        redirect(BASE . '/sampah/setor.php');
    }
}

$sql = "SELECT w.*, us.nama AS warga, rw.nama AS rw FROM waste_withdrawals w
    JOIN users us ON us.id = w.user_id LEFT JOIN rw ON rw.id = us.rw_id
    WHERE w.status = 'diajukan'";
$params = [];
if ($rwId !== null) {
    $sql .= ' AND us.rw_id = ?';
    $params[] = $rwId;
}
$sql .= ' ORDER BY w.created_at ASC';
$st = $pdo->prepare($sql);
$st->execute($params);
$antrean = $st->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Bank sampah · Petugas</p>
                <h1>Catat setoran dan cairkan saldo.</h1>
                <p class="small">Nilai dihitung di server dari berat × harga jenis sampah.</p>
            </div>
        </div>

        <?php if ($errors) : ?>
            <div class="alert alert--error" role="alert">Periksa kembali isian yang ditandai.</div>
        <?php endif; ?>

        <div class="dash-grid">
            <div>
                <div class="panel">
                    <h3>Setoran baru</h3>
                    <form method="post" data-busy>
                        <?= csrf_field() ?>
                        <div class="field <?= isset($errors['user_id']) ? 'field--error' : '' ?>">
                            <label for="user_id">Warga</label>
                            <select id="user_id" name="user_id" required>
                                <option value="">Pilih warga</option>
                                <?php foreach ($warga as $w) : ?>
                                    <option value="<?= (int)$w['id'] ?>" <?= (int)($_POST['user_id'] ?? 0) === (int)$w['id'] ? 'selected' : '' ?>><?= e($w['nama']) ?> · <?= e($w['rw'] ?? '-') ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['user_id'])) : ?><span class="field__error"><?= e($errors['user_id']) ?></span><?php endif; ?>
                        </div>
                        <div class="form-grid">
                            <div class="field <?= isset($errors['waste_type_id']) ? 'field--error' : '' ?>">
                                <label for="waste_type_id">Jenis</label>
                                <select id="waste_type_id" name="waste_type_id" required>
                                    <option value="">Pilih jenis</option>
                                    <?php foreach ($jenis as $j) : ?>
                                        <option value="<?= (int)$j['id'] ?>" <?= (int)($_POST['waste_type_id'] ?? 0) === (int)$j['id'] ? 'selected' : '' ?>><?= e($j['nama']) ?> · <?= rupiah($j['harga_per_kg']) ?>/kg</option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['waste_type_id'])) : ?><span class="field__error"><?= e($errors['waste_type_id']) ?></span><?php endif; ?>
                            </div>
                            <div class="field <?= isset($errors['berat_kg']) ? 'field--error' : '' ?>">
                                <label for="berat_kg">Berat (kg)</label>
                                <input id="berat_kg" name="berat_kg" type="text" inputmode="decimal" value="<?= old('berat_kg') ?>" placeholder="2,50" required>
                                <?php if (isset($errors['berat_kg'])) : ?><span class="field__error"><?= e($errors['berat_kg']) ?></span><?php endif; ?>
                            </div>
                        </div>
                        <button class="btn btn--primary" type="submit">Simpan setoran</button>
                    </form>
                </div>
            </div>

            <div>
                <div class="panel">
                    <h3>Penarikan menunggu</h3>
                    <?php if ($antrean) : ?>
                        <div class="table-wrap">
                            <table class="data">
                                <thead><tr><th>Warga</th><th>Jumlah</th><th>Tanggal</th><th></th></tr></thead>
                                <tbody>
                                <?php foreach ($antrean as $a) : ?>
                                    <tr>
                                        <td><?= e($a['warga']) ?><br><span class="small muted"><?= e($a['rw'] ?? '-') ?></span></td>
                                        <td><?= rupiah($a['jumlah']) ?></td>
                                        <td><?= tanggal($a['created_at']) ?></td>
                                        <td>
                                            <form method="post" action="<?= BASE ?>/sampah/cairkan.php">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                                <button class="btn btn--outline btn--sm" type="submit">Cairkan</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else : ?>
                        <div class="empty"><b>Tidak ada penarikan.</b>Antrean pencairan kosong.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
