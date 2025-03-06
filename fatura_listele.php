<?php
require_once 'templates/header.php';

// Faturaları al
$db = Database::getInstance();
$sql = "SELECT i.*, c.firma_adi, c.vergi_no 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        ORDER BY i.created_at DESC";
$faturalar = $db->query($sql)->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Fatura Listesi</h3>
        <a href="fatura_olustur.php" class="btn btn-primary">
            <i class="bi bi-plus"></i> Yeni Fatura
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="faturaTablosu">
                <thead>
                    <tr>
                        <th>Fatura No</th>
                        <th>Müşteri</th>
                        <th>Fatura Tarihi</th>
                        <th>Vade Tarihi</th>
                        <th>Toplam Tutar</th>
                        <th>KDV</th>
                        <th>Genel Toplam</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($faturalar as $fatura): ?>
                        <tr>
                            <td><?php echo guvenlik($fatura['fatura_no']); ?></td>
                            <td>
                                <?php echo guvenlik($fatura['firma_adi']); ?><br>
                                <small class="text-muted">VN: <?php echo guvenlik($fatura['vergi_no']); ?></small>
                            </td>
                            <td><?php echo formatTarih($fatura['fatura_tarihi']); ?></td>
                            <td><?php echo $fatura['vade_tarihi'] ? formatTarih($fatura['vade_tarihi']) : '-'; ?></td>
                            <td class="text-end"><?php echo formatPara($fatura['toplam_tutar']); ?></td>
                            <td class="text-end"><?php echo formatPara($fatura['kdv_tutari']); ?></td>
                            <td class="text-end"><?php echo formatPara($fatura['genel_toplam']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="fatura_goruntule.php?id=<?php echo $fatura['id']; ?>" 
                                       class="btn btn-info" title="Görüntüle">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="fatura_duzenle.php?id=<?php echo $fatura['id']; ?>" 
                                       class="btn btn-warning" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger fatura-sil" 
                                            data-id="<?php echo $fatura['id']; ?>" title="Sil">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // DataTables başlat
    $('#faturaTablosu').DataTable({
        order: [[2, 'desc']], // Fatura tarihine göre sırala
        columnDefs: [
            { orderable: false, targets: 7 } // İşlemler sütununu sıralamadan çıkar
        ]
    });

    // Fatura silme işlemi
    $('.fatura-sil').click(function() {
        var faturaId = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu fatura kalıcı olarak silinecek!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'fatura_sil.php?id=' + faturaId;
            }
        });
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 