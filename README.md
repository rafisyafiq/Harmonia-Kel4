# HARMONIA

*A Better Life in a Better Community*

Platform layanan warga bertema **SDGs 1 (Tanpa Kemiskinan)** dan **SDGs 11 (Kota & Permukiman Berkelanjutan)**. Tugas Praktikum Pemrograman Web — PHP native + MySQL, tanpa framework, tanpa library, tanpa API eksternal.

## Status pengerjaan

| Fase | Isi | Status |
| --- | --- | --- |
| 1 | Skema + data contoh, config, layout, beranda dinamis, auth, dashboard 3 peran, capaian + grafik Canvas, etalase lapak | selesai |
| 2 | Cek kondisi keluarga, pengajuan bantuan | selesai |
| 3 | Lapor warga, usulan warga | selesai |
| 4 | Bank sampah, bank pangan, lapak (form + verifikasi) | selesai |
| 5 | Siaga bencana, admin CRUD, pengumuman & agenda | selesai |
| 6 | Filter capaian (rentang + RW), tooltip grafik, cetak, uji akhir | selesai |

## Teknologi

HTML5 · CSS3 (satu file, CSS variables) · JavaScript vanilla · Canvas API · PHP 8 (PDO, sesi) · MySQL/MariaDB via XAMPP.

## Instalasi (XAMPP)

1. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
2. Letakkan folder proyek di `C:\xampp\htdocs\Harmonia`.
3. Buka `http://localhost/phpmyadmin`, lalu impor berurutan:
   - `database/schema.sql`
   - `database/seed.sql`
4. Periksa `config/db.php` (bawaan: host `127.0.0.1`, user `root`, sandi kosong, database `harmonia`).
5. Pastikan folder `uploads/` dapat ditulis.
6. Buka `http://localhost/Harmonia/`.

## Akun demo

| Peran | Email | Sandi |
| --- | --- | --- |
| Admin | admin@harmonia.test | Harmonia123 |
| Petugas RW 05 | petugas.rw05@harmonia.test | Harmonia123 |
| Warga RW 05 | warga@harmonia.test | Harmonia123 |

Data contoh: 24 RW, 149 pengguna, 30 laporan, 20 pengajuan, 6 usulan, 26 setoran sampah, 8 lapak, 8 postingan pangan, 14 konfirmasi siaga, dan 6 pengumuman/agenda. Semua angka di beranda dan capaian dihitung dari database.

## Struktur

```
index.php                 beranda dinamis (laporan, lapak, pengumuman)
api/stats.php             JSON statistik + filter rentang/RW
config/                   db.php (PDO, BASE), auth.php (sesi, CSRF, peran)
includes/                 header, footer, helpers, legenda_status, faq
auth/                     login, register, logout, profil
dashboard/                warga, petugas, admin
cek-kondisi/              index, hitung, riwayat
bantuan/                  index, ajukan, detail, proses
lapor/                    index, buat, detail, proses
usulan/                   index, detail, buat, dukung, proses, hapus
sampah/                   index, tarik, setor, cairkan, batal
pangan/                   index, bagikan, ambil
siaga/                    index, konfirmasi, ringkasan, kelola
lapak/                    index, daftar, saya, verifikasi
pengumuman/               index, detail, kelola, form, hapus
capaian/                  index (filter + cetak)
admin/                    pengguna, rw, harga-sampah, pengaturan
assets/                   css/style.css, js (app, charts, cek-kondisi, usulan, pangan, siaga), img
database/                 schema.sql, seed.sql, migrations/002_lanjutan.sql
uploads/                  foto unggahan (PHP dilarang dieksekusi)
```

Sudah punya database dari versi sebelumnya? Impor `database/migrations/002_lanjutan.sql` untuk menambah tabel `announcements` dan kolom `reports.foto_selesai`.

## Keamanan yang sudah diterapkan

- PDO prepared statements, `htmlspecialchars` pada semua keluaran.
- Token CSRF di setiap form; POST tanpa token ditolak (403).
- `password_hash` / `password_verify`; pesan gagal login bersifat umum.
- Sesi kedaluwarsa setelah 30 menit tidak aktif.
- Cek peran di server (`require_role`) — bukan sekadar menyembunyikan menu.
- Upload: validasi MIME `finfo`, maksimal 2 MB, nama file acak, eksekusi PHP di `uploads/` dilarang `.htaccess`.

## Catatan

Angka garis batas (Rp 692.906 per kapita) adalah contoh BPS dan bukan penetapan resmi. Hasil cek kondisi keluarga hanya perkiraan awal; keputusan bantuan ada pada petugas RW.
