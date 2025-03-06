<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Invoice;
use App\Customer;
use App\Product;

$title = 'Yeni Fatura';
$invoice = new Invoice();
$customer = new Customer();
$product = new Product();

$errors = [];
$customers = $customer->all();
$products = $product->all();

// Seçili müşteri
$selected_customer_id = filter_input(INPUT_GET, 'customer_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız';
        header('Location: create.php');
        exit;
    }
    
    // Form verilerini al
    $data = [
        'customer_id' => $_POST['customer_id'] ?? '',
        'fatura_tarihi' => $_POST['fatura_tarihi'] ?? '',
        'vade_tarihi' => $_POST['vade_tarihi'] ?? '',
        'aciklama' => $_POST['aciklama'] ?? '',
        'durum' => 'taslak'
    ];
    
    // Validasyon
    $errors = $invoice->validate($data);
    
    if (empty($errors)) {
        try {
            // Fatura numarası oluştur
            $data['fatura_no'] = $invoice->generateInvoiceNumber();
            
            // Faturayı kaydet
            $invoice_id = $invoice->create($data);
            
            $_SESSION['success'] = 'Fatura başarıyla oluşturuldu';
            header("Location: edit.php?id=$invoice_id");
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Fatura oluşturulurken bir hata oluştu: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Yeni Fatura Oluştur</h5>
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
                        <option value="<?= $c['id'] ?>" <?= ($selected_customer_id == $c['id'] || ($_POST['customer_id'] ?? '') == $c['id']) ? 'selected' : '' ?>>
                            <?= e($c['firma_adi']) ?> 
                            <?php if ($c['vergi_no']): ?>
                            (<?= e($c['vergi_no']) ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?= display_errors($errors, 'customer_id') ?>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="fatura_tarihi" class="form-label">Fatura Tarihi <span class="text-danger">*</span></label>
                    <input type="date" class="form-control <?= isset($errors['fatura_tarihi']) ? 'is-invalid' : '' ?>" 
                           id="fatura_tarihi" name="fatura_tarihi" 
                           value="<?= e($_POST['fatura_tarihi'] ?? date('Y-m-d')) ?>" required>
                    <?= display_errors($errors, 'fatura_tarihi') ?>
                </div>
                
                <div class="col-md-3 mb-3">
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
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Fatura kalemlerini eklemek için önce faturayı kaydetmeniz gerekmektedir.
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kaydet ve Devam Et
                </button>
                
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> İptal
                </a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
        width: '100%'
    });
});</script> 