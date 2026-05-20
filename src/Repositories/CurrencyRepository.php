<?php

namespace App\Repositories;

use App\Models\Currency;
use Psr\Log\LoggerInterface;

class CurrencyRepository implements CurrencyRepositoryInterface {
    public function __construct(
        private \PDO $db,
        private LoggerInterface $logger
    ) {}

    public function getAllActive(): array {
        $stmt = $this->db->query("SELECT * FROM currencies WHERE active = 1 ORDER BY is_base DESC, code ASC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Currency::class, [$this->logger]);
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT * FROM currencies ORDER BY is_base DESC, code ASC");
        return $stmt->fetchAll(\PDO::FETCH_CLASS, Currency::class, [$this->logger]);
    }

    public function findByCode(string $code): ?Currency {
        $stmt = $this->db->prepare("SELECT * FROM currencies WHERE code = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Currency::class, [$this->logger]);
        $stmt->execute([$code]);
        return $stmt->fetch() ?: null;
    }

    public function findBase(): ?Currency {
        $stmt = $this->db->query("SELECT * FROM currencies WHERE is_base = 1 LIMIT 1");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Currency::class, [$this->logger]);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?Currency {
        $stmt = $this->db->prepare("SELECT * FROM currencies WHERE id = ?");
        $stmt->setFetchMode(\PDO::FETCH_CLASS, Currency::class, [$this->logger]);
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function save(array|Currency $data, int $id = 0): int {
        $code = is_array($data) ? $data['code'] : $data->code;
        $name = is_array($data) ? $data['name'] : $data->name;
        $symbol = is_array($data) ? $data['symbol'] : $data->symbol;
        $rate = is_array($data) ? (float)$data['exchange_rate'] : (float)$data->exchange_rate;
        $is_base = is_array($data) ? (int)($data['is_base'] ?? 0) : (int)$data->is_base;
        $active = is_array($data) ? (int)($data['active'] ?? 1) : (int)$data->active;

        if ($is_base) {
            $this->db->query("UPDATE currencies SET is_base = 0");
        }

        if ($id) {
            $stmt = $this->db->prepare(
                "UPDATE currencies SET code = ?, name = ?, symbol = ?, exchange_rate = ?, is_base = ?, active = ?, updated_at = ? WHERE id = ?"
            );
            $stmt->execute([$code, $name, $symbol, $rate, $is_base, $active, date('Y-m-d H:i:s'), $id]);
            return $id;
        } else {
            $stmt = $this->db->prepare(
                "INSERT INTO currencies (code, name, symbol, exchange_rate, is_base, active) VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$code, $name, $symbol, $rate, $is_base, $active]);
            return (int)$this->db->lastInsertId();
        }
    }
}
