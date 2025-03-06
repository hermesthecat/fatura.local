<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Müşteri ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    hata("Geçersiz müşteri ID!");
    header("Location: musteri_listele.php");
    exit;
}

$musteri_id = $_GET['id'];
$db = Database::getInstance();

// Müşteri bilgilerini al
$sql = "SELECT * FROM customers WHERE id = :id";
$musteri = $db->query($sql, [':id' => $musteri_id])->fetch();

if (!$musteri) {
    hata("Müşteri bulunamadı!");
    header("Location: musteri_listele.php");
    exit;
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && csrf_token_kontrol($_POST['csrf_token'])) {
        try {
            $sql = "UPDATE customers SET 
                    firma_adi = :firma_adi,
                    vergi_no = :vergi_no,
                    vergi_dairesi = :vergi_dairesi,
                    adres = :adres,
                    telefon = :telefon,
                    email = :email
                    WHERE id = :id";
            
            $params = [
                ':id' => $musteri_id,
                ':firma_adi' => $_POST['firma_adi'],
                ':vergi_no' => $_POST['vergi_no'],
                ':vergi_dairesi' => $_POST['vergi_dairesi'],
                ':adres' => $_POST['adres'],
                ':telefon' => $_POST['telefon'],
                ':email' => $_POST['email']
            ];

            $db->query($sql, $params);
            basari("Müşteri bilgileri başarıyla güncellendi.");
            header("Location: musteri_listele.php");
            exit;
        } catch (Exception $e) {
            hata("Müşteri güncellenirken bir hata oluştu: " . $e->getMessage());
        }
    }
}

// Header'ı en son dahil et
require_once 'templates/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Müşteri Düzenle</h3>
        <a href="musteri_listele.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Geri
        </a>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token_olustur(); ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="firma_adi" class="form-label">Firma Adı</label>
                    <input type="text" name="firma_adi" id="firma_adi" class="form-control" 
                           value="<?php echo guvenlik($musteri['firma_adi']); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="vergi_no" class="form-label">Vergi No</label>
                    <input type="text" name="vergi_no" id="vergi_no" class="form-control" 
                           value="<?php echo guvenlik($musteri['vergi_no']); ?>">
                </div>
                <div class="col-md-3">
                    <label for="vergi_dairesi" class="form-label">Vergi Dairesi</label>
                    <input type="text" name="vergi_dairesi" id="vergi_dairesi" class="form-control" 
                           value="<?php echo guvenlik($musteri['vergi_dairesi']); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="adres" class="form-label">Adres</label>
                    <textarea name="adres" id="adres" class="form-control" rows="3"><?php echo guvenlik($musteri['adres']); ?></textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="telefon" class="form-label">Telefon</label>
                    <input type="tel" name="telefon" id="telefon" class="form-control" 
                           value="<?php echo guvenlik($musteri['telefon']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" name="email" id="email" class="form-control" 
                           value="<?php echo guvenlik($musteri['email']); ?>">
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 