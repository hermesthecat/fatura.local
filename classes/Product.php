<?php
namespace App;

class Product extends Model {
    protected string $table = 'products';
    protected int $id;
    protected array $fillable = [
        'urun_kodu',
        'urun_adi',
        'birim_fiyat',
        'kdv_orani',
        'birim',
        'stok_miktari'
    ];

    public function updateStock(int $id, float $quantity): bool {
        $sql = "UPDATE {$this->table} 
                SET stok_miktari = stok_miktari + ? 
                WHERE id = ?";
        
        $stmt = $this->db->query($sql, [$quantity, $id]);
        return $stmt->rowCount() > 0;
    }

    public function searchByName(string $search): array {
        $search = "%$search%";
        $sql = "SELECT * FROM {$this->table} 
                WHERE urun_adi LIKE ? 
                OR urun_kodu LIKE ?
                ORDER BY urun_adi";
        
        $stmt = $this->db->query($sql, [$search, $search]);
        return $stmt->fetchAll();
    }

    public function getLowStockProducts(float $threshold = 10): array {
        $sql = "SELECT * FROM {$this->table} 
                WHERE stok_miktari <= ? 
                ORDER BY stok_miktari ASC";
        
        $stmt = $this->db->query($sql, [$threshold]);
        return $stmt->fetchAll();
    }

    public function validate(array $data): array {
        $errors = [];

        if (empty($data['urun_kodu'])) {
            $errors['urun_kodu'] = 'Ürün kodu zorunludur';
        }

        if (empty($data['urun_adi'])) {
            $errors['urun_adi'] = 'Ürün adı zorunludur';
        }

        if (!isset($data['birim_fiyat']) || $data['birim_fiyat'] < 0) {
            $errors['birim_fiyat'] = 'Geçerli bir birim fiyat giriniz';
        }

        if (!isset($data['kdv_orani']) || $data['kdv_orani'] < 0 || $data['kdv_orani'] > 100) {
            $errors['kdv_orani'] = 'KDV oranı 0-100 arasında olmalıdır';
        }

        if (empty($data['birim'])) {
            $errors['birim'] = 'Birim seçimi zorunludur';
        }

        if (isset($data['stok_miktari']) && $data['stok_miktari'] < 0) {
            $errors['stok_miktari'] = 'Stok miktarı negatif olamaz';
        }

        return $errors;
    }
} 