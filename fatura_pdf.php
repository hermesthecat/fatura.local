<?php
// Herhangi bir çıktı olmadığından emin olmak için output buffering'i başlat
ob_start();

// Gerekli dosyaları include et
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

// Oturum kontrolü
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['company_id'])) {
    hata("Lütfen önce bir şirket seçin!");
    header('Location: index.php');
    exit;
}

// Fatura ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    hata("Geçersiz fatura ID!");
    header("Location: fatura_listele.php");
    exit;
}

$fatura_id = $_GET['id'];
$db = Database::getInstance();

// Fatura, müşteri ve para birimi bilgilerini al
$sql = "SELECT i.*, c.*, cur.sembol as para_birimi_sembol, cur.kod as para_birimi_kod
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        LEFT JOIN currencies cur ON cur.id = i.currency_id
        WHERE i.id = :id AND i.company_id = :company_id";
$fatura = $db->query($sql, [':id' => $fatura_id, ':company_id' => $_SESSION['company_id']])->fetch();

if (!$fatura) {
    hata("Fatura bulunamadı!");
    header("Location: fatura_listele.php");
    exit;
}

// Fatura kalemlerini al
$sql = "SELECT * FROM invoice_items WHERE invoice_id = :invoice_id ORDER BY id";
$kalemler = $db->query($sql, [':invoice_id' => $fatura_id])->fetchAll();

if (!$kalemler) {
    hata("Fatura kalemleri bulunamadı!");
    header("Location: fatura_listele.php");
    exit;
}

// Şirket bilgilerini al
$sql = "SELECT c.*, cs.ayar_degeri as fatura_not 
        FROM companies c 
        LEFT JOIN company_settings cs ON cs.company_id = c.id AND cs.ayar_adi = 'FATURA_NOT'
        WHERE c.id = :company_id";
$sirket = $db->query($sql, [':company_id' => $_SESSION['company_id']])->fetch();

if (!$sirket) {
    hata("Şirket bilgileri bulunamadı!");
    header("Location: fatura_listele.php");
    exit;
}

// Tüm çıktıyı temizle
ob_clean();

// PDF oluştur
class MYPDF extends TCPDF {
    public function Header() {
        // Logo
        if (!empty($GLOBALS['sirket']['logo']) && file_exists($GLOBALS['sirket']['logo'])) {
            $this->Image($GLOBALS['sirket']['logo'], 15, 10, 50);
        }
        
        // Şirket Bilgileri
        $this->SetFont('dejavusans', 'B', 12);
        $this->SetXY(70, 10);
        $this->Cell(0, 6, $GLOBALS['sirket']['unvan'], 0, 1, 'L');
        
        $this->SetFont('dejavusans', '', 8);
        $this->SetX(70);
        $this->MultiCell(80, 4, $GLOBALS['sirket']['adres'] . "\n" . 
                               $GLOBALS['sirket']['sehir'] . "\n" .
                               "Tel: " . $GLOBALS['sirket']['telefon'] . "\n" .
                               "E-posta: " . $GLOBALS['sirket']['email'] . "\n" .
                               "Web: " . $GLOBALS['sirket']['web'], 0, 'L');
                               
        // Vergi Bilgileri
        $this->SetXY(150, 10);
        $this->MultiCell(45, 4, 
            "Vergi Dairesi: " . $GLOBALS['sirket']['vergi_dairesi'] . "\n" .
            "VKN: " . $GLOBALS['sirket']['vergi_no'] . "\n" .
            "Mersis No: " . $GLOBALS['sirket']['mersis_no'] . "\n" .
            "Ticaret Sicil No: " . $GLOBALS['sirket']['ticaret_sicil_no'],
            0, 'L');

        // Fatura Başlığı
        $this->SetFont('dejavusans', 'B', 24);
        $this->SetXY(150, 30);
        $this->Cell(45, 10, 'FATURA', 0, 1, 'C');
    }

    public function Footer() {
        $this->SetY(-20);
        $this->SetFont('dejavusans', 'I', 8);
        $this->Cell(0, 4, 'Bu fatura elektronik olarak hazırlanmıştır.', 0, 1, 'C');
        $this->Cell(0, 4, 'Sayfa '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 0, 'C');
    }
}

// PDF dokümanı oluştur
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Doküman bilgilerini ayarla
$pdf->SetCreator('Fatura Sistemi');
$pdf->SetAuthor('A. Kerem Gök');
$pdf->SetTitle('Fatura #' . $fatura['fatura_no']);

// Kenar boşlukları
$pdf->SetMargins(15, 50, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(15);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->setFontSubsetting(true);
$pdf->AddPage();

// Fatura ve Müşteri Bilgileri Tablosu
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(95, 6, '', 'LTR', 0, 'L');
$pdf->Cell(85, 6, 'Fatura No: ' . $fatura['fatura_no'], 'LTR', 1, 'R');

$pdf->Cell(95, 6, 'SAYIN,', 'LR', 0, 'L');
$pdf->Cell(85, 6, 'Düzenleme Tarihi: ' . formatTarih($fatura['fatura_tarihi']), 'LR', 1, 'R');

$pdf->SetFont('dejavusans', 'B', 11);
$pdf->Cell(95, 6, $fatura['firma_adi'], 'LR', 0, 'L');
if ($fatura['vade_tarihi']) {
    $pdf->Cell(85, 6, 'Vade Tarihi: ' . formatTarih($fatura['vade_tarihi']), 'LR', 1, 'R');
} else {
    $pdf->Cell(85, 6, '', 'LR', 1);
}

$pdf->SetFont('dejavusans', '', 9);
$pdf->MultiCell(95, 5, $fatura['adres'], 'LR', 'L');
$y = $pdf->GetY();
$pdf->SetXY(110, $pdf->GetY() - 10);
$pdf->Cell(85, 10, '', 'LR', 1);

$pdf->Cell(95, 5, 'Vergi Dairesi: ' . $fatura['vergi_dairesi'], 'LR', 0, 'L');
$pdf->Cell(85, 5, '', 'LR', 1);

$pdf->Cell(95, 5, 'VKN/TCKN: ' . $fatura['vergi_no'], 'LBR', 0, 'L');
$pdf->Cell(85, 5, '', 'LBR', 1);

$pdf->Ln(5);

// Fatura kalemleri tablosu
$pdf->SetFont('dejavusans', 'B', 9);

// Sütun genişlikleri
$w = array(
    15,     // Sıra
    75,     // Mal/Hizmet
    15,     // Miktar
    15,     // Birim
    20,     // Birim Fiyat
    15,     // KDV(%)
    25      // Toplam
);

// Toplam genişliği hesapla
$total_width = array_sum($w);
$start_x = $pdf->GetX();

$header = array('Sıra', 'Mal/Hizmet', 'Miktar', 'Birim', 'Bir. Fiyat', 'KDV(%)', 'Toplam');

// Başlık
foreach($header as $i => $h) {
    $pdf->Cell($w[$i], 7, $h, 1, 0, 'C');
}
$pdf->Ln();

// Veriler
$pdf->SetFont('dejavusans', '', 9);
$sira = 1;
foreach($kalemler as $kalem) {
    $pdf->Cell($w[0], 6, $sira++, 1, 0, 'C');
    $pdf->Cell($w[1], 6, $kalem['urun_adi'], 1, 0, 'L');
    $pdf->Cell($w[2], 6, $kalem['miktar'], 1, 0, 'C');
    $pdf->Cell($w[3], 6, 'Adet', 1, 0, 'C');
    $pdf->Cell($w[4], 6, formatPara($kalem['birim_fiyat'], $fatura['para_birimi_sembol']), 1, 0, 'R');
    $pdf->Cell($w[5], 6, $fatura['kdv_orani'], 1, 0, 'C');
    $pdf->Cell($w[6], 6, formatPara($kalem['toplam_fiyat'], $fatura['para_birimi_sembol']), 1, 0, 'R');
    $pdf->Ln();
}

// Boş satırlar
$bos_satir = 10 - count($kalemler);
for($i = 0; $i < $bos_satir; $i++) {
    foreach($w as $width) {
        $pdf->Cell($width, 6, '', 1, 0, 'C');
    }
    $pdf->Ln();
}

// Toplamlar
$pdf->Ln(5);
$pdf->SetFont('dejavusans', 'B', 9);

// YAZI İLE kısmı
$yazi_ile = 'YAZI İLE: ' . sayiyiYaziyaCevir($fatura['genel_toplam']) . ' ' . $fatura['para_birimi_kod'];
$pdf->Cell($w[0] + $w[1] + $w[2], 6, $yazi_ile, 0, 1, 'L');

// Toplamlar sağda
$pdf->SetX($start_x + $total_width - ($w[5] + $w[6]));
$pdf->Cell($w[5], 6, 'ARA TOPLAM:', 0, 0, 'R');
$pdf->Cell($w[6], 6, formatPara($fatura['toplam_tutar'], $fatura['para_birimi_sembol']), 1, 1, 'R');

$pdf->SetX($start_x + $total_width - ($w[5] + $w[6]));
$pdf->Cell($w[5], 6, 'KDV TOPLAM:', 0, 0, 'R');
$pdf->Cell($w[6], 6, formatPara($fatura['kdv_tutari'], $fatura['para_birimi_sembol']), 1, 1, 'R');

$pdf->SetX($start_x + $total_width - ($w[5] + $w[6]));
$pdf->Cell($w[5], 6, 'GENEL TOPLAM:', 0, 0, 'R');
$pdf->Cell($w[6], 6, formatPara($fatura['genel_toplam'], $fatura['para_birimi_sembol']), 1, 1, 'R');

// Açıklama ve Notlar
if ($fatura['aciklama']) {
    $pdf->Ln(5);
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell(0, 6, 'Açıklamalar:', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->MultiCell(0, 5, $fatura['aciklama'], 0, 'L');
}

// Fatura Notu
// if ($sirket['fatura_not']) {
//     $pdf->Ln(5);
//     $pdf->SetFont('dejavusans', 'I', 8);
//     $pdf->MultiCell(0, 4, $sirket['fatura_not'], 0, 'L');
// }

// Banka Bilgileri ve İban
$pdf->Ln(5);
$pdf->SetFont('dejavusans', 'B', 9);
$pdf->Cell(0, 6, 'Banka Hesap Bilgileri:', 0, 1, 'L');
$pdf->SetFont('dejavusans', '', 8);
$pdf->Cell(0, 5, 'Banka: ' . $sirket['banka_adi'], 0, 1, 'L');
$pdf->Cell(0, 5, 'IBAN: ' . $sirket['iban'], 0, 1, 'L');

// PDF'i gönder
$pdf->Output('Fatura_' . $fatura['fatura_no'] . '.pdf', 'I'); 