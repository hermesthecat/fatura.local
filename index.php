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
    $sirketler = $db->query("SELECT c.* 
        FROM companies c 
        INNER JOIN user_companies uc ON uc.company_id = c.id 
        WHERE uc.user_id = :user_id AND c.aktif = 1 
        ORDER BY c.unvan", 
        [':user_id' => $_SESSION['user']['id']])->fetchAll();
    ?>
    
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col">
                <h1 class="h3">Hoş Geldiniz, <?php echo $_SESSION['user']['ad_soyad']; ?></h1>
                <p>Lütfen çalışmak istediğiniz şirketi seçin.</p>
            </div>
        </div>

        <div class="row">
            <?php foreach ($sirketler as $sirket): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <?php if ($sirket['logo']): ?>
                    <img src="<?php echo $sirket['logo']; ?>" class="card-img-top p-3" alt="<?php echo $sirket['unvan']; ?>" style="max-height: 150px; object-fit: contain;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $sirket['unvan']; ?></h5>
                        <p class="card-text">
                            <small class="text-muted">
                                <?php echo $sirket['vergi_dairesi']; ?> / <?php echo $sirket['vergi_no']; ?>
                            </small>
                        </p>
                        <a href="sirket_sec.php?id=<?php echo $sirket['id']; ?>" class="btn btn-primary">
                            <i class="bi bi-box-arrow-in-right"></i> Şirketi Seç
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if ($_SESSION['user']['admin']): ?>
            <div class="col-md-4 mb-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <a href="sirket_ekle.php" class="btn btn-outline-primary btn-lg">
                            <i class="bi bi-plus-circle"></i><br>
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
    $fatura_sayisi = $db->query("SELECT COUNT(*) as sayi FROM invoices WHERE company_id = :company_id",
        [':company_id' => $_SESSION['company_id']])->fetch()['sayi'];
    
    $musteri_sayisi = $db->query("SELECT COUNT(*) as sayi FROM customers WHERE company_id = :company_id",
        [':company_id' => $_SESSION['company_id']])->fetch()['sayi'];
    
    $son_faturalar = $db->query("SELECT f.*, c.firma_adi as musteri_adi 
        FROM invoices f 
        INNER JOIN customers c ON c.id = f.customer_id 
        WHERE f.company_id = :company_id 
        ORDER BY f.fatura_tarihi DESC 
        LIMIT 5", 
        [':company_id' => $_SESSION['company_id']])->fetchAll();
    ?>
    
    <div class="container-fluid">
        <div class="row mb-3">
            <div class="col">
                <h1 class="h3">Hoş Geldiniz, <?php echo $_SESSION['user']['ad_soyad']; ?></h1>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Fatura İstatistikleri</h5>
                        <div class="row text-center">
                            <div class="col">
                                <h2 class="h1"><?php echo $fatura_sayisi; ?></h2>
                                <p class="text-muted">Toplam Fatura</p>
                            </div>
                            <div class="col">
                                <h2 class="h1"><?php echo $musteri_sayisi; ?></h2>
                                <p class="text-muted">Toplam Müşteri</p>
                            </div>
                        </div>
                        <div class="text-end">
                            <a href="fatura_listele.php" class="btn btn-primary">
                                <i class="bi bi-list"></i> Faturaları Listele
                            </a>
                            <a href="fatura_olustur.php" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> Yeni Fatura
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Son Faturalar</h5>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
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
                                            <a href="fatura_goruntule.php?id=<?php echo $fatura['id']; ?>">
                                                <?php echo $fatura['fatura_no']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo $fatura['musteri_adi']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($fatura['fatura_tarihi'])); ?></td>
                                        <td class="text-end">
                                            <?php echo number_format($fatura['genel_toplam'], 2, ',', '.'); ?> <?php echo PARA_BIRIMI; ?>
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

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Hızlı Menü</h5>
                        <div class="list-group">
                            <a href="musteri_listele.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-people"></i> Müşteriler
                            </a>
                            <a href="musteri_ekle.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-person-plus"></i> Yeni Müşteri
                            </a>
                            <?php if ($_SESSION['user']['admin']): ?>
                            <a href="admin.php" class="list-group-item list-group-item-action">
                                <i class="bi bi-gear"></i> Ayarlar
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Şirket Bilgileri</h5>
                        <table class="table table-sm">
                            <tr>
                                <th>Ünvan:</th>
                                <td><?php echo COMPANY_UNVAN; ?></td>
                            </tr>
                            <tr>
                                <th>Vergi No:</th>
                                <td><?php echo COMPANY_VERGI_NO; ?></td>
                            </tr>
                            <tr>
                                <th>Vergi Dairesi:</th>
                                <td><?php echo COMPANY_VERGI_DAIRESI; ?></td>
                            </tr>
                            <tr>
                                <th>Telefon:</th>
                                <td><?php echo COMPANY_TELEFON; ?></td>
                            </tr>
                            <tr>
                                <th>E-posta:</th>
                                <td><?php echo COMPANY_EMAIL; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

require_once 'templates/footer.php';
?> 