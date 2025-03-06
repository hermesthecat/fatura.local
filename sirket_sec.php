<?php
require_once 'includes/config.php';

// Oturum kontrolü
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// ID kontrolü
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// Şirket bilgilerini al
$sirket = $db->query("SELECT c.* FROM companies c 
    INNER JOIN user_companies uc ON uc.company_id = c.id 
    WHERE c.id = :company_id AND uc.user_id = :user_id AND c.aktif = 1",
    [':company_id' => $_GET['id'], ':user_id' => $_SESSION['user_id']])->fetch();

if (!$sirket) {
    header('Location: index.php');
    exit;
}

// Şirket bilgilerini oturuma kaydet
$_SESSION['company_id'] = $sirket['id'];
$_SESSION['company_unvan'] = $sirket['unvan'];

// Önceki sayfaya dön
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $referer);
exit; 