<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$db = Database::getInstance();

// Debug modu
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || $_GET['id'] < 1) {
    header('Location: sirketler.php');
    exit;
}

// Debug bilgileri
echo "<pre>";
echo "GET ID: " . htmlspecialchars($_GET['id']) . "\n";

// Şirket bilgilerini al
$sql = "SELECT c.* FROM companies c WHERE c.id = :id";
$params = [':id' => intval($_GET['id'])];

// SQL ve parametreleri göster
echo "SQL: " . $sql . "\n";
echo "Params: ";
print_r($params);

$sirket = $db->query($sql, $params)->fetch(PDO::FETCH_ASSOC);

// Bulunan şirket bilgilerini göster
echo "Bulunan Şirket: ";
print_r($sirket);

// Şirket bulunamadıysa ana sayfaya yönlendir
if (!$sirket) {
    header('Location: sirketler.php');
    exit;
}

// Şirket ayarlarını al
$sql_ayarlar = "SELECT ayar_adi, ayar_degeri FROM company_settings WHERE company_id = :company_id";
$ayarlar = $db->query($sql_ayarlar, [':company_id' => $sirket['id']])->fetchAll(PDO::FETCH_KEY_PAIR);

// Varsayılan değerler
$sirket['fatura_not'] = $ayarlar['FATURA_NOT'] ?? '';
$sirket['varsayilan_kdv'] = $ayarlar['VARSAYILAN_KDV'] ?? '18';

echo "Şirket Ayarları: ";
print_r($ayarlar);
echo "</pre>";

// Şirketin kullanıcılarını al
$sirket_kullanicilari = $db->query(
    "SELECT user_id FROM user_companies WHERE company_id = :company_id",
    [':company_id' => $sirket['id']]
)->fetchAll(PDO::FETCH_COLUMN);

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
            ':id' => $_GET['id']
        ];

        $db->query($sql, $params);

        // Şirket ayarlarını güncelle
        $ayarlar = [
            'FATURA_NOT' => $_POST['fatura_not'],
            'VARSAYILAN_KDV' => $_POST['varsayilan_kdv']
        ];

        foreach ($ayarlar as $ayar_adi => $ayar_degeri) {
            // Önce ayarı sil
            $db->query(
                "DELETE FROM company_settings WHERE company_id = :company_id AND ayar_adi = :ayar_adi",
                [':company_id' => $_GET['id'], ':ayar_adi' => $ayar_adi]
            );

            // Sonra yeni değeri ekle
            if (!empty($ayar_degeri)) {
                $db->query(
                    "INSERT INTO company_settings (company_id, ayar_adi, ayar_degeri) VALUES (:company_id, :ayar_adi, :ayar_degeri)",
                    [
                        ':company_id' => $_GET['id'],
                        ':ayar_adi' => $ayar_adi,
                        ':ayar_degeri' => $ayar_degeri
                    ]
                );
            }
        }

        // Kullanıcı ilişkilerini güncelle
        $db->query(
            "DELETE FROM user_companies WHERE company_id = :company_id",
            [':company_id' => $sirket['id']]
        );

        if (isset($_POST['kullanicilar']) && is_array($_POST['kullanicilar'])) {
            foreach ($_POST['kullanicilar'] as $user_id) {
                $db->query(
                    "INSERT INTO user_companies (user_id, company_id) VALUES (:user_id, :company_id)",
                    [':user_id' => $user_id, ':company_id' => $sirket['id']]
                );
            }
        }

        basari("Şirket bilgileri başarıyla güncellendi!");
        header('Location: sirketler.php');
        exit;
    } catch (Exception $e) {
        hata("Şirket güncellenirken bir hata oluştu: " . $e->getMessage());
    }
}

// Tüm kullanıcıları al
$kullanicilar = $db->query("SELECT * FROM users ORDER BY ad_soyad")->fetchAll();

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
                <?php echo csrf_token_field(); ?>

                <div class="row">
                    <div class="col-md-6">
                        <h5 class="card-title mb-4">Şirket Bilgileri</h5>

                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <?php if ($sirket['logo'] && file_exists($sirket['logo'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo $sirket['logo']; ?>" alt="Logo" style="max-width: 200px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="logo" class="form-control" accept=".png,.jpg,.jpeg">
                            <small class="text-muted">PNG, JPG veya JPEG (max. 2MB)</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ünvan</label>
                            <input type="text" name="unvan" class="form-control" value="<?php echo $sirket['unvan']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vergi Dairesi</label>
                            <input type="text" name="vergi_dairesi" class="form-control" value="<?php echo $sirket['vergi_dairesi']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Vergi No</label>
                            <input type="text" name="vergi_no" class="form-control" value="<?php echo $sirket['vergi_no']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adres</label>
                            <textarea name="adres" class="form-control" rows="3" required><?php echo $sirket['adres']; ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Şehir</label>
                            <input type="text" name="sehir" class="form-control" value="<?php echo $sirket['sehir']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="telefon" class="form-control" value="<?php echo $sirket['telefon']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" name="email" class="form-control" value="<?php echo $sirket['email']; ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Web Sitesi</label>
                            <input type="url" name="web" class="form-control" value="<?php echo $sirket['web']; ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h5 class="card-title mb-4">Diğer Bilgiler</h5>

                        <div class="mb-3">
                            <label class="form-label">Mersis No</label>
                            <input type="text" name="mersis_no" class="form-control" value="<?php echo $sirket['mersis_no']; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ticaret Sicil No</label>
                            <input type="text" name="ticaret_sicil_no" class="form-control" value="<?php echo $sirket['ticaret_sicil_no']; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Banka Adı</label>
                            <input type="text" name="banka_adi" class="form-control" value="<?php echo $sirket['banka_adi']; ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">IBAN</label>
                            <input type="text" name="iban" class="form-control" value="<?php echo $sirket['iban']; ?>">
                        </div>

                        <h5 class="card-title mb-4">Şirket Ayarları</h5>

                        <div class="mb-3">
                            <label class="form-label">Varsayılan Fatura Notu</label>
                            <textarea name="fatura_not" class="form-control" rows="3"><?php echo $sirket['fatura_not']; ?></textarea>
                            <div class="form-text">Bu not tüm faturalarda varsayılan olarak görünecektir.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Varsayılan KDV Oranı (%)</label>
                            <input type="number" name="varsayilan_kdv" class="form-control" value="<?php echo $sirket['varsayilan_kdv']; ?>" min="0" max="100">
                            <div class="form-text">Yeni fatura oluştururken kullanılacak varsayılan KDV oranı.</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="aktif" class="form-check-input" value="1" <?php echo $sirket['aktif'] ? 'checked' : ''; ?>>
                                <label class="form-check-label">Aktif</label>
                            </div>
                        </div>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>