<?php

/**
 * Admin Paneli
 * @author A. Kerem Gök
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Oturum kontrolü
if (!isset($_SESSION['user']['rol']) || $_SESSION['user']['rol'] != 'admin') {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// İşlem mesajları
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Kullanıcı silme işlemi
if (isset($_POST['sil']) && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // Kendini silmeye çalışıyorsa engelle
    if ($user_id == $_SESSION['user']['id']) {
        $_SESSION['error'] = "Kendinizi silemezsiniz!";
    } else {
        try {
            // Önce user_companies tablosundan ilişkileri sil
            $db->query(
                "DELETE FROM user_companies WHERE user_id = :user_id",
                [':user_id' => $user_id]
            );

            // Sonra users tablosundan kullanıcıyı sil
            $db->query(
                "DELETE FROM users WHERE id = :user_id",
                [':user_id' => $user_id]
            );

            $_SESSION['success'] = "Kullanıcı başarıyla silindi.";
        } catch (Exception $e) {
            $_SESSION['error'] = "Kullanıcı silinirken bir hata oluştu!";
        }
    }
    header('Location: admin.php');
    exit;
}

// Kullanıcı ekleme işlemi
if (isset($_POST['ekle'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $ad_soyad = trim($_POST['ad_soyad']);
    $email = trim($_POST['email']);
    $rol = isset($_POST['rol']) ? $_POST['rol'] : 'user';
    $aktif = isset($_POST['aktif']) ? $_POST['aktif'] : 1;

    // Basit validasyon
    if (empty($username) || empty($password) || empty($ad_soyad) || empty($email)) {
        $_SESSION['error'] = "Tüm alanları doldurun!";
    } else {
        try {
            // Kullanıcı adı kontrolü
            $exists = $db->query(
                "SELECT id FROM users WHERE username = :username",
                [':username' => $username]
            )->fetch();

            if ($exists) {
                $_SESSION['error'] = "Bu kullanıcı adı zaten kullanılıyor!";
            } else {
                // Yeni kullanıcı ekle
                $db->query(
                    "INSERT INTO users (username, password, ad_soyad, email, rol, aktif) 
                    VALUES (:username, :password, :ad_soyad, :email, :rol, :aktif)",
                    [
                        ':username' => $username,
                        ':password' => password_hash($password, PASSWORD_DEFAULT),
                        ':ad_soyad' => $ad_soyad,
                        ':email' => $email,
                        ':rol' => $rol,
                        ':aktif' => $aktif
                    ]
                );

                $_SESSION['success'] = "Kullanıcı başarıyla eklendi.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Kullanıcı eklenirken bir hata oluştu!";
        }
    }
    header('Location: admin.php');
    exit;
}

// Kullanıcı düzenleme işlemi
if (isset($_POST['duzenle'])) {
    $user_id = $_POST['user_id'];
    $ad_soyad = trim($_POST['ad_soyad']);
    $email = trim($_POST['email']);
    $rol = isset($_POST['rol']) ? $_POST['rol'] : 'user';
    $password = trim($_POST['password']);

    try {
        if (!empty($password)) {
            // Şifre değişikliği varsa
            $db->query(
                "UPDATE users SET 
                ad_soyad = :ad_soyad,
                email = :email,
                rol = :rol,
                aktif = :aktif,
                password = :password
                WHERE id = :id",
                [
                    ':ad_soyad' => $ad_soyad,
                    ':email' => $email,
                    ':rol' => $rol,
                    ':aktif' => $aktif,
                    ':password' => password_hash($password, PASSWORD_DEFAULT),
                    ':id' => $user_id
                ]
            );
        } else {
            // Şifre değişikliği yoksa
            $db->query(
                "UPDATE users SET 
                ad_soyad = :ad_soyad,
                email = :email,
                rol = :rol,
                aktif = :aktif
                WHERE id = :id",
                [
                    ':ad_soyad' => $ad_soyad,
                    ':email' => $email,
                    ':rol' => $rol,
                    ':aktif' => $aktif,
                    ':id' => $user_id
                ]
            );
        }

        $_SESSION['success'] = "Kullanıcı başarıyla güncellendi.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Kullanıcı güncellenirken bir hata oluştu!";
    }
    header('Location: admin.php');
    exit;
}

// Tüm kullanıcıları getir
$users = $db->query("SELECT * FROM users ORDER BY ad_soyad")->fetchAll();
?>
<?php require_once 'templates/header.php'; ?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <div class="d-flex align-items-center">
                <i class="bi bi-gear-fill fs-1 me-3 text-primary"></i>
                <h1 class="h3 mb-0">Kullanıcı Yönetimi</h1>
            </div>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#yeniKullaniciModal">
                <i class="bi bi-person-plus"></i> Kullanıcı Ekle
            </button>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Ad Soyad</th>
                            <th>Kullanıcı Adı</th>
                            <th>E-posta</th>
                            <th>Rol</th>
                            <th>Durum</th>
                            <th class="text-end">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="fw-bold"><?php echo $user['ad_soyad']; ?></td>
                                <td><?php echo $user['username']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td>
                                    <?php if ($user['rol'] == 'admin'): ?>
                                        <span class="badge bg-primary">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Kullanıcı</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['aktif'] == 1): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal"
                                        data-bs-target="#duzenleModal<?php echo $user['id']; ?>">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user']['id']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#silModal<?php echo $user['id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Düzenleme Modal -->
                            <div class="modal fade" id="duzenleModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Kullanıcı Düzenle</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Kullanıcı Adı</label>
                                                    <input type="text" class="form-control" value="<?php echo $user['username']; ?>" readonly>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Ad Soyad</label>
                                                    <input type="text" class="form-control" name="ad_soyad" value="<?php echo $user['ad_soyad']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">E-posta</label>
                                                    <input type="email" class="form-control" name="email" value="<?php echo $user['email']; ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Yeni Şifre</label>
                                                    <input type="password" class="form-control" name="password" placeholder="Değiştirmek için doldurun">
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" name="rol" value="admin" <?php echo $user['rol'] == 'admin' ? 'checked' : ''; ?>>
                                                        <label class="form-check-label">Admin Yetkisi</label>
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" name="aktif" value="1" <?php echo $user['aktif'] == 1 ? 'checked' : ''; ?>>
                                                        <label class="form-check-label">Aktiflik</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                <button type="submit" name="duzenle" class="btn btn-primary">Kaydet</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Silme Modal -->
                            <div class="modal fade" id="silModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Kullanıcı Sil</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="mb-0">
                                                    <strong><?php echo $user['ad_soyad']; ?></strong> kullanıcısını silmek istediğinize emin misiniz?
                                                </p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                                <button type="submit" name="sil" class="btn btn-danger">Sil</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Yeni Kullanıcı Modal -->
<div class="modal fade" id="yeniKullaniciModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kullanıcı</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" name="ad_soyad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">E-posta</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="rol" value="admin">
                            <label class="form-check-label">Admin Yetkisi</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="aktif" value="1">
                            <label class="form-check-label">Aktiflik</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="ekle" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>