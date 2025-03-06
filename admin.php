<?php
require_once 'templates/header.php';

// Yetki kontrolü eklenebilir
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guncelle'])) {
    try {
        // Logo yükleme
        $logo_path = null;
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['png', 'jpg', 'jpeg'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception('Sadece PNG, JPG ve JPEG formatları kabul edilir.');
            }
            
            $target_path = 'assets/img/logo.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                $logo_path = $target_path;
            }
        }

        // Veritabanı güncelleme
        $sql = "UPDATE company_settings SET 
                unvan = :unvan,
                adres = :adres,
                sehir = :sehir,
                telefon = :telefon,
                email = :email,
                vergi_dairesi = :vergi_dairesi,
                vergi_no = :vergi_no,
                web = :web,
                mersis_no = :mersis_no,
                ticaret_sicil_no = :ticaret_sicil_no,
                banka_adi = :banka_adi,
                iban = :iban";

        $params = [
            ':unvan' => $_POST['unvan'],
            ':adres' => $_POST['adres'],
            ':sehir' => $_POST['sehir'],
            ':telefon' => $_POST['telefon'],
            ':email' => $_POST['email'],
            ':vergi_dairesi' => $_POST['vergi_dairesi'],
            ':vergi_no' => $_POST['vergi_no'],
            ':web' => $_POST['web'],
            ':mersis_no' => $_POST['mersis_no'],
            ':ticaret_sicil_no' => $_POST['ticaret_sicil_no'],
            ':banka_adi' => $_POST['banka_adi'],
            ':iban' => $_POST['iban']
        ];

        if ($logo_path) {
            $sql .= ", logo = :logo";
            $params[':logo'] = $logo_path;
        }

        $sql .= " WHERE id = 1";
        
        $db->query($sql, $params);
        basari("Şirket bilgileri başarıyla güncellendi!");
        header('Location: admin.php');
        exit;
    } catch (Exception $e) {
        hata($e->getMessage());
    }
}

// Mevcut ayarları al
$ayarlar = $db->query("SELECT * FROM company_settings WHERE id = 1")->fetch();
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Şirket Ayarları</h3>
    </div>
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?php echo csrf_token_field(); ?>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <?php if (!empty($ayarlar['logo']) && file_exists($ayarlar['logo'])): ?>
                        <div class="mb-2">
                            <img src="<?php echo $ayarlar['logo']; ?>" alt="Logo" style="max-width: 200px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg">
                    <small class="text-muted">PNG, JPG veya JPEG (max. 2MB)</small>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Şirket Ünvanı</label>
                    <input type="text" name="unvan" class="form-control" value="<?php echo $ayarlar['unvan']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Web Sitesi</label>
                    <input type="text" name="web" class="form-control" value="<?php echo $ayarlar['web']; ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Adres</label>
                    <textarea name="adres" class="form-control" rows="2" required><?php echo $ayarlar['adres']; ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Şehir/Ülke</label>
                    <input type="text" name="sehir" class="form-control" value="<?php echo $ayarlar['sehir']; ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Telefon</label>
                    <input type="text" name="telefon" class="form-control" value="<?php echo $ayarlar['telefon']; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control" value="<?php echo $ayarlar['email']; ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Vergi Dairesi</label>
                    <input type="text" name="vergi_dairesi" class="form-control" value="<?php echo $ayarlar['vergi_dairesi']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Vergi Numarası</label>
                    <input type="text" name="vergi_no" class="form-control" value="<?php echo $ayarlar['vergi_no']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mersis No</label>
                    <input type="text" name="mersis_no" class="form-control" value="<?php echo $ayarlar['mersis_no']; ?>" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Ticaret Sicil No</label>
                    <input type="text" name="ticaret_sicil_no" class="form-control" value="<?php echo $ayarlar['ticaret_sicil_no']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Banka Adı</label>
                    <input type="text" name="banka_adi" class="form-control" value="<?php echo $ayarlar['banka_adi']; ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">IBAN</label>
                    <input type="text" name="iban" class="form-control" value="<?php echo $ayarlar['iban']; ?>" required>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" name="guncelle" class="btn btn-primary">
                    <i class="bi bi-save"></i> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 