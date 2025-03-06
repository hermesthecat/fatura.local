<?php
require_once 'templates/header.php';

// Müşteri listesini al
$db = Database::getInstance();
$musteriler = $db->query("SELECT * FROM customers ORDER BY firma_adi")->fetchAll();

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && csrf_token_kontrol($_POST['csrf_token'])) {
        try {
            $db->beginTransaction();

            // Fatura bilgilerini kaydet
            $fatura_no = fatura_no_uret();
            $sql = "INSERT INTO invoices (fatura_no, customer_id, fatura_tarihi, vade_tarihi, toplam_tutar, kdv_orani, kdv_tutari, genel_toplam, aciklama) 
                    VALUES (:fatura_no, :customer_id, :fatura_tarihi, :vade_tarihi, :toplam_tutar, :kdv_orani, :kdv_tutari, :genel_toplam, :aciklama)";
            
            $params = [
                ':fatura_no' => $fatura_no,
                ':customer_id' => $_POST['customer_id'],
                ':fatura_tarihi' => $_POST['fatura_tarihi'],
                ':vade_tarihi' => $_POST['vade_tarihi'],
                ':toplam_tutar' => $_POST['toplam_tutar'],
                ':kdv_orani' => $_POST['kdv_orani'],
                ':kdv_tutari' => $_POST['kdv_tutari'],
                ':genel_toplam' => $_POST['genel_toplam'],
                ':aciklama' => $_POST['aciklama']
            ];

            $db->query($sql, $params);
            $fatura_id = $db->getLastInsertId();

            // Fatura kalemlerini kaydet
            foreach ($_POST['urun_adi'] as $key => $urun_adi) {
                if (!empty($urun_adi)) {
                    $sql = "INSERT INTO invoice_items (invoice_id, urun_adi, miktar, birim_fiyat, toplam_fiyat) 
                            VALUES (:invoice_id, :urun_adi, :miktar, :birim_fiyat, :toplam_fiyat)";
                    
                    $params = [
                        ':invoice_id' => $fatura_id,
                        ':urun_adi' => $urun_adi,
                        ':miktar' => $_POST['miktar'][$key],
                        ':birim_fiyat' => $_POST['birim_fiyat'][$key],
                        ':toplam_fiyat' => $_POST['kalem_toplam'][$key]
                    ];

                    $db->query($sql, $params);
                }
            }

            $db->commit();
            basari("Fatura başarıyla oluşturuldu.");
            header("Location: fatura_goruntule.php?id=" . $fatura_id);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            hata("Fatura oluşturulurken bir hata oluştu: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Yeni Fatura Oluştur</h3>
    </div>
    <div class="card-body">
        <form id="faturaForm" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo csrf_token_olustur(); ?>">
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="customer_id" class="form-label">Müşteri</label>
                    <select name="customer_id" id="customer_id" class="form-select" required>
                        <option value="">Müşteri Seçin</option>
                        <?php foreach ($musteriler as $musteri): ?>
                            <option value="<?php echo $musteri['id']; ?>">
                                <?php echo guvenlik($musteri['firma_adi']); ?> - 
                                <?php echo guvenlik($musteri['vergi_no']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fatura_tarihi" class="form-label">Fatura Tarihi</label>
                    <input type="date" name="fatura_tarihi" id="fatura_tarihi" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="vade_tarihi" class="form-label">Vade Tarihi</label>
                    <input type="date" name="vade_tarihi" id="vade_tarihi" class="form-control" 
                           value="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered" id="kalemlerTablosu">
                    <thead>
                        <tr>
                            <th>Ürün/Hizmet</th>
                            <th width="100">Miktar</th>
                            <th width="150">Birim Fiyat</th>
                            <th width="150">Toplam</th>
                            <th width="50"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <input type="text" name="urun_adi[]" class="form-control" required>
                            </td>
                            <td>
                                <input type="number" name="miktar[]" class="form-control miktar" value="1" min="1" required>
                            </td>
                            <td>
                                <input type="number" name="birim_fiyat[]" class="form-control birim_fiyat" 
                                       step="0.01" min="0" value="0.00" required>
                            </td>
                            <td>
                                <input type="number" name="kalem_toplam[]" class="form-control kalem_toplam" 
                                       step="0.01" value="0.00" readonly>
                            </td>
                            <td>
                                <button type="button" class="btn btn-danger btn-sm kalem-sil">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5">
                                <button type="button" class="btn btn-success btn-sm" id="kalemEkle">
                                    <i class="bi bi-plus"></i> Yeni Kalem Ekle
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="aciklama" class="form-label">Açıklama</label>
                    <textarea name="aciklama" id="aciklama" class="form-control" rows="3"></textarea>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-2">
                                <label class="col-sm-4 col-form-label">Ara Toplam:</label>
                                <div class="col-sm-8">
                                    <input type="number" name="toplam_tutar" id="toplam_tutar" 
                                           class="form-control" step="0.01" value="0.00" readonly>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <label class="col-sm-4 col-form-label">KDV Oranı (%):</label>
                                <div class="col-sm-8">
                                    <input type="number" name="kdv_orani" id="kdv_orani" 
                                           class="form-control" value="18" min="0" max="100" required>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <label class="col-sm-4 col-form-label">KDV Tutarı:</label>
                                <div class="col-sm-8">
                                    <input type="number" name="kdv_tutari" id="kdv_tutari" 
                                           class="form-control" step="0.01" value="0.00" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <label class="col-sm-4 col-form-label">Genel Toplam:</label>
                                <div class="col-sm-8">
                                    <input type="number" name="genel_toplam" id="genel_toplam" 
                                           class="form-control" step="0.01" value="0.00" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Faturayı Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Yeni kalem satırı ekle
    $('#kalemEkle').click(function() {
        var yeniSatir = $('#kalemlerTablosu tbody tr:first').clone();
        yeniSatir.find('input').val('');
        yeniSatir.find('input[name="miktar[]"]').val(1);
        yeniSatir.find('input[name="birim_fiyat[]"]').val('0.00');
        yeniSatir.find('input[name="kalem_toplam[]"]').val('0.00');
        $('#kalemlerTablosu tbody').append(yeniSatir);
    });

    // Kalem satırı sil
    $(document).on('click', '.kalem-sil', function() {
        if ($('#kalemlerTablosu tbody tr').length > 1) {
            $(this).closest('tr').remove();
            hesaplaToplamlar();
        }
    });

    // Miktar veya birim fiyat değiştiğinde
    $(document).on('input', '.miktar, .birim_fiyat', function() {
        var tr = $(this).closest('tr');
        var miktar = parseFloat(tr.find('.miktar').val()) || 0;
        var birimFiyat = parseFloat(tr.find('.birim_fiyat').val()) || 0;
        var kalemToplam = miktar * birimFiyat;
        tr.find('.kalem_toplam').val(kalemToplam.toFixed(2));
        hesaplaToplamlar();
    });

    // KDV oranı değiştiğinde
    $('#kdv_orani').on('input', function() {
        hesaplaToplamlar();
    });

    // Toplamları hesapla
    function hesaplaToplamlar() {
        var araToplam = 0;
        $('.kalem_toplam').each(function() {
            araToplam += parseFloat($(this).val()) || 0;
        });

        var kdvOrani = parseFloat($('#kdv_orani').val()) || 0;
        var kdvTutari = araToplam * (kdvOrani / 100);
        var genelToplam = araToplam + kdvTutari;

        $('#toplam_tutar').val(araToplam.toFixed(2));
        $('#kdv_tutari').val(kdvTutari.toFixed(2));
        $('#genel_toplam').val(genelToplam.toFixed(2));
    }
});
</script>

<?php require_once 'templates/footer.php'; ?> 