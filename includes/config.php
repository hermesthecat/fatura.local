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

// Veritabanından ayarları al
require_once 'db.php';
$db = Database::getInstance();
$ayarlar = $db->query("SELECT ayar_adi, ayar_degeri FROM system_settings")->fetchAll(PDO::FETCH_KEY_PAIR);

// Sistem ayarlarını tanımla
foreach ($ayarlar as $ayar_adi => $ayar_degeri) {
    if (!defined(strtoupper($ayar_adi))) {
        define(strtoupper($ayar_adi), $ayar_degeri);
    }
}

// Varsayılan değerler (veritabanında yoksa)
if (!defined('FATURA_PREFIX')) define('FATURA_PREFIX', 'INV');
if (!defined('PARA_BIRIMI')) define('PARA_BIRIMI', '₺');
if (!defined('VARSAYILAN_KDV')) define('VARSAYILAN_KDV', 18); 