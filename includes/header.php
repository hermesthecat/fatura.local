<?php
session_start();
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= set_page_title($title ?? 'Ana Sayfa') ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/"><?= config('app.name') ?></a>
            
            <?php if (check_auth()): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu('/') ?>" href="/">
                            <i class="fas fa-home"></i> Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu('/invoices') ?>" href="/modules/invoices">
                            <i class="fas fa-file-invoice"></i> Faturalar
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu('/customers') ?>" href="/modules/customers">
                            <i class="fas fa-users"></i> Müşteriler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu('/products') ?>" href="/modules/products">
                            <i class="fas fa-box"></i> Ürünler
                        </a>
                    </li>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= is_active_menu('/settings') ?>" href="/settings.php">
                            <i class="fas fa-cog"></i> Ayarlar
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= e($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="/profile.php">
                                    <i class="fas fa-user-cog"></i> Profil
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Çıkış
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="container py-4">
        <?php
        if (isset($_SESSION['success'])) {
            echo display_success($_SESSION['success']);
            unset($_SESSION['success']);
        }
        
        if (isset($_SESSION['error'])) {
            echo display_error($_SESSION['error']);
            unset($_SESSION['error']);
        }
        ?>
    </main>
</body>
</html> 