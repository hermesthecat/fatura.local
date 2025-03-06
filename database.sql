-- Veritabanını oluştur
CREATE DATABASE IF NOT EXISTS fatura_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fatura_db;

-- Müşteriler tablosu
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_adi VARCHAR(255) NOT NULL,
    vergi_no VARCHAR(50),
    vergi_dairesi VARCHAR(100),
    adres TEXT,
    telefon VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Faturalar tablosu
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fatura_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    fatura_tarihi DATE NOT NULL,
    vade_tarihi DATE,
    toplam_tutar DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    kdv_orani INT NOT NULL DEFAULT 18,
    kdv_tutari DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    genel_toplam DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    aciklama TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
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

-- Şirket Ayarları Tablosu
CREATE TABLE IF NOT EXISTS `company_settings` (
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
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Örnek müşteri verisi
INSERT INTO customers (firma_adi, vergi_no, vergi_dairesi, adres, telefon, email) VALUES
('Örnek Firma A.Ş.', '1234567890', 'Ankara VD', 'Kızılay Mah. Atatürk Bulvarı No:123 Çankaya/Ankara', '0312 123 45 67', 'info@ornekfirma.com'),
('Test Şirketi Ltd. Şti.', '9876543210', 'İstanbul VD', 'Levent Mah. Büyükdere Cad. No:456 Beşiktaş/İstanbul', '0212 987 65 43', 'info@testsirketi.com');

-- Varsayılan değerler
INSERT INTO `company_settings` (`unvan`, `adres`, `sehir`, `telefon`, `email`, `vergi_dairesi`, `vergi_no`, `web`, `mersis_no`, `ticaret_sicil_no`, `banka_adi`, `iban`) 
VALUES ('A. KEREM GÖK', 'Şirket Adresi, Sokak No: 123', '34000, İstanbul / Türkiye', '+90 (212) 123 45 67', 'info@keremgok.com', 'KADIKÖY', '1234567890', 'www.keremgok.com', '0123456789000001', '123456-0', 'X BANKASI', 'TR00 0000 0000 0000 0000 0000 00'); 