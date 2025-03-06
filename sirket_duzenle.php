<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Şirket ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    hata("Geçersiz şirket ID'si!");
    header('Location: sirketler.php');
    exit;
}

$company_id = (int)$_GET['id'];

// Şirket bilgilerini getir
try {
    $company = $db->query("SELECT * FROM companies WHERE id = :id", [':id' => $company_id])->fetch();
    if (!$company) {
        hata("Şirket bulunamadı!");
        header('Location: sirketler.php');
        exit;
    }

    // Şirket ayarlarını getir
    $settings = [];
    $company_settings = $db->query("SELECT * FROM company_settings WHERE company_id = :company_id", [':company_id' => $company_id])->fetchAll();
    foreach ($company_settings as $setting) {
        $settings[$setting['ayar_adi']] = $setting['ayar_degeri'];
    }
} catch (Exception $e) {
    hata("Şirket bilgileri alınırken bir hata oluştu: " . $e->getMessage());
    header('Location: sirketler.php');
    exit;
}

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Logo yükleme işlemi
        $logo_path = $company['logo']; // Mevcut logo yolunu koru
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['logo']['tmp_name'];
            $name = basename($_FILES['logo']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            
            // Dosya türü kontrolü
            if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                throw new Exception("Sadece JPG, JPEG ve PNG dosyaları yüklenebilir!");
            }
            
            // Dosya boyutu kontrolü (2MB)
            if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Logo dosyası 2MB'dan büyük olamaz!");
            }
            
            // Eski logoyu sil
            if ($company['logo'] && file_exists($company['logo'])) {
                unlink($company['logo']);
            }
            
            // Yeni logoyu kaydet
            $logo_path = 'uploads/logos/' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($tmp_name, $logo_path)) {
                throw new Exception("Logo yüklenirken bir hata oluştu!");
            }
        }

        // Şirket bilgilerini güncelle
        $sql = "UPDATE companies SET 
            unvan = :unvan,
            vergi_dairesi = :vergi_dairesi,
            vergi_no = :vergi_no,
            adres = :adres,
            sehir = :sehir,
            telefon = :telefon,
            email = :email,
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
            ':vergi_dairesi' => $_POST['vergi_dairesi'],
            ':vergi_no' => $_POST['vergi_no'],
            ':adres' => $_POST['adres'],
            ':sehir' => $_POST['sehir'],
            ':telefon' => $_POST['telefon'],
            ':email' => $_POST['email'],
            ':web' => $_POST['web'],
            ':mersis_no' => $_POST['mersis_no'],
            ':ticaret_sicil_no' => $_POST['ticaret_sicil_no'],
            ':banka_adi' => $_POST['banka_adi'],
            ':iban' => $_POST['iban'],
            ':logo' => $logo_path,
            ':aktif' => isset($_POST['aktif']) ? 1 : 0,
            ':id' => $company_id
        ];

        $db->query($sql, $params);

        // Şirket ayarlarını güncelle
        $ayarlar = [
            'FATURA_NOT' => $_POST['fatura_not'],
            'VARSAYILAN_KDV' => $_POST['varsayilan_kdv']
        ];

        foreach ($ayarlar as $ayar_adi => $ayar_degeri) {
            if (!empty($ayar_degeri)) {
                // Önce mevcut ayarı sil
                $db->query("DELETE FROM company_settings WHERE company_id = :company_id AND ayar_adi = :ayar_adi",
                    [':company_id' => $company_id, ':ayar_adi' => $ayar_adi]);
                
                // Yeni ayarı ekle
                $db->query("INSERT INTO company_settings (company_id, ayar_adi, ayar_degeri) VALUES (:company_id, :ayar_adi, :ayar_degeri)",
                    [
                        ':company_id' => $company_id,
                        ':ayar_adi' => $ayar_adi,
                        ':ayar_degeri' => $ayar_degeri
                    ]);
            }
        }

        basari("Şirket başarıyla güncellendi.");
        header('Location: sirketler.php');
        exit;
    } catch (Exception $e) {
        hata("Şirket güncellenirken bir hata oluştu: " . $e->getMessage());
    }
}

// Header'ı en son dahil et
require_once 'templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">Şirket Düzenle</h1>
        </div>
        <div class="col text-end">
            <a href="sirketler.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Geri
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title mb-4">Şirket Bilgileri</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <?php if ($company['logo']): ?>
                                <div class="mb-2">
                                    <img src="<?= htmlspecialchars($company['logo']) ?>" alt="Şirket Logosu" style="max-width: 200px; max-height: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo" class="form-control" accept="image/png,image/jpeg">
                            <div class="form-text">PNG, JPG veya JPEG (max. 2MB)</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ünvan</label>
                            <input type="text" name="unvan" class="form-control" required value="<?= htmlspecialchars($company['unvan']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vergi Dairesi</label>
                            <input type="text" name="vergi_dairesi" class="form-control" required value="<?= htmlspecialchars($company['vergi_dairesi']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vergi No</label>
                            <input type="text" name="vergi_no" class="form-control" required value="<?= htmlspecialchars($company['vergi_no']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control" rows="3" required><?= htmlspecialchars($company['adres']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Şehir</label>
                            <input type="text" name="sehir" class="form-control" required value="<?= htmlspecialchars($company['sehir']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="telefon" class="form-control" required value="<?= htmlspecialchars($company['telefon']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($company['email']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Web Sitesi</label>
                            <input type="url" name="web" class="form-control" value="<?= htmlspecialchars($company['web']) ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="card-title mb-4">Diğer Bilgiler</h5>

                        <div class="mb-3">
                            <label class="form-label">Mersis No</label>
                            <input type="text" name="mersis_no" class="form-control" value="<?= htmlspecialchars($company['mersis_no']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ticaret Sicil No</label>
                            <input type="text" name="ticaret_sicil_no" class="form-control" value="<?= htmlspecialchars($company['ticaret_sicil_no']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Banka Adı</label>
                            <input type="text" name="banka_adi" class="form-control" value="<?= htmlspecialchars($company['banka_adi']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">IBAN</label>
                            <input type="text" name="iban" class="form-control" value="<?= htmlspecialchars($company['iban']) ?>">
                        </div>

                        <h5 class="card-title mb-4">Şirket Ayarları</h5>

                        <div class="mb-3">
                            <label class="form-label">Varsayılan Fatura Notu</label>
                            <textarea name="fatura_not" class="form-control" rows="3"><?= htmlspecialchars($settings['FATURA_NOT'] ?? '') ?></textarea>
                            <div class="form-text">Bu not tüm faturalarda varsayılan olarak görünecektir.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Varsayılan KDV Oranı (%)</label>
                            <input type="number" name="varsayilan_kdv" class="form-control" value="<?= htmlspecialchars($settings['VARSAYILAN_KDV'] ?? '18') ?>" min="0" max="100">
                            <div class="form-text">Yeni fatura oluştururken kullanılacak varsayılan KDV oranı.</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="aktif" class="form-check-input" value="1" <?= $company['aktif'] ? 'checked' : '' ?>>
                                <label class="form-check-label">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 