<?php
namespace App\Repositories;

class ScheduledTaskRepository implements ScheduledTaskRepositoryInterface {
    public function __construct(private \PDO $db) {}

    public function exists(string $name): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM scheduled_tasks WHERE name = ?");
        $stmt->execute([$name]);
        return (bool)$stmt->fetchColumn();
    }

    public function getLastRunAt(string $name): ?string {
        $stmt = $this->db->prepare("SELECT last_run_at FROM scheduled_tasks WHERE name = ?");
        $stmt->execute([$name]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row['last_run_at'] ?? null;
    }

    public function createTask(string $name): void {
        $this->db->prepare("INSERT INTO scheduled_tasks (name) VALUES (?)")->execute([$name]);
    }

    public function updateLastRun(string $name, string $lastRunAt): void {
        $stmt = $this->db->prepare("UPDATE scheduled_tasks SET last_run_at = ? WHERE name = ?");
        $stmt->execute([$lastRunAt, $name]);
    }
}
