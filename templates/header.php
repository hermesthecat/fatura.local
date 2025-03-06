<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Giriş yapılmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    // Remember token kontrolü
    if (isset($_COOKIE['remember_token'])) {
        $db = Database::getInstance();
        $token = $db->query("SELECT * FROM remember_tokens 
                            WHERE token = :token AND expires_at > NOW()", 
            [':token' => $_COOKIE['remember_token']])->fetch();

        if ($token) {
            $user = $db->query("SELECT * FROM users WHERE id = :id", 
                [':id' => $token['user_id']])->fetch();

            if ($user) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'ad_soyad' => $user['ad_soyad'],
                    'email' => $user['email'],
                    'rol' => $user['rol']
                ];

                if ($user['rol'] === 'admin') {
                    $_SESSION['admin'] = true;
                }
            }
        }
    }

    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fatura Yönetim Sistemi</title>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .navbar { margin-bottom: 20px; }
        .table th { white-space: nowrap; }
        .loading { display: none; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Fatura Sistemi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (isset($_SESSION['user'])): ?>
                    <ul class="navbar-nav me-auto">
                        <?php if (isset($_SESSION['company_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="fatura_olustur.php">
                                <i class="bi bi-plus-circle"></i> Yeni Fatura
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="fatura_listele.php">
                                <i class="bi bi-list"></i> Faturalar
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="musteri_listele.php">
                                <i class="bi bi-people"></i> Müşteriler
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['admin'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="sirketler.php">
                                <i class="bi bi-building"></i> Şirketler
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin.php">
                                <i class="bi bi-gear"></i> Ayarlar
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <?php if (isset($_SESSION['user_companies'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-building"></i> 
                                <?php echo isset($_SESSION['company_id']) ? $_SESSION['company_unvan'] : 'Şirket Seç'; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php foreach ($_SESSION['user_companies'] as $company): ?>
                                <li>
                                    <a class="dropdown-item <?php echo isset($_SESSION['company_id']) && $_SESSION['company_id'] == $company['id'] ? 'active' : ''; ?>" 
                                       href="sirket_sec.php?id=<?php echo $company['id']; ?>">
                                        <?php echo $company['unvan']; ?>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i> <?php echo $_SESSION['username']; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="profil.php">
                                        <i class="bi bi-person"></i> Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="bi bi-box-arrow-right"></i> Çıkış
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container">
        <?php echo mesaj_goster(); ?> 