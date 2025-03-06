<?php

/**
 * Konfigürasyon değerini döndürür
 */
function config(string $key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $key) {
        if (!isset($value[$key])) {
            return $default;
        }
        $value = $value[$key];
    }
    
    return $value;
}

/**
 * Para birimini formatlar
 */
function format_money(float $amount): string {
    return number_format(
        $amount,
        config('currency.decimal_places', 2),
        config('currency.decimal_separator', ','),
        config('currency.thousand_separator', '.')
    ) . ' ' . config('currency.symbol', '₺');
}

/**
 * Tarihi formatlar
 */
function format_date(string $date, string $format = 'd.m.Y'): string {
    return date($format, strtotime($date));
}

/**
 * XSS koruması için HTML karakterlerini dönüştürür
 */
function e(string $string): string {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * CSRF token oluşturur
 */
function generate_csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF token doğrular
 */
function verify_csrf_token(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Oturum kontrolü yapar
 */
function check_auth(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Kullanıcı girişi gerektirir
 */
function require_auth(): void {
    if (!check_auth()) {
        header('Location: ' . config('app.url') . '/login.php');
        exit;
    }
}

/**
 * Hata mesajlarını gösterir
 */
function display_errors(array $errors, string $field): string {
    if (isset($errors[$field])) {
        return '<div class="invalid-feedback">' . e($errors[$field]) . '</div>';
    }
    return '';
}

/**
 * Başarı mesajı gösterir
 */
function display_success(string $message): string {
    return '<div class="alert alert-success">' . e($message) . '</div>';
}

/**
 * Hata mesajı gösterir
 */
function display_error(string $message): string {
    return '<div class="alert alert-danger">' . e($message) . '</div>';
}

/**
 * Dosya yükler
 */
function upload_file(array $file, string $destination): ?string {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $destination . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }

    return null;
}

/**
 * Sayfa başlığını ayarlar
 */
function set_page_title(string $title): string {
    return $title . ' - ' . config('app.name');
}

/**
 * Aktif menü elemanını belirler
 */
function is_active_menu(string $path): string {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false ? 'active' : '';
}

/**
 * Fatura durumunu Türkçe olarak döndürür
 */
function get_invoice_status(string $status): string {
    $statuses = [
        'taslak' => 'Taslak',
        'onaylandı' => 'Onaylandı',
        'iptal' => 'İptal'
    ];
    
    return $statuses[$status] ?? $status;
}

/**
 * Kullanıcı rolünü Türkçe olarak döndürür
 */
function get_user_role(string $role): string {
    $roles = [
        'admin' => 'Yönetici',
        'user' => 'Kullanıcı'
    ];
    
    return $roles[$role] ?? $role;
}

/**
 * Pagination için sayfa bağlantılarını oluşturur
 */
function generate_pagination(int $total, int $per_page, int $current_page): string {
    $total_pages = ceil($total / $per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav><ul class="pagination">';
    
    // Önceki sayfa
    if ($current_page > 1) {
        $html .= sprintf(
            '<li class="page-item"><a class="page-link" href="?page=%d">&laquo;</a></li>',
            $current_page - 1
        );
    }
    
    // Sayfa numaraları
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= sprintf(
                '<li class="page-item active"><span class="page-link">%d</span></li>',
                $i
            );
        } else {
            $html .= sprintf(
                '<li class="page-item"><a class="page-link" href="?page=%d">%d</a></li>',
                $i,
                $i
            );
        }
    }
    
    // Sonraki sayfa
    if ($current_page < $total_pages) {
        $html .= sprintf(
            '<li class="page-item"><a class="page-link" href="?page=%d">&raquo;</a></li>',
            $current_page + 1
        );
    }
    
    $html .= '</ul></nav>';
    return $html;
} 