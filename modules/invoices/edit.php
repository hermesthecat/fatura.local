<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Invoice;
use App\Customer;
use App\Product;

$title = 'Fatura Düzenle';
$invoice = new Invoice();
$customer = new Customer();
$product = new Product();

$errors = [];
$customers = $customer->all();
$products = $product->all();

// Fatura ID kontrolü
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    $_SESSION['error'] = 'Geçersiz fatura ID';
    header('Location: index.php');
    exit;
}

// Faturayı getir
$invoiceData = $invoice->find($id);
if (!$invoiceData) {
    $_SESSION['error'] = 'Fatura bulunamadı';
    header('Location: index.php');
    exit;
}

// Fatura kalemlerini getir
$items = $invoice->getItems();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız';
        header("Location: edit.php?id=$id");
        exit;
    }
    
    // Form verilerini al
    $data = [
        'customer_id' => $_POST['customer_id'] ?? '',
        'fatura_tarihi' => $_POST['fatura_tarihi'] ?? '',
        'vade_tarihi' => $_POST['vade_tarihi'] ?? '',
        'aciklama' => $_POST['aciklama'] ?? '',
        'durum' => $_POST['durum'] ?? 'taslak'
    ];
    
    // Validasyon
    $errors = $invoice->validate($data);
    
    if (empty($errors)) {
        try {
            // Faturayı güncelle
            $invoice->update($id, $data);
            
            $_SESSION['success'] = 'Fatura başarıyla güncellendi';
            header("Location: edit.php?id=$id");
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Fatura güncellenirken bir hata oluştu: ' . $e->getMessage();
        }
    }
} else {
    // Form verilerini fatura bilgileriyle doldur
    $_POST = $invoiceData;
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Fatura Düzenle</h5>
    </div>
    <div class="card-body">
        <form method="post" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="customer_id" class="form-label">Müşteri <span class="text-danger">*</span></label>
                    <select class="form-select select2 <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" 
                            id="customer_id" name="customer_id" required>
                        <option value="">Müşteri Seçin</option>
                        <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($_POST['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['firma_adi']) ?> 
                            <?php if ($c['vergi_no']): ?>
                            (<?= e($c['vergi_no']) ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?= display_errors($errors, 'customer_id') ?>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="fatura_no" class="form-label">Fatura No</label>
                    <input type="text" class="form-control" value="<?= e($_POST['fatura_no']) ?>" readonly>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="fatura_tarihi" class="form-label">Fatura Tarihi <span class="text-danger">*</span></label>
                    <input type="date" class="form-control <?= isset($errors['fatura_tarihi']) ? 'is-invalid' : '' ?>" 
                           id="fatura_tarihi" name="fatura_tarihi" 
                           value="<?= e($_POST['fatura_tarihi'] ?? '') ?>" required>
                    <?= display_errors($errors, 'fatura_tarihi') ?>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="vade_tarihi" class="form-label">Vade Tarihi</label>
                    <input type="date" class="form-control <?= isset($errors['vade_tarihi']) ? 'is-invalid' : '' ?>" 
                           id="vade_tarihi" name="vade_tarihi" 
                           value="<?= e($_POST['vade_tarihi'] ?? '') ?>">
                    <?= display_errors($errors, 'vade_tarihi') ?>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="aciklama" class="form-label">Açıklama</label>
                <textarea class="form-control" id="aciklama" name="aciklama" rows="2"><?= e($_POST['aciklama'] ?? '') ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="durum" class="form-label">Durum</label>
                <select class="form-select" id="durum" name="durum">
                    <option value="taslak" <?= ($_POST['durum'] ?? '') === 'taslak' ? 'selected' : '' ?>>Taslak</option>
                    <option value="onaylandı" <?= ($_POST['durum'] ?? '') === 'onaylandı' ? 'selected' : '' ?>>Onaylandı</option>
                    <option value="iptal" <?= ($_POST['durum'] ?? '') === 'iptal' ? 'selected' : '' ?>>İptal</option>
                </select>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kaydet
                </button>
                
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> İptal
                </a>
                
                <a href="view.php?id=<?= $id ?>" class="btn btn-info">
                    <i class="fas fa-eye"></i> Görüntüle
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Fatura Kalemleri -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Fatura Kalemleri</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="fas fa-plus"></i> Kalem Ekle
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Ürün</th>
                        <th class="text-end">Miktar</th>
                        <th class="text-end">Birim Fiyat</th>
                        <th class="text-end">KDV %</th>
                        <th class="text-end">KDV Tutarı</th>
                        <th class="text-end">Ara Toplam</th>
                        <th class="text-end">Toplam</th>
                        <th width="100">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8" class="text-center">Henüz fatura kalemi eklenmemiş</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <?= e($item['urun_adi']) ?><br>
                            <small class="text-muted"><?= e($item['urun_kodu']) ?></small>
                        </td>
                        <td class="text-end">
                            <?= number_format($item['miktar'], 2) ?> <?= e($item['birim']) ?>
                        </td>
                        <td class="text-end"><?= format_money($item['birim_fiyat']) ?></td>
                        <td class="text-end">%<?= $item['kdv_orani'] ?></td>
                        <td class="text-end"><?= format_money($item['kdv_tutari']) ?></td>
                        <td class="text-end"><?= format_money($item['ara_toplam']) ?></td>
                        <td class="text-end"><?= format_money($item['toplam_tutar']) ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger delete-item" 
                                    data-id="<?= $item['id'] ?>"
                                    title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr class="table-light fw-bold">
                        <td colspan="5" class="text-end">Toplam:</td>
                        <td class="text-end"><?= format_money($_POST['toplam_tutar']) ?></td>
                        <td class="text-end"><?= format_money($_POST['genel_toplam']) ?></td>
                        <td></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Kalem Ekleme Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Fatura Kalemi Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addItemForm">
                    <input type="hidden" name="invoice_id" value="<?= $id ?>">
                    
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Ürün <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="product_id" name="product_id" required>
                            <option value="">Ürün Seçin</option>
                            <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id'] ?>" 
                                    data-price="<?= $p['birim_fiyat'] ?>"
                                    data-vat="<?= $p['kdv_orani'] ?>"
                                    data-unit="<?= e($p['birim']) ?>">
                                <?= e($p['urun_adi']) ?> 
                                (<?= e($p['urun_kodu']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="miktar" class="form-label">Miktar <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="miktar" name="miktar" 
                                   min="0.01" step="0.01" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="birim_fiyat" class="form-label">Birim Fiyat <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="birim_fiyat" name="birim_fiyat" 
                                   min="0.01" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="kdv_orani" class="form-label">KDV % <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="kdv_orani" name="kdv_orani" 
                                   min="0" max="100" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="ara_toplam" class="form-label">Ara Toplam</label>
                            <input type="text" class="form-control" id="ara_toplam" readonly>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="toplam_tutar" class="form-label">Toplam</label>
                            <input type="text" class="form-control" id="toplam_tutar" readonly>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="saveItem">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Form validasyonu
(function() {
    'use strict';
    
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
})();

// Select2 inicializasyonu
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        dropdownParent: $('#addItemModal')
    });
    
    // Ürün seçildiğinde
    $('#product_id').change(function() {
        const option = $(this).find('option:selected');
        $('#birim_fiyat').val(option.data('price'));
        $('#kdv_orani').val(option.data('vat'));
        calculateTotals();
    });
    
    // Miktar veya birim fiyat değiştiğinde
    $('#miktar, #birim_fiyat, #kdv_orani').on('input', calculateTotals);
    
    // Kalem kaydetme
    $('#saveItem').click(function() {
        const form = $('#addItemForm')[0];
        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }
        
        const data = {
            invoice_id: $('[name="invoice_id"]').val(),
            product_id: $('#product_id').val(),
            miktar: $('#miktar').val(),
            birim_fiyat: $('#birim_fiyat').val(),
            kdv_orani: $('#kdv_orani').val()
        };
        
        // AJAX ile kaydet
        $.post('add_item.php', data)
            .done(function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata!',
                        text: response.error
                    });
                }
            })
            .fail(function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata!',
                    text: 'Kalem eklenirken bir hata oluştu'
                });
            });
    });
    
    // Kalem silme
    $('.delete-item').click(function() {
        const id = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: 'Bu kalemi silmek istediğinize emin misiniz?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete_item.php?id=${id}`;
            }
        });
    });
});

// Toplamları hesapla
function calculateTotals() {
    const miktar = parseFloat($('#miktar').val()) || 0;
    const birimFiyat = parseFloat($('#birim_fiyat').val()) || 0;
    const kdvOrani = parseFloat($('#kdv_orani').val()) || 0;
    
    const araToplam = miktar * birimFiyat;
    const kdvTutari = araToplam * (kdvOrani / 100);
    const toplam = araToplam + kdvTutari;
    
    $('#ara_toplam').val(formatMoney(araToplam));
    $('#toplam_tutar').val(formatMoney(toplam));
}

// Para formatı
function formatMoney(amount) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
}</script> 