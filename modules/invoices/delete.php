<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Invoice;

$invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$invoice_id) {
    set_flash_message('error', 'Geçersiz fatura ID\'si');
    redirect('/modules/invoices');
}

$invoice = new Invoice();
$invoiceData = $invoice->find($invoice_id);

if (!$invoiceData) {
    set_flash_message('error', 'Fatura bulunamadı');
    redirect('/modules/invoices');
}

try {
    $invoice->delete($invoice_id);
    set_flash_message('success', 'Fatura başarıyla silindi');
} catch (\Exception $e) {
    set_flash_message('error', $e->getMessage());
}

redirect('/modules/invoices'); 