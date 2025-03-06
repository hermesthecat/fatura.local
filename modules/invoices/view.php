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

$title = 'Fatura Detayı';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?= e($title) ?></h1>
        <div>
            <a href="/modules/invoices/edit.php?id=<?= $invoice_id ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Düzenle
            </a>
            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $invoice_id ?>)">
                <i class="fas fa-trash"></i> Sil
            </button>
            <a href="/modules/invoices/print.php?id=<?= $invoice_id ?>" class="btn btn-info" target="_blank">
                <i class="fas fa-print"></i> Yazdır
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Fatura Bilgileri</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Fatura No:</th>
                            <td><?= e($invoiceData['fatura_no']) ?></td>
                        </tr>
                        <tr>
                            <th>Fatura Tarihi:</th>
                            <td><?= format_date($invoiceData['fatura_tarihi']) ?></td>
                        </tr>
                        <tr>
                            <th>Vade Tarihi:</th>
                            <td><?= format_date($invoiceData['vade_tarihi']) ?></td>
                        </tr>
                        <tr>
                            <th>Durum:</th>
                            <td>
                                <?php if ($invoiceData['durum'] === 'aktif'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">İptal</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Açıklama:</th>
                            <td><?= nl2br(e($invoiceData['aciklama'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Müşteri Bilgileri</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Firma Adı:</th>
                            <td><?= e($customerData['firma_adi']) ?></td>
                        </tr>
                        <tr>
                            <th>Vergi No:</th>
                            <td><?= e($customerData['vergi_no']) ?></td>
                        </tr>
                        <tr>
                            <th>Vergi Dairesi:</th>
                            <td><?= e($customerData['vergi_dairesi']) ?></td>
                        </tr>
                        <tr>
                            <th>Adres:</th>
                            <td><?= nl2br(e($customerData['adres'])) ?></td>
                        </tr>
                        <tr>
                            <th>Telefon:</th>
                            <td><?= format_phone($customerData['telefon']) ?></td>
                        </tr>
                        <tr>
                            <th>E-posta:</th>
                            <td><?= e($customerData['eposta']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Fatura Kalemleri</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
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
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu fatura kalıcı olarak silinecektir!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/modules/invoices/delete.php?id=${id}`;
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 