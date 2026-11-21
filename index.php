<?php
require_once __DIR__ . '/config/auth.php';

$title = 'Beranda';
$active = 'beranda';
$stats = public_stats($pdo);
$s = $stats['ringkas'];

$laporan = $pdo->query("SELECT r.judul, r.kategori, r.lokasi_teks, r.status, r.created_at, rw.nama AS rw
    FROM reports r JOIN rw ON rw.id = r.rw_id ORDER BY r.created_at DESC LIMIT 5")->fetchAll();
$lapak = $pdo->query("SELECT nama_usaha, kategori, deskripsi, foto FROM businesses
    WHERE status = 'tayang' AND tampil = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
$pengumuman = $pdo->query("SELECT id, tipe, judul, isi, tanggal_acara, lokasi FROM announcements
    WHERE (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
    ORDER BY sematkan DESC, created_at DESC LIMIT 3")->fetchAll();

$icon = fn(string $d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
$layanan = [
    ['cek', 'Cek kondisi keluarga', 'Ketahui posisi pengeluaran keluarga terhadap garis batas dan langkah berikutnya.', '/cek-kondisi/index.php', 'sage', '<path d="M12 21s-7-4.5-7-9a4 4 0 0 1 7-2.6A4 4 0 0 1 19 12c0 4.5-7 9-7 9z"/>'],
    ['bantuan', 'Pengajuan bantuan', 'Ajukan bantuan pangan, tunai, kesehatan, atau pendidikan dan pantau statusnya.', '/bantuan/ajukan.php', 'peach', '<rect x="3" y="8" width="18" height="13" rx="2"/><path d="M3 12h18M12 8v13M9 8 8 5h8l-1 3"/>'],
    ['lapor', 'Lapor warga', 'Laporkan lampu padam, selokan tersumbat, atau sampah menumpuk dengan foto.', '/lapor/index.php', 'sky', '<path d="M12 3 21 20H3z"/><path d="M12 9v4M12 16h.01"/>'],
    ['usulan', 'Usulan warga', 'Usulkan perbaikan lingkungan dan kumpulkan dukungan tetangga.', '/usulan/index.php', 'lavender', '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>'],
    ['sampah', 'Bank sampah', 'Setor sampah terpilah, dapatkan saldo, dan ajukan pencairan.', '/sampah/index.php', 'sage', '<path d="M7 7h10M9 7V5h6v2M6 7l1 13h10l1-13M10 11v5M14 11v5"/>'],
    ['pangan', 'Bank pangan', 'Bagikan makanan layak berlebih atau ambil porsi untuk keluarga.', '/pangan/index.php', 'peach', '<path d="M3 12h18a9 9 0 0 1-18 0z"/><path d="M8 8c0-2 2-2 2-4M13 8c0-2 2-2 2-4"/>'],
    ['siaga', 'Siaga bencana', 'Temukan titik kumpul, kontak darurat, dan konfirmasi keselamatan.', '/siaga/index.php', 'sky', '<path d="M12 3 20 6v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>'],
    ['lapak', 'Lapak usaha warga', 'Daftarkan usaha kecil warga dan tampilkan di etalase lingkungan.', '/lapak/index.php', 'lavender', '<path d="M4 9h16l-1-5H5z"/><path d="M5 9v11h14V9M9 20v-6h6v6"/>'],
    ['capaian', 'Capaian bersama', 'Lihat angka dan grafik dampak layanan warga secara terbuka.', '/capaian/index.php', 'sage', '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>'],
];
?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="hero">
    <div class="container hero__grid">
        <div class="hero__copy">
            <p class="eyebrow">Platform layanan warga </p>
            <h1>Hidup lebih baik, di komunitas yang lebih baik.</h1>
            <p class="lead">Satu pintu untuk mengecek kondisi keluarga, mengajukan bantuan, melaporkan masalah lingkungan, dan berbagi sumber daya bersama warga RW.</p>
            <div class="hero__actions">
                <a class="btn btn--primary" href="<?= BASE ?>/<?= user() ? 'dashboard/' . e(user()['role']) . '.php' : 'auth/register.php' ?>">
                    <?= user() ? 'Buka dashboard' : 'Gabung sebagai warga' ?>
                </a>
                <a class="btn btn--outline" href="#layanan">Lihat layanan</a>
            </div>
            <div class="hero__stats">
                <div class="hero__stat"><b><?= number_format($s['rw']) ?></b><span>RW terhubung</span></div>
                <div class="hero__stat"><b><?= number_format($s['laporan_total']) ?></b><span>laporan warga</span></div>
                <div class="hero__stat"><b><?= number_format($s['sampah_kg'], 1, ',', '.') ?> kg</b><span>sampah terpilah</span></div>
                <div class="hero__stat"><b><?= number_format($s['keluarga_terbantu']) ?></b><span>keluarga terbantu</span></div>
            </div>
        </div>

        <div class="hero__visual" aria-hidden="true">
            <div class="collage">
                <svg class="collage__skyline" viewBox="0 0 520 220" preserveAspectRatio="none">
                    <g fill="#131E3A" opacity=".82">
                        <rect x="20" y="110" width="52" height="110" rx="3"/>
                        <rect x="82" y="70" width="44" height="150" rx="3"/>
                        <rect x="136" y="126" width="60" height="94" rx="3"/>
                        <rect x="208" y="44" width="48" height="176" rx="3"/>
                        <rect x="266" y="96" width="54" height="124" rx="3"/>
                        <rect x="330" y="62" width="42" height="158" rx="3"/>
                        <rect x="382" y="120" width="58" height="100" rx="3"/>
                        <rect x="450" y="84" width="46" height="136" rx="3"/>
                    </g>
                    <g fill="#EAEFF5" opacity=".5">
                        <rect x="28" y="120" width="8" height="8"/><rect x="44" y="120" width="8" height="8"/>
                        <rect x="90" y="82" width="8" height="8"/><rect x="106" y="82" width="8" height="8"/>
                        <rect x="216" y="56" width="8" height="8"/><rect x="232" y="56" width="8" height="8"/>
                        <rect x="338" y="74" width="8" height="8"/><rect x="354" y="74" width="8" height="8"/>
                    </g>
                    <circle cx="430" cy="46" r="26" fill="#F3E9D2"/>
                </svg>
            </div>
            <div class="glass-card glass-card--a">
                <span>Ditanggapi petugas</span>
                <b><?= $s['ditanggapi_persen'] ?>%</b>
                <span class="row"><i class="dot"></i> laporan warga</span>
            </div>
            <div class="glass-card glass-card--b">
                <span>Usaha warga tayang</span>
                <b><?= number_format($s['usaha']) ?></b>
                <span>lapak di etalase RW</span>
            </div>
        </div>
    </div>
</section>

<section class="values">
    <div class="container values__grid">
        <div class="value"><span class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></span><b>Transparan</b><span>Status dan capaian terbuka untuk warga.</span></div>
        <div class="value"><span class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span><b>Terukur</b><span>Setiap angka berasal dari catatan nyata.</span></div>
        <div class="value"><span class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9h6l3 3 3-3h6"/><path d="M3 15h6l3 3 3-3h6"/></svg></span><b>Gotong royong</b><span>Warga saling bantu lewat pangan dan usaha.</span></div>
        <div class="value"><span class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-5h4v5"/></svg></span><b>Dekat</b><span>Layanan dalam satu RW, satu pintu.</span></div>
    </div>
</section>

<section class="section" id="layanan">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Layanan</p>
                <h2>Sembilan layanan, satu pintu.</h2>
            </div>
            <p>Pilih layanan yang dibutuhkan. Sebagian layanan memerlukan akun warga agar data tetap aman dan hanya terlihat oleh pemilik serta petugas.</p>
        </div>
        <div class="svc-grid">
            <?php foreach ($layanan as [$key, $nama, $desc, $file, $tone, $path]) :
                $ada = file_exists(ROOT_DIR . $file);
                $tag = $ada ? 'a' : 'div';
                $href = $ada ? ' href="' . BASE . $file . '"' : ''; ?>
                <<?= $tag ?> class="svc svc--<?= $tone ?><?= $ada ? '' : ' svc--soon' ?>"<?= $href ?>>
                    <span class="svc__visual"><?= $icon($path) ?></span>
                    <h4><?= e($nama) ?></h4>
                    <p><?= e($desc) ?></p>
                    <span class="svc__link"><?= $ada ? 'Buka layanan →' : 'Segera hadir' ?></span>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--tint">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Cara kerja</p>
                <h2>Dari laporan menjadi tindakan.</h2>
            </div>
            <p>Satu alur sederhana: ajukan atau laporkan, petugas memproses, lalu pantau statusnya.</p>
        </div>
        <div class="steps">
            <div class="step"><b>Sampaikan lewat satu pintu</b><p>Isi form layanan dengan foto dan lokasi. Data langsung masuk antrean petugas RW.</p></div>
            <div class="step"><b>Petugas memproses</b><p>Setiap perubahan status dicatat beserta pelaku dan waktunya, lengkap dengan catatan.</p></div>
            <div class="step"><b>Pantau sampai selesai</b><p>Lihat riwayat di dashboard warga dan angka capaian publik di beranda.</p></div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Laporan terbaru</p>
                <h2>Apa yang sedang ditangani.</h2>
            </div>
            <p>Laporan tampil tanpa nama pelapor demi privasi warga.</p>
        </div>
        <?php if ($laporan) : ?>
            <div class="report-list">
                <?php foreach ($laporan as $r) : ?>
                    <div class="report-row">
                        <span class="chip chip--netral"><?= ucfirst(e($r['kategori'])) ?></span>
                        <div class="report-row__main">
                            <b><?= e($r['judul']) ?></b>
                            <span class="report-row__meta"><?= e($r['lokasi_teks']) ?> · <?= e($r['rw']) ?> · <?= tanggal($r['created_at']) ?></span>
                        </div>
                        <div class="report-row__side"><?= status_chip($r['status']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="empty"><b>Belum ada laporan.</b>Buat laporan pertama untuk lingkunganmu.</div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--tint">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Pengumuman &amp; agenda</p>
                <h2>Kabar terbaru dari RW.</h2>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/pengumuman/index.php">Semua pengumuman</a>
        </div>
        <?php if ($pengumuman) : ?>
            <div class="lapak-grid">
                <?php foreach ($pengumuman as $a) : ?>
                    <article class="panel">
                        <span class="chip chip--<?= $a['tipe'] === 'agenda' ? 'info' : ($a['tipe'] === 'tips' ? 'accent' : 'netral') ?>"><?= ucfirst(e($a['tipe'])) ?></span>
                        <h4 class="mt-1"><?= e($a['judul']) ?></h4>
                        <p class="small"><?= e(mb_strimwidth($a['isi'], 0, 120, '…')) ?></p>
                        <?php if ($a['tanggal_acara']) : ?>
                            <p class="small muted"><?= tanggal($a['tanggal_acara'], true) ?><?= $a['lokasi'] ? ' · ' . e($a['lokasi']) : '' ?></p>
                        <?php endif; ?>
                        <a class="btn btn--outline btn--sm" href="<?= BASE ?>/pengumuman/detail.php?id=<?= (int)$a['id'] ?>">Baca</a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="empty"><b>Belum ada pengumuman.</b>Informasi dari petugas RW akan tampil di sini.</div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--tint">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Capaian bersama</p>
                <h2>Dampak yang bisa dilihat siapa saja.</h2>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/capaian/index.php">Lihat capaian lengkap</a>
        </div>
        <div class="metric-grid">
            <div class="metric metric--sage"><span>Laporan selesai</span><b><?= number_format($s['laporan_selesai']) ?></b><span>dari <?= number_format($s['laporan_total']) ?> laporan</span></div>
            <div class="metric metric--sky"><span>Sampah terpilah</span><b><?= number_format($s['sampah_kg'], 1, ',', '.') ?> kg</b><span>disetor ke bank sampah</span></div>
            <div class="metric metric--peach"><span>Keluarga terbantu</span><b><?= number_format($s['keluarga_terbantu']) ?></b><span>pengajuan disetujui</span></div>
            <div class="metric metric--lavender"><span>Porsi pangan</span><b><?= number_format($s['porsi_pangan']) ?></b><span>tersalurkan antar warga</span></div>
        </div>
        <div class="chart-grid">
            <div class="chart-card">
                <h4>Sampah terpilah per bulan</h4>
                <p class="small">Kilogram sampah yang disetor warga.</p>
                <div class="chart-holder">
                    <canvas data-chart="sampah" role="img" aria-label="Grafik batang sampah terpilah per bulan"></canvas>
                    <div class="chart-empty" data-empty="sampah" hidden>Belum ada data</div>
                </div>
            </div>
            <div class="chart-card">
                <h4>Laporan selesai per bulan</h4>
                <p class="small">Jumlah laporan warga yang tuntas ditangani.</p>
                <div class="chart-holder">
                    <canvas data-chart="laporan" role="img" aria-label="Grafik garis laporan selesai per bulan"></canvas>
                    <div class="chart-empty" data-empty="laporan" hidden>Belum ada data</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Lapak warga</p>
                <h2>Usaha tetangga, dekat dari rumah.</h2>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/lapak/index.php">Semua lapak</a>
        </div>
        <?php if ($lapak) : ?>
            <div class="lapak-grid">
                <?php foreach ($lapak as $l) : ?>
                    <article class="lapak">
                        <img class="lapak__img" src="<?= foto_url($l['foto']) ?>" alt="" loading="lazy">
                        <div class="lapak__body">
                            <span class="chip chip--accent"><?= ucfirst(e($l['kategori'])) ?></span>
                            <h4><?= e($l['nama_usaha']) ?></h4>
                            <p><?= e($l['deskripsi']) ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="empty"><b>Belum ada lapak tayang.</b>Lapak warga akan tampil setelah diverifikasi admin.</div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <section class="cta">
        <div>
            <p class="eyebrow eyebrow--light">Mulai hari ini</p>
            <h2>Satu akun untuk semua layanan RW.</h2>
            <p>Cek kondisi keluarga, ajukan bantuan, dan laporkan masalah lingkungan. Data pribadi hanya terlihat olehmu dan petugas RW.</p>
            <a class="btn btn--light" href="<?= BASE ?>/<?= user() ? 'dashboard/' . e(user()['role']) . '.php' : 'auth/register.php' ?>"><?= user() ? 'Buka dashboard saya' : 'Daftar sebagai warga' ?></a>
        </div>
        <div class="glass-card">
            <blockquote>“Laporan lampu mati di gang kami selesai dalam tiga hari. Statusnya bisa saya pantau tanpa ke kantor RW.”</blockquote>
            <span class="small">Warga RW 05 · data contoh</span>
        </div>
    </section>
</div>

<script>window.HARMONIA_API = <?= json_encode(BASE . '/api/stats.php') ?>;</script>
<?php
$scripts = ['/assets/js/charts.js'];
include ROOT_DIR . '/includes/footer.php';
