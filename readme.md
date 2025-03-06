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
- email
- created_at
- updated_at

### 2. Ürünler (products)
- id (PK)
- urun_kodu
- urun_adi
- birim_fiyat
- kdv_orani
- birim (adet, kg, lt vs)
- stok_miktari
- created_at
- updated_at

### 3. Faturalar (invoices)
- id (PK)
- fatura_no
- customer_id (FK)
- fatura_tarihi
- vade_tarihi
- toplam_tutar
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
│   └── header.php
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── classes/
│   ├── Database.php
│   ├── Customer.php
│   ├── Product.php
│   └── Invoice.php
├── modules/
│   ├── customers/
│   ├── products/
│   └── invoices/
└── templates/
    ├── invoice_template.php
    └── pdf_template.php
```

## Temel Özellikler

### 1. Müşteri Yönetimi
- Müşteri ekleme/düzenleme/silme
- Müşteri listesi görüntüleme
- Müşteri arama

### 2. Ürün Yönetimi
- Ürün ekleme/düzenleme/silme
- Ürün listesi görüntüleme
- Stok takibi
- Ürün arama

### 3. Fatura İşlemleri
- Yeni fatura oluşturma
- Fatura düzenleme
- Fatura silme/iptal etme
- Fatura önizleme
- PDF olarak kaydetme/yazdırma
- Otomatik fatura numarası oluşturma
- KDV hesaplama
- Toplu fatura oluşturma

### 4. Raporlama
- Aylık/yıllık fatura raporları
- Müşteri bazlı raporlar
- Ürün bazlı satış raporları
- Vadesi geçmiş fatura raporları

## Kullanılan Teknolojiler

### 1. Backend
- PHP 8.x
- MySQL 8.x
- PDO için veritabanı bağlantısı
- Composer (dependency management)

### 2. Frontend
- HTML5
- CSS3 (Bootstrap 5)
- JavaScript (jQuery)
- AJAX için asenkron işlemler

### 3. Ek Kütüphaneler
- TCPDF/FPDF (PDF oluşturma)
- PHPMailer (e-posta gönderimi)
- DataTables (tablo yönetimi)
- Select2 (gelişmiş select kutuları)
- SweetAlert2 (bildirimler)

## Güvenlik Önlemleri

1. SQL Injection koruması (PDO prepared statements)
2. XSS koruması
3. CSRF token kullanımı
4. Input validasyonu
5. Oturum yönetimi
6. Kullanıcı yetkilendirme sistemi

## Kurulum Adımları

1. Veritabanı şemasının oluşturulması
2. Temel klasör yapısının kurulması
3. Composer ile gerekli paketlerin yüklenmesi
4. Veritabanı bağlantı sınıfının yazılması
5. Temel CRUD işlemlerinin oluşturulması
