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

// Fatura numarası prefix
define('FATURA_PREFIX', 'INV');

// Para birimi
define('PARA_BIRIMI', '₺');

// Varsayılan KDV oranı
define('VARSAYILAN_KDV', 18); 