<?php
require_once __DIR__ . '/../config/auth.php';
$u = user();
$active = $active ?? '';
$bannerSiaga = null;
if (file_exists(ROOT_DIR . '/pengumuman/detail.php')) {
    $stBanner = $pdo->prepare("SELECT id, judul FROM announcements
        WHERE siaga = 1 AND (berlaku_sampai IS NULL OR berlaku_sampai >= CURDATE())
          AND (rw_id IS NULL OR rw_id = ?)
        ORDER BY created_at DESC LIMIT 1");
    $stBanner->execute([(int)($u['rw_id'] ?? 0)]);
    $bannerSiaga = $stBanner->fetch();
}
$navItems = [
    ['beranda', 'Beranda', '/index.php'],
    ['lapor', 'Lapor', '/lapor/index.php'],
    ['usulan', 'Usulan', '/usulan/index.php'],
    ['lapak', 'Lapak', '/lapak/index.php'],
    ['capaian', 'Capaian', '/capaian/index.php'],
    ['pengumuman', 'Pengumuman', '/pengumuman/index.php'],
];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Beranda') ?> · HARMONIA</title>
<meta name="description" content="HARMONIA — satu pintu layanan warga: cek kondisi keluarga, pengajuan bantuan, laporan lingkungan, bank sampah, bank pangan, dan lapak usaha warga.">
<link rel="stylesheet" href="<?= BASE ?>/assets/css/style.css">
<link rel="icon" href="<?= BASE ?>/assets/img/favicon.svg">
</head>
<body>
<a class="skip-link" href="#main">Lewati ke konten</a>

<?php if ($bannerSiaga) : ?>
    <a class="siaga-banner" href="<?= BASE ?>/pengumuman/detail.php?id=<?= (int)$bannerSiaga['id'] ?>">
        Siaga: <?= e($bannerSiaga['judul']) ?> — baca selengkapnya
    </a>
<?php endif; ?>

<div class="topbar">
    <div class="container topbar__in">
        <span>Layanan warga RW · SDGs 1 &amp; 11</span>
        <span class="hide-sm">Senin–Jumat, 08.00–16.00 · Kantor RW</span>
    </div>
</div>

<header class="site-header">
    <div class="container nav">
        <a class="brand" href="<?= BASE ?>/index.php">
            <span class="brand__mark" aria-hidden="true">H</span>
            <span class="brand__text">HARMONIA<small>A Better Life in a Better Community</small></span>
        </a>

        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="nav-menu" aria-label="Buka menu">
            <span aria-hidden="true">☰</span>
        </button>

        <nav id="nav-menu" class="nav__menu" aria-label="Navigasi utama">
            <?php foreach ($navItems as [$key, $label, $file]) :
                if (!file_exists(ROOT_DIR . $file)) {
                    continue;
                } ?>
                <a href="<?= BASE . $file ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="nav__actions">
            <?php if ($u) : ?>
                <a class="btn btn--ghost btn--sm" href="<?= BASE ?>/dashboard/<?= e($u['role']) ?>.php">Dashboard</a>
                <form method="post" action="<?= BASE ?>/auth/logout.php" class="inline-form">
                    <?= csrf_field() ?>
                    <button class="btn btn--outline btn--sm" type="submit">Keluar</button>
                </form>
            <?php else : ?>
                <a class="btn btn--ghost btn--sm hide-sm" href="<?= BASE ?>/auth/login.php">Masuk</a>
                <a class="btn btn--primary btn--sm" href="<?= BASE ?>/auth/register.php">Daftar</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main id="main">
<?php foreach (take_flashes() as $f) : ?>
    <div class="container">
        <div class="alert alert--<?= e($f['tipe']) ?>" role="status"><?= e($f['pesan']) ?></div>
    </div>
<?php endforeach; ?>
