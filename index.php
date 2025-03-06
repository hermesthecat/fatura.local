<?php

/**
 * Ana Sayfa
 * @author A. Kerem Gök
 */

require_once 'templates/header.php';

// Oturum kontrolü
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Şirket seçili değilse şirket listesini göster
if (!isset($_SESSION['company_id'])) {
    // Kullanıcının şirketlerini al
    $sirketler = $db->query(
        "SELECT c.* 
        FROM companies c 
        INNER JOIN user_companies uc ON uc.company_id = c.id 
        WHERE uc.user_id = :user_id AND c.aktif = 1 
        ORDER BY c.unvan",
        [':user_id' => $_SESSION['user']['id']]
    )->fetchAll();
?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-circle fs-1 me-3 text-primary"></i>
                    <div>
                        <h1 class="h3 mb-1">Hoş Geldiniz, <?php echo $_SESSION['user']['ad_soyad']; ?></h1>
                        <p class="text-muted mb-0">Lütfen çalışmak istediğiniz şirketi seçin.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($sirketler as $sirket): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 hover-shadow">
                        <?php if ($sirket['logo']): ?>
                            <div class="card-img-top bg-light p-4 text-center">
                                <img src="<?php echo $sirket['logo']; ?>" alt="<?php echo $sirket['unvan']; ?>" style="max-height: 120px; object-fit: contain;">
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title fw-bold mb-3"><?php echo $sirket['unvan']; ?></h5>
                            <p class="card-text d-flex align-items-center text-muted mb-4">
                                <i class="bi bi-building me-2"></i>
                                <?php echo $sirket['vergi_dairesi']; ?> / <?php echo $sirket['vergi_no']; ?>
                            </p>
                            <a href="sirket_sec.php?id=<?php echo $sirket['id']; ?>" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Şirketi Seç
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($_SESSION['user']['rol'] == 'admin'): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm border-0 bg-light hover-shadow">
                        <div class="card-body d-flex align-items-center justify-content-center">
                            <a href="sirket_ekle.php" class="btn btn-outline-primary btn-lg d-flex flex-column align-items-center p-4">
                                <i class="bi bi-plus-circle fs-1 mb-2"></i>
                                Yeni Şirket Ekle
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php
} else {
    // Şirket seçili ise özet bilgileri göster
    $fatura_sayisi = $db->query(
        "SELECT COUNT(*) as sayi FROM invoices WHERE company_id = :company_id",
        [':company_id' => $_SESSION['company_id']]
    )->fetch()['sayi'];

    $musteri_sayisi = $db->query(
        "SELECT COUNT(*) as sayi FROM customers WHERE company_id = :company_id",
        [':company_id' => $_SESSION['company_id']]
    )->fetch()['sayi'];

    $son_faturalar = $db->query(
        "SELECT f.*, c.firma_adi as musteri_adi, cur.sembol as para_birimi_sembol 
        FROM invoices f 
        INNER JOIN customers c ON c.id = f.customer_id 
        LEFT JOIN currencies cur ON cur.id = f.currency_id 
        WHERE f.company_id = :company_id 
        ORDER BY f.fatura_tarihi DESC 
        LIMIT 5",
        [':company_id' => $_SESSION['company_id']]
    )->fetchAll();
?>

    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex align-items-center">
                    <i class="bi bi-person-circle fs-1 me-3 text-primary"></i>
                    <h1 class="h3 mb-0">Hoş Geldiniz, <?php echo $_SESSION['user']['ad_soyad']; ?></h1>
                </div>
            </div>
        </div>
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-4">
                            <i class="bi bi-graph-up me-2 text-primary"></i>
                            Fatura İstatistikleri
                        </h5>
                        <div class="row text-center g-4">
                            <div class="col-6">
                                <div class="p-4 bg-light rounded-3">
                                    <h2 class="display-4 fw-bold text-primary mb-0"><?php echo $fatura_sayisi; ?></h2>
                                    <p class="text-muted mb-0">Toplam Fatura</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-4 bg-light rounded-3">
                                    <h2 class="display-4 fw-bold text-primary mb-0"><?php echo $musteri_sayisi; ?></h2>
                                    <p class="text-muted mb-0">Toplam Müşteri</p>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-4">
                            <a href="fatura_listele.php" class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-center">
                                <i class="bi bi-list me-2"></i> Faturaları Listele
                            </a>
                            <a href="fatura_olustur.php" class="btn btn-success flex-grow-1 d-flex align-items-center justify-content-center">
                                <i class="bi bi-plus-circle me-2"></i> Yeni Fatura
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-4">
                            <i class="bi bi-receipt me-2 text-primary"></i>
                            Son Faturalar
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Fatura No</th>
                                        <th>Müşteri</th>
                                        <th>Tarih</th>
                                        <th class="text-end">Tutar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($son_faturalar as $fatura): ?>
                                        <tr>
                                            <td>
                                                <a href="fatura_goruntule.php?id=<?php echo $fatura['id']; ?>" class="text-decoration-none">
                                                    <?php echo $fatura['fatura_no']; ?>
                                                </a>
                                            </td>
                                            <td><?php echo $fatura['musteri_adi']; ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($fatura['fatura_tarihi'])); ?></td>
                                            <td class="text-end fw-bold">
                                                <?php echo number_format($fatura['genel_toplam'], 2, ',', '.'); ?> <?php echo $fatura['para_birimi_sembol'] ?? '₺'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-4">
                            <i class="bi bi-lightning me-2 text-primary"></i>
                            Hızlı Menü
                        </h5>
                        <div class="d-grid gap-2">
                            <a href="musteri_listele.php" class="btn btn-outline-primary d-flex align-items-center">
                                <i class="bi bi-people me-2"></i> Müşteriler
                            </a>
                            <a href="musteri_ekle.php" class="btn btn-outline-success d-flex align-items-center">
                                <i class="bi bi-person-plus me-2"></i> Yeni Müşteri
                            </a>
                            <?php if ($_SESSION['user']['rol'] == 'admin'): ?>
                                <a href="admin.php" class="btn btn-outline-dark d-flex align-items-center">
                                    <i class="bi bi-gear me-2"></i> Ayarlar
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="card-title d-flex align-items-center mb-4">
                            <i class="bi bi-building me-2 text-primary"></i>
                            Şirket Bilgileri
                        </h5>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Ünvan</span>
                                <span class="fw-bold"><?php echo COMPANY_UNVAN; ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Vergi No</span>
                                <span class="fw-bold"><?php echo COMPANY_VERGI_NO; ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Vergi Dairesi</span>
                                <span class="fw-bold"><?php echo COMPANY_VERGI_DAIRESI; ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">Telefon</span>
                                <span class="fw-bold"><?php echo COMPANY_TELEFON; ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                <span class="text-muted">E-posta</span>
                                <span class="fw-bold"><?php echo COMPANY_EMAIL; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}

require_once 'templates/footer.php';
?>