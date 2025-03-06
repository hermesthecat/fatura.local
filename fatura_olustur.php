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

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Fatura numarası oluştur
        $son_fatura = $db->query("SELECT MAX(CAST(SUBSTRING(fatura_no, LENGTH(:prefix) + 1) AS UNSIGNED)) as son_no 
            FROM invoices WHERE company_id = :company_id AND fatura_no LIKE :prefix_like",
            [
                ':prefix' => FATURA_PREFIX,
                ':company_id' => $_SESSION['company_id'],
                ':prefix_like' => FATURA_PREFIX . '%'
            ])->fetch();
        
        $son_no = $son_fatura['son_no'] ?? 0;
        $yeni_no = $son_no + 1;
        $fatura_no = FATURA_PREFIX . str_pad($yeni_no, 6, '0', STR_PAD_LEFT);

        // Fatura başlığını ekle
        $sql = "INSERT INTO invoices (
            company_id, fatura_no, customer_id, fatura_tarihi, vade_tarihi, 
            toplam_tutar, kdv_orani, kdv_tutari, genel_toplam, aciklama
        ) VALUES (
            :company_id, :fatura_no, :customer_id, :fatura_tarihi, :vade_tarihi,
            :toplam_tutar, :kdv_orani, :kdv_tutari, :genel_toplam, :aciklama
        )";

        $params = [
            ':company_id' => $_SESSION['company_id'],
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
        $invoice_id = $db->lastInsertId();

        // Fatura kalemlerini ekle
        foreach ($_POST['items'] as $item) {
            $sql = "INSERT INTO invoice_items (
                invoice_id, urun_adi, miktar, birim_fiyat, toplam_fiyat
            ) VALUES (
                :invoice_id, :urun_adi, :miktar, :birim_fiyat, :toplam_fiyat
            )";

            $params = [
                ':invoice_id' => $invoice_id,
                ':urun_adi' => $item['urun_adi'],
                ':miktar' => $item['miktar'],
                ':birim_fiyat' => $item['birim_fiyat'],
                ':toplam_fiyat' => $item['toplam_fiyat']
            ];

            $db->query($sql, $params);
        }

        basari("Fatura başarıyla oluşturuldu!");
        header('Location: fatura_goruntule.php?id=' . $invoice_id);
        exit;
    } catch (Exception $e) {
        hata($e->getMessage());
    }
}

// Müşterileri al
$musteriler = $db->query("SELECT * FROM customers WHERE company_id = :company_id ORDER BY firma_adi",
    [':company_id' => $_SESSION['company_id']])->fetchAll();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h1 class="h3">Yeni Fatura Oluştur</h1>
        </div>
    </div>

    <form method="post" id="faturaForm">
        <?php echo csrf_token_field(); ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Müşteri</label>
                                <select name="customer_id" class="form-select" required>
                                    <option value="">Müşteri Seçin</option>
                                    <?php foreach ($musteriler as $musteri): ?>
                                    <option value="<?php echo $musteri['id']; ?>">
                                        <?php echo $musteri['firma_adi']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fatura Tarihi</label>
                                <input type="date" name="fatura_tarihi" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Vade Tarihi</label>
                                <input type="date" name="vade_tarihi" class="form-control">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered" id="kalemlerTablosu">
                                <thead>
                                    <tr>
                                        <th>Ürün/Hizmet</th>
                                        <th style="width: 100px;">Miktar</th>
                                        <th style="width: 150px;">Birim Fiyat</th>
                                        <th style="width: 150px;">Toplam</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="kalem-satir">
                                        <td>
                                            <input type="text" name="items[0][urun_adi]" class="form-control urun-adi" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][miktar]" class="form-control miktar" 
                                                   value="1" min="1" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][birim_fiyat]" class="form-control birim-fiyat" 
                                                   value="0.00" min="0" step="0.01" required>
                                        </td>
                                        <td>
                                            <input type="number" name="items[0][toplam_fiyat]" class="form-control toplam-fiyat" 
                                                   value="0.00" readonly>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger satir-sil">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5">
                                            <button type="button" class="btn btn-sm btn-success" id="kalemEkle">
                                                <i class="bi bi-plus-circle"></i> Kalem Ekle
                                            </button>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Açıklama</label>
                            <textarea name="aciklama" class="form-control" rows="3"><?php echo FATURA_NOT; ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Ara Toplam</label>
                            <input type="number" name="toplam_tutar" class="form-control" value="0.00" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">KDV Oranı (%)</label>
                            <input type="number" name="kdv_orani" class="form-control" 
                                   value="<?php echo VARSAYILAN_KDV; ?>" min="0" max="100" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">KDV Tutarı</label>
                            <input type="number" name="kdv_tutari" class="form-control" value="0.00" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Genel Toplam</label>
                            <input type="number" name="genel_toplam" class="form-control" value="0.00" readonly>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Kaydet
                            </button>
                            <a href="fatura_listele.php" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> İptal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
$(document).ready(function() {
    // Kalem satırı şablonu
    var satirNo = 1;
    function yeniSatir() {
        return `
            <tr class="kalem-satir">
                <td>
                    <input type="text" name="items[${satirNo}][urun_adi]" class="form-control urun-adi" required>
                </td>
                <td>
                    <input type="number" name="items[${satirNo}][miktar]" class="form-control miktar" 
                           value="1" min="1" required>
                </td>
                <td>
                    <input type="number" name="items[${satirNo}][birim_fiyat]" class="form-control birim-fiyat" 
                           value="0.00" min="0" step="0.01" required>
                </td>
                <td>
                    <input type="number" name="items[${satirNo}][toplam_fiyat]" class="form-control toplam-fiyat" 
                           value="0.00" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger satir-sil">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }

    // Yeni kalem satırı ekle
    $('#kalemEkle').click(function() {
        $('#kalemlerTablosu tbody').append(yeniSatir());
        satirNo++;
        hesapla();
    });

    // Kalem satırı sil
    $(document).on('click', '.satir-sil', function() {
        if ($('.kalem-satir').length > 1) {
            $(this).closest('tr').remove();
            hesapla();
        }
    });

    // Değişiklikleri izle
    $(document).on('input', '.miktar, .birim-fiyat, [name="kdv_orani"]', function() {
        hesapla();
    });

    // Toplam hesapla
    function hesapla() {
        var toplam = 0;

        $('.kalem-satir').each(function() {
            var miktar = parseFloat($(this).find('.miktar').val()) || 0;
            var birimFiyat = parseFloat($(this).find('.birim-fiyat').val()) || 0;
            var satirToplam = miktar * birimFiyat;

            $(this).find('.toplam-fiyat').val(satirToplam.toFixed(2));
            toplam += satirToplam;
        });

        var kdvOrani = parseFloat($('[name="kdv_orani"]').val()) || 0;
        var kdvTutari = toplam * (kdvOrani / 100);
        var genelToplam = toplam + kdvTutari;

        $('[name="toplam_tutar"]').val(toplam.toFixed(2));
        $('[name="kdv_tutari"]').val(kdvTutari.toFixed(2));
        $('[name="genel_toplam"]').val(genelToplam.toFixed(2));
    }

    // Form gönderilmeden önce kontrol
    $('#faturaForm').submit(function(e) {
        var toplam = parseFloat($('[name="toplam_tutar"]').val()) || 0;
        if (toplam <= 0) {
            e.preventDefault();
            alert('Lütfen en az bir kalem ekleyin ve tutarları kontrol edin!');
        }
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 