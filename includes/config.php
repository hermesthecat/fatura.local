<?php
/**
 * Veritabanı Yapılandırma Dosyası
 * @author A. Kerem Gök
 */

// Hata raporlama
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Veritabanı bağlantı bilgileri
define('DB_HOST', 'localhost');
define('DB_NAME', 'fatura_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_CHARSET', 'utf8mb4');

// Zaman dilimi ayarı
date_default_timezone_set('Europe/Istanbul');

// Site URL
define('SITE_URL', 'http://fatura.local');

// Veritabanı bağlantısı
require_once 'db.php';
$db = Database::getInstance();

// Aktif şirket kontrolü
if (isset($_SESSION['company_id'])) {
    // Şirket ayarlarını al
    $stmt = $db->query("SELECT ayar_adi, ayar_degeri FROM company_settings WHERE company_id = :company_id", 
        [':company_id' => $_SESSION['company_id']]);
    $company_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Şirket bilgilerini al
    $stmt = $db->query("SELECT * FROM companies WHERE id = :id", 
        [':id' => $_SESSION['company_id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($company) {
        foreach ($company as $key => $value) {
            if (!defined('COMPANY_' . strtoupper($key))) {
                define('COMPANY_' . strtoupper($key), $value);
            }
        }
    }
    
    // Şirket özel ayarlarını tanımla
    foreach ($company_settings as $ayar_adi => $ayar_degeri) {
        if (!defined(strtoupper($ayar_adi))) {
            define(strtoupper($ayar_adi), $ayar_degeri);
        }
    }
}

// Genel sistem ayarlarını al
$system_settings = $db->query("SELECT ayar_adi, ayar_degeri FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Sistem ayarlarını tanımla (şirket ayarları yoksa bunlar kullanılır)
foreach ($system_settings as $ayar_adi => $ayar_degeri) {
    if (!defined(strtoupper($ayar_adi))) {
        define(strtoupper($ayar_adi), $ayar_degeri);
    }
}

// Varsayılan değerler (hiçbir ayar bulunamazsa)
if (!defined('FATURA_PREFIX')) define('FATURA_PREFIX', 'INV');
if (!defined('PARA_BIRIMI')) define('PARA_BIRIMI', '₺');
if (!defined('VARSAYILAN_KDV')) define('VARSAYILAN_KDV', 18); 