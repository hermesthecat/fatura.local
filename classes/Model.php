<?php
namespace App;

abstract class Model {
    protected Database $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->query(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
        return $stmt->fetch() ?: null;
    }

    public function all(): array {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    public function create(array $data): int {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        
        $columns = implode(', ', array_keys($fields));
        $values = implode(', ', array_fill(0, count($fields), '?'));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
        
        $this->db->query($sql, array_values($fields));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        
        $setClause = implode(
            ', ',
            array_map(fn($field) => "$field = ?", array_keys($fields))
        );
        
        $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = ?";
        
        $values = array_merge(array_values($fields), [$id]);
        $stmt = $this->db->query($sql, $values);
        
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->query($sql, [$id]);
        return $stmt->rowCount() > 0;
    }

    public function where(string $column, string $operator, $value): array {
        $sql = "SELECT * FROM {$this->table} WHERE $column $operator ?";
        $stmt = $this->db->query($sql, [$value]);
        return $stmt->fetchAll();
    }

    public function findBy(string $column, $value): ?array {
        $stmt = $this->db->query(
            "SELECT * FROM {$this->table} WHERE $column = ?",
            [$value]
        );
        return $stmt->fetch() ?: null;
    }
} 