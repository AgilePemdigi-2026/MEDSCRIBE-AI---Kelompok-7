-- =====================================================================
-- DATABASE SCHEMA: MEDSCRIBE AI
-- Berdasarkan Dokumen Spesifikasi Kebutuhan Perangkat Lunak (SRS)
-- dan Model Data Konseptual/Fisik (CDM & PDM) - Appendix B
-- Sistem Basis Data: MySQL / MariaDB (Engine: InnoDB, Charset: utf8mb4)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `medscribe_ai`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `medscribe_ai`;

-- Nonaktifkan pengecekan foreign key sementara untuk inisialisasi bersih
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. Tabel: pengguna
-- Menyimpan kredensial dan peran tenaga medis (Dokter & Perawat)
-- Sesuai SRS Bagian 2.3, 2.4, 3.3, dan PDM pengguna
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `pengguna`;
CREATE TABLE `pengguna` (
    `id_pengguna` INT NOT NULL AUTO_INCREMENT,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `role` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_pengguna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Tabel: pasien
-- Menyimpan data demografi dan identitas rekam medis pasien
-- Sesuai SRS Bagian 2.1, 3.3, 5.3, dan PDM pasien
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `pasien`;
CREATE TABLE `pasien` (
    `id_pasien` INT NOT NULL AUTO_INCREMENT,
    `no_rekam_medis` VARCHAR(30) NOT NULL,
    `nama_pasien` VARCHAR(100) NOT NULL,
    `tanggal_lahir` DATE DEFAULT NULL,
    `jenis_kelamin` VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (`id_pasien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. Tabel: antrean
-- Menyimpan antrean pasien di poliklinik fasilitas kesehatan
-- Sesuai SRS Bagian 3.1, 4.1.3 REQ-1, REQ-2, dan PDM antrean
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `antrean`;
CREATE TABLE `antrean` (
    `id_antrean` INT NOT NULL AUTO_INCREMENT,
    `id_pasien` INT DEFAULT NULL,
    `tanggal_kunjungan` DATE NOT NULL,
    `status` VARCHAR(30) DEFAULT NULL,
    PRIMARY KEY (`id_antrean`),
    KEY `fk_antrean_pasien` (`id_pasien`),
    CONSTRAINT `fk_antrean_pasien` FOREIGN KEY (`id_pasien`) 
        REFERENCES `pasien` (`id_pasien`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. Tabel: kunjungan
-- Entitas utama yang merekam sesi kunjungan klinis pasien ke dokter
-- Sesuai SRS Bagian 4.1, 4.2, 4.3, 4.4, 4.5, dan PDM kunjungan
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `kunjungan`;
CREATE TABLE `kunjungan` (
    `id_kunjungan` INT NOT NULL AUTO_INCREMENT,
    `id_antrean` INT DEFAULT NULL,
    `id_pasien` INT DEFAULT NULL,
    `id_dokter` INT DEFAULT NULL,
    `tanggal` DATE NOT NULL,
    `status` VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (`id_kunjungan`),
    KEY `fk_kunjungan_pasien` (`id_pasien`),
    KEY `fk_kunjungan_dokter` (`id_dokter`),
    KEY `fk_kunjungan_antrean` (`id_antrean`),
    CONSTRAINT `fk_kunjungan_pasien` FOREIGN KEY (`id_pasien`) 
        REFERENCES `pasien` (`id_pasien`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_kunjungan_dokter` FOREIGN KEY (`id_dokter`) 
        REFERENCES `pengguna` (`id_pengguna`) 
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_kunjungan_antrean` FOREIGN KEY (`id_antrean`) 
        REFERENCES `antrean` (`id_antrean`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. Tabel: pemeriksaan_awal
-- Menyimpan pengukuran Tanda-Tanda Vital (TTV) oleh Perawat di Nurse Station
-- Sesuai SRS Bagian 4.1.3 REQ-3 s/d REQ-7, REQ-10, dan PDM pemeriksaan_awal
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `pemeriksaan_awal`;
CREATE TABLE `pemeriksaan_awal` (
    `id_pemeriksaan` INT NOT NULL AUTO_INCREMENT,
    `id_kunjungan` INT DEFAULT NULL,
    `id_perawat` INT DEFAULT NULL,
    `tekanan_darah` VARCHAR(20) DEFAULT NULL,
    `suhu_tubuh` DECIMAL(4,1) DEFAULT NULL,
    `nadi` INT DEFAULT NULL,
    `catatan` TEXT DEFAULT NULL,
    PRIMARY KEY (`id_pemeriksaan`),
    KEY `fk_pemeriksaan_kunjungan` (`id_kunjungan`),
    KEY `fk_pemeriksaan_perawat` (`id_perawat`),
    CONSTRAINT `fk_pemeriksaan_kunjungan` FOREIGN KEY (`id_kunjungan`) 
        REFERENCES `kunjungan` (`id_kunjungan`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pemeriksaan_perawat` FOREIGN KEY (`id_perawat`) 
        REFERENCES `pengguna` (`id_pengguna`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. Tabel: rekaman_audio
-- Menyimpan metadata berkas rekaman suara konsultasi klinis dokter-pasien
-- Sesuai SRS Bagian 3.2.2, 4.2.3 REQ-1 s/d REQ-3, REQ-9, dan PDM rekaman_audio
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `rekaman_audio`;
CREATE TABLE `rekaman_audio` (
    `id_rekaman` INT NOT NULL AUTO_INCREMENT,
    `id_kunjungan` INT DEFAULT NULL,
    `path_audio` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(30) DEFAULT NULL,
    `waktu_selesai` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id_rekaman`),
    KEY `fk_rekaman_kunjungan` (`id_kunjungan`),
    CONSTRAINT `fk_rekaman_kunjungan` FOREIGN KEY (`id_kunjungan`) 
        REFERENCES `kunjungan` (`id_kunjungan`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. Tabel: draf_soap
-- Menyimpan draf catatan medis terstruktur (Subjective, Objective, Assessment, Plan)
-- hasil ekstraksi Gemini API beserta kode terminologi medis (ICD-10)
-- Sesuai SRS Bagian 4.2.3 REQ-4 s/d REQ-7, 4.3.3 REQ-1 s/d REQ-7, dan PDM draf_soap
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `draf_soap`;
CREATE TABLE `draf_soap` (
    `id_draf_soap` INT NOT NULL AUTO_INCREMENT,
    `id_kunjungan` INT DEFAULT NULL,
    `id_pengguna` INT DEFAULT NULL,
    `subjective_text` TEXT DEFAULT NULL,
    `objective_text` TEXT DEFAULT NULL,
    `assessment_text` TEXT DEFAULT NULL,
    `plan_text` TEXT DEFAULT NULL,
    `kode_icd10` VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (`id_draf_soap`),
    KEY `fk_draf_soap_kunjungan` (`id_kunjungan`),
    KEY `fk_draf_soap_pengguna` (`id_pengguna`),
    CONSTRAINT `fk_draf_soap_kunjungan` FOREIGN KEY (`id_kunjungan`) 
        REFERENCES `kunjungan` (`id_kunjungan`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_draf_soap_pengguna` FOREIGN KEY (`id_pengguna`) 
        REFERENCES `pengguna` (`id_pengguna`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. Tabel: finalisasi
-- Menyimpan status sign-off final dokter, penguncian data rekam medis,
-- dan status integrasi/sinkronisasi ke platform SATUSEHAT Kemenkes RI
-- Sesuai SRS Bagian 4.5.3 REQ-1 s/d REQ-9, dan PDM finalisasi
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `finalisasi`;
CREATE TABLE `finalisasi` (
    `id_finalisasi` INT NOT NULL AUTO_INCREMENT,
    `id_kunjungan` INT DEFAULT NULL,
    `id_dokter` INT DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT NULL,
    `FINAL` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id_finalisasi`),
    KEY `fk_finalisasi_kunjungan` (`id_kunjungan`),
    KEY `fk_finalisasi_dokter` (`id_dokter`),
    CONSTRAINT `fk_finalisasi_kunjungan` FOREIGN KEY (`id_kunjungan`) 
        REFERENCES `kunjungan` (`id_kunjungan`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_finalisasi_dokter` FOREIGN KEY (`id_dokter`) 
        REFERENCES `pengguna` (`id_pengguna`) 
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. Tabel: e_resep
-- Menyimpan resep obat elektronik hasil tindak lanjut medis dokter
-- Sesuai SRS Bagian 4.4.3 REQ-3, REQ-5, REQ-6, dan PDM e_resep
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `e_resep`;
CREATE TABLE `e_resep` (
    `id_e_resep` INT NOT NULL AUTO_INCREMENT,
    `id_pasien` INT DEFAULT NULL,
    `id_tindak_lanjut` INT NOT NULL,
    `nama_obat` VARCHAR(100) NOT NULL,
    `dosis` VARCHAR(50) DEFAULT NULL,
    `bentuk_sediaan` VARCHAR(50) DEFAULT NULL,
    `aturan_konsumsi` VARCHAR(100) DEFAULT NULL,
    `jumlah` INT DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (`id_e_resep`),
    KEY `fk_e_resep_pasien` (`id_pasien`),
    CONSTRAINT `fk_e_resep_pasien` FOREIGN KEY (`id_pasien`) 
        REFERENCES `pasien` (`id_pasien`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aktifkan kembali pengecekan foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- DATA AWAL / SAMPLE DATA (Sesuai Skenario & Contoh pada Dokumen SRS)
-- =====================================================================

-- 1. Pengguna (Perawat & Dokter sesuai SRS Bab 3.1 & Lampiran)
INSERT INTO `pengguna` (`id_pengguna`, `nama_lengkap`, `role`, `password`) VALUES
(1, 'Dewi Pratiwi', 'perawat', '$2y$12$eX4mP1eHashDewiPratiwiNurseStation2026'),
(2, 'dr. Rina Anggraini, Sp.PD', 'dokter', '$2y$12$eX4mP1eHashDokterRinaSpPDPoliklinik2026');

-- 2. Pasien (Data dari Gambar 3.1, 3.2, dan Skenario Pengujian)
INSERT INTO `pasien` (`id_pasien`, `no_rekam_medis`, `nama_pasien`, `tanggal_lahir`, `jenis_kelamin`) VALUES
(1, '00-12-345', 'Budi Santoso', '1992-05-14', 'Laki-laki'),
(2, '00-12-346', 'Siti Aminah', '1980-08-21', 'Perempuan'),
(3, '00-12-347', 'Ahmad Fauzi', '1997-03-10', 'Laki-laki'),
(4, '00-12-348', 'Sri Wahyuni', '1974-11-05', 'Perempuan'),
(5, '00-12-349', 'Dedi Kurniawan', '1985-02-18', 'Laki-laki'),
(6, '00-12-350', 'Ratna Sari', '1989-09-30', 'Perempuan'),
(7, '00-12-351', 'Rizky Pratama', '2001-07-12', 'Laki-laki'),
(8, '00-12-352', 'Nur Aisyah', '1963-01-25', 'Perempuan');

-- 3. Antrean (Sesuai Daftar Antrean Gambar 3.2 SRS)
INSERT INTO `antrean` (`id_antrean`, `id_pasien`, `tanggal_kunjungan`, `status`) VALUES
(1, 1, '2026-09-19', 'Siap Periksa'),
(2, 2, '2026-09-19', 'Siap Periksa'),
(3, 3, '2026-09-19', 'Siap Periksa'),
(4, 4, '2026-09-19', 'Menunggu TTV'),
(5, 5, '2026-09-19', 'Menunggu TTV'),
(6, 6, '2026-09-19', 'Menunggu TTV'),
(7, 7, '2026-09-19', 'Selesai'),
(8, 8, '2026-09-19', 'Selesai');

-- 4. Kunjungan Pasien Budi Santoso (RSUA-20260919-001)
INSERT INTO `kunjungan` (`id_kunjungan`, `id_antrean`, `id_pasien`, `id_dokter`, `tanggal`, `status`) VALUES
(1, 1, 1, 2, '2026-09-19', 'SOAP Terverifikasi');

-- 5. Pemeriksaan Awal & TTV (Input Perawat Dewi Pratiwi)
INSERT INTO `pemeriksaan_awal` (`id_pemeriksaan`, `id_kunjungan`, `id_perawat`, `tekanan_darah`, `suhu_tubuh`, `nadi`, `catatan`) VALUES
(1, 1, 1, '120/80', 38.6, 78, 'Demam 3 hari, nyeri menelan, dan batuk kering. Suhu tubuh 38,6°C');

-- 6. Rekaman Audio Sesi Konsultasi
INSERT INTO `rekaman_audio` (`id_rekaman`, `id_kunjungan`, `path_audio`, `status`, `waktu_selesai`) VALUES
(1, 1, 'storage/audio/RSUA-20260919-001_consultation.wav', 'Diproses', '2026-09-19 08:35:28');

-- 7. Draf SOAP (Hasil Ekstraksi AI & Verifikasi dr. Rina Anggraini)
INSERT INTO `draf_soap` (`id_draf_soap`, `id_kunjungan`, `id_pengguna`, `subjective_text`, `objective_text`, `assessment_text`, `plan_text`, `kode_icd10`) VALUES
(1, 1, 2, 
 'Pasien mengeluhkan demam sejak 3 hari, disertai nyeri menelan dan batuk kering. Riwayat lain perlu dikonfirmasi saat review dokter.',
 'TD 120/80 mmHg, Nadi 78 x/menit, Suhu 38,6°C, RR 18 x/menit, BB 68 kg, TB 170 cm',
 'J02.9 - Faringitis akut, tidak spesifik. Penilaian klinis dan kesesuaian kode wajib dikonfirmasi dokter.',
 'Pertimbangkan terapi simptomatik sesuai penilaian dokter. Review kebutuhan Paracetamol dan pemeriksaan darah lengkap sebelum tindak lanjut.',
 'J02.9');

-- 8. Tindak Lanjut Medis: E-Resep Obat
INSERT INTO `e_resep` (`id_e_resep`, `id_pasien`, `id_tindak_lanjut`, `nama_obat`, `dosis`, `bentuk_sediaan`, `aturan_konsumsi`, `jumlah`, `status`) VALUES
(1, 1, 1, 'Paracetamol', '500 mg', 'Tablet', '3x Sehari', 10, 'Tersimpan');

-- 9. Finalisasi & Penguncian Rekam Medis (Sign-Off & SATUSEHAT)
INSERT INTO `finalisasi` (`id_finalisasi`, `id_kunjungan`, `id_dokter`, `status`, `FINAL`) VALUES
(1, 1, 2, 'Terintegrasi', 1);

