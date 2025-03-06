<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Customer;

$title = 'Müşteriler';
$customer = new Customer();
$customers = $customer->all();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Müşteri Listesi</h5>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Yeni Müşteri
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Firma Adı</th>
                        <th>Vergi No</th>
                        <th>Vergi Dairesi</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th width="150">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= e($customer['firma_adi']) ?></td>
                        <td><?= e($customer['vergi_no']) ?></td>
                        <td><?= e($customer['vergi_dairesi']) ?></td>
                        <td><?= e($customer['telefon']) ?></td>
                        <td><?= e($customer['email']) ?></td>
                        <td>
                            <a href="edit.php?id=<?= $customer['id'] ?>" class="btn btn-sm btn-info" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="view.php?id=<?= $customer['id'] ?>" class="btn btn-sm btn-success" title="Görüntüle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-danger delete-customer" 
                                    data-id="<?= $customer['id'] ?>" 
                                    data-name="<?= e($customer['firma_adi']) ?>"
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

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // DataTables
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json'
        },
        order: [[0, 'asc']] // Firma adına göre sırala
    });
    
    // Müşteri silme
    $('.delete-customer').click(function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: `"${name}" isimli müşteriyi silmek istediğinize emin misiniz?`,
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