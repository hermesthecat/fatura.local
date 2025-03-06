<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Invoice;
use App\Customer;

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

$customer = new Customer();
$customerData = $customer->find($invoiceData['customer_id']);

$items = $invoice->getItems($invoice_id);

$title = 'Fatura Yazdır';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            @page {
                size: A4;
                margin: 1cm;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        .table th {
            background-color: #f8f9fa !important;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container my-4">
        <div class="text-end mb-4 no-print">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print"></i> Yazdır
            </button>
            <a href="/modules/invoices/view.php?id=<?= $invoice_id ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Geri
            </a>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-6">
                        <h2 class="h4">Fatura Bilgileri</h2>
                        <div><strong>Fatura No:</strong> <?= e($invoiceData['fatura_no']) ?></div>
                        <div><strong>Fatura Tarihi:</strong> <?= format_date($invoiceData['fatura_tarihi']) ?></div>
                        <div><strong>Vade Tarihi:</strong> <?= format_date($invoiceData['vade_tarihi']) ?></div>
                        <?php if ($invoiceData['durum'] === 'iptal'): ?>
                            <div class="text-danger"><strong>FATURA İPTAL EDİLMİŞTİR</strong></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-6 text-end">
                        <h2 class="h4">Müşteri Bilgileri</h2>
                        <div><strong><?= e($customerData['firma_adi']) ?></strong></div>
                        <div>Vergi No: <?= e($customerData['vergi_no']) ?></div>
                        <div>Vergi Dairesi: <?= e($customerData['vergi_dairesi']) ?></div>
                        <div><?= nl2br(e($customerData['adres'])) ?></div>
                        <div>Tel: <?= format_phone($customerData['telefon']) ?></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Ürün</th>
                                <th class="text-end">Miktar</th>
                                <th class="text-end">Birim Fiyat</th>
                                <th class="text-end">KDV Oranı</th>
                                <th class="text-end">KDV Tutarı</th>
                                <th class="text-end">Ara Toplam</th>
                                <th class="text-end">Toplam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= e($item['urun_adi']) ?></td>
                                <td class="text-end"><?= number_format($item['miktar'], 2, ',', '.') ?></td>
                                <td class="text-end"><?= format_money($item['birim_fiyat']) ?></td>
                                <td class="text-end">%<?= $item['kdv_orani'] ?></td>
                                <td class="text-end"><?= format_money($item['kdv_tutari']) ?></td>
                                <td class="text-end"><?= format_money($item['ara_toplam']) ?></td>
                                <td class="text-end"><?= format_money($item['toplam_tutar']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5" class="text-end">Ara Toplam:</th>
                                <td class="text-end" colspan="2"><?= format_money($invoiceData['ara_toplam']) ?></td>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">KDV Toplam:</th>
                                <td class="text-end" colspan="2"><?= format_money($invoiceData['kdv_toplam']) ?></td>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end">Genel Toplam:</th>
                                <td class="text-end" colspan="2"><?= format_money($invoiceData['genel_toplam']) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <?php if ($invoiceData['aciklama']): ?>
                <div class="mt-4">
                    <h5>Açıklama</h5>
                    <p><?= nl2br(e($invoiceData['aciklama'])) ?></p>
                </div>
                <?php endif; ?>

                <div class="row mt-5">
                    <div class="col-6">
                        <div class="border-top pt-2">
                            <strong>Teslim Eden</strong><br>
                            Kaşe / İmza
                        </div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="border-top pt-2">
                            <strong>Teslim Alan</strong><br>
                            Kaşe / İmza
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 