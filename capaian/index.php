<?php
require_once __DIR__ . '/../config/auth.php';

$title = 'Capaian bersama';
$active = 'capaian';

$rentang = (string)($_GET['rentang'] ?? '6');
if (!in_array($rentang, ['30', '6', 'all'], true)) {
    $rentang = '6';
}
$fRw = (int)($_GET['rw'] ?? 0);
$rwList = $pdo->query('SELECT id, nama FROM rw ORDER BY nama')->fetchAll();
$rwNama = '';
foreach ($rwList as $r) {
    if ((int)$r['id'] === $fRw) {
        $rwNama = $r['nama'];
    }
}

$stats = public_stats($pdo, $rentang, $fRw);
$s = $stats['ringkas'];
$labelRentang = ['30' => '30 hari terakhir', '6' => 'enam bulan terakhir', 'all' => 'sejak pencatatan pertama'][$rentang];
$konteks = $rwNama ? ' · ' . $rwNama : ' · semua RW';
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Capaian bersama</p>
                <h1>Dampak yang terbuka untuk semua.</h1>
                <p class="lead">Semua angka di halaman ini dihitung langsung dari catatan layanan warga. Tidak ada data pribadi yang ditampilkan, hanya agregat.</p>
            </div>
            <button class="btn btn--outline no-print" type="button" onclick="window.print()">Cetak halaman</button>
        </div>

        <form method="get" class="form-card no-print" style="padding:1rem">
            <div class="form-grid">
                <div class="field" style="margin-bottom:0">
                    <label for="rentang">Rentang waktu</label>
                    <select id="rentang" name="rentang" onchange="this.form.submit()">
                        <option value="30" <?= $rentang === '30' ? 'selected' : '' ?>>30 hari terakhir</option>
                        <option value="6" <?= $rentang === '6' ? 'selected' : '' ?>>6 bulan terakhir</option>
                        <option value="all" <?= $rentang === 'all' ? 'selected' : '' ?>>Semua waktu</option>
                    </select>
                </div>
                <div class="field" style="margin-bottom:0">
                    <label for="rw">RW</label>
                    <select id="rw" name="rw" onchange="this.form.submit()">
                        <option value="">Semua RW</option>
                        <?php foreach ($rwList as $r) : ?>
                            <option value="<?= (int)$r['id'] ?>" <?= $fRw === (int)$r['id'] ? 'selected' : '' ?>><?= e($r['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <noscript><button class="btn btn--outline btn--sm mt-2" type="submit">Terapkan</button></noscript>
        </form>

        <p class="small muted">Menampilkan data <?= e($labelRentang) ?><?= e($konteks) ?>.</p>

        <div class="metric-grid mt-3">
            <div class="metric metric--sage"><span>Laporan selesai</span><b><?= number_format($s['laporan_selesai']) ?></b><span>dari <?= number_format($s['laporan_total']) ?> laporan</span></div>
            <div class="metric metric--sky"><span>Laporan ditanggapi</span><b><?= $s['ditanggapi_persen'] ?>%</b><span>laporan diproses atau selesai</span></div>
            <div class="metric metric--peach"><span>Keluarga terbantu</span><b><?= number_format($s['keluarga_terbantu']) ?></b><span>pengajuan bantuan disetujui</span></div>
            <div class="metric metric--lavender"><span>Usulan diteruskan</span><b><?= number_format($s['usulan_terwujud']) ?></b><span>usulan warga yang dijalankan</span></div>
            <div class="metric metric--sky"><span>Sampah terpilah</span><b><?= number_format($s['sampah_kg'], 1, ',', '.') ?> kg</b><span>disetor ke bank sampah</span></div>
            <div class="metric metric--peach"><span>Paket sembako</span><b><?= number_format($s['sembako_porsi']) ?></b><span>tersalurkan antar warga</span></div>
            <div class="metric metric--sage"><span>Usaha warga tayang</span><b><?= number_format($s['usaha']) ?></b><span>lapak di etalase RW</span></div>
            <div class="metric metric--lavender"><span>RW terhubung</span><b><?= number_format($s['rw']) ?></b><span>wilayah yang dilayani</span></div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <h4>Sampah terpilah</h4>
                <p class="small muted">Kilogram sampah yang disetor warga, <?= e($labelRentang) ?>.</p>
                <div class="chart-holder">
                    <canvas data-chart="sampah" role="img" aria-label="Grafik batang sampah terpilah per periode"></canvas>
                    <div class="chart-empty" data-empty="sampah" hidden>Belum ada data</div>
                </div>
            </div>
            <div class="chart-card">
                <h4>Laporan selesai</h4>
                <p class="small muted">Jumlah laporan warga yang tuntas, <?= e($labelRentang) ?>.</p>
                <div class="chart-holder">
                    <canvas data-chart="laporan" role="img" aria-label="Grafik garis laporan selesai per periode"></canvas>
                    <div class="chart-empty" data-empty="laporan" hidden>Belum ada data</div>
                </div>
            </div>
        </div>

        <div class="panel mt-3">
            <h3>Bagaimana angka ini dihitung?</h3>
            <ul class="list-plain">
                <li><span>Laporan selesai</span><span class="small muted">jumlah laporan berstatus Selesai</span></li>
                <li><span>Laporan ditanggapi</span><span class="small muted">laporan berstatus Diproses atau Selesai dibagi seluruh laporan</span></li>
                <li><span>Keluarga terbantu</span><span class="small muted">pengajuan bantuan berstatus Disetujui</span></li>
                <li><span>Sampah terpilah</span><span class="small muted">total berat setoran yang tidak dibatalkan</span></li>
            </ul>
            <p class="small muted mt-2">Data contoh untuk keperluan praktikum. Angka bukan penetapan resmi pemerintah.</p>
        </div>
    </div>
</section>

<script>window.HARMONIA_API = <?= json_encode(BASE . '/api/stats.php?rentang=' . rawurlencode($rentang) . '&rw=' . $fRw) ?>;</script>
<?php
$scripts = ['/assets/js/charts.js'];
include ROOT_DIR . '/includes/footer.php';
