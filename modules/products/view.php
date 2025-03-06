<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Product;

$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    set_flash_message('error', 'Geçersiz ürün ID\'si');
    redirect('/modules/products');
}

$product = new Product();
$productData = $product->find($product_id);

if (!$productData) {
    set_flash_message('error', 'Ürün bulunamadı');
    redirect('/modules/products');
}

$title = 'Ürün Detayı';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?= e($title) ?></h1>
        <div>
            <a href="/modules/products/edit.php?id=<?= $product_id ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Düzenle
            </a>
            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $product_id ?>)">
                <i class="fas fa-trash"></i> Sil
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Ürün Bilgileri</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Ürün Kodu:</th>
                            <td><?= e($productData['urun_kodu']) ?></td>
                        </tr>
                        <tr>
                            <th>Ürün Adı:</th>
                            <td><?= e($productData['urun_adi']) ?></td>
                        </tr>
                        <tr>
                            <th>Birim:</th>
                            <td><?= e($productData['birim']) ?></td>
                        </tr>
                        <tr>
                            <th>Birim Fiyat:</th>
                            <td><?= format_money($productData['birim_fiyat']) ?></td>
                        </tr>
                        <tr>
                            <th>KDV Oranı:</th>
                            <td>%<?= $productData['kdv_orani'] ?></td>
                        </tr>
                        <tr>
                            <th>Durum:</th>
                            <td>
                                <span class="badge bg-<?= $productData['durum'] === 'aktif' ? 'success' : 'danger' ?>">
                                    <?= $productData['durum'] === 'aktif' ? 'Aktif' : 'Pasif' ?>
                                </span>
                            </td>
                        </tr>
                        <?php if ($productData['aciklama']): ?>
                        <tr>
                            <th>Açıklama:</th>
                            <td><?= nl2br(e($productData['aciklama'])) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Kayıt Tarihi:</th>
                            <td><?= format_datetime($productData['created_at']) ?></td>
                        </tr>
                        <tr>
                            <th>Son Güncelleme:</th>
                            <td><?= format_datetime($productData['updated_at']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Fatura Geçmişi</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Fatura No</th>
                                    <th>Müşteri</th>
                                    <th>Tarih</th>
                                    <th class="text-end">Miktar</th>
                                    <th class="text-end">Tutar</th>
                                    <th width="100">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT i.id, i.fatura_no, i.fatura_tarihi, c.firma_adi, 
                                              ii.miktar, ii.toplam_tutar
                                       FROM invoice_items ii
                                       JOIN invoices i ON i.id = ii.invoice_id
                                       JOIN customers c ON c.id = i.customer_id
                                       WHERE ii.product_id = ?
                                       ORDER BY i.fatura_tarihi DESC
                                       LIMIT 10";
                                
                                $stmt = $product->db->prepare($sql);
                                $stmt->execute([$product_id]);
                                $invoices = $stmt->fetchAll();
                                
                                if (empty($invoices)):
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center">Henüz fatura kaydı yok</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($invoices as $invoice): ?>
                                <tr>
                                    <td><?= e($invoice['fatura_no']) ?></td>
                                    <td><?= e($invoice['firma_adi']) ?></td>
                                    <td><?= format_date($invoice['fatura_tarihi']) ?></td>
                                    <td class="text-end">
                                        <?= number_format($invoice['miktar'], 2, ',', '.') ?> <?= e($productData['birim']) ?>
                                    </td>
                                    <td class="text-end"><?= format_money($invoice['toplam_tutar']) ?></td>
                                    <td>
                                        <a href="/modules/invoices/view.php?id=<?= $invoice['id'] ?>" 
                                           class="btn btn-sm btn-info" title="Faturayı Görüntüle">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id) {
    Swal.fire({
        title: 'Emin misiniz?',
        text: "Bu ürün kalıcı olarak silinecektir!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Evet, sil!',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/modules/products/delete.php?id=${id}`;
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?> 