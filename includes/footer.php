<?php
$tahun = date('Y');
$layanan = [
    ['Cek kondisi keluarga', '/cek-kondisi/index.php'],
    ['Pengajuan bantuan', '/bantuan/ajukan.php'],
    ['Lapor masalah', '/lapor/index.php'],
    ['Bank sampah', '/sampah/index.php'],
    ['Bantuan sosial', '/sembako/index.php'],
    ['Lapak usaha warga', '/lapak/index.php'],
    ['Capaian bersama', '/capaian/index.php'],
];
?>
</main>

<footer class="site-footer">
    <div class="container footer__grid">
        <div class="footer__brand">
            <a class="brand brand--light" href="<?= BASE ?>/index.php">
                <span class="brand__mark" aria-hidden="true">H</span>
                <span class="brand__text">HARMONIA<small>A Better Life in a Better Community</small></span>
            </a>
            <p>Platform layanan warga berbasis gotong royong untuk lingkungan yang lebih adil, sehat, dan berkelanjutan.</p>
        </div>

        <div>
            <h3 class="footer__title">Layanan</h3>
            <ul class="footer__links">
                <?php foreach ($layanan as [$label, $file]) :
                    if (!file_exists(ROOT_DIR . $file)) {
                        continue;
                    } ?>
                    <li><a href="<?= BASE . $file ?>"><?= e($label) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div>
            <h3 class="footer__title">Informasi</h3>
            <ul class="footer__links">
                <?php if (file_exists(ROOT_DIR . '/pengumuman/index.php')) : ?>
                    <li><a href="<?= BASE ?>/pengumuman/index.php">Pengumuman</a></li>
                <?php endif; ?>
                <li><a href="<?= BASE ?>/capaian/index.php">Capaian bersama</a></li>
                <?php if (file_exists(ROOT_DIR . '/siaga/index.php')) : ?>
                    <li><a href="<?= BASE ?>/siaga/index.php">Siaga bencana</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div>
            <h3 class="footer__title">Kontak RW</h3>
            <p class="footer__muted">Kantor RW 05, Kelurahan Cibeunying<br>Senin–Jumat, 08.00–16.00<br>0812-0000-0002</p>
        </div>
    </div>
    <div class="container footer__bottom">
        <span>© <?= $tahun ?> HARMONIA · A Better Life in a Better Community</span>
    </div>
</footer>

<script src="<?= BASE ?>/assets/js/app.js" defer></script>
<script>
(function () {
    var els = document.querySelectorAll('section, .panel, .card, .metric, .svc, .value, .step, .lapak, .report-row, .chart-card, .cta');
    if (!els.length) return;
    els.forEach(function (el) { el.classList.add('reveal'); });
    if (!('IntersectionObserver' in window)) { els.forEach(function (el) { el.classList.add('is-visible'); }); return; }
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (e) { e.target.classList.toggle('is-visible', e.isIntersecting); });
    }, { threshold: 0.1 });
    els.forEach(function (el) { io.observe(el); });
})();
</script>
<?php if (!empty($scripts)) {
    foreach ((array)$scripts as $src) {
        echo '<script src="' . BASE . e($src) . '" defer></script>';
    }
} ?>
</body>
</html>
