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

$db = Database::getInstance();

// Faturaları listele
$faturalar = $db->query("SELECT f.*, c.firma_adi as musteri_adi, cur.sembol as para_birimi_sembol 
    FROM invoices f 
    INNER JOIN customers c ON c.id = f.customer_id 
    INNER JOIN currencies cur ON cur.id = f.currency_id
    WHERE f.company_id = :company_id 
    ORDER BY f.fatura_tarihi DESC", 
    [':company_id' => $_SESSION['company_id']])->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Faturalar</h1>
        </div>
        <div class="col text-end">
            <a href="fatura_olustur.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Fatura Oluştur
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="faturaTablosu">
                    <thead>
                        <tr>
                            <th>Fatura No</th>
                            <th>Müşteri</th>
                            <th>Fatura Tarihi</th>
                            <th>Vade Tarihi</th>
                            <th class="text-end">Toplam</th>
                            <th class="text-end">KDV</th>
                            <th class="text-end">Genel Toplam</th>
                            <th style="width: 100px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($faturalar as $fatura): ?>
                        <tr>
                            <td><?php echo $fatura['fatura_no']; ?></td>
                            <td><?php echo $fatura['musteri_adi']; ?></td>
                            <td><?php echo date('d.m.Y', strtotime($fatura['fatura_tarihi'])); ?></td>
                            <td>
                                <?php if ($fatura['vade_tarihi']): ?>
                                    <?php echo date('d.m.Y', strtotime($fatura['vade_tarihi'])); ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format($fatura['toplam_tutar'], 2, ',', '.'); ?> <?php echo $fatura['para_birimi_sembol']; ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format($fatura['kdv_tutari'], 2, ',', '.'); ?> <?php echo $fatura['para_birimi_sembol']; ?>
                            </td>
                            <td class="text-end">
                                <?php echo number_format($fatura['genel_toplam'], 2, ',', '.'); ?> <?php echo $fatura['para_birimi_sembol']; ?>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="fatura_goruntule.php?id=<?php echo $fatura['id']; ?>" 
                                       class="btn btn-sm btn-info" title="Görüntüle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="fatura_pdf.php?id=<?php echo $fatura['id']; ?>" 
                                       class="btn btn-sm btn-secondary" title="PDF" target="_blank">
                                        <i class="bi bi-file-pdf"></i>
                                    </a>
                                    <a href="fatura_duzenle.php?id=<?php echo $fatura['id']; ?>" 
                                       class="btn btn-sm btn-primary" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#faturaTablosu').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json'
        },
        order: [[2, 'desc']], // Fatura tarihine göre sırala
        pageLength: 25
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 