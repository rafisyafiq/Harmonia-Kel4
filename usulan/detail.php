<?php
require_once __DIR__ . '/../config/auth.php';

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare('SELECT p.*, rw.nama AS rw FROM proposals p JOIN rw ON rw.id = p.rw_id WHERE p.id = ?');
$st->execute([$id]);
$p = $st->fetch();
if (!$p) {
    http_response_code(404);
    exit('Usulan tidak ditemukan.');
}

$st = $pdo->prepare('SELECT COUNT(*) FROM proposal_votes WHERE proposal_id = ?');
$st->execute([$id]);
$jumlah = (int)$st->fetchColumn();
$target = max(1, (int)$p['target_dukungan']);
$persen = min(100, (int)round($jumlah / $target * 100));

$u = user();
$sudah = false;
if ($u) {
    $st = $pdo->prepare('SELECT 1 FROM proposal_votes WHERE proposal_id = ? AND user_id = ?');
    $st->execute([$id, $u['id']]);
    $sudah = (bool)$st->fetchColumn();
}
$bolehDukung = $u && $u['role'] === 'warga' && (int)$u['rw_id'] === (int)$p['rw_id']
    && $p['status'] === 'dibuka' && !$sudah;
$bolehProses = $u && in_array($u['role'], ['petugas', 'admin'], true)
    && ($u['role'] === 'admin' || (int)$u['rw_id'] === (int)$p['rw_id'])
    && in_array($p['status'], ['dibuka', 'tercapai'], true);

$title = $p['judul'];
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="dash">
    <div class="container">
        <div class="dash__head">
            <div>
                <p class="eyebrow">Usulan · <?= e($p['rw']) ?></p>
                <h1><?= e($p['judul']) ?></h1>
                <p class="small">Dibuat <?= tanggal($p['created_at']) ?></p>
            </div>
            <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/usulan/index.php">Kembali</a>
        </div>

        <div class="dash-grid">
            <div>
                <div class="panel">
                    <h3>Isi Usulan</h3>
                    <p><?= nl2br(e($p['deskripsi'])) ?></p>
                    <?php if ($p['catatan_petugas']) : ?>
                        <div class="alert alert--info"><b>Catatan Petugas:</b> <?= e($p['catatan_petugas']) ?></div>
                    <?php endif; ?>
                </div>

                <?php if ($bolehProses) : ?>
                    <div class="panel">
                        <h3>Proses Usulan</h3>
                        <form method="post" action="<?= BASE ?>/usulan/proses.php" data-busy>
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <div class="field">
                                <label for="status">Keputusan</label>
                                <select id="status" name="status" required>
                                    <?php if ($p['status'] === 'tercapai') : ?>
                                        <option value="diteruskan">Teruskan ke RW</option>
                                    <?php endif; ?>
                                    <option value="ditolak">Tolak</option>
                                </select>
                            </div>
                            <div class="field">
                                <label for="catatan_petugas">Catatan <span class="hint">(wajib bila menolak)</span></label>
                                <textarea id="catatan_petugas" name="catatan_petugas" maxlength="200" data-counter="hitung-catatan"></textarea>
                                <span class="field__count" id="hitung-catatan">0 karakter</span>
                            </div>
                            <div class="form-actions">
                                <button class="btn btn--primary" type="submit">Simpan</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <div class="panel">
                    <h3>Dukungan Warga</h3>
                    <p><b id="dukungan-<?= $id ?>"><?= $jumlah ?></b> dari <?= $target ?> dukungan</p>
                    <div class="progress" aria-hidden="true"><span id="progress-<?= $id ?>" style="width:<?= $persen ?>%"></span></div>
                    <p class="mt-2" id="status-usulan-<?= $id ?>"><?= status_chip($p['status']) ?></p>

                    <?php if ($bolehDukung) : ?>
                        <button class="btn btn--primary btn--sm mt-2" type="button"
                                data-dukung="<?= $id ?>"
                                data-url="<?= BASE ?>/usulan/dukung.php"
                                data-csrf="<?= csrf_token() ?>"
                                data-target="<?= $target ?>">Dukung usulan ini</button>
                    <?php elseif ($u && $u['role'] === 'warga' && (int)$u['rw_id'] !== (int)$p['rw_id']) : ?>
                        <p class="small muted mt-2">Hanya warga RW yang sama yang dapat mendukung usulan ini.</p>
                    <?php elseif ($sudah) : ?>
                        <p class="small muted mt-2">Anda sudah mendukung usulan ini.</p>
                    <?php elseif ($p['status'] !== 'dibuka') : ?>
                        <p class="small muted mt-2">Dukungan ditutup karena status usulan sudah <?= e(strtolower(STATUS_LABEL[$p['status']])) ?>.</p>
                    <?php elseif (!$u) : ?>
                        <p class="small muted mt-2"><a href="<?= BASE ?>/auth/login.php">Masuk</a> sebagai warga RW ini untuk mendukung.</p>
                    <?php endif; ?>
                </div>

                <?php if ($u && (int)$p['user_id'] === (int)$u['id'] && $jumlah === 0) : ?>
                    <div class="panel">
                        <h3>Hapus Usulan</h3>
                        <p class="small">Usulan bisa dihapus selama belum ada dukungan.</p>
                        <form method="post" action="<?= BASE ?>/usulan/hapus.php" onsubmit="return confirm('Hapus usulan ini?')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button class="btn btn--danger btn--sm" type="submit">Hapus Usulan</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php
$scripts = ['/assets/js/usulan.js'];
include ROOT_DIR . '/includes/footer.php';
