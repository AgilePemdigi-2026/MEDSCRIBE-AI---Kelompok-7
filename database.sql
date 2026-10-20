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

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabel: pengguna
DROP TABLE IF EXISTS `pengguna`;
CREATE TABLE `pengguna` (
    `id_pengguna` INT NOT NULL AUTO_INCREMENT,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `role` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`id_pengguna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabel: pasien
DROP TABLE IF EXISTS `pasien`;
CREATE TABLE `pasien` (
    `id_pasien` INT NOT NULL AUTO_INCREMENT,
    `no_rekam_medis` VARCHAR(30) NOT NULL,
    `nama_pasien` VARCHAR(100) NOT NULL,
    `tanggal_lahir` DATE DEFAULT NULL,
    `jenis_kelamin` VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (`id_pasien`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tabel: antrean
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

-- 4. Tabel: kunjungan
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

-- 5. Tabel: pemeriksaan_awal
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

-- 6. Tabel: rekaman_audio
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

-- 7. Tabel: draf_soap
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

-- 8. Tabel: finalisasi
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

-- 9. Tabel: e_resep
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

SET FOREIGN_KEY_CHECKS = 1;
