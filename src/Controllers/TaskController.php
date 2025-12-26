<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\TaskService;

class TaskController
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function list(array $params = [], ?int $userId = null): void
    {
        $filters = $_GET;
        $tasks = $this->taskService->listTasks($userId, $filters);
        $this->jsonResponse($tasks);
    }

    public function get(array $params, ?int $userId = null): void
    {
        $taskId = (int)$params['id'];

        try {
            $task = $this->taskService->getTask($taskId, $userId);
            $this->jsonResponse($task);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    public function create(array $params = [], ?int $userId = null): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        $data['user_id'] = $userId;

        try {
            $task = $this->taskService->createTask($data);
            $this->jsonResponse([
                'mensaje' => 'Tarea creada exitosamente',
                'datos' => $task
            ], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function update(array $params, ?int $userId = null): void
    {
        $taskId = (int)$params['id'];
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $task = $this->taskService->updateTask($taskId, $userId, $data);
            $this->jsonResponse([
                'mensaje' => 'Tarea actualizada exitosamente',
                'datos' => $task
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(array $params, ?int $userId = null): void
    {
        $taskId = (int)$params['id'];

        try {
            $this->taskService->deleteTask($taskId, $userId);
            $this->jsonResponse(['mensaje' => 'Tarea eliminada exitosamente']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 404);
        }
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
    }
}
