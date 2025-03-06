<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Customer;

$title = 'Müşteri Detayı';
$customer = new Customer();

// Müşteri ID kontrolü
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'Geçersiz müşteri ID';
    header('Location: index.php');
    exit;
}

// Müşteriyi getir
$customerData = $customer->find($id);
if (!$customerData) {
    $_SESSION['error'] = 'Müşteri bulunamadı';
    header('Location: index.php');
    exit;
}

// Müşterinin faturalarını getir
$invoices = $customer->getInvoices();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Müşteri Bilgileri</h5>
                <div>
                    <a href="edit.php?id=<?= $id ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Düzenle
                    </a>
                    <button type="button" class="btn btn-danger btn-sm delete-customer" 
                            data-id="<?= $id ?>" 
                            data-name="<?= e($customerData['firma_adi']) ?>">
                        <i class="fas fa-trash"></i> Sil
                    </button>
                </div>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <tr>
                        <th width="150">Firma Adı:</th>
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
                        <td><?= e($customerData['telefon']) ?></td>
                    </tr>
                    <tr>
                        <th>E-posta:</th>
                        <td><?= e($customerData['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Kayıt Tarihi:</th>
                        <td><?= format_date($customerData['created_at']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Faturalar</h5>
                <a href="/modules/invoices/create.php?customer_id=<?= $id ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Yeni Fatura
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fatura No</th>
                                <th>Tarih</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th width="100">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Henüz fatura bulunmuyor</td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($invoices as $invoice): ?>
                            <tr>
                                <td><?= e($invoice['fatura_no']) ?></td>
                                <td><?= format_date($invoice['fatura_tarihi']) ?></td>
                                <td><?= format_money($invoice['genel_toplam']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $invoice['durum'] === 'onaylandı' ? 'success' : ($invoice['durum'] === 'iptal' ? 'danger' : 'warning') ?>">
                                        <?= get_invoice_status($invoice['durum']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/modules/invoices/view.php?id=<?= $invoice['id'] ?>" 
                                       class="btn btn-sm btn-info" title="Görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Müşteri silme
document.querySelector('.delete-customer').addEventListener('click', function() {
    const id = this.dataset.id;
    const name = this.dataset.name;
    
    Swal.fire({
        title: 'Emin misiniz?',
        text: `"${name}" isimli müşteriyi silmek istediğinize emin misiniz?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `delete.php?id=${id}`;
        }
    });
});</script> 