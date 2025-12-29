<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/**
 * Repositorio para el acceso a datos de tareas en SQLite.
 */
class TaskRepository
{
    private PDO $db;

    /**
     * Inicializa la conexión con la base de datos.
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function list(int $userId, array $filters = []): array
    {
        $sql = "SELECT * FROM tasks WHERE user_id = :user_id";
        $params = ['user_id' => $userId];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['due_date'])) {
            $sql .= " AND date(due_date) = date(:due_date)";
            $params['due_date'] = $filters['due_date'];
        }

        // Ordenación
        $order = isset($filters['order']) && strtolower($filters['order']) === 'desc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY created_at $order";

        // Paginación
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 10;
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $offset = ($page - 1) * $limit;

        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        if (!empty($filters['status'])) $stmt->bindValue(':status', $filters['status'], PDO::PARAM_STR);
        if (!empty($filters['due_date'])) $stmt->bindValue(':due_date', $filters['due_date'], PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById(int $id, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :user_id");
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $task = $stmt->fetch();
        return $task ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO tasks (user_id, title, description, status, due_date) 
             VALUES (:user_id, :title, :description, :status, :due_date)"
        );
        $stmt->execute([
            'user_id' => $data['user_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'] ?? 'pendiente',
            'due_date' => $data['due_date'] ?? null
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE tasks SET 
                title = :title, 
                description = :description, 
                status = :status, 
                due_date = :due_date, 
                updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $data['status'],
            'due_date' => $data['due_date'] ?? null
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tasks WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function logAudit(int $taskId, ?string $oldStatus, string $newStatus): void
    {
        $stmt = $this->db->prepare(
            "INSERT INTO audit_logs (task_id, old_status, new_status) VALUES (:task_id, :old, :new)"
        );
        $stmt->execute([
            'task_id' => $taskId,
            'old' => $oldStatus,
            'new' => $newStatus
        ]);
    }
}
