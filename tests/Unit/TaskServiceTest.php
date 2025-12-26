<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\TaskService;
use App\Repositories\TaskRepository;
use Exception;

class TaskServiceTest extends TestCase
{
    private $taskRepositoryMock;
    private TaskService $taskService;

    protected function setUp(): void
    {
        $this->taskRepositoryMock = $this->createMock(TaskRepository::class);
        $this->taskService = new TaskService($this->taskRepositoryMock);
    }

    public function testCreateTaskSuccess()
    {
        $data = [
            'user_id' => 1,
            'title' => 'Nueva Tarea',
            'status' => 'pendiente'
        ];

        $this->taskRepositoryMock->method('create')->willReturn(1);
        $this->taskRepositoryMock->method('findById')->willReturn([
            'id' => 1,
            'user_id' => 1,
            'title' => 'Nueva Tarea',
            'status' => 'pendiente'
        ]);

        $result = $this->taskService->createTask($data);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Nueva Tarea', $result['title']);
    }

    public function testCreateTaskWithoutTitleThrowsException()
    {
        $data = [
            'user_id' => 1,
            'status' => 'pendiente'
            // 'title' falte
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("El título de la tarea es obligatorio.");

        $this->taskService->createTask($data);
    }

    public function testUpdateTaskStatusAudit()
    {
        $userId = 1;
        $taskId = 1;
        $oldData = [
            'id' => $taskId,
            'user_id' => $userId,
            'title' => 'Tarea',
            'status' => 'pendiente'
        ];
        $newData = ['status' => 'en progreso'];

        $this->taskRepositoryMock->expects($this->exactly(2))
            ->method('findById')
            ->willReturnOnConsecutiveCalls($oldData, array_merge($oldData, $newData));
        
        $this->taskRepositoryMock->expects($this->once())
            ->method('logAudit')
            ->with($taskId, 'pendiente', 'en progreso');

        $this->taskService->updateTask($taskId, $userId, $newData);
    }
}
