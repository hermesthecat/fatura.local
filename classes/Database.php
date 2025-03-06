<?php
namespace App;

use PDO;
use PDOException;

class Database {
    private static ?Database $instance = null;
    public PDO $connection;
    private array $config;

    private function __construct() {
        $this->config = require_once __DIR__ . '/../config/database.php';
        $this->connect();
    }

    private function connect(): void {
        $dsn = "mysql:host={$this->config['host']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
        
        try {
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            throw new PDOException("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO {
        return $this->connection;
    }

    public function query(string $sql, array $params = []): \PDOStatement {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function lastInsertId(): string {
        return $this->connection->lastInsertId();
    }

    public function beginTransaction(): bool {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool {
        return $this->connection->commit();
    }

    public function rollBack(): bool {
        return $this->connection->rollBack();
    }

    // Singleton pattern için clone ve unserialize metodlarını engelleyelim
    private function __clone() {}
    private function __wakeup() {}
} 