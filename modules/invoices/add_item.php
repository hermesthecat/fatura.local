<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Invoice;

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
$product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$miktar = filter_input(INPUT_POST, 'miktar', FILTER_VALIDATE_FLOAT);
$birim_fiyat = filter_input(INPUT_POST, 'birim_fiyat', FILTER_VALIDATE_FLOAT);
$kdv_orani = filter_input(INPUT_POST, 'kdv_orani', FILTER_VALIDATE_INT);

if (!$invoice_id || !$product_id || !$miktar || !$birim_fiyat || !is_numeric($kdv_orani)) {
    http_response_code(400);
    echo json_encode(['error' => 'Geçersiz veriler']);
    exit;
}

$invoice = new Invoice();

// Faturayı kontrol et
$invoiceData = $invoice->find($invoice_id);
if (!$invoiceData) {
    http_response_code(404);
    echo json_encode(['error' => 'Fatura bulunamadı']);
    exit;
}

// İptal edilmiş faturaya kalem eklenemez
if ($invoiceData['durum'] === 'iptal') {
    http_response_code(400);
    echo json_encode(['error' => 'İptal edilmiş faturaya kalem eklenemez']);
    exit;
}

try {
    // Hesaplamalar
    $ara_toplam = $miktar * $birim_fiyat;
    $kdv_tutari = $ara_toplam * ($kdv_orani / 100);
    $toplam_tutar = $ara_toplam + $kdv_tutari;
    
    // Kalemi ekle
    $item = [
        'product_id' => $product_id,
        'miktar' => $miktar,
        'birim_fiyat' => $birim_fiyat,
        'kdv_orani' => $kdv_orani,
        'kdv_tutari' => $kdv_tutari,
        'ara_toplam' => $ara_toplam,
        'toplam_tutar' => $toplam_tutar
    ];
    
    $invoice->addItem($item);
    
    echo json_encode(['success' => true]);
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 