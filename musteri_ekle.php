<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Şirket seçili değilse ana sayfaya yönlendir
if (!isset($_SESSION['company_id'])) {
    hata("Lütfen önce bir şirket seçin!");
    header('Location: index.php');
    exit;
}

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && csrf_token_kontrol($_POST['csrf_token'])) {
        try {
            $db = Database::getInstance();
            
            $sql = "INSERT INTO customers (company_id, firma_adi, vergi_no, vergi_dairesi, adres, telefon, email) 
                    VALUES (:company_id, :firma_adi, :vergi_no, :vergi_dairesi, :adres, :telefon, :email)";
            
            $params = [
                ':company_id' => $_SESSION['company_id'],
                ':firma_adi' => $_POST['firma_adi'],
                ':vergi_no' => $_POST['vergi_no'],
                ':vergi_dairesi' => $_POST['vergi_dairesi'],
                ':adres' => $_POST['adres'],
                ':telefon' => $_POST['telefon'],
                ':email' => $_POST['email']
            ];

            $db->query($sql, $params);
            basari("Müşteri başarıyla eklendi.");
            header("Location: musteri_listele.php");
            exit;
        } catch (Exception $e) {
            hata("Müşteri eklenirken bir hata oluştu: " . $e->getMessage());
        }
    }
}

// Header'ı en son dahil et
require_once 'templates/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Yeni Müşteri Ekle</h3>
        <div>
            <span class="text-muted me-3">
                <i class="bi bi-building"></i> <?php echo $_SESSION['company_unvan']; ?>
            </span>
            <a href="musteri_listele.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Geri
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token_olustur(); ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="firma_adi" class="form-label">Firma Adı</label>
                    <input type="text" name="firma_adi" id="firma_adi" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label for="vergi_no" class="form-label">Vergi No</label>
                    <input type="text" name="vergi_no" id="vergi_no" class="form-control">
                </div>
                <div class="col-md-3">
                    <label for="vergi_dairesi" class="form-label">Vergi Dairesi</label>
                    <input type="text" name="vergi_dairesi" id="vergi_dairesi" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-12">
                    <label for="adres" class="form-label">Adres</label>
                    <textarea name="adres" id="adres" class="form-control" rows="3"></textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="telefon" class="form-label">Telefon</label>
                    <input type="tel" name="telefon" id="telefon" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">E-posta</label>
                    <input type="email" name="email" id="email" class="form-control">
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Müşteriyi Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 