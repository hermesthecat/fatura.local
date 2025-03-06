<?php

/**
 * Kullanıcı Profil Sayfası
 * @author A. Kerem Gök
 */

require_once 'templates/header.php';

// Oturum kontrolü header.php'de yapılıyor

$db = Database::getInstance();

// Profil güncelleme işlemi
if (isset($_POST['guncelle'])) {
    $ad_soyad = trim($_POST['ad_soyad']);
    $email = trim($_POST['email']);
    $mevcut_sifre = trim($_POST['mevcut_sifre']);
    $yeni_sifre = trim($_POST['yeni_sifre']);
    $yeni_sifre_tekrar = trim($_POST['yeni_sifre_tekrar']);

    // Validasyon
    $hatalar = [];

    if (empty($ad_soyad)) {
        $hatalar[] = "Ad Soyad alanı boş bırakılamaz.";
    }

    if (empty($email)) {
        $hatalar[] = "E-posta alanı boş bırakılamaz.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $hatalar[] = "Geçerli bir e-posta adresi giriniz.";
    }

    // Şifre değişikliği yapılacaksa
    if (!empty($mevcut_sifre) || !empty($yeni_sifre) || !empty($yeni_sifre_tekrar)) {
        // Mevcut şifre kontrolü
        $user = $db->query(
            "SELECT password FROM users WHERE id = :id",
            [':id' => $_SESSION['user']['id']]
        )->fetch();

        if (!password_verify($mevcut_sifre, $user['password'])) {
            $hatalar[] = "Mevcut şifreniz hatalı.";
        }

        if (empty($yeni_sifre)) {
            $hatalar[] = "Yeni şifre alanı boş bırakılamaz.";
        } elseif (strlen($yeni_sifre) < 6) {
            $hatalar[] = "Yeni şifre en az 6 karakter olmalıdır.";
        }

        if ($yeni_sifre !== $yeni_sifre_tekrar) {
            $hatalar[] = "Yeni şifreler eşleşmiyor.";
        }
    }

    if (empty($hatalar)) {
        try {
            if (!empty($yeni_sifre)) {
                // Şifre değişikliği varsa
                $db->query(
                    "UPDATE users SET 
                    ad_soyad = :ad_soyad,
                    email = :email,
                    password = :password
                    WHERE id = :id",
                    [
                        ':ad_soyad' => $ad_soyad,
                        ':email' => $email,
                        ':password' => password_hash($yeni_sifre, PASSWORD_DEFAULT),
                        ':id' => $_SESSION['user']['id']
                    ]
                );
            } else {
                // Şifre değişikliği yoksa
                $db->query(
                    "UPDATE users SET 
                    ad_soyad = :ad_soyad,
                    email = :email
                    WHERE id = :id",
                    [
                        ':ad_soyad' => $ad_soyad,
                        ':email' => $email,
                        ':id' => $_SESSION['user']['id']
                    ]
                );
            }

            // Session'daki bilgileri güncelle
            $_SESSION['user']['ad_soyad'] = $ad_soyad;
            $_SESSION['user']['email'] = $email;

            mesaj_yonlendir("Profiliniz başarıyla güncellendi.", "success", "profil.php");
        } catch (Exception $e) {
            mesaj_yonlendir("Profil güncellenirken bir hata oluştu!", "danger", "profil.php");
        }
    } else {
        $_SESSION['hatalar'] = $hatalar;
        header('Location: profil.php');
        exit;
    }
}

// Kullanıcı bilgilerini getir
$user = $db->query(
    "SELECT * FROM users WHERE id = :id",
    [':id' => $_SESSION['user']['id']]
)->fetch();

// Hata mesajlarını al
$hatalar = $_SESSION['hatalar'] ?? [];
unset($_SESSION['hatalar']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex align-items-center">
                <i class="bi bi-person-circle fs-1 me-3 text-primary"></i>
                <h1 class="h3 mb-0">Profil Bilgileri</h1>
            </div>
        </div>
    </div>

    <?php if (!empty($hatalar)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                <?php foreach ($hatalar as $hata): ?>
                    <li><?php echo $hata; ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" class="form-control" value="<?php echo $user['username']; ?>" readonly>
                            <div class="form-text">Kullanıcı adı değiştirilemez.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" name="ad_soyad" value="<?php echo $user['ad_soyad']; ?>" required>
                            <div class="invalid-feedback">Ad Soyad alanı zorunludur.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-posta</label>
                            <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                            <div class="invalid-feedback">Geçerli bir e-posta adresi giriniz.</div>
                        </div>

                        <hr class="my-4">

                        <h5 class="card-title mb-3">Şifre Değiştir</h5>
                        <div class="mb-3">
                            <label class="form-label">Mevcut Şifre</label>
                            <input type="password" class="form-control" name="mevcut_sifre">
                            <div class="form-text">Şifrenizi değiştirmek istemiyorsanız boş bırakın.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" name="yeni_sifre" minlength="6">
                            <div class="invalid-feedback">Şifre en az 6 karakter olmalıdır.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" name="yeni_sifre_tekrar">
                            <div class="invalid-feedback">Şifreler eşleşmiyor.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" name="guncelle" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Değişiklikleri Kaydet
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Geri Dön
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-shield-lock me-2"></i>Güvenlik Bilgileri
                    </h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Rol</span>
                            <span class="badge bg-<?php echo $user['rol'] == 'admin' ? 'primary' : 'secondary'; ?>">
                                <?php echo $user['rol'] == 'admin' ? 'Admin' : 'Kullanıcı'; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Durum</span>
                            <span class="badge bg-<?php echo $user['aktif'] ? 'success' : 'danger'; ?>">
                                <?php echo $user['aktif'] ? 'Aktif' : 'Pasif'; ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span>Son Güncelleme</span>
                            <span class="text-muted">
                                <?php echo date('d.m.Y H:i', strtotime($user['updated_at'])); ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <h5 class="card-title mb-3">
                        <i class="bi bi-building me-2"></i>Erişim Verilen Şirketler
                    </h5>
                    <?php
                    $sirketler = $db->query(
                        "SELECT c.* 
                        FROM companies c 
                        INNER JOIN user_companies uc ON uc.company_id = c.id 
                        WHERE uc.user_id = :user_id 
                        ORDER BY c.unvan",
                        [':user_id' => $_SESSION['user']['id']]
                    )->fetchAll();
                    ?>
                    <?php if (!empty($sirketler)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($sirketler as $sirket): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <span><?php echo $sirket['unvan']; ?></span>
                                    <span class="badge bg-<?php echo $sirket['aktif'] ? 'success' : 'danger'; ?>">
                                        <?php echo $sirket['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted mb-0">Henüz hiçbir şirkete erişiminiz yok.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Form validasyon
    (function() {
        'use strict'
        var forms = document.querySelectorAll('.needs-validation')
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
    })()

    // Şifre eşleştirme kontrolü
    document.querySelector('input[name="yeni_sifre_tekrar"]').addEventListener('input', function(e) {
        var yeni_sifre = document.querySelector('input[name="yeni_sifre"]').value;
        if (this.value !== yeni_sifre) {
            this.setCustomValidity('Şifreler eşleşmiyor.');
        } else {
            this.setCustomValidity('');
        }
    });
</script>

<?php require_once 'templates/footer.php'; ?>