# HARMONIA

*A Better Life in a Better Community*

## 1. Nama dan Deskripsi Proyek

**HARMONIA** adalah aplikasi web layanan warga tingkat RW. Warga dapat mengecek kondisi ekonomi keluarga, mengajukan bantuan, melaporkan masalah lingkungan, mengusulkan perbaikan, menabung lewat bank sampah, berbagi sembako, mengakses informasi siaga bencana, dan memasarkan usaha kecil. Petugas RW dan admin mengelola semuanya dari satu sistem.


## 2. Latar Belakang dan Tujuan

**Latar belakang.** Keluarga yang membutuhkan bantuan sering tidak terdata, sulit mengajukan bantuan, dan tidak tahu perkembangan pengajuannya. Laporan warga tentang masalah lingkungan, seperti masalah sampah, jalan rusak, dan lampu mati, juga tidak punya jalur yang jelas untuk dipantau sampai selesai. Informasi siaga bencana dan kegiatan rutin lingkungan juga hanya tersebar melalui pesan berantai. Proyek ini mengangkat dua tujuan SDGs:

- **SDGs 1 – Tanpa Kemiskinan:** cek kondisi keluarga, pengajuan bantuan, bantuan sosial sembako, bank sampah, dan lapak usaha warga.
- **SDGs 11 – Kota dan Permukiman Berkelanjutan:** laporan warga, usulan warga, siaga bencana, pengumuman, dan capaian bersama.

**Tujuan.**
1. Memberi warga  untuk menyampaikan kebutuhan dan laporan, lengkap dengan status yang bisa dipantau.
2. Membantu petugas RW memprioritaskan keluarga rentan dan menindaklanjuti laporan.
3. Mendorong warga saling membantu lewat berbagi sembako, bank sampah, dan lapak usaha.
4. Menyajikan angka capaian bersama yang dihitung langsung dari database.

## 3. Daftar Fitur dan Modul

| No | Modul | Ringkasan |
| --- | --- | --- |
| 1 | Login dan peran | Daftar, masuk, keluar, profil. Tiga peran: warga, petugas RW, dan admin, dengan pembatasan akses di server. |
| 2 | Cek kondisi keluarga | Menghitung pengeluaran per kapita dan menentukan status rentan, dipantau, atau cukup tanpa reload halaman. Riwayat tersimpan. |
| 3 | Pengajuan bantuan | Warga mengajukan bantuan. Petugas memverifikasi, menyetujui, atau menolak. Riwayat status tercatat. |
| 4 | Lapor warga | Laporan lingkungan dengan foto. Daftar publik tanpa nama pelapor, pencarian lewat kode laporan, dan petugas memproses sampai selesai dengan foto hasil. |
| 5 | Usulan warga | Warga mengusulkan perbaikan dan mendukung usulan warga RW yang sama. Satu dukungan per pengguna. |
| 6 | Bank sampah | Petugas mencatat setoran, saldo dihitung dari harga per jenis sampah, warga mengajukan penarikan, petugas mencairkan. |
| 7 | Bantuan sosial (sembako) | Warga membagikan dan mengambil sembako di RW yang sama, maksimal 2 porsi per orang. |
| 8 | Siaga bencana | Titik kumpul per RW dan konfirmasi "aman" atau "butuh bantuan". Petugas melihat ringkasan. |
| 9 | Lapak usaha warga | Warga mendaftarkan usaha dengan foto, admin memverifikasi, etalase publik dengan filter. |
| 10 | Pengumuman dan agenda | Petugas dan admin memposting pengumuman, agenda, dan tips. |
| 11 | Capaian bersama | Statistik dan grafik Canvas dengan filter rentang waktu dan RW, serta tombol cetak. |
| 12 | Administrasi | Kelola pengguna, RW, harga sampah, dan pengaturan garis batas. |

Modul saling terhubung. Data cek kondisi dipakai pengajuan bantuan, setoran sampah membentuk saldo, dan semua modul memengaruhi angka di beranda dan capaian.

## 4. Teknologi yang Digunakan

- HTML5
- CSS3 (satu berkas, memakai CSS variables)
- JavaScript vanilla, termasuk `fetch` ke endpoint PHP sendiri dan Canvas API untuk grafik
- PHP 8 native (PDO, session)
- MySQL/MariaDB
- XAMPP sebagai lingkungan localhost

## 5. Panduan Instalasi dan Konfigurasi

1. Pasang [XAMPP](https://www.apachefriends.org/) dengan PHP 8 atau lebih baru.
2. Salin atau clone repository ke folder `htdocs`, misalnya `C:\xampp\htdocs\Harmonia`.
3. Jalankan **Apache** dan **MySQL** dari XAMPP Control Panel.
4. Buka `http://localhost/phpmyadmin`, lalu impor berurutan:
   1. `database/schema.sql` (membuat database `harmonia` dan seluruh tabel)
   2. `database/seed.sql` (data contoh)
5. Periksa `config/db.php`. Bawaannya host `127.0.0.1`, user `root`, sandi kosong, database `harmonia`. Sesuaikan bila berbeda.
6. Pastikan folder `uploads/` dapat ditulis oleh Apache.

## 6. Panduan Menjalankan Aplikasi

1. Pastikan Apache dan MySQL berjalan.
2. Buka `http://localhost/Harmonia/` di browser. Sesuaikan nama folder bila berbeda.
3. Masuk dengan akun demo berikut (sandi semuanya `Harmonia123`):

| Peran | Email |
| --- | --- |
| Admin | admin@harmonia.test |
| Petugas RW 05 | petugas.rw05@harmonia.test |
| Warga RW 05 | warga@harmonia.test |

4. Coba alur utama:
   - **Warga:** cek kondisi, ajukan bantuan, buat laporan, dukung usulan, ambil sembako, dan tarik saldo sampah.
   - **Petugas:** proses pengajuan dan laporan, catat setoran sampah, lihat ringkasan siaga, dan posting pengumuman.
   - **Admin:** verifikasi lapak, ubah harga sampah, ubah garis batas, dan kelola pengguna.