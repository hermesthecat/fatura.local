<?php
require_once __DIR__ . '/includes/functions.php';
session_start();

// Zaten giriş yapmışsa ana sayfaya yönlendir
if (check_auth()) {
    header('Location: /');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validasyon
    if (empty($username)) {
        $errors['username'] = 'Kullanıcı adı zorunludur';
    }

    if (empty($password)) {
        $errors['password'] = 'Şifre zorunludur';
    }

    if (empty($errors)) {
        // Veritabanı bağlantısı
        $db = \App\Database::getInstance();

        // Kullanıcıyı kontrol et
        $stmt = $db->query(
            "SELECT * FROM users WHERE username = ? AND aktif = 1 LIMIT 1",
            [$username]
        );

        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Oturum bilgilerini kaydet
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['ad_soyad'];
            $_SESSION['user_role'] = $user['rol'];

            // Ana sayfaya yönlendir
            header('Location: /');
            exit;
        } else {
            $errors['login'] = 'Kullanıcı adı veya şifre hatalı';
        }
    }
}

$title = 'Giriş';
?>
<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= set_page_title($title) ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">

    <style>
        .login-page {
            height: 100vh;
            display: flex;
            align-items: center;
            padding-top: 40px;
            padding-bottom: 40px;
            background-color: #f5f5f5;
        }

        .form-signin {
            width: 100%;
            max-width: 330px;
            padding: 15px;
            margin: auto;
        }

        .form-signin .form-floating:focus-within {
            z-index: 2;
        }

        .form-signin input[type="text"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }

        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
    </style>
</head>

<body class="login-page text-center">
    <main class="form-signin">
        <form method="post" action="">
            <i class="fas fa-file-invoice fa-3x mb-3"></i>
            <h1 class="h3 mb-3 fw-normal"><?= config('app.name') ?></h1>

            <?php if (isset($errors['login'])): ?>
                <?= display_error($errors['login']) ?>
            <?php endif; ?>

            <div class="form-floating">
                <input type="text" class="form-control <?= isset($errors['username']) ? 'is-invalid' : '' ?>"
                    id="username" name="username" placeholder="Kullanıcı Adı"
                    value="<?= e($_POST['username'] ?? '') ?>">
                <label for="username">Kullanıcı Adı</label>
                <?= display_errors($errors, 'username') ?>
            </div>

            <div class="form-floating">
                <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                    id="password" name="password" placeholder="Şifre">
                <label for="password">Şifre</label>
                <?= display_errors($errors, 'password') ?>
            </div>

            <button class="w-100 btn btn-lg btn-primary" type="submit">
                Giriş Yap
            </button>

            <p class="mt-5 mb-3 text-muted">
                &copy; <?= date('Y') ?> <?= config('app.name') ?>
            </p>
        </form>
    </main>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>