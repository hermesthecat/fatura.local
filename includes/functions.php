<?php
/**
 * Genel fonksiyonlar
 * @author A. Kerem Gök
 */

/**
 * Mesaj ekleme fonksiyonu
 * @param string $mesaj Gösterilecek mesaj
 * @param string $tur Mesaj türü (success, danger, warning, info)
 * @return void
 */
function mesaj_ekle($mesaj, $tur = 'info') {
    if (!isset($_SESSION['mesajlar'])) {
        $_SESSION['mesajlar'] = [];
    }
    $_SESSION['mesajlar'][] = [
        'mesaj' => $mesaj,
        'tur' => $tur
    ];
}

/**
 * Mesajları gösterme fonksiyonu
 * @return string HTML formatında mesajlar
 */
function mesaj_goster() {
    if (isset($_SESSION['mesaj'])) {
        $mesaj = $_SESSION['mesaj'];
        unset($_SESSION['mesaj']);
        return '<div class="alert alert-' . $mesaj['tur'] . ' alert-dismissible fade show" role="alert">
                    ' . $mesaj['icerik'] . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    }
    return '';
}

/**
 * Para formatı
 * @param float $tutar Formatlanacak tutar
 * @return string Formatlanmış tutar
 */
function para_format($tutar) {
    return number_format($tutar, 2, ',', '.');
}

/**
 * Tarih formatı
 * @param string $tarih MySQL tarih formatı
 * @return string dd.mm.yyyy formatında tarih
 */
function tarih_format($tarih) {
    return date('d.m.Y', strtotime($tarih));
}

/**
 * Güvenli string
 * @param string $str Güvenli hale getirilecek string
 * @return string Güvenli string
 */
function guvenli_str($str) {
    return htmlspecialchars(trim($str), ENT_QUOTES, 'UTF-8');
}

// Para formatı
function formatPara($tutar, $para_birimi_sembol = null) {
    if ($para_birimi_sembol === null && isset($_SESSION['fatura']['para_birimi_sembol'])) {
        $para_birimi_sembol = $_SESSION['fatura']['para_birimi_sembol'];
    }
    return number_format($tutar, 2, ',', '.') . ' ' . ($para_birimi_sembol ?? '₺');
}

// Tarih formatı
function formatTarih($tarih) {
    return date('d.m.Y', strtotime($tarih));
}

// XSS koruması
function guvenlik($data) {
    if(is_array($data)) {
        foreach($data as $key => $value) {
            $data[$key] = guvenlik($value);
        }
    } else {
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

// Fatura numarası oluşturma
function fatura_no_uret() {
    $prefix = FATURA_PREFIX;
    $yil = date('Y');
    $ay = date('m');
    $random = rand(1000, 9999);
    return $prefix . $yil . $ay . $random;
}

// CSRF token oluşturma
function csrf_token_olustur() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF token kontrolü
function csrf_token_kontrol($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        die('CSRF token doğrulaması başarısız!');
    }
    return true;
}

// CSRF token field oluşturma
function csrf_token_field() {
    return '<input type="hidden" name="csrf_token" value="' . csrf_token_olustur() . '">';
}

// Başarı mesajı
function basari($mesaj) {
    $_SESSION['mesaj'] = [
        'tur' => 'success',
        'icerik' => $mesaj
    ];
}

// Hata mesajı
function hata($mesaj) {
    $_SESSION['mesaj'] = [
        'tur' => 'danger',
        'icerik' => $mesaj
    ];
}

function sayiyiYaziyaCevir($sayi) {
    $birler = array(
        "", "BİR", "İKİ", "ÜÇ", "DÖRT", "BEŞ", "ALTI", "YEDİ", "SEKİZ", "DOKUZ"
    );
    $onlar = array(
        "", "ON", "YİRMİ", "OTUZ", "KIRK", "ELLİ", "ALTMIŞ", "YETMİŞ", "SEKSEN", "DOKSAN"
    );
    $binler = array(
        "", "BİN", "MİLYON", "MİLYAR", "TRİLYON", "KATRİLYON"
    );

    // Sayıyı düzgün formata getir
    $sayi = floatval($sayi);
    $sayi = number_format($sayi, 2, '.', '');
    
    // Tam ve ondalık kısımları ayır
    $sayi_parcalari = explode('.', $sayi);
    $tam_kisim = $sayi_parcalari[0];
    
    // Ondalık kısmı kontrol et ve düzenle
    $ondalik_kisim = "00";
    if (isset($sayi_parcalari[1])) {
        $ondalik_kisim = str_pad($sayi_parcalari[1], 2, "0", STR_PAD_RIGHT);
        $ondalik_kisim = substr($ondalik_kisim, 0, 2);
    }
    
    if ($tam_kisim == 0) {
        return "SIFIR TÜRK LİRASI " . $ondalik_kisim . " KURUŞ";
    }

    $yazi = "";
    $basamak_sayisi = strlen($tam_kisim);
    $basamak_grubu = ceil($basamak_sayisi / 3);

    for ($i = $basamak_grubu; $i > 0; $i--) {
        $basamak = "";
        $grup = substr($tam_kisim, -3);
        if ($grup === false) $grup = "0";
        $tam_kisim = substr($tam_kisim, 0, -3);
        if ($tam_kisim === false) $tam_kisim = "";

        if (strlen($grup) > 0 && intval($grup) > 99) {
            $yuzler = substr($grup, 0, 1);
            if ($yuzler === "1") {
                $basamak .= "YÜZ";
            } else if ($yuzler !== "0") {
                $basamak .= $birler[intval($yuzler)] . "YÜZ";
            }
            $grup = substr($grup, 1);
            if ($grup === false) $grup = "0";
        }

        if (strlen($grup) > 0 && intval($grup) > 9) {
            $onlar_basamak = substr($grup, 0, 1);
            if ($onlar_basamak !== "0") {
                $basamak .= $onlar[intval($onlar_basamak)];
            }
            $grup = substr($grup, 1);
            if ($grup === false) $grup = "0";
        }

        if (strlen($grup) > 0 && intval($grup) > 0) {
            $basamak .= $birler[intval($grup)];
        }

        if ($basamak != "") {
            if ($i == 2 && $basamak == "BİR") {
                $yazi = "BİN" . $yazi;
            } else {
                $yazi = $basamak . $binler[$i-1] . $yazi;
            }
        } else if ($i == 2) { // Binler basamağı boş ama gerekli
            $yazi = "BİN" . $yazi;
        }
    }

    $yazi = trim($yazi);
    if (empty($yazi)) $yazi = "SIFIR";

    return $yazi . " TÜRK LİRASI " . $ondalik_kisim . " KURUŞ";
}

function mesaj_yonlendir($mesaj, $tur = 'success', $url = null) {
    $_SESSION['mesaj'] = [
        'icerik' => $mesaj,
        'tur' => $tur
    ];
    
    if ($url) {
        header('Location: ' . $url);
        exit;
    }
} 