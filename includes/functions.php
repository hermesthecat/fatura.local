<?php
/**
 * Yardımcı Fonksiyonlar
 * @author A. Kerem Gök
 */

// Para formatı
function formatPara($tutar) {
    return number_format($tutar, 2, ',', '.') . ' ' . PARA_BIRIMI;
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

// Mesaj gösterimi
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