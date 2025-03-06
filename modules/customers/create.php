<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Customer;

$title = 'Yeni Müşteri';
$customer = new Customer();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF kontrolü
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = 'Güvenlik doğrulaması başarısız';
        header('Location: create.php');
        exit;
    }
    
    // Form verilerini al
    $data = [
        'firma_adi' => $_POST['firma_adi'] ?? '',
        'vergi_no' => $_POST['vergi_no'] ?? '',
        'vergi_dairesi' => $_POST['vergi_dairesi'] ?? '',
        'adres' => $_POST['adres'] ?? '',
        'telefon' => $_POST['telefon'] ?? '',
        'email' => $_POST['email'] ?? ''
    ];
    
    // Validasyon
    $errors = $customer->validate($data);
    
    if (empty($errors)) {
        try {
            // Müşteriyi kaydet
            $customer->create($data);
            
            $_SESSION['success'] = 'Müşteri başarıyla eklendi';
            header('Location: index.php');
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Müşteri eklenirken bir hata oluştu: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Yeni Müşteri Ekle</h5>
    </div>
    <div class="card-body">
        <form method="post" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="firma_adi" class="form-label">Firma Adı <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['firma_adi']) ? 'is-invalid' : '' ?>" 
                           id="firma_adi" name="firma_adi" 
                           value="<?= e($_POST['firma_adi'] ?? '') ?>" required>
                    <?= display_errors($errors, 'firma_adi') ?>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="vergi_no" class="form-label">Vergi No</label>
                    <input type="text" class="form-control <?= isset($errors['vergi_no']) ? 'is-invalid' : '' ?>" 
                           id="vergi_no" name="vergi_no" 
                           value="<?= e($_POST['vergi_no'] ?? '') ?>">
                    <?= display_errors($errors, 'vergi_no') ?>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="vergi_dairesi" class="form-label">Vergi Dairesi</label>
                    <input type="text" class="form-control" 
                           id="vergi_dairesi" name="vergi_dairesi" 
                           value="<?= e($_POST['vergi_dairesi'] ?? '') ?>">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="adres" class="form-label">Adres</label>
                <textarea class="form-control" id="adres" name="adres" rows="3"><?= e($_POST['adres'] ?? '') ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="telefon" class="form-label">Telefon</label>
                    <input type="tel" class="form-control <?= isset($errors['telefon']) ? 'is-invalid' : '' ?>" 
                           id="telefon" name="telefon" 
                           value="<?= e($_POST['telefon'] ?? '') ?>">
                    <?= display_errors($errors, 'telefon') ?>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" 
                           id="email" name="email" 
                           value="<?= e($_POST['email'] ?? '') ?>">
                    <?= display_errors($errors, 'email') ?>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Kaydet
                </button>
                
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> İptal
                </a>
            </div>
        </form>
    </div>
</div>

<script>
// Form validasyonu için
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

// Telefon formatı için
const telefon = document.getElementById('telefon');
telefon.addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 0) {
        if (value.length <= 3) {
            value = value;
        } else if (value.length <= 6) {
            value = value.slice(0, 3) + ' ' + value.slice(3);
        } else if (value.length <= 8) {
            value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6);
        } else {
            value = value.slice(0, 3) + ' ' + value.slice(3, 6) + ' ' + value.slice(6, 8) + ' ' + value.slice(8, 10);
        }
    }
    e.target.value = value;
});</script> 