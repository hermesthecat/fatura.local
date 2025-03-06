<?php
require_once __DIR__ . '/../../includes/functions.php';
require_auth();

use App\Invoice;

$title = 'Faturalar';
$invoice = new Invoice();

// Faturaları müşteri bilgileriyle birlikte getir
$sql = "SELECT i.*, c.firma_adi 
        FROM invoices i 
        JOIN customers c ON c.id = i.customer_id 
        ORDER BY i.created_at DESC";

$stmt = $invoice->db->query($sql);
$invoices = $stmt->fetchAll();

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Fatura Listesi</h5>
        <a href="create.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Yeni Fatura
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Fatura No</th>
                        <th>Müşteri</th>
                        <th>Fatura Tarihi</th>
                        <th>Vade Tarihi</th>
                        <th>Ara Toplam</th>
                        <th>KDV</th>
                        <th>Genel Toplam</th>
                        <th>Durum</th>
                        <th width="150">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td><?= e($invoice['fatura_no']) ?></td>
                        <td><?= e($invoice['firma_adi']) ?></td>
                        <td><?= format_date($invoice['fatura_tarihi']) ?></td>
                        <td><?= $invoice['vade_tarihi'] ? format_date($invoice['vade_tarihi']) : '-' ?></td>
                        <td><?= format_money($invoice['toplam_tutar']) ?></td>
                        <td><?= format_money($invoice['kdv_toplam']) ?></td>
                        <td><?= format_money($invoice['genel_toplam']) ?></td>
                        <td>
                            <span class="badge bg-<?= $invoice['durum'] === 'onaylandı' ? 'success' : ($invoice['durum'] === 'iptal' ? 'danger' : 'warning') ?>">
                                <?= get_invoice_status($invoice['durum']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-info" title="Düzenle">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="view.php?id=<?= $invoice['id'] ?>" class="btn btn-sm btn-success" title="Görüntüle">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if ($invoice['durum'] !== 'iptal'): ?>
                            <button type="button" class="btn btn-sm btn-danger delete-invoice" 
                                    data-id="<?= $invoice['id'] ?>" 
                                    data-no="<?= e($invoice['fatura_no']) ?>"
                                    title="İptal Et">
                                <i class="fas fa-times"></i>
                            </button>
                            <?php endif; ?>
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
        order: [[2, 'desc']], // Fatura tarihine göre sırala
        columnDefs: [
            { targets: [4, 5, 6], className: 'text-end' } // Para birimlerini sağa yasla
        ]
    });
    
    // Fatura iptal etme
    $('.delete-invoice').click(function() {
        const id = $(this).data('id');
        const no = $(this).data('no');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: `"${no}" numaralı faturayı iptal etmek istediğinize emin misiniz?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, İptal Et',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `cancel.php?id=${id}`;
            }
        });
    });
});</script> 