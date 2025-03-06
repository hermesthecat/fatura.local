<?php
require_once 'templates/header.php';

// Yetki kontrolü
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// ID kontrolü
if (!isset($_GET['id'])) {
    header('Location: sirketler.php');
    exit;
}

// Şirket bilgilerini al
$sirket = $db->query("SELECT * FROM companies WHERE id = :id", 
    [':id' => $_GET['id']])->fetch();

if (!$sirket) {
    header('Location: sirketler.php');
    exit;
}

// Şirketin kullanıcılarını al
$sirket_kullanicilari = $db->query("SELECT user_id FROM user_companies WHERE company_id = :company_id",
    [':company_id' => $sirket['id']])->fetchAll(PDO::FETCH_COLUMN);

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Logo yükleme
        $logo_path = $sirket['logo'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['png', 'jpg', 'jpeg'];
            $filename = $_FILES['logo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $allowed)) {
                throw new Exception('Sadece PNG, JPG ve JPEG formatları kabul edilir.');
            }
            
            $target_path = 'assets/img/company_logos/' . uniqid() . '.' . $ext;
            if (!is_dir('assets/img/company_logos')) {
                mkdir('assets/img/company_logos', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                // Eski logoyu sil
                if ($logo_path && file_exists($logo_path)) {
                    unlink($logo_path);
                }
                $logo_path = $target_path;
            }
        }

        // Şirket bilgilerini güncelle
        $sql = "UPDATE companies SET 
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
            iban = :iban,
            logo = :logo,
            aktif = :aktif
            WHERE id = :id";

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
            ':iban' => $_POST['iban'],
            ':logo' => $logo_path,
            ':aktif' => isset($_POST['aktif']) ? 1 : 0,
            ':id' => $sirket['id']
        ];

        $db->query($sql, $params);

        // Kullanıcı ilişkilerini güncelle
        $db->query("DELETE FROM user_companies WHERE company_id = :company_id",
            [':company_id' => $sirket['id']]);

        if (isset($_POST['kullanicilar']) && is_array($_POST['kullanicilar'])) {
            foreach ($_POST['kullanicilar'] as $user_id) {
                $db->query("INSERT INTO user_companies (user_id, company_id) VALUES (:user_id, :company_id)",
                    [':user_id' => $user_id, ':company_id' => $sirket['id']]);
            }
        }

        basari("Şirket bilgileri başarıyla güncellendi!");
        header('Location: sirketler.php');
        exit;
    } catch (Exception $e) {
        hata($e->getMessage());
    }
}

// Tüm kullanıcıları al
$kullanicilar = $db->query("SELECT * FROM users ORDER BY ad_soyad")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Şirket Düzenle: <?php echo $sirket['unvan']; ?></h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <?php echo csrf_token_field(); ?>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Logo</label>
                        <?php if ($sirket['logo'] && file_exists($sirket['logo'])): ?>
                            <div class="mb-2">
                                <img src="<?php echo $sirket['logo']; ?>" alt="Logo" style="max-width: 200px;">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg">
                        <small class="text-muted">PNG, JPG veya JPEG (max. 2MB)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Durum</label>
                        <div class="form-check">
                            <input type="checkbox" name="aktif" value="1" class="form-check-input" 
                                <?php echo $sirket['aktif'] ? 'checked' : ''; ?>>
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Şirket Ünvanı</label>
                        <input type="text" name="unvan" class="form-control" 
                               value="<?php echo $sirket['unvan']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Web Sitesi</label>
                        <input type="text" name="web" class="form-control" 
                               value="<?php echo $sirket['web']; ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Adres</label>
                        <textarea name="adres" class="form-control" rows="2" required><?php echo $sirket['adres']; ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Şehir</label>
                        <input type="text" name="sehir" class="form-control" 
                               value="<?php echo $sirket['sehir']; ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="telefon" class="form-control" 
                               value="<?php echo $sirket['telefon']; ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo $sirket['email']; ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" name="vergi_dairesi" class="form-control" 
                               value="<?php echo $sirket['vergi_dairesi']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vergi Numarası</label>
                        <input type="text" name="vergi_no" class="form-control" 
                               value="<?php echo $sirket['vergi_no']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mersis No</label>
                        <input type="text" name="mersis_no" class="form-control" 
                               value="<?php echo $sirket['mersis_no']; ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Ticaret Sicil No</label>
                        <input type="text" name="ticaret_sicil_no" class="form-control" 
                               value="<?php echo $sirket['ticaret_sicil_no']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Banka Adı</label>
                        <input type="text" name="banka_adi" class="form-control" 
                               value="<?php echo $sirket['banka_adi']; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" class="form-control" 
                               value="<?php echo $sirket['iban']; ?>" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Kullanıcılar</label>
                        <select name="kullanicilar[]" class="form-select" multiple>
                            <?php foreach ($kullanicilar as $kullanici): ?>
                            <option value="<?php echo $kullanici['id']; ?>"
                                <?php echo in_array($kullanici['id'], $sirket_kullanicilari) ? 'selected' : ''; ?>>
                                <?php echo $kullanici['ad_soyad']; ?> (<?php echo $kullanici['email']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Birden fazla seçim için CTRL tuşunu basılı tutun</small>
                    </div>
                </div>

                <div class="text-end">
                    <a href="sirketler.php" class="btn btn-secondary">İptal</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 