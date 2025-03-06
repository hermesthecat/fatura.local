<?php
require_once 'templates/header.php';

// Yetki kontrolü
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['guncelle_firma'])) {
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

            // Firma bilgilerini güncelle
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
            basari("Firma bilgileri başarıyla güncellendi!");

        } elseif (isset($_POST['guncelle_sistem'])) {
            // Sistem ayarlarını güncelle
            foreach ($_POST['ayarlar'] as $ayar_adi => $ayar_degeri) {
                $db->query("UPDATE system_settings SET ayar_degeri = :deger WHERE ayar_adi = :adi",
                    [':deger' => $ayar_degeri, ':adi' => $ayar_adi]);
            }
            basari("Sistem ayarları başarıyla güncellendi!");
        }

        header('Location: admin.php');
        exit;
    } catch (Exception $e) {
        hata($e->getMessage());
    }
}

// Mevcut ayarları al
$firma = $db->query("SELECT * FROM company_settings WHERE id = 1")->fetch();
$sistem_ayarlari = $db->query("SELECT * FROM system_settings ORDER BY ayar_adi")->fetchAll();
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <ul class="nav nav-tabs" id="adminTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="firma-tab" data-bs-toggle="tab" href="#firma" role="tab">
                    <i class="bi bi-building"></i> Firma Bilgileri
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="sistem-tab" data-bs-toggle="tab" href="#sistem" role="tab">
                    <i class="bi bi-gear"></i> Sistem Ayarları
                </a>
            </li>
        </ul>
    </div>
</div>

<div class="tab-content" id="adminTabContent">
    <!-- Firma Bilgileri -->
    <div class="tab-pane fade show active" id="firma" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Firma Bilgileri</h3>
            </div>
            <div class="card-body">
                <form method="post" enctype="multipart/form-data">
                    <?php echo csrf_token_field(); ?>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <?php if (!empty($firma['logo']) && file_exists($firma['logo'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo $firma['logo']; ?>" alt="Logo" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg">
                            <small class="text-muted">PNG, JPG veya JPEG (max. 2MB)</small>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Şirket Ünvanı</label>
                            <input type="text" name="unvan" class="form-control" value="<?php echo $firma['unvan']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Web Sitesi</label>
                            <input type="text" name="web" class="form-control" value="<?php echo $firma['web']; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control" rows="2" required><?php echo $firma['adres']; ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Şehir/Ülke</label>
                            <input type="text" name="sehir" class="form-control" value="<?php echo $firma['sehir']; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="telefon" class="form-control" value="<?php echo $firma['telefon']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $firma['email']; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Vergi Dairesi</label>
                            <input type="text" name="vergi_dairesi" class="form-control" value="<?php echo $firma['vergi_dairesi']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vergi Numarası</label>
                            <input type="text" name="vergi_no" class="form-control" value="<?php echo $firma['vergi_no']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mersis No</label>
                            <input type="text" name="mersis_no" class="form-control" value="<?php echo $firma['mersis_no']; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Ticaret Sicil No</label>
                            <input type="text" name="ticaret_sicil_no" class="form-control" value="<?php echo $firma['ticaret_sicil_no']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Banka Adı</label>
                            <input type="text" name="banka_adi" class="form-control" value="<?php echo $firma['banka_adi']; ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">IBAN</label>
                            <input type="text" name="iban" class="form-control" value="<?php echo $firma['iban']; ?>" required>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="guncelle_firma" class="btn btn-primary">
                            <i class="bi bi-save"></i> Firma Bilgilerini Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sistem Ayarları -->
    <div class="tab-pane fade" id="sistem" role="tabpanel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sistem Ayarları</h3>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php echo csrf_token_field(); ?>
                    
                    <?php foreach ($sistem_ayarlari as $ayar): ?>
                    <div class="mb-3">
                        <label class="form-label">
                            <?php echo ucwords(str_replace('_', ' ', $ayar['ayar_adi'])); ?>
                            <?php if ($ayar['aciklama']): ?>
                                <i class="bi bi-info-circle" title="<?php echo $ayar['aciklama']; ?>"></i>
                            <?php endif; ?>
                        </label>
                        <input type="text" name="ayarlar[<?php echo $ayar['ayar_adi']; ?>]" 
                               class="form-control" value="<?php echo $ayar['ayar_degeri']; ?>" required>
                    </div>
                    <?php endforeach; ?>

                    <div class="text-end">
                        <button type="submit" name="guncelle_sistem" class="btn btn-primary">
                            <i class="bi bi-save"></i> Sistem Ayarlarını Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 