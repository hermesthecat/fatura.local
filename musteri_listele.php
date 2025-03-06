<?php
require_once 'templates/header.php';

// Müşterileri al
$db = Database::getInstance();
$sql = "SELECT * FROM customers ORDER BY firma_adi";
$musteriler = $db->query($sql)->fetchAll();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Müşteri Listesi</h3>
        <a href="musteri_ekle.php" class="btn btn-primary">
            <i class="bi bi-plus"></i> Yeni Müşteri
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover" id="musteriTablosu">
                <thead>
                    <tr>
                        <th>Firma Adı</th>
                        <th>Vergi No</th>
                        <th>Vergi Dairesi</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($musteriler as $musteri): ?>
                        <tr>
                            <td><?php echo guvenlik($musteri['firma_adi']); ?></td>
                            <td><?php echo guvenlik($musteri['vergi_no']); ?></td>
                            <td><?php echo guvenlik($musteri['vergi_dairesi']); ?></td>
                            <td><?php echo guvenlik($musteri['telefon']); ?></td>
                            <td><?php echo guvenlik($musteri['email']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="musteri_duzenle.php?id=<?php echo $musteri['id']; ?>" 
                                       class="btn btn-warning" title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger musteri-sil" 
                                            data-id="<?php echo $musteri['id']; ?>" title="Sil">
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
    $('#musteriTablosu').DataTable({
        order: [[0, 'asc']], // Firma adına göre sırala
        columnDefs: [
            { orderable: false, targets: 5 } // İşlemler sütununu sıralamadan çıkar
        ]
    });

    // Müşteri silme işlemi
    $('.musteri-sil').click(function() {
        var musteriId = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu müşteri kalıcı olarak silinecek!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'musteri_sil.php?id=' + musteriId;
            }
        });
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 