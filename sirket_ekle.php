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
        // Logo yükleme
        $logo_path = null;
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
                $logo_path = $target_path;
            }
        }

        // Şirket bilgilerini ekle
        $sql = "INSERT INTO companies (
            unvan, adres, sehir, telefon, email, vergi_dairesi, vergi_no, 
            web, mersis_no, ticaret_sicil_no, banka_adi, iban, logo, aktif
        ) VALUES (
            :unvan, :adres, :sehir, :telefon, :email, :vergi_dairesi, :vergi_no,
            :web, :mersis_no, :ticaret_sicil_no, :banka_adi, :iban, :logo, :aktif
        )";

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
            ':aktif' => isset($_POST['aktif']) ? 1 : 0
        ];

        $db->query($sql, $params);
        $company_id = $db->lastInsertId();

        // Seçili kullanıcıları şirkete ekle
        if (isset($_POST['kullanicilar']) && is_array($_POST['kullanicilar'])) {
            foreach ($_POST['kullanicilar'] as $user_id) {
                $db->query("INSERT INTO user_companies (user_id, company_id) VALUES (:user_id, :company_id)",
                    [':user_id' => $user_id, ':company_id' => $company_id]);
            }
        }

        basari("Şirket başarıyla eklendi!");
        header('Location: sirketler.php');
        exit;
    } catch (Exception $e) {
        hata($e->getMessage());
    }
}

// Kullanıcıları al
$kullanicilar = $db->query("SELECT * FROM users ORDER BY ad_soyad")->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Yeni Şirket Ekle</h1>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <?php echo csrf_token_field(); ?>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Logo</label>
                        <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg">
                        <small class="text-muted">PNG, JPG veya JPEG (max. 2MB)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Durum</label>
                        <div class="form-check">
                            <input type="checkbox" name="aktif" value="1" class="form-check-input" checked>
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Şirket Ünvanı</label>
                        <input type="text" name="unvan" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Web Sitesi</label>
                        <input type="text" name="web" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Adres</label>
                        <textarea name="adres" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Şehir</label>
                        <input type="text" name="sehir" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="telefon" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-posta</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Vergi Dairesi</label>
                        <input type="text" name="vergi_dairesi" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vergi Numarası</label>
                        <input type="text" name="vergi_no" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Mersis No</label>
                        <input type="text" name="mersis_no" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Ticaret Sicil No</label>
                        <input type="text" name="ticaret_sicil_no" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Banka Adı</label>
                        <input type="text" name="banka_adi" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" class="form-control" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label">Kullanıcılar</label>
                        <select name="kullanicilar[]" class="form-select" multiple>
                            <?php foreach ($kullanicilar as $kullanici): ?>
                            <option value="<?php echo $kullanici['id']; ?>">
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