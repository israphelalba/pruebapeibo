<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Repositories\TaskRepository;
use App\Services\TaskService;
use PDO;

class TaskIntegrationTest extends TestCase
{
    private PDO $db;
    private TaskRepository $repository;
    private TaskService $service;

    protected function setUp(): void
    {
        // Usar base de datos en memoria para los tests
        $this->db = new PDO("sqlite::memory:");
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear tablas necesarias
        $this->db->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, password TEXT)");
        $this->db->exec("CREATE TABLE tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            title TEXT,
            description TEXT,
            status TEXT,
            due_date DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $this->db->exec("CREATE TABLE audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            task_id INTEGER,
            old_status TEXT,
            new_status TEXT,
            changed_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->repository = new TaskRepository($this->db);
        $this->service = new TaskService($this->repository);
    }

    public function testCompleteFlowAndPagination()
    {
        $userId = 1;
        
        // Crear 15 tareas
        for ($i = 1; $i <= 15; $i++) {
            $this->service->createTask([
                'user_id' => $userId,
                'title' => "Tarea $i",
                'status' => 'pendiente'
            ]);
        }

        // Probar paginación (Página 1, Límite 10)
        $tasksP1 = $this->service->listTasks($userId, ['limit' => 10, 'page' => 1]);
        $this->assertCount(10, $tasksP1);
        $this->assertEquals('Tarea 1', $tasksP1[0]['title']);

        // Probar paginación (Página 2, Límite 10)
        $tasksP2 = $this->service->listTasks($userId, ['limit' => 10, 'page' => 2]);
        $this->assertCount(5, $tasksP2);
        $this->assertEquals('Tarea 11', $tasksP2[0]['title']);
    }

    public function testAuditLogIntegration()
    {
        $userId = 1;
        $task = $this->service->createTask([
            'user_id' => $userId,
            'title' => "Tarea Auditoría",
            'status' => 'pendiente'
        ]);

        $this->service->updateTask((int)$task['id'], $userId, ['status' => 'en progreso']);

        $stmt = $this->db->query("SELECT * FROM audit_logs WHERE task_id = " . $task['id']);
        $logs = $stmt->fetchAll();

        // Debe haber 2 logs: uno inicial (null -> pendiente) y uno de actualización (pendiente -> en progreso)
        $this->assertCount(2, $logs);
        $this->assertEquals('pendiente', $logs[1]['old_status']);
        $this->assertEquals('en progreso', $logs[1]['new_status']);
    }
}
