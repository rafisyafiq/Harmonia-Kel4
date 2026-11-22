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
    ['cek', 'Cek Kondisi Keluarga', 'Ketahui posisi pengeluaran keluarga terhadap garis batas dan langkah berikutnya.', '/cek-kondisi/index.php', 'sage', '<path d="M12 21s-7-4.5-7-9a4 4 0 0 1 7-2.6A4 4 0 0 1 19 12c0 4.5-7 9-7 9z"/>', BASE . '/assets/images/svc-cek-kondisi.png', true],
    ['bantuan', 'Pengajuan Bantuan', 'Ajukan bantuan pangan, tunai, kesehatan, atau pendidikan dan pantau statusnya.', '/bantuan/ajukan.php', 'peach', '<rect x="3" y="8" width="18" height="13" rx="2"/><path d="M3 12h18M12 8v13M9 8 8 5h8l-1 3"/>', BASE . '/assets/images/svc-pengajuan-bantuan.png', true],
    ['lapor', 'Lapor Warga', 'Laporkan lampu padam, selokan tersumbat, atau sampah menumpuk dengan foto.', '/lapor/index.php', 'sky', '<path d="M12 3 21 20H3z"/><path d="M12 9v4M12 16h.01"/>', BASE . '/assets/images/svc-lapor-warga.png', false],
    ['usulan', 'Usulan Warga', 'Usulkan perbaikan lingkungan dan kumpulkan dukungan tetangga.', '/usulan/index.php', 'lavender', '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>', BASE . '/assets/images/svc-usulan-warga.png', false],
    ['sampah', 'Bank Sampah', 'Setor sampah terpilah, dapatkan saldo, dan ajukan pencairan.', '/sampah/index.php', 'sage', '<path d="M7 7h10M9 7V5h6v2M6 7l1 13h10l1-13M10 11v5M14 11v5"/>', BASE . '/assets/images/svc-bank-sampah.png', true],
    ['sembako', 'Bantuan Sosial', 'Bagikan sembako (beras, minyak, gula) untuk warga yang membutuhkan.', '/sembako/index.php', 'peach', '<path d="M3 12h18a9 9 0 0 1-18 0z"/><path d="M8 8c0-2 2-2 2-4M13 8c0-2 2-2 2-4"/>', BASE . '/assets/images/svc-bantuan-sosial.jpg', true],
    ['siaga', 'Siaga Bencana', 'Temukan titik kumpul, kontak darurat, dan konfirmasi keselamatan.', '/siaga/index.php', 'sky', '<path d="M12 3 20 6v6c0 5-4 8-8 9-4-1-8-4-8-9V6z"/><path d="m9 12 2 2 4-4"/>', BASE . '/assets/images/svc-siaga-bencana.png', false],
    ['lapak', 'Lapak Usaha Warga', 'Daftarkan usaha kecil warga dan tampilkan di etalase lingkungan.', '/lapak/index.php', 'lavender', '<path d="M4 9h16l-1-5H5z"/><path d="M5 9v11h14V9M9 20v-6h6v6"/>', BASE . '/assets/images/svc-lapak-usaha.png', false],
    ['capaian', 'Capaian Bersama', 'Lihat angka dan grafik dampak layanan warga secara terbuka.', '/capaian/index.php', 'sage', '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>', BASE . '/assets/images/svc-capaian-bersama.png', false],
];

// tampilkan semua layanan; layanan khusus warga tetap terlihat (akan diminta login saat digunakan).

?>
<?php include ROOT_DIR . '/includes/header.php'; ?>

<section class="hero hero--image">
    <div class="hero__bg" aria-hidden="true"></div>
    <div class="container hero__grid">
        <div class="hero__copy">
            <h1>Hidup Lebih Baik, di Komunitas yang Lebih Baik</h1>
            <p class="lead">Satu pintu untuk mengecek kondisi keluarga, mengajukan bantuan, melaporkan masalah lingkungan, dan berbagi sumber daya bersama warga</p>
            <div class="hero__actions">
                <a class="btn btn--glass" href="<?= BASE ?>/<?= user() ? 'dashboard/' . e(user()['role']) . '.php' : 'auth/register.php' ?>">
                    <?= user() ? 'Buka dashboard' : 'Gabung Sebagai Warga' ?>
                </a>
                <a class="btn btn--glass btn--glass-outline" href="#layanan">Lihat Layanan</a>
            </div>
            <div class="hero__stats">
                <div class="hero__stat"><b><?= number_format($s['rw']) ?></b><span>RW Terhubung</span></div>
                <div class="hero__stat"><b><?= number_format($s['laporan_total']) ?></b><span>Laporan Warga</span></div>
                <div class="hero__stat"><b><?= number_format($s['keluarga_terbantu']) ?></b><span>Keluarga Terbantu</span></div>
            </div>
        </div>
    </div>
</section>

<section class="values reveal">
    <div class="container values__grid">
        <div class="value"><span class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></span><b>Transparan</b><span>Status dan capaian terbuka untuk warga</span></div>
        <div class="value"><span class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg></span><b>Terukur</b><span>Setiap angka berasal dari catatan nyata</span></div>
        <div class="value"><span class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9h6l3 3 3-3h6"/><path d="M3 15h6l3 3 3-3h6"/></svg></span><b>Gotong Royong</b><span>Warga saling bantu lewat pangan dan usaha</span></div>
        <div class="value"><span class="value__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-5h4v5"/></svg></span><b>Dekat</b><span>Layanan dalam satu RW, satu pintu</span></div>
    </div>
</section>

<section class="section section--tint reveal" id="layanan">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Layanan</p>
                <h2>Layanan yang Tersedia</h2>
            </div>
        </div>
        <div class="svc-grid svc-slider">
            <?php foreach ($layanan as [$key, $nama, $desc, $file, $tone, $path, $img, $wargaOnly]) :
                $ada = file_exists(ROOT_DIR . $file);
                $tag = $ada ? 'a' : 'div';
                $href = $ada ? ' href="' . BASE . $file . '"' : ''; ?>
                <<?= $tag ?> class="svc svc--<?= $tone ?><?= $ada ? '' : ' svc--soon' ?>"<?= $href ?>>
                    <span class="svc__visual">
                        <?php if ($img) : ?>
                            <img src="<?= e($img) ?>" alt="" loading="lazy" style="width:100%;height:100%;object-fit:contain;border-radius:inherit">
                        <?php else : ?>
                            <?= $icon($path) ?>
                        <?php endif; ?>
                    </span>
                    <h4><?= e($nama) ?></h4>
                    <p><?= e($desc) ?></p>
                    <span class="svc__link"><?= $ada ? 'Buka layanan →' : 'Segera hadir' ?></span>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section reveal">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow"></p>
                <h2>Dari Laporan Menjadi Tindakan</h2>
            </div>
        </div>
        <div class="steps">
            <div class="step"><b>Sampaikan Lewat Satu Pintu</b><p>Isi form layanan dengan foto dan lokasi. Data langsung masuk antrean petugas RW</p></div>
            <div class="step"><b>Petugas Memproses</b><p>Setiap perubahan status dicatat beserta pelaku dan waktunya, lengkap dengan catatan</p></div>
            <div class="step"><b>Pantau sampai Selesai</b><p>Lihat riwayat di dashboard warga dan angka capaian publik di beranda</p></div>
        </div>
    </div>
</section>

<section class="section section--tint reveal">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Laporan Terbaru</p>
                <h2>Apa yang Sedang Ditangani</h2>
            </div>
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
            <div class="empty"><b>Belum Ada Laporan</b>Buat laporan pertama untuk lingkunganmu.</div>
        <?php endif; ?>
    </div>
</section>

<section class="section reveal">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Pengumuman &amp; Agenda</p>
                <h2>Kabar Terbaru dari RW</h2>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/pengumuman/index.php">Semua Pengumuman</a>
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
            <div class="empty"><b>Belum Ada Pengumuman</b>Informasi dari petugas RW akan tampil di sini.</div>
        <?php endif; ?>
    </div>
</section>

<section class="section section--tint reveal">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Capaian Bersama</p>
                <h2>Dampak yang Bisa Dilihat Siapa Saja</h2>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/capaian/index.php">Lihat Capaian Lengkap</a>
        </div>
        <div class="metric-grid">
            <div class="metric"><span>Laporan Selesai</span><b><?= number_format($s['laporan_selesai']) ?></b><span>dari <?= number_format($s['laporan_total']) ?> laporan</span></div>
            <div class="metric"><span>Sampah Terpilah</span><b><?= number_format($s['sampah_kg'], 1, ',', '.') ?> kg</b><span>disetor ke bank sampah</span></div>
            <div class="metric"><span>Keluarga Terbantu</span><b><?= number_format($s['keluarga_terbantu']) ?></b><span>pengajuan disetujui</span></div>
            <div class="metric"><span>Paket Sembako</span><b><?= number_format($s['sembako_porsi']) ?></b><span>tersalurkan antar warga</span></div>
        </div>
        <div class="chart-grid">
            <div class="chart-card">
                <h4>Sampah Terpilah per Bulan</h4>
                <p class="small">Kilogram sampah yang disetor warga.</p>
                <div class="chart-holder">
                    <canvas data-chart="sampah" role="img" aria-label="Grafik batang sampah terpilah per bulan"></canvas>
                    <div class="chart-empty" data-empty="sampah" hidden>Belum ada data</div>
                </div>
            </div>
            <div class="chart-card">
                <h4>Laporan Selesai per Bulan</h4>
                <p class="small">Jumlah laporan warga yang tuntas ditangani.</p>
                <div class="chart-holder">
                    <canvas data-chart="laporan" role="img" aria-label="Grafik garis laporan selesai per bulan"></canvas>
                    <div class="chart-empty" data-empty="laporan" hidden>Belum ada data</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section reveal">
    <div class="container">
        <div class="section__head">
            <div>
                <p class="eyebrow">Lapak Warga</p>
                <h2>Usaha Tetangga, Dekat dari Rumah</h2>
            </div>
            <a class="btn btn--outline btn--sm" href="<?= BASE ?>/lapak/index.php">Semua Lapak</a>
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
            <div class="empty"><b>Belum Ada Lapak Tayang</b>Lapak warga akan tampil setelah diverifikasi admin.</div>
        <?php endif; ?>
    </div>
</section>

<div class="container">
    <section class="cta reveal">
        <div>
            <p class="eyebrow eyebrow--light">Mulai Hari Ini</p>
            <h2>Satu Akun untuk Semua Layanan RW</h2>
            <p>Cek kondisi keluarga, ajukan bantuan, dan laporkan masalah lingkungan. Data pribadi hanya terlihat olehmu dan petugas RW.</p>
            <a class="btn btn--light" href="<?= BASE ?>/<?= user() ? 'dashboard/' . e(user()['role']) . '.php' : 'auth/register.php' ?>"><?= user() ? 'Buka dashboard saya' : 'Daftar sebagai warga' ?></a>
        </div>
        <div class="glass-card">
            <blockquote>“Laporan lampu mati di gang kami selesai dalam tiga hari. Statusnya bisa saya pantau tanpa ke kantor RW.”</blockquote>
            <span class="small">Warga RW 05</span>
        </div>
    </section>
</div>

<script>window.HARMONIA_API = <?= json_encode(BASE . '/api/stats.php') ?>;</script>
<script>
(function () {
    var slider = document.querySelector('.svc-slider');
    if (!slider) return;
    var timer = null;
    function step() {
        var card = slider.querySelector('.svc');
        var amount = card ? card.offsetWidth + 20 : 300;
        if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 4) {
            slider.scrollTo({ left: 0, behavior: 'smooth' });
        } else {
            slider.scrollBy({ left: amount, behavior: 'smooth' });
        }
    }
    timer = setInterval(step, 3000);
    slider.addEventListener('mouseenter', function () { clearInterval(timer); });
    slider.addEventListener('mouseleave', function () { timer = setInterval(step, 3000); });
})();
</script>
<?php
$scripts = ['/assets/js/charts.js'];
include ROOT_DIR . '/includes/footer.php';
