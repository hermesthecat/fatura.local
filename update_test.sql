-- Test şirketi
INSERT INTO `companies` (
    `unvan`, `adres`, `sehir`, `telefon`, `email`, 
    `vergi_dairesi`, `vergi_no`, `web`, `mersis_no`, 
    `ticaret_sicil_no`, `banka_adi`, `iban`, `aktif`
) VALUES (
    'Test Şirketi A.Ş.', 
    'Test Mahallesi Test Sokak No:1', 
    'İstanbul', 
    '0212 123 45 67',
    'info@testfirma.com',
    'Test Vergi Dairesi',
    '1234567890',
    'www.testfirma.com',
    '0123456789012345',
    '123456',
    'Test Bank',
    'TR12 3456 7890 1234 5678 9012 34',
    1
);

-- Admin kullanıcısını test şirketine ekle
INSERT INTO `user_companies` (`user_id`, `company_id`)
SELECT 
    (SELECT `id` FROM `users` WHERE `username` = 'admin'),
    (SELECT `id` FROM `companies` WHERE `unvan` = 'Test Şirketi A.Ş.'); 