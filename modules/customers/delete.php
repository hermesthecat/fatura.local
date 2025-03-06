<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Customer;

// Müşteri ID kontrolü
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'Geçersiz müşteri ID';
    header('Location: index.php');
    exit;
}

$customer = new Customer();

try {
    // Müşteriyi sil
    if ($customer->delete($id)) {
        $_SESSION['success'] = 'Müşteri başarıyla silindi';
    } else {
        $_SESSION['error'] = 'Müşteri silinirken bir hata oluştu';
    }
} catch (\Exception $e) {
    // Muhtemelen foreign key hatası (müşteriye ait fatura var)
    $_SESSION['error'] = 'Müşteriye ait faturalar olduğu için silinemez';
}

header('Location: index.php');
exit; 