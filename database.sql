-- Veritabanı oluşturma
CREATE DATABASE IF NOT EXISTS fatura_db CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci;
USE fatura_db;

-- Müşteriler tablosu
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_adi VARCHAR(255) NOT NULL,
    vergi_no VARCHAR(11),
    vergi_dairesi VARCHAR(100),
    adres TEXT,
    telefon VARCHAR(20),
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Ürünler tablosu
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    urun_kodu VARCHAR(50) UNIQUE NOT NULL,
    urun_adi VARCHAR(255) NOT NULL,
    birim_fiyat DECIMAL(10,2) NOT NULL,
    kdv_orani TINYINT DEFAULT 18,
    birim VARCHAR(20) NOT NULL,
    stok_miktari DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Faturalar tablosu
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fatura_no VARCHAR(50) UNIQUE NOT NULL,
    customer_id INT NOT NULL,
    fatura_tarihi DATE NOT NULL,
    vade_tarihi DATE,
    toplam_tutar DECIMAL(12,2) NOT NULL DEFAULT 0,
    kdv_toplam DECIMAL(12,2) NOT NULL DEFAULT 0,
    genel_toplam DECIMAL(12,2) NOT NULL DEFAULT 0,
    aciklama TEXT,
    durum ENUM('taslak', 'onaylandı', 'iptal') DEFAULT 'taslak',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Fatura kalemleri tablosu
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    miktar DECIMAL(10,2) NOT NULL,
    birim_fiyat DECIMAL(10,2) NOT NULL,
    kdv_orani TINYINT NOT NULL,
    kdv_tutari DECIMAL(10,2) NOT NULL,
    ara_toplam DECIMAL(12,2) NOT NULL,
    toplam_tutar DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Kullanıcılar tablosu
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    ad_soyad VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'user') DEFAULT 'user',
    aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Örnek admin kullanıcısı oluşturma (şifre: admin123)
INSERT INTO users (username, password, email, ad_soyad, rol) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@fatura.local', 'Sistem Yöneticisi', 'admin');

-- Ayarlar tablosu
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firma_adi VARCHAR(255) NOT NULL,
    vergi_no VARCHAR(11),
    vergi_dairesi VARCHAR(100),
    adres TEXT,
    telefon VARCHAR(20),
    email VARCHAR(100),
    logo_path VARCHAR(255),
    fatura_footer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci; 