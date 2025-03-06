<?php
// Herhangi bir çıktı olmadığından emin olmak için output buffering'i başlat
ob_start();

// Gerekli dosyaları include et
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

// Fatura ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    hata("Geçersiz fatura ID!");
    header("Location: fatura_listele.php");
    exit;
}

$fatura_id = $_GET['id'];
$db = Database::getInstance();

// Fatura ve müşteri bilgilerini al
$sql = "SELECT i.*, c.* 
        FROM invoices i 
        LEFT JOIN customers c ON i.customer_id = c.id 
        WHERE i.id = :id";
$fatura = $db->query($sql, [':id' => $fatura_id])->fetch();

if (!$fatura) {
    hata("Fatura bulunamadı!");
    header("Location: fatura_listele.php");
    exit;
}

// Fatura kalemlerini al
$sql = "SELECT * FROM invoice_items WHERE invoice_id = :invoice_id ORDER BY id";
$kalemler = $db->query($sql, [':invoice_id' => $fatura_id])->fetchAll();

// Şirket bilgilerini al
$sirket = $db->query("SELECT * FROM company_settings WHERE id = 1")->fetch();

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
$header = array('Sıra', 'Mal/Hizmet', 'Miktar', 'Birim', 'Birim Fiyat', 'KDV(%)', 'Toplam');
$w = array(10, 65, 20, 15, 25, 20, 25);

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
    $pdf->Cell($w[4], 6, formatPara($kalem['birim_fiyat']), 1, 0, 'R');
    $pdf->Cell($w[5], 6, $fatura['kdv_orani'], 1, 0, 'C');
    $pdf->Cell($w[6], 6, formatPara($kalem['toplam_fiyat']), 1, 0, 'R');
    $pdf->Ln();
}

// Boş satırlar
$bos_satir = 10 - count($kalemler);
for($i = 0; $i < $bos_satir; $i++) {
    $pdf->Cell($w[0], 6, '', 1, 0, 'C');
    $pdf->Cell($w[1], 6, '', 1, 0, 'L');
    $pdf->Cell($w[2], 6, '', 1, 0, 'C');
    $pdf->Cell($w[3], 6, '', 1, 0, 'C');
    $pdf->Cell($w[4], 6, '', 1, 0, 'R');
    $pdf->Cell($w[5], 6, '', 1, 0, 'C');
    $pdf->Cell($w[6], 6, '', 1, 0, 'R');
    $pdf->Ln();
}

// Toplamlar
$pdf->Ln(5);
$pdf->SetFont('dejavusans', 'B', 9);
$pdf->Cell(135, 6, 'YAZI İLE: ' . sayiyiYaziyaCevir($fatura['genel_toplam']) . ' TL', 0, 0, 'L');
$pdf->Cell(30, 6, 'ARA TOPLAM:', 0, 0, 'R');
$pdf->Cell(25, 6, formatPara($fatura['toplam_tutar']), 1, 1, 'R');

$pdf->Cell(135, 6, '', 0, 0, 'L');
$pdf->Cell(30, 6, 'KDV TOPLAM:', 0, 0, 'R');
$pdf->Cell(25, 6, formatPara($fatura['kdv_tutari']), 1, 1, 'R');

$pdf->Cell(135, 6, '', 0, 0, 'L');
$pdf->Cell(30, 6, 'GENEL TOPLAM:', 0, 0, 'R');
$pdf->Cell(25, 6, formatPara($fatura['genel_toplam']), 1, 1, 'R');

// Açıklama ve Notlar
if ($fatura['aciklama']) {
    $pdf->Ln(5);
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell(0, 6, 'Açıklamalar:', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 9);
    $pdf->MultiCell(0, 5, $fatura['aciklama'], 0, 'L');
}

// Banka Bilgileri ve İban
$pdf->Ln(5);
$pdf->SetFont('dejavusans', 'B', 9);
$pdf->Cell(0, 6, 'Banka Hesap Bilgileri:', 0, 1, 'L');
$pdf->SetFont('dejavusans', '', 8);
$pdf->Cell(0, 5, 'Banka: ' . $sirket['banka_adi'], 0, 1, 'L');
$pdf->Cell(0, 5, 'IBAN: ' . $sirket['iban'], 0, 1, 'L');

// PDF'i gönder
$pdf->Output('Fatura_' . $fatura['fatura_no'] . '.pdf', 'I'); 