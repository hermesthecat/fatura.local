<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Remember token'ı temizle
if (isset($_COOKIE['remember_token'])) {
    $db = Database::getInstance();
    $db->query(
        "DELETE FROM remember_tokens WHERE token = :token",
        [':token' => $_COOKIE['remember_token']]
    );
    setcookie('remember_token', '', time() - 3600, '/');
}

// Session'ı temizle
session_destroy();

// Başarılı mesajı göster ve login sayfasına yönlendir
basari("Başarıyla çıkış yaptınız.");
header('Location: login.php');
exit;
