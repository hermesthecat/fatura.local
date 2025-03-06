<?php
namespace App;

class Customer extends Model {
    protected string $table = 'customers';
    protected int $id;
    protected array $fillable = [
        'firma_adi',
        'vergi_no',
        'vergi_dairesi',
        'adres',
        'telefon',
        'email'
    ];

    public function getInvoices(): array {
        $sql = "SELECT i.* FROM invoices i 
                WHERE i.customer_id = ? 
                ORDER BY i.fatura_tarihi DESC";
        
        $stmt = $this->db->query($sql, [$this->id]);
        return $stmt->fetchAll();
    }

    public function searchByName(string $search): array {
        $search = "%$search%";
        $sql = "SELECT * FROM {$this->table} 
                WHERE firma_adi LIKE ? 
                OR vergi_no LIKE ?
                ORDER BY firma_adi";
        
        $stmt = $this->db->query($sql, [$search, $search]);
        return $stmt->fetchAll();
    }

    public function validate(array $data): array {
        $errors = [];

        if (empty($data['firma_adi'])) {
            $errors['firma_adi'] = 'Firma adı zorunludur';
        }

        if (!empty($data['vergi_no']) && !preg_match('/^[0-9]{10,11}$/', $data['vergi_no'])) {
            $errors['vergi_no'] = 'Vergi numarası 10 veya 11 haneli olmalıdır';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Geçerli bir e-posta adresi giriniz';
        }

        if (!empty($data['telefon']) && !preg_match('/^[0-9]{10,11}$/', preg_replace('/[^0-9]/', '', $data['telefon']))) {
            $errors['telefon'] = 'Geçerli bir telefon numarası giriniz';
        }

        return $errors;
    }
} 