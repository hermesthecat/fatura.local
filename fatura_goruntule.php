<?php
require_once 'templates/header.php';

// Fatura ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    hata("Geçersiz fatura ID!");
    header("Location: fatura_listele.php");
    exit;
}

$fatura_id = $_GET['id'];

// Fatura ve müşteri bilgilerini al
$db = Database::getInstance();
$sql = "SELECT i.*, c.* 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        WHERE i.id = :id";
$fatura = $db->query($sql, [':id' => $fatura_id])->fetch();

if (!$fatura) {
    hata("Fatura bulunamadı!");
    header("Location: fatura_listele.php");
    exit;
}

// Fatura kalemlerini al
$sql = "SELECT * FROM invoice_items WHERE invoice_id = :invoice_id ORDER BY id";
$kalemler = $db->query($sql, [':invoice_id' => $fatura_id])->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Fatura Detayı</h3>
        <div class="btn-group">
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Yazdır
            </button>
            <a href="fatura_duzenle.php?id=<?php echo $fatura_id; ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Düzenle
            </a>
            <a href="fatura_listele.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Geri
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5 class="mb-3">Fatura Bilgileri</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="150">Fatura No:</th>
                        <td><?php echo guvenlik($fatura['fatura_no']); ?></td>
                    </tr>
                    <tr>
                        <th>Fatura Tarihi:</th>
                        <td><?php echo formatTarih($fatura['fatura_tarihi']); ?></td>
                    </tr>
                    <tr>
                        <th>Vade Tarihi:</th>
                        <td><?php echo $fatura['vade_tarihi'] ? formatTarih($fatura['vade_tarihi']) : '-'; ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5 class="mb-3">Müşteri Bilgileri</h5>
                <table class="table table-sm">
                    <tr>
                        <th width="150">Firma Adı:</th>
                        <td><?php echo guvenlik($fatura['firma_adi']); ?></td>
                    </tr>
                    <tr>
                        <th>Vergi No:</th>
                        <td><?php echo guvenlik($fatura['vergi_no']); ?></td>
                    </tr>
                    <tr>
                        <th>Vergi Dairesi:</th>
                        <td><?php echo guvenlik($fatura['vergi_dairesi']); ?></td>
                    </tr>
                    <tr>
                        <th>Adres:</th>
                        <td><?php echo nl2br(guvenlik($fatura['adres'])); ?></td>
                    </tr>
                    <tr>
                        <th>Telefon:</th>
                        <td><?php echo guvenlik($fatura['telefon']); ?></td>
                    </tr>
                    <tr>
                        <th>E-posta:</th>
                        <td><?php echo guvenlik($fatura['email']); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <h5 class="mb-3">Fatura Kalemleri</h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Ürün/Hizmet</th>
                        <th width="100" class="text-center">Miktar</th>
                        <th width="150" class="text-end">Birim Fiyat</th>
                        <th width="150" class="text-end">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kalemler as $kalem): ?>
                        <tr>
                            <td><?php echo guvenlik($kalem['urun_adi']); ?></td>
                            <td class="text-center"><?php echo $kalem['miktar']; ?></td>
                            <td class="text-end"><?php echo formatPara($kalem['birim_fiyat']); ?></td>
                            <td class="text-end"><?php echo formatPara($kalem['toplam_fiyat']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-end">Ara Toplam:</th>
                        <td class="text-end"><?php echo formatPara($fatura['toplam_tutar']); ?></td>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">KDV (<?php echo $fatura['kdv_orani']; ?>%):</th>
                        <td class="text-end"><?php echo formatPara($fatura['kdv_tutari']); ?></td>
                    </tr>
                    <tr>
                        <th colspan="3" class="text-end">Genel Toplam:</th>
                        <td class="text-end"><strong><?php echo formatPara($fatura['genel_toplam']); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php if ($fatura['aciklama']): ?>
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-3">Açıklama</h5>
                    <div class="card">
                        <div class="card-body">
                            <?php echo nl2br(guvenlik($fatura['aciklama'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style media="print">
    .navbar, .btn-group, .footer {
        display: none !important;
    }
    .card {
        border: none !important;
    }
    .card-header {
        background: none !important;
        padding: 0 !important;
    }
    .card-body {
        padding: 0 !important;
    }
</style>

<?php require_once 'templates/footer.php'; ?> 