<?php
require_once __DIR__ . '/../config/auth.php';
$u = require_login();

$title = 'Pengajuan bantuan';
$statusList = ['diajukan', 'diverifikasi', 'disetujui', 'ditolak'];
$jenisList = ['pangan', 'tunai', 'kesehatan', 'pendidikan'];
$fStatus = in_array($_GET['status'] ?? '', $statusList, true) ? $_GET['status'] : '';
$fJenis = in_array($_GET['jenis'] ?? '', $jenisList, true) ? $_GET['jenis'] : '';

$sql = 'SELECT a.*, us.nama AS warga, rw.nama AS rw, fc.status AS cek_status
    FROM aid_requests a
    JOIN users us ON us.id = a.user_id
    LEFT JOIN rw ON rw.id = us.rw_id
    LEFT JOIN family_checks fc ON fc.id = a.check_id';
$params = [];
$where = [];
if ($u['role'] === 'warga') {
    $where[] = 'a.user_id = ?';
    $params[] = $u['id'];
} elseif ($u['role'] === 'petugas') {
    $where[] = 'us.rw_id = ?';
    $params[] = $u['rw_id'];
}
if ($fStatus) {
    $where[] = 'a.status = ?';
    $params[] = $fStatus;
}
if ($fJenis) {
    $where[] = 'a.jenis = ?';
    $params[] = $fJenis;
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
if ($u['role'] === 'warga') {
    $sql .= ' ORDER BY a.created_at DESC';
} else {
    $sql .= " ORDER BY FIELD(fc.status, 'rentan', 'dipantau', 'cukup') DESC, a.created_at ASC";
}
$st = $pdo->prepare($sql);
$st->execute($params);
$data = $st->fetchAll();
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow"><?= $u['role'] === 'warga' ? 'Pengajuan saya' : 'Antrean pengajuan' ?></p>
                <h1><?= $u['role'] === 'warga' ? 'Bantuan yang saya ajukan.' : 'Pengajuan yang menunggu.' ?></h1>
                <p class="small"><?= $u['role'] === 'warga' ? 'Pantau status dan catatan petugas.' : 'Warga berstatus rentan diurutkan lebih dulu, lalu yang paling lama menunggu.' ?></p>
            </div>
            <?php if ($u['role'] === 'warga' && file_exists(ROOT_DIR . '/bantuan/ajukan.php')) : ?>
                <a class="btn btn--primary btn--sm" href="<?= BASE ?>/bantuan/ajukan.php">Ajukan baru</a>
            <?php endif; ?>
        </div>

        <?php include ROOT_DIR . '/includes/legenda_status.php'; ?>

        <form method="get" class="form-card" style="padding:1rem">
            <div class="form-grid">
                <div class="field" style="margin-bottom:0">
                    <label for="status">Status</label>
                    <select id="status" name="status" onchange="this.form.submit()">
                        <option value="">Semua status</option>
                        <?php foreach ($statusList as $s) : ?>
                            <option value="<?= $s ?>" <?= $fStatus === $s ? 'selected' : '' ?>><?= e(STATUS_LABEL[$s]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0">
                    <label for="jenis">Jenis</label>
                    <select id="jenis" name="jenis" onchange="this.form.submit()">
                        <option value="">Semua jenis</option>
                        <?php foreach ($jenisList as $j) : ?>
                            <option value="<?= $j ?>" <?= $fJenis === $j ? 'selected' : '' ?>><?= ucfirst($j) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <noscript><button class="btn btn--outline btn--sm mt-2" type="submit">Terapkan</button></noscript>
        </form>

        <?php if ($data) : ?>
            <div class="table-wrap mt-3">
                <table class="data">
                    <thead><tr>
                        <th>Kode</th>
                        <?php if ($u['role'] !== 'warga') : ?><th>Warga</th><th>RW</th><?php endif; ?>
                        <th>Jenis</th><th>Diajukan</th><th>Status</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($data as $a) : ?>
                        <tr>
                            <td><b><?= kode_pengajuan((int)$a['id']) ?></b></td>
                            <?php if ($u['role'] !== 'warga') : ?>
                                <td><?= e($a['warga']) ?><?= $a['cek_status'] ? '<br><span class="small muted">' . e(STATUS_LABEL[$a['cek_status']] ?? $a['cek_status']) . '</span>' : '' ?></td>
                                <td><?= e($a['rw'] ?? '-') ?></td>
                            <?php endif; ?>
                            <td><?= ucfirst(e($a['jenis'])) ?></td>
                            <td><?= tanggal($a['created_at']) ?></td>
                            <td><?= status_chip($a['status']) ?></td>
                            <td><a class="btn btn--ghost btn--sm" href="<?= BASE ?>/bantuan/detail.php?id=<?= (int)$a['id'] ?>">Detail</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="empty mt-3"><b>Belum ada pengajuan.</b><?= $u['role'] === 'warga' ? 'Mulai dengan mengecek kondisi keluarga.' : 'Antrean kosong untuk filter ini.' ?></div>
        <?php endif; ?>
    </div>
</section>

<?php include ROOT_DIR . '/includes/footer.php'; ?>
