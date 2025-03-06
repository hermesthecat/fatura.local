<?php
return [
    'app' => [
        'name' => 'Fatura Yönetim Sistemi',
        'version' => '1.0.0',
        'url' => 'http://fatura.local',
        'timezone' => 'Europe/Istanbul',
        'locale' => 'tr_TR',
        'debug' => true
    ],
    
    'session' => [
        'name' => 'fatura_session',
        'lifetime' => 7200, // 2 saat
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true
    ],
    
    'mail' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => '',
        'password' => '',
        'encryption' => 'tls',
        'from_address' => 'noreply@fatura.local',
        'from_name' => 'Fatura Sistemi'
    ],
    
    'pdf' => [
        'paper_size' => 'A4',
        'orientation' => 'portrait',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 16,
        'margin_bottom' => 16,
        'margin_header' => 9,
        'margin_footer' => 9
    ],
    
    'currency' => [
        'code' => 'TRY',
        'symbol' => '₺',
        'decimal_places' => 2,
        'decimal_separator' => ',',
        'thousand_separator' => '.'
    ]
]; 