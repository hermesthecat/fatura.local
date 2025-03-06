<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

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

// Müşterileri al
$musteriler = $db->query("SELECT * FROM customers WHERE company_id = :company_id ORDER BY firma_adi",
    [':company_id' => $_SESSION['company_id']])->fetchAll();

// Para birimlerini al
$sql = "SELECT * FROM currencies WHERE aktif = 1 ORDER BY varsayilan DESC, kod";
$para_birimleri = $db->query($sql)->fetchAll();

// Şirket ve ayarlarını al
$sql = "SELECT 
    c.*,
    MAX(CASE WHEN cs.ayar_adi = 'FATURA_NOT' THEN cs.ayar_degeri END) as fatura_not,
    MAX(CASE WHEN cs.ayar_adi = 'VARSAYILAN_KDV' THEN cs.ayar_degeri ELSE '18' END) as varsayilan_kdv
    FROM companies c 
    LEFT JOIN company_settings cs ON cs.company_id = c.id 
    WHERE c.id = :company_id
    GROUP BY c.id";
$sirket = $db->query($sql, [':company_id' => $_SESSION['company_id']])->fetch();

// Varsayılan değerler
$fatura_not = $sirket['fatura_not'] ?? '';
$varsayilan_kdv = $sirket['varsayilan_kdv'] ?? '18';

// Form gönderildi mi?
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['csrf_token']) && csrf_token_kontrol($_POST['csrf_token'])) {
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

            // Vade tarihini kontrol et
            $vade_tarihi = !empty($_POST['vade_tarihi']) ? $_POST['vade_tarihi'] : null;

            // Fatura başlığını ekle
            $sql = "INSERT INTO invoices (
                company_id, currency_id, fatura_no, customer_id, fatura_tarihi, vade_tarihi, 
                toplam_tutar, kdv_orani, kdv_tutari, genel_toplam, aciklama
            ) VALUES (
                :company_id, :currency_id, :fatura_no, :customer_id, :fatura_tarihi, :vade_tarihi,
                :toplam_tutar, :kdv_orani, :kdv_tutari, :genel_toplam, :aciklama
            )";

            $params = [
                ':company_id' => $_SESSION['company_id'],
                ':currency_id' => $_POST['currency_id'],
                ':fatura_no' => $fatura_no,
                ':customer_id' => $_POST['customer_id'],
                ':fatura_tarihi' => $_POST['fatura_tarihi'],
                ':vade_tarihi' => $vade_tarihi,
                ':toplam_tutar' => $_POST['toplam_tutar'],
                ':kdv_orani' => $_POST['kdv_orani'],
                ':kdv_tutari' => $_POST['kdv_tutari'],
                ':genel_toplam' => $_POST['genel_toplam'],
                ':aciklama' => $_POST['aciklama']
            ];

            $db->query($sql, $params);
            $invoice_id = $db->lastInsertId();

            // Fatura kalemlerini ekle
            foreach ($_POST['urun_adi'] as $key => $urun_adi) {
                if (!empty($urun_adi)) {
                    $sql = "INSERT INTO invoice_items (invoice_id, urun_adi, miktar, birim_fiyat, toplam_fiyat) 
                            VALUES (:invoice_id, :urun_adi, :miktar, :birim_fiyat, :toplam_fiyat)";
                    
                    $params = [
                        ':invoice_id' => $invoice_id,
                        ':urun_adi' => $urun_adi,
                        ':miktar' => $_POST['miktar'][$key],
                        ':birim_fiyat' => $_POST['birim_fiyat'][$key],
                        ':toplam_fiyat' => $_POST['kalem_toplam'][$key]
                    ];

                    $db->query($sql, $params);
                }
            }

            basari("Fatura başarıyla oluşturuldu!");
            header('Location: fatura_goruntule.php?id=' . $invoice_id);
            exit;
        } catch (Exception $e) {
            hata("Fatura oluşturulurken bir hata oluştu: " . $e->getMessage());
        }
    }
}

// Header'ı en son dahil et
require_once 'templates/header.php';
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title">Yeni Fatura Oluştur</h3>
        <div class="btn-group">
            <a href="fatura_listele.php" class="btn btn-secondary">
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
                            <option value="<?php echo $musteri['id']; ?>">
                                <?php echo guvenlik($musteri['firma_adi']); ?> - 
                                <?php echo guvenlik($musteri['vergi_no']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="currency_id" class="form-label">Para Birimi</label>
                    <select name="currency_id" id="currency_id" class="form-select" required>
                        <?php foreach ($para_birimleri as $para_birimi): ?>
                            <option value="<?php echo $para_birimi['id']; ?>" 
                                    data-symbol="<?php echo $para_birimi['sembol']; ?>"
                                    <?php echo $para_birimi['varsayilan'] ? 'selected' : ''; ?>>
                                <?php echo $para_birimi['kod']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="fatura_tarihi" class="form-label">Fatura Tarihi</label>
                    <input type="date" name="fatura_tarihi" id="fatura_tarihi" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-2">
                    <label for="vade_tarihi" class="form-label">Vade Tarihi</label>
                    <input type="date" name="vade_tarihi" id="vade_tarihi" class="form-control" 
                           value="<?php echo date('Y-m-d'); ?>">
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
                                <input type="number" name="miktar[]" class="form-control miktar" 
                                       value="1" min="1" required>
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
                    <textarea name="aciklama" id="aciklama" class="form-control" rows="3"><?php echo $fatura_not; ?></textarea>
                    <div class="form-text">Faturada görünecek not/açıklama.</div>
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
                                           class="form-control" value="<?php echo $varsayilan_kdv; ?>" min="0" max="100" required>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <label class="col-sm-4 col-form-label">KDV Tutarı:</label>
                                <div class="col-sm-8">
                                    <input type="number" name="kdv_tutari" id="kdv_tutari" 
                                           class="form-control" step="0.01" value="0.00" readonly>
                                </div>
                            </div>
                            <div class="row mb-2">
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
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Kaydet
                </button>
            </div>
        </form>
    </div>
</div>

<script>
$(document).ready(function() {
    // Para birimi sembolünü güncelle
    function updateCurrencySymbol() {
        var symbol = $('#currency_id option:selected').data('symbol');
        $('.currency-symbol').text(symbol);
    }

    // Sayfa yüklendiğinde ve para birimi değiştiğinde sembolü güncelle
    updateCurrencySymbol();
    $('#currency_id').on('change', updateCurrencySymbol);

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

    // Form gönderilmeden önce kontrol
    $('#faturaForm').submit(function(e) {
        var toplam = parseFloat($('#toplam_tutar').val()) || 0;
        if (toplam <= 0) {
            e.preventDefault();
            alert('Lütfen en az bir kalem ekleyin ve tutarları kontrol edin!');
        }
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 