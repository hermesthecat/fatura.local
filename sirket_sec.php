<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Şirket ID kontrolü
if (!isset($_GET['id'])) {
    hata("Şirket ID'si belirtilmedi!");
    header('Location: index.php');
    exit;
}

try {
    $db = Database::getInstance();
    
    // Şirket bilgilerini al ve kullanıcının yetkisi var mı kontrol et
    $sirket = $db->query(
        "SELECT c.* FROM companies c 
        INNER JOIN user_companies uc ON uc.company_id = c.id 
        WHERE c.id = :id AND uc.user_id = :user_id AND c.aktif = 1",
        [
            ':id' => $_GET['id'],
            ':user_id' => $_SESSION['user']['id']
        ]
    )->fetch();

    if (!$sirket) {
        throw new Exception("Geçersiz şirket seçimi veya yetkiniz yok!");
    }

    // Şirket bilgilerini session'a kaydet
    $_SESSION['company_id'] = $sirket['id'];
    $_SESSION['company_unvan'] = $sirket['unvan'];
    $_SESSION['company_vergi_no'] = $sirket['vergi_no'];
    $_SESSION['company_vergi_dairesi'] = $sirket['vergi_dairesi'];
    $_SESSION['company_adres'] = $sirket['adres'];
    $_SESSION['company_telefon'] = $sirket['telefon'];
    $_SESSION['company_email'] = $sirket['email'];
    $_SESSION['company_web'] = $sirket['web'];
    $_SESSION['company_mersis_no'] = $sirket['mersis_no'];
    $_SESSION['company_ticaret_sicil_no'] = $sirket['ticaret_sicil_no'];
    $_SESSION['company_banka_adi'] = $sirket['banka_adi'];
    $_SESSION['company_iban'] = $sirket['iban'];

    basari($sirket['unvan'] . " şirketi seçildi.");
    
    // Önceki sayfaya yönlendir
    if (isset($_SERVER['HTTP_REFERER'])) {
        header('Location: ' . $_SERVER['HTTP_REFERER']);
    } else {
        header('Location: index.php');
    }
    exit;
    
} catch (Exception $e) {
    hata($e->getMessage());
    header('Location: index.php');
    exit;
} 