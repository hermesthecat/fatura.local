<?php
require_once 'templates/header.php';
require_once 'vendor/autoload.php';

// TCPDF Sabitleri
define('PDF_PAGE_ORIENTATION', 'P');
define('PDF_UNIT', 'mm');
define('PDF_PAGE_FORMAT', 'A4');
define('PDF_CREATOR', 'Fatura Sistemi');
define('PDF_HEADER_LOGO', '');
define('PDF_HEADER_LOGO_WIDTH', 0);
define('PDF_HEADER_TITLE', '');
define('PDF_HEADER_STRING', '');
define('PDF_FONT_SIZE_MAIN', 10);
define('PDF_FONT_SIZE_DATA', 8);
define('PDF_FONT_MONOSPACED', 'courier');
define('PDF_MARGIN_LEFT', 15);
define('PDF_MARGIN_TOP', 27);
define('PDF_MARGIN_RIGHT', 15);
define('PDF_MARGIN_HEADER', 5);
define('PDF_MARGIN_FOOTER', 10);
define('PDF_MARGIN_BOTTOM', 25);

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

// PDF oluştur
class MYPDF extends TCPDF {
    public function Header() {
        $this->SetFont('dejavusans', 'B', 20);
        $this->Cell(0, 15, 'FATURA', 0, true, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $this->Cell(0, 10, 'Sayfa '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// PDF dokümanı oluştur
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Doküman bilgilerini ayarla
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('A. Kerem Gök');
$pdf->SetTitle('Fatura #' . $fatura['fatura_no']);

// Varsayılan başlık ayarları
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);
$pdf->setHeaderFont(Array('dejavusans', '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array('dejavusans', '', PDF_FONT_SIZE_DATA));

// Varsayılan monospace yazı tipi
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Kenar boşlukları
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Otomatik sayfa sonu
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Yazı tipi ölçekleme faktörü
$pdf->setFontSubsetting(true);

// Yazı tipi
$pdf->SetFont('dejavusans', '', 10);

// Yeni sayfa ekle
$pdf->AddPage();

// Fatura başlık bilgileri
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 10, 'Fatura No: ' . $fatura['fatura_no'], 0, 1, 'R');
$pdf->Cell(0, 10, 'Tarih: ' . formatTarih($fatura['fatura_tarihi']), 0, 1, 'R');
if ($fatura['vade_tarihi']) {
    $pdf->Cell(0, 10, 'Vade Tarihi: ' . formatTarih($fatura['vade_tarihi']), 0, 1, 'R');
}

// Müşteri bilgileri
$pdf->SetFont('dejavusans', 'B', 12);
$pdf->Cell(0, 10, 'Müşteri Bilgileri:', 0, 1, 'L');
$pdf->SetFont('dejavusans', '', 10);
$pdf->Cell(0, 6, 'Firma Adı: ' . $fatura['firma_adi'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Vergi No: ' . $fatura['vergi_no'], 0, 1, 'L');
$pdf->Cell(0, 6, 'Vergi Dairesi: ' . $fatura['vergi_dairesi'], 0, 1, 'L');
$pdf->MultiCell(0, 6, 'Adres: ' . $fatura['adres'], 0, 'L', 0, 1, '', '', true);
$pdf->Cell(0, 6, 'Telefon: ' . $fatura['telefon'], 0, 1, 'L');
$pdf->Cell(0, 6, 'E-posta: ' . $fatura['email'], 0, 1, 'L');

$pdf->Ln(10);

// Fatura kalemleri tablosu
$pdf->SetFont('dejavusans', 'B', 10);
$header = array('Ürün/Hizmet', 'Miktar', 'Birim Fiyat', 'Toplam');
$w = array(90, 25, 35, 35);

// Başlık
for($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C');
}
$pdf->Ln();

// Veriler
$pdf->SetFont('dejavusans', '', 10);
foreach($kalemler as $kalem) {
    $pdf->Cell($w[0], 6, $kalem['urun_adi'], 1, 0, 'L');
    $pdf->Cell($w[1], 6, $kalem['miktar'], 1, 0, 'C');
    $pdf->Cell($w[2], 6, formatPara($kalem['birim_fiyat']), 1, 0, 'R');
    $pdf->Cell($w[3], 6, formatPara($kalem['toplam_fiyat']), 1, 0, 'R');
    $pdf->Ln();
}

// Toplamlar
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(array_sum($w) - $w[3], 6, 'Ara Toplam:', 1, 0, 'R');
$pdf->Cell($w[3], 6, formatPara($fatura['toplam_tutar']), 1, 1, 'R');

$pdf->Cell(array_sum($w) - $w[3], 6, 'KDV (' . $fatura['kdv_orani'] . '%):', 1, 0, 'R');
$pdf->Cell($w[3], 6, formatPara($fatura['kdv_tutari']), 1, 1, 'R');

$pdf->Cell(array_sum($w) - $w[3], 6, 'Genel Toplam:', 1, 0, 'R');
$pdf->Cell($w[3], 6, formatPara($fatura['genel_toplam']), 1, 1, 'R');

// Açıklama
if ($fatura['aciklama']) {
    $pdf->Ln(10);
    $pdf->SetFont('dejavusans', 'B', 10);
    $pdf->Cell(0, 6, 'Açıklama:', 0, 1, 'L');
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->MultiCell(0, 6, $fatura['aciklama'], 0, 'L', 0, 1, '', '', true);
}

// PDF'i gönder
$pdf->Output('Fatura_' . $fatura['fatura_no'] . '.pdf', 'I'); 