<?php
declare(strict_types=1);

/* ---------------- tampilan ---------------- */

function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rupiah($n): string
{
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function tanggal(?string $dt, bool $dengan_jam = false): string
{
    if (!$dt) {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($dt);
    return date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t) . ($dengan_jam ? ' ' . date('H:i', $t) : '');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $tipe, string $pesan): void
{
    $_SESSION['flash'][] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

const STATUS_LABEL = [
    'baru' => 'Baru', 'diproses' => 'Diproses', 'selesai' => 'Selesai',
    'diajukan' => 'Diajukan', 'diverifikasi' => 'Diverifikasi', 'disetujui' => 'Disetujui', 'ditolak' => 'Ditolak',
    'dibuka' => 'Dibuka', 'tercapai' => 'Tercapai', 'diteruskan' => 'Diteruskan',
    'menunggu' => 'Menunggu', 'tayang' => 'Tayang',
    'tersedia' => 'Tersedia', 'habis' => 'Habis', 'kedaluwarsa' => 'Kedaluwarsa',
    'aman' => 'Aman', 'butuh_bantuan' => 'Butuh Bantuan',
    'rentan' => 'Rentan', 'dipantau' => 'Perlu Dipantau', 'cukup' => 'Cukup',
    'dicairkan' => 'Dicairkan',
];

function status_chip(string $status): string
{
    $nada = match ($status) {
        'selesai', 'disetujui', 'tayang', 'tercapai', 'diteruskan', 'aman', 'dicairkan' => 'ok',
        'ditolak', 'butuh_bantuan', 'rentan' => 'warn',
        'diproses', 'diverifikasi', 'dipantau' => 'info',
        default => 'netral',
    };
    return '<span class="chip chip--' . $nada . '">' . e(STATUS_LABEL[$status] ?? $status) . '</span>';
}

/* ---------------- data ---------------- */

function setting(string $key, string $default = '0'): string
{
    global $pdo;
    $st = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

/**
 * Angka capaian bersama. $rentang: '30' (30 hari), '6' (6 bulan), 'all' (semua).
 * $rw = 0 berarti semua RW. Nilai di luar daftar putih diabaikan.
 */
function public_stats(PDO $pdo, string $rentang = 'all', int $rw = 0): array
{
    if (!in_array($rentang, ['30', '6', 'all'], true)) {
        $rentang = 'all';
    }
    if ($rw > 0) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM rw WHERE id = ?');
        $st->execute([$rw]);
        if (!(int)$st->fetchColumn()) {
            $rw = 0;
        }
    }
    $cutoff = $rentang === '30' ? date('Y-m-d H:i:s', strtotime('-30 days'))
        : ($rentang === '6' ? date('Y-m-d H:i:s', strtotime('-6 months')) : null);

    // Kondisi waktu + RW. $hasRw = tabel punya kolom rw_id sendiri.
    $cond = function (string $t, bool $hasRw, string $userCol = 'user_id') use ($cutoff, $rw): array {
        $w = [];
        $a = [];
        if ($cutoff !== null) {
            $w[] = "$t.created_at >= ?";
            $a[] = $cutoff;
        }
        if ($rw) {
            $w[] = $hasRw ? "$t.rw_id = ?" : "$t.$userCol IN (SELECT id FROM users WHERE rw_id = ?)";
            $a[] = $rw;
        }
        return [$w ? ' AND ' . implode(' AND ', $w) : '', $a];
    };
    $count = function (string $sql, array $args) use ($pdo): int {
        $st = $pdo->prepare($sql);
        $st->execute($args);
        return (int)$st->fetchColumn();
    };

    [$c, $a] = $cond('r', true);
    $total = $count("SELECT COUNT(*) FROM reports r WHERE 1=1$c", $a);
    $baru = $count("SELECT COUNT(*) FROM reports r WHERE r.status = 'baru'$c", $a);
    $selesai = $count("SELECT COUNT(*) FROM reports r WHERE r.status = 'selesai'$c", $a);

    [$c, $a] = $cond('wd', false);
    $st = $pdo->prepare("SELECT COALESCE(SUM(wd.berat_kg),0) FROM waste_deposits wd WHERE wd.dibatalkan = 0$c");
    $st->execute($a);
    $sampah_kg = (float)$st->fetchColumn();

    [$c, $a] = $cond('ar', false);
    $keluarga = $count("SELECT COUNT(*) FROM aid_requests ar WHERE ar.status = 'disetujui'$c", $a);

    [$c, $a] = $cond('p', true);
    $usulan = $count("SELECT COUNT(*) FROM proposals p WHERE p.status = 'diteruskan'$c", $a);

    [$c, $a] = $cond('b', false);
    $usaha = $count("SELECT COUNT(*) FROM businesses b WHERE b.status = 'tayang' AND b.tampil = 1$c", $a);

    $w = [];
    $a = [];
    if ($cutoff !== null) {
        $w[] = 'sc.created_at >= ?';
        $a[] = $cutoff;
    }
    if ($rw) {
        $w[] = 'ss.rw_id = ?';
        $a[] = $rw;
    }
    $st = $pdo->prepare('SELECT COALESCE(SUM(sc.porsi),0) FROM sembako_claims sc
        JOIN sembako_shares ss ON ss.id = sc.share_id WHERE 1=1' . ($w ? ' AND ' . implode(' AND ', $w) : ''));
    $st->execute($a);
    $sembako_porsi = (int)$st->fetchColumn();

    $ringkas = [
        'rw' => $rw ? 1 : (int)$pdo->query('SELECT COUNT(*) FROM rw')->fetchColumn(),
        'laporan_total' => $total,
        'laporan_selesai' => $selesai,
        'ditanggapi_persen' => $total ? (int)round(100 * ($total - $baru) / $total) : 0,
        'sampah_kg' => $sampah_kg,
        'keluarga_terbantu' => $keluarga,
        'usulan_terwujud' => $usulan,
        'sembako_porsi' => $sembako_porsi,
        'usaha' => $usaha,
    ];

    $nama_bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $keys = [];
    $labels = [];
    if ($rentang === '30') {
        $grup = '%Y-%m-%d';
        for ($i = 29; $i >= 0; $i--) {
            $d = (new DateTimeImmutable('today'))->modify("-$i days");
            $keys[] = $d->format('Y-m-d');
            $labels[] = $d->format('j/n');
        }
        $awal = $keys[0] . ' 00:00:00';
    } else {
        $grup = '%Y-%m';
        $now = new DateTimeImmutable('first day of this month');
        if ($rentang === '6') {
            $awal_bulan = $now->modify('-5 months');
        } else {
            $awal_data = $pdo->query('SELECT MIN(d) FROM (SELECT MIN(created_at) d FROM waste_deposits
                UNION ALL SELECT MIN(created_at) FROM reports) x')->fetchColumn();
            $awal_bulan = $awal_data ? new DateTimeImmutable(date('Y-m-01', strtotime($awal_data))) : $now;
            if ($awal_bulan < $now->modify('-59 months')) {
                $awal_bulan = $now->modify('-59 months');
            }
        }
        for ($d = $awal_bulan; $d <= $now; $d = $d->modify('+1 month')) {
            $keys[] = $d->format('Y-m');
            $labels[] = $nama_bulan[(int)$d->format('n')];
        }
        $awal = $keys[0] . '-01 00:00:00';
    }

    $w = [];
    $a = [];
    if ($rentang !== 'all') {
        $w[] = 'created_at >= ?';
        $a[] = $awal;
    }
    if ($rw) {
        $w[] = 'user_id IN (SELECT id FROM users WHERE rw_id = ?)';
        $a[] = $rw;
    }
    $where = $w ? ' AND ' . implode(' AND ', $w) : '';
    $st = $pdo->prepare("SELECT DATE_FORMAT(created_at, '$grup') m, SUM(berat_kg) v
        FROM waste_deposits WHERE dibatalkan = 0$where GROUP BY m");
    $st->execute($a);
    $sampah = $st->fetchAll(PDO::FETCH_KEY_PAIR);

    $w = [];
    $a = [];
    if ($rentang !== 'all') {
        $w[] = 'created_at >= ?';
        $a[] = $awal;
    }
    if ($rw) {
        $w[] = 'rw_id = ?';
        $a[] = $rw;
    }
    $where = $w ? ' AND ' . implode(' AND ', $w) : '';
    $st = $pdo->prepare("SELECT DATE_FORMAT(created_at, '$grup') m, COUNT(*) v
        FROM reports WHERE status = 'selesai'$where GROUP BY m");
    $st->execute($a);
    $selesai_bulan = $st->fetchAll(PDO::FETCH_KEY_PAIR);

    return [
        'ringkas' => $ringkas,
        'rentang' => $rentang,
        'rw' => $rw,
        'bulan' => [
            'labels' => $labels,
            'sampah' => array_map(fn($k) => (float)($sampah[$k] ?? 0), $keys),
            'laporan' => array_map(fn($k) => (int)($selesai_bulan[$k] ?? 0), $keys),
        ],
    ];
}

/* ---------------- unggah foto ---------------- */

function upload_foto(array $file, ?string &$error = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Foto gagal diunggah. Coba lagi.';
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        $error = 'Ukuran foto maksimal 2 MB.';
        return null;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
    if (!$ext || !@getimagesize($file['tmp_name'])) {
        $error = 'Foto harus berformat JPG atau PNG.';
        return null;
    }
    $nama = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], ROOT_DIR . '/uploads/' . $nama)) {
        $error = 'Foto gagal disimpan. Periksa izin folder uploads.';
        return null;
    }
    return 'uploads/' . $nama;
}

function foto_url(?string $path): string
{
    $path = $path ?: 'assets/img/lapak-placeholder.svg';
    $segments = explode('/', $path);
    $encoded = implode('/', array_map('rawurlencode', $segments));
    return BASE . '/' . ltrim($encoded, '/');
}

/* ---------------- komponen bersama (PRD 1.1) ---------------- */

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function log_status(PDO $pdo, string $refType, int $refId, string $status, ?string $catatan, int $by): void
{
    $st = $pdo->prepare('INSERT INTO status_logs (ref_type, ref_id, status, catatan, by_user) VALUES (?, ?, ?, ?, ?)');
    $st->execute([$refType, $refId, $status, $catatan ?: null, $by ?: null]);
}

/** Saldo bank sampah — Lampiran G, satu-satunya sumber saldo. */
function saldo_warga(PDO $pdo, int $userId): int
{
    $st = $pdo->prepare("SELECT
        COALESCE((SELECT SUM(nilai) FROM waste_deposits WHERE user_id = ? AND dibatalkan = 0), 0)
        - COALESCE((SELECT SUM(jumlah) FROM waste_withdrawals WHERE user_id = ?), 0)");
    $st->execute([$userId, $userId]);
    return (int)$st->fetchColumn();
}

function klasifikasi_kondisi(int $perKapita): string
{
    $batas = (int)setting('garis_batas_per_kapita');
    $ambang = (float)setting('ambang_dipantau_kali', '1.5');
    if ($perKapita < $batas) {
        return 'rentan';
    }
    return $perKapita < (int)round($batas * $ambang) ? 'dipantau' : 'cukup';
}

function kode_laporan(int $id): string
{
    return 'LPR-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

function kode_pengajuan(int $id): string
{
    return 'PGJ-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

function refresh_sembako(PDO $pdo): void
{
    $pdo->exec("UPDATE sembako_shares SET status = 'kedaluwarsa'
        WHERE status = 'tersedia' AND batas_ambil < NOW()");
}

