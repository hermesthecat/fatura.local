<?php
require_once 'templates/header.php';

// Fatura ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    hata("Geçersiz fatura ID!");
    header("Location: fatura_listele.php");
    exit;
}

$fatura_id = $_GET['id'];
$db = Database::getInstance();

try {
    $db->beginTransaction();

    // Önce fatura kalemlerini sil (CASCADE olduğu için otomatik silinecek ama yine de ekleyelim)
    $sql = "DELETE FROM invoice_items WHERE invoice_id = :invoice_id";
    $db->query($sql, [':invoice_id' => $fatura_id]);

    // Sonra faturayı sil
    $sql = "DELETE FROM invoices WHERE id = :id";
    $db->query($sql, [':id' => $fatura_id]);

    $db->commit();
    basari("Fatura başarıyla silindi.");
} catch (Exception $e) {
    $db->rollback();
    hata("Fatura silinirken bir hata oluştu: " . $e->getMessage());
}

header("Location: fatura_listele.php");
exit; 