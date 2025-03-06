-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS fatura_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fatura_db;

-- Şirketler tablosu
CREATE TABLE IF NOT EXISTS `companies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `unvan` varchar(255) NOT NULL,
    `adres` text NOT NULL,
    `sehir` varchar(255) NOT NULL,
    `telefon` varchar(20) NOT NULL,
    `email` varchar(255) NOT NULL,
    `vergi_dairesi` varchar(255) NOT NULL,
    `vergi_no` varchar(20) NOT NULL,
    `web` varchar(255) NOT NULL,
    `mersis_no` varchar(20) NOT NULL,
    `ticaret_sicil_no` varchar(20) NOT NULL,
    `banka_adi` varchar(255) NOT NULL,
    `iban` varchar(50) NOT NULL,
    `logo` varchar(255) DEFAULT NULL,
    `aktif` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Şirket ayarları tablosu
CREATE TABLE IF NOT EXISTS `company_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `company_id` int(11) NOT NULL,
    `ayar_adi` varchar(50) NOT NULL,
    `ayar_degeri` varchar(255) NOT NULL,
    `aciklama` varchar(255) DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `company_setting_unique` (`company_id`, `ayar_adi`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Müşteriler tablosu güncelleme
DROP TABLE IF EXISTS customers;
CREATE TABLE IF NOT EXISTS customers (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` int(11) NOT NULL,
    `firma_adi` VARCHAR(255) NOT NULL,
    `vergi_no` VARCHAR(50),
    `vergi_dairesi` VARCHAR(100),
    `adres` TEXT,
    `telefon` VARCHAR(20),
    `email` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Faturalar tablosu güncelleme
DROP TABLE IF EXISTS invoices;
CREATE TABLE IF NOT EXISTS invoices (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` int(11) NOT NULL,
    `fatura_no` VARCHAR(50) NOT NULL,
    `customer_id` INT NOT NULL,
    `fatura_tarihi` DATE NOT NULL,
    `vade_tarihi` DATE,
    `toplam_tutar` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `kdv_orani` INT NOT NULL DEFAULT 18,
    `kdv_tutari` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `genel_toplam` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `aciklama` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `company_invoice_unique` (`company_id`, `fatura_no`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fatura kalemleri tablosu
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    urun_adi VARCHAR(255) NOT NULL,
    miktar INT NOT NULL DEFAULT 1,
    birim_fiyat DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    toplam_fiyat DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcılar tablosu güncelleme
DROP TABLE IF EXISTS users;
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `ad_soyad` varchar(100) NOT NULL,
    `email` varchar(255) NOT NULL UNIQUE,
    `rol` enum('admin','user') NOT NULL DEFAULT 'user',
    `son_giris` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `admin` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Kullanıcı-Şirket ilişki tablosu
CREATE TABLE IF NOT EXISTS `user_companies` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `company_id` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_company_unique` (`user_id`, `company_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan admin kullanıcısı (şifre: 123456)
INSERT INTO `users` (`username`, `password`, `ad_soyad`, `email`, `rol`) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'A. Kerem Gök', 'admin@keremgok.com', 'admin');

-- Remember Tokens tablosu
CREATE TABLE IF NOT EXISTS `remember_tokens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `token` varchar(64) NOT NULL,
    `expires_at` datetime NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `token` (`token`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sistem Ayarları Tablosu (Genel ayarlar için)
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ayar_adi` varchar(50) NOT NULL UNIQUE,
    `ayar_degeri` varchar(255) NOT NULL,
    `aciklama` varchar(255) DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Varsayılan sistem ayarları
INSERT INTO `system_settings` (`ayar_adi`, `ayar_degeri`, `aciklama`) VALUES 
('fatura_prefix', 'INV', 'Fatura numarası öneki'),
('para_birimi', '₺', 'Para birimi sembolü'),
('varsayilan_kdv', '18', 'Varsayılan KDV oranı'),
('firma_telefon_formati', '+90 (XXX) XXX XX XX', 'Telefon numarası formatı'),
('fatura_not', 'Bu bir fatura notu örneğidir.', 'Faturalarda görünecek varsayılan not'); 