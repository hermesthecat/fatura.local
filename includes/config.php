<?php

/**
 * Veritabanı Yapılandırma Dosyası
 * @author A. Kerem Gök
 */

// Session başlat (eğer başlatılmamışsa)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    // Şirket bilgilerini al
    $stmt = $db->query(
        "SELECT * FROM companies WHERE id = :id",
        [':id' => $_SESSION['company_id']]
    );
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($company) {
        // Şirket bilgilerini sabitlere tanımla
        define('COMPANY_ID', $company['id']);
        define('COMPANY_UNVAN', $company['unvan']);
        define('COMPANY_ADRES', $company['adres']);
        define('COMPANY_SEHIR', $company['sehir']);
        define('COMPANY_TELEFON', $company['telefon']);
        define('COMPANY_EMAIL', $company['email']);
        define('COMPANY_VERGI_DAIRESI', $company['vergi_dairesi']);
        define('COMPANY_VERGI_NO', $company['vergi_no']);
        define('COMPANY_WEB', $company['web']);
        define('COMPANY_MERSIS_NO', $company['mersis_no']);
        define('COMPANY_TICARET_SICIL_NO', $company['ticaret_sicil_no']);
        define('COMPANY_BANKA_ADI', $company['banka_adi']);
        define('COMPANY_IBAN', $company['iban']);
        define('COMPANY_LOGO', $company['logo']);
    }

    // Şirket ayarlarını al
    $stmt = $db->query(
        "SELECT ayar_adi, ayar_degeri FROM company_settings WHERE company_id = :company_id",
        [':company_id' => $_SESSION['company_id']]
    );
    $company_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

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
if (!defined('FATURA_NOT')) define('FATURA_NOT', 'Bu bir fatura notudur.');
