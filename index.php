<?php
require_once __DIR__ . '/includes/functions.php';
require_auth();

use App\Invoice;
use App\Customer;
use App\Product;

$title = 'Dashboard';

// İstatistikleri al
$invoice = new Invoice();
$customer = new Customer();
$product = new Product();

$total_invoices = count($invoice->all());
$total_customers = count($customer->all());
$total_products = count($product->all());
$overdue_invoices = count($invoice->getOverdueInvoices());

require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card stats-card bg-primary text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="fas fa-file-invoice"></i>
                </div>
                <div class="title">Toplam Fatura</div>
                <div class="value"><?= $total_invoices ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card bg-success text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="title">Toplam Müşteri</div>
                <div class="value"><?= $total_customers ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card bg-info text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="title">Toplam Ürün</div>
                <div class="value"><?= $total_products ?></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card stats-card bg-warning text-white">
            <div class="card-body">
                <div class="icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="title">Vadesi Geçmiş</div>
                <div class="value"><?= $overdue_invoices ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Son Faturalar</h5>
                <a href="/modules/invoices" class="btn btn-primary btn-sm">
                    Tümünü Gör
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Fatura No</th>
                                <th>Müşteri</th>
                                <th>Tarih</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT i.*, c.firma_adi 
                                    FROM invoices i 
                                    JOIN customers c ON c.id = i.customer_id 
                                    ORDER BY i.created_at DESC 
                                    LIMIT 5";
                            
                            $stmt = $invoice->db->query($sql);
                            $invoices = $stmt->fetchAll();
                            
                            foreach ($invoices as $inv):
                            ?>
                            <tr>
                                <td><?= e($inv['fatura_no']) ?></td>
                                <td><?= e($inv['firma_adi']) ?></td>
                                <td><?= format_date($inv['fatura_tarihi']) ?></td>
                                <td><?= format_money($inv['genel_toplam']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $inv['durum'] === 'onaylandı' ? 'success' : ($inv['durum'] === 'iptal' ? 'danger' : 'warning') ?>">
                                        <?= get_invoice_status($inv['durum']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Stok Durumu</h5>
                <a href="/modules/products" class="btn btn-primary btn-sm">
                    Tümünü Gör
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ürün Kodu</th>
                                <th>Ürün Adı</th>
                                <th>Stok</th>
                                <th>Birim</th>
                                <th>Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $low_stock_products = $product->getLowStockProducts(10);
                            foreach ($low_stock_products as $prod):
                            ?>
                            <tr>
                                <td><?= e($prod['urun_kodu']) ?></td>
                                <td><?= e($prod['urun_adi']) ?></td>
                                <td><?= number_format($prod['stok_miktari'], 2) ?></td>
                                <td><?= e($prod['birim']) ?></td>
                                <td>
                                    <?php if ($prod['stok_miktari'] <= 0): ?>
                                    <span class="badge bg-danger">Stokta Yok</span>
                                    <?php elseif ($prod['stok_miktari'] <= 5): ?>
                                    <span class="badge bg-warning">Kritik Stok</span>
                                    <?php else: ?>
                                    <span class="badge bg-success">Yeterli Stok</span>
                                    <?php endif; ?>
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

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Select2
    $('.select2').select2({
        theme: 'bootstrap-5'
    });
    
    // DataTables
    $('.datatable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json'
        }
    });
});
</script> 