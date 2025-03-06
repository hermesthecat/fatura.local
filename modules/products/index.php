<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Product;

$title = 'Ürünler';
$product = new Product();
$products = $product->all();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Ürün Listesi</h5>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Yeni Ürün
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Ürün Kodu</th>
                        <th>Ürün Adı</th>
                        <th>Birim</th>
                        <th class="text-end">Birim Fiyat</th>
                        <th class="text-end">KDV %</th>
                        <th>Durum</th>
                        <th width="150">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= e($product['urun_kodu']) ?></td>
                        <td><?= e($product['urun_adi']) ?></td>
                        <td><?= e($product['birim']) ?></td>
                        <td class="text-end"><?= format_money($product['birim_fiyat']) ?></td>
                        <td class="text-end">%<?= $product['kdv_orani'] ?></td>
                        <td>
                            <span class="badge bg-<?= $product['durum'] === 'aktif' ? 'success' : 'danger' ?>">
                                <?= $product['durum'] === 'aktif' ? 'Aktif' : 'Pasif' ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-info" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="view.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-success" title="Görüntüle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger delete-product" 
                                    data-id="<?= $product['id'] ?>" 
                                    data-name="<?= e($product['urun_adi']) ?>"
                                    title="Sil">
                                <i class="fas fa-trash"></i>
                            </button>
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
    // DataTables
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json'
        },
        order: [[1, 'asc']], // Ürün adına göre sırala
        columnDefs: [
            { targets: [3, 4], className: 'text-end' } // Sayısal değerleri sağa yasla
        ]
    });
    
    // Ürün silme
    $('.delete-product').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: `"${name}" ürününü silmek istediğinize emin misiniz?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete.php?id=${id}`;
            }
        });
    });
});</script> 