# Fatura Oluşturma Web Uygulaması

**Yazar:** A. Kerem Gök

## Veritabanı Yapısı (MySQL)

### 1. Müşteriler (customers)
- id (PK)
- firma_adi
- vergi_no
- vergi_dairesi
- adres
- telefon
- eposta
- durum (aktif/pasif)
- created_at
- updated_at

### 2. Ürünler (products)
- id (PK)
- urun_kodu
- urun_adi
- birim_fiyat
- kdv_orani
- birim (ADET, KG, LT, MT, M2, M3)
- aciklama
- durum (aktif/pasif)
- created_at
- updated_at

### 3. Faturalar (invoices)
- id (PK)
- fatura_no
- customer_id (FK)
- fatura_tarihi
- vade_tarihi
- ara_toplam
- kdv_toplam
- genel_toplam
- aciklama
- durum (taslak, onaylandı, iptal)
- created_at
- updated_at

### 4. Fatura Kalemleri (invoice_items)
- id (PK)
- invoice_id (FK)
- product_id (FK)
- miktar
- birim_fiyat
- kdv_orani
- kdv_tutari
- ara_toplam
- toplam_tutar
- created_at
- updated_at

## Klasör Yapısı

```
/
├── config/
│   ├── database.php
│   └── config.php
├── includes/
│   ├── functions.php
│   ├── auth.php
│   ├── header.php
│   └── footer.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── classes/
│   ├── Database.php
│   ├── Customer.php
│   ├── Product.php
│   └── Invoice.php
└── modules/
    ├── customers/
    │   ├── index.php
    │   ├── create.php
    │   ├── edit.php
    │   ├── view.php
    │   └── delete.php
    ├── products/
    │   ├── index.php
    │   ├── create.php
    │   ├── edit.php
    │   ├── view.php
    │   └── delete.php
    └── invoices/
        ├── index.php
        ├── create.php
        ├── edit.php
        ├── view.php
        ├── print.php
        ├── delete.php
        ├── add_item.php
        └── delete_item.php
```

## Temel Özellikler

### 1. Müşteri Yönetimi ✅
- Müşteri ekleme/düzenleme/silme
- Müşteri listesi görüntüleme (DataTables)
- Müşteri detay sayfası
- Müşteriye ait fatura listesi

### 2. Ürün Yönetimi ✅
- Ürün ekleme/düzenleme/silme
- Ürün listesi görüntüleme (DataTables)
- Ürün detay sayfası
- Ürüne ait fatura geçmişi

### 3. Fatura İşlemleri ✅
- Yeni fatura oluşturma
- Fatura düzenleme
- Fatura silme/iptal etme
- Fatura önizleme
- Yazdırma görünümü
- Otomatik fatura numarası oluşturma
- KDV hesaplama
- Fatura kalemi ekleme/silme

### 4. Genel Özellikler ✅
- Responsive tasarım (Bootstrap 5)
- Form doğrulama
- CSRF koruması
- XSS koruması
- DataTables entegrasyonu
- Select2 entegrasyonu
- SweetAlert2 entegrasyonu
- Para birimi formatlaması
- Tarih formatlaması
- Telefon numarası formatlaması
- Hata yönetimi
- Flash mesajları

## Kullanılan Teknolojiler

### 1. Backend
- PHP 8.x
- MySQL 8.x
- PDO veritabanı bağlantısı

### 2. Frontend
- HTML5
- CSS3 (Bootstrap 5)
- JavaScript (jQuery)
- AJAX

### 3. Ek Kütüphaneler
- DataTables (tablo yönetimi)
- Select2 (gelişmiş select kutuları)
- SweetAlert2 (bildirimler)
- FontAwesome (ikonlar)

## Güvenlik Önlemleri

1. SQL Injection koruması (PDO prepared statements)
2. XSS koruması (e() fonksiyonu)
3. CSRF token kullanımı
4. Input validasyonu
5. Oturum yönetimi
6. Kullanıcı yetkilendirme sistemi

## Kurulum

1. Veritabanı şemasını oluşturun
2. Composer ile gerekli paketleri yükleyin
3. `config/database.php` dosyasında veritabanı bağlantı bilgilerini düzenleyin
4. `config/config.php` dosyasında gerekli ayarları yapın
5. Web sunucusunu yapılandırın ve uygulamayı çalıştırın

## Geliştirme Aşamasında

1. Raporlama modülü
2. PDF çıktısı
3. E-posta gönderimi
4. Toplu fatura oluşturma
5. Stok takibi
6. Döviz kurları
7. Çoklu dil desteği
