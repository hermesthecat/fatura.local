<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Product;

$title = 'Yeni Ürün';
$product = new Product();

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
        'urun_kodu' => $_POST['urun_kodu'] ?? '',
        'urun_adi' => $_POST['urun_adi'] ?? '',
        'birim' => $_POST['birim'] ?? '',
        'birim_fiyat' => $_POST['birim_fiyat'] ?? '',
        'kdv_orani' => $_POST['kdv_orani'] ?? '',
        'aciklama' => $_POST['aciklama'] ?? '',
        'durum' => 'aktif'
    ];
    
    // Validasyon
    $errors = $product->validate($data);
    
    if (empty($errors)) {
        try {
            // Ürünü kaydet
            $product_id = $product->create($data);
            
            $_SESSION['success'] = 'Ürün başarıyla oluşturuldu';
            header("Location: view.php?id=$product_id");
            exit;
            
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Ürün oluşturulurken bir hata oluştu: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Yeni Ürün Oluştur</h5>
    </div>
    <div class="card-body">
        <form method="post" action="" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="urun_kodu" class="form-label">Ürün Kodu <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['urun_kodu']) ? 'is-invalid' : '' ?>" 
                           id="urun_kodu" name="urun_kodu" 
                           value="<?= e($_POST['urun_kodu'] ?? '') ?>" required>
                    <?= display_errors($errors, 'urun_kodu') ?>
                </div>
                
                <div class="col-md-8 mb-3">
                    <label for="urun_adi" class="form-label">Ürün Adı <span class="text-danger">*</span></label>
                    <input type="text" class="form-control <?= isset($errors['urun_adi']) ? 'is-invalid' : '' ?>" 
                           id="urun_adi" name="urun_adi" 
                           value="<?= e($_POST['urun_adi'] ?? '') ?>" required>
                    <?= display_errors($errors, 'urun_adi') ?>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="birim" class="form-label">Birim <span class="text-danger">*</span></label>
                    <select class="form-select <?= isset($errors['birim']) ? 'is-invalid' : '' ?>" 
                            id="birim" name="birim" required>
                        <option value="">Birim Seçin</option>
                        <option value="ADET" <?= ($_POST['birim'] ?? '') === 'ADET' ? 'selected' : '' ?>>ADET</option>
                        <option value="KG" <?= ($_POST['birim'] ?? '') === 'KG' ? 'selected' : '' ?>>KG</option>
                        <option value="LT" <?= ($_POST['birim'] ?? '') === 'LT' ? 'selected' : '' ?>>LT</option>
                        <option value="MT" <?= ($_POST['birim'] ?? '') === 'MT' ? 'selected' : '' ?>>MT</option>
                        <option value="M2" <?= ($_POST['birim'] ?? '') === 'M2' ? 'selected' : '' ?>>M²</option>
                        <option value="M3" <?= ($_POST['birim'] ?? '') === 'M3' ? 'selected' : '' ?>>M³</option>
                    </select>
                    <?= display_errors($errors, 'birim') ?>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="birim_fiyat" class="form-label">Birim Fiyat <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control <?= isset($errors['birim_fiyat']) ? 'is-invalid' : '' ?>" 
                               id="birim_fiyat" name="birim_fiyat" 
                               value="<?= e($_POST['birim_fiyat'] ?? '') ?>" 
                               min="0.01" step="0.01" required>
                        <span class="input-group-text">₺</span>
                        <?= display_errors($errors, 'birim_fiyat') ?>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="kdv_orani" class="form-label">KDV Oranı <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" class="form-control <?= isset($errors['kdv_orani']) ? 'is-invalid' : '' ?>" 
                               id="kdv_orani" name="kdv_orani" 
                               value="<?= e($_POST['kdv_orani'] ?? '18') ?>" 
                               min="0" max="100" required>
                        <span class="input-group-text">%</span>
                        <?= display_errors($errors, 'kdv_orani') ?>
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="aciklama" class="form-label">Açıklama</label>
                <textarea class="form-control" id="aciklama" name="aciklama" rows="3"><?= e($_POST['aciklama'] ?? '') ?></textarea>
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
})();</script> 