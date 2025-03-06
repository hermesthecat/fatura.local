<?php
namespace App;

class Invoice extends Model {
    protected string $table = 'invoices';
    protected int $id;
    protected array $fillable = [
        'fatura_no',
        'customer_id',
        'fatura_tarihi',
        'vade_tarihi',
        'toplam_tutar',
        'kdv_toplam',
        'genel_toplam',
        'aciklama',
        'durum'
    ];

    public function getItems(): array {
        $sql = "SELECT i.*, p.urun_kodu, p.urun_adi, p.birim 
                FROM invoice_items i
                JOIN products p ON p.id = i.product_id
                WHERE i.invoice_id = ?
                ORDER BY i.id ASC";
        
        $stmt = $this->db->query($sql, [$this->id]);
        return $stmt->fetchAll();
    }

    public function addItem(array $item): int {
        try {
            $this->db->beginTransaction();

            // Ürün stoğunu güncelle
            $product = new Product();
            $product->updateStock($item['product_id'], -$item['miktar']);

            // Fatura kalemini ekle
            $sql = "INSERT INTO invoice_items (
                        invoice_id, product_id, miktar, birim_fiyat,
                        kdv_orani, kdv_tutari, ara_toplam, toplam_tutar
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $this->id,
                $item['product_id'],
                $item['miktar'],
                $item['birim_fiyat'],
                $item['kdv_orani'],
                $item['kdv_tutari'],
                $item['ara_toplam'],
                $item['toplam_tutar']
            ];

            $stmt = $this->db->query($sql, $params);
            $itemId = (int) $this->db->lastInsertId();

            // Fatura toplamlarını güncelle
            $this->updateTotals();

            $this->db->commit();
            return $itemId;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function removeItem(int $itemId): bool {
        try {
            $this->db->beginTransaction();

            // Ürün miktarını stoka geri ekle
            $sql = "SELECT product_id, miktar FROM invoice_items WHERE id = ?";
            $stmt = $this->db->query($sql, [$itemId]);
            $item = $stmt->fetch();

            if ($item) {
                $product = new Product();
                $product->updateStock($item['product_id'], $item['miktar']);

                // Fatura kalemini sil
                $sql = "DELETE FROM invoice_items WHERE id = ?";
                $this->db->query($sql, [$itemId]);

                // Fatura toplamlarını güncelle
                $this->updateTotals();

                $this->db->commit();
                return true;
            }

            $this->db->rollBack();
            return false;

        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function updateTotals(): void {
        $sql = "UPDATE invoices i SET
                i.toplam_tutar = (
                    SELECT COALESCE(SUM(ara_toplam), 0)
                    FROM invoice_items
                    WHERE invoice_id = i.id
                ),
                i.kdv_toplam = (
                    SELECT COALESCE(SUM(kdv_tutari), 0)
                    FROM invoice_items
                    WHERE invoice_id = i.id
                ),
                i.genel_toplam = (
                    SELECT COALESCE(SUM(toplam_tutar), 0)
                    FROM invoice_items
                    WHERE invoice_id = i.id
                )
                WHERE i.id = ?";

        $this->db->query($sql, [$this->id]);
    }

    public function generateInvoiceNumber(): string {
        $year = date('Y');
        $month = date('m');
        
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
        
        $stmt = $this->db->query($sql, [$year, $month]);
        $result = $stmt->fetch();
        
        $sequence = str_pad($result['count'] + 1, 4, '0', STR_PAD_LEFT);
        return $year . $month . $sequence;
    }

    public function validate(array $data): array {
        $errors = [];

        if (empty($data['customer_id'])) {
            $errors['customer_id'] = 'Müşteri seçimi zorunludur';
        }

        if (empty($data['fatura_tarihi'])) {
            $errors['fatura_tarihi'] = 'Fatura tarihi zorunludur';
        } elseif (!strtotime($data['fatura_tarihi'])) {
            $errors['fatura_tarihi'] = 'Geçerli bir fatura tarihi giriniz';
        }

        if (!empty($data['vade_tarihi']) && !strtotime($data['vade_tarihi'])) {
            $errors['vade_tarihi'] = 'Geçerli bir vade tarihi giriniz';
        }

        if (!empty($data['vade_tarihi']) && strtotime($data['vade_tarihi']) < strtotime($data['fatura_tarihi'])) {
            $errors['vade_tarihi'] = 'Vade tarihi fatura tarihinden önce olamaz';
        }

        return $errors;
    }

    public function getOverdueInvoices(): array {
        $sql = "SELECT i.*, c.firma_adi 
                FROM {$this->table} i
                JOIN customers c ON c.id = i.customer_id
                WHERE i.vade_tarihi < CURDATE()
                AND i.durum = 'onaylandı'
                ORDER BY i.vade_tarihi ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
} 