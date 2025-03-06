<?php
require_once 'templates/header.php';

// Fatura ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    hata("Geçersiz fatura ID!");
    header("Location: fatura_listele.php");
    exit;
}

$fatura_id = $_GET['id'];
$db = Database::getInstance();

// Fatura bilgilerini al
$sql = "SELECT * FROM invoices WHERE id = :id";
$fatura = $db->query($sql, [':id' => $fatura_id])->fetch();

if (!$fatura) {
    hata("Fatura bulunamadı!");
    header("Location: fatura_listele.php");
    exit;
}

// Fatura kalemlerini al
$sql = "SELECT * FROM invoice_items WHERE invoice_id = :invoice_id ORDER BY id";
$kalemler = $db->query($sql, [':invoice_id' => $fatura_id])->fetchAll();

// Müşteri listesini al
$sql = "SELECT * FROM customers ORDER BY firma_adi";
$musteriler = $db->query($sql)->fetchAll();

// Form gönderildi mi kontrol et
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && csrf_token_kontrol($_POST['csrf_token'])) {
        try {
            $db->beginTransaction();

            // Fatura bilgilerini güncelle
            $sql = "UPDATE invoices SET 
                    customer_id = :customer_id,
                    fatura_tarihi = :fatura_tarihi,
                    vade_tarihi = :vade_tarihi,
                    toplam_tutar = :toplam_tutar,
                    kdv_orani = :kdv_orani,
                    kdv_tutari = :kdv_tutari,
                    genel_toplam = :genel_toplam,
                    aciklama = :aciklama
                    WHERE id = :id";
            
            $params = [
                ':id' => $fatura_id,
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

            // Eski fatura kalemlerini sil
            $sql = "DELETE FROM invoice_items WHERE invoice_id = :invoice_id";
            $db->query($sql, [':invoice_id' => $fatura_id]);

            // Yeni fatura kalemlerini ekle
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
            basari("Fatura başarıyla güncellendi.");
            header("Location: fatura_goruntule.php?id=" . $fatura_id);
            exit;
        } catch (Exception $e) {
            $db->rollback();
            hata("Fatura güncellenirken bir hata oluştu: " . $e->getMessage());
        }
    }
}
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Fatura Düzenle</h3>
        <div class="btn-group">
            <a href="fatura_goruntule.php?id=<?php echo $fatura_id; ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Geri
            </a>
        </div>
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
                            <option value="<?php echo $musteri['id']; ?>" 
                                    <?php echo $musteri['id'] == $fatura['customer_id'] ? 'selected' : ''; ?>>
                                <?php echo guvenlik($musteri['firma_adi']); ?> - 
                                <?php echo guvenlik($musteri['vergi_no']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="fatura_tarihi" class="form-label">Fatura Tarihi</label>
                    <input type="date" name="fatura_tarihi" id="fatura_tarihi" class="form-control" 
                           value="<?php echo $fatura['fatura_tarihi']; ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="vade_tarihi" class="form-label">Vade Tarihi</label>
                    <input type="date" name="vade_tarihi" id="vade_tarihi" class="form-control" 
                           value="<?php echo $fatura['vade_tarihi']; ?>">
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
                        <?php foreach ($kalemler as $kalem): ?>
                            <tr>
                                <td>
                                    <input type="text" name="urun_adi[]" class="form-control" 
                                           value="<?php echo guvenlik($kalem['urun_adi']); ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="miktar[]" class="form-control miktar" 
                                           value="<?php echo $kalem['miktar']; ?>" min="1" required>
                                </td>
                                <td>
                                    <input type="number" name="birim_fiyat[]" class="form-control birim_fiyat" 
                                           step="0.01" min="0" value="<?php echo $kalem['birim_fiyat']; ?>" required>
                                </td>
                                <td>
                                    <input type="number" name="kalem_toplam[]" class="form-control kalem_toplam" 
                                           step="0.01" value="<?php echo $kalem['toplam_fiyat']; ?>" readonly>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm kalem-sil">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
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
                    <textarea name="aciklama" id="aciklama" class="form-control" rows="3"><?php echo guvenlik($fatura['aciklama']); ?></textarea>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="row mb-2">
                                <label class="col-sm-4 col-form-label">Ara Toplam:</label>
                                <div class="col-sm-8">
                                    <input type="number" name="toplam_tutar" id="toplam_tutar" 
                                           class="form-control" step="0.01" value="<?php echo $fatura['toplam_tutar']; ?>" readonly>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <label class="col-sm-4 col-form-label">KDV Oranı (%):</label>
                                <div class="col-sm-8">
                                    <input type="number" name="kdv_orani" id="kdv_orani" 
                                           class="form-control" value="<?php echo $fatura['kdv_orani']; ?>" min="0" max="100" required>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <label class="col-sm-4 col-form-label">KDV Tutarı:</label>
                                <div class="col-sm-8">
                                    <input type="number" name="kdv_tutari" id="kdv_tutari" 
                                           class="form-control" step="0.01" value="<?php echo $fatura['kdv_tutari']; ?>" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <label class="col-sm-4 col-form-label">Genel Toplam:</label>
                                <div class="col-sm-8">
                                    <input type="number" name="genel_toplam" id="genel_toplam" 
                                           class="form-control" step="0.01" value="<?php echo $fatura['genel_toplam']; ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Yeni kalem satırı ekle
    $('#kalemEkle').click(function() {
        var yeniSatir = `
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
        `;
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