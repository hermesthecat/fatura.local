<?php
require_once 'templates/header.php';

// Oturum ve şirket kontrolü
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['company_id'])) {
    hata("Lütfen önce bir şirket seçin!");
    header('Location: index.php');
    exit;
}

// ID kontrolü
if (!isset($_GET['id'])) {
    header('Location: fatura_listele.php');
    exit;
}

$db = Database::getInstance();

// Fatura bilgilerini al
$fatura = $db->query("SELECT f.*, c.*, cur.sembol as para_birimi_sembol, cur.kod as para_birimi_kod 
    FROM invoices f 
    INNER JOIN customers c ON c.id = f.customer_id 
    INNER JOIN currencies cur ON cur.id = f.currency_id 
    WHERE f.id = :id AND f.company_id = :company_id",
    [':id' => $_GET['id'], ':company_id' => $_SESSION['company_id']])->fetch();

    var_dump("SQL: " . "SELECT f.*, c.*, cur.sembol as para_birimi_sembol, cur.kod as para_birimi_kod 
    FROM invoices f 
    INNER JOIN customers c ON c.id = f.customer_id 
    INNER JOIN currencies cur ON cur.id = f.currency_id 
    WHERE f.id = " . $_GET['id'] . " AND f.company_id = " . $_SESSION['company_id']);

if (!$fatura) {
    header('Location: fatura_listele.php');
    exit;
}

// Fatura kalemlerini al
$kalemler = $db->query("SELECT * FROM invoice_items WHERE invoice_id = :invoice_id",
    [':invoice_id' => $fatura['id']])->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Fatura Detayı</h1>
        </div>
        <div class="col text-end">
            <div class="btn-group">
                <a href="fatura_pdf.php?id=<?php echo $fatura['id']; ?>" class="btn btn-secondary" target="_blank">
                    <i class="bi bi-file-pdf"></i> PDF
                </a>
                <a href="fatura_duzenle.php?id=<?php echo $fatura['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Düzenle
                </a>
                <a href="fatura_listele.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Geri
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h5 class="card-title">Fatura Bilgileri</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th>Fatura No:</th>
                                    <td><?php echo $fatura['fatura_no']; ?></td>
                                </tr>
                                <tr>
                                    <th>Para Birimi:</th>
                                    <td><?php echo $fatura['para_birimi_kod']; ?> (<?php echo $fatura['para_birimi_sembol']; ?>)</td>
                                </tr>
                                <tr>
                                    <th>Fatura Tarihi:</th>
                                    <td><?php echo date('d.m.Y', strtotime($fatura['fatura_tarihi'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Vade Tarihi:</th>
                                    <td>
                                        <?php if ($fatura['vade_tarihi']): ?>
                                            <?php echo date('d.m.Y', strtotime($fatura['vade_tarihi'])); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5 class="card-title">Müşteri Bilgileri</h5>
                            <table class="table table-sm">
                                <tr>
                                    <th>Firma Adı:</th>
                                    <td><?php echo $fatura['firma_adi']; ?></td>
                                </tr>
                                <tr>
                                    <th>Vergi No:</th>
                                    <td><?php echo $fatura['vergi_no']; ?></td>
                                </tr>
                                <tr>
                                    <th>Vergi Dairesi:</th>
                                    <td><?php echo $fatura['vergi_dairesi']; ?></td>
                                </tr>
                                <tr>
                                    <th>Adres:</th>
                                    <td><?php echo $fatura['adres']; ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h5 class="card-title">Fatura Kalemleri</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Ürün/Hizmet</th>
                                    <th class="text-end" style="width: 100px;">Miktar</th>
                                    <th class="text-end" style="width: 150px;">Birim Fiyat</th>
                                    <th class="text-end" style="width: 150px;">Toplam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kalemler as $kalem): ?>
                                <tr>
                                    <td><?php echo $kalem['urun_adi']; ?></td>
                                    <td class="text-end"><?php echo $kalem['miktar']; ?></td>
                                    <td class="text-end">
                                        <?php echo number_format($kalem['birim_fiyat'], 2, ',', '.'); ?>
                                        <?php echo $fatura['para_birimi_sembol']; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php echo number_format($kalem['toplam_fiyat'], 2, ',', '.'); ?>
                                        <?php echo $fatura['para_birimi_sembol']; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Ara Toplam:</th>
                                    <td class="text-end">
                                        <?php echo number_format($fatura['toplam_tutar'], 2, ',', '.'); ?>
                                        <?php echo $fatura['para_birimi_sembol']; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">KDV (%<?php echo $fatura['kdv_orani']; ?>):</th>
                                    <td class="text-end">
                                        <?php echo number_format($fatura['kdv_tutari'], 2, ',', '.'); ?>
                                        <?php echo $fatura['para_birimi_sembol']; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Genel Toplam:</th>
                                    <td class="text-end">
                                        <strong>
                                            <?php echo number_format($fatura['genel_toplam'], 2, ',', '.'); ?>
                                            <?php echo $fatura['para_birimi_sembol']; ?>
                                        </strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <?php if ($fatura['aciklama']): ?>
                    <div class="mt-3">
                        <h5 class="card-title">Açıklama</h5>
                        <p class="card-text"><?php echo nl2br($fatura['aciklama']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-4">
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
                            <th>Adres:</th>
                            <td><?php echo COMPANY_ADRES; ?></td>
                        </tr>
                        <tr>
                            <th>Telefon:</th>
                            <td><?php echo COMPANY_TELEFON; ?></td>
                        </tr>
                        <tr>
                            <th>E-posta:</th>
                            <td><?php echo COMPANY_EMAIL; ?></td>
                        </tr>
                        <tr>
                            <th>Web:</th>
                            <td><?php echo COMPANY_WEB; ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?> 