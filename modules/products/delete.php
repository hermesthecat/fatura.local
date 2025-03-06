<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Product;

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    set_flash_message('error', 'Geçersiz ürün ID\'si');
    redirect('/modules/products');
}

$product = new Product();
$productData = $product->find($product_id);

if (!$productData) {
    set_flash_message('error', 'Ürün bulunamadı');
    redirect('/modules/products');
}

try {
    $product->delete($product_id);
    set_flash_message('success', 'Ürün başarıyla silindi');
} catch (\Exception $e) {
    set_flash_message('error', $e->getMessage());
}

redirect('/modules/products'); 