-- HARMONIA — schema (Lampiran G PRD)
CREATE DATABASE IF NOT EXISTS harmonia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE harmonia;

DROP TABLE IF EXISTS announcements, food_claims, food_shares, safety_checks, shelters, businesses,
  proposal_votes, proposals, status_logs, waste_withdrawals, waste_deposits, waste_types,
  reports, aid_requests, family_checks, users, rw, settings;

CREATE TABLE rw (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(30) NOT NULL,
  kelurahan VARCHAR(60) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(80) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('warga','petugas','admin') NOT NULL DEFAULT 'warga',
  rw_id INT NULL,
  no_hp VARCHAR(20) NULL,
  foto VARCHAR(120) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rw_id) REFERENCES rw(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_id INT NOT NULL,
  rw_id INT NULL,                                   -- NULL = semua RW (hanya admin)
  tipe ENUM('pengumuman','agenda','tips') NOT NULL DEFAULT 'pengumuman',
  judul VARCHAR(120) NOT NULL,
  isi TEXT NOT NULL,
  tanggal_acara DATETIME NULL,                      -- wajib bila tipe = agenda
  lokasi VARCHAR(120) NULL,
  siaga TINYINT(1) NOT NULL DEFAULT 0,              -- tampil sebagai banner siaga (admin saja)
  sematkan TINYINT(1) NOT NULL DEFAULT 0,
  berlaku_sampai DATE NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (rw_id) REFERENCES rw(id) ON DELETE CASCADE,
  INDEX idx_tampil (rw_id, tipe, tanggal_acara)
) ENGINE=InnoDB;

CREATE TABLE family_checks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  jumlah_orang TINYINT UNSIGNED NOT NULL,
  pengeluaran_bulanan INT UNSIGNED NOT NULL,
  per_kapita INT UNSIGNED NOT NULL,
  status ENUM('rentan','dipantau','cukup') NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_user_waktu (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE aid_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  check_id INT NULL,
  jenis ENUM('pangan','tunai','kesehatan','pendidikan') NOT NULL,
  alasan TEXT NOT NULL,
  status ENUM('diajukan','diverifikasi','disetujui','ditolak') NOT NULL DEFAULT 'diajukan',
  catatan_petugas TEXT NULL,
  processed_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (check_id) REFERENCES family_checks(id) ON DELETE SET NULL,
  FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE reports (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  rw_id INT NOT NULL,
  kategori ENUM('lampu','selokan','sampah','jalan','lainnya') NOT NULL,
  judul VARCHAR(100) NOT NULL,
  deskripsi TEXT NOT NULL,
  lokasi_teks VARCHAR(150) NOT NULL,
  foto VARCHAR(120) NULL,
  foto_selesai VARCHAR(120) NULL,
  status ENUM('baru','diproses','selesai') NOT NULL DEFAULT 'baru',
  handled_by INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (rw_id) REFERENCES rw(id) ON DELETE RESTRICT,
  FOREIGN KEY (handled_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_rw_status (rw_id, status)
) ENGINE=InnoDB;

CREATE TABLE status_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ref_type ENUM('aid','report') NOT NULL,
  ref_id INT NOT NULL,
  status VARCHAR(20) NOT NULL,
  catatan TEXT NULL,
  by_user INT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (by_user) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_ref (ref_type, ref_id)
) ENGINE=InnoDB;

CREATE TABLE proposals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  rw_id INT NOT NULL,
  judul VARCHAR(100) NOT NULL,
  deskripsi TEXT NOT NULL,
  target_dukungan INT UNSIGNED NOT NULL DEFAULT 50,
  status ENUM('dibuka','tercapai','diteruskan','ditolak') NOT NULL DEFAULT 'dibuka',
  catatan_petugas VARCHAR(200) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (rw_id) REFERENCES rw(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE proposal_votes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  proposal_id INT NOT NULL,
  user_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY uq_vote (proposal_id, user_id)
) ENGINE=InnoDB;

CREATE TABLE waste_types (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(40) NOT NULL UNIQUE,
  harga_per_kg INT UNSIGNED NOT NULL
) ENGINE=InnoDB;

CREATE TABLE waste_deposits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  waste_type_id INT NOT NULL,
  berat_kg DECIMAL(6,2) NOT NULL,
  nilai INT UNSIGNED NOT NULL,
  dicatat_oleh INT NULL,
  dibatalkan TINYINT(1) NOT NULL DEFAULT 0,
  catatan_batal VARCHAR(200) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (waste_type_id) REFERENCES waste_types(id) ON DELETE RESTRICT,
  FOREIGN KEY (dicatat_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE waste_withdrawals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  jumlah INT UNSIGNED NOT NULL,
  status ENUM('diajukan','dicairkan') NOT NULL DEFAULT 'diajukan',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE food_shares (
  id INT AUTO_INCREMENT PRIMARY KEY,
  donor_id INT NOT NULL,
  rw_id INT NOT NULL,
  nama_makanan VARCHAR(100) NOT NULL,
  kondisi VARCHAR(100) NULL,
  porsi_total INT UNSIGNED NOT NULL,
  porsi_sisa INT UNSIGNED NOT NULL,
  batas_ambil DATETIME NOT NULL,
  status ENUM('tersedia','habis','kedaluwarsa') NOT NULL DEFAULT 'tersedia',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (rw_id) REFERENCES rw(id) ON DELETE RESTRICT,
  CHECK (porsi_sisa <= porsi_total)
) ENGINE=InnoDB;

CREATE TABLE food_claims (
  id INT AUTO_INCREMENT PRIMARY KEY,
  share_id INT NOT NULL,
  user_id INT NOT NULL,
  porsi TINYINT UNSIGNED NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (share_id) REFERENCES food_shares(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE shelters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  rw_id INT NOT NULL,
  nama VARCHAR(80) NOT NULL,
  alamat_teks VARCHAR(150) NOT NULL,
  kapasitas INT UNSIGNED NOT NULL,
  kontak_nama VARCHAR(80) NOT NULL,
  kontak_hp VARCHAR(20) NOT NULL,
  FOREIGN KEY (rw_id) REFERENCES rw(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE safety_checks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  shelter_id INT NULL,
  status ENUM('aman','butuh_bantuan') NOT NULL,
  catatan VARCHAR(200) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (shelter_id) REFERENCES shelters(id) ON DELETE SET NULL,
  INDEX idx_user_waktu (user_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE businesses (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  nama_usaha VARCHAR(80) NOT NULL,
  kategori ENUM('makanan','jasa','kerajinan','pertanian') NOT NULL,
  deskripsi TEXT NOT NULL,
  foto VARCHAR(120) NOT NULL,
  kontak VARCHAR(20) NOT NULL,
  status ENUM('menunggu','tayang','ditolak') NOT NULL DEFAULT 'menunggu',
  alasan_tolak VARCHAR(200) NULL,
  tampil TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO settings (setting_key, setting_value) VALUES
  ('garis_batas_per_kapita', '692906'),
  ('ambang_dipantau_kali', '1.5');
INSERT INTO waste_types (nama, harga_per_kg) VALUES
  ('Plastik', 2500), ('Kertas', 1500), ('Logam', 5000), ('Kaca', 500), ('Minyak jelantah', 4000);

ALTER TABLE reports ADD COLUMN foto_selesai VARCHAR(120) NULL AFTER foto;

-- F11: Pengumuman & Agenda RW
CREATE TABLE announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  author_id INT NOT NULL,
  rw_id INT NULL,                                   -- NULL = semua RW (hanya admin)
  tipe ENUM('pengumuman','agenda','tips') NOT NULL DEFAULT 'pengumuman',
  judul VARCHAR(120) NOT NULL,
  isi TEXT NOT NULL,
  tanggal_acara DATETIME NULL,                      -- wajib bila tipe = agenda
  lokasi VARCHAR(120) NULL,
  siaga TINYINT(1) NOT NULL DEFAULT 0,              -- tampil sebagai banner siaga (admin saja)
  sematkan TINYINT(1) NOT NULL DEFAULT 0,
  berlaku_sampai DATE NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (rw_id) REFERENCES rw(id) ON DELETE CASCADE,
  INDEX idx_tampil (rw_id, tipe, tanggal_acara)
) ENGINE=InnoDB;