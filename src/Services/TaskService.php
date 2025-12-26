<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TaskRepository;
use Exception;

/**
 * Servicio para la lógica de negocio relacionada con las tareas.
 */
class TaskService
{
    private TaskRepository $repository;

    /**
     * Constructor con inyección de dependencias.
     */
    public function __construct(TaskRepository $repository)
    {
        $this->repository = $repository;
    }

    public function listTasks(int $userId, array $filters): array
    {
        return $this->repository->list($userId, $filters);
    }

    public function getTask(int $id, int $userId): array
    {
        $task = $this->repository->findById($id, $userId);
        if (!$task) {
            throw new Exception("Tarea no encontrada o no pertenece al usuario.");
        }
        return $task;
    }

    public function createTask(array $data): array
    {
        $this->validateTaskData($data);
        
        $taskId = $this->repository->create($data);
        
        // Auditoría inicial
        $this->repository->logAudit($taskId, null, $data['status'] ?? 'pendiente');
        
        return $this->getTask($taskId, (int)$data['user_id']);
    }

    public function updateTask(int $id, int $userId, array $data): array
    {
        $oldTask = $this->getTask($id, $userId);
        
        // Mezclar datos antiguos con nuevos para actualización parcial o completa
        $updatedData = array_merge($oldTask, $data);
        $this->validateTaskData($updatedData);

        $this->repository->update($id, $updatedData);

        // Auditoría si cambió el estado
        if ($oldTask['status'] !== $updatedData['status']) {
            $this->repository->logAudit($id, $oldTask['status'], $updatedData['status']);
        }

        return $this->getTask($id, $userId);
    }

    public function deleteTask(int $id, int $userId): void
    {
        $this->getTask($id, $userId); // Verificar existencia y pertenencia
        $this->repository->delete($id);
    }

    private function validateTaskData(array $data): void
    {
        if (empty($data['title'])) {
            throw new Exception("El título de la tarea es obligatorio.");
        }

        $allowedStatuses = ['pendiente', 'en progreso', 'completada'];
        if (isset($data['status']) && !in_array($data['status'], $allowedStatuses)) {
            throw new Exception("Estado inválido. Debe ser: " . implode(', ', $allowedStatuses));
        }

        if (isset($data['due_date']) && !empty($data['due_date'])) {
            if (!strtotime($data['due_date'])) {
                throw new Exception("Formato de fecha de vencimiento inválido.");
            }
        }
    }
}
