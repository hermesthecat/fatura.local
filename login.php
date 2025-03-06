<?php

/**
 * Giriş Sayfası
 * @author A. Kerem Gök
 */

// Session başlat
session_start();

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Oturum kontrolü
if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance();

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF kontrolü
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception('Geçersiz form gönderimi!');
        }

        // Kullanıcı bilgilerini al
        $username = $_POST['username'];
        $password = $_POST['password'];
        $remember = isset($_POST['remember']);

        // Kullanıcıyı kontrol et
        $user = $db->query(
            "SELECT * FROM users WHERE username = :username AND aktif = 1",
            [':username' => $username]
        )->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('Kullanıcı adı veya şifre hatalı!');
        }

        // Session'a kullanıcı bilgilerini kaydet
        $_SESSION['user'] = [
            'id' => $user['id'],
            'ad_soyad' => $user['ad_soyad'],
            'username' => $user['username'],
            'email' => $user['email'],
            'admin' => $user['rol'] === 'admin'
        ];

        // Son giriş tarihini güncelle
        $db->query(
            "UPDATE users SET son_giris = NOW() WHERE id = :id",
            [':id' => $user['id']]
        );

        // Beni hatırla
        if ($remember) {
            // Token oluştur
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

            // Token'ı veritabanına kaydet
            $db->query(
                "INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)",
                [
                    ':user_id' => $user['id'],
                    ':token' => $token,
                    ':expires_at' => $expires
                ]
            );

            // Cookie oluştur (30 gün)
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', true, true);
        }

        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Sayfa başlığı
$sayfa_baslik = "Giriş Yap";
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sayfa_baslik; ?> - Fatura Yönetim Sistemi</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }

        .card-body {
            padding: 30px;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .btn-primary {
            width: 100%;
            padding: 12px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <?php echo mesaj_goster(); ?>

        <div class="text-center mb-4">
            <h1 class="h3">Fatura Yönetim Sistemi</h1>
            <p class="text-muted">Lütfen giriş yapın</p>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Giriş Yap</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                    <div class="form-floating">
                        <input type="text" class="form-control" id="username" name="username"
                            placeholder="Kullanıcı Adı" required autofocus>
                        <label for="username">Kullanıcı Adı</label>
                    </div>

                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password"
                            placeholder="Şifre" required>
                        <label for="password">Şifre</label>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Beni Hatırla</label>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right"></i> Giriş Yap
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>