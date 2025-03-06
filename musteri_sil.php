<?php
require_once 'templates/header.php';

// Müşteri ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    hata("Geçersiz müşteri ID!");
    header("Location: musteri_listele.php");
    exit;
}

$musteri_id = $_GET['id'];
$db = Database::getInstance();

try {
    // Önce müşteriye ait fatura var mı kontrol et
    $sql = "SELECT COUNT(*) as fatura_sayisi FROM invoices WHERE customer_id = :customer_id";
    $result = $db->query($sql, [':customer_id' => $musteri_id])->fetch();

    if ($result['fatura_sayisi'] > 0) {
        hata("Bu müşteriye ait faturalar olduğu için silinemez!");
        header("Location: musteri_listele.php");
        exit;
    }

    // Müşteriyi sil
    $sql = "DELETE FROM customers WHERE id = :id";
    $db->query($sql, [':id' => $musteri_id]);

    basari("Müşteri başarıyla silindi.");
} catch (Exception $e) {
    hata("Müşteri silinirken bir hata oluştu: " . $e->getMessage());
}

header("Location: musteri_listele.php");
exit;
